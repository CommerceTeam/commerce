<?php
namespace CommerceTeam\Commerce\Utility;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Domain\Repository\BackendUsergroupRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A metaclass for creating inputfield fields in the backend.
 *
 * Class \CommerceTeam\Commerce\Utility\BackendUserUtility
 */
class BackendUserUtility implements SingletonInterface
{
    /**
     * Returns a combined binary representation of the current users
     * permissions for the page-record, $row. The perms for user, group
     * and everybody is OR'ed together (provided that the page-owner is
     * the user and for the groups that the user is a member of the group.
     * If the user is admin, 31 is returned.
     * (full permissions for all five flags).
     *
     * @param array $row Input page row with all perms_* fields available.
     *
     * @return int Bitwise representation of the users permissions in
     *      relation to input page row, $row
     */
    public function calcPerms(array $row)
    {
        $backendUser = $this->getBackendUser();
        // Return 31 for admin users.
        if ($backendUser->isAdmin()) {
            return 31;
        }
        // Return 0 if page is not within the allowed web mount
        if (!$this->isInWebMount($row['uid'])) {
            return 0;
        }
        $out = 0;
        if (isset($row['perms_userid']) && isset($row['perms_user'])
            && isset($row['perms_groupid']) && isset($row['perms_group'])
            && isset($row['perms_everybody']) && isset($backendUser->groupList)
        ) {
            /** @noinspection PhpInternalEntityUsedInspection */
            if ($backendUser->user['uid'] == $row['perms_userid']) {
                $out |= $row['perms_user'];
            }
            if ($backendUser->isMemberOfGroup($row['perms_groupid'])) {
                $out |= $row['perms_group'];
            }
            $out |= $row['perms_everybody'];
        }

        return $out;
    }

    /**
     * Checks if the category id, $id, is found within the webmounts set up for
     * the user. This should ALWAYS be checked for any page id a user works
     * with, whether it's about reading, writing or whatever. The point is
     * that this will add the security that a user can NEVER touch parts
     * outside his mounted pages in the page tree. This is otherwise possible
     * if the raw page permissions allows for it.
     * So this security check just makes it easier to make safe user
     * configurations.
     * If the user is admin OR if this feature is disabled (fx. by setting
     * TYPO3_CONF_VARS['BE']['lockBeUserToDBmounts']=0) then it returns "1"
     * right away. Otherwise the function will return the uid of the webmount
     * which was first found in the rootline of the input page $id.
     *
     * @param int $id Category ID to check
     * @param string $readPerms Content of "getCategoryPermsClause(Permission::PAGE_SHOW)"
     *      (read-permissions) If not set, they will be internally calculated
     *      (but if you have the correct value right away you can save that
     *      database lookup!)
     * @param bool|int $exitOnError If set, then the function will exit with
     *      an error message.
     *
     * @return int|NULL The page UID in the rootline that matched a mount point
     * @throws \RuntimeException If page is not in database mount
     */
    public function isInWebMount($id, $readPerms = '', $exitOnError = 0)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] || $this->getBackendUser()->isAdmin()) {
            return 1;
        }
        $id = (int) $id;
        // Check if input id is an offline version page
        // in which case we will map id to the online version:
        $checkRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord(
            'tx_commerce_categories',
            $id,
            'pid,t3ver_oid'
        );
        if ($checkRec['pid'] == -1) {
            $id = (int) $checkRec['t3ver_oid'];
        }
        if (!$readPerms) {
            $readPerms = $this->getCategoryPermsClause(Permission::PAGE_SHOW);
        }
        if ($id > 0) {
            $wM = $this->returnWebmounts();
            $rL = BackendUtility::BEgetRootLine($id, $readPerms);
            foreach ($rL as $v) {
                if ($v['uid'] && in_array($v['uid'], $wM)) {
                    return $v['uid'];
                }
            }
        }
        if ($exitOnError) {
            throw new \RuntimeException('Access Error: This page is not within your DB-mounts', 1294586445);
        }

        return null;
    }

    /**
     * Returns a WHERE-clause for the pages-table where user permissions
     * according to input argument, $perms, is validated.
     * $perms is the "mask" used to select. Fx. if $perms is 1 then
     * you'll get all pages that a user can actually see!
     *     2^0 = show (1)
     *     2^1 = edit (2)
     *     2^2 = delete (4)
     *     2^3 = new (8)
     * If the user is 'admin' " 1=1" is returned (no effect)
     * If the user is not set at all (->user is not an array),
     * then " 1=0" is returned (will cause no selection results at all)
     * The 95% use of this function is "->getCategoryPermsClause(Permission::PAGE_SHOW)" which will
     * return WHERE clauses for *selecting* pages in backend listings
     * - in other words this will check read permissions.
     *
     * @param int $perms Permission mask to use, see function description
     *
     * @return string Part of where clause. Prefix " AND " to this.
     */
    public function getCategoryPermsClause($perms)
    {
        /** @noinspection PhpInternalEntityUsedInspection */
        if (is_array($this->getBackendUser()->user)) {
            $backenduser = $this->getBackendUser();
            if ($backenduser->isAdmin()) {
                return ' 1=1';
            }
            $perms = (int) $perms;
            // Make sure it's int.
            /** @noinspection PhpInternalEntityUsedInspection */
            $str = ' ( (tx_commerce_categories.perms_everybody & ' . $perms . ' = ' . $perms .
                ') OR (tx_commerce_categories.perms_userid = ' . $backenduser->user['uid'] .
                ' AND tx_commerce_categories.perms_user & ' . $perms . ' = ' . $perms . ')';

            // User
            if ($backenduser->groupList) {
                // Group (if any is set)
                $str .= ' OR (tx_commerce_categories.perms_groupid in (' . $backenduser->groupList .
                    ') AND tx_commerce_categories.perms_group & ' . $perms . ' = ' . $perms . ')';
            }
            $str .= ')';

            return $str;
        }

        return ' 1=0';
    }

    /**
     * Returns an array with the webmounts.
     * If no webmounts, and empty array is returned.
     * NOTICE: Deleted tx_commerce_categories WILL NOT be filtered out!
     * So if a mounted page has been deleted it is STILL coming out as
     * a webmount.
     * This is not checked due to performance.
     *
     * @return array
     */
    public function returnWebmounts()
    {
        if ($this->getBackendUser()->isAdmin()) {
            return [ 0 ];
        }

        $mountPoints = [];
        if (!empty($this->getBackendUser()->groupList)) {
            /** @var BackendUsergroupRepository $backendUsergroupRepository */
            $backendUsergroupRepository = GeneralUtility::makeInstance(BackendUsergroupRepository::class);
            $groups = $backendUsergroupRepository->findByGroupList(
                GeneralUtility::intExplode(',', $this->getBackendUser()->groupList, true)
            );

            foreach ($groups as $group) {
                $mount = current($group);
                if (!empty($mount)) {
                    $mountPoints = array_merge($mountPoints, GeneralUtility::trimExplode(',', $mount));
                }
            }
        }

        $mountPoints = array_unique($mountPoints);

        return $mountPoints;
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

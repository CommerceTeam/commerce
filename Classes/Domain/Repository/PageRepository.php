<?php
namespace CommerceTeam\Commerce\Domain\Repository;

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

/**
 * Class \CommerceTeam\Commerce\Domain\Repository\PageRepository
 */
class PageRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'pages';

    /**
     * Find folder by uid that is editable.
     *
     * @param int $uid Page uid
     *
     * @return array
     */
    public function findEditFolderByUid($uid)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'tx_commerce_foldereditorder',
            $this->databaseTable,
            'tx_commerce_foldereditorder = 1 AND uid = ' . (int) $uid
        );
        $row = is_array($row) ? $row : [];
        return $row;
    }

    /**
     * Find all sys language uids of page overlay for given page
     *
     * @param int $pageUid
     *
     * @return array
     */
    public function findLanguageUidsByUid($pageUid)
    {
        $locale = array_keys(
            (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
                'sys_language_uid',
                'pages_language_overlay',
                'pid = ' . (int) $pageUid,
                '',
                '',
                '',
                'sys_language_uid'
            )
        );

        return $locale;
    }
}

<?php
namespace CommerceTeam\Commerce\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class extends the commerce module in the TYPO3 Backend to provide
 * convenient methods of editing of category permissions
 * (including category ownership (user and group)) via new
 * \TYPO3\CMS\Core\Http\AjaxRequestHandler facility.
 *
 * Class \CommerceTeam\Commerce\Controller\PermissionAjaxController
 *
 * @author 2007-2008 mehrwert <typo3@mehrwert.de>
 */
class PermissionAjaxController extends \TYPO3\CMS\Beuser\Controller\PermissionAjaxController
{
    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param array $_ Parameters from the AJAX interface
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Ajax object
     *
     * @return void
     */
    public function dispatch(array $_ = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = null)
    {
        $content = '';

        // Basic test for required value
        if ($this->conf['page'] > 0) {
            // Init TCE for execution of update
            /**
             * Data handler.
             *
             * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
             */
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->stripslashes_values = 1;

            // Determine the scripts to execute
            switch ($this->conf['action']) {
                case 'show_change_owner_selector':
                    // Return the select to change the owner (BE user) of the page
                    $content = $this->renderUserSelector(
                        (int) $this->conf['page'],
                        (int) $this->conf['ownerUid'],
                        $this->conf['username']
                    );
                    break;

                case 'change_owner':
                    // Change the owner and return the new owner HTML snippet
                    if (is_int($this->conf['new_owner_uid'])) {
                        // Prepare data to change
                        $data = array();
                        $data['tx_commerce_categories'][$this->conf['page']]['perms_userid'] =
                            $this->conf['new_owner_uid'];

                        // Execute TCE Update
                        $tce->start($data, array());
                        $tce->process_datamap();
                        $content = $this->renderOwnername(
                            (int) $this->conf['page'],
                            (int) $this->conf['new_owner_uid'],
                            $this->conf['new_owner_username']
                        );
                    } else {
                        $ajaxObj->setError('An error occured: No page owner uid specified.');
                    }
                    break;

                case 'show_change_group_selector':
                    // Return the select to change the group (BE group) of the page
                    $content = $this->renderGroupSelector(
                        (int) $this->conf['page'],
                        (int) $this->conf['groupUid'],
                        $this->conf['groupname']
                    );
                    break;

                case 'change_group':
                    // Change the group and return the new group HTML snippet
                    if (is_int($this->conf['new_group_uid'])) {
                        // Prepare data to change
                        $data = array();
                        $data['tx_commerce_categories'][$this->conf['page']]['perms_groupid'] =
                            $this->conf['new_group_uid'];

                        // Execute TCE Update
                        $tce->start($data, array());
                        $tce->process_datamap();

                        $content = $this->renderGroupname(
                            (int) $this->conf['page'],
                            (int) $this->conf['new_group_uid'],
                            $this->conf['new_group_username']
                        );
                    } else {
                        $ajaxObj->setError('An error occured: No page group uid specified.');
                    }
                    break;

                case 'toggle_edit_lock':
                    // Prepare data to change
                    $data = array();
                    $data['tx_commerce_categories'][$this->conf['page']]['editlock'] =
                        ($this->conf['editLockState'] === 1 ? 0 : 1);

                    // Execute TCE Update
                    $tce->start($data, array());
                    $tce->process_datamap();

                    $content = $this->renderToggleEditLock(
                        (int) $this->conf['page'],
                        $data['tx_commerce_categories'][$this->conf['page']]['editlock']
                    );
                    break;

                default:
                    // The script defaults to change permissions
                    if ($this->conf['mode'] == 'delete') {
                        $this->conf['permissions'] = (int) ($this->conf['permissions'] - $this->conf['bits']);
                    } else {
                        $this->conf['permissions'] = (int) ($this->conf['permissions'] + $this->conf['bits']);
                    }

                    // Prepare data to change
                    $data = array();
                    $data['tx_commerce_categories'][$this->conf['page']]['perms_' . $this->conf['who']] =
                        $this->conf['permissions'];

                    // Execute TCE Update
                    $tce->start($data, array());
                    $tce->process_datamap();

                    $content = $this->renderPermissions(
                        (int) $this->conf['permissions'],
                        $this->conf['page'],
                        $this->conf['who']
                    );
            }
        } else {
            $ajaxObj->setError('This script cannot be called directly.');
        }
        $ajaxObj->addContent($this->conf['page'] . '_' . $this->conf['who'], $content);
    }
}

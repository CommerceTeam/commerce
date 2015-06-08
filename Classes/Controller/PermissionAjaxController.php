<?php
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

/**
 * This class extends the commerce module in the TYPO3 Backend to provide
 * convenient methods of editing of category permissions
 * (including category ownership (user and group)) via new TYPO3AJAX facility
 *
 * Class Tx_Commerce_Controller_PermissionAjaxController
 *
 * @author 2007-2008 mehrwert <typo3@mehrwert.de>
 */
class Tx_Commerce_Controller_PermissionAjaxController extends \TYPO3\CMS\Perm\Controller\PermissionAjaxController {
	/**
	 * The main dispatcher function. Collect data and prepare HTML output.
	 *
	 * @param array $params Parameters from the AJAX interface
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Ajax object
	 *
	 * @return void
	 */
	public function dispatch(array $params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$content = '';

			// Basic test for required value
		if ($this->conf['page'] > 0) {
			// Init TCE for execution of update
			/**
			 * Data handler
			 *
			 * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
			 */
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 1;

				// Determine the scripts to execute
			switch ($this->conf['action']) {

					// Return the select to change the owner (BE user) of the page
				case 'show_change_owner_selector':
					$content = $this->renderUserSelector($this->conf['page'], $this->conf['ownerUid'], $this->conf['username']);
					break;

					// Change the owner and return the new owner HTML snippet
				case 'change_owner':
					if (is_int($this->conf['new_owner_uid'])) {
							// Prepare data to change
						$data = array();
						$data['tx_commerce_categories'][$this->conf['page']]['perms_userid'] = $this->conf['new_owner_uid'];

							// Execute TCE Update
						$tce->start($data, array());
						$tce->process_datamap();
						$content = $this->renderOwnername($this->conf['page'], $this->conf['new_owner_uid'], $this->conf['new_owner_username']);
					} else {
						$ajaxObj->setError('An error occured: No page owner uid specified.');
					}
					break;

					// Return the select to change the group (BE group) of the page
				case 'show_change_group_selector':
					$content = $this->renderGroupSelector($this->conf['page'], $this->conf['groupUid'], $this->conf['groupname']);
					break;

					// Change the group and return the new group HTML snippet
				case 'change_group':
					if (is_int($this->conf['new_group_uid'])) {
							// Prepare data to change
						$data = array();
						$data['tx_commerce_categories'][$this->conf['page']]['perms_groupid'] = $this->conf['new_group_uid'];

							// Execute TCE Update
						$tce->start($data, array());
						$tce->process_datamap();

						$content = $this->renderGroupname($this->conf['page'], $this->conf['new_group_uid'], $this->conf['new_group_username']);
					} else {
						$ajaxObj->setError('An error occured: No page group uid specified.');
					}
					break;

				case 'toggle_edit_lock':
					// Prepare data to change
					$data = array();
					$data['tx_commerce_categories'][$this->conf['page']]['editlock'] = ($this->conf['editLockState'] === 1 ? 0 : 1);

						// Execute TCE Update
					$tce->start($data, array());
					$tce->process_datamap();

					$content = $this->renderToggleEditLock(
						$this->conf['page'],
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
					$data['tx_commerce_categories'][$this->conf['page']]['perms_' . $this->conf['who']] = $this->conf['permissions'];

						// Execute TCE Update
					$tce->start($data, array());
					$tce->process_datamap();

					$content = $this->renderPermissions($this->conf['permissions'], $this->conf['page'], $this->conf['who']);
			}
		} else {
			$ajaxObj->setError('This script cannot be called directly.');
		}
		$ajaxObj->addContent($this->conf['page'] . '_' . $this->conf['who'], $content);
	}
}

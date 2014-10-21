<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2008 mehrwert <typo3@mehrwert.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This class extends the commerce module in the TYPO3 Backend to provide
 * convenient methods of editing of category permissions
 * (including category ownership (user and group)) via new TYPO3AJAX facility
 */
	// require_once in 4.x needed because in ajax mod the class can't get autoloaded
/** @noinspection PhpIncludeInspection */
require_once(PATH_typo3 . 'sysext/perm/mod1/class.sc_mod_web_perm_ajax.php');

/**
 * Class Tx_Commerce_Controller_PermissionAjaxController
 */
class Tx_Commerce_Controller_PermissionAjaxController extends SC_mod_web_perm_ajax {
	/**
	 * The main dispatcher function. Collect data and prepare HTML output.
	 *
	 * @param array $params array of parameters from the AJAX interface
	 * @param TYPO3AJAX &$ajaxObj object of type TYPO3AJAX
	 * @return Void
	 */
	public function dispatch($params = array(), TYPO3AJAX &$ajaxObj = NULL) {
		$content = '';

			// Basic test for required value
		if ($this->conf['page'] > 0) {

				// Init TCE for execution of update
			/** @var t3lib_TCEmain $tce */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
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

					$content = $this->renderToggleEditLock($this->conf['page'], $data['tx_commerce_categories'][$this->conf['page']]['editlock']);
					break;

					// The script defaults to change permissions
				default:
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

class_alias('Tx_Commerce_Controller_PermissionAjaxController', 'SC_mod_access_perm_ajax');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/PermissionAjaxController.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/PermissionAjaxController.php']);
}

?>
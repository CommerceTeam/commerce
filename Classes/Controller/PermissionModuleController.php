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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Perm\Controller\PermissionAjaxController;

/**
 * Module: Permission setting
 *
 * Script Class for the Web > Access module
 * This module lets you view and change permissions for pages.
 *
 * Variables:
 * $this->MOD_SETTINGS['depth']: int 1-3: decides the depth of the list
 * $this->MOD_SETTINGS['mode']: 'perms' / '': decides if we view a user-overview
 * 		or the permissions.
 *
 * Class Tx_Commerce_Controller_PermissionModuleController
 *
 * @author 2008-2012 Erik Frister <typo3@marketing-factory.de>
 */
class Tx_Commerce_Controller_PermissionModuleController extends \TYPO3\CMS\Perm\Controller\PermissionModuleController {
	/**
	 * @var int
	 */
	protected $categoryUid = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_perm.xlf');
		$this->getLanguageService()->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_access.xml');
		$this->getBackendUser()->modAccess($GLOBALS['MCONF'], TRUE);
	}

	/**
	 * Initialization of the class
	 *
	 * @return void
	 */
	public function init() {
		// Setting GPvars:
		$this->id = (int) GeneralUtility::_GP('id');
		if (!$this->id) {
			Tx_Commerce_Utility_FolderUtility::initFolders();
			$this->id =
				current(array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'Commerce', 0, 'Commerce')));
		}

		$this->edit = GeneralUtility::_GP('edit');
		$this->return_id = GeneralUtility::_GP('return_id');
		$this->lastEdited = GeneralUtility::_GP('lastEdited');

		// Setting GPvars:
		$controlParams = GeneralUtility::_GP('control');
		if ($controlParams) {
			$controlArray = current($controlParams);
			$this->categoryUid = (int) $controlArray['uid'];
		}

		// Module name;
		$this->MCONF = $GLOBALS['MCONF'];
		// Page select clause:
		$this->perms_clause = Tx_Commerce_Utility_BackendUtility::getCategoryPermsClause(1);

		$this->initPage();

		// Set up menus:
		$this->menuConfig();
	}

	/**
	 * Initializing document template object
	 *
	 * @return void
	 */
	public function initPage() {
		/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate $doc */
		$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$doc->backPath = $GLOBALS['BACK_PATH'];
		$doc->setModuleTemplate('EXT:perm/Resources/Private/Templates/perm.html');
		$doc->form = '<form action="' . $GLOBALS['BACK_PATH'] . 'tce_db.php" method="post" name="editform">';
		$doc->loadJavascriptLib('js/jsfunc.updateform.js');
		$doc->getPageRenderer()->loadPrototype();
		$doc->loadJavascriptLib(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('perm') . 'mod1/perm.js');
		// Setting up the context sensitive menu:
		$doc->getContextMenuCode();

		// override attributes of WebPermissions found in sysext/perm/mod1/perm.js
		$doc->JScode .= $doc->wrapScriptTags('
			WebPermissions.thisScript = TYPO3.settings.ajaxUrls["Tx_Commerce_Controller_PermissionAjaxController::dispatch"];
		');

		$this->doc = $doc;
	}

	/**
	 * Main function, creating the content for the access editing forms/listings
	 *
	 * @return void
	 */
	public function main() {
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();

		// Access check...
		// The page will show only if there is a valid page and if this page
		// may be viewed by the user
		if ($this->categoryUid) {
			$this->pageinfo = Tx_Commerce_Utility_BackendUtility::readCategoryAccess($this->categoryUid, $this->perms_clause);
		} else {
			$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		}
		$access = is_array($this->pageinfo);

		// Checking access:
		if ($this->categoryUid && $access || $backendUser->isAdmin() && !$this->categoryUid) {
			if ($backendUser->isAdmin() && !$this->categoryUid) {
				$this->pageinfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
			}
			// This decides if the editform (tceAction) can and will be drawn:
			$this->editingAllowed = ($this->pageinfo['perms_userid'] == $backendUser->user['uid'] || $backendUser->isAdmin());
			$this->edit = $this->edit && $this->editingAllowed;
			// If $this->edit then these functions are called in the end of the page...
			if ($this->edit) {
				$this->doc->postCode .= $this->doc->wrapScriptTags('
					setCheck("check[perms_user]", "data[tx_commerce_categories][' . $this->categoryUid . '][perms_user]");
					setCheck("check[perms_group]", "data[tx_commerce_categories][' . $this->categoryUid . '][perms_group]");
					setCheck("check[perms_everybody]", "data[tx_commerce_categories][' . $this->categoryUid . '][perms_everybody]");
				');
			}

				// Draw the HTML page header.
			$this->content .= $this->doc->header($language->getLL('permissions') . ($this->edit ? ': ' . $language->getLL('Edit') : ''));
			$vContent = $this->doc->getVersionSelector($this->categoryUid, 1);
			if ($vContent) {
				$this->content .= $this->doc->section('', $vContent);
			}

			// Main function, branching out:
			if (!$this->edit) {
				$this->notEdit();
			} else {
				$this->doEdit();
			}

			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = BackendUtility::getFuncMenu(
				$this->id,
				'SET[mode]',
				$this->MOD_SETTINGS['mode'],
				$this->MOD_MENU['mode']
			);
			$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
			$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		} else {
				// If no access or if ID == zero
			$this->content .= $this->doc->header($language->getLL('permissions'));
		}

		// Renders the module page
		$this->content = $this->doc->render($language->getLL('permissions'), $this->content);
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise
	 * perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'view' => '',
			'shortcut' => ''
		);

		// CSH
		$buttons['csh'] = BackendUtility::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', TRUE);
		// View page
		$buttons['view'] = '<a href="#" onclick="' .
			htmlspecialchars(BackendUtility::viewonclick(
				$this->id,
				$GLOBALS['BACK_PATH'],
				BackendUtility::BEgetRootLine($this->pageinfo['uid'])
			)) .
			'" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) .
			'">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';

		// Shortcut
		if ($this->getBackendUser()->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon(
				'id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit',
				implode(',', array_keys($this->MOD_MENU)),
				$this->MCONF['name']
			);
		}

		return $buttons;
	}

	/*****************************
	 * Listing and Form rendering
	 *****************************/

	/**
	 * Creating form for editing the permissions	($this->edit = true)
	 * (Adding content to internal content variable)
	 *
	 * @return void
	 */
	public function doEdit() {
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();

		if ($backendUser->workspace != 0) {
			// Adding section with the permission setting matrix:
			/**
			 * FlashMessage
			 *
			 * @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage
			 */
			$flashMessage = GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$language->getLL('WorkspaceWarningText'),
				$language->getLL('WorkspaceWarning'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
			);
			/**
			 * Flash message service
			 *
			 * @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService
			 */
			$flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
			/**
			 * Flash message queue
			 *
			 * @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue
			 */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
		}

		// Get usernames and groupnames
		$beGroupArray = BackendUtility::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroupArray);
		$beUserArray = BackendUtility::getUserNames();
		if (!$backendUser->isAdmin()) {
			$beUserArray = BackendUtility::blindUserNames($beUserArray, $beGroupKeys, 1);
		}
		$beGroupArrayO = $beGroupArray = BackendUtility::getGroupNames();
		if (!$backendUser->isAdmin()) {
			$beGroupArray = BackendUtility::blindGroupNames($beGroupArrayO, $beGroupKeys, 1);
		}

		// Owner selector:
		$options = '';

		// flag: is set if the page-userid equals one from the user-list
		foreach ($beUserArray as $uid => $row) {
			if ($uid == $this->pageinfo['perms_userid']) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options .= LF . '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['username']) . '</option>';
		}

		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_userid]">' . $options . '</select>';
		$this->content .= $this->doc->section($language->getLL('Owner'), $selector, TRUE);

		// Group selector:
		$options = '';
		$userset = 0;
		foreach ($beGroupArray as $uid => $row) {
			if ($uid == $this->pageinfo['perms_groupid']) {
				$userset = 1;
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options .= '
				<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
		}
			// If the group was not set AND there is a group for the page
		if (!$userset && $this->pageinfo['perms_groupid']) {
			$options = '
				<option value="' . $this->pageinfo['perms_groupid'] . '" selected="selected">' .
					htmlspecialchars($beGroupArrayO[$this->pageinfo['perms_groupid']]['title']) .
				'</option>' .
				$options;
		}
		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_groupid]"> ' . $options . '</select>';

		$this->content .= $this->doc->section($language->getLL('Group'), $selector, TRUE);

		// Permissions checkbox matrix:
		$code = '
			<table class="t3-table" id="typo3-permissionMatrix">
				<thead>
					<tr>
						<th></th>
						<th>' . $language->getLL('1', TRUE) . '</th>
						<th>' . $language->getLL('16', TRUE) . '</th>
						<th>' . $language->getLL('2', TRUE) . '</th>
						<th>' . $language->getLL('4', TRUE) . '</th>
						<th>' . $language->getLL('8', TRUE) . '</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>' . $language->getLL('Owner', TRUE) . '</strong></td>
						<td>' . $this->printCheckBox('perms_user', 1) . '</td>
						<td>' . $this->printCheckBox('perms_user', 5) . '</td>
						<td>' . $this->printCheckBox('perms_user', 2) . '</td>
						<td>' . $this->printCheckBox('perms_user', 3) . '</td>
						<td>' . $this->printCheckBox('perms_user', 4) . '</td>
					</tr>
					<tr>
						<td><strong>' . $language->getLL('Group', TRUE) . '</strong></td>
						<td>' . $this->printCheckBox('perms_group', 1) . '</td>
						<td>' . $this->printCheckBox('perms_group', 5) . '</td>
						<td>' . $this->printCheckBox('perms_group', 2) . '</td>
						<td>' . $this->printCheckBox('perms_group', 3) . '</td>
						<td>' . $this->printCheckBox('perms_group', 4) . '</td>
					</tr>
					<tr>
						<td><strong>' . $language->getLL('Everybody', TRUE) . '</strong></td>
						<td>' . $this->printCheckBox('perms_everybody', 1) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 5) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 2) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 3) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 4) . '</td>
					</tr>
				</tbody>
			</table>

			<input type="hidden" name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_user]" value="' .
			$this->pageinfo['perms_user'] . '" />
			<input type="hidden" name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_group]" value="' .
			$this->pageinfo['perms_group'] . '" />
			<input type="hidden" name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_everybody]" value="' .
			$this->pageinfo['perms_everybody'] . '" />
			' . $this->getRecursiveSelect($this->id) . '
			<input type="submit" name="submit" value="' . $language->getLL('Save', TRUE) . '" /><input type="submit" value="' .
			$language->getLL('Abort', TRUE) . '" onclick="' . htmlspecialchars(
				'jumpToUrl(' .
				GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('txcommerceM1_permission') . '&id=' . $this->id) .
				'); return false;'
			) . '" />
			<input type="hidden" name="redirect" value="' . htmlspecialchars(
				BackendUtility::getModuleUrl('txcommerceM1_permission') . '&mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' .
				$this->MOD_SETTINGS['depth'] . '&id=' . (int)$this->return_id . '&lastEdited=' . $this->id
			) . '" />
			' . \TYPO3\CMS\Backend\Form\FormEngine::getHiddenTokenField('tceAction');

		// Adding section with the permission setting matrix:
		$this->content .= $this->doc->section($language->getLL('permissions'), $code, TRUE);

		// CSH for permissions setting
		$this->content .= BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module_setting', $this->doc->backPath, '<br /><br />');

		// Adding help text:
		if (TRUE || $backendUser->uc['helpText']) {
			$legendText = '<p><strong>' . $language->getLL('1', TRUE) . '</strong>: ' . $language->getLL('1_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $language->getLL('16', TRUE) . '</strong>: ' . $language->getLL('16_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $language->getLL('2', TRUE) . '</strong>: ' . $language->getLL('2_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $language->getLL('4', TRUE) . '</strong>: ' . $language->getLL('4_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $language->getLL('8', TRUE) . '</strong>: ' . $language->getLL('8_t', TRUE) . '</p>';

			$code = $legendText . '<p>' . $language->getLL('def', TRUE) . '</p>';

			$this->content .= $this->doc->section($language->getLL('Legend', TRUE), $code, TRUE);
		}
	}

	/**
	 * Showing the permissions in a tree ($this->edit = false)
	 * (Adding content to internal content variable)
	 *
	 * @return void
	 */
	public function notEdit() {
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();
		// stores which depths already have their last item
		$depthStop = array();
		$lastDepth = 0;

		// Get usernames and groupnames: The arrays we get in return contains only
		// 1) users which are members of the groups of the current user,
		// 2) groups that the current user is member of
		$beGroupKeys = $backendUser->userGroupsUID;
		$beUserArray = BackendUtility::getUserNames();
		if (!$backendUser->isAdmin()) {
			$beUserArray = BackendUtility::blindUserNames($beUserArray, $beGroupKeys, 0);
		}
		$beGroupArray = BackendUtility::getGroupNames();
		if (!$backendUser->isAdmin()) {
			$beGroupArray = BackendUtility::blindGroupNames($beGroupArray, $beGroupKeys, 0);
		}

		// Length of strings:
		$tLen = 20;

			// Selector for depth:
		$code = $language->getLL('Depth') . ': ';
		$code .= BackendUtility::getFuncMenu($this->categoryUid, 'SET[depth]', $this->MOD_SETTINGS['depth'], $this->MOD_MENU['depth']);
		$this->content .= $this->doc->section('', $code);

		// Initialize tree object:
		/**
		 * Category tree
		 *
		 * @var Tx_Commerce_Tree_CategoryTree $tree
		 */
		$tree = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryTree');
		$tree->setBare();
		$tree->init();
		$tree->readRecursively($this->categoryUid, $this->MOD_SETTINGS['depth']);

		// Creating top icon; the current page
		$rootIcon = IconUtility::getSpriteIcon('apps-pagetree-root');

		// Create the tree from $this->categoryUid:
		$tree->getTree();
		$tree = $tree->getRecordsAsArray($this->categoryUid);

		// Make header of table:
		$code = '
			<thead>
				<tr>
					<th colspan="2">&nbsp;</th>
					<th>' . $language->getLL('Owner', TRUE) . '</th>
					<th align="center">' . $language->getLL('Group', TRUE) . '</th>
					<th align="center">' . $language->getLL('Everybody', TRUE) . '</th>
					<th align="center">' . $language->getLL('EditLock', TRUE) . '</th>
				</tr>
			</thead>
		';

		// Traverse tree:
		foreach ($tree as $data) {
			$cells  = array();
			$pageId = $data['row']['uid'];

			// Background colors:
			$bgCol = $this->lastEdited == $pageId ? ' class="bgColor-20"' : '';
			$elementBgCol = $bgCol;

			// User/Group names:
			$userName = $beUserArray[$data['row']['perms_userid']] ?
				$beUserArray[$data['row']['perms_userid']]['username'] :
				($data['row']['perms_userid'] ? $data['row']['perms_userid'] : '');

			if ($data['row']['perms_userid'] && !$beUserArray[$data['row']['perms_userid']]) {
				$userName = PermissionAjaxController::renderOwnername(
					$pageId, $data['row']['perms_userid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20)), FALSE
				);
			} else {
				$userName = PermissionAjaxController::renderOwnername(
					$pageId, $data['row']['perms_userid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20))
				);
			}

			$groupName = $beGroupArray[$data['row']['perms_groupid']] ?
				$beGroupArray[$data['row']['perms_groupid']]['title'] :
				($data['row']['perms_groupid'] ? $data['row']['perms_groupid'] : '');

			if ($data['row']['perms_groupid'] && !$beGroupArray[$data['row']['perms_groupid']]) {
				$groupName = PermissionAjaxController::renderGroupname(
					$pageId, $data['row']['perms_groupid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20)), FALSE
				);
			} else {
				$groupName = PermissionAjaxController::renderGroupname(
					$pageId, $data['row']['perms_groupid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20))
				);
			}

			// Seeing if editing of permissions are allowed for that page:
			$editPermsAllowed = $data['row']['perms_userid'] == $backendUser->user['uid'] || $backendUser->isAdmin();

			// First column:
			// @todo check for better solution
			$plusMinusIcon = '';
			// Add PM only if we are not looking at the root
			if ($data['depth'] > 0) {
				// Add simple join-images for categories that are deeper level than 1
				if ($data['depth'] > 1) {
					$k = $data['depth'];

					for ($j = 1; $j < $k; $j++) {
						if (!array_key_exists($j, $depthStop) || $depthStop[$j] != 1) {
							$plusMinusIcon .= IconUtility::getSpriteIcon('treeline-line');
						} elseif ($depthStop[$j] == 1) {
							$plusMinusIcon .= IconUtility::getSpriteIcon('treeline-blank');
						}
					}
				}

				if ($lastDepth > $data['depth']) {
					for ($j = $data['depth'] + 1; $j <= $lastDepth; $j++) {
						$depthStop[$j] = 0;
					}
				}

					// Add cross or bottom
				$bottom = (TRUE == $data['last']) ? 'bottom' : '';

				// save that the depth of the current record has its last item - is used to
				// add blanks, not lines to following deeper elements
				if (TRUE == $data['last']) {
					$depthStop[$data['depth']] = 1;
				}

				$lastDepth = $data['depth'];

				$plusMinusIcon .= IconUtility::getSpriteIcon('treeline-join' . $bottom);
			}

			// determine which icon to use
			$rowIcon = $plusMinusIcon .
				($pageId ? IconUtility::getSpriteIconForRecord('tx_commerce_categories', $data['row']) : $rootIcon);
			// @todo end of check for better solution

			// First column:
			$cellAttrib = $data['row']['_CSSCLASS'] ? ' class="' . $data['row']['_CSSCLASS'] . '"' : '';
			$cells[] = '<td align="left" nowrap="nowrap"' . ($cellAttrib ? $cellAttrib : $bgCol) . '>' .
				$rowIcon . htmlspecialchars(GeneralUtility::fixed_lgd_cs($data['row']['title'], $tLen)) . '</td>';

			// "Edit permissions" -icon
			if ($editPermsAllowed && $pageId) {
				$aHref = BackendUtility::getModuleUrl('txcommerceM1_permission') . '&mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' .
					$this->MOD_SETTINGS['depth'] . '&control[tx_commerce_categories][uid]=' .
					($data['row']['_ORIG_uid'] ? $data['row']['_ORIG_uid'] : $pageId) .
					'&return_id=' . $this->id . '&edit=1';
				$cells[] = '<td' . $bgCol . '><a href="' . htmlspecialchars($aHref) . '" title="' . $language->getLL('ch_permissions', 1) .
					'">' . IconUtility::getSpriteIcon('actions-document-open') . '</a></td>';
			} else {
				$cells[] = LF . '<td' . $bgCol . '></td>';
			}

			$userPermission = PermissionAjaxController::renderPermissions($data['row']['perms_user'], $pageId, 'user');
			$groupPermission = PermissionAjaxController::renderPermissions($data['row']['perms_group'], $pageId, 'group');
			$allPermission = PermissionAjaxController::renderPermissions($data['row']['perms_everybody'], $pageId, 'everybody');

			$cells[] = '
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? $userPermission . ' ' . $userName : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? $groupPermission . ' ' . $groupName : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? ' ' . $allPermission : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . (
					$data['row']['editlock'] ?
						'<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' .
							$pageId . '\', \'1\');" title="' . $language->getLL('EditLock_descr', TRUE) . '">' .
							IconUtility::getSpriteIcon('status-warning-lock') . '</a></span>' :
						(
							$pageId === 0 ?
								'' :
								'<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' .
								$pageId . '\', \'0\');" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a></span>'
						)
				) . '</td>
			';

			// Compile table row:
			$code .= '<tr>' . implode(LF, $cells) . '</tr>';
		}

		// Wrap rows in table tags:
		$code = '<table class="t3-table" id="typo3-permissionList">' . $code . '</table>';

		// Adding the content as a section:
		$this->content .= $this->doc->section('', $code);

		// CSH for permissions setting
		$this->content .= BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module', $this->doc->backPath, '<br />|');

		// Creating legend table:
		$legendText = '<strong>' . $language->getLL('1', TRUE) . '</strong>: ' . $language->getLL('1_t', TRUE);
		$legendText .= '<br /><strong>' . $language->getLL('16', TRUE) . '</strong>: ' . $language->getLL('16_t', TRUE);
		$legendText .= '<br /><strong>' . $language->getLL('2', TRUE) . '</strong>: ' . $language->getLL('2_t', TRUE);
		$legendText .= '<br /><strong>' . $language->getLL('4', TRUE) . '</strong>: ' . $language->getLL('4_t', TRUE);
		$legendText .= '<br /><strong>' . $language->getLL('8', TRUE) . '</strong>: ' . $language->getLL('8_t', TRUE);

		$code = '<div id="permission-information">
					<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/legend.gif', 'width="86" height="75"') . ' alt="" />
				<div class="text">' . $legendText . '</div></div>';

		$code .= '<div id="perm-legend">' . $language->getLL('def', TRUE);
		$code .= '<br /><br />' . IconUtility::getSpriteIcon('status-status-permission-granted') . ': ' .
			$language->getLL('A_Granted', TRUE);
		$code .= '<br />' . IconUtility::getSpriteIcon('status-status-permission-denied') . ': ' . $language->getLL('A_Denied', TRUE);
		$code .= '</div>';

		// Adding section with legend code:
		$this->content .= $this->doc->section($language->getLL('Legend') . ':', $code, TRUE, TRUE);
	}

	/*****************************
	 * Helper functions
	 *****************************/

	/**
	 * Print a checkbox for the edit-permission form
	 *
	 * @param string $checkName Checkbox name key
	 * @param int $num Checkbox number index
	 *
	 * @return string HTML checkbox
	 */
	public function printCheckBox($checkName, $num) {
		$onclick = 'checkChange(\'check[' . $checkName . ']\', \'data[tx_commerce_categories][' . $GLOBALS['SOBE']->categoryUid . '][' .
			$checkName . ']\')';
		return '<input type="checkbox" name="check[' . $checkName . '][' . $num . ']" onclick="' . htmlspecialchars($onclick) .
			'" /><br />';
	}

	/**
	 * Finding tree and offer setting of values recursively.
	 *
	 * @param int $id Page id.
	 *
	 * @return string Select form element for recursive levels
	 */
	public function getRecursiveSelect($id) {
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();

		// Initialize tree object:
		/**
		 * Category tree
		 *
		 * @var Tx_Commerce_Tree_CategoryTree $tree
		 */
		$tree = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryTree');
		$tree->setBare();
		$tree->readRecursively($this->categoryUid, $this->getLevels);
		$tree->init();
		// Create the tree from $this->categoryUid:
		$tree->getTree();

		// Get the tree records
		$recsPerLevel = $tree->getRecordsPerLevelArray($this->categoryUid);

		// If there are a hierarchy of category ids, then...
		if ($backendUser->user['uid'] && count($recsPerLevel)) {
			// Init:
			$labelRecursive = $language->getLL('recursive');
			$labelLevels = $language->getLL('levels');
			$labelPagesAffected = $language->getLL('pages_affected');
			$theIdListArr = array();
			$opts = '<option value=""></option>' . LF;
			// Traverse the number of levels we want to
			// allow recursive setting of permissions for:
			for ($a = 0; $a <= $this->getLevels; $a++) {
					// Go through the levels
				if (is_array($recsPerLevel[$a])) {
					foreach ($recsPerLevel[$a] as $theId) {
							// get the category record
						$cat = $tree->getCategory($theId);

							// Check if the category uid should be added as a child
						if ($backendUser->isAdmin() || $backendUser->user['uid'] == $cat['perms_userid']) {
							$theIdListArr[] = $theId;
						}
					}
					$lKey = $a + 1;
					$opts .= '
						<option value="' . htmlspecialchars(implode(',', $theIdListArr)) . '">' .
						htmlspecialchars($labelRecursive . ' ' . $lKey . ' ' . $labelLevels, ENT_COMPAT, 'UTF-8', FALSE) .
						' (' . count($theIdListArr) . ' ' . $labelPagesAffected . ')</option>';
				}
			}

			// Put the selector box together:
			$theRecursiveSelect = '<br />
				<select name="mirror[tx_commerce_categories][' . $id . ']">
					' . $opts . '
				</select>
				<br /><br />';
		} else {
			$theRecursiveSelect = '';
		}

		// Return selector box element:
		return $theRecursiveSelect;
	}


	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}

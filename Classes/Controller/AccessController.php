<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2012 Erik Frister <typo3@marketing-factory.de>
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
 * Module: Permission setting
 *
 * Script Class for the Web > Access module
 * This module lets you view and change permissions for pages.
 *
 * Variables:
 * $this->MOD_SETTINGS['depth']: integer 1-3: decides the depth of the list
 * $this->MOD_SETTINGS['mode']: 'perms' / '': decides if we view a user-overview or the permissions.
 */
class Tx_Commerce_Controller_AccessController extends t3lib_SCbase {
	/**
	 * Number of levels to enable recursive settings for
	 * @var integer
	 */
	public $getLevels = 10;

	/**
	 * @var string
	 */
	public $iconPath = '../../../typo3conf/ext/commerce/Resources/Public/Icons/Table/';

	/**
	 * @var string
	 */
	public $rootIconName = 'commerce_globus.gif';

	/**
	 * Current page record
	 * @var array
	 */
	public $pageinfo;

	/**
	 * Set internally if the current user either OWNS the category OR is admin user!
	 * @var boolean
	 */
	public $editingAllowed;

	/**
	 * If set, editing of the category permissions will occur (showing the editing screen). Notice:
	 * This value is evaluated against permissions and so it will change internally!
	 * @var boolean
	 */
	public $edit;

	/**
	 * ID to return to after editing.
	 * @var integer
	 */
	public $return_id;

	/**
	 * Id of the category which was just edited.
	 * @var integer
	 */
	public $lastEdited;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var array
	 */
	protected $controlArray;

	/**
	 * @var integer
	 */
	public $categoryUid = 0;

	/**
	 * Initialization of the class
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

			// GPvars:
		$this->id = (int) t3lib_div::_GP('id');
		if (!$this->id) {
			Tx_Commerce_Utility_FolderUtility::init_folders();
			$this->id = current(array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'Commerce', 0, 'Commerce')));
		}

			// Setting GPvars:
		$controlParams = t3lib_div::_GP('control');
		if ($controlParams) {
			$this->controlArray = current($controlParams);
			$this->categoryUid = (int) $this->controlArray['uid'];
		}

		$this->edit = t3lib_div::_GP('edit');
		$this->return_id = t3lib_div::_GP('return_id');
		$this->lastEdited = t3lib_div::_GP('lastEdited');

		$this->perms_clause = Tx_Commerce_Utility_BackendUtility::getCategoryPermsClause(1);
	}

	/**
	 * Initializes the Page
	 *
	 * @return void
	 */
	public function initPage() {
			// Initializing document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_category_index.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set moduleTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_category_index.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_category_index.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_category_index.html';
			$this->doc->moduleTemplate = t3lib_div::getURL(PATH_site . $templateFile);
		}

		$this->doc->form = '<form action="' . $this->doc->backPath . 'tce_db.php" method="post" name="editform">';
		$this->doc->loadJavascriptLib($this->doc->backPath . 'sysext/perm/mod1/perm.js');

			// override attributes of WebPermissions found in sysext/perm/mod1/perm.js
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			WebPermissions.thisScript = "../../../../../../typo3/ajax.php";
			WebPermissions.ajaxID = "Tx_Commerce_Controller_PermissionAjaxController::dispatch";
		');
	}

	/**
	 * Configuration of the menu and initialization of ->MOD_SETTINGS
	 *
	 * @return void
	 */
	public function menuConfig() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$levelLabel = $language->getLL('levels');
		$this->MOD_MENU = array(
			'depth' => array(
				1 => '1 ' . $levelLabel,
				2 => '2 ' . $levelLabel,
				3 => '3 ' . $levelLabel,
				4 => '4 ' . $levelLabel,
				10 => '10 ' . $levelLabel
			),
			'mode' => array(
				0 => $language->getLL('user_overview'),
				'perms' => $language->getLL('permissions')
			)
		);

			// Clean up settings:
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Main function, creating the content for the access editing forms/listings
	 *
	 * @return void
	 */
	public function main() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Access check...
			// The page will show only if there is a valid page and if this page may be viewed by the user
		if ($this->categoryUid) {
			$this->pageinfo = Tx_Commerce_Utility_BackendUtility::readCategoryAccess($this->categoryUid, $this->perms_clause);
		} else {
			$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		}
		$access = is_array($this->pageinfo);

			// Checking access:
		if (($this->categoryUid && $access) || ($backendUser->isAdmin() && !$this->categoryUid)) {
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
			$this->content .= $this->doc->spacer(5);

				// Main function, branching out:
			if (!$this->edit) {
				$this->notEdit();
			} else {
				$this->doEdit();
			}
		} else {
				// If no access or if ID == zero
			$this->content .= $this->doc->header($language->getLL('permissions'));
		}

		$docHeaderButtons = $this->getHeaderButtons();

		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content,
			'CATINFO' => $this->categoryUid ? $this->getCategoryInfo($this->pageinfo) : $this->getPageInfo($this->pageinfo),
			'CATPATH' => $this->categoryUid ? $this->getCategoryPath($this->pageinfo) : $this->getPagePath($this->pageinfo),
		);
		$markers['FUNC_MENU'] = $this->doc->funcMenu(
			'',
			t3lib_BEfunc::getFuncMenu(
				$this->id,
				'SET[mode]',
				$this->MOD_SETTINGS['mode'],
				$this->MOD_MENU['mode']
			)
		);

			// put it all together
		$this->content = $this->doc->startPage($language->getLL('permissions'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getHeaderButtons() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$buttons = array(
			'csh' => '',
				// group left 1
			'level_up' => '',
			'back' => '',
				// group left 2
			'new_record' => '',
			'paste' => '',
				// group left 3
			'view' => '',
			'edit' => '',
			'move' => '',
			'hide_unhide' => '',
				// group left 4
			'csv' => '',
			'export' => '',
				// group right 1
			'cache' => '',
			'reload' => '',
			'shortcut' => '',
		);

			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', TRUE);

			// Shortcut
		if ($backendUser->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon(
				'id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit',
				implode(',', array_keys($this->MOD_MENU)),
				$this->MCONF['name']
			);
		}

			// If access to Web>List for user, then link to that module.
		if ($backendUser->check('modules', 'web_list')) {
			$href = $GLOBALS['BACK_PATH'] . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' .
				rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'apps-filetree-folder-list',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1))
				) . '</a>';
		}
		return $buttons;
	}

	/**
	 * Returns the Category Path info
	 *
	 * @param array $categoryRecord Category row
	 * @return string
	 */
	protected function getCategoryPath($categoryRecord) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Is this a real page
		if (is_array($categoryRecord) && $categoryRecord['uid']) {
			$title = substr($categoryRecord['_thePathFull'], 0, -1);
				// remove current page title
			$pos = strrpos($title, '/');
			if ($pos !== FALSE) {
				$title = substr($title, 0, $pos) . '/';
			}
		} else {
			$title = '';
		}

			// Setting the path of the page
		$pagePath = $language->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span class="typo3-docheader-pagePath">';

			// crop the title to title limit (or 50, if not defined)
		$cropLength = (empty($backendUser->uc['titleLen'])) ? 50 : $backendUser->uc['titleLen'];
		$croppedTitle = t3lib_div::fixed_lgd_cs($title, - $cropLength);
		if ($croppedTitle !== $title) {
			$pagePath .= '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
		} else {
			$pagePath .= htmlspecialchars($title);
		}
		$pagePath .= '</span>';
		return $pagePath;
	}

	/**
	 * Returns the info for the Category Path
	 *
	 * @param array $categoryRecord - Category record
	 * @return string
	 */
	protected function getCategoryInfo($categoryRecord) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Add icon with clickmenu, etc:
			// If there IS a real page
		if (is_array($categoryRecord) && $categoryRecord['uid']) {
			$alttext = t3lib_BEfunc::getRecordIconAltText($categoryRecord, 'tx_commerce_categories');
			$iconImg = t3lib_iconWorks::getSpriteIconForRecord('tx_commerce_categories', $categoryRecord, array('title' => $alttext));
				// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'tx_commerce_categories', $categoryRecord['uid']);
			$uid = $categoryRecord['uid'];
			$title = t3lib_BEfunc::getRecordTitle('tx_commerce_categories', $categoryRecord);
		} else {
				// On root-level of page tree
				// Make Icon
			$iconImg = t3lib_iconWorks::getSpriteIcon('apps-pagetree-root', array('title' => htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])));
			if ($backendUser->user['admin']) {
				$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'tx_commerce_categories', 0);
			} else {
				$theIcon = $iconImg;
			}
			$uid = '0';
			$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}

			// Setting icon with clickmenu + uid
		$pageInfo = $theIcon . '<strong>' . htmlspecialchars($title) . '&nbsp;[' . $uid . ']</strong>';
		return $pageInfo;
	}

	/**
	 * Generate the page path for docheader
	 *
	 * @param array $pageRecord Current page
	 * @return string Page path
	 */
	protected function getPagePath($pageRecord) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Is this a real page
		if (is_array($pageRecord) && $pageRecord['uid']) {
			$title = substr($pageRecord['_thePathFull'], 0, -1);
				// remove current page title
			$pos = strrpos($title, '/');
			if ($pos !== FALSE) {
				$title = substr($title, 0, $pos) . '/';
			}
		} else {
			$title = '';
		}

			// Setting the path of the page
		$pagePath = $language->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span class="typo3-docheader-pagePath">';

			// crop the title to title limit (or 50, if not defined)
		$cropLength = (empty($backendUser->uc['titleLen'])) ? 50 : $backendUser->uc['titleLen'];
		$croppedTitle = t3lib_div::fixed_lgd_cs($title, - $cropLength);
		if ($croppedTitle !== $title) {
			$pagePath .= '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
		} else {
			$pagePath .= htmlspecialchars($title);
		}
		$pagePath .= '</span>';
		return $pagePath;
	}

	/**
	 * Setting page icon with clickmenu + uid for docheader
	 *
	 * @param array $pageRecord Current page
	 * @return string Page info
	 */
	protected function getPageInfo($pageRecord) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Add icon with clickmenu, etc:
			// If there IS a real page
		if (is_array($pageRecord) && $pageRecord['uid']) {
			$alttext = t3lib_BEfunc::getRecordIconAltText($pageRecord, 'pages');
			$iconImg = t3lib_iconWorks::getSpriteIconForRecord('pages', $pageRecord, array('title' => $alttext));
				// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', $pageRecord['uid']);
			$uid = $pageRecord['uid'];
			$title = t3lib_BEfunc::getRecordTitle('pages', $pageRecord);
		} else {
				// On root-level of page tree
				// Make Icon
			$iconImg = t3lib_iconWorks::getSpriteIcon('apps-pagetree-root', array('title' => htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])));
			if ($backendUser->user['admin']) {
				$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', 0);
			} else {
				$theIcon = $iconImg;
			}
			$uid = '0';
			$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}

			// Setting icon with clickmenu + uid
		$pageInfo = $theIcon . '<strong>' . htmlspecialchars($title) . '&nbsp;[' . $uid . ']</strong>';
		return $pageInfo;
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
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		if ($backendUser->workspace != 0) {
				// Adding section with the permission setting matrix:
				/** @var t3lib_FlashMessage $lockedMessage */
			$lockedMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$language->getLL('WorkspaceWarningText'),
				$language->getLL('WorkspaceWarning'),
				t3lib_FlashMessage::WARNING
			);
			t3lib_FlashMessageQueue::addMessage($lockedMessage);
		}

			// Get usernames and groupnames
		$beGroupArray = t3lib_BEfunc::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroupArray);

		$beUserArray = t3lib_BEfunc::getUserNames();
		if (!$backendUser->isAdmin()) {
			$beUserArray = t3lib_BEfunc::blindUserNames($beUserArray, $beGroupKeys, 1);
		}
		$beGroupArray_o = $beGroupArray = t3lib_BEfunc::getGroupNames();
		if (!$backendUser->isAdmin()) {
			$beGroupArray = t3lib_BEfunc::blindGroupNames($beGroupArray_o, $beGroupKeys, 1);
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

		$options = '
				<option value="0"></option>' . $options;
		$selector = '
			<select name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_userid]">
				' . $options . '
			</select>';

		$this->content .= $this->doc->section($language->getLL('Owner') . ':', $selector);

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
					htmlspecialchars($beGroupArray_o[$this->pageinfo['perms_groupid']]['title']) .
				'</option>' .
				$options;
		}
		$options = '
				<option value="0"></option>' . $options;
		$selector = '
			<select name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_groupid]">
				' . $options . '
			</select>';

		$this->content .= $this->doc->divider(5);
		$this->content .= $this->doc->section($language->getLL('Group') . ':', $selector);

			// Permissions checkbox matrix:
		$code = '
			<table border="0" cellspacing="2" cellpadding="0" id="typo3-permissionMatrix">
				<tr>
					<td></td>
					<td class="bgColor2">' . str_replace(' ', '<br />', $language->getLL('1', 1)) . '</td>
					<td class="bgColor2">' . str_replace(' ', '<br />', $language->getLL('16', 1)) . '</td>
					<td class="bgColor2">' . str_replace(' ', '<br />', $language->getLL('2', 1)) . '</td>
					<td class="bgColor2">' . str_replace(' ', '<br />', $language->getLL('4', 1)) . '</td>
					<td class="bgColor2">' . str_replace(' ', '<br />', $language->getLL('8', 1)) . '</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">' . $language->getLL('Owner', 1) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_user', 1) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_user', 5) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_user', 2) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_user', 3) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_user', 4) . '</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">' . $language->getLL('Group', 1) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_group', 1) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_group', 5) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_group', 2) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_group', 3) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_group', 4) . '</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">' . $language->getLL('Everybody', 1) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_everybody', 1) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_everybody', 5) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_everybody', 2) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_everybody', 3) . '</td>
					<td class="bgColor-20">' . $this->printCheckBox('perms_everybody', 4) . '</td>
				</tr>
			</table>
			<br />

			<input type="hidden" name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_user]" value="' . $this->pageinfo['perms_user'] . '" />
			<input type="hidden" name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_group]" value="' . $this->pageinfo['perms_group'] . '" />
			<input type="hidden" name="data[tx_commerce_categories][' . $this->categoryUid . '][perms_everybody]" value="' . $this->pageinfo['perms_everybody'] . '" />
			' . $this->getRecursiveSelect($this->categoryUid) . t3lib_TCEforms::getHiddenTokenField('editform') . '
			<input type="submit" name="submit" value="' . $language->getLL('Save', 1) . '" />' .
			'<input type="submit" value="' . $language->getLL('Abort', 1) . '" onclick="' .
				htmlspecialchars('jumpToUrl(\'index.php?id=' . $this->categoryUid . '\'); return false;') . '" />
			<input type="hidden" name="redirect" value="' .
				htmlspecialchars(TYPO3_MOD_PATH . 'index.php?mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' .
				$this->MOD_SETTINGS['depth'] . '&id=' . (int) $this->return_id . '&lastEdited=' . $this->categoryUid) . '" />
		';

			// Adding section with the permission setting matrix:
		$this->content .= $this->doc->divider(5);
		$this->content .= $this->doc->section($language->getLL('permissions') . ':', $code);

			// CSH for permissions setting
		$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'perm_module_setting', $GLOBALS['BACK_PATH'], '<br/><br/>');

			// Adding help text:
		if ($backendUser->uc['helpText']) {
			$this->content .= $this->doc->divider(20);
			$legendText = '<b>' . $language->getLL('1', 1) . '</b>: ' . $language->getLL('1_t', 1);
			$legendText .= '<br /><b>' . $language->getLL('16', 1) . '</b>: ' . $language->getLL('16_t', 1);
			$legendText .= '<br /><b>' . $language->getLL('2', 1) . '</b>: ' . $language->getLL('2_t', 1);
			$legendText .= '<br /><b>' . $language->getLL('4', 1) . '</b>: ' . $language->getLL('4_t', 1);
			$legendText .= '<br /><b>' . $language->getLL('8', 1) . '</b>: ' . $language->getLL('8_t', 1);

			$code = $legendText . '<br /><br />' . $language->getLL('def', 1);
			$this->content .= $this->doc->section($language->getLL('Legend', 1) . ':', $code);
		}
	}

	/**
	 * Showing the permissions in a tree ($this->edit = false)
	 * (Adding content to internal content variable)
	 *
	 * @return void
	 */
	public function notEdit() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var Tx_Commerce_Controller_PermissionAjaxController $permissionAjaxController */
		$permissionAjaxController = t3lib_div::makeInstance('Tx_Commerce_Controller_PermissionAjaxController');

			// Get usernames and groupnames: The arrays we get in return contains only 1) users which are members of the groups
			// of the current user, 2) groups that the current user is member of
		$beGroupKeys = $backendUser->userGroupsUID;
		$beUserArray = t3lib_BEfunc::getUserNames();
		if (!$backendUser->isAdmin()) {
			$beUserArray = t3lib_BEfunc::blindUserNames($beUserArray, $beGroupKeys, 0);
		}
		$beGroupArray = t3lib_BEfunc::getGroupNames();
		if (!$backendUser->isAdmin()) {
			$beGroupArray = t3lib_BEfunc::blindGroupNames($beGroupArray, $beGroupKeys, 0);
		}

			// Length of strings:
		$tLen = ($this->MOD_SETTINGS['mode'] == 'perms' ? 20 : 30);

			// Selector for depth:
		$code = $language->getLL('Depth') . ': ';
		$code .= t3lib_BEfunc::getFuncMenu($this->categoryUid, 'SET[depth]', $this->MOD_SETTINGS['depth'], $this->MOD_MENU['depth']);
		$this->content .= $this->doc->section('', $code);
		$this->content .= $this->doc->spacer(5);

			// Initialize tree object:
		/** @var Tx_Commerce_Tree_CategoryTree $tree */
		$tree = t3lib_div::makeInstance('Tx_Commerce_Tree_CategoryTree');

		$tree->setBare();
		$tree->init();
		$tree->readRecursively($this->categoryUid, $this->MOD_SETTINGS['depth']);
			// Create the tree from $this->categoryUid:
		$tree->getTree();

			// Get the tree records
		$records = $tree->getRecordsAsArray($this->categoryUid);

			// Make header of table:
		$code = '';

		$lineImg = t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/line.gif', 'width="5" height="16"');
		if ($this->MOD_SETTINGS['mode'] == 'perms') {
			$code .= '
				<tr>
					<td colspan="2">&nbsp;</td>
					<td ><img' . $lineImg . ' alt="" /></td>
					<td align="center"><b>' . $language->getLL('Owner', 1) . '</b></td>
					<td><img' . $lineImg . ' alt="" /></td>
					<td align="center"><b>' . $language->getLL('Group', 1) . '</b></td>
					<td><img' . $lineImg . ' alt="" /></td>
					<td align="center"><b>' . $language->getLL('Everybody', 1) . '</b></td>
					<td><img' . $lineImg . ' alt="" /></td>
					<td align="center"><b>' . $language->getLL('EditLock', 1) . '</b></td>
				</tr>
			';
		} else {
			$code .= '
				<tr class="t3-row-header">
					<td colspan="2">&nbsp;</td>
					<td><img' . $lineImg . ' alt="" /></td>
					<td align="center" nowrap="nowrap">' . $language->getLL('User', TRUE) . ': ' . htmlspecialchars($backendUser->user['username']) . '</td>
					' . (!$backendUser->isAdmin() ? '<td><img' . $lineImg . ' alt="" /></td>
					<td align="center">' . $language->getLL('EditLock', 1) . '</td>' : '') . '
				</tr>';
		}

			// Creating category Icon
		$icon = t3lib_iconWorks::getIconImage('tx_commerce_categories', $this->pageinfo, $this->doc->backPath, 'align="top" class="c-recIcon"');
		$rootIcon = '<img' . t3lib_iconWorks::skinImg($this->doc->backPath . $this->iconPath, $this->rootIconName, 'width="18" height="16"') .
			' title="Root" alt="" />';

			// Traverse tree:
			// stores which depths already have their last item
		$depthStop = array();
		$l = count($records);
		$lastDepth = 0;

		for ($i = 0; $i < $l; $i ++) {
			$row = $records[$i]['record'];

			$cells  = array();
			$pageId = $row['uid'];

				// Background colors:
			$bgCol = ($this->lastEdited == $pageId ? ' class="bgColor-20"' : '');
			$lE_bgCol = $bgCol;

				// User/Group names:
			$userName = $beUserArray[$row['perms_userid']] ?
				$beUserArray[$row['perms_userid']]['username'] :
				($row['perms_userid'] ? $row['perms_userid'] : '');
			$userName = $permissionAjaxController->renderOwnername($pageId, $row['perms_userid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($userName, 20)));

			$groupName = $beGroupArray[$row['perms_groupid']] ?
				$beGroupArray[$row['perms_groupid']]['title']  :
				($row['perms_groupid'] ? $row['perms_groupid'] : '');
			if ($row['perms_groupid'] && (!$beGroupArray[$row['perms_groupid']])) {
				$groupName = $permissionAjaxController->renderGroupname($pageId, $row['perms_groupid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($groupName, 20)), FALSE);
			} else {
				$groupName = $permissionAjaxController->renderGroupname($pageId, $row['perms_groupid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($groupName, 20)));
			}

				// Seeing if editing of permissions are allowed for that page:
			$editPermsAllowed = ($row['perms_userid'] == $backendUser->user['uid'] || $backendUser->isAdmin());

				// First column:
			$PMicon = '';
				// Add PM only if we are not looking at the root
			if ($records[$i]['depth'] > 0) {
					// Add simple join-images for categories that are deeper level than 1
				if ($records[$i]['depth'] > 1) {
					$k = $records[$i]['depth'];

					for ($j = 1; $j < $k; $j ++) {
						if (!array_key_exists($j, $depthStop) || $depthStop[$j] != 1) {
							$PMicon .= '<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/ol/line.gif', 'width="18" height="16"') . ' alt="" />';
						} elseif ($depthStop[$j] == 1) {
							$PMicon .= '<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/ol/blank.gif', 'width="18" height="16"') . ' alt="" />';
						}
					}
				}

				if ($lastDepth > $records[$i]['depth']) {
					for ($j = $records[$i]['depth'] + 1; $j <= $lastDepth; $j ++) {
						$depthStop[$j] = 0;
					}
				}

					// Add cross or bottom
				$bottom = (TRUE == $records[$i]['last']) ? 'bottom' : '';

					// save that the depth of the current record has its last item - is used to add blanks, not lines to following deeper elements
				if (TRUE == $records[$i]['last']) {
					$depthStop[$records[$i]['depth']] = 1;
				}

				$lastDepth = $records[$i]['depth'];

				$PMicon .= '<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/ol/join' . $bottom . '.gif', 'width="18" height="16"') . ' alt="" />';
			}

				// determine which icon to use
			$rowIcon = $pageId ? $icon : $rootIcon;

			$cellAttrib = ($row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : '');
			$cells[] = '
				<td align="left" nowrap="nowrap"' . ($cellAttrib ? $cellAttrib : $bgCol) . '>' . $PMicon . $rowIcon .
				htmlspecialchars(t3lib_div::fixed_lgd_cs($row['title'], $tLen)) . '&nbsp;</td>';

				// "Edit permissions" -icon
			if ($editPermsAllowed && $pageId) {
				$aHref = 'index.php?mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' . $this->MOD_SETTINGS['depth'] . '&id=' .
					($row['_ORIG_uid'] ? $row['_ORIG_uid'] : $pageId) . '&return_id=' . $this->id . '&edit=1';
				$cells[] = '
					<td' . $bgCol . '><a href="' . htmlspecialchars($aHref) . '" title="' . $language->getLL('ch_permissions', 1) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-open') .
					'</a></td>';
			} else {
				$cells[] = LF . '<td' . $bgCol . '></td>';
			}

				// Rest of columns (depending on mode)
			$lineImg = t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/line.gif', 'width="5" height="16"');
			if ($this->MOD_SETTINGS['mode'] == 'perms') {
				$cells[] = '
					<td' . $bgCol . ' class="center"><img' . $lineImg . ' alt="" /></td>
					<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? $permissionAjaxController->renderPermissions($row['perms_user'], $pageId, 'user') . ' ' . $userName : '') . '</td>

					<td' . $bgCol . ' class="center"><img' . $lineImg . ' alt="" /></td>
					<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? $permissionAjaxController->renderPermissions($row['perms_group'], $pageId, 'group') . ' ' . $groupName : '') . '</td>

					<td' . $bgCol . ' class="center"><img' . $lineImg . ' alt="" /></td>
					<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? ' ' . $permissionAjaxController->renderPermissions($row['perms_everybody'], $pageId, 'everybody') : '') . '</td>

					<td' . $bgCol . ' class="center"><img' . $lineImg . ' alt="" /></td>
					<td' . $bgCol . ' nowrap="nowrap">' . (
						$row['editlock'] ?
						'<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' .
							$pageId . '\', \'1\');">' . t3lib_iconWorks::getSpriteIcon(
								'status-warning-lock',
								array('title' => $language->getLL('EditLock_descr', 1))
							) .
							'</a></span>' :
						(
							$pageId === 0 ?
							'' :
							'<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' . $pageId .
							'\', \'0\');" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a></span>'
						)
					) . '</td>
					';
			} else {
				$cells[] = '
					<td' . $bgCol . ' class="center"><img' . $lineImg . ' alt="" /></td>';

				$bgCol = ($backendUser->user['uid'] == $row['perms_userid'] ? ' class="bgColor-20"' : $lE_bgCol);

				$cells[] = '
					<td' . $bgCol . ' nowrap="nowrap" align="center">' . (
						$pageId ?
						$permissionAjaxController->renderPermissions($backendUser->calcPerms($row), $pageId, 'user') :
						''
					) . '</td>
					' . (!$backendUser->isAdmin() ?
						'
						<td' . $bgCol . '><img' . $lineImg . ' alt="" /></td>
						<td' . $bgCol . ' nowrap="nowrap">' . (
							$row['editlock'] ?
							t3lib_iconWorks::getSpriteIcon(
								'status-warning-lock',
								array('title' => $language->getLL('EditLock_descr', 1))
							) :
							''
						) . '</td>
						':
						''
					);

				$bgCol = $lE_bgCol;
			}

				// Compile table row:
			$code .= '<tr>' . implode(LF, $cells) . '</tr>';
		}

			// Wrap rows in table tags:
		$code = '<table border="0" cellspacing="0" cellpadding="0" id="typo3-permissionList" width="99.5%">' . $code . '</table>';

			// Adding the content as a section:
		$this->content .= $this->doc->section('', $code);

			// CSH for permissions setting
		$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'perm_module', $GLOBALS['BACK_PATH'], '<br/>|');

			// Creating legend table:
		$legendText = '<b>' . $language->getLL('1', 1) . '</b>: ' . $language->getLL('1_t', 1);
		$legendText .= '<br /><b>' . $language->getLL('16', 1) . '</b>: ' . $language->getLL('16_t', 1);
		$legendText .= '<br /><b>' . $language->getLL('2', 1) . '</b>: ' . $language->getLL('2_t', 1);
		$legendText .= '<br /><b>' . $language->getLL('4', 1) . '</b>: ' . $language->getLL('4_t', 1);
		$legendText .= '<br /><b>' . $language->getLL('8', 1) . '</b>: ' . $language->getLL('8_t', 1);

		$code = '<table border="0" id="typo3-legendTable">
			<tr>
				<td valign="top"><img src="' . $this->doc->backPath . 'gfx/legend.gif" width="86" height="75" alt="" /></td>
				<td valign="top" nowrap="nowrap">' . $legendText . '</td>
			</tr>
		</table>
		<div id="perm-legend">' . $language->getLL('def', 1) .
			'<br /><br />' . t3lib_iconWorks::getSpriteIcon('status-status-permission-granted') . ': ' . $language->getLL('A_Granted', 1) .
			'<br />' . t3lib_iconWorks::getSpriteIcon('status-status-permission-denied') . ': ' . $language->getLL('A_Denied', 1) .
			'</div>';

			// Adding section with legend code:
		$this->content .= $this->doc->spacer(20);
		$this->content .= $this->doc->section($language->getLL('Legend') . ':', $code, 0, 1);
	}

	/*****************************
	 * Helper functions
	 *****************************/

	/**
	 * Print a checkbox for the edit-permission form
	 *
	 * @param string $checkName Checkbox name key
	 * @param integer $num Checkbox number index
	 * @return string HTML checkbox
	 */
	public function printCheckBox($checkName, $num) {
		$onclick = 'checkChange(\'check[' . $checkName . ']\', \'data[tx_commerce_categories][' . $GLOBALS['SOBE']->id . '][' . $checkName . ']\')';
		return '<input type="checkbox" name="check[' . $checkName . '][' . $num . ']" onclick="' . htmlspecialchars($onclick) . '" /><br />';
	}

	/**
	 * Returns the permissions for a group based of the perms_groupid of $row. If the $row[perms_groupid] equals the
	 * $firstGroup[uid] then the function returns perms_everybody OR'ed with perms_group, else just perms_everybody
	 *
	 * @param array $row array (from pages table)
	 * @param array $firstGroup first group data
	 * @return integer Integer: Combined permissions.
	 */
	public function groupPerms($row, $firstGroup) {
		$result = 0;

		if (is_array($row)) {
			$out = (int) $row['perms_everybody'];
			if ($row['perms_groupid'] && $firstGroup['uid'] == $row['perms_groupid']) {
				$out |= (int) $row['perms_group'];
			}
			$result = $out;
		}

		return $result;
	}

	/**
	 * Finding tree and offer setting of values recursively.
	 *
	 * @param integer $id Page id.
	 * @return string Select form element for recursive levels (if any levels are found)
	 */
	public function getRecursiveSelect($id) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Initialize tree object:
		/** @var Tx_Commerce_Tree_CategoryTree $tree */
		$tree = t3lib_div::makeInstance('Tx_Commerce_Tree_CategoryTree');
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
			$label_recur = $language->getLL('recursive');
			$label_levels = $language->getLL('levels');
			$label_pA = $language->getLL('pages_affected');
			$theIdListArr = array();

				// Put dummy entry so user is not forced to select
			$opts = '<option value=""></option>';

				// Traverse the number of levels we want to allow recursive setting of permissions for:
			for ($a = 0; $a <= $this->getLevels; $a ++) {
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
						<option value="' . htmlspecialchars(implode(',', $theIdListArr)) . '">
							' .
							t3lib_div::deHSCentities(htmlspecialchars($label_recur . ' ' . $lKey . ' ' . $label_levels)) .
							' (' . count($theIdListArr) . ' ' . $label_pA . ')
						</option>';
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
}

class_alias('Tx_Commerce_Controller_AccessController', 'SC_mod_access_perm_index');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/AccessController.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/AccessController.php']);
}

?>
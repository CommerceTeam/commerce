<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 - 2012 Ingo Schmitt <typo3@marketing-factory.de>
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

class Tx_Commerce_Controller_CategoriesController extends t3lib_SCbase {
	/**
	 * Treeitem Id for which to make the listing
	 *
	 * @public integer
	 */
	public $id;

	/**
	 * Pointer - for browsing list of records.
	 *
	 * @public integer
	 */
	public $pointer;

	/**
	 * Thumbnails or not
	 *
	 * @var string
	 */
	public $imagemode;

	/**
	 * Which table to make extended listing for
	 *
	 * @var string
	 */
	public $table;

	/**
	 * Search-fields
	 *
	 * @var string
	 */
	public $search_field;

	/**
	 * Search-levels
	 *
	 * @var integer
	 */
	public $search_levels;

	/**
	 * Show-limit
	 *
	 * @var integer
	 */
	public $showLimit;

	/**
	 * Return URL
	 *
	 * @var string
	 */
	public $returnUrl;

	/**
	 * Clear-cache flag - if set, clears page cache for current id.
	 *
	 * @var boolean
	 */
	public $clear_cache;

	/**
	 * Command: Eg. "delete" or "setCB" (for TCEmain / clipboard operations)
	 *
	 * @var string
	 */
	public $cmd;

	/**
	 * Table on which the cmd-action is performed.
	 *
	 * @var string
	 */
	public $cmd_table;

	/**
	 * Page select perms clause
	 *
	 * @var string
	 */
	public $perms_clause;

	/**
	 * Module TSconfig
	 *
	 * @var array
	 */
	public $modTSconfig;

	/**
	 * Current ids page record
	 *
	 * @var array
	 */
	public $pageinfo;

	/**
	 * Document template object
	 *
	 * @var template
	 */
	public $doc;

	/**
	 * Module configuration
	 *
	 * @var array
	 */
	public $MCONF = array();

	/**
	 * Menu configuration
	 *
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * Module settings (session variable)
	 *
	 * @var array
	 */
	public $MOD_SETTINGS = array();

	/**
	 * Module output accumulation
	 *
	 * @var string
	 */
	public $content;

	/**
	 * Array of tree leaf names and leaf data classes
	 *
	 * @var array
	 */
	public $control;

	/**
	 * the script calling this class
	 *
	 * @var string
	 */
	public $script;

	/**
	 * the script for the wizard of the command 'new'
	 *
	 * @var string
	 */
	public $scriptNewWizard;

	/**
	 * the paramters for the script
	 *
	 * @var string
	 */
	public $params;

	/**
	 * @var array
	 */
	public $controlArray;

	/**
	 * @var integer
	 */
	public $resCountAll;

	/**
	 * @var array
	 */
	protected $buttons = array();

	/**
	 * Initializing the module
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		Tx_Commerce_Utility_FolderUtility::init_folders();
		$this->control = array (
			'category' => array (
				'dataClass' => 'Tx_Commerce_Tree_Leaf_CategoryData',
				'parent' => 'parent_category'
			),
			'product' => array (
				'dataClass' => 'Tx_Commerce_Tree_Leaf_ProductData',
				'parent' => 'categories'
			)
		);

		$this->scriptNewWizard = 'wizard.php';

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = & $GLOBALS['BE_USER'];

			// Setting module configuration / page select clause
		$this->MCONF = $GLOBALS['MCONF'];
		$this->perms_clause = $backendUser->getPagePermsClause(1);

			// GPvars:
		$this->id = (int) t3lib_div::_GP('id');
		if (!$this->id) {
			$this->id = current(array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'Commerce', 0, 'Commerce')));
		}

			// Initialize the listing object, dblist, for rendering the list:
		$this->pointer = t3lib_div::intInRange(t3lib_div::_GP('pointer'), 0, 100000);
		$this->imagemode = t3lib_div::_GP('imagemode');
		$this->table = t3lib_div::_GP('table');

			// Get Tabpe and controlArray in a different way
		$controlParams = t3lib_div::_GP('control');
		if ($controlParams) {
			$this->table = key($controlParams);
			$this->controlArray = current($controlParams);
		}

		$this->search_field = t3lib_div::_GP('search_field');
		$this->search_levels = t3lib_div::_GP('search_levels');
		$this->showLimit = (int) t3lib_div::_GP('showLimit');
		$this->returnUrl = t3lib_div::_GP('returnUrl');

		$this->clear_cache = (boolean) t3lib_div::_GP('clear_cache');
		$this->cmd = t3lib_div::_GP('cmd');
		$this->cmd_table = t3lib_div::_GP('cmd_table');

			// Initialize menu
		$this->menuConfig();

			// Current script name with parameters
		$this->params = t3lib_div::getIndpEnv('QUERY_STRING');
			// Current script name
		$this->script = t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT');

			// Initializing document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_index.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set moduleTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_index.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_index.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_index.html';
			$this->doc->moduleTemplate = t3lib_div::getURL(PATH_site . $templateFile);
		}
	}

	/**
	 * Initialize function menu array
	 *
	 * @return	void
	 */
	public function menuConfig() {
			// MENU-ITEMS:
		$this->MOD_MENU = array(
			'bigControlPanel' => '',
			'clipBoard' => '',
			'localization' => ''
		);

			// Loading module configuration:
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.' . $this->MCONF['name']);

			// Clean up settings:
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Clears page cache for the current id, $this->id
	 *
	 * @return void
	 */
	public function clearCache() {
		if ($this->clear_cache) {
			/** @var t3lib_TCEmain $tce */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$tce->start(array(), array());
			$tce->clear_cacheCmd($this->id);
		}
	}

	/**
	 * Main function, starting the rendering of the list.
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
		$this->pageinfo = Tx_Commerce_Utility_BackendUtility::readCategoryAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);

			// Checking access:
		if (($this->id && $access) || $backendUser->isAdmin()) {
			if ($backendUser->isAdmin() && !$this->id) {
				$this->pageinfo = array('title' => '[Category]', 'uid' => 0, 'pid' => 0);
			}

				// Render content:
			$this->moduleContent();
		} else {
				// If no access or if ID == zero
			$this->content .= $this->doc->header($language->getLL('permissions'));
		}

		$docHeaderButtons = $this->getHeaderButtons();

		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content,
			'CATINFO' => $this->categoryInfo($this->pageinfo),
			'CATPATH' => $this->categoryPath($this->pageinfo),
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
		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return void
	 */
	protected function moduleContent() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = & $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = & $GLOBALS['LANG'];

			// default values for the new command
		$defVals = '';

		$listingProduced = FALSE;
		$formProduced = FALSE;
		$linkparam = array();
		$parent_uid = (int) $this->controlArray['uid'];

			// Initialize the dblist object:
		/** @var Tx_Commerce_ViewHelpers_CategoryRecordList $dblist */
		$dblist = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_CategoryRecordList');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->calcPerms = $backendUser->calcPerms($this->pageinfo);
		$dblist->thumbs = $backendUser->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;
		$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 1;
		$dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
		$dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dblist->clickTitleMode = $this->modTSconfig['properties']['clickTitleMode'];
		$dblist->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors'] ? 1 : 0;
		$dblist->allowedNewTables = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], 1);
		$dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;

		$newRecordIcon = '';
			// Link for creating new records:
		if (!$this->modTSconfig['properties']['noCreateRecordsLink']) {
			$sumlink = $this->scriptNewWizard . '?id=' . (int) $this->id;
			foreach ($this->control as $controldat) {
				$treedb = t3lib_div::makeInstance($controldat['dataClass']);
				$treedb->init();

				if ($treedb->getTable()) {
					$sumlink .= '&edit[' . $treedb->getTable() . '][-' . $parent_uid . ']=new';
					$tmpDefVals = '&defVals[' . $treedb->getTable() . '][' . $controldat['parent'] . ']=' . $parent_uid;
					$defVals .= $tmpDefVals;
				}
			}
			$sumlink .= $defVals;

			$newRecordIcon = '
				<!--
					Link for creating a new record:
				-->
				<a href="' . htmlspecialchars($sumlink .
					'&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-new', array('title' => $language->getLL('editPage', 1))) .
				'</a>';
		}

		$dblist->newRecordIcon = $newRecordIcon;

			// Clipboard is initialized:
			// Start clipboard
		$dblist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			// Initialize - reads the clipboard content from the user session
		$dblist->clipObj->initializeClipboard();

			// Clipboard actions are handled:
			// CB is the clipboard command array
		$CB = t3lib_div::_GET('CB');
		if ($this->cmd === 'setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge((array)t3lib_div::_POST('CBH'), (array)t3lib_div::_POST('CBC')), $this->cmd_table);
		}

			// If the clipboard is NOT shown, set the pad to 'normal'.
		if (!$this->MOD_SETTINGS['clipBoard']) {
			$CB['setP'] = 'normal';
		}

			// Execute commands.
		$dblist->clipObj->setCmd($CB);
			// Clean up pad
		$dblist->clipObj->cleanCurrent();
			// Save the clipboard content
		$dblist->clipObj->endClipboard();

		$this->buttons = $dblist->getHeaderButtons($this->pageinfo);

			// This flag will prevent the clipboard panel in being shown.
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = $GLOBALS['CLIENT']['FORMSTYLE'] && !$this->MOD_SETTINGS['bigControlPanel']
			&& $dblist->clipObj->current == 'normal' && !$backendUser->uc['disableCMlayers']
			&& !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];

			// Add JavaScript functions to the page:
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpToUrl(URL) {
				document.location = URL;
				return false;
			}
			function jumpExt(URL, anchor) {
				var anc = anchor?anchor:"";
				document.location = URL + (T3_THIS_LOCATION ? "&returnUrl=" + T3_THIS_LOCATION : "") + anc;
				return false;
			}
			function jumpSelf(URL) {
				document.location = URL + (T3_RETURN_URL ? "&returnUrl=" + T3_RETURN_URL : "");
				return false;
			}
			' . $this->doc->redirectUrls($dblist->listURL()) . '
			' . $dblist->CBfunctions() . '

			function editRecords(table,idList,addParams,CBflag) {
				document.location="' . $this->doc->backPath . 'alt_doc.php?returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
					'&edit["+table+"]["+idList+"]=edit"+addParams;
			}
			function editList(table, idList) {
				var list = "";
					// Checking how many is checked, how many is not
				var pointer = 0;
				var pos = idList.indexOf(",");

				while (pos != -1) {
					if (cbValue(table + "|" + idList.substr(pointer, pos-pointer))) {
						list += idList.substr(pointer, pos-pointer) + ",";
					}
					pointer = pos + 1;
					pos = idList.indexOf(",", pointer);
				}
				if (cbValue(table + "|" + idList.substr(pointer))) {
					list += idList.substr(pointer) + ",";
				}

				return list ? list : idList;
			}

			if (top.fsMod) {
				top.fsMod.recentIds["web"] = ' . (int) $this->id . ';
			}
		');

		foreach ($this->control as $controldat) {
			$dblist->HTMLcode = '';
			$treedb = t3lib_div::makeInstance($controldat['dataClass']);
			$treedb->init();

			$records = $treedb->getRecordsDbList($parent_uid);

			$this->resCountAll = count($records['pid'][$parent_uid]);

			if ($treedb->getTable()) {
				$linkparam[] = '&edit[' . $treedb->getTable() . '][-' . $parent_uid . ']=new';
				$tmpDefVals = '&defVals[' . $treedb->getTable() . '][' . $controldat['parent'] . ']=' . $parent_uid;
				$defVals .= $tmpDefVals;
			}

				// If there is access to the page, then render the list contents and set up the document template object:
			if ($this->resCountAll) {
				$this->table = ($treedb->getTable() ? $treedb->getTable() : $this->table);
				$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;

					// Deleting records...:
					// Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
				if ($this->cmd == 'delete') {
					$items = $dblist->clipObj->cleanUpCBC(t3lib_div::_POST('CBC'), $this->cmd_table, 1);
					if (count($items)) {
						$cmd = array();
						reset($items);
						while (list($iK) = each($items)) {
							$iKParts = explode('|', $iK);
							$cmd[$iKParts[0]][$iKParts[1]]['delete'] = 1;
						}

						/** @var t3lib_TCEmain $tce */
						$tce = t3lib_div::makeInstance('t3lib_TCEmain');
						$tce->stripslashes_values = 0;
						$tce->start(array(), $cmd);
						$tce->process_cmdmap();

						if (isset($cmd['pages'])) {
							t3lib_BEfunc::setUpdateSignal('updatePageTree');
						}

						$tce->printLogErrorMessages(t3lib_div::getIndpEnv('REQUEST_URI'));
					}
				}

					// Get the list of uids
				$uids = array();

				for ($i = 0; $i < $this->resCountAll; $i++) {
					$uids[] = $records['pid'][$parent_uid][$i]['uid'];
				}

				$dblist->uid = implode(',', $uids);
					// uid of the parent category
				$dblist->parent_uid = $parent_uid;

				$dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
				$dblist->setDispFields();

					// $defVal for Tableheader;
				$dblist->defVals = $defVals;

					// Render versioning selector:
				$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
					// Render the list of tables:
				$dblist->generateList();

					// Write the bottom of the page:
				$dblist->writeBottom();
			}

			if (!$formProduced) {
				$formProduced = TRUE;

					// Begin to compile the whole page, starting out with page header:
				$this->doc->form .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';

					// List Module CSH:
				if (!strlen($this->id)) {
					$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_noId', $GLOBALS['BACK_PATH'], '<br/>|');
				} elseif (!$this->id) {
					$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_root', $GLOBALS['BACK_PATH'], '<br/>|');
				}
			}
			if ($dblist->HTMLcode) {
				$listingProduced = TRUE;

					// Add listing HTML code:
				$this->content .= $dblist->HTMLcode;

					// Making field select box (when extended view for a single table is enabled):
				$this->content .= $dblist->fieldSelectBox($dblist->table);
			}

		}

		if ($formProduced) {
			$this->content .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';

				// List Module CSH:
			if ($this->id) {
				$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module', $GLOBALS['BACK_PATH'], '<br/>|');
			}
		} else {
				// This is always needed to get the correct page layout
				// Begin to compile the whole page, starting out with page header:
			$this->content = $this->doc->startPage('DB list');
		}

			// If a listing was produced, create the page footer with search form etc:
		if ($listingProduced) {

				// Search box:
			$this->content .= $dblist->getSearchBox();

				// Display sys-notes, if any are found:
			$this->content .= $dblist->showSysNotesForPage();
		}
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

		$buttons = $this->buttons;

			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_commerce_statistic', '', $GLOBALS['BACK_PATH'], '', TRUE);

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
	 * Returns the info for the Category Path
	 *
	 * @param array $row - Row
	 * @return string
	 */
	public function categoryInfo($row) {
			// Add icon with clickmenu, etc:
			// If there IS a real page
		if ($row['uid']) {
			$alttext = t3lib_BEfunc::getRecordIconAltText($row, 'tx_commerce_categories');
			$iconImg = t3lib_iconWorks::getIconImage(
				'tx_commerce_categories',
				$row,
				$this->doc->backPath,
				'class="absmiddle" title="' . htmlspecialchars($alttext) . '"'
			);
			// On root-level of page tree
		} else {
				// Make Icon
			$iconImg = t3lib_iconWorks::getSpriteIcon('apps-pagetree-root');
		}

			// Setting icon with clickmenu + uid
		$pageInfo = $iconImg . '<em>[pid: ' . $row['uid'] . ']</em>';
		return $pageInfo;
	}

	/**
	 * Returns the Category Path info
	 *
	 * @param array $row Row
	 * @return string
	 */
	public function categoryPath($row) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$title = $row['title'];

			// Setting the path of the page
		$pagePath = $language->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span class="typo3-docheader-pagePath">' .
			htmlspecialchars(t3lib_div::fixed_lgd_cs($title, -50)) . '</span>';
		return $pagePath;
	}
}

class_alias('Tx_Commerce_Controller_CategoriesController', 'tx_commerce_categories');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/CategoriesController.php']) {
		/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/CategoriesController.php']);
}

?>
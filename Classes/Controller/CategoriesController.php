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
	 * @var array
	 */
	public $MOD_MENU = array(
		'function' => array(),
		'bigControlPanel' => '',
		'clipBoard' => '',
		'localization' => ''
	);


	/**
	 * the script for the wizard of the command 'new'
	 *
	 * @var string
	 */
	public $scriptNewWizard = 'wizard.php';

	/**
	 * @var array
	 */
	public $controlArray;

	/**
	 * @var integer
	 */
	public $categoryUid = 0;

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
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = & $GLOBALS['BE_USER'];

			// Setting module configuration / page select clause
		$this->MCONF = $GLOBALS['MCONF'];
		$this->perms_clause = $backendUser->getPagePermsClause(1);

			// GPvars:
		$this->id = (int) t3lib_div::_GP('id');
		if (!$this->id) {
			Tx_Commerce_Utility_FolderUtility::init_folders();
			$this->id = current(array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'Commerce', 0, 'Commerce')));
		}

			// Initialize the listing object, dblist, for rendering the list:
		$this->pointer = t3lib_div::intInRange(t3lib_div::_GP('pointer'), 0, 100000);
		$this->imagemode = t3lib_div::_GP('imagemode');
		$this->table = t3lib_div::_GP('table');
		$this->search_field = t3lib_div::_GP('search_field');
		$this->search_levels = t3lib_div::_GP('search_levels');
		$this->showLimit = (int) t3lib_div::_GP('showLimit');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));

		$this->clear_cache = (boolean) t3lib_div::_GP('clear_cache');
		$this->cmd = t3lib_div::_GP('cmd');
		$this->cmd_table = t3lib_div::_GP('cmd_table');

			// Initialize menu
		$this->menuConfig();

			// Inclusions?
		if ($this->clear_cache || $this->cmd == 'delete') {
			$this->include_once[] = PATH_t3lib . 'class.t3lib_tcemain.php';
		}

			// Get Tabpe and controlArray in a different way
		$controlParams = t3lib_div::_GP('control');
		if ($controlParams) {
			$this->controlArray = current($controlParams);
			$this->categoryUid = (int) $this->controlArray['uid'];
		}
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

		$newRecordIcon = '';
			// Link for creating new records:
		if (!$this->modTSconfig['properties']['noCreateRecordsLink']) {
			$controlls = array (
				'category' => array (
					'dataClass' => 'Tx_Commerce_Tree_Leaf_CategoryData',
					'parent' => 'parent_category'
				),
				'product' => array (
					'dataClass' => 'Tx_Commerce_Tree_Leaf_ProductData',
					'parent' => 'categories'
				)
			);

			$sumlink = $this->scriptNewWizard . '?id=' . (int) $this->id;
			$defVals = '';
			foreach ($controlls as $controldat) {
				$treedb = t3lib_div::makeInstance($controldat['dataClass']);
				$treedb->init();

				if ($treedb->getTable()) {
					$sumlink .= '&edit[' . $treedb->getTable() . '][-' . $this->categoryUid . ']=new';
					$tmpDefVals = '&defVals[' . $treedb->getTable() . '][' . $controldat['parent'] . ']=' . $this->categoryUid;
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

			// Access check...
			// The page will show only if there is a valid page and if this page may be viewed by the user
		if ($this->categoryUid) {
			$this->pageinfo = Tx_Commerce_Utility_BackendUtility::readCategoryAccess($this->categoryUid, $this->perms_clause);
		} else {
			$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		}
		$access = is_array($this->pageinfo);

			// Apply predefined values for hidden checkboxes
			// Set predefined value for DisplayBigControlPanel:
		if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'activated') {
			$this->MOD_SETTINGS['bigControlPanel'] = TRUE;
		} elseif ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'deactivated') {
			$this->MOD_SETTINGS['bigControlPanel'] = FALSE;
		}

			// Set predefined value for Clipboard:
		if ($this->modTSconfig['properties']['enableClipBoard'] === 'activated') {
			$this->MOD_SETTINGS['clipBoard'] = TRUE;
		} elseif ($this->modTSconfig['properties']['enableClipBoard'] === 'deactivated') {
			$this->MOD_SETTINGS['clipBoard'] = FALSE;
		}

			// Set predefined value for LocalizationView:
		if ($this->modTSconfig['properties']['enableLocalizationView'] === 'activated') {
			$this->MOD_SETTINGS['localization'] = TRUE;
		} elseif ($this->modTSconfig['properties']['enableLocalizationView'] === 'deactivated') {
			$this->MOD_SETTINGS['localization'] = FALSE;
		}

			// Initialize the dblist object:
		/** @var $dblist Tx_Commerce_ViewHelpers_CategoryRecordList */
		$dblist = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_CategoryRecordList');
		$dblist->backPath = $this->doc->backPath;
		$dblist->script = 'index.php';
		$dblist->calcPerms = $backendUser->calcPerms($this->pageinfo);
		$dblist->thumbs = $backendUser->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;
		$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 1;
		$dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
		$dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
		$dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
		$dblist->clickTitleMode = $this->modTSconfig['properties']['clickTitleMode'];
		$dblist->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors']?1:0;
		$dblist->allowedNewTables = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], 1);
		$dblist->deniedNewTables = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], 1);
		$dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
		$dblist->pageRow = $this->pageinfo;
		$dblist->counter++;
		$dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
		$dblist->modTSconfig = $this->modTSconfig;
		$dblist->newRecordIcon = $newRecordIcon;

		$dblist->tableList = 'tx_commerce_categories,tx_commerce_products';
		$dblist->parentUid = $this->categoryUid;

			// Clipboard is initialized:
			// Start clipboard
		$dblist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			// Initialize - reads the clipboard content from the user session
		$dblist->clipObj->initializeClipboard();

			// Clipboard actions are handled:
			// CB is the clipboard command array
		$CB = t3lib_div::_GET('CB');
		if ($this->cmd == 'setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge((array) t3lib_div::_POST('CBH'), (array) t3lib_div::_POST('CBC')), $this->cmd_table);
		}
		if (!$this->MOD_SETTINGS['clipBoard']) {
				// If the clipboard is NOT shown, set the pad to 'normal'.
			$CB['setP'] = 'normal';
		}
			// Execute commands.
		$dblist->clipObj->setCmd($CB);
			// Clean up pad
		$dblist->clipObj->cleanCurrent();
			// Save the clipboard content
		$dblist->clipObj->endClipboard();

			// This flag will prevent the clipboard panel in being shown.
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = $GLOBALS['CLIENT']['FORMSTYLE']
			&& !$this->MOD_SETTINGS['bigControlPanel']
			&& $dblist->clipObj->current == 'normal'
			&& !$backendUser->uc['disableCMlayers']
			&& !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];

		if ($access) {
				// Deleting records...:
				// Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
			if ($this->cmd == 'delete') {
				$items = $dblist->clipObj->cleanUpCBC(t3lib_div::_POST('CBC'), $this->cmd_table, 1);
				if (count($items)) {
					$cmd = array();
					foreach ($items as $iK => $value) {
						$iKParts = explode('|', $iK);
						$cmd[$iKParts[0]][$iKParts[1]]['delete'] = 1;
					}
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

				// Initialize the listing object, dblist, for rendering the list:
			$dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
			$dblist->setDispFields();

				// Render versioning selector:
			if (t3lib_extMgm::isLoaded('version')) {
				$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
			}

			$dblist->parentUid = $this->categoryUid;

				// Render the list of tables:
			$dblist->generateList();

				// Write the bottom of the page:
			$dblist->writeBottom();
			$listUrl = substr($dblist->listURL(), strlen($GLOBALS['BACK_PATH']));
				// Add JavaScript functions to the page:
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}
				function jumpExt(URL, anchor) {
					var anc = anchor ? anchor : "";
					window.location.href = URL + (T3_THIS_LOCATION ? "&returnUrl=" + T3_THIS_LOCATION : "") + anc;
					return false;
				}
				function jumpSelf(URL) {
					window.location.href = URL + (T3_RETURN_URL ? "&returnUrl=" + T3_RETURN_URL : "");
					return false;
				}

				function setHighlight(id) {
					top.fsMod.recentIds["web"] = id;
					top.fsMod.navFrameHighlightedID["web"] = "pages" + id + "_" + top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
						top.content.nav_frame.refresh_nav();
					}
				}
				' . $this->doc->redirectUrls($listUrl) . '
				' . $dblist->CBfunctions() . '
				function editRecords(table, idList, addParams, CBflag) {
					window.location.href = "' . $this->doc->backPath . 'alt_doc.php?returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
						'&edit[" + table + "][" + idList + "]=edit" + addParams;
				}
				function editList(table, idList) {
					var list = "";

						// Checking how many is checked, how many is not
					var pointer = 0;
					var pos = idList.indexOf(",");
					while (pos != -1) {
						if (cbValue(table + "|" + idList.substr(pointer, pos-pointer))) {
							list += idList.substr(pointer, pos - pointer) + ",";
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
					top.fsMod.recentIds["web"] = ' . $this->id . ';
				}
			');

				// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
		}

			// Begin to compile the whole page, starting out with page header:
		$this->content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
		$this->content .= $dblist->HTMLcode;
		$this->content .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';

			// If a listing was produced, create the page footer with search form etc:
		if ($dblist->HTMLcode) {

				// Making field select box (when extended view for a single table is enabled):
			if ($dblist->table) {
				$this->content .= $dblist->fieldSelectBox($dblist->table);
			}

				// Adding checkbox options for extended listing and clipboard display:
			$this->content .= '

					<!--
						Listing options for extended view, clipboard and localization view
					-->
					<div id="typo3-listOptions">
						<form action="" method="post">';

				// Add "display bigControlPanel" checkbox:
			if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
				$this->content .= t3lib_BEfunc::getFuncCheck(
					$this->id,
					'SET[bigControlPanel]',
					$this->MOD_SETTINGS['bigControlPanel'],
					'',
					($this->table ? '&table=' . $this->table : ''),
					'id="checkLargeControl"'
				);
				$this->content .= '<label for="checkLargeControl">' .
					t3lib_BEfunc::wrapInHelp(
						'xMOD_csh_corebe',
						'list_options',
						$language->getLL('largeControl', TRUE)
					) . '</label><br />';
			}

				// Add "clipboard" checkbox:
			if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
				if ($dblist->showClipboard) {
					$this->content .= t3lib_BEfunc::getFuncCheck(
						$this->id,
						'SET[clipBoard]',
						$this->MOD_SETTINGS['clipBoard'],
						'',
						($this->table ? '&table=' . $this->table : ''),
						'id="checkShowClipBoard"'
					);
					$this->content .= '<label for="checkShowClipBoard">' .
						t3lib_BEfunc::wrapInHelp(
							'xMOD_csh_corebe',
							'list_options',
							$language->getLL('showClipBoard', TRUE)
						) . '</label><br />';
				}
			}

				// Add "localization view" checkbox:
			if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
				$this->content .= t3lib_BEfunc::getFuncCheck(
					$this->id,
					'SET[localization]',
					$this->MOD_SETTINGS['localization'],
					'',
					($this->table ? '&table=' . $this->table : ''),
					'id="checkLocalization"'
				);
				$this->content .= '<label for="checkLocalization">' .
					t3lib_BEfunc::wrapInHelp(
						'xMOD_csh_corebe',
						'list_options',
						$language->getLL('localization', TRUE)
					) . '</label><br />';
			}

			$this->content .= '
						</form>
					</div>';

				// Printing clipboard if enabled:
			if ($this->MOD_SETTINGS['clipBoard'] && $dblist->showClipboard) {
				$this->content .= $dblist->clipObj->printClipboard();
			}

				// Search box:
			$sectionTitle = t3lib_BEfunc::wrapInHelp('xMOD_csh_corebe', 'list_searchbox', $language->sL('LLL:EXT:lang/locallang_core.php:labels.search', TRUE));
			$this->content .= $this->doc->section(
				$sectionTitle,
				$dblist->getSearchBox(),
				FALSE, TRUE, FALSE, TRUE
			);

				// Display sys-notes, if any are found:
			$this->content .= $dblist->showSysNotesForPage();
		}

		$this->buttons = $dblist->getButtons($this->pageinfo);
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

		$buttons = $this->buttons;

			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_commerce_categories', '', $GLOBALS['BACK_PATH'], '', TRUE);

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
}

class_alias('Tx_Commerce_Controller_CategoriesController', 'tx_commerce_categories');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/CategoriesController.php']) {
		/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/CategoriesController.php']);
}

?>
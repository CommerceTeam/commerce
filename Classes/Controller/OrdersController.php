<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_Commerce_Controller_OrdersController extends t3lib_SCbase {
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
	 * Array, where files to include is accumulated in the init() function
	 *
	 * @var array
	 */
	public $include_once = array();

	/**
	 * Module output accumulation
	 *
	 * @var string
	 */
	public $content;


	/**
	 * the script for the wizard of the command 'new'
	 *
	 * @var string
	 */
		// public $scriptNewWizard = 'wizard.php';

	/**
	 * @var array
	 */
	public $controlArray;

	/**
	 * @var array
	 */
	protected $buttons = array();

	/**
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

		Tx_Commerce_Utility_FolderUtility::init_folders();

			// Find the right pid for the Ordersfolder
		$orderPid = current(array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce')));;

		if ($this->id == $orderPid) {
			$this->id = 0;
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
				// $this->table = key($controlParams);
			$this->controlArray = current($controlParams);
		}
	}

	/**
	 * Initializes the Page
	 *
	 * @return void
	 */
	public function initPage() {
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_index.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set moduleTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_index.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_index.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_index.html';
			$this->doc->moduleTemplate = t3lib_div::getURL(PATH_site . $templateFile);
		}

		$this->doc->form = '<form action="" method="POST">';
	}

	/**
	 * Initialize function menu array
	 *
	 * @return void
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
	 * Main function of the module. Write the content to $this->content
	 */
	public function main() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Loading current page record and checking access:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

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
		/** @var $dblist Tx_Commerce_ViewHelpers_OrderRecordList */
		$dblist = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_OrderRecordList');
		$dblist->backPath = $this->doc->backPath;
		$dblist->script = t3lib_BEfunc::getModuleUrl('web_list', array(), '');
		$dblist->calcPerms = $backendUser->calcPerms($this->pageinfo);
		$dblist->thumbs = $backendUser->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;
		$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 0;
		$dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
		$dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
		$dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
		$dblist->clickTitleMode = $this->modTSconfig['properties']['clickTitleMode'];
		$dblist->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors'] ? 1 : 0;
		$dblist->allowedNewTables = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], 1);
		$dblist->deniedNewTables = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], 1);
		$dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
		$dblist->pageRow = $this->pageinfo;
		$dblist->counter++;
		$dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
		$dblist->modTSconfig = $this->modTSconfig;

		$dblist->tableList = 'tx_commerce_orders';

			// Clipboard is initialized:
			// Start clipboard
			/** @var t3lib_clipboard clipObj */
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

			// If there is access to the page, then render the list contents and set up the document template object:
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

				// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = t3lib_div::intInRange($this->pointer, 0, 100000);
			$dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
			$dblist->setDispFields();

				// Render versioning selector:
			if (t3lib_extMgm::isLoaded('version')) {
				$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
			}

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
						if (cbValue(table + "|" + idList.substr(pointer, pos - pointer))) {
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

				// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
		}

			// Begin to compile the whole page, starting out with page header:
		$this->content = '';
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
		$this->content = $this->doc->startPage($language->getLL('orders'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Prints out the module HTML
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
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_commerce_orders', '', $GLOBALS['BACK_PATH'], '', TRUE);

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
}

class_alias('Tx_Commerce_Controller_OrdersController', 'tx_commerce_orders');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/OrdersController.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/OrdersController.php']);
}

?>
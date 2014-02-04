<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2008 - 2012 Ingo Schmitt <typo3@marketing-factory.de>
 * (c) 2013 Sebastian Fischer <typo3@marketing-factory.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/** @var language $language */
$language = & $GLOBALS['LANG'];
$language->includeLLFile('EXT:lang/locallang_mod_web_list.xml');

/**
 * Used for rendering a list of records for a tree item
 * The original file (typo3/db_list.php) could not be used only because of the object intantiation at the bottom of the file.
 */
class Tx_Commerce_ViewHelpers_DatabaseList {
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
	 * @public template
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
		$this->pointer = (int) t3lib_div::_GP('pointer');
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
		$backendUser = & $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = & $GLOBALS['LANG'];

			// default values for the new command
		$defVals = '';

		$listingProduced = FALSE;
		$formProduced = FALSE;
		$linkparam = array();
		$parent_uid = (is_null($this->controlArray['uid'])) ? 0 : $this->controlArray['uid'];
			// Start document template object:
		$this->doc = t3lib_div::makeInstance('template');

		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';

			// Loading current page record and checking access:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// Initialize the dblist object:
		/** @var Tx_Commerce_ViewHelpers_DatabaseListExtra $dblist */
		$dblist = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_DatabaseListExtra');
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

		$newRecordLink = $newRecordIcon = '';
			// Link for creating new records:
		if (!$this->modTSconfig['properties']['noCreateRecordsLink']) {
			$sumlink = $this->scriptNewWizard . '?id=' . intval($this->id);
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
				<a href="' . htmlspecialchars($this->scriptNewWizard . '?id=' . $this->id . $sumlink .
					'&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-new', array('title' => $language->getLL('editPage', 1))) .
				'</a>';

			$newRecordLink = '
				<!--
					Link for creating a new record:
				-->
				<div id="typo3-newRecordLink">
					<a href="' . htmlspecialchars($this->scriptNewWizard . '?id=' . $this->id . $sumlink .
						'&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-new', array('title' => $language->getLL('editPage', 1))) .
						$language->getLL('newRecordGeneral', 1) .
					'</a>
				</div>';
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

			// This flag will prevent the clipboard panel in being shown.
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = $GLOBALS['CLIENT']['FORMSTYLE'] && !$this->MOD_SETTINGS['bigControlPanel']
			&& $dblist->clipObj->current == 'normal' && !$backendUser->uc['disableCMlayers']
			&& !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];

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
			if ($access && $this->resCountAll) {
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

					// Initialize the listing object, dblist, for rendering the list:
				$this->pointer = t3lib_div::intInRange($this->pointer, 0, 100000);

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

				if (!$listingProduced) {
						// Render the page header:
					$dblist->writeTop($this->pageinfo);
				}
					// Render versioning selector:
				$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
					// Render the list of tables:

				$dblist->generateList();

					// Write the bottom of the page:
				$dblist->writeBottom();
			}

				// If there is access to the page, then render the JavaScript for the clickmenu
			if ($access) {
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

					if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
				');

					// Setting up the context sensitive menu:
				$CMparts = $this->doc->getContextMenuCode();
				$this->doc->bodyTagAdditions = $CMparts[1];
				$this->doc->JScode .= $CMparts[0];
				$this->doc->postCode .= $CMparts[2];
			}

			if (!$formProduced) {
				$formProduced = TRUE;

					// Begin to compile the whole page, starting out with page header:
				$this->content = $this->doc->startPage('DB list');
				$this->content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';

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

				// Adding checkbox options for extended listing and clipboard display:
			$this->content .= '

					<!--
						Listing options for clipboard and thumbnails
					-->
					<div id="typo3-listOptions">
						<form action="" method="post">';

			$this->content .= t3lib_BEfunc::getFuncCheck(
				$this->id,
				'SET[bigControlPanel]',
				$this->MOD_SETTINGS['bigControlPanel'],
				$this->script,
				$this->params
			) . ' ' . $language->getLL('largeControl', 1) . '<br />';
			$this->content .= t3lib_BEfunc::getFuncCheck(
				$this->id,
				'SET[localization]',
				$this->MOD_SETTINGS['localization'],
				$this->script,
				$this->params
			) . ' ' . $language->getLL('localization', 1) . '<br />';
			$this->content .= '
						</form>
					</div>';
			$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_options', $GLOBALS['BACK_PATH']);

			$this->content .= $newRecordLink;

				// Search box:
			$this->content .= $dblist->getSearchBox();

				// Display sys-notes, if any are found:
			$this->content .= $dblist->showSysNotesForPage();

				// ShortCut:
			if ($backendUser->mayMakeShortcut()) {
				$this->content .= '<br/>' . $this->doc->makeShortcutIcon(
					'id,imagemode,pointer,table,search_field,search_levels,showLimit,sortField,sortRev',
					implode(',', array_keys($this->MOD_MENU)),
					$this->MCONF['name']
				);
			}
		} else {
			$this->content .= $newRecordLink;
		}

			// Finally, close off the page:
		$this->content .= $this->doc->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	public function printContent() {
		echo $this->content;
	}
}

class_alias('Tx_Commerce_ViewHelpers_DatabaseList', 'tx_commerce_db_list');

	// XClass Statement
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/DatabaseList.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/DatabaseList.php']);
}

?>
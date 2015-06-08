<?php
/**
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Tx_Commerce_Controller_OrdersModuleController extends \TYPO3\CMS\Recordlist\RecordList {
	/**
	 * the script for the wizard of the command 'new'
	 *
	 * @var string
	 */
	public $scriptNewWizard = 'wizard.php';

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @var int
	 */
	protected $orderPid;

	/**
	 * @return void
	 */
	public function init() {
		$this->getLanguageService()->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_orders.xml');
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_list.php');

		// Setting GPvars:
		$this->id = (int) GeneralUtility::_GP('id');
		// Find the right pid for the Ordersfolder
		$this->orderPid = current(
			array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce'))
		);
		if ($this->id == $this->orderPid) {
			$this->id = 0;
		}

		// Initialize the listing object, dblist, for rendering the list:
		$this->pointer = max(min(GeneralUtility::_GP('pointer'), 100000), 0);
		$this->imagemode = GeneralUtility::_GP('imagemode');
		$this->table = GeneralUtility::_GP('table');
		$this->search_field = GeneralUtility::_GP('search_field');
		$this->search_levels = GeneralUtility::_GP('search_levels');
		$this->showLimit = (int) GeneralUtility::_GP('showLimit');
		$this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

		$this->clear_cache = (bool) GeneralUtility::_GP('clear_cache');
		$this->cmd = GeneralUtility::_GP('cmd');
		$this->cmd_table = GeneralUtility::_GP('cmd_table');

		// Module name;
		$this->MCONF = $GLOBALS['MCONF'];
		// Page select clause:
		$this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);

		$this->initPage();
		$this->clearCache();

		// Set up menus:
		$this->menuConfig();
	}

	/**
	 * Initializes the Page
	 *
	 * @return void
	 */
	public function initPage() {
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_index.html');

		if (!$this->doc->moduleTemplate) {
			GeneralUtility::devLog('cannot set moduleTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_index.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_index.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_index.html';
			$this->doc->moduleTemplate = GeneralUtility::getURL(PATH_site . $templateFile);
		}

		$this->doc->form = '<form action="" method="POST">';
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return void
	 */
	public function main() {
		$language = $this->getLanguageService();

			// Loading current page record and checking access:
		$this->pageinfo = BackendUtility::readPageAccess($this->id ? $this->id : $this->orderPid, $this->perms_clause);
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
		/**
		 * Order record list
		 *
		 * @var $dblist Tx_Commerce_ViewHelpers_OrderRecordList
		 */
		$dblist = GeneralUtility::makeInstance('Tx_Commerce_ViewHelpers_OrderRecordList');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->script = BackendUtility::getModuleUrl('txcommerceM1_orders', array(), '');
		$dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
		$dblist->thumbs = $this->getBackendUser()->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;
		$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 1;
		$dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
		$dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
		$dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
		$dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
		$dblist->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors'] ? 1 : 0;
		$dblist->allowedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], 1);
		$dblist->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], 1);
		$dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
		$dblist->pageRow = $this->pageinfo;
		$dblist->counter++;
		$dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
		$dblist->modTSconfig = $this->modTSconfig;
		$clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
		$dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;

		$dblist->tableList = 'tx_commerce_orders';
		$dblist->orderPid = $this->orderPid;

		// Clipboard is initialized:
		// Start clipboard
		/**
		 * Clipboard
		 *
		 * @var \TYPO3\CMS\Backend\Clipboard\Clipboard $clipObj
		 */
		$clipObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
		$dblist->clipObj = $clipObj;
		// Initialize - reads the clipboard content from the user session
		$dblist->clipObj->initializeClipboard();
		// Clipboard actions are handled:
		// CB is the clipboard command array
		$clipboard = GeneralUtility::_GET('CB');
		if ($this->cmd == 'setCB') {
			// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
			// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$clipboard['el'] = $dblist->clipObj->cleanUpCBC(
				array_merge((array) GeneralUtility::_POST('CBH'), (array) GeneralUtility::_POST('CBC')),
				$this->cmd_table
			);
		}
		if (!$this->MOD_SETTINGS['clipBoard']) {
			// If the clipboard is NOT shown, set the pad to 'normal'.
			$clipboard['setP'] = 'normal';
		}
		// Execute commands.
		$dblist->clipObj->setCmd($clipboard);
		// Clean up pad
		$dblist->clipObj->cleanCurrent();
		// Save the clipboard content
		$dblist->clipObj->endClipboard();

		// This flag will prevent the clipboard panel in being shown.
		// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = (
			!$this->MOD_SETTINGS['bigControlPanel'] &&
			$dblist->clipObj->current == 'normal' &&
			!$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']
		);

		// If there is access to the page, then render the list contents and set up the document template object:
		if ($access) {
			// Deleting records...:
			// Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
			if ($this->cmd == 'delete') {
				$items = $dblist->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
				if (count($items)) {
					$cmd = array();
					foreach (array_keys($items) as $iK) {
						$iKparts = explode('|', $iK);
						$cmd[$iKparts[0]][$iKparts[1]]['delete'] = 1;
					}
					/**
					 * Data handler
					 *
					 * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
					 */
					$tce = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = 0;
					$tce->start(array(), $cmd);
					$tce->process_cmdmap();

					if (isset($cmd['pages'])) {
						BackendUtility::setUpdateSignal('updateFolderTree');
					}

					$tce->printLogErrorMessages(GeneralUtility::getIndpEnv('REQUEST_URI'));
				}
			}

				// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = max(min($this->pointer, 100000), 0);
			$dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
			$dblist->setDispFields();

				// Render versioning selector:
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
				$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
			}

			// Render the list of tables:
			$dblist->generateList();
			$listUrl = $dblist->listURL();
				// Add JavaScript functions to the page:
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function jumpExt(URL,anchor) {	//
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {	//
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}

				function setHighlight(id) {	//
					top.fsMod.recentIds["web"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
						top.content.nav_frame.refresh_nav();
					}
				}
				' . $this->doc->redirectUrls($listUrl) . '
				' . $dblist->CBfunctions() . '
				function editRecords(table, idList, addParams, CBflag) {
					window.location.href = "' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
					rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '&edit[" + table + "][" + idList + "]=edit" + addParams;
				}
				function editList(table,idList) {	//
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1) {
						if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
							list+=idList.substr(pointer,pos-pointer)+",";
						}
						pointer=pos+1;
						pos = idList.indexOf(",",pointer);
					}
					if (cbValue(table+"|"+idList.substr(pointer))) {
						list+=idList.substr(pointer)+",";
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

		// access
		// Begin to compile the whole page, starting out with page header:
		$this->body = $this->doc->header($this->pageinfo['title']);
		$this->body .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
		$this->body .= $dblist->HTMLcode;
		$this->body .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
		// If a listing was produced, create the page footer with search form etc:
		if ($dblist->HTMLcode) {
			// Making field select box (when extended view for a single table is enabled):
			if ($dblist->table) {
				$this->body .= $dblist->fieldSelectBox($dblist->table);
			}
			// Adding checkbox options for extended listing and clipboard display:
			$this->body .= '

					<!--
						Listing options for extended view, clipboard and localization view
					-->
					<div id="typo3-listOptions">
						<form action="" method="post">';

			// Add "display bigControlPanel" checkbox:
			if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
				$this->body .= BackendUtility::getFuncCheck(
					$this->id, 'SET[bigControlPanel]', $this->MOD_SETTINGS['bigControlPanel'], '',
					$this->table ? '&table=' . $this->table : '', 'id="checkLargeControl"'
				);
				$this->body .= '<label for="checkLargeControl">' .
					BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $language->getLL('largeControl', TRUE)) .
					'</label><br />';
			}

			// Add "clipboard" checkbox:
			if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
				if ($dblist->showClipboard) {
					$this->body .= BackendUtility::getFuncCheck(
						$this->id, 'SET[clipBoard]', $this->MOD_SETTINGS['clipBoard'], '',
						$this->table ? '&table=' . $this->table : '', 'id="checkShowClipBoard"'
					);
					$this->body .= '<label for="checkShowClipBoard">' .
						BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $language->getLL('showClipBoard', TRUE)) .
						'</label><br />';
				}
			}

			// Add "localization view" checkbox:
			if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
				$this->body .= BackendUtility::getFuncCheck(
					$this->id, 'SET[localization]', $this->MOD_SETTINGS['localization'], '',
					$this->table ? '&table=' . $this->table : '', 'id="checkLocalization"'
				);
				$this->body .= '<label for="checkLocalization">' .
					BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $language->getLL('localization', TRUE)) .
					'</label><br />';
			}
			$this->body .= '
						</form>
					</div>';
		}
		// Printing clipboard if enabled
		if ($this->MOD_SETTINGS['clipBoard'] && $dblist->showClipboard && ($dblist->HTMLcode || $dblist->clipObj->hasElements())) {
			$this->body .= '<div class="db_list-dashboard">' . $dblist->clipObj->printClipboard() . '</div>';
		}
		// Search box:
		if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dblist->HTMLcode || $dblist->searchString !== '')) {
			$sectionTitle = BackendUtility::wrapInHelp(
				'xMOD_csh_corebe', 'list_searchbox', $language->sL('LLL:EXT:lang/locallang_core.xlf:labels.search', TRUE)
			);
			$this->body .= '<div class="db_list-searchbox">' .
				$this->doc->section($sectionTitle, $dblist->getSearchBox(), FALSE, TRUE, FALSE, TRUE) .
				'</div>';
		}
		// Additional footer content
		$footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/mod1/index.php']['drawFooterHook'];
		if (is_array($footerContentHook)) {
			foreach ($footerContentHook as $hook) {
				$params = array();
				$this->body .= GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}

		$buttons = $dblist->getButtons($this->pageinfo);
		$docHeaderButtons = $this->getHeaderButtons($buttons);

		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->body,
			'EXTRACONTAINERCLASS' => $this->table ? 'singletable' : ''
		);

		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render('DB list', $this->content);
	}

	/**
	 * Create the panel of buttons for submitting the
	 * form or otherwise perform operations.
	 *
	 * @param array $buttons Button
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getHeaderButtons(array $buttons) {
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();

		// CSH
		$buttons['csh'] = BackendUtility::cshItem('_MOD_commerce_orders', '', $GLOBALS['BACK_PATH'], '', TRUE);

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
				rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
				\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(
					'apps-filetree-folder-list',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1))
				) . '</a>';
		}
		return $buttons;
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

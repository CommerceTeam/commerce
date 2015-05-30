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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Tx_Commerce_Controller_CategoryModuleController
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class Tx_Commerce_Controller_CategoryModuleController extends \TYPO3\CMS\Recordlist\RecordList {
	/**
	 * The script for the wizard of the command 'new'
	 *
	 * @var string
	 */
	public $scriptNewWizard = 'wizard.php';

	/**
	 * Category uid
	 *
	 * @var int
	 */
	public $categoryUid = 0;

	/**
	 * Body content
	 *
	 * @var string
	 */
	protected $body;

	/**
	 * Initializing the module
	 *
	 * @return void
	 */
	public function init() {
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_list.xml');

		// Setting GPvars:
		$this->id = (int) GeneralUtility::_GP('id');
		if (!$this->id) {
			Tx_Commerce_Utility_FolderUtility::initFolders();
			$this->id = current(
				array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'Commerce', 0, 'Commerce'))
			);
		}

		// Initialize the listing object, dblist, for rendering the list:
		$this->pointer = max(min(GeneralUtility::_GP('pointer'), 100000), 0);
		$this->imagemode = GeneralUtility::_GP('imagemode');
		$this->table = GeneralUtility::_GP('table');
		$this->search_field = GeneralUtility::_GP('search_field');
		$this->search_levels = GeneralUtility::_GP('search_levels');
		$this->showLimit = (int) GeneralUtility::_GP('showLimit');
		$this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

		$this->clear_cache = (boolean) GeneralUtility::_GP('clear_cache');
		$this->cmd = GeneralUtility::_GP('cmd');
		$this->cmd_table = GeneralUtility::_GP('cmd_table');

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
		/**
		 * Template
		 *
		 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
		 */
		$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$doc->backPath = $GLOBALS['BACK_PATH'];
		$doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_category_index.html');
		$this->doc = $doc;
	}

	/**
	 * Main function, starting the rendering of the list.
	 *
	 * @return void
	 */
	public function main() {
		$language = $this->getLanguageService();

		$newRecordIcon = '';
		// Link for creating new records:
		if (!$this->modTSconfig['properties']['noCreateRecordsLink']) {
			$controls = array(
				'category' => array(
					'dataClass' => 'Tx_Commerce_Tree_Leaf_CategoryData',
					'parent' => 'parent_category'
				),
				'product' => array(
					'dataClass' => 'Tx_Commerce_Tree_Leaf_ProductData',
					'parent' => 'categories'
				)
			);

			$newRecordLink = $this->scriptNewWizard . '?id=' . (int) $this->id;
			foreach ($controls as $controlData) {
				/**
				 * Tree data
				 *
				 * @var Tx_Commerce_Tree_Leaf_Data $treeData
				 */
				$treeData = GeneralUtility::makeInstance($controlData['dataClass']);
				$treeData->init();

				if ($treeData->getTable()) {
					$newRecordLink .= '&edit[' . $treeData->getTable() . '][-' . $this->categoryUid . ']=new';
					$newRecordLink .= '&defVals[' . $treeData->getTable() . '][' . $controlData['parent'] . ']=' . $this->categoryUid;
				}
			}

			$newRecordIcon = '
				<!--
					Link for creating a new record:
				-->
				<a href="' . htmlspecialchars($newRecordLink . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'))) .
					'">' . IconUtility::getSpriteIcon('actions-document-new', array('title' => $language->getLL('editPage', 1))) . '</a>';
		}

		// Access check...
		// The page will show only if there is a valid page
		// and if this page may be viewed by the user
		if ($this->categoryUid) {
			$this->pageinfo = Tx_Commerce_Utility_BackendUtility::readCategoryAccess($this->categoryUid, $this->perms_clause);
		} else {
			$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(1));
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
		/**
		 * Category record list
		 *
		 * @var $dbList Tx_Commerce_ViewHelpers_CategoryRecordList
		 */
		$dbList = GeneralUtility::makeInstance('Tx_Commerce_ViewHelpers_CategoryRecordList');
		$dbList->backPath = $GLOBALS['BACK_PATH'];
		$dbList->script = BackendUtility::getModuleUrl('txcommerceM1_category', array(), '');

		/**
		 * Backend utility
		 *
		 * @var \CommerceTeam\Commerce\Utility\BackendUserUtility $utility
		 */
		$utility = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Utility\\BackendUserUtility');
		$dbList->calcPerms = $utility->calcPerms(BackendUtility::getRecord('tx_commerce_categories', $this->categoryUid));
		$dbList->thumbs = $this->getBackendUser()->uc['thumbnailsByDefault'];
		$dbList->returnUrl = $this->returnUrl;
		$dbList->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
		$dbList->localizationView = $this->MOD_SETTINGS['localization'];
		$dbList->showClipboard = 1;
		$dbList->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
		$dbList->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dbList->hideTables = $this->modTSconfig['properties']['hideTables'];
		$dbList->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
		$dbList->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
		$dbList->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors'] ? 1 : 0;
		$dbList->allowedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], TRUE);
		$dbList->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], TRUE);
		$dbList->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
		$dbList->pageRow = $this->pageinfo;
		$dbList->counter++;
		$dbList->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
		$dbList->modTSconfig = $this->modTSconfig;
		$clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
		$dbList->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;

		$dbList->newRecordIcon = $newRecordIcon;
		$dbList->parentUid = $this->categoryUid;
		$dbList->tableList = 'tx_commerce_categories,tx_commerce_products';

		// Clipboard is initialized:
		// Start clipboard
		/**
		 * Clipboard
		 *
		 * @var \TYPO3\CMS\Backend\Clipboard\Clipboard $clipObj
		 */
		$clipObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
		$dbList->clipObj = $clipObj;
		// Initialize - reads the clipboard content from the user session
		$dbList->clipObj->initializeClipboard();
		// Clipboard actions are handled:
		// CB is the clipboard command array
		$clipboard = GeneralUtility::_GET('CB');
		if ($this->cmd == 'setCB') {
			// CBH is all the fields selected for the clipboard, CBC is the checkbox fields
			// which were checked. By merging we get a full array of checked/unchecked
			// elements This is set to the 'el' array of the CB after being parsed so only
			// the table in question is registered.
			$clipboard['el'] = $dbList->clipObj->cleanUpCBC(
				array_merge((array) GeneralUtility::_POST('CBH'), (array) GeneralUtility::_POST('CBC')),
				$this->cmd_table
			);
		}
		if (!$this->MOD_SETTINGS['clipBoard']) {
			// If the clipboard is NOT shown, set the pad to 'normal'.
			$clipboard['setP'] = 'normal';
		}
		// Execute commands.
		$dbList->clipObj->setCmd($clipboard);
		// Clean up pad
		$dbList->clipObj->cleanCurrent();
		// Save the clipboard content
		$dbList->clipObj->endClipboard();

		// This flag will prevent the clipboard panel in being shown.
		// It is set, the clickmenu-layer is active AND the extended
		// view is not enabled.
		$dbList->dontShowClipControlPanels = (
			!$this->MOD_SETTINGS['bigControlPanel'] &&
			$dbList->clipObj->current == 'normal' &&
			!$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']
		);

		if ($access || ($this->id === 0 && $this->search_levels > 0 && strlen($this->search_field) > 0)) {
			// Deleting records...:
			// Has not to do with the clipboard but is simply the delete action. The
			// clipboard object is used to clean up the submitted entries to only the
			// selected table.
			if ($this->cmd == 'delete') {
				$items = $dbList->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
				if (count($items)) {
					$cmd = array();
					foreach ($items as $iK => $_) {
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
			$this->pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
			$dbList->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
			$dbList->setDispFields();
			$dbList->perms_clause = $this->perms_clause;
			// Render versioning selector:
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
				$dbList->HTMLcode .= $this->doc->getVersionSelector($this->id);
			}

			$dbList->parentUid = $this->categoryUid;

			// Render the list of tables:
			$dbList->generateList();
			$listUrl = $dbList->listURL();
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
				' . $dbList->CBfunctions() . '
				function editRecords(table,idList,addParams,CBflag) {	//
					window.location.href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
					rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '&edit["+table+"]["+idList+"]=edit"+addParams;
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
					top.fsMod.recentIds["commerce"] = ' . $this->categoryUid . ';
				}
			');

			// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
		}
		// access
		// Begin to compile the whole page, starting out with page header:
		$this->body = $this->doc->header($this->pageinfo['title']);
		$this->body .= '<form action="' . htmlspecialchars($dbList->listURL()) . '" method="post" name="dblistForm">';
		$this->body .= $dbList->HTMLcode;
		$this->body .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
		// If a listing was produced, create the page footer with search form etc:
		if ($dbList->HTMLcode) {
			// Making field select box (when extended view for a single table is enabled):
			if ($dbList->table) {
				$this->body .= $dbList->fieldSelectBox($dbList->table);
			}
			// Adding checkbox options for extended listing and clipboard display:
			$this->body .= '

					<!--
						Listing options for extended view, clipboard and localization view
					-->
					<div id="typo3-listOptions">
						<form action="" method="post">';

			$functionParameter = array('id' => $this->id,);
			if ($this->categoryUid) {
				$functionParameter['control[tx_commerce_categories][uid]'] = $this->categoryUid;
			}

			// Add "display bigControlPanel" checkbox:
			if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
				$this->body .= BackendUtility::getFuncCheck(
					$functionParameter, 'SET[bigControlPanel]', $this->MOD_SETTINGS['bigControlPanel'], '',
					$this->table ? '&table=' . $this->table : '', 'id="checkLargeControl"'
				);
				$this->body .= '<label for="checkLargeControl">' .
					BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $language->getLL('largeControl', TRUE)) .
					'</label><br />';
			}
			// Add "clipboard" checkbox:
			if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable' && $dbList->showClipboard) {
				$this->body .= BackendUtility::getFuncCheck(
					$functionParameter, 'SET[clipBoard]', $this->MOD_SETTINGS['clipBoard'], '',
					$this->table ? '&table=' . $this->table : '', 'id="checkShowClipBoard"'
				);
				$this->body .= '<label for="checkShowClipBoard">' .
					BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $language->getLL('showClipBoard', TRUE)) .
					'</label><br />';
			}
			// Add "localization view" checkbox:
			if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
				$this->body .= BackendUtility::getFuncCheck(
					$functionParameter, 'SET[localization]', $this->MOD_SETTINGS['localization'], '',
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
		if ($this->MOD_SETTINGS['clipBoard'] && $dbList->showClipboard && ($dbList->HTMLcode || $dbList->clipObj->hasElements())) {
			$this->body .= '<div class="db_list-dashboard">' . $dbList->clipObj->printClipboard() . '</div>';
		}
		// Search box:
		if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dbList->HTMLcode || $dbList->searchString !== '')) {
			$sectionTitle = BackendUtility::wrapInHelp(
				'xMOD_csh_corebe', 'list_searchbox', $language->sL('LLL:EXT:lang/locallang_core.xlf:labels.search', TRUE)
			);
			$this->body .= '<div class="db_list-searchbox">' .
				$this->doc->section($sectionTitle, $dbList->getSearchBox(), FALSE, TRUE, FALSE, TRUE) .
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

		$docHeaderButtons = $dbList->getButtons($this->pageinfo);

		if (!$this->categoryUid) {
			$docHeaderButtons['edit'] = '';
		}

		$categoryInfo = $this->categoryUid ? $this->getCategoryInfo($this->pageinfo) : $this->getPageInfo($this->pageinfo);
		$categoryPath = $this->categoryUid ? $this->getCategoryPath($this->pageinfo) : $this->getPagePath($this->pageinfo);

		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CATPATH' => $categoryPath,
			'CATINFO' => $categoryInfo,
			'CONTENT' => $this->body,
			'EXTRACONTAINERCLASS' => $this->table ? 'singletable' : ''
		);

		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render('DB list', $this->content);
	}

	/**
	 * Returns the Category Path info
	 *
	 * @param array $categoryRecord Category row
	 *
	 * @return string
	 */
	protected function getCategoryPath(array $categoryRecord) {
		$language = $this->getLanguageService();
		$backendUser = $this->getBackendUser();

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
		$cropLength = empty($backendUser->uc['titleLen']) ? 50 : $backendUser->uc['titleLen'];
		$croppedTitle = GeneralUtility::fixed_lgd_cs($title, - $cropLength);
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
	 * @param array $categoryRecord Category record
	 *
	 * @return string
	 */
	protected function getCategoryInfo(array $categoryRecord) {
		// Add icon with clickmenu, etc:
		// If there IS a real page
		if (is_array($categoryRecord) && $categoryRecord['uid']) {
			$alttext = BackendUtility::getRecordIconAltText($categoryRecord, 'tx_commerce_categories');
			$iconImg = IconUtility::getSpriteIconForRecord(
				'tx_commerce_categories', $categoryRecord, array('title' => $alttext)
			);
			// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'tx_commerce_categories', $categoryRecord['uid']);
			$uid = $categoryRecord['uid'];
			$title = BackendUtility::getRecordTitle('tx_commerce_categories', $categoryRecord);
		} else {
			// On root-level of page tree
			// Make Icon
			$iconImg = IconUtility::getSpriteIcon(
				'apps-pagetree-root', array('title' => htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']))
			);
			if ($this->getBackendUser()->user['admin']) {
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
	 *
	 * @return string Page path
	 */
	protected function getPagePath(array $pageRecord) {
		$backendUser = $this->getBackendUser();

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
		$pagePath = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) .
			': <span class="typo3-docheader-pagePath">';

		// crop the title to title limit (or 50, if not defined)
		$cropLength = empty($backendUser->uc['titleLen']) ? 50 : $backendUser->uc['titleLen'];
		$croppedTitle = GeneralUtility::fixed_lgd_cs($title, - $cropLength);
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
	 *
	 * @return string Page info
	 */
	protected function getPageInfo(array $pageRecord) {
		// Add icon with clickmenu, etc:
		// If there IS a real page
		if (is_array($pageRecord) && $pageRecord['uid']) {
			$alttext = BackendUtility::getRecordIconAltText($pageRecord, 'pages');
			$iconImg = IconUtility::getSpriteIconForRecord('pages', $pageRecord, array('title' => $alttext));
			// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', $pageRecord['uid']);
			$uid = $pageRecord['uid'];
			$title = BackendUtility::getRecordTitle('pages', $pageRecord);
		} else {
			// On root-level of page tree
			// Make Icon
			$iconImg = IconUtility::getSpriteIcon(
				'apps-pagetree-root', array('title' => htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']))
			);
			if ($this->getBackendUser()->user['admin']) {
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

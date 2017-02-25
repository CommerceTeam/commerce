<?php
namespace CommerceTeam\Commerce\Controller;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Controller\OrdersModuleController
 */
class OrdersModuleController extends \TYPO3\CMS\Recordlist\RecordList
{
    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'commerce_order';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Order pid.
     *
     * @var int
     */
    protected $orderPid = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile(
            'EXT:commerce/Resources/Private/Language/locallang_mod_category.xlf'
        );
    }

    /**
     * Initialization.
     *
     * @return void
     */
    public function init()
    {
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $backendUser = $this->getBackendUserAuthentication();
        $this->perms_clause = $backendUser->getPagePermsClause(1);
        // Get session data
        $sessionData = $backendUser->getSessionData(CategoryModuleController::class);
        $this->search_field = !empty($sessionData['search_field']) ? $sessionData['search_field'] : '';

        // Setting GPvars:
        $this->id = (int) GeneralUtility::_GP('id');
        // Find the right pid for the Ordersfolder
        $this->orderPid = FolderRepository::initFolders('Orders', FolderRepository::initFolders());
        if ($this->id == $this->orderPid) {
            $this->id = 0;
        }

        // GPvars:
        $this->pointer = max(GeneralUtility::_GP('pointer'), 0);
        $this->imagemode = GeneralUtility::_GP('imagemode');
        $this->table = GeneralUtility::_GP('table');
        $this->search_field = GeneralUtility::_GP('search_field');
        $this->search_levels = (int)GeneralUtility::_GP('search_levels');
        $this->showLimit = GeneralUtility::_GP('showLimit');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->clear_cache = GeneralUtility::_GP('clear_cache');
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->cmd_table = GeneralUtility::_GP('cmd_table');
        $sessionData['search_field'] = $this->search_field;
        // Set up menus:
        $this->menuConfig();
        // Store session data
        $backendUser->setAndSaveSessionData(CategoryModuleController::class, $sessionData);
    }

    /**
     * Main function of the module. Write the content to $this->content.
     *
     * @return void
     */
    public function main()
    {
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Loading current page record and checking access:
        $this->pageinfo = BackendUtility::readPageAccess($this->id ? $this->id : $this->orderPid, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        $calcPerms = $backendUser->calcPerms($this->pageinfo);

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Tooltip');
        // Apply predefined values for hidden checkboxes
        // Set predefined value for DisplayBigControlPanel:
        if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'activated') {
            $this->MOD_SETTINGS['bigControlPanel'] = true;
        } elseif ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'deactivated') {
            $this->MOD_SETTINGS['bigControlPanel'] = false;
        }
        // Set predefined value for Clipboard:
        if ($this->modTSconfig['properties']['enableClipBoard'] === 'activated') {
            $this->MOD_SETTINGS['clipBoard'] = true;
        } elseif ($this->modTSconfig['properties']['enableClipBoard'] === 'deactivated') {
            $this->MOD_SETTINGS['clipBoard'] = false;
        } else {
            if ($this->MOD_SETTINGS['clipBoard'] === null) {
                $this->MOD_SETTINGS['clipBoard'] = true;
            }
        }
        // Set predefined value for LocalizationView:
        if ($this->modTSconfig['properties']['enableLocalizationView'] === 'activated') {
            $this->MOD_SETTINGS['localization'] = true;
        } elseif ($this->modTSconfig['properties']['enableLocalizationView'] === 'deactivated') {
            $this->MOD_SETTINGS['localization'] = false;
        }

        // Initialize the dblist object:
        /**
         * Order record list.
         *
         * @var \CommerceTeam\Commerce\RecordList\OrderRecordList $dblist
         */
        $dblist = GeneralUtility::makeInstance(\CommerceTeam\Commerce\RecordList\OrderRecordList::class);
        $dblist->script = BackendUtility::getModuleUrl('commerce_orders');
        $dblist->calcPerms = $calcPerms;
        $dblist->thumbs = $backendUser->uc['thumbnailsByDefault'];
        $dblist->returnUrl = $this->returnUrl;
        $dblist->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
        $dblist->localizationView = $this->MOD_SETTINGS['localization'];
        $dblist->showClipboard = 1;
        $dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
        $dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
        $dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
        $dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
        $dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
        $dblist->allowedNewTables = GeneralUtility::trimExplode(
            ',',
            $this->modTSconfig['properties']['allowedNewTables'],
            true
        );
        $dblist->deniedNewTables = GeneralUtility::trimExplode(
            ',',
            $this->modTSconfig['properties']['deniedNewTables'],
            true
        );
        $dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
        $dblist->pageRow = $this->pageinfo;
        $dblist->counter++;
        $dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
        $dblist->modTSconfig = $this->modTSconfig;
        $clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
        $dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        // Clipboard is initialized:
        // Start clipboard
        $dblist->clipObj = GeneralUtility::makeInstance(Clipboard::class);
        // Initialize - reads the clipboard content from the user session
        $dblist->clipObj->initializeClipboard();
        // Clipboard actions are handled:
        // CB is the clipboard command array
        $CB = GeneralUtility::_GET('CB');
        if ($this->cmd == 'setCB') {
            // CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked.
            // By merging we get a full array of checked/unchecked elements
            // This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
            $CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge(
                GeneralUtility::_POST('CBH'),
                (array)GeneralUtility::_POST('CBC')
            ), $this->cmd_table);
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
        $dblist->dontShowClipControlPanels = (
            $dblist->clipObj->current == 'normal'
            && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']
        );
        // If there is access to the page or root page is used for searching,
        // then render the list contents and set up the document template object:
        if ($access) {
            // Deleting records...:
            // Has not to do with the clipboard but is simply the delete action.
            // The clipboard object is used to clean up the submitted entries to only the selected table.
            if ($this->cmd == 'delete') {
                $items = $dblist->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
                if (!empty($items)) {
                    $cmd = array();
                    foreach ($items as $iK => $value) {
                        $iKParts = explode('|', $iK);
                        $cmd[$iKParts[0]][$iKParts[1]]['delete'] = 1;
                    }
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->stripslashes_values = 0;
                    $tce->start(array(), $cmd);
                    $tce->process_cmdmap();
                    if (isset($cmd['tx_commerce_orders'])) {
                        BackendUtility::setUpdateSignal('updateOrderTree');
                    }
                    $tce->printLogErrorMessages(GeneralUtility::getIndpEnv('REQUEST_URI'));
                }
            }
            // Initialize the listing object, dblist, for rendering the list:
            $this->pointer = max(0, (int)$this->pointer);

            $dblist->tableList = 'tx_commerce_orders';
            $dblist->orderPid = $this->orderPid;

            $dblist->start(
                $this->id,
                $this->table,
                $this->pointer,
                $this->search_field,
                $this->search_levels,
                $this->showLimit
            );
            $dblist->setDispFields();
            // Render versioning selector:
            if (ExtensionManagementUtility::isLoaded('version')) {
                /** @noinspection PhpInternalEntityUsedInspection */
                $dblist->HTMLcode .= $this->moduleTemplate->getVersionSelector($this->id);
            }
            // Render the list of tables:
            $dblist->generateList();
            $listUrl = $dblist->listURL();

            /** @noinspection PhpInternalEntityUsedInspection */
            $requestUri = $this->moduleTemplate->redirectUrls($listUrl);

            // Add JavaScript functions to the page:
            $this->moduleTemplate->addJavaScriptCode(
                'OrdersModuleController',
                '
				function jumpExt(URL,anchor) {	//
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {	//
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}
				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}

				function setHighlight(id) {	//
					top.fsMod.recentIds["web"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
						top.content.nav_frame.refresh_nav();
					}
				}
				' . $requestUri . '
				' . $dblist->CBfunctions() . '
				function editRecords(table,idList,addParams,CBflag) {	//
					window.location.href="'
                . BackendUtility::getModuleUrl(
                    'record_edit',
                    array('returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'))
                )
                . '&edit["+table+"]["+idList+"]=edit"+addParams;
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

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
			'
            );

            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        }
        // access
        // Begin to compile the whole page, starting out with page header:
        if (!$this->id) {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } else {
            $title = $this->pageinfo['title'];
        }
        /** @noinspection PhpInternalEntityUsedInspection */
        $this->body = $this->moduleTemplate->header($title);
        $this->moduleTemplate->setTitle($title);

        if (!empty($dblist->HTMLcode)) {
            $output = $dblist->HTMLcode;
        } else {
            $output = '';
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $lang->getLL('noRecordsOnThisPage'),
                '',
                FlashMessage::INFO
            );
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        $this->body .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
        $this->body .= $output;
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
					<div class="typo3-listOptions">
						<form action="" method="post">';

            // Add "display bigControlPanel" checkbox:
            if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
                $this->body .= '<div class="checkbox">' .
                    '<label for="checkLargeControl">' .
                    BackendUtility::getFuncCheck(
                        $this->id,
                        'SET[bigControlPanel]',
                        $this->MOD_SETTINGS['bigControlPanel'],
                        '',
                        $this->table ? '&table=' . $this->table : '',
                        'id="checkLargeControl"'
                    ) .
                    BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $lang->getLL('largeControl', true)) .
                    '</label>' .
                    '</div>';
            }

            // Add "clipboard" checkbox:
            if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
                if ($dblist->showClipboard) {
                    $this->body .= '<div class="checkbox">' .
                        '<label for="checkShowClipBoard">' .
                        BackendUtility::getFuncCheck(
                            $this->id,
                            'SET[clipBoard]',
                            $this->MOD_SETTINGS['clipBoard'],
                            '',
                            $this->table ? '&table=' . $this->table : '',
                            'id="checkShowClipBoard"'
                        ) .
                        BackendUtility::wrapInHelp(
                            'xMOD_csh_corebe',
                            'list_options',
                            $lang->getLL('showClipBoard', true)
                        ) .
                        '</label>' .
                        '</div>';
                }
            }

            $this->body .= '
						</form>
					</div>';
        }
        // Additional footer content
        $footerContentHook =
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook'];
        if (is_array($footerContentHook)) {
            foreach ($footerContentHook as $hook) {
                $params = [];
                $this->body .= GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
        // Setting up the buttons for docheader
        $dblist->getDocHeaderButtons($this->moduleTemplate);
        // searchbox toolbar
        if (!$this->modTSconfig['properties']['disableSearchBox']
            && ($dblist->HTMLcode || !empty($dblist->searchString))
        ) {
            $this->content = $dblist->getSearchBox();
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');

            /** @var LinkButton $searchButton */
            $searchButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
            $searchButton
                ->setHref('#')
                ->setClasses('t3js-toggle-search-toolbox')
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.searchIcon'))
                ->setIcon($this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL));
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $searchButton,
                ButtonBar::BUTTON_POSITION_LEFT,
                90
            );
        }

        if ($this->pageinfo) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }

        $this->content .= $this->body;
    }
}

<?php
namespace CommerceTeam\Commerce\Controller;

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

use CommerceTeam\Commerce\Template\ModuleTemplate;
use CommerceTeam\Commerce\Utility\BackendUserUtility;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Extbase\Service\TypoScriptService;

/**
 * Class \CommerceTeam\Commerce\Controller\CategoryModuleController.
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class CategoryModuleController extends \TYPO3\CMS\Recordlist\RecordList
{
    /**
     * The name of the module
     *
     * @var string
     */
    public $moduleName = 'commerce_category';

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Category uid.
     *
     * @var int
     */
    public $categoryUid = 5;
    // @todo only for development set to 0 afterward set to 0 again

    /**
     * Constructor
     *
     * @return self
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_list.xlf');
        $this->getLanguageService()->includeLLFile(
            'EXT:commerce/Resources/Private/Language/locallang_mod_category.xml'
        );
        $this->moduleTemplate->getPageRenderer()->loadJquery();
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/FieldSelectBox');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
    }

    /**
     * Initializing the module.
     *
     * @return void
     */
    public function init()
    {
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $backendUser = $this->getBackendUserAuthentication();
        $this->perms_clause = \CommerceTeam\Commerce\Utility\BackendUtility::getCategoryPermsClause(1);
        // Get session data
        $sessionData = $backendUser->getSessionData(CategoryModuleController::class);
        $this->search_field = !empty($sessionData['search_field']) ? $sessionData['search_field'] : '';

        // In commerce context all categories and products are stored in only one folder so no need to use get vars
        \CommerceTeam\Commerce\Utility\FolderUtility::initFolders();
        $this->id = \CommerceTeam\Commerce\Utility\BackendUtility::getProductFolderUid();

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

        // Get category uid from control
        $controlFromGetPost = GeneralUtility::_GP('control');
        if (is_array($controlFromGetPost)) {
            $this->categoryUid = (int) $controlFromGetPost['categoryUid'];
        }
    }

    /**
     * Main function, starting the rendering of the list.
     *
     * @return void
     */
    public function main()
    {
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Loading current category/page record and checking access:
        if ($this->categoryUid) {
            $this->pageinfo = \CommerceTeam\Commerce\Utility\BackendUtility::readCategoryAccess(
                $this->categoryUid,
                $this->perms_clause
            );
        } else {
            $this->pageinfo = BackendUtility::readPageAccess(
                $this->id,
                $backendUser->getPagePermsClause(1)
            );
        }
        $access = is_array($this->pageinfo);

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        $calcPerms = $backendUserUtility->calcPerms($this->pageinfo);
        $userCanEditPage = $calcPerms & Permission::PAGE_EDIT
            && !empty($this->id)
            && ($backendUser->isAdmin() || (int)$this->pageinfo['editlock'] === 0);
        if ($userCanEditPage) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/PageActions', 'function(PageActions) {
                PageActions.setPageId(' . (int)$this->id . ');
                PageActions.initializePageTitleRenaming();
            }');
        }
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
        /** @var \CommerceTeam\Commerce\RecordList\CategoryRecordList $dbList */
        $dbList = GeneralUtility::makeInstance(\CommerceTeam\Commerce\RecordList\CategoryRecordList::class);
        $dbList->script = BackendUtility::getModuleUrl('commerce_category');
        $dbList->calcPerms = $calcPerms;
        $dbList->thumbs = $backendUser->uc['thumbnailsByDefault'];
        $dbList->returnUrl = $this->returnUrl;
        $dbList->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
        $dbList->localizationView = $this->MOD_SETTINGS['localization'];
        $dbList->showClipboard = 1;
        $dbList->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
        $dbList->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
        $dbList->hideTables = $this->modTSconfig['properties']['hideTables'];
        $dbList->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
        $dbList->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
        $dbList->allowedNewTables = GeneralUtility::trimExplode(
            ',',
            $this->modTSconfig['properties']['allowedNewTables'],
            true
        );
        $dbList->deniedNewTables = GeneralUtility::trimExplode(
            ',',
            $this->modTSconfig['properties']['deniedNewTables'],
            true
        );
        $dbList->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
        $dbList->pageRow = $this->pageinfo;
        $dbList->counter++;
        $dbList->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
        $dbList->modTSconfig = $this->modTSconfig;
        $clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
        $dbList->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        if (isset($this->modTSconfig['properties']['tableDisplayOrder.'])) {
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $dbList->setTableDisplayOrder(
                $typoScriptService->convertTypoScriptArrayToPlainArray(
                    $this->modTSconfig['properties']['tableDisplayOrder.']
                )
            );
        }
        // Clipboard is initialized:
        // Start clipboard
        /**
         * Clipboard.
         *
         * @var Clipboard $clipObj
         */
        $clipObj = GeneralUtility::makeInstance(Clipboard::class);
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
        // It is set, if the clickmenu-layer is active AND the extended view is not enabled.
        $dbList->dontShowClipControlPanels = (
            !$this->MOD_SETTINGS['bigControlPanel']
            && $dbList->clipObj->current == 'normal'
            && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']
        );

        $dbList->categoryUid = $this->categoryUid;
        $dbList->tableList = 'tx_commerce_categories,tx_commerce_products';

        if ($access || ($this->id === 0 && $this->search_levels > 0 && strlen($this->search_field) > 0)) {
            // Deleting records...:
            // Has not to do with the clipboard but is simply the delete action. The
            // clipboard object is used to clean up the submitted entries to only the
            // selected table.
            if ($this->cmd == 'delete') {
                $items = $dbList->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
                if (!empty($items)) {
                    $cmd = array();
                    foreach ($items as $iK => $_) {
                        $iKparts = explode('|', $iK);
                        $cmd[$iKparts[0]][$iKparts[1]]['delete'] = 1;
                    }
                    /**
                     * Data handler.
                     *
                     * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
                     */
                    $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    $tce->start(array(), $cmd);
                    $tce->process_cmdmap();
                    if (isset($cmd['tx_commerce_categories'])) {
                        BackendUtility::setUpdateSignal('updateFolderTree');
                    }
                    $tce->printLogErrorMessages(GeneralUtility::getIndpEnv('REQUEST_URI'));
                }
            }
            // Initialize the listing object, dblist, for rendering the list:
            $this->pointer = max(0, (int)$this->pointer);
            $dbList->start(
                $this->id,
                $this->table,
                $this->pointer,
                $this->search_field,
                $this->search_levels,
                $this->showLimit
            );
            $dbList->setDispFields();
            // Render versioning selector:
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
                $dbList->HTMLcode .= $this->moduleTemplate->getVersionSelector($this->id);
            }
            // Render the list of tables:
            $dbList->generateList();
            $listUrl = $dbList->listURL();
            // Add JavaScript functions to the page:
            $this->moduleTemplate->addJavaScriptCode(
                'CategoryModuleController',
                '
                function jumpExt(URL, anchor) {
                    var anc = anchor ? anchor : "";
                    window.location.href = URL + (T3_THIS_LOCATION ? "&returnUrl=" + T3_THIS_LOCATION : "") + anc;
                    return false;
                }
                function jumpSelf(URL) {
                    window.location.href = URL + (T3_RETURN_URL ? "&returnUrl=" + T3_RETURN_URL : "");
                    return false;
                }
                function jumpToUrl(URL) {
                    window.location.href = URL;
                    return false;
                }

                function setHighlight(id) {
                    top.fsMod.recentIds["web"] = id;
                    // For highlighting
                    top.fsMod.navFrameHighlightedID["web"] = "pages" + id + "_" + top.fsMod.currentBank;
                    top.fsMod.navFrameHighlightedID["commerce"] = "tx_commerce_categories" + id + "_"
                        + top.fsMod.currentBank;

                    if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
                        top.content.nav_frame.refresh_nav();
                    }
                }
                ' . $this->moduleTemplate->redirectUrls($listUrl) . '
                ' . $dbList->CBfunctions() . '
                function editRecords(table, idList, addParams, CBflag) {
                    window.location.href = "'
                . BackendUtility::getModuleUrl(
                    'record_edit',
                    array('returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'))
                )
                . '&edit[" + table + "][" + idList + "]=edit" + addParams;
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
                    top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
                    top.fsMod.recentIds["commerce"] = ' . (int)$this->categoryUid . ';
                }
                '
            );

            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        }
        // access
        // Begin to compile the whole page, starting out with page header:
        $this->body = $this->moduleTemplate->header(!$this->id ? 'Commerce' : $this->pageinfo['title']);

        if (!empty($dbList->HTMLcode)) {
            $output = $dbList->HTMLcode;
        } else {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $lang->getLL('noRecordsOnThisPage'),
                '',
                FlashMessage::INFO
            );
            $severityClass = sprintf('alert %s', $flashMessage->getClass());
            $messageContent = htmlspecialchars($flashMessage->getMessage());
            if ($flashMessage->getTitle() !== '') {
                $messageContent = sprintf('<h4>%s</h4>', htmlspecialchars($flashMessage->getTitle())) . $messageContent;
            }
            $output = sprintf('<li class="%s">%s</li>', htmlspecialchars($severityClass), $messageContent);
        }

        $this->body .= '<form action="' . htmlspecialchars($dbList->listURL()) . '" method="post" name="dblistForm">';
        $this->body .= $output;
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

            // add the page id and the current selected categor uid to the function links
            $functionParameter = array('id' => $this->id);
            if ($this->categoryUid) {
                $functionParameter['control']['categoryUid'] = $this->categoryUid;
            }

            // Add "display bigControlPanel" checkbox:
            if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
                $this->body .= '<div class="checkbox">'
                    . '<label for="checkLargeControl">'
                    . BackendUtility::getFuncCheck(
                        $functionParameter,
                        'SET[bigControlPanel]',
                        $this->MOD_SETTINGS['bigControlPanel'],
                        '',
                        $this->table ? '&table=' . $this->table : '',
                        'id="checkLargeControl"'
                    )
                    . BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $lang->getLL('largeControl', true))
                    . '</label>'
                    . '</div>';
            }

            // Add "clipboard" checkbox:
            if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable' && $dbList->showClipboard) {
                if ($dbList->showClipboard) {
                    $this->body .= '<div class="checkbox">'
                        . '<label for="checkShowClipBoard">'
                        . BackendUtility::getFuncCheck(
                            $functionParameter,
                            'SET[clipBoard]',
                            $this->MOD_SETTINGS['clipBoard'],
                            '',
                            $this->table ? '&table=' . $this->table : '',
                            'id="checkShowClipBoard"'
                        )
                        . BackendUtility::wrapInHelp(
                            'xMOD_csh_corebe',
                            'list_options',
                            $lang->getLL('showClipBoard', true)
                        )
                        . '</label>'
                        . '</div>';
                }
            }

            // Add "localization view" checkbox:
            if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
                $this->body .= '<div class="checkbox">'
                    . '<label for="checkLocalization">'
                    . BackendUtility::getFuncCheck(
                        $functionParameter,
                        'SET[localization]',
                        $this->MOD_SETTINGS['localization'],
                        '',
                        $this->table ? '&table=' . $this->table : '',
                        'id="checkLocalization"'
                    )
                    . BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $lang->getLL('localization', true))
                    . '</label>'
                    . '</div>';
            }

            $this->body .= '
                        </form>
                    </div>';
        }
        // Printing clipboard if enabled
        if ($this->MOD_SETTINGS['clipBoard']
            && $dbList->showClipboard
            && ($dbList->HTMLcode || $dbList->clipObj->hasElements())) {
            $this->body .= '<div class="db_list-dashboard">' . $dbList->clipObj->printClipboard() . '</div>';
        }
        // Additional footer content
        $footerContentHook =
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook'];
        if (is_array($footerContentHook)) {
            foreach ($footerContentHook as $hook) {
                $params = array();
                $this->body .= GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
        // Setting up the buttons for docheader
        $dbList->getDocHeaderButtons($this->moduleTemplate);
        // searchbox toolbar
        if (!$this->modTSconfig['properties']['disableSearchBox']
            && ($dbList->HTMLcode || !empty($dbList->searchString))) {
            $this->content = $dbList->getSearchBox();
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

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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
    protected $orderPid;

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
        // Setting GPvars:
        $this->id = (int) GeneralUtility::_GP('id');
        // Find the right pid for the Ordersfolder
        $this->orderPid = FolderRepository::initFolders('Orders', FolderRepository::initFolders());
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

        // Page select clause:
        $this->perms_clause = $this->getBackendUserAuthentication()->getPagePermsClause(1);

        // Set up menus:
        $this->menuConfig();
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clearCache()
    {
        if (GeneralUtility::_GP('clear_all_cache')) {
            /** @var DataHandler $tce */
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], []);
            $tce->clear_cacheCmd('all');
        }
    }


    /**
     * Main function of the module. Write the content to $this->content.
     *
     * @return void
     */
    public function main()
    {
        $language = $this->getLanguageService();

        // Loading current page record and checking access:
        $this->pageinfo = BackendUtility::readPageAccess($this->id ? $this->id : $this->orderPid, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
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
        $dblist->script = BackendUtility::getModuleUrl('commerce_orders', [], '');
        $dblist->calcPerms = $this->getBackendUserAuthentication()->calcPerms($this->pageinfo);
        $dblist->thumbs = $this->getBackendUserAuthentication()->uc['thumbnailsByDefault'];
        $dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
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
            1
        );
        $dblist->deniedNewTables = GeneralUtility::trimExplode(
            ',',
            $this->modTSconfig['properties']['deniedNewTables'],
            1
        );
        $dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
        $dblist->pageRow = $this->pageinfo;
        ++$dblist->counter;
        $dblist->modTSconfig = $this->modTSconfig;
        $clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
        $dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;

        $dblist->tableList = 'tx_commerce_orders';
        $dblist->orderPid = $this->orderPid;

        // Clipboard is initialized:
        // Start clipboard
        /**
         * Clipboard.
         *
         * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
         */
        $clipObj = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);
        $dblist->clipObj = $clipObj;
        // Initialize - reads the clipboard content from the user session
        $dblist->clipObj->initializeClipboard();
        // Clipboard actions are handled:
        // CB is the clipboard command array
        $clipboard = GeneralUtility::_GET('CB');
        if ($this->cmd == 'setCB') {
            // CBH is all the fields selected for the clipboard, CBC is
            // the checkbox fields which were checked. By merging we get
            // a full array of checked/unchecked elements
            // This is set to the 'el' array of the CB after being parsed
            // so only the table in question is registered.
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
        // It is set, if the clickmenu-layer is active
        // AND the extended view is not enabled.
        $dblist->dontShowClipControlPanels = (
            !$this->MOD_SETTINGS['bigControlPanel'] &&
            $dblist->clipObj->current == 'normal' &&
            !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']
        );

        // If there is access to the page, then render the list contents
        // and set up the document template object:
        if ($access) {
            // Deleting records...:
            // Has not to do with the clipboard but is simply the delete
            // action. The clipboard object is used to clean up the
            // submitted entries to only the selected table.
            if ($this->cmd == 'delete') {
                $items = $dblist->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
                if (!empty($items)) {
                    $cmd = [];
                    foreach (array_keys($items) as $iK) {
                        $iKparts = explode('|', $iK);
                        $cmd[$iKparts[0]][$iKparts[1]]['delete'] = 1;
                    }

                    /**
                     * Data handler.
                     *
                     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
                     */
                    $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    $tce->start([], $cmd);
                    $tce->process_cmdmap();

                    $tce->printLogErrorMessages(GeneralUtility::getIndpEnv('REQUEST_URI'));
                }
            }

            // Initialize the listing object, dblist, for rendering the list:
            $this->pointer = max(min($this->pointer, 100000), 0);
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
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
                $dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
            }

            // Render the list of tables:
            $dblist->generateList();
            // Add JavaScript functions to the page:
        }

        // access
        // Begin to compile the whole page, starting out with page header:
        $this->body = '<h1>' . $this->pageinfo['title'] . '</h1>';
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
                    $this->id,
                    'SET[bigControlPanel]',
                    $this->MOD_SETTINGS['bigControlPanel'],
                    '',
                    $this->table ? '&table=' . $this->table : '',
                    'id="checkLargeControl"'
                );
                $this->body .= '<label for="checkLargeControl">' .
                    BackendUtility::wrapInHelp(
                        'xMOD_csh_corebe',
                        'list_options',
                        $language->getLL('largeControl', true)
                    ) .
                    '</label><br />';
            }

            // Add "clipboard" checkbox:
            if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
                if ($dblist->showClipboard) {
                    $this->body .= BackendUtility::getFuncCheck(
                        $this->id,
                        'SET[clipBoard]',
                        $this->MOD_SETTINGS['clipBoard'],
                        '',
                        $this->table ? '&table=' . $this->table : '',
                        'id="checkShowClipBoard"'
                    );
                    $this->body .= '<label for="checkShowClipBoard">' .
                        BackendUtility::wrapInHelp(
                            'xMOD_csh_corebe',
                            'list_options',
                            $language->getLL('showClipBoard', true)
                        ) .
                        '</label><br />';
                }
            }

            // Add "localization view" checkbox:
            if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
                $this->body .= BackendUtility::getFuncCheck(
                    $this->id,
                    'SET[localization]',
                    $this->MOD_SETTINGS['localization'],
                    '',
                    $this->table ? '&table=' . $this->table : '',
                    'id="checkLocalization"'
                );
                $this->body .= '<label for="checkLocalization">' .
                    BackendUtility::wrapInHelp(
                        'xMOD_csh_corebe',
                        'list_options',
                        $language->getLL('localization', true)
                    ) .
                    '</label><br />';
            }
            $this->body .= '
						</form>
					</div>';
        }
        // Printing clipboard if enabled
        if ($this->MOD_SETTINGS['clipBoard']
            && $dblist->showClipboard
            && ($dblist->HTMLcode || $dblist->clipObj->hasElements())
        ) {
            $this->body .= '<div class="db_list-dashboard">' . $dblist->clipObj->printClipboard() . '</div>';
        }
        // Search box:
        if (!$this->modTSconfig['properties']['disableSearchBox']
            && ($dblist->HTMLcode || $dblist->searchString !== '')
        ) {
            $sectionTitle = BackendUtility::wrapInHelp(
                'xMOD_csh_corebe',
                'list_searchbox',
                $language->sL('LLL:EXT:lang/locallang_core.xlf:labels.search', true)
            );
            $this->body = '<div class="db_list-searchbox">' .
                $this->doc->section($sectionTitle, $dblist->getSearchBox(), false, true, false, true) .
                '</div>' . $this->body;
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

        // Build the <body> for the module
        $this->content = $this->body;
    }

    /**
     * Create the panel of buttons for submitting the
     * form or otherwise perform operations.
     *
     * @param array $buttons Button
     *
     * @return array All available buttons as an assoc. array
     */
    protected function getButtons(array $buttons)
    {
        $backendUser = $this->getBackendUserAuthentication();
        $language = $this->getLanguageService();

        // CSH
        $buttons['csh'] = BackendUtility::cshItem('_MOD_commerce_orders', '');

        // Shortcut
        if ($backendUser->mayMakeShortcut()) {
            $buttons['shortcut'] = $this->doc->makeShortcutIcon(
                'id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit',
                implode(',', array_keys($this->MOD_MENU)),
                $this->moduleName
            );
        }

        // If access to Web>List for user, then link to that module.
        if ($backendUser->check('modules', 'web_list')) {
            // @todo modify to use index.php entry
            $href = 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' .
                rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
            $buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '" title="'
                . $language->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1) . '">' .
                $this->iconFactory->getIcon('apps-filetree-folder-list')->render() . '</a>';
        }

        return $buttons;
    }


    /**
     * Get backend user authentication
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}

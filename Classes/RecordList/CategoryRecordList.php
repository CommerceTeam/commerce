<?php
namespace CommerceTeam\Commerce\RecordList;

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

use CommerceTeam\Commerce\Controller\CategoryModuleController;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use CommerceTeam\Commerce\Utility\BackendUserUtility;
use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface;

/**
 * Extension of DatabaseRecordList to render category and product lists.
 */
class CategoryRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
{
    /**
     * Parent uid.
     *
     * @var int
     */
    public $categoryUid = 0;

    /**
     * @var array
     */
    public $categoryRow = [];

    /**
     * @var BackendUserUtility
     */
    protected $backendUserUtility;

    /**
     * Additional where per table.
     *
     * @var array
     */
    protected $addWhere = [
        'tx_commerce_products' => ' AND uid_foreign = %d',
        'tx_commerce_categories' => ' AND uid_foreign = %d',
    ];

    /**
     * Join queries per table.
     *
     * @var array
     */
    protected $joinTables = [
        'tx_commerce_products' => ' LEFT JOIN tx_commerce_products_categories_mm
            ON tx_commerce_products.uid = tx_commerce_products_categories_mm.uid_local',
        'tx_commerce_categories' => ' LEFT JOIN tx_commerce_categories_parent_category_mm
            ON tx_commerce_categories.uid = tx_commerce_categories_parent_category_mm.uid_local',
    ];

    /**
     * @var int
     */
    protected $previewPageId = 0;

    /**
     * New record icon.
     *
     * @var string
     */
    public $newRecordIcon = '';

    /**
     * Query part for either a list of ids "pid IN (1,2,3)" or a single id "pid = 123" from
     * which to select/search etc. (when search-levels are set high). See start()
     *
     * @var string
     */
    public $pidSelect = '';

    /**
     * CategoryRecordList constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        $this->previewPageId = (int)ConfigurationUtility::getInstance()->getExtConf('previewPageID');
    }

    /**
     * Initializes the list generation
     *
     * @param int $id Page id for which the list is rendered. Must be >= 0
     * @param string $table Tablename - if extended mode where only one table is listed at a time.
     * @param int $pointer Browsing pointer.
     * @param string $search Search word, if any
     * @param int $levels Number of levels to search down the page tree
     * @param int $showLimit Limit of records to be listed.
     * @return void
     */
    public function start($id, $table, $pointer, $search = '', $levels = 0, $showLimit = 0)
    {
        parent::start($id, $table, $pointer, $search, $levels, $showLimit);
        if ($this->searchLevels > 0) {
            $allowedMounts = $this->getSearchableWebmounts($this->id, $this->searchLevels, $this->perms_clause);
            $pidList = implode(',', array_map('intval', $allowedMounts));
            $this->pidSelect = 'pid IN (' . $pidList . ')';
        } elseif ($this->searchLevels < 0) {
            // Search everywhere
            $this->pidSelect = '1=1';
        } else {
            $this->pidSelect = 'pid=' . (int)$id;
        }
    }

    /**
     * @param ModuleTemplate $moduleTemplate
     */
    public function getDocHeaderButtons(ModuleTemplate $moduleTemplate)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        /** @var CategoryModuleController $module */
        $module = $this->getModule();
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Get users permissions for this page record:
        $localCalcPerms = $this->backendUserUtility->calcPerms($this->categoryRow);

        // CSH
        if ((string)$this->id === '') {
            $fieldName = 'list_module_noId';
        } elseif (!$this->id) {
            $fieldName = 'list_module_root';
        } else {
            $fieldName = 'list_module';
        }
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName($fieldName);
        $buttonBar->addButton($cshButton);

        if (isset($this->id)) {
            // New record on tx_commerce_categories that are not locked by editlock
            if (!$module->modTSconfig['properties']['noCreateRecordsLink']
                && (
                    $this->getBackendUserAuthentication()->isAdmin()
                    || !$this->categoryRow['editlock']
                )
            ) {
                $parameter = ['id' => $this->id];
                if ($this->categoryUid) {
                    $parameter['defVals'] = [
                        'tx_commerce_categories' => [
                            'uid' => $this->categoryUid,
                        ]
                    ];
                }

                $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue(
                    BackendUtility::getModuleUrl('db_new', $parameter)
                ) . ');';
                $newRecordButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->getLL('newRecordGeneral'))
                    ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
            }

            // Preview
            if ($this->previewPageId && $this->categoryUid) {
                $params = '&id=' . $this->previewPageId . '&tx_commerce_pi1[catUid]=' . $this->categoryUid;
                /** @var $cacheHash CacheHashCalculator */
                $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class);
                $cHash = $cacheHash->generateForParameters($params);
                $params .= $cHash ? '&cHash=' . $cHash : '';

                $onClick = BackendUtility::viewOnClick(
                    $this->id,
                    '',
                    BackendUtility::BEgetRootLine($this->id),
                    '',
                    '/index.php?' . $params
                );
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }

            // If edit permissions are set, see
            // \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
            if ($localCalcPerms & Permission::PAGE_EDIT && !empty($this->id) && $this->editLockPermissions()) {
                // Edit
                $params = '&edit[tx_commerce_categories][' . $this->categoryRow['uid'] . ']=edit';
                $onClick = BackendUtility::editOnClick($params, '', -1);
                $editButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->getLL('editPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
                $buttonBar->addButton($editButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }

            // Paste
            if ($this->showClipboard
                && ($localCalcPerms & Permission::PAGE_NEW || $localCalcPerms & Permission::CONTENT_EDIT)
                && $this->editLockPermissions()
            ) {
                $elFromTable = $this->clipObj->elFromTable('');
                if (!empty($elFromTable)) {
                    $confirmMessage = $this->clipObj->confirmMsgText(
                        'tx_commerce_categories',
                        $this->categoryRow,
                        'into',
                        $elFromTable
                    );
                    $pasteButton = $buttonBar->makeLinkButton()
                        ->setHref($this->clipObj->pasteUrl('', $this->id))
                        ->setTitle($lang->getLL('clip_paste'))
                        ->setClasses('t3js-modal-trigger')
                        ->setDataAttributes([
                            'severity' => 'warning',
                            'content' => $confirmMessage,
                            'title' => $lang->getLL('clip_paste')
                        ])
                        ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));
                    $buttonBar->addButton($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                }
            }

            // Export
            // @todo need to respect selected category
            if ($this->table && (!isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                || (isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                    && !$module->modTSconfig['properties']['noExportRecordsLinks']))
            ) {
                // CSV
                $csvButton = $buttonBar->makeLinkButton()
                    ->setHref($this->listURL() . '&csv=1')
                    ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.csv'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-export-csv', Icon::SIZE_SMALL));
                $buttonBar->addButton($csvButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                // Export
                if (ExtensionManagementUtility::isLoaded('impexp')) {
                    $url = BackendUtility::getModuleUrl('xMOD_tximpexp', ['tx_impexp[action]' => 'export']);
                    $exportButton = $buttonBar->makeLinkButton()
                        ->setHref($url . '&tx_impexp[list][]=' . rawurlencode($this->table . ':' . $this->id))
                        ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.export'))
                        ->setIcon($this->iconFactory->getIcon('actions-document-export-t3d', Icon::SIZE_SMALL));
                    $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                }
            }

            // Reload
            $reloadButton = $buttonBar->makeLinkButton()
                ->setHref($this->listURL())
                ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

            // Shortcut
            if ($backendUser->mayMakeShortcut()) {
                $shortCutButton = $buttonBar->makeShortcutButton()
                    ->setModuleName($module->moduleName)
                    ->setGetVariables([
                        'id',
                        'M',
                        'imagemode',
                        'pointer',
                        'table',
                        'search_field',
                        'search_levels',
                        'showLimit',
                        'sortField',
                        'sortRev',
                        'defVals'
                    ])
                    ->setSetVariables(array_keys($this->MOD_MENU));
                $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
            }

            // Back
            if ($this->returnUrl) {
                $href = htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl, ['id' => $this->id]));
                $buttons['back'] = '<a href="' . $href . '" class="typo3-goBack" title="'
                    . htmlspecialchars($lang->sL(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.goBack'
                    )) . '">'
                    . $this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }
    }

    /**
     * Creates the listing of records from a single table.
     *
     * @param string $table Table name
     * @param int $id Page id
     * @param string $rowList List of fields to show in the listing.
     *      Pseudo fields will be added including the record header.
     *
     * @return string HTML table with the listing for the record.
     *
     * @throws \UnexpectedValueException If hook was of wrong interface
     */
    public function getTable($table, $id, $rowList = '')
    {
        $rowListArray = GeneralUtility::trimExplode(',', $rowList, true);
        // if no columns have been specified, show description (if configured)
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']) && empty($rowListArray)) {
            array_push($rowListArray, $GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']);
        }
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();

        $tableConfig = ConfigurationUtility::getInstance()->getTcaValue($table);

        // Init
        $addWhere = '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $titleCol = $tableConfig['ctrl']['label'];
        $thumbsCol = $tableConfig['ctrl']['thumbnail'];
        $l10nEnabled = $tableConfig['ctrl']['languageField'] &&
            $tableConfig['ctrl']['transOrigPointerField'] &&
            !$tableConfig['ctrl']['transOrigPointerTable'];
        $tableCollapsed = (bool)$this->tablesCollapsed[$table];
        // prepare space icon
        $this->spaceIcon = '<span class="btn btn-default disabled">'
            . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        // Cleaning rowlist for duplicates and place the $titleCol as the first column always!
        $this->fieldArray = [];
        // title Column
        // Add title column
        $this->fieldArray[] = $titleCol;
        // Control-Panel
        if (!GeneralUtility::inList($rowList, '_CONTROL_')) {
            $this->fieldArray[] = '_CONTROL_';
        }
        // Clipboard
        if ($this->showClipboard) {
            $this->fieldArray[] = '_CLIPBOARD_';
        }
        // Ref
        if (!$this->dontShowClipControlPanels) {
            $this->fieldArray[] = '_REF_';
        }
        // Path
        if ($this->searchLevels) {
            $this->fieldArray[] = '_PATH_';
        }
        // Localization
        if ($this->localizationView && $l10nEnabled) {
            $this->fieldArray[] = '_LOCALIZATION_';
            $this->fieldArray[] = '_LOCALIZATION_b';
            // Only restrict to the default language if no search request is in place
            if ($this->searchString === '') {
                $addWhere = (string)$queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t.' . $tableConfig['ctrl']['languageField'], 0),
                    $queryBuilder->expr()->eq('t.' . $tableConfig['ctrl']['transOrigPointerField'], 0)
                );
            }
        }
        // Cleaning up:
        $this->fieldArray = array_unique(array_merge($this->fieldArray, $rowListArray));
        if ($this->noControlPanels) {
            $tempArray = array_flip($this->fieldArray);
            unset($tempArray['_CONTROL_']);
            unset($tempArray['_CLIPBOARD_']);
            $this->fieldArray = array_keys($tempArray);
        }
        // Creating the list of fields to include in the SQL query:
        $selectFields = $this->fieldArray;
        $selectFields[] = 'uid';
        $selectFields[] = 'pid';
        // adding column for thumbnails
        if ($thumbsCol) {
            $selectFields[] = $thumbsCol;
        }
        if (is_array($tableConfig['ctrl']['enablecolumns'])) {
            $selectFields = array_merge($selectFields, $tableConfig['ctrl']['enablecolumns']);
        }
        foreach (['type', 'typeicon_column', 'editlock'] as $field) {
            if ($tableConfig['ctrl'][$field]) {
                $selectFields[] = $tableConfig['ctrl'][$field];
            }
        }
        if ($tableConfig['ctrl']['versioningWS']) {
            $selectFields[] = 't3ver_id';
            $selectFields[] = 't3ver_state';
            $selectFields[] = 't3ver_wsid';
        }
        if ($l10nEnabled) {
            $selectFields[] = $tableConfig['ctrl']['languageField'];
            $selectFields[] = $tableConfig['ctrl']['transOrigPointerField'];
        }
        if ($tableConfig['ctrl']['label_alt']) {
            $selectFields = array_merge(
                $selectFields,
                GeneralUtility::trimExplode(',', $tableConfig['ctrl']['label_alt'], true)
            );
        }
        // Unique list!
        $selectFields = array_unique($selectFields);
        $fieldListFields = $this->makeFieldList($table, 1);
        if (empty($fieldListFields) && $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {
            $message = sprintf(
                htmlspecialchars($lang->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:missingTcaColumnsMessage'
                )),
                $table,
                $table
            );
            $messageTitle = htmlspecialchars(
                htmlspecialchars($lang->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:missingTcaColumnsMessageTitle'
                ))
            );
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                $messageTitle,
                FlashMessage::WARNING,
                true
            );
            /** @var $flashMessageService FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // Making sure that the fields in the field-list ARE in the field-list from TCA!
        $selectFields = array_intersect($selectFields, $fieldListFields);
        // implode it into a list of fields for the SQL-statement.
        $selFieldList = implode(',', $selectFields);
        $this->selFieldList = $selFieldList;
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as
                     $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                if (!$hookObject instanceof RecordListGetTableHookInterface) {
                    throw new \UnexpectedValueException(
                        '$hookObject must implement interface ' . RecordListGetTableHookInterface::class,
                        1195114460
                    );
                }
                $hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
            }
        }
        /** @noinspection PhpInternalEntityUsedInspection */
        $additionalConstraints = empty($addWhere) ? [] : [
            \TYPO3\CMS\Core\Database\Query\QueryHelper::stripLogicalOperatorPrefix($addWhere)
        ];
        $selFieldList = GeneralUtility::trimExplode(',', $selFieldList, true);

        // Create the SQL query for selecting the elements in the listing:
        // do not do paging when outputting as CSV
        if ($this->csvOutput) {
            $this->iLimit = 0;
        }
        if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
            // Get the two previous rows for sorting if displaying page > 1
            $this->firstElementNumber = $this->firstElementNumber - 2;
            $this->iLimit = $this->iLimit + 2;
            // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
            $queryBuilder = $this->getQueryBuilder($table, $id, $additionalConstraints);
            $this->firstElementNumber = $this->firstElementNumber + 2;
            $this->iLimit = $this->iLimit - 2;
        } else {
            // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
            $queryBuilder = $this->getQueryBuilder($table, $id, $additionalConstraints);
        }

        // Finding the total amount of records on the page
        // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
        $this->setTotalItems($table, $id, $additionalConstraints);

        // Init:
        $queryResult = $queryBuilder->execute();
        $dbCount = 0;
        $out = '';
        $tableHeader = '';
        $listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
        // If the count query returned any number of records, we perform the real query,
        // selecting records.
        if ($this->totalItems) {
            // Fetch records only if not in single table mode
            if ($listOnlyInSingleTableMode) {
                $dbCount = $this->totalItems;
            } else {
                // set the showLimit to the number of records when outputting as CSV
                if ($this->csvOutput) {
                    $this->showLimit = $this->totalItems;
                    $this->iLimit = $this->totalItems;
                }
                $dbCount = $queryResult->rowCount();
            }
        }
        // If any records was selected, render the list:
        if ($dbCount) {
            $tableTitle = htmlspecialchars($lang->sL($tableConfig['ctrl']['title']));
            if ($tableTitle === '') {
                $tableTitle = $table;
            }
            // Header line is drawn
            $theData = [];
            if ($this->disableSingleTableView) {
                $theData[$titleCol] = '<span class="c-table">' . BackendUtility::wrapInHelp($table, '', $tableTitle)
                    . '</span> (<span class="t3js-table-total-items">' . $this->totalItems . '</span>)';
            } else {
                $icon = $this->table
                    ? '<span title="' . htmlspecialchars($lang->getLL('contractView')) . '">'
                    . $this->iconFactory->getIcon('actions-view-table-collapse', Icon::SIZE_SMALL)->render() . '</span>'
                    : '<span title="' . htmlspecialchars($lang->getLL('expandView')) . '">'
                    . $this->iconFactory->getIcon('actions-view-table-expand', Icon::SIZE_SMALL)->render() . '</span>';
                $theData[$titleCol] = $this->linkWrapTable(
                    $table,
                    $tableTitle . ' (<span class="t3js-table-total-items">' . $this->totalItems . '</span>) ' . $icon
                );
            }
            if ($listOnlyInSingleTableMode) {
                $tableHeader .= BackendUtility::wrapInHelp($table, '', $theData[$titleCol]);
            } else {
                // Render collapse button if in multi table mode
                $collapseIcon = '';
                if (!$this->table) {
                    $href = htmlspecialchars((
                        $this->listURL() . '&collapse[' . $table . ']=' . ($tableCollapsed ? '0' : '1')
                    ));
                    $title = htmlspecialchars($tableCollapsed
                        ? $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.expandTable')
                        : $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.collapseTable'));
                    $icon = '<span class="collapseIcon">' . $this->iconFactory->getIcon(
                        ($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'),
                        Icon::SIZE_SMALL
                    )->render() . '</span>';
                    $collapseIcon = '<a href="' . $href . '" title="' . $title
                        . '" class="pull-right t3js-toggle-recordlist" data-table="' . htmlspecialchars($table)
                        . '" data-toggle="collapse" data-target="#recordlist-' . htmlspecialchars($table)
                        . '">' . $icon . '</a>';
                }
                $tableHeader .= $theData[$titleCol] . $collapseIcon;
            }
            // Render table rows only if in multi table view or if in single table view
            $rowOutput = '';
            if (!$listOnlyInSingleTableMode || $this->table) {
                // Fixing an order table for sortby tables
                $this->currentTable = [];
                $currentIdList = [];
                $doSort = ($tableConfig['ctrl']['sortby'] && !$this->sortField);
                $prevUid = 0;
                $prevPrevUid = 0;
                // Get first two rows and initialize prevPrevUid and prevUid if on page > 1
                if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
                    $row = $queryResult->fetch();
                    $prevPrevUid = -((int)$row['uid']);
                    $row = $queryResult->fetch();
                    $prevUid = $row['uid'];
                }

                $accRows = [];
                // Accumulate rows here
                while ($row = $queryResult->fetch()) {
                    if (!$this->isRowListingConditionFulfilled($table, $row)) {
                        continue;
                    }
                    // In offline workspace, look for alternative record:
                    BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
                    if (is_array($row)) {
                        $accRows[] = $row;
                        $currentIdList[] = $row['uid'];
                        if ($doSort) {
                            if ($prevUid) {
                                $this->currentTable['prev'][$row['uid']] = $prevPrevUid;
                                $this->currentTable['next'][$prevUid] = '-' . $row['uid'];
                                $this->currentTable['prevUid'][$row['uid']] = $prevUid;
                            }
                            $prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
                            $prevUid = $row['uid'];
                        }
                    }
                }
                $this->totalRowCount = count($accRows);

                // CSV initiated
                if ($this->csvOutput) {
                    $this->initCSV();
                }

                // Render items:
                $this->CBnames = [];
                $this->duplicateStack = [];
                $this->eCounter = $this->firstElementNumber;
                $cc = 0;
                foreach ($accRows as $row) {
                    // Render item row if counter < limit
                    if ($cc < $this->iLimit) {
                        $cc++;
                        $this->translations = false;
                        $rowOutput .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
                        // If localization view is enabled it means that the selected records are
                        // either default or All language and here we will not select translations
                        // which point to the main record:
                        if ($this->localizationView && $l10nEnabled) {
                            // For each available translation, render the record:
                            if (is_array($this->translations)) {
                                foreach ($this->translations as $lRow) {
                                    // $lRow isn't always what we want - if record was moved we've to work with the
                                    // placeholder records otherwise the list is messed up a bit
                                    if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
                                        $where = 't3ver_move_id="' . (int)$lRow['uid'] . '" AND pid="'
                                            . $row['_MOVE_PLH_pid'] . '" AND t3ver_wsid=' . $row['t3ver_wsid']
                                            . BackendUtility::deleteClause($table);
                                        $tmpRow = BackendUtility::getRecordRaw($table, $where, $selFieldList);
                                        $lRow = is_array($tmpRow) ? $tmpRow : $lRow;
                                    }
                                    // In offline workspace, look for alternative record:
                                    BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                                    if (is_array($lRow)
                                        && $backendUser->checkLanguageAccess(
                                            $lRow[$tableConfig['ctrl']['languageField']]
                                        )
                                    ) {
                                        $currentIdList[] = $lRow['uid'];
                                        $rowOutput .= $this->renderListRow(
                                            $table,
                                            $lRow,
                                            $cc,
                                            $titleCol,
                                            $thumbsCol,
                                            18
                                        );
                                    }
                                }
                            }
                        }
                    }
                    // Counter of total rows incremented:
                    $this->eCounter++;
                }
                // Record navigation is added to the beginning and end of the table if in single
                // table mode
                if ($this->table) {
                    $rowOutput = $this->renderListNavigation('top') . $rowOutput
                        . $this->renderListNavigation('bottom');
                } else {
                    // show that there are more records than shown
                    if ($this->totalItems > $this->itemsLimitPerTable) {
                        $countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ?
                            $this->itemsLimitSingleTable :
                            $this->totalItems;
                        $hasMore = ($this->totalItems > $this->itemsLimitSingleTable);
                        $colspan = $this->showIcon ? count($this->fieldArray) + 1 : count($this->fieldArray);
                        $rowOutput .= '<tr><td colspan="' . $colspan . '">
								<a href="' . htmlspecialchars(($this->listURL() . '&table=' . rawurlencode($table)))
                            . '" class="btn btn-default">'
                            . '<span class="t3-icon fa fa-chevron-down"></span> <i>[1 - ' . $countOnFirstPage
                            . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
                    }
                }
                // The header row for the table is now created:
                $out .= $this->renderListHeader($table, $currentIdList);
            }

            $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse in';
            $dataState = $tableCollapsed && !$this->table ? 'collapsed' : 'expanded';

            // The list of records is added after the header:
            $out .= $rowOutput;
            // ... and it is all wrapped in a table:
            $out = '



			<!--
				DB listing of elements:	"' . htmlspecialchars($table) . '"
			-->
				<div class="panel panel-space panel-default recordlist">
					<div class="panel-heading">
					' . $tableHeader . '
					</div>
					<div class="' . $collapseClass . '" data-state="' . $dataState . '" id="recordlist-'
                . htmlspecialchars($table) . '">
						<div class="table-fit">
							<table data-table="' . htmlspecialchars($table)
                . '" class="table table-striped table-hover'
                . ($listOnlyInSingleTableMode ? ' typo3-dblist-overview' : '') . '">
								' . $out . '
							</table>
						</div>
					</div>
				</div>
			';
            // Output csv if...
            // This ends the page with exit.
            if ($this->csvOutput) {
                $this->outputCSV($table);
            }
        }
        // Return content:
        return $out;
    }

    /**
     * Returns the SQL-query array to select the records
     * from a table $table with pid = $id.
     *
     * @param string $table Table name
     * @param int $id Page id (NOT USED! $this->pidSelect is used instead)
     * @param string $addWhere Additional part for where clause
     * @param string $fieldList Field list to select,
     *      for all (for "SELECT [fieldlist] FROM ...")
     *
     * @return array Returns query array
     */
    public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*')
    {
        $tableControl = ConfigurationUtility::getInstance()->getTcaValue($table . '.ctrl');

        $hookObjectsArr = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'] as
                     $classRef) {
                $hookObjectsArr[] = GeneralUtility::makeInstance($classRef);
            }
        }

        // Set ORDER BY:
        $orderBy = 'ORDER BY ' . $table . '.'
            . ($tableControl['sortby'] ? $tableControl['sortby'] : $tableControl['default_sortby']);
        if ($this->sortField) {
            if (in_array($this->sortField, $this->makeFieldList($table, 1))) {
                $orderBy = 'ORDER BY ' . $table . '.' . $this->sortField;
                if ($this->sortRev) {
                    $orderBy .= ' DESC';
                }
            }
        }

        // Set LIMIT:
        $limit = $this->iLimit ?
            ($this->firstElementNumber ? $this->firstElementNumber . ',' : '') . ($this->iLimit + 1) :
            '';
        // Filtering on displayable tx_commerce_categories (permissions):
        $pC = ($table == 'tx_commerce_categories' && $this->perms_clause) ? $this->perms_clause : '';

        // extra where for commerce
        $categoryWhere = sprintf($this->addWhere[$table], $this->categoryUid);

        // Adding search constraints:
        $search = $this->makeSearchString($table, $id);
        // Compiling query array:
        $queryParts = [
            'SELECT' => $fieldList,
            'FROM' => $table . $this->joinTables[$table],
            'WHERE' => $this->pidSelect .
                ' ' . $pC .
                BackendUtility::deleteClause($table) .
                BackendUtility::versioningPlaceholderClause($table) .
                ' ' . $addWhere . $categoryWhere .
                ' ' . $search,
            'GROUPBY' => '',
            'LIMIT' => $limit,
        ];
        $tempOrderBy = [];
        /** @noinspection PhpInternalEntityUsedInspection */
        foreach (\TYPO3\CMS\Core\Database\Query\QueryHelper::parseOrderBy($orderBy) as $orderPair) {
            list($fieldName, $order) = $orderPair;
            if ($order !== null) {
                $tempOrderBy[] = implode(' ', $orderPair);
            } else {
                $tempOrderBy[] = $fieldName;
            }
        }
        $queryParts['ORDERBY'] = implode(',', $tempOrderBy);
        // Filter out records that are translated, if TSconfig mod.web_list.hideTranslations is set
        if ((
                in_array($table, GeneralUtility::trimExplode(',', $this->hideTranslations))
                || $this->hideTranslations === '*'
            )
            && !empty($tableControl['transOrigPointerField'])
        ) {
            $queryParts['WHERE'] .= ' AND ' . $tableControl['transOrigPointerField'] . '=0 ';
        }
        // Apply hook as requested in http://forge.typo3.org/issues/16634
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'makeQueryArray_post')) {
                $parameter = [
                    'orderBy' => $orderBy,
                    'limit' => $limit,
                    'pC' => $pC,
                    'search' => $search,
                ];
                $hookObj->makeQueryArray_post($queryParts, $this, $table, $id, $addWhere, $fieldList, $parameter);
            }
        }
        // Return query:
        return $queryParts;
    }

    /**
     * Set the total items for the record list
     *
     * @param string $table Table name
     * @param int $pageId Only used to build the search constraints, $this->pidList is used for restrictions
     * @param array $constraints Additional constraints for where clause
     */
    public function setTotalItems(string $table, int $pageId, array $constraints)
    {
        $queryParameters = $this->buildQueryParameters($table, $pageId, ['*'], $constraints);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($queryParameters['table']);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder
            ->from($queryParameters['table'], 't')
            ->where(...$queryParameters['where']);

        $this->totalItems = (int)$queryBuilder->count('*')
            ->execute()
            ->fetchColumn();
    }

    /**
     * Returns a QueryBuilder configured to select $fields from $table where the pid is restricted
     * depending on the current searchlevel setting.
     *
     * @param string $table Table name
     * @param int $pageId Page id Only used to build the search constraints, getPageIdConstraint() used for restrictions
     * @param string[] $additionalConstraints Additional part for where clause
     * @param string[] $fields Field list to select, * for all
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    public function getQueryBuilder(
        string $table,
        int $pageId,
        array $additionalConstraints = [],
        array $fields = ['*']
    ) : QueryBuilder
    {
        $queryParameters = $this->buildQueryParameters($table, $pageId, $fields, $additionalConstraints);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($queryParameters['table']);
        /** @var DeletedRestriction $deleteRestriction */
        $deleteRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);
        /** @var $workspaceRestriction $workspaceRestriction */
        $workspaceRestriction = GeneralUtility::makeInstance(BackendWorkspaceRestriction::class);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add($deleteRestriction)
            ->add($workspaceRestriction);
        $queryBuilder
            ->select(...$queryParameters['fields'])
            ->from($queryParameters['table'], 't')
            ->where(...$queryParameters['where']);

        if (!empty($queryParameters['orderBy'])) {
            foreach ($queryParameters['orderBy'] as $fieldNameAndSorting) {
                list($fieldName, $sorting) = $fieldNameAndSorting;
                $queryBuilder->addOrderBy($fieldName, $sorting);
            }
        }

        if (!empty($queryParameters['firstResult'])) {
            $queryBuilder->setFirstResult((int)$queryParameters['firstResult']);
        }

        if (!empty($queryParameters['maxResults'])) {
            $queryBuilder->setMaxResults((int)$queryParameters['maxResults']);
        }

        if (!empty($queryParameters['groupBy'])) {
            $queryBuilder->groupBy($queryParameters['groupBy']);
        }

        if ($table == 'tx_commerce_categories') {
            $queryBuilder->innerJoin(
                't',
                'tx_commerce_categories_parent_category_mm',
                'mm',
                't.uid = mm.uid_local'
            );
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($this->categoryUid, \PDO::PARAM_INT)
                )
            );
        } elseif ($table == 'tx_commerce_products') {
            $queryBuilder->innerJoin(
                't',
                'tx_commerce_products_categories_mm',
                'mm',
                't.uid = mm.uid_local'
            );
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($this->categoryUid, \PDO::PARAM_INT)
                )
            );
        }

        return $queryBuilder;
    }

    /**
     * Return the query parameters to select the records from a table $table with pid = $this->pidList
     *
     * @param string $table Table name
     * @param int $pageId Page id Only used to build the search constraints, $this->pidList is used for restrictions
     * @param string[] $fieldList List of fields to select from the table
     * @param string[] $additionalConstraints Additional part for where clause
     * @return array
     */
    protected function buildQueryParameters(
        string $table,
        int $pageId,
        array $fieldList = ['*'],
        array $additionalConstraints = []
    ) : array
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table)
            ->expr();

        $fieldList = array_map(
            function ($field) {
                return (strpos('.', $field) === false ? 't.' : '') . $field;
            },
            $fieldList
        );

        $parameters = [
            'table' => $table,
            'fields' => $fieldList,
            'groupBy' => null,
            'orderBy' => null,
            'firstResult' => $this->firstElementNumber ?: null,
            'maxResults' => $this->iLimit ? $this->iLimit : null,
        ];

        if ($this->sortField && in_array($this->sortField, $this->makeFieldList($table, 1))) {
            $parameters['orderBy'][] = $this->sortRev ? [$this->sortField, 'DESC'] : [$this->sortField, 'ASC'];
        } else {
            $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?: $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
            /** @noinspection PhpInternalEntityUsedInspection */
            $parameters['orderBy'] = \TYPO3\CMS\Core\Database\Query\QueryHelper::parseOrderBy((string)$orderBy);
        }

        if (is_array($parameters['orderBy'])) {
            $parameters['orderBy'] = array_map(
                function ($orderBy) {
                    $orderBy[0] = 't.' . $orderBy[0];
                    return $orderBy;
                },
                $parameters['orderBy']
            );
        }

        // Build the query constraints
        $constraints = [
            'pidSelect' => str_replace($table, 't', $this->getPageIdConstraint($table)),
            'search' => $this->makeSearchString($table, $pageId)
        ];

        // Filtering on displayable pages (permissions):
        if ($table === 'tx_commerce_categories' && $this->perms_clause) {
            $constraints['pagePermsClause'] = $this->perms_clause;
        }

        // Filter out records that are translated, if TSconfig mod.web_list.hideTranslations is set
        if ((GeneralUtility::inList($this->hideTranslations, $table) || $this->hideTranslations === '*')
            && !empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
            && $table !== 'pages_language_overlay'
        ) {
            $constraints['transOrigPointerField'] = $expressionBuilder->eq(
                't.' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                0
            );
        }

        $parameters['where'] = array_merge($constraints, $additionalConstraints);

        $hookName = DatabaseRecordList::class;
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['buildQueryParameters'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['buildQueryParameters'] as $classRef) {
                $hookObject = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObject, 'buildQueryParametersPostProcess')) {
                    $hookObject->buildQueryParametersPostProcess(
                        $parameters,
                        $table,
                        $pageId,
                        $additionalConstraints,
                        $fieldList,
                        $this
                    );
                }
            }
        }

        // array_unique / array_filter used to eliminate empty and duplicate constraints
        // the array keys are eliminated by this as well to facilitate argument unpacking
        // when used with the querybuilder.
        $parameters['where'] = array_unique(array_filter(array_values($parameters['where'])));

        return $parameters;
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display).
     *
     * @param int $h Is an int >=0 and denotes how tall a element is.
     *      Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join'
     *      and above makes 'line'
     * @param string $icon Is the <img>+<a> of the record. If not supplied the
     *      first 'join'-icon will be a 'line' instead
     * @param array $data Is the dataarray, record with the fields.
     *      Notice: These fields are (currently) NOT htmlspecialchar'ed before being
     *      wrapped in <td>-tags
     * @param string $rowParams Is insert in the <td>-tags.
     *      Must carry a ' ' as first character
     * @param string $_ OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (int)
     * @param string $_2 OBSOLETE - NOT USED ANYMORE. Is the HTML <img>-tag for an
     *      alternative 'gfx/ol/line.gif'-icon (used in the top)
     * @param string $colType Defines the tag being used for the columns. Default is td.
     * @return string HTML content for the table row
     */
    public function addElement($h, $icon, $data, $rowParams = '', $_ = '', $_2 = '', $colType = 'td')
    {
        $colType = ($colType === 'th') ? 'th' : 'td';
        $noWrap = $this->no_noWrap ? '' : ' nowrap="nowrap"';
        // Start up:
        $parent = isset($data['parent']) ? (int)$data['parent'] : 0;
        $out = '
		<!-- Element, begin: -->
		<tr ' . $rowParams . ' data-uid="' . (int)$data['uid'] . '" data-l10nparent="' . $parent . '">';
        // Show icon and lines
        if ($this->showIcon) {
            $out .= '
			<' . $colType . ' nowrap="nowrap" class="col-icon">';
            if (!$h) {
                $out .= '&nbsp;';
            } else {
                for ($a = 0; $a < $h; $a++) {
                    if (!$a) {
                        if ($icon) {
                            $out .= $icon;
                        }
                    }
                }
            }
            $out .= '</' . $colType . '>
			';
        }
        // Init rendering.
        $colsp = '';
        $lastKey = '';
        $c = 0;
        $ccount = 0;
        // __label is used as the label key to circumvent problems with uid used as label (see #67756)
        // as it was introduced later on, check if it really exists before using it
        $fields = $this->fieldArray;
        if ($colType === 'td' && array_key_exists('__label', $data)) {
            $fields[0] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($this->fieldArray as $vKey) {
            if (isset($data[$vKey])) {
                if ($lastKey) {
                    $cssClass = $this->addElement_tdCssClass[$lastKey];
                    if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
                        $cssClass = implode(
                            ' ',
                            [$this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass]
                        );
                    }
                    $out .= '
						<' . $colType . $noWrap . ' class="' . $cssClass . '"' . $colsp
                        . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</' . $colType . '>';
                }
                $lastKey = $vKey;
                $c = 1;
                $ccount++;
            } else {
                if (!$lastKey) {
                    $lastKey = $vKey;
                }
                $c++;
            }
            if ($c > 1) {
                $colsp = ' colspan="' . $c . '"';
            } else {
                $colsp = '';
            }
        }
        if ($lastKey) {
            $cssClass = $this->addElement_tdCssClass[$lastKey];
            if ($this->oddColumnsCssClass) {
                $cssClass = implode(' ', [$this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass]);
            }
            $out .= '
				<' . $colType . $noWrap . ' class="' . $cssClass . '"' . $colsp . $this->addElement_tdParams[$lastKey]
                . '>' . $data[$lastKey] . '</' . $colType . '>';
        }
        // End row
        $out .= '
		</tr>';
        // Return row.
        return $out;
    }

    /**
     * Rendering the header row for a table.
     *
     * @param string $table Table name
     * @param array $currentIdList Array of the currently displayed uids of the table
     *
     * @return string Header table row
     *
     * @throws \UnexpectedValueException If hook was of wrong interface
     */
    public function renderListHeader($table, $currentIdList)
    {
        $lang = $this->getLanguageService();
        $tableConfig = ConfigurationUtility::getInstance()->getTcaValue($table);

        // Init:
        $theData = [];
        $icon = '';
        // Traverse the fields:
        foreach ($this->fieldArray as $fCol) {
            // Calculate users permissions to edit records in the table:
            $permsEdit = $this->calcPerms & ($table == 'tx_commerce_categories' ? 2 : 16)
                && $this->overlayEditLockPermissions($table);
            switch ((string) $fCol) {
                case '_PATH_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels._PATH_'
                    ))
                    . ']</i>';
                    break;

                case '_REF_':
                    // References
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_mod_file_list.xlf:c__REF_'
                    ))
                    . ']</i>';
                    break;

                case '_LOCALIZATION_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels._LOCALIZATION_'
                    ))
                    . ']</i>';
                    break;

                case '_LOCALIZATION_b':
                    // Path
                    $theData[$fCol] = htmlspecialchars($lang->getLL('Localize'));
                    break;

                case '_CLIPBOARD_':
                    if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
                        break;
                    }
                    // Clipboard:
                    $cells = [];
                    // If there are elements on the clipboard for this table, and the parent page is not locked by
                    // editlock then display the "paste into" icon:
                    $elFromTable = $this->clipObj->elFromTable($table);
                    if (!empty($elFromTable) && $this->overlayEditLockPermissions($table)) {
                        $href = htmlspecialchars($this->clipObj->pasteUrl($table, $this->id));
                        $confirmMessage = $this->clipObj->confirmMsgText(
                            'tx_commerce_categories',
                            $this->categoryRow,
                            'into',
                            $elFromTable
                        );
                        $cells['pasteAfter'] = '<a class="btn btn-default t3js-modal-trigger"' .
                            ' href="' . $href . '"' .
                            ' title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"' .
                            ' data-title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"' .
                            ' data-content="' . htmlspecialchars($confirmMessage) . '"' .
                            ' data-severity="warning">' .
                            $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() .
                            '</a>';
                    }
                    // If the numeric clipboard pads are enabled, display the control icons for that:
                    if ($this->clipObj->current != 'normal') {
                        // The "select" link:
                        $spriteIcon = '<span title="' . htmlspecialchars($lang->getLL('clip_selectMarked')) . '">'
                            . $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render()
                            . '</span>';
                        $cells['copyMarked'] = $this->linkClipboardHeaderIcon($spriteIcon, $table, 'setCB');

                        // The "edit marked" link:
                        $editIdList = implode(',', $currentIdList);
                        $editIdList = '\'+editList(' . GeneralUtility::quoteJSvalue($table) . ','
                            . GeneralUtility::quoteJSvalue($editIdList) . ')+\'';
                        $params = 'edit[' . $table . '][' . $editIdList . ']=edit';
                        $onClick = BackendUtility::editOnClick('', '', -1);
                        $onClickArray = explode('?', $onClick, 2);
                        $lastElement = array_pop($onClickArray);
                        array_push($onClickArray, $params . '&' . $lastElement);
                        $onClick = implode('?', $onClickArray);
                        $cells['edit'] = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick)
                            . '" title="'
                            . htmlspecialchars($lang->getLL('clip_editMarked')) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';

                        // The "Delete marked" link:
                        $cells['delete'] = $this->linkClipboardHeaderIcon(
                            '<span title="' . htmlspecialchars($lang->getLL('clip_deleteMarked')) . '">'
                            . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render()
                            . '</span>',
                            $table,
                            'delete',
                            sprintf($lang->getLL('clip_deleteMarkedWarning'), $lang->sL($tableConfig['ctrl']['title']))
                        );

                        // The "Select all" link:
                        $onClick = htmlspecialchars(('checkOffCB('
                            . GeneralUtility::quoteJSvalue(implode(',', $this->CBnames)) . ', this); return false;'));
                        $cells['markAll'] = '<a class="btn btn-default" rel="" href="#" onclick="' . $onClick
                            . '" title="' . htmlspecialchars($lang->getLL('clip_markRecords')) . '">'
                            . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL)->render()
                            . '</a>';
                    } else {
                        $cells['empty'] = '';
                    }

                    /**
                     * @hook renderListHeaderActions: Allows to change the clipboard icons of the Web>List table headers
                     * @usage Above each listed table in Web>List a header row is shown.
                     *        This hook allows to modify the icons responsible for the clipboard functions
                     *        (shown above the clipboard checkboxes when a clipboard other than "Normal" is selected),
                     *        or other "Action" functions which perform operations on the listed records.
                    */
                    if (is_array(
                        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions']
                    )) {
                        $scOptions = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc'];
                        foreach ($scOptions['actions'] as $classData) {
                            $hookObject = GeneralUtility::makeInstance($classData);
                            if (!$hookObject instanceof RecordListHookInterface) {
                                throw new \UnexpectedValueException(
                                    '$hookObject must implement interface ' . RecordListHookInterface::class,
                                    1195567850
                                );
                            }
                            $cells = $hookObject->renderListHeaderActions($table, $currentIdList, $cells, $this);
                        }
                    }
                    $theData[$fCol] = '<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
                    break;

                case '_CONTROL_':
                    // Control panel:
                    if ($this->isEditable($table)) {
                        // If new records can be created on this page, add links:
                        $permsAdditional = ($table === 'tx_commerce_categories' ? 8 : 16);
                        if ($this->calcPerms & $permsAdditional && $this->showNewRecLink($table)) {
                            $spriteIcon = $table === 'tx_commerce_categories'
                                ? $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)->render()
                                : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render();
                            if ($table === 'tt_content' && $this->newWizards) {
                                // If mod.newContentElementWizard.override is set, use that extension's create new
                                // content wizard instead:
                                $tmpTSc = BackendUtility::getModTSconfig((int)$this->pageinfo['uid'], 'mod');
                                $newContentElementWizard =
                                    $tmpTSc['properties']['newContentElementWizard.']['override'] ?:
                                        'new_content_element';
                                $newContentWizScriptPath = BackendUtility::getModuleUrl(
                                    $newContentElementWizard,
                                    ['id' => $this->id]
                                );

                                $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($newContentWizScriptPath)
                                    . ');';
                                $icon = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick)
                                    . '" title="'
                                    . htmlspecialchars($lang->getLL('new')) . '">' . $spriteIcon . '</a>';
                            } elseif ($table == 'tx_commerce_categories' && $this->newWizards) {
                                $parameters = [
                                    'id' => $this->id,
                                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                                ];
                                $href = BackendUtility::getModuleUrl('db_new', $parameters);
                                $icon = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                                    . htmlspecialchars($lang->getLL('new')) . '">'
                                    . $spriteIcon . '</a>';
                            } else {
                                $params = '&edit[' . $table . '][' . $this->id . ']=new';
                                $icon = '<a class="btn btn-default" href="#" onclick="'
                                    . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                                    . '" title="' . htmlspecialchars($lang->getLL('new')) . '">' . $spriteIcon . '</a>';
                            }
                        }

                        // If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
                        if ($permsEdit && $this->table && is_array($currentIdList)) {
                            $editIdList = implode(',', $currentIdList);
                            if ($this->clipNumPane()) {
                                $editIdList = '\'+editList(' . GeneralUtility::quoteJSvalue($table) . ','
                                    . GeneralUtility::quoteJSvalue($editIdList) . ')+\'';
                            }
                            $params = 'edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly='
                                . implode(',', $this->fieldArray);
                            // we need to build this uri differently, otherwise GeneralUtility::quoteJSvalue messes
                            // up the edit list function
                            $onClick = BackendUtility::editOnClick('', '', -1);
                            $onClickArray = explode('?', $onClick, 2);
                            $lastElement = array_pop($onClickArray);
                            array_push($onClickArray, $params . '&' . $lastElement);
                            $onClick = implode('?', $onClickArray);
                            $icon .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick)
                                . '" title="' . htmlspecialchars($lang->getLL('editShownColumns')) . '">'
                                . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render()
                                . '</a>';
                            $icon = '<div class="btn-group" role="group">' . $icon . '</div>';
                        }
                        // Add an empty entry, so column count fits again after moving this into $icon
                        $theData[$fCol] = '&nbsp;';
                    }
                    break;

                default:
                    // Regular fields header:
                    $theData[$fCol] = '';

                    // Check if $fCol is really a field and get the label and remove the colons
                    // at the end
                    $sortLabel = BackendUtility::getItemLabel($table, $fCol);
                    if ($sortLabel !== null) {
                        $sortLabel = htmlspecialchars($lang->sL($sortLabel));
                        $sortLabel = rtrim(trim($sortLabel), ':');
                    } else {
                        // No TCA field, only output the $fCol variable with square brackets []
                        $sortLabel = htmlspecialchars($fCol);
                        $sortLabel = '<i>[' . rtrim(trim($sortLabel), ':') . ']</i>';
                    }

                    if ($this->table && is_array($currentIdList)) {
                        // If the numeric clipboard pads are selected, show duplicate sorting link:
                        if ($this->clipNumPane()) {
                            $theData[$fCol] .= '<a class="btn btn-default" href="'
                                . htmlspecialchars($this->listURL('', '-1') . '&duplicateField=' . $fCol)
                                . '" title="' . htmlspecialchars($lang->getLL('clip_duplicates')) . '">'
                                . $this->iconFactory->getIcon(
                                    'actions-document-duplicates-select',
                                    Icon::SIZE_SMALL
                                )->render()
                                . '</a>';
                        }

                        // If the table can be edited, add link for editing THIS field for all
                        // listed records:
                        if ($this->isEditable($table) && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
                            $editIdList = implode(',', $currentIdList);
                            if ($this->clipNumPane()) {
                                $editIdList = '\'+editList(' . GeneralUtility::quoteJSvalue($table) . ','
                                    . GeneralUtility::quoteJSvalue($editIdList) . ')+\'';
                            }
                            $params = 'edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol;
                            // we need to build this uri differently, otherwise GeneralUtility::quoteJSvalue messes
                            // up the edit list function
                            $onClick = BackendUtility::editOnClick('', '', -1);
                            $onClickArray = explode('?', $onClick, 2);
                            $lastElement = array_pop($onClickArray);
                            array_push($onClickArray, $params . '&' . $lastElement);
                            $onClick = implode('?', $onClickArray);
                            $iTitle = sprintf($lang->getLL('editThisColumn'), $sortLabel);
                            $theData[$fCol] .= '<a class="btn btn-default" href="#" onclick="'
                                . htmlspecialchars($onClick) . '" title="' . htmlspecialchars($iTitle) . '">'
                                . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render()
                                . '</a>';
                        }

                        if (strlen($theData[$fCol]) > 0) {
                            $theData[$fCol] = '<div class="btn-group" role="group">' . $theData[$fCol] . '</div> ';
                        }
                    }
                    $theData[$fCol] .= $this->addSortLink($sortLabel, $fCol, $table);
            }
        }

        /**
         * @hook renderListHeader: Allows to change the contents of columns/cells of the Web>List table headers
         * @usage Above each listed table in Web>List a header row is shown.
         *        Containing the labels of all shown fields and additional icons to create new records for this
         *        table or perform special clipboard tasks like mark and copy all listed records to clipboard, etc.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            $scOptions = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc'];
            foreach ($scOptions['actions'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException(
                        '$hookObject must implement interface ' . RecordListHookInterface::class,
                        1195567855
                    );
                }
                $theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
            }
        }

        // Create and return header table row:
        return '<thead>' . $this->addElement(1, $icon, $theData, '', '', '', 'th') . '</thead>';
    }

    /**
     * Creates the control panel for a single record in the listing.
     *
     * @param string $table The table
     * @param array $row The record for which to make the control panel.
     *
     * @return string HTML table with the control panel (unless disabled)
     *
     * @throws \UnexpectedValueException If hook was of wrong interface
     */
    public function makeControl($table, $row)
    {
        $tableConfig = ConfigurationUtility::getInstance()->getTcaValue($table);
        /**
         * Utility.
         *
         * @var \CommerceTeam\Commerce\Utility\BackendUserUtility $backendUserUtility
         */
        $backendUserUtility = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUserUtility::class);
        $module = $this->getModule();
        $rowUid = $row['uid'];
        if (ExtensionManagementUtility::isLoaded('version') && isset($row['_ORIG_uid'])) {
            $rowUid = $row['_ORIG_uid'];
        }
        $cells = [
            'primary' => [],
            'secondary' => []
        ];

        // If the listed table is 'tx_commerce_categories' we have to request the permission settings for each page:
        $localCalcPerms = 0;
        if ($table == 'tx_commerce_categories' || $table == 'tx_commerce_products') {
            $localCalcPerms = $backendUserUtility->calcPerms(
                (array) BackendUtility::getRecord('tx_commerce_categories', $this->categoryUid)
            );
        }

        // This expresses the edit permissions for this particular element:
        $permsEdit = $table === 'tx_commerce_categories'
            && $localCalcPerms & Permission::PAGE_EDIT
            || $table != 'tx_commerce_categories'
            && $this->calcPerms & Permission::CONTENT_EDIT;
        $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);

        // "Show" link (only tx_commerce_categories and tx_commerce_products elements)
        // @todo test url generation
        if ($table == 'tx_commerce_categories' || $table == 'tx_commerce_products') {
            $pid = \CommerceTeam\Commerce\Utility\ConfigurationUtility::getInstance()
                ->getConfiguration('previewPageID');

            $params = '&id=' . $pid . '&tx_commerce_pi1[catUid]=';
            if ($table == 'tx_commerce_categories') {
                if ($row['l18n_parent']) {
                    $params .= $row['l18n_parent'] . '&L=' . $row['sys_language_uid'];
                } else {
                    $params .= $row['uid'];
                }
            } else {
                $params .= $this->categoryUid;
            }

            if ($table == 'tx_commerce_products') {
                $params .= '&tx_commerce_pi1[showUid]=';
                if ($row['l18n_parent']) {
                    $params .= $row['l18n_parent'] . '&L=' . $row['sys_language_uid'];
                } else {
                    $params .= $row['uid'];
                }
            }
            /** @var $cacheHash CacheHashCalculator */
            $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class);
            $cHash = $cacheHash->generateForParameters($params);
            $params .= $cHash ? '&cHash=' . $cHash : '';

            $viewAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars(
                    BackendUtility::viewOnClick(
                        $this->previewPageId,
                        '',
                        '',
                        '',
                        '/index.php?id=' . $this->previewPageId . $params
                    )
                ) . '" title="'
                . htmlspecialchars($this->getLanguageService()->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage'
                )) . '">'
                . $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
            $this->addActionToCellGroup($cells, $viewAction, 'view');
        }

        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit) {
            $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
            $iconIdentifier = 'actions-open';
            $overlayIdentifier = !$this->isEditable($table) ? 'overlay-readonly' : null;
            $editAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">'
                . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL, $overlayIdentifier)->render() . '</a>';
        } else {
            $editAction = $this->spaceIcon;
        }
        $this->addActionToCellGroup($cells, $editAction, 'edit');

        // "Info": (All records)
        $onClick = 'top.launchView(' . GeneralUtility::quoteJSvalue($table) . ', ' . (int)$row['uid']
            . '); return false;';
        $viewBigAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
            . htmlspecialchars($this->getLanguageService()->getLL('showInfo')) . '">'
            . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
        $this->addActionToCellGroup($cells, $viewBigAction, 'viewBig');

        // "Move" wizard link for tx_commerce_categories/tx_commerce_products elements:
        // @todo fix this
        if ($permsEdit && ($table === 'tx_commerce_products' || $table === 'tx_commerce_categories')) {
            $onClick = 'return jumpExt('
                . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('move_commerce_element')
                . '&table=' . $table . '&uid=' . $row['uid']) . ');';
            $linkTitleLL = htmlspecialchars($this->getLanguageService()->getLL(
                'move_' . ($table === 'tx_commerce_products' ? 'record' : 'page')
            ));
            $icon = ($table == 'tx_commerce_categories' ?
                $this->iconFactory->getIcon('actions-page-move', Icon::SIZE_SMALL)->render() :
                $this->iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL)->render()
            );
            $moveAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
                . $linkTitleLL . '">' . $icon . '</a>';
            $this->addActionToCellGroup($cells, $moveAction, 'move');
        }

        // If the table is NOT a read-only table, then show these links:
        if ($this->isEditable($table)) {
            // "Revert" link (history/undo)
            $moduleUrl = BackendUtility::getModuleUrl('record_history', ['element' => $table . ':' . $row['uid']]);
            $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($moduleUrl) . ',\'#latest\');';
            $historyAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
                . htmlspecialchars($this->getLanguageService()->getLL('history')) . '">'
                . $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render() . '</a>';
            $this->addActionToCellGroup($cells, $historyAction, 'history');

            // Versioning:
            // @todo needs testing
            if (ExtensionManagementUtility::isLoaded('version')
                && !ExtensionManagementUtility::isLoaded('workspaces')
            ) {
                $vers = BackendUtility::selectVersionsOfRecord(
                    $table,
                    $row['uid'],
                    'uid',
                    $this->getBackendUserAuthentication()->workspace,
                    false,
                    $row
                );
                // If table can be versionized.
                if (is_array($vers)) {
                    $href = BackendUtility::getModuleUrl('web_txversionM1', [
                        'table' => $table, 'uid' => $row['uid']
                    ]);
                    $versionAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($this->getLanguageService()->getLL('displayVersions')) . '">'
                        . $this->iconFactory->getIcon('actions-version-page-open', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $versionAction, 'version');
                }
            }

            // "Edit Perms" link:
            // @todo fix this
            if ($table === 'tx_commerce_categories'
                && $this->getBackendUserAuthentication()->check('modules', 'system_BeuserTxPermission')
                && ExtensionManagementUtility::isLoaded('beuser')
            ) {
                $href = BackendUtility::getModuleUrl('system_BeuserTxPermission') . '&id=' . $row['uid']
                    . '&return_id=' . $row['uid'] . '&edit=1';
                $permsAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                    . htmlspecialchars($this->getLanguageService()->getLL('permissions')) . '">'
                    . $this->iconFactory->getIcon('status-status-locked', Icon::SIZE_SMALL)->render() . '</a>';
                $this->addActionToCellGroup($cells, $permsAction, 'perms');
            }

            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row
            // or if default values can depend on previous record):
            if (($tableConfig['ctrl']['sortby'] || $tableConfig['ctrl']['useColumnsForDefaultValues']) && $permsEdit) {
                if ($table !== 'tx_commerce_categories'
                    && $this->calcPerms & Permission::CONTENT_EDIT
                    || $table === 'tx_commerce_categories'
                    && $this->calcPerms & Permission::PAGE_NEW
                ) {
                    if ($this->showNewRecLink($table)) {
                        $params = '&edit[' . $table . '][' . -(
                            $row['_MOVE_PLH'] ? $row['_MOVE_PLH_uid'] : $row['uid']
                        ) . ']=new';
                        $categoryField = $table == 'tx_commerce_categories' ? 'parent_category' : 'categories';
                        $params .= '&defVals[' . $table . '][' . $categoryField . '] = ' . $this->categoryUid;

                        $icon = $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render();
                        $titleLabel = 'new';
                        if ($tableConfig['ctrl']['sortby']) {
                            $titleLabel .= 'Record';
                        }
                        $newAction = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($titleLabel)) . '">'
                            . $icon . '</a>';
                        $this->addActionToCellGroup($cells, $newAction, 'new');
                    }
                }
            }

            // "Up/Down" links
            if ($permsEdit && $tableConfig['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
                if (isset($this->currentTable['prev'][$row['uid']])) {
                    // Up
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]='
                        . $this->currentTable['prev'][$row['uid']];
                    $moveUpAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars(
                            'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                        )
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveUp')) . '">'
                        . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveUpAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveUpAction, 'moveUp');

                if ($this->currentTable['next'][$row['uid']]) {
                    // Down
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]='
                        . $this->currentTable['next'][$row['uid']];
                    $moveDownAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars(
                            'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                        )
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveDown')) . '">'
                        . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveDownAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveDownAction, 'moveDown');
            }

            // "Hide/Unhide" links:
            $hiddenField = $tableConfig['ctrl']['enablecolumns']['disabled'];
            if ($permsEdit && $hiddenField && $tableConfig['columns'][$hiddenField]
                && (!$tableConfig['columns'][$hiddenField]['exclude']
                    || $this->getBackendUserAuthentication()->check('non_exclude_fields', $table . ':' . $hiddenField))
            ) {
                if ($this->isRecordCurrentBackendUser($table, $row)) {
                    $hideAction = $this->spaceIcon;
                } else {
                    $hideTitle = htmlspecialchars($this->getLanguageService()->getLL(
                        'hide' . ($table == 'tx_commerce_categories' ? 'Category' : '')
                    ));
                    $unhideTitle = htmlspecialchars($this->getLanguageService()->getLL(
                        'unHide' . ($table == 'tx_commerce_categories' ? 'Category' : '')
                    ));
                    if ($row[$hiddenField]) {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=0';
                        $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="hidden" href="#"'
                            . ' data-params="' . htmlspecialchars($params) . '"'
                            . ' title="' . $unhideTitle . '"'
                            . ' data-toggle-title="' . $hideTitle . '">'
                            . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=1';
                        $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="visible" href="#"'
                            . ' data-params="' . htmlspecialchars($params) . '"'
                            . ' title="' . $hideTitle . '"'
                            . ' data-toggle-title="' . $unhideTitle . '">'
                            . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render() . '</a>';
                    }
                }
                $this->addActionToCellGroup($cells, $hideAction, 'hide');
            }

            // "Delete" link:
            if ($permsEdit
                && ($table === 'tx_commerce_categories'
                    && $localCalcPerms & Permission::PAGE_DELETE
                    || $table !== 'tx_commerce_categories'
                    && $this->calcPerms & Permission::CONTENT_EDIT
                )
            ) {
                // Check if the record version is in "deleted" state, because that will switch the action to "restore"
                if ($this->getBackendUserAuthentication()->workspace > 0
                    && isset($row['t3ver_state'])
                    && (int)$row['t3ver_state'] === 2
                ) {
                    $actionName = 'restore';
                    $refCountMsg = '';
                } else {
                    $actionName = 'delete';
                    $refCountMsg = BackendUtility::referenceCount(
                        $table,
                        $row['uid'],
                        ' '
                        . $this->getLanguageService()->sL(
                            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'
                        ),
                        $this->getReferenceCount($table, $row['uid'])
                    ) . BackendUtility::translationCount(
                        $table,
                        $row['uid'],
                        ' ' . $this->getLanguageService()->sL(
                            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord'
                        )
                    );
                }

                if ($this->isRecordCurrentBackendUser($table, $row)) {
                    $deleteAction = $this->spaceIcon;
                } else {
                    $titleOrig = BackendUtility::getRecordTitle($table, $row, false, true);
                    $title = str_replace('\\', '\\\\', GeneralUtility::fixed_lgd_cs($titleOrig, $this->fixedL));
                    $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" '
                        . '[' . $table . ':' . $row['uid'] . ']' . $refCountMsg;

                    $params = 'cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
                    $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
                    $linkTitle = htmlspecialchars($this->getLanguageService()->getLL($actionName));
                    $deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" '
                        . ' data-l10parent="' . htmlspecialchars($row['l10n_parent']) . '"'
                        . ' data-params="' . htmlspecialchars($params) . '" data-title="'
                        . htmlspecialchars($titleOrig) . '"'
                        . ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"'
                        . '>' . $icon . '</a>';
                }
            } else {
                $deleteAction = $this->spaceIcon;
            }
            $this->addActionToCellGroup($cells, $deleteAction, 'delete');

            // "Levels" links: Moving tx_commerce_categories into new levels...
            // @todo fix this
            if ($permsEdit && $table == 'tx_commerce_categories' && !$this->searchLevels) {
                // Up (Paste as the page right after the current parent page)
                if ($this->calcPerms & Permission::PAGE_NEW) {
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . -$this->id;
                    $moveLeftAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars(
                            'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                        )
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('prevLevel')) . '">'
                        . $this->iconFactory->getIcon('actions-move-left', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $moveLeftAction, 'moveLeft');
                }
                // Down (Paste as subpage to the page right above)
                if ($this->currentTable['prevUid'][$row['uid']]) {
                    $localCalcPerms = $backendUserUtility->calcPerms(
                        BackendUtility::getRecord('tx_commerce_categories', $this->currentTable['prevUid'][$row['uid']])
                    );
                    if ($localCalcPerms & Permission::PAGE_NEW) {
                        $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]='
                            . $this->currentTable['prevUid'][$row['uid']];
                        $moveRightAction = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars(
                                'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                            )
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('nextLevel')) . '">'
                            . $this->iconFactory->getIcon('actions-move-right', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $moveRightAction = $this->spaceIcon;
                    }
                } else {
                    $moveRightAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveRightAction, 'moveRight');
            }
        }

        /**
         * @hook recStatInfoHooks: Allows to insert HTML before record icons on various places
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $stat = '';
            $_params = [$table, $row['uid']];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
            $this->addActionToCellGroup($cells, $stat, 'stat');
        }
        /**
         * @hook makeControl: Allows to change control icons of records in list-module
         * @usage This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the icons/actions generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            // for compatibility reason, we move all icons to the rootlevel
            // before calling the hooks
            foreach ($cells as $section => $actions) {
                foreach ($actions as $actionKey => $action) {
                    $cells[$actionKey] = $action;
                }
            }
            $scOptions = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'];
            foreach ($scOptions['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException(
                        '$hookObject must implement interface ' . RecordListHookInterface::class,
                        1195567840
                    );
                }
                $cells = $hookObject->makeControl($table, $row, $cells, $this);
            }
            // now sort icons again into primary and secondary sections
            // after all hooks are processed
            $hookCells = $cells;
            foreach ($hookCells as $key => $value) {
                if ($key === 'primary' || $key === 'secondary') {
                    continue;
                }
                $this->addActionToCellGroup($cells, $value, $key);
            }
        }
        $output = '<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->';
        foreach ($cells as $classification => $actions) {
            $visibilityClass = ($classification !== 'primary' && !$module->MOD_SETTINGS['bigControlPanel'] ?
                'collapsed' :
                'expanded'
            );
            if ($visibilityClass === 'collapsed') {
                $cellOutput = '';
                foreach ($actions as $action) {
                    $cellOutput .= $action;
                }
                $output .= ' <div class="btn-group">'
                    . '<span id="actions_' . $table . '_' . $row['uid']
                    . '" class="btn-group collapse collapse-horizontal width">' . $cellOutput . '</span>'
                    . '<a href="#actions_' . $table . '_' . $row['uid']
                    . '" class="btn btn-default collapsed" data-toggle="collapse" aria-expanded="false">
                    <span class="t3-icon fa fa-ellipsis-h"></span></a></div>';
            } else {
                $output .= ' <div class="btn-group" role="group">' . implode('', $actions) . '</div>';
            }
        }
        return $output;
    }

    /**
     * Creates the clipboard panel for a single record in the listing.
     *
     * @param string $table The table
     * @param array $row The record for which to make the clipboard panel.
     *
     * @return string HTML table with the clipboard panel (unless disabled)
     *
     * @throws \UnexpectedValueException If hook was of wrong interface
     */
    public function makeClip($table, $row)
    {
        // Return blank, if disabled:
        if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
            return '';
        }
        $cells = [];
        $cells['pasteAfter'] = ($cells['pasteInto'] = $this->spaceIcon);
        //enables to hide the copy, cut and paste icons for localized records - doesn't make much sense to perform
        // these options for them
        $isL10nOverlay = $this->localizationView && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
        // Return blank, if disabled:
        // Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
        // For the "Normal" pad:
        if ($this->clipObj->current === 'normal') {
            // Show copy/cut icons:
            $isSel = (string)$this->clipObj->isSelected($table, $row['uid']);
            if ($isL10nOverlay || !$this->overlayEditLockPermissions($table, $row)) {
                $cells['copy'] = $this->spaceIcon;
                $cells['cut'] = $this->spaceIcon;
            } else {
                $copyIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();
                $cutIcon = $this->iconFactory->getIcon('actions-edit-cut', Icon::SIZE_SMALL)->render();

                if ($isSel === 'copy') {
                    $copyIcon = $this->iconFactory->getIcon('actions-edit-copy-release', Icon::SIZE_SMALL)->render();
                } elseif ($isSel === 'cut') {
                    $cutIcon = $this->iconFactory->getIcon('actions-edit-cut-release', Icon::SIZE_SMALL)->render();
                }

                $cells['copy'] = '<a class="btn btn-default" href="#" onclick="'
                    . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue(
                        $this->clipObj->selUrlDB($table, $row['uid'], 1, ($isSel === 'copy'), ['returnUrl' => ''])
                    ) . ');')
                    . '" title="' . htmlspecialchars($this->getLanguageService()->sL(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.copy'
                    ))
                    . '">'
                    . $copyIcon . '</a>';
                if (true) {
                    $cells['cut'] = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue(
                            $this->clipObj->selUrlDB(
                                $table,
                                $row['uid'],
                                0,
                                ($isSel === 'cut'),
                                ['returnUrl' => '']
                            )
                        ) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->sL(
                            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.cut'
                        ))
                        . '">'
                        . $cutIcon . '</a>';
                } else {
                    $cells['cut'] = $this->spaceIcon;
                }
            }
        } else {
            // For the numeric clipboard pads (showing checkboxes where one can select elements on/off)
            // Setting name of the element in ->CBnames array:
            $n = $table . '|' . $row['uid'];
            $this->CBnames[] = $n;
            // Check if the current element is selected and if so, prepare to set the checkbox as selected:
            $checked = $this->clipObj->isSelected($table, $row['uid']) ? 'checked="checked" ' : '';
            // If the "duplicateField" value is set then select all elements which are duplicates...
            if ($this->duplicateField && isset($row[$this->duplicateField])) {
                $checked = '';
                if (in_array($row[$this->duplicateField], $this->duplicateStack)) {
                    $checked = 'checked="checked" ';
                }
                $this->duplicateStack[] = $row[$this->duplicateField];
            }
            // Adding the checkbox to the panel:
            $cells['select'] = $isL10nOverlay ?
                $this->spaceIcon :
                '<input type="hidden" name="CBH[' . $n
                . ']" value="0" /><label class="btn btn-default btn-checkbox"><input type="checkbox"'
                . ' name="CBC[' . $n . ']" value="1" ' . $checked . '/><span class="t3-icon fa"></span></label>';
        }

        // Now, looking for selected elements from the current table:
        $elFromTable = $this->clipObj->elFromTable($table);
        if (!empty($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
            // IF elements are found, they can be individually ordered and are not locked by editlock,
            // then add a "paste after" icon:
            $cells['pasteAfter'] = $isL10nOverlay || !$this->overlayEditLockPermissions($table, $row) ?
                $this->spaceIcon :
                '<a class="btn btn-default t3js-modal-trigger"' .
                ' href="' . htmlspecialchars($this->clipObj->pasteUrl($table, -$row['uid'])) . '"' .
                ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"' .
                ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"' .
                ' data-content="' . htmlspecialchars(
                    $this->clipObj->confirmMsgText($table, $row, 'after', $elFromTable)
                ) . '"' .
                ' data-severity="warning">' .
                $this->iconFactory->getIcon('actions-document-paste-after', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // Now, looking for elements in general:
        $elFromTable = $this->clipObj->elFromTable('');
        if ($table == 'tx_commerce_categories' && !empty($elFromTable)) {
            $cells['pasteInto'] = '<a class="btn btn-default t3js-modal-trigger"' .
                ' href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '"' .
                ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"' .
                ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"' .
                ' data-content="' . htmlspecialchars(
                    $this->clipObj->confirmMsgText($table, $row, 'into', $elFromTable)
                ) . '"' .
                ' data-severity="warning">' .
                $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() . '</a>';
        }

        /**
         * @hook makeClip: Allows to change clip-icons of records in list-module
         * @usage This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the clipboard icons generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            $scOptions = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'];
            foreach ($scOptions['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException(
                        '$hookObject must implement interface ' . RecordListHookInterface::class,
                        1195567845
                    );
                }
                $cells = $hookObject->makeClip($table, $row, $cells, $this);
            }
        }
        // Compile items into a DIV-element:
        return '<!-- CLIPBOARD PANEL: ' . $table . ':' . $row['uid'] . ' -->
			<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
    }

    /**
     * Creates the localization panel.
     *
     * @param string $table The table
     * @param array $row The record for which to make the localization panel.
     *
     * @return array Array with key 0/1 with content for column 1 and 2
     */
    public function makeLocalizationPanel($table, $row)
    {
        // @todo check if still needed
        $tableControl = ConfigurationUtility::getInstance()->getTcaValue($table . '.ctrl');
        $out = [
            0 => '',
            1 => ''
        ];
        // Reset translations
        $this->translations = [];

        // Language title and icon:
        $out[0] = $this->languageFlag($row[$tableControl['languageField']]);
        // Guard clause so we can quickly return if a record is localized to "all languages"
        // It should only be possible to localize a record off default (uid 0)
        // Reasoning: The Parent is for ALL languages... why overlay with a localization?
        if ((int)$row[$tableControl['languageField']] === -1) {
            return $out;
        }

        $translations = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $this->selFieldList);
        if (is_array($translations)) {
            $this->translations = $translations['translations'];
            // Traverse page translations and add icon for each language that does NOT yet exist:
            $lNew = '';
            foreach ($this->pageOverlays as $lUid_OnPage => $lsysRec) {
                if ($this->isEditable($table)
                    && !isset($translations['translations'][$lUid_OnPage])
                    && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)
                ) {
                    $url = $this->listURL();
                    $href = BackendUtility::getLinkToDataHandlerAction(
                        '&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $lUid_OnPage,
                        $url . '&justLocalized=' . rawurlencode($table . ':' . $row['uid'] . ':' . $lUid_OnPage)
                    );
                    $language = BackendUtility::getRecord('sys_language', $lUid_OnPage, 'title');
                    if ($this->languageIconTitles[$lUid_OnPage]['flagIcon']) {
                        $lC = $this->iconFactory->getIcon(
                            $this->languageIconTitles[$lUid_OnPage]['flagIcon'],
                            Icon::SIZE_SMALL
                        );
                    } else {
                        $lC = $this->languageIconTitles[$lUid_OnPage]['title'];
                    }
                    $lC = '<a href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($language['title']) . '" class="btn btn-default">' . $lC . '</a> ';
                    $lNew .= $lC;
                }
            }
            if ($lNew) {
                $out[1] .= $lNew;
            }
        } elseif ($row['l18n_parent']) {
            $out[0] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $out[0];
        }
        return $out;
    }

    /**
     * As we can't use BackendUtility::getModuleUrl this method needs
     * to be overridden to set the url to $this->script.
     *
     * NOTE: Since Typo3 4.5 we can't use listURL from parent class
     * we need to link to $this->script instead of web_list
     *
     * Creates the URL to this script, including all relevant GPvars
     * Fixed GPvars are id, table, imagemode, returlUrl, search_field,
     * search_levels and showLimit The GPvars "sortField" and "sortRev"
     * are also included UNLESS they are found in the $exclList variable.
     *
     * @param string $altId Alternative id value.
     *      Enter blank string for the current id ($this->id)
     * @param string $table Tablename to display. Enter "-1" for the current table.
     * @param string $exclList Commalist of fields
     *      NOT to include ("sortField" or "sortRev")
     *
     * @return string URL
     */
    public function listURL($altId = '', $table = '-1', $exclList = '')
    {
        $urlParameters = [];
        if (strcmp($altId, '')) {
            $urlParameters['id'] = $altId;
        } else {
            $urlParameters['id'] = $this->id;
        }
        if ($this->categoryUid) {
            $urlParameters['defVals']['tx_commerce_categories']['uid'] = $this->categoryUid;
        }
        if ($table === '-1') {
            $urlParameters['table'] = $this->table;
        } else {
            $urlParameters['table'] = $table;
        }
        if ($this->thumbs) {
            $urlParameters['imagemode'] = $this->thumbs;
        }
        if ($this->returnUrl) {
            $urlParameters['returnUrl'] = $this->returnUrl;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'search_field')) && $this->searchString) {
            $urlParameters['search_field'] = $this->searchString;
        }
        if ($this->searchLevels) {
            $urlParameters['search_levels'] = $this->searchLevels;
        }
        if ($this->showLimit) {
            $urlParameters['showLimit'] = $this->showLimit;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'firstElementNumber')) && $this->firstElementNumber) {
            $urlParameters['pointer'] = $this->firstElementNumber;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'sortField')) && $this->sortField) {
            $urlParameters['sortField'] = $this->sortField;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'sortRev')) && $this->sortRev) {
            $urlParameters['sortRev'] = $this->sortRev;
        }

        $urlParameters = array_merge_recursive($urlParameters, $this->overrideUrlParameters);

        if ($routePath = GeneralUtility::_GP('route')) {
            /** @var Router $router */
            $router = GeneralUtility::makeInstance(Router::class);
            $route = $router->match($routePath);
            /** @var UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute($route->getOption('_identifier'), $urlParameters);
        } elseif ($moduleName = GeneralUtility::_GP('M')) {
            $url = BackendUtility::getModuleUrl($moduleName, $urlParameters);
        } else {
            $url = GeneralUtility::getIndpEnv('SCRIPT_NAME') . '?'
                . ltrim(GeneralUtility::implodeArrayForUrl('', $urlParameters), '&');
        }
        return $url;
    }

    /**
     * Returns the title (based on $code) of a record (from table $table) with
     * the proper link around (that is for 'tx_commerce_categories'-records
     * a link to the level of that record...).
     *
     * @param string $table Table name
     * @param int $uid Item uid
     * @param string $code Item title (not htmlspecialchars()'ed yet)
     * @param array $row Item row
     *
     * @return string The item title. Ready for HTML output
     */
    public function linkWrapItems($table, $uid, $code, $row)
    {
        $lang = $this->getLanguageService();
        /**
         * Utility.
         *
         * @var \CommerceTeam\Commerce\Utility\BackendUserUtility $backendUserUtility
         */
        $backendUserUtility = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUserUtility::class);
        $origCode = $code;
        // If the title is blank, make a "no title" label:
        if ((string)$code === '') {
            $code = '<i>[' . htmlspecialchars($lang->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.no_title'
            )) . ']</i> - '
            . htmlspecialchars(
                GeneralUtility::fixed_lgd_cs(
                    BackendUtility::getRecordTitle($table, $row),
                    (int)$this->getBackendUserAuthentication()->uc['titleLen']
                )
            );
        } else {
            $code = htmlspecialchars(GeneralUtility::fixed_lgd_cs($code, $this->fixedL), ENT_QUOTES, 'UTF-8', false);
            if ($code != htmlspecialchars($origCode)) {
                $code = '<span title="' . htmlspecialchars($origCode, ENT_QUOTES, 'UTF-8', false) . '">' . $code
                    . '</span>';
            }
        }

        switch ((string) $this->clickTitleMode) {
            case 'edit':
                // If the listed table is 'tx_commerce_categories' we have to request the permission settings
                // for each category:
                if ($table == 'tx_commerce_categories') {
                    $localCalcPerms = $backendUserUtility->calcPerms(
                        BackendUtility::getRecord('tx_commerce_categories', $row['uid'])
                    );
                    $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
                } else {
                    $permsEdit = $this->calcPerms & Permission::CONTENT_EDIT;
                }
                // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page
                // ($this->id)
                if ($permsEdit) {
                    $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
                    $code = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                        . '" title="' . htmlspecialchars($lang->getLL('edit')) . '">' . $code . '</a>';
                }
                break;

            case 'show':
                // "Show" link (only tx_commerce_categories and tx_commerce_products elements)
                if ($table == 'tx_commerce_categories' || $table == 'tx_commerce_products') {
                    $code = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick(
                        ($table == 'tt_content' ? $this->id . '#' . $row['uid'] : $row['uid'])
                    )) . '" title="' . htmlspecialchars($lang->sL(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage'
                    )) . '">'
                    . $code . '</a>';
                }
                break;

            case 'info':
                // "Info": (All records)
                $code = '<a href="#" onclick="' . htmlspecialchars(('top.launchView(\'' . $table . '\', \''
                    . $row['uid'] . '\'); return false;')) . '" title="' . htmlspecialchars($lang->getLL('showInfo')) . '">'
                    . $code . '</a>';
                break;

            default:
                // Output the label now:
                if ($table == 'tx_commerce_categories') {
                    $code = '<a href="' . htmlspecialchars($this->listURL($uid, '', 'firstElementNumber'))
                        . '" onclick="setHighlight(' . $uid . ')">' . $code . '</a>';
                } else {
                    $code = $this->linkUrlMail($code, $origCode);
                }
        }

        return $code;
    }


    /**
     * Check if the current record is locked by editlock. Categories are locked if their editlock flag is set,
     * records are if they are locked themselves or if the page they are on is locked (a page’s editlock
     * is transitive for its content elements).
     *
     * @param string $table
     * @param array $row
     * @param bool $editPermission
     * @return bool
     */
    protected function overlayEditLockPermissions($table, $row = [], $editPermission = true)
    {
        $tableControl = ConfigurationUtility::getInstance()->getTcaValue($table . '.ctrl');
        if ($editPermission && !$this->getBackendUserAuthentication()->isAdmin()) {
            // If no $row is submitted we only check for general edit lock of current category
            // (except for table "tx_commerce_categories")
            if (empty($row)) {
                return $table === 'tx_commerce_categories' ? true : !$this->categoryRow['editlock'];
            }
            if (($table === 'tx_commerce_categories' && $row['editlock'])
                || ($table !== 'tx_commerce_categories' && $this->categoryRow['editlock'])
            ) {
                $editPermission = false;
            } elseif (isset($tableControl['editlock']) && $row[$tableControl['editlock']]) {
                $editPermission = false;
            }
        }
        return $editPermission;
    }


    /**
     * Get controller.
     *
     * @return \CommerceTeam\Commerce\Controller\CategoryModuleController
     */
    protected function getController()
    {
        return $GLOBALS['SOBE'];
    }

    /**
     * Get controller document template.
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getControllerDocumentTemplate()
    {
        // $GLOBALS['SOBE'] might be any kind of PHP class (controller most
        // of the times) These class do not inherit from any common class,
        // but they all seem to have a "doc" member
        return $this->getController()->doc;
    }
}

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

use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface;

/**
 * Render order list in the BE order module.
 */
class OrderRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
{
    /**
     * Order pid.
     *
     * @var int
     */
    public $orderPid;

    /**
     * Additional out top.
     *
     * @var string
     */
    public $additionalOutTop;

    /**
     * Default fields.
     *
     * @var array
     */
    protected $defaultFieldArray = [
        'order_type_uid_noName',
        'tstamp',
        'numarticles',
        'sum_price_gross',
        'cu_iso_3',
        'company',
        'name',
        'email',
        'phone_1',
    ];

    /**
     * Additional fields.
     *
     * @var array
     */
    protected $additionalFieldArray = [
        'crdate',
        'article_number',
        'article_name',
        'delivery',
        'payment',
        'address',
        'zip',
        'city',
        'email',
        'phone_2',
        'uid',
        'pid',
    ];

    /**
     * Csv fields.
     *
     * @var array
     */
    protected $csvFieldArray = [
        'order_id',
        'crdate',
        'tstamp',
        'delivery',
        'payment',
        'numarticles',
        'sum_price_gross',
        'cu_iso_3',
        'company',
        'surname',
        'name',
        'address',
        'zip',
        'city',
        'email',
        'phone_1',
        'phone_2',
        'comment',
        'internalcomment',
        'articles',
    ];

    /**
     * Disable single table view.
     *
     * @var bool
     */
    public $disableSingleTableView;

    /**
     * @var object|\TYPO3\CMS\Core\Imaging\IconRegistry
     */
    protected $iconRegistry;

    /**
     * Query part for either a list of ids "pid IN (1,2,3)" or a single id "pid = 123" from
     * which to select/search etc. (when search-levels are set high). See start()
     *
     * @var string
     */
    public $pidSelect = '';

    /**
     * OrderRecordList constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->iconRegistry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
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
            $pidList = implode(',', $this->getDatabaseConnection()->cleanIntArray($allowedMounts));
            $this->pidSelect = 'pid IN (' . $pidList . ')';
        } elseif ($this->searchLevels < 0) {
            // Search everywhere
            $this->pidSelect = '1=1';
        } else {
            $this->pidSelect = 'pid=' . (int)$id;
        }
    }

    /**
     * @param \TYPO3\CMS\Backend\Template\ModuleTemplate $moduleTemplate
     */
    public function getDocHeaderButtons(\TYPO3\CMS\Backend\Template\ModuleTemplate $moduleTemplate)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $module = $this->getModule();
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
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
            // Cache
            $clearCacheButton = $buttonBar->makeLinkButton()
                ->setHref($this->listURL() . '&clear_cache=1')
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
            $buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
            if ($this->table && (!isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                || (isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                    && !$module->modTSconfig['properties']['noExportRecordsLinks']))
            ) {
                // CSV
                $csvButton = $buttonBar->makeLinkButton()
                    ->setHref($this->listURL() . '&csv=1')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.csv'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-export-csv', Icon::SIZE_SMALL));
                $buttonBar->addButton($csvButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                // Export
                if (ExtensionManagementUtility::isLoaded('impexp')) {
                    $url = BackendUtility::getModuleUrl('xMOD_tximpexp', array('tx_impexp[action]' => 'export'));
                    $exportButton = $buttonBar->makeLinkButton()
                        ->setHref($url . '&tx_impexp[list][]=' . rawurlencode($this->table . ':' . $this->id))
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.export'))
                        ->setIcon($this->iconFactory->getIcon('actions-document-export-t3d', Icon::SIZE_SMALL));
                    $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                }
            }

            // Reload
            $reloadButton = $buttonBar->makeLinkButton()
                ->setHref($this->listURL())
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload'))
                ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
            // Shortcut
            if ($backendUser->mayMakeShortcut()) {
                $shortCutButton = $buttonBar->makeShortcutButton()
                    ->setModuleName('web_list')
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
                        'sortRev'
                    ])
                    ->setSetVariables(array_keys($this->MOD_MENU));
                $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
            }
        }
    }

    /**
     * Get table.
     *
     * @param string $table Table
     * @param int $id Uid
     * @param string $rowList Row list

     * @return string
     * @throws \UnexpectedValueException If hook is of wrong interface
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
        $db = $this->getDatabaseConnection();
        // Init
        $addWhere = '';
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
        $l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField']
            && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
            && !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];
        $tableCollapsed = (bool)$this->tablesCollapsed[$table];
        // prepare space icon
        $this->spaceIcon = '<span class="btn btn-default disabled">' .
            $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        // Cleaning rowlist for duplicates and place the $titleCol as the first column always!
        $this->fieldArray = array();
        // title Column
        // Add title column
        $this->fieldArray[] = $titleCol;
        // Control-Panel
        if (!GeneralUtility::inList($rowList, '_CONTROL_')) {
            $this->fieldArray[] = '_CONTROL_';
            $this->fieldArray[] = '_AFTERCONTROL_';
        }
        // Clipboard
        if ($this->showClipboard) {
            $this->fieldArray[] = '_CLIPBOARD_';
        }
        // Ref
        if (!$this->dontShowClipControlPanels) {
            $this->fieldArray[] = '_REF_';
            $this->fieldArray[] = '_AFTERREF_';
        }
        // Path
        if ($this->searchLevels) {
            $this->fieldArray[] = '_PATH_';
        }
        // Localization
        if ($this->localizationView && $l10nEnabled) {
            $this->fieldArray[] = '_LOCALIZATION_';
            $this->fieldArray[] = '_LOCALIZATION_b';
            $addWhere .= ' AND (
				' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '<=0
				OR
				' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ' = 0
			)';
        }

        if (ConfigurationUtility::getInstance()->getExtConf('showArticleNumber') == 1) {
            $this->defaultFieldArray[] = 'article_number';
        }
        if (ConfigurationUtility::getInstance()->getExtConf('showArticleTitle') == 1) {
            $this->defaultFieldArray[] = 'article_name';
        }
        $this->fieldArray = array_merge($this->fieldArray, $this->defaultFieldArray);

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
        if ($table == 'pages') {
            $selectFields[] = 'module';
            $selectFields[] = 'extendToSubpages';
            $selectFields[] = 'nav_hide';
            $selectFields[] = 'doktype';
            $selectFields[] = 'shortcut';
            $selectFields[] = 'shortcut_mode';
            $selectFields[] = 'mount_pid';
        }
        if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
            $selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
        }
        foreach (array('type', 'typeicon_column', 'editlock') as $field) {
            if ($GLOBALS['TCA'][$table]['ctrl'][$field]) {
                $selectFields[] = $GLOBALS['TCA'][$table]['ctrl'][$field];
            }
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
            $selectFields[] = 't3ver_id';
            $selectFields[] = 't3ver_state';
            $selectFields[] = 't3ver_wsid';
        }
        if ($l10nEnabled) {
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
            $selectFields = array_merge(
                $selectFields,
                GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], true)
            );
        }
        // Unique list!
        $selectFields = array_unique($selectFields);
        $fieldListFields = $this->makeFieldList($table, 1);
        if (empty($fieldListFields) && $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {
            $message = sprintf(
                $lang->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:missingTcaColumnsMessage', true),
                $table,
                $table
            );
            $messageTitle = $lang->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:missingTcaColumnsMessageTitle', true);
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
        // Implode it into a list of fields for the SQL-statement.
        $selFieldList = implode(',', $selectFields);
        $this->selFieldList = $selFieldList;
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
            $getTable = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'];
            foreach ($getTable as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof RecordListGetTableHookInterface) {
                    throw new \UnexpectedValueException(
                        $classData . ' must implement interface ' . RecordListGetTableHookInterface::class,
                        1195114460
                    );
                }
                $hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
            }
        }
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
            $queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
            $this->firstElementNumber = $this->firstElementNumber + 2;
            $this->iLimit = $this->iLimit - 2;
        } else {
            // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
            $queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
        }

        // Finding the total amount of records on the page
        // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
        $this->setTotalItems($table, $id, $queryParts);

        // Init:
        $dbCount = 0;
        $out = '';
        $tableHeader = '';
        $result = null;
        $listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
        // If the count query returned any number of records, we perform the real query,
        // selecting records.
        if ($this->totalItems) {
            // Fetch records only if not in single table mode
            if ($listOnlyInSingleTableMode) {
                $dbCount = $this->totalItems;
            } else {
                // Set the showLimit to the number of records when outputting as CSV
                if ($this->csvOutput) {
                    $this->showLimit = $this->totalItems;
                    $this->iLimit = $this->totalItems;
                }
                $result = $db->exec_SELECT_queryArray($queryParts);
                $dbCount = $db->sql_num_rows($result);
            }
        }
        // If any records was selected, render the list:
        if ($dbCount) {
            $tableTitle = $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'], true);
            if ($tableTitle === '') {
                $tableTitle = $table;
            }
            // Header line is drawn
            $theData = array();
            if ($this->disableSingleTableView) {
                $theData[$titleCol] = '<span class="c-table">' . BackendUtility::wrapInHelp($table, '', $tableTitle)
                    . '</span> (<span class="t3js-table-total-items">' . $this->totalItems . '</span>)';
            } else {
                $icon = $this->table
                    ? '<span title="' . $lang->getLL('contractView', true) . '">' .
                    $this->iconFactory->getIcon('actions-view-table-collapse', Icon::SIZE_SMALL)->render() . '</span>'
                    : '<span title="' . $lang->getLL('expandView', true) . '">' .
                    $this->iconFactory->getIcon('actions-view-table-expand', Icon::SIZE_SMALL)->render() . '</span>';
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
                        $this->listURL() . '&collapse[' . $table . ']=' .
                        ($tableCollapsed ? '0' : '1')
                    ));
                    $title = $tableCollapsed
                        ? $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.expandTable', true)
                        : $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.collapseTable', true);
                    $icon = '<span class="collapseIcon">' . $this->iconFactory->getIcon(
                        ($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'),
                        Icon::SIZE_SMALL
                    )->render() . '</span>';
                    $collapseIcon = '<a href="' . $href . '" title="' . $title .
                        '" class="pull-right t3js-toggle-recordlist" data-table="' . htmlspecialchars($table) .
                        '" data-toggle="collapse" data-target="#recordlist-' . htmlspecialchars($table) .
                        '">' . $icon . '</a>';
                }
                $tableHeader .= $theData[$titleCol] . $collapseIcon;
            }
            // Render table rows only if in multi table view or if in single table view
            $rowOutput = '';
            if (!$listOnlyInSingleTableMode || $this->table) {
                // Fixing an order table for sortby tables
                $this->currentTable = array();
                $currentIdList = array();
                $doSort = $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField;
                $prevUid = 0;
                $prevPrevUid = 0;
                // Get first two rows and initialize prevPrevUid and prevUid if on page > 1
                if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
                    $row = $db->sql_fetch_assoc($result);
                    $prevPrevUid = -((int)$row['uid']);
                    $row = $db->sql_fetch_assoc($result);
                    $prevUid = $row['uid'];
                }
                $accRows = array();
                // Accumulate rows here
                while ($row = $db->sql_fetch_assoc($result)) {
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
                $db->sql_free_result($result);
                $this->totalRowCount = count($accRows);
                // CSV initiated
                if ($this->csvOutput) {
                    $this->initCSV();
                }
                // Render items:
                $this->CBnames = array();
                $this->duplicateStack = array();
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
                                        $where = 't3ver_move_id="' . (int)$lRow['uid'] . '" AND pid="' .
                                            $row['_MOVE_PLH_pid'] . '" AND t3ver_wsid=' . $row['t3ver_wsid'] .
                                            BackendUtility::deleteClause($table);
                                        $tmpRow = BackendUtility::getRecordRaw($table, $where, $selFieldList);
                                        $lRow = is_array($tmpRow) ? $tmpRow : $lRow;
                                    }
                                    // In offline workspace, look for alternative record:
                                    BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                                    if (is_array($lRow) && $backendUser->checkLanguageAccess(
                                        $lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']]
                                    )) {
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

                // Record navigation is added to the beginning and
                // end of the table if in single table mode
                if ($this->table) {
                    $rowOutput = $this->renderListNavigation('top') . $rowOutput .
                        $this->renderListNavigation('bottom');
                } else {
                    // Show that there are more records than shown
                    if ($this->totalItems > $this->itemsLimitPerTable) {
                        $countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ?
                            $this->itemsLimitSingleTable :
                            $this->totalItems;
                        $hasMore = $this->totalItems > $this->itemsLimitSingleTable;
                        $colspan = $this->showIcon ? count($this->fieldArray) + 1 : count($this->fieldArray);
                        $rowOutput .= '<tr><td colspan="' . $colspan . '">
								<a href="' . htmlspecialchars(($this->listURL() . '&table=' . rawurlencode($table))) .
                            '" class="btn btn-default">'
                            . '<span class="t3-icon fa fa-chevron-down"></span> <i>[1 - ' . $countOnFirstPage .
                            ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
                    }
                }
                // The header row for the table is now created:
                $out .= $this->renderListHeader($table, $currentIdList);
            }

            $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse in';
            $dataState = $tableCollapsed && !$this->table ? 'collapsed' : 'expanded';

            // Build the selector
            $moveToSelector = $this->renderMoveToSelector($table);

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
					<div class="' . $collapseClass . '" data-state="' . $dataState . '" id="recordlist-' .
                htmlspecialchars($table) . '">
						<div class="table-fit">
							<table data-table="' . htmlspecialchars($table) .
                '" class="table table-striped table-hover' .
                ($listOnlyInSingleTableMode ? ' typo3-dblist-overview' : '') . '">
								' . $out . $moveToSelector . '
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
     * Creates the search box
     *
     * @param bool $formFields If TRUE, the search box is wrapped in its own form-tags
     *
     * @return string HTML for the search box
     */
    public function getSearchBox($formFields = true)
    {
        /** @var $iconFactory IconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $lang = $this->getLanguageService();
        // Setting form-elements, if applicable:
        $formElements = ['', ''];
        if ($formFields) {
            $formElements = [
                '<form action="' . htmlspecialchars($this->listURL('', '-1', 'firstElementNumber,search_field')) .
                '" method="post">',
                '</form>'
            ];
        }
        // Make level selector:
        $opt = [];
        $parts = explode('|', $lang->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xlf:labels.enterSearchLevels'
        ));
        foreach ($parts as $kv => $label) {
            $opt[] = '<option value="' . $kv . '" ' . ($kv === $this->searchLevels ? 'selected="selected"' : '') .
                '>' . htmlspecialchars($label) . '</option>';
        }
        $lMenu = '<select class="form-control" name="search_levels" title="' .
            $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.search_levels', true) . '" id="search_levels">' .
            implode('', $opt) . '</select>';
        // Table with the search box:
        $content = '<div class="db_list-searchbox-form db_list-searchbox-toolbar module-docheader-bar
            module-docheader-bar-search t3js-module-docheader-bar t3js-module-docheader-bar-search"
            id="db_list-searchbox-toolbar" style="display: ' .
            ($this->searchString == '' ? 'none' : 'block') . ';">
			' . $formElements[0] . '
                <div id="typo3-dblist-search">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="form-inline form-inline-spaced">
                                <div class="form-group">
									<input class="form-control" type="search" placeholder="' .
            $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.enterSearchString', true) .
            '" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.searchString', true) .
            '" name="search_field" id="search_field" value="' . htmlspecialchars($this->searchString) . '" />
                                </div>
                                <div class="form-group">
									<label for="search_levels">' .
            $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.label.search_levels', true) .
            ': </label>
									' . $lMenu . '
                                </div>
                                <div class="form-group">
									<label for="showLimit">' .
            $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.label.limit', true) .
            ': </label>
							<input class="form-control" type="number" min="0" max="10000" placeholder="10" title="' .
            $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.limit', true) .
            '" name="showLimit" id="showLimit" value="' .
            htmlspecialchars(($this->showLimit ? $this->showLimit : '')) . '" />
                                </div>
                                <div class="form-group">
									<button type="submit" class="btn btn-default" name="search" title="' .
            $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.search', true) .
            '">
										' . $iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render() . ' ' .
            $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.search', true) . '
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			' . $formElements[1] . '</div>';
        return $content;
    }

    /**
     * Rendering a single row for the list
     *
     * @param string $table Table name
     * @param mixed[] $row Current record
     * @param int $cc Counter, counting for each time an element is rendered (used for alternating colors)
     * @param string $titleCol Table field (column) where header value is found
     * @param string $thumbsCol Table field (column) where (possible) thumbnails can be found
     * @param int $indent Indent from left.
     *
     * @return string Table row for the element
     */
    public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0)
    {
        if (!is_array($row)) {
            return '';
        }

        // Icon for order comment and delivery address
        $iconPath = '';
        if ($row['comment'] != '' && $row['internalcomment'] != '') {
            if ($row['tx_commerce_address_type_id'] == 2) {
                $iconPath = 'orders-add-user-int';
            } else {
                $iconPath = 'orders-user-int';
            }
        } elseif ($row['comment'] != '') {
            if ($row['tx_commerce_address_type_id'] == 2) {
                $iconPath = 'orders-add-user';
            } else {
                $iconPath = 'orders-user';
            }
        } elseif ($row['internalcomment'] != '') {
            if ($row['tx_commerce_address_type_id'] == 2) {
                $iconPath = 'orders-add-int';
            } else {
                $iconPath = 'orders-int';
            }
        } else {
            if ($row['tx_commerce_address_type_id'] == 2) {
                $iconPath = 'orders-add';
            }
        }
        if ($iconPath) {
            $iconImg = $this->iconFactory->getIcon($iconPath, Icon::SIZE_SMALL)->render();
        } else {
            $iconImg = $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render();
        }

        $rowOutput = '';
        $id_orig = null;
        // If in search mode, make sure the preview will show the correct page
        if ((string)$this->searchString !== '') {
            $id_orig = $this->id;
            $this->id = $row['pid'];
        }
        // Add special classes for first and last row
        $rowSpecial = '';
        if ($cc == 1 && $indent == 0) {
            $rowSpecial .= ' firstcol';
        }
        if ($cc == $this->totalRowCount || $cc == $this->iLimit) {
            $rowSpecial .= ' lastcol';
        }

        $row_bgColor = ' class="' . $rowSpecial . '"';

        // Overriding with versions background color if any:
        $row_bgColor = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : $row_bgColor;
        // Incr. counter.
        $this->counter++;
        // The icon with link
        $toolTip = BackendUtility::getRecordToolTip($row, $table);
        $additionalStyle = $indent ? ' style="margin-left: ' . $indent . 'px;"' : '';
        $iconImg = '<span ' . $toolTip . ' ' . $additionalStyle . '>' . $iconImg . '</span>';
        $theIcon = $this->clickMenuEnabled ?
            BackendUtility::wrapClickMenuOnIcon($iconImg, $table, $row['uid']) :
            $iconImg;
        // Preparing and getting the data-array
        $theData = array();
        $localizationMarkerClass = '';
        foreach ($this->fieldArray as $fCol) {
            if ($fCol == 'sum_price_gross') {
                if ($this->csvOutput) {
                    $row[$fCol] = $row[$fCol] / 100;
                } else {
                    $theData[$fCol] = \CommerceTeam\Commerce\ViewHelpers\MoneyViewHelper::format(
                        $row[$fCol],
                        $row['cu_iso_3'],
                        false
                    );
                }
            } elseif ($fCol == 'crdate' || $fCol == 'tstamp') {
                $theData[$fCol] = BackendUtility::date($row[$fCol]);
                $row[$fCol] = BackendUtility::date($row[$fCol]);
            } elseif ($fCol == 'articles') {
                $result = [];
                $articles = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    'article_number, title',
                    'tx_commerce_order_articles',
                    'order_uid = ' . (int) $row['uid']
                );
                foreach ($articles as $article) {
                    $articles[] = $article['article_number'] . ':' . $article['title'];
                }

                if ($this->csvOutput) {
                    $theData[$fCol] = implode(',', $result);
                    $row[$fCol] = implode(',', $result);
                } else {
                    $theData[$fCol] = '<input type="checkbox" name="orderUid[]" value="' . $row['uid'] . '">';
                }
            } elseif ($fCol == 'numarticles') {
                $amount = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'sum(amount) AS amount',
                    'tx_commerce_order_articles',
                    'order_uid = ' . (int) $row['uid'] . ' AND article_type_uid = ' . NORMALARTICLETYPE
                );
                if (!empty($amount)) {
                    $theData[$fCol] = $amount['amount'];
                    $row[$fCol] = $amount['amount'];
                }
            } elseif ($fCol == 'article_number') {
                $articleNumber = [];
                $articles = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    $fCol,
                    'tx_commerce_order_articles',
                    'order_uid = ' . (int) $row['uid'] . ' AND article_type_uid = ' . NORMALARTICLETYPE
                );
                foreach ($articles as $article) {
                    $articleNumber[] = $article[$fCol] ?
                        $article[$fCol] :
                        $this->getLanguageService()->sL('no_article_number');
                }
                $theData[$fCol] = implode(',', $articleNumber);
            } elseif ($fCol == 'article_name') {
                $articleName = [];
                $articles = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    'title',
                    'tx_commerce_order_articles',
                    'order_uid = ' . (int) $row['uid'] . ' AND article_type_uid = ' . NORMALARTICLETYPE
                );
                foreach ($articles as $article) {
                    $articleName[] = $article['title'] ?
                        $article['title'] :
                        $this->getLanguageService()->sL('no_article_title');
                }
                $theData[$fCol] = implode(',', $articleName);
            } elseif ($fCol == 'order_type_uid_noName') {
                $type = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'title, icon',
                    'tx_commerce_order_types',
                    'uid = ' . (int) $row['order_type_uid_noName']
                );
                if (!empty($type)) {
                    if ($type['icon']) {
                        $filepath = ConfigurationUtility::getInstance()->getTcaValue(
                            'tx_commerce_order_types.columns.icon.config.uploadfolder'
                        ) . '/' . $type['icon'];

                        $this->iconRegistry->registerIcon(
                            $type['icon'],
                            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
                            ['source' => PATH_site . $filepath]
                        );

                        $theData[$fCol] = $this->iconFactory->getIcon($type['icon'], Icon::SIZE_SMALL)->render();
                    } else {
                        $theData[$fCol] = $type['title'];
                    }
                }
            } elseif ($fCol == $titleCol) {
                $recTitle = BackendUtility::getRecordTitle($table, $row, false, true);
                $warning = '';
                // If the record is edit-locked	by another user, we will show a little warning sign:
                /** @noinspection PhpInternalEntityUsedInspection */
                $lockInfo = BackendUtility::isRecordLocked($table, $row['uid']);
                if ($lockInfo) {
                    $warning = '<a href="#" onclick="alert('
                        . GeneralUtility::quoteJSvalue($lockInfo['msg']) . '); return false;" title="'
                        . htmlspecialchars($lockInfo['msg']) . '">'
                        . $this->iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</a>';
                }
                $theData[$fCol] = $theData['__label'] = $warning .
                    $this->linkWrapItems($table, $row['uid'], $recTitle, $row);
                // Render thumbnails, if:
                // - a thumbnail column exists
                // - there is content in it
                // - the thumbnail column is visible for the current type
                $type = 0;
                if (isset($GLOBALS['TCA'][$table]['ctrl']['type'])) {
                    $typeColumn = $GLOBALS['TCA'][$table]['ctrl']['type'];
                    $type = $row[$typeColumn];
                }
                // If current type doesn't exist, set it to 0 (or to 1 for historical reasons,
                // if 0 doesn't exist)
                if (!isset($GLOBALS['TCA'][$table]['types'][$type])) {
                    $type = isset($GLOBALS['TCA'][$table]['types'][0]) ? 0 : 1;
                }
                $visibleColumns = $GLOBALS['TCA'][$table]['types'][$type]['showitem'];

                if ($this->thumbs &&
                    trim($row[$thumbsCol]) &&
                    preg_match('/(^|(.*(;|,)?))' . $thumbsCol . '(((;|,).*)|$)/', $visibleColumns) === 1
                ) {
                    $thumbCode = '<br />' . $this->thumbCode($row, $table, $thumbsCol);
                    $theData[$fCol] .= $thumbCode;
                    $theData['__label'] .= $thumbCode;
                }
                if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
                    && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] != 0
                    && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0
                ) {
                    // It's a translated record with a language parent
                    $localizationMarkerClass = ' localization';
                }
            } elseif ($fCol == 'order_id') {
                $theData[$fCol] = $row[$fCol];
            } elseif ($fCol == 'pid') {
                $theData[$fCol] = $row[$fCol];
            } elseif ($fCol == '_PATH_') {
                $theData[$fCol] = $this->recPath($row['pid']);
            } elseif ($fCol == '_REF_') {
                $theData[$fCol] = $this->createReferenceHtml($table, $row['uid']);
            } elseif ($fCol == '_CONTROL_') {
                $theData[$fCol] = $this->makeControl($table, $row);
            } elseif ($fCol == '_CLIPBOARD_') {
                // $theData[$fCol] = $this->makeClip($table, $row);
            } elseif ($fCol == '_LOCALIZATION_') {
                list($lC1, $lC2) = $this->makeLocalizationPanel($table, $row);
                $theData[$fCol] = $lC1;
                $theData[$fCol . 'b'] = '<div class="btn-group">' . $lC2 . '</div>';
            } elseif ($fCol == '_LOCALIZATION_b') {
                // deliberately empty
            } else {
                $tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid']);
                $theData[$fCol] = $this->linkUrlMail(htmlspecialchars($tmpProc), $row[$fCol]);
                if ($this->csvOutput) {
                    $row[$fCol] = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']);
                }
            }
        }
        // Reset the ID if it was overwritten
        if ((string)$this->searchString !== '') {
            $this->id = $id_orig;
        }
        // Add row to CSV list:
        if ($this->csvOutput) {
            $beCsvCharset = ConfigurationUtility::getInstance()->getExtConf('BECSVCharset');
            // Charset Conversion
            /**
             * Charset converter.
             *
             * @var \TYPO3\CMS\Core\Charset\CharsetConverter $csObj
             */
            $csObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
            $csObj->initCharset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);

            if (!$beCsvCharset) {
                $beCsvCharset = 'iso-8859-1';
            }
            $csObj->initCharset($beCsvCharset);
            $csObj->convArray($row, $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'], $beCsvCharset);
            $this->addToCSV($row);
        }
        // Add classes to table cells
        $this->addElement_tdCssClass[$titleCol] = 'col-title' . $localizationMarkerClass;
        $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
        if ($this->getModule()->MOD_SETTINGS['clipBoard']) {
            $this->addElement_tdCssClass['_CLIPBOARD_'] = 'col-clipboard';
        }
        $this->addElement_tdCssClass['_PATH_'] = 'col-path';
        $this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';
        $this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';
        // Create element in table cells:
        $theData['uid'] = $row['uid'];
        if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
            && isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
            && !isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])
        ) {
            $theData['parent'] = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
        }
        $rowOutput .= $this->addElement(1, $theIcon, $theData, $row_bgColor);
        // Finally, return table row element:
        return $rowOutput;
    }

    /**
     * Creates the control panel for a single record in the listing.
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the control panel.
     * @throws \UnexpectedValueException
     * @return string HTML table with the control panel (unless disabled)
     */
    public function makeControl($table, $row)
    {
        $module = $this->getModule();
        $rowUid = $row['uid'];
        if (ExtensionManagementUtility::isLoaded('version') && isset($row['_ORIG_uid'])) {
            $rowUid = $row['_ORIG_uid'];
        }
        $cells = array(
            'primary' => array(),
            'secondary' => array()
        );
        // If the listed table is 'pages' we have to request the permission settings for each page:
        $localCalcPerms = 0;
        if ($table == 'pages') {
            $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(
                BackendUtility::getRecord('pages', $row['uid'])
            );
        }
        $permsEdit = $table === 'pages'
                     && $this->getBackendUserAuthentication()->checkLanguageAccess(0)
                     && $localCalcPerms & Permission::PAGE_EDIT
                     || $table !== 'pages'
                        && $this->calcPerms & Permission::CONTENT_EDIT;
        $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);
        // "Show" link (only pages and tt_content elements)
        if ($table == 'pages' || $table == 'tt_content') {
            $viewAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars(
                    BackendUtility::viewOnClick(
                        ($table === 'tt_content' ? $this->id : $row['uid']),
                        '',
                        '',
                        ($table === 'tt_content' ? '#' . $row['uid'] : '')
                    )
                ) . '" title="' .
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', true) . '">'
                . $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
            $this->addActionToCellGroup($cells, $viewAction, 'view');
        }
        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit) {
            $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
            $iconIdentifier = 'actions-open';
            $overlayIdentifier = !$this->isEditable($table) ? 'overlay-readonly' : null;
            $editAction = '<a class="btn btn-default" href="#" onclick="' .
                htmlspecialchars(BackendUtility::editOnClick($params, '', -1)) .
                '" title="' . $this->getLanguageService()->getLL('edit', true) . '">' .
                $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL, $overlayIdentifier)->render() . '</a>';
        } else {
            $editAction = $this->spaceIcon;
        }
        $this->addActionToCellGroup($cells, $editAction, 'edit');
        // "Info": (All records)
        $onClick = 'top.launchView(' . GeneralUtility::quoteJSvalue($table) . ', ' . (int)$row['uid'] .
            '); return false;';
        $viewBigAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) .
            '" title="' . $this->getLanguageService()->getLL('showInfo', true) . '">' .
            $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
        $this->addActionToCellGroup($cells, $viewBigAction, 'viewBig');
        // "Move" wizard link for pages/tt_content elements:
        if ($permsEdit && ($table === 'tt_content' || $table === 'pages')) {
            $onClick = 'return jumpExt(' .
                GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('move_element') . '&table=' . $table .
                '&uid=' . $row['uid']) . ');';
            $linkTitleLL = $this->getLanguageService()->getLL(
                'move_' . ($table === 'tt_content' ? 'record' : 'page'),
                true
            );
            $icon = (
                $table == 'pages' ?
                $this->iconFactory->getIcon('actions-page-move', Icon::SIZE_SMALL) :
                $this->iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL)
            );
            $moveAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) .
                '" title="' . $linkTitleLL . '">' . $icon->render() . '</a>';
            $this->addActionToCellGroup($cells, $moveAction, 'move');
        }
        // If the table is NOT a read-only table, then show these links:
        if ($this->isEditable($table)) {
            // "Revert" link (history/undo)
            $moduleUrl = BackendUtility::getModuleUrl('record_history', array('element' => $table . ':' . $row['uid']));
            $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($moduleUrl) . ',\'#latest\');';
            $historyAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
                . $this->getLanguageService()->getLL('history', true) . '">'
                . $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render() . '</a>';
            $this->addActionToCellGroup($cells, $historyAction, 'history');
            // Versioning:
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
                    $href = BackendUtility::getModuleUrl('web_txversionM1', array(
                        'table' => $table, 'uid' => $row['uid']
                    ));
                    $versionAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                        . $this->getLanguageService()->getLL('displayVersions', true) . '">'
                        . $this->iconFactory->getIcon('actions-version-page-open', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $versionAction, 'version');
                }
            }
            // "Edit Perms" link:
            if ($table === 'pages'
                && $this->getBackendUserAuthentication()->check('modules', 'system_BeuserTxPermission')
                && ExtensionManagementUtility::isLoaded('beuser')
            ) {
                $href = BackendUtility::getModuleUrl('system_BeuserTxPermission') . '&id=' . $row['uid'] .
                    '&return_id=' . $row['uid'] . '&edit=1';
                $permsAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                    . $this->getLanguageService()->getLL('permissions', true) . '">'
                    . $this->iconFactory->getIcon('status-status-locked', Icon::SIZE_SMALL)->render() . '</a>';
                $this->addActionToCellGroup($cells, $permsAction, 'perms');
            }
            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row
            // or if default values can depend on previous record):
            if ((
                    $GLOBALS['TCA'][$table]['ctrl']['sortby']
                    || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']
                )
                && $permsEdit
            ) {
                if ($table !== 'pages'
                    && $this->calcPerms & Permission::CONTENT_EDIT
                    || $table === 'pages'
                    && $this->calcPerms & Permission::PAGE_NEW
                ) {
                    if ($this->showNewRecLink($table)) {
                        $params = '&edit[' . $table . '][' .
                            -($row['_MOVE_PLH'] ? $row['_MOVE_PLH_uid'] : $row['uid']) . ']=new';
                        $icon = (
                            $table == 'pages' ?
                            $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL) :
                            $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)
                        );
                        $titleLabel = 'new';
                        if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
                            $titleLabel .= ($table === 'pages' ? 'Page' : 'Record');
                        }
                        $newAction = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($titleLabel)) . '">'
                            . $icon->render() . '</a>';
                        $this->addActionToCellGroup($cells, $newAction, 'new');
                    }
                }
            }
            // "Up/Down" links
            if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
                if (isset($this->currentTable['prev'][$row['uid']])) {
                    // Up
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]='
                        . $this->currentTable['prev'][$row['uid']];
                    $moveUpAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars(
                            'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                        )
                        . '" title="' . $this->getLanguageService()->getLL('moveUp', true) . '">'
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
                        . '" title="' . $this->getLanguageService()->getLL('moveDown', true) . '">'
                        . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveDownAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveDownAction, 'moveDown');
            }
            // "Hide/Unhide" links:
            $hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];

            if ($permsEdit && $hiddenField && $GLOBALS['TCA'][$table]['columns'][$hiddenField]
                && (
                    !$GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude']
                    || $this->getBackendUserAuthentication()->check('non_exclude_fields', $table . ':' . $hiddenField)
                )
            ) {
                if ($this->isRecordCurrentBackendUser($table, $row)) {
                    $hideAction = $this->spaceIcon;
                } else {
                    $hideTitle = $this->getLanguageService()->getLL('hide' . ($table == 'pages' ? 'Page' : ''), true);
                    $unhideTitle = $this->getLanguageService()->getLL(
                        'unHide' . ($table == 'pages' ? 'Page' : ''),
                        true
                    );
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
                && (
                    $table === 'pages'
                    && $localCalcPerms & Permission::PAGE_DELETE
                    || $table !== 'pages'
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
                        ' ' . $this->getLanguageService()->sL(
                            'LLL:EXT:lang/locallang_core.xlf:labels.referencesToRecord'
                        ),
                        $this->getReferenceCount($table, $row['uid'])
                    ) . BackendUtility::translationCount(
                        $table,
                        $row['uid'],
                        ' ' . $this->getLanguageService()->sL(
                            'LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord'
                        )
                    );
                }

                if ($this->isRecordCurrentBackendUser($table, $row)) {
                    $deleteAction = $this->spaceIcon;
                } else {
                    $titleOrig = BackendUtility::getRecordTitle($table, $row, false, true);
                    $title = str_replace('\\', '\\\\', GeneralUtility::fixed_lgd_cs($titleOrig, $this->fixedL));
                    $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title .
                        '" ' . '[' . $table . ':' . $row['uid'] . ']' . $refCountMsg;

                    $params = 'cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
                    $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
                    $linkTitle = $this->getLanguageService()->getLL($actionName, true);
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
            // "Levels" links: Moving pages into new levels...
            if ($permsEdit && $table == 'pages' && !$this->searchLevels) {
                // Up (Paste as the page right after the current parent page)
                if ($this->calcPerms & Permission::PAGE_NEW) {
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . -$this->id;
                    $moveLeftAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars(
                            'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                        )
                        . '" title="' . $this->getLanguageService()->getLL('prevLevel', true) . '">'
                        . $this->iconFactory->getIcon('actions-move-left', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $moveLeftAction, 'moveLeft');
                }
                // Down (Paste as subpage to the page right above)
                if ($this->currentTable['prevUid'][$row['uid']]) {
                    $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(
                        BackendUtility::getRecord('pages', $this->currentTable['prevUid'][$row['uid']])
                    );
                    if ($localCalcPerms & Permission::PAGE_NEW) {
                        $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' .
                            $this->currentTable['prevUid'][$row['uid']];
                        $moveRightAction = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars(
                                'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                            )
                            . '" title="' . $this->getLanguageService()->getLL('nextLevel', true) . '">'
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
            $_params = array($table, $row['uid']);
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
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as
                     $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException(
                        $classData . ' must implement interface ' . RecordListHookInterface::class,
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

        if ($this->getModule()->MOD_SETTINGS['clipBoard']) {
            $value = '<label class="btn btn-default btn-checkbox"><input type="checkbox"' .
                ' name="orderUid[]" value="' . $row['uid'] . '"/><span class="t3-icon fa"></span></label>';
            $this->addActionToCellGroup($cells, $value, 'move');
        }

        $output = '<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->';
        foreach ($cells as $classification => $actions) {
            $visibilityClass = (
                $classification !== 'primary' && !$module->MOD_SETTINGS['bigControlPanel'] ?
                'collapsed' :
                'expanded'
            );
            if ($visibilityClass === 'collapsed') {
                $cellOutput = '';
                foreach ($actions as $action) {
                    $cellOutput .= $action;
                }
                $output .= ' <div class="btn-group">' .
                    '<span id="actions_' . $table . '_' . $row['uid'] .
                    '" class="btn-group collapse collapse-horizontal width">' . $cellOutput . '</span>' .
                    '<a href="#actions_' . $table . '_' . $row['uid'] .
                    '" class="btn btn-default collapsed" data-toggle="collapse" aria-expanded="false">
                    <span class="t3-icon fa fa-ellipsis-h"></span></a>' .
                    '</div>';
            } else {
                $output .= ' <div class="btn-group" role="group">' . implode('', $actions) . '</div>';
            }
        }
        return $output;
    }

    /**
     * Returns TRUE if a link for creating new records should be displayed for $table
     *
     * @param string $table Table name
     *
     * @return bool Returns TRUE if a link for creating new records should be displayed for $table
     * @see \TYPO3\CMS\Backend\Controller\NewRecordController::showNewRecLink
     */
    public function showNewRecLink($table)
    {
        // orders should never be created via the order module
        return false;
    }

    /**
     * Create the selector box for selecting fields to display from a table:.
     *
     * @param string $table Table name
     * @param bool|int $formFields If true, form-fields will be wrapped
     *      around the table.
     *
     * @return string HTML table with the selector box
     *      (name: displayFields['.$table.'][])
     */
    public function fieldSelectBox($table, $formFields = 1)
    {
        $lang = $this->getLanguageService();
        // Init:
        $formElements = ['', ''];
        if ($formFields) {
            $formElements = [
                '<form action="' . htmlspecialchars($this->listURL()) . '" method="post" name="fieldSelectBox">',
                '</form>'
            ];
        }
        // Load already selected fields, if any:
        $setFields = is_array($this->setFields[$table]) ? $this->setFields[$table] : array();
        // Request fields from table:
        // $fields = $this->makeFieldList($table, false, true);
        $fields = $this->additionalFieldArray;
        // Add pseudo "control" fields
        $fields[] = '_PATH_';
        $fields[] = '_REF_';
        $fields[] = '_LOCALIZATION_';
        $fields[] = '_CONTROL_';
        $fields[] = '_CLIPBOARD_';
        // Create a checkbox for each field:
        $checkboxes = array();
        $checkAllChecked = true;
        foreach ($fields as $fieldName) {
            // Determine, if checkbox should be checked
            if (in_array($fieldName, $setFields, true) || $fieldName === $this->fieldArray[0]) {
                $checked = ' checked="checked"';
            } else {
                $checkAllChecked = false;
                $checked = '';
            }
            // Field label
            $fieldLabel = is_array($GLOBALS['TCA'][$table]['columns'][$fieldName])
                ? rtrim($lang->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label']), ':')
                : '';
            $checkboxes[] = '<tr><td class="col-checkbox"><input type="checkbox" id="check-' . $fieldName .
                '" name="displayFields[' .
                $table . '][]" value="' . $fieldName . '" ' . $checked .
                ($fieldName === $this->fieldArray[0] ? ' disabled="disabled"' : '') . '></td><td class="col-title">' .
                '<label class="label-block" for="check-' . $fieldName . '">' . htmlspecialchars($fieldLabel) .
                ' <span class="text-muted text-monospace">[' . htmlspecialchars($fieldName) .
                ']</span></label></td></tr>';
        }
        // Table with the field selector::
        $content = $formElements[0] . '
			<input type="hidden" name="displayFields[' . $table . '][]" value="">
			<div class="table-fit table-scrollable">
				<table border="0" cellpadding="0" cellspacing="0" class="table table-transparent table-hover">
					<thead>
						<tr>
							<th class="col-checkbox" colspan="2">
								<input type="checkbox" class="checkbox checkAll" ' .
            ($checkAllChecked ? ' checked="checked"' : '') . '>
							</th>
						</tr>
					</thead>
					<tbody>
					' . implode('', $checkboxes) . '
					</tbody>
				</table>
			</div>
			<input type="submit" name="search" class="btn btn-default" value="'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.setFields', true) . '"/>
			' . $formElements[1];
        return '<div class="fieldSelectBox">' . $content . '</div>';
    }

    /**
     * Make query array.
     *
     * @param string $table Table
     * @param int $id Id
     * @param string $addWhere Additional where
     * @param string $fieldList Field list
     *
     * @return array
     */
    public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*')
    {
        $hookObjectsArr = array();
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'] as
                     $classRef) {
                $hookObjectsArr[] = GeneralUtility::getUserObj($classRef);
            }
        }
        // Set ORDER BY:
        $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?
            'ORDER BY ' . $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] :
            $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
        if ($this->sortField) {
            if (in_array($this->sortField, $this->makeFieldList($table, 1))) {
                $orderBy = 'ORDER BY ' . $this->sortField;
                if ($this->sortRev) {
                    $orderBy .= ' DESC';
                }
            }
        }
        // Set LIMIT:
        $limit = $this->iLimit ?
            ($this->firstElementNumber ? $this->firstElementNumber . ',' : '') . ($this->iLimit + 1) :
            '';
        // Filtering on displayable pages (permissions):
        $pC = $table == 'pages' && $this->perms_clause ? ' AND ' . $this->perms_clause : '';
        // Adding search constraints:
        $search = $this->makeSearchString($table, $id);

        // specialhandling of search for joins
        if ($search) {
            $searchParts = GeneralUtility::trimExplode('OR', $search);
            foreach ($searchParts as &$part) {
                $part = str_replace('LOWER(', 'LOWER(' . $table . '.', $part);
                $part = str_replace('LOWER(' . $table . '.\'', 'LOWER(\'', $part);
            }
            $search = implode(' OR ', $searchParts);
        }

        $fieldList = '
            # order
            tx_commerce_orders.uid, tx_commerce_orders.pid, tx_commerce_orders.order_id,
            tx_commerce_orders.crdate, tx_commerce_orders.tstamp, tx_commerce_orders.sum_price_gross,
            tx_commerce_orders.cu_iso_3_uid, tx_commerce_orders.uid AS articles,
            tx_commerce_orders.comment, tx_commerce_orders.internalcomment,
            tx_commerce_orders.order_type_uid AS order_type_uid_noName, static_currencies.cu_iso_3,
            # payment article
            payment_table.article_type_uid, payment_table.title AS payment,
            # delivery article
            delivery_table.title AS delivery,
            # customer address
            tt_address.tx_commerce_address_type_id, tt_address.company,
            tt_address.name, tt_address.surname, tt_address.address, tt_address.zip, tt_address.city,
            tt_address.email, tt_address.phone AS phone_1, tt_address.mobile AS phone_2
            ';
        $from = 'tx_commerce_orders
            LEFT JOIN tt_address 
                ON tx_commerce_orders.cust_deliveryaddress = tt_address.uid
            LEFT JOIN tx_commerce_order_articles AS payment_table
                ON payment_table.order_id = tx_commerce_orders.order_id
            LEFT JOIN tx_commerce_order_articles AS delivery_table 
                ON delivery_table.order_id = tx_commerce_orders.order_id
            LEFT JOIN static_currencies 
                ON static_currencies.uid = tx_commerce_orders.cu_iso_3_uid';
        $addWhere = ' AND payment_table.article_type_uid = ' . PAYMENTARTICLETYPE .
            ' AND delivery_table.article_type_uid = ' . DELIVERYARTICLETYPE .
            $addWhere;

        $this->pidSelect = 'tx_commerce_orders.pid = ' . $id;

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        // get Module TSConfig
        $moduleConfig = BackendUtility::getModTSconfig($id, 'mod.commerce_orders');

        $deliveryProductUid = $moduleConfig['properties']['deliveryProductUid'] ?
            $moduleConfig['properties']['deliveryProductUid'] :
            0;
        if ($deliveryProductUid > 0) {
            $deliveryArticles = $articleRepository->findUidsByProductUid($deliveryProductUid);

            if (!empty($deliveryArticles)) {
                $addWhere .= ' AND delivery_table.article_uid IN (' . implode(',', $deliveryArticles) . ') ';
            }
        }

        $paymentProductUid = $moduleConfig['properties']['paymentProductUid'] ?
            $moduleConfig['properties']['paymentProductUid'] :
            0;
        if ($paymentProductUid > 0) {
            $paymentArticles = $articleRepository->findUidsByProductUid($paymentProductUid);

            if (!empty($paymentArticles)) {
                $addWhere .= ' AND delivery_table.article_uid IN (' . implode(',', $paymentArticles) . ') ';
            }
        }

        $queryParts = [
            'SELECT' => $fieldList,
            'FROM' => $from,
            'WHERE' => $this->pidSelect . ' ' . $pC . BackendUtility::deleteClause($table) .
                BackendUtility::versioningPlaceholderClause($table) . ' ' . $addWhere . ' ' . $search,
            'GROUPBY' => '',
            'ORDERBY' => $this->getDatabaseConnection()->stripOrderBy($orderBy),
            'LIMIT' => $limit,
        ];
        // Filter out records that are translated, if TSconfig mod.web_list.hideTranslations is set
        if ((
                in_array($table, GeneralUtility::trimExplode(',', $this->hideTranslations))
                || $this->hideTranslations === '*'
            )
            && !empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
            && $table !== 'pages_language_overlay'
        ) {
            $queryParts['WHERE'] .= ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . '=0 ';
        }
        // Apply hook as requested in http://forge.typo3.org/issues/16634
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'makeQueryArray_post')) {
                $_params = array(
                    'orderBy' => $orderBy,
                    'limit' => $limit,
                    'pC' => $pC,
                    'search' => $search
                );
                $hookObj->makeQueryArray_post($queryParts, $this, $table, $id, $addWhere, $fieldList, $_params);
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
        $this->totalItems = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            $constraints['FROM'],
            $constraints['WHERE']
        );
    }

    /**
     * Makes the list of fields to select for a table.
     *
     * @param string $table Table name
     * @param bool|int $dontCheckUser If set, users access to the
     *      field (non-exclude-fields) is NOT checked.
     * @param bool|int $addDateFields If set, also adds crdate and
     *      tstamp fields (note: they will also be added if user is admin or
     *      dontCheckUser is set)
     *
     * @return array Array, where values are fieldnames to include in query
     */
    public function makeFieldList($table, $dontCheckUser = 0, $addDateFields = 0)
    {
        if ($this->csvOutput) {
            $fieldListArr = $this->csvFieldArray;
        } else {
            $fieldListArr = parent::makeFieldList($table, $dontCheckUser, $addDateFields);
            foreach ($this->additionalFieldArray as $fN) {
                $fieldListArr[] = $fN;
            }
        }
        return $fieldListArr;
    }


    /**
     * Query the table to build dropdown list.
     *
     * @param string $table Table
     *
     * @return string
     */
    protected function renderMoveToSelector($table)
    {
        // @todo fix move action

        // Return blank, if disabled:
        if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
            return '';
        }

        $languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xlf:';
        $database = $this->getDatabaseConnection();

        $tableReadOnly = ConfigurationUtility::getInstance()->getTcaValue($table . '.ctrl.readOnly');

        // Init:
        $theData = [];

        // Traverse the fields:
        foreach ($this->fieldArray as $fCol) {
            switch ((string) $fCol) {
                case '_CONTROL_':
                    if ($this->id && !$tableReadOnly) {
                        $foreignTable = ConfigurationUtility::getInstance()
                            ->getTcaValue('tx_commerce_orders.columns.newpid.config.foreign_table');
                        $resParent = $database->exec_SELECTquery(
                            'pid',
                            $foreignTable,
                            'uid = ' . $this->id . ' ' . BackendUtility::deleteClause($foreignTable)
                        );

                        $moveToSelectorRow = '';
                        if (($parentRow = $database->sql_fetch_assoc($resParent))) {
                            // Get the pages below $orderPid
                            $ret = \CommerceTeam\Commerce\Utility\BackendUtility::getOrderFolderSelector(
                                $this->orderPid,
                                ConfigurationUtility::getInstance()->getExtConf('OrderFolderRecursiveLevel')
                            );
                            $moveToSelectorRow .= '<select name="modeDestUid" size="1">
								<option value="" selected="selected">' .
                                $this->getLanguageService()->sL($languageFile . 'movedestination') .
                                '</option>';
                            foreach ($ret as $displayArray) {
                                $moveToSelectorRow .= '<option value="' . $displayArray[1] . '">' .
                                    $displayArray[0] .
                                    '</option>';
                            }

                            $moveToSelectorRow .= '</select> <input type="submit" name="OK" value="ok">';
                        }

                        $theData[$fCol] = $moveToSelectorRow;
                    }
                    break;

                    // Regular fields header:
                default:
                    $theData[$fCol] = '';
            };
        }

        // Create and return header table row:
        return $this->addElement(1, '', $theData, '', '');
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

<?php
namespace CommerceTeam\Commerce\ViewHelpers;

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

use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the Orderlist in the BE ordermodule.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\FeuserRecordList
 *
 * @todo discuss if this class can be removed
 *
 * @author 2006-2011 Volker Graubaum <vg_typo3@e-netconsulting.de>
 */
class FeuserRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
{
    /**
     * Alternate background color.
     *
     * @var bool
     */
    public $alternateBgColors = true;

    /**
     * Disable single table view.
     *
     * @var bool
     */
    protected $disableSingleTableView;

    /**
     * Writes the top of the full listing.
     *
     * @param array $row Current page record
     *
     * @return void
     */
    public function writeTop(array $row)
    {
        $language = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        // Makes the code for the pageicon in the top
        $this->pageRow = $row;
        ++$this->counter;
        $alttext = BackendUtility::getRecordIconAltText($row, 'pages');
        $iconImg = IconUtility::skinImg(
            $this->backPath,
            IconUtility::getIcon('pages', $row),
            'class="absmiddle" title="' . htmlspecialchars($alttext) . '"'
        );
        // pseudo title column name
        $titleCol = 'test';
        // Setting the fields to display in the list
        // (this is of course "pseudo fields" since this is the top!)
        $this->fieldArray = array($titleCol, 'up');

        // Filling in the pseudo data array:
        $theData = array();
        $theData[$titleCol] = $this->widthGif;

        // Get users permissions for this row:
        $localCalcPerms = $backendUser->calcPerms($row);

        $theData['up'] = array();

        // Initialize control panel for currect page ($this->id):
        // Some of the controls are added only if $this->id is set
        // - since they make sense only on a real page, not root level.
        $theCtrlPanel = array();

            // If edit permissions are set
        if ($localCalcPerms & 2) {
            // Adding "New record" icon:
            if (!$this->getController()->modTSconfig['properties']['noCreateRecordsLink']) {
                $theCtrlPanel[] = '<a href="#" onclick="' .
                    htmlspecialchars('return jumpExt(\'db_new.php?id=' . $this->id . '\');') . '">' .
                    IconUtility::getSpriteIcon(
                        'actions-document-new',
                        array('title' => $language->getLL('newRecordGeneral', 1))
                    ) . '</a>';
            }

                // Adding "Hide/Unhide" icon:
            if ($this->id) {
                // @todo: change the return path
                if ($row['hidden']) {
                    $params = '&data[pages][' . $row['uid'] . '][hidden]=0';
                    $theCtrlPanel[] = '<a href="#" onclick="' .
                        htmlspecialchars(
                            'return jumpToUrl(\'' .
                            $this->getControllerDocumentTemplate()->issueCommand($params, -1) .
                            '\');'
                        ) . '">' .
                        IconUtility::getSpriteIcon(
                            'actions-edit-unhide',
                            array('title' => $language->getLL('unHidePage', 1))
                        ) .
                        '</a>';
                } else {
                    $params = '&data[pages][' . $row['uid'] . '][hidden]=1';
                    $theCtrlPanel[] = '<a href="#" onclick="' .
                        htmlspecialchars(
                            'return jumpToUrl(\'' .
                            $this->getControllerDocumentTemplate()->issueCommand($params, -1) .
                            '\');'
                        ) . '">' .
                        IconUtility::getSpriteIcon(
                            'actions-edit-hide',
                            array('title' => $language->getLL('hidePage', 1))
                        ) .
                        '</a>';
                }
            }
        }

        // "Paste into page" link:
        if (($localCalcPerms & 8) || ($localCalcPerms & 16)) {
            $elFromTable = $this->clipObj->elFromTable('');
            if (!empty($elFromTable)) {
                $theCtrlPanel[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $this->id)) .
                    '" onclick="' .
                    htmlspecialchars(
                        'return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into', $elFromTable)
                    ) . '">' .
                    IconUtility::getSpriteIcon(
                        'actions-document-paste-into',
                        array('title' => $language->getLL('clip_paste', 1))
                    ) . '</a>';
            }
        }

        // Finally, compile all elements of the control panel into table cells:
        if (!empty($theCtrlPanel)) {
            $theData['up'][] = '
                <!--
                    Control panel for page
                -->
                <table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-ctrltop">
                    <tr>
                        <td>' . implode('</td><td>', $theCtrlPanel) . '</td>
                    </tr>
                </table>';
        }

        // Add "CSV" link, if a specific table is shown:
        if ($this->table) {
            $theData['up'][] = '<a href="' . htmlspecialchars($this->listURL() . '&csv=1') . '">' .
                IconUtility::getSpriteIcon(
                    'mimetypes-text-csv',
                    array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.csv', 1))
                ) . '</a>';
        }

        // Add "Export" link, if a specific table is shown:
        if ($this->table && ExtensionManagementUtility::isLoaded('impexp')) {
            $theData['up'][] = '<a href="' .
                htmlspecialchars(
                    $this->backPath . ExtensionManagementUtility::extRelPath('impexp') .
                    'app/index.php?tx_impexp[action]=export&tx_impexp[list][]=' .
                    rawurlencode($this->table . ':' . $this->id)
                ) . '">' .
                IconUtility::getSpriteIcon(
                    'actions-document-export-t3d',
                    array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:rm.export', 1))
                ) . '</a>';
        }

        // Add "refresh" link:
        $theData['up'][] = '<a href="' . htmlspecialchars($this->listURL()) . '">' .
            IconUtility::getSpriteIcon(
                'actions-system-refresh',
                array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.reload', 1))
            ) . '</a>';

        // Add icon with clickmenu, etc:
        // If there IS a real page...:
        if ($this->id) {
            // Setting title of page + the "Go up" link:
            $theData[$titleCol] .= '<br /><span title="' . htmlspecialchars($row['_thePathFull']) . '">' .
                htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['_thePath'], -$this->fixedL)) . '</span>';
            $theData['up'][] = '<a href="' . htmlspecialchars($this->listURL($row['pid'])) .
                '" onclick="setHighlight(' . $row['pid'] . ')">' .
                IconUtility::getSpriteIcon(
                    'actions-view-go-up',
                    array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', 1))
                ) . '</a>';

            // Make Icon:
            if ($this->clickMenuEnabled) {
                $theIcon = $this->getControllerDocumentTemplate()->wrapClickMenuOnIcon($iconImg, 'pages', $this->id);
            } else {
                $theIcon = $iconImg;
            }
            // On root-level of page tree:
        } else {
            // Setting title of root (sitename):
            $theData[$titleCol] .= '<br />' .
                htmlspecialchars(GeneralUtility::fixed_lgd_cs(
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                    -$this->fixedL
                ));

            // Make Icon:
            $theIcon = IconUtility::getSpriteIcon('apps-pagetree-root');
        }

        // If there is a returnUrl given, add a back-link:
        if ($this->returnUrl) {
            $theData['up'][] = '<a href="' .
                htmlspecialchars(GeneralUtility::linkThisUrl(
                    $this->returnUrl,
                    array('id' => $this->id)
                )) . '" class="typo3-goBack">' .
                IconUtility::getSpriteIcon(
                    'actions-view-go-back',
                    array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.goBack', 1))
                ) . '</a>';
        }

        // Finally, the "up" pseudo field is compiled into a table
        // - has been accumulated in an array:
        $theData['up'] = '
			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>' . implode('</td><td>', $theData['up']) . '</td>
				</tr>
			</table>';

        // ... and the element row is created:
        $out = $this->addelement(1, $theIcon, $theData, '', $this->leftMargin);

        // ... and wrapped into a table and added to the internal ->HTMLcode variable:
        $this->HTMLcode .= '
			<!--
				Page header for db_list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-dblist-top">
				' . $out . '
			</table>';
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
        $id = (int) $id;
        if ($this->sortField) {
            $orderby = $this->sortField . ' ';
            if ($this->sortRev == 1) {
                $orderby .= 'DESC';
            }
        } else {
            $orderby = 'fe_users.crdate DESC';
        }
        $queryArray = array(
            'SELECT' => 'fe_users.uid, fe_users.username, fe_users.name, fe_users.email,
                count(tx_commerce_orders.uid) AS bestellungen ',
            'FROM' => 'tx_commerce_orders, fe_users',
            'WHERE' => 'fe_users.deleted = 0
                AND tx_commerce_orders.cust_fe_user = fe_users.uid
                AND tx_commerce_orders.pid = ' .
                (int) $id . ' ' . $addWhere,
            'GROUPBY' => 'fe_users.uid, fe_users.username, fe_users.name, fe_users.email',
            'ORDERBY' => $orderby,
            'sorting' => '',
            'LIMIT' => '',
        );

        $this->dontShowClipControlPanels = 1;

        return $queryArray;
    }

    /**
     * Generate list.
     *
     * @return void
     */
    public function generateList()
    {
        $backendUser = $this->getBackendUser();
        $settingsFactory = SettingsFactory::getInstance();

        // @todo auf eine tabelle beschränken, keine while liste mehr
        foreach ($GLOBALS['TCA'] as $tableName) {
            // Checking if the table should be rendered:
            // Checks that we see only permitted/requested tables:
            if ((!$this->table || $tableName == $this->table)
                && (!$this->tableList || GeneralUtility::inList($this->tableList, $tableName))
                && $backendUser->check('tables_select', $tableName)
            ) {
                // iLimit is set depending on whether we're in single- or multi-table mode
                if ($this->table) {
                    $maxSingleDdListItems = $settingsFactory
                        ->getTcaValue($tableName . '.interface.maxSingleDBListItems');
                    $this->iLimit = $maxSingleDdListItems ? (int) $maxSingleDdListItems : $this->itemsLimitSingleTable;
                } else {
                    $maxDdListItems = $settingsFactory->getTcaValue($tableName . '.interface.maxDBListItems');
                    $this->iLimit = $maxDdListItems ? (int) $maxDdListItems : $this->itemsLimitPerTable;
                }
                if ($this->showLimit) {
                    $this->iLimit = $this->showLimit;
                }

                $fields = array(
                    'username',
                    'surname',
                    'name',
                    'email',
                );

                $this->HTMLcode .= $this->getTable($tableName, $this->id, implode(',', $fields));
            }
        }
    }

    /**
     * Wrapping input code in link to URL or email if $testString is either.
     *
     * @param string $code Code
     * @param string $testString Test string
     *
     * @return string Link-Wrapped $code value, if $testString was URL or email.
     */
    protected function myLinkUrlMail($code, $testString)
    {
        // Check for URL:
        $schema = parse_url($testString);
        if ($schema['scheme'] && GeneralUtility::inList('http,https,ftp', $schema['scheme'])) {
            return '<a href="' . htmlspecialchars($testString) . '" target="_blank">' . $code . '</a>';
        }

        // Check for email:
        if (GeneralUtility::validEmail($testString)) {
            return '<a href="mailto:' . htmlspecialchars($testString) . '" target="_blank">' . $code . '</a>';
        }

        // Return if nothing else...
        return $code;
    }

    /**
     * Rendering a single row for the list.
     *
     * @param string $table Table name
     * @param array $row Current record
     * @param int $cc Counter, counting for each time an element
     *      is rendered (used for alternating colors)
     * @param string $titleCol Table field (column) where header value is found
     * @param string $thumbsCol Table field (column) where (possible)
     *      thumbnails can be found
     * @param int $indent Indent from left.
     *
     * @return string Table row for the element
     */
    public function renderListRow($table, array $row, $cc, $titleCol, $thumbsCol, $indent = 0)
    {
        $backendUser = $this->getBackendUser();

        $iOut = '';

        if (substr(TYPO3_version, 0, 3) >= '4.0') {
            // In offline workspace, look for alternative record:
            BackendUtility::workspaceOL($table, $row, $this->getBackendUser()->workspace);
        }

        $rowBackgroundColor = '';
        if ($this->alternateBgColors) {
            $rowBackgroundColor = $cc % 2 ? '' : ' bgcolor="' . GeneralUtility::modifyHTMLColor(
                $this->getControllerDocumentTemplate()->bgColor4,
                10,
                10,
                10
            ) . '"';
        }

        if ($backendUser->getModuleData('commerce_orders/index.php/userid', 'ses') == $row['uid']) {
            $rowBackgroundColor = ' bgcolor="' .
                GeneralUtility::modifyHTMLColor($this->getControllerDocumentTemplate()->bgColor4, 30, 30, 30) . '"';
        }

        // Overriding with versions background color if any:
        $rowBackgroundColor = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : $rowBackgroundColor;

        // Initialization
        $alttext = BackendUtility::getRecordIconAltText($row, $table);

        // Incr. counter.
        ++$this->counter;

        // The icon with link
        $iconImg = IconUtility::skinImg(
            $this->backPath,
            IconUtility::getIcon($table, $row),
            'title="' . htmlspecialchars($alttext) . '"' . ($indent ? ' style="margin-left: ' . $indent . 'px;"' : '')
        );
        $theIcon = $this->clickMenuEnabled ? $this->getControllerDocumentTemplate()->wrapClickMenuOnIcon(
            $iconImg,
            $table,
            $row['uid']
        ) : $iconImg;

        // Preparing and getting the data-array
        $theData = array();
        foreach ($this->fieldArray as $fCol) {
            if ($fCol == 'pid') {
                $theData[$fCol] = $row[$fCol];
            }
            if ($fCol == 'username') {
                $theData[$fCol] = $row[$fCol];
            } elseif ($fCol == 'crdate') {
                $theData[$fCol] = BackendUtility::date($row[$fCol]);
            } elseif ($fCol == '_PATH_') {
                $theData[$fCol] = $this->recPath($row['pid']);
            } elseif ($fCol == '_CONTROL_') {
                $theData[$fCol] = $this->makeControl($table, $row);
            } elseif ($fCol == '_CLIPBOARD_') {
                $theData[$fCol] = $this->makeClip($table, $row);
            } elseif ($fCol == '_LOCALIZATION_') {
                list($lC1, $lC2) = $this->makeLocalizationPanel($table, $row);
                $theData[$fCol] = $lC1;
                $theData[$fCol . 'b'] = $lC2;
            } elseif ($fCol == '_LOCALIZATION_b') {
                // Do nothing, has been done above.
                $theData[$fCol] .= '';
            } else {
                /*
                 * Use own method, if typo3 4.0.0 is not installed
                 */
                if (substr(TYPO3_version, 0, 3) >= '4.0') {
                    $theData[$fCol] = $this->linkUrlMail(
                        htmlspecialchars(
                            BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'])
                        ),
                        $row[$fCol]
                    );
                } else {
                    $theData[$fCol] = $this->myLinkUrlMail(
                        htmlspecialchars(
                            BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'])
                        ),
                        $row[$fCol]
                    );
                }
            }
        }

        // Add row to CSV list:
        if ($this->csvOutput) {
            $beCsvCharset = SettingsFactory::getInstance()->getExtConf('BECSVCharset');
            // Charset Conversion
            /**
             * Charset converter.
             *
             * @var \TYPO3\CMS\Core\Charset\CharsetConverter
             */
            $csObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
            $csObj->initCharset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);

            if (!$beCsvCharset) {
                $beCsvCharset = 'iso-8859-1';
            }
            $csObj->initCharset($beCsvCharset);

            $csObj->convArray($row, $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'], $beCsvCharset);

            $this->addToCSV($row);
        }

        // Create element in table cells:
        $iOut .= $this->addelement(1, $theIcon, $theData, $rowBackgroundColor);

        // Render thumbsnails if a thumbnail column exists and there is content in it:
        if ($this->thumbs && trim($row[$thumbsCol])) {
            $iOut .= $this->addelement(
                4,
                '',
                array($titleCol => $this->thumbCode($row, $table, $thumbsCol)),
                $rowBackgroundColor
            );
        }

        // Finally, return table row element:
        return $iOut;
    }

    /**
     * Rendering the header row for a table.
     *
     * @param string $table Table name
     * @param array $currentIdList Array of the currectly displayed uids of the table
     *
     * @return string Header table row
     */
    public function renderListHeader($table, array $currentIdList)
    {
        $language = $this->getLanguageService();

        // Init:
        $theData = array();

        // Traverse the fields:
        foreach ($this->fieldArray as $fCol) {
            $theData[$fCol] = '';
            if ($this->table && is_array($currentIdList)) {
                // If the numeric clipboard pads are selected, show duplicate sorting link:
                if ($this->clipNumPane()) {
                    $theData[$fCol] .= '<a href="' .
                        htmlspecialchars($this->listURL('', -1) . '&duplicateField=' . $fCol) . '">' .
                        IconUtility::getSpriteIcon(
                            'actions-document-duplicates-select',
                            array('title' => $language->getLL('clip_duplicates', 1))
                        ) . '</a>';
                }
            }

            /*
             * Modified from this point to use relation table queries
             */
            $tables = array('fe_users');
            $temporaryData = '';
            foreach ($tables as $workTable) {
                if (SettingsFactory::getInstance()->getTcaValue($workTable . '.columns.' . $fCol)) {
                    $temporaryData = $this->addSortLink(
                        $language->sL(BackendUtility::getItemLabel($workTable, $fCol, '<i>[|]</i>')),
                        $fCol,
                        $table
                    );
                }
            }
            if ($temporaryData) {
                // Only if we have a entry in locallang
                $theData[$fCol] = $temporaryData;
            } else {
                // default handling
                $theData[$fCol] .= $this->addSortLink(
                    $language->sL(BackendUtility::getItemLabel($table, $fCol, '<i>[|]</i>')),
                    $fCol,
                    $table
                );
            }
        }

            // Create and return header table row:
        return $this->addelement(1, '', $theData, ' class="c-headLine"', '');
    }

    /**
     * Get table.
     *
     * @param string $table Table
     * @param int $id Id
     * @param array $rowlist Row list
     *
     * @return string
     */
    public function getTable($table, $id, array $rowlist)
    {
        // Loading all TCA details for this table:

        $tableConfig = SettingsFactory::getInstance()->getTcaValue($table);

        // Init
        $addWhere = '';
        $titleCol = $tableConfig['ctrl']['label'];
        $thumbsCol = $tableConfig['ctrl']['thumbnail'];
        $l10nEnabled = $tableConfig['ctrl']['languageField']
            && $tableConfig['ctrl']['transOrigPointerField']
            && !$tableConfig['ctrl']['transOrigPointerTable'];

        // Cleaning rowlist for duplicates and place
        // the $titleCol as the first column always!
        $this->fieldArray = array();
        // Add title column
        $this->fieldArray[] = $titleCol;

        if ($this->localizationView && $l10nEnabled) {
            $this->fieldArray[] = '_LOCALIZATION_';
            $addWhere .= ' AND ' . $tableConfig['ctrl']['languageField'] . ' <= 0';
        }
        if ($this->showClipboard) {
            $this->fieldArray[] = '_CLIPBOARD_';
        }
        if ($this->searchLevels) {
            $this->fieldArray[] = '_PATH_';
        }

        // Cleaning up:
        $this->fieldArray = array_unique(array_merge($this->fieldArray, GeneralUtility::trimExplode(',', $rowlist, 1)));
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
        if ($thumbsCol) {
            // adding column for thumbnails
            $selectFields[] = $thumbsCol;
        }

        if ($table == 'pages') {
            if (ExtensionManagementUtility::isLoaded('cms')) {
                $selectFields[] = 'module';
                $selectFields[] = 'extendToSubpages';
            }
            $selectFields[] = 'doktype';
        }
        if (is_array($tableConfig['ctrl']['enablecolumns'])) {
            $selectFields = array_merge($selectFields, $tableConfig['ctrl']['enablecolumns']);
        }
        if ($tableConfig['ctrl']['type']) {
            $selectFields[] = $tableConfig['ctrl']['type'];
        }

        if ($tableConfig['ctrl']['typeicon_column']) {
            $selectFields[] = $tableConfig['ctrl']['typeicon_column'];
        }
        if ($tableConfig['ctrl']['versioning']) {
            $selectFields[] = 't3ver_id';
        }
        if ($l10nEnabled) {
            $selectFields[] = $tableConfig['ctrl']['languageField'];
            $selectFields[] = $tableConfig['ctrl']['transOrigPointerField'];
        }
        if ($tableConfig['ctrl']['label_alt']) {
            $selectFields = array_merge(
                $selectFields,
                GeneralUtility::trimExplode(',', $tableConfig['ctrl']['label_alt'], 1)
            );
        }

        // Unique list!
        $selectFields = array_unique($selectFields);
        // Making sure that the fields in the field-list ARE in the field-list from TCA!
        $selectFields = array_intersect($selectFields, $this->makeFieldList($table, 1));
        // implode it into a list of fields for the SQL-statement.
        $selFieldList = implode(',', $selectFields);

        // Create the SQL query for selecting the elements in the listing:
        // (API function from class.db_list.inc)
        $queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
        // Finding the total amount of records on the page
        // (API function from class.db_list.inc)
        $this->setTotalItems($queryParts);

        // Init:
        $dbCount = 0;
        $out = '';

        $database = $this->getDatabaseConnection();
        $language = $this->getLanguageService();

        // If the count query returned any number of records,
        // we perform the real query, selecting records.
        $result = false;
        if ($this->totalItems) {
            $result = $database->exec_SELECT_queryArray($queryParts);
            $dbCount = $database->sql_num_rows($result);
        }
        $listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;

        // If any records was selected, render the list:
        if ($dbCount) {
            // Half line is drawn between tables:
            if (!$listOnlyInSingleTableMode) {
                $theData = array();
                if (!$this->table && !$rowlist) {
                    $theData[$titleCol] = '<span style="display: block; width: ' .
                        ($this->getController()->MOD_SETTINGS['bigControlPanel'] ? '230' : '350') .
                        'px; height: 1px"></span>';
                }
                $out .= $this->addelement(0, '', $theData, '', $this->leftMargin);
            }

                // Header line is drawn
            $theData = array();
            if ($this->disableSingleTableView) {
                $theData[$titleCol] = '<span class="c-table">' .
                        $language->sL($tableConfig['ctrl']['title'], 1) .
                    '</span> (' . $this->totalItems . ')';
            } else {
                $title = $language->getLL(!$this->table ? 'expandView' : 'contractView', 1);
                $icon = IconUtility::getSpriteIcon(
                    'actions-view-table-' . ($this->table ? 'collapse' : 'expand'),
                    array('title' => $title)
                );
                $theData[$titleCol] = $this->linkWrapTable(
                    $table,
                    '<span class="c-table">' . $language->sL($tableConfig['ctrl']['title'], 1) .
                    '</span> (' . $this->totalItems . ') ' . $icon
                );
            }

            // CSH:
            $theData[$titleCol] .= BackendUtility::cshItem($table, '');

            if ($listOnlyInSingleTableMode) {
                $out .= '
					<tr>
						<td class="c-headLineTable" style="width: 95%;" ' . $theData[$titleCol] . '</td>
					</tr>';

                if ($this->getBackendUser()->uc['edit_showFieldHelp']) {
                    $language->loadSingleTableDescription($table);
                    if (isset($GLOBALS['TCA_DESCR'][$table]['columns'][''])) {
                        $out .= '
					<tr>
						<td class="c-tableDescription">' .
                            BackendUtility::helpTextIcon($table, '', $this->backPath, true) .
                            $GLOBALS['TCA_DESCR'][$table]['columns']['']['description'] . '</td>
					</tr>';
                    }
                }
            } else {
                $theUpIcon = ($table == 'pages' && $this->id && isset($this->pageRow['pid'])) ?
                    '<a href="' . htmlspecialchars($this->listURL($this->pageRow['pid'])) . '">' .
                        IconUtility::getSpriteIcon(
                            'actions-view-go-up',
                            array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', 1))
                        ) . '</a>' :
                    '';
                $out .= $this->addelement(1, $theUpIcon, $theData, ' class="c-headLineTable"', '');
            }

            $iOut = '';
            if (!$listOnlyInSingleTableMode) {
                // Fixing a order table for sortby tables
                $this->currentTable = array();
                $currentIdList = array();
                $doSort = ($tableConfig['ctrl']['sortby'] && !$this->sortField);

                $prevUid = 0;
                $prevPrevUid = 0;
                    // Accumulate rows here
                $accRows = array();
                while (($row = $database->sql_fetch_assoc($result))) {
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
                $database->sql_free_result($result);

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
                    // Forward/Backwards navigation links:
                    list($flag, $code) = $this->fwd_rwd_nav($table);
                    $iOut .= $code;

                    // If render item, increment counter and call function
                    if ($flag) {
                        ++$cc;
                        $row[$titleCol] = '<a href="' . GeneralUtility::getIndpEnv('REQUEST_URI') .
                            '&userId=' . $row['uid'] . '">' . $row[$titleCol] . '</a>';

                        $iOut .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
                        // If localization view is enabled it means that the selected records are either
                        // default or All language and here we will not select translations which point
                        // to the main record:
                        if ($this->localizationView && $l10nEnabled) {
                            // Look for translations of this record:
                            $translations = $database->exec_SELECTgetRows(
                                $selFieldList,
                                $table,
                                'pid = ' . $row['pid'] . ' AND ' . $tableConfig['ctrl']['languageField'] . ' > 0 AND ' .
                                $tableConfig['ctrl']['transOrigPointerField'] . ' = ' . (int) $row['uid'] .
                                BackendUtility::deleteClause($table)
                            );

                            // For each available translation, render the record:
                            foreach ($translations as $lRow) {
                                $iOut .= $this->renderListRow($table, $lRow, $cc, $titleCol, $thumbsCol, 18);
                            }
                        }
                    }

                    // Counter of total rows incremented:
                    ++$this->eCounter;
                }

                // The header row for the table is now created:
                $out .= $this->renderListHeader($table, $currentIdList);
            }

            // The list of records is added after the header:
            $out .= $iOut;

            // ... and it is all wrapped in a table:
            $out = '
            <!--
                DB listing of elements:	"' . htmlspecialchars($table) . '"
            -->
                <table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist' .
                    ($listOnlyInSingleTableMode ? ' typo3-dblist-overview' : '') . '">
                    ' . $out . '
                </table>';

            // Output csv if...
            if ($this->csvOutput) {
                $this->outputCSV($table);
            }
        }

        // Return content:
        return $out;
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get controller.
     *
     * @return \CommerceTeam\Commerce\Controller\OrdersModuleController
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

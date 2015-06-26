<?php
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension of DatabaseRecordList to render category and product lists
 *
 * Class Tx_Commerce_ViewHelpers_CategoryRecordList
 *
 * @author 2005-2012 Franz Holzinger <kontakt@fholzinger.com>
 */
class Tx_Commerce_ViewHelpers_CategoryRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList {
	/**
	 * Parent uid
	 *
	 * @var int
	 */
	public $parentUid;

	/**
	 * Additional where per table
	 *
	 * @var array
	 */
	public $addWhere = array(
		'tx_commerce_products' => ' AND uid_foreign = %d',
		'tx_commerce_categories' => ' AND uid_foreign = %d',
	);

	/**
	 * Join queries per table
	 *
	 * @var array
	 */
	public $joinTables = array(
		'tx_commerce_products' => ' LEFT JOIN tx_commerce_products_categories_mm
			ON tx_commerce_products.uid = tx_commerce_products_categories_mm.uid_local',
		'tx_commerce_categories' => ' LEFT JOIN tx_commerce_categories_parent_category_mm
			ON tx_commerce_categories.uid = tx_commerce_categories_parent_category_mm.uid_local',
	);

	/**
	 * Module menu
	 *
	 * @var array
	 */
	public $MOD_MENU;

	/**
	 * New record icon
	 *
	 * @var string
	 */
	public $newRecordIcon = '';

	/**
	 * Page information
	 *
	 * @var array
	 */
	public $pageinfo;

	/**
	 * Create the panel of buttons for submitting the form
	 * or otherwise perform operations.
	 *
	 * @param array $row Data
	 *
	 * @return array all available buttons as an assoc. array
	 */
	public function getButtons(array $row) {
		$language = $this->getLanguageService();

		$buttons = array(
			'csh' => '',
			'view' => '',
			'edit' => '',
			'hide_unhide' => '',
			'move' => '',
			'new_record' => '',
			'paste' => '',
			'level_up' => '',
			'cache' => '',
			'reload' => '',
			'shortcut' => '',
			'back' => '',
			'csv' => '',
			'export' => ''
		);
		// Get users permissions for this row:
		$localCalcPerms = $this->getBackendUser()->calcPerms($row);
		// CSH
		if (!strlen($this->id)) {
			$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_commerce', 'list_module_noId', $GLOBALS['BACK_PATH'], '', TRUE);
		} elseif (!$this->id) {
			$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_commerce', 'list_module_root', $GLOBALS['BACK_PATH'], '', TRUE);
		} else {
			$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_commerce', 'list_module', $GLOBALS['BACK_PATH'], '', TRUE);
		}
		if (isset($this->id)) {
			// New record
			if (!$GLOBALS['SOBE']->modTSconfig['properties']['noCreateRecordsLink']) {
				$params = '&parentCategory=' . $this->parentUid;
				$buttons['new_record'] = '<a href="#" onclick="' .
					htmlspecialchars(('return jumpExt(\'' . $this->backPath . 'db_new.php?id=' . $this->id . $params . '\');')) .
					'" title="' . $language->getLL('newRecordGeneral', TRUE) . '">' .
					IconUtility::getSpriteIcon('actions-document-new') . '</a>';
			}
			// If edit permissions are set, see
			// \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
			if ($localCalcPerms & 2 && !empty($this->id)) {
				// Edit
				$params = '&edit[tx_commerce_categories][' . $this->pageRow['uid'] . ']=edit';
				$buttons['edit'] = '<a href="#" onclick="' .
					htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
					'" title="' . $language->getLL('editPage', TRUE) . '">' .
					IconUtility::getSpriteIcon('actions-page-open') . '</a>';
			}
			// Paste
			if (($localCalcPerms & 8 || $localCalcPerms & 16) && $this->parentUid) {
				$elFromTable = $this->clipObj->elFromTable('');
				if (count($elFromTable)) {
					$buttons['paste'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $this->id)) . '" onclick="' .
						htmlspecialchars(('return ' . $this->clipObj->confirmMsg('tx_commerce_categories', $this->pageRow, 'into', $elFromTable))) .
						'" title="' . $language->getLL('clip_paste', TRUE) . '">' .
						IconUtility::getSpriteIcon('actions-document-paste-after') . '</a>';
				}
			}
			if (
				$this->table && (!isset($GLOBALS['SOBE']->modTSconfig['properties']['noExportRecordsLinks']) ||
				(isset($GLOBALS['SOBE']->modTSconfig['properties']['noExportRecordsLinks']) &&
				!$GLOBALS['SOBE']->modTSconfig['properties']['noExportRecordsLinks']))
			) {
				// CSV
				$buttons['csv'] = '<a href="' . htmlspecialchars(($this->listURL() . '&csv=1')) . '" title="' .
					$language->sL('LLL:EXT:lang/locallang_core.xlf:labels.csv', TRUE) . '">' .
					IconUtility::getSpriteIcon('mimetypes-text-csv') . '</a>';
				// Export
				if (ExtensionManagementUtility::isLoaded('impexp')) {
					$url = BackendUtility::getModuleUrl('xMOD_tximpexp', array('tx_impexp[action]' => 'export'));
					$buttons['export'] = '<a href="' .
						htmlspecialchars(($url . '&tx_impexp[list][]=' . rawurlencode(($this->table . ':' . $this->id)))) .
						'" title="' . $language->sL('LLL:EXT:lang/locallang_core.xlf:rm.export', TRUE) . '">' .
						IconUtility::getSpriteIcon('actions-document-export-t3d') . '</a>';
				}
			}
			// Reload
			$buttons['reload'] = '<a href="' . htmlspecialchars($this->listURL()) . '" title="' .
				$language->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload', TRUE) .
				'">' . IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';
			// Shortcut
			if ($this->getBackendUser()->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->getDocumentTemplate()->makeShortcutIcon(
					'id, imagemode, pointer, table, search_field, search_levels, showLimit, sortField, sortRev',
					implode(',', array_keys($this->MOD_MENU)),
					'web_list'
				);
			}
			// Back
			if ($this->returnUrl) {
				$buttons['back'] = '<a href="' .
					htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl, array('id' => $this->id))) .
					'" class="typo3-goBack" title="' . $language->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', TRUE) . '">' .
					IconUtility::getSpriteIcon('actions-view-go-back') . '</a>';
			}

			if (!empty($this->parentUid)) {
				// Setting title of page + the "Go up" link:
				$temp = $this->parentUid;
				$this->parentUid = $this->pageRow['pid'];
				$buttons['level_up'] = '<a href="' . htmlspecialchars($this->listURL($this->id)) .
					'" onclick="setHighlight(' . $this->pageRow['pid'] . ')" title="' .
					$language->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', TRUE) . '">' .
					IconUtility::getSpriteIcon('actions-view-go-up') . '</a>';
				$this->parentUid = $temp;
			}
		}

		return $buttons;
	}

	/**
	 * Creates the listing of records from a single table
	 *
	 * @param string $table Table name
	 * @param int $id Page id
	 * @param string $rowlist List of fields to show in the listing.
	 * 	Pseudo fields will be added including the record header.
	 *
	 * @return string HTML table with the listing for the record.
	 * @throws UnexpectedValueException If hook was of wrong interface
	 */
	public function getTable($table, $id, $rowlist) {
		$database = $this->getDatabaseConnection();
		$language = $this->getLanguageService();
		$backendUser = $this->getBackendUser();

		// Init
		$addWhere = '';
		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField'] &&
			$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] &&
			!$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];
		$tableCollapsed = (!$this->tablesCollapsed[$table]) ? FALSE : TRUE;

		// prepare space icon
		$this->spaceIcon = IconUtility::getSpriteIcon('empty-empty', array('style' => 'background-position: 0 10px;'));

		// Cleaning rowlist for duplicates and place the $titleCol
		// as the first column always!
		$this->fieldArray = array();
		// title Column
		// Add title column
		$this->fieldArray[] = $titleCol;
		// Control-Panel
		if (!GeneralUtility::inList($rowlist, '_CONTROL_')) {
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
				' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . ' <= 0
				OR
				' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ' = 0
			)';
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
		// adding column for thumbnails
		if ($thumbsCol) {
			$selectFields[] = $thumbsCol;
		}

		if ($table == 'pages') {
			if (ExtensionManagementUtility::isLoaded('cms')) {
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
				$selectFields[] = 'nav_hide';
			}
			$selectFields[] = 'doktype';
		}
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
			$selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['type']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['type'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$selectFields[] = 't3ver_id';
			$selectFields[] = 't3ver_state';
			$selectFields[] = 't3ver_wsid';
			// Filtered out when tx_commerce_categories in makeFieldList()
			$selectFields[] = 't3ver_swapmode';
		}
		if ($l10nEnabled) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
			$selectFields = array_merge($selectFields, GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1));
		}
		// Unique list!
		$selectFields = array_unique($selectFields);
		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selectFields = array_intersect($selectFields, $this->makeFieldList($table, 1));
		// implode it into a list of fields for the SQL-statement.
		$selFieldList = implode(',', $selectFields);
		$this->selFieldList = $selFieldList;

		/**
		 * DB-List getTable
		 *
		 * @date 2007-11-16
		 * @request Malte Jansen <mail@maltejansen.de>
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);

				if (!($hookObject instanceof \TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface)) {
					throw new UnexpectedValueException(
						'$hookObject must implement interface \TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface',
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
			// (API function from class.db_list.inc)
			$queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
			$this->firstElementNumber = $this->firstElementNumber + 2;
			$this->iLimit = $this->iLimit - 2;
		} else {
			// (API function from class.db_list.inc)
			$queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
		}

		// Finding the total amount of records on the page
		$this->setTotalItems($queryParts);

		// Init:
		$dbCount = 0;
		$out = '';
		$listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;

		$result = FALSE;
		// If the count query returned any number of records,
		// we perform the real query, selecting records.
		if ($this->totalItems) {
			// Fetch records only if not in single table mode or
			// if in multi table mode and not collapsed
			if ($listOnlyInSingleTableMode || (!$this->table && $tableCollapsed)) {
				$dbCount = $this->totalItems;
			} else {
				// set the showLimit to the number of records when outputting as CSV
				if ($this->csvOutput) {
					$this->showLimit = $this->totalItems;
					$this->iLimit = $this->totalItems;
				}
				$result = $database->exec_SELECT_queryArray($queryParts);
				$dbCount = $database->sql_num_rows($result);
			}
		}

		// If any records was selected, render the list:
		if ($dbCount) {
			// Half line is drawn between tables:
			if (!$listOnlyInSingleTableMode) {
				$theData = array();
				if (!$this->table && !$rowlist) {
					$theData[$titleCol] = '<img src="/' . TYPO3_mainDir . '/clear.gif" width="' .
						($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] ? '230' : '350') . '" height="1" alt="" />';
					if (in_array('_CONTROL_', $this->fieldArray)) {
						$theData['_CONTROL_'] = '';
					}
					if (in_array('_CLIPBOARD_', $this->fieldArray)) {
						$theData['_CLIPBOARD_'] = '';
					}
				}
				$out .= $this->addelement(0, '', $theData, 'class="c-table-row-spacer"', $this->leftMargin);
			}

				// Header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView) {
				$theData[$titleCol] = '<span class="c-table">' .
					BackendUtility::wrapInHelp($table, '', $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE)) .
					'</span> (' . $this->totalItems . ')';
			} else {
				$theData[$titleCol] = $this->linkWrapTable(
					$table,
					'<span class="c-table">' . $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE) .
						'</span> (' . $this->totalItems . ') ' .
					(
						$this->table ?
						IconUtility::getSpriteIcon('actions-view-table-collapse', array('title' => $language->getLL('contractView', TRUE))) :
						IconUtility::getSpriteIcon('actions-view-table-expand', array('title' => $language->getLL('expandView', TRUE)))
					)
				);
			}

			if ($listOnlyInSingleTableMode) {
				$out .= '
					<tr>
						<td class="t3-row-header" style="width: 95%;">' . BackendUtility::wrapInHelp($table, '', $theData[$titleCol]) . '</td>
					</tr>';
			} else {
				// Render collapse button if in multi table mode
				$collapseIcon = '';
				if (!$this->table) {
					if ($tableCollapsed) {
						$options = array(
							'class' => 'collapseIcon',
							'title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.expandTable', TRUE)
						);
					} else {
						$options = array(
							'class' => 'collapseIcon',
							'title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.collapseTable', TRUE)
						);
					}
					$value = $tableCollapsed ? '0' : '1';

					$collapseIcon = '<a href="' . htmlspecialchars($this->listURL() . '&collapse[' . $table . ']=' . $value) . '">' .
						(
							$tableCollapsed ?
							IconUtility::getSpriteIcon('actions-view-list-expand', $options) :
							IconUtility::getSpriteIcon('actions-view-list-collapse', $options)
						) .
						'</a>';
				}
				$out .= $this->addElement(1, $collapseIcon, $theData, ' class="t3-row-header"', '');
			}

			$iOut = '';
			// Render table rows only if in multi table view
			// and not collapsed or if in single table view
			if (!$listOnlyInSingleTableMode && (!$tableCollapsed || $this->table)) {
					// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;

					// Get first two rows and initialize prevPrevUid and prevUid if on page > 1
				if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
					$row = $database->sql_fetch_assoc($result);
					$prevPrevUid = - (int) $row['uid'];
					$row = $database->sql_fetch_assoc($result);
					$prevUid = $row['uid'];
				}

					// Accumulate rows here
				$accRows = array();
				while (($row = $database->sql_fetch_assoc($result))) {
					// In offline workspace, look for alternative record:
					BackendUtility::workspaceOL($table, $row, $this->getBackendUser()->workspace, TRUE);

					if (is_array($row)) {
						$accRows[] = $row;
						$currentIdList[] = $row['uid'];
						if ($doSort) {
							if ($prevUid) {
								$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
								$this->currentTable['next'][$prevUid] = '-' . $row['uid'];
								$this->currentTable['prevUid'][$row['uid']] = $prevUid;
							}
							$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? - $prevUid : $row['pid'];
							$prevUid = $row['uid'];
						}
					}
				}
				$database->sql_free_result($result);

				$this->totalRowCount = count($accRows);

				// CSV initiated
				if ($this->csvOutput) {
					$this->initCSV();
				}

				// Render items:
				$this->CBnames = array();
				$this->duplicateStack = array();
				$this->eCounter = $this->firstElementNumber;

				$iOut = '';
				$cc = 0;

				foreach ($accRows as $row) {
					// Render item row if counter < limit
					if ($cc < $this->iLimit) {
						$cc++;
						$this->translations = FALSE;
						$iOut .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);

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
										$tmpRow = BackendUtility::getRecordRaw(
											$table,
											't3ver_move_id="' . (int) $lRow['uid'] . '" AND pid="' . $row['_MOVE_PLH_pid'] .
												'" AND t3ver_wsid=' . $row['t3ver_wsid'] . BackendUtility::deleteClause($table),
											$selFieldList
										);
										$lRow = is_array($tmpRow) ? $tmpRow : $lRow;
									}

									// In offline workspace, look for alternative record:
									BackendUtility::workspaceOL($table, $lRow, $this->getBackendUser()->workspace, TRUE);
									if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
										$currentIdList[] = $lRow['uid'];
										$iOut .= $this->renderListRow($table, $lRow, $cc, $titleCol, $thumbsCol, 18);
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
					$iOut = $this->renderListNavigation('top') . $iOut . $this->renderListNavigation('bottom');
				} else {
						// show that there are more records than shown
					if ($this->totalItems > $this->itemsLimitPerTable) {
						$countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ? $this->itemsLimitSingleTable : $this->totalItems;
						$hasMore = ($this->totalItems > $this->itemsLimitSingleTable);
						$iOut .= '<tr><td colspan="' . count($this->fieldArray) . '" style="padding: 5px;">
							<a href="' . htmlspecialchars($this->listURL() . '&table=' . rawurlencode($table)) . '">' .
							'<img' . IconUtility::skinImg($this->backPath, 'gfx/pildown.gif', 'width="14" height="14"') . ' alt="" />' .
							' <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
							</td></tr>';
					}
				}

					// The header row for the table is now created:
				$out .= $this->renderListHeader($table, $currentIdList);
			}

				// The list of records is added after the header:
			$out .= $iOut;
			unset($iOut);

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
					// This ends the page with exit.
				$this->outputCSV($table);
			}
		}

			// Return content:
		return $out;
	}

	/**
	 * Returns the SQL-query array to select the records
	 * from a table $table with pid = $id
	 *
	 * @param string $table Table name
	 * @param int $id Page id (NOT USED! $this->pidSelect is used instead)
	 * @param string $addWhere Additional part for where clause
	 * @param string $fieldList Field list to select,
	 * 	for all (for "SELECT [fieldlist] FROM ...")
	 *
	 * @return array Returns query array
	 */
	public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*') {
		$database = $this->getDatabaseConnection();

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'] as $classRef) {
				$hookObjectsArr[] = GeneralUtility::getUserObj($classRef);
			}
		}

		// Set ORDER BY:
		$orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?
			'ORDER BY ' . $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] :
			$GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
		if ($this->sortField) {
			if (in_array($this->sortField, $this->makeFieldList($table, 1))) {
				$orderBy = 'ORDER BY ' . $table . '.' . $this->sortField;
				if ($this->sortRev) {
					$orderBy .= ' DESC';
				}
			}
		}

		// Set LIMIT:
		$limit = '';
		if ($this->iLimit) {
			$limit = ($this->firstElementNumber ? $this->firstElementNumber . ',' : '') . ($this->iLimit + 1);
		}

		// Filtering on displayable tx_commerce_categories (permissions):
		$pC = ($table == 'tx_commerce_categories' && $this->perms_clause) ? ' AND ' . $this->perms_clause : '';

		$categoryWhere = sprintf($this->addWhere[$table], $this->parentUid);

		// Adding search constraints:
		$search = $this->makeSearchString($table);

		// Compiling query array:
		$queryParts = array(
			'SELECT' => $fieldList,
			'FROM' => $table . $this->joinTables[$table],
			'WHERE' => $this->pidSelect .
				' ' . $pC .
				BackendUtility::deleteClause($table) .
				BackendUtility::versioningPlaceholderClause($table) .
				' ' . $addWhere . $categoryWhere .
				' ' . $search,
			'GROUPBY' => '',
			'ORDERBY' => $database->stripOrderBy($orderBy),
			'LIMIT' => $limit
		);

		// Apply hook as requested in http://bugs.typo3.org/view.php?id=4361
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'makeQueryArray_post')) {
				$parameter = array(
					'orderBy' => $orderBy,
					'limit' => $limit,
					'pC' => $pC,
					'search' => $search,
				);
				$hookObj->makeQueryArray_post($queryParts, $this, $table, $id, $addWhere, $fieldList, $parameter);
			}
		}

		// Return query:
		return $queryParts;
	}

	/**
	 * Returns a table-row with the content from the fields in the input data array.
	 * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
	 *
	 * @param int $h Is an int >=0 and denotes how tall a element is.
	 * 	Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join'
	 * 	and above makes 'line'
	 * @param string $icon Is the <img>+<a> of the record. If not supplied the
	 * 	first 'join'-icon will be a 'line' instead
	 * @param array $data Is the dataarray, record with the fields.
	 * 	Notice: These fields are (currently) NOT htmlspecialchar'ed before being
	 * 	wrapped in <td>-tags
	 * @param string $trParams Is insert in the <td>-tags.
	 * 	Must carry a ' ' as first character
	 * @param int|string $lMargin Obsolete - NOT USED ANYMORE.
	 * 	$lMargin is the leftMargin (int)
	 * @param string $altLine Is the HTML <img>-tag
	 * 	for an alternative 'gfx/ol/line.gif'-icon (used in the top)
	 *
	 * @return string HTML content for the table row
	 */
	public function addElement($h, $icon, array $data, $trParams = '', $lMargin = '', $altLine = '') {
		$noWrap = ($this->no_noWrap) ? '' : ' nowrap="nowrap"';

		// Start up:
		$out = '
		<!-- Element, begin: -->
		<tr ' . $trParams . '>';
		// Show icon and lines
		if ($this->showIcon) {
			$out .= '
			<td class="col-icon">';

			if (!$h) {
				$out .= '<img src="/' . TYPO3_mainDir . '/clear.gif" width="1" height="8" alt="" />';
			} else {
				for ($a = 0; $a < $h; $a++) {
					if (!$a) {
						if ($icon) {
							$out .= $icon;
						}
					}
				}
			}
			$out .= '</td>
			';
		}

		// Init rendering.
		$colsp = '';
		$lastKey = '';
		$c = 0;
		$ccount = 0;

		// Traverse field array which contains the data to present:
		foreach ($this->fieldArray as $vKey) {
			if (isset($data[$vKey])) {
				if ($lastKey) {
					$cssClass = $this->addElement_tdCssClass[$lastKey];
					if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
						$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
					}

					$out .= '
						<td' . $noWrap . ' class="' . $cssClass . '"' . $colsp .
							$this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</td>';
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
				$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
			}

			$out .= '
				<td' . $noWrap . ' class="' . $cssClass . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' .
					$data[$lastKey] .
				'</td>';
		}

		// End row
		$out .= '
		</tr>';

		// Return row.
		return $out;
	}

	/**
	 * Rendering the header row for a table
	 *
	 * @param string $table Table name
	 * @param array $currentIdList Array of the currently displayed uids of the table
	 *
	 * @return string Header table row
	 * @throws UnexpectedValueException If hook was of wrong interface
	 */
	public function renderListHeader($table, array $currentIdList) {
		$language = & $this->getLanguageService();

		// Init:
		$theData = Array();

		$icon = '';
		// Traverse the fields:
		foreach ($this->fieldArray as $fCol) {
			// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table == 'tx_commerce_categories' ? 2 : 16);

			switch ((string) $fCol) {
					// Path
				case '_PATH_':
					$theData[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels._PATH_', 1) . ']</i>';
					break;

					// References
				case '_REF_':
					$theData[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_mod_file_list.xml:c__REF_', 1) . ']</i>';
					break;

					// Path
				case '_LOCALIZATION_':
					$theData[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels._LOCALIZATION_', 1) . ']</i>';
					break;

					// Path
				case '_LOCALIZATION_b':
					$theData[$fCol] = $language->getLL('Localize', 1);
					break;

					// Clipboard:
				case '_CLIPBOARD_':
					$cells = array();

					// If there are elements on the clipboard for this table,
					// then display the "paste into" icon:
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable)) {
						$cells['pasteAfter'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl($table, $this->id)) .
							'" onclick="' .
							htmlspecialchars('return ' . $this->clipObj->confirmMsg('tx_commerce_categories', $this->pageRow, 'into', $elFromTable)) .
							'" title="' . $language->getLL('clip_paste', TRUE) . '">' .
							IconUtility::getSpriteIcon('actions-document-paste-after') . '</a>';
					}

					// If the numeric clipboard pads are enabled,
					// display the control icons for that:
					if ($this->clipObj->current != 'normal') {
							// The "select" link:
						$cells['copyMarked'] = $this->linkClipboardHeaderIcon(
							IconUtility::getSpriteIcon('actions-edit-copy', array('title' => $language->getLL('clip_selectMarked', TRUE))),
							$table,
							'setCB'
						);

							// The "edit marked" link:
						$editIdList = implode(',', $currentIdList);
						$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
						$params = '&edit[' . $table . '][' . $editIdList . ']=edit&disHelp=1';
						$cells['edit'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
							'">' .
							IconUtility::getSpriteIcon('actions-document-open', array('title' => $language->getLL('clip_editMarked', TRUE))) .
							'</a>';

							// The "Delete marked" link:
						$cells['delete'] = $this->linkClipboardHeaderIcon(
							IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $language->getLL('clip_deleteMarked', TRUE))),
							$table,
							'delete',
							sprintf($language->getLL('clip_deleteMarkedWarning'), $language->sL($GLOBALS['TCA'][$table]['ctrl']['title']))
						);

							// The "Select all" link:
						$cells['markAll'] = '<a class="cbcCheckAll" rel="" href="#" onclick="' .
							htmlspecialchars('checkOffCB(\'' . implode(',', $this->CBnames) . '\', this); return false;') .
							'" title="' . $language->getLL('clip_markRecords', TRUE) . '">' .
							IconUtility::getSpriteIcon('actions-document-select') . '</a>';
					} else {
						$cells['empty'] = '';
					}

					/**
					 * Render list header actions hook: Allows to change the clipboard icons
					 * @date 2007-11-20
					 * @request Bernhard Kraft  <krafbt@kraftb.at>
					 * @usage Above each listed table in Web>List a header row is shown.
					 * 	This hook allows to modify the icons responsible for the clipboard
					 * 	functions (shown above the clipboard checkboxes when a clipboard
					 * 	other than "Normal" is selected), or other "Action" functions which
					 * 	perform operations on the listed records.
					 */
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
							$hookObject = GeneralUtility::getUserObj($classData);
							if (!($hookObject instanceof \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface)) {
								throw new UnexpectedValueException(
									'$hookObject must implement interface \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface', 1195567850
								);
							}
							$cells = $hookObject->renderListHeaderActions($table, $currentIdList, $cells, $this);
						}
					}
					$theData[$fCol] = implode('', $cells);
					break;

					// Control panel:
				case '_CONTROL_':
					if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {
						// If new records can be created on this page, add links:
						if ($this->calcPerms & ($table == 'tx_commerce_categories' ? 8 : 16) && $this->showNewRecLink($table) && $this->parentUid) {
							if ($table == 'tt_content' && $this->newWizards) {
								// If mod.web_list.newContentWiz.overrideWithExtension is set,
								// use that extension's create new content wizard instead:
								$tmpTyposcriptConfig = BackendUtility::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
								$tmpTyposcriptConfig = $tmpTyposcriptConfig['properties']['newContentWiz.']['overrideWithExtension'];
								$newContentWizScriptPath = $this->backPath . ExtensionManagementUtility::isLoaded($tmpTyposcriptConfig) ?
									(ExtensionManagementUtility::extRelPath($tmpTyposcriptConfig) . 'mod1/db_new_content_el.php') :
									'sysext/cms/layout/db_new_content_el.php';

								$icon = '<a href="#" onclick="' .
									htmlspecialchars('return jumpExt(\'' . $newContentWizScriptPath . '?id=' . $this->id . '\');') .
									'" title="' . $language->getLL('new', TRUE) . '">' . (
										$table == 'tx_commerce_categories' ?
										IconUtility::getSpriteIcon('actions-page-new') :
										IconUtility::getSpriteIcon('actions-document-new')
									) . '</a>';
							} elseif ($table == 'tx_commerce_categories' && $this->newWizards) {
								$icon = '<a href="' . htmlspecialchars($this->backPath . 'db_new.php?id=' . $this->id .
									'&pagesOnly=1&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'))) .
									'" title="' . $language->getLL('new', TRUE) . '">' . (
										$table == 'tx_commerce_categories' ?
										IconUtility::getSpriteIcon('actions-page-new') :
										IconUtility::getSpriteIcon('actions-document-new')
									) . '</a>';
							} else {
								$parameters = '&edit[' . $table . '][' . $this->id . ']=new';
								if ($table == 'pages_language_overlay') {
									$parameters .= '&overrideVals[pages_language_overlay][doktype]=' . (int) $this->pageRow['doktype'];
								}
								switch ($table) {
									case 'tx_commerce_categories':
										$parameters .= '&defVals[tx_commerce_categories][parent_category]=' . $this->parentUid;
										break;

									case 'tx_commerce_products':
										$parameters .= '&defVals[tx_commerce_products][categories]=' . $this->parentUid;
										break;

									default:
								}
								$icon = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($parameters, $this->backPath, -1)) .
									'" title="' . $language->getLL('new', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-new') . '</a>';
							}
						}

						// If the table can be edited, add link for editing
						// ALL SHOWN fields for all listed records:
						if ($permsEdit && $this->table && is_array($currentIdList)) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . implode(',', $this->fieldArray) . '&disHelp=1';
							$icon .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
								'" title="' . $language->getLL('editShownColumns', TRUE) . '">' .
								IconUtility::getSpriteIcon('actions-document-open') . '</a>';
						}

							// add an empty entry, so column count fits again after moving this into $icon
						$theData[$fCol] = '&nbsp;';
					}
					break;

					// space column
				case '_AFTERCONTROL_':
					// space column
				case '_AFTERREF_':
					$theData[$fCol] = '&nbsp;';
					break;

					// Regular fields header:
				default:
					$theData[$fCol] = '';
					if ($this->table && is_array($currentIdList)) {
						// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol] .= '<a href="' . htmlspecialchars($this->listURL('', -1) .
								'&duplicateField=' . $fCol) . '" title="' . $language->getLL('clip_duplicates', TRUE) . '">' .
								IconUtility::getSpriteIcon('actions-document-duplicates-select') . '</a>';
						}

						// If the table can be edited, add link for editing THIS field for all records:
						if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol . '&disHelp=1';
							$iTitle = sprintf(
								$language->getLL('editThisColumn'),
								rtrim(trim($language->sL(BackendUtility::getItemLabel($table, $fCol))), ':')
							);
							$theData[$fCol] .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
								'" title="' . htmlspecialchars($iTitle) . '">' .
								IconUtility::getSpriteIcon('actions-document-open') . '</a>';
						}
					}
					$theData[$fCol] .= $this->addSortLink(
						$language->sL(BackendUtility::getItemLabel($table, $fCol, '<i>[|]</i>')),
						$fCol,
						$table
					);
			}
		}

		/**
		 * Above each listed table in Web>List a header row is shown.
		 * Containing the labels of all shown fields and additional icons to
		 * create new records for this table or perform special clipboard tasks
		 * like mark and copy all listed records to clipboard, etc.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!($hookObject instanceof \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface)) {
					throw new UnexpectedValueException(
						'$hookObject must implement interface \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface', 1195567855
					);
				}
				$theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
			}
		}

			// Create and return header table row:
		return $this->addelement(1, $icon, $theData, ' class="c-headLine"', '');
	}

	/**
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param string $table The table
	 * @param array $row The record for which to make the control panel.
	 *
	 * @return string HTML table with the control panel (unless disabled)
	 * @throws UnexpectedValueException If hook was of wrong interface
	 */
	public function makeControl($table, array $row) {
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();

		if ($this->dontShowClipControlPanels) {
			return '';
		}
		$rowUid = $row['uid'];
		if (ExtensionManagementUtility::isLoaded('version') && isset($row['_ORIG_uid'])) {
			$rowUid = $row['_ORIG_uid'];
		}
		$cells = array();
		// If the listed table is 'pages' we have to request
		// the permission settings for each page:
		$localCalcPerms = 0;
		if ($table == 'tx_commerce_categories' || $table == 'tx_commerce_products') {
			/**
			 * Utility
			 *
			 * @var \CommerceTeam\Commerce\Utility\BackendUserUtility $utility
			 */
			$utility = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Utility\\BackendUserUtility');
			$localCalcPerms = $utility->calcPerms((array) BackendUtility::getRecord('tx_commerce_categories', $this->parentUid));
		}
		// This expresses the edit permissions for this particular element:
		$permsEdit = $table == 'tx_commerce_categories' && $localCalcPerms & 2 ||
			$table != 'tx_commerce_categories' && $this->calcPerms & 16;
		// "Show" link (only tx_commerce_categories and tx_commerce_products elements)
		if ($table == 'tx_commerce_categories' || $table == 'tx_commerce_products') {
			$cells['view'] = '<a href="#" onclick="' .
				htmlspecialchars(
					BackendUtility::viewOnClick(
						($table === 'tx_commerce_products' ? $this->id : $row['uid']),
						$this->backPath,
						'',
						($table === 'tx_commerce_products' ? '#' . $row['uid'] : '')
					)
				) . '" title="' . $language->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">' .
				IconUtility::getSpriteIcon('actions-document-view') . '</a>';
		} elseif (!$this->table) {
			$cells['view'] = $this->spaceIcon;
		}
		// "Edit" link: ( Only if permissions to edit the page-record of
		// the content of the parent page ($this->id)
		if ($permsEdit) {
			$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
			$cells['edit'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
				'" title="' . $language->getLL('edit', TRUE) . '">' .
				(
					$GLOBALS['TCA'][$table]['ctrl']['readOnly'] ?
					IconUtility::getSpriteIcon('actions-document-open-read-only') :
					IconUtility::getSpriteIcon('actions-document-open')
				) .
				'</a>';
		} elseif (!$this->table) {
			$cells['edit'] = $this->spaceIcon;
		}

		// "Move" wizard link for tx_commerce_categories/tx_commerce_products elements:
		if (($table == 'tx_commerce_products' && $permsEdit) || ($table == 'tx_commerce_categories')) {
			$options = array('title' => $language->getLL('move_' . ($table == 'tx_commerce_products' ? 'record' : 'page'), TRUE));
			$cells['move'] = '<a href="#" onclick="' .
				htmlspecialchars('return jumpExt(\'' . $this->backPath . 'move_el.php?table=' . $table . '&uid=' . $row['uid'] . '\');') .
				'">' .
				($table == 'tx_commerce_products' ?
					IconUtility::getSpriteIcon('actions-document-move', $options) :
					IconUtility::getSpriteIcon('actions-page-move', $options)
				) .
				'</a>';
		} elseif (!$this->table) {
			$cells['move'] = $this->spaceIcon;
		}

		// If the extended control panel is enabled OR if we are seeing a single table:
		if ($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] || $this->table) {
			// "Info": (All records)
			$cells['viewBig'] = '<a href="#" onclick="' .
				htmlspecialchars('top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;') .
				'" title="' . $language->getLL('showInfo', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-info') .
				'</a>';

			// If the table is NOT a read-only table, then show these links:
			if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {
				// "Revert" link (history/undo)
				$cells['history'] = '<a href="#" onclick="' .
					htmlspecialchars(
						'return jumpExt(\'' . $this->backPath . 'show_rechis.php?element=' .
						rawurlencode($table . ':' . $row['uid']) . '\',\'#latest\');'
					) .
					'" title="' . $language->getLL('history', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-history-open') .
					'</a>';

				// Versioning:
				if (ExtensionManagementUtility::isLoaded('version') && !ExtensionManagementUtility::isLoaded('workspaces')) {
					$vers = BackendUtility::selectVersionsOfRecord($table, $row['uid'], 'uid', $this->getBackendUser()->workspace, FALSE, $row);
					// If table can be versionized.
					if (is_array($vers)) {
						$versionIcon = 'no-version';
						if (count($vers) > 1) {
							$versionIcon = count($vers) - 1;
						}

						$cells['version'] = '<a href="' . htmlspecialchars($this->backPath . ExtensionManagementUtility::extRelPath('version') .
							'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . rawurlencode($row['uid'])) . '" title="' .
							$language->getLL('displayVersions', TRUE) . '">' . IconUtility::getSpriteIcon('status-version-' . $versionIcon) .
							'</a>';
					} elseif (!$this->table) {
						$cells['version'] = $this->spaceIcon;
					}
				}

				// "Edit Perms" link:
				if (
					$table == 'tx_commerce_categories'
					&& $backendUser->check('modules', 'txcommerceM1_permission')
				) {
					$cells['perms'] = '<a href="' .
						htmlspecialchars(
							BackendUtility::getModuleUrl('txcommerceM1_permission') . '&control[tx_commerce_categories][uid]=' . $row['uid'] .
							'&return_id=' . $row['uid'] . '&edit=1'
						) .
						'">' .
						IconUtility::getSpriteIcon('status-status-locked', array('titel' => $language->getLL('permissions', TRUE))) .
						'</a>';
				} elseif (!$this->table && $backendUser->check('modules', 'web_perm')) {
					$cells['perms'] = $this->spaceIcon;
				}

				// "New record after" link (ONLY if the records in the table are sorted
				// by a "sortby"-row or if default values can depend on previous record):
				if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) {
					if (
						// For NON-tx_commerce_categories, must have permission to edit content
						($table != 'tx_commerce_categories' && ($this->calcPerms & 16))
						// For tx_commerce_categories, must have permission to create new
						|| ($table == 'tx_commerce_categories' && ($this->calcPerms & 8))
					) {
						if ($this->showNewRecLink($table) && $this->parentUid) {
							$params = '&edit[' . $table . '][' . ( - ($row['_MOVE_PLH'] ? $row['_MOVE_PLH_uid'] : $row['uid'])) . ']=new';
							$options = array('title' => $language->getLL('new' . ($table == 'tx_commerce_categories' ? 'Category' : 'Record'), TRUE));
							$cells['new'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
								'"' .
								($table == 'tx_commerce_categories' ?
									IconUtility::getSpriteIcon('actions-page-new', $options) :
									IconUtility::getSpriteIcon('actions-document-new', $options)
								) .
								'</a>';
						}
					}
				} elseif (!$this->table) {
					$cells['new'] = $this->spaceIcon;
				}

					// "Up/Down" links
				if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
						// Up
					if (isset($this->currentTable['prev'][$row['uid']])) {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prev'][$row['uid']];
						$cells['moveUp'] = '<a href="#" onclick="' .
							htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') .
							'" title="' . $language->getLL('moveUp', TRUE) . '">' . IconUtility::getSpriteIcon('actions-move-up') .
							'</a>';
					} else {
						$cells['moveUp'] = $this->spaceIcon;
					}
						// Down
					if ($this->currentTable['next'][$row['uid']]) {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['next'][$row['uid']];
						$cells['moveDown'] = '<a href="#" onclick="' .
							htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') .
							'" title="' . $language->getLL('moveDown', TRUE) . '">' . IconUtility::getSpriteIcon('actions-move-down') .
							'</a>';
					} else {
						$cells['moveDown'] = $this->spaceIcon;
					}
				} elseif (!$this->table) {
					$cells['moveUp']  = $this->spaceIcon;
					$cells['moveDown'] = $this->spaceIcon;
				}

				// "Hide/Unhide" links:
				$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
				if (
					$permsEdit
					&& $hiddenField
					&& $GLOBALS['TCA'][$table]['columns'][$hiddenField]
					&& (
						!$GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude']
						|| $backendUser->check('non_exclude_fields', $table . ':' . $hiddenField)
					)
				) {
					if ($row[$hiddenField]) {
						$params = '&data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=0';
						$cells['hide'] = '<a href="#" onclick="' .
							htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') .
							'" title="' . $language->getLL('unHide' . ($table == 'tx_commerce_categories' ? 'Category' : ''), TRUE) . '">' .
							IconUtility::getSpriteIcon('actions-edit-unhide') .
							'</a>';
					} else {
						$params = '&data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=1';
						$cells['hide'] = '<a href="#" onclick="' .
							htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') .
							'" title="' . $language->getLL('hide' . ($table == 'tx_commerce_categories' ? 'Category' : ''), TRUE) . '">' .
							IconUtility::getSpriteIcon('actions-edit-hide') .
							'</a>';
					}
				} elseif (!$this->table) {
					$cells['hide'] = $this->spaceIcon;
				}

				// "Delete" link:
				if (
					($table == 'tx_commerce_categories' && ($localCalcPerms & 4))
					|| ($table != 'tx_commerce_categories' && ($this->calcPerms & 16))
				) {
					$titleOrig = BackendUtility::getRecordTitle($table, $row, FALSE, TRUE);
					$title = GeneralUtility::slashJS(GeneralUtility::fixed_lgd_cs($titleOrig, $this->fixedL), 1);
					$params = '&cmd[' . $table . '][' . $row['uid'] . '][delete]=1';

					$refCountMsg = BackendUtility::referenceCount(
						$table,
						$row['uid'],
						' ' . $language->sL('LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'),
						$this->getReferenceCount($table, $row['uid'])
					) . BackendUtility::translationCount(
						$table,
						$row['uid'],
						' ' . $language->sL('LLL:EXT:lang/locallang_core.xml:labels.translationsOfRecord')
					);

					$cells['delete'] = '<a href="#" onclick="' .
						htmlspecialchars(
							'if (confirm(' . $language->JScharCode($language->getLL('deleteWarning') . ' "' . $title . '" ' . $refCountMsg
						) . ')) {jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');} return false;') .
						'">' .
						IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $language->getLL('delete', TRUE))) .
						'</a>';
				} elseif (!$this->table) {
					$cells['delete'] = $this->spaceIcon;
				}

				// "Levels" links: Moving pages into new levels...
				// @todo make moving left and right working with custom wizard
				if ($permsEdit && $table == 'pages' && !$this->searchLevels) {
						// Up (Paste as the page right after the current parent page)
					if ($this->calcPerms & 8) {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . - $this->id;
						$cells['moveLeft'] = '<a href="#" onclick="' .
							htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') .
							'" title="' . $language->getLL('prevLevel', TRUE) . '">' . IconUtility::getSpriteIcon('actions-move-left') . '</a>';
					}
						// Down (Paste as subpage to the page right above)
					if ($this->currentTable['prevUid'][$row['uid']]) {
						$localCalcPerms = $backendUser->calcPerms(
							BackendUtility::getRecord('tx_commerce_categories',
							$this->currentTable['prevUid'][$row['uid']])
						);
						if ($localCalcPerms & 8) {
							$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prevUid'][$row['uid']];
							$cells['moveRight'] = '<a href="#" onclick="' .
								htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') .
								'" title="' . $language->getLL('nextLevel', TRUE) . '">' .
								IconUtility::getSpriteIcon('actions-move-right') .
								'</a>';
						} else {
							$cells['moveRight'] = $this->spaceIcon;
						}
					} else {
						$cells['moveRight'] = $this->spaceIcon;
					}
				} elseif (!$this->table) {
					$cells['moveLeft'] = $this->spaceIcon;
					$cells['moveRight'] = $this->spaceIcon;
				}
			}
		}

		/**
		 * Record stat info hooks: Allows to insert HTML
		 * 	before record icons on various places
		 * @date 2007-09-22
		 * @request Kasper Skårhøj <kasper2007@typo3.com>
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
			$stat = '';
			$parameter = array($table, $row['uid']);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $functionReference) {
				$stat .= GeneralUtility::callUserFunction($functionReference, $parameter, $this);
			}
			$cells['stat'] = $stat;
		}

		/**
		 * Make control Hook: Allows to change control icons of records in list-module
		 * @date 2007-11-20
		 * @request Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage This hook method gets passed the current $cells
		 * 	array as third parameter. This array contains values for the
		 * 	icons/actions generated for each record in Web>List.
		 * 	Each array entry is accessible by an index-key. The order of the
		 * 	icons is dependend on the order of those array entries.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!($hookObject instanceof \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface)) {
					throw new UnexpectedValueException(
						'$hookObject must implement interface \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface', 1195567840
					);
				}
				$cells = $hookObject->makeControl($table, $row, $cells, $this);
			}
		}

			// Compile items into a DIV-element:
		return '
			<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->
			<div class="typo3-DBctrl">' . implode('', $cells) . '</div>';
	}

	/**
	 * Creates the clipboard panel for a single record in the listing.
	 *
	 * @param string $table The table
	 * @param array $row The record for which to make the clipboard panel.
	 *
	 * @return string HTML table with the clipboard panel (unless disabled)
	 * @throws UnexpectedValueException If hook was of wrong interface
	 */
	public function makeClip($table, array $row) {
		$language = $this->getLanguageService();

		// Return blank, if disabled:
		if ($this->dontShowClipControlPanels) {
			return '';
		}

		$cells = array();

		$cells['pasteAfter'] = $cells['pasteInto'] = $this->spaceIcon;
		// enables to hide the copy, cut and paste icons for localized records
		// - doesn't make much sense to perform these options for them
		$isL10nOverlay = $this->localizationView && $table != 'pages_language_overlay' &&
			$row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
		// Return blank, if disabled:
		// Whether a numeric clipboard pad is active or the normal
		// pad we will see different content of the panel:
		// For the "Normal" pad:
		if ($this->clipObj->current == 'normal') {
			// Show copy/cut icons:
			$isSel = (string) $this->clipObj->isSelected($table, $row['uid']);
			$cells['copy'] = $isL10nOverlay ? $this->spaceIcon : '<a href="#" onclick="' .
				htmlspecialchars(
					'return jumpSelf(\'' .
					$this->clipObj->selUrlDB($table, $row['uid'], 1, ($isSel == 'copy'), array('returnUrl' => '')) . '\');'
				) .
				'" title="' . $language->sL('LLL:EXT:lang/locallang_core.php:cm.copy', TRUE) . '">' .
				((!$isSel == 'copy') ?
					IconUtility::getSpriteIcon('actions-edit-copy') :
					IconUtility::getSpriteIcon('actions-edit-copy-release')
				) .
				'</a>';
			$cells['cut'] = $isL10nOverlay ? $this->spaceIcon : '<a href="#" onclick="' .
				htmlspecialchars(
					'return jumpSelf(\'' .
					$this->clipObj->selUrlDB($table, $row['uid'], 0, ($isSel == 'cut'), array('returnUrl' => '')) . '\');'
				) .
				'" title="' . $language->sL('LLL:EXT:lang/locallang_core.php:cm.cut', TRUE) . '">' .
				((!$isSel == 'cut') ?
					IconUtility::getSpriteIcon('actions-edit-cut') :
					IconUtility::getSpriteIcon('actions-edit-cut-release')
				) .
				'</a>';
		// For the numeric clipboard pads (showing checkboxes
		// where one can select elements on/off)
		} else {
			// Setting name of the element in ->CBnames array:
			$n = $table . '|' . $row['uid'];
			$this->CBnames[] = $n;

			// Check if the current element is selected and if so,
			// prepare to set the checkbox as selected:
			$checked = ($this->clipObj->isSelected($table, $row['uid']) ? ' checked="checked"' : '');

			// If the "duplicateField" value is set then select all
			// elements which are duplicates...
			if ($this->duplicateField && isset($row[$this->duplicateField])) {
				$checked = '';
				if (in_array($row[$this->duplicateField], $this->duplicateStack)) {
					$checked = ' checked="checked"';
				}
				$this->duplicateStack[] = $row[$this->duplicateField];
			}

			// Adding the checkbox to the panel:
			$cells['select'] = $isL10nOverlay ?
				$this->spaceIcon :
				'<input type="hidden" name="CBH[' . $n . ']" value="0" /><input type="checkbox" name="CBC[' . $n .
				']" value="1" class="smallCheckboxes"' . $checked . ' />';
		}

		// Now, looking for selected elements from the current table:
		$elFromTable = $this->clipObj->elFromTable($table);
		// IF elements are found and they can be individually ordered,
		// then add a "paste after" icon:
		if (count($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
			$cells['pasteAfter'] = $isL10nOverlay ? $this->spaceIcon : '<a href="' .
				htmlspecialchars($this->clipObj->pasteUrl($table, - $row['uid'])) . '" onclick="' .
				htmlspecialchars('return ' . $this->clipObj->confirmMsg($table, $row, 'after', $elFromTable)) .
				'" title="' . $language->getLL('clip_pasteAfter', TRUE) . '">' .
				IconUtility::getSpriteIcon('actions-document-paste-after') .
				'</a>';
		}

		// Now, looking for elements in general:
		$elFromTable = $this->clipObj->elFromTable('');
		if ($table == 'tx_commerce_categories' && count($elFromTable)) {
			$cells['pasteInto'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) .
				'" onclick="' . htmlspecialchars('return ' . $this->clipObj->confirmMsg($table, $row, 'into', $elFromTable)) .
				'">' .
				IconUtility::getSpriteIcon('actions-document-paste-into', array('title' => $language->getLL('clip_pasteInto', TRUE))) .
				'</a>';
		}

		/**
		 * Make clip hook: Allows to change clip-icons of records in list-module
		 *
		 * @date 2007-11-20
		 * @request Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage This hook method gets passed the current $cells array as third
		 * 	parameter. This array contains values for the clipboard icons generated
		 * 	for each record in Web>List. Each array entry is accessible by an
		 * 	index-key. The order of the icons is dependend on the order of those
		 * 	array entries.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!($hookObject instanceof \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface)) {
					throw new UnexpectedValueException(
						'$hookObject must implement interface \TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface', 1195567845
					);
				}
				$cells = $hookObject->makeClip($table, $row, $cells, $this);
			}
		}

			// Compile items into a DIV-element:
		return '
			<!-- CLIPBOARD PANEL: ' . $table . ':' . $row['uid'] . ' -->
			<div class="typo3-clipCtrl">' . implode('', $cells) . '</div>';
	}

	/**
	 * Creates the localization panel
	 *
	 * @param string $table The table
	 * @param array $row The record for which to make the localization panel.
	 *
	 * @return array Array with key 0/1 with content for column 1 and 2
	 */
	public function makeLocalizationPanel($table, array $row) {
		$backendUser = $this->getBackendUser();

		$out = array(0 => '', 1 => '');

		$this->translations = array();
		$translations = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $this->selFieldList);

			// Language title and icon:
		$out[0] = $this->languageFlag($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);

		if (is_array($translations)) {
			$this->translations = $translations['translations'];
			// Traverse page translations and add icon
			// for each language that does NOT yet exist:
			$lNew = '';
			foreach (array_keys($this->pageOverlays) as $overlayUid) {
				if (!isset($translations['translations'][$overlayUid]) && $backendUser->checkLanguageAccess($overlayUid)) {
					$params = '&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $overlayUid;
					$language = BackendUtility::getRecord('sys_language', $overlayUid, 'title');
					if ($this->languageIconTitles[$overlayUid]['flagIcon']) {
						$lC = IconUtility::getSpriteIcon($this->languageIconTitles[$overlayUid]['flagIcon']);
					} else {
						$lC = $this->languageIconTitles[$overlayUid]['title'];
					}
					$lC = '<a href="#" onclick="' .
						htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') .
						'" title="' . htmlspecialchars($language['title']) . '">' . $lC . '</a> ';

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
	 * to be overridden to set the url to $this->script
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
	 * 	Enter blank string for the current id ($this->id)
	 * @param string $table Tablename to display. Enter "-1" for the current table.
	 * @param string $excludeList Commalist of fields
	 * 	NOT to include ("sortField" or "sortRev")
	 *
	 * @return string URL
	 */
	public function listURL($altId = '', $table = '-1', $excludeList = '') {
		$urlParameters = array();
		if (strcmp($altId, '')) {
			$urlParameters['id'] = $altId;
		} else {
			$urlParameters['id'] = $this->id;
		}
		if ($this->parentUid) {
			$urlParameters['control']['tx_commerce_categories']['uid'] = $this->parentUid;
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
		if ($this->searchString) {
			$urlParameters['search_field'] = $this->searchString;
		}
		if ($this->searchLevels) {
			$urlParameters['search_levels'] = $this->searchLevels;
		}
		if ($this->showLimit) {
			$urlParameters['showLimit'] = $this->showLimit;
		}
		if ((!$excludeList || !GeneralUtility::inList($excludeList, 'firstElementNumber')) && $this->firstElementNumber) {
			$urlParameters['pointer'] = $this->firstElementNumber;
		}
		if ((!$excludeList || !GeneralUtility::inList($excludeList, 'sortField')) && $this->sortField) {
			$urlParameters['sortField'] = $this->sortField;
		}
		if ((!$excludeList || !GeneralUtility::inList($excludeList, 'sortRev')) && $this->sortRev) {
			$urlParameters['sortRev'] = $this->sortRev;
		}

		return $this->script . GeneralUtility::implodeArrayForUrl('', $urlParameters, '', TRUE);
	}

	/**
	 * Returns the title (based on $code) of a record (from table $table) with
	 * the proper link around (that is for 'tx_commerce_categories'-records
	 * a link to the level of that record...)
	 *
	 * @param string $table Table name
	 * @param int $uid Item uid
	 * @param string $code Item title (not htmlspecialchars()'ed yet)
	 * @param array $row Item row
	 *
	 * @return string The item title. Ready for HTML output
	 */
	public function linkWrapItems($table, $uid, $code, array $row) {
		$language = $this->getLanguageService();
		$backendUser = $this->getBackendUser();

		// If the title is blank, make a "no title" label:
		if (!strcmp($code, '')) {
			$code = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.no_title', 1) . ']</i> - ' .
				htmlspecialchars(GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $row), $backendUser->uc['titleLen']));
		} else {
			$code = htmlspecialchars(GeneralUtility::fixed_lgd_cs($code, $this->fixedL));
		}

		switch ((string) $this->clickTitleMode) {
			case 'edit':
				// If the listed table is 'tx_commerce_categories' we have to
				// request the permission settings for each page:
				if ($table == 'tx_commerce_categories') {
					$localCalcPerms = $backendUser->calcPerms(BackendUtility::getRecord('tx_commerce_categories', $row['uid']));
					$permsEdit = $localCalcPerms & 2;
				} else {
					$permsEdit = $this->calcPerms & 16;
				}

				// "Edit" link: ( Only if permissions to edit the page-record
				// of the content of the parent page ($this->id)
				if ($permsEdit) {
					$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
					$code = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
						'" title="' . $language->getLL('edit', 1) . '">' . $code . '</a>';
				}
				break;

			case 'show':
				// "Show" link (only tx_commerce_categories and tx_commerce_products elements)
				if ($table == 'tx_commerce_categories' || $table == 'tx_commerce_products') {
					$code = '<a href="#" onclick="' .
						htmlspecialchars(
							BackendUtility::viewOnClick($table == 'tx_commerce_products' ? $this->id . '#' . $row['uid'] : $row['uid'])
						) .
						'" title="' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . '">' . $code . '</a>';
				}
				break;

			case 'info':
				// "Info": (All records)
				$code = '<a href="#" onclick="' .
					htmlspecialchars('top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;') .
					'" title="' . $language->getLL('showInfo', 1) . '">' . $code . '</a>';
				break;

			default:
					// Output the label now:
				if ($table == 'tx_commerce_categories') {
					$code = '<a href="' . htmlspecialchars($this->listURL($uid, '')) . '">' . $code . '</a>';
				}
		}

		return $code;
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

	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Get document template
	 *
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}
}

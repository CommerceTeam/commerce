<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Daniel Schöttgen <ds@marketing-factory.de>
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Renders order list in the BE order module
 */
class Tx_Commerce_ViewHelpers_OrderRecordList extends localRecordList {
	/**
	 * @var integer
	 */
	public $orderPid;

	/**
	 * @var integer
	 */
	public $alternateBgColors = 1;

	/**
	 * @var string
	 */
	public $additionalOutTop;

	/**
	 * @var integer
	 */
	public $onlyUser;

	/**
	 * @var array
	 */
	protected $defaultFieldArrayFull = array(
		'articles', 'order_type_uid_noName', 'order_id', 'tstamp', 'crdate', 'delivery', 'payment',
		'numarticles', 'sum_price_gross', 'cu_iso_3', 'company', 'surname', 'name',
		'address', 'zip', 'city', 'email', 'phone_1', 'phone_2', 'order_number');

	/**
	 * @var array
	 */
	protected $defaultFieldArray = array(
		'order_type_uid_noName', 'tstamp', 'numarticles', 'sum_price_gross', 'cu_iso_3', 'company', 'name', 'email', 'phone_1');

	/**
	 * @var boolean
	 */
	public $disableSingleTableView;

	/**
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @param array $row
	 * @return array all available buttons as an assoc. array
	 */
	public function getButtons($row) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$buttons = array(
			'csh' => '',
				// group left 1
			'level_up' => '',
			'back' => '',
				// group left 2
			'new_record' => '',
			'paste' => '',
				// group left 3
			'view' => '',
			'edit' => '',
			'move' => '',
			'hide_unhide' => '',
				// group left 4
			'csv' => '',
			'export' => '',
				// group right 1
			'cache' => '',
			'reload' => '',
			'shortcut' => '',
		);

		if ($this->id) {
				// Setting title of page + the "Go up" link:
			$buttons['level_up'] = '<a href="' . htmlspecialchars($this->listURL($row['pid'])) . '" onclick="setHighlight(' . $row['pid'] . ')">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-view-go-up',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', 1))
				) .
				'</a>';
		}

			// Add "CSV" link, if a specific table is shown:
		if ($this->table) {
			$buttons['csv'] = '<a href="' . htmlspecialchars($this->listURL() . '&csv=1') . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'mimetypes-text-csv',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.csv', 1))
				) . '</a>';
		}

			// Add "Export" link, if a specific table is shown:
		if ($this->table && t3lib_extMgm::isLoaded('impexp')) {
			$buttons['export'] = '<a href="' . htmlspecialchars($this->backPath . t3lib_extMgm::extRelPath('impexp') .
				'app/index.php?tx_impexp[action]=export&tx_impexp[list][]=' . rawurlencode($this->table . ':' . $this->id)) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-document-export-t3d',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:rm.export', 1))
				) . '</a>';
		}

			// Add "refresh" link:
		$buttons['reload'] = '<a href="' . htmlspecialchars($this->listURL()) . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-system-refresh', array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.reload', 1))) .
			'</a>';

		return $buttons;
	}

	/**
	 * @param string $table
	 * @param integer $id
	 * @param string $rowlist
	 * @throws UnexpectedValueException
	 * @return string
	 */
	public function getTable($table, $id, $rowlist) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Loading all TCA details for this table:
		t3lib_div::loadTCA($table);
		t3lib_div::loadTCA('tx_commerce_order_types');

			// Init
		$addWhere = '';
		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField']
			&& $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
			&& !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];
		$tableCollapsed = (!$this->tablesCollapsed[$table]) ? FALSE : TRUE;

			// prepare space icon
		$this->spaceIcon = t3lib_iconWorks::getSpriteIcon('empty-empty', array('style' => 'background-position: 0 10px;'));

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray = array();
			// Add title column
		$this->fieldArray[] = $titleCol;

			// Control-Panel
		if (!t3lib_div::inList($rowlist, '_CONTROL_')) {
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
			// @todo need custom fieldlist
			// Cleaning up:

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['showArticleNumber'] == 1) {
			$this->defaultFieldArray[] = 'article_number';
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['showArticleTitle'] == 1) {
			$this->defaultFieldArray[] = 'article_name';
		}
		$this->fieldArray = array_merge($this->fieldArray, $this->defaultFieldArray);

		$this->fieldArray = array_unique(array_merge($this->fieldArray, t3lib_div::trimExplode(',', $rowlist, 1)));
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
			if (t3lib_extMgm::isLoaded('cms')) {
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
				// Filtered out when pages in makeFieldList()
			$selectFields[] = 't3ver_swapmode';
		}
		if ($l10nEnabled) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
			$selectFields = array_merge($selectFields, t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1));
		}

			// @todo do we need this?
		if ($this->onlyUser) {
			$addWhere .= ' AND cust_fe_user = \'' . $this->onlyUser . '\' ';
		}

			// Unique list!
		$selectFields = array_unique($selectFields);
			// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selectFields = array_intersect($selectFields, $this->makeFieldList($table, 1));
			// implode it into a list of fields for the SQL-statement.
		$selFieldList = implode(',', $selectFields);
		$this->selFieldList = $selFieldList;

		/**
		 * @hook DB-List getTable
		 * @date 2007-11-16
		 * @request Malte Jansen <mail@maltejansen.de>
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);

				if (!($hookObject instanceof t3lib_localRecordListGetTableHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface t3lib_localRecordListGetTableHook', 1195114460);
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

			// Finding the total amount of records on the page (API function from class.db_list.inc)
		$this->setTotalItems($queryParts);

			// Init:
		$dbCount = 0;
		$out = '';
		$listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;

			// If the count query returned any number of records, we perform the real query, selecting records.
		$result = FALSE;
		if ($this->totalItems) {
				// Fetch records only if not in single table mode or if in multi table mode and not collapsed
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
				$theData = Array();
				if (!$this->table && !$rowlist) {
					$theData[$titleCol] = '<img src="' . $this->backPath . 'clear.gif" width="' . ($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] ? '230' : '350') . '" height="1" alt="" />';
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
					t3lib_BEfunc::wrapInHelp($table, '', $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE)) .
					'</span> (' . $this->totalItems . ')';
			} else {
				$theData[$titleCol] = $this->linkWrapTable(
					$table,
					'<span class="c-table">' . $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE) . '</span> (' . $this->totalItems . ') ' .
						($this->table ?
							t3lib_iconWorks::getSpriteIcon('actions-view-table-collapse', array('title' => $language->getLL('contractView', TRUE))) :
							t3lib_iconWorks::getSpriteIcon('actions-view-table-expand', array('title' => $language->getLL('expandView', TRUE))))
				);
			}

			if ($listOnlyInSingleTableMode) {
				$out .= '
					<tr>
						<td class="t3-row-header" style="width:95%;">' . t3lib_BEfunc::wrapInHelp($table, '', $theData[$titleCol]) . '</td>
					</tr>';
			} else {
					// Render collapse button if in multi table mode
				$collapseIcon = '';
				if (!$this->table) {
					$collapseIcon = '<a href="' . htmlspecialchars($this->listURL() . '&collapse[' . $table . ']=' . ($tableCollapsed ? '0' : '1')) . '" title="' . ($tableCollapsed ? $language->sL('LLL:EXT:lang/locallang_core.php:labels.expandTable', TRUE) : $language->sL('LLL:EXT:lang/locallang_core.php:labels.collapseTable', TRUE)) . '">' .
							($tableCollapsed ? t3lib_iconWorks::getSpriteIcon('actions-view-list-expand', array('class' => 'collapseIcon')) : t3lib_iconWorks::getSpriteIcon('actions-view-list-collapse', array('class' => 'collapseIcon'))) .
						'</a>';
				}
				$out .= $this->addElement(1, $collapseIcon, $theData, ' class="t3-row-header"', '');
			}

			$iOut = '';
				// Render table rows only if in multi table view and not collapsed or if in single table view
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
				while ($row = $database->sql_fetch_assoc($result)) {

						// In offline workspace, look for alternative record:
					t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace, TRUE);

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

							// If localization view is enabled it means that the selected records are either default or All language and here we will not select translations which point to the main record:
						if ($this->localizationView && $l10nEnabled) {
								// For each available translation, render the record:
							if (is_array($this->translations)) {
								foreach ($this->translations as $lRow) {
										// $lRow isn't always what we want - if record was moved we've to work with the placeholder records otherwise the list is messed up a bit
									if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
										$tmpRow = t3lib_BEfunc::getRecordRaw($table, 't3ver_move_id="' . intval($lRow['uid']) . '" AND pid="' . $row['_MOVE_PLH_pid'] . '" AND t3ver_wsid=' . $row['t3ver_wsid'] . t3lib_beFunc::deleteClause($table), $selFieldList);
										$lRow = is_array($tmpRow)?$tmpRow:$lRow;
									}
										// In offline workspace, look for alternative record:
									t3lib_BEfunc::workspaceOL($table, $lRow, $GLOBALS['BE_USER']->workspace, TRUE);
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

					// Record navigation is added to the beginning and end of the table if in single table mode
				if ($this->table) {
					$iOut = $this->renderListNavigation('top') . $iOut . $this->renderListNavigation('bottom');
				} else {
						// show that there are more records than shown
					if ($this->totalItems > $this->itemsLimitPerTable) {
						$countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ? $this->itemsLimitSingleTable : $this->totalItems;
						$hasMore = ($this->totalItems > $this->itemsLimitSingleTable);
						$iOut .= '<tr><td colspan="' . count($this->fieldArray) . '" style="padding:5px;">
							<a href="' . htmlspecialchars($this->listURL() . '&table=' . rawurlencode($table)) . '"><img' .
							t3lib_iconWorks::skinImg($this->backPath, 'gfx/pildown.gif', 'width="14" height="14"') .
							' alt="" /> <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
							</td></tr>';
						}

				}

					// The header row for the table is now created:
				$out .= $this->renderListHeader($table, $currentIdList);
			}

				// The list of records is added after the header:
			$out .= $iOut;
			unset($iOut);

				// Build the selector
			$moveToSelector = $this->renderMoveToSelector($table);

				// ... and it is all wrapped in a table:
			$out = '



			<!--
				DB listing of elements:	"' . htmlspecialchars($table) . '"
			-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist' . ($listOnlyInSingleTableMode ? ' typo3-dblist-overview' : '') . '">
					' . $out . $moveToSelector . '
				</table>';

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
	 * Rendering a single row for the list
	 *
	 * @param string $table Table name
	 * @param array $row Current record
	 * @param integer $cc Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param string $titleCol Table field (column) where header value is found
	 * @param string $thumbsCol Table field (column) where (possible) thumbnails can be found
	 * @param integer $indent Indent from left.
	 * @return string Table row for the element
	 * @access private
	 * @see getTable()
	 */
	public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$iOut = '';

		if (substr(TYPO3_version, 0, 3)  >= '4.0') {
				// In offline workspace, look for alternative record:
			t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace);
		}
			// Background color, if any:
		$row_bgColor =
			$this->alternateBgColors ?
			(($cc % 2) ? '' : ' bgcolor="' . t3lib_div::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4, 10, 10, 10) . '"') :
			'';

			// Overriding with versions background color if any:
		$row_bgColor = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : $row_bgColor;

			// Initialization
		$alttext = t3lib_BEfunc::getRecordIconAltText($row, $table);

			// Incr. counter.
		$this->counter++;

		$indentStyle = ($indent ? ' style="margin-left: ' . $indent . 'px;"' : '');
		$iconAttributes = 'title="' . htmlspecialchars($alttext) . '"' . $indentStyle;

			// Icon for order comment and delivery address
		if ($row['comment'] != '' && $row['internalcomment'] != '') {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add_user_int.gif',
					$iconAttributes
				);
			} else {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_user_int.gif',
					$iconAttributes
				);
			}
		} elseif ($row['comment'] != '') {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add_user.gif',
					$iconAttributes
				);
			} else {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_user.gif',
					$iconAttributes
				);
			}
		} elseif ($row['internalcomment'] != '') {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add_int.gif',
					$iconAttributes
				);
			} else {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_int.gif',
					$iconAttributes
				);
			}
		} else {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add.gif',
					$iconAttributes
				);
			} else {
				$iconImg = t3lib_iconWorks::getIconImage($table, $row, $this->backPath, $iconAttributes);
			}
		}

		$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;

		$extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];
			// Preparing and getting the data-array
		$theData = Array();

		foreach ($this->fieldArray as $fCol) {
			if ($fCol == 'pid') {
				$theData[$fCol] = $row[$fCol];
			} elseif ($fCol == 'sum_price_gross') {
				if ($this->csvOutput) {
					$row[$fCol] = $row[$fCol] / 100;
				} else {
					$theData[$fCol] = tx_moneylib::format($row[$fCol], $row['cu_iso_3'], FALSE);
				}
			} elseif ($fCol == 'crdate') {
				$theData[$fCol] = t3lib_BEfunc::date($row[$fCol]);

				$row[$fCol] = t3lib_BEfunc::date($row[$fCol]);
			} elseif ($fCol == 'tstamp') {
				$theData[$fCol] = t3lib_BEfunc::date($row[$fCol]);
				$row[$fCol] = t3lib_BEfunc::date($row[$fCol]);
			} elseif ($fCol == 'articles') {
				$articleNumber = array();
				$articleName = array();

				$res_articles = $database->exec_SELECTquery(
					'article_number, title, order_uid',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid']
				);
				$articles = array();
				while (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$articles[] = $lokalRow['article_number'] . ':' . $lokalRow['title'];
					$articleNumber[] = $lokalRow['article_number'];
					$articleName[] = $lokalRow['title'];
				}

				if ($this->csvOutput) {
					$theData[$fCol] = implode(',', $articles);
					$row[$fCol]  = implode(',', $articles);
				} else {
					$theData[$fCol] = '<input type="checkbox" name="orderUid[]" value="' . $row['uid'] . '">';
				}
			} elseif ($fCol == 'numarticles') {
				$res_articles = $database->exec_SELECTquery(
					'sum(amount) anzahl',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid'] . ' and article_type_uid =' . NORMALARTICLETYPE
				);
				if (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$theData[$fCol] = $lokalRow['anzahl'];
					$row[$fCol]  = $lokalRow['anzahl'];
				}
			} elseif ($fCol == 'article_number') {
				$articleNumber = array();

				$res_articles = $database->exec_SELECTquery(
					'article_number',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid'] . ' and article_type_uid =' . NORMALARTICLETYPE
				);
				while (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$articleNumber[] = $lokalRow['article_number'];
					/**
					 * @TODO: implement default value, if number is not defined
					 */
				}
				$theData[$fCol] = implode(',', $articleNumber);
			} elseif ($fCol == 'article_name') {
				$articleName = array();

				$res_articles = $database->exec_SELECTquery(
					'title',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid'] . ' and article_type_uid =' . NORMALARTICLETYPE
				);
				while (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$articleName[] = $lokalRow['title'];
					/**
					 * @TODO: implement default value, if title is not defined
					 */
				}
				$theData[$fCol] = implode(',', $articleName);
			} elseif ($fCol == 'order_type_uid_noName') {
				$res_type = $database->exec_SELECTquery(
					'*',
					'tx_commerce_order_types',
					'uid = ' . (int) $row['order_type_uid_noName']
				);
				while (($localRow = $database->sql_fetch_assoc($res_type))) {
					if ($localRow['icon']) {
						$filepath = $this->backPath . $GLOBALS['TCA']['tx_commerce_order_types']['columns']['icon']['config']['uploadfolder'] . '/' . $localRow['icon'];

						$theData[$fCol] = '<img' . t3lib_iconWorks::skinImg(
							$this->backPath,
							$filepath,
							'title="' . htmlspecialchars($localRow['title']) . '"' . $indentStyle
						);
					} else {
						$theData[$fCol] = $localRow['title'];
					}
				}
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
			} elseif ($fCol == 'order_id') {
				$theData[$fCol] = $row[$fCol];
			} else {
				$theData[$fCol] = $this->linkUrlMail(htmlspecialchars(t3lib_BEfunc::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'])), $row[$fCol]);
			}
		}

			// Add row to CSV list:
		if ($this->csvOutput) {
				// Charset Conversion
			/** @var t3lib_cs $csObj */
			$csObj = t3lib_div::makeInstance('t3lib_cs');
			$csObj->initCharset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);

			if (!$extConf['BECSVCharset']) {
				$extConf['BECSVCharset'] = 'iso-8859-1';
			}
			$csObj->initCharset($extConf['BECSVCharset']);
			$csObj->convArray($row, $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'], $extConf['BECSVCharset']);
			$this->addToCSV($row, $table);
		}

			// Create element in table cells:
		$iOut .= $this->addelement(1, $theIcon, $theData, $row_bgColor);
			// Render thumbsnails if a thumbnail column exists and there is content in it:
		if ($this->thumbs && trim($row[$thumbsCol])) {
			$iOut .= $this->addelement(4, '', array($titleCol => $this->thumbCode($row, $table, $thumbsCol)), $row_bgColor);
		}

			// Finally, return table row element:
		return $iOut;
	}

	/**
	 * Rendering the header row for a table
	 *
	 * @param string $table Table name
	 * @param array $currentIdList Array of the currently displayed uids of the table
	 * @throws UnexpectedValueException
	 * @return string Header table row
	 * @access private
	 * @see getTable()
	 */
	public function renderListHeader($table, $currentIdList) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Init:
		$theData = Array();

			// Traverse the fields:
		$icon = '';
		foreach ($this->fieldArray as $fCol) {

				// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table == 'pages' ? 2 : 16);

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

						// If there are elements on the clipboard for this table, then display the "paste into" icon:
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable)) {
						$cells['pasteAfter'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl($table, $this->id)) .
							'" onclick="' . htmlspecialchars('return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into', $elFromTable)) .
							'" title="' . $language->getLL('clip_paste', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-paste-after') . '</a>';
					}

						// If the numeric clipboard pads are enabled, display the control icons for that:
					if ($this->clipObj->current != 'normal') {

							// The "select" link:
						$cells['copyMarked'] = $this->linkClipboardHeaderIcon(
							t3lib_iconWorks::getSpriteIcon('actions-edit-copy',
							array('title' => $language->getLL('clip_selectMarked', TRUE))),
							$table,
							'setCB'
						);

							// The "edit marked" link:
						$editIdList = implode(',', $currentIdList);
						$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
						$params = '&edit[' . $table . '][' . $editIdList . ']=edit&disHelp=1';
						$cells['edit'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '" title="' . $language->getLL('clip_editMarked', TRUE) . '">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';

							// The "Delete marked" link:
						$cells['delete'] = $this->linkClipboardHeaderIcon(
							t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $language->getLL('clip_deleteMarked', TRUE))),
							$table,
							'delete',
							sprintf($language->getLL('clip_deleteMarkedWarning'), $language->sL($GLOBALS['TCA'][$table]['ctrl']['title']))
						);

							// The "Select all" link:
						$cells['markAll'] = '<a class="cbcCheckAll" rel="" href="#" onclick="' . htmlspecialchars('checkOffCB(\'' . implode(',', $this->CBnames) . '\', this); return false;') . '" title="' . $language->getLL('clip_markRecords', TRUE) . '">' . t3lib_iconWorks::getSpriteIcon('actions-document-select') . '</a>';
					} else {
						$cells['empty'] = '';
					}
					/**
					 * @hook renderListHeaderActions: Allows to change the clipboard icons of the Web>List table headers
					 * @date 2007-11-20
					 * @request Bernhard Kraft <krafbt@kraftb.at>
					 * @usage Above each listed table in Web>List a header row is shown. This hook allows to modify the icons responsible for the clipboard functions (shown above the clipboard checkboxes when a clipboard other than "Normal" is selected), or other "Action" functions which perform operations on the listed records.
					 */
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
							$hookObject = t3lib_div::getUserObj($classData);
							if (!($hookObject instanceof localRecordList_actionsHook)) {
								throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567850);
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
						if ($this->calcPerms & ($table == 'pages' ? 8 : 16) && $this->showNewRecLink($table)) {
							if ($table == 'tt_content' && $this->newWizards) {
									//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
								$tmpTSc = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
								$tmpTSc = $tmpTSc['properties']['newContentWiz.']['overrideWithExtension'];
								$newContentWizScriptPath = $this->backPath . t3lib_extMgm::isLoaded($tmpTSc) ?
									(t3lib_extMgm::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php') :
									'sysext/cms/layout/db_new_content_el.php';

								$icon = '<a href="#" onclick="' .
									htmlspecialchars('return jumpExt(\'' . $newContentWizScriptPath . '?id=' . $this->id . '\');') .
									'" title="' . $language->getLL('new', TRUE) . '">' .
									($table == 'pages' ?
										t3lib_iconWorks::getSpriteIcon('actions-page-new') :
										t3lib_iconWorks::getSpriteIcon('actions-document-new')) . '</a>';
							} elseif ($table == 'pages' && $this->newWizards) {
								$icon = '<a href="' .
									htmlspecialchars($this->backPath . 'db_new.php?id=' . $this->id . '&pagesOnly=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) .
									'" title="' . $language->getLL('new', TRUE) . '">' .
									($table == 'pages' ?
										t3lib_iconWorks::getSpriteIcon('actions-page-new') :
										t3lib_iconWorks::getSpriteIcon('actions-document-new')) . '</a>';
							} else {
								$params = '&edit[' . $table . '][' . $this->id . ']=new';
								if ($table == 'pages_language_overlay') {
									$params .= '&overrideVals[pages_language_overlay][doktype]=' . (int) $this->pageRow['doktype'];
								}
								$icon = '<a href="#" onclick="' .
									htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
									'" title="' . $language->getLL('new', TRUE) . '">' .
									($table == 'pages' ?
										t3lib_iconWorks::getSpriteIcon('actions-page-new') :
										t3lib_iconWorks::getSpriteIcon('actions-document-new')) . '</a>';
							}
						}

							// If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
						if ($permsEdit && $this->table && is_array($currentIdList)) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . implode(',', $this->fieldArray) . '&disHelp=1';
							$icon .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
								'" title="' . $language->getLL('editShownColumns', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';
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
							$theData[$fCol] .= '<a href="' . htmlspecialchars($this->listURL('', -1) . '&duplicateField=' . $fCol) .
								'" title="' . $language->getLL('clip_duplicates', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-duplicates-select') . '</a>';
						}

							// If the table can be edited, add link for editing THIS field for all listed records:
						if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol . '&disHelp=1';
							$iTitle = sprintf($language->getLL('editThisColumn'), rtrim(trim($language->sL(t3lib_BEfunc::getItemLabel($table, $fCol))), ':'));
							$theData[$fCol] .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
								'" title="' . htmlspecialchars($iTitle) . '">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';
						}
					}
					$theData[$fCol] .= $this->addSortLink($language->sL(t3lib_BEfunc::getItemLabel($table, $fCol, 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xml:|')), $fCol, $table);
				break;
			}

		}

		/**
		 * @hook renderListHeader: Allows to change the contents of columns/cells of the Web>List table headers
		 * @date 2007-11-20
		 * @request Bernhard Kraft <krafbt@kraftb.at>
		 * @usage Above each listed table in Web>List a header row is shown. Containing the labels of all shown fields and additional icons to create new records for this table or perform special clipboard tasks like mark and copy all listed records to clipboard, etc.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);
				if (!($hookObject instanceof localRecordList_actionsHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567855);
				}
				$theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
			}
		}

			// Create and return header table row:
		return $this->addelement(1, $icon, $theData, ' class="c-headLine"', '');
	}

	/**
	 * Query the table to build dropdown list
	 *
	 * @param string $table
	 * @return string
	 */
	protected function renderMoveToSelector($table) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Init:
		$theData = Array();

			// Traverse the fields:
		foreach ($this->fieldArray as $fCol) {
			switch ((string) $fCol) {
					// Path
				case '_CONTROL_':
					if ($this->id && !$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {
						$resParent = $database->exec_SELECTquery(
							'pid',
							'pages',
							'uid = ' . $this->id . ' ' .
								t3lib_BEfunc::deleteClause($GLOBALS['TCA']['tx_commerce_orders']['columns']['newpid']['config']['foreign_table'])
						);

						$moveToSelectorRow = '';
						if ($rowParentes = $database->sql_fetch_assoc($resParent)) {
								// Get the pages below $orderPid
							$ret = Tx_Commerce_Utility_BackendUtility::getOrderFolderSelector(
								$this->orderPid,
								$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['OrderFolderRecursiveLevel']
							);
							$moveToSelectorRow .= $language->getLL('moveorderto') . ' <select name="modeDestUid" size="1">
								<option value="" selected="selected">' . $language->getLL('movedestination') . '</option>';
							foreach ($ret as $displayArray) {
								$moveToSelectorRow .= '<option value="' . $displayArray[1] . '">' . $displayArray[0] . '</option>';
							}

							$moveToSelectorRow .= '</select>
								<input type="submit" name="OK" value="ok">';
						}

						$theData[$fCol] = $moveToSelectorRow;
					}
				break;

					// Regular fields header:
				default:
					$theData[$fCol] = '';
				break;
			};
		}

			// Create and return header table row:
		return $this->addelement(1, '', $theData, '', '');
	}

	/**
	 * @param string $table
	 * @param integer $id
	 * @param string $addWhere
	 * @param string $fieldList
	 * @return array
	 */
	public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
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
		$limit = $this->iLimit ? ($this->firstElementNumber ? $this->firstElementNumber . ',' : '') . ($this->iLimit + 1) : '';

			// Filtering on displayable pages (permissions):
		$pC = ($table == 'pages' && $this->perms_clause) ? ' AND ' . $this->perms_clause : '';

		if ($id > 0) {
			$pidWhere = ' AND tx_commerce_orders.pid=' . $id;
		} else {
			Tx_Commerce_Utility_FolderUtility::init_folders();

				// Find the right pid for the Ordersfolder
			$orderPid = current(array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce')));;

			$orderFolders = Tx_Commerce_Utility_BackendUtility::getOrderFolderSelector($orderPid, PHP_INT_MAX);

			$list = array();
			foreach ($orderFolders as $orderFolder) {
				$list[] = $orderFolder[1];
			}

			$pidWhere = ' AND tx_commerce_orders.pid in (' . implode(',', $list) . ')';
		}

			// Adding search constraints:
		$search = $this->makeSearchString($table);

		$queryParts = array(
			'SELECT' => 'DISTINCT tx_commerce_order_articles.order_id, delivery_table.order_id AS order_number,
				tx_commerce_order_articles.article_type_uid, tx_commerce_order_articles.title AS payment,
				delivery_table.title AS delivery, tx_commerce_orders.uid, tx_commerce_orders.pid, tx_commerce_orders.crdate,
				tx_commerce_orders.tstamp, tx_commerce_orders.order_id, tx_commerce_orders.sum_price_gross,
				tt_address.tx_commerce_address_type_id, tt_address.company, tt_address.name, tt_address.surname,
				tt_address.address, tt_address.zip, tt_address.city, tt_address.email, tt_address.phone AS phone_1,
				tt_address.mobile AS phone_2, tx_commerce_orders.cu_iso_3_uid, tx_commerce_orders.tstamp,
				tx_commerce_orders.uid AS articles, tx_commerce_orders.comment, tx_commerce_orders.internalcomment,
				tx_commerce_orders.order_type_uid AS order_type_uid_noName, static_currencies.cu_iso_3',
			'FROM' => 'tx_commerce_orders, tt_address, tx_commerce_order_articles, tx_commerce_order_articles AS delivery_table, static_currencies',
			'WHERE' => 'static_currencies.uid = tx_commerce_orders.cu_iso_3_uid
				AND delivery_table.order_id = tx_commerce_orders.order_id
				AND tx_commerce_order_articles.order_id = tx_commerce_orders.order_id
				AND tx_commerce_order_articles.article_type_uid = ' . PAYMENTARTICLETYPE . '
				AND delivery_table.article_type_uid = ' . DELIVERYARTICLETYPE . '
				AND tx_commerce_orders.deleted = 0
				AND tx_commerce_orders.cust_deliveryaddress = tt_address.uid' .
				' ' . $pC .
				' ' . $addWhere . $pidWhere .
				' ' . $search,
			'GROUPBY' => '',
			'ORDERBY' => $database->stripOrderBy($orderBy),
			'LIMIT' => $limit,
		);

			// get Module TSConfig
		$moduleConfig = t3lib_BEfunc::getModTSconfig($id, 'mod.txcommerceM1_orders');

		if ($moduleConfig['properties']['delProdUid']) {
			t3lib_div::deprecationLog('mod.txcommerceM1_orders.delProdUid is deprecated since commerce 0.14.0, this setting will be removed in commerce 0.16.0, please use mod.txcommerceM1_orders.deliveryProductUid instead');
		}
		if ($moduleConfig['properties']['payProdUid']) {
			t3lib_div::deprecationLog('mod.txcommerceM1_orders.payProdUid is deprecated since commerce 0.14.0, this setting will be removed in commerce 0.16.0, please use mod.txcommerceM1_orders.paymentProductUid instead');
		}

		$deliveryProductUid = $moduleConfig['properties']['delProdUid'] ?
			$moduleConfig['properties']['delProdUid'] :
			$moduleConfig['properties']['deliveryProductUid'] ? $moduleConfig['properties']['deliveryProductUid'] : 0;
		if ($deliveryProductUid > 0) {
			$deliveryArticles = Tx_Commerce_Utility_BackendUtility::getArticlesOfProductAsUidList($deliveryProductUid);

			if (count($deliveryArticles)) {
				$queryParts['WHERE'] .= ' AND delivery_table.article_uid IN (' . implode(',', $deliveryArticles) . ') ';
			}
		}

		$paymentProductUid = $moduleConfig['properties']['payProdUid'] ?
			$moduleConfig['properties']['payProdUid'] :
			$moduleConfig['properties']['paymentProductUid'] ? $moduleConfig['properties']['paymentProductUid'] : 0;
		if ($paymentProductUid > 0) {
			$paymentArticles = Tx_Commerce_Utility_BackendUtility::getArticlesOfProductAsUidList($paymentProductUid);

			if (count($paymentArticles)) {
				$queryParts['WHERE'] .= ' AND delivery_table.article_uid IN (' . implode(',', $paymentArticles) . ') ';
			}
		}

			// Apply hook as requested in http://bugs.typo3.org/view.php?id=4361
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'makeQueryArray_post')) {
				$_params = array(
					'orderBy' => $orderBy,
					'limit' => $limit,
					'pC' => $pC,
					'search' => $search,
				);
				$hookObj->makeQueryArray_post($queryParts, $this, $table, $id, $addWhere, $fieldList, $_params);
			}
		}

		return $queryParts;
	}

	/**
	 * @todo fix that this can get removed
	 * @NOTE: Since Typo3 4.5 we can't use listURL from parent class ("class.db_list.inc" - class recordList) anymore. It would lead to wrong url linking to web_list.
	 * This is just a copy of function listURL from Typo3 4.2
	 *
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returlUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
	 *
	 * @param string $altId Alternative id value. Enter blank string for the current id ($this->id)
	 * @param string $table Tablename to display. Enter "-1" for the current table.
	 * @param string $exclList Commalist of fields NOT to include ("sortField" or "sortRev")
	 * @return string URL
	 */
	public function listURL($altId = '', $table = -1, $exclList = '') {
		$urlParameters = array();
		if (strcmp($altId, '')) {
			$urlParameters['id'] = $altId;
		} else {
			$urlParameters['id'] = $this->id;
		}
		if ($table === -1) {
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
		if ((!$exclList || !t3lib_div::inList($exclList, 'firstElementNumber')) && $this->firstElementNumber) {
			$urlParameters['pointer'] = $this->firstElementNumber;
		}
		if ((!$exclList || !t3lib_div::inList($exclList, 'sortField')) && $this->sortField) {
			$urlParameters['sortField'] = $this->sortField;
		}
		if ((!$exclList || !t3lib_div::inList($exclList, 'sortRev')) && $this->sortRev) {
			$urlParameters['sortRev'] = $this->sortRev;
		}

		return 'index.php?' . t3lib_div::implodeArrayForUrl('', $urlParameters, '', TRUE);
	}

	/**
	 * Makes the list of fields to select for a table
	 *
	 * @param string $table Table name
	 * @param boolean|integer $dontCheckUser If set, users access to the field (non-exclude-fields) is NOT checked.
	 * @param boolean|integer $addDateFields If set, also adds crdate and tstamp fields (note: they will also be added if user is admin or dontCheckUser is set)
	 * @return array Array, where values are fieldnames to include in query
	 */
	public function makeFieldList($table, $dontCheckUser = 0, $addDateFields = 0) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Init fieldlist array:
		$fieldListArr = array();

			// Check table:
		if (is_array($GLOBALS['TCA'][$table])) {
			t3lib_div::loadTCA($table);

			if (isset($GLOBALS['TCA'][$table]['columns']) && is_array($GLOBALS['TCA'][$table]['columns'])) {
					// Traverse configured columns and add them to field array, if available for user.
				foreach ($GLOBALS['TCA'][$table]['columns'] as $fN => $fieldValue) {
					if (
						$dontCheckUser
						|| ((!$fieldValue['exclude'] || $backendUser->check('non_exclude_fields', $table . ':' . $fN))
						&& $fieldValue['config']['type'] != 'passthrough')
					) {
						$fieldListArr[] = $fN;
					}
				}

					// Add special fields:
				if ($dontCheckUser || $backendUser->isAdmin()) {
					$fieldListArr[] = 'uid';
					$fieldListArr[] = 'pid';
				}

					// Add date fields
				if ($dontCheckUser || $backendUser->isAdmin() || $addDateFields) {
					if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
						$fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['tstamp'];
					}
					if ($GLOBALS['TCA'][$table]['ctrl']['crdate']) {
						$fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['crdate'];
					}
				}

					// Add more special fields:
				if ($dontCheckUser || $backendUser->isAdmin()) {
					if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id']) {
						$fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['cruser_id'];
					}
					if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
						$fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
					}
					if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
						$fieldListArr[] = 't3ver_id';
						$fieldListArr[] = 't3ver_state';
						$fieldListArr[] = 't3ver_wsid';
						if ($table === 'pages') {
							$fieldListArr[] = 't3ver_swapmode';
						}
					}
				}
			} else {
				t3lib_div::sysLog(
					sprintf('$TCA is broken for the table "%s": no required "columns" entry in $TCA.', $table),
					'core',
					t3lib_div::SYSLOG_SEVERITY_ERROR
				);
			}
		}

			// CSV Export
		if ($this->csvOutput) {
			$fieldListArr = array('order_id', 'crdate', 'tstamp', 'delivery', 'payment', 'numarticles', 'sum_price_gross',
				'cu_iso_3', 'company', 'surname', 'name', 'address', 'zip', 'city', 'email', 'phone_1', 'phone_2', 'comment',
				'internalcomment', 'articles');
		}

		return $fieldListArr;
	}
}

class_alias('Tx_Commerce_ViewHelpers_OrderRecordList', 'tx_commerce_order_localRecordlist');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/OrderRecordlist.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/OrderRecordlist.php']);
}

?>
<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
 * (c) 2005 Franz Holzinger <kontakt@fholzinger.com>
 * (c) 2009-2012 Ingo Schmitt <is@marketing-factory.de>
 * All rights reserved
 *
 * This script is part of the Typo3 project. The Typo3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Script class for the graytree list view (old version - not used any more)
 */
class Tx_Commerce_ViewHelpers_CategoryRecordList extends localRecordList {
	/**
	 * uid - unique recore ids
	 *
	 * @var integer
	 */
	public $uid;

	/**
	 * @var integer
	 */
	public $parent_uid;

	/**
	 * default values necessary for the flexform
	 *
	 * @var string
	 */
	public $defVals;

	/**
	 * @var boolean
	 */
	public $disableSingleTableView;

	/**
	 * @var array
	 */
	public $pageinfo = array();

	/**
	 * @var t3lib_transl8tools
	 */
	public $translateTools;

	/**
	 * @var string
	 */
	public $newRecordIcon = '';

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @param array $row
	 * @return array all available buttons as an assoc. array
	 */
	public function getHeaderButtons($row) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
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

			// Get users permissions for this row:
		$localCalcPerms = $backendUser->calcPerms($row);

			// If edit permissions are set (see class.t3lib_userauthgroup.php)
		if ($localCalcPerms & 2) {
				// Adding "New record" icon:
			if (!$GLOBALS['SOBE']->modTSconfig['properties']['noCreateRecordsLink']) {
				$buttons['new_record'] = $this->newRecordIcon;
			}
		}

			// "Paste into page" link:
		if (($localCalcPerms & 8) || ($localCalcPerms & 16)) {
			$elFromTable = $this->clipObj->elFromTable('');
			if (count($elFromTable)) {
				$buttons['paste'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $this->id)) . '" onclick="' .
					htmlspecialchars('return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into', $elFromTable)) . '">' .
					t3lib_iconWorks::getSpriteIcon(
						'actions-document-paste-into',
						array('title' => $language->getLL('clip_paste', 1))
					) . '</a>';
			}
		}

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
	 * Creates the listing of records from a single table
	 *
	 * @param string $table Table name
	 * @param integer $id Page id
	 * @param string $rowList List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @return string HTML table with the listing for the record.
	 */
	public function getTable($table, $id, $rowList) {
			// Loading all TCA details for this table:
		t3lib_div::loadTCA($table);
		/** @var t3lib_db $database */
		$database = & $GLOBALS['TYPO3_DB'];
		/** @var language $language */
		$language = & $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Init
		$addWhere = '';
		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField']
			&& $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
			&& !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray = array();
			// Add title column
		$this->fieldArray[] = $titleCol;
		if ($this->localizationView && $l10nEnabled) {
			$this->fieldArray[] = '_LOCALIZATION_';
			$this->fieldArray[] = '_LOCALIZATION_b';
			$addWhere .= ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '<=0';
		}
		if (!t3lib_div::inList($rowList, '_CONTROL_')) {
			$this->fieldArray[] = '_CONTROL_';
		}
		if ($this->searchLevels) {
			$this->fieldArray[] = '_PATH_';
		}
			// Cleaning up:
		$this->fieldArray = array_unique(array_merge($this->fieldArray, t3lib_div::trimExplode(',', $rowList, 1)));
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
			if (t3lib_extMgm::isLoaded('cms')) {
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
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
		if ($GLOBALS['TCA'][$table]['ctrl']['versioning']) {
			$selectFields[] = 't3ver_id';
		}
		if ($l10nEnabled) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
			$selectFields = array_merge($selectFields, t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1));
		}
			// Unique list!
		$selectFields = array_unique($selectFields);
			// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selectFields = array_intersect($selectFields, $this->makeFieldList($table, 1));
			// implode it into a list of fields for the SQL-statement.
		$selFieldList = implode(',', $selectFields);

			// Graytree Start
		$addWhere .= ' AND uid IN (' . $this->uid . ')';
			// Graytree Ende

			// Create the SQL query for selecting the elements in the listing:
			// (API function from class.db_list.inc)
		$queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
			// Finding the total amount of records on the page (API function from class.db_list.inc)
		$this->setTotalItems($queryParts);

			// Init:
		$dbCount = 0;
		$out = '';

			// If the count query returned any number of records, we perform the real query, selecting records.
		$result = FALSE;
		if ($this->totalItems) {
			$result = $database->exec_SELECT_queryArray($queryParts);
			$dbCount = $database->sql_num_rows($result);
		}

		$LOISmode = $this->listOnlyInSingleTableMode && !$this->table;

			// If any records was selected, render the list:
		if ($dbCount) {

				// Half line is drawn between tables:
			if (!$LOISmode) {
				$theData = Array();
				if (!$this->table && !$rowList) {
					$theData[$titleCol] = '<img src="/typo3/clear.gif" width="' .
						($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] ? '230' : '350') . '" height="1" alt="" />';
					if (in_array('_CONTROL_', $this->fieldArray)) {
						$theData['_CONTROL_'] = '';
					}
					if (in_array('_CLIPBOARD_', $this->fieldArray)) {
						$theData['_CLIPBOARD_'] = '';
					}
				}
				$out .= $this->addelement(0, '', $theData, '', $this->leftMargin);
			}

				// Header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView) {
				$theData[$titleCol] = '<span class="c-table">' . $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], 1) . '</span> (' . $this->totalItems . ')';
			} else {
				$title = $language->getLL(!$this->table ? 'expandView' : 'contractView', 1);
				$icon = t3lib_iconWorks::getSpriteIcon('actions-view-table-' . ($this->table ? 'collapse' : 'expand'), array('title' => $title));
				$theData[$titleCol] = $this->linkWrapTable($table, '<span class="c-table">' . $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], 1) .
					'</span> (' . $this->totalItems . ') ' . $icon
				);
			}

				// CSH:
			$theData[$titleCol] .= t3lib_BEfunc::cshItem($table, '', $this->backPath, '', FALSE, 'margin-bottom:0px; white-space: normal;');

			if ($LOISmode) {
				$out .= '
					<tr>
						<td class="c-headLineTable" style="width:95%;">' . $theData[$titleCol] . '</td>
					</tr>';

				if ($backendUser->uc['edit_showFieldHelp']) {
					$language->loadSingleTableDescription($table);
					if (isset($GLOBALS['TCA_DESCR'][$table]['columns'][''])) {
						$out .= '
					<tr>
						<td class="c-tableDescription">' . t3lib_BEfunc::helpTextIcon($table, '', $this->backPath, TRUE) .
							$GLOBALS['TCA_DESCR'][$table]['columns']['']['description'] . '</td>
					</tr>';
					}
				}
			} else {
				$theUpIcon = ($table == 'pages' && $this->id && isset($this->pageRow['pid'])) ?
					'<a href="' . htmlspecialchars($this->listURL($this->pageRow['pid'])) . '">' .
						t3lib_iconWorks::getSpriteIcon(
							'actions-view-go-up',
							array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', 1))
						) . '</a>' :
					'';
				$out .= $this->addelement(1, $theUpIcon, $theData, ' class="c-headLineTable"', '');
			}

			$iOut = '';
			if (!$LOISmode) {
					// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;
					// Accumulate rows here
				$accRows = array();
				while ($row = $database->sql_fetch_assoc($result)) {
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
										$tmpRow = t3lib_BEfunc::getRecordRaw(
											$table,
											't3ver_move_id=' . (int) $lRow['uid'] . ' AND pid=' . (int) $row['_MOVE_PLH_pid'] .
												' AND t3ver_wsid=' . (int) $row['t3ver_wsid'] . t3lib_beFunc::deleteClause($table),
											$selFieldList
										);
										$lRow = is_array($tmpRow) ? $tmpRow : $lRow;
									} else {
										$tmpRow = t3lib_BEfunc::getRecordRaw($table, 'uid=' . $lRow['uid'] . t3lib_beFunc::deleteClause($table), $selFieldList);
										$lRow = is_array($tmpRow) ? $tmpRow : $lRow;
									}
										// In offline workspace, look for alternative record:
									t3lib_BEfunc::workspaceOL($table, $lRow, $backendUser->workspace, TRUE);
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

					// The header row for the table is now created:
				$out .= $this->renderListHeader($table, $currentIdList);
			}

				// The list of records is added after the header:
			$out .= $iOut;

				// ... and it is all wrapped in a table:
			$out = '
				<!--
					DB listing of elements: "' . htmlspecialchars($table) . '"
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist' .
					($LOISmode ? ' typo3-dblist-overview' : '') . '">
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
	 * Rendering the header row for a table
	 *
	 * @param string $table Table name
	 * @param array $currentIdList Array of the currectly displayed uids of the table
	 * @return string        Header table row
	 * @see getTable()
	 */
	public function renderListHeader($table, $currentIdList) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Init:
		$theData = Array();

			// Traverse the fields:
		foreach ($this->fieldArray as $fCol) {
				// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table == 'pages' ? 2 : 16);

			switch ((string) $fCol) {
				case '_PATH_':
					$theData[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels._PATH_', 1) . ']</i>';
				break;

				case '_LOCALIZATION_':
					$theData[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels._LOCALIZATION_', 1) . ']</i>';
				break;

				case '_LOCALIZATION_b':
					$theData[$fCol] = $language->getLL('Localize', 1);
				break;

				case '_CLIPBOARD_':
					$cells = array();

						// If there are elements on the clipboard for this table, then display the "paste into" icon:
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable)) {
						$cells[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl($table, $this->id)) . '" onclick="' .
							htmlspecialchars('return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into', $elFromTable)) . '">' .
								t3lib_iconWorks::getSpriteIcon(
									'actions-document-paste-into',
									array('title' => $language->getLL('clip_paste', 1))
								) . '</a>';
					}

						// If the numeric clipboard pads are enabled, display the control icons for that:
					if ($this->clipObj->current != 'normal') {
							// The "select" link:
						$cells[] = $this->linkClipboardHeaderIcon(
								t3lib_iconWorks::getSpriteIcon('actions-edit-copy', array('title' => $language->getLL('clip_selectMarked', 1))),
								$table,
								'setCB'
							);

							// The "edit marked" link:
						$editIdList = implode(',', $currentIdList);
						$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
						$params = '&edit[' . $table . '][' . $editIdList . ']=edit&disHelp=1';
						$cells[] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $language->getLL('clip_editMarked', 1))) . '</a>';

							// The "Delete marked" link:
						$cells[] = $this->linkClipboardHeaderIcon(
							t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $language->getLL('clip_deleteMarked', 1))),
							$table,
							'delete',
							sprintf($language->getLL('clip_deleteMarkedWarning'), $language->sL($GLOBALS['TCA'][$table]['ctrl']['title']))
						);

							// The "Select all" link:
						$cells[] = '<a href="#" onclick="' . htmlspecialchars('checkOffCB(\'' . implode(',', $this->CBnames) . '\'); return false;') . '">' .
							t3lib_iconWorks::getSpriteIcon(
								'actions-document-open',
								array('title' => $language->getLL('clip_markRecords', 1))
							) . '</a>';
					} else {
						$cells[] = '';
					}
					$theData[$fCol] = implode('', $cells);
				break;

				case '_CONTROL_':
					if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {
							// If new records can be created on this page, add links:
						if ($this->calcPerms & ($table == 'pages' ? 8 : 16) && $this->showNewRecLink($table)) {
							$icon = ($table == 'pages' ? 'actions-page-new' : 'actions-document-new');
							$title = $language->getLL('new', 1);
							$sprite = t3lib_iconWorks::getSpriteIcon($icon, array('title' => $title));
							if ($table == 'tt_content' && $this->newWizards) {
									//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
								$tmpTSc = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
								$tmpTSc = $tmpTSc['properties']['newContentWiz.']['overrideWithExtension'];
								$newContentWizScriptPath = t3lib_extMgm::isLoaded($tmpTSc) ?
									(t3lib_extMgm::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php') :
									'sysext/cms/layout/db_new_content_el.php';

								$theData[$fCol] = '<a href="#" onclick="' .
									htmlspecialchars('return jumpExt(\'' . $newContentWizScriptPath . '?id=' . $this->id . '\');') .
									'">' . $sprite . '</a>';
							} elseif ($table == 'pages' && $this->newWizards) {
								$theData[$fCol] = '<a href="' . htmlspecialchars('db_new.php?id=' . $this->id . '&pagesOnly=1returnUrl=' .
									rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' . $sprite . '</a>';
							} else {
								$params = '&edit[' . $table . '][' . $this->id . ']=new' . $this->defVals;
								$theData[$fCol] = '<a href="#" onclick="' .
									htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '">' . $sprite . '</a>';
							}
						}

							// If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
						if ($permsEdit && $this->table && is_array($currentIdList)) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . implode(',', $this->fieldArray) . '&disHelp=1' . $this->defVals;
							$temp = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '">' .
								t3lib_iconWorks::getSpriteIcon(
									'actions-document-open',
									array('title' => $language->getLL('editShownColumns', 1))
								) . '</a>';
							$theData[$fCol] .= $temp;
						}
					}
				break;

				default:
					$theData[$fCol] = '';
					if ($this->table && is_array($currentIdList)) {

							// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol] .= '<a href="' . htmlspecialchars($this->listURL('', -1) . '&duplicateField=' . $fCol) . '">' .
								t3lib_iconWorks::getSpriteIcon(
									'actions-document-duplicates-select',
									array('title' => $language->getLL('clip_duplicates', 1))
								) . '</a>';
						}

							// If the table can be edited, add link for editing THIS field for all listed records:
						if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol . '&disHelp=1' . $this->defVals;
							$iTitle = sprintf($language->getLL('editThisColumn'), rtrim(trim($language->sL(t3lib_BEfunc::getItemLabel($table, $fCol))), ':'));
							$temp = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => htmlspecialchars($iTitle))) . '</a>';
							$theData[$fCol] .= $temp;
						}
					}
					$theData[$fCol] .= $this->addSortLink($language->sL(t3lib_BEfunc::getItemLabel($table, $fCol, '<i>[|]</i>')), $fCol, $table);
					break;
			}
		}

			// Create and return header table row:
		return $this->addelement(1, '', $theData, ' class="c-headLine"', '');
	}

	/**
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param string $table The table
	 * @param array $row The record for which to make the control panel.
	 * @return string HTML table with the control panel (unless disabled)
	 */
	public function makeControl($table, $row) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Return blank, if disabled:
		if ($this->dontShowClipControlPanels || !$backendUser->checkLanguageAccess($row['sys_language_uid'])) {
			return '';
		}

			// Initialize:
		t3lib_div::loadTCA($table);
		$cells = array();

			// If the listed table is 'pages' we have to request the permission settings for each page:
		$localCalcPerms = 0;
		if ($table == 'pages') {
			$localCalcPerms = $backendUser->calcPerms(t3lib_BEfunc::getRecord('pages', $row['uid']));
		}

			// This expresses the edit permissions for this particular element:
		$permsEdit = ($table == 'pages' && ($localCalcPerms & 2)) || ($table != 'pages' && ($this->calcPerms & 16));

			// "Show" link (only pages and tt_content elements)
		if ($table == 'pages' || $table == 'tt_content') {
			$cells[] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($table == 'tt_content' ? $this->id . '#' . $row['uid'] : $row['uid'])) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-document-view',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1))
				) . '</a>';
		}

			// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit) {
			$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit' . $this->defVals;
			$temp = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-document-open' . ($GLOBALS['TCA'][$table]['ctrl']['readOnly'] ? '-read-only' : ''),
					array('title' => $language->getLL('edit', 1))
				) . '</a>';
			$cells[] = $temp;
		}

			// "Move" wizard link for pages/tt_content elements:
		if (($table == 'tt_content' && $permsEdit) || ($table == 'pages')) {
			$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpExt(\'move_el.php?table=' . $table . '&uid=' . $row['uid'] . '\');') .
				'"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/move_' . ($table == 'tt_content' ? 'record' : 'page') .
				'.gif', 'width="11" height="12"') . ' title="' . $language->getLL('move_' . ($table == 'tt_content' ? 'record' : 'page'), 1) .
				'" alt="" /></a>';
		}

			// If the extended control panel is enabled OR if we are seeing a single table:
		if ($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] || $this->table) {

				// "Info": (All records)
			$cells[] = '<a href="#" onclick="' . htmlspecialchars('top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;') .
				'"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="' .
				$language->getLL('showInfo', 1) . '" alt="" /></a>';

				// If the table is NOT a read-only table, then show these links:
			if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {

					// "Revert" link (history/undo)
				$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpExt(\'' . $this->backPath . 'show_rechis.php?element=' .
					rawurlencode($table . ':' . $row['uid']) . '\',\'#latest\');') . '"><img' .
					t3lib_iconWorks::skinImg($this->backPath, 'gfx/history2.gif', 'width="13" height="12"') . ' title="' .
					$language->getLL('history', 1) . '" alt="" /></a>';

					// Versioning:
				if (t3lib_extMgm::isLoaded('version')) {
					$vers = t3lib_BEfunc::selectVersionsOfRecord($table, $row['uid'], $fields = 'uid');
					if (is_array($vers)) {
						if (count($vers) > 1) {
							$st = 'background-color: #FFFF00; font-weight: bold;';
							$lab = count($vers) - 1;
						} else {
							$st = 'background-color: #9999cc; font-weight: bold;';
							$lab = 'V';
						}

						$cells[] = '<a href="' . $this->backPath . htmlspecialchars(t3lib_extMgm::extRelPath('version')) .
							'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . rawurlencode($row['uid']) .
							'" class="typo3-ctrl-versioning" style="' . htmlspecialchars($st) . '">' . $lab . '</a>';
					}
				}

					// "Edit Perms" link:
				if ($table == 'tx_commerce_categories' && $backendUser->check('modules', 'web_perm')) {
					$cells[] = '<a href="' . htmlspecialchars('mod/web/perm/index.php?id=' . $row['uid'] . '&return_id=' . $row['uid'] . '&edit=1') . '">' .
						t3lib_iconWorks::getSpriteIcon('status-status-locked', array('title' => $language->getLL('permissions', 1))) . '</a>';
				}

					// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
				if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) {

					if (
							// For NON-pages, must have permission to edit content on this parent page
						($table != 'pages' && ($this->calcPerms & 16))
							// For pages, must have permission to create new pages here.
						|| ($table == 'pages' && ($this->calcPerms & 8))
					) {
						if ($this->showNewRecLink($table)) {
							$icon = ($table == 'pages' ? 'actions-page-new' : 'actions-document-new');
							$params = '&edit[' . $table . '][' . ( - $row['uid']) . ']=new' . $this->defVals;
							$cells[] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '">' .
								t3lib_iconWorks::getSpriteIcon(
									$icon,
									array('title' => $language->getLL('new' . ($table == 'pages' ? 'Page' : 'Record'), 1))
								) . '</a>';
						}
					}
				}

					// "Up/Down" links
				if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
					if (isset($this->currentTable['prev'][$row['uid']])) {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prev'][$row['uid']];
						$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' .
							$GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-move-up', array('title' => $language->getLL('moveUp', 1))) . '</a>';
					} else {
						$cells[] = t3lib_iconWorks::getSpriteIcon('actions-move-up');
					}
					if ($this->currentTable['next'][$row['uid']]) {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['next'][$row['uid']];
						$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' .
							$GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-move-down', array('title' => $language->getLL('moveDown', 1))) . '</a>';
					} else {
						$cells[] = t3lib_iconWorks::getSpriteIcon('actions-move-down');
					}
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
						$icon = 'button_unhide';
						$value = 0;
					} else {
						$icon = 'button_hide';
						$value = 1;
					}
					$params = '&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=' . $value;
					$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' .
						$GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '"><img' .
						t3lib_iconWorks::skinImg($this->backPath, 'gfx/' . $icon . '.gif', 'width="11" height="10"') .
						' title="' . $language->getLL('unHide' . ($table == 'pages' ? 'Page' : ''), 1) . '" alt="" /></a>';
				}

					// "Delete" link:
				if (($table == 'pages' && ($localCalcPerms & 4)) || ($table != 'pages' && ($this->calcPerms & 16))
				) {
					$params = '&cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
					$cells[] = '<a href="#" onclick="' . htmlspecialchars('if (confirm(' .
						$language->JScharCode($language->getLL('deleteWarning')) . ')) {jumpToUrl(\'' .
						$GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');} return false;') . '"><img' .
						t3lib_iconWorks::skinImg($this->backPath, 'gfx/garbage.gif', 'width="11" height="12"') . ' title="' .
						$language->getLL('delete', 1) . '" alt="" /></a>';
				}

					// "Levels" links: Moving pages into new levels...
				if ($permsEdit && $table == 'pages' && !$this->searchLevels) {

						// Up (Paste as the page right after the current parent page)
					if ($this->calcPerms & 8) {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . - $this->id . $this->defVals;
						$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' .
							$GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-move-left', array('title' => $language->getLL('prevLevel', 1))) . '</a>';
					}
						// Down (Paste as subpage to the page right above)
					if ($this->currentTable['prevUid'][$row['uid']]) {
						$localCalcPerms = $backendUser->calcPerms(t3lib_BEfunc::getRecord('pages', $this->currentTable['prevUid'][$row['uid']]));
						if ($localCalcPerms & 8) {
							$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prevUid'][$row['uid']] . $this->defVals;
							$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' .
								$GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-move-right', array('title' => $language->getLL('nextLevel', 1))) . '</a>';
						} else {
							$cells[] = t3lib_iconWorks::getSpriteIcon('actions-move-right');
						}
					} else {
						$cells[] = t3lib_iconWorks::getSpriteIcon('actions-move-right');
					}
				}
			}
		}

			// If the record is edit-locked	by another user, we will show a little warning sign:
		if ($lockInfo = t3lib_BEfunc::isRecordLocked($table, $row['uid'])) {
			$cells[] = '<a href="#" onclick="' . htmlspecialchars('alert(' . $language->JScharCode($lockInfo['msg']) . ');return false;') .
				'"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/recordlock_warning3.gif', 'width="17" height="12"') .
				' title="' . htmlspecialchars($lockInfo['msg']) . '" alt="" /></a>';
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
	 * @return string HTML table with the clipboard panel (unless disabled)
	 */
	public function makeClip($table, $row) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Return blank, if disabled:
		if ($this->dontShowClipControlPanels) {
			return '';
		}
		$cells = array();

			// Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
			// For the "Normal" pad:
		if ($this->clipObj->current == 'normal') {

				// Show copy/cut icons:
			$isSel = (string) $this->clipObj->isSelected($table, $row['uid']);
			$cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpSelf(\'' .
					$this->clipObj->selUrlDB($table, $row['uid'], 1, ($isSel == 'copy'), array('returnUrl' => '')) . '\');'
				) . '"><img' .
				t3lib_iconWorks::skinImg($this->backPath, 'gfx/clip_copy' . ($isSel == 'copy' ? '_h' : '') . '.gif', 'width="12" height="12"') .
				' title="' . $language->sL('LLL:EXT:lang/locallang_core.php:cm.copy', 1) . '" alt="" /></a>';
			$cells[] = '<a href="#" onclick="' . htmlspecialchars(
					'return jumpSelf(\'' . $this->clipObj->selUrlDB($table, $row['uid'], 0, ($isSel == 'cut'), array('returnUrl' => '')) . '\');'
				) . '"><img' .
				t3lib_iconWorks::skinImg($this->backPath, 'gfx/clip_cut' . ($isSel == 'cut' ? '_h' : '') . '.gif', 'width="12" height="12"') .
				' title="' . $language->sL('LLL:EXT:lang/locallang_core.php:cm.cut', 1) . '" alt="" /></a>';
		} else {
				// For the numeric clipboard pads (showing checkboxes where one can select elements on/off)
				// Setting name of the element in ->CBnames array:
			$n = $table . '|' . $row['uid'];
			$this->CBnames[] = $n;

				// Check if the current element is selected and if so, prepare to set the checkbox as selected:
			$checked = ($this->clipObj->isSelected($table, $row['uid']) ? ' checked="checked"' : '');

				// If the "duplicateField" value is set then select all elements which are duplicates...
			if ($this->duplicateField && isset($row[$this->duplicateField])) {
				$checked = '';
				if (in_array($row[$this->duplicateField], $this->duplicateStack)) {
					$checked = ' checked="checked"';
				}
				$this->duplicateStack[] = $row[$this->duplicateField];
			}

				// Adding the checkbox to the panel:
			$cells[] = '<input type="hidden" name="CBH[' . $n . ']" value="0" /><input type="checkbox" name="CBC[' . $n . ']" value="1" class="smallCheckboxes"' . $checked . ' />';
		}

			// Now, looking for selected elements from the current table:
		$elFromTable = $this->clipObj->elFromTable($table);
			// IF elements are found and they can be individually ordered, then add a "paste after" icon:
		if (count($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
			$cells[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl($table, - $row['uid'])) . '" onclick="' .
				htmlspecialchars('return ' . $this->clipObj->confirmMsg($table, $row, 'after', $elFromTable)) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-document-paste-after',
					array('title' => $language->getLL('clip_paste', 1))
				) . '</a>';
		}

			// Now, looking for elements in general:
		$elFromTable = $this->clipObj->elFromTable('');
		if ($table == 'pages' && count($elFromTable)) {
			$cells[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '" onclick="' .
				htmlspecialchars('return ' . $this->clipObj->confirmMsg($table, $row, 'into', $elFromTable)) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-document-paste-into',
					array('title' => $language->getLL('clip_paste', 1))
				) . '</a>';
		}

			// Compile items into a DIV-element:
		return '<!-- CLIPBOARD PANEL: ' . $table . ':' . $row['uid'] . ' -->
				<div class="typo3-clipCtrl">' . implode('', $cells) . '</div>';
	}

	/**
	 * Creates the localization panel
	 *
	 * @param string $table The table
	 * @param array $row The record for which to make the localization panel.
	 * @return array Array with key 0/1 with content for column 1 and 2
	 */
	public function makeLocalizationPanel($table, $row) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$out = array(0 => '', 1 => '',);

		$translations = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $this->selFieldList);
		$this->translations = $translations['translations'];

			// Language title and icon:
		$out[0] = $this->languageFlag($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);

		if (is_array($translations)) {
				// Traverse page translations and add icon for each language that does NOT yet exist:
			$lNew = '';
			foreach ($this->pageOverlays as $lUid_OnPage => $lsysRec) {
				if (!isset($translations['translations'][$lUid_OnPage]) && $backendUser->checkLanguageAccess($lUid_OnPage)) {
					$url = $this->listURL();
					$href = $GLOBALS['SOBE']->doc->issueCommand('&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $lUid_OnPage, $url);
					$language = t3lib_BEfunc::getRecord('sys_language', $lUid_OnPage, 'title');
					if ($this->languageIconTitles[$lUid_OnPage]['flagIcon']) {
						$lC = t3lib_iconWorks::getSpriteIcon($this->languageIconTitles[$lUid_OnPage]['flagIcon']);
					} else {
						$lC = $this->languageIconTitles[$lUid_OnPage]['title'];
					}
					$lC = '<a href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($language['title']) . '">' . $lC . '</a> ';

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
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returlUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
	 *
	 * @param string $altId Alternative id value. Enter blank string for the current id ($this->id)
	 * @param string $table Tablename to display. Enter "-1" for the current table.
	 * @param string $exclList Commalist of fields NOT to include ("sortField" or "sortRev")
	 * @return string        URL
	 */
	public function listURL($altId = '', $table = -1, $exclList = '') {
		$listUrl = t3lib_div::getIndpEnv('SCRIPT_NAME') . '?' . t3lib_div::getIndpEnv('QUERY_STRING');

		return $listUrl;
	}

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for 'pages'-records a link to the level of that record...)
	 *
	 * @param string $table Table name
	 * @param integer $uid Item uid
	 * @param string $code Item title (not htmlspecialchars()'ed yet)
	 * @param array $row Item row
	 * @return string The item title. Ready for HTML output (is htmlspecialchars()'ed)
	 */
	public function linkWrapItems($table, $uid, $code, $row) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// If the title is blank, make a "no title" label:
		if (!strcmp($code, '')) {
			$code = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.no_title', 1) . ']</i> - ' .
				htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table, $row), $backendUser->uc['titleLen']));
		} else {
			$code = htmlspecialchars(t3lib_div::fixed_lgd_cs($code, $this->fixedL));
		}

		switch ((string) $this->clickTitleMode) {
			case 'edit':
					// If the listed table is 'pages' we have to request the permission settings for each page:
				if ($table == 'pages') {
					$localCalcPerms = $backendUser->calcPerms(t3lib_BEfunc::getRecord('pages', $row['uid']));
					$permsEdit = $localCalcPerms & 2;
				} else {
					$permsEdit = $this->calcPerms & 16;
				}

					// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
				if ($permsEdit) {
					$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
					$code = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
						'" title="' . $language->getLL('edit', 1) . '">' . $code . '</a>';
				}
			break;

			case 'show':
					// "Show" link (only pages and tt_content elements)
				if ($table == 'pages' || $table == 'tt_content') {
					$code = '<a href="#" onclick="' .
						htmlspecialchars(t3lib_BEfunc::viewOnClick($table == 'tt_content' ? $this->id . '#' . $row['uid'] : $row['uid'])) .
						'" title="' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . '">' . $code . '</a>';
				}
			break;

			case 'info':
					// "Info": (All records)
				$code = '<a href="#" onclick="' . htmlspecialchars('top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;') .
					'" title="' . $language->getLL('showInfo', 1) . '">' . $code . '</a>';
			break;

			default:
					// Output the label now:
				if ($table == 'pages') {
					$code = '<a href="' . htmlspecialchars($this->listURL($uid, '')) . '">' . $code . '</a>';
				}
			break;
		}

		return $code;
	}
}

class_alias('Tx_Commerce_ViewHelpers_CategoryRecordList', 'commerceRecordList');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/DatabaseListExtra.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/DatabaseListExtra.php']);
}

?>
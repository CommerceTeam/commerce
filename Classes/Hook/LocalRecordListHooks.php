<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sebastian Fischer <typo3@evoweb.de>
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

	// require_once in 4.x needed because in ajax mod the class can't get autoloaded
/** @noinspection PhpIncludeInspection */
require_once(PATH_typo3 . 'interfaces/interface.localrecordlist_actionsHook.php');

class Tx_Commerce_Hook_LocalRecordListHooks implements localRecordList_actionsHook {
	/**
	 * modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
	 *
	 * @param string $table the current database table
	 * @param array $row the current record row
	 * @param array $cells the default clip-icons to get modified
	 * @param localRecordList $parentObject Instance of calling object
	 * @return array the modified clip-icons
	 */
	public function makeClip($table, $row, $cells, &$parentObject) {
		if (
			$parentObject->id && !$GLOBALS['TCA'][$table]['ctrl']['readOnly']
			&& $GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] && $table == 'tx_commerce_orders'
		) {
			$cells['moveOrder'] = '<input type="checkbox" name="orderUid[]" value="' . $row['uid'] . '" class="smallCheckboxes">';
		}

		return $cells;
	}

	/**
	 * modifies Web>List control icons of a displayed row
	 * just to satisfy interface
	 *
	 * @param string $table the current database table
	 * @param array $row the current record row
	 * @param array $cells the default control-icons to get modified
	 * @param localRecordList $parentObject Instance of calling object
	 * @return array the modified control-icons
	 */
	public function makeControl($table, $row, $cells, &$parentObject) {
		return $cells;
	}

	/**
	 * @param string $table
	 * @param array $currentIdList
	 * @param array $headerColumns
	 * @param localRecordList $parentObject
	 * @return array
	 */
	public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		if (get_class($parentObject) == 'Tx_Commerce_ViewHelpers_OrderRecordList') {
			$icon = '';
			foreach ($parentObject->fieldArray as $fCol) {

					// Calculate users permissions to edit records in the table:
				$permsEdit = $parentObject->calcPerms & ($table == 'pages' ? 2 : 16);

				switch ((string) $fCol) {
						// Path
					case '_PATH_':
						$headerColumns[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels._PATH_', 1) . ']</i>';
					break;

						// References
					case '_REF_':
						$headerColumns[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_mod_file_list.xml:c__REF_', 1) . ']</i>';
					break;

						// Path
					case '_LOCALIZATION_':
						$headerColumns[$fCol] = '<i>[' . $language->sL('LLL:EXT:lang/locallang_core.php:labels._LOCALIZATION_', 1) . ']</i>';
					break;

						// Path
					case '_LOCALIZATION_b':
						$headerColumns[$fCol] = $language->getLL('Localize', 1);
					break;

						// Clipboard:
					case '_CLIPBOARD_':
						if ($parentObject->id && !$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel']) {
							$headerColumns[$fCol] = $language->getLL('moveorderto');
						} else {
							$headerColumns[$fCol] = '';
						}
					break;

						// Control panel:
					case '_CONTROL_':
						if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {

								// If new records can be created on this page, add links:
							if ($parentObject->calcPerms & ($table == 'pages' ? 8 : 16) && $parentObject->showNewRecLink($table)) {
								if ($table == 'tt_content' && $parentObject->newWizards) {
										//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
									$tmpTSc = t3lib_BEfunc::getModTSconfig($parentObject->id, 'mod.web_list');
									$tmpTSc = $tmpTSc['properties']['newContentWiz.']['overrideWithExtension'];
									$newContentWizScriptPath = $parentObject->backPath . t3lib_extMgm::isLoaded($tmpTSc) ?
										(t3lib_extMgm::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php') :
										'sysext/cms/layout/db_new_content_el.php';

									$icon = '<a href="#" onclick="' .
										htmlspecialchars('return jumpExt(\'' . $newContentWizScriptPath . '?id=' . $parentObject->id . '\');') .
										'" title="' . $language->getLL('new', TRUE) . '">' .
										($table == 'pages' ?
											t3lib_iconWorks::getSpriteIcon('actions-page-new') :
											t3lib_iconWorks::getSpriteIcon('actions-document-new')) . '</a>';
								} elseif ($table == 'pages' && $parentObject->newWizards) {
									$icon = '<a href="' .
										htmlspecialchars($parentObject->backPath . 'db_new.php?id=' . $parentObject->id . '&pagesOnly=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) .
										'" title="' . $language->getLL('new', TRUE) . '">' .
										($table == 'pages' ?
											t3lib_iconWorks::getSpriteIcon('actions-page-new') :
											t3lib_iconWorks::getSpriteIcon('actions-document-new')) . '</a>';
								} else {
									$params = '&edit[' . $table . '][' . $parentObject->id . ']=new';
									if ($table == 'pages_language_overlay') {
										$params .= '&overrideVals[pages_language_overlay][doktype]=' . (int) $parentObject->pageRow['doktype'];
									}
									$icon = '<a href="#" onclick="' .
										htmlspecialchars(t3lib_BEfunc::editOnClick($params, $parentObject->backPath, -1)) .
										'" title="' . $language->getLL('new', TRUE) . '">' .
										($table == 'pages' ?
											t3lib_iconWorks::getSpriteIcon('actions-page-new') :
											t3lib_iconWorks::getSpriteIcon('actions-document-new')) . '</a>';
								}
							}

								// If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
							if ($permsEdit && $parentObject->table && is_array($currentIdList)) {
								$editIdList = implode(',', $currentIdList);
								if ($parentObject->clipNumPane()) {
									$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
								}
								$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . implode(',', $parentObject->fieldArray) . '&disHelp=1';
								$icon .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $parentObject->backPath, -1)) .
									'" title="' . $language->getLL('editShownColumns', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';
							}
								// add an empty entry, so column count fits again after moving this into $icon
							$headerColumns[$fCol] = '&nbsp;';
						}
					break;

						// space column
					case '_AFTERCONTROL_':
						// space column
					case '_AFTERREF_':
					$headerColumns[$fCol] = '&nbsp;';
					break;

						// Regular fields header:
					default:
						$headerColumns[$fCol] = '';
						if ($parentObject->table && is_array($currentIdList)) {

								// If the numeric clipboard pads are selected, show duplicate sorting link:
							if ($parentObject->clipNumPane()) {
								$headerColumns[$fCol] .= '<a href="' . htmlspecialchars($parentObject->listURL('', -1) . '&duplicateField=' . $fCol) .
									'" title="' . $language->getLL('clip_duplicates', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-document-duplicates-select') . '</a>';
							}

								// If the table can be edited, add link for editing THIS field for all listed records:
							if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
								$editIdList = implode(',', $currentIdList);
								if ($parentObject->clipNumPane()) {
									$editIdList = "'+editList('" . $table . "','" . $editIdList . "')+'";
								}
								$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol . '&disHelp=1';
								$iTitle = sprintf($language->getLL('editThisColumn'), rtrim(trim($language->sL(t3lib_BEfunc::getItemLabel($table, $fCol))), ':'));
								$headerColumns[$fCol] .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $parentObject->backPath, -1)) .
									'" title="' . htmlspecialchars($iTitle) . '">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';
							}
						}
						$headerColumns[$fCol] .= $parentObject->addSortLink($language->sL(t3lib_BEfunc::getItemLabel($table, $fCol, 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xml:|')), $fCol, $table);
					break;
				}
			}
		}

		return $headerColumns;
	}

	/**
	 * modifies Web>List header row clipboard/action icons
	 * just to satisfy interface
	 *
	 * @param string $table the current database table
	 * @param array $currentIdList Array of the currently displayed uids of the table
	 * @param array $cells An array of the current clipboard/action icons
	 * @param object $parentObject Instance of calling (parent) object
	 * @return array Array of modified clipboard/action icons
	 */
	public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject) {
		return $cells;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/LocalRecordListHooks.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/LocalRecordListHooks.php']);
}

?>
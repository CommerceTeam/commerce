<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * User Class for displaying Orders
 */
class Tx_Commerce_ViewHelpers_AttributeEditFunc {
	/**
	 * renders the value list to a value
	 *
	 * @param array $parameter
	 * @return string HTML-Content
	 */
	public function valuelist($parameter) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$content = '';
		$foreignTable = 'tx_commerce_attribute_values';
		$table = 'tx_commerce_attributes';

		/** @var \TYPO3\CMS\Backend\Template\SmallDocumentTemplate $doc */
		$doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\SmallDocumentTemplate');
		$doc->backPath = $GLOBALS['BACK_PATH'];

		$attributeStoragePid = $parameter['row']['pid'];
		$attributeUid = $parameter['row']['uid'];
		/**
		 * Select Attribute Values
		 */

		/**
		 * @todo TS config of fields in list
		 */
		$rowFields = array('attributes_uid', 'value');
		$titleCol = $GLOBALS['TCA'][$foreignTable]['ctrl']['label'];

			// Create the SQL query for selecting the elements in the listing:
		$result = $database->exec_SELECTquery(
			'*',
			$foreignTable,
			'pid = $attributeStoragePid ' . BackendUtility::deleteClause($foreignTable) .
				' AND attributes_uid=\'' . $database->quoteStr($attributeUid, $foreignTable) . '\''
		);
		$dbCount = $database->sql_num_rows($result);

		$out = '';
		if ($dbCount) {
			/**
			 * Only if we have a result
			 */
			$theData[$titleCol] = '<span class="c-table">' .
				$language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:attributeview.valuelist', 1) .
				'</span> (' . $dbCount . ')';
			$fieldCount = count($rowFields);
			$out .= '
					<tr>
						<td class="c-headLineTable" style="width:95%;" colspan="' . ($fieldCount + 1) . '">' . $theData[$titleCol] . '</td>
					</tr>';
			/**
			 * Header colum
			 */
			$out .= '<tr>';
			foreach ($rowFields as $field) {
				$out .= '<td class="c-headLineTable"><b>' . $language->sL(BackendUtility::getItemLabel($foreignTable, $field)) . '</b></td>';
			}
			$out .= '<td class="c-headLineTable"></td>';
			$out .= '</tr>';

			/**
			 * Walk true Data
			 */
			$cc = 0;
			$iOut = '';
			while (($row = $database->sql_fetch_assoc($result))) {
				$cc++;
				$rowBackgroundColor = (
					($cc % 2) ?
					'' :
					' bgcolor="' . \TYPO3\CMS\Core\Utility\GeneralUtility::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4, 10, 10, 10) . '"'
				);

				/**
				 * Not very noice to render html_code directly
				 *
				 * @todo change rendering html code here
				 * */
				$iOut .= '<tr ' . $rowBackgroundColor . '>';
				foreach ($rowFields as $field) {
					$iOut .= '<td>';
					$wrap = array('', '');

					switch ($field) {
						case $titleCol:
							$params = '&edit[' . $foreignTable . '][' . $row['uid'] . ']=edit';
							$wrap = array(
								'<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">',
								'</a>'
							);
							break;

						default:
					}
					$iOut .= implode(BackendUtility::getProcessedValue($foreignTable, $field, $row[$field], 100), $wrap);
					$iOut .= '</td>';
				}
				/**
				 * Trash icon
				 */
				$onClick = 'deleteRecord(\'' . $foreignTable . '\', ' . $row['uid'] .
					', \'alt_doc.php?edit[tx_commerce_attributes][' . $attributeUid . ']=edit\');';

				$iOut .= '<td>&nbsp;';
				$iOut .= '<a href="#" onclick="' . $onClick . '">' .
					\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete') . '</a></td>';
				$iOut .= '</tr>';
			}

			$out .= $iOut;
			/**
			 * Cerate the summ row
			 */
			$out .= '<tr>';

			foreach ($rowFields as $field) {
				$out .= '<td class="c-headLineTable"><b>';
				if ($sum[$field] > 0) {
					$out .= BackendUtility::getProcessedValueExtra($foreignTable, $field, $sum[$field], 100);
				}

				$out .= '</b></td>';
			}
			$out .= '<td class="c-headLineTable"></td>';
			$out .= '</tr>';
		}

		$out = '
			<!--
				DB listing of elements: "' . htmlspecialchars($table) . '"
			-->
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
				' . $out . '
			</table>';
		$content .= $out;

		/**
		 * New article
		 */
		$params = '&edit[' . $foreignTable . '][' . $attributeStoragePid . ']=new&defVals[' . $foreignTable . '][attributes_uid]=' .
			urlencode($attributeUid);
		$content .= '<div id="typo3-newRecordLink">
			<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">
				' . $language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:attributeview.addvalue', 1) .
				'</a>
			</div>';

		return $content;
	}
}

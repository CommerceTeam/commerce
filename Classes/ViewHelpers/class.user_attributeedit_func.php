<?php
/***************************************************************
 *  Copyright notice
 *
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * User Class for displaying Orders
 */
class user_attributeedit_func {
	/**
	 * valuelist
	 * renders the valulist to a value
	 *
	 * @param array $PA
	 * @param $fobj
	 * @return string HTML-Content
	 */
	public function valuelist($PA, $fobj) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$content = '';
		$foreign_table = 'tx_commerce_attribute_values';
		$table = 'tx_commerce_attributes';

		/** @var smallDoc $doc */
		$doc = t3lib_div::makeInstance('smallDoc');
		$doc->backPath = $GLOBALS['BACK_PATH'];

		/**
		 * Load the table TCA into local variable
		 */
		t3lib_div::loadTCA($foreign_table);

		$attributeStoragePid = $PA['row']['pid'];
		$attributeUid = $PA['row']['uid'];
		/**
		 * Select Attribute Values
		 */

		/**
		 * @todo TS config of fields in list
		 */
		$field_rows = array('attributes_uid', 'value');

		/**
		 * Taken from class.db_list_extra.php
		 */
		$titleCol = $GLOBALS['TCA'][$foreign_table]['ctrl']['label'];

			// Create the SQL query for selecting the elements in the listing:
		$result = $database->exec_SELECTquery(
			'*',
			$foreign_table,
			'pid = $attributeStoragePid ' . t3lib_BEfunc::deleteClause($foreign_table) .
				' AND attributes_uid=\'' . $database->quoteStr($attributeUid, $foreign_table) . '\''
		);
		$dbCount = $database->sql_num_rows($result);

		$out = '';
		if ($dbCount) {
			/**
			 * Only if we have a result
			 */
			$theData[$titleCol] = '<span class="c-table">' . $language->sL('LLL:EXT:commerce/locallang_be.php:attributeview.valuelist', 1) . '</span> (' . $dbCount . ')';
			$num_cols = count($field_rows);
			$out .= '
					<tr>
						<td class="c-headLineTable" style="width:95%;" colspan="' . ($num_cols + 1) . '">' . $theData[$titleCol] . '</td>
					</tr>';
			/**
			 * Header colum
			 */
			$out .= '<tr>';
			foreach ($field_rows as $field) {
				$out .= '<td class="c-headLineTable"><b>' . $language->sL(t3lib_BEfunc::getItemLabel($foreign_table, $field)) . '</b></td>';
			}
			$out .= '<td class="c-headLineTable"></td>';
			$out .= '</tr>';

			/**
			 * Walk true Data
			 */
			$cc = 0;
			$iOut = '';
			while ($row = $database->sql_fetch_assoc($result)) {
				$cc++;
				$row_bgColor = (($cc % 2) ? '' : ' bgcolor="' . t3lib_div::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4, 10, 10, 10) . '"');

				/**
				 * Not very noice to render html_code directly
				 *
				 * @todo change rendering html code here
				 * */
				$iOut .= '<tr ' . $row_bgColor . '>';
				foreach ($field_rows as $field) {
					$iOut .= '<td>';
					$wrap = array('', '');

					switch ($field) {
						case $titleCol:
							$params = '&edit[' . $foreign_table . '][' . $row['uid'] . ']=edit';
							$wrap = array(
								'<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">',
								'</a>'
							);
						break;
					}
					$iOut .= implode(t3lib_BEfunc::getProcessedValue($foreign_table, $field, $row[$field], 100), $wrap);
					$iOut .= '</td>';
				}
				/**
				 * Trash icon
				 */
				$onClick = 'deleteRecord(\'' . $foreign_table . '\', ' . $row['uid'] . ', \'alt_doc.php?edit[tx_commerce_attributes][' . $attributeUid . ']=edit\');';

				$iOut .= '<td>&nbsp;';
				$iOut .= '<a href="#" onclick="' . $onClick . '"><img' .
					t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/delete_record.gif', 'width="11" height="12"') .
					' title="Delete" border="0" alt="" /></a></td>';
				$iOut .= '</tr>';
			}

			$out .= $iOut;
			/**
			 * Cerate the summ row
			 */
			$out .= '<tr>';

			foreach ($field_rows as $field) {
				$out .= '<td class="c-headLineTable"><b>';
				if ($sum[$field] > 0) {
					$out .= t3lib_BEfunc::getProcessedValueExtra($foreign_table, $field, $sum[$field], 100);
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
		$params = '&edit[' . $foreign_table . '][' . $attributeStoragePid . ']=new&defVals[' . $foreign_table . '][attributes_uid]=' . urlencode($attributeUid);
		$content .= '<div id="typo3-newRecordLink">';
		$content .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">';
		$content .= $language->sL('LLL:EXT:commerce/locallang_be.php:attributeview.addvalue', 1);
		$content .= '</a>';
		$content .= '</div>';

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/class.user_attributeedit_func.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/class.user_attributeedit_func.php']);
}

?>
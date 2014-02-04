<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * This class is the base for listing of database records and files in the modules Web>List and File>Filelist
 */
class ux_localRecordList extends localRecordList {
	/**
	 * Creates part of query for searching after a word ($this->searchString) fields in input table
	 *
	 * @param string $table Table, in which the fields are being searched.
	 * @return string Returns part of WHERE-clause for searching, if applicable.
	 */
	public function makeSearchString($table) {
		if ($table !== 'tx_commerce_orders') {
			return parent::makeSearchString($table);
		} else {
				// added type none to search filed types
				// Make query, only if table is valid and a search string is actually defined:
			if ($GLOBALS['TCA'][$table] && $this->searchString) {

					// Loading full table description - we need to traverse fields:
				t3lib_div::loadTCA($table);

					// Initialize field array:
				$sfields = array();
					// Adding "uid" by default.
				$sfields[] = 'uid';

					// Traverse the configured columns and add all columns that can be searched:
				foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $info) {
					if (
						$info['config']['type'] == 'text' || $info['config']['type'] == 'none'
						|| ($info['config']['type'] == 'input' && !preg_match('/date|time|int/', $info['config']['eval']))
					) {
						$sfields[] = $fieldName;
					}
				}

					// If search-fields were defined (and there always are) we create the query:
				if (count($sfields)) {
					/** @var t3lib_db $database */
					$database = $GLOBALS['TYPO3_DB'];
						// Free-text searching...
					$like = ' LIKE \'%' . $database->quoteStr($this->searchString, $table) . '%\'';
					$queryPart = ' AND (' . implode($like . ' OR ', $sfields) . $like . ')';

						// Return query:
					return $queryPart;
				}
			}
		}

		return '';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/ux_localRecordList.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/ux_localRecordList.php']);
}

?>
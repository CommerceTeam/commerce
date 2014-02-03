<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2008 - 2012 Ingo Schmitt <typo3@marketing-factory.de>
 * (c) 2013 Sebastian Fischer <typo3@marketing-factory.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
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
 * Implements the data view for leaf slave
 */
abstract class leafSlaveData extends leafData {

	/**
	 * Returns an array of Positions
	 *
	 * @return array
	 * @param integer $index Index of this leaf
	 * @param array $indices Parent Indices
	 */
	public function getPositionsByIndices($index, $indices) {
		if (!is_numeric($index) || !is_array($indices)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getPositionsByIndices (productdata) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

			// Construct the Array of Position Ids
		$firstIndex = $indices[0];
		if (!is_array($this->positionArray[$firstIndex])) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getPositionsByIndices (leafSlaveData) does not find the first Index in the position array.', COMMERCE_EXTKEY, 2);
			}
			$this->positionUids = array();
			return $this->positionUids;
		}

		$this->mountIds = array_keys($this->positionArray[$firstIndex]);

		return parent::getPositionsByIndices($index, $indices);
	}

	/**
	 * Initializes the Records
	 * All Products are read, no matter what the rights - only editing them is restricted!
	 *
	 * @param integer $index Leaf index
	 * @param array $parentIndices Parent Indices
	 * @param object $parentLeafData LeafData of the Parent Leaf
	 * @return void
	 */
	public function initRecords($index, $parentIndices, &$parentLeafData) {
		if (!is_numeric($index) || !is_array($parentIndices) || is_null($parentLeafData)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('initRecords (leafSlaveData) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
			// Check if User's Group may view the records
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = & $GLOBALS['BE_USER'];
		if (!$backendUser->check('tables_select', $this->table)) {
			$this->records = NULL;
			if (TYPO3_DLOG) {
				t3lib_div::devLog('initRecords User is not allowed to view table:' . $this->table, COMMERCE_EXTKEY, 3);
			}
			return;
		}

			// Store the position Uids
		$this->getPositionsByIndices($index, $parentIndices);

			// Get the uids of the open parent - returns uids which are currently open
		$recordUids = $parentLeafData->getRecordsUids();

		if ($recordUids == NULL) {
			return;
		}

			// Read all items
		if ($this->useMMTable) {
			$this->where['uid_foreign'] = implode(',', $recordUids);
			$this->where['uid_local'] = 0;
		} else {
			$this->where[$this->itemParentField] = implode(',', $recordUids);
			$this->where['uid'] = 0;
		}

		$this->records = $this->loadRecords();
	}
}

?>
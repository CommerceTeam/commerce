<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2009 Erik Frister <typo3@marketing-factory.de>
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
 * Implements the Tx_Commerce_Tree_Leaf_Data for the Category
 */
class Tx_Commerce_Tree_Leaf_CategoryData extends Tx_Commerce_Tree_Leaf_MasterData {
	/**
	 * Permission Mask for reading Categories
	 *
	 * @var integer
	 */
	protected $permsMask = 1;

	/**
	 * make this as a var which field is used as item_parent
	 *
	 * @var string
	 */
	protected $extendedFields  = 'parent_category, title, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody, editlock, hidden, starttime, endtime, fe_group';

	/**
	 * @var string
	 */
	protected $table = 'tx_commerce_categories';

	/**
	 * @var string
	 */
	protected $item_parent = 'uid_foreign';

	/**
	 * table to read the leafitems from
	 *
	 * @var string
	 */
	protected $itemTable = 'tx_commerce_categories';

	/**
	 * table that is to be used to find parent items
	 *
	 * @var string
	 */
	protected $mmTable = 'tx_commerce_categories_parent_category_mm';

	/**
	 * Flag if mm table is to be used or the parent field
	 *
	 * @var boolean
	 */
	protected $useMMTable = TRUE;

	/**
	 * Sets the Permission Mask for reading Categories from the db
	 *
	 * @param $mask integer mask for reading the permissions
	 * @return void
	 */
	public function setPermsMask($mask) {
		if (!is_numeric($mask)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setPermsMask (categorydata) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
		} else {
			$this->permsMask = $mask;
		}
	}

	/**
	 * Initializes the categorydata
	 * Builds the Permission-Statement
	 *
	 * @return void
	 */
	public function init() {
		$this->whereClause = ' deleted = 0 AND ' . Tx_Commerce_Utility_BackendUtility::getCategoryPermsClause($this->permsMask);
		$this->order = 'tx_commerce_categories.sorting ASC';
	}

	/**
	 * Loads and returns the Array of Records (for db_list)
	 *
	 * @param integer $uid UID of the starting Category
	 * @param integer $depth Recursive Depth
	 * @return array
	 */
	public function getRecordsDbList($uid, $depth = 2) {
		if (!is_numeric($uid) || !is_numeric($depth)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getRecordsDbList (categorydata) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Check if User's Group may view the records
		if (!$backendUser->check('tables_select', $this->table)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getRecordsDbList (categorydata): Usergroup is not allowed to view the records.', COMMERCE_EXTKEY, 2);
			}
			return array();
		}

		$this->setUid($uid);
		$this->setDepth($depth);

		$records = $this->getRecordsByUid();

		return $records;
	}

	/**
	 * Returns the Category Root record
	 *
	 * @return array
	 */
	protected function getRootRecord() {
		$root = array();

		$root['uid'] = 0;
		$root['pid'] = 0;
		$root['title'] = $this->getLL('leaf.category.root');
			// root always has pm icon
		$root['hasChildren'] = 1;
		$root['lastNode'] = TRUE;
		$root['item_parent'] = 0;

		return $root;
	}
}

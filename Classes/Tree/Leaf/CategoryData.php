<?php
namespace CommerceTeam\Commerce\Tree\Leaf;
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

/**
 * Implements the \CommerceTeam\Commerce\Tree\Leaf\Data for the Category
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\CategoryData
 *
 * @author 2008-2009 Erik Frister <typo3@marketing-factory.de>
 */
class CategoryData extends MasterData {
	/**
	 * Permission Mask for reading Categories
	 *
	 * @var int
	 */
	protected $permsMask = 1;

	/**
	 * Make this as a var which field is used as item_parent
	 *
	 * @var string
	 */
	protected $extendedFields  = 'parent_category, title, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody,
		editlock, hidden, starttime, endtime, fe_group';

	/**
	 * Database table
	 *
	 * @var string
	 */
	protected $table = 'tx_commerce_categories';

	/**
	 * Mm relation field
	 *
	 * @var string
	 */
	protected $item_parent = 'uid_foreign';

	/**
	 * Table to read the leafitems from
	 *
	 * @var string
	 */
	protected $itemTable = 'tx_commerce_categories';

	/**
	 * Relation table
	 *
	 * @var string
	 */
	protected $mmTable = 'tx_commerce_categories_parent_category_mm';

	/**
	 * Flag if mm table is to be used or the parent field
	 *
	 * @var bool
	 */
	protected $useMMTable = TRUE;

	/**
	 * Sets the Permission Mask for reading Categories from the db
	 *
	 * @param int $mask Mask for reading the permissions
	 *
	 * @return void
	 */
	public function setPermsMask($mask) {
		if (!is_numeric($mask)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'setPermsMask (categorydata) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
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
		$this->whereClause = ' deleted = 0 AND ' .
			\CommerceTeam\Commerce\Utility\BackendUtility::getCategoryPermsClause($this->permsMask);
		$this->order = 'tx_commerce_categories.sorting ASC';
	}

	/**
	 * Loads and returns the Array of Records (for db_list)
	 *
	 * @param int $uid UID of the starting Category
	 * @param int $depth Recursive Depth
	 *
	 * @return array
	 */
	public function getRecordsDbList($uid, $depth = 2) {
		if (!is_numeric($uid) || !is_numeric($depth)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'getRecordsDbList (categorydata) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return array();
		}

		$backendUser = $this->getBackendUser();

		// Check if User's Group may view the records
		if (!$backendUser->check('tables_select', $this->table)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'getRecordsDbList (categorydata): Usergroup is not allowed to view the records.',
					COMMERCE_EXTKEY,
					2
				);
			}
			return array();
		}

		$this->setUid($uid);
		$this->setDepth($depth);

		return $this->getRecordsByUid();
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


	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}

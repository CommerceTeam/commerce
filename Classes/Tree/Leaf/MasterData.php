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
 * Implements the data view for a master leaf
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\MasterData
 *
 * @author 2008-2009 Erik Frister <typo3@marketing-factory.de>
 */
abstract class MasterData extends Data {
	/**
	 * Do we want to read the leafs by Mountpoints
	 *
	 * @var bool
	 */
	protected $useMountpoints = FALSE;

	/**
	 * UID of the Items that acts as UBER-Parent (in case we read byUid not byMounts)
	 *
	 * @var int
	 */
	protected $uid;

	/**
	 * Recursive Depth if we are reading by UIDs
	 *
	 * @var int
	 */
	protected $depth;

	/**
	 * Flag if mounts should be ignored
	 *
	 * @var bool
	 */
	protected $ignoreMounts = FALSE;

	/**
	 * To be overridden by child classes
	 *
	 * @return void
	 */
	public function init() {
	}

	/**
	 * Initializes the item records
	 *
	 * @param int $index Index
	 * @param array $indices Indices
	 *
	 * @return void
	 */
	public function initRecords($index, array &$indices) {
		if (!is_numeric($index) || !is_array($indices)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'initRecords (CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return;
		}

		/*
		 * @todo
		 * Error Handling should be improved in this case, since in case
		 * of no access to records, no records would be read when selecting
		 * the mounts and the error woudl be no Mounts
		 */
		// Check if User's Group may view the records
		$backendUser = $this->getBackendUser();
		if (!$backendUser->check('tables_select', $this->table)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'initRecords (CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData): Usergroup is not allowed to view the records.',
					COMMERCE_EXTKEY,
					2
				);
			}
			$this->records = NULL;
			return;
		}

		// Check if we have access to the records.
		if (!$backendUser->check('tables_select', $this->table)) {
			return;
		}

		// Get the records
		if ($this->useMountpoints) {
			// Get the records by Mountpoint
			$this->records = $this->getRecordsByMountpoints($index, $indices);
		} else {
			// Get the records by Uid
			$this->records = $this->getRecordsByUid();
		}
	}

	/**
	 * Sets the Mount Ids
	 *
	 * @param array $mountIds Array with the item uids which are mounts for the user
	 *
	 * @return void
	 */
	public function setMounts(array $mountIds) {
		if (!is_array($mountIds)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'setMounts (CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return;
		}

		$this->mountIds = $mountIds;
		$this->useMountpoints = TRUE;
	}

	/**
	 * Sets the UID of the item which acts as the uber-parent
	 *
	 * @param int $uid UID of the Uber-item
	 * 	(could be a mountpoint, but a separate function exists for those)
	 *
	 * @return void
	 */
	public function setUid($uid) {
		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'setUid (CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return;
		}
		$this->uid = $uid;
		$this->useMountpoints = FALSE;
	}

	/**
	 * Get uid
	 *
	 * @return int
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Sets the depth of the recursion
	 *
	 * @param int $depth Depth of Recursion
	 *
	 * @return void
	 */
	public function setDepth($depth) {
		if (!is_numeric($depth)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'setDepth (CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return;
		}
		$this->depth = $depth;
		$this->useMountpoints = FALSE;
	}

	/**
	 * Initializes the Records by the Mountpoints
	 *
	 * @param int $index Index of the current leaf
	 * @param array $indices Array with parent indices
	 *
	 * @return array Records-Array
	 */
	protected function getRecordsByMountpoints($index, array &$indices) {
		if (!is_numeric($index) || !is_array($indices) || !is_array($this->mountIds) || 0 == count($this->mountIds)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'getRecordsByMountpoints (CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return NULL;
		}

		// First prepare hook objects
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['commerce/class.leafMasterData.php']['getRecordsByMountpointsClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['commerce/class.leafMasterData.php']['getRecordsByMountpointsClass'] as
				$classRef
			) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}

		$positions = $this->getPositionsByIndices($index, $indices);

		// Add the subquery - this makes sure that we not only read all
		// categories that are currently visible, but also their ("hidden") children
		if ($this->useMMTable) {
			$subquery = 'SELECT uid_local FROM ' . $this->mmTable . ' WHERE uid_foreign IN (' .
				implode(',', array_merge($positions, $this->mountIds)) . ') OR uid_local IN (' . implode(',', $this->mountIds) . ')';

			// uids of the items that are used as parents
			// this gets all the children from the parent items
			$this->where['uid_foreign'] = $subquery;
			// uids of the items that are the parents - this gets the mounts
			$this->where['uid_local'] = $subquery;
		} else {
			$subquery = 'SELECT uid FROM ' . $this->itemTable . ' WHERE ' . $this->itemParentField . ' IN (' .
				implode(',', array_merge($positions, $this->mountIds)) . ') OR uid IN (' . implode(',', $this->mountIds) . ')';

			$this->where[$this->itemParentField] = $subquery;
			$this->where['uid'] = $subquery;
		}

		// Hook: getRecordsByMountpoints_preLoadRecords
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'getRecordsByMountpoints_preLoadRecords')) {
				$hookObj->getRecordsByMountpoints_preLoadRecords($positions, $this);
			}
		}

		$records = $this->loadRecords();

		// Hook: getRecordsByMountpoints_postProcessRecords
		// useful especially if you are reading your tree items from an MM table and
		// have the mountpoint 0 - that mountpoint is not in the DB and thus you won't
		// see the correct tree if you belong to that group, use this mount to create
		// the relations in the MM table to the fictional root record
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'getRecordsByMountpoints_postProcessRecords')) {
				$hookObj->getRecordsByMountpoints_postProcessRecords($records, $this);
			}
		}

		return $records;
	}

	/**
	 * Initializes the Records by a starting item
	 *
	 * @return array
	 */
	protected function getRecordsByUid() {
		// Get all Uids
		$uids = $this->getRecursiveUids($this->uid, $this->depth);

		if (!is_array($uids) || 0 == count($uids)) {
			$result = NULL;
		} else {
			$this->where['uid_local'] = implode(',', $uids);
			$this->where['uid_foreign'] = '0';

			$result = $this->loadRecords();
		}

		return $result;
	}

	/**
	 * Returns an array with all Uids that should be read
	 *
	 * @param int $uid UID to be added and recursed
	 * @param int $depth Recursive Depth
	 * @param array|NULL $array Result
	 *
	 * @return array
	 */
	protected function getRecursiveUids($uid, $depth, &$array = NULL) {
		if (!is_numeric($uid) || !is_numeric($depth)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'getRecursiveUids (CommerceTeam\\Commerce\\Tree\\Leaf\\MasterData) gets passed invalid parameters.',
					COMMERCE_EXTKEY,
					3
				);
			}
			return array();
		}

		if ($depth <= 0) {
			return NULL;
		}
		if ($array === NULL) {
			$array = array();
		}

		$array[] = $uid;

		$database = $this->getDatabaseConnection();
		if ($this->useMMTable) {
			$res = $database->exec_SELECTquery('uid_local AS uid', $this->mmTable, 'uid_foreign = ' . $uid);
		} else {
			$res = $database->exec_SELECTquery('uid', $this->itemTable, $this->itemParentField . ' = ' . $uid);
		}

		while (($row = $database->sql_fetch_assoc($res))) {
			$this->getRecursiveUids($row['uid'], $depth - 1, $array);
		}

		return $array;
	}

	/**
	 * Loads the records of a given query and stores it
	 *
	 * @return array Records array
	 */
	public function loadRecords() {
		$rows = parent::loadRecords();

			// Add the root if it is the starting ID or in the mounts
		if (
			!$this->ignoreMounts
			&& (
				(!$this->useMountpoints && $this->uid == 0)
				|| ($this->useMountpoints && in_array(0, $this->mountIds))
			)
		) {
			$rows['uid'][0] = $this->getRootRecord();
		}

		$this->records = $rows;

		return $rows;
	}

	/**
	 * Returns the Root record - should be overridden by extending classes
	 *
	 * @return array
	 */
	protected function getRootRecord() {
		$root = array();

		$root['uid'] = 0;
		$root['pid'] = 0;
		$root['title'] = $this->getLL('leaf.leaf.root');
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

	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

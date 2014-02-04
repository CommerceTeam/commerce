<?php
/**
 * Implements the data view for a master leaf
 */
abstract class Tx_Commerce_Tree_Leaf_MasterData extends Tx_Commerce_Tree_Leaf_Data {
	/**
	 * Do we want to read the leafs by Mountpoints
	 *
	 * @var boolean
	 */
	protected $useMountpoints = FALSE;

	/**
	 * UID of the Items that acts as UBER-Parent (in case we read byUid, not byMounts)
	 *
	 * @var integer
	 */
	protected $uid;

	/**
	 * Recursive Depth if we are reading by UIDs
	 *
	 * @var integer
	 */
	protected $depth;

	/**
	 * Flag if mounts should be ignored
	 *
	 * @var boolean
	 */
	protected $ignoreMounts = FALSE;

	/**
	 * to be overridden by child classes
	 *
	 * @return void
	 */
	public function init() {
	}

	/**
	 * Initializes the item records
	 *
	 * @param array $index
	 * @param array $indices
	 * @return void
	 */
	public function initRecords($index, &$indices) {
		if (!is_numeric($index) || !is_array($indices)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('initRecords (Tx_Commerce_Tree_Leaf_MasterData) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

		/**
		 * @TODO
		 * Error Handling should be improved in this case, since in case of no access to records, no records would be read
		 * when selecting the mounts and the error woudl be no Mounts
		 */
			// Check if User's Group may view the records
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = & $GLOBALS['BE_USER'];
		if (!$backendUser->check('tables_select', $this->table)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('initRecords (Tx_Commerce_Tree_Leaf_MasterData): Usergroup is not allowed to view the records. ', COMMERCE_EXTKEY, 2);
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
			$this->records = &$this->getRecordsByMountpoints($index, $indices);
		} else {
				// Get the records by Uid
			$this->records = &$this->getRecordsByUid();
		}
	}

	/**
	 * Sets the Mount Ids
	 *
	 * @param array $mountIds - Array with the item uids which are mounts for the user
	 * @return void
	 */
	public function setMounts($mountIds) {
		if (!is_array($mountIds)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setMounts (Tx_Commerce_Tree_Leaf_MasterData) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

		$this->mountIds = $mountIds;
		$this->useMountpoints = TRUE;
	}

	/**
	 * Sets the UID of the item which acts as the uber-parent
	 *
	 * @return void
	 * @param integer $uid - UID of the Uber-item (could be a mountpoint, but a separate function exists for those)
	 */
	public function setUid($uid) {
		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setUid (Tx_Commerce_Tree_Leaf_MasterData) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->uid = $uid;
		$this->useMountpoints = FALSE;
	}

	/**
	 * Sets the depth of the recursion
	 *
	 * @return void
	 * @param integer $depth - Depth of Recursion
	 */
	public function setDepth($depth) {
		if (!is_numeric($depth)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setDepth (Tx_Commerce_Tree_Leaf_MasterData) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->depth = $depth;
		$this->useMountpoints = FALSE;
	}

	/**
	 * Initializes the Records by the Mountpoints
	 *
	 * @return array Records-Array
	 * @param $index {int}		Index of the current leaf
	 * @param $indices {array}	Array with parent indices
	 */
	protected function &getRecordsByMountpoints($index, &$indices) {
		if (!is_numeric($index) || !is_array($indices) || !is_array($this->mountIds) || 0 == count($this->mountIds)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getRecordsByMountpoints (Tx_Commerce_Tree_Leaf_MasterData) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return NULL;
		}

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['commerce/class.leafMasterData.php']['getRecordsByMountpointsClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['commerce/class.leafMasterData.php']['getRecordsByMountpointsClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		$positions = $this->getPositionsByIndices($index, $indices);

			// Add the subquery - this makes sure that we not only read all categories that are currently visible, but also their ("hidden") children
		if ($this->useMMTable) {
			$subquery = 'SELECT uid_local FROM ' . $this->mmTable . ' WHERE uid_foreign IN (' .
				implode(',', array_merge($positions, $this->mountIds)) . ') OR uid_local IN (' . implode(',', $this->mountIds) . ')';

				// uids of the items that are used as parents - this gets all the children from the parent items
			$this->where['uid_foreign'] = $subquery;
				// uids of the items that are the parents - this gets the mounts
			$this->where['uid_local'] = $subquery;
		} else {
			$subquery 	= 'SELECT uid FROM ' . $this->itemTable . ' WHERE ' . $this->itemParentField . ' IN (' .
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
			// useful especially if you are reading your tree items from an MM table and have the mountpoint 0 - that mountpoint is not in the DB and thus you won't see the correct tree
			// if you belong to that group, use this mount to create the relations in the MM table to the fictional root record
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
	protected function &getRecordsByUid() {
			// Get all Uids
		$uids = $this->getRecursiveUids($this->uid, $this->depth);

		if (!is_array($uids) || 0 == count($uids)) {
			return NULL;
		}

		$this->where['uid_local'] = implode(',', $uids);
		$this->where['uid_foreign'] = '0';

		return $this->loadRecords();
	}

	/**
	 * Returns an array with all Uids that should be read
	 *
	 * @param integer $uid - UID to be added and recursed
	 * @param integer $depth - Recursive Depth
	 * @param array|NULL $array
	 * @return array
	 */
	protected function &getRecursiveUids($uid, $depth, &$array = NULL) {
		if (!is_numeric($uid) || !is_numeric($depth)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getRecursiveUids (Tx_Commerce_Tree_Leaf_MasterData) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
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

		/** @var t3lib_db $database */
		$database = & $GLOBALS['TYPO3_DB'];
		if ($this->useMMTable) {
			$res = $database->exec_SELECTquery('uid_local AS uid', $this->mmTable, 'uid_foreign = ' . $uid);
		} else {
			$res = $database->exec_SELECTquery('uid', $this->itemTable, $this->itemParentField . ' = ' . $uid);
		}

		while ($row = $database->sql_fetch_assoc($res)) {
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
		if (!$this->ignoreMounts && ((!$this->useMountpoints && $this->uid == 0) || ($this->useMountpoints && in_array(0, $this->mountIds)))) {
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
}

class_alias('Tx_Commerce_Tree_Leaf_MasterData', 'leafMasterData');

?>
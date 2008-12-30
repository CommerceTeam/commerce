<?php
/**
 * Implements the data view for a master leaf
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leafData.php');

abstract class leafMasterData extends leafData{
	
	protected $useMountpoints = false;	//Do we want to read the leafs by Mountpoints
	protected $uid;						//UID of the Items that acts as UBER-Parent (in case we read byUid, not byMounts)
	protected $depth;					//Recursive Depth if we are reading by UIDs
	protected $ignoreMounts = false;	//Flag if mounts should be ignored
	
	/**
	 * Initializes the item records
	 * 
	 * @param {array} $uids - UID of item
	 * @param {array} $pids - Parent UIDS for Child items
	 * @return {void}
	 */
	public function initRecords($index, &$indices) {	
		if(!is_numeric($index) || !is_array($indices)) {
			if (TYPO3_DLOG) t3lib_div::devLog('initRecords (leafMasterData) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		/**
		 * @TODO
		 * Error Handling should be improved in this case, since in case of no access to records, no records would be read 
		 * when selecting the mounts and the error woudl be no Mounts
		 */
		//Check if User's Group may view the records
		if(!$GLOBALS['BE_USER']->check('tables_select',$this->table)) {
			if (TYPO3_DLOG) t3lib_div::devLog('initRecords (leafMasterData): Usergroup is not allowed to view the records. ', COMMERCE_EXTkey, 2);	
			$this->records = null;
			return;
		}
		
		//Get the records
		if($this->useMountpoints) {
			//Get the records by Mountpoint
			$this->records = &$this->getRecordsByMountpoints($index, $indices);
		} else {
			//Get the records by Uid
			$this->records = &$this->getRecordsByUid();
		}
		
	}
	
	/**
	 * Sets the Mount Ids
	 * 
	 * @param {array} $mountIds - Array with the item uids which are mounts for the user
	 * @return {void}
	 */
	function setMounts($mountIds) {
		if(!is_array($mountIds)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setMounts (leafMasterData) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		$this->mountIds 		= $mountIds;
		$this->useMountpoints 	= true;
	}
	
	/**
	 * Sets the UID of the item which acts as the uber-parent
	 * 
	 * @return {void}
	 * @param {int} $uid - UID of the Uber-item (could be a mountpoint, but a separate function exists for those)
	 */
	function setUid($uid) {
		if(!is_numeric($uid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setUid (leafMasterData) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->uid 				= $uid;
		$this->useMountpoints 	= false;
	}
	
	/**
	 * Sets the depth of the recursion
	 * 
	 * @return {void}
	 * @param {int} $depth - Depth of Recursion
	 */
	function setDepth($depth) {
		if(!is_numeric($depth)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setDepth (leafMasterData) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->depth 			= $depth;
		$this->useMountpoints 	= false;
	}
	
	/**
	 * Initializes the Records by the Mountpoints
	 * 
	 * @return {object}	Records-Array
	 * @param $index {int}		Index of the current leaf
	 * @param $indices {array}	Array with parent indices
	 */
	protected function &getRecordsByMountpoints($index, &$indices) {
		global $TYPO3_CONF_VARS;
		
		if(!is_numeric($index) || !is_array($indices) || !is_array($this->mountIds) || 0 == count($this->mountIds)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getRecordsByMountpoints (leafMasterData) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return null;	
		}
		
		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['commerce/class.leafMasterData.php']['getRecordsByMountpointsClass'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['commerce/class.leafMasterData.php']['getRecordsByMountpointsClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		
		$positions = $this->getPositionsByIndices($index, $indices);
		
		//Add the subquery - this makes sure that we not only read all categories that are currently visible, but also their ("hidden") children
		if($this->useMMTable) {
			$subquery 	= 'SELECT uid_local FROM '.$this->mmTable.' WHERE uid_foreign IN ('.implode(',', array_merge($positions, $this->mountIds)).') OR uid_local IN ('.implode(',', $this->mountIds).')';
		
			$this->where['uid_foreign'] = $subquery; 	//uids of the items that are used as parents - this gets all the children from the parent items
			$this->where['uid_local']	= $subquery;	//uids of the items that are the parents - this gets the mounts 
		} else {
			$subquery 	= 'SELECT uid FROM '.$this->itemTable.' WHERE '.$this->itemParentField.' IN ('.implode(',', array_merge($positions, $this->mountIds)).') OR uid IN ('.implode(',', $this->mountIds).')';
		
			$this->where[$this->itemParentField] = $subquery;
			$this->where['uid'] = $subquery;
		}
		#debug($this->where,'where',__LINE__,__FILE__);
		// Hook: getRecordsByMountpoints_preLoadRecords
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'getRecordsByMountpoints_preLoadRecords')) {
				$hookObj->getRecordsByMountpoints_preLoadRecords($positions, $this);
			}
		}
		
		$records = $this->loadRecords();	
		#debug($records,'records',__LINE__,__FILE__);
		//Hook: getRecordsByMountpoints_postProcessRecords
		//useful especially if you are reading your tree items from an MM table and have the mountpoint 0 - that mountpoint is not in the DB and thus you won't see the correct tree
		//if you belong to that group, use this mount to create the relations in the MM table to the fictional root record
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'getRecordsByMountpoints_postProcessRecords')) {
				$hookObj->getRecordsByMountpoints_postProcessRecords($records, $this);
			}
		}
		
		return $records;
	}
	
	/**
	 * Initializes the Records by a starting item
	 * 
	 * @return {array}
	 */
	protected function &getRecordsByUid() {
		
		//Get all Uids
		$uids = $this->getRecursiveUids($this->uid, $this->depth);
		
		if(!is_array($uids) || 0 == count($uids)) return null;
	
		$this->where['uid_local'] 	= implode(',',$uids);
		$this->where['uid_foreign'] = '0';
		
		return $this->loadRecords();
	}
	
	
	/**
	 * Returns an array with all Uids that should be read
	 * 
	 * @param {int} $uid - UID to be added and recursed
	 * @param {int} $depth - Recursive Depth
	 * @return {array}
	 */
	protected function &getRecursiveUids($uid, $depth, &$array = null) {
		if(!is_numeric($uid) || !is_numeric($depth)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getRecursiveUids (leafMasterData) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return array();	
		}
		
		if($depth <= 0) 	return null;
		if(null == $array) 	$array = array();
		
		$array[] = $uid;
		
		if($this->useMMTable) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_local AS uid', $this->mmTable, 'uid_foreign = '.$uid);
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $this->itemTable, $this->itemParentField.' = '.$uid);
		}
		
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->getRecursiveUids($row['uid'], $depth - 1, $array);
		}
		
		return $array;
	}
	
	/**
	 * Loads the records of a given query and stores it
	 * 
	 * @return {array}	Records array
	 */
	public function loadRecords() {
		
		$rows = parent::loadRecords();
		
		//Add the root if it is the starting ID or in the mounts
		if(!$this->ignoreMounts && ((!$this->useMountpoints && $this->uid == 0) || ($this->useMountpoints && in_array(0, $this->mountIds))))
		{
			$rows['uid'][0] = $this->getRootRecord();
		}	
		
		$this->records = $rows;
		
		return $rows;
	}
	
	/**
	 * Returns the Root record - should be overridden by extending classes
	 * 
	 * @return {array}
	 */
	protected function getRootRecord() {
		$root = array();
		
		$root['uid']		 	= 0;
		$root['pid'] 			= 0;
		$root['title'] 			= $this->getLL('leaf.leaf.root');
		$root['hasChildren'] 	= 1; //root always has pm icon
		$root['lastNode'] 		= true;
		$root['item_parent'] 	= 0;
		
		return $root;
	}
}
?>

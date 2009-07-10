<?php
/**
 * Implements the data view of the leaf
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
require_once(t3lib_extmgm::extPath('commerce').'tree/class.langbase.php');

class leafData extends langbase {
	
	protected $positionArray;			//Complete Array of position IDs
	protected $positionUids;	 		//Array with only the position uids of the current leaf
	protected $positionMountUids;		//Holds an array with the positionUids per mount [mount] => array(pos1, pos2,...,posX)
	protected $bank; 					//Item UID of the Mount for this Data
	protected $table;					//Name of the Table to read from
	protected $mountIds;				//Array with the Mount IDs (UID of Items that act as mounts OR the root mount)
	
	//used to load records
	protected $from				= '';	/*	DB Table Statement								*/
	//protected $where 			= '';   /*	DB Where Statement								*/
	protected $limit			= '';	/*	DB Limit Statement								*/
	protected $order			= '';	/*	DB Order Statement								*/
	protected $extendedFields 	= '';	//Used to load additional fields  -  for extending classes
	protected $whereClause;				//WHERE-Clause of the SELECT; will be calculated depending on if we read them recursively or by Mountpoints
	protected $defaultFields  	= 'uid, pid'; 	//Default Fields that will be read
	protected $item_parent		= '';	//field that will be aliased as item_parent; MANDATORY!
	
	//new try to make this general
	protected $itemTable;		//table to read the leafitems from
	protected $mmTable;			//table that is to be used to find parent items
	protected $itemParentField;	//if no mm table is used, this field will be used to get the parents
	protected $useMMTable;		//Flag if mm table is to be used or the parent field
	protected $where;		//Array with uids to the uid_local and uid_foreign field if mm is used
	
	//Calculated
	protected $sorted		= false;
	protected $sortedArray  = null;
	protected $records 		= null;			//Holds the records
	
	
	/**
	 * Returns the table name
	 * 
	 * @return {string}	Table name
	 */
	function getTable() {
		return $this->table;
	}
	
	/**
	 * Returns the position Uids for the items
	 * 
	 * @return {array}
	 */
	function getPositionsUids() {
		return $this->positionUids;
	}
	
	/**
	 * Returns the positions for the supplied mount (has to be set by setBank)
	 * 
	 * @return {array}
	 */
	function getPositionsByMountpoint() {
		$ret = $this->positionMountUids[$this->bank];
		return ($ret != null) ? $ret : array();
	}
	
	/**
	 * Returns true if this leaf is currently expanded
	 * 
	 * @param {int} $uid 	uid of the current row
	 * @return {boolean}
	 */
	public function isExpanded($uid) {
		if(!is_numeric($uid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('isExpanded (leafdata) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		//Check if the UID is in the Position-Array
		return (in_array($uid, $this->getPositionsByMountpoint()));	
	}
	
	/**
	 * Sets the position Ids
	 * 
	 * @return {void}
	 * @param {array} $positionIds - Array with the Category uids which are current positions of the user
	 */
	function setPositions(&$positionIds) {
		if(!is_array($positionIds)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setPositions (leafdata) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->positionArray 	= $positionIds;
	}
	
	/**
	 * Returns an array of Positions
	 * 
	 * @return {array}
	 * @param $index {int}		Index of this leaf
	 * @param $indices {int}	Parent Indices
	 */
	function getPositionsByIndices($index, $indices) {
		if(!is_numeric($index) || !is_array($indices)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getPositionsByIndices (productdata) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return array();	
		}
		
		$m = count($indices);
		
		//Construct the Array of Position Ids
		$firstIndex = (0 >= $m) ? $index : $indices[0];
		
		//normally we read the mounts
		$mounts = $this->mountIds;
		$l 		= count($mounts);
		
		//if we didn't find mounts, exit
		if(0 == $l) {
			if (TYPO3_DLOG) t3lib_div::devLog('getPositionsByIndices (leafData) cannot proceed because it did not find mounts', COMMERCE_EXTkey, 3);	
			return array();
		}
		
		$positions = array();
		$posIds	   = array();
		
		for($i = 0; $i < $l; $i ++) {
			$posIds = $this->positionArray[$firstIndex][$mounts[$i]];
			
			//Go to the correct Leaf in the Positions
			if(0 < $m) {
				//Go to correct parentleaf
				for($j = 1; $j < $m; $j ++) {
					$posIds = $posIds[$indices[$j]];
				}
				//select current leaf
				$posIds = $posIds[$index];
			}
			
			//If no Items are set for the current Leaf, skip it
			if(!is_array($posIds['items'])) {
				continue;
			}
			
			$positionUids = array_keys($posIds['items']);	//Get the position uids
			
			$this->positionMountUids[$mounts[$i]] = $positionUids;	//Store in the Mount - PosUids Array
			$positions = array_merge($positions, $positionUids);			//Store in Array of all UIDS
		}
		
		$this->positionUids = $positions;
		
		return $positions;
	}
	
	/**
	 * Sets the bank
	 * 
	 * @param {int} $bank - Category UID of the Mount (aka Bank)
	 * @return {void}
	 */
	function setBank($bank) {
		if(!is_numeric($bank)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setBank (leafdata) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->bank = $bank;
	}
	
	/**
	 * Returns the records
	 * 
	 * @return {object}		Records of the leaf
	 */
	function getRecords() {
		return $this->records;
	}
	
	/**
	 * Returns the open uids of this leaf
	 * 
	 * @return {array}	Open uids 
	 */
	function getOpenRecordUids() {
		return $this->positionUids;
	}
	
	/**
	 * Returns the Uids of the records in an array
	 * 
	 * @return {array}		Uids of the records
	 */
	function getRecordsUids() {
		if(!$this->isLoaded() || !is_array($this->records['uid'])) {
			return null;
		}
		
		return array_keys($this->records['uid']);
	}
	
	/**
	 * Returns whether this leafdata has been loaded
	 * 
	 * @return {boolean}
	 */
	function isLoaded() {
		return (null != $this->records);
	}
	
	/**
	 * Sorts the records to represent the linar structure of the tree
	 * Stores the resulting array in an internal variable
	 * 
	 * @param {int} $rootUid - UID of the Item that will act as the root to the tree
	 * @return {void}
	 */
	function sort($rootUid, $depth = 0, $last = false, $crazyRecursionLimiter = 999) {
		if(!is_numeric($rootUid) || !is_numeric($depth) || !is_numeric($crazyRecursionLimiter)) {
			if (TYPO3_DLOG) t3lib_div::devLog('sort (leafdata) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		//Return if the records are already sorted
		if($this->sorted) return;
		if($crazyRecursionLimiter <= 0) return; //Prevent endless recursion
		
		if(isset($this->records['uid'][$rootUid])) {
			
			//Place the current record in the array
			$entry = array();
			$entry['record'] = $this->records['uid'][$rootUid];
			$entry['depth']  = $depth;
			$entry['last']   = $last;
			
			$this->sortedArray[] = $entry;
			
			//Get the children and iterate
			$children = $this->getChildrenByPid($rootUid);
			
			$l = count($children);
			
			for($i = 0; $i < $l; $i ++) {
				$this->sort($children[$i]['uid'], $depth + 1, ($i == $l - 1),$crazyRecursionLimiter - 1);
			}
		} 
		
		//Set sorted to True to block further sorting - only after all recursion is done
		if(0 == $depth) {
			$this->sorted = true;
		}
	}
	
	/**
	 * Returns the sorted array
	 * False if the data has not been sorted yet
	 * 
	 * @return {array} 
	 */
	function &getSortedArray() {
		if(!$this->sorted) return false;
		
		return $this->sortedArray;
	}
	
	/**
	 * Returns if the data has loaded any records
	 * 
	 * @return {boolean}
	 */
	function hasRecords() {
		if(!$this->isLoaded()) return false;
		return !(0 >= count($this->records['uid']) && 0 >= count($this->records['pid']));
	}
	
	/**
	 * Returns a record from the 'uid' array
	 * Returns null if the index is not found
	 * 
	 * @param {int} $uid - UID for which we will look
	 * @return {array}
	 */
	function &getChildByUid($uid) {
		if(!is_numeric($uid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getChildByUid (leafdata) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return null;	
		}
		
		if(!is_array($this->records) || !isset($this->records['uid']) || !is_array($this->records['uid'])) {
			//if (TYPO3_DLOG) t3lib_div::devLog('getChildByUid (leafdata) cannot find a dataitem by uid.', COMMERCE_EXTkey, 2);	
			return null;	
		}

		return $this->records['uid'][$uid];
	}
	
	/**
	 * Returns a subset of records from the 'pid' array
	 * Returns null if PID is not found
	 * 
	 * @return {array}
	 */
	function &getChildrenByPid($pid) {
		if(!is_numeric($pid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getChildrenByPid (leafdata) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return null;	
		}
		
		if(!is_array($this->records) || !isset($this->records['pid']) || !is_array($this->records['pid'])) {
			//if (TYPO3_DLOG) t3lib_div::devLog('getChildrenByPid (leafdata) cannot find dataitems by uid.', COMMERCE_EXTkey, 2);	
			return null;	
		}

		return $this->records['pid'][$pid];
	}
	
	/**
	 * Loads the records of a given query and stores it
	 * 
	 * @return {array}	Records array
	 */
	function loadRecords() {
		
		//Add the extended fields to the select statement
		$select = (is_string($this->extendedFields) && '' != $this->extendedFields) ? $this->defaultFields.','.$this->extendedFields : $this->defaultFields;
		
		//add item parent
		$select .= ','.$this->item_parent.' AS item_parent';
		
		//add the item search
		if($this->useMMTable) {
			$where .= ('' == $this->whereClause) ? '' : ' AND '.$this->whereClause;
			$where .= ' AND (uid_foreign IN ('.$this->where['uid_foreign'].') OR uid_local IN ('.$this->where['uid_local'].'))';	
		} else {
			$where  = $this->whereClause;
			$where .= ('' == $this->whereClause) ? '' : ' AND ';
			$where .= '('.$this->itemParentField.' IN ('.$this->where[$this->itemParentField].') OR uid IN('.$this->where['uid'].'))';
		}
	#debug($where,'where',__LINE__,__FILE__);
		//exec the query
		if($this->useMMTable) {
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query($select, $this->itemTable, $this->mmTable, '',$where, '',$this->order, $this->limit);
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $this->itemTable, $where, '', $this->order, $this->limit);
		}
		
		if($GLOBALS['TYPO3_DB']->sql_error()) {
			if (TYPO3_DLOG) t3lib_div::devLog('loadRecords (leafdata) could not load records. Possible sql error. Empty rows returned.', COMMERCE_EXTkey, 3);	
			return array();	
		}
		
		$checkRightRow = false;	// Will hold a record to check rights against after this loop.
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
		{	
			//get the version overlay if wanted
			$parentItem = $row['item_parent'];	//store parent item
			unset($row['item_parent']);			//unset the pseudo-field (no pseudo-fields allowed for workspaceOL)
			
			t3lib_BEfunc::workspaceOL($this->itemTable, $row);	//overlay
			
			if(!is_array($row)) {
				debug('There was an error overlaying a record with its workspace version.');
				continue;
			} else {
				$row['item_parent'] = $parentItem;	//write the pseudo field again
			}
			
			//the row will by default start with being the last node
			$row['lastNode'] = false;
			
			//Set the row in the 'uid' part
			$rows['uid'][$row['uid']] = $row;
			
			//Set the row in the 'pid' part
			if(!isset($rows['pid'][$row['item_parent']])) {
				 $rows['pid'][$row['item_parent']] = array($row);
			} else {
				//store
				$rows['pid'][$row['item_parent']][] = $row;
			}
			
			$checkRightRow = (false === $checkRightRow) ? $row : $checkRightRow;
		}
		
		//free memory
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		
		// Check perms on Commerce folders.
		if(false !== $checkRightRow && !$this->checkAccess($this->itemTable, $checkRightRow)) {
			if (TYPO3_DLOG) t3lib_div::devLog('loadRecords (leafdata) could not load records because it doesnt have permissions on the commerce folder. Return empty array.', COMMERCE_EXTkey, 3);	
			return array();
		}
		
		//Calculate the records which are last
		if(is_array($rows['pid'])) {
			$keys 		= array_keys($rows['pid']);
			$l	  		= count($keys);
			$lastIndex 	= null;
			
			for($i = 0; $i < $l; $i ++) {
				$lastIndex = end(array_keys($rows['pid'][$keys[$i]]));
				
				//Change last-attribute in 'uid' and 'pid' array - this now holds under which pids the record is last
				$uidItem = $rows['uid'][$rows['pid'][$keys[$i]][$lastIndex]['uid']]; //sh
				
				$rows['uid'][$rows['pid'][$keys[$i]][$lastIndex]['uid']]['lastNode'] = (false !== $uidItem['lastNode']) ? $uidItem['lastNode'].','.$keys[$i] : $keys[$i];
				$rows['pid'][$keys[$i]][$lastIndex]['lastNode'] = $keys[$i];
			}
		}
		
		$this->records = $rows;
		
		return $this->records;
	}
	
	/**
	 * Checks the page access rights (Code for access check mostly taken from alt_doc.php)
	 * as well as the table access rights of the user.
	 *
	 * @see 	tx_recycler
	 * @param	string		$cmd: The command that sould be performed ('new' or 'edit')
	 * @param	string		$table: The table to check access for
	 * @param	string		$theUid: The record uid of the table
	 * @return	boolean		Returns true is the user has access, or false if not
	 */
	public function checkAccess($table, $row) {
		// Checking if the user has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
		// First, resetting flags.
		$hasAccess = 0;
		$deniedAccessReason = '';
		
		$calcPRec = $row;
		t3lib_BEfunc::fixVersioningPid($table,$calcPRec);
		if (is_array($calcPRec)) {
			if ($table=='pages') {	// If pages:
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($calcPRec);
				$hasAccess = $CALC_PERMS & 2 ? 1 : 0;
			} else {
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$calcPRec['pid']));	// Fetching pid-record first.
				$hasAccess = $CALC_PERMS & 16 ? 1 : 0;
			}
		}
		
		if($hasAccess) {
			$hasAccess = $GLOBALS['BE_USER']->isInWebMount($calcPRec['pid'], '1=1');
		}

		return $hasAccess ? true : false;
	}
}
?>

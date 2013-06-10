<?php
/**
 * Implements the data view for leaf slave
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leafData.php');

abstract class leafSlaveData extends leafData{
	
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
		$firstIndex = $indices[0];
		#debug($this->positionArray,'$this->positionArray',__LINE__,__FILE__);
		if(!is_array($this->positionArray[$firstIndex])) {
			if (TYPO3_DLOG) t3lib_div::devLog('getPositionsByIndices (leafSlaveData) does not find the first Index in the position array.', COMMERCE_EXTkey, 2);	
			$this->positionUids = array();
			return $this->positionUids;
		}
		
		$this->mountIds = array_keys($this->positionArray[$firstIndex]);
		
		parent::getPositionsByIndices($index, $indices);
	}
	
	/**
	 * Initializes the Records
	 * All Products are read, no matter what the rights - only editing them is restricted!
	 * 
	 * @param $index {int}				Leaf index
	 * @param $parentIndices {array}	Parent Indices
	 * @param $parentLeafData {object}	LeafData of the Parent Leaf
	 * @return {void}
	 **/
	public function initRecords($index, $parentIndices, &$parentLeafData) {
		if(!is_numeric($index) || !is_array($parentIndices) || is_null($parentLeafData)) {
			if (TYPO3_DLOG) t3lib_div::devLog('initRecords (leafSlaveData) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		//Check if User's Group may view the records
		if(!$GLOBALS['BE_USER']->check('tables_select',$this->table)) {
			$this->records = null;
			if (TYPO3_DLOG) t3lib_div::devLog('initRecords User is not allowed to view table:'.$this->table, COMMERCE_EXTkey, 3);	
			return;
		}
		
		//Store the position Uids
		$this->getPositionsByIndices($index, $parentIndices);
		
		//Get the uids of the open parent - returns uids which are currently open
		$recordUids = $parentLeafData->getRecordsUids();
		
		if(null == $recordUids) return;
		
		//Read all items
		if($this->useMMTable) {
			$this->where['uid_foreign'] = implode(',', $recordUids);
			$this->where['uid_local']	= 0;
		} else {
			$this->where[$this->itemParentField] = implode(',', $recordUids);
			$this->where['uid'] = 0;
		}
		
		$this->records = $this->loadRecords();
	}
	
}
?>

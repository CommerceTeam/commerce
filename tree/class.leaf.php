<?php
/**
 * Implements an abstract leaf of the browseTree
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
require_once(t3lib_extmgm::extPath('commerce').'tree/class.langbase.php');

require_once (PATH_t3lib.'class.t3lib_iconworks.php');


abstract class leaf extends langbase{
	
	public $view;			//LeafView Object of the Leaf
	public $data;			//LeafData Object of the Leaf
	
	protected $leafs; 			//Leafs can contain leafs
	protected $leafcount;		//Amount of childleafs (ONLY direct children are counted)
	protected $BACK_PATH 		= '../../../../typo3/';
	protected $treeName;		//Treename
	protected $resetDone;		//have the last uids been resetted already?
	
	protected $parentClass = '';	//name of the parent class - MUST be overridden by extending classes!!!
	protected $selfClass 	= ''; 	//name of this class - is calculated in initBasic
	
	/**
	 * Sets the View and the Data of the Leaf
	 * 
	 * @return {void}
	 * @param {object} $view 	LeafView of the Leaf
	 * @param {object} $data 	LeafData of the Leaf
	 */
	public function initBasic(&$view, &$data) {
		if(is_null($view) || is_null($data)) {
			if (TYPO3_DLOG) t3lib_div::devLog('initBasic (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		//Storing the View and the Data and initializing the standard values
		$this->view 		= $view;
		$this->data 		= $data;
		
		$this->leafs 		= array();
		$this->leafcount 	= 0;
		//do NOT set treename or it will break the functionality
		$this->resetDone	= false;
		$this->selfClass 	= get_class($this); //store the name of this class
	}
	
	/**
	 * Passes to the leafview if it should enable the clickmenu
	 * 
	 * @return {void}
	 * @param $flag {boolean}[optional]	Flag
	 */
	public function noClickmenu($flag = true) {
		$this->view->noClickmenu($flag);
	}
	
	/**
	 * Adds a child leaf to the leaf
	 * 
	 * @param {object} $leaf 	Slave Leaf-Object
	 * @return {boolean}
	 */
	public function addLeaf(leafSlave &$leaf) {
		if(null == $leaf) {
			if (TYPO3_DLOG) t3lib_div::devLog('addLeaf (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;
		}
		
		//pass treename to the leaf
		$leaf->setTreeName($this->treeName);
		
		$this->leafs[$this->leafcount ++] = &$leaf;
		return true;
	}
	
	/**
	 * Stores the name of the tree
	 * 
	 * @return {void}
	 * @param $treeName {string}	Name of the tree
	 */
	public function setTreeName($treeName) {
		if(!is_string($treeName)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setTreeName (leaf) gets passed invalid parameters. Are set to default!', COMMERCE_EXTkey, 3);	
			$treeName = 'unassigned';
		}
		
		$this->treeName = $treeName;	
	}
	
	/**
	 * Returns the childleaf at a given index
	 * 
	 * @return {object} 	Childleaf
	 * @param $index {int}	Index of the childleaf
	 */
	public function &getChildLeaf($index) {
		if(!is_numeric($index)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getChildLeaf (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return null;	
		}
		
		if($index >= $this->leafcount) {
			if (TYPO3_DLOG) t3lib_div::devLog('getChildLeaf (leaf) has an index out of bounds.', COMMERCE_EXTkey, 3);	
			return null;
		}
		
		return $this->leafs[$index]; 
	}
	
	/**
	 * Pass the Item UID Array with the Userpositions to the LeafData
	 * 
	 * @return {void}
	 * @param $positionIds {array}	Array with item uids that are positions
	 */
	public function setPositions(&$positionIds) {
		if(!is_array($positionIds)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setPositions (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->data->setPositions($positionIds);
	}
	
	/**
	 * Initializes the leaf
	 * Passes the Parameters to its child leafs
	 * 
	 * @param $index {int}			Index of this leaf
	 * @param $parentIndices {array}Array with parent indices
	 * @return {void}
	 */
	public function init($index, $parentIndices = array()) {
		if(!is_numeric($index) || !is_array($parentIndices)) {
			if (TYPO3_DLOG) t3lib_div::devLog('init (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		//Store the index
		$this->view->setLeafIndex($index);
		$this->view->setParentIndices($parentIndices);
		
		//Add our own index to the parentIndices Array
		$parentIndices[] 	= $index;
		
		//Call 'init' for all child leafs - notice how the childleafs are NOT read by mounts
		for($i = 0; $i < $this->leafcount; $i ++) {
			$this->leafs[$i]->setParentLeaf($this);			//For every childleaf, set its parent leaf to the current leaf
			$this->leafs[$i]->init($i, $parentIndices);
		}
	}
	
	/**
	 * Sets the PositionIds for this leafs own LeafData and its ChildLeafs ("recursively")
	 * 
	 * @param $positions {array}	item uids that are positions
	 * @return {void}
	 */
	public function setDataPositions(&$positions) {
		if(!is_array($positions)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setDataPositions (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		$this->data->setPositions($positions);
		
		//"Recursive" Call
		for($i = 0; $i < $this->leafcount; $i ++) {
			$this->leafs[$i]->setDataPositions($positions);	
		}
	}
	
	/**
	 * Sorts the Leafdata in a way to represent the linear tree structure
	 * Sorts its leafs as well
	 * 
	 * @todo	So far this only sorts the masterLeaves correctly, but not all slave leafs - this is because the same uid is passed through the whole stack
	 * 
	 * @param {int} $rootUid 	uid of the Item that will act as the root of the tree
	 * @return {void} 
	 */
	public function sort($rootUid) {
		if(!is_numeric($rootUid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('sort (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		$this->data->sort($rootUid);
		
		//Sort Leafs
		for($i = 0; $i < $this->leafcount; $i ++) {
			$this->leafs[$i]->sort($rootUid);
		}
	}
	
	/**
	 * Returns the sorted array
	 * Merges with the sorted arrays of the leafs
	 * 
	 * @return {array} 
	 */
	public function &getSortedArray() {
		$sortedData = $this->data->getSortedArray();
		
		for($i = 0; $i < $this->leafcount; $i ++) {
			$sortedData = array_merge($sortedData, $this->leafs[$i]->getSortedArray());
		}
		
		return $sortedData;
	}
	
	/**
	 * Returns whether any following sibling of the current leaf (if it has any) has records
	 * 
	 * @todo Implement this if it is still logically needed
	 * @return {boolean} 
	 */
	function nextSiblingHasRecords() {
		/**
		 * NOT YET FULLY IMPLEMENTED BECAUSE THE SITUATION OF SIBLINGNODES IS NOT ARISING YET
		 */
		
		//if this leaf has no parent, obviously it has no siblings
		/*if(null == $this->parentLeaf) {
			return false;	
		}
		
		$l = $this->parentLeaf->leafcount;
		$currentPosition = false;
		
		//Find the current position of the leaf
		for($i = 0; $i < $l; $i ++) {
				
		}*/
	}
	
	/**
	 * Returns if any leaf (beneath this one) has subrecords for a specific row
	 * 
	 * @param $pid {int} Row Item which would be parent of the leaf's records
	 * @return {boolean} 
	 */
	public function leafsHaveRecords($pid) {
		if(!is_numeric($pid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('leafsHaveRecords (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}

		//if we have no leafs, we have no records - if we dont have an entry 'uid', what should we look for? - the row has to be expanded
		if(0 >= $this->leafcount || !$this->data->isExpanded($pid)) return false;
		
		for($i = 0; $i < $this->leafcount; $i ++) {
			//if the childleaf has children for the parent
			if(0 < count($this->leafs[$i]->data->getChildrenByPid($pid))) {
				return true;	
			}
		}
		return false;
	}
	
	/**
	 * Returns whether or not a node is the last in the current subtree
	 * 
	 * @return {boolean}
	 * @param $row {array}		Row Item
	 * @param $pid {int}		Parent UID of the current Row Item
	 * @param $i {int}			Current Index of the Row in the loop
	 * @param $l {int}			Length of the loop
	 */
	public function isLast($row, $pid = 0) {
		if(!is_array($row) || !is_numeric($pid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('isLast (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;
		}
		
		//If the row has an entry 'lastNode', its position is supplied from the DB - check if the item is last under the current pid
		$isLast = (isset($row['lastNode']) && t3lib_div::inList($row['lastNode'],$pid)) ? true : false;
		
		return $isLast;
	}
	
	/**
	 * Returns whether or not a node has Children
	 * 
	 * @param $row {array}	Row Item
	 * @return {boolean}
	 */
	public function hasChildren($row)  {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('hasChildren (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		$hasChildren = false;
		
		//check if any leaf has a subitem for the current row
		if($this->leafcount > 0) {
			for($i = 0; $i < $this->leafcount; $i ++) {
				$hasChildren = $this->leafs[$i]->hasSubitems($row);
				if(true == $hasChildren) break;
			}
		}
		return $hasChildren;
	}
	
	/**
	 * Returns whether we have at least 1 subitem for a specific parent row 
	 * 
	 * @return {boolean}
	 * @param $row {array}		Parent Row Information
	 */
	public function hasSubitems($row) {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('hasSubitems (leaf) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		$children = $this->data->getChildrenByPid($row['uid']);
		
		return (0 < count($children));
	}
}
?>

<?php
/**
 * Implements the performance class for leaf_category
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
class ux_tx_commerce_leaf_category extends tx_commerce_leaf_category {
	
	public 		$profiler; //Will be set by the parent tree
	protected 	$parentClass = 'ux_tx_commerce_leaf_category';
	/**
	 * Give the reference to the profiler to the data and to the view
	 */
	function init($index, $parentIndices = array()) {
		$this->profiler->enterSection('leaf_category->init');
		
		$this->data->profiler = & $this->profiler;
		$this->view->profiler = & $this->profiler;
		
		parent::init($index, $parentIndices);
		$this->profiler->leaveSection('leaf_category->init');
	}
	
	public function loadMountpoints() {
		$this->profiler->enterSection('leaf_category->loadMountpoints');
		parent::loadMountpoints();	
		$this->profiler->leaveSection('leaf_category->loadMountpoints');
	}
	
	public function printLeafByUid($catUid, $bank, $treeName) {
		$this->profiler->enterSection('leaf_category->printLeafByUid');
		parent::printLeafByUid($catUid, $bank, $treeName);	
		$this->profiler->leaveSection('leaf_category->printLeafByUid');
	}
	
	public function printChildleafsByLoop($startUid, $bank, $treeName) {
		$this->profiler->enterSection('leaf_category->printChildleafsByLoop');
		parent::printChildleafsByLoop($startUid, $bank, $treeName);	
		$this->profiler->leaveSection('leaf_category->printChildleafsByLoop');
	}
	
	/**
	 * Since this function is recursive, we need to add some extra flavour to it to make it work correctly
	 */
	public function printChildleafsByPid($pid, $bank, $treeName, $crazyRecursionLimiter = 999) {
		
		//Only start the section at the very first call (which is not recursive)
		if($crazyRecursionLimiter == 999) {
			$this->profiler->enterSection('leaf_category->printChildleafsByPid');
		}
		
		parent::printChildleafsByPid($pid, $bank, $treeName, $crazyRecursionLimiter);	
		
		//Only end the section at the very first call (which is not recursive)
		if($crazyRecursionLimiter == 999) {
			$this->profiler->leaveSection('leaf_category->printChildleafsByPid');
		}
	}
	
	/***********************
	 * Methods to overwrite the leaf baseclass
	 **********************/
	function byMounts($flag = true) {
		$this->profiler->enterSection('leaf_category->byMounts');
		parent::byMounts($flag);	
		$this->profiler->leaveSection('leaf_category->byMounts');
	}
	
	function addLeaf(tx_commerce_leaf &$leaf) {
		$this->profiler->enterSection('leaf_category->addLeaf');
		parent::addLeaf($leaf);	
		$this->profiler->leaveSection('leaf_category->addLeaf');
	}
	
	function &getChildLeaf($index) {
		$this->profiler->enterSection('leaf_category->getChildLeaf');
		$el = parent::getChildLeaf($index);	
		$this->profiler->leaveSection('leaf_category->getChildLeaf');
		return $el;
	}
	
	function setMounts(&$mountIds) {
		$this->profiler->enterSection('leaf_category->setMounts');
		parent::setMounts($mountIds);	
		$this->profiler->leaveSection('leaf_category->setMounts');
	}
	
	function setPositions(&$positionIds) {
		$this->profiler->enterSection('leaf_category->setPositions');
		parent::setPositions($positionIds);	
		$this->profiler->leaveSection('leaf_category->setPositions');
	}
	
	function setUid($uid) {
		$this->profiler->enterSection('leaf_category->setUid');
		parent::setUid($uid);	
		$this->profiler->leaveSection('leaf_category->setUid');
	}
	
	function setDepth($depth) {
		$this->profiler->enterSection('leaf_category->setDepth');
		parent::setDepth($depth);	
		$this->profiler->leaveSection('leaf_category->setDepth');
	}
	
	protected function setParentLeaf(tx_commerce_leaf &$parentLeaf) {
		$this->profiler->enterSection('leaf_category->setParentLeaf');
		parent::setParentLeaf($parentLeaf);	
		$this->profiler->leaveSection('leaf_category->setParentLeaf');
	}
	
	function setDataPositions(&$positions) {
		$this->profiler->enterSection('leaf_category->setDataPositions');
		parent::setDataPositions($positions);	
		$this->profiler->leaveSection('leaf_category->setDataPositions');
	}
	
	function sort($rootUid) {
		$this->profiler->enterSection('leaf_category->sort');
		parent::sort($rootUid);	
		$this->profiler->leaveSection('leaf_category->sort');
	}
	
	function &getSortedArray() {
		$this->profiler->enterSection('leaf_category->getSortedArray');
		$el = parent::getSortedArray();	
		$this->profiler->leaveSection('leaf_category->getSortedArray');
		return $el;
	}
	
	function printLeafByMounts($treeName) {
		$this->profiler->enterSection('leaf_category->printLeafByMounts');
		$el = parent::printLeafByMounts($treeName);	
		$this->profiler->leaveSection('leaf_category->printLeafByMounts');
		return $el;
	}
	
	function leafsHaveRecords(&$row) {
		$this->profiler->enterSection('leaf_category->leafsHaveRecords');
		$el = parent::leafsHaveRecords($row);	
		$this->profiler->leaveSection('leaf_category->leafsHaveRecords');
		return $el;
	}
	
	function isLast(&$row, $parent = null, $i = 0, $l = 0) {
		$this->profiler->enterSection('leaf_category->isLast');
		$el = parent::isLast($row, $parent, $i, $l);	
		$this->profiler->leaveSection('leaf_category->isLast');
		return $el;
	}
	
	public function hasChildren(&$row)  {
		$this->profiler->enterSection('leaf_category->hasChildren');
		$el = parent::hasChildren($row);	
		$this->profiler->leaveSection('leaf_category->hasChildren');
		return $el;
	}
	
	function hasSubitems(&$row) {
		$this->profiler->enterSection('leaf_category->hasSubitems');
		$el = parent::hasSubitems($row);	
		$this->profiler->leaveSection('leaf_category->hasSubitems');
		return $el;
	}
}
?>

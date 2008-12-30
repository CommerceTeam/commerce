<?php
/**
 * Implements the speed test for the leaf_categorydata class
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
class ux_tx_commerce_leaf_categorydata extends tx_commerce_leaf_categorydata {
	
	public $profiler;
	
	function setMounts(&$mountIds) {
		$this->profiler->enterSection('leaf_categorydata->setMounts');
		parent::setMounts($mountIds);	
		$this->profiler->leaveSection('leaf_categorydata->setMounts');	
	}
	
	function setUid($uid) {
		$this->profiler->enterSection('leaf_categorydata->setUid');
		parent::setUid($uid);	
		$this->profiler->leaveSection('leaf_categorydata->setUid');	
	}
	
	function &getChildrenByPid($pid) {
		$this->profiler->enterSection('leaf_categorydata->getChildrenByPid');
		$el = parent::getChildrenByPid($pid);	
		$this->profiler->leaveSection('leaf_categorydata->getChildrenByPid');	
		
		return $el;
	}
	
	function setDepth($depth) {
		$this->profiler->enterSection('leaf_categorydata->setDepth');
		parent::setDepth($depth);	
		$this->profiler->leaveSection('leaf_categorydata->setDepth');	
	}
	
	function init() {
		$this->profiler->enterSection('leaf_categorydata->init');
		parent::init();	
		$this->profiler->leaveSection('leaf_categorydata->init');	
	}
	
	function generatePermissionClause() {
		$this->profiler->enterSection('leaf_categorydata->generatePermissionClause');
		$el = parent::generatePermissionClause();	
		$this->profiler->leaveSection('leaf_categorydata->generatePermissionClause');	
		
		return $el;
	}
	
	function getWhere() {
		$this->profiler->enterSection('leaf_categorydata->getWhere');
		$el = parent::getWhere();	
		$this->profiler->leaveSection('leaf_categorydata->getWhere');	
		
		return $el;
	}
	
	function getPositionsByIndices($index, $indices) {
		$this->profiler->enterSection('leaf_categorydata->getPositionsByIndices');
		$el = parent::getPositionsByIndices($index, $indices);	
		$this->profiler->leaveSection('leaf_categorydata->getPositionsByIndices');	
		
		return $el;
	}	
	
	public function initRecords($index, &$indices) {	
		$this->profiler->enterSection('leaf_categorydata->initRecords');
		parent::initRecords($index, $indices);	
		$this->profiler->leaveSection('leaf_categorydata->initRecords');	
	}
	
	protected function &getRecordsByMountpoints($index, &$indices) {
		$this->profiler->enterSection('leaf_categorydata->getRecordsByMountpoints');
		$el = parent::getRecordsByMountpoints($index, $indices);	
		$this->profiler->leaveSection('leaf_categorydata->getRecordsByMountpoints');	
		
		return $el;
	}
	
	protected function &getRecordsByUid() {
		$this->profiler->enterSection('leaf_categorydata->getRecordsByUid');
		$el = parent::getRecordsByUid();	
		$this->profiler->leaveSection('leaf_categorydata->getRecordsByUid');	
		
		return $el;
	}
	
	public function getRecordsDbList($uid, $depth = 2) {
		$this->profiler->enterSection('leaf_categorydata->getRecordsDbList');
		$el = parent::getRecordsDbList($uid, $depth);	
		$this->profiler->leaveSection('leaf_categorydata->getRecordsDbList');	
		
		return $el;
	}
	
	protected function &loadLeafRecords($where = '') {
		$this->profiler->enterSection('leaf_categorydata->loadLeafRecords');
		$el = parent::loadLeafRecords($where);	
		$this->profiler->leaveSection('leaf_categorydata->loadLeafRecords');	
		
		return $el;
	}
	
	public function &loadRecords() {
		$this->profiler->enterSection('leaf_categorydata->loadRecords');
		$el = parent::loadRecords();	
		$this->profiler->leaveSection('leaf_categorydata->loadRecords');	
		
		return $el;
	}
	
	protected function &getRecursiveUids($uid, $depth, &$array = null) {
		$this->profiler->enterSection('leaf_categorydata->getRecursiveUids');
		$el = parent::getRecursiveUids($uid, $depth, $array);	
		$this->profiler->leaveSection('leaf_categorydata->getRecursiveUids');	
		
		return $el;
	}
	
	public function isExpanded(&$row) {
		$this->profiler->enterSection('leaf_categorydata->isExpanded');
		$el = parent::isExpanded($row);	
		$this->profiler->leaveSection('leaf_categorydata->isExpanded');	
		
		return $el;
	}
	
	protected function getRootRecord() {
		$this->profiler->enterSection('leaf_categorydata->getRootRecord');
		$el = parent::getRootRecord();	
		$this->profiler->leaveSection('leaf_categorydata->getRootRecord');	
		
		return $el;
	}
}
?>

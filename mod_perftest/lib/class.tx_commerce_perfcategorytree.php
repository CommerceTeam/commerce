<?php
/**
 * Implements a performance-checked category tree
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_belib.php');
require_once(t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_categorytree.php');

class tx_commerce_perfcategorytree extends tx_commerce_categorytree {
	
	public $profiler;
	
	public function __construct() {
		$this->profiler = new Benchmark_Profiler();
	}
	
	/**
	 * Overwrite init to measure execution time
	 * @return {void}
	 */
	public function init() {
		$this->profiler->enterSection('categorytree->init');
		parent::init();	
		$this->profiler->leaveSection('categorytree->init');
	}
	
	/**
	 * Overwrite getBrowseableTree to measure exec time
	 * @return {void}
	 * @param $uid Object[optional]
	 * @param $useMountpoints Object[optional]
	 */
	public function getBrowseableTree($uid = 0, $useMountpoints = true) {
		$this->profiler->enterSection('categorytree->getBrowseableTree');
		parent::getBrowseableTree($uid, $useMountpoints);	
		$this->profiler->leaveSection('categorytree->getBrowseableTree');
	}
	
	public function getTreeByMountpoints() {
		$this->profiler->enterSection('categorytree->getTreeByMountpoints');
		parent::getTreeByMountpoints();	
		$this->profiler->leaveSection('categorytree->getTreeByMountpoints');
	}
	
	public function setTreeName($tree = '') {
		$this->profiler->enterSection('setTreeName');
		parent::setTreeName($tree);	
		$this->profiler->leaveSection('setTreeName');
	}
	
	/**
	 * Adds a leaf to the tree and add
	 * @return {void}
	 * @param $leaf Object
	 */
	public function addLeaf(tx_commerce_leaf &$leaf) {
		$this->profiler->enterSection('categorytree->addLeaf');
		
		$leaf->profiler = &$this->profiler;
		
		parent::addLeaf($leaf);	
		$this->profiler->leaveSection('categorytree->addLeaf');
	}
	
	public function getBrowseableAjaxTree($PM) {
		$this->profiler->enterSection('categorytree->getBrowseableAjaxTree');
		parent::getBrowseableAjaxTree($PM);	
		$this->profiler->leaveSection('categorytree->getBrowseableAjaxTree');
	}
	
	public function getTree($uid = 0, $depth = 999) {
		$this->profiler->enterSection('categorytree->getTree');
		parent::getTree($uid, $depth);	
		$this->profiler->leaveSection('categorytree->getTree');
	}
	
	public function printTreeByMountpoints() {
		$this->profiler->enterSection('categorytree->printTreeByMountpoints');
		parent::printTreeByMountpoints();	
		$this->profiler->leaveSection('categorytree->printTreeByMountpoints');
	}
	
	public function printAjaxTree($PM) {
		$this->profiler->enterSection('categorytree->printAjaxTree');
		parent::printAjaxTree($PM);	
		$this->profiler->leaveSection('categorytree->printAjaxTree');
	}
	
	protected function initializePositionSaving() {
		$this->profiler->enterSection('categorytree->initializePositionSaving');
		parent::initializePositionSaving();	
		$this->profiler->leaveSection('categorytree->initializePositionSaving');
	}
	
	protected function savePosition(&$positions) {
		$this->profiler->enterSection('categorytree->savePosition');
		parent::savePosition($positions);
		$this->profiler->leaveSection('categorytree->savePosition');
	}
	
	public function &getRecordsPerLevelArray($rootUid) {
		$this->profiler->enterSection('categorytree->getRecordsPerLevelArray');
		parent::getRecordsPerLevelArray($rootUid);
		$this->profiler->leaveSection('categorytree->getRecordsPerLevelArray');
	}
	
	public function getRecordsAsArray($rootUid) {
		$this->profiler->enterSection('categorytree->getRecordsAsArray');
		parent::getRecordsAsArray($rootUid);
		$this->profiler->leaveSection('categorytree->getRecordsAsArray');
	}
}
?>

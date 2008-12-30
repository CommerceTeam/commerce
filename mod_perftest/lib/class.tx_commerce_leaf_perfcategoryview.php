<?php
/**
 * Implements the speed test for the leaf_categorydata class
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
class ux_tx_commerce_leaf_categoryview extends tx_commerce_leaf_categoryview {
	
	public $profiler;
	
	public function setLeafIndex($index) {
		$this->profiler->enterSection('leaf_categoryview->setLeafIndex');
		parent::setLeafIndex($index);
		$this->profiler->leaveSection('leaf_categoryview->setLeafIndex');	
	}
	
	public function setParentIndices($indices = array()) {
		$this->profiler->enterSection('leaf_categoryview->setParentIndices');
		parent::setParentIndices($indices);
		$this->profiler->leaveSection('leaf_categoryview->setParentIndices');	
	}
	
	function setBank($bank) {
		$this->profiler->enterSection('leaf_categoryview->setBank');
		parent::setBank($bank);
		$this->profiler->leaveSection('leaf_categoryview->setBank');	
	}
	
	function setTreeName($name) {
		$this->profiler->enterSection('leaf_categoryview->setTreeName');
		parent::setTreeName($name);
		$this->profiler->leaveSection('leaf_categoryview->setTreeName');
	}
	
	/*public function noClickmenu($flag = true) {
		$this->profiler->enterSection('leaf_categoryview->noClickmenu');
		parent::noClickmenu($flag);
		$this->profiler->leaveSection('leaf_categoryview->noClickmenu');
	}*/
	
	/*public function noRootOnclick($flag = true) {
		$this->profiler->enterSection('leaf_categoryview->noRootOnclick');
		parent::noRootOnclick($flag);
		$this->profiler->leaveSection('leaf_categoryview->noRootOnclick');
	}*/
	
	function getIcon(&$row) {
		$this->profiler->enterSection('leaf_categoryview->getIcon');
		$el = parent::getIcon($row);
		$this->profiler->leaveSection('leaf_categoryview->getIcon');	
		
		return $el;
	}
	
	function getRootIcon(&$row) {
		$this->profiler->enterSection('leaf_categoryview->getRootIcon');
		$el = parent::getRootIcon($row);
		$this->profiler->leaveSection('leaf_categoryview->getRootIcon');	
		
		return $el;
	}
	
	function wrapIcon($icon, &$row, $addParams = '') {
		$this->profiler->enterSection('leaf_categoryview->wrapIcon');
		$el = parent::wrapIcon($icon, &$row, $addParams);
		$this->profiler->leaveSection('leaf_categoryview->wrapIcon');	
		
		return $el;
	}
	
	function wrapTitle($title, &$row, $bank = 0)	{
		$this->profiler->enterSection('leaf_categoryview->wrapTitle');
		$el = parent::wrapTitle($title, &$row, $bank);
		$this->profiler->leaveSection('leaf_categoryview->wrapTitle');	
		
		return $el;
	}
	
	function getJumpToParam(&$row) {
		$this->profiler->enterSection('leaf_categoryview->getJumpToParam');
		$el = parent::getJumpToParam($row);
		$this->profiler->leaveSection('leaf_categoryview->getJumpToParam');	
		
		return $el;
	}
	
	function addTagAttributes($icon,$attr)	{
		$this->profiler->enterSection('leaf_categoryview->addTagAttributes');
		$el = parent::addTagAttributes($icon,$attr);
		$this->profiler->leaveSection('leaf_categoryview->addTagAttributes');	
		
		return $el;
	}
	
	function getTitleAttrib(&$row) {
		$this->profiler->enterSection('leaf_categoryview->getTitleAttrib');
		$el = parent::getTitleAttrib($row);
		$this->profiler->leaveSection('leaf_categoryview->getTitleAttrib');	
		
		return $el;
	}
	
	function PMicon(&$row, $isLast, $isExpanded,$isBank = false, $hasChildren = false)	{
		$this->profiler->enterSection('leaf_categoryview->PMicon');
		$el = parent::PMicon(&$row, $isLast, $isExpanded,$isBank, $hasChildren);
		$this->profiler->leaveSection('leaf_categoryview->PMicon');	
		
		return $el;
	}
	
	function PMiconATagWrap($icon, $cmd, $isExpand = true)	{
		$this->profiler->enterSection('leaf_categoryview->PMiconATagWrap');
		$el = parent::PMiconATagWrap($icon, $cmd, $isExpand);
		$this->profiler->leaveSection('leaf_categoryview->PMiconATagWrap');	
		
		return $el;
	}
	
}
?>

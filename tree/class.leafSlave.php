<?php
/**
 * Implements a slave leaf of the browsetree
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leaf.php');

class leafSlave extends leaf {
	
	var $parentLeaf;	//If the leaf has a parent leaf, then it is stored in this variable
	
	/**
	 * Sets the parent leaf of this leaf
	 * 
	 * @return {void}
	 * @param $parentLeaf {object}	tx_commerce_leaf that is the parent of this leaf
	 */
	protected function setParentLeaf(leaf &$parentLeaf) {
		if(is_null($parentLeaf)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setParentLeaf (leafSlave) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		$this->parentLeaf = $parentLeaf;
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
			if (TYPO3_DLOG) t3lib_div::devLog('init (leafSlave) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return;	
		}
		
		//Initialize the LeafData
		$this->data->init();
		$this->data->initRecords($index, $parentIndices, $this->parentLeaf->data);
		
		parent::init($index, $parentIndices);
	}
	
	
	/**
	 * Prints the single leaf item
	 * Since this is a slave, this can only EVER be called by AJAX
	 * 
	 * @return {string}		HTML Code
	 * @param $startUid {int}	 UID in which we start
	 * @param $bank	{int}		 Bank UID
	 * @param $treeName {string} Tree Name
	 * @param $pid {int}		 UID of the parent item
	 */
	function printChildleafsByLoop($startUid, $bank, $pid) { 
	
		//Check for valid parameters
		if(!is_numeric($startUid) || !is_numeric($bank)) {
			if (TYPO3_DLOG) t3lib_div::devLog('printChildleafsByLoop (leafSlave) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		
		//Set the bank
		$this->view->setBank($bank);
		$this->data->setBank($bank);
		
		//Set the TreeName
		$this->view->setTreeName($this->treeName);
		
		//init vars
		$out 			= '';
		
		//get the Parent Item and set it as the starting child to print
		$child 					= $this->data->getChildByUid($startUid);	
		$child['item_parent'] 	= $pid;
		
		//Abort if the starting Category is not found
		if(null == $child) {
			if (TYPO3_DLOG) t3lib_div::devLog('printChildleafsByLoop (leafSlave) cannot find the starting category by its uid.', COMMERCE_EXTkey, 3);	
			return '';
		}
			
		/********************
		 * Printing the Item
		 *******************/
		//Give class 'expanded' if it is
		$exp 		 = $this->data->isExpanded($child['uid']);
		$cssExpanded = ($exp) ? 'expanded' : '';
		
		//Add class 'last' if it is
		$isLast 	= $this->isLast($child, $pid);
		$cssLast 	= ($isLast) ? ' last' : '';
		
		$cssClass 	= $cssExpanded.' '.$cssLast;
		
		$out .= '<li class="'.$cssClass.'">'; //start the element
		
		$isBank 		= false; //a slave can never be a bank
		$hasChildren 	= $this->hasChildren($child);
		
		$out .= $this->view->PMicon($child, $isLast, $exp, $isBank, $hasChildren); //pm icon
		
		if ($this->selfClass === 'tx_commerce_leaf_product') {
			$this->view->substituteRealValues();
		}
		$out .= $this->view->getIcon($child); //icon
		$out .= $this->view->wrapTitle($child['title'], $child);	//title
		
		/******************
		 * Done printing
		 *****************/
		
		//Print the children from the child leafs if the current leaf is expanded
		if($exp) {
			$out .= '<ul>';
			for($i = 0; $i < $this->leafcount; $i ++) {
				$out .= $this->leafs[$i]->printChildleafsByParent($child['uid'], $bank, $this->treeName);
			}
			$out .= '</ul>';
		}
		
		//close the list item
		$out .= '</li>';
		
		return $out;
	}
	
	/**
	 * Prints all leafs by the parent item
	 * 
	 * @return {string}			 HTML Code
	 * @param $pid {int}	 	 UID of the parent item
	 * @param $bank	{int}		 Bank UID
	 * @param $treeName {string} Tree Name
	 */
	function printChildleafsByParent($pid, $bank) {
	
		//Check for valid parameters
		if(!is_numeric($pid) || !is_numeric($bank)) {
			if (TYPO3_DLOG) t3lib_div::devLog('printChildleafsByParent (leafSlave) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		
		$out = '';
		
		//get the children
		$children = $this->data->getChildrenByPid($pid);
		
		$l = count($children);
		
		//Process the child and children
		for($i = 0; $i < $l; $i ++) {
			
			$child = $children[$i];
			
			$out .= $this->printChildleafsByLoop($child['uid'], $bank, $pid);
		}
		
		//DLOG
		if (TYPO3_DLOG) t3lib_div::devLog('printChildleafsByParent (leafSlave) did '.($l).' loops!', COMMERCE_EXTkey, 1);	
		
		return $out;
	}
}
?>

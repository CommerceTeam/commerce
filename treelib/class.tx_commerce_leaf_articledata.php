<?php
/**
 * Created on 29.07.2008
 * 
 * Implements the Data for the Article Leaf
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leafSlaveData.php'); 

class tx_commerce_leaf_articledata extends leafSlaveData {
		
	protected $extendedFields 	= 'title, hidden';	//Fields that should be read from the products
	
	protected $table 			= 'tx_commerce_articles';
	protected $itemTable		= 'tx_commerce_articles';				//table to read the leafitems from
	protected $useMMTable		= false;		//Flag if mm table is to be used or the parent field
	protected $itemParentField  = 'uid_product';
	protected $item_parent		= 'uid_product';
	
	/**
	 * Initializes the ProductData Object
	 * @return {void}
	 */
	public function init() {
		$this->whereClause 	= 'deleted = 0';
		$this->order		= 'tx_commerce_articles.sorting ASC';
	}
	
	/**
	 * @todo	If we implement the positions (see above), we should also implement this and any function related to making this leaf not ultimate
	 * Returns true if this Article is currently expanded
	 * 
	 * @param {object} $row - Current Row
	 * @return {boolean}
	 */
	public function isExpanded(&$row) {
		//Article is the ultimate leaf, so to speak - it currently has no subleafs
		return false;	
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_articledata.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_articledata.php']);
}
?>

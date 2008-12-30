<?php
/**
 * Created on 29.07.2008
 * 
 * Implements the leafview for the Category
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leafView.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_belib.php');

class tx_commerce_leaf_categoryview extends leafView{
	protected $table 			= 'tx_commerce_categories';	//DB Table ##isnt this read automatically?###
	protected $domIdPrefix 	= 'txcommerceCategory';
	
	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param	{object} $row - Array with the ID Information
	 * @return	{string}
	 */
	function getJumpToParam($row) {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getJumpToParam (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		
		//get the UID of the Products SysFolder
		$prodPid = tx_commerce_belib::getProductFolderUid();
		
		$res = 'id='.$prodPid.'&control['.$this->table.'][uid]='.$row['uid'];
		return $res;
	}
	
	
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_categoryview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_categoryview.php']);
}
?>

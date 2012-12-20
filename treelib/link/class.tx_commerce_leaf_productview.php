<?php
/**
 * Created on 29.07.2008
 * 
 * Implements the leafview for Product
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leafView.php');

class tx_commerce_leaf_productview extends leafView {
	protected $table 		= 'tx_commerce_products';	//DB Table
	protected $domIdPrefix 	= 'txcommerceProduct';
	protected $openProd		= 0; // uid of the open product
	
	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	function wrapTitle($title, &$row, $bank = 0)	{
		if(!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) t3lib_div::devLog('wrapTitle (productview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		$res = '';
		
		//Max. size for Title of 30
		$title = ('' != trim($title)) ? t3lib_div::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');
		
		$aOnClick = 'return link_folder(\''.$this->getJumpToParam($row).'\');';
		
		$style = ($row['uid'] == $this->openProd) ? 'style="color: red; font-weight: bold"' : '';
		$res = '<a href="#" onclick="'.htmlspecialchars($aOnClick).'" '.$style.'>'.htmlspecialchars(strip_tags($title)).'</a>';

		return $res;
	}
	
	function setOpenProduct($uid) {
		$this->openProd = $uid;
	}
	
	/**
	 * returns the link from the tree used to jump to a destination
	 *
	 * @param	{object} $row - Array with the ID Information
	 * @return	{string}
	 */
	function getJumpToParam(&$row) {
		if(!is_array($row)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getJumpToParam (productview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return '';	
		}
		$res = 'commerce:tx_commerce_products:'.$row['uid'].'|tx_commerce_categories:'.$row['item_parent'];
		return $res;
	}
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_leaf_productview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_leaf_productview.php']);
}
?>

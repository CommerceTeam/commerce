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
		
		$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->domIdPrefix.$row['uid'].'_'.$bank.'\',\'alt_doc.php\');';
		
		$res = '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';

		return $res;
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
		
		$value = 'edit';
		
		if ($this->realValues) {
			$value = $this->table . '_' . $row['uid'];
		}
		
		$res = 'edit['.$this->table.']['.$row['uid'].']=' . $value;
		return $res;
	}
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_productview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_productview.php']);
}
?>

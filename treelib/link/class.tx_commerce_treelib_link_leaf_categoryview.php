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

class tx_commerce_treelib_link_leaf_categoryview extends leafView{
	protected $table 		= 'tx_commerce_categories';	//DB Table ##isnt this read automatically?###
	protected $domIdPrefix 	= 'txcommerceCategory';
	protected $openCat		= 0; // the linked category
	
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
		
		$res = 'commerce:tx_commerce_categories:'.$row['uid'];
		return $res;
	}
	
	function setOpenCategory($uid) {
		$this->openCat = $uid;
	}
	
	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	function wrapTitle($title, $row, $bank = 0)	{
		if(!is_array($row) || !is_numeric($bank)) {
			if (TYPO3_DLOG) t3lib_div::devLog('wrapTitle (leafview) gets passed invalid parameters.', COMMERCE_EXTkey, 3);		
			return '';
		}
		
		$res = '';
		
		//Max. size for Title of 255
		$title = ('' != $title) ? t3lib_div::fixed_lgd_cs($title, 255) : $this->getLL('leaf.noTitle');
		
		$aOnClick = 'return link_folder(\''.$this->getJumpToParam($row).'\');';
		$style = ($row['uid'] == $this->openCat && 0 != $this->openCat) ? 'style="color: red; font-weight: bold"' : '';
		$res = (($this->noRootOnclick && 0 == $row['uid']) || $this->noOnclick) ? $title : '<a href="#" onclick="'.htmlspecialchars($aOnClick).'" '.$style.'>'.$title.'</a>';

		return $res;
	}
	
	
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_treelib_link_leaf_categoryview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_treelib_link_leaf_categoryview.php']);
}
?>
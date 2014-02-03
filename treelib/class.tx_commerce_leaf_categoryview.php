<?php
/**
 * Implements the leafview for the Category
 */
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
			if (TYPO3_DLOG) t3lib_div::devLog('getJumpToParam (leafview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			return '';
		}

		//get the UID of the Products SysFolder
		$prodPid = tx_commerce_belib::getProductFolderUid();

		$res = 'id='.$prodPid.'&control['.$this->table.'][uid]='.$row['uid'];

		if ($this->realValues) {
            $res = $this->table . '_' . $row['uid'];
        }

		return $res;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_categoryview.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_categoryview.php']);
}

?>
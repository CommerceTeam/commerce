<?php
/**
 * Implements the leafdata for Product
 */
class tx_commerce_leaf_productdata extends leafSlaveData {

	protected $extendedFields 	= 'title, navtitle, hidden, deleted, starttime, endtime, t3ver_oid, t3ver_id, t3ver_wsid, t3ver_label, t3ver_state, t3ver_stage, t3ver_count, t3ver_tstamp';	//Fields that should be read from the products
	protected $table 			= 'tx_commerce_products';
	protected $itemTable		= 'tx_commerce_products';				//table to read the leafitems from
	protected $mmTable			= 'tx_commerce_products_categories_mm';	//table that is to be used to find parent items
	protected $useMMTable		= true;		//Flag if mm table is to be used or the parent field
	protected $item_parent 		= 'uid_foreign';

	/**
	 * Initializes the ProductData Object
	 *
	 * @return {void}
	 */
	public function init() {
		$this->whereClause 	= 'tx_commerce_products.deleted = 0 AND tx_commerce_products.pid != -1'; //do not read deleted and offline versions
		$this->order		= 'tx_commerce_products.sorting ASC';
	}

	/**
	 * Loads and returns the Array of Records
	 *
	 * @return {array}
	 * @param $uid {int}	UID of the Category that is the parent
	 * @param $depth {int}[optional] Recursive Depth (not used here)
	 */

	public function getRecordsDbList($uid, $depth = 2) {
		if(!is_numeric($uid) || !is_numeric($depth)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getRecordsDbList (productdata) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			return null;
		}
		//Check if User's Group may view the records
		if(!$GLOBALS['BE_USER']->check('tables_select',$this->table)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getRecordsDbList (productdata): Usergroup is not allowed to view records.', COMMERCE_EXTKEY, 2);
			return null;
		}

		if(!is_numeric($uid)) return null;

		$this->where['uid_foreign'] = $uid;
		$this->where['uid_local']	= 0;

		return $this->loadRecords();
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_productdata.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_productdata.php']);
}

?>
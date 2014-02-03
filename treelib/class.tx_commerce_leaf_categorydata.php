<?php
/**
 * Implements the leafdata for the Category
 */
class tx_commerce_leaf_categorydata extends leafMasterData {

	protected $pointer 	 = 0;			//Pointer to the internal Leafrow ###what is this for generally?###
	protected $permsMask = 1;			//Permission Mask for reading Categories

	//Fields that will be read
	protected $extendedFields 	= 'parent_category, title, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody, editlock, starttime, endtime, hidden'; ###make this as a var which field is used as item_parent###
	protected $table 			= 'tx_commerce_categories';
	protected $item_parent		= 'uid_foreign';

	//new try to generalize the query
	protected $itemTable		= 'tx_commerce_categories';		//table to read the leafitems from
	protected $mmTable			= 'tx_commerce_categories_parent_category_mm';			//table that is to be used to find parent items
	protected $useMMTable		= true;		//Flag if mm table is to be used or the parent field

	/**
	 * Sets the Permission Mask for reading Categories from the db
	 *
	 * @return void
	 * @param $mask int		mask for reading the permissions
	 */
	function setPermsMask($mask) {
		if(!is_numeric($mask)) {
			if (TYPO3_DLOG) t3lib_div::devLog('setPermsMask (categorydata) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			return;
		}

		$this->permsMask = $mask;
	}

	/**
	 * Initializes the categorydata
	 * Builds the Permission-Statement
	 *
	 * @return void
	 * @param int $uid - Category UID
	 */
	function init() {
		$this->whereClause = ' deleted = 0 AND '.tx_commerce_belib::getCategoryPermsClause($this->permsMask);
		$this->order	   = 'tx_commerce_categories.sorting ASC';
	}


	/**
	 * Loads and returns the Array of Records (for db_list)
	 *
	 * @return array
	 * @param $uid int	UID of the starting Category
	 * @param $depth int[optional] Recursive Depth
	 */
	public function getRecordsDbList($uid, $depth = 2) {
		if(!is_numeric($uid) || !is_numeric($depth)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getRecordsDbList (categorydata) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			return array();
		}

		//Check if User's Group may view the records
		if(!$GLOBALS['BE_USER']->check('tables_select',$this->table)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getRecordsDbList (categorydata): Usergroup is not allowed to view the records.', COMMERCE_EXTKEY, 2);
			return array();
		}

		$this->setUid($uid);
		$this->setDepth($depth);

		$records = $this->getRecordsByUid();

		return $records;
	}

	/**
	 * Returns the Category Root record
	 * @return array
	 */
	protected function getRootRecord() {
		$root = array();

		$root['uid'] 			= 0;
		$root['pid'] 			= 0;
		$root['title'] 			= $this->getLL('leaf.category.root');
		$root['hasChildren'] 	= 1; //root always has pm icon
		$root['lastNode'] 		= true;
		$root['item_parent'] 	= 0;

		return $root;
	}

	/**
	 * This function is used to normalize the records in the tx_commerce_categories
	 * Any category that has the parent_category = '' will be updated
	 *
	 * @return void
	 */
	protected function normalizeRecords() {
		/*$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_categories', 'parent_category = ""');

		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

		}*/
	}


}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_categorydata.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_categorydata.php']);
}

?>
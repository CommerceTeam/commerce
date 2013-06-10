<?php
/**
 * Module: Commerce > Category
 *
 * Listing database records from the tables configured in $TCA as they are related to the current category or root.
 * 
 * @author	Marketing Factory
 * @maintainer Erik Frister
 */
	
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_list.php');

$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
t3lib_BEfunc::lockRecords();

class tx_commerce_categories extends tx_commerce_db_list {
	var $extKey = COMMERCE_EXTkey;

	/**
	 * Initializing the module
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH;
		tx_commerce_create_folder::init_folders();
		$this->control = array (
			'category' => array ( 
				'dataClass' => 'tx_commerce_leaf_categorydata',
				'parent'   => 'parent_category'),
			'product' => array (  
				'dataClass' => 'tx_commerce_leaf_productdata',
				'parent'   => 'categories')
		);
		

		$this->scriptNewWizard = 'class.tx_commerce_cmd_wizard.php';
		parent::init();
	}

}

	// Make instance:
$SOBE = t3lib_div::makeInstance('tx_commerce_categories');
$SOBE->init();

$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();

//XClass Statement
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/index.php']);
}

?>
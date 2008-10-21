<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Franz Holzinger (kontakt@fholzinger.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'Category' for the 'commerce' extension.
 * 
 * $Id: index.php 8328 2008-02-20 18:02:10Z ischmittis $
 */


unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

/**
 * Graytree classes
 */
require_once(t3lib_extmgm::extPath('graytree').'modfunc_list_list/class.tx_graytree_db_list.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_db.php');

/**
 * Commerce classes
 */
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafcategorydata.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafproductdata.php');


$BE_USER->modAccess($MCONF,1);
t3lib_BEfunc::lockRecords();

/**
 * Script Class for the Web > List module; rendering the listing of records on a page
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_commerce_categories extends tx_graytree_db_list {
	var $extKey = COMMERCE_EXTkey;

	/**
	 * Initializing the module
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH;

		$this->control = array (
			'category' => array ( 
				'dataClass' => 'tx_commerce_leafCategoryData',
				'parent'   => 'parent_category'),
			'product' => array (  
				'dataClass' => 'tx_commerce_leafProductData',
				'parent'   => 'categories')
		);
		$this->scriptNewWizard = $BACK_PATH.PATH_txgraytree_rel.'mod_cmd/class.tx_graytree_cmd_wizard.php';
		parent::init();
	}

}

	// Make instance:
$SOBE = t3lib_div::makeInstance('tx_commerce_categories');
$SOBE->init();

	// Include files?
foreach	($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();

	// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/index.php']);
}

?>

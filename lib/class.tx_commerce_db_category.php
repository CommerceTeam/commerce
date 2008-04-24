<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2006 Ingo Schmitt <is@marketing-factory.de>
*  All   rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Database Class for tx_commerce_categories. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_category to get informations for articles.
 * Inherited from tx_commerce_db_alib
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_category
 * @see tx_comerce_category
 * @see tx_commerce_db_alib
 *
 * $Id$
 */
 /**
  * @todo
  *
  */

 /**
 * Basic abtract Class for Database Query for
 * Database retrival class fro product
 * inherited from tx_commerce_db_alib
 *
 *
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>

 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_category
 */

define('COMMERCE_CLICKMENU_DLOG', '0'); // +++ Switch for debugging error messages

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_alib.php');

class tx_commerce_db_category extends tx_commerce_db_alib {


	/**
	 * @var Database table concerning the data
	 * @access private
	 */
	var $database_table = 'tx_commerce_categories';
	var $mm_database_table ='tx_commerce_categories_parent_category_mm';
	var $database_attribute_rel_table = 'tx_commerce_categories_attributes_mm';
	var $CategoryOrderField ='tx_commerce_categories.sorting';
	var $ProductOrderField ='tx_commerce_products.sorting';
	/**
	 * Gets the "master" category from this category
	 * @param uid = Category UID
	 * @return integer Category UID
	 *
	 */

	function get_parent_category($uid) 	{
		if (TYPO3_DLOG && COMMERCE_CLICKMENU_DLOG) t3lib_div::devLog('tx_commerce_db_category::get_parent_category  $uid = '.$uid, COMMERCE_EXTkey);

		if (t3lib_div::testInt($uid) && ($uid > 0)){
			$this->uid=$uid;
			if (TYPO3_DLOG && COMMERCE_CLICKMENU_DLOG) t3lib_div::devLog('SELECT uid_foreign FROM '.$this->mm_database_table.' WHERE uid_local = '.$uid.' AND is_reference=0' , COMMERCE_EXTkey);
			if ($result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign', $this->mm_database_table,"uid_local = $uid and is_reference=0")) 	{
				if ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
					$GLOBALS['TYPO3_DB']->sql_free_result($result);
					if (TYPO3_DLOG && COMMERCE_CLICKMENU_DLOG) t3lib_div::devLog('tx_commerce_db_category::get_parent_category return '.$return_data['uid_foreign'], COMMERCE_EXTkey);
					return $return_data['uid_foreign'];
				}
			}
		}

		if (TYPO3_DLOG && COMMERCE_CLICKMENU_DLOG) t3lib_div::devLog('tx_commerce_db_category::get_parent_category return FALSE', COMMERCE_EXTkey);
 		return false;
 	}

 	/**
 	 * Gets the parent categories from this category
 	 * @param uid = Product UID
 	 * @return array of parent categories uid
 	 *
 	 */

 	function get_parent_categories($uid) 	{
 		if ((empty($uid)) || (!is_numeric($uid)) ){
 			return false;
 		}

 		$this->uid=$uid;
 		if (is_object($GLOBALS['TSFE']->sys_page)) {
 			$add_where = $GLOBALS['TSFE']->sys_page->enableFields($this->database_table, $GLOBALS['TSFE']->showHiddenRecords);
 		}else{
 			$add_where = '';
 		}
 		if ($result=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('uid_foreign',
 												$this->database_table,
 												$this->mm_database_table,
 												$this->database_table,
 												' and '.$this->mm_database_table.'.uid_local= '.$uid.' '.
 												$add_where
 												)
 			)
 		{
 			$data=array();
 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))			{
 				/**
 				 *  @todo access_check for datasets
 				 */
 				$data[]=(int)$return_data['uid_foreign'];

 			}
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 			return $data;

 		}


 		return false;

 	}

 	/**
 	 * Gets the child categories from this category
 	 * @param uid = Product UID
 	 * @return array of child categories uid
 	 *
 	 */
 	function get_child_categories($uid) 	{
 		if ((empty($uid)) || (!is_numeric($uid)) ){
 			return false;
 		}
 		/**
 		 * @TODO: Sorting should be by database 'tx_commerce_categories_parent_category_mm.sorting'
 		 * As TYPO3 issnt Curretly ablte to sort by MM tables (or we haven't found a way to use it)
 		 * We are using $this->database_table.sorting
 		 */
 		$this->uid=$uid;
 		$localOrderField = $this->CategoryOrderField;

 		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']);
		}
		if (method_exists($hookObj, 'categoryOrder')) {
			$localOrderField = $hookObj->categoryOrder($this->CategoryOrderField,$this);
		}
 		if (is_object($GLOBALS['TSFE']->sys_page)) {
 			$add_where = $GLOBALS['TSFE']->sys_page->enableFields($this->database_table, $GLOBALS['TSFE']->showHiddenRecords);
 		}else{
 			$add_where = '';
 		}
 		if ($result=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('uid_local',
 												$this->database_table,
 												$this->mm_database_table,
 												$this->database_table,
 												' and '.$this->mm_database_table.'.uid_foreign= '.$uid.' '.$add_where
 												,
 												'',
 												$localOrderField
 												)
 			)
 		{
 			$data=array();
 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) 			{

 				/**
 				 *  @todo access_check for datasets
 				 */
 				$data[]=(int)$return_data['uid_local'];

 			}
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);

 			return $data;

 		}


 		return false;

 	}

 	/**
 	 * Gets child products  categories from this category
 	 * @param uid = Product UID
 	 * @param lang_uid 	integer defauil -1	language uid
 	 * @return array child products uid
 	 * @since 20060712 Performance IMprovemnt top get rid the like queries
 	 */

 	function get_child_products($uid,$lang_uid=-1) 	{

 		if ((empty($uid)) || (!is_numeric($uid)) ){
 			return false;
 		}

 		if ($lang_uid==-1)		{
 			unset($lang_uid);
 		}
		$this->uid=$uid;
		if ((($lang_uid ==0) || empty($lang_uid)) && ($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']>0))		{
			$lang_uid=$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}

		$localOrderField = $this->ProductOrderField;

 		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']);

		}

		if (is_object($hookObj) && (method_exists($hookObj, 'productOrder'))) {

			$localOrderField = $hookObj->productOrder($localOrderField,$this);
		}


 		$where_clause = 'AND tx_commerce_products_categories_mm.uid_foreign = ' . $uid;
 		$where_clause.= ' AND tx_commerce_products.uid=tx_commerce_articles.uid_product ';
 		$where_clause.= ' AND tx_commerce_articles.uid=tx_commerce_article_prices.uid_article ';
 		if(is_object($GLOBALS['TSFE']->sys_page)){
		  $where_clause .= $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords);
		  $where_clause .= $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
		  $where_clause .= $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_article_prices', $GLOBALS['TSFE']->showHiddenRecords);
		}



		$queryArray = array(
			'SELECT' => 'tx_commerce_products.uid',
			'FROM' => 'tx_commerce_products ,tx_commerce_products_categories_mm,tx_commerce_articles, tx_commerce_article_prices',
			'WHERE' => 'tx_commerce_products.uid=tx_commerce_products_categories_mm.uid_local '.$where_clause,
			'GROUPBY' => '',
			'ORDERBY' => $localOrderField,
			'LIMIT' => ''
		);

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']);
			if (is_object($hookObj) && method_exists($hookObj, 'productQueryPreHook')) {
				$queryArray = $hookObj->productQueryPreHook($data, $this);
			}
		}

		if (false !== ($result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArray))) {


 			$data=array();
 			while (false !== ($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))) {
 				
 				
 				
 				if ($lang_uid == 0)
 				{
 					$data[]=(int)$return_data['uid'];
 				}else{
 						//	 Check if a lokalised product is availiabe for this product
					/**
					 * @TODO: Check if this is correct in Multi Tree Sites
					 */
 					 $lresult=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid',
			 			'tx_commerce_products',
						'l18n_parent = '.$return_data['uid'].' AND sys_language_uid=' . $lang_uid

						);

 					 if ($GLOBALS['TYPO3_DB']->sql_num_rows( $lresult) == 1)	 {

 					 	$data[] = (int)$return_data['uid'];
 					 }
 				}

 			}
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']) {
				$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']);
				if (is_object($hookObj) && method_exists($hookObj, 'productQueryPostHook')) {
					$data = $hookObj->productQueryPostHook($data, $this);
				}
			}
 			return $data;

 		}

 		return false;

 	}

 	

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_category.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_category.php"]);
}
?>

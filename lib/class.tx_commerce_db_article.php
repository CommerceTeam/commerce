<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2008 Ingo Schmitt <is@marketing-factory.de>
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
 * Database Class for tx_commerce_articles. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_article to get informations for articles.
 * Inherited from tx_commerce_db_alib
 *
 *
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_article
 * @see tx_comerce_article
 * @see tx_commerce_db_alib
 *
 * $Id$
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
 * @subpackage tx_commerce_db_article
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_alib.php');

class tx_commerce_db_article extends tx_commerce_db_alib {
	var $database_attribute_rel_table = 'tx_commerce_articles_article_attributes_mm';

 	/**
	 * Setting the Class Datebase Table
	 * @access private
	 */
	function tx_commerce_db_article() {
		$this->database_table= 'tx_commerce_articles';	
	 }

	/**
	 * returns the paren Product uid
	 * @param uid Article uid
	 * @return product uid
	 *
	 */
	function get_parent_product_uid($uid,$translationMode = false){
	 	$data=parent::get_data($uid,$translationMode);
	 	if ($data) {
	 		//Backwards Compatibility
	 		if ($data['uid_product']) {
	 			return $data['uid_product'];
			}
	 		if ($data['products_uid']) {
	 			return $data['products_uid'];
			}
	 	}
	 	else {
	 		return false;	
	 	}
	 }

	/**
	 * gets all prices form database related to this product
	 * @param uid= Article uid
	 * @param count = Number of Articles for price_scale_amount, default 1
	 * @return array of Price UID
	 */
	function get_prices($uid,$count=1,$orderField = 'price_net') {
		$uid = intval($uid);
		$count = intval($count);
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['priceOrder']) {
			$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['priceOrder']);
		}
		if (method_exists($hookObj, 'priceOrder')) {
			$orderField = $hookObj->priceOrder($orderField);
		}

		// hook to define any additional restrictions in where clause (Melanie Meyer, 2008-09-17)
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['additionalPriceWhere']) {
			$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['additionalPriceWhere']);
		}
		if (method_exists($hookObj, 'additionalPriceWhere')) {
			$additionalWhere = $hookObj->additionalPriceWhere($this,$uid);
		}
		if ($uid>0) {
			$price_uid_list=array();
			if (is_object($GLOBALS['TSFE']->sys_page)) {
				$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_article_prices',$GLOBALS['TSFE']->showHiddenRecords);
			}
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,fe_group',
				'tx_commerce_article_prices',
				"uid_article = $uid and price_scale_amount_start <= $count and price_scale_amount_end >= $count" .  $proofSQL . $additionalWhere,
				'',
				$orderField
			);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0) {
				while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					// Some users of the prices depend on fe_group being 0 when no group is selected. See bug #8894
					if ($return_data['fe_group'] == '') {
						$return_data['fe_group'] = '0';
					}
					$price_uid_list[$return_data['fe_group']][]=$return_data['uid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);
				return $price_uid_list;
			}else{
				 $this->error("exec_SELECTquery('uid','tx_commerce_article_prices',\"uid_article = $uid\"); returns no Result");
				return false;
			}
		}else {
			return false;
		}
	}

	/**
	 * Returns an array of all scale price amounts
	 * @param uid= Article uid
	 * @return array of Price UID
	 */
	
	function getPriceScales($uid,$count=1) {
		$uid = intval($uid);
		$count = intval($count);
		if ($uid>0) {
			$price_uid_list=array();
			if (is_object($GLOBALS['TSFE']->sys_page)) {
				$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_article_prices',$GLOBALS['TSFE']->showHiddenRecords);
			}
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,price_scale_amount_start, price_scale_amount_end',
				'tx_commerce_article_prices',
				"uid_article = $uid AND price_scale_amount_start >= $count " .  $proofSQL
			);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0) {
				while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					$price_uid_list[$return_data['price_scale_amount_start']][$return_data['price_scale_amount_end']]=$return_data['uid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);
				return $price_uid_list;
			} else {
				$this->error("exec_SELECTquery('uid','tx_commerce_article_prices',\"uid_article = $uid\"); returns no Result");
				return false;
			}
		}else {
			return false;
		}
	}

	/**
	 * Returns an array of all prices
	 * @param uid= Article uid
	 * @return array of Price UID
	 */
	function getPrices($uid) {
		$uid = intval($uid);
		if ($uid>0) {
			$price_uid_list=array();
			if (is_object($GLOBALS['TSFE']->sys_page)) {
					$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_article_prices',$GLOBALS['TSFE']->showHiddenRecords);
			}
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,price_scale_amount_start, price_scale_amount_end',
				'tx_commerce_article_prices',
				'uid_article = ' . $uid .  $proofSQL
			);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0) {
			 	while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					$price_uid_list[]=$return_data['uid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);
				return $price_uid_list;
			} else {
				$this->error("exec_SELECTquery('uid','tx_commerce_article_prices',\"uid_article = $uid\"); returns no Result");
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * gets all attributes from this product
	 * @param uid= Product uid
	 * @see tx_commerce_db_alib.php
	 * @return array of attribute UID 
	 */
	function get_attributes($uid) {
		return parent::get_attributes($uid,'');
	}

	/**
	 * Returns the attribute Value from the given Article attribute pair
	 * @param uid Article UID
	 * @param attribute_uid Attribute UID
	 * @param valueListAsUid if true, returns not the value from the valuelist, instaed the uid
	 * @return value
	 */
	function getAttributeValue($uid, $attribute_uid,$valueListAsUid=false) {
		$uid = intval($uid);
		$attribute_uid = intval($attribute_uid);
		if ($uid > 0) {
				// First select attribute, to detecxt if is valuelist
			if(is_object($GLOBALS['TSFE']->sys_page)){
				$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes',$GLOBALS['TSFE']->showHiddenRecords);
			}
			$result= $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT uid,has_valuelist','tx_commerce_attributes',"uid = $attribute_uid " .  $proofSQL);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)==1){
				$return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
				if ($return_data['has_valuelist']==1) {
					// Attribute has a valuelist, so do separate query
					$a_result= $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'DISTINCT distinct tx_commerce_attribute_values.value,tx_commerce_attribute_values.uid',
						'tx_commerce_articles_article_attributes_mm, tx_commerce_attribute_values',
						'tx_commerce_articles_article_attributes_mm.uid_valuelist = tx_commerce_attribute_values.uid AND' .
							'uid_local = ' . $uid . ' AND' .
							'uid_foreign = ' .$attribute_uid
					);
					if($GLOBALS['TYPO3_DB']->sql_num_rows($a_result)==1) {
						$value_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($a_result);
						if ($valueListAsUid==true) {
							return 	$value_data['uid'];
						} else {
							return 	$value_data['value'];
						}
					}
				} else {
						// attribute has no valuelist, so do normal query
					$a_result= $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'DISTINCT value_char,default_value',
						'tx_commerce_articles_article_attributes_mm',
						'uid_local = ' . $uid . ' AND uid_foreign = ' . $attribute_uid
					);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($a_result)==1) {
						$value_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($a_result);
						if ($value_data['value_char']) {
							return $value_data['value_char'];
						} else {
							return 	$value_data['default_value'];
						}
					} else {
						$this->error('More than one Value for thsi attribute');
					}
				}
			} else {
				$this->error('Could not get Attribute for call');
			}
		} else {
			$this->error('no Uid');
		}
	}

	/**
	 * returns the supplier name to a given UID, selected from tx_commerce_supplier
	 * @author	ingo Schmitt <is@marketing-factory.de>
	 * @param	integer	supplierUid
	 * @return 	string 	Supplier name
	 */
	function getSupplierName($supplieruid){
		if ($supplieruid > 0) {
			$result= $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'title',
				'tx_commerce_supplier',
				'uid = '.intval($supplieruid)
			);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)==1) {
				$return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
				return $return_data['title'];
			}
		}
		return false;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_article.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_article.php']);
}
?>

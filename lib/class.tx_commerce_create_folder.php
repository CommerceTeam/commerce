<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Ingo Schmitt <is@marketing-factory.de>
*  All rights reserved
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
 * Handling of sysfolders inseide tx_commerce. Basically creates
 * the needed sysfolders and the needed system_articles. 
 * 
 * The method of this Class should be called by 
 * tx_commerce_create_folder::methodname
 * 
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @author	Thomas Hempel <thomas@work.de>
 * @author 	Volker Graubaum <vg_typo3@e-netconsulting.de>
 *
 * @package TYPO3
 * @subpackage tx_commerce  
 * 
 * $Id$
 */
 require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_folder_db.php');
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_belib.php');
 
 /**
  * Creation of tx_commerce_basic folders 
  * call: tx_commerce_create_folder::init_folders()
  */
  
  /**
   * This class creates the systemfolders for TX_commerce
   * @author Ingo Schmitt
   * 
   */
 class tx_commerce_create_folder {
	/**
	 * Initializes the folders for tx_commerce
	 * @access public
	 * 
	 */
	function init_folders()	{
		
		
		/**
		 * Folder Creation
		 * @TODO Get list from Order folders from TS
		 */
		list($modPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Commerce', 'commerce');
		list($prodPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Products', 'commerce',$modPid);
		list($attrPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Attributes', 'commerce',$modPid);
		list($orderPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Orders', 'commerce',$modPid);
		list($xPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Incoming', 'commerce',$orderPid);
		list($xPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Working', 'commerce',$orderPid);
		list($xPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Waiting', 'commerce',$orderPid);
		list($xPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Delivered', 'commerce',$orderPid);

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatefolder'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatefolder'] as $classRef) {
						$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
		}

// Create System Product for payment and other things.
		$now = time();
		$addArray = array('tstamp' => $now, 'crdate' => $now, 'pid'=>$prodPid);
				
		// handle payment types
		// create the category if it not exists
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tx_commerce_categories',
			'uname=\'SYSTEM\' AND parent_category=\'\' AND deleted=0'
		);
		$catUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$catUid = $catUid['uid'];
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['precreatesyscategory'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['precreatesyscategory'] as $classRef) {
						$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
		}
		if (!$res || intval($catUid) == 0) {
			$catArray = $addArray;
			$catArray['title'] = 'SYSTEM';
			$catArray['uname'] = 'SYSTEM';
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_categories', $catArray);
			$catUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
		}
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatesyscategory'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatesyscategory'] as $classRef) {
						$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS'] as $type => $data) {
				tx_commerce_create_folder::makeSystemCatsProductsArtcilesAndPrices($catUid, strtoupper($type), $addArray);
			}
		}
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatediliveryarticles'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatediliveryarticles'] as $classRef) {
						$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
		}
	}
	
	
	/**
	 * Generates the System Articles
	 * @param 	$catUid 
	 * @param	$type
	 * @param	$addArray
	 * @author Volker Graubaum <vg_typo3@e-netconsulting.de>
	 */
	function makeSystemCatsProductsArtcilesAndPrices($catUid, $type, $addArray) {
		
		$pUid = tx_commerce_create_folder::makeProduct($catUid, $type, $addArray);
		// create some articles, depending on the PAYMENT types
		while (list($key, $value) = each($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['SYSPRODUCTS'][$type]['types'])) {
			tx_commerce_create_folder::makeArticle($pUid, $key, $value, $addArray);
		}
	}
	
	/**
	 * Creates a product with a special uname inside of a specific category.
	 * If the product already exists, the method returns the UID of it.
	 * @author Volker Graubaum <vg_typo3@e-netconsulting.de>
	 * @param 	$catUid	
	 * @param	$uname
	 * @param 	$addArray
	 */
	function makeProduct($catUid, $uname, $addArray){
		// first of all, check if there is a product for this value already in the database
		// if the product already exists, exit
		$pCheck = tx_commerce_create_folder::checkProd($catUid, $uname);
		if (isset($pCheck) && !($pCheck === false)) {
			// the return value of the method above is the uid of the product that already exists
			// in the category
			return $pCheck;
		}		
		
		// noproduct was found, so we create one
		// make the addArray
		$paArray = $addArray;
		$paArray['uname'] = $uname;
		$paArray['title'] = $uname;
		$paArray['categories'] = $catUid;
		
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_products', $paArray);
		$pUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
		
		// create relation between product and category
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_commerce_products_categories_mm',
			array(
				'uid_local' => $pUid,
				'uid_foreign' => $catUid,
			)
		);
		
		return $pUid;
	}

	/**
	 * Checks if a product is inside a category. The product is identified
	 * by the uname field.
	 *
	 * @param	Integer	$cUid: The uid of the category we search in
	 * @param	String	$classname: The unique name by which the product should be identified
	 * @return	false or UID of the found product
	 * @author Volker Graubaum <vg_typo3@e-netconsulting.de>
	 */
	function checkProd($cUid, $uname) {
		// select all product from that category
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_local',
			'tx_commerce_products_categories_mm',
			'uid_foreign=' .$cUid
		);
		$pList = array();
		while ($pUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$pList[] = $pUid['uid_local'];
		}
		// if no products where found for this category, we can return false
		if (count($pList) <= 0) return false;
		
		// else search the uid of the product with the classname within the product list
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tx_commerce_products',
			'uname=\'' .$uname .'\' AND uid IN (' .implode(',', $pList) .') AND deleted=0 AND hidden=0',
			'', '', 1
		);
		$pUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$pUid = $pUid['uid'];
		
		return $pUid;
	}
	
	/**
	 * Creates an article for the product. Used for sysarticles (e.g. payment articles)
	 * @param 	integer	$pUid: Product Uid under wich the articles are created
	 * @param	integer	$key: keyname for the sysarticle, used for classname and title first, title can be changed
	 * @param	array	$value: values for the article, only type is used
	 * @param	array	$addArray: additional params for the inserts (like timestamp)
	 * @author Volker Graubaum <vg_typo3@e-netconsulting.de>
	 */
	function makeArticle($pUid, $key, $value, $addArray) {
		// try to select an article that has a relation for this product
		// and the correct classname
		$this->belib = t3lib_div::makeInstance('tx_commerce_belib');
		$articles = $this->belib->getArticlesOfProduct($pUid, 'classname=\'' .$key .'\'');

		if (is_array($articles) AND count($articles) > 0) {
			return $articles[0]['uid'];
			
		}
		$aArray = $addArray;
		$aArray['classname'] = $key;
		$aArray['title'] = $key;
		$aArray['uid_product'] = $pUid;
		$aArray['article_type_uid'] = $value['type'];
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles', $aArray);		
		$aUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
		
		$pArray = $addArray;	
		$pArray['uid_article'] = $aUid;
		// create a price
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_article_prices', $pArray);
	}

	
 }

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']["ext/commerce/lib/class.tx_commerce_create_folder.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']["ext/commerce/lib/class.tx_commerce_create_folder.php"]);
}

 ?>
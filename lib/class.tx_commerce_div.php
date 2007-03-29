<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Ingo Schmitt <is@marketing-factory.de>
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
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * @author	
 * @package TYPO3
 * @subpackage tx_commerce
 */



/**
 * Misc COMMERCE functions
 *
 * 
 * @author  Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id: class.tx_commerce_div.php 576 2007-03-22 22:38:22Z ingo $
 */
 
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_basket.php');

class tx_commerce_div {





	/***************************************
	 *
	 *	 files folders paths
	 *
	 ***************************************/



	/**
	 * convert a path to a relative path if possible
	 * @author	Ren� Fritz <r.fritz@colorcube.de>
	 * @param	string		Path to convert
	 * @param	string		Path which will be used as base path. Otherwise PATH_site is used.
	 * @return	string		Relative path
	 * @deprecated 
	 */
	 /*
	function getRelPath ($path, $mountpath=NULL) {

		$mountpath = is_null($mountpath) ? PATH_site : $mountpath;

			// remove the site path from the beginning to make the path relative
			// all other's stay absolute
		return preg_replace('#^'.preg_quote($mountpath).'#','',$path);
	}
	*/


	/**
	 * Convert a path to an absolute path
	 * @author	Ren� Fritz <r.fritz@colorcube.de>
	 * @param	string		Path to convert
	 * @param	string		Path which will be used as base path. Otherwise PATH_site is used.
	 * @return	string		Absolute path
	 * @deprecated 
	 
	function getAbsPath ($path) {
		if(t3lib_div::isAbsPath($path)) {
			return $path;
		}
		$mountpath = is_null($mountpath) ? PATH_site : $mountpath;
		return $mountpath.$path;
	}
	*/
	/**
	 * Formates a price for the designated output
	 * @author	Ingo Schmitt <is@marketing-factory.de>
	 * @param 	float	price
	 * @return	string	formated Price
	 * @deprecated 
	 * @todo configurable
	 */
	
	function formatPrice($price)
	{
		return sprintf("%01.2f", $price);
		
	}
	
	/**
	 * This method initilize the basket for the fe_user from
	 * Session. If the basket is already initialized nothing happend 
	 * at this point.
	 * 
	 * @return void
	 */
	function initializeFeUserBasket() {
		
		if(is_object($GLOBALS['TSFE']->fe_user->tx_commerce_basket)) {
			return;
		}
		
		$GLOBALS['TSFE']->fe_user->tx_commerce_basket = t3lib_div::makeInstance('tx_commerce_basket');	
		$GLOBALS['TSFE']->fe_user->tx_commerce_basket->set_session_id($GLOBALS['TSFE']->fe_user->id);
		$GLOBALS['TSFE']->fe_user->tx_commerce_basket->load_data();
		return;
	}
	/***
	 * Remove Products from list wich have no articles wich are available
	 * from Stockn
	 * 
	 * @param	$productUids = array()	Array	List of productUIDs to work onn
	 * @param	$dontRemoveArticles = 1	integer	switch to show or not show articles
	 * @return	Array	Cleaned up Productarrayt
	 */	
	function removeNoStockProducts($productUids = array(),$dontRemoveProducts = 1) {
		if($dontRemoveProducts == 1) {
			return $productUids;
		}

		foreach ( $productUids as $arrayKey => $productUid ) {
			$productObj = new tx_commerce_product($productUid);
			$productObj->load_data();
			
			if(!($productObj->hasStock())) {
				unset($productUids[$arrayKey]);
			}
			$productObj = NULL;
		}

		return $productUids;
	}
	
	/***
	 * Remove article from product for frontendviewing, if articles
	 * with no stock should not shown
	 * 
	 * @param	$productObj	Object	ProductObject to work on
	 * @param	$dontRemoveArticles = 1	integer	switch to show or not show articles
	 * @return	Object	Cleaned up Productobjectt
	 */
		function removeNoStockArticles( $productObj, $dontRemoveArticles = 1 ) {
		if($dontRemoveArticles == 1) {
			return $productObj;
		}
		$articleUids = $productObj->getArticleUids();
		$articles = $productObj->getArticleObjects();
		foreach ( $articleUids as $arrayKey => $articleUid ) {			
			if($articles[$articleUid]->getStock() <= 0 ) {
				unset($productObj->articles_uids[$arrayKey]);
				unset($productObj->articles[$articleUid]);
			}
		}

		return $articleUids;
	}


	



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_div.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_div.php"]);
}
?>
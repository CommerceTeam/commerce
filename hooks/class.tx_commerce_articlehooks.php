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
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * @author      Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_article
 * 
 * Hook for article_class
 * This class is ment as programming-tutorial for programming hooks for delivery_costs
 * 
 * $Id: class.tx_commerce_articlehooks.php 308 2006-07-26 22:23:51Z ingo $
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_article.php');

class tx_commerce_articlehooks{
	/**
	 * Basic Method to calculate the delivereycost (net)
	 * Ment as Programming tutorial. Mostly you have to change or add some functionality
	 * @param price_object
	 * @param article_object (reference)
	 */
	function calculateDeliveryCostNet(&$net_price,&$article_obj)	{
		$article=$this->getdeliveryarticle($article_obj);
		if ($article) {
			$net_price=$article->get_price_net();
		} else {
			$net_price=0;
		}
	}
	
	/**
	 * Basic Method to calculate the delivereycost (gross)
	 * Ment as Programming tutorial. Mostly you have to change or add some functionality
	 * @param price_object
	 * @param article_object (reference)
	 */
	function calculateDeliveryCostGross(&$gross_price,&$article_obj)	{
		$article=$this->getdeliveryarticle($article_obj);
		if ($article) {
			$gross_price=$article->get_price_gross();
		} else {
			$gross_price=0;
		}
	}
	
	/**
	 * Loades the deliveryArticle
	 * @return article object
	 * 
	 */
	 function getdeliveryarticle(&$article_obj)	{
	 	$delivery_conf=($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['DELIVERY']['types']);
		
		$classname=array_shift(array_keys($delivery_conf));
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid',
 			'tx_commerce_articles',
			"classname = '".$classname."'"
		);
		if($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
			return false;
		}
		$return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		
 		$GLOBALS['TYPO3_DB']->sql_free_result($result);
 		$article_uid=$return_data['uid'];
 		
 		/**
 		 * Instantiate article class
 		 */
 		$article = t3lib_div::makeInstance('tx_commerce_article');
		$article->init($article_uid,$article_obj->get_lang());
		/**
		 * Do not call load_data at this point, since load_data recalls this hook, so we have a
		 * non endingrecursion
		 */
		if(is_object($article)){ 	   
			$article->load_prices();
		}
		return $article;
	}
}
  
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_articlehooks.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_articlehooks.php"]);
}
 
 
 ?>

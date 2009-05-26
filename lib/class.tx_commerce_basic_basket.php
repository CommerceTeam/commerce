<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2009 Ingo Schmitt <is@marketing-factory.de>
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
 * Abstract libary for Basket Handling. This class should not be used directly,
 * instead use tx_commerce_basket.
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private 
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_basic_basket 
 * @see tx_commerce_basket
 * @see tx_commerce_basket_item
 * Basic class for basket_handeling
 * 
 * $Id$
 **/
 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_basket_item.php');
 
 class tx_commerce_basic_basket{
 	
 	/**
 	 * Internal associative array for storing basket_items in the basket
 	 * 
 	 * @access public
 	 */
 	var $basket_items=array();
 	
 	/**
 	 * net Basket Summ
 	 * @acces private
 	 */
 	var $basket_sum_net=0;
 	
 	/**
 	 * gross Basket Summ
 	 * @acces private
 	 */
 	var $basket_sum_gross=0;
	
	/**
	 * calculated price from net price
	 * @access private
	 */		    
	var $pricefromnet = 0;
					    
 	
 	/**
 	 * Number of items in the basket
 	 * @access private
 	 */
 	 var $items=0;
	
	/**
	 * @var integer crdate
	 * Create Date of this basket
	 *  
	 */

	var $crdate =0;
 	
	/**
	 * Current State of the Bakset
	 *
	 * @var boolean
	 */
	var $readOnly = false;
 	
 	/**
 	 * Dummy instantiate Method
 	 */
 	function tx_commerce_basket()
 	{
 				
 	}
 	
 	/**
 	 * Loads the Basket_data from the session/database
 	 * 
 	 * 
 	 */
 	
 	function load_data(){
 		// check if paymentarticle is available and set default if not
		if(count($this->get_articles_by_article_type_uid_asUidlist(PAYMENTArticleType)) < 1){
			$this->add_article($this->conf['defaultPaymentArticleId']);
		}
 	}
 	
 	/**
 	 * Addes an article to the basket
	 * @change Volker Graubaum, proofs if item exists
 	 * @param article_uid Article UID
 	 * @param quantity quantity for this basket
 	 * @return true if success / false if no success
 	 * @acces public
 	 * @todo implement methid is_in_basket
 	 * 
 	 * @todo: Zusaetzliches feld fuer price_uid, pflichfeld
 	 * @since Get the price from the article object
 	 */
 	
 	function add_article($article_uid, $quantity=1,$priceid=''){
 		if ($article_uid && $this->isChangeable()) {
			if(is_object($this->basket_items[$article_uid]) || ($quantity == 0)){
				$this->change_quantity($article_uid, $quantity);
			}else{
				$article = t3lib_div::makeInstance('tx_commerce_article');
				$article->init($article_uid,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'])	;
				$article->load_data('basket');
				/*
				// If the record is not translated for this language, 
				// initialise the laguage fallback
				// if no language Fallback, use default language (0)
				if ($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_fallBackOrder']) {
					$fallbackLanguages = split(',',$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_fallBackOrder']) ;
					while (($article->isTranslated() == false) && (count($fallbackLanguages>0))){
						$article->init($artilce_uid,array_pop($fallbackLanguages));
						$article->load_data();
					}
				}
				if ($article->isTranslated() == false){
					$article->init($article_uid,0)	;
					$article->load_data();
				}*/
				$article->load_Prices();
				$priceids=$article->getPossiblePriceUids();
				if (is_array($priceids)) {
				/**
				 * Check if the given price id is related to the article
				 */
					if (!in_array($priceid,$priceids)){
						$priceid='';
					}
				}
				if ($priceid == '')	{
					// no priceid is given,. get the price from article_object
					
					$priceid=$article->getActualPriceforScaleUid($quantity);
					if (!$priceid) {
						$priceid=$article->get_article_price_uid();
					}
				}
				
				$this->basket_items[$article_uid] = t3lib_div::makeInstance('tx_commerce_basket_item');
				if ($this->basket_items[$article_uid]->init($article_uid,$quantity,$priceid,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'])){
	 				$this->basket_items[$article_uid]->setTaxCalculationMethod($this->pricefromnet);
	 				$this->recalculate_sums();
	 				$this->items++;
	 				 
	 			}	
			}
			return true;
 		}
 		return false;
			
 		
 	}
 	
 	/**
 	 * Changes the price_value for one ARticle
 	 * @param $article_uid
 	 * @param $new_price_gross
 	 * @param $new_price_net
 	 */
 	function changePrices($article_uid,$new_price_gross,$new_price_net){
 		if ($this->isChangeable()){
	 		if((is_object($this->basket_items[$article_uid])) && (is_a($this->basket_items[$article_uid],'tx_commerce_basket_item'))){
	 			$this->basket_items[$article_uid]->setPriceNet($new_price_net);
	 			$this->basket_items[$article_uid]->setPriceGross($new_price_gross);
	 			$this->basket_items[$article_uid]->recalculate_item_sums(true);	
	 		}	
 		}
 			
 	}
 	
 	/**
 	 * returns the gross price of the article in the basket
 	 *
 	 * @param integer $articleUid	article_uid
 	 * @return iteger	money value
 	 */
 	
 	function getPriceGrosss($articleUid){
 	 
 		if(is_object($this->basket_items[$articleUid])) {
 			return $this->basket_items[$articleUid]->get_item_sum_gross();
 		}
 		return false;
 	}
 	
 /**
 	 * returns the gross price of the article in the basket
 	 *
 	 * @param integer $articleUid	article_uid
 	 * @return iteger	money value
 	 */
 	
 	function getPriceNet($articleUid){
 	 
 		if(is_object($this->basket_items[$articleUid])) {
 			return $this->basket_items[$articleUid]->get_item_sum_net();
 		}
 		return false;
 	}
 		
 	
 	
 	
 	/**
 	 * Changes the Title for one Article
 	 * @param $article_uid
 	 * @param $newtitle
 	 * 
 	 */
 	function changeTitle($article_uid,$newtitle){
 		if ($this->isChangeable()){
	 		if(is_object($this->basket_items[$article_uid])){
	 			$this->basket_items[$article_uid]->setTitle($newtitle);
	 		}
 		}
 		
 	}
 	/**
 	 * Changes the quantity for this artcile in the basket
 	 * @param article_uid Article UID
 	 * @param quantity quantity for this basket
 	 * @return true if success / false if no success
 	 * @acces public
 	 */
 	
 	function change_quantity($article_uid, $quantity=1)
     {	if ($this->isChangeable()){
	         if ($quantity==0)
	         {
	             if(isset($this->basket_items[$article_uid])) {
	                 $this->delete_article($article_uid);
	             }
	             $items = $this->get_articles_by_article_type_uid_asUidlist(NORMALArticleType);
	             if(count($items) == 0) {
	                 $this->delete_all_articles();
	             }
	             return true;//$this->delete_article($article_uid);
	         }
	         $this->recalculate_sums();
	         return $this->basket_items[$article_uid]->change_quantity($quantity);
     	}else{
     		return false;
     	}
     }
     
     
     
     /**
      * returns the quantity of the given article_uid
      * @author	Ingo Schmitt <is@marketing-factory.de>
      *
      * @param integer $article_uid	uid of an article
      * @return	integer	quantity
      */
     function getQuantity($articleUid) {
     	if (is_object($this->basket_items[$articleUid])){
     		return $this->basket_items[$articleUid]->get_quantity();
     	}
     	return 0;
     	
     }
 	/**
 	 * deletes article form basket
 	 * @param article_uid article UID
 	 * @return true
 	 */
 	
 	function delete_article($article_uid)
 	{
 		if ($this->isChangeable()){
	 		if(!isset($this->basket_items[$article_uid])) {
	 			return false;
	 		}
	 		unset($this->basket_items[$article_uid]);
			
	 		$this->items--;
	 		$this->recalculate_sums();
	 		return true;
 		}
 		return false;
 	}
	
	/**
 	 * deletes all articles form basket
 	 * @return true
 	 */
 	
 	function delete_all_articles()
 	{
 		if ($this->isChangeable()){
	 		unset($this->basket_items);
			$this->basket_items = array();
	 		$this->items = '0';
	 		$this->recalculate_sums();
	 		return true;
 		}
 		return false;
 	}
	
	/**
	 * gets the number of items in the basket
	 * @return integer number of items
	 * 
	 */
	function getItemsCount() {
		return $this->items;	
	}
	

 	
 	/**
 	 * Recalculates the sums
 	 */
 	
 	function recalculate_sums()
 	{
 		$this->get_net_sum();
 		$this->get_gross_sum(false);
 	}
 	
 	/**
 	 * gets the gross basket sum
 	 * @param again if should be recalculated
 	 * @return basket_sum
 	 */
 	function get_gross_sum($again=true)
 	{
 	    $lokal_sum=0;
 	    if($this->pricefromnet == 1) {
 			$netSumArray = array();
 			foreach ($this->basket_items as $one_item) {
	 			$netSumArray[(string)$one_item->get_tax()]+=$one_item->get_item_sum_net();
	 		}
	 		foreach ($netSumArray as $taxrate => $rateNetSum) {
	 			$lokal_sum+=(int)round($rateNetSum * (1 + (((float)$taxrate) / 100)));
	 		}  			
 		} else {
	 	    foreach ($this->basket_items as $one_item) {
	 	    	$lokal_sum+=$one_item->get_item_sum_gross();
	 	    }
 	    }
 	    $this->basket_sum_gross=$lokal_sum;
	    
 	    return $this->basket_sum_gross;
 	}
 	
 	/**
 	 * gets the net basket sum
 	 * @return basket_sum
 	 */
 	function get_net_sum()
 	{
 		$lokal_sum=0;
 		if($this->pricefromnet == 0) {
 			$grossSumArray = array();
 			foreach ($this->basket_items as $one_item) {
	 			$grossSumArray[(string)$one_item->get_tax()]+=$one_item->get_item_sum_gross();
	 		}
	 		foreach ($grossSumArray as $taxrate => $rateGrossSum) {
	 			$lokal_sum+=(int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
	 		} 			
 		} else {
	 		foreach ($this->basket_items as $one_item) {
	 			$lokal_sum+=$one_item->get_item_sum_net();
	 		}
 		}
 		$this->basket_sum_net=$lokal_sum;
 		return $lokal_sum;
 		
 	}
 	
 	/**
 	 * Creates an Array of assoc_arrays from the basket_articles
 	 * the array within 
 	 * array (uid => array(
 	 * 			'article' => result form tx_commerce_article->return_assoc_array(),
 	 * 			'product' => result form tx_commerce_product->return_assoc_array(),
 	 * 	),
 	 * 	uid2 =>array(
 	 * 			'article' => result form tx_commerce_article->return_assoc_array();
 	 * 			'product' => result form tx_commerce_product->return_assoc_array();
 	 * 	),
 	 *  )
 	 * @param prefix Prefix for the keys or returnung array optional
 	 * @return array or arrays
 	 */
 	
 	function get_assoc_arrays($prefix='')
 	{
 		$result_array=array();
 		
 		foreach ($this->basket_items as $oneuid  => $one_item)	{
 			$result_array[$oneuid]=$one_item->get_array_of_assoc_array($prefix);
 		}
 		
 		return $result_array;
 		
 	}
 	
 	/**
 	 * Returns an array of articles to a corresponding article_tyoe
 	 * @param $article_type_uid article type
 	 * @return array or article_ids
 	 */
 	
 	function get_articles_by_article_type_uid_asUidlist($article_type_uid){
 		$result_array=array();
 		foreach ($this->basket_items as $oneuid  => $one_item){
 			
 			if ($one_item->article->article_type_uid == $article_type_uid){
			    	$result_array[]=$oneuid;	
 			}
 			
 		}
 		return $result_array; 	
 	}
 	
 	/**
 	 * returns the Count of all Articles for this type
 	 * Useful for geting the deliverycost 
 	 * @example
 	 * $basket->getArticleTypeSumNet(PAYMENTArticleType)
 	 * $basket->getArticleTypeSumNet(DELIVERYArticleType)
	 * @return sum as integer
	 * 
 	 */
 	
 	function getArticleTypeCount($article_type_uid) {
 		
 		$Count=0;
 		foreach ($this->basket_items as $oneuid  => $one_item)	{
		   	if ($one_item->article->article_type_uid == $article_type_uid)		{
			    	$Count++;
 			}
 		}
 		return $Count;
 	
		
	}

 	/**
 	 * returns the count of allArticles in the given array
 	 * @param	array	$articleType
 	 */
 	
 	function getArticleTypeCountFromList($articleTypes){
 		
 		$Count=0;
 		foreach ($this->basket_items as $oneuid  => $one_item)	{
		   	if (in_array($one_item->article->article_type_uid ,$articleTypes))		{
			    	$Count++;
 			}
 		}
 		return $Count;
 	}
 	/**
 	 * returns the Sum of all Articles for this type
 	 * Useful for geting the deliverycost 
 	 * @example
 	 * $basket->getArticleTypeSumNet(PAYMENTArticleType)
 	 * $basket->getArticleTypeSumNet(DELIVERYArticleType)
	 * @return sum as integer
	 * 
 	 */
 	  /** @TODO check function */
 	function getArticleTypeSumNet($article_type_uid){
 		$sumNet=0;
 		if($this->pricefromnet == 0) {
 			$grossSumArray = array();
 			foreach ($this->basket_items as $oneuid  => $one_item)	{
		   		if ($one_item->article->article_type_uid == $article_type_uid)		{
			    	$grossSumArray[(string)$one_item->get_tax()]+=$one_item->get_item_sum_gross();
 				}
 			}
	 		foreach ($grossSumArray as $taxrate => $rateGrossSum) {
	 			$sumNet+=(int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
	 		} 			
 		} else {
	 		foreach ($this->basket_items as $oneuid  => $one_item)	{
		   		if ($one_item->article->article_type_uid == $article_type_uid)		{
			    	$sumNet+=($one_item->get_quantity()*$one_item->get_price_net());
 				}
 			}
 		}
 		
 		return $sumNet;
 	}
 	/**
 	 * returns the Sum of all Articles for this type
 	 * Useful for geting the deliverycost 
 	 * @example
 	 * $basket->getArticleTypeSumGross(PAYMENTArticleType)
 	 * $basket->getArticleTypeSumGross(DELIVERYArticleType)
	 * @return sum as integer
	 * 
 	 */
 	 /** @TODO check function */
 	function getArticleTypeSumGross($article_type_uid){
 		$sumGross=0;
 		if($this->pricefromnet == 1) {
 			$netSumArray = array();
 			foreach ($this->basket_items as $oneuid  => $one_item)	{
		   		if ($one_item->article->article_type_uid == $article_type_uid)		{
			    	$netSumArray[(string)$one_item->get_tax()]+=$one_item->get_item_sum_net();
 				}
 			}
	 		foreach ($netSumArray as $taxrate => $rateGrossSum) {
	 			$sumGross+=(int)round($rateGrossSum * (1 + (((float)$taxrate) / 100)));
	 		} 			
 		} else {
	 		foreach ($this->basket_items as $oneuid  => $one_item)	{
		   		if ($one_item->article->article_type_uid == $article_type_uid)		{
			    	$sumGross+=($one_item->get_quantity()*$one_item->get_price_gross());
 				}
 			}
 		}
 		
 		return $sumGross;
 	}
 	/**
 	 * Returns the first Title from of all Articles concerning this type
 	 *
 	 * @example
 	 * $basket->getFirstArticleTypeTitle(PAYMENTArticleType)
 	 * $basket->getFirstArticleTypeTitle(DELIVERYArticleType)
	 * @return text TITLE
	 * 
 	 */
 	function getFirstArticleTypeTitle($article_type_uid){
 		foreach ($this->basket_items as $oneuid  => $one_item)	{
 			
		   	if ($one_item->article->article_type_uid == $article_type_uid)		{
			    	if ($one_item->article->get_title()>'')
			    	{
			    		return 	$one_item->article->get_title();
			    	}
 			}
 		}
 	}

 	/**
 	 * Returns the first Description from of all Articles concerning this type
 	 *
 	 * @example
 	 * $basket->getFirstArticleTypeDescription(PAYMENTArticleType)
 	 * $basket->getFirstArticleTypeDescription(DELIVERYArticleType)
	 * @return text Description
	 * 
 	 */
 	function getFirstArticleTypeDescription($article_type_uid){
 		foreach ($this->basket_items as $oneuid  => $one_item)	{
		   	if ($one_item->article->article_type_uid == $article_type_uid) {
			    	if ($one_item->article->get_description_extra()>'')	{
			    		return 	$one_item->article->get_description_extra();
			    	}
 			}
 		}
 	}

 	/**
 	 * Calculates the TAX-Sum for the complete Basket
 	 * @return integert sum
 	 */
 	
 	function getTaxSum(){
 		$taxSum=0;
 		$taxRatesSums = $this->getTaxRateSums();
 		foreach ($taxRatesSums as $taxRateSum) {
 			$taxSum+= $taxRateSum;			
 		}
 		return $taxSum;
 	}
 	
 	
 	/**
 	 * Calculates the TAX-Sum for the complete and different Tax-Rates depending on article
 	 * @return array Taxratesums
 	 */
 	
 	function getTaxRateSums(){
 		$taxes=array();
 		foreach ($this->basket_items as $oneuid  => $one_item)	{
			$taxRate = $one_item->get_tax();
			$taxRate = (string)$taxRate;
			if($this->pricefromnet == 1) {
				$taxSum = ($one_item->get_item_sum_net() * (((float)$taxRate) /100));
			} else {
				$taxSum = ($one_item->get_item_sum_gross() * ((((float)$taxRate) / 100) / (1 + (((float)$taxRate) / 100))));
			}
			if(!isset($taxes[$taxRate]) AND $taxSum <= 0) {
				continue;
			}
			if(!isset($taxes[$taxRate])) {
				$taxes[$taxRate] = 0;
			}
			
			$taxes[$taxRate] += $taxSum;
		}
 		
 		foreach ($taxes as $taxRate => $taxSum) {
 				$taxes[$taxRate]= (int)round($taxSum);			
 		}
 		
 		return $taxes;
 	}
 	
 	/**
 	 * returns true if the the basket has currently active articles
 	 * @return booles true / false
 	 */
 	 function hasArticles() {
 	 	if (count($this->basket_items)>0) {
 	 		return true;	
 	 	}else {
 	 		return false;	
 	 	}
 	 }
 	 
 	/**
 	 * returns true if the the basket has currently active articles
 	 * @return char basketHash value
 	 */
 	 function  getBasketHashValue() {
 	 	if (count($this->basket_items)>0) { 	 			
 	 		return t3lib_div::shortMD5(serialize($this->basket_items));	
 	 	} else {
 	 		return false;
 	 	}
 	 }
 	 
 	 /**
 	  * This Method Sets the Tax Calculation method (pricefromnet)
 	  * 
 	  * @param   boolean	Switch if calculationg from net or not
 	  * @return  void
 	  */
 	 function setTaxCalculationMethod($priceFromNet) {
 	 
 	 	$this->pricefromnet = $priceFromNet;
 	 	foreach ($this->basket_items as $one_item) {
 			$one_item->setTaxCalculationMethod($this->pricefromnet);
 		}
 	 }
 	
 	 
 /**
 	 * Sets the Basket to readonly, for checkout
 	 *
 	 */
 	function setReadOnly(){
 		$this->readOnly = true;
 	}
 	/**
 	 * returns the currenty readonly statd
 	 *
 	 * @return boolean: true if readonly
 	 */
 	
 	function isReadOnly(){
 		return $this->readOnly;
 	}
 	/**
 	 * returns True if the basket is changeable
 	 *
 	 * @return unknown
 	 */
 	function isChangeable(){
 		return !$this->readOnly;
 	}
 	
 	/**
 	 * releasses the readOnly
 	 *
 	 */
 	function releaseReadOnly(){
 		$this->readOnly = false;
 	}
 }
 
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_basic_basket.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_basic_basket.php"]);
}
 ?>
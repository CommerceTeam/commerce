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
 * Libary for handling basket-items in the Frontend. 
 * 
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_basket 
 * Basic class for basket_items
 * @todo change  {} from pascal to php doc style :-)
 * @todo implementation of language
 * 
 * $Id: class.tx_commerce_basket_item.php 8328 2008-02-20 18:02:10Z ischmittis $
 **/


require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_article.php'); 
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_product.php'); 

 
/**
 * Class for defining one Item in the basket
 * 
*/

class tx_commerce_basket_item{
 	
 	/**
 	 * object Article_object
 	 * @see tx_commerce_article
 	 * @access public
 	 */
 	var $article='';
 	
 	/**
 	 * Object tx_commerce_product 
 	 * @see tx_commerce_product
 	 * @access public
 	 * 
 	 */
 	var $product='';

 	/**
 	 * Object tx_commerce_article_price
 	 * @see tx_commerce_product
 	 * @access private
 	 * 
 	 */
 	var $price='';
 	
 	/**
 	 * integer quantity for this article
 	 * @access private
 	 */
 	var $quantity=0;

 	/**
 	 * integer priceid for this item
 	 * @access private
 	 */
 	var $priceid='';
 	
 	/**
 	 * item summe from net_price
 	 * @acces private
 	 */
 	var $item_net_sum=0;
 	
 	/**
 	 * item summe from gross_price
 	 * @acces private
 	 */
 	var $item_gross_sum=0;

	/**
	 * calculated price from net price
	 * @access private
	 */
    
	var $pricefromnet = 0;
	
	
	/**
	 * Net Price for this item
	 * @var price_net
	 * @acces private 
	 */
	 var $priceNet;
	 
	 /**
	 * Gross Price for this item
	 * @var price_gross
	 * @acces  private 
	 */
	 var $priceGross;
	 
	 /**
	  * Lang uid
	  * @var lang_uid
	  * @acces private
	  */
	var $lang_id=0; 	
 	/**
 	 * Call to $this->init
 	 * @param uid  artcile UID
 	 * @param quantity amount for this article
 	 * @param lang_id Language ID
 	 */
 	
 	function tx_commerce_basket_item()	{
		if ((func_num_args()>=3) && (func_num_args()<=4)){
			$uid = func_get_arg(0); 
			$quantity = func_get_arg(1);
			$priceid = func_get_arg(2);
			if (func_num_args()==4){
				$lang_uid=func_get_arg(3);
			}else
			{
				$lang_uid=0;
			}
			return $this->init($uid,$quantity,$priceid,$lang_uid);
		}
		
	}
 		
 	/**
 	 * Initialises the object,
 	 * checks if given uid is valid and loads the the article an product data
 	 * @param uid  artcile UID
 	 * @param quantity amount for this article
 	 * @param lang_id Language ID
 	 */
 	function init($uid,$quantity,$priceid,$lang_id=0){
 		
 		$this->quantity=$quantity;
 		$this->lang_id=$lang_id;
 		$this->article = new tx_commerce_article($uid,$this->lang_id);
 		
 		
		if($quantity < 1 ) return false;
		
		if (is_object($this->article)) {
	 		
	 			$this->article->load_data();
	 					
	 			$this->product=$this->article->get_parent_product();
	 			$this->product->load_data();
	 			
	 			
	 			$this->priceid = $priceid; 
				$this->price = new tx_commerce_article_price($priceid,$this->lang_id);
				$this->price->load_data();
				$this->priceNet=$this->price->get_price_net();
				$this->priceGross=$this->price->get_price_gross();
				$this->recalculate_item_sums();
	 		
	 			return true;
	 			
				
	 		
		}
 		
 		
	 	/***
	 	 * Article is not availiabe, so clear object
	 	**/
 		$this->quantity=0;
 		$this->article='';
 		$this->product='';
 		return false;	
 		
 	}
 	
 	
 	
 	
 	/**
 	 * Change the basket item quantity
 	 * @param quanitity
 	 * @return true
 	 * @access public
 	 */
 	function change_quantity($quantity) 	{
 		$this->quantity=$quantity;
 		$this->priceid=$this->article->getActualPriceforScaleUid($quantity);
 		
 		$this->price = new tx_commerce_article_price($this->priceid,$this->lang_id);
		$this->price->load_data();
 		$this->priceNet=$this->price->get_price_net();
		$this->priceGross=$this->price->get_price_gross();
		$this->recalculate_item_sums();
 		return true;
 	}
 	
 	/**
 	 * recalculates the itm sums
 	 */
 	
 	function recalculate_item_sums() 	{
 		$this->calculate_net_sum();
 		$this->calculate_gross_sum();
 	}
 	
 	/**
 	 * Calculates the net_sum
 	 * @return integer net_sum 
 	 * @todo add hook for this function
 	 */
 	function calculate_net_sum() 	{
 		if($this->pricefromnet == 0) {
 			$this->calculate_gross_sum();
 			$taxrate = $this->get_tax();
 			$this->item_net_sum = (int)round($this->item_gross_sum / (1 + ($taxrate / 100)));
 		} else {
 			$this->item_net_sum = $this->get_price_net() * $this->quantity;	
 		}
 		return $this->item_net_sum;
 	}
 	
 	/**
 	 * Calculates the gross_sum
 	 * @return integer gross_sum 
 	 * @todo add hook for this function
 	 */
 	function calculate_gross_sum() 	{
 		if($this->pricefromnet == 1) {
 			$this->calculate_net_sum();
 			$taxrate = $this->get_tax();
 			$this->item_gross_sum = (int)round($this->item_net_sum * (1 + ($taxrate / 100)));
 		} else {
 			$this->item_gross_sum = $this->get_price_gross() * $this->quantity;
 		}
		return $this->item_gross_sum;
 	}
 	/**
 	 * gets the price_net from thhe article
 	 * @return price_net
 	 */
 	
 	function get_price_net() 	{
 		#return $this->price->get_price_net();
 		return $this->priceNet;
 	}
 	
 	/**
 	 * return the the gross price without the scale calculation
 	 * 
 	 */
 	
 	function getNoScalePriceGross() {
 		return $this->article->get_price_gross();
 	}
 	/**
 	 * return the the net price without the scale calculation
 	 * 
 	 */
 	function getNoScalePriceNet() {
 		return $this->article->get_price_net();
 	}
 	/**
	 * Sets the Title
	 * @param new Title
	 */
 	function setTitle($title) 	{
 		$this->article->title=$title;
 		$this->product->title=$title;
 		
 	}
 	/**
	 * Gets the title
	 * @param 	string 	type of title, possible values arte article and product
	 * @return title of article (default) or product
	 */
 	function getTitle($type='article') {
 	
 		switch ($type) {
 			case 'product':
 				return $this->product->get_title();	
 				break;
 			
 			case 'article':
 			default:
 				return $this->article->get_title();	
 				break;
 				
 		}
 		
 	}
 	
 	/**
	 * Gets the subtitle of the basket item
	 * @param 	string 	type of subtitle, possible values arte article and product
	 * @return Subtitle of article (default) or product
	 */
 	
 	function getSubtitle($type='article') {
 		switch ($type) {
 			case 'product':
 				return $this->product->get_title();	
 				break;
 			
 			case 'article':
 			default:
 				return $this->article->getSubtitle();	
 				break;
 				
 		}
 		
 	}
 	/**
	 * Sets the net price
	 * @param new Price Value
	 */
 	function setPriceNet($value) 	{
 		$this->priceNet=$value;
 		$this->calculate_net_sum();
 	}
 	
 	
 	/**
 	 * gets the price_gross from thhe article
 	 * @return  price_gross
 	 */
 	
 	function get_price_gross() 	{
 	
 		return $this->priceGross;
	}
	
	/**
	 * Sets pre gross price
	 * @param new Price Value
	 */
 	function setPriceGross($value) 	{
 		$this->priceGross=$value;
 		$this->calculate_gross_sum();
 	}
 	/**
 	 * gets the tax from the article
 	 * @return  percantage of tax
 	 */
 	
 	function get_tax() 	{
 		if(is_object($this->article)) {
 			return $this->article->get_tax();
 		}
 	}
 	/**
 	 * gets the quantity from thos item
 	 * @return  quantity
 	 */
 	
 	function getQuantity() 	{
 		return $this->quantity;
 	}
 	
 	/**
 	 * gets the uid from thhe article
 	 * @return  uid
 	 */
 	
 	function get_price_uid()
 	{
 		return $this->priceid;
 	}
 	
 	
 	
 	/**
 	 * retruns the item_sum_net
 	 * @param recalculate if the sum should be recalculated, default false
 	 * @return item sum net
 	 */
 	function get_item_sum_net($recalculate=false){
 		if ($recalculate==true)
 		{
 			return $this->calculate_net_sum();
 		}
 		else
 		{
 			return 	$this->item_net_sum;
 		}
 	}
 	
 	/**
 	 * retruns the item_sum_gross
 	 * @param recalculate if the sum shoudl be recalculated, defaul false
 	 * @return item sum gross
 	 */
 	function get_item_sum_gross($recalculate=false){
 		if ($recalculate==true)
 		{
 			return $this->calculate_gross_sum();
 		}
 		else
 		{
 			return 	$this->item_gross_sum;
 		}
 	}
 	
 	/**
 	 * retruns the absolut TAX
 	 * @param recalculate if the sum shoudl be recalculated, defaul false
 	 * @return item sum gross
 	 */
 	function get_item_sum_tax($recalculate=false){
 		
 		return ($this->get_item_sum_gross($recalculate)-$this->get_item_sum_net($recalculate));
 	}
 	
 	
 	
 	/**
 	 * ----------------------------------------------------------------------
 	 * Article Methods 
 	 * ----------------------------------------------------------------------
 	 */
 	/**
 	 * gets the article type uid
 	 * @return type of the article
 	 */
 	function getArticleTypeUid() {
 		return $this->article->getArticleTypeUid();
 	}
 	
 	/**
 	 * return the Ordernumber of item
 	 * @return Oredernumber of Articles
 	 * 
 	 */
 	
 	
 	function getOrderNumber() {
 		return $this->article->getOrdernumber();
 	}
 	
 	/**
 	 * return the Ordernumber of item
 	 * @return Oredernumber of Articles
 	 * 
 	 */
 	
 	
 	function getEanCode() {
 		return $this->article->getEanCode();
 	}
 	
 	
 	/**
 	 * gets the uid from the article
 	 * @return  uid
 	 */
 	
 	function get_article_uid() 	{
 		return $this->article->get_uid();
 	}
 	
 	/**
 	 * returns the ArticleAssocArray
 	 * @return array
 	 */ 
 	 
 	function getArticleAssocArray($prefix) {
 		return $this->article->return_assoc_array($prefix);	
 	}
 	
 	/**
 	 * Returns the ArticleObject
 	 * @return article object
 	 */
 	function getArticleObj()
 	{
 		
 		return $this->article;	
 	}
 	/**
 	 * gets the article_assoc_array
 	 * @param prefix Prefix for the keys or returnung array optional
 	 * @return array
 	 * @see tx_commerce_article <- tx_commerce_element_alib
 	 */
 	
 	
 	function get_article_assoc_array($prefix='')
 	{
 		return $this->article->return_assoc_array($prefix);	
 		
 	}
 	
 	/**
 	 * ----------------------------------------------------------------------
 	 * Product Methods
 	 * ----------------------------------------------------------------------
 	 */
 	
 	/**
 	 * gets the uid from the product
 	 * @return  uid
 	 */
 	
 	function getProductUid() 	{
 		return $this->product->get_uid();
 	}
 	
 	/**
 	 * gets the master parent category
 	 * @return  category
 	 * @see producz
 	 */
 	function getProductMasterparentCategorie() 	{
 		return $this->product->getMasterparentCategorie();	
 	}
 	
 	
 	/**
 	 * returns the ArticleAssocArray
 	 * @return array
 	 */ 
 	 
 	function getProductAssocArray($prefix) {
 		return $this->product->return_assoc_array($prefix);	
 	}
 	
 	
 	
 	/**
 	 * gets the product_assoc_array
 	 * @param prefix Prefix for the keys or returnung array optional
 	 * @return array
 	 * @see tx_commerce_product <- tx_commerce_element_alib
 	 */
 	
 	function get_product_assoc_array($prefix='')
 	{
 		return $this->getProductAssocArray($prefix);	
 	}
 	
 	
 	
 	
 	/**
 	 * --------------------------------------------------------------------
 	 * Other methods, related to article and product
 	 * --------------------------------------------------------------------
 	 */
 	
 	/**
 	 * gets an array of get_article_assoc_array and get_product_assoc_array
 	 * 
 	 * @param prefix Prefix for the keys or returnung array optional
 	 * @return array
 	 * 
 	 */
 	
 	function get_array_of_assoc_array($prefix='')
 	{
 		return array (
			'article' => $this->get_article_assoc_array($prefix),
 			'product' => $this->get_product_assoc_array($prefix)		
 	    	);
 		
 	}
 	
 	/**
 	 * This Method Sets the Tax Calculation method (pricefromnet)
 	 * 
 	 * @param   boolean	Switch if calculationg from net or not
 	 * @return  void
 	 */
 	function setTaxCalculationMethod($priceFromNet) {
 	 	$this->pricefromnet = $priceFromNet;
 	}
 	
 	/**
 	 * #######################################################################
 	 * Depricated methods !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 	 */
 	
 	
 	/**
 	 * gets the article Type uid
 	 * @return integer article type uid
 	 * @depricated
 	 */
 	
 	function get_article_article_type_uid()
 	{
 		return $this->getArticleTypeUid();
 		
 	}
 	
 	/**
 	 * gets the quantity from thos item
 	 * @return  quantity
 	 * @depricated
 	 */
 	
 	function get_quantity()
 	{
 		return $this->getQuantity();
 	}
 	
 	/** 
  	 * set a given field, only to use with custom field without own method 
  	 * 
  	 * Warning: commerce provides getMethods for all default fields. For Compatibility
  	 * reasons always use the built in Methods. Only use this method with you own added fields 
  	 * @see add_fields_to_fieldlist
  	 * @see add_field_to_fieldlist
  	 * 
  	 * @param string	$field: fieldname
  	 * @param mixed	$value: value
  	 * @return void
  	 */	
  	
  	function setField($field,$value){
		$this->$field = $value;
	}

  	/** 
  	 * get a given field value, only to use with custom field without own method 
  	 * 
  	 * Warning: commerce provides getMethods for all default fields. For Compatibility
  	 * reasons always use the built in Methods. Only use this method with you own added fields 
  	 * @see add_fields_to_fieldlist
  	 * @see add_field_to_fieldlist
  	 * 
  	 * @param string	$field: fieldname
  	 * @return mixed	value of the field
  	 */	

	function getField($field){
		return $this->$field;
	}
  	
 	
 		
 	
 }

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_basket_item.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_basket_item.php"]);
}
 ?>
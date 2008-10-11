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
 * Libary for Frontend-Rendering of article prices. This class 
 * should be used for all Fronten-Rendering, no Database calls 
 * to the commerce tables should be made directly
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic Database calls are made from a separate Database Class
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_element_alib
 * @see tx_commerce_element_alib
 * @see tx_commerce_db_price
 *
 * $Id$
 */

  
  require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_element_alib.php'); 
  require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_price.php');
 
 /**
 * 
 *
 * @author		Volker Graubaum <vg@e-netconsulting.de>
 * @coauthor	Ingo Schmitt	<is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_article_prices
 */
 
 class tx_commerce_article_price extends tx_commerce_element_alib {

	
	
	
	/**
	 * @var currency for price, will move into shop system
	 */

	 var $currency = 'EUR';
	 /**
	  * @Price Scae amount Start
	  * @access private
	  * @private
	  */
	 var $price_scale_amount_start=1;
	 /**
	  * @Price Scae amount End
	  * @access private
	  * @private
	  */
	  var $price_scale_amount_end=1;
   
   	 /**
   	  * @var price gross
   	  * @acces private
   	  */
	  var $price_gross=0;
	   /**
   	  * @var price net
   	  * @acces private
   	  */
	  var $price_net=0;
	 /**
	  * Method called by constuctor
	  * @param integer uid of product
	  * @param integer integer language_uid , default 0
	  */
	  function init($uid,$lang_uid=0) {
	    /**
	    * Define variables
	    */
	    
	     $this->database_class='tx_commerce_db_price';
	     $this->fieldlist=array('price_net','price_gross','fe_group','price_scale_amount_start','price_scale_amount_end');
	     if ($uid > 0){
	              $this->uid=$uid;
	              // Set Lang_uid always to 0, as there are no lokalised Versions of prices
	              $this->lang_uid = 0;
	              $this->conn_db=new $this->database_class;
	              
	              $hookObjectsArr = array();
				  if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postinit'] as $classRef) {
							$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
						}
				  }
				  foreach($hookObjectsArr as $hookObj)	{
						if (method_exists($hookObj, 'postinit')) {
							$hookObj->postinit($this);
						}
				  }
	              
	              return true;
	     }else{
		         return false;
         }
          
     }
     
	/**
	 * Constructor
	 * 
	 * @param integer uid of product
	 * @param integer integer language_uid , default 0
	 * @abstract calls init-Method
	 * @depricated use make_instance and init of object
	 */
	 
	 function tx_commerce_article_price() {   	
	  	if ((func_num_args()>0) && (func_num_args()<=2)){
			$uid = func_get_arg(0); 
			if (func_num_args()==2){
				$lang_uid=func_get_arg(1);
			}else{
				$lang_uid=0;
			}
			return $this->init($uid,$lang_uid);
		}
	 }																																								      
																																												      
	/**
	 * Removed get_uid since inherited from element_alib
	 * Removed return_assoc_array since inherited from element_alib
	 * removed is_valid_uid since inherited from element_alib
	 */

	

	
	
	
	
	 /**
       * @return double priceNet 
       * @access public
       */
	function getPriceNet(){
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricenet'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'postpricenet')) {
				$hookObj->postpricenet($this);
			}
		}
		return $this->price_net;
		
	}
	 /**
       * @return double priceGross
       * @access public
       */
	function getPriceGross(){
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article_price.php']['postpricegross'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'postpricegross')) {
				$hookObj->postpricegross($this);
			}
		}
		return $this->price_gross;
		
	}
	
	/**
	 * returns The Proce Scae Amount Start
	 * @return integer 
	 */
	
	function getPriceScaleAmountStart(){
		return $this->price_scale_amount_start;
	}
	
	/**
	 * returns The Proce Scae Amount End
	 * @return integer 
	 */
	function getPriceScaleAmountEnd(){
		return $this->price_scale_amount_end;
	}
 	
 	
 	/**
	 * Returns the net price as value
	 * @return integer	netPrice
	 * @deprecated
	 * @see getPriceNet()
	 */
	function get_price_net(){
	    return $this->getPriceNet();
	}
	
	
	/**
	 * Returns the gross Price
	 * @return 	integer	grossPrice
	 * @deprecated
	 * @see getPriceGross();
	 */
	
	function get_price_gross(){
	    return $this->getPriceGross();
	}
 	
 }
 
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php']);
}
 
 ?>
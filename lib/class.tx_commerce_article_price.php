<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c)  2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Libary for Frontend-Rendering of article prices. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic Database calls are made from a separate Database Class
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @coauthor Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_element_alib
 * @see tx_commerce_element_alib
 * @see tx_commerce_db_price
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
	    $uid = intval($uid);
	    $lang_uid = intval($lang_uid);
	    
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
	 * returns The Price Scale Amount End
	 * @return integer 
	 */
	function getPriceScaleAmountEnd(){
		return $this->price_scale_amount_end;
	}
 	
	/**
	 * Returns the label for the TCA
	 * Only for use in TCA
	 * @params array	record value
	 * @params object	Parent Object
	 * @return array	new record values
	 */
	function getTCARecordTitle($params, $pObj){
		global $LANG;
		
		$params['title'] = 
			$LANG->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices','price_gross'),1).': '.tx_commerce_div::FormatPrice($params['row']['price_gross']/100).
			' ,'.$LANG->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices','price_net'),1).': '.tx_commerce_div::FormatPrice($params['row']['price_net']/100).
			' ('.$LANG->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices','price_scale_amount_start'),1).': '.$params['row']['price_scale_amount_start'].
			'  '.$LANG->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices','price_scale_amount_end'),1).': '.$params['row']['price_scale_amount_end'].') '.
			' '.($params['row']['fe_group'] ?( $LANG->sL(t3lib_befunc::getItemLabel('tx_commerce_article_prices','fe_group'),1).' '.t3lib_BEfunc::getProcessedValueExtra('tx_commerce_article_prices','fe_group',$params['row']['fe_group'],100,$params['row']['uid'])) : '')
			;
		return $params;
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
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article_price.php']);
}
?>
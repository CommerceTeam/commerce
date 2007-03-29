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
 * Libary for Frontend-Rendering of attributes. This class 
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
 * @subpackage tx_commerce_attribute
 * @see tx_commerce_element_alib
 * @see tx_commerce_db_attrubute
 * 
 * Basic class for handeleing attribures
 *  */
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_element_alib.php'); 
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_attribute.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_attribute_value.php');
 
 /**
 * Main script class for the handling of attributes. An attribute desribes the
 * technical data of an article
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id: class.tx_commerce_attribute.php 308 2006-07-26 22:23:51Z ingo $
 */
  class tx_commerce_attribute extends tx_commerce_element_alib {
  
  	
  	var $title=''; 				// Title of Attribute (private)
  	
  	var $unit='';				// Unit auf the attribute (private)
  	
  	var $has_valuelist=0;		//  If the attribute has a separate value_list for selecting the value (private)
  	
  	/**
  	 * Attribute value uid list
  	 * @acces private
  	 */
  	
  	var $attribute_value_uids=array();
  	
  	/**
  	 * Attribute value object list
  	 * @acces private
  	 */
  	
  	var $attribute_values=array();
  	
  	
  
        
        
   
       
    
   /** Constructor class, basically calls init
    * @param uid integer uid or attribute
    * @param lang_uid integer language uid, default 0
    */
  	
   function tx_commerce_attribute() {
   		if ((func_num_args()>0) && (func_num_args()<=2)){
			$uid = func_get_arg(0); 
			if (func_num_args()==2){
				$lang_uid=func_get_arg(1);
			}else
			{
				$lang_uid=0;
			}
			return $this->init($uid,$lang_uid);
		}
   	
   }   
	
   
   /** Constructor class, basically calls init
    * @param uid integer uid or attribute
    * @param lang_uid integer language uid, default 0
    */
  	
   function init($uid,$lang_uid=0)
   {
		
		 $this->fieldlist=array('title','unit','has_valuelist','l18n_parent');
		 $this->database_class='tx_commerce_db_attribute';
		 if ($uid>0)
		 {
		 	
				$this->uid=$uid;
				$this->lang_uid=$lang_uid;
				$this->conn_db=new $this->database_class;
				$hookObjectsArr = array();
				if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute.php']['postinit'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute.php']['postinit'] as $classRef) {
							$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
					}
				}
				foreach($hookObjectsArr as $hookObj)	{
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
				return true;
		 }
		 else
		 {
		 	return false;	
		 }
   }
		
		
   /** Franz: how do we take care about depencies between attributes?
    * @return array values of attribute
    * @access public
    */
  	
   
  	function get_all_values()
  	{
  	
  		if ($this->attribute_value_uids=$this->conn_db->get_attribute_value_uids($this->uid))
  		{
  			foreach ($this->attribute_value_uids as $value_uid)
  			{
  				$this->attribute_values[$value_uid]=new tx_commerce_attribute_value($value_uid,$this->lang_uid);
  				$this->attribute_values[$value_uid]->load_data();
  			}	
  			
  		}
  		$return_array=array();
  		foreach ($this->attribute_values as $value_uid => $one_value)
  		{
  			$return_array[$value_uid]=$one_value->get_value();
  			
  		}
  		
  		return $return_array;	
  	}
  	
  	/**
  	 * synonym to get_all_values
  	 * @see tx_commerce_attributes->get_all_values()
  	 * 
  	 */
  	function get_values()
  	{
  		return $this->get_all_values();	
  	}

  	/**
  	 * synonym to get_all_values
  	 * @see tx_commerce_attributes->get_all_values()
  	 * @param uid uid of value
  	 */
  	function get_value($uid)
  	{
  		if ($uid)
  		{
  			if ($this->has_valuelist)
  			{
  				
  			}
  			else
  			{
  				$this->get_all_values();
				return $this->attribute_values[$uid]->get_value();
  			}
  		}
  		else
  		{
  			return false;	
  		}
		
  	}


   /** 
    * removed get_uid since inherited from elemenet_alib
    */
	/**
	 * gets the attribute title
	 * @return string title
	 * @access public
	 */
  	function get_title()
  	{
  		return $this->title;	
  	}
  	/**
  	 * 
  	 * @return string unit
  	 *  @access public
  	 */
  	function get_unit()
  	{
  		return $this->unit;	
  	}
  	
  	/**
  	 * Overwrite get_attributes as attributes cant hav attributes
  	 * @return false;
  	 */
  	function get_attributes()
  	{
  		return false;	
  	}
  	
  	
  	
  	
  	
  	
  }
  
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute.php']);
}
 ?>
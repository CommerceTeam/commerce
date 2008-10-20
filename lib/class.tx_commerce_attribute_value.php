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
 * Libary for Frontend-Rendering of attribute values. This class 
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
 * @subpackage tx_commerce_attribute_value
 * @see tx_commerce_element_alib
 * @see tx_commerce_db_attribute_value
 * 
 * Basic class for handeleing attribure_values  */
   require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_element_alib.php'); 
   require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_attribute_value.php');
 
 /**
 * Main script class for the handling of attribute Values. An attribute_value
 * desribes the technical data of an article
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id: class.tx_commerce_attribute_value.php 8328 2008-02-20 18:02:10Z ischmittis $
 */
  class tx_commerce_attribute_value extends tx_commerce_element_alib {
  
  	
  	var $title=''; 				// Title of Attribute (private)
  	
  	var $value='';				// The Value for 
  	
  	/**
  	 * @var show value
  	 * if thzis value should be shown in Fe output
  	 * @acces private
  	 */
  	var $showvalue=1;	
  	
  	/**
  	 * @var icon
	 * Icon for this Value
  	 * @acces private
  	 */
  	var $icon='';		
  	
  	
   
	
	
  
    
   /**
    * Constructor Class
    * @param uid integer uid or attribute
    * @param lang_uid integer language uid, default 0
    */
  	
   function tx_commerce_attribute_value($uid,$lang_uid=0)
   {
   		return $this->init($uid,$lang_uid) ;
   }
  
    
   /**
    * Init Class
    * @param uid integer uid or attribute
    * @param lang_uid integer language uid, default 0
    */
  
   function init($uid,$lang_uid=0)
   {
   		$uid = intval($uid);
	    $lang_uid = intval($lang_uid);
		/*
		 * Define variables
		 */
		$this->fieldlist=array('title','value','showvalue','icon','l18n_parent');
        $this->database_class='tx_commerce_db_attribute_value';
		$this->uid=$uid;
		$this->lang_uid=$lang_uid;
		$this->conn_db=new $this->database_class;
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'] as $classRef) {
							$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
					}
		}
		foreach($hookObjectsArr as $hookObj)	{
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
				}
		}
		
   }
		
		
  
  	
	/**
	 * Deleted get UID as integrated in element_alib
	 */
	
	/**
	 * gets the attribute title
	 * @param optional check if value shoudl be show in FE
	 * @return string title
	 * @access public
	 * @since 2005 01. 11. parameter checkvalue
	 */
  	function get_value($checkvalue=false)
  	{
  		if (($checkvalue) && ($this->showicon))
  		{
  			return $this->value;	
  		}elseif ($checkvalue==false)
  		{
  			return $this->value;	
  		}else
  		{
  			return false;	
  		}
  		
  		
  	}
  	
  	/**
  	 * Overwrite get_attributes as attribute_values cant hav attributes
  	 * @return false;
  	 */
  	function get_attributes()
  	{
  		return false;	
  	}
  	
  	  	
  	
  	/**
  	 * Gets thze icon for this value
  	 * @return icon
  	 */
  	 function getIcon()
  	 {
  	 	return $this->icon;	
  	 }
  	/**
  	 * Gets the showicon value 
  	 * @retun integer
  	 */
  	
  	 function getshowicon()
  	 {
  	 	return $this->showicon;	
  	 }
  	
  	
  	
  	
  }

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute_value.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute_value.php']);
}
 ?>
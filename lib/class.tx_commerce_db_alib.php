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
 * Abstract Class for handling almost all Database-Calls for all
 * FE Rendering processes. This Class is mostly extended by distinct
 * Classes for spezified Objects
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_alib
 * 
 * $Id: class.tx_commerce_db_alib.php 8328 2008-02-20 18:02:10Z ischmittis $
 */
 /**
  * @todo change from pascal to php style {}
  * @todo implementation of accessCheck to isUid and get_data
  * 
  */
  
  /*
   * Include cObj
   */
   require_once(PATH_t3lib."class.t3lib_page.php");
   
  
  
 /**
 * Basic abtract Class for Database Query for 
 * tx_commerce_product
 * tx_commerce_article
 * tx_commerce_category
 * tx_commerce_attribute
 * 
 * 
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>

 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_alib
 */
 
 class tx_commerce_db_alib{ 
 
 	/**
 	 * @var Database table concerning the data
 	 * @access private
 	 * @TODO Change in implementation with PHP5
 	 */
 	var $database_table= '';
 	
 	/**
 	 * @var Order field for most select statments
 	 * @access private
 	 */
 	var $orderField = ' sorting ';
 	
 	/**
 	 * Stores the relation for the attributes to product,category, article
 	 * @var Database attribute rel table
 	 * @acces private
 	 */
 	 var $database_attribute_rel_table='';

 	/**
 	 * debugmode for errorHandling
 	 * @var debugMode Boolean
 	 * @acces private
 	 */
 	 var $debugMode = FALSE;
 	 
 	 /**
	 * @Var Translation Mode for getRecordOverlay
	 * @see class.t3lib_page.php
	 * @acces private
	 */
	
	 var $translationMode='hideNonTranslated';

 	/**
 	 * 
 	 * @param uid integer UID for Data
 	 * @return array assoc Array with data
 	 * @todo implement access_check concering category tree
 	 * 
 	 
 	 **/
 	 
 	
 	function get_data($uid,$lang_uid=-1)
 	{
 		if ($lang_uid==-1)
 		{
 			unset($lang_uid);	
 		}
		if(is_object($GLOBALS['TSFE']->sys_page)){
		    $proofSQL = $GLOBALS['TSFE']->sys_page->enableFields($this->database_table,$GLOBALS['TSFE']->showHiddenRecords);
		}
		if ((($lang_uid ==0) || empty($lang_uid)) && ($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']>0))
		{
			$lang_uid=$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}
		
 		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
 			$this->database_table,
			"uid = '$uid' " .$proofSQL
			);
			
		
		
 		// Result should contain only one Dataset
 		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)==1)	{
 			$return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 			
 			if (($lang_uid>0) ) {
 			/**
 			 * Get Overlay, if availiabe
 			 */	
 				
 				$return_data=$GLOBALS['TSFE']->sys_page->getRecordOverlay($this->database_table,$return_data,$lang_uid,$this->translationMode);
 				
 			}
 			
 			return $return_data;
 		}else{
 			// error Handling
 			$this->error("exec_SELECTquery('*',".$this->database_table.",\"uid = $uid\"); returns no or more than one Result");
 			return false; 			
 		}
 	}
 	
 	/**
 	 * checks if one given UID is availiabe
 	 * @return boolean true id availiabe
 	 * @todo implement access_check
 	 */
 	
 	function isUid($uid){
		if(!$uid){
		    return false;
		}
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid',
 			$this->database_table,
			"uid = $uid"
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)==1){
 			return true;
 		}else{
 			return false;	
 		}
		
 		
 	}
 	/**
 	 * Checks in the Database if a UID is accessiblbe, 
 	 * basically checks against the enableFields
 	 * @param	$uid	Record Uid
 	 * @return 	true	if is accessible
 	 * 			false	if is not accessible
 	 * @author	Ingo Schmitt	<is@marketing-factory.de>
 	 */
 	
 	function isAccessible($uid) {
 		
 		if ($uid >0) {
 			
 			 if (is_object($GLOBALS['TSFE']->sys_page)){
	 		 	$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields($this->database_table,$GLOBALS['TSFE']->showHiddenRecords);
 			 }
	 		 $result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
	 			$this->database_table,
				"uid = '$uid' " .$proofSQL
				);
			
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)==1) {
				$GLOBALS['TYPO3_DB']->sql_free_result($result);
				return true;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 		}
		return false;
 	}
 	
 	/**
 	 * Error Handling Funktion
 	 * @param string Errortext
 	 * @TODO Write to Devlog + Output
 	 * 
 	 */
 	function error($err)
 	{
 		/**
 		 * @TODO Devlog implementation
 		 * @TODO PHP Error Implementation
 		 * @TODO TYPO3db SQL Error implementation
 		 */
 		if($this->debugMode){
			debug("Error:".$err);
 		}			
 		
 	}
 	
 	/**
 	 * gets all attributes from this product
 	 * @param uid= Product uid
 	 * @param attribute_corelation_type_list array of corelation_types, optional
  	 * @return array of attribute UID 
 	 */
 	function getAttributes($uid,$attribute_corelation_type_list=''){

 		if ($this->database_attribute_rel_table==''){
 			/**
 			 * No table defined
 			 * wrong call
 			 * go away
 			 */	
 			 return false;
 		}
 		if (is_array($attribute_corelation_type_list)){
 			$add_where=' AND '.$this->database_attribute_rel_table.'.uid_correlationtype in ('.implode(',',$attribute_corelation_type_list).')';	
 			
 		}

 		$result=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_commerce_attributes.uid',
 								$this->database_table,
								$this->database_attribute_rel_table,
								'tx_commerce_attributes',
								'AND '.$this->database_table.".uid = $uid".$add_where.' order by '.$this->database_attribute_rel_table.'.sorting'
								);
				
 		if (($result) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0)){
 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
 				$attribute_uid_list[]=$return_data['uid'];
 			}
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 			return $attribute_uid_list;
 			
 		}
 		return false;
 		
 	}
 	



 	/**
 	 * gets all attributes from this product
	 * @deprecated
 	 * @param uid= Product uid
 	 * @param attribute_corelation_type_list array of corelation_types, optional
  	 * @return array of attribute UID 
 	 */
 	
	function get_attributes($uid,$attribute_corelation_type_list=''){
	    
		return $this->getAttributes($uid,$attribute_corelation_type_list);
	}
  	
 	
 }
 
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_alib.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_alib.php"]);
}
?>
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
 * Database Class for tx_commerce_attributes. All database calle should
 * be made by this class. In most cases you should use the methodes 
 * provided by tx_commerce_attribute to get informations for articles.
 * Inherited from tx_commerce_db_alib
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_attribute
 * @see tx_comerce_attribute
 * @see tx_commerce_db_alib
 * 
 * $Id: class.tx_commerce_db_attribute.php 308 2006-07-26 22:23:51Z ingo $
 */
 /**
  * @todo
  * 
  */
  
 /**
 * Basic abtract Class for Database Query for 
 * Database retrival class fro product
 * inherited from tx_commerce_db_alib
 * 
 * 
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>

 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_attribute
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_alib.php');
 
class tx_commerce_db_attribute extends tx_commerce_db_alib {

 	
 	var $child_database_table='tx_commerce_attribute_values';
 	/**
 	 	* Setting the Class Datebase Table
 	 	* @access private
 	 */
 	 function tx_commerce_db_attribute()
 	 {
 	 	
 		$this->database_table= 'tx_commerce_attributes';	
 		#parent::tx_commerce_db_alib();
 	 }
 	 
 	 /**
 	  * Gets a list of attribute_value_uids
 	  * @return array
 	  */
 	 function get_attribute_value_uids($uid)
 	 {
 	 	$attribute_value_uid_list=array();
 	 	$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid',
 			$this->child_database_table,
			"attributes_uid = $uid"
			);
 		// a result is availiabe
 		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0)
 		{
 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))
 			{
 				$attribute_value_uid_list[]=$return_data['uid'];
 			}
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 			return $attribute_value_uid_list;
 		}
 		$GLOBALS['TYPO3_DB']->sql_free_result($result);
 		return $attribute_value_uid_list;
 	 	
 	 }
 	 
 	
 	
 	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_attribute.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_attribute.php"]);
}
?>

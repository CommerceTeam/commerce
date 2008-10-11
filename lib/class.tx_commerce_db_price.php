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
 * Database Class for tx_commerce_article_prices. All database calle should
 * be made by this class. In most cases you should use the methodes 
 * provided by tx_commerce_article_price to get informations for articles.
 * Inherited from tx_commerce_db_alib
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_db_alib
 * @see tx_comerce_article
 * @see tx_commerce_db_price
 * 
 * $Id$
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
 * @subpackage tx_commerce_db__product
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_alib.php');
require_once(t3lib_extMgm::extPath('moneylib').'/class.tx_moneylib.php');
 
class tx_commerce_db_price extends tx_commerce_db_alib {

 	
 	/**
 	 * @var Database table concerning the data
 	 * @access private
 	 */
 	var $database_table= 'tx_commerce_article_prices';	
 	
 	
 	/**
 	 * 
 	 * @param uid integer UID for Data
 	 * @return array assoc Array with data
 	 * @todo implement access_check concering category tree
 	 * Special Implementation for prices, as they don't have a localisation'
 	 
 	 **/
 	 
 	
 	function get_data($uid,$lang_uid=-1)
 	{
 		
 		unset($lang_uid);	
 		
		if(is_object($GLOBALS['TSFE']->sys_page)){
		    $proofSQL = $GLOBALS['TSFE']->sys_page->enableFields($this->database_table,$GLOBALS['TSFE']->showHiddenRecords);
		}
		
		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
 			$this->database_table,
			"uid = '$uid' " .$proofSQL
			);
		
		#}
		/**
		 * @TODO: Test
		 */
 		// Result should contain only one Dataset
 		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)==1)
 		{
 			$return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 					
 			return $return_data;
 		}
 		else
 		{
 			// error Handling
 			$this->error("exec_SELECTquery('*',".$this->database_table.",\"uid = $uid\"); returns no or more than one Result");
 			return false; 			
 		}
 	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_price.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_db_price.php"]);
}
?>

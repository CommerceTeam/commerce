<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Carsten Lausen
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
* class tx_commerce_tcehooks for the extension openbc feuser extension
* The method processDatamap_preProcessFieldArray() from this class is called by process_datamap() from class.t3lib_tcemain.php.
* The method processDatamap_postProcessFieldArray() from this class is called by process_datamap() from class.t3lib_tcemain.php.
* The method processDatamap_afterDatabaseOperations() from this class is called by process_datamap() from class.t3lib_tcemain.php.
*
* This class handles backend updates
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/

require_once(t3lib_extMgm::extPath('commerce').'dao/class.feusers_observer.php');
require_once(t3lib_extMgm::extPath('commerce').'dao/class.address_observer.php');


class tx_commerce_tcehooksHandler {

	/**
	* At this place we process prices, before they are written to the database. We use this for tax calculation
	*
	* @param array $incomingFieldArray: The values from the form, by reference
	* @param string $table: The table we are working on
	* @param int $id: The uid we are working on
	* @param mixed $pObj: The caller
	*/
	function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, $pObj) {
		if($table=='tx_commerce_article_prices') {
			//Get the whole price, not only the tce-form fields
			foreach($pObj->datamap['tx_commerce_articles'] as $v){
				$uids = explode(',',$v['prices']);
				if(in_array($id, $uids)) {
					$this->calculateTax($incomingFieldArray, doubleval($v['tax']));
				}
			}
			foreach($incomingFieldArray as $key => $value){
				if ($key == 'price_net' || $key == 'price_gross' || $key == 'purchase_price')   {
					if (is_numeric($value)){
						$incomingFieldArray[$key] = intval($value *100);
					}
				}
			}
		}
	}
	
	function calculateTax(&$fieldArray, $tax) {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
		if($extConf['genprices']==0) {
			return;
		} else {
			if($extConf['genprices']==2 || !isset($fieldArray['price_gross']) || $fieldArray['price_gross']==='' || strlen($fieldArray['price_gross'])==0 || doubleval($fieldArray['price_gross'])===0.0) {
				$fieldArray['price_gross']=round(($fieldArray['price_net']*100)*(100+$tax)/100)/100;
			}
			if($extConf['genprices']==3 || !isset($fieldArray['price_net']) || $fieldArray['price_net']==='' || strlen($fieldArray['price_net'])==0 || doubleval($fieldArray['price_net'])===0.0) {
				$fieldArray['price_net']=round(($fieldArray['price_gross']*100)/(100+$tax)*100)/100;
			}
		}
	}

	/**
	* processDatamap_postProcessFieldArray()
	* this function is called by the Hook in tce from class.t3lib_tcemain.php after processing insert & update database operations
	*
	* @param string $status: update or new
	* @param string $table: database table
	* @param string $id: database table
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	*/
	
	function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj){
		if($table=='tx_commerce_article_prices') {
			// ugly hack since typo3 makes ugly checks
			foreach($fieldArray as $key => $value){
				if ($key == 'price_net' || $key == 'price_gross' || $key == 'purchase_price')   {
					$fieldArray[$key] = intval($value);
				}
			}
		}
	}
	
	/**
	* processDatamap_afterDatabaseOperations()
	* this function is called by the Hook in tce from class.t3lib_tcemain.php after processing insert & update database operations
	*
	* @param string $status: update or new
	* @param string $table: database table
	* @param string $id: database table
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	*/
	function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$pObj){
		if ($table=='fe_users') {
			//do something...
			if (($status=='new') OR (empty($fieldArray['tx_commerce_tt_address_id']))) {
				$this->notify_feuserObserver($status, $table, $id, $fieldArray, $pObj);
			} else {
				$emptyArray=array();
				$this->notify_addressObserver($status, $table, $fieldArray['tx_commerce_tt_address_id'], $emptyArray, $pObj);
			}
		}
		if ($table=='tt_address') {
			//do something...
			$this->notify_addressObserver($status, $table, $id, $fieldArray, $pObj);
		}
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
       
        if ($table=='tx_commerce_articles' && $extConf['simpleMode'] && ($articleId = $pObj->substNEWwithIDs[$id])) {
           
            /**
             * @author     Ingo Schmitt    <is@marketing-factory.de>
             */
            // Now check, if the parent Product is already lokalised, so creat Article in the lokalised version
            // Select from Database different localisations
           
            $resOrigArticle=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_articles','uid='.intval($articleId).' and deleted = 0');
            $origArticle=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resOrigArticle);
            $resLocalisedProducts=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_products','l18n_parent='.intval($origArticle['uid_product']).' and deleted = 0');
            if (($resLocalisedProducts) && ($GLOBALS['TYPO3_DB']->sql_num_rows($resLocalisedProducts)>0)) {
                // Only if there are products
                while ($localisedProducts = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resLocalisedProducts))    {
                    // walk thru and create articles
                    $destLanguage=$localisedProducts['sys_language_uid'];
                    // get the highest sorting
                    $langIsoCode = t3lib_BEfunc::getRecord('sys_language', intval($destLanguage), 'static_lang_isocode');
                    $langIdent = t3lib_BEfunc::getRecord('static_languages', intval($langIsoCode['static_lang_isocode']), 'lg_typo3');
                    $langIdent = strtoupper($langIdent['lg_typo3']);
   
                    // create article data array
                    $articleData = array(
                        'pid' => intval($fieldArray['pid']),
                        'crdate' => time(),
                        'title' => $fieldArray['title'],
                        'uid_product' => intval($localisedProducts['uid']),
                        'sys_language_uid' => intval($localisedProducts['sys_language_uid']),
                        'l18n_parent' => intval($articleId),
                        'sorting' => (intval($fieldArray['sorting']) *2),
                        'article_type_uid' => intval($fieldArray['article_type_uid']),
                    );
                   
                        // create the article
                    $articleRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles', $articleData);
                }
            }
        }
	}


	/**
	* processCmdmap_preProcess()
	* this function is called by the Hook in tce from class.t3lib_tcemain.php before processing commands
	*
	* @param string $command: reference to command: move,copy,version,delete or undelete
	* @param string $table: database table
	* @param string $id: database record uid
	* @param array $value: reference to command parameter array
	* @param object $pObj: page Object reference
	*/
	function processCmdmap_preProcess(&$command, $table, $id, &$value, &$pObj){
		if (($table=='tt_address') AND ($command=='delete')) {
			//do something...
		    if($this->checkAddressDelete($id)){
				//remove delete command
				$command='';
		    };
		}
	}




	// ------------------------------------------------------------------------------------------------------------


	/**
	 * notify feuser observer
	 *
	 * get id and notify observer
	 *
	* @param string $status: update or new
	* @param string $table: database table
	* @param string $id: database table
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	 */
	function notify_feuserObserver($status, $table, $id, &$fieldArray, &$pObj) {


		//get id
		if($status=='new') $id = $pObj->substNEWwithIDs[$id];

		//notify observer
		feusers_observer::update($status, $id, $fieldArray);

	}


	/**
	 * notify address observer
	 *
	 * check status and notify observer
	 *
	* @param string $status: update or new
	* @param string $table: database table
	* @param string $id: database table
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	 */
	function notify_addressObserver($status, $table, $id, &$fieldArray, &$pObj) {

		//if address is updated
		if($status=='update') {
			//notify observer
			address_observer::update($status, $id, $fieldArray);
		}

	}


	function checkAddressDelete($id) {
		return address_observer::checkDelete($id);
	}

}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/hooks/class.tx_commerce_tcehooksHandler.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/hooks/class.tx_commerce_tcehooksHandler.php']);
}
?>
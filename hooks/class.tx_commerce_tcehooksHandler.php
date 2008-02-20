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
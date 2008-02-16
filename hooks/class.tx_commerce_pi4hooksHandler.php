<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Carsten Lausen
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


class tx_commerce_pi4hooksHandler {


	/**
	* deleteAddress()
	* this function is called by the Hook in tx_commerce_pi4 before processing delete address operations
	* return false to permit delete operation
	*
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	* @return string error: do not delete message
	*/
	function deleteAddress($id, &$pObj) {
		return $this->checkAddressDelete($id);
	}


	/**
	* beforeAddressSave()
	* this function is called by the Hook in tx_commerce_pi4 before processing insert address operations
	*
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	* @return string error: do not delete message
	*/
	function beforeAddressSave(&$fieldArray, &$pObj) {

	}


	/**
	* afterAddressSave()
	* this function is called by the Hook in tx_commerce_pi4 after processing insert address operations
	*
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	* @return string error: do not delete message
	*/

	function afterAddressSave($newUid,&$fieldArray, &$pObj) {
		//notify address observer
		$this->notify_addressObserver('new','tt_address',$uid,$fieldArray,$pObj);
	}



	/**
	* beforeAddressEdit()
	* this function is called by the Hook in tx_commerce_pi4 before processing update address operations
	*
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	* @return string error: do not delete message
	*/
	function beforeAddressEdit($uid,&$fieldArray, &$pObj) {

	}

	/**
	* afterAddressEdit()
	* this function is called by the Hook in tx_commerce_pi4 before processing update address operations
	*
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	* @return string error: do not delete message
	*/
	function afterAddressEdit($uid,&$fieldArray, &$pObj) {
		//notify address observer
		$this->notify_addressObserver('update','tt_address',$uid,$fieldArray,$pObj);
	}



	// ------------------------------------------------------------------------------------------------------------


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

		//debug($fieldArray,'pi4HooksHandler');
		//debug($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi4/class.tx_commerce_pi4.php']);

		address_observer::update($status, $id, $fieldArray);

	}

	function checkAddressDelete($id) {
		return address_observer::checkDelete($id);
	}


}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/hooks/class.tx_commerce_pi4hooksHandler.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/hooks/class.tx_commerce_pi4hooksHandler.php']);
}
?>

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
*/
class tx_commerce_pi4hooksHandler {
	/**
	* deleteAddress()
	* this function is called by the Hook in tx_commerce_pi4 before processing delete address operations
	* return false to permit delete operation
	*
	* @param integer $uid: reference to the incoming fields
	* @param object $pObj: page Object reference
	* @return string error: do not delete message
	*/
	public function deleteAddress($uid, &$pObj) {
		return $this->checkAddressDelete($uid);
	}

	/**
	* beforeAddressSave()
	* this function is called by the Hook in tx_commerce_pi4 before processing insert address operations
	*
	* @param array $fieldArray: reference to the incoming fields
	* @param object $pObj: page Object reference
	* @return string error: do not delete message
	*/
	public function beforeAddressSave(&$fieldArray, &$pObj) {
	}

	/**
	 * afterAddressSave()
	 * this function is called by the Hook in tx_commerce_pi4 after processing insert address operations
	 *
	 * @param integer $uid
	 * @param array $fieldArray: reference to the incoming fields
	 * @param object $pObj: page Object reference
	 * @return string error: do not delete message
	 */
	public function afterAddressSave($uid, &$fieldArray, &$pObj) {
			// notify address observer
		$this->notify_addressObserver('new', 'tt_address', $uid, $fieldArray, $pObj);
	}

	/**
	 * beforeAddressEdit()
	 * this function is called by the Hook in tx_commerce_pi4 before processing update address operations
	 *
	 * @param integer $uid
	 * @param array $fieldArray: reference to the incoming fields
	 * @param object $pObj: page Object reference
	 * @return string error: do not delete message
	 */
	public function beforeAddressEdit($uid, &$fieldArray, &$pObj) {
	}

	/**
	 * afterAddressEdit()
	 * this function is called by the Hook in tx_commerce_pi4 before processing update address operations
	 *
	 * @param integer $uid
	 * @param array $fieldArray: reference to the incoming fields
	 * @param object $pObj: page Object reference
	 * @return string error: do not delete message
	 */
	public function afterAddressEdit($uid, &$fieldArray, &$pObj) {
			// notify address observer
		$this->notify_addressObserver('update', 'tt_address', $uid, $fieldArray, $pObj);
	}


	/**
	 * notify address observer
	 *
	 * check status and notify observer
	 *
	 * @param string $status: update or new
	 * @param string $table: database table
	 * @param integer $uid: record id
	 * @param array $fieldArray: reference to the incoming fields
	 * @param object $pObj: page Object reference
	 */
	protected function notify_addressObserver($status, $table, $uid, &$fieldArray, &$pObj) {
		Tx_Commerce_Dao_AddressObserver::update($status, $uid, $fieldArray);
	}

	/**
	 * @param integer $uid
	 * @return boolean|string
	 */
	protected function checkAddressDelete($uid) {
		return Tx_Commerce_Dao_AddressObserver::checkDelete($uid);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/class.tx_commerce_pi4hooksHandler.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Hook/class.tx_commerce_pi4hooksHandler.php']);
}

?>
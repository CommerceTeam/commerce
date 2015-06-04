<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Hook for the extension openbc feuser extension
 *
 * This class handles backend updates
 *
 * Class Tx_Commerce_Hook_Pi4Hooks
 *
 * @author 2006-2008 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Hook_Pi4Hooks {
	/**
	 * this function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing delete address
	 * operations return false to permit delete operation
	 *
	 * @param integer $uid reference to the incoming fields
	 * @return string error: do not delete message
	 */
	public function deleteAddress($uid) {
		return $this->checkAddressDelete($uid);
	}

	/**
	 * beforeAddressSave()
	 * this function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing insert address
	 * operations
	 *
	 * @param array &$fieldArray reference to the incoming fields
	 * @param object &$pObj page Object reference
	 * @return void
	 */
	public function beforeAddressSave(&$fieldArray, &$pObj) {
	}

	/**
	 * afterAddressSave()
	 * this function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController after processing
	 * insert address operations
	 *
	 * @param integer $uid
	 * @param array &$fieldArray reference to the incoming fields
	 * @param object &$pObj page Object reference
	 * @return void
	 */
	public function afterAddressSave($uid, &$fieldArray, &$pObj) {
			// notify address observer
		$this->notify_addressObserver('new', 'tt_address', $uid, $fieldArray, $pObj);
	}

	/**
	 * beforeAddressEdit()
	 * this function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing update
	 * address operations
	 *
	 * @param integer $uid
	 * @param array &$fieldArray reference to the incoming fields
	 * @param object &$pObj page Object reference
	 * @return void
	 */
	public function beforeAddressEdit($uid, &$fieldArray, &$pObj) {
	}

	/**
	 * afterAddressEdit()
	 * this function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing update address
	 * operations
	 *
	 * @param integer $uid
	 * @param array &$fieldArray reference to the incoming fields
	 * @param object &$pObj page Object reference
	 * @return void
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
	 * @param string $status update or new
	 * @param string $table database table
	 * @param integer $uid record id
	 * @param array &$fieldArray reference to the incoming fields
	 * @return void
	 */
	protected function notify_addressObserver($status, $table, $uid, &$fieldArray) {
		Tx_Commerce_Dao_AddressObserver::update($status, $uid, $fieldArray);
	}

	/**
	 * Check if an address is deleted
	 *
	 * @param integer $uid
	 * @return boolean|string
	 */
	protected function checkAddressDelete($uid) {
		return Tx_Commerce_Dao_AddressObserver::checkDelete($uid);
	}
}

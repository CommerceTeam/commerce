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
 * This class handles backend updates
 *
 * Class Tx_Commerce_Hook_Pi4Hooks
 *
 * @author 2006-2008 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Hook_Pi4Hooks {
	/**
	 * This function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing delete address
	 * operations return false to permit delete operation
	 *
	 * @param int $uid Reference to the incoming fields
	 *
	 * @return string error: do not delete message
	 */
	public function beforeDeleteAddress($uid) {
		return Tx_Commerce_Dao_AddressObserver::checkDelete($uid);
	}

	/**
	 * This function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController after processing
	 * insert address operations
	 *
	 * @param int $uid Address uid
	 * @param array $fieldArray Reference to the incoming fields
	 * @param object $pObj Page Object reference
	 *
	 * @return void
	 */
	public function afterAddressSave($uid, array &$fieldArray, &$pObj) {
		// notify address observer
		Tx_Commerce_Dao_AddressObserver::update('new', $uid);
	}

	/**
	 * This function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing update address
	 * operations
	 *
	 * @param int $uid Uid
	 * @param array $fieldArray Reference to the incoming fields
	 * @param object $pObj Page Object reference
	 *
	 * @return void
	 */
	public function afterAddressEdit($uid, array &$fieldArray, &$pObj) {
		// notify address observer
		Tx_Commerce_Dao_AddressObserver::update('update', $uid);
	}
}

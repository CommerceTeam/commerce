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


	/**
	 * This function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing delete address
	 * operations return false to permit delete operation
	 *
	 * @param int $uid Reference to the incoming fields
	 *
	 * @return string error: do not delete message
	 * @deprecated This method is redundant and will be removed in 4.0.0
	 */
	public function deleteAddress($uid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->beforeDeleteAddress($uid);
	}

	/**
	 * This function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing insert address
	 * operations
	 *
	 * @param array $fieldArray Reference to the incoming fields
	 * @param object $pObj Page Object reference
	 *
	 * @return void
	 * @deprecated This method is redundant and will be removed in 4.0.0
	 */
	public function beforeAddressSave(array &$fieldArray, &$pObj) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
	}

	/**
	 * This function is called by the Hook in
	 * Tx_Commerce_Controller_AddressesController before processing update
	 * address operations
	 *
	 * @param int $uid Uid
	 * @param array $fieldArray Reference to the incoming fields
	 * @param object $pObj Page Object reference
	 *
	 * @return void
	 * @deprecated This method is redundant and will be removed in 4.0.0
	 */
	public function beforeAddressEdit($uid, array &$fieldArray, &$pObj) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
	}

	/**
	 * Notify address observer
	 * check status and notify observer
	 *
	 * @param string $status Status [update,new]
	 * @param string $table Database table
	 * @param int $uid Record id
	 * @param array $fieldArray Reference to the incoming fields
	 *
	 * @return void
	 * @deprecated This method is redundant and will be removed in 4.0.0 please use hook afterAddressSave or afterAddressEdit
	 */
	protected function notify_addressObserver($status, $table, $uid, array &$fieldArray) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		Tx_Commerce_Dao_AddressObserver::update($status, $uid);
	}

	/**
	 * Check if an address is deleted
	 *
	 * @param int $uid Uid
	 *
	 * @return bool|string
	 * @deprecated This method is redundant and will be removed in 4.0.0
	 */
	protected function checkAddressDelete($uid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return Tx_Commerce_Dao_AddressObserver::checkDelete($uid);
	}
}

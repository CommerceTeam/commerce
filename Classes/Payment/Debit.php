<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Thomas Hempel <thomas@work.de>
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
 * Debit payment implementation
 */
class Tx_Commerce_Payment_Debit extends Tx_Commerce_Payment_PaymentAbstract {
	/**
	 * @var string Payment type
	 */
	protected $type = 'debit';

	/**
	 * @var array Locallang array, only needed if individual fields are defined
	 */
	public $LOCAL_LANG = array(
		'default' => array(
			'payment_debit_bic' => 'Bank Identification Number',
			'payment_debit_an' => 'Account number',
			'payment_debit_bn' => 'Bankname',
			'payment_debit_ah' => 'Account holder',
			'payment_debit_company' => 'Company',
		),
		'de' => array(
			'payment_debit_bic' => 'Bankleitzahl',
			'payment_debit_an' => 'Kontonummer',
			'payment_debit_bn' => 'Bankname',
			'payment_debit_ah' => 'Kontoinhaber',
			'payment_debit_company' => 'Firma',
		),
		'fr' => array(
			'payment_debit_bic' => 'Code de banque',
			'payment_debit_an' => 'Num�ro de compte',
			'payment_debit_bn' => 'Nom bancaire',
			'payment_debit_ah' => 'D�tenteur de compte',
			'payment_debit_company' => 'Firme',
		),
	);

	/**
	 * Get configuration of additional fields
	 *
	 * @return mixed|null
	 */
	public function getAdditionalFieldsConfig() {
		$result = array(
			'debit_bic.' => array(
				'mandatory' => 1
			),
			'debit_an.' => array(
				'mandatory' => 1
			),
			'debit_bn.' => array(
				'mandatory' => 1
			),
			'debit_ah.' => array(
				'mandatory' => 1
			),
			'debit_company.' => array(
				'mandatory' => 0
			)
		);
		return $result;
	}

	/**
	 * Check if provided data is ok
	 *
	 * @param array $formData Current form data
	 * @return boolean TRUE if data is ok
	 */
	public function proofData(array $formData = array()) {
		// If formData is empty we know that this is the very first
		// call from Tx_Commerce_Controller_CheckoutController->handlePayment and
		// at this time there can't be form data.
		if (empty($formData)) {
			return FALSE;
		}

		$config['sourceFields.'] = $this->getAdditionalFieldsConfig($this->pObj);

		$result = TRUE;

		foreach ($formData as $name => $value) {
			if ($config['sourceFields.'][$name . '.']['mandatory'] == 1 && strlen($value) == 0) {
				$result = FALSE;
			}
		}

		if ($this->provider !== NULL) {
			return $this->provider->proofData($formData, $result);
		}

		return $result;
	}

	/**
	 * Update order data after order has been finished
	 *
	 * @param integer $orderUid Id of this order
	 * @param array $session Session data
	 * @return void
	 */
	public function updateOrder($orderUid, array $session = array()) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$database->exec_UPDATEquery(
			'tx_commerce_orders',
			'uid = ' . $orderUid,
			array(
				'payment_debit_bic' => $session['payment']['debit_bic'],
				'payment_debit_an' => $session['payment']['debit_an'],
				'payment_debit_bn' => $session['payment']['debit_bn'],
				'payment_debit_ah' => $session['payment']['debit_ah'],
				'payment_debit_company' => $session['payment']['debit_company'],
			)
		);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Debit.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Debit.php']);
}

?>
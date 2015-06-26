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
 * Debit payment implementation
 *
 * Class Tx_Commerce_Payment_Debit
 *
 * @author 2005-2011 Thomas Hempel <thomas@work.de>
 */
class Tx_Commerce_Payment_Debit extends Tx_Commerce_Payment_PaymentAbstract {
	/**
	 * Payment type
	 *
	 * @var string
	 */
	protected $type = 'debit';

	/**
	 * Locallang array, only needed if individual fields are defined
	 *
	 * @var array
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
	 * @return array
	 */
	public function getAdditionalFieldsConfig() {
		return array(
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
	}

	/**
	 * Check if provided data is ok
	 *
	 * @param array $formData Current form data
	 *
	 * @return bool Check if data is ok
	 */
	public function proofData(array $formData = array()) {
		// If formData is empty we know that this is the very first
		// call from Tx_Commerce_Controller_CheckoutController->handlePayment and
		// at this time there can't be form data.
		if (empty($formData)) {
			return FALSE;
		}

		$config['sourceFields.'] = $this->getAdditionalFieldsConfig();

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
	 * @param int $orderUid Id of this order
	 * @param array $session Session data
	 *
	 * @return void
	 */
	public function updateOrder($orderUid, array $session = array()) {
		$this->getDatabaseConnection()->exec_UPDATEquery(
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


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

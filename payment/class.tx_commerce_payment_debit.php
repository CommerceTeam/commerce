<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Volker Graubaum <vg@e-netconsulting.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


require_once(t3lib_extmgm::extPath('commerce') . 'payment/class.tx_commerce_payment_abstract.php');


/**
 *
 *
 * @package commerce
 * @subpackage payment
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @internal Maintainer Michael Staatz <michael.staatz@e-netconsulting.com>
 */
class tx_commerce_payment_debit extends tx_commerce_payment_abstract {

	protected $type = 'debit';

	/**
	 * The locallang array for this payment module
	 * This is only needed, if individual fields are defined
	 */
	public $LOCAL_LANG = array (
		'default' => array (
			'payment_debit_bic' => 'Bank Identification Number',
			'payment_debit_an' => 'Account number',
			'payment_debit_bn' => 'Bankname',
			'payment_debit_ah' => 'Account holder',
			'payment_debit_company' => 'Company',
		),
		'de' => array (
			'payment_debit_bic' => 'Bankleitzahl',
			'payment_debit_an' => 'Kontonummer',
			'payment_debit_bn' => 'Bankname',
			'payment_debit_ah' => 'Kontoinhaber',
			'payment_debit_company' => 'Firma',
		),
		'fr' => array (
			'payment_debit_bic' => 'Code de banque',
			'payment_debit_an' => 'Num�ro de compte',
			'payment_debit_bn' => 'Nom bancaire',
			'payment_debit_ah' => 'D�tenteur de compte',
			'payment_debit_company' => 'Firme',
		),
	);


	public function getAdditonalFieldsConfig($pObj) {
		if (!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}
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


	public function proofData($formData, $pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		// if formData is empty we know that this is the very first
		// call from tx_commerce_pi3->handlePayment and at this time
		// there can't be formData.
		if (empty($formData)) {
			return false;
		}

		$config['sourceFields.'] = $this->getAdditonalFieldsConfig($this->pObj);

		$result = true;

		foreach ($formData as $name => $value) {
			if ($config['sourceFields.'][$name . '.']['mandatory'] == 1 && strlen($value) == 0) {
				$result = false;
			}
		}

		if ($this->provider !== null) {
			return $this->provider->proofData($formData, $result, $this);
		}

		return $result;
	}


	/**
	 * This method can make something with the created order. For example add the
	 * reference id for payments with creditcards.
	 */
	public function updateOrder($orderUid, $session, $pObj) {
		if (!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
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


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_debit.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_debit.php"]);
}


?>
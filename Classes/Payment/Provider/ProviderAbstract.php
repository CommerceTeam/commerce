<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Volker Graubaum <vg@e-netconsulting.de>
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
 * Abstract payment provider implementation
 */
abstract class Tx_Commerce_Payment_Provider_ProviderAbstract implements Tx_Commerce_Payment_Interface_Provider {

	/**
	 * @var array Index of error messages (keys are field names)
	 */
	public $errorMessages = array();

	/**
	 * @var Tx_Commerce_Payment_Interface_Payment Parent payment object
	 */
	protected $paymentObject = NULL;

	/**
	 * @var string Provider type, eg 'wirecard'
	 */
	protected $type = '';

	/**
	 * @var array Criteria objects bound to this payment provider
	 */
	protected $criteria = array();

	/**
	 * Construct this payment provider
	 *
	 * @param Tx_Commerce_Payment_Interface_Payment $paymentObject Parent payment object
	 * @return self
	 */
	public function __construct(Tx_Commerce_Payment_Interface_Payment $paymentObject) {
		$this->paymentObject = $paymentObject;
		$this->loadCriteria();
	}

	/**
	 * Load configured criteria
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function loadCriteria() {
			// Get and instantiate registered criteria of this payment provider
		$criteraConfigurations = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['PAYMENT']['types'][$this->paymentObject->getType()]['provider'][$this->type]['criteria'];
		if (is_array($criteraConfigurations)) {
			foreach ($criteraConfigurations as $criterionConfiguration) {
				if (!is_array($criterionConfiguration['options'])) {
					$criterionConfiguration['options'] = array();
				}
				/** @var $criterion Tx_Commerce_Payment_Interface_ProviderCriterion */
				$criterion = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($criterionConfiguration['class'], $this, $criterionConfiguration['options']);
				if (!($criterion instanceof Tx_Commerce_Payment_Interface_ProviderCriterion)) {
					throw new Exception(
						'Criterion ' . $criterionConfiguration['class'] . ' must implement interface Tx_Commerce_Payment_Interface_ProviderCriterion',
						1307720945
					);
				}
				$this->criteria[] = $criterion;
			}
		}
	}

	/**
	 * Get parent payment object
	 *
	 * @return Tx_Commerce_Payment_Interface_Payment Parent payment object
	 */
	public function getPaymentObject() {
		return $this->paymentObject;
	}

	/**
	 * Get provider type
	 *
	 * @return string Provider type
	 */
	public function getType() {
		return strtolower($this->type);
	}

	/**
	 * Check if this payment provider is allowed for the current amount, payment type etc.
	 *
	 * @return boolean TRUE if provider is allowed
	 */
	public function isAllowed() {
		$result = TRUE;
		/** @var Tx_Commerce_Payment_Criterion_CriterionAbstract $criterion */
		foreach ($this->criteria as $criterion) {
			if ($criterion->isAllowed() === FALSE) {
				$result = FALSE;
				break;
			}
		}
		return $result;
	}

	/**
	 * Determine if additional data is needed.
	 *
	 * @return boolean TRUE if the provider should be queried for more data
	 */
	public function needAdditionalData() {
		return TRUE;
	}

	/**
	 * Returns an array containing some configuration for the fields the customer shall enter his data into.
	 *
	 * @return mixed NULL for no data
	 */
	public function getAdditionalFieldsConfig() {
		return NULL;
	}

	/**
	 * Check if provided data is ok
	 *
	 * @param array $formData Current form data
	 * @param boolean $parentResult Already determined result of payment object
	 * @return bool TRUE if data is ok
	 */
	public function proofData(array $formData = array(), $parentResult = TRUE) {
		return $parentResult;
	}

	/**
	 * Wether or not finishing an order is allowed
	 *
	 * @param array $config Current configuration
	 * @param array $session Session data
	 * @param Tx_Commerce_Domain_Model_Basket $basket Basket object
	 * @return boolean TRUE if finishing order is allowed
	 */
	public function finishingFunction(array $config = array(), array $session = array(), Tx_Commerce_Domain_Model_Basket $basket = NULL) {
		return TRUE;
	}

	/**
	 * Method called in finishIt function
	 *
	 * @param array $globalRequest _REQUEST
	 * @param array $session Session array
	 * @return boolean TRUE if data is ok
	 */
	public function checkExternalData(array $globalRequest = array(), array $session = array()) {
		return TRUE;
	}

	/**
	 * Update order data after order has been finished
	 *
	 * @param integer $orderUid Id of this order
	 * @param array $session Session data
	 * @return void
	 */
	public function updateOrder($orderUid, array $session = array()) {
	}

	/**
	 * Get error message if form data was not ok
	 *
	 * @return string error message
	 */
	public function getLastError() {
		$errorMessages = '';

		foreach ($this->errorMessages as $message) {
			$errorMessages .= $message;
		}

		return $errorMessages;
	}
}

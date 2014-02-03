<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Volker Graubaum <vg@e-netconsulting.com>
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

/**
 * Abstract payment implementation
 *
 * @package commerce
 * @subpackage payment
 * @author Volker Graubaum <vg@e-netconsulting.de>
 */
abstract class tx_commerce_payment_abstract implements tx_commerce_payment {

	/**
	 * @var array Error messages, keys are field names
	 */
	public $errorMessages = array();

	/**
	 * @var tx_commerce_pi3 Reference to parent object, usually tx_commerce_pi2 or tx_commerce_pi3
	 */
	protected $pObj = NULL;

	/**
	 * @var string Payment type, for example 'creditcard'. Extending classes _must_ set this!
	 */
	protected $type = '';

	/**
	 * @var tx_commerce_payment_provider_abstract Payment proivder if configured for this payment
	 */
	protected $provider = NULL;

	/**
	 * @var array Criterion objects that check if a payment is allowed
	 */
	protected $criteria = array();

	/**
	 * Default constructor
	 *
	 * @throws Exception If type was not set or criteria are not valid
	 * @param tx_commerce_pi3 $pObj Parent object
	 * @return self
	 */
	public function __construct(tx_commerce_pi3 $pObj) {
		if (!strlen($this->type) > 0) {
			throw new Exception(
				'$type not set.',
				1306266978
			);
		}

		$this->pObj = $pObj;

			// Create criterion objects if defined
		$criteraConfigurations = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['PAYMENT']['types'][$this->type]['criteria'];
		if (is_array($criteraConfigurations)) {
			foreach ($criteraConfigurations as $criterionConfiguration) {
				if (!is_array($criterionConfiguration['options'])) {
					$criterionConfiguration['options'] = array();
				}
				$criterion = t3lib_div::makeInstance($criterionConfiguration['class'], $this, $criterionConfiguration['options']);
				if (!($criterion instanceof tx_commerce_payment_criterion)) {
					throw new Exception(
						'Criterion ' . $criterionConfiguration['class'] . ' must implement interface tx_commerce_payment_criterion',
						1306267908
					);
				}
				$this->criteria[] = $criterion;
			}
		}

		$this->findProvider();
	}

	/**
	 * Get parent object
	 *
	 * @return tx_commerce_pi3 Parent object instance
	 */
	public function getPObj() {
		return $this->pObj;
	}

	/**
	 * Get payment type
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Return TRUE if this payment type is allowed.
	 *
	 * @return boolean
	 */
	public function isAllowed() {
		/** @var tx_commerce_payment_criterion_abstract $criterion */
		foreach ($this->criteria as $criterion) {
			if ($criterion->isAllowed() === FALSE) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Get payment provider
	 *
	 * @return tx_commerce_payment_provider
	 */
	public function getProvider() {
		return $this->provider;
	}

	/**
	 * Find appropriate provider for this payment
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function findProvider() {
			// Check if type has criteria, create all needed objects
		$providerConfigurations = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['PAYMENT']['types'][$this->type]['provider'];

		if (is_array($providerConfigurations)) {
			foreach ($providerConfigurations as $providerConfiguration) {
				$provider = t3lib_div::makeInstance($providerConfiguration['class'], $this);
				if (!($provider instanceof tx_commerce_payment_provider)) {
					throw new Exception(
						'Provider ' . $providerConfiguration['class'] . ' must implement interface tx_commerce_payment_provider',
						1307705798
					);
				}
					// Check if provider is allowed and break if so
				if ($provider->isAllowed()) {
					$this->provider = $provider;
					break;
				}
			}
		}
	}

	/**
	 * Determine if additional data is needed
	 *
	 * @return boolean True if additional data is needed
	 */
	public function needAdditionalData() {
		$result = FALSE;
		if ($this->provider !== NULL) {
			$result = $this->provider->needAdditionalData();
		}
		return $result;
	}

	/**
	 * Get configuration of additional fields
	 *
	 * @return mixed|null
	 */
	public function getAdditionalFieldsConfig() {
		$result = NULL;
		if ($this->provider !== NULL) {
			$result = $this->provider->getAdditionalFieldsConfig();
		}
		return $result;
	}

	/**
	 * Check if provided data is ok
	 *
	 * @param array $formData Current form data
	 * @return boolean TRUE if data is ok
	 */
	public function proofData(array $formData = array()) {
		$result = TRUE;
		if ($this->provider !== NULL) {
			$result = $this->provider->proofData($formData, $result);
		}
		return $result;
	}

	/**
	 * Wether or not finishing an order is allowed
	 *
	 * @param array $config Current configuration
	 * @param array $session Session data
	 * @param tx_commerce_basket $basket Basket object
	 * @return boolean True is finishing order is allowed
	 */
	public function finishingFunction(array $config = array(), array $session = array(), tx_commerce_basket $basket = NULL) {
		$result = TRUE;
		if ($this->provider !== NULL) {
			$result = $this->provider->finishingFunction($config, $session, $basket);
		}
		return $result;
	}

	/**
	 * Method called in finishIt function
	 *
	 * @param array $globalRequest _REQUEST
	 * @param array $session Session array
	 * @return boolean TRUE if data is ok
	 */
	public function checkExternalData(array $globalRequest = array(), array $session = array()) {
		$result = TRUE;
		if ($this->provider !== NULL) {
			$result = $this->provider->checkExternalData($globalRequest, $session);
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
		if ($this->provider !== NULL) {
			$this->provider->updateOrder($orderUid, $session);
		}
	}

	/**
	 * Get error message if form data was not ok
	 *
	 * @return string error message
	 */
	public function getLastError() {
		$result = '';

		if ($this->provider !== NULL) {
			$result = $this->provider->getLastError();
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/class.tx_commerce_payment_abstract.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/class.tx_commerce_payment_abstract.php']);
}

?>
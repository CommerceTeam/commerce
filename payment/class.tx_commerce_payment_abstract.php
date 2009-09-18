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


/**
 *
 *
 * @package commerce
 * @subpackage payment
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @internal Maintainer Michael Staatz <michael.staatz@e-netconsulting.com>
 */
abstract class tx_commerce_payment_abstract {

	/**
	 * In this var the wrong fields are stored (for future use)
	 *
	 * @var mixed
	 */
	public $errorFields = array();

	/**
	 * This var holds the errormessages (keys are the fieldnames)
	 *
	 * @var mixed
	 */
	public $errorMessages = array();

	/**
	 * Holds an copy of parent object. should be tx_commerce_pi2 or tx_commerce_pi3
	 *
	 * @var object
	 */
	protected $pObj = null;

	/**
	 * Type of payment. E.g. creditcard
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * payment provider, if configured, for this payment
	 *
	 * @var object
	 */
	protected $provider = null;

	/**
	 * array of criteria objects for check if a paymentMethod is allowed
	 *
	 * @var mixed
	 */
	protected $criterias = array();

	/**
	 * configuration array
	 *
	 * @var mixed
	 */
	protected $configuration = array();


	/**
	 * Class constructor
	 *
	 * @param object $pObj
	 * @return void
	 */
	public function __construct(tslib_pibase $pObj) {
		$this->pObj = $pObj;

		//check if type has criterias, create all needed objects
		$criteraConfigurations = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types'][$this->type]['criteria'];
		if (is_array($criteraConfigurations)) {
			foreach ($criteraConfigurations as $criteriaConfiguration) {
				$criteria = t3lib_div::getUserObj($criteriaConfiguration['class']);
				$criteria->init($this);
				if ($criteria instanceof tx_commerce_criteria_abstract) {
					if (is_array($criteriaConfiguration['options'])) {
						$criteria->setOptions($criteriaConfiguration['options']);
					}
					$this->criterias[] = $criteria;
				}
			}
		}

		$this->setProvider();

		if ($this->provider) {
			$this->configuration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types'][$this->type]['provider'][$this->provider->getType()]['configuration'];
		}
	}


	/**
	 * Public getter for configuration
	 *
	 * @return mixed
	 */
	public function getConfiguration() {
		return $this->configuration;
	}


	/**
	 * Public setter for actual step in checkout. Is not implemented yet.
	 *
	 * @param mixed $request
	 * @param string $step
	 * @return string
	 */
	public function setStep($request, $step) {
		return $step;
	}


	/**
	 * Public getter for the parant object
	 *
	 * @return object
	 */
	public function getPObj() {
		$retPObj = &$this->pObj;
		return $retPObj;
	}


	/**
	 * Public getter for the type of payment
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * Check if the payment is allowed or not.
	 * configured by criterias.
	 *
	 * @return boolean
	 */
	public function isAllowed() {
		foreach ($this->criterias as $criteria) {
			if ($criteria->isAllowed() === false) {
				return false;
			}
		}
		return true;
	}


	/**
	 * Public getter for payment provider
	 *
	 * @return object
	 */
	public function getProvider() {
		return $this->provider;
	}


	/**
	 * Public setter for payment provider
	 *
	 * @return void
	 */
	public function setProvider() {
		//check if type has criterias, create all needed objects
		$providerConfigurations = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types'][$this->type]['provider'];

		if (is_array($providerConfigurations)) {
			foreach ($providerConfigurations as $providerConfiguration) {
				$provider = t3lib_div::getUserObj($providerConfiguration['class']);
				$provider->init($this);
				if ($provider instanceof tx_commerce_provider_abstract) {
					if ($provider->isAllowed()) {
						$this->provider = $provider;
					}
				}
			}
		}
	}


	/**
	 * Next methods are descibed in class.tx_commerce_provider_abstract.php
	 * As well as they are called from class.tx_commerce_pi3.php in method
	 * "handlePayment" and "finishIt"
	 */


	public function needAdditionalData($pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if ($this->provider !== null) {
			return $this->provider->needAdditionalData();
		}

		return true;
	}


	public function getAdditonalFieldsConfig($pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if ($this->provider !== null) {
			return $this->provider->getAdditonalFieldsConfig();
		}

		return NULL;
	}


	public function proofData($formData, $pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if($this->provider !== null) {
			return $this->provider->proofData($formData, $this);
		}
		return true;
	}


	public function finishingFunction($config, $session, $basket, $pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if ($this->provider !== null) {
			return $this->provider->finishingFunction($config, $session, $basket);
		}

		return true;
	}


	public function updateOrder($orderUid, $session, $pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if ($this->provider !== null) {
			$this->provider->updateOrder($orderUid, $session);
		}
	}


	public function getLastError($finish = 0, $pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if ($this->provider !== null) {
			return $this->provider->getLastError($finish, $this->errorMessages);
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_abstract.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_abstract.php"]);
}


?>
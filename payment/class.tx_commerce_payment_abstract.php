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
 * @internal Maintainer Michael Staatz
 */

abstract class tx_commerce_payment_abstract {

		// In this var the wrong fields are stored (for future use)
	public $errorFields = array();

		// This var holds the errormessages (keys are the fieldnames)
	public $errorMessages = array();

	protected $pObj = null;

	protected $type = '';

	protected $provider = null;

		// array of criteria objects for check if a paymentMethod is allowed
	protected $criterias = array();

		// configuration array
	protected $configuration = array();

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

	public function getConfiguration() {
		return $this->configuration;
	}

	// @TODO check if this couldn't be part of the constructor
	public function setStep($request, $step) {
		return $step;
	}

	public function getPObj() {
		$retPObj = &$this->pObj;
		return $retPObj;
	}

	public function getType() {
		return $this->type;
	}

	public function isAllowed() {
		foreach ($this->criterias as $criteria) {
			if ($criteria->isAllowed() === false) {
				return false;
			}
		}
		return true;
	}

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

	public function getProvider() {
		return $this->provider;
	}

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

	/**
	 * This method is called in the last step. Here can be made some final checks or whatever is
	 * needed to be done before saving some data in the database.
	 * Write any errors into $this->errorMessages!
	 * To save some additonal data in the database use the method updateOrder().
	 *
	 * @param	array		$config: The configuration from the TYPO3_CONF_VARS
	 * @param	array		$session
	 * @param	array		$basket: The basket object
	 * @param	stdClass	$pObj
	 *
	 * @return	boolean		True or false
	 */
	public function finishingFunction($config, $session, $basket, $pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if ($this->provider !== null) {
			return $this->provider->finishingFunction($config, $session, $basket);
		}

		return true;
	}

	/**
	 * This method can make something with the created order. For example add the
	 * reference id for payments with creditcards.
	 */
	public function updateOrder($orderUid, $session, $pObj = null) {
		if ($pObj !== null) {
			$this->pObj = $pObj;
		}

		if ($this->provider !== null) {
			$this->provider->updateOrder($orderUid, $session);
		}
	}

	/**
	 * Returns the last error message
	 */
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
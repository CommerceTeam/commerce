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
 * @package commerce
 * @subpackage payment
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @internal Maintainer Michael Staatz <michael.staatz@e-netconsulting.com>
 */

abstract class tx_commerce_provider_abstract {

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
	 * Holds an copy of the payment object for the provider
	 *
	 * @var object
	 */
	protected $pObj = null;

	/**
	 * The Provider needs also an type like the payment class
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Array of criteria objects for check if a paymentMethod is allowed
	 * @var mixed
	 */
	protected $criterias = NULL;


	/**
	 * Initializes the payment provider. (i.e. builds the criteria array to check if this payment provider is allowed later on.)
	 *
	 * @param	object	$pObj: This is the payment object
	 */
	public function init(tx_commerce_payment_abstract $pObj) {
		$this->pObj = $pObj;
	}


	/**
	 * Load all configured criterias
	 *
	 * @param void
	 * @return void
	 */
	protected function loadCriterias() {
		//clear the old criterias first
		$this->criterias = array();

		//check if type has criterias, create all needed objects
		$criteraConfigurations = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['SYSPRODUCTS']['PAYMENT']['types'][$this->pObj->getType()]['provider'][$this->type]['criteria'];

		if (is_array($criteraConfigurations)) {
			foreach ($criteraConfigurations as $criteriaConfiguration) {
				$criteria = t3lib_div::getUserObj($criteriaConfiguration['class']);
				$criteria->init($this->pObj);
				if ($criteria instanceof tx_commerce_criteria_abstract) {
					if (is_array($criteriaConfiguration['options'])) {
						$criteria->setOptions($criteriaConfiguration['options']);
					}
					$this->criterias[] = $criteria;
				}
			}
		}
	}


	/**
	 * Returns the parent object.
	 *
	 * @return object : The commerce default payment object.
	 */
	public function getPObj() {
		return $this->pObj->getPObj();
	}


	/**
	 * Returns the type of the provider
	 *
	 * @return string : type of the provider.
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * Checks, if this payment provider is allowed for the current amount, payment type etc.
	 *
	 * @return boolean: True iff this provider can and may handle the payment.
	 */
	public function isAllowed() {
		if (NULL == $this->criterias) {
			$this->loadCriterias();
		}
		foreach ($this->criterias as $criteria) {
			if ($criteria->isAllowed() === false) {
				return false;
			}
		}
		return true;
	}


	/**
	 * This method gets called by commerce, in order to check if all data required
	 * to fullfill the payment is already available.
	 *
	 * @return boolean: False iff the customer should be queried for more data
	 */
	public function needAdditionalData() {
		return true;
	}


	/**
	 * Returns an array containing some configuration for the fields the customer shall enter his data into.
	 *
	 * @return mixed
	 * @see class.tx_commerce_provider_wirecard.php to see how this can be used.
	 */
	public function getAdditonalFieldsConfig() {
		return null;
	}


	/**
	 * This function gets called by commerce and allows you to handle the data that the customer has entered
	 *
	 * @param mixed		$formData	The data the customer has entered into the payment form
	 * @param object	$pObj		The payment object
	 * @return boolean	 			True if the data you handle is correct. Otherwhise you should write your error messages into $this->errorMessages usually
	 */
	public function proofData($formData, tx_commerce_payment_abstract $pObj = null) {
		return true;
	}


	/**
	 * This method is called in the last step. Here can be made some final checks or whatever is
	 * needed to be done before saving some data in the database.
	 * Write any errors into $this->errorMessages!
	 * To save some additonal data in the database use the method updateOrder().
	 *
	 * @param	array	$config: The configuration from the TYPO3_CONF_VARS
	 * @param	array	$session
	 * @param	array	$basket
	 * @param	boolean	True or false
	 */
	public function finishingFunction($config, $session, $basket) {
		return true;
	}


	/**
	 * This method can make something with the created order. For example add the
	 * reference id for payments with creditcards.
	 *
	 * @param int	$orderUid	the uid from the last inserted databaserecord
	 * @param mixed	$session	this came from commerce checkout = MYSESSION
	 */
	public function updateOrder($orderUid, $session) {
		// Write your code here...
	}


	/**
	 * Returns the last error message
	 *
	 * @param int $finish
	 */
	function getLastError($finish = 0) {
		if ($finish) {
			return $this->getReadableError();
		} else {
			return $this->errorMessages[(count($this->errorMessages) - 1)];
		}
	}


	/**
	 * Get all errormessages as concated string
	 *
	 * @return string
	 */
	function getReadableError(){
		$back = '';
		reset($this->errorMessages);
		while (list($k, $v) = each($this->errorMessages)){
			$back .= $v;
		}
		return $back;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/provider/class.tx_commerce_provider_abstract.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/provider/class.tx_commerce_provider_abstract.php"]);
}


?>
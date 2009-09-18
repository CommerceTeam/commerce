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

abstract class tx_commerce_criteria_abstract {

	/*
	 * save a copy of the calling payment object
	 * @var tx_commerce_payment_abstract
	 */
	protected $pObj = null;

	/*
	 * store the options for this criteria
	 * @var array
	 * @see look at the included criterias and in ext_localconf.php
	 */
	protected $options = array();


	/**
	 * The init function of criterias will be only called without an $pObj
	 * if it was called within a criteria itself.
	 *
	 * @param tx_commerce_payment_abstract $pObj
	 * @return void
	 */
	public function init($pObj = null) {
		if (is_object($pObj) && is_a($pObj, 'tx_commerce_payment_abstract')) {
			$this->pObj = $pObj->getPObj();
		}
	}


	/**
	 * Setter for criteria options
	 *
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}


	/**
	 * Abstract function. Every criteria has to implement its own method
	 * The paymentobject or providerobject initialise the criterias and set the configuration
	 * option. Then the paymentpobject iterate over the criterias to see
	 * if there is a condition where the paymentobject is allowed or not.
	 *
	 * @param	void
	 * @return	boolean
	 */
	abstract public function isAllowed();
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_abstract.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_abstract"]);
}


?>
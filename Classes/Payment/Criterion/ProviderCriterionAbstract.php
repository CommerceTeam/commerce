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
 * Abstract payment criterion implementation
 */
abstract class Tx_Commerce_Payment_Criterion_ProviderCriterionAbstract implements Tx_Commerce_Payment_Interface_ProviderCriterion {
	/**
	 * @var Tx_Commerce_Controller_BaseController Parent commerce pibase object
	 */
	protected $pibaseObject = NULL;

	/**
	 * @var Tx_Commerce_Payment_Interface_Payment Parent payment object
	 */
	protected $paymentObject = NULL;

	/**
	 * @var Tx_Commerce_Payment_Interface_Provider Parent provider object
	 */
	protected $providerObject = NULL;

	/**
	 * Options of this criterion
	 *
	 * @var array Option array from ext_localconf
	 */
	protected $options = array();

	/**
	 * Constructor
	 *
	 * @param Tx_Commerce_Payment_Interface_Provider $providerObject Parent payment
	 * @param array $options Configuration array
	 * @return self
	 */
	public function __construct(Tx_Commerce_Payment_Interface_Provider $providerObject, array $options = array()) {
		$this->providerObject = $providerObject;
		$this->paymentObject = $this->providerObject->getPaymentObject();
		$this->pibaseObject = $this->paymentObject->getPObj();
		$this->options = $options;
	}
}

class_alias('Tx_Commerce_Payment_Criterion_ProviderCriterionAbstract', 'tx_commerce_payment_provider_criterion_abstract');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Criterion/ProviderCriterionAbstract.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Criterion/ProviderCriterionAbstract.php']);
}

?>
<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Volker Graubaum <vg@e-netconsulting.com>
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
 * Abstract payment criterion implementation
 */
abstract class tx_commerce_payment_criterion_abstract implements tx_commerce_payment_criterion {

	/**
	 * @var Tx_Commerce_Controller_BaseController Parent commerce pibase object
	 */
	protected $pibaseObject = NULL;

	/**
	 * @var tx_commerce_payment Parent payment object
	 */
	protected $paymentObject = NULL;

	/**
	 * Options of this criterion
	 *
	 * @var array Option array from ext_localconf
	 */
	protected $options = array();

	/**
	 * Constructor
	 *
	 * @param tx_commerce_payment $paymentObject Parent payment object
	 * @param array $options Configuration array
	 * @return self
	 */
	public function __construct(tx_commerce_payment $paymentObject, array $options = array()) {
		$this->paymentObject = $paymentObject;
		$this->pibaseObject = $this->paymentObject->getPObj();
		$this->options = $options;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/criteria/class.tx_commerce_payment_criterion_abstract.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/criteria/class.tx_commerce_payment_criterion_abstract']);
}

?>
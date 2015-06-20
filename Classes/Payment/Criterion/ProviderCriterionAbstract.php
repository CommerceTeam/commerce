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
 * Abstract payment criterion implementation
 *
 * Class Tx_Commerce_Payment_Criterion_ProviderCriterionAbstract
 *
 * @author 2009-2011 Volker Graubaum <vg@e-netconsulting.de>
 */
abstract class Tx_Commerce_Payment_Criterion_ProviderCriterionAbstract
		implements Tx_Commerce_Payment_Interface_ProviderCriterion {
	/**
	 * Parent commerce pibase object
	 *
	 * @var Tx_Commerce_Controller_BaseController
	 */
	protected $pibaseObject = NULL;

	/**
	 * Payment object
	 *
	 * @var Tx_Commerce_Payment_Interface_Payment
	 */
	protected $paymentObject = NULL;

	/**
	 * Provider object
	 *
	 * @var Tx_Commerce_Payment_Interface_Provider
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
	 *
	 * @return self
	 */
	public function __construct(Tx_Commerce_Payment_Interface_Provider $providerObject, array $options = array()) {
		$this->providerObject = $providerObject;
		$this->paymentObject = $this->providerObject->getPaymentObject();
		$this->pibaseObject = $this->paymentObject->getParentObject();
		$this->options = $options;
	}
}

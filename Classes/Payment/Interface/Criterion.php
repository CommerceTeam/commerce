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
 * Payment criterion interface
 *
 * Class Tx_Commerce_Payment_Interface_Criterion
 *
 * @author 2011 Christian Kuhn <lolli@schwarzbu.ch>
 */
interface Tx_Commerce_Payment_Interface_Criterion {
	/**
	 * Constructor
	 *
	 * @param Tx_Commerce_Payment_Interface_Payment $paymentObject Parent payment
	 * @param array $options Configuration array
	 * @return self
	 */
	public function __construct(Tx_Commerce_Payment_Interface_Payment $paymentObject, array $options = array());

	/**
	 * Return TRUE if this payment type is allowed.
	 *
	 * @return boolean
	 */
	public function isAllowed();
}

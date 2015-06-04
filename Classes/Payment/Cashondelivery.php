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
 * Cash on delivery payment implementation
 *
 * Class Tx_Commerce_Payment_Cashondelivery
 *
 * @author 2005-2011 Thomas Hempel <thomas@work.de>
 */
class Tx_Commerce_Payment_Cashondelivery extends Tx_Commerce_Payment_PaymentAbstract {
	/**
	 * Payment type
	 *
	 * @var string
	 */
	protected $type = 'cashondelivery';
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Cashondelivery.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Cashondelivery.php']);
}

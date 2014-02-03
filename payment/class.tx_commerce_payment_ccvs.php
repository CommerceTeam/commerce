<?php

class tx_commerce_payment_Ccvs extends CreditCardValidationSolution {
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/class.tx_commerce_payment_ccvs.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/payment/class.tx_commerce_payment_ccvs.php']);
}

?>
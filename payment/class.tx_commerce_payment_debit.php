<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Thomas Hempel (thomas@work.de)
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
 * @author Thomas Hempel <thomas@work.de>
 * @internal Maintainer Thomas Hempel
 * 
 * $Id: class.tx_commerce_payment_debit.php 483 2007-01-09 17:42:40Z ingo $
 */
 
class tx_commerce_payment_debit {
	/**
	 * The locallang array for this payment module
	 * This is only needed, if individual fields are defined
	 */
	var $LOCAL_LANG = array (
		'default' => array (
			'payment_debit_bic' => 'Bank Identification Number',
			'payment_debit_an' => 'Account number',
			'payment_debit_bn' => 'Bankname',
			'payment_debit_ah' => 'Account holder',
			'payment_debit_company' => 'Company',
		),
		'de' => array (
			'payment_debit_bic' => 'Bankleitzahl',
			'payment_debit_an' => 'Kontonummer',
			'payment_debit_bn' => 'Bankname',
			'payment_debit_ah' => 'Kontoinhaber',
			'payment_debit_company' => 'Firma',
		),
		'fr' => array (
			'payment_debit_bic' => 'Code de banque',
			'payment_debit_an' => 'Numéro de compte',
			'payment_debit_bn' => 'Nom bancaire',
			'payment_debit_ah' => 'Détenteur de compte',
			'payment_debit_company' => 'Firme',
		),
	);

	/// In this var the wrong fields are stored (for future use)
	var $errorFields = array();
	
	/// This var holds the errormessages (keys are the fieldnames)
	var $errorMessages = array();
	
	
	function needAdditionalData($pObj) {
		if(!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}
		return true;
	}

	function getAdditonalFieldsConfig($pObj) {
		if(!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}
		$result = array(
			'debit_bic' => array ('mandatory' => 1),
			'debit_an' => array ('mandatory' => 1),
			'debit_bn' => array ('mandatory' => 1),
			'debit_ah' => array ('mandatory' => 1),
			'debit_company' => array ('mandatory' => 0)
		);
		return $result;
	}
	
	function proofData($formData,$pObj) {
		if(!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}
		return true;
	}
	
	/**
	 * This method is called in the last step. Here can be made some final checks or whatever is
	 * needed to be done before saving some data in the database.
	 * Write any errors into $this->errorMessages!
	 * To save some additonal data in the database use the method updateOrder().
	 *
	 * @param	array	$config: The configuration from the TYPO3_CONF_VARS
	 * @param	array	$basket: The basket object
	 *
	 * @return boolean	True or false
	 */
	function finishingFunction($config, $session, $basket,$pObj) {
		if(!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}
		return true;
	}
	
	/**
	 * This method can make something with the created order. For example add the
	 * reference id for payments with creditcards.
	 */
	function updateOrder($orderUid, $session,$pObj) {
		if(!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_commerce_orders',
			'uid = ' .$orderUid,
			array(
				'payment_debit_bic' => $session['payment']['debit_bic'],
				'payment_debit_an' => $session['payment']['debit_an'],
				'payment_debit_bn' => $session['payment']['debit_bn'],
				'payment_debit_ah' => $session['payment']['debit_ah'],
				'payment_debit_company' => $session['payment']['debit_company'],
			)
		);
	}
	
	/**
	 * Returns the last error message
	 */
	function getLastError($finish = 0,$pObj) {
		if(!is_object($this->pObj)) {
			$this->pObj = $pObj;
		}
		if ($finish) {
			return $this->getReadableError();
		} else {
			return $this->errorMessages[(count($this->errorMessages) -1)];
		}
	}
	
	// creditcard Error Code Handling
	
	function getReadableError(){
		$back = 'es wurde folgender Fehler zurueckgegeben';
		while(list($k, $v) = each($this->errorMessages)) {
			$back .= '<br> '.$k. ' => '.$v;
	    }
		return $back;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_debit.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_debit.php"]);
}

?>
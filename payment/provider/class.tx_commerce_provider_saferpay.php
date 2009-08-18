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

	// library for credit card checks
require_once(PATH_txcommerce .'lib/class.tx_commerce_ccvs_lib.php');
require_once(t3lib_extmgm::extPath('commerce') . 'payment/libs/saferpay/payment/scd/Saferpay.class.php');
require_once(t3lib_extmgm::extPath('commerce') . 'payment/provider/class.tx_commerce_provider_abstract.php');

define('SAFERPAY_ACCOUNTID_PRODUCTION', '');
define('SAFERPAY_ACCOUNTID_DEVELOPMENT', '99867-94913159');
define('SAFERPAY_ACCOUNTID', SAFERPAY_ACCOUNTID_DEVELOPMENT);

class tx_commerce_provider_saferpay extends tx_commerce_provider_abstract {
		// You have to set the type for each new provider to get criterias and
		// configuration for each from ext_locaconf.php
	protected $type = 'saferpay';

		/**
		 * The locallang array for this payment module
		 * This is only needed, if individual fields are defined
		 */
	public $LOCAL_LANG = array ( );

		// In this var the wrong fields are stored (for future use)
	public $errorFields = array();

		// This var holds the errormessages (keys are the fieldnames)
	public $errorMessages = array();

		// Needed for use with 3DS - Payment
	public $ECI = array(
		0 => '1', // SSL gesicherte Internet-Zahlung, keine Haftungsumkehr
		1 => '1', // SSL gesicherte Internet-Zahlung mit 3DS und Haftungsumkehr, Karteninhaber nimmt am Verfahren teil
		2 => '1'  // SSL gesicherte Internet-Zahlung mit 3DS und Haftungsumkehr, Karteninhaber nimmt nicht am Verfahren teil
	);

	public function __construct() {
		$this->saferpay = new Saferpay(
			'/usr/bin/java -jar ' .
			t3lib_extMgm::extPath('commerce') . 'payment/libs/saferpay/bin/Saferpay.jar',
			t3lib_extMgm::extPath('commerce') . 'payment/libs/saferpay/bin/'
		);
	}

	public function getAlternativFormAction($pObj) {
		$url_parameters = array(
			'tx_commerce_pi3[step]' => 'payment',
			'tx_commerce_pi3[saferpay]' => 'success'
		);

		$success_url = 'http://' . $GLOBALS['_SERVER']['SERVER_NAME'] . '/';
		$success_url .= $pObj->cObj->currentPageURL($url_parameters);

		$url_parameters = array(
			'tx_commerce_pi3[step]' => 'payment',
			'tx_commerce_pi3[saferpay]' => 'failed'
		);

		$failed_url = 'http://' . $GLOBALS['_SERVER']['SERVER_NAME'] . '/';
		$failed_url .= $pObj->cObj->currentPageURL($url_parameters);

		$cardrefid = md5('saferpay' . microtime(true) . $_REQUEST['fe_typo_user']);

		$attributes = array(
			'ACCOUNTID' => SAFERPAY_ACCOUNTID,
			'CARDREFID' => $cardrefid,
			'SUCCESSLINK' => $success_url,
			'FAILLINK' => $failed_url
		);

		return $this->saferpay->CreatePayInit($attributes);
	}

	public function getAdditonalFieldsConfig() {
		$result = array(
			'sfpCardNumber.' => array(
				'mandatory' => '0',
				'noPrefix' => 1
			),
			'cc_type.' => array(
				'mandatory' => '0',
				'type' => 'select',
				'values' => array(
					'American Express',
					'Visa',
					'MasterCard'
				)
			),
			'sfpCardExpiryYear.' => array(
				'mandatory' => '0',
				'noPrefix' => 1
			),
			'sfpCardExpiryMonth.' => array(
				'mandatory' => '0',
				'noPrefix' => 1
			),
			'cc_holder.' => array(
				'mandatory' => '0'
			),
			'cc_checksum.' => array(
				'mandatory' => '0'
			)
		);
		return $result;
	}

	public function proofData($formData, $parentResult, $paymentObj) {

			// if formData is empty we know that this is the very first
			// call from tx_commerce_pi3->handlePayment and at this time
			// there can't be formData.
		if (!isset($formData)) {
			return false;
		}

		/* This Block is not needed for GData
			// parentResult is the result of basic credit card validation
			// called in class tx_commerce_payment_creditcard
		if ($parentResult == false) {
			$ccvResult = false;
		}
		*/

		$pObj = $paymentObj->getPObj();

		$DATA = $GLOBALS['_GET']['DATA'];
		$SIGNATURE = $GLOBALS['_GET']['SIGNATURE'];

		if ($pObj->piVars['step'] == 'payment' && $pObj->piVars['saferpay'] == 'failed') {
			$attributes = $this->saferpay->GetAttributes(stripslashes($DATA));
			switch ($attributes['RESULT']) {
				case '7000':
						// Allgemeiner Fehler
					$this->errorMessages[] = $attributes['DESCRIPTION'];
					break;
				case '7001':
						// Anfragen konnte nicht vollstaendig verarbeitet werden
					$this->errorMessages[] = $attributes['DESCRIPTION'];
					break;
				case '7002':
						// Unbekannter Kartentyp
					$this->errorMessages[] = $attributes['DESCRIPTION'];
					break;
				case '7003':
						// Anfrage oder Feldinhalt fehlerhaft
					$this->errorMessages[] = $attributes['DESCRIPTION'];
					break;
				case '7004':
						// Kartenreferenz ID nicht gefunden (nur bei Autorisierung)
					$this->errorMessages[] = $attributes['DESCRIPTION'];
					break;
				case '7005':
						// Fehlender Parameter/Attribut in der Anfrage
					$paymentObj->formError['sfpCardNumber'] = $attributes['DESCRIPTION'];
					$paymentObj->formError['sfpCardExpiryYear'] = $attributes['DESCRIPTION'];
					$paymentObj->formError['sfpCardExpiryMonth'] = $attributes['DESCRIPTION'];
					$this->errorMessages[] = $attributes['DESCRIPTION'];
					break;
				case '7006':
						// Kartenreferenz ID existiert bereits
					$this->errorMessages[] = $attributes['DESCRIPTION'];
					break;
				default:
						// Andere Saferpay Ablehnungscodes
					$this->errorMessages[] = $attributes['DESCRIPTION'];
			}

			return false;
		}

		$verifyPayConfirmResult = $this->saferpay->VerifyPayConfirm(
			stripslashes($DATA),
			stripslashes($SIGNATURE)
		);

		if ($verifyPayConfirmResult == 1) {
			$attributes = $this->saferpay->GetAttributes(stripslashes($DATA));
			if ($attributes['RESULT'] != '0') {
				// Registration failed
				return false;
			}

			// Write the payment data into the session for later usage.
			$sessionkeyToTest = $GLOBALS['TSFE']->fe_user->getKey(
				'ses',
				tx_commerce_div::generateSessionKey('payment')
			);

			if (!is_array($sessionkeyToTest)) {
				$GLOBALS['TSFE']->fe_user->setKey(
					'ses',
					tx_commerce_div::generateSessionKey('payment'),
					array()
				);
			}
			unset($sessionkeyToTest);

			$pObj->piVars['payment'] = array_merge(
				$GLOBALS['TSFE']->fe_user->getKey(
					'ses',
					tx_commerce_div::generateSessionKey('payment')
				),
				(array) $pObj->piVars['payment'],
				$attributes
			);

			$GLOBALS['TSFE']->fe_user->setKey(
				'ses',
				tx_commerce_div::generateSessionKey('payment'),
				$pObj->piVars['payment']
			);
			$GLOBALS['TSFE']->fe_user->storeSessionData();

			return true;
		} else {
			// Verification of response data failed.
			return false;
		}
	}

	public function checkExternalData($globalRequest, $session, $pObj) {
		if (!isset($globalRequest['saferpay3ds']) &&
			(empty($pObj->piVars['terms']) || $pObj->piVars['terms'] != 'termschecked')) {
			return false;
		}

		if (isset($globalRequest['saferpay3ds'])) {
			$DATA = stripslashes($globalRequest['DATA']);
			$SIGNATURE = $globalRequest['SIGNATURE'];
			switch ($globalRequest['saferpay3ds']) {
				case 'success':
					$result = $this->saferpay->payconfirm($DATA, $SIGNATURE);
					if ($result['RESULT'] == 0) {
						$attributes = $this->saferpay->GetAttributes(stripslashes($DATA));
						$pObj->piVars['payment'] = array_merge(
							$GLOBALS['TSFE']->fe_user->getKey(
								'ses',
								tx_commerce_div::generateSessionKey('payment')
							),
							(array) $pObj->piVars['payment'],
							$attributes
						);
						$GLOBALS['TSFE']->fe_user->setKey(
							'ses',
							tx_commerce_div::generateSessionKey('payment'),
							$pObj->piVars['payment']
						);
						$GLOBALS['TSFE']->fe_user->storeSessionData();
						return true;
					}
					break;
				case 'fail':
					break;
				case 'back':
					break;
			}
		}

		$pObj->piVars['payment'] = array_merge(
			$GLOBALS['TSFE']->fe_user->getKey(
				'ses',
				tx_commerce_div::generateSessionKey('payment')
			),
			(array)$pObj->piVars['payment']
		);

		$basket = $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
		$amount = $basket->basket_sum_gross;

			//Check if 3D-Secure is available
		$attributes = array(
			"ACCOUNTID" => $pObj->piVars['payment']['ACCOUNTID'],
			"CARDREFID" => $pObj->piVars['payment']['CARDREFID'],
			"EXP" => $pObj->piVars['payment']['EXPIRYMONTH'] . $pObj->piVars['payment']['EXPIRYYEAR'],
			"AMOUNT" => $amount,
			"CURRENCY" => $pObj->currency
		);

		$result = $this->saferpay->Execute($attributes, 'VerifyEnrollment');

		if ($result['RESULT'] === '301') {
			$this->errorMessages[] = 'ERROR: 3DS is not available. Payment is interrupted for security reasons. Please contact the technical site administrator.';
			return false;
		} elseif ($result['RESULT'] !== '0') {
			$this->errorMessages[] = 'Unknown error: ' . $result['RESULT'] . '<br />Payment is interrupted for security reasons. Please contact the technical site administrator.';
			return false;
		} else {
			if ($this->ECI[$result['ECI']] !== '1') {
				$this->errorMessages[] = 'ERROR: The ECI ' . $result['ECI'] . ' isn\'t allowed for this shop. Please use 3DS if possible';
				return false;
			} else {
				if ($result['ECI'] === '1') {
					$url_to_test = $pObj->pi_getPageLink($GLOBALS['TSFE']->id);
					if (substr(strtolower($url_to_test), 0, 4) == 'http') {
						$self_url = $pObj->pi_getPageLink($GLOBALS['TSFE']->id);
						$self_url .= '?&tx_commerce_pi3[step]=finish';
					} else {
						$self_url = 'http://' . $GLOBALS['_SERVER']['SERVER_NAME'] . '/' ;
						$self_url .= $pObj->pi_getPageLink($GLOBALS['TSFE']->id);
						$self_url .= '?&tx_commerce_pi3[step]=finish';
					}
					$attributes = array(
						"ACCOUNTID" => $pObj->piVars['payment']['ACCOUNTID'],
						"AMOUNT" => $amount,
						"CURRENCY" => $pObj->currency,
						"MPI_SESSIONID" => $result['MPI_SESSIONID'],
						"SUCCESSLINK" => $self_url . '&saferpay3ds=success&tx_commerce_pi3[terms]=termschecked',
						"FAILLINK" => $self_url . '&saferpay3ds=fail',
						"BACKLINK" => $self_url . '&saferpay3ds=back',
						"LANGID" => 'de'
					);
						//Redirect to Saferpay and hope for a comeback.
					$this->link = $this->saferpay->CreatePayInit($attributes);

					header('Location: ' . $this->link);
					exit();
				} else {
					if (!is_array($attributes)) {
						$attributes = array();
					}
					$pObj->piVars['payment'] = array_merge(
						$GLOBALS['TSFE']->fe_user->getKey(
							'ses',
							tx_commerce_div::generateSessionKey('payment')
						),
						$pObj->piVars['payment'],
						$result
					);
					$GLOBALS['TSFE']->fe_user->setKey(
						'ses',
						tx_commerce_div::generateSessionKey('payment'),
						$pObj->piVars['payment']
					);
					$GLOBALS['TSFE']->fe_user->storeSessionData();
					return false;
				}
			}
		}
		return false;
	}


	private function generateRefID() {
		return 'GD'.time();
	}

	/**
	 * This method is called in the last step. Here can be made some final checks or whatever is
	 * needed to be done before saving some data in the database.
	 * Write any errors into $this->errorMessages!
	 * To save some additonal data in the database use the method updateOrder().
	 *
	 * @param	array	$config : The configuration from the TYPO3_CONF_VARS
	 * @param array $session: The actual Session
	 * @param	array	$basket : The basket object
	 *
	 * @return boolean	True or false
	 */
	public function finishingFunction($config, $session, $basket) {
		$this->paymentRefId = $this->generateRefID();

		$amount = $basket->basket_sum_gross;
		$currency = $this->getPObj()->currency;

		// This is needed for GData because $session['payment'] is empty in this case
		$session = array_merge($session, $this->getPObj()->piVars);

		$attributes = array (
			"ACCOUNTID" => $session['payment']['ACCOUNTID'],
			"CARDREFID" => $session['payment']['CARDREFID'],
			"EXP" => $session['payment']['EXPIRYMONTH'] . $session['payment']['EXPIRYYEAR'],
			"AMOUNT" => $amount,
			"CURRENCY" => $currency,
			"ORDERID" => $this->paymentRefId
		);

		$session3DS['payment'] = $GLOBALS['TSFE']->fe_user->getKey(
			'ses',
			tx_commerce_div::generateSessionKey('payment')
		);
		if ($session3DS['payment']['RESULT'] === '0' && $session3DS['payment']['ECI'] === '1') {
			// Karte nimmt am 3DS - Verfahren teil
			$attributes['ECI'] = $session3DS['payment']['ECI'];
			$attributes['XID'] = $session3DS['payment']['XID'];
			$attributes['CAVV'] = $session3DS['payment']['CAVV'];
			$attributes['MPI_SESSIONID'] = $session3DS['payment']['MPI_SESSIONID'];
		}

		$result = $this->saferpay->Execute($attributes, 'Authorization');

		if (is_array($result) && array_key_exists('RESULT', $result) && $result['RESULT'] == '0') {
				//Authorization successfull. Capture the money
			$this->paymentAuthorized = true;
			$captureResult = $this->saferpay->capture($result['ID'], $result['TOKEN']);
			if ($captureResult == '') {
				$this->paymentCaptured = true;
				return true;
			} else {
				$this->errorMessages[] = 'Capturing the money failed although authorization was given. Error is #' . $captureResult;
				return false;
			}
		}

		$this->errorMessages[] = $result['ERROR'] . ' Error #' . $result['AUTHMESSAGE'];
		$this->errorMessages[] = $result['ERROR'];

		return false;
	}

	/**
	 * This method can make something with the created order. For example add the
	 * reference id for payments with creditcards.
	 */
	public function updateOrder($orderUid, $session) {
		/*
			Hier muss die vom checkout erzeugte Order geupdatet werden!
			Bei Kreditkartenzahlung muss eine Referenz ID im Feld payment_ref_id
			gespeichert werden. Genau, das passiert auch schon, und zwar hier.
			Die UID des angelegten order Datensatzes steht in $orderUid! Um die
			Order upzudaten müsste folgendes reichen:
		*/

		$updateData = array(
			'payment_ref_id' => $this->paymentRefId,
		);

		$conf = $this->pObj->getConfiguration();

		if ($this->paymentCaptured === true) {
			// The money is actually transfered
			$updateData['pid'] = $conf['orderPid']['paymentCaptured'];
		} elseif ($this->paymentAuthorized === true) {
			// The money is reserved, however the transfer could not be completed
			$updateData['pid'] = $conf['orderPid']['paymentAuthorized'];
		} else {
			// This order has failed somehow. We should not be here, however we write the paymentRefId to the database, so we can retrace it
			// Do not update the pid, as the payment is not completed
			debug('This order has failed somehow.', __LINE__, __FILE__);
		}

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_orders', 'uid=' . $orderUid, $updateData);
	}

	/**
	 * Returns the last error message
	 */
	public function getLastError($finish = 0) {
		if ($finish) {
			return $this->getReadableError();
		} else {
			return $this->errorMessages[(count($this->errorMessages) -1)];
		}
	}

	/**
	 * creditcard Error Code Handling
	 */
	public function getReadableError(){
		$back = '';
		reset($this->errorMessages);
		while (list($k,$v) = each($this->errorMessages)) {
			$back .= $v;
		}
		return $back;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_provider_saferpay.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_provider_saferpay.php"]);
}

?>
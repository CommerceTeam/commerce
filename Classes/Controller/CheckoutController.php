<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2012 Ingo Schmitt <is@marketing-factory.de>
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
 * Plugin 'checkout' for the 'commerce' extension.
 * This plugin handles everything concerning the checkout. It gets his
 * configuration completely from TypoScript. Every step is a collection
 * of single modules. Each module is represented by a class that
 * provides several methods for displaying forms, checking data and
 * storing data.
 */
class Tx_Commerce_Controller_CheckoutController extends Tx_Commerce_Controller_BaseController {
	/**
	 * Same as class name
	 *
	 * @var string
	 */
	public $prefixId = 'tx_commerce_pi3';

	/**
	 * @var array
	 */
	public $dbFieldData = array();

	/**
	 * @var array
	 */
	public $formError = array();

	/**
	 * Holding the Static_info object
	 *
	 * @var tx_staticinfotables_pi1
	 */
	public $staticInfo;

	/**
	 * @var string
	 */
	public $currentStep = '';

	/**
	 * If set to TRUE some debug message will be printed.
	 *
	 * @var boolean
	 */
	public $debug = FALSE;

	/**
	 * TRUE if checkoutmail to user sent correctly
	 *
	 * @var boolean
	 */
	public $userMailOK;

	/**
	 * TRUE if checkoutmail to Admin send correctly
	 *
	 * @var boolean
	 */
	public $adminMailOK;

	/**
	 * You have to implement FALSE by your own
	 *
	 * @var boolean TRUE if finish IT is ok
	 */
	public $finishItOK = TRUE;

	/**
	 * Array of checkout steps
	 *
	 * @var array
	 */
	public $checkoutSteps = array();

	/**
	 * Array of the extConf
	 *
	 * @var array
	 */
	public $extConf = array();

	/**
	 * String to clear session after checkout
	 *
	 * @var array
	 */
	public $clearSessionAfterCheckout = TRUE;

	/**
	 * @var array
	 */
	public $sessionData = array();

	/**
	 * @var integer
	 */
	public $orderUid = 0;

	/**
	 * @var array
	 */
	public $userData = array();

	/**
	 * @var string
	 */
	public $step;

	/**
	 * @var boolean
	 */
	public $isHTMLMail;

	/**
	 * Init Method, autmatically called $this->main
	 *
	 * @param string $conf Configuration
	 * @return void
	 */
	public function init($conf) {
		parent::init($conf);

		$this->conf['basketPid'] = $GLOBALS['TSFE']->id;

		$this->staticInfo = t3lib_div::makeInstance('tx_staticinfotables_pi1');
		$this->staticInfo->init();

		$this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];

		/** @var $basket Tx_Commerce_Domain_Model_Basket */
		$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
		$basket->setTaxCalculationMethod($this->conf['priceFromNet']);

		if ($this->conf['currency'] <> '') {
			$this->currency = $this->conf['currency'];
		}

		$this->checkoutSteps[0] = 'billing';
		$this->checkoutSteps[1] = 'delivery';
		$this->checkoutSteps[2] = 'payment';
		$this->checkoutSteps[3] = 'listing';
		$this->checkoutSteps[4] = 'finish';

		$hookObjectsArr = $this->getHookObjectArray('init');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'CheckoutSteps')) {
				$hookObj->CheckoutSteps($this->checkoutSteps, $this);
			}
		}
	}

	/**
	 * Main Method, automatically called by TYPO3
	 *
	 * @param string $content from Parent Page
	 * @param array $conf Configuration
	 * @return string HTML-Content
	 */
	public function main($content, $conf) {
		$this->debug(
			$GLOBALS['TSFE']->fe_user->getKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('billing')),
			'billingsession',
			__FILE__ . ' ' . __LINE__
		);

		$this->init($conf);

		$this->debug($this->piVars, 'piVars', __FILE__ . ' ' . __LINE__);

		$hookObjectsArr = $this->getHookObjectArray('main');

			// Set basket to readonly, if set in extension configuration
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['lockBasket'] == 1) {
			/** @var $basket Tx_Commerce_Domain_Model_Basket */
			$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
			$basket->setReadOnly();
			$basket->storeData();
		}

			// Store current step
		$this->currentStep = strtolower($this->piVars['step']);

			// Set deliverytype as current step, if comes from pi4 to create a new address
		if (empty($this->currentStep) && $this->piVars['addressType']) {
			switch ($this->piVars['addressType']) {
				case '2':
					$this->currentStep = 'delivery';
				break;
				default:
				break;
			}
		}

			// Hook for handling own steps and information
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'processData')) {
				$hookObj->processData($this);
			}
		}

		$this->storeRequestDataIntoSession();
		$this->fetchSessionDataIntoSessionAttribute();
		$this->storeSessionData();

		$canMakeCheckout = $this->canMakeCheckout();
		if (is_string($canMakeCheckout)) {
			return $this->cObj->cObjGetSingle(
				$this->conf['cantMakeCheckout.'][$canMakeCheckout],
				$this->conf['cantMakeCheckout.'][$canMakeCheckout . '.']
			);
		}

			// Get the template
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

		$this->debug($this->currentStep, '$this->currentSteps', __FILE__ . ' ' . __LINE__);

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'preSwitch')) {
				$hookObj->preSwitch($this->currentStep, $this);
			}
		}

			// The purpose of the while loop is simply to be able to define any
			// step as the step after payment. This counter breaks the loop after 10
			// rounds to prevent infinite loops with poorly setup shops
		if (!$this->validateAddress('billing')) {
			$this->currentStep = 'billing';
		}

		$content = FALSE;
		$finiteloop = 0;
		while ($content === FALSE && $finiteloop < 10) {
			switch ($this->currentStep) {
				case 'delivery':
						// Get delivery address
					$content = $this->getDeliveryAddress();
				break;

				case 'payment':
					$paymentObj = $this->getPaymentObject();
					$content = $this->handlePayment($paymentObj);
						// Only break at this point if we need some payment handling
					if ($content != FALSE) {
						break;
					}
						// Go on with listing
					$this->currentStep = $this->getStepAfter('payment');
				break;

				case 'listing':
					$content = $this->getListing();
				break;

				case 'finish':
					$paymentObj = $this->getPaymentObject();
					$content = $this->finishIt($paymentObj);
				break;

				case 'billing':
					$content = $this->getBillingAddress();
				break;

				default:
					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, $this->currentStep)) {
							$content = $hookObj->{$this->currentStep}($this);
						}
					}
					if (!$content) {
							// get billing address
						$content = $this->getBillingAddress();
					}
				break;
			}
			$finiteloop++;
		}

		if ($content === FALSE) {
			$content = 'Been redirected internally ' . $finiteloop . ' times, this suggest a configuration error';
		}

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postSwitch')) {
				$content = $hookObj->postSwitch($this->currentStep, $content, $this);
			}
		}

		/** @var $feUser tslib_feUserAuth */
		$feUser = & $GLOBALS['TSFE']->fe_user;
		$feUser->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('currentStep'), $this->currentStep);

		$content = $this->renderSteps($content);

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postRender')) {
				$content = $hookObj->postRender($this->currentStep, $content, $this);
			}
		}

		return $this->pi_WrapInBaseClass($content);
	}

	/**
	 * @return void
	 */
	protected function storeRequestDataIntoSession() {
		/** @var $feUser tslib_feUserAuth */
		$feUser = $GLOBALS['TSFE']->fe_user;
			// Write the billing address into session, if it is present in the REQUEST
		if (isset($this->piVars['billing'])) {
			$this->piVars['billing'] = Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($this->piVars['billing']);
			$feUser->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('billing'), $this->piVars['billing']);
		}
		if (isset($this->piVars['delivery'])) {
			$this->piVars['delivery'] = Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($this->piVars['delivery']);
			$feUser->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('delivery'), $this->piVars['delivery']);
		}
		if (isset($this->piVars['payment'])) {
			$this->piVars['payment'] = Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($this->piVars['payment']);
			$feUser->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('payment'), $this->piVars['payment']);
		}

			// Fetch the address data from hidden fields if address_id is set.
			// This means that the address was selected from list with radio buttons.
		if (isset($this->piVars['address_uid'])) {
				// Override missing or incorrect email with username if username is email,
				// because we need to be sure to have at least one correct mail address
				// This way email is not necessarily mandatory for billing/delivery address
			if (!$this->conf['randomUser'] && !t3lib_div::validEmail($this->piVars[$this->piVars['address_uid']]['email'])) {
				$this->piVars[$this->piVars['address_uid']]['email'] = $GLOBALS['TSFE']->fe_user->user['email'];
			}
			$this->piVars[$this->piVars['address_uid']]['uid'] = (int) $this->piVars['address_uid'];
			$feUser->setKey(
				'ses',
				Tx_Commerce_Utility_GeneralUtility::generateSessionKey($this->piVars['check']),
				$this->piVars[(int) $this->piVars['address_uid']]
			);
		}
	}

	/**
	 * @return void
	 */
	protected function fetchSessionDataIntoSessionAttribute() {
		/** @var $feUser tslib_feUserAuth */
		$feUser = & $GLOBALS['TSFE']->fe_user;
		$this->sessionData['billing'] = Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray(
			$feUser->getKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('billing'))
		);
		$this->sessionData['delivery'] = Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray(
			$feUser->getKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('delivery'))
		);
		$this->sessionData['payment'] = Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray(
			$feUser->getKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('payment'))
		);
		$this->sessionData['mails'] = $feUser->getKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('mails'));

		if ($this->piVars['check'] == 'billing' && $this->piVars['step'] == 'payment') {
				// Remove reference to delivery address
			$this->sessionData['delivery'] = FALSE;
			$feUser->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('delivery'), FALSE);
		}
	}

	/**
	 * @return void
	 */
	public function storeSessionData() {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		/** @var $feUser tslib_feUserAuth */
		$feUser = & $GLOBALS['TSFE']->fe_user;
			// Saves UC and SesData if changed.
		if ($feUser->userData_change) {
			$feUser->writeUC('');
		}

		if ($feUser->sesData_change && $feUser->id) {
			if (empty($feUser->sesData)) {
					// Remove session-data
				$feUser->removeSessionData();
			} else {
					// Write new session-data
				$insertFields = array(
					'hash' => $GLOBALS['TSFE']->fe_user->id,
					'content' => serialize($feUser->sesData),
					'tstamp' => $GLOBALS['EXEC_TIME'],
				);
				$feUser->removeSessionData();
				$database->exec_INSERTquery('fe_session_data', $insertFields);
			}
		}
	}


	/**
	 * This method renders the step layout into the checkout process
	 * It replaces the subpart ###CHECKOUT_STEPS###
	 *
	 * @param string $content Content
	 * @return string $content
	 */
	public function renderSteps($content) {
		$myTemplate = $this->cObj->getSubpart($this->templateCode, '###CHECKOUT_STEPS_BAR###');
		$activeTemplate = $this->cObj->getSubpart($myTemplate, '###CHECKOUT_ONE_STEP_ACTIVE###');
		$actualTemplate = $this->cObj->getSubpart($myTemplate, '###CHECKOUT_ONE_STEP_ACTUAL###');
		$inactiveTemplate = $this->cObj->getSubpart($myTemplate, '###CHECKOUT_ONE_STEP_INACTIVE###');

		$stepsToNumbers = array_flip($this->checkoutSteps);
		$currentStepNumber = $stepsToNumbers[$this->currentStep];

		$activeContent = '';
		$inactiveContent = '';
		for ($i = 0; $i < $currentStepNumber; $i++) {
			$localTs = $this->conf['activeStep.'];
			if ($localTs['typolink.']['setCommerceValues'] == 1) {
				$localTs['typolink.']['parameter'] = $this->conf['basketPid'];
				$localTs['typolink.']['additionalParams'] = $this->argSeparator . $this->prefixId .
					'[step]=' . $this->checkoutSteps[$i];
			}
			$label = sprintf($this->pi_getLL('label_step_' . $this->checkoutSteps[$i]), $i + 1);
			$lokContent = $this->cObj->stdWrap($label, $localTs);
			$activeContent .= $this->cObj->substituteMarker($activeTemplate, '###LINKTOSTEP###', $lokContent);
		}

		$label = sprintf($this->pi_getLL('label_step_' . $this->checkoutSteps[$i]), $i + 1);
		$lokContent = $this->cObj->stdWrap($label, $this->conf['actualStep.']);
		$actualContent = $this->cObj->substituteMarker($actualTemplate, '###STEPNAME###', $lokContent);

		$stepCount = count($this->checkoutSteps);
		for ($i = ($currentStepNumber + 1); $i < $stepCount; $i++) {
			$label = sprintf($this->pi_getLL('label_step_' . $this->checkoutSteps[$i]), $i + 1);
			$lokContent = $this->cObj->stdWrap($label, $this->conf['inactiveStep.']);
			$inactiveContent .= $this->cObj->substituteMarker($inactiveTemplate, '###STEPNAME###', $lokContent);
		}

		$myTemplate = $this->cObj->substituteSubpart($myTemplate, '###CHECKOUT_ONE_STEP_ACTIVE###', $activeContent);
		$myTemplate = $this->cObj->substituteSubpart($myTemplate, '###CHECKOUT_ONE_STEP_INACTIVE###', $inactiveContent);
		$myTemplate = $this->cObj->substituteSubpart($myTemplate, '###CHECKOUT_ONE_STEP_ACTUAL###', $actualContent);
		$content = $this->cObj->substituteMarker($content, '###CHECKOUT_STEPS###', $myTemplate);

		return $content;
	}

	/** STEP ROUTINES **/

	/**
	 * Creates a form for collection the billing address data.
	 *
	 * @param integer $withTitle
	 * @return string $content
	 */
	public function getBillingAddress($withTitle = 1) {
		$this->debug($this->sessionData, 'sessionData', __FILE__ . ' ' . __LINE__);
		if ($this->conf['billing.']['subpartMarker.']['containerWrap']) {
			$template = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf['billing.']['subpartMarker.']['containerWrap'])
			);
		} else {
			$template = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_CONTAINER###');
		}

		$markerArray['###ADDRESS_TITLE###'] = '';
		$markerArray['###ADDRESS_DESCRIPTION###'] = '';
		if ($withTitle == 1) {
				// Fill standard markers
			$markerArray['###ADDRESS_TITLE###'] = $this->pi_getLL('billing_title');
			$markerArray['###ADDRESS_DESCRIPTION###'] = $this->pi_getLL('billing_description');
		}

			// Get the form
		$markerArray['###ADDRESS_FORM_TAG###'] = '<form name="addressForm" action="' .
			$this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="post" ' . $this->conf[$this->step . '.']['formParams'] . '>';
		$markerArray['###ADDRESS_FORM_HIDDENFIELDS###'] = '<input type="hidden" name="' .
			$this->prefixId . '[check]" value="billing" />';

		$billingForm = '<form name="addressForm" action="' . $this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="post">';
		$billingForm .= '<input type="hidden" name="' . $this->prefixId . '[check]" value="billing" />';

		$markerArray['###HIDDEN_STEP###'] = '<input type="hidden" name="' . $this->prefixId . '[check]" value="billing" />';

			// If a user is logged in, get the form from the address management
		if ($GLOBALS['TSFE']->loginUser) {
			$addressManagerConf = $this->conf;
			$addressManagerConf['formFields.'] = $this->conf['billing.']['sourceFields.'];
			$addressManagerConf['addressPid'] = $this->conf['addressPid'];

			/** @var Tx_Commerce_Controller_AddressesController $addressMgm */
			$addressMgm = t3lib_div::makeInstance('Tx_Commerce_Controller_AddressesController');
			$addressMgm->cObj = $this->cObj;
			$addressMgm->templateCode = $this->templateCode;
			$addressMgm->init($addressManagerConf, FALSE);
			$addressMgm->addresses = $addressMgm->getAddresses(
				$GLOBALS['TSFE']->fe_user->user['uid'],
				$this->conf['billing.']['addressType']
			);
			$addressMgm->piVars['backpid'] = $GLOBALS['TSFE']->id;

			$markerArray['###ADDRESS_FORM_INPUTFIELDS###'] = $addressMgm->getListing(
				$this->conf['billing.']['addressType'], TRUE, $this->prefixId, $this->sessionData['billing']['uid']);
		} else {
			$markerArray['###ADDRESS_FORM_INPUTFIELDS###'] = $this->getInputForm($this->conf['billing.'], 'billing');
		}

		$billingForm .= $markerArray['###ADDRESS_FORM_INPUTFIELDS###'];

			// Marker for the delivery address chooser
		$stepNodelivery = $this->getStepAfter('delivery');

			// Build pre selcted Radio Boxes
		if ($this->piVars['step'] == $stepNodelivery) {
			$deliveryChecked = '  ';
			$paymentChecked = ' checked="checked" ';
		} elseif ($this->piVars['step'] == 'delivery') {
			$deliveryChecked = ' checked="checked" ';
			$paymentChecked = '  ';
		} elseif ($this->conf['paymentIsDeliveryAdressDefault'] == 1) {
			$deliveryChecked = '  ';
			$paymentChecked = ' checked="checked" ';
		} elseif ($this->conf['deliveryAdressIsSeparateDefault'] == 1) {
			$deliveryChecked = ' checked="checked" ';
			$paymentChecked = '  ';
		} else {
			$deliveryChecked = '  ';
			$paymentChecked = '  ';
		}

		$this->debug($this->sessionData, 'sessionData', __FILE__ . ' ' . __LINE__);
		if (is_array($this->sessionData['delivery']) && count($this->sessionData['delivery']) > 0) {
			$deliveryChecked = ' checked="checked" ';
			$paymentChecked = '  ';
		}

		$markerArray['###ADDRESS_RADIOFORM_DELIVERY###'] = $this->cObj->stdWrap(
			'<input type="radio" id="delivery" name="' . $this->prefixId . '[step]" value="delivery" ' . $deliveryChecked . '/>',
			$this->conf['billing.']['deliveryAddress.']['delivery_radio.']
		);
		$markerArray['###ADDRESS_RADIOFORM_NODELIVERY###'] = $this->cObj->stdWrap(
			'<input type="radio" id="nodelivery"  name="' . $this->prefixId . '[step]" value="' . $stepNodelivery . '" ' . $paymentChecked . '/>',
			$this->conf['billing.']['deliveryAddress.']['nodelivery_radio.']
		);
		$markerArray['###ADDRESS_LABEL_DELIVERY###'] = $this->cObj->stdWrap(
			'<label for="delivery">' . $this->pi_getLL('billing_deliveryaddress') . '</label>',
			$this->conf['billing.']['deliveryAddress.']['delivery_label.']
		);
		$markerArray['###ADDRESS_LABEL_NODELIVERY###'] = $this->cObj->stdWrap(
			'<label for="nodelivery">' . $this->pi_getLL('billing_nodeliveryaddress') . '</label>',
			$this->conf['billing.']['deliveryAddress.']['nodelivery_label.']
		);

			// @Deprecated marker, use marker above instead (see example Template)
		$markerArray['###ADDRESS_FORM_FIELDS###'] = $billingForm;
		$markerArray['###ADDRESS_FORM_SUBMIT###'] = '<input type="submit" value="' . $this->pi_getLL('billing_submit') . '" />';

			// We are thrown back because address data is not valid
		if (($this->currentStep == 'billing' || $this->currentStep == 'delivery') && !$this->validateAddress('billing')) {
			$markerArray['###ADDRESS_MANDATORY_MESSAGE###'] = $this->cObj->stdWrap(
				$this->pi_getLL('label_loginUser_mandatory_message', 'data incorrect'),
				$this->conf['billing.']['errorWrap.']
			);
		} else {
			$markerArray['###ADDRESS_MANDATORY_MESSAGE###'] = '';
		}

		$markerArray['###ADDRESS_DISCLAIMER###'] = sprintf(
			$this->pi_getLL('general_disclaimer'),
			$this->cObj->typoLink($this->pi_getLL('privacy_agreement'), $this->conf['privacyAgreementUrl.'])
		);

		$markerArray = $this->addFormMarker($markerArray, '###|###');

		$hookObjectsArr = $this->getHookObjectArray('getBillingAddress');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray = $hookObj->ProcessMarker($markerArray, $this);
			}
		}

		$this->currentStep = 'billing';

		return $this->cObj->substituteMarkerArray($this->cObj->substituteMarkerArray($template, $markerArray), $this->languageMarker);
	}

	/**
	 * Creates a form for collection the delivery address data.
	 *
	 * @param int $withTitle
	 * @return string $content
	 */
	public function getDeliveryAddress($withTitle = 1) {
		$this->debug($this->sessionData, 'sessionData', __FILE__ . ' ' . __LINE__);

		if (!$this->validateAddress('billing')) {
			return $this->getBillingAddress();
		}
		$this->validateAddress('delivery');

		if ($this->conf['delivery.']['subpartMarker.']['containerWrap']) {
			$template = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf['delivery.']['subpartMarker.']['containerWrap'])
			);
		} else {
			$template = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_CONTAINER###');
		}

		$markerArray['###ADDRESS_TITLE###'] = '';
		$markerArray['###ADDRESS_DESCRIPTION###'] = '';
		if ($withTitle == 1) {
				// Fill standard markers
			$markerArray['###ADDRESS_TITLE###'] = $this->pi_getLL('delivery_title');
			$markerArray['###ADDRESS_DESCRIPTION###'] = $this->pi_getLL('delivery_description');
		}

			// Get form
			// @Depricated Marker
		$markerArray['###ADDRESS_FORM_TAG###'] = '<form name="addressForm" action="' .
			$this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="post" ' . $this->conf[$this->step . '.']['formParams'] . '>';

		$nextstep = $this->getStepAfter('delivery');

		$markerArray['###ADDRESS_FORM_HIDDENFIELDS###'] = '<input type="hidden" name="' .
			$this->prefixId . '[step]" value="' . $nextstep . '" /><input type="hidden" name="' .
			$this->prefixId . '[check]" value="delivery" />';

		$deliveryForm = '<form name="addressForm" action="' . $this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="post">';
		$deliveryForm .= '<input type="hidden" name="' . $this->prefixId . '[step]" value="' . $nextstep . '" />';
		$deliveryForm .= '<input type="hidden" name="' . $this->prefixId . '[check]" value="delivery" />';

		$markerArray['###HIDDEN_STEP###'] = '<input type="hidden" name="' . $this->prefixId . '[step]" value="' . $nextstep . '" />';
		$markerArray['###HIDDEN_STEP###'] .= '<input type="hidden" name="' . $this->prefixId . '[check]" value="delivery" />';

			// If a user is logged in, get form from the address management
		if ($GLOBALS['TSFE']->loginUser) {
			$addressManagerConf = $this->conf;
			$addressManagerConf['formFields.'] = $this->conf['delivery.']['sourceFields.'];
			$addressManagerConf['addressPid'] = $this->conf['addressPid'];

			/** @var Tx_Commerce_Controller_AddressesController $addressMgm */
			$addressMgm = t3lib_div::makeInstance('Tx_Commerce_Controller_AddressesController');
			$addressMgm->cObj = $this->cObj;
			$addressMgm->templateCode = $this->templateCode;
			$addressMgm->init($addressManagerConf, FALSE);
			$addressMgm->addresses = $addressMgm->getAddresses(
				$GLOBALS['TSFE']->fe_user->user['uid'],
				$this->conf['delivery.']['addressType']
			);
			$addressMgm->piVars['backpid'] = $GLOBALS['TSFE']->id;

			$markerArray['###ADDRESS_FORM_INPUTFIELDS###'] = $addressMgm->getListing(
				$this->conf['delivery.']['addressType'], TRUE, $this->prefixId, $this->sessionData['delivery']['uid']
			);
		} else {
			$markerArray['###ADDRESS_FORM_INPUTFIELDS###'] = $this->getInputForm($this->conf['delivery.'], 'delivery');
		}

		$deliveryForm .= $markerArray['###ADDRESS_FORM_INPUTFIELDS###'];

		$markerArray['###ADDRESS_RADIOFORM_DELIVERY###'] = '';
		$markerArray['###ADDRESS_RADIOFORM_NODELIVERY###'] = '';
		$markerArray['###ADDRESS_LABEL_DELIVERY###'] = '';
		$markerArray['###ADDRESS_LABEL_NODELIVERY###'] = '';

			// @Depricated marker, use new template
		$markerArray['###ADDRESS_FORM_FIELDS###'] = $deliveryForm;
		$markerArray['###ADDRESS_FORM_SUBMIT###'] = '<input type="submit" value="' . $this->pi_getLL('delivery_submit') . '" />';

			// We are thrown back because address data is not valid
		if ($this->currentStep == 'payment' && !$this->validateAddress('delivery')) {
			$markerArray['###ADDRESS_MANDATORY_MESSAGE###'] = $this->cObj->stdWrap(
				$this->pi_getLL('label_loginUser_mandatory_message', 'data incorrect'),
				$this->conf['delivery.']['errorWrap.']
			);
		} else {
			$markerArray['###ADDRESS_MANDATORY_MESSAGE###'] = '';
		}

		$markerArray['###ADDRESS_DISCLAIMER###'] = sprintf(
			$this->pi_getLL('general_disclaimer'),
			$this->cObj->typoLink($this->pi_getLL('privacy_agreement'), $this->conf['privacyAgreementUrl.'])
		);

		$markerArray = $this->addFormMarker($markerArray, '###|###');

		$hookObjectsArr = $this->getHookObjectArray('getDeliveryAddress');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray = $hookObj->ProcessMarker($markerArray, $this);
			}
		}

		$this->currentStep = 'delivery';

		return $this->cObj->substituteMarkerArray($this->cObj->substituteMarkerArray($template, $markerArray), $this->languageMarker);
	}

	/**
	 * Handles all the stuff concerning the payment.
	 *
	 * @param Tx_Commerce_Payment_Interface_Payment $paymentObj The payment object
	 * @return string Substituted template
	 */
	public function handlePayment($paymentObj = NULL) {
		$hookObjectsArr = $this->getHookObjectArray('handlePayment');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'alternativePaymentStep')) {
				return $hookObj->alternativePaymentStep($paymentObj, $this);
			}
		}

		if (!$this->validateAddress('delivery')) {
			return $this->getDeliveryAddress();
		}
		if (!$this->validateAddress('billing')) {
			return $this->getBillingAddress();
		}

		$sysConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['SYSPRODUCTS']['PAYMENT'];

		$paymentType = $this->getPaymentType();

		if ($this->conf[$paymentType . '.']['subpartMarker.']['listWrap']) {
			$template = $this->cObj->getSubpart(
				$this->templateCode,
				strtoupper($this->conf[$paymentType . '.']['subpartMarker.']['listWrap'])
			);
		} else {
			$template = $this->cObj->getSubpart($this->templateCode, '###PAYMENT###');
		}

			// Fill standard markers
		$markerArray['###PAYMENT_TITLE###'] = $this->pi_getLL('payment_title');
		$markerArray['###PAYMENT_DESCRIPTION###'] = $this->pi_getLL('payment_description');
		$markerArray['###PAYMENT_DISCLAIMER###'] = $this->pi_getLL('general_disclaimer') . '<br />' .
			$this->pi_getLL('payment_disclaimer');

		$config = $sysConfig['types'][strtolower((string) $paymentType)];

			// Check if we already have a payment object
			// If we don't have one, try to create a new one from the config
		if (!isset($paymentObj)) {
			$errorStr = NULL;
			if (!isset($config['class'])) {
				$errorStr[] = 'class not set!';
			}
			if (!file_exists($config['path'])) {
				$errorStr[] = 'file not found!';
			}
			if (is_array($errorStr)) {
				die('PAYMENT:FATAL! No payment possible because I don\'t know how to handle it! (' . implode(', ', $errorStr) . ')');
			}

			$path = $GLOBALS['TSFE']->tmpl->getFileName($config['path']);
			/** @noinspection PhpIncludeInspection */
			require_once($path);

			$paymentObj = t3lib_div::makeInstance($config['class']);
		}

		/**
		 * Check if data needed by the payment provider needs to be inserted and
		 * payment information are stored in the session is invalid or
		 * information in session result in an error
		 */
		if (
			$paymentObj->needAdditionalData($this)
			&& (
				(isset($this->sessionData['payment']) && !$paymentObj->proofData($this->sessionData['payment']))
				|| (!isset($this->sessionData['payment']) || $paymentObj->getLastError())
			)
		) {
				// Merge local lang array with language information of payment object
			if (is_array($this->LOCAL_LANG) && isset($paymentObj->LOCAL_LANG)) {
				foreach ($this->LOCAL_LANG as $llKey => $llData) {
					$newLlData = array();
					if (isset($paymentObj->LOCAL_LANG[$llKey]) && is_array($paymentObj->LOCAL_LANG[$llKey])) {
						$newLlData = array_merge($llData, $paymentObj->LOCAL_LANG[$llKey]);
					}
					$this->LOCAL_LANG[$llKey] = $newLlData;
				}
			}

			$formAction = $this->pi_getPageLink($GLOBALS['TSFE']->id);
			if (method_exists($paymentObj, 'getProvider')) {
				/** @var $paymentProvider Tx_Commerce_Payment_Provider_ProviderAbstract */
				$paymentProvider = $paymentObj->getProvider();
				if (method_exists($paymentProvider, 'getAlternativFormAction')) {
					$formAction = $paymentProvider->getAlternativFormAction($this);
				}
			}

			$this->formError = $paymentObj->formError;

				// Show the payment form if it's needed, otherwise go to next step
			$paymentForm = '<form name="paymentForm" action="' . $formAction . '" method="post">';
			$paymentForm .= '<input type="hidden" name="' . $this->prefixId . '[step]" value="payment" />';
			$paymentConfig = $this->conf['payment.'];
			$paymentConfig['sourceFields.'] = $paymentObj->getAdditionalFieldsConfig();
			$paymentForm .= $this->getInputForm($paymentConfig, 'payment', TRUE);
			$paymentErr = $paymentObj->getLastError();

			$markerArray['###PAYMENT_PAYMENTOBJ_MESSAGE###'] = $this->pi_getLL($paymentErr);
			if ($markerArray['###PAYMENT_PAYMENTOBJ_MESSAGE###'] == '' AND $paymentErr != '') {
				$markerArray['###PAYMENT_PAYMENTOBJ_MESSAGE###'] = $this->pi_getLL('defaultPaymentDataError');
			}
			$markerArray['###PAYMENT_FORM_FIELDS###'] = $paymentForm;
			$markerArray['###PAYMENT_FORM_SUBMIT###'] = '<input type="submit" value="' . $this->pi_getLL('payment_submit') . '" /></form>';
		} else {
				// Redirect to the next page because no additional payment
				// information is needed or everything is correct
			return FALSE;
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray = $hookObj->ProcessMarker($markerArray, $this);
			}
		}

		$this->currentStep = 'payment';

		return $this->cObj->substituteMarkerArray($this->cObj->substituteMarkerArray($template, $markerArray), $this->languageMarker);
	}

	/**
	 * Method to list the content of the basket including all articles, sums and addresses.
	 *
	 * @param string $template Template for rendering
	 * @return string Substituted template
	 */
	public function getListing($template = '') {
		if (!$template) {
			$template = $this->cObj->getSubpart($this->templateCode, '###LISTING###');
		}

		/** @var $basket Tx_Commerce_Domain_Model_Basket */
		$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
		$this->debug($basket, '$basket', __FILE__ . ' ' . __LINE__);

		$listingForm = '<form name="listingForm" action="' . $this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="post">';

		$nextStep = $this->getStepAfter($this->currentStep);

		$listingForm .= '<input type="hidden" name="' . $this->prefixId . '[step]" value="' . $nextStep . '" />';

		$markerArray['###HIDDEN_STEP###'] = '<input type="hidden" name="' . $this->prefixId . '[step]" value="' . $nextStep . '" />';
		$markerArray['###LISTING_TITLE###'] = $this->pi_getLL('listing_title');
		$markerArray['###LISTING_DESCRIPTION###'] = $this->pi_getLL('listing_description');
		$markerArray['###LISTING_FORM_FIELDS###'] = $listingForm;
		$markerArray['###LISTING_BASKET###'] = $this->makeBasketView(
			$basket,
			'###BASKET_VIEW###',
			t3lib_div::intExplode(',', $this->conf['regularArticleTypes']),
			array(
				'###LISTING_ARTICLE###',
				'###LISTING_ARTICLE2###'
			)
		);
		$markerArray['###BILLING_ADDRESS###'] = $this->cObj->stdWrap(
			$this->getAddress('billing'),
			$this->conf['listing.']['stdWrap_billing_address.']
		);
		$markerArray['###DELIVERY_ADDRESS###'] = $this->cObj->stdWrap(
			$this->getAddress('delivery'),
			$this->conf['listing.']['stdWrap_delivery_address.']
		);
		$markerArray['###LISTING_FORM_SUBMIT###'] = '<input type="submit" value="' . $this->pi_getLL('listing_submit') . '" />';
		$markerArray['###LISTING_DISCLAIMER###'] = $this->pi_getLL('listing_disclaimer');

		if ($this->formError['terms']) {
			$markerArray['###ERROR_TERMS_ACCEPT###'] = $this->cObj->dataWrap(
				$this->formError['terms'],
				$this->conf['terms.']['errorWrap']
			);
		} else {
			$markerArray['###ERROR_TERMS_ACCEPT###'] = '';
		}
		$termsChecked = '';
		if ($this->conf['terms.']['checkedDefault']) {
			$termsChecked = 'checked';
		}

		$comment = isset($this->piVars['comment']) ? t3lib_div::removeXSS(strip_tags($this->piVars['comment'])) : '';

			// Use new version with label and field
		$markerArray['###LISTING_TERMS_ACCEPT_LABEL###'] = sprintf(
			$this->pi_getLL('termstext'),
			$this->cObj->typoLink($this->pi_getLL('termstext_tca'), $this->conf['termsAndConditionsUrl.'])
		);
		$markerArray['###LISTING_COMMENT_LABEL###'] = $this->pi_getLL('comment');
		$markerArray['###LISTING_TERMS_ACCEPT_FIELD###'] = '<input type="checkbox" name="' .
			$this->prefixId . '[terms]" value="termschecked" ' . $termsChecked . ' />';
		$markerArray['###LISTING_COMMENT_FIELD###'] = '<textarea name="' .
			$this->prefixId . '[comment]" rows="4" cols="40">' . $comment . '</textarea>';

		$hookObjectsArr = $this->getHookObjectArray('getListing');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray = $hookObj->ProcessMarker($markerArray, $this);
			}
		}

		$markerArray = $this->addFormMarker($markerArray, '###|###');

		$this->currentStep = 'listing';

		return $this->cObj->substituteMarkerArray($this->cObj->substituteMarkerArray($template, $markerArray), $this->languageMarker);
	}

	/**
	 * Finishing Page from Checkout
	 *
	 * @param Tx_Commerce_Payment_Interface_Payment|null $paymentObj The payment object
	 * @return string HTML-Content
	 */
	public function finishIt($paymentObj = NULL) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$sysConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['SYSPRODUCTS']['PAYMENT'];

		$paymentType = $this->getPaymentType();

		$config = $sysConfig['types'][strtolower((string) $paymentType)];

		if (!isset($paymentObj)) {
			if (!isset($config['class']) || !file_exists($config['path'])) {
				die('FINISHING: FATAL! No payment possible because I don\'t know how to handle it!');
			}
			/** @noinspection PhpIncludeInspection */
			require_once($config['path']);
			$paymentObj = t3lib_div::makeInstance($config['class']);
		}

		if ($paymentObj instanceof Tx_Commerce_Payment_Interface_Payment) {
			$paymentDone = $paymentObj->checkExternalData($_REQUEST, $this->sessionData);
		} else {
			$paymentDone = FALSE;
		}

			// Check if terms are accepted
		if (!$paymentDone && (empty($this->piVars['terms']) || ($this->piVars['terms'] != 'termschecked'))) {
			$this->formError['terms'] = $this->pi_getLL('error_terms_not_accepted');
			$content = $this->handlePayment($paymentObj);
			if ($content == FALSE) {
				$this->formError['terms'] = $this->pi_getLL('error_terms_not_accepted');
				$content = $this->getListing();
			}

			return $content;
		}

			// Check stock amount of articles
		if (!$this->checkStock()) {
			$content = '<div class="cmrc_mb_no_stock">';
			$content .= $this->pi_getLL('not_all_articles_in_stock');
			$content .= $this->pi_linkToPage($this->pi_getLL('no_stock_back'), $this->conf['noStockBackPID']);
			$content .= '</div>';

			return $content;
		}

		$hookObjectsArr = $this->getHookObjectArray('finishIt');

			// Handle orders
		/** @var $basket Tx_Commerce_Domain_Model_Basket */
		$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'prepayment')) {
				$hookObj->prepayment($paymentObj, $basket);
			}
		}

		$this->debug($basket, '$basket', __FILE__ . ' ' . __LINE__);

			// Merge local lang array
		if (is_array($this->LOCAL_LANG) && isset($paymentObj->LOCAL_LANG)) {
			foreach ($this->LOCAL_LANG as $llKey => $llData) {
				$newLlData = array_merge($llData, (array) $paymentObj->LOCAL_LANG[$llKey]);
				$this->LOCAL_LANG[$llKey] = $newLlData;
			}
		}

		$paymentObj->parentObj = $this;

		if (method_exists($paymentObj, 'hasSpecialFinishingForm') && $paymentObj->hasSpecialFinishingForm($_REQUEST)) {
			$content = $paymentObj->getSpecialFinishingForm($config, $this->sessionData, $basket);

			return $content;
		} else {
			if (!$paymentObj->finishingFunction($config, $this->sessionData, $basket)) {
				$content = $this->handlePayment($paymentObj);

				return $content;
			}
		}

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postpayment')) {
				$hookObj->postpayment($paymentObj, $basket, $this);
			}
		}

		/**
		 * We implement a new TS - Setting to handle the generating of orders.
		 * if you want to use the "generateOrderId" - Hook and need a unique ID
		 * this is only possible if you insert an empty order an make an update
		 * later.
		 */
		if (isset($this->conf['lockOrderIdInGenerateOrderId']) && $this->conf['lockOrderIdInGenerateOrderId'] == 1) {
			$orderData = array();
			$now = time();
			$orderData['crdate'] = $now;
			$orderData['tstamp'] = $now;
			$database->exec_INSERTquery('tx_commerce_orders', $orderData);
			$orderUid = $database->sql_insert_id();
				// make orderUid avaible in hookObjects
			$this->orderUid = $orderUid;
		}

			// Real finishing starts here !

		$orderId = '';
			// Hook to generate OrderId
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'generateOrderId')) {
				$orderId = $hookObj->generateOrderId($orderId, $basket, $this);
			}
		}

		if (empty($orderId)) {
				// generate id if no one was generated by hook
			$orderId = uniqid('', TRUE);
		}

			// Determine sysfolder, where to place all datasests
			// Default (if no hook us used, the Commerce default folder)
		if (isset($this->conf['newOrderPid']) and ($this->conf['newOrderPid'] > 0)) {
			$orderData['pid'] = $this->conf['newOrderPid'];
		}
		if (empty($orderData['pid']) || ($orderData['pid'] < 0)) {
			$comPid = array_keys(Tx_Commerce_Domain_Repository_FolderRepository::getFolders('commerce', 0, 'COMMERCE'));
			$ordPid = array_keys(Tx_Commerce_Domain_Repository_FolderRepository::getFolders('commerce', $comPid[0], 'Orders'));
			$incPid = array_keys(Tx_Commerce_Domain_Repository_FolderRepository::getFolders('commerce', $ordPid[0], 'Incoming'));
			$orderData['pid'] = $incPid[0];
		}

			// Save the order, execute the hooks and stock
		$orderData = $this->saveOrder($orderId, $orderData['pid'], $basket, $paymentObj, TRUE, TRUE);

			// Send emails
		$this->userMailOK = $this->sendUserMail($orderId, $orderData);
		$this->adminMailOK = $this->sendAdminMail($orderId, $orderData);

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'afterMailSend')) {
				$markerArray = $hookObj->afterMailSend($orderData, $this);
			}
		}

			// Start content rendering
		$content = $this->cObj->getSubpart($this->templateCode, '###FINISH###');

		$markerArray['###LISTING_BASKET###'] = $this->makeBasketView(
			$basket,
			'###BASKET_VIEW###',
			t3lib_div::intExplode(',', $this->conf['regularArticleTypes']),
			array(
				'###LISTING_ARTICLE###',
				'###LISTING_ARTICLE2###'
			)
		);
		$markerArray['###MESSAGE###'] = '';
		$markerArray['###LISTING_TITLE###'] = $this->pi_getLL('order_confirmation');

		if (method_exists($paymentObj, 'getSuccessData')) {
			$markerArray['###MESSAGE_PAYMENT_OBJECT###'] = $paymentObj->getSuccessData($this);
		} else {
			$markerArray['###MESSAGE_PAYMENT_OBJECT###'] = '';
		}

		$deliveryAddress = '';
		if ($orderData['cust_deliveryaddress']) {
			$res = $database->exec_SELECTquery('*', 'tt_address', 'uid=' . $orderData['cust_deliveryaddress']);
			if ($data = $database->sql_fetch_assoc($res)) {
				$deliveryAddress = $this->makeAdressView($data, '###DELIVERY_ADDRESS###');
			}
		}

		$content = $this->cObj->substituteSubpart($content, '###DELIVERY_ADDRESS###', $deliveryAddress);

		$billingAddress = '';
		if ($orderData['cust_invoice']) {
			$res = $database->exec_SELECTquery('*', 'tt_address', 'uid=' . $orderData['cust_invoice']);
			if ($data = $$database->sql_fetch_assoc($res)) {
				$billingAddress = $this->makeAdressView($data, '###BILLING_ADDRESS_SUB###');
				$markerArray['###CUST_NAME###'] = $data['NAME'];
			}
		}

		$content = $this->cObj->substituteSubpart($content, '###BILLING_ADDRESS###', $billingAddress);

		$markerArray = $this->FinishItRenderGoodBadMarker($markerArray);

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray = $hookObj->ProcessMarker($markerArray, $this);
			}
		}

		$content = $this->cObj->substituteMarkerArray(
			$this->cObj->substituteMarkerArray($content, $markerArray),
			$this->languageMarker
		);

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postFinish')) {
				$hookObj->postFinish($basket, $this);
			}
		}

			// At last remove some things from the session
			// Change from mySession to real session key
		if ($this->clearSessionAfterCheckout == TRUE) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('payment'), NULL);
			$GLOBALS['TSFE']->fe_user->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('delivery'), NULL);
			$GLOBALS['TSFE']->fe_user->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('billing'), NULL);
		}

		$basket->finishOrder();
		$GLOBALS['TSFE']->fe_user->tx_commerce_basket = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Basket');
		$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;

			// Generate new Basket-ID
		$basketId = md5($GLOBALS['TSFE']->fe_user->id . ':' . rand(0, PHP_INT_MAX));

		$GLOBALS['TSFE']->fe_user->setKey('ses', 'commerceBasketId', $basketId);
		$basket->setSessionId($basketId);
		$basket->loadData();

		return $content;
	}

	/** HELPER ROUTINES **/

	/**
	 * Fills the markerArray with correct markers, regarding the success of the order
	 * Currently a dummy, will be filed in future with more error codes
	 *
	 * @param array $markerArray
	 * @return array $markerArray
	 */
	public function FinishItRenderGoodBadMarker($markerArray) {
		if ($this->finishItOK == TRUE) {
			$markerArray['###FINISH_MESSAGE_GOOD###'] = $this->pi_getLL('finish_message_good');
			$markerArray['###FINISH_MESSAGE_BAD###'] = '';
		} else {
			$markerArray['###FINISH_MESSAGE_BAD###'] = $this->pi_getLL('finish_message_bad');
			$markerArray['###FINISH_MESSAGE_GOOD###'] = '';
		}

		if ($this->userMailOK && $this->adminMailOK) {
			$markerArray['###FINISH_MESSAGE_EMAIL###'] = $this->pi_getLL('finish_message_email');
			$markerArray['###FINISH_MESSAGE_NOEMAIL###'] = '';
		} else {
			$markerArray['###FINISH_MESSAGE_NOEMAIL###'] = $this->pi_getLL('finish_message_noemail');
			$markerArray['###FINISH_MESSAGE_EMAIL###'] = '';
		}

		$markerArray['###FINISH_MESSAGE_THANKYOU###'] = $this->pi_getLL('finish_message_thankyou');

		return $markerArray;
	}

	/**
	 * check if all Articles of Basket are in stock
	 *
	 * @return boolean
	 */
	public function checkStock() {
		$result = TRUE;

		if ($this->conf['useStockHandling'] == 1 AND $this->conf['checkStock'] == 1) {
			/** @var $basket Tx_Commerce_Domain_Model_Basket */
			$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
			if (is_array($basket->getBasketItems())) {
				/** @var $basketItem Tx_Commerce_Domain_Model_BasketItem */
				foreach ($basket->getBasketItems() as $artUid => $basketItem) {
					/** @var $article Tx_Commerce_Domain_Model_Article */
					$article = $basketItem->article;
					$this->debug($article, '$article', __FILE__ . ' ' . __LINE__);
					if (!$article->hasStock($basketItem->getQuantity())) {
						$basket->changeQuantity($artUid, 0);
						$result = FALSE;
					}
				}
			}
			$basket->storeData();
		}

		return $result;
	}

	/**
	 * This method returns a general overview about the basket content.
	 * It contains
	 *  - price of all articles (sum net)
	 *  - price for shipping and package
	 *  - netto sum
	 *  - sum for tax
	 *  - end sum (gross)
	 *
	 * @param string $type ?
	 * @return string Basket sum
	 */
	public function getBasketSum($type = 'WEB') {
		/** @var $basket Tx_Commerce_Domain_Model_Basket */
		$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;

		$template = $this->cObj->getSubpart($this->templateCode, '###LISTING_BASKET_' . strtoupper($type) . '###');

		$sumNet = $basket->getSumNet();
		$sumGross = $basket->getSumGross();
		$sumTax = $sumGross - $sumNet;

		$deliveryArticleArray = $basket->getArticlesByArticleTypeUidAsUidlist(DELIVERYARTICLETYPE);

		$sumShippingNet = 0;
		$sumShippingGross = 0;

		foreach ($deliveryArticleArray as $oneDeliveryArticle) {
			/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
			$basketItem = $basket->getBasketItem($oneDeliveryArticle);
			$sumShippingNet += $basketItem->getPriceNet();
			$sumShippingGross += $basketItem->getPriceGross();
		}

		$paymentArticleArray = $basket->getArticlesByArticleTypeUidAsUidlist(PAYMENTARTICLETYPE);

		$sumPaymentNet = 0;
		$sumPaymentGross = 0;

		foreach ($paymentArticleArray as $onePaymentArticle) {
			/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
			$basketItem = $basket->getBasketItem($onePaymentArticle);
			$sumPaymentNet += $basketItem->getPriceNet();
			$sumPaymentGross += $basketItem->getPriceGross();
		}

		$paymentTitle = $basket->getFirstArticleTypeTitle(PAYMENTARTICLETYPE);

		$markerArray = array();
		$markerArray['###LABEL_SUM_ARTICLE_NET###'] = $this->pi_getLL('listing_article_net');
		$markerArray['###LABEL_SUM_ARTICLE_GROSS###'] = $this->pi_getLL('listing_article_gross');
		$markerArray['###SUM_ARTICLE_NET###'] = tx_moneylib::format($sumNet, $this->currency);
		$markerArray['###SUM_ARTICLE_GROSS###'] = tx_moneylib::format($sumGross, $this->currency);
		$markerArray['###LABEL_SUM_SHIPPING_NET###'] = $this->pi_getLL('listing_shipping_net');
		$markerArray['###LABEL_SUM_SHIPPING_GROSS##'] = $this->pi_getLL('listing_shipping_gross');
		$markerArray['###SUM_SHIPPING_NET###'] = tx_moneylib::format($sumShippingNet, $this->currency);
		$markerArray['###SUM_SHIPPING_GROSS###'] = tx_moneylib::format($sumShippingGross, $this->currency);
		$markerArray['###LABEL_SUM_NET###'] = $this->pi_getLL('listing_sum_net');
		$markerArray['###SUM_NET###'] = tx_moneylib::format($sumNet, $this->currency);
		$markerArray['###LABEL_SUM_TAX###'] = $this->pi_getLL('listing_tax');
		$markerArray['###SUM_TAX###'] = tx_moneylib::format($sumTax, $this->currency);

		$markerArray['###LABEL_SUM_GROSS###'] = $this->pi_getLL('listing_sum_gross');
		$markerArray['###SUM_GROSS###'] = tx_moneylib::format($sumGross, $this->currency);
		$markerArray['###SUM_PAYMENT_NET###'] = tx_moneylib::format($sumPaymentNet, $this->currency);
		$markerArray['###SUM_PAYMENT_GROSS###'] = tx_moneylib::format($sumPaymentGross, $this->currency);
		$markerArray['###LABEL_SUM_PAYMENT_GROSS###'] = $this->pi_getLL('label_sum_payment_gross');
		$markerArray['###LABEL_SUM_PAYMENT_NET###'] = $this->pi_getLL('label_sum_payment_net');
		$markerArray['###PAYMENT_TITLE###'] = $paymentTitle;

		$hookObjectsArr = $this->getHookObjectArray('getBasketSum');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray = $hookObj->ProcessMarker($markerArray, $this);
			}
		}

		return $this->cObj->substituteMarkerArray($template, $markerArray);
	}

	/**
	 * Returns a string that contains the address data of the specified type.
	 * Type can be 'billing' or 'delivery'.
	 *
	 * @param string $addressType Type of the address that should be exported
	 * @return string Address
	 */
	public function getAddress($addressType) {
		$typeLower = strtolower($addressType);

		$data = $this->parseRawData($this->sessionData[$typeLower], $this->conf[$typeLower . '.']['sourceFields.']);

		if (is_array($this->sessionData[$typeLower]) && (count($this->sessionData[$typeLower]) > 0) && is_array($data)) {
			$addressArray = array();

			$addressArray['###HEADER###'] = $this->pi_getLL($addressType . '_title');
			foreach ($data as $key => $value) {
				$addressArray['###LABEL_' . strtoupper($key) . '###'] = $this->pi_getLL('general_' . $key);
				$addressArray['###' . strtoupper($key) . '###'] = $value;
			}

			if ($this->conf[$addressType . '.']['subpartMarker.']['listItem']) {
				$template = $this->cObj->getSubpart(
					$this->templateCode,
					strtoupper($this->conf[$addressType . '.']['subpartMarker.']['listItem'])
				);
			} else {
				$template = $this->cObj->getSubpart($this->templateCode, '###ADDRESS_LIST###');
			}

			return $this->cObj->substituteMarkerArray($template, $addressArray);
		}

		return '';
	}

	/**
	 * Checks if an address in the SESSION is valid
	 *
	 * @param string $addressType
	 * @return boolean
	 */
	public function validateAddress($addressType) {
		$typeLower = strtolower($addressType);
		$config = $this->conf[$typeLower . '.'];
		$returnVal = TRUE;

		t3lib_div::deprecationLog('
			hook
			$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi3/class.tx_commerce_pi3.php\'][\'bevorValidateAddress\']
			is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
			$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/CheckoutController.php\'][\'beforeValidateAddress\']
		');
		$hookObjectsArr = $this->getHookObjectArray('bevorValidateAddress');
			// @todo remove merge after above hook is removed
		$hookObjectsArr = array_merge($hookObjectsArr, $this->getHookObjectArray('beforeValidateAddress'));

		$this->debug($config, 'TS Config', __FILE__ . ' ' . __LINE__);

		$this->formError = array();

		if ($this->piVars['check'] != $addressType) {
			return TRUE;
		}

			// If the address doesn't exsist in the session it's valid.
			// For the case that no delivery address was set
		$isArray = is_array($this->sessionData[$typeLower]);

		if (!$isArray) {
			return $typeLower == 'delivery';
		}

		foreach ($this->sessionData[$typeLower] as $name => $value) {
			if ($config['sourceFields.'][$name . '.']['mandatory'] == 1 && strlen($value) == 0) {
				$this->formError[$name] = $this->pi_getLL('error_field_mandatory');
				$returnVal = FALSE;
			}

			$eval = explode(',', $config['sourceFields.'][$name . '.']['eval']);

			foreach ($eval as $method) {
				$method = explode('_', $method);
				switch (strtolower($method[0])) {
					case 'email':
						if (!t3lib_div::validEmail($value)) {
							$this->formError[$name] = $this->pi_getLL('error_field_email');
							$returnVal = FALSE;
						}
					break;

					case 'username':
						if ($GLOBALS['TSFE']->loginUser) {
							break;
						}
						if (!$this->checkUserName($value)) {
							$this->formError[$name] = $this->pi_getLL('error_field_username');
							$returnVal = FALSE;
						}
					break;

					case 'string':
						if (!is_string($value)) {
							$this->formError[$name] = $this->pi_getLL('error_field_string');
							$returnVal = FALSE;
						}
					break;

					case 'int':
						if (!is_integer($value) && preg_match('/^\d+$/', $value) !== 1) {
							$this->formError[$name] = $this->pi_getLL('error_field_int');
							$returnVal = FALSE;
						}
					break;

					case 'min':
						if (strlen((string) $value) < (int) $method[1]) {
							$this->formError[$name] = $this->pi_getLL('error_field_min');
							$returnVal = FALSE;
						}
					break;

					case 'max':
						if (strlen((string) $value) > (int) $method[1]) {
							$this->formError[$name] = $this->pi_getLL('error_field_max');
							$returnVal = FALSE;
						}
					break;

					case 'alpha':
						if (preg_match('/[0-9]/', $value) === 1) {
							$this->formError[$name] = $this->pi_getLL('error_field_alpha');
							$returnVal = FALSE;
						}
					break;

					default:
						if (!empty($method[0])) {
							$actMethod = 'validationMethod_' . strtolower($method[0]);
							foreach ($hookObjectsArr as $hookObj) {
								if (method_exists($hookObj, $actMethod)) {
									if (!$hookObj->$actMethod($this, $name, $value)) {
										$returnVal = FALSE;
									}
								}
							}
						}
					break;
				}
			}

			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'validateField')) {
					$params = array(
						'fieldName' => $name,
						'fieldValue' => $value,
						'addressType' => $addressType,
						'config' => $config['sourceFields.'][$name . '.']
					);
					if (!$hookObj->validateField($params, $this)) {
						$returnVal = FALSE;
					}
				}
			}
		}

		return $returnVal;
	}

	/**
	 * Check if a username is valid
	 *
	 * @param string $username Username
	 * @return boolean
	 */
	public function checkUserName($username) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$table = 'fe_users';
		$fields = 'uid';
		$select = 'username = ' . $database->fullQuoteStr($username, $table) . ' ';
		$select .= t3lib_befunc::deleteClause($table);
		$select .= ' AND pid = ' . $this->conf['userPID'];

		$res = $database->exec_SELECTquery($fields, $table, $select);
		$row = $database->sql_fetch_assoc($res);

		if (is_array($row) && count($row)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Get payment data from session
	 *
	 * @return string Payment data
	 */
	public function getPaymentData() {
		$result = '';

		if (is_array($this->sessionData['mails']['payment'])) {
			foreach ($this->sessionData['mails']['payment'] as $k => $data) {
				if ($k <> 'cc_checksum') {
					$result .= $data['label'] . ' : ';
					if ($k == 'cc_number') {
						$data['data'] = substr($data['data'], 0, -3) . 'XXX';
					}
					$result .= $data['data'] . LF;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the payment object and includes the Payment Class.
	 * If there is no payment it throws an error
	 *
	 * @param string $paymentType
	 * @return Tx_Commerce_Payment_Interface_Payment
	 */
	public function getPaymentObject($paymentType = '') {
		if (empty($paymentType)) {
			$paymentType = $this->getPaymentType();
		}

		return parent::getPaymentObject($paymentType);
	}

	/**
	 * Return payment type. The type is extracted from the basket object. The type
	 * is stored in the basket as a special article.
	 *
	 * @param boolean $id  Switch for returning the id or classname
	 * @return string Determines the payment ('creditcard', 'invoice' or whatever)
	 * 		if not $id is set, otherwise returns the id of the paymentarticle
	 */
	public function getPaymentType($id = FALSE) {
		/** @var $basket Tx_Commerce_Domain_Model_Basket */
		$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
		$payment = $basket->getArticlesByArticleTypeUidAsUidlist(PAYMENTARTICLETYPE);

		if ($id) {
			return $payment[0];
		}

		$paymenttitle = $basket->getBasketItem($payment[0])->getArticle()->getClassname();

		return strtolower($paymenttitle);
	}

	/**
	 * Create a form from a table where the fields can prefilled,
	 * configured via TypoScript.
	 *
	 * @param array $config Config array
	 * @param string $step Current step
	 * @param boolean $parseList
	 * @return string Form HTML
	 */
	public function getInputForm($config, $step, $parseList = TRUE) {
		$hookObjectsArr = $this->getHookObjectArray('processInputForm');

			// Build a query for selecting an address from database
			// if we have a logged in user
		if ($parseList) {
			$fieldList = $this->parseFieldList($config['sourceFields.']);
		} else {
			$fieldList = array_keys($config['sourceFields.']);
		}

		$this->dbFieldData = $this->sessionData[$step];

		$fieldTemplate = $this->cObj->getSubpart($this->templateCode, '###SINGLE_INPUT###');
		$fieldTemplateCheckbox = $this->cObj->getSubpart($this->templateCode, '###SINGLE_CHECKBOX###');

		$fieldCode = '';
		foreach ($fieldList as $fieldName) {
			$fieldMarkerArray = array();
			$fieldLabel = $this->pi_getLL($step . '_' . $fieldName, $this->pi_getLL('general_' . $fieldName));
			if ($config['sourceFields.'][$fieldName . '.']['mandatory'] == '1') {
				$fieldLabel .= ' ' . $this->cObj->stdWrap($config['mandatorySign'], $config['mandatorySignStdWrap.']);
			}
			$fieldMarkerArray['###FIELD_LABEL###'] = $fieldLabel;

				// Clear the error field, this has to be implemented in future versions
			if (strlen($this->formError[$fieldName]) > 0) {
				$fieldMarkerArray['###FIELD_ERROR###'] = $this->cObj->stdWrap($this->formError[$fieldName], $config['fielderror.']);
			} else {
				$fieldMarkerArray['###FIELD_ERROR###'] = '';
			}

				// Create input field
			$arrayName = $fieldName . (($parseList) ? '.' : '');
			$fieldMarkerArray['###FIELD_INPUT###'] = $this->getInputField(
				$fieldName,
				$config['sourceFields.'][$arrayName],
				t3lib_div::removeXSS(strip_tags($this->sessionData[$step][$fieldName])),
				$step
			);
			$fieldMarkerArray['###FIELD_NAME###'] = $this->prefixId . '[' . $step . '][' . $fieldName . ']';
			$fieldMarkerArray['###FIELD_INPUTID###'] = $step . '-' . $fieldName;

				// Save some data for mails
			$this->sessionData['mails'][$step][$fieldName] = array(
				'data' => $this->sessionData[$step][$fieldName],
				'label' => $fieldLabel
			);
			if ($config['sourceFields.'][$arrayName]['type'] == 'check') {
				$fieldCodeTemplate = $fieldTemplateCheckbox;
			} else {
				$fieldCodeTemplate = $fieldTemplate;
			}

			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'processInputForm')) {
					$hookObj->processInputForm($fieldName, $fieldMarkerArray, $config, $step, $fieldCodeTemplate, $this);
				}
			}
			$fieldCode .= $this->cObj->substituteMarkerArray($fieldCodeTemplate, $fieldMarkerArray);
		}

		$GLOBALS['TSFE']->fe_user->setKey('ses', Tx_Commerce_Utility_GeneralUtility::generateSessionKey('mails'), $this->sessionData['mails']);

		return $fieldCode;
	}

	/**
	 * Handle adress data
	 *
	 * @param string $type Session type
	 * @return integer uid of user
	 */
	public function handleAddress($type) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (!is_array($this->sessionData[$type])) {
			return 0;
		}

		$config = $this->conf[$type . '.'];

		$fieldList = $this->parseFieldList($config['sourceFields.']);
		if (is_array($fieldList)) {
			foreach ($fieldList as $fieldName) {
				$dataArray[$fieldName] = $this->sessionData[$type][$fieldName];
			}
		}

			// Check if a uid is set, so address handling can be used.
			// Only possible if user is logged in
		if ($this->sessionData[$type]['uid'] && $GLOBALS['TSFE']->loginUser) {
			$uid = $this->sessionData[$type]['uid'];
		} else {
				// Create
			if (isset($this->conf['addressPid'])) {
				$dataArray['pid'] = $this->conf['addressPid'];
			} else {
				$modPid = 0;
				list($commercePid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Commerce', 'commerce', $modPid);
				$dataArray['pid'] = $commercePid;
			}

			if (isset($GLOBALS['TSFE']->fe_user->user['uid'])) {
				$dataArray[$config['userConnection']] = $GLOBALS['TSFE']->fe_user->user['uid'];
			} else {
					// Create new user if no user is logged in and the option is set
				if ($this->conf['createNewUsers']) {
						// Added some changes for
						// 1) using email as username by default
						// 2) fill in new fields in table
						// 3) provide data for usermail
						// 4) use billing as default type
					$feuData = array();
					$feuData['pid'] = $this->conf['userPID'];
					$feuData['usergroup'] = $this->conf['userGroup'];
					$feuData['tstamp'] = time();
					if ($this->conf['randomUser']) {
						$feuData['username'] = substr($this->sessionData['billing']['name'], 0, 2) .
							substr($this->sessionData['billing']['surname'], 0, 4) . substr(uniqid(rand()), 0, 4);
					} else {
						$feuData['username'] = $this->sessionData['billing']['email'];
					}

						// uses either the typed in password (if configured) or a random password - default is random
					if (
						isset($config['dontUseRandomPassword'])
						&& $config['dontUseRandomPassword']
						&& isset($this->sessionData['billing']['password'])
					) {
						$feuData['password'] = $this->sessionData['billing']['password'];
					} else {
						$feuData['password'] = substr(uniqid(rand()), 0, 6);
					}

					$feuData['email'] = $this->sessionData['billing']['email'];
					$feuData['name'] = $this->sessionData['billing']['name'] . ' ' . $this->sessionData['billing']['surname'];
					$feuData['first_name'] = $this->sessionData['billing']['name'];
					$feuData['last_name'] = $this->sessionData['billing']['surname'];

					$hookObjectsArr = $this->getHookObjectArray('handleAddress');
					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'preProcessUserData')) {
							$hookObj->preProcessUserData($feuData, $this);
						}
					}

					$database->exec_INSERTquery('fe_users', $feuData);

					$dataArray[$config['userConnection']] = $database->sql_insert_id();

					$GLOBALS['TSFE']->fe_user->user['uid'] = $dataArray[$config['userConnection']];

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'postProcessUserData')) {
							$hookObj->postProcessUserData($feuData, $this);
						}
					}

					$this->userData = $feuData;
				}
			}

			$dataArray[$config['sourceLimiter.']['field']] = $config['sourceLimiter.']['value'];

				// unsets the fields that are not present in tt_adress before inserting them
			if (isset($config['tt_adressExcludeFields']) && $config['tt_adressExcludeFields'] != '') {
				$tt_adressExcludeFields = t3lib_div::trimExplode(',', $config['tt_adressExcludeFields']);
				foreach ($tt_adressExcludeFields as $excludeField) {
					unset($dataArray[$excludeField]);
				}
			}

				// First address should be main address by default
			$dataArray['tx_commerce_is_main_address'] = 1;

			$database->exec_INSERTquery('tt_address', $dataArray);

			$uid = $database->sql_insert_id();
		}

		return $uid;
	}

	/**
	 * @param string $value
	 * @param string $type
	 * @param string $field
	 * @return string
	 */
	public function getField($value, $type, $field) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if ($this->conf[$type . '.']['sourceFields.'][$field . '.']['table']) {
			$table = $this->conf[$type . '.']['sourceFields.'][$field . '.']['table'];
			$select = $this->conf[$type . '.']['sourceFields.'][$field . '.']['value'] . ' = \'' . $value . '\'';
			$fields = $this->conf[$type . '.']['sourceFields.'][$field . '.']['label'];
			$res = $database->exec_SELECTquery($fields, $table, $select);
			$row = $database->sql_fetch_assoc($res);

			return $row[$fields];
		}

		return $value;
	}

	/**
	 * Return a single input form field.
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration of this field
	 * @param string $fieldValue Current value of this field
	 * @param string $step Name of the step
	 * @return string Single input field
	 */
	protected function getInputField($fieldName, $fieldConfig, $fieldValue, $step) {
		$this->debug($step, '$step', __FILE__ . ' ' . __LINE__);
		$this->debug($fieldConfig, '$fieldConfig', __FILE__ . ' ' . __LINE__);
		$this->debug($fieldValue, '$fieldValue', __FILE__ . ' ' . __LINE__);

		switch (strtolower($fieldConfig['type'])) {
			case 'select':
				$result = $this->getSelectInputField($fieldName, $fieldConfig, $fieldValue, $step);
			break;

			case 'static_info_tables':
				$selected = $fieldValue != '' ? $fieldValue : $fieldConfig['default'];

				$result = $this->staticInfo->buildStaticInfoSelector(
					$fieldConfig['field'],
					$this->prefixId . '[' . $step . '][' . $fieldName . ']',
					$fieldConfig['cssClass'],
					$selected,
					'',
					'',
					$step . '-' . $fieldName,
					'',
					$fieldConfig['select'],
					$GLOBALS['TSFE']->tmpl->setup['config.']['language']
				);
			break;

			case 'static_info_country':
				$countries = $this->staticInfo->initCountries(
					$fieldConfig['country_association'],
					$GLOBALS['TSFE']->tmpl->setup['config.']['language'],
					1,
					$fieldConfig['select']
				);
				asort($countries, SORT_LOCALE_STRING);

				$selected = $fieldValue != '' ? $fieldValue : $fieldConfig['default'];

				$result = '<select id="' . $step . '-' . $fieldName . '"' . $this->prefixId . '[' . $step . '][' . $fieldName . ']' .
					'class="' . $fieldConfig['cssClass'] . '">' . LF;
				$result .= $this->staticInfo->optionsConstructor($countries, array($selected), $outSelectedArray);
				$result .= '</select>' . LF;
			break;

			case 'check':
				$result = $this->getCheckboxInputField($fieldName, $fieldConfig, $fieldValue, $step);
			break;

			case 'single':
			default:
				$result = $this->getSingleInputField($fieldName, $fieldConfig, $step);
			break;
		}

		return $result;
	}

	/**
	 * Return a single text input field
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration of this field (usually TypoScript)
	 * @param string $step Name of the step
	 * @return string Single input field
	 */
	protected function getSingleInputField($fieldName, $fieldConfig, $step) {
		if (($fieldConfig['default']) && empty($this->dbFieldData[$fieldName])) {
			$value = $fieldConfig['default'];
		} else {
			$value = $this->dbFieldData[$fieldName];
		}

		$maxlength = '';
		if (isset($fieldConfig['maxlength']) AND is_numeric($fieldConfig['maxlength'])) {
			$maxlength = ' maxlength="' . $fieldConfig['maxlength'] . '"';
		}

		if ($fieldConfig['noPrefix'] == 1) {
			$result = '<input id="' . $step . '-' . $fieldName . '" type="text" name="' . $fieldName .
				'" value="' . $value . '" ' . $maxlength;
			if ($fieldConfig['readonly'] == 1) {
				$result .= ' readonly disabled /><input type="hidden" name="' . $fieldName .
					'" value="' . $value . '" ' . $maxlength . ' />';
			} else {
				$result .= '/>';
			}
		} else {
			$result = '<input id="' . $step . '-' . $fieldName . '" type="text" name="' . $this->prefixId .
				'[' . $step . '][' . $fieldName . ']" value="' . $value . '" ' . $maxlength;
			if ($fieldConfig['readonly'] == 1) {
				$result .= ' readonly disabled /><input type="hidden" name="' . $this->prefixId .
					'[' . $step . '][' . $fieldName . ']" value="' . $value . '" ' . $maxlength . ' />';
			} else {
				$result .= '/>';
			}
		}

		return $result;
	}

	/**
	 * Return a single selectbox
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration of this field (usually TypoScript)
	 * @param string $fieldValue Current value of this field (usually from piVars)
	 * @param string $step Name of the step
	 * @return string Single selectbox
	 */
	protected function getSelectInputField($fieldName, $fieldConfig, $fieldValue = '', $step = '') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = '<select id="' . $step . '-' . $fieldName . '" name="' . $this->prefixId . '[' . $step . '][' . $fieldName . ']">';

		if ($fieldValue != '') {
			$fieldConfig['default'] = $fieldValue;
		}

			// If static items are set
		if (is_array($fieldConfig['values.'])) {
			foreach ($fieldConfig['values.'] as $key => $option) {
				$result .= '<option name="' . $key . '" value="' . $key . '"';
				if ($fieldValue === $key) {
					$result .= ' selected="selected"';
				}
				$result .= '>' . $option . '</option>' . LF;
			}
		} else {
				// Try to fetch data from database
			$table = $fieldConfig['table'];
			$select = $fieldConfig['select'] . $this->cObj->enableFields($fieldConfig['table']);
			$fields = $fieldConfig['label'] . ' AS label,' . $fieldConfig['value'] . ' AS value';
			$orderby = ($fieldConfig['orderby']) ?
				$fieldConfig['orderby'] :
				'';
			$res = $database->exec_SELECTquery($fields, $table, $select, '', $orderby);

			while ($row = $database->sql_fetch_assoc($res)) {
				$result .= '<option  value="' . $row['value'] . '"';
				if ($row['value'] === $fieldConfig['default']) {
					$result .= ' selected="selected"';
				}
				$result .= '>' . $row['label'] . '</option>' . LF;
			}
		}
		$result .= '</select>';

		return $result;
	}

	/**
	 * Returns a single checkbox
	 *
	 * @param string $fieldName Name of the field
	 * @param array $fieldConfig Configuration of this field (usually TypoScript)
	 * @param string $fieldValue Current value of this field (usually piVars)
	 * @param string $step Name of the step
	 * @return string Single checkbox
	 */
	protected function getCheckboxInputField($fieldName, $fieldConfig, $fieldValue = '', $step = '') {
		$result = '<input id="' . $step . '-' . $fieldName . '" type="checkbox" name="' . $this->prefixId .
			'[' . $step . '][' . $fieldName . ']" id="' . $this->prefixId . '[' . $step . '][' . $fieldName . ']" value="1" ';

		if (($fieldConfig['default'] == '1' && $fieldValue != 0) || $fieldValue == 1) {
			$result .= 'checked="checked" ';
		}
		$result .= ' /> ';

		if ($fieldConfig['additionalinfo'] != '') {
			$result .= $fieldConfig['additionalinfo'];
		}

		return $result;
	}

	/**
	 * Creates a list of array keys where the last character is removed from it
	 * but only if the last character is a dot (.)
	 *
	 * @param array $fieldConfig Configuration of this field
	 * @return array
	 */
	protected function parseFieldList($fieldConfig) {
		$result = array();
		if (!is_array($fieldConfig)) {
			return $result;
		}

		foreach ($fieldConfig as $key => $data) {
			$result[] = rtrim($key, '.');
		}

		return $result;
	}

	/**
	 * Returns whether a checkout is allowed or not.
	 * It can return different types of results. Possible keywords are:
	 * - noarticles => User has not articles in basket
	 * - nopayment => User has no payment type selected
	 * - nobilling => User is in step 'finish' but no billing address was set
	 *
	 * @return string|boolean TRUE if checkout is possible, else one of the keywords
	 */
	protected function canMakeCheckout() {
		$checks = array(
			'noarticles',
			'nopayment',
			'nobilling'
		);

		$myCheck = FALSE;

		$hookObjectsArr = $this->getHookObjectArray('canMakeCheckout');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'canMakeCheckoutOwnTests')) {
				$hookObj->canMakeCheckoutOwnTests($checks, $myCheck);
			}
		}

		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'canMakeCheckoutOwnAdvancedTests')) {
				$params = array(
					'checks' => &$checks,
					'myCheck' => &$myCheck
				);
				$hookObj->canMakeCheckoutOwnAdvancedTests($params, $this);
			}
		}

			// Check if the hooks returned an error
		if (strlen($myCheck) >= 1) {
			return $myCheck;
		}

		/** @var $basket Tx_Commerce_Domain_Model_Basket */
		$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;

			// Check if basket is empty
		if (in_array('noarticles', $checks) && !$basket->hasArticles(NORMALARTICLETYPE)) {
			return 'noarticles';
		}

			// Check if we have a payment article in the basket
		if (in_array('nopayment', $checks) && $this->currentStep == 'finish') {
			$paymentArticles = $basket->getArticlesByArticleTypeUidAsUidlist(PAYMENTARTICLETYPE);
			if (count($paymentArticles) <= 0) {
				return 'nopayment';
			}
		}

			// Check if we have a delivery address, some payment infos
			// and if we are in the finishing step
		if (in_array('nobilling', $checks) && $this->currentStep == 'finish' && !isset($this->sessionData['billing'])) {
			return 'nobilling';
		}

			// If we reach this point, everything is fine
		return TRUE;
	}

	/**
	 * Sends information mail to the user
	 * Also performes a charset Conversion for the mail
	 *
	 * @param integer $orderUid OrderID
	 * @param array $orderData Collected Order Data form PI3
	 * @return boolean TRUE on success
	 */
	public function sendUserMail($orderUid, $orderData) {
		$hookObjectsArr = $this->getHookObjectArray('sendUserMail');

		if (strlen($this->sessionData['billing']['email'])) {
				// If user has email in the formular, use this
			$userMail = $this->sessionData['billing']['email'];
		} elseif (is_array($GLOBALS['TSFE']->fe_user->user) && strlen($GLOBALS['TSFE']->fe_user->user['email'])) {
			$userMail = $GLOBALS['TSFE']->fe_user->user['email'];
		} else {
			return FALSE;
		}

		$userMail = Tx_Commerce_Utility_GeneralUtility::validEmailList($userMail);

		if ($userMail && !preg_match("/\r/i", $userMail) && !preg_match("/\n/i", $userMail)) {
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'getUserMail')) {
					$hookObj->getUserMail($userMail, $orderUid, $orderData);
				}
			}

			if ($userMail != '' && t3lib_div::validEmail($userMail)) {
				/** @var $userMailObj Tx_Commerce_Controller_CheckoutController */
				$userMailObj = t3lib_div::makeInstance('Tx_Commerce_Controller_CheckoutController');
				$userMailObj->conf = $this->conf;
				$userMailObj->pi_setPiVarDefaults();
				$userMailObj->cObj = $this->cObj;
				$userMailObj->pi_loadLL();
				$userMailObj->staticInfo = & $this->staticInfo;
				$userMailObj->currency = $this->currency;
				$userMailObj->showCurrency = $this->conf['usermail.']['showCurrency'];
				$userMailObj->templateCode = $this->cObj->fileResource($this->conf['usermail.']['templateFile']);
				$userMailObj->generateLanguageMarker();
				$userMailObj->userData = $this->userData;

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'preGenerateMail')) {
						$hookObj->preGenerateMail($userMailObj, $this);
					}
				}

				$userMarker = array();
				$mailcontent = $userMailObj->generateMail($orderUid, $orderData, $userMarker);

				/** @var $basket Tx_Commerce_Domain_Model_Basket */
				$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'PostGenerateMail')) {
						$hookObj->PostGenerateMail($userMailObj, $this, $basket, $mailcontent);
					}
				}

				$htmlContent = '';
				if ($this->conf['usermail.']['useHtml'] == '1' && $this->conf['usermail.']['templateFileHtml']) {
					$userMailObj->templateCode = $this->cObj->fileResource($this->conf['usermail.']['templateFileHtml']);
					$htmlContent = $userMailObj->generateMail($orderUid, $orderData, $userMarker, TRUE);
					$userMailObj->isHTMLMail = TRUE;
					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'PostGenerateMail')) {
							$hookObj->PostGenerateMail($userMailObj, $this, $basket, $htmlContent);
						}
					}
					unset($userMailObj->isHTMLMail);
				}

					// Moved to plainMailEncoded
				$parts = explode(chr(10), $mailcontent, 2);
					// First line is subject
				$subject = trim($parts[0]);
				$plainMessage = trim($parts[1]);

					// Check if charset ist set by TS
					// Otherwise set to default Charset
				if (!$this->conf['usermail.']['charset']) {
					$this->conf['usermail.']['charset'] = $GLOBALS['TSFE']->renderCharset;
				}

					// Checck if mailencoding ist set
					// otherwise set to 8bit
				if (!$this->conf['usermail.']['encoding']) {
					$this->conf['usermail.']['encoding'] = '8bit';
				}

					// Convert Text to charset
				$GLOBALS['TSFE']->csConvObj->initCharset($GLOBALS['TSFE']->renderCharset);
				$GLOBALS['TSFE']->csConvObj->initCharset(strtolower($this->conf['usermail.']['charset']));
				$plainMessage = $GLOBALS['TSFE']->csConvObj->conv(
					$plainMessage,
					$GLOBALS['TSFE']->renderCharset,
					strtolower($this->conf['usermail.']['charset'])
				);
				$subject = $GLOBALS['TSFE']->csConvObj->conv(
					$subject,
					$GLOBALS['TSFE']->renderCharset,
					strtolower($this->conf['usermail.']['charset'])
				);

				if ($this->debug) {
					print '<b>Usermail to ' . $userMail . '</b><pre>' . $plainMessage . '</pre>' . LF;
				}

					// Mailconf for  tx_commerce_div::sendMail($mailconf);
				$recipient = array();
				if ($this->conf['usermail.']['cc']) {
					$recipient = t3lib_div::trimExplode(',', $this->conf['usermail.']['cc']);
				}
				if (is_array($recipient)) {
					array_push($recipient, $userMail);
				}
				$mailconf = array(
					'plain' => array(
						'content' => $plainMessage,
						'subject' => $subject
					),
					'html' => array(
						'content' => $htmlContent,
						'path' => '',
						'useHtml' => $this->conf['usermail.']['useHtml']
					),
					'defaultCharset' => $this->conf['usermail.']['charset'],
					'encoding' => $this->conf['usermail.']['encoding'],
					'attach' => $this->conf['usermail.']['attach.'],
					'alternateSubject' => $this->conf['usermail.']['alternateSubject'],
					'recipient' => implode(',', $recipient),
					'recipient_copy' => $this->conf['usermail.']['bcc'],
					'fromEmail' => $this->conf['usermail.']['from'],
					'fromName' => $this->conf['usermail.']['from_name'],
					'replyTo' => $this->conf['usermail.']['from'],
					'priority' => $this->conf['usermail.']['priority'],
					'callLocation' => 'sendUserMail',
					'additionalData' => $this
				);

				Tx_Commerce_Utility_GeneralUtility::sendMail($mailconf);

				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Send admin mail
	 * Also performes a charset Conversion for the mail, including Sender
	 *
	 * @param integer $orderUid Order ID
	 * @param array $orderData Collected Order Data form PI3
	 * @return boolean TRUE on success
	 */
	public function sendAdminMail($orderUid, $orderData) {
		$hookObjectsArr = $this->getHookObjectArray('sendAdminMail');

		if (is_array($GLOBALS['TSFE']->fe_user->user && strlen($GLOBALS['TSFE']->fe_user->user['email']))) {
			$userMail = $GLOBALS['TSFE']->fe_user->user['email'];
		} else {
			$userMail = $this->sessionData['billing']['email'];
		}

		if (is_array($GLOBALS['TSFE']->fe_user->user && strlen($GLOBALS['TSFE']->fe_user->user['email']))) {
			$userName = $GLOBALS['TSFE']->fe_user->user['name'] . ' ' . $GLOBALS['TSFE']->fe_user->user['surname'];
		} else {
			$userName = $this->sessionData['billing']['name'] . ' ' . $this->sessionData['billing']['surname'];
		}

		if ($this->conf['adminmail.']['from'] || $userMail) {
			/** @var $adminMailObj Tx_Commerce_Controller_CheckoutController */
			$adminMailObj = t3lib_div::makeInstance('Tx_Commerce_Controller_CheckoutController');
			$adminMailObj->conf = $this->conf;
			$adminMailObj->pi_setPiVarDefaults();
			$adminMailObj->cObj = $this->cObj;
			$adminMailObj->pi_loadLL();
			$adminMailObj->staticInfo = & $this->staticInfo;
			$adminMailObj->currency = $this->currency;
			$adminMailObj->showCurrency = $this->conf['adminmail.']['showCurrency'];
			$adminMailObj->templateCode = $this->cObj->fileResource($this->conf['adminmail.']['templateFile']);
			$adminMailObj->generateLanguageMarker();
			$adminMailObj->userData = $this->userData;

			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'preGenerateMail')) {
					$hookObj->preGenerateMail($adminMailObj, $this);
				}
			}

			$mailcontent = $adminMailObj->generateMail($orderUid, $orderData);

			/** @var $basket Tx_Commerce_Domain_Model_Basket */
			$basket = & $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'PostGenerateMail')) {
					$hookObj->PostGenerateMail($adminMailObj, $this, $basket, $mailcontent, $this);
				}
			}

			$htmlContent = '';
			if ($this->conf['adminmail.']['useHtml'] == '1' && $this->conf['adminmail.']['templateFileHtml']) {
				$adminMailObj->templateCode = $this->cObj->fileResource($this->conf['adminmail.']['templateFileHtml']);
				$htmlContent = $adminMailObj->generateMail($orderUid, $orderData, '', TRUE);
				$adminMailObj->isHTMLMail = TRUE;

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'PostGenerateMail')) {
						$hookObj->PostGenerateMail($adminMailObj, $this, $basket, $htmlContent);
					}
				}
				unset($adminMailObj->isHTMLMail);
			}

				// Moved to plainMailEncoded
				// First line is subject
			$parts = explode(chr(10), $mailcontent, 2);
			$subject = trim($parts[0]);
			$plainMessage = trim($parts[1]);

				// Check if charset ist set by TS
				// Otherwise set to default Charset
			if (!$this->conf['adminmail.']['charset']) {
				$this->conf['adminmail.']['charset'] = $GLOBALS['TSFE']->renderCharset;
			}

				// Checck if mailencoding ist set
				// Otherwise set to 8bit
			if (!$this->conf['adminmail.']['encoding ']) {
				$this->conf['adminmail.']['encoding '] = '8bit';
			}

				// Convert Text to charset
			$GLOBALS['TSFE']->csConvObj->initCharset($GLOBALS['TSFE']->renderCharset);
			$GLOBALS['TSFE']->csConvObj->initCharset(strtolower($this->conf['adminmail.']['charset']));
			$plainMessage = $GLOBALS['TSFE']->csConvObj->conv(
				$plainMessage,
				$GLOBALS['TSFE']->renderCharset,
				strtolower($this->conf['adminmail.']['charset'])
			);
			$subject = $GLOBALS['TSFE']->csConvObj->conv(
				$subject,
				$GLOBALS['TSFE']->renderCharset,
				strtolower($this->conf['adminmail.']['charset'])
			);
			$usernameMailencoded = $GLOBALS['TSFE']->csConvObj->specCharsToASCII($GLOBALS['TSFE']->renderCharset, $userName);

			if ($this->debug) {
				print '<b>Adminmail from </b><pre>' . $plainMessage . '</pre>' . LF;
			}

				// Mailconf for tx_commerce_div::sendMail($mailconf);
			$recipient = array();
			if ($this->conf['adminmail.']['cc']) {
				$recipient = t3lib_div::trimExplode(',', $this->conf['adminmail.']['cc']);
			}
			if (is_array($recipient)) {
				array_push($recipient, $this->conf['adminmail.']['mailto']);
			}
			$mailconf = array(
				'plain' => array(
					'content' => $plainMessage,
					'subject' => $subject
				),
				'html' => array(
					'content' => $htmlContent,
					'path' => '',
					'useHtml' => $this->conf['adminmail.']['useHtml']
				),
				'defaultCharset' => $this->conf['adminmail.']['charset'],
				'encoding' => $this->conf['adminmail.']['encoding'],
				'attach' => $this->conf['adminmail.']['attach.'],
				'alternateSubject' => $this->conf['adminmail.']['alternateSubject'],
				'recipient' => implode(',', $recipient),
				'recipient_copy' => $this->conf['adminmail.']['bcc'],
				'replyTo' => $this->conf['adminmail.']['from'],
				'priority' => $this->conf['adminmail.']['priority'],
				'callLocation' => 'sendAdminMail',
				'additionalData' => $this
			);

				// Check if user mail is set
			if (($userMail) && ($usernameMailencoded) && ($this->conf['adminmail.']['sendAsUser'] == 1)) {
				$mailconf['fromEmail'] = $userMail;
				$mailconf['fromName'] = $usernameMailencoded;
			} else {
				$mailconf['fromEmail'] = $this->conf['adminmail.']['from'];
				$mailconf['fromName'] = $this->conf['adminmail.']['from_name'];
			}

			Tx_Commerce_Utility_GeneralUtility::sendMail($mailconf);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Generate one Mail
	 *
	 * @param string $orderUid The Order UID
	 * @param array $orderData Collected Order Data form PI3
	 * @param array $userMarker User marker array
	 * @return string MailContent
	 */
	public function generateMail($orderUid, $orderData, $userMarker = array()) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$markerArray = $userMarker;
		$markerArray['###ORDERID###'] = $orderUid;
		$markerArray['###ORDERDATE###'] = date($this->conf['generalMail.']['orderDate_format'], $orderData['tstamp']);
		$markerArray['###COMMENT###'] = $orderData['comment'];
		$markerArray['###LABEL_PAYMENTTYPE###'] =
			$this->pi_getLL('payment_paymenttype_' . $orderData['paymenttype'], $orderData['paymenttype']);

			// Since The first line of the mail is the Suibject, trim the template
		$template = ltrim($this->cObj->getSubpart($this->templateCode, '###MAILCONTENT###'));

			// Added replacing marker for new users
		$templateUser = '';
		if (is_array($this->userData)) {
			$templateUser = trim($this->cObj->getSubpart($template, '###NEW_USER###'));
			$templateUser = $this->cObj->substituteMarkerArray($templateUser, $this->userData, '###|###', 1);
		}

		$content = $this->cObj->substituteSubpart($template, '###NEW_USER###', $templateUser);

		$basketContent = $this->makeBasketView(
			$GLOBALS['TSFE']->fe_user->tx_commerce_basket,
			'###BASKET_VIEW###',
			t3lib_div::intExplode(',', $this->conf['regularArticleTypes']), array(
				'###LISTING_ARTICLE###',
				'###LISTING_ARTICLE2###'
			)
		);

		$content = $this->cObj->substituteSubpart($content, '###BASKET_VIEW###', $basketContent);

			// Get addresses
		$deliveryAdress = '';
		if ($orderData['cust_deliveryaddress']) {
			$res = $database->exec_SELECTquery('*', 'tt_address', 'uid = ' . (int) $orderData['cust_deliveryaddress']);
			if ($data = $database->sql_fetch_assoc($res)) {
				$data = $this->parseRawData($data, $this->conf['delivery.']['sourceFields.']);
				$deliveryAdress = $this->makeAdressView($data, '###DELIVERY_ADDRESS###');
			}
		}

		$content = $this->cObj->substituteSubpart($content, '###DELIVERY_ADDRESS###', $deliveryAdress);

		$billingAdress = '';
		if ($orderData['cust_invoice']) {
			$res = $database->exec_SELECTquery('*', 'tt_address', 'uid = ' . (int) $orderData['cust_invoice']);
			if ($data = $database->sql_fetch_assoc($res)) {
				$data = $this->parseRawData($data, $this->conf['billing.']['sourceFields.']);
				$billingAdress = $this->makeAdressView($data, '###BILLING_ADDRESS###');
				$markerArray['###CUST_NAME###'] = $data['NAME'];
			}
		}

		$content = $this->cObj->substituteSubpart($content, '###BILLING_ADDRESS###', $billingAdress);

			// Hook to process marker array
		$hookObjectsArr = $this->getHookObjectArray('generateMail');
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray = $hookObj->ProcessMarker($markerArray, $this);
			}
		}

		$markerArray = array_merge((array) $markerArray, (array) $this->languageMarker);

		$content = $this->cObj->substituteMarkerArray($content, $markerArray);

		return ltrim($content);
	}

	/**
	 * Parses raw data array from db and replace keys with matching values (select
	 * fields) like country in address data
	 *
	 * @param array $data Address data
	 * @param array $typoScript TypoScript for addresshandling for this type
	 * @throws Exception
	 * @return array Address data
	 */
	public function parseRawData($data = array(), $typoScript) {
		if (!is_array($data)) {
			return array();
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$this->debug($typoScript, '$typoScript', __FILE__ . ' ' . __LINE__);

		$newdata = array();
		foreach ($data as $key => $value) {
			$newdata[$key] = $value;

			$fieldConfig = $typoScript[$key . '.'];
				// Get the value from database if the field is a select box
			if ($fieldConfig['type'] == 'select' && strlen($fieldConfig['table'])) {
				$table = $fieldConfig['table'];
				$select = $fieldConfig['value'] . '=\'' . $value . '\'' . $this->cObj->enableFields($fieldConfig['table']);
				$fields = $fieldConfig['label'] . ' AS label,';
				$fields .= $fieldConfig['value'] . ' AS value';
				$res = $database->exec_SELECTquery($fields, $table, $select);
				$value = $database->sql_fetch_assoc($res);

				$newdata[$key] = $value['label'];
			} elseif ($fieldConfig['type'] == 'select' && is_array($fieldConfig['values.'])) {
				$newdata[$key] = $fieldConfig['values.'][$value];
			} elseif ($fieldConfig['type'] == 'select') {
				throw new Exception('Neither table nor value-list defined for select field ' . $key, 1304333953);
			}
			if ($typoScript[$key . '.']['type'] == 'static_info_tables') {
				$fieldConfig = $typoScript[$key . '.'];
				$field = $fieldConfig['field'];
				$valueHidden = $this->staticInfo->getStaticInfoName($field, $value);
				$newdata[$key] = $valueHidden;
			}
		}

		return $newdata;
	}

	/**
	 * Save an order in the given folder
	 * Order-ID has to be calculated beforehand!
	 *
	 * @param int $orderId Uid of the order
	 * @param int $pid Uid of the folder to save the order in
	 * @param object $basket Basket object of the user
	 * @param object $paymentObj Payment Object
	 * @param boolean $doHook Flag if the hooks should be executed
	 * @param boolean $doStock Flag if stockreduce should be executed
	 * @return array $orderData Array with all the order data
	 */
	public function saveOrder($orderId, $pid, $basket, $paymentObj, $doHook = TRUE, $doStock = TRUE) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// Save addresses with reference to the pObj - which is an instance of pi3
		$uids = array();
		$res = $database->exec_SELECTquery('name', 'tx_commerce_address_types', '1');
		while ($type = $database->sql_fetch_assoc($res)) {
			$uids[$type['name']] = $this->handleAddress($type['name']);
		}

			// Generate an order id on the fly if none was passed
		if (empty($orderId)) {
			$orderId = uniqid('', TRUE);
		}

			// create backend user for inserting the order data
		/** @var $backendUser t3lib_userAuth */
		$backendUser = t3lib_div::makeInstance('t3lib_userAuthGroup');
		$backendUser->user['uid'] = 0;
		$backendUser->user['username'] = '_cli_commerce';
		$backendUser->user['admin'] = TRUE;
		$backendUser->user['uc']['recursiveDelete'] = FALSE;

		$orderData = array();
		$orderData['cust_deliveryaddress'] = ((isset($uids['delivery'])) ? $uids['delivery'] : $uids['billing']);
		$orderData['cust_invoice'] = $uids['billing'];
		$orderData['paymenttype'] = $this->getPaymentType(TRUE);
		$orderData['sum_price_net'] = $basket->get_net_sum();
		$orderData['sum_price_gross'] = $basket->get_gross_sum();
		$orderData['order_sys_language_uid'] = $GLOBALS['TSFE']->config['config']['sys_language_uid'];
		$orderData['pid'] = $pid;
		$orderData['order_id'] = $orderId;
		$orderData['crdate'] = $GLOBALS['EXEC_TIME'];
		$orderData['tstamp'] = $GLOBALS['EXEC_TIME'];
		$orderData['cu_iso_3_uid'] = $this->conf['currencyId'];
		$orderData['comment'] = t3lib_div::removeXSS(strip_tags($this->piVars['comment']));

		if (is_array($GLOBALS['TSFE']->fe_user->user)) {
			$orderData['cust_fe_user'] = $GLOBALS['TSFE']->fe_user->user['uid'];
		}

			// Get hook objects
		$hookObjectsArr = array();
		if ($doHook) {
			$hookObjectsArr = $this->getHookObjectArray('finishIt');
				// Insert order
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'preinsert')) {
					$hookObj->preinsert($orderData, $this);
				}
			}
		}

		$this->debug($orderData, '$orderData', __FILE__ . ' ' . __LINE__);

		$tce = $this->getTceMain($pid);
		$data = array();
		if (isset($this->conf['lockOrderIdInGenerateOrderId']) && $this->conf['lockOrderIdInGenerateOrderId'] == 1) {
			$data['tx_commerce_orders'][(int) $this->orderUid] = $orderData;
			$tce->start($data, array(), $backendUser);
			$tce->process_datamap();
		} else {
			$newUid = uniqid('NEW');
			$data['tx_commerce_orders'][$newUid] = $orderData;
			$tce->start($data, array(), $backendUser);
			$tce->process_datamap();

			$this->orderUid = $tce->substNEWwithIDs[$newUid];
		}

			// make orderUid avaible in hookObjects
		$orderUid = $this->orderUid;

			// Call update method from the payment class
		$paymentObj->updateOrder($orderUid, $this->sessionData);

			// Insert order
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'modifyBasketPreSave')) {
				$hookObj->modifyBasketPreSave($basket, $this);
			}
		}

			// Save order articles
		if (is_array($basket->basket_items)) {
			/** @var $basketItem Tx_Commerce_Domain_Model_BasketItem */
			foreach ($basket->basket_items as $artUid => $basketItem) {
				/** @var $article Tx_Commerce_Domain_Model_Article */
				$article = $basketItem->article;

				$this->debug($article, '$article', __FILE__ . ' ' . __LINE__);

				$orderArticleData = array();
				$orderArticleData['pid'] = $orderData['pid'];
				$orderArticleData['crdate'] = $GLOBALS['EXEC_TIME'];
				$orderArticleData['tstamp'] = $GLOBALS['EXEC_TIME'];
				$orderArticleData['article_uid'] = $artUid;
				$orderArticleData['article_type_uid'] = $article->getArticleTypeUid();
				$orderArticleData['article_number'] = $article->getOrdernumber();
				$orderArticleData['title'] = $basketItem->getTitle();
				$orderArticleData['subtitle'] = $article->getSubtitle();
				$orderArticleData['price_net'] = $basketItem->getPriceNet();
				$orderArticleData['price_gross'] = $basketItem->getPriceGross();
				$orderArticleData['tax'] = $basketItem->getTax();
				$orderArticleData['amount'] = $basketItem->getQuantity();
				$orderArticleData['order_uid'] = $orderUid;
				$orderArticleData['order_id'] = $orderId;

				$this->debug($orderArticleData, '$orderArticleData', __FILE__ . ' ' . __LINE__);

				$newUid = 0;
				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'modifyOrderArticlePreSave')) {
						$hookObj->modifyOrderArticlePreSave($newUid, $orderArticleData, $this);
					}
				}
				if (($this->conf['useStockHandling'] == 1) && ($doStock == TRUE)) {
					$article->reduceStock($basketItem->getQuantity());
				}

				$newUid = uniqid('NEW');
				$data = array();
				$data['tx_commerce_order_articles'][$newUid] = $orderArticleData;
				$tce->start($data, array(), $backendUser);
				$tce->process_datamap();

				$newUid = $tce->substNEWwithIDs[$newUid];

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'modifyOrderArticlePostSave')) {
						$hookObj->modifyOrderArticlePostSave($newUid, $orderArticleData, $this);
					}
				}
			}
		}

		unset($backendUser);

		return $orderData;
	}

	/**
	 * @param integer $pid
	 * @return t3lib_TCEmain
	 */
	protected function getTceMain($pid) {
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->bypassWorkspaceRestrictions = TRUE;
		$tce->recInsertAccessCache['tx_commerce_orders'][$pid] = 1;
		$tce->recInsertAccessCache['tx_commerce_order_articles'][$pid] = 1;

		return $tce;
	}

	/**
	 * getStepAfter
	 * returns Name of the next step
	 * if no next step is found, it returns itself, the actual step
	 *
	 * @param string $step Step
	 * @return string
	 */
	public function getStepAfter($step) {
		$rev = array_flip($this->checkoutSteps);

		$nextStep = $this->checkoutSteps[++$rev[$step]];

		if (empty($nextStep)) {
			$result = $step;
		} else {
			$result = $nextStep;
		}

		return $result;
	}

	/**
	 * @param mixed $var
	 * @param string $header
	 * @param string $group
	 * @return void
	 */
	protected function debug($var, $header, $group) {
		if ($this->debug) {
			t3lib_utility_Debug::debug($var, $header, $group);
		}
	}

	/**
	 * @param string $type
	 * @return array
	 */
	public function getHookObjectArray($type) {
		$hookObjectsArr = array();

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi3/class.tx_commerce_pi3.php'][$type])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi3/class.tx_commerce_pi3.php\'][\'' . $type . '\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/CheckoutController.php\'][\'' . $type . '\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi3/class.tx_commerce_pi3.php'][$type] as $classRef) {
				$hookObjectsArr[] = & t3lib_div::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/CheckoutController.php'][$type])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/CheckoutController.php'][$type] as $classRef) {
				$hookObjectsArr[] = & t3lib_div::getUserObj($classRef);
			}
		}

		return $hookObjectsArr;
	}
}

class_alias('Tx_Commerce_Controller_CheckoutController', 'tx_commerce_pi3');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/CheckoutController.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/CheckoutController.php']);
}

?>
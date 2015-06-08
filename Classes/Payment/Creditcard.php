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
 * Credit card payment implementation
 *
 * @author 2005-2011 Thomas Hempel <thomas@work.de>
 */
class Tx_Commerce_Payment_Creditcard extends Tx_Commerce_Payment_PaymentAbstract {

	/**
	 * @var array Locallang array, only needed if individual fields are defined
	 */
	public $LOCAL_LANG = array();

	/**
	 * @var string Payment type
	 */
	protected $type = 'creditcard';

	/**
	 * @var string
	 */
	protected $scriptRelPath;

	/**
	 * @var array
	 */
	protected $formError = array();

	/**
	 * Determine if additional data is needed
	 *
	 * @return bool True if additional data is needed
	 */
	public function needAdditionalData() {
		$basePath = PATH_TXCOMMERCE . 'Resources/Private/Language/locallang_creditcard.xml';

		foreach ($this->parentObject->LOCAL_LANG as $llKey => $_) {
			$newLl = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($basePath, $llKey);
			$this->LOCAL_LANG[$llKey] = $newLl[$llKey];
		}

		if ($this->parentObject->altLLkey) {
			$tempLocalLang = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($basePath, $this->parentObject->altLLkey);
			$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(), $tempLocalLang);
		}

		if ($this->provider !== NULL) {
			return $this->provider->needAdditionalData();
		}

		return TRUE;
	}

	/**
	 * Check if provided data is ok
	 *
	 * @param array $formData Current form data
	 *
	 * @return bool TRUE if data is ok
	 */
	public function proofData(array $formData = array()) {
		/** @var $ccvs Tx_Commerce_Payment_Ccvs */
		$ccvs = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Payment_Ccvs');
		$result = $ccvs->validateCreditCard($formData['cc_number'], $formData['cc_checksum']);
		$this->errorMessages[] = $ccvs->CCVSError;

		$config['sourceFields.'] = $this->getAdditionalFieldsConfig($this->parentObject);

		foreach ($this->parentObject->sessionData['payment'] as $name => $value) {
			if ($config['sourceFields.'][$name . '.']['mandatory'] == 1 && strlen($value) == 0) {
				$this->formError[$name] = $this->parentObject->pi_getLL('error_field_mandatory');
				$result = FALSE;
			}

			$eval = explode(',', $config['sourceFields.'][$name . '.']['eval']);
			foreach ($eval as $method) {
				$method = explode('_', $method);
				switch (strtolower($method[0])) {
					case 'email':
						if (!\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($value)) {
							$this->formError[$name] = $this->parentObject->pi_getLL('error_field_email');
							$result = FALSE;
						}
						break;

					case 'username':
						if ($GLOBALS['TSFE']->loginUser) {
							break;
						}
						if (!$this->parentObject->checkUserName($value)) {
							$this->formError[$name] = $this->parentObject->pi_getLL('error_field_username');
							$result = FALSE;
						}
						break;

					case 'string':
						if (!is_string($value)) {
							$this->formError[$name] = $this->parentObject->pi_getLL('error_field_string');
							$result = FALSE;
						}
						break;

					case 'int':
						if (!is_integer($value)) {
							$this->formError[$name] = $this->parentObject->pi_getLL('error_field_int');
							$result = FALSE;
						}
						break;

					case 'min':
						if (strlen((string)$value) < (int) $method[1]) {
							$this->formError[$name] = $this->parentObject->pi_getLL('error_field_min');
							$result = FALSE;
						}
						break;

					case 'max':
						if (strlen((string)$value) > (int) $method[1]) {
							$this->formError[$name] = $this->parentObject->pi_getLL('error_field_max');
							$result = FALSE;
						}
						break;

					case 'alpha':
						if (preg_match('/[0-9]/', $value) === 1) {
							$this->formError[$name] = $this->parentObject->pi_getLL('error_field_alpha');
							$result = FALSE;
						}
						break;

					default:
				}
			}
		}

		unset($ccvs);

		if ($this->provider !== NULL) {
			return $this->provider->proofData($formData, $result);
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Creditcard.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Payment/Creditcard.php']);
}

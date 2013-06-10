<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Thomas Hempel (thomas@work.de)
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

/*
 * Credit card payment implementation
 *
 * @package commerce
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @author Thomas Hempel <thomas@work.de>
 */
class tx_commerce_payment_creditcard extends tx_commerce_payment_abstract {

	/**
	 * @var array Locallang array, only needed if individual fields are defined
	 */
	public $LOCAL_LANG = array();

	/**
	 * @var string Payment type
	 */
	protected $type = 'creditcard';

	/**
	 * Determine if additional data is needed
	 *
	 * @return bool True if additional data is needed
	 */
	public function needAdditionalData() {
		$basePath = t3lib_extMgm::extPath($this->pObj->extKey) . dirname($this->scriptRelPath) . 'payment/locallang_creditcard.xml';

		foreach($this->pObj->LOCAL_LANG as $llKey => $llData) {
			$newLL = t3lib_div::readLLfile($basePath, $llKey);
			$this->LOCAL_LANG[$llKey] = $newLL[$llKey];
		}

		if ($this->pObj->altLLkey) {
			$tempLOCAL_LANG = t3lib_div::readLLfile($basePath, $this->pObj->altLLkey);
			$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(), $tempLOCAL_LANG);
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
	 * @return bool TRUE if data is ok
	 */
	public function proofData(array $formData = array()) {
			/** @var $ccvs tx_commerce_payment_Ccvs */
		$ccvs = t3lib_div::makeInstance('tx_commerce_payment_Ccvs');
		$result = $ccvs->validateCreditCard($formData['cc_number'], $formData['cc_checksum']);
		$this->errorMessages[] = $ccvs->CCVSError;

		$config['sourceFields.'] = $this->getAdditionalFieldsConfig($this->pObj);

		foreach ($this->pObj->MYSESSION['payment'] as $name => $value) {
			if ($config['sourceFields.'][$name .'.']['mandatory'] == 1 && strlen($value) == 0) {
				$this->formError[$name] = $this->pObj->pi_getLL('error_field_mandatory');
				$result = FALSE;
			}

			$eval = explode(',', $config['sourceFields.'][$name .'.']['eval']);
			foreach ($eval as $method) {
				$method = explode('_', $method);
				switch (strtolower($method[0])) {
					case 'email':
						if (!t3lib_div::validEmail($value)) {
							$this->formError[$name] = $this->pObj->pi_getLL('error_field_email');
							$result = FALSE;
						}
						break;
					case 'username':
						if($GLOBALS['TSFE']->loginUser) {
							break;
						}
						if (!$this->pObj->checkUserName($value)) {
							$this->formError[$name] = $this->pObj->pi_getLL('error_field_username');
							$result = FALSE;
						}
						break;
					case 'string':
						if (!is_string($value)) {
							$this->formError[$name] = $this->pObj->pi_getLL('error_field_string');
							$result = FALSE;
						}
						break;
					case 'int':
						if (!is_integer($value)) {
							$this->formError[$name] = $this->pObj->pi_getLL('error_field_int');
							$result = FALSE;
						}
						break;
					case 'min':
						if (strlen((string)$value) < intval($method[1])) {
							$this->formError[$name] = $this->pObj->pi_getLL('error_field_min');
							$result = FALSE;
						}
						break;
					case 'max':
						if (strlen((string)$value) > intval($method[1])) {
							$this->formError[$name] = $this->pObj->pi_getLL('error_field_max');
							$result = FALSE;
						}
						break;
					case 'alpha':
						if (preg_match('/[0-9]/', $value) === 1) {
							$this->formError[$name] = $this->pObj->pi_getLL('error_field_alpha');
							$result = FALSE;
						}
						break;
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_creditcard.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/class.tx_commerce_payment_creditcard.php"]);
}
?>
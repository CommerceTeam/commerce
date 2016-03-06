<?php
namespace CommerceTeam\Commerce\Payment;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Credit card payment implementation.
 *
 * Class \CommerceTeam\Commerce\Payment\Creditcard
 *
 * @author 2005-2011 Thomas Hempel <thomas@work.de>
 */
class Creditcard extends PaymentAbstract
{
    /**
     * Locallang array, only needed if individual fields are defined.
     *
     * @var array
     */
    public $LOCAL_LANG = [];

    /**
     * Payment type.
     *
     * @var string
     */
    protected $type = 'creditcard';

    /**
     * Determine if additional data is needed.
     *
     * @return bool If additional data is needed true gets returned
     */
    public function needAdditionalData()
    {
        /** @var $languageFactory \TYPO3\CMS\Core\Localization\LocalizationFactory */
        $languageFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LocalizationFactory::class);

        $basePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('commerce')
            . 'Resources/Private/Language/locallang_creditcard.xlf';

        foreach ($this->parentObject->LOCAL_LANG as $llKey => $_) {
            $newLl = $languageFactory->getParsedData($basePath, $llKey);
            $this->LOCAL_LANG[$llKey] = $newLl[$llKey];
        }

        if ($this->parentObject->altLLkey) {
            $tempLocalLang = $languageFactory->getParsedData($basePath, $this->parentObject->altLLkey);
            $this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : [], $tempLocalLang);
        }

        if ($this->provider !== null) {
            return $this->provider->needAdditionalData();
        }

        return true;
    }

    /**
     * Check if provided data is ok.
     *
     * @param array $formData Current form data
     *
     * @return bool If data is ok true gets returned
     */
    public function proofData(array $formData = [])
    {
        /**
         * Credit card validation service.
         *
         * @var \CommerceTeam\Commerce\Payment\Ccvs $ccvs
         */
        $ccvs = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Payment\Ccvs::class);
        $result = $ccvs->validateCreditCard($formData['cc_number'], $formData['cc_checksum']);
        $this->errorMessages[] = $ccvs->CCVSError;

        $config['sourceFields.'] = $this->getAdditionalFieldsConfig();

        foreach ($this->parentObject->sessionData['payment'] as $name => $value) {
            if ($config['sourceFields.'][$name . '.']['mandatory'] == 1 && strlen($value) == 0) {
                $this->formError[$name] = $this->parentObject->pi_getLL('error_field_mandatory');
                $result = false;
            }

            $eval = explode(',', $config['sourceFields.'][$name . '.']['eval']);
            foreach ($eval as $method) {
                $method = explode('_', $method);
                switch (strtolower($method[0])) {
                    case 'email':
                        if (!GeneralUtility::validEmail($value)) {
                            $this->formError[$name] = $this->parentObject->pi_getLL('error_field_email');
                            $result = false;
                        }
                        break;

                    case 'username':
                        if ($this->getFrontendController()->loginUser) {
                            break;
                        }
                        if (!$this->parentObject->checkUserName($value)) {
                            $this->formError[$name] = $this->parentObject->pi_getLL('error_field_username');
                            $result = false;
                        }
                        break;

                    case 'string':
                        if (!is_string($value)) {
                            $this->formError[$name] = $this->parentObject->pi_getLL('error_field_string');
                            $result = false;
                        }
                        break;

                    case 'int':
                        if (!is_integer($value)) {
                            $this->formError[$name] = $this->parentObject->pi_getLL('error_field_int');
                            $result = false;
                        }
                        break;

                    case 'min':
                        if (strlen((string) $value) < (int) $method[1]) {
                            $this->formError[$name] = $this->parentObject->pi_getLL('error_field_min');
                            $result = false;
                        }
                        break;

                    case 'max':
                        if (strlen((string) $value) > (int) $method[1]) {
                            $this->formError[$name] = $this->parentObject->pi_getLL('error_field_max');
                            $result = false;
                        }
                        break;

                    case 'alpha':
                        if (preg_match('/[0-9]/', $value) === 1) {
                            $this->formError[$name] = $this->parentObject->pi_getLL('error_field_alpha');
                            $result = false;
                        }
                        break;

                    default:
                }
            }
        }

        unset($ccvs);

        if ($this->provider !== null) {
            return $this->provider->proofData($formData, $result);
        }

        return $result;
    }


    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}

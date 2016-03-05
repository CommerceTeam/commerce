<?php
namespace CommerceTeam\Commerce\Payment\Provider;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;

/**
 * Abstract payment provider implementation.
 *
 * Class \CommerceTeam\Commerce\Payment\Provider\ProviderAbstract
 *
 * @author 2009-2011 Volker Graubaum <vg@e-netconsulting.de>
 */
abstract class ProviderAbstract implements ProviderInterface
{
    /**
     * Error messages (keys are field names).
     *
     * @var array
     */
    public $errorMessages = array();

    /**
     * Payment object.
     *
     * @var \CommerceTeam\Commerce\Payment\PaymentInterface
     */
    protected $paymentObject;

    /**
     * Provider type, eg 'wirecard'.
     *
     * @var string
     */
    protected $type = '';

    /**
     * Criteria objects bound to this payment provider.
     *
     * @var array
     */
    protected $criteria = array();

    /**
     * Construct this payment provider.
     *
     * @param \CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject Payment
     */
    public function __construct(\CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject)
    {
        $this->paymentObject = $paymentObject;
        $this->loadCriteria();
    }

    /**
     * Load configured criteria.
     *
     * @return void
     * @throws \Exception If criteria was not of correct interface
     */
    protected function loadCriteria()
    {
        // Get and instantiate registered criteria of this payment provider
        $criteraConfigurations = ConfigurationUtility::getInstance()
            ->getConfiguration('SYSPRODUCTS.PAYMENT.types.' . $this->paymentObject->getType() . '.provider.' .
                $this->type . '.criteria');

        if (is_array($criteraConfigurations)) {
            foreach ($criteraConfigurations as $criterionConfiguration) {
                if (!is_array($criterionConfiguration['options'])) {
                    $criterionConfiguration['options'] = array();
                }
                /**
                 * Criterion.
                 *
                 * @var ProviderCriterionInterface
                 */
                $criterion = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    $criterionConfiguration['class'],
                    $this,
                    $criterionConfiguration['options']
                );
                if (!($criterion instanceof ProviderCriterionInterface)) {
                    throw new \Exception(
                        'Criterion ' . $criterionConfiguration['class'] .
                        ' must implement interface \CommerceTeam\Commerce\Payment\Provider\ProviderCriterionInterface',
                        1307720945
                    );
                }
                $this->criteria[] = $criterion;
            }
        }
    }

    /**
     * Get parent payment object.
     *
     * @return \CommerceTeam\Commerce\Payment\PaymentInterface Parent payment object
     */
    public function getPaymentObject()
    {
        return $this->paymentObject;
    }

    /**
     * Get provider type.
     *
     * @return string Provider type
     */
    public function getType()
    {
        return strtolower($this->type);
    }

    /**
     * Check if this payment provider is allowed for
     * the current amount, payment type etc.
     *
     * @return bool TRUE if provider is allowed
     */
    public function isAllowed()
    {
        $result = true;

        /**
         * Criterion.
         *
         * @var \CommerceTeam\Commerce\Payment\Criterion\CriterionAbstract $criterion
         */
        foreach ($this->criteria as $criterion) {
            if ($criterion->isAllowed() === false) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Determine if additional data is needed.
     *
     * @return bool TRUE if the provider should be queried for more data
     */
    public function needAdditionalData()
    {
        return true;
    }

    /**
     * Returns an array containing some configuration
     * for the fields the customer shall enter his data into.
     *
     * @return mixed NULL for no data
     */
    public function getAdditionalFieldsConfig()
    {
        return null;
    }

    /**
     * Check if provided data is ok.
     *
     * @param array $formData Current form data
     * @param bool $parentResult Already determined result of payment object
     *
     * @return bool TRUE if data is ok
     */
    public function proofData(array $formData = array(), $parentResult = true)
    {
        return $parentResult;
    }

    /**
     * Wether or not finishing an order is allowed.
     *
     * @param array $config Current configuration
     * @param array $session Session data
     * @param \CommerceTeam\Commerce\Domain\Model\Basket $basket Basket object
     *
     * @return bool TRUE if finishing order is allowed
     */
    public function finishingFunction(
        array $config = array(),
        array $session = array(),
        \CommerceTeam\Commerce\Domain\Model\Basket $basket = null
    ) {
        return true;
    }

    /**
     * Method called in finishIt function.
     *
     * @param array $globalRequest Global request
     * @param array $session Session array
     *
     * @return bool TRUE if data is ok
     */
    public function checkExternalData(array $globalRequest = array(), array $session = array())
    {
        return true;
    }

    /**
     * Update order data after order has been finished.
     *
     * @param int $orderUid Id of this order
     * @param array $session  Session data
     *
     * @return void
     */
    public function updateOrder($orderUid, array $session = array())
    {
    }

    /**
     * Get error message if form data was not ok.
     *
     * @return string error message
     */
    public function getLastError()
    {
        $errorMessages = '';

        foreach ($this->errorMessages as $message) {
            $errorMessages .= $message;
        }

        return $errorMessages;
    }
}

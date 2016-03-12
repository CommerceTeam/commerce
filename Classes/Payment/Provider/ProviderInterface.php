<?php
namespace CommerceTeam\Commerce\Payment\Provider;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Payment provider interface.
 *
 * Class \CommerceTeam\Commerce\Payment\Provider\ProviderInterface
 */
interface ProviderInterface
{
    /**
     * Constructor gets parent object.
     *
     * @param \CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject Payment
     */
    public function __construct(\CommerceTeam\Commerce\Payment\PaymentInterface $paymentObject);

    /**
     * Get parent object.
     *
     * @return \CommerceTeam\Commerce\Payment\PaymentInterface Parent object instance
     */
    public function getPaymentObject();

    /**
     * Get payment type.
     *
     * @return string
     */
    public function getType();

    /**
     * Return TRUE if this payment type is allowed.
     *
     * @return bool
     */
    public function isAllowed();

    /**
     * Determine if additional data is needed.
     *
     * @return bool True if additional data is needed
     */
    public function needAdditionalData();

    /**
     * Get configuration of additional fields.
     *
     * @return mixed|null
     */
    public function getAdditionalFieldsConfig();

    /**
     * Check if provided data is ok.
     *
     * @param array $formData Current form data
     * @param bool $parentResult Already determined result of payment object
     *
     * @return bool TRUE if data is ok
     */
    public function proofData(array $formData = [], $parentResult = true);

    /**
     * Wether or not finishing an order is allowed.
     *
     * @param array $config Current configuration
     * @param array $session Session data
     * @param \CommerceTeam\Commerce\Domain\Model\Basket $basket Basket object
     *
     * @return bool True is finishing order is allowed
     */
    public function finishingFunction(
        array $config = [],
        array $session = [],
        \CommerceTeam\Commerce\Domain\Model\Basket $basket = null
    );

    /**
     * Method called in finishIt function.
     *
     * @param array $globalRequest Global Request
     * @param array $session Session array
     *
     * @return bool TRUE if data is ok
     */
    public function checkExternalData(array $globalRequest = [], array $session = []);

    /**
     * Update order data after order has been finished.
     *
     * @param int $orderUid Id of this order
     * @param array $session  Session data
     *
     * @return void
     */
    public function updateOrder($orderUid, array $session = []);

    /**
     * Get error message if form data was not ok.
     *
     * @return string error message
     */
    public function getLastError();
}

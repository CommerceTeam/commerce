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

/**
 * Payment interface.
 *
 * Class \CommerceTeam\Commerce\Payment\PaymentInterface
 *
 * @author 2011 Christian Kuhn <lolli@schwarzbu.ch>
 */
interface PaymentInterface
{
    /**
     * Constructor gets parent object.
     *
     * @param \CommerceTeam\Commerce\Controller\BaseController $pObj Parent object
     */
    public function __construct(\CommerceTeam\Commerce\Controller\BaseController $pObj);

    /**
     * Get form errors
     *
     * @return array
     */
    public function getFormError();

    /**
     * Get parent object.
     *
     * @return \CommerceTeam\Commerce\Controller\BaseController Parent object
     */
    public function getParentObject();

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
     * Get payment provider.
     *
     * @return \CommerceTeam\Commerce\Payment\PaymentInterface
     */
    public function getProvider();

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
     *
     * @return bool TRUE if data is ok
     */
    public function proofData(array $formData = array());

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
    );

    /**
     * Method called in finishIt function.
     *
     * @param array $globalRequest Global request
     * @param array $session Session array
     *
     * @return bool TRUE if data is ok
     */
    public function checkExternalData(array $globalRequest = array(), array $session = array());

    /**
     * Update order data after order has been finished.
     *
     * @param int $orderUid Id of this order
     * @param array $session Session data
     *
     * @return void
     */
    public function updateOrder($orderUid, array $session = array());

    /**
     * Get error message if form data was not ok.
     *
     * @return string error message
     */
    public function getLastError();
}

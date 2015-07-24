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

use CommerceTeam\Commerce\Domain\Repository\OrderRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Wirecard payment provider implementation.
 *
 * Testing data:
 * Card type			Test number
 * Visa      			4111 1111 1111 1111
 * MasterCard			5500 0000 0000 0004
 * American Express		3400 0000 0000 009
 * Diner's Club			3000 0000 0000 04
 * Carte Blanche		3000 0000 0000 04
 * Discover				6011 0000 0000 0004
 * JCB					3088 0000 0000 0009
 *
 * Class \CommerceTeam\Commerce\Payment\Provider\Wirecard
 *
 * @author 2009-2011 Volker Graubaum <vg@e-netconsulting.de>
 */
class Wirecard extends ProviderAbstract
{
    /**
     * Provider type.
     *
     * @var string
     */
    protected $type = 'wirecard';

    /**
     * Payment type.
     *
     * @var string
     */
    public $LOCAL_LANG = array();

    /**
     * Payment reference id.
     *
     * @var string
     */
    public $paymentRefId;

    /**
     * Returns an array containing some configuration for
     * the fields the customer shall enter his data into.
     *
     * @return array
     */
    public function getAdditionalFieldsConfig()
    {
        return array(
            'cc_type.' => array(
                'mandatory' => 1,
                'type' => 'select',
                'values.' => array(
                    'Visa',
                    'Mastercard',
                    'Amercican Express',
                    'Diners Club',
                    'JCB',
                    'Switch',
                    'VISA Carte Bancaire',
                    'Visa Electron',
                    'UATP',
                ),
            ),
            'cc_number.' => array(
                'mandatory' => 1,
            ),
            'cc_expirationYear.' => array(
                'mandatory' => 1,
            ),
            'cc_expirationMonth.' => array(
                'mandatory' => 1,
            ),
            'cc_holder.' => array(
                'mandatory' => 1,
            ),
            'cc_checksum.' => array(
                'mandatory' => 1,
            ),
        );
    }

    /**
     * This method is called in the last step. Here can be made some final checks
     * or whatever is needed to be done before saving some data in the database.
     * Write any errors into $this->errorMessages!
     * To save some additional data in the database use the method updateOrder().
     *
     * @param array $config Configuration from TYPO3_CONF_VARS
     * @param array $session Current session data
     * @param \CommerceTeam\Commerce\Domain\Model\Basket $basket Basket object
     *
     * @return bool Check if everything was ok
     */
    public function finishingFunction(
        array $config = array(),
        array $session = array(),
        \CommerceTeam\Commerce\Domain\Model\Basket $basket = null
    ) {
        /**
         * Payment library.
         *
         * @var \CommerceTeam\Commerce\Payment\Payment $paymentLib
         */
        $paymentLib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Payment\\Payment');

        // I think there is a new URL for testing with wirecard, so overwrite
        // the old value. you can replace this with your own.
        $paymentLib->setUrl('https://c3-test.wirecard.com');
        $paymentLib->paymentmethod = 'creditcard';
        $paymentLib->paymenttype = 'cc';

        $paymentLib->setPaymentData(array(
            'kk_number' => $session['payment']['cc_number'],
            'exp_month' => $session['payment']['cc_expirationMonth'],
            'exp_year' => $session['payment']['cc_expirationYear'],
            'holder' => $session['payment']['cc_holder'],
            'cvc' => $session['payment']['cc_checksum'],
        ));

        $parent = $this->paymentObject->getParentObject();
        $actCurrency = $parent->conf['currency'] != '' ? $parent->conf['currency'] : 'EUR';

        $paymentLib->setTransactionData(array(
            'amount' => $basket->getSumGross(),
            'currency' => $actCurrency,
        ));

        $paymentLib->sendData = $paymentLib->getwirecardXML();

        $back = $paymentLib->sendTransaction();

        if (!$back) {
            $this->errorMessages = array_merge($this->errorMessages, (array) $paymentLib->getError());

            return false;
        } else {
            $this->paymentRefId = $paymentLib->referenzID;
            // The ReferenceID should be stored here, so that it can be
            // added to the record in updateOrder()
            return true;
        }
    }

    /**
     * Update order data after order has been finished.
     *
     * @param int $orderUid Id of this order
     * @param array $session Session data
     *
     * @return void
     */
    public function updateOrder($orderUid, array $session = array())
    {
        // Update order that was created by checkout process
        // With credit card payment a reference ID has to be stored in field
        // payment_ref_id (I have no idea where it comes from, maybe it is
        // given by wirecard?!)
        // To update the order something like this should be sufficient:
        // $this->paymentRefId should probably be set in finishingFunction()
        /**
         * Order repository.
         *
         * @var OrderRepository
         */
        $orderRepository = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Repository\\OrderRepository');
        $orderRepository->updateByUid(
            $orderUid,
            array('payment_ref_id' => $this->paymentRefId)
        );
    }
}

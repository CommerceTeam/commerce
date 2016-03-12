<?php
namespace CommerceTeam\Commerce\Payment;

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
use CommerceTeam\Commerce\Domain\Repository\OrderRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Debit payment implementation.
 *
 * Class \CommerceTeam\Commerce\Payment\Debit
 */
class Debit extends PaymentAbstract
{
    /**
     * Payment type.
     *
     * @var string
     */
    protected $type = 'debit';

    /**
     * Locallang array, only needed if individual fields are defined.
     *
     * @var array
     */
    public $LOCAL_LANG = [
        'default' => [
            'payment_debit_bic' => 'Bank Identification Number',
            'payment_debit_an' => 'Account number',
            'payment_debit_bn' => 'Bankname',
            'payment_debit_ah' => 'Account holder',
            'payment_debit_company' => 'Company',
        ],
        'de' => [
            'payment_debit_bic' => 'Bankleitzahl',
            'payment_debit_an' => 'Kontonummer',
            'payment_debit_bn' => 'Bankname',
            'payment_debit_ah' => 'Kontoinhaber',
            'payment_debit_company' => 'Firma',
        ],
        'fr' => [
            'payment_debit_bic' => 'Code de banque',
            'payment_debit_an' => 'Num�ro de compte',
            'payment_debit_bn' => 'Nom bancaire',
            'payment_debit_ah' => 'D�tenteur de compte',
            'payment_debit_company' => 'Firme',
        ],
    ];

    /**
     * Get configuration of additional fields.
     *
     * @return array
     *
     * @return void
     */
    public function getAdditionalFieldsConfig()
    {
        return [
            'debit_bic.' => [
                'mandatory' => 1,
            ],
            'debit_an.' => [
                'mandatory' => 1,
            ],
            'debit_bn.' => [
                'mandatory' => 1,
            ],
            'debit_ah.' => [
                'mandatory' => 1,
            ],
            'debit_company.' => [
                'mandatory' => 0,
            ],
        ];
    }

    /**
     * Check if provided data is ok.
     *
     * @param array $formData Current form data
     *
     * @return bool Check if data is ok
     */
    public function proofData(array $formData = [])
    {
        // If formData is empty we know that this is the very first call from
        // \CommerceTeam\Commerce\Controller\CheckoutController->handlePayment
        // and at this time there can't be form data.
        if (empty($formData)) {
            return false;
        }

        $config['sourceFields.'] = $this->getAdditionalFieldsConfig();

        $result = true;

        foreach ($formData as $name => $value) {
            if ($config['sourceFields.'][$name . '.']['mandatory'] == 1 && strlen($value) == 0) {
                $result = false;
            }
        }

        if ($this->provider !== null) {
            return $this->provider->proofData($formData, $result);
        }

        return $result;
    }

    /**
     * Update order data after order has been finished.
     *
     * @param int $orderUid Id of this order
     * @param array $session  Session data
     *
     * @return void
     */
    public function updateOrder($orderUid, array $session = [])
    {
        /**
         * Order repository.
         *
         * @var OrderRepository
         */
        $orderRepository = GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Repository\OrderRepository::class
        );
        $orderRepository->updateByUid(
            $orderUid,
            [
                'payment_debit_bic' => $session['payment']['debit_bic'],
                'payment_debit_an' => $session['payment']['debit_an'],
                'payment_debit_bn' => $session['payment']['debit_bn'],
                'payment_debit_ah' => $session['payment']['debit_ah'],
                'payment_debit_company' => $session['payment']['debit_company'],
            ]
        );
    }
}

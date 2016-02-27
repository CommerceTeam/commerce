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

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'tx_commerce_salesfigures',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'readOnly' => '1',
        'adminOnly' => '1',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_salesfigures.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'year, month, day, dow, hour, pricegross, pricenet, amount, orders',
    ],
    'interface' => [
        'showRecordFieldList' => 'year, month, day, dow, hour, pricegross, pricenet, amount, orders',
    ],
    'columns' => [
        'year' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.year',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'month' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.month',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'day' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.day',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'dow' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.dow',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'hour' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.hour',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'pricegross' => [
            'exclude' => 1,
            'label' =>
                $languageFile . 'tx_commerce_salesfigures.pricegross',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'pricenet' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.pricenet',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'amount' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.amount',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'orders' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_salesfigures.orders',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                year, month, day, dow, hour, pricegross, pricenet, amount, orders
            '
        ],
    ],
];

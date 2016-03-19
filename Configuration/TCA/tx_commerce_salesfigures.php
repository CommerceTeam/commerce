<?php

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

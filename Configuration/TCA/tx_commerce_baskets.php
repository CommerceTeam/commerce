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
        'title' => $languageFile . 'tx_commerce_baskets',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_baskets.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'sid, finished_time, article_id,price_id, price_gross, price_net, quantity',
    ],
    'interface' => [
        'showRecordFieldList' => 'sid, article_id, price_gross, price_net, quantity',
    ],
    'columns' => [
        'sid' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_baskets.sid',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ],
        ],
        'article_id' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_baskets.article_id',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_articles',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'price_id' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_baskets.price_id',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_article_prices',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'price_gross' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_baskets.price_gross',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2,nospace',
            ],
        ],
        'price_net' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_baskets.price_net',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'double2,nospace',
            ],
        ],
        'quantity' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_baskets.quantity',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '5000',
                    'lower' => '0',
                ],
                'default' => 0,
            ],
        ],
        'finished_time' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_basket.finished_time',
            'config' => [
                'type' => 'input',
                'eval' => 'date',
            ],
        ],
        'readonly' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_basket.readonly',
            'config' => [
                'type' => 'check',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'sid, article_id, price_id, price_gross, price_net, quantity, finished_time, readonly'
        ],
    ],
];

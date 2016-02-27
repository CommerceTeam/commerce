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
        'title' => $languageFile . 'tx_commerce_article_prices',
        'label' => 'price_net',
        'label_alt' => 'price_net,price_gross,purchase_price',
        'label_alt_force' => 1,
        'label_userFunc' => \CommerceTeam\Commerce\Domain\Model\ArticlePrice::class . '->getTcaRecordTitle',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        // @todo check if this works without further versioning configuration
        'versioning' => '1',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_article_prices.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'hidden, starttime, endtime, fe_group, price_gross, price_net, purchase_price,
            price_scale_amount_start, price_scale_amount_end',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden, starttime, endtime, fe_group, price_gross, price_net, purchase_price,
            price_scale_amount_start, price_scale_amount_end',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 50,
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login', -1],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.any_login', -2],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.usergroups', '--div--'],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            ],
        ],

        'price_gross' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_article_prices.price_gross',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'nospace',
            ],
        ],

        'price_net' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_article_prices.price_net',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'nospace',
            ],
        ],

        'purchase_price' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_article_prices.purchase_price',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'nospace',
            ],
        ],

        'price_scale_amount_start' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.price_scale_amount_start',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'int,nospace,required',
                'range' => ['lower' => 1],
                'default' => '1',
            ],
        ],

        'price_scale_amount_end' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.price_scale_amount_end',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'int,nospace,required',
                'range' => ['lower' => 1],
                'default' => '1',
            ],
        ],

        'uid_article' => [
            'exclude' => 1,
            'label' => 'Article UID',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\Utility\ArticleCreatorUtility::class . '->articleUid',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                price_gross, price_net, purchase_price, 
                price_scale_amount_start, price_scale_amount_end, uid_article,
                hidden,
                --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access
            ',
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => 'starttime, endtime, --linebreak--, fe_group',
        ],
    ],
];

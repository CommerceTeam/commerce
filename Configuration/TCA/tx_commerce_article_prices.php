<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'label' => 'price_net',
        'label_alt' => 'price_net,price_gross,purchase_price',
        'label_alt_force' => 1,
        'label_userFunc' => \CommerceTeam\Commerce\Domain\Model\ArticlePrice::class . '->getTcaRecordTitle',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_article_prices',
        'delete' => 'deleted',
        'versioningWS' => true,
        'default_sortby' => 'ORDER BY crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_article_prices.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'price_gross, price_net, purchase_price,
            price_scale_amount_start, price_scale_amount_end, starttime, endtime, fe_group',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
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
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        -1
                    ],
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        -2
                    ],
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'enableMultiSelectFilterTextfield' => true
            ]
        ],

        'price_gross' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_article_prices.price_gross',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'nospace,double2',
            ]
        ],

        'price_net' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_article_prices.price_net',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'nospace,double2',
            ]
        ],

        'purchase_price' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_article_prices.purchase_price',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'nospace,double2',
            ]
        ],

        'price_scale_amount_start' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.price_scale_amount_start',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'int,nospace,required',
                'range' => ['lower' => 1],
                'default' => 1,
            ]
        ],

        'price_scale_amount_end' => [
            'exclude' => 1,
            'label' => $languageFile . 'tx_commerce_articles.price_scale_amount_end',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'int,nospace,required',
                'range' => ['lower' => 1],
                'default' => 1,
            ]
        ],

        'uid_article' => [
            'exclude' => 1,
            'label' => 'Article UID',
            'config' => [
                'readOnly' => 1,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_commerce_articles',
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;price,
                    --palette--;;scale,
                    uid_article,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access
            '
        ],
    ],
    'palettes' => [
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
            ',
        ],
        'access' => [
            'showitem' => '
                starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
                --linebreak--,
                fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel
            ',
        ],
        'price' => [
            'showitem' => '
                price_gross, price_net,
                --linebreak--,
                purchase_price
            ',
        ],
        'scale' => [
            'showitem' => '
                price_scale_amount_start, price_scale_amount_end
            ',
        ],
    ],
];

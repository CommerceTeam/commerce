<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . ':tx_commerce_order_articles',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_order_articles.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'article_type_uid, article_uid, article_number,
            title, subtitle, price_net, price_gross, tax, amount, order_id',
    ],
    'interface' => [
        'showRecordFieldList' => 'amount, title, article_type_uid, article_uid,
            article_number, subtitle, price_net, price_gross, tax, order_uid',
    ],
    'columns' => [
        'tstamp' => [
            'label' => $languageFile . ':tx_commerce_order_articles.tstamp',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'crdate' => [
            'label' => $languageFile . ':tx_commerce_order_articles.crdate',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'format' => 'date',
                'eval' => 'date',
            ],
        ],
        'article_uid' => [
            'label' => $languageFile . ':tx_commerce_order_articles.article_uid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_commerce_articles',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'article_type_uid' => [
            'label' => $languageFile . ':tx_commerce_order_articles.article_type_uid',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'article_number' => [
            'label' => $languageFile . ':tx_commerce_order_articles.article_number',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
            ],
        ],
        'title' => [
            'label' => $languageFile . ':tx_commerce_order_articles.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'required,trim',
            ],
        ],
        'subtitle' => [
            'label' => $languageFile . ':tx_commerce_order_articles.subtitle',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'price_net' => [
            'label' => $languageFile . ':tx_commerce_order_articles.price_net',
            'config' => [
                'type' => 'input',
                'size' => '6',
                'eval' => 'integer',
            ],
        ],
        'price_gross' => [
            'label' => $languageFile . ':tx_commerce_order_articles.price_gross',
            'config' => [
                'type' => 'input',
                'size' => '6',
                'eval' => 'integer',
            ],
        ],
        'tax' => [
            'label' => $languageFile . ':tx_commerce_order_articles.tax',
            'config' => [
                'type' => 'input',
                'size' => '6',
                'eval' => 'integer',
            ],
        ],
        'amount' => [
            'label' => $languageFile . ':tx_commerce_order_articles.amount',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'eval' => 'required,num',
            ],
        ],
        'order_uid' => [
            'label' => $languageFile . ':tx_commerce_order_articles.order_uid',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_commerce_orders',
                'readOnly' => true,
            ],
        ],
        // @todo Declaration for iproc function for selecting right value
        'order_id' => [
            'label' => $languageFile . ':tx_commerce_order_articles.order_id',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditFunc::class . '->articleOrderId',
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                order_id, article_type_uid, article_uid, article_number,
                title, subtitle, amount, price_net, price_gross, tax
            '
        ],
    ],
];

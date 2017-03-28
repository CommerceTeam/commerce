<?php

$languageFile = 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'label' => 'order_id',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'title' => $languageFile . 'tx_commerce_orders',
        'delete' => 'deleted',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_orders.gif',
        'searchFields' => 'order_id, comment',
    ],
    'interface' => [
        'showRecordFieldList' => 'cust_deliveryaddress, order_type_uid, order_id, cust_fe_user, cust_invoice,
            paymenttype, sum_price_net, sum_price_gross, payment_ref_id, cu_iso_3_uid, order_sys_language_uid,
            pricefromnet',
    ],
    'columns' => [
        'cust_deliveryaddress' => [
            'label' => $languageFile . 'tx_commerce_orders.cust_deliveryaddress',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditViewhelper::class . '->deliveryAddress',
            ]
        ],
        'order_type_uid' => [
            'label' => $languageFile . 'tx_commerce_orders.order_type_uid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['' => 0],
                ],
                'foreign_table' => 'tx_commerce_order_types',
                'default' => '',
            ]
        ],
        'order_id' => [
            'label' => $languageFile . 'tx_commerce_orders.order_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ]
        ],

        'tstamp' => [
            'label' => $languageFile . 'tx_commerce_orders.tstamp',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'readOnly' => true,
                'eval' => 'datetime',
            ]
        ],
        'crdate' => [
            'label' => $languageFile . 'tx_commerce_orders.crdate',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'readOnly' => true,
                'eval' => 'datetime',
            ]
        ],

        'newpid' => [
            'label' => $languageFile . 'tx_commerce_orders.newpid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'pages',
                'itemsProcFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditViewhelper::class . '->orderStatus',
            ]
        ],
        'cust_fe_user' => [
            'label' => $languageFile . 'tx_commerce_orders.cust_fe_user',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => [
                    '_PADDING' => 1,
                    '_VERTICAL' => 1,
                ],
                'fieldControl' => [
                    'editPopup' => [
                        'options' => [
                            'title' => 'Edit',
                            'windowOpenParameters' => 'width=800,height=600,status=0,menubar=0,scrollbars=1',
                        ]
                    ]
                ],
            ]
        ],
        'cust_invoice' => [
            'label' => $languageFile . 'tx_commerce_orders.cust_invoice',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditViewhelper::class . '->invoiceAddress',
            ],
        ],
        'paymenttype' => [
            'label' => $languageFile . 'tx_commerce_orders.paymenttype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_commerce_articles',
                'foreign_table_where' => ' AND tx_commerce_articles.article_type_uid = 2',
            ]
        ],
        // @todo check if calculation and stored values are correct
        'sum_price_net' => [
            'label' => $languageFile . 'tx_commerce_orders.sum_price_net',
            'config' => [
                'type' => 'input',
                'eval' => \CommerceTeam\Commerce\Evaluation\FloatEvaluator::class,
            ]
        ],
        'sum_price_gross' => [
            'label' => $languageFile . 'tx_commerce_orders.sum_price_gross',
            'config' => [
                'type' => 'input',
                'eval' => \CommerceTeam\Commerce\Evaluation\FloatEvaluator::class,
            ]
        ],
        'articles' => [
            'label' => $languageFile . 'tx_commerce_orders.articles',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditViewhelper::class . '->orderArticles',
            ]
        ],
        'payment_ref_id' => [
            'label' => $languageFile . 'tx_commerce_orders.payment_ref_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'pass_content' => 1,
            ]
        ],
        'cu_iso_3_uid' => [
            'label' => $languageFile . 'tx_commerce_orders.cu_iso_3_uid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'static_currencies',
                'foreign_table_where' => ' ',
                'default' => '49',
            ]
        ],
        'comment' => [
            'label' => $languageFile . 'tx_commerce_orders.comment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ]
        ],
        'internalcomment' => [
            'label' => $languageFile . 'tx_commerce_orders.internalcomment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ]
        ],
        'order_sys_language_uid' => [
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.php:LGL.default_value', 0],
                ],
            ]
        ],
        'pricefromnet' => [
            'label' => $languageFile . 'tx_commerce_orders.pricefromnet',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:no', 0],
                    ['LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:yes', 1],
                ],
            ]
        ],

    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;' . $languageFile . 'tx_commerce_orders.basis,
                    order_id, crdate, order_type_uid, cu_iso_3_uid, newpid, paymenttype, payment_ref_id,
                    comment, internalcomment, order_sys_language_uid, pricefromnet,
                --div--;' . $languageFile . 'tx_commerce_orders.customer,
                    cust_fe_user, cust_invoice,
                --div--;' . $languageFile . 'tx_commerce_orders.cust_deliveryaddress,
                    cust_deliveryaddress,
                --div--;' . $languageFile . 'tx_commerce_orders.items,
                    sum_price_net, sum_price_gross, articles
            ',
        ],
    ],
];

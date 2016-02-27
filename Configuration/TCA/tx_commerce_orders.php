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
        'title' => $languageFile . 'tx_commerce_orders',
        'label' => 'order_id',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'dividers2tabs' => '1',
        'iconfile' => 'EXT:commerce/Resources/Public/Icons/tx_commerce_orders.gif',
        'searchFields' => 'order_id, comment',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'cust_deliveryaddress, order_type_uid, order_id, cust_fe_user, cust_invoice,
            paymenttype, sum_price_net, sum_price_gross,pid,cu_iso_3_uid,order_sys_language_uid,pricefromnet',
    ],
    'interface' => [
        'showRecordFieldList' => 'cust_deliveryaddress, order_type_uid, order_id, cust_fe_user, cust_invoice,
            paymenttype, sum_price_net, sum_price_gross, crdate, pid, payment_ref_id, cu_iso_3_uid,
            order_sys_language_uid, pricefromnet',
    ],
    'columns' => [
        'cust_deliveryaddress' => [
            'label' => $languageFile . 'tx_commerce_orders.cust_deliveryaddress',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditFunc::class . '->deliveryAddress',
            ],
        ],
        'order_type_uid' => [
            'label' => $languageFile . 'tx_commerce_orders.order_type_uid',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['' => 0],
                ],
                'foreign_table' => 'tx_commerce_order_types',
                'default' => '',
            ],
        ],
        'order_id' => [
            'label' => $languageFile . 'tx_commerce_orders.order_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],

        'tstamp' => [
            'label' => $languageFile . 'tx_commerce_orders.tstamp',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'crdate' => [
            'label' => $languageFile . 'tx_commerce_orders.crdate',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'format' => 'date',
                'eval' => 'date',
            ],
        ],

        'newpid' => [
            'label' => $languageFile . 'tx_commerce_orders.pid',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'pages',
                'itemsProcFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditFunc::class . '->orderStatus',
            ],
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
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit user',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'cust_invoice' => [
            'label' => $languageFile . 'tx_commerce_orders.cust_invoice',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditFunc::class . '->invoiceAddress',
            ],
        ],
        'paymenttype' => [
            'label' => $languageFile . 'tx_commerce_orders.paymenttype',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_commerce_articles',
                'foreign_table_where' => ' AND tx_commerce_articles.article_type_uid = 2',
            ],
        ],
        'sum_price_net' => [
            'label' => $languageFile . 'tx_commerce_orders.sum_price_net',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditFunc::class . '->orderArticles',
            ],
        ],
        'sum_price_gross' => [
            'label' => $languageFile . 'tx_commerce_orders.sum_price_gross',
            'config' => [
                'type' => 'user',
                'userFunc' => \CommerceTeam\Commerce\ViewHelpers\OrderEditFunc::class . '->sumPriceGrossFormat',
            ],
        ],
        'payment_ref_id' => [
            'label' => $languageFile . 'tx_commerce_orders.payment_ref_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'pass_content' => 1,
            ],
        ],
        'cu_iso_3_uid' => [
            'label' => $languageFile . 'tx_commerce_orders.cu_iso_3_uid',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'static_currencies',
                'foreign_table_where' => ' ',
                'default' => '49',
            ],
        ],
        'comment' => [
            'label' => $languageFile . 'tx_commerce_orders.comment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'internalcomment' => [
            'label' => $languageFile . 'tx_commerce_orders.internalcomment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'order_sys_language_uid' => [
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.php:LGL.default_value', 0],
                ],
            ],
        ],
        'pricefromnet' => [
            'label' => $languageFile . 'tx_commerce_orders.pricefromnet',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:no', 0],
                    ['LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:yes', 1],
                ],
            ],
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

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

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders',
        'label' => 'order_id',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'dividers2tabs' => '1',
        'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'orders.gif',
        'searchFields' => 'order_id, comment',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'cust_deliveryaddress, order_type_uid, order_id, cust_fe_user, cust_invoice,
            paymenttype, sum_price_net, sum_price_gross,pid,cu_iso_3_uid,order_sys_language_uid,pricefromnet',
    ),
    'interface' => array(
        'showRecordFieldList' => 'cust_deliveryaddress, order_type_uid, order_id, cust_fe_user, cust_invoice,
            paymenttype, sum_price_net, sum_price_gross, crdate, pid, payment_ref_id, cu_iso_3_uid,
            order_sys_language_uid, pricefromnet',
    ),
    'columns' => array(
        'cust_deliveryaddress' => array(
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_deliveryaddress',
            'config' => array(
                'type' => 'user',
                'userFunc' => 'CommerceTeam\\Commerce\\ViewHelpers\\OrderEditFunc->deliveryAddress',
            ),
        ),
        'order_type_uid' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.order_type_uid',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('' => 0),
                ),
                'foreign_table' => 'tx_commerce_order_types',
                'default' => '',
            ),
        ),
        'order_id' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.order_id',
            'config' => array(
                'type' => 'input',
                'readOnly' => true,
            ),
        ),

        'tstamp' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.tstamp',
            'config' => array(
                'type' => 'input',
                'readOnly' => true,
            ),
        ),

        'crdate' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.crdate',
            'config' => array(
                'type' => 'input',
                'readOnly' => true,
                'format' => 'date',
                'eval' => 'date',
            ),
        ),

        'newpid' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.pid',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'pages',
                'itemsProcFunc' => 'CommerceTeam\\Commerce\\ViewHelpers\\OrderEditFunc->orderStatus',
            ),
        ),
        'cust_fe_user' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_fe_user',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => array(
                    '_PADDING' => 1,
                    '_VERTICAL' => 1,
                    'edit' => array(
                        'type' => 'popup',
                        'title' => 'Edit user',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ),
                ),
            ),
        ),
        'cust_invoice' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_invoice',
            'config' => array(
                'type' => 'user',
                'userFunc' => 'CommerceTeam\\Commerce\\ViewHelpers\\OrderEditFunc->invoiceAddress',
            ),
        ),
        'paymenttype' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.paymenttype',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'tx_commerce_articles',
                'foreign_table_where' => ' AND tx_commerce_articles.article_type_uid = 2',
            ),
        ),
        'sum_price_net' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.sum_price_net',
            'config' => array(
                'type' => 'user',
                'userFunc' => 'CommerceTeam\\Commerce\\ViewHelpers\\OrderEditFunc->orderArticles',
            ),
        ),
        'sum_price_gross' => array(
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.sum_price_gross',
            'config' => array(
                'type' => 'user',
                'userFunc' => 'CommerceTeam\\Commerce\\ViewHelpers\\OrderEditFunc->sumPriceGrossFormat',
            ),
        ),
        'payment_ref_id' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.payment_ref_id',
            'config' => array(
                'type' => 'input',
                'readOnly' => true,
                'pass_content' => 1,
            ),
        ),
        'cu_iso_3_uid' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cu_iso_3_uid',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'static_currencies',
                'foreign_table_where' => ' ',
                'default' => '49',
            ),
        ),
        'comment' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.comment',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ),
        ),
        'internalcomment' => array(
            'label' =>
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.internalcomment',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ),
        ),
        'order_sys_language_uid' => array(
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0),
                ),
            ),
        ),
        'pricefromnet' => array(
            'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.pricefromnet',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:no', 0),
                    array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:yes', 1),
                ),
            ),
        ),

    ),
    'types' => array(
        '0' => array(
            'showitem' => '
                --div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.basis,
                    order_id, crdate, order_type_uid, cu_iso_3_uid, newpid, paymenttype, payment_ref_id, comment,
                    internalcomment, order_sys_language_uid, pricefromnet,
                --div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.customer,
                    cust_fe_user, cust_invoice,
           --div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_deliveryaddress,
                    cust_deliveryaddress,
                --div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.items,
                    sum_price_net, sum_price_gross, articles',
        ),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);

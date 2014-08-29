<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2008 Ingo Schmitt <typo3@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Dynamic config file for tx_commerce_orders
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_orders'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_orders']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'cust_deliveryaddress,order_type_uid,order_id,cust_fe_user,cust_invoice,paymenttype,sum_price_net,sum_price_gross,crdate,pid,payment_ref_id,cu_iso_3_uid,order_sys_language_uid,pricefromnet'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_orders']['feInterface'],
	'columns' => Array(
		'cust_deliveryaddress' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_deliveryaddress',
			'config' => Array(
				'type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->deliveryAddress',
			)
		),
		'order_type_uid' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.order_type_uid',
			'config' => Array(
				'type' => 'select',
				'items' => Array(
					Array('' => 0),
				),
				'foreign_table' => 'tx_commerce_order_types',
				'default' => '',
			)
		),
		'order_id' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.order_id',
			'config' => Array(
				'type' => 'input',
				'readOnly' => TRUE,
			)
		),

		'tstamp' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.tstamp',
			'config' => Array(
				'type' => 'input',
				'readOnly' => TRUE,
			)
		),

		'crdate' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.crdate',
			'config' => Array(
				'type' => 'input',
				'readOnly' => TRUE,
				'format' => 'date',
				'eval' => 'date',
			)
		),

		'newpid' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.pid',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'pages',
				'itemsProcFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->orderStatus',
			)
		),
		'cust_fe_user' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_fe_user',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => Array(
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
		'cust_invoice' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_invoice',
			'config' => Array(
				'type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->invoiceAddress',
			)
		),
		'paymenttype' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.paymenttype',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_articles',
				'foreign_table_where' => ' AND tx_commerce_articles.article_type_uid = 2',
			)
		),
		'sum_price_net' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.sum_price_net',
			'config' => Array(
				'type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->orderArticles',
			)
		),
		'sum_price_gross' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.sum_price_gross',
			'config' => Array(
				'type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->sumPriceGrossFormat',
			)
		),
		'payment_ref_id' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.payment_ref_id',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
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
		'comment' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.comment',
			'config' => Array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'internalcomment' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.internalcomment',
			'config' => Array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'order_sys_language_uid' => Array(
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
				)
			)
		),
		'pricefromnet' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.pricefromnet',
			'config' => array(
				'type' => 'select',
				'items' => Array(
					Array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:no', 0),
					Array('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:yes', 1)
				)
			),
		),

	),
	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.basis,
					order_id, crdate, order_type_uid, cu_iso_3_uid, newpid, paymenttype, payment_ref_id, comment, internalcomment,
					order_sys_language_uid, pricefromnet,
				--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.customer,
					cust_fe_user, cust_invoice,
				--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.cust_deliveryaddress,
					cust_deliveryaddress,
				--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_orders.items,
					sum_price_net, sum_price_gross, articles'
		)
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);

?>
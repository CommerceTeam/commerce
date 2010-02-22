<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2008 Ingo Schmitt <is@marketing-factory.de>
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
 *
 * @package commerce
 * @author Ingo Schmitt <is@marketing-factory.de>
 * 
 * $Id: tx_commerce_orders.tca.php 532 2007-02-01 14:19:15Z ingo $
 */
 
 
 if(!defined('TYPO3_MODE')) die("Access denied.");
 
 
 $TCA['tx_commerce_orders'] = Array (
	'ctrl' => $TCA['tx_commerce_orders']['ctrl'],
	
	'interface' => Array (
		'showRecordFieldList' => 'cust_deliveryaddress,order_type_uid,order_id,cust_fe_user,cust_invoice,paymenttype,sum_price_net,sum_price_gross,crdate,pid,payment_ref_id,cu_iso_3_uid,order_sys_language_uid,pricefromnet'
	),
	'feInterface' => $TCA['tx_commerce_orders']['feInterface'],
	'columns' => Array (
		'cust_deliveryaddress' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.cust_deliveryaddress',
			'config' => Array (
				'type' => 'user',
				'userFunc' => 'user_orderedit_func->delivery_adress',
			)
				
		),
		'order_type_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.order_type_uid',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('' => 0),
				),
				'foreign_table' => 'tx_commerce_order_types',
				'default' => '',
			)
		),
		'order_id' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.order_id',
			'config' => Array (
				'type' => 'none',
				'pass_content' => 1,
			)
		),

		'crdate' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.crdate',
			'config' => Array (
				'type' => 'none',
				'format' => 'date',
				'eval' => 'date',
		
			)
		),
		
	
		'newpid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.pid',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'pages',
				'itemsProcFunc' =>'user_orderedit_func->order_status',
				/**
				 * @see mod_orders:class.user_orderedit_func.php
				 */
				/**
				 * Dummy sql, for selecting nothing
				 */
				#'foreign_table_where' => 'AND -1 = 1'
			)
		),
		'cust_fe_user' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.cust_fe_user',
			'config' => Array (
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
		'cust_invoice' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.cust_invoice',
			'config' => Array (
				'type' => 'user',
				'userFunc' => 'user_orderedit_func->invoice_adress',
			)
		), 
		'paymenttype' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.paymenttype',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tx_commerce_articles',
				'foreign_table_where' => ' AND tx_commerce_articles.article_type_uid = 2',
			)
		),
		'sum_price_net' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.sum_price_net',
			'config' => Array (
				'type' => 'user',
				'userFunc' => 'user_orderedit_func->order_articles',
		
			)
		),
		'sum_price_gross' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.sum_price_gross',
			'config' => Array (
				'type' => 'user',
				'userFunc' => 'user_orderedit_func->sum_price_gross_format',
			)
		),
		'payment_ref_id' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.payment_ref_id',
			'config' => array (
				'type' => 'none',
				'pass_content' => 1,
			),
		),
		'cu_iso_3_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.cu_iso_3_uid',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'static_currencies',
				'foreign_table_where' => ' ',
				'default' => '49',
			),
		),
		'comment' => Array (
                         'exclude' => 1,
			 'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.comment',
	         'config' => Array (
    		     'type' => 'text',
	             'cols' => '30',
	             'rows' => '5',
	),
		),
		'internalcomment' => Array (
                         'exclude' => 1,
			 'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.internalcomment',
	         'config' => Array (
    		     'type' => 'text',
	             'cols' => '30',
	             'rows' => '5',
			 ),
		),
		'order_sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'pricefromnet' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.pricefromnet',
			'config' => array (
				'type' => 'select',
				'items' => Array(
					Array('LLL:EXT:commerce/locallang_be.xml:no',0),
					Array('LLL:EXT:commerce/locallang_be.xml:yes',1)
				)
			),
		),
		
	),
	'types' => array (
		'0' => array('showitem' => 
				'--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.basis,'.
				'order_id;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.order_id;;;2-2-2,' .
				'crdate;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.crdate;;;, ' .
				'order_type_uid;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.order_type_uid;;;2-2-2,  ' .
				'cu_iso_3_uid;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.cu_iso_3_uid;;;, ' .
				'newpid;LLL:EXT:commerce/locallang_be.php:order_view.order_status;;;, ' .
				'paymenttype;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.paymenttype;;;, ' .
				'payment_ref_id;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.payment_ref_id;;,comment,internalcomment, ' .
				'order_sys_language_uid;LLL:EXT:lang/locallang_general.php:LGL.language;;;,' .
				'pricefromnet;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.pricefromnet;;;,' .
				'--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.customer, '.
				'cust_fe_user, ' .
				'cust_invoice, ' .
				'--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.cust_deliveryaddress,'.
				'cust_deliveryaddress, ' .
				'--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_orders.items, '.
				
				'sum_price_net, ' .
				'sum_price_gross, ' .
				'articles')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_commerce_order_types'] = array (
	'ctrl' => $TCA['tx_commerce_order_types']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'feInterface' => $TCA['tx_commerce_order_types']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table' => 'tx_commerce_order_types',
				'foreign_table_where' => 'AND tx_commerce_order_types.pid=###CURRENT_PID### AND tx_commerce_order_types.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'title' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_order_types.title',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'icon' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_order_types.icon',
			'l10n_mode' => 'mergeIfNotBlank',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_commerce',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'show_thumbs' => 1,
			),
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2,icon')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);


/**
 * Includion of Class for editing
 */
 if (TYPO3_MODE=='BE') {
     require_once(t3lib_extMgm::extPath(COMMERCE_EXTkey).'mod_orders/class.user_orderedit_func.php');
 }
 ?>
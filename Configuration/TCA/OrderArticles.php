<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2005 - 2006 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Dynamic config file for tx_commerce_order_articles
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_order_articles'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_order_articles']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'amount,title,article_type_uid,article_uid,article_number,subtitle,price_net,price_gross,tax,order_uid',
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_order_articles']['feInterface'],
	'columns' => Array(
		'article_type_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_type_uid',
			'config' => Array(
				'type' => 'passthrough',
			),
		),
		'order_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.order_uid',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_orders',
			)
		),
		'article_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_uid',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_articles',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'article_number' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_number',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.subtitle',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'price_net' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.price_net',
			'config' => Array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'price_gross' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.price_gross',
			'config' => Array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'tax' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.tax',
			'config' => Array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'amount' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.amount',
			'config' => Array(
				'type' => 'input',
				'size' => '2',
				'eval' => 'required,num',
			)
		),
		/**
		 * @TODO Declaration for iproc function for selecting right value
		 */
		'order_id' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.order_id',
			'config' => Array(
				'type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->articleOrderId',
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'order_id;;;;1-1-1,article_type_uid , article_uid, article_number, title;;;;2-2-2, subtitle,  amount;;;;3-3-3, price_net, price_gross, tax')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

?>
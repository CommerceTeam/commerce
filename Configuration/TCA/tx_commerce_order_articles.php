<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'order_articles.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'article_type_uid, article_uid, article_number,
			title, subtitle, price_net, price_gross, tax, amount, order_id',
	),
	'interface' => Array(
		'showRecordFieldList' => 'amount, title, article_type_uid, article_uid, article_number, subtitle, price_net, price_gross,
			tax, order_uid',
	),
	'columns' => Array(
		'tstamp' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.tstamp',
			'config' => Array(
				'type' => 'input',
				'readOnly' => TRUE,
			)
		),
		'crdate' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.crdate',
			'config' => Array(
				'type' => 'input',
				'readOnly' => TRUE,
				'format' => 'date',
				'eval' => 'date',
			)
		),
		'article_uid' => Array(
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
		'article_type_uid' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_type_uid',
			'config' => Array(
				'type' => 'input',
				'readOnly' => TRUE,
			)
		),
		'article_number' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_number',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'title' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.subtitle',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'price_net' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.price_net',
			'config' => Array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'price_gross' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.price_gross',
			'config' => Array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'tax' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.tax',
			'config' => Array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'amount' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.amount',
			'config' => Array(
				'type' => 'input',
				'size' => '2',
				'eval' => 'required,num',
			)
		),
		'order_uid' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.order_uid',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_orders',
				'readOnly' => TRUE,
			)
		),
		/**
		 * @todo Declaration for iproc function for selecting right value
		 */
		'order_id' => Array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.order_id',
			'config' => Array(
				'type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_OrderEditFunc->articleOrderId',
				'readOnly' => TRUE,
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'order_id;;;;1-1-1, article_type_uid, article_uid, article_number, title;;;;2-2-2, subtitle,
			amount;;;;3-3-3, price_net, price_gross, tax')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

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
	'interface' => array(
		'showRecordFieldList' => 'amount, title, article_type_uid, article_uid, article_number, subtitle, price_net, price_gross,
			tax, order_uid',
	),
	'columns' => array(
		'tstamp' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.tstamp',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
			)
		),
		'crdate' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.crdate',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
				'format' => 'date',
				'eval' => 'date',
			)
		),
		'article_uid' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_uid',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_articles',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'article_type_uid' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_type_uid',
			'config' => array(
				'type' => 'input',
				'readOnly' => TRUE,
			)
		),
		'article_number' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.article_number',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'title' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.title',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.subtitle',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'price_net' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.price_net',
			'config' => array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'price_gross' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.price_gross',
			'config' => array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'tax' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.tax',
			'config' => array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'integer',
			)
		),
		'amount' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.amount',
			'config' => array(
				'type' => 'input',
				'size' => '2',
				'eval' => 'required,num',
			)
		),
		'order_uid' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.order_uid',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_orders',
				'readOnly' => TRUE,
			)
		),
		// @todo Declaration for iproc function for selecting right value
		'order_id' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_order_articles.order_id',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'CommerceTeam\\Commerce\\ViewHelpers\\OrderEditFunc->articleOrderId',
				'readOnly' => TRUE,
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'order_id;;;;1-1-1, article_type_uid, article_uid, article_number, title;;;;2-2-2, subtitle,
			amount;;;;3-3-3, price_net, price_gross, tax')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);

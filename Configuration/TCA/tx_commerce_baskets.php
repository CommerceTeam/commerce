<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'baskets.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sid, finished_time, article_id,price_id, price_gross, price_net, quantity',
	),
	'interface' => Array(
		'showRecordFieldList' => 'sid,article_id,price_gross,price_net,quantity'
	),
	'columns' => Array(
		'sid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.sid',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'article_id' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.article_id',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_articles',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'price_id' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.price_id',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_article_prices',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'price_gross' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.price_gross',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'double2,nospace',
			)
		),
		'price_net' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.price_net',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'double2,nospace',
			)
		),
		'quantity' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_baskets.quantity',
			'config' => Array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array(
					'upper' => '5000',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'finished_time' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_basket.finished_time',
			'config' => Array(
				'type' => 'input',
				'eval' => 'date',
			)
		),
		'readonly' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_basket.readonly',
			'config' => array(
				'type' => 'check',
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'sid;;;;1-1-1, article_id,price_id, price_gross, price_net, quantity')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

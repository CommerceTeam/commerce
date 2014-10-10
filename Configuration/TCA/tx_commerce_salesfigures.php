<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'readOnly' => '1',
		'adminOnly' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'salesfigures.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'year, month, day, dow, hour, pricegross, pricenet, amount, orders',
	),
	'interface' => Array(
		'showRecordFieldList' => 'year,month,day,dow,hour,pricegross, pricenet,amount,orders'
	),
	'columns' => Array(
		'year' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.year',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'month' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.month',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'day' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.day',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'dow' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.dow',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'hour' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.hour',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'pricegross' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.pricegross',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'pricenet' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.pricenet',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'amount' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.amount',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'orders' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.orders',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'year;;;;1-1-1, month, day, dow, hour, pricegross, pricenet, amount, orders')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

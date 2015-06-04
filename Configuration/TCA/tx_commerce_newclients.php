<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'readOnly' => '1',
		'adminOnly' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'newclients.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'year, month, day, dow, hour, registration',
	),
	'interface' => Array(
		'showRecordFieldList' => 'year,month,day,dow,hour,registration'
	),
	'columns' => Array(
		'year' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.year',
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
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.month',
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
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.day',
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
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.dow',
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
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.hour',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'registration' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_newclients.registration',
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
		'0' => Array('showitem' => 'year;;;;1-1-1, month, day, dow, hour, registration')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

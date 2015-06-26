<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'tracking.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'orders_uid, trackingcodes_uid, msg',
	),
	'interface' => array(
		'showRecordFieldList' => 'orders_uid,trackingcodes_uid,msg'
	),
	'columns' => array(
		'orders_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking.orders_uid',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_orders',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'trackingcodes_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking.trackingcodes_uid',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_trackingcodes',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'msg' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking.msg',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'orders_uid;;;;1-1-1, trackingcodes_uid, msg')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);

<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_trackingcodes',
		'label' => 'description',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'tracking_codes.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title, description',
	),
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title,description'
	),
	'columns' => Array(
		'sys_language_uid' => Array(
			'exclude' => 1,
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
		'l18n_parent' => Array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array(
				'type' => 'select',
				'items' => Array(
					Array('', 0),
				),
				'foreign_table' => 'tx_commerce_trackingcodes',
				'foreign_table_where' => 'AND tx_commerce_trackingcodes.pid=###CURRENT_PID###
					AND tx_commerce_trackingcodes.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array(
			'config' => Array(
				'type' => 'passthrough'
			)
		),
		'title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_trackingcodes.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'description' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_trackingcodes.description',
			'config' => Array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '10',
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => '
			sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2,
			description;;;richtext:rte_transform[flag=rte_enabled|mode=ts_css]
		')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);
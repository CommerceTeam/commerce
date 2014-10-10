<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_address_types',
		'label' => 'title',
		'readOnly' => 1,
		'adminOnly' => 1,
		'rootLevel' => 1,
		'is_static' => 1,
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'address_types.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, title',
	),
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
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
				'foreign_table' => 'tx_commerce_address_types',
				'foreign_table_where' =>
					'AND tx_commerce_address_types.pid=###CURRENT_PID### AND tx_commerce_address_types.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array(
			'config' => Array(
				'type' => 'passthrough'
			)
		),
		'title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_address_types.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'name' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_address_types.name',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

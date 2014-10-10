<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY title,uid',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dividers2tabs' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'manufacturer.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo',
	),
	'interface' => Array(
		'showRecordFieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo'
	),
	'columns' => Array(
		'title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'street' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.street',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'number' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.number',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'zip' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.zip',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'city' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.city',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'country' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.country',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'static_countries',
				'foreign_table_where' => 'ORDER BY static_countries.cn_short_en',
			)
		),
		'phone' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.phone',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'fax' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.fax',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'email' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.email',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'internet' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.internet',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'contactperson' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.contactperson',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'logo' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.logo',
			'l10n_mode' => 'mergeIfNotBlank',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_commerce',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 5,
			)
		),
	),
	'types' => Array(
		'0' => Array(
			'showitem' => '
			hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson,logo;;;;2-2-2'
			),
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

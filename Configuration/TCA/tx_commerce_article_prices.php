<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices',
		'label' => 'price_net',
		'label_alt' => 'price_net,price_gross,purchase_price',
		'label_alt_force' => 1,
		'label_userFunc' => 'CommerceTeam\\Commerce\\Domain\\Model\\ArticlePrice->getTcaRecordTitle',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => PATH_TXCOMMERCE . 'Configuration/TCA/ArticlePrices.php',
		'dividers2tabs' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'price.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime,
			fe_group, price_gross, price_net, price_scale_amount, purchase_price',
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden, starttime, endtime, fe_group, price_gross, price_net, purchase_price,
			price_scale_amount_start, price_scale_amount_end',
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),

		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),

		'endtime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
				)
			)
		),

		'fe_group' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => array(
				'type' => 'select',
				'size' => 5,
				'maxitems' => 50,
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',

			)
		),

		'price_gross' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices.price_gross',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'nospace',
			)
		),

		'price_net' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices.price_net',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'nospace',
			)
		),

		'purchase_price' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices.purchase_price',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'nospace',
			)
		),

		'price_scale_amount_start' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.price_scale_amount_start',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'eval' => 'int,nospace,required',
				'range' => array('lower' => 1),
				'default' => '1',
			)
		),

		'price_scale_amount_end' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.price_scale_amount_end',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'eval' => 'int,nospace,required',
				'range' => array('lower' => 1),
				'default' => '1',
			)
		),

		'uid_article' => array(
			'exclude' => 1,
			'label' => 'Article UID',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'CommerceTeam\\Commerce\\Utility\\ArticleCreatorUtility->articleUid',
			),
		),
	),
	'types' => array(
		'0' => array(
			'showitem' => '
			hidden;;1, price_gross, price_net, price_scale_amount_start, price_scale_amount_end, purchase_price;;;;3-3-3, uid_article'
		),
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime, fe_group')
	)
);

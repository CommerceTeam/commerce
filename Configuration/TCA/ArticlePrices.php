<?php

$GLOBALS['TCA']['tx_commerce_article_prices'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_article_prices']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,price_gross,price_net,purchase_price,price_scale_amount_start,price_scale_amount_end',
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_articles']['feInterface'],
	'columns' => Array(
		'hidden' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array(
				'type' => 'check',
				'default' => '0'
			)
		),

		'starttime' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),

		'endtime' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
				)
			)
		),

		'fe_group' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array(
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

		'price_gross' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices.price_gross',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'nospace',
			)
		),

		'price_net' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices.price_net',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'nospace',
			)
		),

		'purchase_price' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_prices.purchase_price',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'nospace',
			)
		),

		'price_scale_amount_start' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.price_scale_amount_start',
			'config' => Array(
				'type' => 'input',
				'size' => '10',
				'eval' => 'int,nospace,required',
				'range' => array('lower' => 1),
				'default' => '1',
			)
		),

		'price_scale_amount_end' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.price_scale_amount_end',
			'config' => Array(
				'type' => 'input',
				'size' => '10',
				'eval' => 'int,nospace,required',
				'range' => array('lower' => 1),
				'default' => '1',
			)
		),

		'uid_article' => Array(
			'exclude' => 1,
			'label' => 'Article UID',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'Tx_Commerce_Utility_ArticleCreatorUtility->articleUid',
			),
		),
	),
	'types' => Array(
		'0' => Array(
			'showitem' => '
			hidden;;1, price_gross, price_net, price_scale_amount_start, price_scale_amount_end, purchase_price;;;;3-3-3, uid_article'
		),
	),
	'palettes' => Array(
		'1' => Array('showitem' => 'starttime, endtime, fe_group')
	)
);

?>
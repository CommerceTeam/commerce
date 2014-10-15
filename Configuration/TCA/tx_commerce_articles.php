<?php
$GLOBALS['TCA']['tx_commerce_articles'] = Array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'sortby' => 'sorting',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY sorting,crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dividers2tabs' => '1',
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'article.gif',
	),
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title,
			subtitle, navtitle, description_extra, plain_text, price_gross, price_net, purchase_price, tax, article_type_uid,
			products_uid'
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime,
			fe_group, title, subtitle, navtitle, images, ordernumber, eancode, description_extra,plain_text,
			price_gross, price_net, purchase_price, tax, article_type_uid, products_uid, article_attributes',
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
				'foreign_table' => 'tx_commerce_articles',
				'foreign_table_where' => 'AND tx_commerce_articles.pid=###CURRENT_PID### AND tx_commerce_articles.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array(
			'config' => Array(
				'type' => 'passthrough'
			)
		),
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
		'title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.subtitle',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'navtitle' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.navtitle',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'images' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.images',
			'l10n_mode' => 'mergeIfNotBlank',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_commerce',
				'show_thumbs' => 1,
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 200,
				'autoSizeMax' => 40,
			)
		),
		'ordernumber' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.ordernumber',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'eancode' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.eancode',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'input',
				'size' => '20',
				'max' => '20',
				'eval' => 'trim',
			)
		),
		'description_extra' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.description_extra',
			'config' => Array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 2,
					'RTE' => Array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php',
					),
				),
			)
		),
		'plain_text' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.plain_text',
			'config' => Array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '10',
			)
		),
		'prices' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.prices',
			'l10n_mode' => 'exclude',
			'config' => array(
				'type' => 'inline',
				'appearance' => array(
					'newRecordLinkAddTitle' => TRUE,
					'levelLinksPosition' => 'bottom',
							),
				'foreign_table' => 'tx_commerce_article_prices',
				'foreign_field' => 'uid_article',
			),
		),
		'tax' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.tax',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'double2,nospace',
			)
		),
		'supplier_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_supplier.title',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_supplier',
				'items' => Array(
					Array(
						'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.noManufacturer',
						0
					)
				)
			)
		),
		'article_type_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.article_type_uid',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_article_types',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 1,
			),
		),
		'relatedpage' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.relatedpage',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'uid_product' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.products_uid',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_products',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'attributesedit' => array(
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.edit_attributes',
			'l10n_display' => 'hideDiff',
			'config' => Array(
				'type' => 'flex',
				'ds' => Array(
					'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<ROOT>
		<type>array</type>
		<el>
			<dummy>
				<TCEforms>
					<config>
						<type>input</type>
					</config>
				</TCEforms>
			</dummy>
		</el>
	</ROOT>
</T3DataStructure>'
				),
			),
		),
	),
	'types' => Array(
		'0' => Array(
			'showitem' => '
			hidden;;1, title, subtitle, ordernumber,eancode,
			description_extra;;;richtext:rte_transform[flag=rte_enabled|mode=ts_cssimgpath=uploads/tx_commerce/rte/], images,
			plain_text, tax, supplier_uid, article_type_uid, relatedpage;;;;1-1-1, products_uid, article_attributes,' .
				(
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode'] ?
					'' :
					'--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.edit_attributes,
						attributesedit,'
				) .
			'--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_articles.prices, prices'
		),
	),
	'palettes' => Array(
		'1' => Array('showitem' => 'starttime, endtime, fe_group')
	)
);

return $GLOBALS['TCA']['tx_commerce_articles'];

<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Dynamic config file for tx_commerce_products
 *
 * @author 2005-2010 Ingo Schmitt <is@marketing-factory.de>
 */
$GLOBALS['TCA']['tx_commerce_products'] = Array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'sortby' => 'sorting',
		'cruser_id' => 'cruser_id',
		'versioning' => '1',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'versioningWS' => TRUE,
		'delete' => 'deleted',
		'thumbnail' => 'images',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'products.gif',
		'dividers2tabs' => '1',
	),
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title,
			subtitle, navtitle, description, images, teaser, teaserimages, manufacturer_uid'
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
			title, subtitle, navtitle, description, images, teaser, teaserimages, categories, manufacturer_uid, attributes',
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
				'foreign_table' => 'tx_commerce_products',
				'foreign_table_where' => 'AND tx_commerce_products.pid=###CURRENT_PID### AND tx_commerce_products.sys_language_uid IN (-1,0)',
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
			'exclude' => 0,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '255',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.subtitle',
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
		'keywords' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.keywords',
			'config' => Array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'description' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.description',
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
		'images' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.images',
			'l10n_mode' => 'mergeIfNotBlank',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_commerce',
				'show_thumbs' => 1,
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 200,
				'autoSizeMax' => 40,
			)
		),
		'teaser' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.teaser',
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
			),
		),
		'teaserimages' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.teaserimages',
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
		'categories' => Array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.categories',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'passthrough',
				'allowed' => 'tx_commerce_categories',
				'form_type' => 'user',
				'userFunc' => 'Tx_Commerce_ViewHelpers_TceFunc->getSingleField_selectCategories',
				'treeView' => 1,
				'treeClass' => 'Tx_Commerce_ViewHelpers_TceFunc_CategoryTree',
				'size' => 7,
				'autoSizeMax' => 10,
				'minitems' => 1,
				'maxitems' => 20,
			),
		),

		'manufacturer_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_manufacturer.title',
			'l10n_mode' => 'exclude',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_manufacturer',
				'foreign_table_where' => 'ORDER BY tx_commerce_manufacturer.title ASC',
				'items' => Array(
					Array('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.noManufacturer', 0)
				)
			)
		),
		'relatedpage' => Array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.relatedpage',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'relatedproducts' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.relatedproducts',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_products',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 20,
				'MM' => 'tx_commerce_products_related_mm',
				'foreign_table' => 'tx_commerce_products',
			)
		),
		'attributes' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.attributes',
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
	</ROOT>
</T3DataStructure>'
				),
			),
		),
		'attributesedit' => Array(
			'exclude' => 1,
			'l10n_display' => 'hideDiff',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.attributes',
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
	</ROOT>
</T3DataStructure>'
				),
			),
		),
		'articles' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.articles',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<sheets>
		<sEXISTING>
			<ROOT>
				<TCEforms>
					<sheetTitle>LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.existing_articles</sheetTitle>
				</TCEforms>
				<type>array</type>
				<el>
					<existingArticles>
						<TCEforms>
							<config>
								<type>user</type>
								<userFunc>Tx_Commerce_Utility_ArticleCreatorUtility->existingArticles</userFunc>
							</config>
						</TCEforms>
					</existingArticles>
				</el>
			</ROOT>
		</sEXISTING>
		<sCREATE>
			<ROOT>
				<TCEforms>
				<sheetTitle>LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.producible_articles</sheetTitle>
				</TCEforms>
				<type>array</type>
				<el>
					<producibleArticles>
						<TCEforms>
							<config>
								<type>user</type>
								<userFunc>Tx_Commerce_Utility_ArticleCreatorUtility->producibleArticles</userFunc>
							</config>
						</TCEforms>
					</producibleArticles>
				</el>
			</ROOT>
		</sCREATE>
	</sheets>
</T3DataStructure>',
				),
			),
		),

		'articleslok' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.articleslok',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => '
<T3DataStructure>
	<meta>
		<langDisable>1</langDisable>
	</meta>
	<sheets>
		<sEXISTING>
			<ROOT>
				<TCEforms>
					<sheetTitle>LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.existing_articles</sheetTitle>
				</TCEforms>
				<type>array</type>
				<el>
					<existingArticles>
						<TCEforms>
							<config>
								<type>user</type>
								<userFunc>Tx_Commerce_Utility_ArticleCreatorUtility->existingArticles</userFunc>
							</config>
						</TCEforms>
					</existingArticles>
				</el>
			</ROOT>
		</sEXISTING>
	</sheets>
</T3DataStructure>',
				),
			),
		),
	),
	'types' => Array(
		'0' => Array(
			'showitem' => '
				sys_language_uid, l18n_parent, l18n_diffsource,
				title, subtitle, navtitle, keywords,
				images, teaserimages,
				description;;;richtext:rte_transform[flag=rte_enabled|mode=ts_cssimgpath=uploads/tx_commerce/rte/],
				teaser;;;richtext:rte_transform[flag=rte_enabled|mode=ts_cssimgpath=uploads/tx_commerce/rte/],
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tabs.references,
					categories, manufacturer_uid, relatedpage, relatedproducts'
		)
	),
	'palettes' => Array(
		'1' => Array('showitem' => 'starttime, endtime, --linebreak--, fe_group'),
		'access' => Array('showitem' => 'starttime, endtime, --linebreak--, fe_group'),
		'visibility' => Array('showitem' => 'hidden'),
	)
);

$simpleMode = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode'];
if ($simpleMode) {
	$GLOBALS['TCA']['tx_commerce_products']['columns']['articles'] = array(
		'exclude' => 1,
		'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.articles',
		'config' => array(
			'type' => 'inline',
			'foreign_table' => 'tx_commerce_articles',
			'foreign_field' => 'uid_product',
			'minitems' => 0,
		),
	);
	$GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem'] = str_replace(
		'articleslok',
		'articles',
		$GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem']
	);
}

return $GLOBALS['TCA']['tx_commerce_products'];

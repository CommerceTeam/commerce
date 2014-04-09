<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2010 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Dynamic config file for tx_commerce_products
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_products'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_products']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group,title,subtitle,navtitle,description,images,teaser,teaserimages,manufacturer_uid'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_products']['feInterface'],
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
			'exclude' => 0,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.categories',
			'config' => Array(
				'type' => 'passthrough',
				'form_type' => 'user',
					// TYPO3 core will require_once the file automatically when needed
				'userFunc' => 'Tx_Commerce_ViewHelpers_TceFunc->getSingleField_selectCategories',
				'treeViewBrowseable' => TRUE,
				'size' => 10,
				'autoSizeMax' => 30,
				'minitems' => 0,
				'maxitems' => 20,
				'eval' => 'required',
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
						</T3DataStructure>
					'
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
						</T3DataStructure>
					'
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
						</T3DataStructure>
					',
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
						</T3DataStructure>
					',
				),
			),
		),
	),
	'types' => Array(
		'0' => Array(
			'showitem' => '
				sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, title;;;;1-1-1, subtitle;;;;3-3-3, navtitle,
				keywords, description;;;richtext:rte_transform[flag=rte_enabled|mode=ts_cssimgpath=uploads/tx_commerce/rte/],
				images, teaser;;;richtext:rte_transform[flag=rte_enabled|mode=ts_cssimgpath=uploads/tx_commerce/rte/],
				teaserimages, categories;;;;4-4-4, manufacturer_uid;;;;2-2-2, relatedpage;;;;1-1-1, relatedproducts;;;;1-1-1'
		)
	),
	'palettes' => Array(
		'1' => Array('showitem' => 'starttime, endtime, fe_group')
	)
);

/**
 * Only perform from TCA if the BE form is called the first time
 * ('First time' also means calling the editform of an product),
 * no data has to be saved and extension dynaflex is available
 */

$postEdit = t3lib_div::_GP('edit');
$postData = t3lib_div::_GP('data');
if (is_array($postEdit['tx_commerce_products']) && (($postData == NULL) || (t3lib_div::_GP('createArticles') == 'create')) &&
	t3lib_extMgm::isLoaded('dynaflex')) {
		// Load the configuration from a file
	/** @noinspection PhpIncludeInspection */
	require_once(t3lib_extMgm::extPath('commerce') . 'Configuration/DCA/Product.php');
	$uid = array_keys($postEdit['tx_commerce_products']);
	if ($postEdit['tx_commerce_products'][$uid[0]] == 'new') {
		$uid = 0;
	} else {
		$uid = $uid[0];
	}

	$dynaFlexConf[0]['uid'] = $uid;
	$dynaFlexConf[1]['uid'] = $uid;

		// And start the dynyflex processing
	/** @noinspection PhpIncludeInspection */
	require_once(t3lib_extMgm::extPath('dynaflex') . 'class.dynaflex.php');
	/** @var dynaflex $dynaflex */
	$dynaflex = t3lib_div::makeInstance('dynaflex', $GLOBALS['TCA'], $dynaFlexConf);
		// write back the modified TCA
	$GLOBALS['TCA'] = $dynaflex->getDynamicTCA();
}

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
	$GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem'] = str_replace('articleslok', 'articles', $GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem']);
}

?>
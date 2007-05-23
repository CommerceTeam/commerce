<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Thomas Hempel <thomas@work.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * Dynamic config file for tx_commerce_articles
 *
 * @package commerce
 * @author Thomas Hempel <thomas@work.de>
 * 
 * $Id: tx_commerce_categories.tca.php 577 2007-03-27 18:22:11Z ingo $
 */
 
 
if(!defined('TYPO3_MODE')) die("Access denied.");

        // field templates for usage in other tables to link categories
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_tcefunc_categorytree.php');
require_once(t3lib_extmgm::extPath('graytree') .'lib/class.tx_graytree_tcefunc.php');

	// needed only for the leaf classes to be shown in the TCE category tree
require_once(PATH_txcommerce.'lib/class.tx_commerce_leafproductdata.php');
require_once(PATH_txcommerce.'lib/class.tx_commerce_leafproductview.php');

		


$TCA['tx_commerce_categories'] = Array (
	'ctrl' => $TCA['tx_commerce_categories']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group,title,subtitle,navtitle,description,images,keywords'
	),
	'feInterface' => $TCA['tx_commerce_categories']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_commerce_categories',
				'foreign_table_where' => 'AND tx_commerce_categories.pid=###CURRENT_PID### AND tx_commerce_categories.sys_language_uid IN (-1,0)',
			)
			
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (
			'exclude' => 1,
			
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'fe_group' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
					Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'extendToSubpages' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.extendToSubpages',
			'config' => Array (
				'type' => 'check'
			)
		),
		'title' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.subtitle',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'description' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.description',
			'config' => Array (
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
		'images' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.images',
			'l10n_mode' => 'mergeIfNotBlank',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_commerce',
				'show_thumbs' => 1,
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 10,
			)
		),
		'navtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.navtitle',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'keywords' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.keywords',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'parent_category' => Array (
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.parent_category',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_categories',
				'form_type' => 'user',
				'userFunc' => 'tx_graytree_tceFunc->getSingleField_selectTree',
				'treeView' => 1,
				'treeClass' => 'tx_commerce_tceFunc_categoryTree',
				'leafInfoArray' => Array (
				),
				'size' => 7,
				'autoSizeMax' => 10,
				'minitems' => 0,
				'maxitems' => 100,
			),
		), 
		'ts_config' => Array (
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.ts_config',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '10',
			),
		),

		'attributes' => Array (
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.attributes',
			'config' => Array (
				'type' => 'flex',
				'ds' => Array (
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
	),
	'types' => Array (
		'0' => Array('showitem' => '
			sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, title;;;;2-2-2, subtitle;;;;3-3-3, description;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], images, navtitle, keywords,parent_category;;;;1-1-1, relatedpage;;;;1-1-1, ts_config;;;;1-1-1,
			--div--;LLL:EXT:commerce/locallang_db.xml:tx_commerce_categories.select_attributes,attributes;;;;1-1-1'
		)
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group, extendToSubpages')
	)
);


// Only perform from TCA if the BE form is called the first time ('First time' also means
// calling the editform of an product), no data has to be saved and extension dynaflex is
// available (of course!)
$postEdit = t3lib_div::_GP('edit');
$postData = t3lib_div::_GP('data');
if (is_array($postEdit['tx_commerce_categories']) &&
	($postData == NULL) && 
	t3lib_extMgm::isLoaded('dynaflex')
	) {
	// Load the configuration from a file
	require_once(t3lib_extMgm::extPath('commerce') .'ext_df_category_config.php');
	$uid = array_keys($postEdit['tx_commerce_categories']);
	$dynaFlexConf[0]['uid'] = $uid[0];
	
	// And start the dynyflex processing
	require_once(t3lib_extMgm::extPath('dynaflex') .'class.dynaflex.php');
	$dynaflex = new dynaflex($TCA, $dynaFlexConf);
	
	// write back the modified TCA
	$TCA = $dynaflex->getDynamicTCA();
}

?>
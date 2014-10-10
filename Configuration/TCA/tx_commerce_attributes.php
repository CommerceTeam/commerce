<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2005 - 2010 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Dynamic config file for tx_commerce_attributes
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_attributes'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes',
		'label' => 'internal_title',
		'label_alt' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'versioning' => '1',
		'dividers2tabs' => '1',
		'requestUpdate' => 'has_valuelist',
		'languageField' => 'sys_language_uid',
		'thumbnail' => 'icon',
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
		'typeicon_column' => 'has_valuelist',
		'typeicons' => array(
			'0' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'attributes_free.gif',
			'1' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'attributes_list.gif'
		),
		'iconfile' => PATH_TXCOMMERCE_ICON_TABLE_REL . 'attributes_free.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
			has_valuelist, title, internal_title, unit, valueformat, valuelist',
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group,
			has_valuelist, title, internal_title, unit, valuelist, icon'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_commerce_attributes',
				'foreign_table_where' =>
					'AND tx_commerce_attributes.pid=###CURRENT_PID### AND tx_commerce_attributes.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
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

		'has_valuelist' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.has_valuelist',
			'displayCond' => 'FIELD:sys_language_uid:=:0',
			'config' => array(
				'type' => 'check',
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.title',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'internal_title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.internal_title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '160',
				'eval' => 'trim',
			)
		),
		'unit' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.unit',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'valueformat' => array(
			'exclude' => 1,
			'displayCond' => 'FIELD:has_valuelist:=:0',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.valueformat',
			'l10n_mode' => 'exclude',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', ''),
					array('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.valueformat.money  ', '%01.2f'),
					array('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.valueformat.integer  ', '%d'),
					array('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.valueformat.float  ', '%f'),
				)
			)
		),
		'valuelist' => array(
			'exclude' => 1,
			'displayCond' => 'FIELD:has_valuelist:=:1',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.valuelist',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_commerce_attribute_values',
				'foreign_field' => 'attributes_uid',
				'foreign_sortby' => 'sorting',
				'foreign_label' => 'value',

				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
					'useSortable' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
				),
			)
		),
		'multiple' => array(
			'exclude' => 1,
			'displayCond' => 'FIELD:has_valuelist:=:1',
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.multiple',
			'l10n_mode' => 'exclude',
			'config' => array(
				'type' => 'check',
			),
		),
		'icon' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.icon',
			'l10n_mode' => 'mergeIfNotBlank',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_commerce',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'show_thumbs' => 1,
			),
		),
		'iconmode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.iconMode',
			'l10n_mode' => 'exclude',
			'displayCond' => 'FIELD:has_valuelist:=:1',
			'config' => array(
				'type' => 'check',
			),
		),
		'parent' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.parent',
			'l10n_mode' => 'exclude',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_commerce_attributes',
				'foreign_label' => 'title',
				'foreign_table_where' => ' AND tx_commerce_attributes.uid != ###THIS_UID###',
				'size' => 10,
				'autoSizeMax' => 20,
				'minitems' => 0,
				'maxitems' => 30,
				'renderMode' => 'tree',
				'treeConfig' => array(
					'parentField' => 'parent',
					'appearance' => array(
						'expandAll' => TRUE,
						'showHeader' => TRUE,
					),
				),
			),
		),
	),
	'types' => array(
		'0' => array(
			'showitem' => '--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.basis,
				sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, parent, has_valuelist, multiple, valueformat,
				title;;;;2-2-2, internal_title, unit, icon;;;;3-3-3;,iconmode,
				--div--;LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_attributes.valuelistlist, valuelist'
		)
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime, fe_group')
	)
);

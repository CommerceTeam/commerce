<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2005 - 2006 Thomas Hempel <thomas@work.de>
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
 * Dynamic config file for tx_commerce_articles
 *
 * @package commerce
 * @author Thomas Hempel <thomas@work.de>
 * $Id: tx_commerce_tracking.tca.php 298 2006-07-25 05:28:35Z ingo $
 */
 
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_trackingcodes'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_trackingcodes']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title,description'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_trackingcodes']['feInterface'],
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
				'foreign_table_where' => 'AND tx_commerce_trackingcodes.pid=###CURRENT_PID### AND tx_commerce_trackingcodes.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array(
			'config' => Array(
				'type' => 'passthrough'
			)
		),
		'title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_trackingcodes.title',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'description' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_trackingcodes.description',
			'config' => Array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '10',
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2, description;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts];3-3-3')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

$GLOBALS['TCA']['tx_commerce_tracking'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_tracking']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'orders_uid,trackingcodes_uid,msg'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_tracking']['feInterface'],
	'columns' => Array(
		'orders_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_tracking.orders_uid',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_orders',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'trackingcodes_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_tracking.trackingcodes_uid',
			'config' => Array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_trackingcodes',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'msg' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_tracking.msg',
			'config' => Array(
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'orders_uid;;;;1-1-1, trackingcodes_uid, msg')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

?>
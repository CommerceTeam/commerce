<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Thomas Hempel <thomas@work.de>
 *  (c) 2006 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Dynamic config file for tx_commerce_articles
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_article_types'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_article_types']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,title'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_article_types']['feInterface'],
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
				'foreign_table' => 'tx_commerce_article_types',
				'foreign_table_where' => 'AND tx_commerce_article_types.pid=###CURRENT_PID### AND tx_commerce_article_types.sys_language_uid IN (-1,0)',
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
		'title' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_article_types.title',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim',
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, title;;;;2-2-2')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

?>
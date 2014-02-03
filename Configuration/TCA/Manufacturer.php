<?php
/***************************************************************
 *  Copyright notice
 * (c) 2005 - 2006 Joerg Sprung <jsp@marketing-factory.de> All rights reserved
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
 * Dynamic config file for tx_commerce_manufacturer
 *
 * @package commerce
 * @author Joerg Sprung <jsp@marketing-factory.de>
 * $Id$
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_manufacturer'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_manufacturer']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'hidden, title, street, number, zip, city, country, phone, fax, email, internet, contactperson, logo'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_manufacturer']['feInterface'],
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

?>
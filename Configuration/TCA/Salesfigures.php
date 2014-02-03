<?php
/***************************************************************
 *  Copyright notice
 *  (c)  2005 - 2006 Joerg Sprung <jsp@marketing-factory.de>  All rights reserved
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
 * Dynamic config file for tx_commerce_salesfigures
 *
 * @package commerce
 * @author Joerg Sprung <jsp@marketing-factory.de>
 * $Id$
 */

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_salesfigures'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_salesfigures']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'year,month,day,dow,hour,pricegross, pricenet,amount,orders'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_salesfigures']['feInterface'],
	'columns' => Array(
		'year' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.year',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'month' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.month',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'day' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.day',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'dow' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.dow',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'hour' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.hour',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'pricegross' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.pricegross',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'pricenet' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.pricenet',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'amount' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.amount',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'orders' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_salesfigures.orders',
			'config' => Array(
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
	),
	'types' => Array(
		'0' => Array('showitem' => 'year;;;;1-1-1, month, day, dow, hour, pricegross, pricenet, amount, orders')
	),
	'palettes' => Array(
		'1' => Array('showitem' => '')
	)
);

?>
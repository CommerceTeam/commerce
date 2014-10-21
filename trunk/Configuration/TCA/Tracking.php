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

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TCA']['tx_commerce_tracking'] = Array(
	'ctrl' => $GLOBALS['TCA']['tx_commerce_tracking']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'orders_uid,trackingcodes_uid,msg'
	),
	'feInterface' => $GLOBALS['TCA']['tx_commerce_tracking']['feInterface'],
	'columns' => Array(
		'orders_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking.orders_uid',
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
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking.trackingcodes_uid',
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
			'label' => 'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_tracking.msg',
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

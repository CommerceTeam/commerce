<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2006 Joerg Sprung <jsp@marketing-factory.de>  All rights reserved
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
 * Dynamic config file for tx_commerce_newclients
 *
 * @package commerce
 * @author Joerg Sprung <jsp@marketing-factory.de>
 * 
 * $Id$
 */
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_commerce_newclients'] = Array (
	'ctrl' => $TCA['tx_commerce_newclients']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'year,month,day,dow,hour,registration'
	),
	'feInterface' => $TCA['tx_commerce_newclients']['feInterface'],
	'columns' => Array (
		'year' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:commerce/locallang_db.php:tx_commerce_newclients.year',		
			'config' => Array (
				'type' => 'input',	
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'month' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_newclients.month',		
			'config' => Array (
				'type' => 'input',	
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'day' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_newclients.day',		
			'config' => Array (
				'type' => 'input',	
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'dow' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_newclients.dow',		
			'config' => Array (
				'type' => 'input',	
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'hour' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_newclients.hour',		
			'config' => Array (
				'type' => 'input',	
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
		'registration' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_newclients.registration',		
			'config' => Array (
				'type' => 'input',	
				'size' => '11',
				'max' => '11',
				'eval' => 'int',
				'default' => 0
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'year;;;;1-1-1, month, day, dow, hour, registration')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);
?>
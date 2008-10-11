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
 * $Id: tx_commerce_baskets.tca.php 298 2006-07-25 05:28:35Z ingo $
 */
 
 
if(!defined('TYPO3_MODE')) die("Access denied.");


$TCA['tx_commerce_baskets'] = Array (
	'ctrl' => $TCA['tx_commerce_baskets']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sid,article_id,price_gross,price_net,quantity'
	),
	'feInterface' => $TCA['tx_commerce_baskets']['feInterface'],
	'columns' => Array (
		'sid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_baskets.sid',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'article_id' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_baskets.article_id',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_articles',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'price_id' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_baskets.price_id',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_commerce_article_prices',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'price_gross' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_baskets.price_gross',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'double2,nospace',
			)
		),
		'price_net' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_baskets.price_net',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'double2,nospace',
			)
		),
		'quantity' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_baskets.quantity',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '5000',
					'lower' => '0'
				),
				'default' => 0
			)
		),
         'finished_time' => Array (
                        'exclude' => 1,
                        'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_basket.finished_time',
                        'config' => Array (
			       'type' => 'input',
	                       'eval' => 'date',
	           )
	   ),
	   ' readonly' => Array(
	   				 'exclude' => 1,
	   				 'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_basket.readonly',
	   				 'config' => array (
							'type' => 'check',
					 )
	   		),
																					       
	),
	'types' => Array (
		'0' => Array('showitem' => 'sid;;;;1-1-1, article_id,price_id, price_gross, price_net, quantity')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);

?>
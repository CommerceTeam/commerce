<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 * Inherited from tx_commerce_db_alib
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_db_attribute_value extends tx_commerce_db_alib {
	/**
	 * @var string
	 */
	public $databaseTable = 'tx_commerce_attribute_values';
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_attribute_value.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_attribute_value.php']);
}

?>
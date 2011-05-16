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
 * Database class for tx_commerce_attributes. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_attribute to get informations for articles.
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_db_attribute extends tx_commerce_db_alib {

	/**
	 * @var string Child database table
	 */
	protected $child_database_table = 'tx_commerce_attribute_values';

	/**
	 * Default constructor sets database table
	 *
	 * @return void
	 */
	public function __construct() {
		$this->database_table = 'tx_commerce_attributes';
	}

	/**
	 * Gets a list of attribute_value_uids
	 *
	 * @return array
	 */
	public function get_attribute_value_uids($uid) {
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			$this->child_database_table,
			'attributes_uid = ' . intval($uid),
			'',
			'sorting'
		);

		$attribute_value_uid_list = array();
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {
			while ($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$attribute_value_uid_list[] = (int)$return_data['uid'];
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);

		return $attribute_value_uid_list;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_attribute.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_attribute.php']);
}
?>
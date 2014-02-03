<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *  This script is part of the Typo3 project. The Typo3 project is
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
	 * @var string
	 */
	public $databaseTable = 'tx_commerce_attributes';

	/**
	 * @var string Child database table
	 */
	protected $childDatabaseTable = 'tx_commerce_attribute_values';

	/**
	 * Gets a list of attribute_value_uids
	 *
	 * @param integer $uid
	 * @return array
	 */
	public function getAttributeValueUids($uid) {
		/** @var $db t3lib_db */
		$db = & $GLOBALS['TYPO3_DB'];

		$result = $db->exec_SELECTquery(
			'uid',
			$this->childDatabaseTable,
			'attributes_uid = ' . (int) $uid . $GLOBALS['TSFE']->sys_page->enableFields($this->childDatabaseTable),
			'',
			'sorting'
		);

		$attributeValueList = array();
		if ($db->sql_num_rows($result) > 0) {
			while ($data = $db->sql_fetch_assoc($result)) {
				$attributeValueList[] = (int) $data['uid'];
			}
		}
		$db->sql_free_result($result);

		return $attributeValueList;
	}

	/**
	 * @param integer $uid
	 * @return array
	 * @deprecated since commerce 0.14.0, will be removed in commerce 0.15.0 - Use tx_commerce_db_attribute::getAttributeValueUids() instead
	 */
	public function get_attribute_value_uids($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getAttributeValueUids($uid);
	}

	/**
	 * @param integer $uid
	 * @return array
	 */
	public function getChildAttributeUids($uid) {
		$childAttributeList = array();

		if (intval($uid) > 0) {
			$result = $this->database->exec_SELECTquery(
				'uid',
				$this->databaseTable,
				'parent = ' . (int) $uid . $GLOBALS['TSFE']->sys_page->enableFields($this->databaseTable),
				'',
				'sorting'
			);

			if ($this->database->sql_num_rows($result) > 0) {
				while ($data = $this->database->sql_fetch_assoc($result)) {
					$childAttributeList[] = (int) $data['uid'];
				}
			}
			$this->database->sql_free_result($result);
		}

		return $childAttributeList;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_attribute.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_attribute.php']);
}

?>
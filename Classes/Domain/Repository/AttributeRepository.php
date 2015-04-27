<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 */
class Tx_Commerce_Domain_Repository_AttributeRepository extends Tx_Commerce_Domain_Repository_Repository {
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
		$result = $this->database->exec_SELECTquery(
			'uid',
			$this->childDatabaseTable,
			'attributes_uid = ' . (int) $uid . $this->enableFields($this->childDatabaseTable),
			'',
			'sorting'
		);

		$attributeValueList = array();
		if ($this->database->sql_num_rows($result) > 0) {
			while (($data = $this->database->sql_fetch_assoc($result))) {
				$attributeValueList[] = (int) $data['uid'];
			}
		}
		$this->database->sql_free_result($result);

		return $attributeValueList;
	}

	/**
	 * Gets a list of attribute value uids
	 *
	 * @param integer $uid
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_db_attribute::getAttributeValueUids() instead
	 */
	public function get_attribute_value_uids($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getAttributeValueUids($uid);
	}

	/**
	 * Get child attribute uids
	 *
	 * @param integer $uid
	 * @return array
	 */
	public function getChildAttributeUids($uid) {
		$childAttributeList = array();

		if ((int) $uid) {
			$result = $this->database->exec_SELECTquery(
				'uid',
				$this->databaseTable,
				'parent = ' . (int) $uid . $this->enableFields($this->databaseTable),
				'',
				'sorting'
			);

			if ($this->database->sql_num_rows($result) > 0) {
				while (($data = $this->database->sql_fetch_assoc($result))) {
					$childAttributeList[] = (int) $data['uid'];
				}
			}
			$this->database->sql_free_result($result);
		}

		return $childAttributeList;
	}
}

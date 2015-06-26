<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Database class for tx_commerce_attributes. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_attribute to get informations for articles.
 *
 * Class Tx_Commerce_Domain_Repository_AttributeRepository
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Repository_AttributeRepository extends Tx_Commerce_Domain_Repository_Repository {
	/**
	 * Database table
	 *
	 * @var string
	 */
	public $databaseTable = 'tx_commerce_attributes';

	/**
	 * Database value table
	 *
	 * @var string Child database table
	 */
	protected $childDatabaseTable = 'tx_commerce_attribute_values';

	/**
	 * Gets a list of attribute_value_uids
	 *
	 * @param int $uid Uid
	 *
	 * @return array
	 */
	public function getAttributeValueUids($uid) {
		$database = $this->getDatabaseConnection();

		$result = $database->exec_SELECTquery(
			'uid',
			$this->childDatabaseTable,
			'attributes_uid = ' . (int) $uid . $this->enableFields($this->childDatabaseTable),
			'',
			'sorting'
		);

		$attributeValueList = array();
		if ($database->sql_num_rows($result) > 0) {
			while (($data = $database->sql_fetch_assoc($result))) {
				$attributeValueList[] = (int) $data['uid'];
			}
		}
		$database->sql_free_result($result);

		return $attributeValueList;
	}

	/**
	 * Get child attribute uids
	 *
	 * @param int $uid Uid
	 *
	 * @return array
	 */
	public function getChildAttributeUids($uid) {
		$database = $this->getDatabaseConnection();

		$childAttributeList = array();
		if ((int) $uid) {
			$result = $database->exec_SELECTquery(
				'uid',
				$this->databaseTable,
				'parent = ' . (int) $uid . $this->enableFields($this->databaseTable),
				'',
				'sorting'
			);

			if ($database->sql_num_rows($result) > 0) {
				while (($data = $database->sql_fetch_assoc($result))) {
					$childAttributeList[] = (int) $data['uid'];
				}
			}
			$database->sql_free_result($result);
		}

		return $childAttributeList;
	}


	/**
	 * Gets a list of attribute value uids
	 *
	 * @param int $uid Uid
	 *
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_db_attribute::getAttributeValueUids() instead
	 */
	public function get_attribute_value_uids($uid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getAttributeValueUids($uid);
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

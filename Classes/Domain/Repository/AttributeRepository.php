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
	 * @param int $uid
	 *
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
	 * @param int $uid
	 *
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_db_attribute::getAttributeValueUids() instead
	 */
	public function get_attribute_value_uids($uid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getAttributeValueUids($uid);
	}

	/**
	 * Get child attribute uids
	 *
	 * @param int $uid
	 *
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

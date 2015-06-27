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
 * Abstract Class for handling almost all Database-Calls for all
 * FE Rendering processes. This Class is mostly extended by distinct
 * Classes for spezified Objects
 *
 * Basic abtract Class for Database Query for
 * tx_commerce_product
 * tx_commerce_article
 * tx_commerce_category
 * tx_commerce_attribute
 *
 * Class Tx_Commerce_Domain_Repository_Repository
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Repository_Repository {
	/**
	 * Database table concerning the data
	 *
	 * @var string
	 */
	protected $databaseTable = '';

	/**
	 * Order field for most select statments
	 *
	 * @var string
	 */
	protected $orderField = ' sorting ';

	/**
	 * Stores the relation for the attributes to product, category, article
	 *
	 * @var string Database attribute rel table
	 */
	protected $databaseAttributeRelationTable = '';

	/**
	 * Debugmode for errorHandling
	 *
	 * @var bool
	 */
	protected $debugMode = FALSE;

	/**
	 * Translation mode for getRecordOverlay
	 *
	 * @var string
	 */
	protected $translationMode = 'hideNonTranslated';

	/**
	 * Uid
	 *
	 * @var int
	 */
	protected $uid;

	/**
	 * Database connection
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 * @deprecated Since 2.0.0 will be removed in 4.0.0
	 */
	protected $database;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->database = $this->getDatabaseConnection();
	}

	/**
	 * Get data
	 *
	 * @param int $uid UID for Data
	 * @param int $langUid Language Uid
	 * @param bool $translationMode Translation Mode for recordset
	 *
	 * @return array assoc Array with data
	 * @todo implement access_check concering category tree
	 */
	public function getData($uid, $langUid = -1, $translationMode = FALSE) {
		$database = $this->getDatabaseConnection();
		if ($translationMode == FALSE) {
			$translationMode = $this->translationMode;
		}

		$uid = (int) $uid;
		$langUid = (int) $langUid;
		if ($langUid == -1) {
			$langUid = 0;
		}

		if (empty($langUid) && $this->getFrontendController()->tmpl->setup['config.']['sys_language_uid'] > 0) {
			$langUid = $this->getFrontendController()->tmpl->setup['config.']['sys_language_uid'];
		}

		$proofSql = '';
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$proofSql = $this->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
		}

		$result = $database->exec_SELECTquery(
			'*',
			$this->databaseTable,
			'uid = ' . $uid . $proofSql
		);

		// Result should contain only one Dataset
		if ($database->sql_num_rows($result) == 1) {
			$returnData = $database->sql_fetch_assoc($result);
			$database->sql_free_result($result);

			// @since 8.10.2008: get workspace version if available
			if (!empty($GLOBALS['TSFE']->sys_page)) {
				$GLOBALS['TSFE']->sys_page->versionOL($this->databaseTable, $returnData);
			}

			if (!is_array($returnData)) {
				$this->error('There was an error overlaying the record with the version');
				return FALSE;
			}

			if ($langUid > 0) {
				/**
				 * Get Overlay, if available
				 */
				switch($translationMode) {
					case 'basket':
						// special Treatment for basket, so you could have
						// a product not translated init a language
						// but the basket is in the not translated laguage
						$newData = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
							$this->databaseTable,
							$returnData,
							$langUid,
							$this->translationMode
						);

						if (!empty($newData)) {
							$returnData = $newData;
						}
						break;

					default:
						$returnData = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
							$this->databaseTable,
							$returnData,
							$langUid,
							$this->translationMode
						);
				}
			}

			return $returnData;
		}

		// error Handling
		$this->error('exec_SELECTquery(\'*\', ' . $this->databaseTable . ', "uid = ' . $uid . '"); returns no or more than one Result');
		return FALSE;
	}

	/**
	 * Checks if one given UID is availiabe
	 *
	 * @param int $uid Uid
	 *
	 * @return bool true id availiabe
	 * @todo implement access_check
	 */
	public function isUid($uid) {
		$database = $this->getDatabaseConnection();

		if (!$uid) {
			return FALSE;
		}

		$result = $database->exec_SELECTquery(
			'uid',
			$this->databaseTable,
			'uid = ' . (int) $uid
		);

		return $database->sql_num_rows($result) == 1;
	}

	/**
	 * Checks in the Database if a UID is accessiblbe,
	 * basically checks against the enableFields
	 *
	 * @param int $uid Record Uid
	 *
	 * @return bool	TRUE if is accessible
	 * 				FALSE if is not accessible
	 */
	public function isAccessible($uid) {
		$database = $this->getDatabaseConnection();

		$return = FALSE;
		$uid = (int) $uid;
		if ($uid > 0) {
			$proofSql = '';
			if (is_object($GLOBALS['TSFE']->sys_page)) {
				$proofSql = $this->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
			}

			$result = $database->exec_SELECTquery(
				'*',
				$this->databaseTable,
				'uid = ' . $uid . $proofSql
			);

			if ($database->sql_num_rows($result) == 1) {
				$return = TRUE;
			}

			$database->sql_free_result($result);
		}
		return $return;
	}

	/**
	 * Error Handling Funktion
	 *
	 * @param string $err Errortext
	 *
	 * @return void
	 */
	public function error($err) {
		if ($this->debugMode) {
			debug('Error: ' . $err);
		}
	}

	/**
	 * Gets all attributes from this product
	 *
	 * @param int $uid Product uid
	 * @param array|NULL $attributeCorrelationTypeList Corelation types
	 *
	 * @return array of attribute UID
	 */
	public function getAttributes($uid, $attributeCorrelationTypeList = NULL) {
		$database = $this->getDatabaseConnection();
		$uid = (int) $uid;
		if ($this->databaseAttributeRelationTable == '') {
			return FALSE;
		}

		$additionalWhere = '';
		if (is_array($attributeCorrelationTypeList)) {
			$additionalWhere = ' AND ' . $this->databaseAttributeRelationTable . '.uid_correlationtype in (' .
				implode(',', $attributeCorrelationTypeList) . ')';
		}

		$result = $database->exec_SELECT_mm_query(
			'tx_commerce_attributes.uid',
			$this->databaseTable,
			$this->databaseAttributeRelationTable,
			'tx_commerce_attributes',
			'AND ' . $this->databaseTable . '.uid = ' . $uid . $additionalWhere . ' order by ' .
				$this->databaseAttributeRelationTable . '.sorting'
		);

		$attributeUidList = FALSE;
		if (($result) && ($database->sql_num_rows($result) > 0)) {
			$attributeUidList = array();
			while (($returnData = $database->sql_fetch_assoc($result))) {
				$attributeUidList[] = (int) $returnData['uid'];
			}
			$database->sql_free_result($result);
		}
		return $attributeUidList;
	}

	/**
	 * Update record data
	 *
	 * @param int $uid Uid of the item
	 * @param array $fields Assoc. array with update fields
	 *
	 * @return bool
	 */
	public function updateRecord($uid, array $fields) {
		if (!is_numeric($uid) || !is_array($fields)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('updateRecord (db_alib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		$database = $this->getDatabaseConnection();
		$database->exec_UPDATEquery($this->databaseTable, 'uid = ' . $uid, $fields);
		if ($database->sql_error()) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('updateRecord (db_alib): invalid sql.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Get enableFields
	 *
	 * @param string $tableName Table name
	 * @param bool|int $showHiddenRecords Show hidden records
	 *
	 * @return string
	 */
	public function enableFields($tableName, $showHiddenRecords = -1) {
		if (TYPO3_MODE === 'FE') {
			$result = $this->getFrontendController()->sys_page->enableFields($tableName, $showHiddenRecords);
		} else {
			$result = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($tableName);
		}

		return $result;
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}
}

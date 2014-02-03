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
 */
class tx_commerce_db_alib {
	/**
	 * @var string Database table concerning the data
	 */
	protected $databaseTable = '';

	/**
	 * @var string Order field for most select statments
	 */
	protected $orderField = ' sorting ';

	/**
	 * Stores the relation for the attributes to product,category, article
	 * @var string Database attribute rel table
	 */
	protected $databaseAttributeRelationTable = '';

	/**
	 * debugmode for errorHandling
	 * @var boolean debugMode Boolean
	 */
	protected $debugMode = FALSE;

	/**
	 * @var string Translation Mode for getRecordOverlay
	 * @see class.t3lib_page.php
	 */
	protected $translationMode = 'hideNonTranslated';

	/**
	 * @var integer
	 */
	protected $uid;

	/**
	 * @var t3lib_db
	 */
	protected $database;

	/**
	 * @return self
	 */
	public function __construct() {
		$this->database = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @param integer $uid UID for Data
	 * @param integer $langUid Language Uid
	 * @param boolean $translationMode Translation Mode for recordset
	 * @return array assoc Array with data
	 * @todo implement access_check concering category tree
	 */
	public function getData($uid, $langUid = -1, $translationMode = FALSE) {
		if ($translationMode == FALSE) {
			$translationMode = $this->translationMode;
		}

		$uid = intval($uid);
		$langUid = intval($langUid);
		if ($langUid == -1) {
			$langUid = 0;
		}

		if ((($langUid == 0) || empty($langUid)) && ($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] > 0)) {
			$langUid = $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}

		$proofSQL = '';
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
		}

		$result = $this->database->exec_SELECTquery(
			'*',
			$this->databaseTable,
			'uid = ' . $uid . $proofSQL
		);

			// Result should contain only one Dataset
		if ($this->database->sql_num_rows($result) == 1) {
			$return_data = $this->database->sql_fetch_assoc($result);
			$this->database->sql_free_result($result);

				// @since 8.10.2008: get workspace version if available
			if (!is_null($GLOBALS['TSFE']->sys_page)) {
				$GLOBALS['TSFE']->sys_page->versionOL($this->databaseTable, $return_data);
			}

			if (!is_array($return_data)) {
				$this->error('There was an error overlaying the record with the version');
				return FALSE;
			}

			if (($langUid > 0)) {
				/**
				 * Get Overlay, if available
				 */
				switch($translationMode) {
					case 'basket':
							// special Treatment for basket, so you could have a product not transleted inti a language
							// but the basket is in the not translated laguage
						$newData = $GLOBALS['TSFE']->sys_page->getRecordOverlay($this->databaseTable, $return_data, $langUid, $this->translationMode);

						if (!empty($newData)) {
							$return_data = $newData;
						}
					break;
					default:
						$return_data = $GLOBALS['TSFE']->sys_page->getRecordOverlay($this->databaseTable, $return_data, $langUid, $this->translationMode);
					break;
				}
			}

			return $return_data;
		} else {
				// error Handling
			$this->error('exec_SELECTquery(\'*\', ' . $this->databaseTable . ', "uid = ' . $uid . '"); returns no or more than one Result');
			return FALSE;
		}
	}

	/**
	 * checks if one given UID is availiabe
	 *
	 * @param integer $uid
	 * @return boolean true id availiabe
	 * @todo implement access_check
	 */
	public function isUid($uid) {
		if (!$uid) {
			return FALSE;
		}

		$result = $this->database->exec_SELECTquery(
			'uid',
			$this->databaseTable,
			'uid = ' . (int) $uid
		);

		return $this->database->sql_num_rows($result) == 1;
	}

	/**
	 * Checks in the Database if a UID is accessiblbe,
	 * basically checks against the enableFields
	 *
	 * @param integer $uid Record Uid
	 * @return boolean	TRUE if is accessible
	 * 					FALSE	if is not accessible
	 */
	public function isAccessible($uid) {
		$return = FALSE;
		$uid = intval($uid);
		if ($uid > 0) {
			$proofSQL = '';
			if (is_object($GLOBALS['TSFE']->sys_page)) {
				$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
			}

			$result = $this->database->exec_SELECTquery(
				'*',
				$this->databaseTable,
				'uid = ' . $uid . $proofSQL
			);

			if ($this->database->sql_num_rows($result) == 1) {
				$return = TRUE;
			}

			$this->database->sql_free_result($result);
		}
		return $return;
	}

	/**
	 * Error Handling Funktion
	 *
	 * @param string $err Errortext
	 */
	public function error($err) {
		if ($this->debugMode) {
			debug('Error: ' . $err);
		}
	}

	/**
	 * gets all attributes from this product
	 *
	 * @param integer $uid Product uid
	 * @param array $attribute_corelation_type_list list of corelation_types, optional
	 * @return array of attribute UID
	 */
	public function getAttributes($uid, $attribute_corelation_type_list = NULL) {
		$uid = intval($uid);
		if ($this->databaseAttributeRelationTable == '') {
			return FALSE;
		}

		$add_where = '';
		if (is_array($attribute_corelation_type_list)) {
			$add_where = ' AND ' . $this->databaseAttributeRelationTable . '.uid_correlationtype in (' .
				implode(',', $attribute_corelation_type_list) . ')';
		}

		$result = $this->database->exec_SELECT_mm_query(
			'tx_commerce_attributes.uid',
			$this->databaseTable,
			$this->databaseAttributeRelationTable,
			'tx_commerce_attributes',
			'AND ' . $this->databaseTable . '.uid = ' . $uid . $add_where . ' order by ' . $this->databaseAttributeRelationTable . '.sorting'
		);

		$attributeUidList = FALSE;
		if (($result) && ($this->database->sql_num_rows($result) > 0)) {
			$attributeUidList = array();
			while ($return_data = $this->database->sql_fetch_assoc($result)) {
				$attributeUidList[] = (int) $return_data['uid'];
			}
			$this->database->sql_free_result($result);
		}
		return $attributeUidList;
	}

	/**
	 * Update record data
	 *
	 * @return boolean
	 * @param integer $uid  uid of the item
	 * @param array $fields Assoc. array with update fields
	 */
	public function updateRecord($uid, array $fields) {
		if (!is_numeric($uid) || !is_array($fields)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('updateRecord (db_alib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		$database->exec_UPDATEquery($this->databaseTable, 'uid = ' . $uid, $fields);
		if ($database->sql_error()) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('updateRecord (db_alib): invalid sql.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}
		return TRUE;
	}


	/**
	 * gets all attributes from this product
	 * @param integer $uid Product uid
	 * @param array $attribute_corelation_type_list array of corelation_types, optional
	 * @return array of attribute UID
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getAttributes instead
	 */
	public function get_attributes($uid, $attribute_corelation_type_list = NULL) {
		t3lib_div::logDeprecatedFunction();
		return $this->getAttributes($uid, $attribute_corelation_type_list);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_alib.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_alib.php']);
}

?>
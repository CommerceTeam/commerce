<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2012 Ingo Schmitt <is@marketing-factory.de>
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
 * Database Class for tx_commerce_categories. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_category to get informations for articles.
 */
class Tx_Commerce_Domain_Repository_CategoryRepository extends Tx_Commerce_Domain_Repository_Repository {
	/**
	 * @var string Database table concerning the data
	 */
	public $databaseTable = 'tx_commerce_categories';

	/**
	 * @var string mm_table
	 */
	protected $databaseParentCategoryRelationTable = 'tx_commerce_categories_parent_category_mm';

	/**
	 * @var string Attribute rel table
	 */
	protected $databaseAttributeRelationTable = 'tx_commerce_categories_attributes_mm';

	/**
	 * @var string Category sorting Field
	 */
	protected $categoryOrderField = 'tx_commerce_categories.sorting';

	/**
	 * @var string Product sorting field
	 */
	protected $productOrderField = 'tx_commerce_products.sorting';

	/**
	 * @var integer Uid of this Category
	 */
	protected $uid;

	/**
	 * @var integer Language UID
	 */
	protected $lang_uid;

	/**
	 * Gets the "master" category from this category
	 *
	 * @param integer $uid Category UID
	 * @return integer Category UID
	 */
	public function getParentCategory($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = 0;
		if (t3lib_div::testInt($uid) && ($uid > 0)) {
			$this->uid = $uid;
			if ($result = $database->exec_SELECTquery(
				'uid_foreign', $this->databaseParentCategoryRelationTable, 'uid_local = ' . (int) $uid . ' and is_reference=0'
			)
			) {
				if ($return_data = $database->sql_fetch_assoc($result)) {
					$database->sql_free_result($result);
					$result = $return_data['uid_foreign'];
				}
			}
		}
		return $result;
	}

	/**
	 * Returns the permissions information for the category with the uid
	 *
	 * @param integer $uid Category UID
	 * @return array Array with permission information
	 */
	public function getPermissionsRecord($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (t3lib_div::testInt($uid) && ($uid > 0)) {
			$result = $database->exec_SELECTquery(
				'perms_everybody, perms_user, perms_group, perms_userid, perms_groupid, editlock',
				$this->databaseTable,
				'uid = ' . $uid
			);
			return $database->sql_fetch_assoc($result);
		}

		return array();
	}

	/**
	 * Gets the parent categories from this category
	 *
	 * @param integer $uid Category UID
	 * @return array Array of parent categories UIDs
	 */
	public function getParentCategories($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}
		$this->uid = $uid;
		$additionalWhere = $this->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);

		$result = $database->exec_SELECT_mm_query(
			'uid_foreign',
			$this->databaseTable, $this->databaseParentCategoryRelationTable,
			$this->databaseTable,
			' AND ' . $this->databaseParentCategoryRelationTable . '.uid_local= ' . (int) $uid . ' ' . $additionalWhere
		);
		if ($result) {
			$data = array();
			while (($row = $database->sql_fetch_assoc($result))) {
				// @todo access_check for data sets
				$data[] = (int) $row['uid_foreign'];
			}
			$database->sql_free_result($result);
			return $data;
		}
		return FALSE;
	}

	/**
	 * Returns an array of sys_language_uids of the i18n categories
	 * Only use in BE
	 *
	 * @param integer $uid UID of the category we want to get the i18n languages from
	 * @return array Array of UIDs
	 */
	public function getL18nCategories($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if ((empty($uid)) || (!is_numeric($uid))) {
			return FALSE;
		}
		$this->uid = $uid;
		$res = $database->exec_SELECTquery(
			't1.title, t1.uid, t2.flag, t2.uid as sys_language',
			$this->databaseTable . ' AS t1 LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
			'l18n_parent = ' . $uid . ' AND deleted = 0'
		);
		$uids = array();
		while (($row = $database->sql_fetch_assoc($res))) {
			$uids[] = $row;
		}
		return $uids;
	}

	/**
	 * Gets the child categories from this category
	 *
	 * @param   integer $uid Product     UID
	 * @param   integer $languageUid Language UID
	 * @return array Array of child categories UID
	 */
	public function getChildCategories($uid, $languageUid = -1) {
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}
		if ($languageUid == -1) {
			$languageUid = 0;
		}
		$this->uid = $uid;
		if ($languageUid == 0 && $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] > 0) {
			$languageUid = $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}
		$this->lang_uid = $languageUid;

		// @todo Sorting should be by database
		// 'tx_commerce_categories_parent_category_mm.sorting'
		// as TYPO3 isn't currently able to sort by MM tables
		// We are using $this->databaseTable.sorting

		$localOrderField = $this->categoryOrderField;

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'categoryOrder\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'categoryOrder\']
			');
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']);
			if (method_exists($hookObj, 'categoryOrder')) {
				$localOrderField = $hookObj->categoryOrder($this->categoryOrderField, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryOrder']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryOrder']);
			if (method_exists($hookObj, 'categoryOrder')) {
				$localOrderField = $hookObj->categoryOrder($this->categoryOrderField, $this);
			}
		}

		$additionalWhere = $this->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECT_mm_query(
			'uid_local',
			$this->databaseTable, $this->databaseParentCategoryRelationTable,
			$this->databaseTable,
			' AND ' . $this->databaseParentCategoryRelationTable . '.uid_foreign= ' . (int) $uid . ' ' . $additionalWhere,
			'',
			$localOrderField
		);
		if ($result) {
			$data = array();
			while (($row = $database->sql_fetch_assoc($result))) {
				/**
				 *  @todo access_check for datasets
				 */
				if ($languageUid == 0) {
					$data[] = (int) $row['uid_local'];
				} else {
						// Check if a lokalised product is availiabe for this product
					/**
					 * @todo Check if this is correct in Multi Tree Sites
					 */
					$lresult = $database->exec_SELECTquery(
						'uid',
						'tx_commerce_categories',
						'l18n_parent = ' . (int) $row['uid_local'] . ' AND sys_language_uid=' . $languageUid .
							$this->enableFields('tx_commerce_categories', $GLOBALS['TSFE']->showHiddenRecords)
					);

					if ($database->sql_num_rows($lresult)) {
						$data[] = (int) $row['uid_local'];
					}
				}
			}

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryQueryPostHook']) {
				t3lib_div::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'categoryQueryPostHook\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'categoryQueryPostHook\']
				');
				$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryQueryPostHook']);
				if (is_object($hookObj) && method_exists($hookObj, 'categoryQueryPostHook')) {
					$data = $hookObj->categoryQueryPostHook($data, $this);
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryQueryPostHook']) {
				$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['categoryQueryPostHook']);
				if (is_object($hookObj) && method_exists($hookObj, 'categoryQueryPostHook')) {
					$data = $hookObj->categoryQueryPostHook($data, $this);
				}
			}

			$database->sql_free_result($result);
			return $data;
		}
		return FALSE;
	}

	/**
	 * Gets child products from this category
	 *
	 * @param integer $uid Product UID
	 * @param integer $languageUid Language UID
	 * @return array Array of child products UIDs
	 */
	public function getChildProducts($uid, $languageUid = -1) {
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}
		if ($languageUid == -1) {
			$languageUid = 0;
		}
		$this->uid = $uid;
		if ($languageUid == 0 && $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] > 0) {
			$languageUid = $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}
		$this->lang_uid = $languageUid;

		$localOrderField = $this->productOrderField;

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'productOrder\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'productOrder\']
			');
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']);
			if (is_object($hookObj) && method_exists($hookObj, 'productOrder')) {
				$localOrderField = $hookObj->productOrder($localOrderField, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productOrder']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productOrder']);
			if (is_object($hookObj) && method_exists($hookObj, 'productOrder')) {
				$localOrderField = $hookObj->productOrder($localOrderField, $this);
			}
		}

		$whereClause = 'AND tx_commerce_products_categories_mm.uid_foreign = ' . (int) $uid . '
			AND tx_commerce_products.uid=tx_commerce_articles.uid_product
			AND tx_commerce_articles.uid=tx_commerce_article_prices.uid_article ';
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$whereClause .= $this->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords);
			$whereClause .= $this->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
			$whereClause .= $this->enableFields('tx_commerce_article_prices', $GLOBALS['TSFE']->showHiddenRecords);
		}

			// Versioning - no deleted or versioned records, nor live placeholders
		$whereClause .= ' AND tx_commerce_products.deleted = 0 AND tx_commerce_products.pid != -1 AND tx_commerce_products.t3ver_state != 1';
		$queryArray = array(
			'SELECT' => 'tx_commerce_products.uid',
			'FROM' => 'tx_commerce_products ,tx_commerce_products_categories_mm,tx_commerce_articles, tx_commerce_article_prices',
			'WHERE' => 'tx_commerce_products.uid=tx_commerce_products_categories_mm.uid_local ' . $whereClause,
			'GROUPBY' => '',
			'ORDERBY' => $localOrderField,
			'LIMIT' => ''
		);

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'productQueryPreHook\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'productQueryPreHook\']
			');
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']);
			if (is_object($hookObj) && method_exists($hookObj, 'productQueryPreHook')) {
				$queryArray = $hookObj->productQueryPreHook($queryArray, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPreHook']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPreHook']);
			if (is_object($hookObj) && method_exists($hookObj, 'productQueryPreHook')) {
				$queryArray = $hookObj->productQueryPreHook($queryArray, $this);
			}
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECT_queryArray($queryArray);
		if ($result !== FALSE) {
			$data = array();
			while (($row = $database->sql_fetch_assoc($result)) !== FALSE) {
				if ($languageUid == 0) {
					$data[] = (int) $row['uid'];
				} else {
					// Check if a locallized product is availabe
					// @todo Check if this is correct in multi tree sites
					$lresult = $database->exec_SELECTquery(
						'uid',
						'tx_commerce_products', 'l18n_parent = ' . (int) $row['uid'] . ' AND sys_language_uid=' . $languageUid .
							$this->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords)
					);
					if ($database->sql_num_rows($lresult)) {
						$data[] = (int) $row['uid'];
					}
				}
			}
			$database->sql_free_result($result);

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']) {
				t3lib_div::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_db_category.php\'][\'productQueryPostHook\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/CategoryRepository.php\'][\'productQueryPostHook\']
				');
				$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']);
				if (is_object($hookObj) && method_exists($hookObj, 'productQueryPostHook')) {
					$data = $hookObj->productQueryPostHook($data, $this);
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPostHook']) {
				$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/CategoryRepository.php']['productQueryPostHook']);
				if (is_object($hookObj) && method_exists($hookObj, 'productQueryPostHook')) {
					$data = $hookObj->productQueryPostHook($data, $this);
				}
			}

			return $data;
		}
		return FALSE;
	}


	/**
	 * Getter
	 *
	 * @return int
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0
	 * remove attributes and setting of them too
	 */
	public function getUid() {
		t3lib_div::logDeprecatedFunction();
		return $this->uid;
	}

	/**
	 * Getter
	 *
	 * @return int
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0
	 * remove attributes and setting of them too
	 */
	public function getLangUid() {
		t3lib_div::logDeprecatedFunction();
		return $this->lang_uid;
	}

	/**
	 * Gets the "master" category from this category
	 *
	 * @param integer $uid Category UID
	 * @return integer Category UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getParentCategory instead
	 */
	public function get_parent_category($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getParentCategory($uid);
	}

	/**
	 * Gets the parent categories from this category
	 *
	 * @param integer $uid Category UID
	 * @return array Array of parent categories UIDs
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getParentCategories instead
	 */
	public function get_parent_categories($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getParentCategories($uid);
	}

	/**
	 * Returns an array of sys_language_uids of the i18n categories
	 * Only use in BE
	 *
	 * @param integer $uid UID of the category we want to get the i18n languages from
	 * @return array Array of UIDs
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getL18nCategories instead
	 */
	public function get_l18n_categories($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getL18nCategories($uid);
	}

	/**
	 * Gets the child categories from this category
	 *
	 * @param integer $uid Product
	 * @param integer $languageUid Language
	 * @return array Array of child categories
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getChildCategories instead
	 */
	public function get_child_categories($uid, $languageUid = -1) {
		t3lib_div::logDeprecatedFunction();
		return $this->getChildCategories($uid, $languageUid);
	}

	/**
	 * Gets child products from this category
	 *
	 * @param integer $uid Product UID
	 * @param integer $languageUid Language UID
	 * @return array Array of child products UIDs
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getChildProducts instead
	 */
	public function get_child_products($uid, $languageUid = -1) {
		t3lib_div::logDeprecatedFunction();
		return $this->getChildProducts($uid, $languageUid);
	}
}

class_alias('Tx_Commerce_Domain_Repository_CategoryRepository', 'tx_commerce_db_category');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Repository/CategoryRepository.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Repository/CategoryRepository.php']);
}

?>
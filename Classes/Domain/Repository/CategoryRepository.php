<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2012 Ingo Schmitt <is@marketing-factory.de>
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
 * Database Class for tx_commerce_categories. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_category to get informations for articles.
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class Tx_Commerce_Domain_Repository_CategoryRepository extends Tx_Commerce_Domain_Repository_Repository {
	/**
	 * @var string Database table concerning the data
	 */
	public $databaseTable = 'tx_commerce_categories';

	/**
	 * @var string mm_table
	 */
	protected $mm_database_table = 'tx_commerce_categories_parent_category_mm';

	/**
	 * @var string Attribute rel table
	 */
	public $database_attribute_rel_table = 'tx_commerce_categories_attributes_mm';

	/**
	 * @var string Category sorting Field
	 */
	protected $CategoryOrderField = 'tx_commerce_categories.sorting';

	/**
	 * @var string Product sorting field
	 */
	protected $ProductOrderField = 'tx_commerce_products.sorting';

	/**
	 * @var integer Uid of this Category
	 */
	protected $uid;

	/**
	 * @var integer Language UID
	 */
	protected $lang_uid;

	/**
	 * @return integer
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * @return int
	 */
	public function getLangUid() {
		return $this->lang_uid;
	}

	/**
	 * Gets the "master" category from this category
	 *
	 * @param integer $uid Category UID
	 * @return integer Category UID
	 */
	public function get_parent_category($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = 0;
		if (t3lib_div::testInt($uid) && ($uid > 0)) {
			$this->uid = $uid;
			if ($result = $database->exec_SELECTquery('uid_foreign', $this->mm_database_table, 'uid_local = ' . (int) $uid . ' and is_reference=0')) {
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
		} else {
			return array();
		}
	}

	/**
	 * Gets the parent categories from this category
	 *
	 * @param integer $uid Category UID
	 * @return array Array of parent categories UIDs
	 */
	public function get_parent_categories($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}
		$this->uid = $uid;
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$add_where = $GLOBALS['TSFE']->sys_page->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
		} else {
			$add_where = '';
		}
		$result = $database->exec_SELECT_mm_query(
			'uid_foreign',
			$this->databaseTable,
			$this->mm_database_table,
			$this->databaseTable,
			' AND ' . $this->mm_database_table . '.uid_local= ' . (int) $uid . ' ' . $add_where
		);
		if ($result) {
			$data = array();
			while ($return_data = $database->sql_fetch_assoc($result)) {
					// @TODO access_check for data sets
				$data[] = (int) $return_data['uid_foreign'];
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
	public function get_l18n_categories($uid) {
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
		while ($row = $database->sql_fetch_assoc($res)) {
			$uids[] = $row;
		}
		return $uids;
	}

	/**
	 * Returns an array with uids of all direct child categories for the category
	 *
	 * @param integer $uid Category UID to start
	 * @return array Array of category UIDs
	 * @deprecated
	 */
	public function getChildCategories($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getChildCategories (db_category) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}
		$uids = array();
		$res = $database->exec_SELECTquery(
			'uid_local AS uid',
			'tx_commerce_categories_parent_category_mm',
			'uid_foreign = ' . $uid
		);
		while ($row = $database->sql_fetch_assoc($res)) {
			$uids[] = $row['uid'];
		}
		return $uids;
	}

	/**
	 * Gets the child categories from this category
	 *
	 * @param   integer $uid    Product     UID
	 * @param   integer $lang_uid   Language UID
	 * @return array Array of child categories UID
	 */
	public function get_child_categories($uid, $lang_uid = -1) {
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}
		if ($lang_uid == -1) {
			$lang_uid = 0;
		}
		$this->uid = $uid;
		if ($lang_uid == 0 && $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] > 0) {
			$lang_uid = $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}
		$this->lang_uid = $lang_uid;

			// @TODO: Sorting should be by database 'tx_commerce_categories_parent_category_mm.sorting'
			// as TYPO3 is´nt currently able to sort by MM tables (or we haven't found a way to use it)
			// We are using $this->databaseTable.sorting

		$localOrderField = $this->CategoryOrderField;
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']);
			if (method_exists($hookObj, 'categoryOrder')) {
				$localOrderField = $hookObj->categoryOrder($this->CategoryOrderField, $this);
			}
		}

		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$add_where = $GLOBALS['TSFE']->sys_page->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
		} else {
			$add_where = '';
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECT_mm_query(
			'uid_local',
			$this->databaseTable,
			$this->mm_database_table,
			$this->databaseTable,
			' AND ' . $this->mm_database_table . '.uid_foreign= ' . (int) $uid . ' ' . $add_where,
			'',
			$localOrderField
		);
		if ($result) {
			$data = array();
			while ($return_data = $database->sql_fetch_assoc($result)) {
				/**
				 *  @todo access_check for datasets
				 */
				if ($lang_uid == 0) {
					$data[] = (int) $return_data['uid_local'];
				} else {
						// Check if a lokalised product is availiabe for this product
					/**
					 * @TODO: Check if this is correct in Multi Tree Sites
					 */
					$lresult = $database->exec_SELECTquery(
						'uid',
						'tx_commerce_categories',
						'l18n_parent = ' . (int) $return_data['uid_local'] .
							' AND sys_language_uid=' . $lang_uid .
							$GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_categories', $GLOBALS['TSFE']->showHiddenRecords)
					);

					if ($database->sql_num_rows( $lresult) == 1) {
						$data[] = (int) $return_data['uid_local'];
					}
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryQueryPostHook']) {
				$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryQueryPostHook']);
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
	 * @param integer $lang_uid Language UID
	 * @return array Array of child products UIDs
	 */
	public function get_child_products($uid, $lang_uid = -1) {
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}
		if ($lang_uid == -1) {
			$lang_uid = 0;
		}
		$this->uid = $uid;
		if ($lang_uid == 0 && $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] > 0) {
			$lang_uid = $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}
		$this->lang_uid = $lang_uid;

		$localOrderField = $this->ProductOrderField;

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']);
			if (is_object($hookObj) && method_exists($hookObj, 'productOrder')) {
				$localOrderField = $hookObj->productOrder($localOrderField, $this);
			}
		}

		$where_clause = 'AND tx_commerce_products_categories_mm.uid_foreign = ' . (int) $uid . '
			AND tx_commerce_products.uid=tx_commerce_articles.uid_product
			AND tx_commerce_articles.uid=tx_commerce_article_prices.uid_article ';
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$where_clause .= $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords);
			$where_clause .= $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
			$where_clause .= $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_article_prices', $GLOBALS['TSFE']->showHiddenRecords);
		}

			// Versioning - no deleted or versioned records, nor live placeholders
		$where_clause .= ' AND tx_commerce_products.deleted = 0 AND tx_commerce_products.pid != -1 AND tx_commerce_products.t3ver_state != 1';
		$queryArray = array(
			'SELECT' => 'tx_commerce_products.uid',
			'FROM' => 'tx_commerce_products ,tx_commerce_products_categories_mm,tx_commerce_articles, tx_commerce_article_prices',
			'WHERE' => 'tx_commerce_products.uid=tx_commerce_products_categories_mm.uid_local ' . $where_clause,
			'GROUPBY' => '',
			'ORDERBY' => $localOrderField,
			'LIMIT' => ''
		);
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPreHook']);
			if (is_object($hookObj) && method_exists($hookObj, 'productQueryPreHook')) {
				$queryArray = $hookObj->productQueryPreHook($queryArray, $this);
			}
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECT_queryArray($queryArray);
		if ($result !== FALSE) {
			$data = array();
			while (($return_data = $database->sql_fetch_assoc($result)) !== FALSE ) {
				if ($lang_uid == 0) {
					$data[] = (int) $return_data['uid'];
				} else {
						// Check if a locallized product is availabe
						// @TODO: Check if this is correct in multi tree sites
					$lresult = $database->exec_SELECTquery(
						'uid',
						'tx_commerce_products',
						'l18n_parent = ' . (int) $return_data['uid'] . ' AND sys_language_uid=' . $lang_uid .
							$GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords)
					);
					if ($database->sql_num_rows($lresult) == 1) {
						$data[] = (int)$return_data['uid'];
					}
				}
			}
			$database->sql_free_result($result);

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']) {
				$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productQueryPostHook']);
				if (is_object($hookObj) && method_exists($hookObj, 'productQueryPostHook')) {
					$data = $hookObj->productQueryPostHook($data, $this);
				}
			}

			return $data;
		}
		return FALSE;
	}
}

class_alias('Tx_Commerce_Domain_Repository_CategoryRepository', 'tx_commerce_db_category');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Repository/CategoryRepository.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Repository/CategoryRepository.php']);
}

?>
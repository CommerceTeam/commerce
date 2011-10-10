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
 * Database Class for tx_commerce_categories. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_category to get informations for articles.
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_db_category extends tx_commerce_db_alib {

	/**
	 * @var string Database table concerning the data
	 */
	public $database_table = 'tx_commerce_categories';

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
	 * Gets the "master" category from this category
	 *
	 * @param integer $uid Category UID
	 * @return integer Category UID
	 */
	public function get_parent_category($uid) {
		if (t3lib_div::testInt($uid) && ($uid > 0)) {
			$this->uid = $uid;
			if ($result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign', $this->mm_database_table, 'uid_local = ' . intval($uid) . ' and is_reference=0')) {
				if ($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					$GLOBALS['TYPO3_DB']->sql_free_result($result);
					return $return_data['uid_foreign'];
				}
			}
		}
		return FALSE;
	}

	/**
	 * Returns the permissions information for the category with the uid
	 *
	 * @param integer $uid Category UID
	 * @return array Array with permission information
	 */
	public function getPermissionsRecord($uid) {
		if (t3lib_div::testInt($uid) && ($uid > 0)) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'perms_everybody, perms_user, perms_group, perms_userid, perms_groupid, editlock',
				$this->database_table,
				'uid = ' . $uid
			);
			return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
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
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}
		$this->uid = $uid;
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$add_where = $GLOBALS['TSFE']->sys_page->enableFields($this->database_table, $GLOBALS['TSFE']->showHiddenRecords);
		} else {
			$add_where = '';
		}
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'uid_foreign',
			$this->database_table,
			$this->mm_database_table,
			$this->database_table,
			' AND ' . $this->mm_database_table . '.uid_local= ' . intval($uid) . ' ' . $add_where
		);
		if ($result) {
			$data = array();
			while ($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					// @TODO access_check for data sets
				$data[] = (int)$return_data['uid_foreign'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
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
		if ((empty($uid)) || (!is_numeric($uid))) {
			return FALSE;
		}
		$this->uid = $uid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			't1.title, t1.uid, t2.flag, t2.uid as sys_language',
			$this->database_table . ' AS t1 LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
			'l18n_parent = ' . $uid . ' AND deleted = 0'
		);
		$uids = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$uids[] = $row;
		}
		return $uids;
	}

	/**
	 * Returns an array with uids of all direct child categories for the category
	 *
	 * @param integer $uid Category UID to start
	 * @return array Array of category UIDs
	 */
	public function getChildCategories($uid) {
		if(!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getChildCategories (db_category) gets passed invalid parameters.', COMMERCE_EXTkey, 3);
			}
			return array();
		}
		$uids = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_local AS uid',
			'tx_commerce_categories_parent_category_mm',
			'uid_foreign = ' . $uid
		);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$uids[] = $row['uid'];
		}
		return $uids;
	}

	/**
	 * Gets the child categories from this category
	 *
	 * @param integer $uid Product UID
	 * @return array Array of child categories UID
	 */
	public function get_child_categories($uid) {
		if (empty($uid) || !is_numeric($uid)) {
			return FALSE;
		}

			// @TODO: Sorting should be by database 'tx_commerce_categories_parent_category_mm.sorting'
			// as TYPO3 is´nt currently able to sort by MM tables (or we haven't found a way to use it)
			// We are using $this->database_table.sorting
		$this->uid = $uid;
		$localOrderField = $this->CategoryOrderField;
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['categoryOrder']);
		}
		if (method_exists($hookObj, 'categoryOrder')) {
			$localOrderField = $hookObj->categoryOrder($this->CategoryOrderField, $this);
		}
		if (is_object($GLOBALS['TSFE']->sys_page)) {
			$add_where = $GLOBALS['TSFE']->sys_page->enableFields($this->database_table, $GLOBALS['TSFE']->showHiddenRecords);
		} else {
			$add_where = '';
		}
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'uid_local',
			$this->database_table,
			$this->mm_database_table,
			$this->database_table,
			' AND ' . $this->mm_database_table . '.uid_foreign= ' . intval($uid) . ' ' .$add_where,
			'',
			$localOrderField
		);
		if ($result) {
			$data = array();
			while ($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					// @TODO Access check for data sets
				$data[] = (int)$return_data['uid_local'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
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
			unset($lang_uid);
		}
		$this->uid = $uid;
		if ((($lang_uid == 0) || empty($lang_uid)) && ($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] > 0)) {
			$lang_uid = $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
		}
		$localOrderField = $this->ProductOrderField;

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_db_category.php']['productOrder']);
		}
		if (is_object($hookObj) && method_exists($hookObj, 'productOrder')) {
			$localOrderField = $hookObj->productOrder($localOrderField,$this);
		}
		$where_clause = 'AND tx_commerce_products_categories_mm.uid_foreign = ' . intval($uid);
		$where_clause.= ' AND tx_commerce_products.uid=tx_commerce_articles.uid_product ';
		$where_clause.= ' AND tx_commerce_articles.uid=tx_commerce_article_prices.uid_article ';
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
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryArray);
		if ($result !== FALSE) {
			$data = array();
			while (($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) !== FALSE ) {
				if ($lang_uid == 0) {
					$data[] = (int)$return_data['uid'];
				} else {
						// Check if a locallized product is availabe
						// @TODO: Check if this is correct in multi tree sites
					$lresult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid',
						'tx_commerce_products',
						'l18n_parent = ' . intval($return_data['uid']) . ' AND sys_language_uid=' . $lang_uid . $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_products', $GLOBALS['TSFE']->showHiddenRecords)
					);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($lresult) == 1) {
						$data[] = (int)$return_data['uid'];
					}
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_category.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_category.php']);
}
?>
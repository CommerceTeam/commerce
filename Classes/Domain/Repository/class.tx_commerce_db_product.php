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
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 * Inherited from tx_commerce_db_alib
 */
class tx_commerce_db_product extends tx_commerce_db_alib {
	/**
	 * @var string table concerning the data
	 */
	public $databaseTable = 'tx_commerce_products';

	public $database_attribute_rel_table = 'tx_commerce_products_attributes_mm';
	public $database_category_rel_table = 'tx_commerce_products_categories_mm';
	public $database_products_related_table = 'tx_commerce_products_related_mm';
	public $orderField = 'sorting';

	/**
	 * gets all articles form database related to this product
	 * @param integer $uid uid= Product uid
	 * @return array of Article UID
	 */
	public function get_articles($uid) {
		$uid = intval($uid);
		$article_uid_list = array();
		if ($uid) {
			$localOrderField = $this->orderField;
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['articleOrder']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['articleOrder']);
				if (method_exists($hookObj, 'articleOrder')) {
					$localOrderField = $hookObj->articleOrder($this->orderField);
				}
			}

			if (is_object($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE']->sys_page)) {
				$where = 'uid_product = ' . $uid .  $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
			} else {
				$where = 'uid_product = ' . $uid;
			}
			$additionalWhere = '';

			/**
			 * @deprecated
			 */
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['aditionalWhere']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['aditionalWhere']);
				if (method_exists($hookObj, 'aditionalWhere')) {
					$additionalWhere = $hookObj->aditionalWhere($where);
				}
			}

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['additionalWhere']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['additionalWhere']);
				if (method_exists($hookObj, 'additionalWhere')) {
					$additionalWhere = $hookObj->additionalWhere($where);
				}
			}

			$result = $this->database->exec_SELECTquery('uid', 'tx_commerce_articles', $where . ' ' . $additionalWhere, '', $localOrderField);
			if ($this->database->sql_num_rows($result) > 0) {
				while ($return_data = $this->database->sql_fetch_assoc($result)) {
					$article_uid_list[] = $return_data['uid'];
				}
				$this->database->sql_free_result($result);
				return $article_uid_list;

			} else {
				$this->error('exec_SELECTquery("uid", "tx_commerce_articles", "uid_product = ' . $uid . '"); returns no Result');
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * gets all attributes form database related to this product where corelation type = 4
	 * @param integer $uid Product uid
	 * @param array|integer $correlationtypes
	 * @return array of Article UID
	 */
	public function get_attributes($uid, $correlationtypes) {
		$uid = intval($uid);
		if ($uid > 0) {
				// here some strang changes,
				// change uid_product to uid_local since product_attributes table doesn't have a uid_product, but's it's running
			if (!is_array($correlationtypes)) {
				$correlationtypes = array($correlationtypes);
			}

			$article_uid_list = array();
			$result = $this->database->exec_SELECTquery(
				'distinct(uid_foreign) as uid',
				$this->database_attribute_rel_table,
				'uid_local = ' . $uid . ' and uid_correlationtype in (' . implode(',', $correlationtypes) . ')',
				'',
				$this->database_attribute_rel_table . '.sorting'
			);
			if ($this->database->sql_num_rows($result) > 0) {
				while ($return_data = $this->database->sql_fetch_assoc($result)) {
					$article_uid_list[] = (int) $return_data['uid'];
				}
				$this->database->sql_free_result($result);
				return $article_uid_list;
			} else {
				$this->error('exec_SELECTquery(\'distinct(uid_foreign)\', ' . $this->database_attribute_rel_table . ', \'uid_local = ' . $uid . '\'); returns no Result');
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns a list of uid's that are related to this product
	 * @param integer $uid product uid
	 * @return array Product UIDs
	 * @TODO:we dont really need to extract category uids
	 */
	public function get_related_product_uids($uid) {
		$uid = intval($uid);
		$res = $this->database->exec_SELECTquery(
			'R.uid_foreign as rID,C.uid_foreign as cID',
			$this->database_products_related_table . ' R,' . $this->database_category_rel_table . ' as C',
			'R.uid_foreign = C.uid_local AND R.uid_local=' . intval($uid),
			'rID');
		$relatedProducts = array();
		while ($data = $this->database->sql_fetch_assoc($res)) {
			$relatedProducts[$data['rID']] = $data['cID'];
		}
		return $relatedProducts;
	}

	/**
	 * Gets the "master" category from this product
	 * @param integer $uid Product UID
	 * @return integer Categorie UID
	 * @TODO Change to correct handling way concering databas model, currently wrongly interperted
	 * @TODO change to mm db class function
	 * @deprecated use getParentCategories instead; note that getParentCategories will return an array
	 */
	public function get_parent_category($uid) {
		t3lib_div::logDeprecatedFunction();
		$uid = intval($uid);
		if ($uid) {
			if (is_object($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE']->sys_page)) {
				$addWhere = $GLOBALS['TSFE']->sys_page->enableFields($this->databaseTable, $GLOBALS['TSFE']->showHiddenRecords);
			} else {
				$addWhere = '';
			}

			if ($result = $this->database->exec_SELECTquery('categories', $this->databaseTable, 'uid = ' . $uid . $addWhere)) {
				if ($return_data = $this->database->sql_fetch_assoc($result)) {
					$this->database->sql_free_result($result);
					if ($return_data['categories']) {
						if (strpos($return_data['categories'], ',') > 0) {
							$rdataArr = explode(',', $return_data['categories']);
							$rdata = $rdataArr[0];
						} else {
							$rdata = $return_data['categories'];
						}
						return $rdata;
					}
				}
			}

			$this->error('exec_SELECTquery("categories", ' . $this->databaseTable . ', "uid = $uid"); returns no Result');
		}
		return FALSE;
	}

	/**
	 * Gets the "master" category from this product
	 * @param uid = Product UID
	 * @return integer Categorie UID
	 * @deprecated use getParentCategories instead
	 * @see get_parent_categorie
	 */
	public function get_parent_categorie($uid) {
		t3lib_div::logDeprecatedFunction();

		return $this->getParentCategories($uid);
	}

	/**
	 * Gets the "master" category from this product
	 * @param uid = Product UID
	 * @return array of parent categories
	 * @TODO Change to correct handling way concering databas model, currently wrongly interperted
	 * @TODO currently only call to get_parent_category
	 * @deprecated use getParentCategories instead
	 */
	public function get_parent_categories($uid) {
		t3lib_div::logDeprecatedFunction();

		return array($this->getParentCategories($uid));
	}

	/**
	 * Returns an array of sys_language_uids of the i18n products
	 * Only use in BE
	 *
	 * @param integer $uid uid of the product we want to get the i18n languages from
	 * @return array $uid uids
	 */
	public function get_l18n_products($uid) {
		if ((empty($uid)) || (!is_numeric($uid))) {
			return FALSE;
		}

		$this->uid = $uid;

		$res = $this->database->exec_SELECTquery(
			't1.title, t1.uid, t2.flag, t2.uid as sys_language',
			$this->databaseTable . ' AS t1 LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
			'l18n_parent = ' . $uid . ' AND deleted = 0'
		);

		$uids = array();
		while ($row = $this->database->sql_fetch_assoc($res)) {
			$uids[] =  $row;
		}

		return $uids;
	}

	/**
	 * Gets the parent categories of th
	 *
	 * @param integer $uid uid of the product
	 * @return array parent categories for products
	 */
	public function getParentCategories($uid) {
		if (!$uid || !is_numeric($uid)) {
			$this->error('getparentCategories has not been delivered a proper uid');
			return NULL;
		}

		$uids = array();

			// read from sql
		$result = $this->database->exec_SELECTquery(
			'uid_foreign',
			$this->database_category_rel_table,
			'uid_local = ' . $uid,
			'',
			'sorting ASC'
		);
		while ($row = $this->database->sql_fetch_assoc($result)) {
			$uids[] = $row['uid_foreign'];
		}

		$this->database->sql_free_result($result);

			// If $uids is empty, the record might be a localized product, related to issue #27021
		if (count($uids) === 0) {
			$rows = $this->database->exec_SELECTgetRows(
				'l18n_parent',
				$this->databaseTable,
				'uid = ' . $uid
			);
			if ($rows[0] && (int)$rows[0]['l18n_parent'] > 0) {
				$uids = $this->getParentCategories($rows[0]['l18n_parent']);
			}
		}

		return $uids;
	}

	/**
	 * Returns the Manuafacturer Title to a given Manufacturere UID
	 * @param	integer	$ManufacturerUid
	 * @return	string		Title
	 */
	public function getManufacturerTitle($ManufacturerUid) {
		$rSql = $this->database->exec_SELECTquery(
			'*',
			'tx_commerce_manufacturer',
			'uid = ' . intval($ManufacturerUid)
			);

		$sTitle = '';
		while (($aFiche = $this->database->sql_fetch_assoc($rSql)) !== FALSE) {
			$sTitle = $aFiche['title'];
		}

		return $sTitle;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_product.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_db_product.php']);
}

?>
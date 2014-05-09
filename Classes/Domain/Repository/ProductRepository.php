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
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 * Inherited from Tx_Commerce_Domain_Repository_Repository
 */
class Tx_Commerce_Domain_Repository_ProductRepository extends Tx_Commerce_Domain_Repository_Repository {
	/**
	 * @var string table concerning the data
	 */
	public $databaseTable = 'tx_commerce_products';

	/**
	 * @var string
	 */
	public $databaseAttributeRelationTable = 'tx_commerce_products_attributes_mm';

	/**
	 * @var string
	 */
	public $databaseCategoryRelationTable = 'tx_commerce_products_categories_mm';

	/**
	 * @var string
	 */
	public $databaseProductsRelatedTable = 'tx_commerce_products_related_mm';

	/**
	 * @var string
	 */
	public $orderField = 'sorting';

	/**
	 * gets all articles form database related to this product
	 *
	 * @param int $uid Product uid
	 * @return array of Article UID
	 */
	public function getArticles($uid) {
		$uid = (int) $uid;
		$articleUids = array();
		if ($uid) {
			$localOrderField = $this->orderField;
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['articleOrder']) {
				t3lib_div::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_product.php\'][\'articleOrder\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/ProductRepository.php\'][\'storeDataToDatabase\']
				');
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['articleOrder']);
				if (method_exists($hookObj, 'articleOrder')) {
					$localOrderField = $hookObj->articleOrder($this->orderField);
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ProductRepository.php']['articleOrder']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ProductRepository.php']['articleOrder']);
				if (method_exists($hookObj, 'articleOrder')) {
					$localOrderField = $hookObj->articleOrder($this->orderField);
				}
			}

			$where = 'uid_product = ' . $uid . $this->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
			$additionalWhere = '';

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['aditionalWhere']) {
				t3lib_div::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_product.php\'][\'aditionalWhere\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/ProductRepository.php\'][\'additionalWhere\']
				');
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['aditionalWhere']);
				if (method_exists($hookObj, 'aditionalWhere')) {
					$additionalWhere = $hookObj->aditionalWhere($where);
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['additionalWhere']) {
				t3lib_div::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_product.php\'][\'additionalWhere\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/ProductRepository.php\'][\'additionalWhere\']
				');
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['additionalWhere']);
				if (method_exists($hookObj, 'additionalWhere')) {
					$additionalWhere = $hookObj->additionalWhere($where);
				}
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ProductRepository.php']['additionalWhere']) {
				$hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ProductRepository.php']['additionalWhere']);
				if (method_exists($hookObj, 'additionalWhere')) {
					$additionalWhere = $hookObj->additionalWhere($where);
				}
			}

			$result = $this->database->exec_SELECTquery('uid', 'tx_commerce_articles', $where . ' ' . $additionalWhere, '', $localOrderField);
			if ($this->database->sql_num_rows($result) > 0) {
				while (($data = $this->database->sql_fetch_assoc($result))) {
					$articleUids[] = $data['uid'];
				}
				$this->database->sql_free_result($result);
				return $articleUids;

			} else {
				$this->error('exec_SELECTquery("uid", "tx_commerce_articles", "uid_product = ' . $uid . '"); returns no Result');
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * gets all attributes form database related to this product
	 * where corelation type = 4
	 *
	 * @param int $uid Product uid
	 * @param array|int $correlationtypes
	 * @return array of Article UID
	 */
	public function getAttributes($uid, $correlationtypes) {
		if ((int)$uid) {
			if (!is_array($correlationtypes)) {
				$correlationtypes = array($correlationtypes);
			}

			$articleUids = array();
			$result = $this->database->exec_SELECTquery(
				'distinct(uid_foreign) as uid', $this->databaseAttributeRelationTable,
				'uid_local = ' . (int)$uid . ' and uid_correlationtype in (' . implode(',', $correlationtypes) . ')',
				'', $this->databaseAttributeRelationTable . '.sorting'
			);
			if ($this->database->sql_num_rows($result) > 0) {
				while (($data = $this->database->sql_fetch_assoc($result))) {
					$articleUids[] = (int) $data['uid'];
				}
				$this->database->sql_free_result($result);
				return $articleUids;
			} else {
				$this->error(
					'exec_SELECTquery(\'distinct(uid_foreign)\', ' . $this->databaseAttributeRelationTable . ', \'uid_local = ' . (int) $uid . '\'); returns no Result'
				);
				return FALSE;
			}
		}

		return FALSE;
	}

	/**
	 * Returns a list of uid's that are related to this product
	 *
	 * @param int $uid product uid
	 * @return array Product UIDs
	 */
	public function getRelatedProductUids($uid) {
		$relatedProducts = $this->database->exec_SELECTgetRows(
			'r.uid_foreign as uid', $this->databaseProductsRelatedTable . ' AS r',
			'r.uid_local = ' . (int)$uid,
			'',
			'r.sorting ASC',
			'',
			'uid'
		);
		return $relatedProducts;
	}

	/**
	 * Returns an array of sys_language_uids of the i18n products
	 * Only use in BE
	 *
	 * @param int $uid uid of the product we want to get the i18n languages from
	 * @return array $uid uids
	 */
	public function getL18nProducts($uid) {
		if (!(int)$uid) {
			return FALSE;
		}

		$this->uid = $uid;

		$rows = $this->database->exec_SELECTgetRows(
			't1.title, t1.uid, t2.flag, t2.uid as sys_language',
			$this->databaseTable . ' AS t1 LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
			'l18n_parent = ' . (int)$uid . ' AND deleted = 0'
		);

		return $rows;
	}

	/**
	 * Get first category as master
	 *
	 * @param int $uid
	 * @return int
	 */
	public function getMasterParentCategory($uid) {
		return reset($this->getParentCategories($uid));
	}

	/**
	 * Gets the parent categories of th
	 *
	 * @param int $uid uid of the product
	 * @return array parent categories for products
	 */
	public function getParentCategories($uid) {
		if (!(int)$uid) {
			$this->error('getParentCategories has not been delivered a proper uid');
			return NULL;
		}

		$uids = array();

			// read from sql
		$rows = (array)$this->database->exec_SELECTgetRows(
			'uid_foreign', $this->databaseCategoryRelationTable,
			'uid_local = ' . (int)$uid,
			'',
			'sorting ASC'
		);
		foreach ($rows as $row) {
			$uids[] = $row['uid_foreign'];
		}

		// If $uids is empty, the record might be a localized product
		if (count($uids) === 0) {
			$row = $this->database->exec_SELECTgetSingleRow(
				'l18n_parent',
				$this->databaseTable,
				'uid = ' . $uid
			);
			if (is_array($row) && isset($row['l18n_parent']) && (int)$row['l18n_parent'] > 0) {
				$uids = $this->getParentCategories($row['l18n_parent']);
			}
		}

		return $uids;
	}

	/**
	 * Returns the Manuafacturer Title to a given Manufacturere UID
	 *
	 * @param int $manufacturer
	 * @return string Title
	 */
	public function getManufacturerTitle($manufacturer) {
		$row = $this->database->exec_SELECTgetSingleRow(
			'*',
			'tx_commerce_manufacturer',
			'uid = ' . (int) $manufacturer
		);

		return is_array($row) && isset($row['title']) ? $row['title'] : '';
	}


	/**
	 * gets all articles form database related to this product
	 *
	 * @param int $uid Product uid
	 * @return array of Article UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getArticles instead
	 */
	public function get_articles($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getArticles($uid);
	}

	/**
	 * gets all attributes form database related to this product where corelation type = 4
	 *
	 * @param int $uid Product uid
	 * @param array|int $correlationtypes
	 * @return array of Article UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getAttributes instead
	 */
	public function get_attributes($uid, $correlationtypes) {
		t3lib_div::logDeprecatedFunction();
		return $this->getAttributes($uid, $correlationtypes);
	}

	/**
	 * Returns an array of sys_language_uids of the i18n products
	 * Only use in BE
	 *
	 * @param int $uid uid of the product we want to get the i18n languages from
	 * @return array $uid uids
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getL18nProducts instead
	 */
	public function get_l18n_products($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getL18nProducts($uid);
	}

	/**
	 * Returns a list of uid's that are related to this product
	 *
	 * @param int $uid product uid
	 * @return array Product UIDs
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getRelatedProductUids instead
	 */
	public function get_related_product_uids($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getRelatedProductUids($uid);
	}

	/**
	 * Gets the "master" category from this product
	 *
	 * @param int $uid Product UID
	 * @return int Categorie UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getMasterParentCategory instead
	 */
	public function get_parent_category($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getMasterParentCategory($uid);
	}

	/**
	 * Gets the "master" category from this product
	 *
	 * @param int $uid = Product UID
	 * @return int Categorie UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getParentCategories instead
	 */
	public function get_parent_categorie($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getMasterParentCategory($uid);
	}

	/**
	 * Gets the "master" category from this product
	 *
	 * @param uid = Product UID
	 * @return array of parent categories
	 * @deprecated sinde commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getParentCategories instead
	 */
	public function get_parent_categories($uid) {
		t3lib_div::logDeprecatedFunction();
		return array($this->getParentCategories($uid));
	}
}

class_alias('Tx_Commerce_Domain_Repository_ProductRepository', 'tx_commerce_db_product');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Repository/ProductRepository.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Repository/ProductRepository.php']);
}

?>
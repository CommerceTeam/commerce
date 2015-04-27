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
 * Database Class for tx_commerce_articles. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_article to get informations for articles.
 * Inherited from Tx_Commerce_Domain_Repository_Repository
 */
class Tx_Commerce_Domain_Repository_ArticleRepository extends Tx_Commerce_Domain_Repository_Repository {
	/**
	 * @var string
	 */
	public $databaseTable = 'tx_commerce_articles';

	/**
	 * @var string
	 */
	public $databaseAttributeRelationTable = 'tx_commerce_articles_article_attributes_mm';

	/**
	 * returns the parent Product uid
	 *
	 * @param integer $uid Article uid
	 * @param boolean $translationMode
	 * @return integer product uid
	 */
	public function getParentProductUid($uid, $translationMode = FALSE) {
		$data = parent::getData($uid, $translationMode);
		$result = FALSE;

		if ($data) {
				// Backwards Compatibility
			if ($data['uid_product']) {
				$result = $data['uid_product'];
			} elseif ($data['products_uid']) {
				$result = $data['products_uid'];
			}
		}
		return $result;
	}

	/**
	 * Get parent product uid
	 *
	 * @param int $uid
	 * @param bool $translationMode
	 * @return int
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_Domain_Repository_ArticleRepository::getParentProductUid instead
	 */
	public function get_parent_product_uid($uid, $translationMode = FALSE) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getParentProductUid($uid, $translationMode);
	}

	/**
	 * gets all prices form database related to this product
	 *
	 * @param integer $uid Article uid
	 * @param integer $count = Number of Articles for price_scale_amount, default 1
	 * @param string $orderField
	 * @return array of Price UID
	 */
	public function getPrices($uid, $count = 1, $orderField = 'price_net') {
		$uid = (int) $uid;
		$count = (int) $count;

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['priceOrder']) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'priceOrder\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/ArticleRepository.php\'][\'priceOrder\']
			');
			$hookObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['priceOrder']);
			if (method_exists($hookObj, 'priceOrder')) {
				$orderField = $hookObj->priceOrder($orderField);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ArticleRepository.php']['priceOrder']) {
			$hookObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ArticleRepository.php']['priceOrder']);
			if (method_exists($hookObj, 'priceOrder')) {
				$orderField = $hookObj->priceOrder($orderField);
			}
		}

		$additionalWhere = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['additionalPriceWhere']) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'additionalPriceWhere\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Repository/ArticleRepository.php\'][\'additionalPriceWhere\']
			');
			$hookObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['additionalPriceWhere']);
			if (method_exists($hookObj, 'additionalPriceWhere')) {
				$additionalWhere = $hookObj->additionalPriceWhere($this, $uid);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ArticleRepository.php']['additionalPriceWhere']) {
			$hookObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Repository/ArticleRepository.php']['additionalPriceWhere']);
			if (method_exists($hookObj, 'additionalPriceWhere')) {
				$additionalWhere = $hookObj->additionalPriceWhere($this, $uid);
			}
		}

		if ($uid > 0) {
			$priceUidList = array();
			$proofSql = $this->enableFields('tx_commerce_article_prices', $GLOBALS['TSFE']->showHiddenRecords);

			/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
			$database = $GLOBALS['TYPO3_DB'];

			$result = $database->exec_SELECTquery(
				'uid,fe_group',
				'tx_commerce_article_prices',
				'uid_article = ' . $uid . ' AND price_scale_amount_start <= ' . $count .
					' AND price_scale_amount_end >= ' . $count . $proofSql . $additionalWhere,
				'',
				$orderField
			);
			if ($database->sql_num_rows($result) > 0) {
				while (($data = $database->sql_fetch_assoc($result))) {
					$feGroups = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $data['fe_group'], TRUE);
					if (count($feGroups)) {
						foreach ($feGroups as $feGroup) {
							$priceUidList[(string)$feGroup][] = $data['uid'];
						}
					} else {
						$priceUidList[(string)$data['fe_group']][] = $data['uid'];
					}
				}
				$database->sql_free_result($result);
				return $priceUidList;
			} else {
				$this->error('exec_SELECTquery(\'uid\', \'tx_commerce_article_prices\', \'uid_article = \' . $uid); returns no Result');
				return FALSE;
			}
		}

		return FALSE;
	}

	/**
	 * gets all prices form database related to this product
	 *
	 * @param integer $uid Article uid
	 * @param integer $count = Number of Articles for price_scale_amount, default 1
	 * @param string $orderField
	 * @return array of Price UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_Domain_Repository_ArticleRepository::getPrices instead
	 */
	public function get_prices($uid, $count = 1, $orderField = 'price_net') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getPrices($uid, $count, $orderField);
	}

	/**
	 * Returns an array of all scale price amounts
	 *
	 * @param integer $uid Article uid
	 * @param integer $count
	 * @return array of Price UID
	 */
	public function getPriceScales($uid, $count = 1) {
		$uid = (int) $uid;
		$count = (int) $count;
		if ($uid > 0) {
			$priceUidList = array();
			$proofSql = $this->enableFields('tx_commerce_article_prices', $GLOBALS['TSFE']->showHiddenRecords);

			/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
			$database = $GLOBALS['TYPO3_DB'];

			$result = $database->exec_SELECTquery('uid,price_scale_amount_start, price_scale_amount_end',
				'tx_commerce_article_prices',
				'uid_article = ' . $uid . ' AND price_scale_amount_start >= ' . $count . $proofSql
			);
			if ($database->sql_num_rows($result) > 0) {
				while (($data = $database->sql_fetch_assoc($result))) {
					$priceUidList[$data['price_scale_amount_start']][$data['price_scale_amount_end']] = $data['uid'];
				}
				$database->sql_free_result($result);
				return $priceUidList;
			} else {
				$this->error('exec_SELECTquery(\'uid\', \'tx_commerce_article_prices\', \'uid_article = \' . $uid); returns no Result');
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * gets all attributes from this product
	 *
	 * @param integer $uid Product uid
	 * @return array of attribute UID
	 */
	public function getAttributes($uid) {
		return parent::getAttributes($uid, '');
	}

	/**
	 * gets all attributes from this product
	 *
	 * @param integer $uid Product uid
	 * @return array of attribute UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_Domain_Repository_ArticleRepository::getAttributes instead
	 */
	public function get_attributes($uid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getAttributes($uid);
	}

	/**
	 * Returns the attribute Value from the given Article attribute pair
	 *
	 * @param integer $uid Article UID
	 * @param integer $attributeUid Attribute UID
	 * @param boolean $valueListAsUid if true, returns not the value from the valuelist, instaed the uid
	 * @return string
	 */
	public function getAttributeValue($uid, $attributeUid, $valueListAsUid = FALSE) {
		$uid = (int) $uid;
		$attributeUid = (int) $attributeUid;

		if ($uid > 0) {
			// First select attribute, to detect if is valuelist
			$proofSql = $this->enableFields('tx_commerce_attributes', $GLOBALS['TSFE']->showHiddenRecords);

			/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
			$database = $GLOBALS['TYPO3_DB'];

			$result = $database->exec_SELECTquery(
				'DISTINCT uid,has_valuelist',
				'tx_commerce_attributes',
				'uid = ' . (int) $attributeUid . $proofSql
			);
			if ($database->sql_num_rows($result) == 1) {
				$returnData = $database->sql_fetch_assoc($result);
				if ($returnData['has_valuelist'] == 1) {
						// Attribute has a valuelist, so do separate query
					$attributeResult = $database->exec_SELECTquery(
						'DISTINCT distinct tx_commerce_attribute_values.value,tx_commerce_attribute_values.uid',
						'tx_commerce_articles_article_attributes_mm, tx_commerce_attribute_values',
						'tx_commerce_articles_article_attributes_mm.uid_valuelist = tx_commerce_attribute_values.uid' .
							' AND uid_local = ' . $uid .
							' AND uid_foreign = ' . $attributeUid
					);
					if ($database->sql_num_rows($attributeResult) == 1) {
						$valueData = $database->sql_fetch_assoc($attributeResult);
						if ($valueListAsUid == TRUE) {
							return $valueData['uid'];
						} else {
							return $valueData['value'];
						}
					}
				} else {
						// attribute has no valuelist, so do normal query
					$attributeResult = $database->exec_SELECTquery(
						'DISTINCT value_char,default_value',
						'tx_commerce_articles_article_attributes_mm',
						'uid_local = ' . $uid . ' AND uid_foreign = ' . $attributeUid
					);
					if ($database->sql_num_rows($attributeResult) == 1) {
						$valueData = $database->sql_fetch_assoc($attributeResult);
						if ($valueData['value_char']) {
							return $valueData['value_char'];
						} else {
							return $valueData['default_value'];
						}
					} else {
						$this->error('More than one Value for thsi attribute');
					}
				}
			} else {
				$this->error('Could not get Attribute for call');
			}
		} else {
			$this->error('no Uid');
		}

		return '';
	}

	/**
	 * returns the supplier name to a given UID, selected from tx_commerce_supplier
	 *
	 * @param integer $supplierUid
	 * @return string Supplier name
	 */
	public function getSupplierName($supplierUid) {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
		$database = $GLOBALS['TYPO3_DB'];

		if ($supplierUid > 0) {
			$result = $database->exec_SELECTquery(
				'title',
				'tx_commerce_supplier',
				'uid = ' . (int) $supplierUid
			);
			if ($database->sql_num_rows($result) == 1) {
				$returnData = $database->sql_fetch_assoc($result);
				return $returnData['title'];
			}
		}
		return FALSE;
	}
}

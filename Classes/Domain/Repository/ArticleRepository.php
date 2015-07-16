<?php
namespace CommerceTeam\Commerce\Domain\Repository;
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
 * Database Class for tx_commerce_articles. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_article to get informations for articles.
 * Inherited from \CommerceTeam\Commerce\Domain\Repository\Repository
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\ArticleRepository
 *
 * @author 2005-2012 Ingo Schmitt <is@marketing-factory.de>
 */
class ArticleRepository extends Repository {
	/**
	 * Database table
	 *
	 * @var string
	 */
	public $databaseTable = 'tx_commerce_articles';

	/**
	 * Database relation table
	 *
	 * @var string
	 */
	public $databaseAttributeRelationTable = 'tx_commerce_articles_article_attributes_mm';

	/**
	 * Returns the parent Product uid
	 *
	 * @param int $uid Article uid
	 * @param bool $translationMode Translation mode
	 *
	 * @return int product uid
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
	 * Gets all prices form database related to this product
	 *
	 * @param int $uid Article uid
	 * @param int $count Number of Articles for price_scale_amount, default 1
	 * @param string $orderField Order field
	 *
	 * @return array of Price UID
	 */
	public function getPrices($uid, $count = 1, $orderField = 'price_net') {
		$uid = (int) $uid;
		$count = (int) $count;
		$additionalWhere = '';

		$hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook('Domain/Repository/ArticleRepository', 'getPrices');
		if (is_object($hookObject) && method_exists($hookObject, 'priceOrder')) {
			$orderField = $hookObject->priceOrder($orderField);
		}
		if (is_object($hookObject) && method_exists($hookObject, 'additionalPriceWhere')) {
			$additionalWhere = $hookObject->additionalPriceWhere($this, $uid);
		}

		if ($uid > 0) {
			$priceUidList = array();
			$proofSql = $this->enableFields('tx_commerce_article_prices', $this->getFrontendController()->showHiddenRecords);

			$database = $this->getDatabaseConnection();

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
					if (!empty($feGroups)) {
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
	 * Returns an array of all scale price amounts
	 *
	 * @param int $uid Article uid
	 * @param int $count Count
	 *
	 * @return array of Price UID
	 */
	public function getPriceScales($uid, $count = 1) {
		$uid = (int) $uid;
		$count = (int) $count;
		if ($uid > 0) {
			$priceUidList = array();
			$proofSql = $this->enableFields('tx_commerce_article_prices', $this->getFrontendController()->showHiddenRecords);

			$database = $this->getDatabaseConnection();

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
	 * Gets all attributes from this product
	 *
	 * @param int $uid Product uid
	 *
	 * @return array of attribute UID
	 */
	public function getAttributes($uid) {
		return parent::getAttributes($uid, '');
	}

	/**
	 * Returns the attribute Value from the given Article attribute pair
	 *
	 * @param int $uid Article UID
	 * @param int $attributeUid Attribute UID
	 * @param bool $valueListAsUid If true, returns not the value from
	 *	the valuelist, instead the uid
	 *
	 * @return string
	 */
	public function getAttributeValue($uid, $attributeUid, $valueListAsUid = FALSE) {
		$uid = (int) $uid;
		$attributeUid = (int) $attributeUid;

		if ($uid > 0) {
			// First select attribute, to detecxt if is valuelist
			$proofSql = $this->enableFields('tx_commerce_attributes', $this->getFrontendController()->showHiddenRecords);

			$database = $this->getDatabaseConnection();

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
	 * Rreturns the supplier name to a given UID, selected from tx_commerce_supplier
	 *
	 * @param int $supplierUid Supplier uid
	 *
	 * @return string Supplier name
	 */
	public function getSupplierName($supplierUid) {
		$database = $this->getDatabaseConnection();

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

	/**
	 * Find article by classname
	 *
	 * @param string $classname Classname
	 *
	 * @return array
	 */
	public function findByClassname($classname) {
		return (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'uid',
			'tx_commerce_articles',
			'classname = \'' . $classname . '\''
		);
	}
}

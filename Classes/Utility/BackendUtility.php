<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2012 Thomas Hempel <thomas@work.de>
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
 * This metaclass provides several helper methods for handling relations in the backend.
 * This is quite useful because attributes for an article can come from products and categories.
 * And products can be assigned to several categories and a category can have a lot of parent
 * categories.
 */
class Tx_Commerce_Utility_BackendUtility {
	/**
	 * This gets all categories for a product from the database (even those that are not direct).
	 *
	 * @param integer $pUid The UID of the product
	 * @return array An array of UIDs of all categories for this product
	 */
	public function getCategoriesForProductFromDB($pUid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// get categories that are directly stored in the product dataset
		$pCategories = $database->exec_SELECTquery('uid_foreign', 'tx_commerce_products_categories_mm', 'uid_local=' . (int) $pUid);
		$result = array();
		while ($cUid = $database->sql_fetch_assoc($pCategories)) {
			$this->getParentCategories($cUid['uid_foreign'], $result);
		}
		return $result;
	}

	/**
	 * Gets all direct parent categories of a product
	 *
	 * @param integer $uid uid of the product
	 * @return array
	 */
	public function getProductParentCategories($uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$pCategories = $database->exec_SELECTquery('uid_foreign', 'tx_commerce_products_categories_mm', 'uid_local=' . $uid);

		$result = array();
		while ($cUid = $database->sql_fetch_assoc($pCategories)) {
			$result[] = $cUid['uid_foreign'];
		}

		return $result;
	}

	/**
	 * Fetches all attribute relation from the database that are assigned to a product specified
	 * through pUid. It can also fetch information about the attribute and a list of attribute
	 * values if the attribute has a valuelist.
	 *
	 * @param integer $pUid: The uid of the product
	 * @param boolean $separateCT1: If this is true, all attributes with ct1 will be saved in a separated result section
	 * @param boolean $addAttributeData: If true, all information about the attributes will be fetched from the database (default is false)
	 * @param boolean $getValueListData: If this is true and additional data is fetched and an attribute has a valuelist, this gets the values for the list (default is false)
	 * @return array of attributes
	 */
	public function getAttributesForProduct($pUid, $separateCT1 = FALSE, $addAttributeData = FALSE, $getValueListData = FALSE) {
		if (!$pUid) {
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = & $GLOBALS['TYPO3_DB'];

			// get all attributes for the product
		$res = $database->exec_SELECTquery(
			'distinct *',
			'tx_commerce_products_attributes_mm',
			'uid_local=' . (int) $pUid,
			'',
			'sorting, uid_foreign DESC, uid_correlationtype ASC'
		);
		if ($database->sql_num_rows($res) == 0) {
			return FALSE;
		}

		$result = array();
		while ($relData = $database->sql_fetch_assoc($res)) {
			if ($addAttributeData) {
					// fetch the data from the attribute table
				$aRes = $database->exec_SELECTquery(
					'*',
					'tx_commerce_attributes',
					'uid=' . (int) $relData['uid_foreign'] . $this->enableFields('tx_commerce_attributes'),
					'',
					'uid'
				);
				$aData = $database->sql_fetch_assoc($aRes);
				$relData['attributeData'] = $aData;
				if ($aData['has_valuelist'] && $getValueListData) {
						// fetch values for this valuelist entry
					$vlRes = $database->exec_SELECTquery(
						'*',
						'tx_commerce_attribute_values',
						'attributes_uid=' . (int) $aData['uid'] . $this->enableFields('tx_commerce_attribute_values'),
						'',
						'uid'
					);
					$vlData = array();
					while ($vlEntry = $database->sql_fetch_assoc($vlRes)) {
						$vlData[$vlEntry['uid']] = $vlEntry;
					}

					$relData['valueList'] = $vlData;
				}

				$relData['has_valuelist'] = $aData['has_valuelist'] ? '1' : '0';
			}

			if (empty($relData)) {
				continue;
			}

			if ($separateCT1) {
				if ($relData['uid_correlationtype'] == 1 && $relData['attributeData']['has_valuelist'] == 1) {
					$result['ct1'][] = $relData;
				} else {
					$result['rest'][] = $relData;
				}
			} else {
				$result[] = $relData;
			}
		}

		return $result;
	}

	/** CATEGORIES **/

	/**
	 *  Fetch all parent categories for a given category.
	 *
	 * @param integer $cUid: The UID of the category that is the startingpoint
	 * @param array $cUidList: A list of category UIDs. PASSED BY REFERENCE
	 * @param integer $dontAdd: A single UID, if this is found in the parent results, it's not added to the list
	 * @param integer $excludeUid: If the current cUid is like this UID the cUid is not processed at all
	 * @param boolean $recursive: If true, this method calls itself for each category if finds
	 * @return void
	 */
	public function getParentCategories($cUid, &$cUidList, $dontAdd = 0, $excludeUid = 0, $recursive = TRUE) {
		if (strlen((string) $cUid) > 0) {
			/** @var t3lib_db $database */
			$database = $GLOBALS['TYPO3_DB'];

				// add the submitted uid to the list if it is bigger than 0 and not already in the list
			if ($cUid > 0 && $cUid != $excludeUid) {
				if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) {
					$cUidList[] = $cUid;
				}

				$res = $database->exec_SELECTquery(
					'uid_foreign',
					'tx_commerce_categories_parent_category_mm',
					'uid_local=' . (int) $cUid,
					'',
					'uid_foreign'
				);

				while ($relData = $database->sql_fetch_assoc($res)) {
					if ($recursive) {
						$this->getParentCategories($relData['uid_foreign'], $cUidList, $cUid, $excludeUid);
					} else {
						$cUid = $relData['uid_foreign'];
						if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) {
							$cUidList[] = $cUid;
						}
					}
				}
			}
		}
	}

	/**
	 * Get all categories that have this one as parent.
	 *
	 * @param integer $cUid: The UID of the category that is the startingpoint
	 * @param array $cUidList: A list of category UIDs. PASSED BY REFERENCE
	 * @param integer $dontAdd: A single UID, if this is found in the parent results, it's not added to the list
	 * @param integer $excludeUid: If the current cUid is like this UID the cUid is not processed at all
	 * @param boolean $recursive: If true, this method calls itself for each category if finds
	 * @return void
	 */
	public function getChildCategories($cUid, &$cUidList, $dontAdd = 0, $excludeUid = 0, $recursive = TRUE) {
		if (strlen((string) $cUid) > 0) {
			/** @var t3lib_db $database */
			$database = $GLOBALS['TYPO3_DB'];

				// add the submitted uid to the list if it is bigger than 0 and not already in the list
			if ($cUid > 0 && $cUid != $excludeUid) {
				if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) {
					$cUidList[] = $cUid;
				}

				$res = $database->exec_SELECTquery(
					'uid_local',
					'tx_commerce_categories_parent_category_mm',
					'uid_foreign=' . (int) $cUid,
					'',
					'uid_local'
				);

				if ($res) {
					while ($relData = $database->sql_fetch_assoc($res)) {
						if ($recursive) {
							$this->getChildCategories($relData['uid_local'], $cUidList, $cUid, $excludeUid);
						} else {
							$cUid = $relData['uid_local'];
							if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) {
								$cUidList[] = $cUid;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Get all parent categories for a list of given categories. This method
	 * calls the "getParentCategories" method. That one will work recursive.
	 * The result will be written into the argument cUidList.
	 *
	 * @param array $cUidList: The list of category UIDs PASSED BY REFERENCE
	 * @return void
	 */
	public function getParentCategoriesFromList(&$cUidList) {
		if (is_array($cUidList)) {
			foreach ($cUidList as $cUid) {
				$this->getParentCategories($cUid, $cUidList);
			}
		}
	}

	/**
	 * Returns an array with the data for a single category.
	 *
	 * @param integer $cUid: The UID of the category
	 * @param string $select: The WHERE part of the query
	 * @param string $groupBy: The GROUP BY part of the query
	 * @param string $orderBy: The ORDER BY part of the query
	 * @return array An associative array with the data of the category
	 */
	public function getCategoryData($cUid, $select = '*', $groupBy = '', $orderBy = '') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECTquery(
			$select,
			'tx_commerce_categories',
			'uid=' . (int) $cUid,
			$groupBy,
			$orderBy
		);
		return $database->sql_fetch_assoc($result);
	}

	/**
	 * Returns all attributes for a list of categories.
	 *
	 * @param array $catList: A list of category UIDs
	 * @param integer $ct: the correlationtype (can be null)
	 * @param string $uidField: The name of the field in the excludeAttributes array that holds the uid of the attributes
	 * @param array $excludeAttributes: Array with attributes (the method expects a field called uid_foreign)
	 * @return array of attributes
	 */
	public function getAttributesForCategoryList($catList, $ct = NULL, $uidField = 'uid', $excludeAttributes = array()) {
		$result = array();
		if (!is_array($catList)) {
			return $result;
		}

		foreach ($catList as $catUid) {
			$attributes = $this->getAttributesForCategory($catUid, $ct, $excludeAttributes, $uidField);
			if (is_array($attributes)) {
				foreach ($attributes as $attribute) {
					$result[] = $attribute;
					$excludeAttributes[] = $attribute;
				}
			}
		}

		return $result;
	}

	/**
	 * This fetches all attributes that are assigned to a category.
	 *
	 * @param integer $categoryUid: the uid of the category
	 * @param integer $correlationtype: the correlationtype (can be null)
	 * @param array $excludeAttributes: Array with attributes (the method expects a field called uid_foreign)
	 * @return array of attributes
	 */
	public function getAttributesForCategory($categoryUid, $correlationtype = NULL, $excludeAttributes = NULL) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// build the basic query
		$where = 'uid_local=' . $categoryUid;

			// select only for a special correlation type?
		if ($correlationtype != NULL) {
			$where .= ' AND uid_correlationtype=' . (int) $correlationtype;
		}

			// should we exclude some attributes
		if (is_array($excludeAttributes) && count($excludeAttributes) > 0) {
			$eAttributes = array();
			foreach ($excludeAttributes as $eAttribute) {
				$eAttributes[] = (int) $eAttribute['uid_foreign'];
			}
			$where .= ' AND uid_foreign NOT IN (' . implode(',', $eAttributes) . ')';
		}

			// execute the query
		$res = $database->exec_SELECTquery(
			'*',
			'tx_commerce_categories_attributes_mm',
			$where,
			'', 'sorting'
		);

			// build the result and return it...
		$result = array();
		while ($attribute = $database->sql_fetch_assoc($res)) {
			$result[] = $attribute;
		}
		return $result;
	}

	/** ATTRIBUTES **/

	/**
	 * Returns the title of an attribute.
	 *
	 * @param integer $aUid: The UID of the attribute
	 * @return string The title of the attribute as string
	 */
	public function getAttributeTitle($aUid) {
		$attribute = $this->getAttributeData($aUid, 'title');
		return $attribute['title'];
	}

	/**
	 * Returns a list of Titles for a list of attributes.
	 *
	 * @param array $attributeList: An array of attributes (complete datasets with at least one field that contains the UID of an attribute)
	 * @param string $uidField: The name of the array key in the attributeList that contains the UID
	 * @return array of attribute titles as strings
	 */
	public function getAttributeTitles($attributeList, $uidField = 'uid') {
		$result = array();
		if (is_array($attributeList) && count($attributeList) > 0) {
			foreach ($attributeList as $attribute) {
				$result[] = $this->getAttributeTitle($attribute[$uidField]);
			}
		}
		return $result;
	}

	/**
	 * Returns the complete dataset of an attribute. You can select which fields should be fetched from the database.
	 *
	 * @param integer $aUid: The UID of the attribute
	 * @param string $select: Select here, which fields should be fetched (default is *)
	 * @return array associative array with the attributeData
	 */
	public function getAttributeData($aUid, $select = '*') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		return $database->exec_SELECTgetSingleRow($select, 'tx_commerce_attributes', 'uid = ' . (int) $aUid);
	}

	/**
	 * This fetches the value for an attribute. It fetches the "default_value" from the table
	 * if the attribute has no valuelist, otherwise it fetches the title from the attribute_values
	 * table.
	 * You can submit only an attribute uid, then the mehod fetches the data from the databse itself,
	 * or you submit the data from the relation table and the data from the attribute table if this
	 * data is already present.
	 *
	 * @param integer $pUid: The product UID
	 * @param integer $aUid: The attribute UID
	 * @param string $relationTable: The table where the relations between prodcts and attributes are stored
	 * @param array $relationData: The relation dataset between the product and the attribute (default NULL)
	 * @param array $attributeData: The meta data (has_valuelist, unit) for the attribute you want to get the value from (default NULL)
	 * @return string The value of the attribute. It's maybe appended with the unit of the attribute
	 */
	public function getAttributeValue($pUid, $aUid, $relationTable, $relationData = NULL, $attributeData = NULL) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if ($relationData == NULL || $attributeData == NULL) {
				// data from database if one of the arguments is NULL. This nesccessary
				// to keep the data consistant
			$relRes = $database->exec_SELECTquery(
				'uid_valuelist,default_value,value_char',
				$relationTable,
				'uid_local = ' . (int) $pUid . ' AND uid_foreign = ' . (int) $aUid
			);
			$relationData = $database->sql_fetch_assoc($relRes);

			$attributeData = $this->getAttributeData($aUid, 'has_valuelist,unit');
		}

		if ($attributeData['has_valuelist'] == '1') {
			if ($attributeData['multiple'] == 1) {
				$result = array();
				if (is_array($relationData)) {
					foreach ($relationData as $relation) {
						$valueRes = $database->exec_SELECTquery(
							'value',
							'tx_commerce_attribute_values',
							'uid = ' . (int) $relation['uid_valuelist'] . $this->enableFields('tx_commerce_attribute_values')
						);
						$value = $database->sql_fetch_assoc($valueRes);
						$result[] = $value['value'];
					}
				}
				return '<ul><li>' . implode('</li><li>', Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($result)) . '</li></ul>';

			} else {
					// fetch data from attribute values table
				$valueRes = $database->exec_SELECTquery(
					'value',
					'tx_commerce_attribute_values',
					'uid = ' . (int) $relationData['uid_valuelist'] . $this->enableFields('tx_commerce_attribute_values')
				);
				$value = $database->sql_fetch_assoc($valueRes);
				return $value['value'];
			}
		} elseif (!empty($relationData['value_char'])) {
				// the value is in field default_value
			return $relationData['value_char'] . ' ' . $attributeData['unit'];
		} else {
			return $relationData['default_value'] . ' ' . $attributeData['unit'];
		}
	}

	/**
	 * Returns the correlationtype of a special attribute inside a product.
	 *
	 * @param integer $aUid: The UID of the attribute
	 * @param integer $pUid: The UID of the product
	 * @return integer The correlationtype
	 */
	public function getCtForAttributeOfProduct($aUid, $pUid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		$ctRes = $database->exec_SELECTquery(
			'uid_correlationtype',
			'tx_commerce_products_attributes_mm',
			'uid_local = ' . (int) $pUid . ' AND uid_foreign = ' . (int) $aUid
		);
		$uidCT = $database->sql_fetch_assoc($ctRes);
		return $uidCT['uid_correlationtype'];
	}


	/** ARTICLES **/

	/**
	 * Return all articles that where created from a given product.
	 *
	 * @param integer $pUid: The UID of the product
	 * @param string $additionalWhere
	 * @param string $orderBy
	 * @return array of article datasets as assoc array or false if nothing was found
	 */
	public function getArticlesOfProduct($pUid, $additionalWhere = '', $orderBy = '') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$where = 'uid_product=' . (int) $pUid;

		$where .= ' AND deleted=0';

		if ($additionalWhere != '') {
			$where .= ' AND ' . $additionalWhere;
		}

		$res = $database->exec_SELECTquery('*', 'tx_commerce_articles', $where, '', $orderBy);
		if ($res && $database->sql_num_rows($res) > 0) {
			$result = array();
			while ($article = $database->sql_fetch_assoc($res)) {
				$result[] = $article;
			}
			return $result;
		} else {
			return FALSE;
		}
	}

	/**
	 * Return all articles that where created from a given product.
	 *
	 * @param integer $pUid: The UID of the product
	 * @param string $additionalWhere
	 * @param string $orderBy
	 * @return array of article UIDs, ready to implode for coma separed list
	 */
	public static function getArticlesOfProductAsUidList($pUid, $additionalWhere = '', $orderBy = '') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$where = 'uid_product = ' . (int) $pUid . ' AND deleted = 0';

		if ($additionalWhere != '') {
			$where .= ' AND ' . $additionalWhere;
		}

		$res = $database->exec_SELECTquery('uid', 'tx_commerce_articles', $where, '', $orderBy);
		if ($res && $database->sql_num_rows($res) > 0) {
			$result = array();
			while ($article = $database->sql_fetch_assoc($res)) {
				$result[] = $article['uid'];
			}
			return $result;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns the product from which an article was created.
	 *
	 * @param integer $aUid: Article UID
	 * @param string $getProductData: which fields should be returned of the product. This is a comma separated list (default *)
	 * @return integer the "getProductData" param is empty, this returnss the UID as integer, otherwise it returns an associative array of the dataset
	 */
	public function getProductOfArticle($aUid, $getProductData = '*') {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$where = 'uid=' . (int) $aUid . ' AND deleted=0';

		$proRes = $database->exec_SELECTquery('uid_product', 'tx_commerce_articles', $where);
		$proRes = $database->sql_fetch_assoc($proRes);

		if (strlen($getProductData) > 0) {
			$where = 'uid = ' . (int) $proRes['uid_product'];
			$proRes = $database->exec_SELECTquery($getProductData, 'tx_commerce_products', $where);

			$proRes = $database->sql_fetch_assoc($proRes);
			return $proRes;
		} else {
			return $proRes['uid_product'];
		}
	}

	/**
	 * Returns all attributes for an article
	 *
	 * @param integer $aUid: The article UID
	 * @param integer $ct: The correlationtype
	 * @param array $excludeAttributes: An array of relation datasets where the field "uid_foreign" is the UID of the attribute you don't want to get
	 * @return array of attributes
	 */
	public function getAttributesForArticle($aUid, $ct = NULL, $excludeAttributes = NULL) {
			// build the basic query
		$where = 'uid_local=' . $aUid;

		if ($ct != NULL) {
			$pUid = $this->getProductOfArticle($aUid, 'uid');

			$productAttributes = $this->getAttributesForProduct($pUid['uid']);
			$ctAttributes = array();
			if (is_array($productAttributes)) {
				foreach ($productAttributes as $productAttribute) {
					if ($productAttribute['uid_correlationtype'] == $ct) {
						$ctAttributes[] = $productAttribute['uid_foreign'];
					}
				}
				if (count($ctAttributes) > 0) {
					$where .= ' AND uid_foreign IN (' . implode(',', $ctAttributes) . ')';
				}
			}
		}

			// should we exclude some attributes
		if (is_array($excludeAttributes) && count($excludeAttributes) > 0) {
			$eAttributes = array();
			foreach ($excludeAttributes as $eAttribute) {
				$eAttributes[] = (int) $eAttribute['uid_foreign'];
			}
			$where .= ' AND uid_foreign NOT IN (' . implode(',', $eAttributes) . ')';
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// execute the query
		$result = (array) $database->exec_SELECTgetRows(
			'*',
			'tx_commerce_articles_article_attributes_mm',
			$where
		);

		return $result;
	}

	/**
	 * Returns the hash value for the ct1 select attributes for an article.
	 *
	 * @param integer $aUid: the uid of the article
	 * @param array $fullAttributeList: The list of uids for the attributes that are assigned to the article
	 * @return string
	 */
	public function getArticleHash($aUid, $fullAttributeList) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$hashData = array();

		if (count($fullAttributeList) > 0) {
			$res = $database->exec_SELECTquery(
				'*',
				'tx_commerce_articles_article_attributes_mm',
				'uid_local=' . (int) $aUid . ' AND uid_foreign IN (' . implode(',', $fullAttributeList) . ')'
			);

			while ($attributeData = $database->sql_fetch_assoc($res)) {
				$hashData[$attributeData['uid_foreign']] = $attributeData['uid_valuelist'];
			}
			asort($hashData);
		}

		$hash = md5(serialize($hashData));

		return $hash;
	}

	/**
	 * Updates an article and recalculates the hash value for the assigned attributes.
	 *
	 * @param integer $aUid: the uid of the article
	 * @param array $fullAttributeList: The list of uids for the attributes that are assigned to the article
	 * @return void
	 */
	public function updateArticleHash($aUid, $fullAttributeList = NULL) {
		if ($fullAttributeList == NULL) {
			$fullAttributeList = array();
			$articleAttributes = $this->getAttributesForArticle($aUid, 1);
			if (count($articleAttributes) > 0) {
				foreach ($articleAttributes as $articleAttribute) {
					$fullAttributeList[] = $articleAttribute['uid_foreign'];
				}
			}
		}

		$hash = $this->getArticleHash($aUid, $fullAttributeList);

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// update the article
		$database->exec_UPDATEquery(
			'tx_commerce_articles',
			'uid=' . $aUid,
			array ('attribute_hash' => $hash)
		);
	}

	/** Diverse **/

	/**
	 * proofs if there are non numeric chars in it
	 *
	 * @param string $data: string to check for a number
	 * @return integer length of wrong chars
	 */
	public function isNumber($data) {
		$charArray = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0, '.');
		return strlen(str_replace($charArray, array(), $data));
	}

	/**
	 * This method returns the last part of a string.
	 * It splits up the string at the underscores.
	 * If the key doesn't contain any underscores, it returns
	 * the integer of the key.
	 *
	 * @param string $key: The key string (e.g.: bla_fasel_12)
	 * @param array $keyData: the result from the explode method (PASSED BY REFERENCE)
	 * @return integer value of the last part from the key
	 */
	public function getUidFromKey($key, &$keyData) {
		$uid = 0;

		if (strpos($key, '_') === FALSE) {
			$uid = (int) $key;
		} else {
			$keyData = @explode('_', $key);
			if (is_array($keyData)) {
				$uid = (int) $keyData[(count($keyData) - 1)];
			}
		}

		return $uid;
	}

	/**
	 * Searches for a string in an array of arrays.
	 *
	 * @param string $needle: The string you want to check for
	 * @param array $array: The array you want to search in
	 * @param string $field: The fieldname of the inside arrays in the search array
	 * @return boolean if the needle was found, otherwise false
	 */
	public function checkArray($needle, $array, $field) {
		$result = FALSE;

		if (is_array($array)) {
			foreach ($array as $entry) {
				if ($needle == $entry[$field]) {
					$result = TRUE;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the "enable fields" for a sql query.
	 *
	 * @param string $table: The table for the query
	 * @param boolean $getDeleted: Flag if deleted entries schould be returned
	 * @return string A SQL-string with the enable fields
	 */
	public function enableFields($table, $getDeleted = FALSE) {
		$result = t3lib_befunc::BEenableFields($table);
		if (!$getDeleted) {
			$result .= t3lib_befunc::deleteClause($table);
		}
		return $result;
	}

	/**
	 * Returns a list of UID from a list of strings that contains UIDs.
	 *
	 * @see $this->getUidFromKey
	 *
	 * @param array $list: The list of strings
	 * @return array with extracted UIDs
	 */
	public static function getUidListFromList($list) {
		$result = array();
		if (is_array($list)) {
			foreach ($list as $item) {
				$uid = self::getUidFromKey($item, $keyData);
				if ($uid > 0) {
					$result[] = $uid;
				}
			}
		}
		return $result;
	}

	/**
	 * Saves all relations between two tables. For example all relations between products and articles.
	 *
	 * @param integer $uid_local: The uid_local in the mm table
	 * @param array $relationData: An array with the data that should be stored additionally in the relation table
	 * @param string $relationTable: The table where the relations are stored
	 * @param boolean $delete: Delete all old relations
	 * @param boolean $withReference: If true, the field "is_reference" is inserted into the database
	 * @return void
	 */
	public static function saveRelations($uid_local, $relationData, $relationTable, $delete = FALSE, $withReference = TRUE) {
		$delWhere = array();
		$counter = 1;

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (is_array($relationData)) {
			foreach ($relationData as $relation) {
				$where = 'uid_local=' . (int) $uid_local;
				$dataArray = array ('uid_local' => (int) $uid_local);

				if (is_array($relation)) {
					foreach ($relation as $key => $data) {
						$dataArray[$key] = $data;
						$where .= ' AND ' . $key . '=\'' . $data . '\'';
					}
				}
				if ($withReference && ($counter > 1)) {
					$dataArray['is_reference'] = 1;
					$where .= ' AND is_reference=1';
				}

				$dataArray['sorting'] = $counter;
				$counter++;

				$checkRes = $database->exec_SELECTquery('uid_local', $relationTable, $where);
				$exists = $database->sql_num_rows($checkRes) > 0;

				if (!$exists) {
					$database->exec_INSERTquery($relationTable, $dataArray);
				} else {
					$database->exec_UPDATEquery($relationTable, $where, array('sorting' => $counter));
				}

				if (isset($relation['uid_foreign'])) {
					$delClause = 'uid_foreign=' . $relation['uid_foreign'];
					if (isset($relation['uid_correlationtype'])) {
						$delClause .= ' AND uid_correlationtype=' . $relation['uid_correlationtype'];
					}
					$delWhere[] = $delClause;
				}
			}
		}

		if ($delete && (count($delWhere) > 0)) {
			$where = '';
			if (count($delWhere) > 0) {
				$where = ' AND NOT ((' . implode(') OR (', $delWhere) . '))';
			}
			$database->exec_DELETEquery($relationTable, 'uid_local=' . $uid_local . $where);
		}
	}

	/**
	 * Get all existing correlation types.
	 *
	 * @return array with correlation type entities
	 */
	public function getAllCorrelationTypes() {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$ctRes = $database->exec_SELECTquery('uid', 'tx_commerce_attribute_correlationtypes', '1');
		$result = array();
		while ($ct = $database->sql_fetch_assoc($ctRes)) {
			$result[] = $ct;
		}
		return $result;
	}

	/**
	 * Updates the XML of an article. This is neccessary because if we change anything in a category
	 * we also change all related products and articles. This has to be done in two steps.
	 * At first we have to update the relations in the database. But if we do so, the user won't recognize the
	 * changes in the backend because of the usage of flexforms.
	 * So this method, updates all the flexform value data in the database for the articles we change.
	 *
	 * @see $this->updateXML
	 *
	 * @param array $articleRelations: The relation dataset for the article
	 * @param boolean $add: If this is true, we fetch the existing data before. Otherwise we overwrite it
	 * @param integer $articleUid: The UID of the article
	 * @param integer $productUid: The UID of the product
	 * @return void
	 */
	public function updateArticleXML($articleRelations, $add = FALSE, $articleUid = NULL, $productUid = NULL) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$xmlData = array();
		if ($add && is_numeric($articleUid)) {
			$result = $database->exec_SELECTquery('attributesedit', 'tx_commerce_articles', 'uid=' . (int) $articleUid);
			$xmlData = $database->sql_fetch_assoc($result);
			$xmlData = t3lib_div::xml2array($xmlData['attributesedit']);
		}

		$relationData = array();
		/**
		 * Build Relation Data
		 */
		$resRelationData = NULL;
		if ($productUid) {
			$resRelationData = $database->exec_SELECTquery(
				'tx_commerce_articles_article_attributes_mm.*',
				'tx_commerce_articles, tx_commerce_articles_article_attributes_mm',
				' tx_commerce_articles.uid = tx_commerce_articles_article_attributes_mm.uid_local and tx_commerce_articles.uid_product = ' . (int) $productUid
			);
		}

		if ($articleUid) {
			$resRelationData = $database->exec_SELECTquery(
				'tx_commerce_articles_article_attributes_mm.*',
				'tx_commerce_articles, tx_commerce_articles_article_attributes_mm',
				' tx_commerce_articles.uid = tx_commerce_articles_article_attributes_mm.uid_local and tx_commerce_articles.uid = ' . (int) $articleUid
			);
		}

		if ($resRelationData !== NULL && $database->sql_num_rows($resRelationData) > 0) {
			while ($relationRows = $database->sql_fetch_assoc($resRelationData)) {
				$relationData[] = $relationRows;
			}
		}

		if (count($relationData)) {
			foreach ($articleRelations as $articleRelation) {
				if ($articleRelation['uid_valuelist'] != 0) {
					$value = $articleRelation['uid_valuelist'];
				} elseif (!empty($articleRelation['value_char'])) {
					$value = $articleRelation['value_char'];
				} else {
					if ($articleRelation['default_value'] <> 0) {
						$value = $articleRelation['default_value'];
					} else {
						$value = '';
					}
				}

				$xmlData['data']['sDEF']['lDEF']['attribute_' . $articleRelation['uid_foreign']] = array('vDEF' => $value);
			}
		}

		$xmlData = t3lib_div::array2xml($xmlData, '', 0, 'T3FlexForms');

		if ($articleUid) {
			$database->exec_UPDATEquery(
				'tx_commerce_articles',
				'uid=' . $articleUid . ' AND deleted=0',
				array('attributesedit' => $xmlData)
			);
		} elseif ($productUid) {
			$database->exec_UPDATEquery(
				'tx_commerce_articles',
				'uid_product=' . $productUid . ' AND deleted=0',
				array('attributesedit' => $xmlData)
			);
		}
	}

	/**
	 * Updates the XML of an FlexForm value field. This is almost the same as "updateArticleXML" but more general.
	 *
	 * @see $this->updateArticleXML
	 *
	 * @param string $xmlField: The fieldname where the FlexForm values are stored
	 * @param string $table: The table in which the FlexForm values are stored
	 * @param integer $uid: The UID of the entity inside the table
	 * @param string $type: The type of data we are handling (category, product)
	 * @param array $ctList: A list of correlationtype UID we should handle
	 * @param boolean $rebuild
	 * @return array array($xmlField => $xmlData)
	 */
	public function updateXML($xmlField, $table, $uid, $type, $ctList, $rebuild = FALSE) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$xmlDataResult = $database->exec_SELECTquery($xmlField, $table, 'uid=' . (int) $uid);
		$xmlData = $database->sql_fetch_assoc($xmlDataResult);
		$xmlData = t3lib_div::xml2array($xmlData[$xmlField]);
		if (!is_array($xmlData)) {
			$xmlData = array();
		}

		$relList = NULL;
		switch (strtolower($type)) {
			case 'category':
				$relList = $this->getAttributesForCategory($uid);
			break;
			case 'product':
				$relList = $this->getAttributesForProduct($uid);
			break;
		}

		$cTypes = array();

			// write the data
		if (is_array($ctList)) {
			foreach ($ctList as $ct) {
				$value = array();
				if (is_array($relList)) {
					foreach ($relList as $relation) {
						if ($relation['uid_correlationtype'] == (int) $ct['uid']) {
								// add ctype to checklist in case we need to rebuild
							if (!in_array($ct['uid'], $cTypes)) {
								$cTypes[] = (int) $ct['uid'];
							}

							$value[] = $relation['uid_foreign'];
						}
					}
				}

				if (count($value) > 0) {
					$xmlData['data']['sDEF']['lDEF']['ct_' . (string) $ct['uid']] = array('vDEF' => (string) implode(',', $value));
				}
			}
		}

			// rebuild
		if ($rebuild && 0 < count($cTypes) && is_array($ctList)) {
			foreach ($ctList as $ct) {
				if (!in_array($ct['uid'], $cTypes)) {
					$xmlData['data']['sDEF']['lDEF']['ct_' . (string) $ct['uid']] = array('vDEF' => '');
				}
			}
		}

			// build new XML
		if (is_array($xmlData)) {
				// Dump Quickfix
			$xmlData = t3lib_div::array2xml($xmlData, '', 0, 'T3FlexForms');
		} else {
			$xmlData = '';
		}

			// update database entry
		$database->exec_UPDATEquery($table, 'uid=' . $uid, array($xmlField => $xmlData));
		return array($xmlField => $xmlData);
	}

	/**
	 * Merges an attribute list from a flexform together into one array.
	 *
	 * @param array $ffData: The FlexForm data as array
	 * @param string $prefix: The prefix that is used for the correlationtypes
	 * @param array $ctList: The list of correlations that should be processed
	 * @param integer $uid_local: The "uid_local" in the relation table
	 * @param array $paList: The list of product attributes (PASSED BY REFERENCE)
	 * @return void
	 */
	public function mergeAttributeListFromFFData($ffData, $prefix, $ctList, $uid_local, &$paList) {
		if (is_array($ctList)) {
			foreach ($ctList as $ctUid) {
				$ffaList = explode(',', $ffData[$prefix . $ctUid['uid']]['vDEF']);
				if (count($ffaList) == 1 && $ffaList[0] == '') {
					continue;
				}

				foreach ($ffaList as $aUid) {
					if (!(
						$this->checkArray($uid_local, $paList, 'uid_local')
						&& $this->checkArray($aUid, $paList, 'uid_foreign')
						&& $this->checkArray($ctUid['uid'], $paList, 'uid_correlationtype')
					)) {
						if ($aUid != '') {
							$newRel = array(
								'uid_local' => $uid_local,
								'uid_foreign' => $aUid,
								'uid_correlationtype' => $ctUid['uid']
							);

							$paList[] = $newRel;
						}
					}
				}
			}
		}
	}

	/**
	 * Extracts a fieldvalue from an associative array.
	 *
	 * @param array $array: The data array
	 * @param string $field: The field that should be extracted
	 * @param boolean $makeArray: If true the result is returned as an array of arrays
	 * @param array $extraFields: Add some extra fields to the result
	 * @return array with fieldnames
	 */
	public static function extractFieldArray($array, $field, $makeArray = FALSE, $extraFields = array()) {
		$result = array();
		if (is_array($array)) {
			foreach ($array as $data) {
				if (!is_array($data) || (is_array($data) && !array_key_exists($field, $data))) {
					$item[$field] = $data;
				} else {
					$item = $data;
				}
				if ($makeArray) {
					$newItem = array($field => $item[$field]);
					if (count($extraFields)) {
						foreach ($extraFields as $extraFieldName) {
							$newItem[$extraFieldName] = $item[$extraFieldName];
						}
					}
				} else {
					$newItem = $item[$field];
				}
				if (!in_array($newItem, $result)) {
					$result[] = $newItem;
				}
			}
		}
		return $result;
	}

	/**
	 * Return all productes that are related to a category.
	 *
	 * @param integer $categoryUid: The UID of the category.
	 * @return array with the entities of the found products
	 */
	public function getProductsOfCategory($categoryUid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECTgetRows(
			'*',
			'tx_commerce_products_categories_mm',
			'uid_foreign=' . (int) $categoryUid
		);

		return $result;
	}

	/**
	 * Returns an array for the UPDATEquery method. It fills different arrays with an attribute value.
	 * This wraper is needed because the fields have different names in different tables. I know that's stupid
	 * but this is a fact... :-/
	 *
	 * @param array $attributeData: The data that should be stored
	 * @param string|integer $data: The value of the attribute
	 * @param integer $productUid: The UID of the produt
	 * @return array with two arrays as elements
	 */
	public function getUpdateData($attributeData, $data, $productUid = 0) {
		$updateArray = array();
		$updateArray['uid_valuelist'] = '';
		$updateArray['default_value'] = '';
		$updateArray2 = $updateArray;
		$updateArray2['value_char'] = '';
		$updateArray2['uid_product'] = $productUid;

		if ($attributeData['has_valuelist'] == 1) {
			$updateArray['uid_valuelist'] = $data;
			$updateArray2['uid_valuelist'] = $data;
		} else {
			if (!$this->isNumber($data)) {
				$updateArray['default_value'] = $data;
				$updateArray2['default_value'] = $data;
			} else {
				$updateArray['default_value'] = $data;
				$updateArray2['value_char'] = $data;
			}
		}

		return array($updateArray, $updateArray2);
	}

	/**
	 * Builds the lokalised Array for the attribute Felxform
	 *
	 * @param string $flexValue String XML-Flex-Form
	 * @param string $langIdent Language Ident
	 * @return string XML-Flex-Form
	 */
	public function buildLocalisedAttributeValues($flexValue, $langIdent) {
		$attrFFData = t3lib_div::xml2array($flexValue);

		$result = '';
		if (is_array($attrFFData)) {
				// change the language
			$attrFFData['data']['sDEF']['l' . $langIdent] = $attrFFData['data']['sDEF']['lDEF'];

			/**
			 * Decide on what to to on lokalisation, how to act
			 * @see ext_conf_template
			 * attributeLokalisationType[0|1|2]
			 * 0: set blank
			 * 1: Copy
			 * 2: prepend [Translate to .$langRec['title'].:]
			 */
			switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['attributeLokalisationType']) {
				case 0:
					unset($attrFFData['data']['sDEF']['lDEF']);
				break;

				case 1:
				break;

				case 2:
					/**
					 * Walk thru the array and prepend text
					 */
					$prepend = '[Translate to ' . $langIdent . ':] ';
					foreach ($attrFFData['data']['sDEF']['lDEF'] as  $attribKey => $attributes) {
						$attrFFData['data']['sDEF']['lDEF'][$attribKey]['vDEF'] = $prepend . $attrFFData['data']['sDEF']['lDEF'][$attribKey]['vDEF'];
					}
				break;
			}
			$result = t3lib_div::array2xml($attrFFData, '', 0, 'T3FlexForms');
		}
		return $result;
	}

	/**
	 * Removed with irre implementation. Stub left for api compatibility
	 * save Price-Flexform with given Article-UID
	 *
	 * @return boolean Status of method
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, this wont get replaced as it was empty before and will get removed from the api
	 */
	public function savePriceFlexformWithArticle() {
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * @param integer $pid
	 * @param integer $levels
	 * @param integer $aktLevel
	 * @return array|boolean
	 */
	public static function getOrderFolderSelector($pid, $levels, $aktLevel = 0) {
		$returnArray = array();
		/**
		 * Query the table to build dropdown list
		 */
		$prep = '';
		for ($i = 0; $i < $aktLevel; $i++) {
			$prep .= '- ';
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECTquery(
			'*',
			$GLOBALS['TCA']['tx_commerce_orders']['columns']['newpid']['config']['foreign_table'],
			'pid = ' . $pid . t3lib_BEfunc::deleteClause($GLOBALS['TCA']['tx_commerce_orders']['columns']['newpid']['config']['foreign_table']),
			'',
			'sorting'
		);
		if ($database->sql_num_rows($result) > 0) {
			while ($return_data = $database->sql_fetch_assoc($result)) {
				$return_data['title'] = $prep . $return_data['title'];

				$returnArray[] = array($return_data['title'], $return_data['uid']);
				$tmparray = self::getOrderFolderSelector($return_data['uid'], $levels - 1, $aktLevel + 1);
				if (is_array($tmparray)) {
					$returnArray = array_merge($returnArray, $tmparray);
				}
			}
			$database->sql_free_result($result);
		}
		if (count($returnArray) > 0) {
			return $returnArray;
		} else {
			return FALSE;
		}
	}

	/**
	 * update Flexform XML from Database
	 *
	 * @param integer $articleUid ID of article
	 * @return boolean Status of method
	 */
	public function updatePriceXMLFromDatabase( $articleUid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$res = $database->exec_SELECTquery(
			'*',
			'tx_commerce_article_prices',
			'deleted=0 AND uid_article=' . (int) $articleUid
		);

		$data = array('data' => array('sDEF' => array('lDEF')));
		while ($priceDataArray = $database->sql_fetch_assoc($res)) {
			$priceUid = $priceDataArray['uid'];

			$data['data']['sDEF']['lDEF']['price_net_' . $priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['price_net'] / 100)));
			$data['data']['sDEF']['lDEF']['price_gross_' . $priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['price_gross'] / 100)));
			$data['data']['sDEF']['lDEF']['purchase_price_' . $priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['purchase_price'] / 100)));
			$data['data']['sDEF']['lDEF']['hidden_' . $priceUid] = array('vDEF' => $priceDataArray['hidden']);
			$data['data']['sDEF']['lDEF']['starttime_' . $priceUid] = array('vDEF' => $priceDataArray['starttime']);
			$data['data']['sDEF']['lDEF']['endtime_' . $priceUid] = array('vDEF' => $priceDataArray['endtime']);
			$data['data']['sDEF']['lDEF']['fe_group_' . $priceUid] = array('vDEF' => $priceDataArray['fe_group']);
			$data['data']['sDEF']['lDEF']['price_scale_amount_start_' . $priceUid] = array('vDEF' => $priceDataArray['price_scale_amount_start']);
			$data['data']['sDEF']['lDEF']['price_scale_amount_end_' . $priceUid] = array('vDEF' => $priceDataArray['price_scale_amount_end']);
		}

		$xml = t3lib_div::array2xml($data, '', 0, 'T3FlexForms');

		$res = $database->exec_UPDATEquery(
			'tx_commerce_articles',
			'uid=' . $articleUid,
			array('prices' => $xml)
		);

		return (bool) $res;
	}

	/**
	 * This makes the Xml to draw the price form of an article
	 * Call this function if you have imported data to the database and havn't
	 * updated the Flexform
	 *
	 * @param integer $article_uid
	 * @return void
	 * @see updatePriceXMLFromDatabase
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use Tx_Commerce_Utility_BackendUtility::updateXML
	 */
	public function fix_articles_price($article_uid = 0) {
		t3lib_div::logDeprecatedFunction();
		if ($article_uid > 0) {
			self::updatePriceXMLFromDatabase($article_uid);
		}
	}

	/**
	 * This makes the Xml for the product Attributes
	 * Call thos function if you have imported data to the database and havn't
	 * updated the Flexform
	 *
	 * @param integer $product_uid
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use Tx_Commerce_Utility_BackendUtility::updateXML
	 */
	public function fix_product_atributte($product_uid = 0) {
		t3lib_div::logDeprecatedFunction();
		if ($product_uid > 0) {
			/** @var t3lib_db $database */
			$database = $GLOBALS['TYPO3_DB'];

			$mm_table = 'tx_commerce_products_attributes_mm';
			$where = 'uid_local= ' . (int) $product_uid;
			$rs = $database->exec_SELECTquery(
				'uid_correlationtype,uid_foreign',
				$mm_table,
				$where,
				$groupBy = '',
				$orderBy = '',
				$limit = ''
			);
			$xmlArray = array();
			while ($row = $database->sql_fetch_assoc($rs)) {
				if (empty($xmlArray['data']['sDEF']['lDEF']['ct_' . $row['uid_correlationtype']]['vDEF'])) {
					$xmlArray['data']['sDEF']['lDEF']['ct_' . $row['uid_correlationtype']]['vDEF'] = $row['uid_foreign'];
				} else {
					$xmlArray['data']['sDEF']['lDEF']['ct_' . $row['uid_correlationtype']]['vDEF'] .= ',' . $row['uid_foreign'];
				}
			}
			$arrayToSave = array();
			$xmlText = t3lib_div::array2xml_cs($xmlArray, 'T3FlexForms', $options = array(), $charset = '');
			$arrayToSave['attributes'] = $xmlText;

			$database->exec_UPDATEquery(
				'tx_commerce_products',
				'uid=' . $product_uid,
				$arrayToSave,
				$no_quote_fields = FALSE
			);
		}
	}

	/**
	 * This function gives all attributes of one product to the other (only mm attributes, flexforms need to be handled separately [@see fix_product_atributte()])
	 *
	 * @param integer $pUidFrom Product UID from which to take the Attributes
	 * @param integer $pUidTo Product UID to which we give the Attributes
	 * @param boolean $copy If set, the Attributes will only be copied - else cut (aka "swapped" in its true from)
	 * @return boolean Success
	 */
	public static function swapProductAttributes($pUidFrom, $pUidTo, $copy = FALSE) {
			// verify params
		if (!is_numeric($pUidFrom) || !is_numeric($pUidTo)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('swapProductAttributes (Tx_Commerce_Utility_BackendUtility) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check perms
		if (!self::checkProductPerms($pUidFrom, ($copy) ? 'show' : 'editcontent')) {
			return FALSE;
		}
		if (!self::checkProductPerms($pUidTo, 'editcontent')) {
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (!$copy) {
				// cut the attributes - or, update the mm table with the new uids of the product
			$database->exec_UPDATEquery('tx_commerce_products_attributes_mm', 'uid_local = ' . (int) $pUidFrom, array('uid_local' => (int) $pUidTo));

			$success = $database->sql_error() == '';
		} else {
				// copy the attributes - or, get all values from the original product relation and insert them with the new uid_local
			$res = $database->exec_SELECTquery(
				'uid_foreign, tablenames, sorting, uid_correlationtype, uid_valuelist, default_value',
				'tx_commerce_products_attributes_mm',
				'uid_local = ' . $pUidFrom
			);

			while ($row = $database->sql_num_rows($res)) {
				$row['uid_local'] = $pUidTo;

					// insert
				$database->exec_INSERTquery('tx_commerce_products_attributes_mm', $row);
			}

			$success = $database->sql_error() == '';
		}

		return $success;
	}

	/**
	 * This function gives all articles of one product to another
	 *
	 * @param integer $pUidFrom Product UID from which to take the Articles
	 * @param integer $pUidTo Product UID to which we give the Articles
	 * @param boolean $copy If set, the Articles will only be copied - else cut (aka "swapped" in its true from)
	 * @return boolean Success
	 */
	public static function swapProductArticles($pUidFrom, $pUidTo, $copy = FALSE) {
			// check params
		if (!is_numeric($pUidFrom) || !is_numeric($pUidTo)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('swapProductArticles (Tx_Commerce_Utility_BackendUtility) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check perms
		if (!self::checkProductPerms($pUidFrom, ($copy) ? 'show' : 'editcontent')) {
			return FALSE;
		}
		if (!self::checkProductPerms($pUidTo, 'editcontent')) {
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (!$copy) {
				// cut the articles - or, give all articles of the old product the product_uid of the new product
			$database->exec_UPDATEquery('tx_commerce_articles', 'uid_product = ' . $pUidFrom, array('uid_product' => $pUidTo));

			$success = $database->sql_error() == '';
		} else {
				// copy the articles - or, read all article uids of the old product and invoke the copy command
			$res = $database->exec_SELECTquery('uid', 'tx_commerce_articles', 'uid_product = ' . $pUidFrom);

			$success = TRUE;

			while ($row = $database->sql_fetch_assoc($res)) {
					// copy
				$success = self::copyArticle($row['uid'], $pUidTo);
				if (!$success) {
					break;
				}
			}
		}
		return $success;
	}

	/**
	 * Copies the specified Article to the new product
	 *
	 * @param integer $uid uid of existing article which is to be copied
	 * @param integer $uid_product uid of product that is new parent
	 * @param array $locale array with sys_langauges to copy along, none if null
	 * @return integer UID of the new article or false on error
	 */
	public function copyArticle($uid, $uid_product, $locale = array()) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// check params
		if (!is_numeric($uid) || !is_numeric($uid_product)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyArticle (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check show right for this article under all categories of the current parent product
		$prod = self::getProductOfArticle($uid);

		if (!self::checkProductPerms($prod['uid'], 'show')) {
			return FALSE;
		}

			// check editcontent right for this article under all categories of the new parent product
		if (!self::checkProductPerms($uid_product, 'editcontent')) {
			return FALSE;
		}

			// get uid of the last article in the articles table
		$res = $database->exec_SELECTquery('uid', 'tx_commerce_articles', 'deleted = 0', '', 'uid DESC', '0,1');

			// if there are no articles at all, abort.
		if (0 >= $database->sql_num_rows($res)) {
			return FALSE;
		}

		$row = $database->sql_fetch_assoc($res);
			// uid of the last article (after this article we will copy the new article
		$uidLast = $row['uid'];

			// init tce
		/** @var t3lib_TCEmain $tce */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;

		$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

			// start
		$tce->start(array(), array());

			// invoke the copy manually so we can actually override the uid_product field
		$overrideArray = array('uid_product' => $uid_product);

			// Write to session that we copy
			// this is used by the hook to the datamap class to figure out if it should call the dynaflex
			// so far this is the best (though not very clean) way to solve the issue we get when saving an article
		$backendUser->uc['txcommerce_copyProcess'] = 1;
		$backendUser->writeUC();

		$newUid = $tce->copyRecord('tx_commerce_articles', $uid, - $uidLast, 1, $overrideArray);

			// We also overwrite because Attributes and Prices will not be saved when we copy
			// this is because commerce hooks into _preProcessFieldArray when it wants to make the prices etc.
			// which is too early when we copy because at this point the uid does not exist
			// self::overwriteArticle($uid, $newUid, $locale); <-- REPLACED WITH OVERWRITE OF WHOLE PRODUCT BECAUSE THAT ACTUALLY WORKS

			// copying done, clear session
		$backendUser->uc['txcommerce_copyProcess'] = 0;
		$backendUser->writeUC();

		if (!is_numeric($newUid)) {
			return FALSE;
		}

			// copy prices - not possible through datamap so we do it here
		self::copyPrices($uid, $newUid);

			// copy attributes - creating attributes doesn't work with normal copy because only
			// when a product is created in datamap, it creates the attributes for articles with hook
			// But by the time we copy articles, product is already created and we have to copy the attributes manually
		self::overwriteArticleAttributes($uid, $newUid);

			// copy locales
		if (count($locale)) {
			foreach ($locale as $loc) {
				self::copyLocale('tx_commerce_articles', $uid, $newUid, $loc);
			}
		}

		return $newUid;
	}

	/**
	 * Copies the Prices of a Article
	 *
	 * @param integer $uidFrom
	 * @param integer $uidTo
	 * @return boolean
	 */
	public function copyPrices($uidFrom, $uidTo) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// select all existing prices of the article
		$res = $database->exec_SELECTquery('uid', 'tx_commerce_article_prices', 'deleted = 0 AND uid_article = ' . $uidFrom, '');

		$newUid = 0;
		while ($row = $database->sql_fetch_assoc($res)) {
				// copy them to the new article
			/** @var t3lib_TCEmain $tce */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;

			$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride)) {
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}

				// start
			$tce->start(array(), array());

				// invoke the copy manually so we can actually override the uid_product field
			$overrideArray = array('uid_article' => $uidTo);

			$backendUser->uc['txcommerce_copyProcess'] = 1;
			$backendUser->writeUC();

			$newUid = $tce->copyRecord('tx_commerce_article_prices', $row['uid'], - $row['uid'], 1, $overrideArray);

				// copying done, clear session
			$backendUser->uc['txcommerce_copyProcess'] = 0;
			$backendUser->writeUC();
		}

		return !is_numeric($newUid);
	}

	/**
	 * Copies the article attributes from one article to the next
	 * Used when we copy an article
	 * To copy from locale to locale, just insert the uids of the localized
	 * records; note that this function deletes the existing attributes
	 *
	 * @param integer $uidFrom uid of the article we get the attributes from
	 * @param integer $uidTo uid of the article we want to copy the attributes to
	 * @param integer $loc
	 * @return boolean	Success
	 */
	public function overwriteArticleAttributes($uidFrom, $uidTo, $loc = 0) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
			// delete existing attributes
		$table = 'tx_commerce_articles_article_attributes_mm';

		if ($loc != 0) {
				// we want to overwrite the attributes of the locale
				// replace $uidFrom and $uidTo with their localized versions
			$uids = $uidFrom . ',' . $uidTo;
			$res = $database->exec_SELECTquery(
				'uid, l18n_parent',
				'tx_commerce_articles',
				'sys_language_uid = ' . $loc . ' AND l18n_parent IN (' . $uids . ')'
			);
			$newFrom = $uidFrom;
			$newTo = $uidTo;

				// get uids
			while ($row = $database->sql_fetch_assoc($res)) {
				if ($row['l18n_parent'] == $uidFrom) {
					$newFrom = $row['uid'];
				} elseif ($row['l18n_parent'] == $uidTo) {
					$newTo = $row['uid'];
				}
			}

				// abort if we didn't find the locale for any of the articles
			if ($newFrom == $uidFrom || $newTo == $uidTo) {
				return FALSE;
			}

				// replace uids
			$uidFrom = $newFrom;
			$uidTo = $newTo;
		}

		$database->exec_DELETEquery(
			$table,
			'uid_local=' . $uidTo
		);

			// copy the attributes
		$res = $database->exec_SELECTquery('*', $table, 'uid_local=' . $uidFrom . ' AND uid_valuelist = 0');

		while ($origRelation = $database->sql_fetch_assoc($res)) {
			$origRelation['uid_local'] = $uidTo;
			$database->exec_INSERTquery($table, $origRelation);
		}

		return TRUE;
	}

	/**
	 * Copies the specified Product to the new category
	 *
	 * @param integer $uid uid of existing product which is to be copied
	 * @param integer $uid_category
	 * @param boolean $ignoreWS true if versioning should be disabled (be warned: use this only if you are 100% sure you know what you are doing)
	 * @param array $locale array of languages that should be copied, or null if none are specified
	 * @param integer $sorting uid of the record behind which we insert this product, or 0 to just append
	 * @return integer UID of the new product or false on error
	 */
	public static function copyProduct($uid, $uid_category, $ignoreWS = FALSE, $locale = NULL, $sorting = 0) {
			// check params
		if (!is_numeric($uid) || !is_numeric($uid_category) || !is_numeric($sorting)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyProduct (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check if we may actually copy the product (no permission check, only check if we are not accidentally copying a placeholder or deleted product)
			// also hidden products are not allowed to be copied
		$record = t3lib_BEfunc::getRecordWSOL('tx_commerce_products', $uid, '*', ' AND hidden = 0 AND t3ver_state = 0');

		if (!$record) {
			return FALSE;
		}

			// check if we have the permissions to copy (check category rights) - skip if we are copying locales
		if (!self::checkProductPerms($uid, 'copy')) {
			return FALSE;
		}

			// check editcontent right for uid_category
		if (!self::readCategoryAccess($uid_category, self::getCategoryPermsClause(self::getPermMask('editcontent')))) {
			return FALSE;
		}

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyProductClass'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_belib.php\'][\'copyProductClass\']
				is deprecated since commerce 0.14.0, it will be removed in commerce 0.16.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Utility/BackendUtility.php\'][\'copyProduct\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyProductClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/BackendUtility.php']['copyProduct'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/BackendUtility.php']['copyProduct'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (0 == $sorting) {
				// get uid of the last product in the products table
			$res = $database->exec_SELECTquery('uid', 'tx_commerce_products', 'deleted = 0 AND pid != -1', '', 'uid DESC', '0,1');

				// if there are no products at all, abort.
			if (0 >= $database->sql_num_rows($res)) {
				return FALSE;
			}

			$row = $database->sql_fetch_assoc($res);
				// uid of the last product (after this product we will copy the new product)
			$uidLast = - $row['uid'];
		} else {
				// sorting position is specified
			$uidLast = (0 > $sorting) ? $sorting : self::getCopyPid('tx_commerce_products', $sorting);
		}

			// init tce
		/** @var t3lib_TCEmain $tce */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
			// set workspace bypass if requested
		$tce->bypassWorkspaceRestrictions = $ignoreWS;

		$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

			// start
		$tce->start(array(), array());

			// invoke the copy manually so we can actually override the categories field
		$overrideArray = array('categories' => $uid_category);

			// Hook: beforeCopy
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'beforeCopy')) {
				$hookObj->beforeCopy($uid, $uidLast, $overrideArray);
			}
		}

		$newUid = $tce->copyRecord('tx_commerce_products', $uid, $uidLast, 1, $overrideArray);

			// Hook: afterCopy
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'afterCopy')) {
				$hookObj->afterCopy($newUid, $uid, $$overrideArray);
			}
		}

		if (!is_numeric($newUid)) {
			return FALSE;
		}

			// copy locales
		if (is_array($locale) && 0 != count($locale)) {
			foreach ($locale as $loc) {
				self::copyLocale('tx_commerce_products', $uid, $newUid, $loc, $ignoreWS);
			}
		}

			// copy articles
		$success = self::copyArticlesByProduct($newUid, $uid, $locale);

			// Overwrite the Product we just created again - to fix that Attributes and Prices are not copied for Articles when they are only copied
			// This should be TEMPORARY - find a clean way to fix that problem
			// self::overwriteProduct($uid, $newUid, $locale); ###fixed###
		return !$success ? $success : $newUid;

	}

	/**
	 * Copies any locale of a commerce items
	 *
	 * @param string $table Name of the table in which the locale is
	 * @param integer $uidCopied uid of the record that is localized
	 * @param integer $uidNew uid of the record that was copied and now needs a locale
	 * @param integer $loc id of the sys_language
	 * @param boolean $ignoreWS
	 * @return boolean Success
	 */
	public function copyLocale($table, $uidCopied, $uidNew, $loc, $ignoreWS = FALSE) {
			// check params
		if (!is_string($table) || !is_numeric($uidCopied) || !is_numeric($uidNew) || !is_numeric($loc)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyLocale (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if ($GLOBALS['TCA'][$table] && $uidCopied) {
			t3lib_div::loadTCA($table);

				// make data
			$rec = t3lib_BEfunc::getRecordLocalization($table, $uidCopied, $loc);

				// if the item is not localized, return
			if (FALSE == $rec) {
				return TRUE;
			}

				// overwrite l18n parent
			$rec[0]['l18n_parent'] = $uidNew;
				// unset uid for cleanliness
			unset($rec[0]['uid']);

				// unset all fields that are not supposed to be copied on localized versions
			foreach ($GLOBALS['TCA'][$table]['columns'] as $fN => $fCfg) {
					// Otherwise, do not copy field (unless it is the language field or pointer to the original language)
				if (
					t3lib_div::inList('exclude,noCopy,mergeIfNotBlank', $fCfg['l10n_mode'])
					&& $fN != $GLOBALS['TCA'][$table]['ctrl']['languageField']
					&& $fN != $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
				) {
					unset($rec[0][$fN]);
				}
			}

				// if we localize an article, add the product uid of the $uidNew localized product
			if ('tx_commerce_articles' == $table) {
				/** @var Tx_Commerce_Domain_Model_Article $article */
				$article = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Article');
				$article->init($uidNew);
				$productUid = $article->getParentProductUid();

					// load uid of the localized product
				$res = $database->exec_SELECTquery(
					'uid',
					'tx_commerce_products',
					'l18n_parent = ' . $productUid . ' AND sys_language_uid = ' . $loc
				);
				$row = $database->sql_fetch_assoc($res);

				$rec[0]['uid_product'] = $row['uid'];
			}

			$data = array();

			$newUid = uniqid('NEW');
			$data[$table][$newUid] = $rec[0];

				// init tce
			/** @var t3lib_TCEmain $tce */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
				// set workspace bypass if requested
			$tce->bypassWorkspaceRestrictions = $ignoreWS;

			$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride)) {
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}

				// start
			$tce->start($data, array());

				// Write to session that we copy
				// this is used by the hook to the datamap class to figure out if it should call the dynaflex
				// so far this is the best (though not very clean) way to solve the issue we get when saving an article
			$backendUser->uc['txcommerce_copyProcess'] = 1;
			$backendUser->writeUC();

			$tce->process_datamap();

				// copying done, clear session
			$backendUser->uc['txcommerce_copyProcess'] = 0;
			$backendUser->writeUC();

				// get real uid
			$newUid = $tce->substNEWwithIDs[$newUid];

				// for articles we have to overwrite the attributes
			if ('tx_commerce_articles' == $table) {
				self::overwriteArticleAttributes($uidCopied, $newUid, $loc);
			}
		}
		return TRUE;
	}

	/**
	 * Overwrites the localization of a record
	 * if the record does not have the localization, it is copied to the record
	 *
	 * @param string $table Name of the table in which we overwrite records
	 * @param integer $uidCopied uid of the record that is the overwriter
	 * @param integer $uidOverwrite uid of the record that is to be overwritten
	 * @param integer $loc uid of the syslang that is overwritten
	 * @return boolean Success
	 */
	public function overwriteLocale($table, $uidCopied, $uidOverwrite, $loc) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// check params
		if (!is_string($table) || !is_numeric($uidCopied) || !is_numeric($uidOverwrite) || !is_numeric($loc)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyLocale (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check if table is defined in the TCA
		if ($GLOBALS['TCA'][$table] && $uidCopied) {
			t3lib_div::loadTCA($table);

				// make data
			$recFrom = t3lib_BEfunc::getRecordLocalization($table, $uidCopied, $loc);
			$recTo = t3lib_BEfunc::getRecordLocalization($table, $uidOverwrite, $loc);

				// if the item is not localized, return
			if (FALSE == $recFrom) {
				return TRUE;
			}

				// if the overwritten record does not have the corresponding localization, just copy it
			if (FALSE == $recTo) {
				return self::copyLocale($table, $uidCopied, $uidOverwrite, $loc);
			}

				// overwrite l18n parent
			$recFrom[0]['l18n_parent'] = $uidOverwrite;
				// unset uid for cleanliness
			unset($recFrom[0]['uid']);

				// unset all fields that are not supposed to be copied on localized versions
			foreach ($GLOBALS['TCA'][$table]['columns'] as $fN => $fCfg) {
					// Otherwise, do not copy field (unless it is the language field or pointer to the original language)
				if (
					t3lib_div::inList('exclude,noCopy,mergeIfNotBlank', $fCfg['l10n_mode'])
					&& $fN != $GLOBALS['TCA'][$table]['ctrl']['languageField']
					&& $fN != $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
				) {
					unset($recFrom[0][$fN]);
				} elseif (isset($fCfg['config']['type']) && 'flex' == $fCfg['config']['type'] && isset($recFrom[0][$fN])) {
					if ('' != $recFrom[0][$fN]) {
						$recFrom[0][$fN] = t3lib_div::xml2array($recFrom[0][$fN]);

						if ('' == trim($recFrom[0][$fN])) {
							unset($recFrom[0][$fN]);
						}
					} else {
						unset($recFrom[0][$fN]);
					}
				}
			}

			$data = array();
			$data[$table][$recTo[0]['uid']] = $recFrom[0];

				// init tce
			/** @var t3lib_TCEmain $tce */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;

			$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride)) {
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}

				// start
			$tce->start($data, array());

				// Write to session that we copy
				// this is used by the hook to the datamap class to figure out if it should call the dynaflex
				// so far this is the best (though not very clean) way to solve the issue we get when saving an article
			$backendUser->uc['txcommerce_copyProcess'] = 1;
			$backendUser->writeUC();

			$tce->process_datamap();

				// copying done, clear session
			$backendUser->uc['txcommerce_copyProcess'] = 0;
			$backendUser->writeUC();

				// for articles we have to overwrite the attributes
			if ('tx_commerce_articles' == $table) {
				self::overwriteArticleAttributes($uidCopied, $uidOverwrite, $loc);
			}
		}
		return TRUE;
	}

	/**
	 * Deletes all localizations of a record
	 * Note that no permission check is made whatsoever! Check perms if you implement this beforehand
	 *
	 * @param string $table Table name
	 * @param integer $uid uid of the record
	 * @return boolean Success
	 */
	public function deleteL18n($table, $uid) {
			// check params
		if (!is_string($table) || !is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('deleteL18n (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// get all locales
		$res = $database->exec_SELECTquery('uid', $table, 'l18n_parent = ' . (int) $uid);

			// delete them
		while ($row = $database->sql_fetch_assoc($res)) {
			$database->exec_UPDATEquery($table, 'uid = ' . $row['uid'], array('deleted' => 1));
		}

		return TRUE;
	}

	/**
	 * Copies the specified category into the new category
	 * note: you may NOT copy the same category into itself
	 *
	 * @param integer $uid uid of existing category which is to be copied
	 * @param integer $parent_uid uid of category that is new parent
	 * @param array $locale Array with all uids of languages that should as well be copied - if null, no languages shall be copied
	 * @param integer $sorting uid of the record behind which we copy (like - 23), or 0 if none is given at it should just be appended
	 * @return integer UID of the new category or false on error
	 */
	public static function copyCategory($uid, $parent_uid, $locale = NULL, $sorting = 0) {
			// check params
		if (!is_numeric($uid) || !is_numeric($parent_uid) || $uid == $parent_uid) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyCategory (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check if we have the right to copy this category
			// show right
		if (!self::readCategoryAccess($uid, self::getCategoryPermsClause(self::getPermMask('copy')))) {
			return FALSE;
		}

			// check if we have the right to insert into the parent Category
			// new right
		if (!self::readCategoryAccess($parent_uid, self::getCategoryPermsClause(self::getPermMask('new')))) {
			return FALSE;
		}

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyCategoryClass'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_belib.php\'][\'copyCategoryClass\']
				is deprecated since commerce 0.14.0, it will be removed in commerce 0.16.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Utility/BackendUtility.php\'][\'copyCategory\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyCategoryClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/BackendUtility.php']['copyCategory'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/BackendUtility.php']['copyCategory'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if (0 == $sorting) {
				// get uid of the last category in the category table
			$res = $database->exec_SELECTquery('uid', 'tx_commerce_categories', 'deleted = 0', '', 'uid DESC', '0,1');

				// if there are no categories at all, abort.
			if (0 >= $database->sql_num_rows($res)) {
				return FALSE;
			}

			$row = $database->sql_fetch_assoc($res);

				// uid of the last category (after this product we will copy the new category)
			$uidLast = - $row['uid'];
		} else {
				// copy after the given sorting point
			$uidLast = (0 > $sorting) ? $sorting : self::getCopyPid('tx_commerce_categories', $sorting);
		}

			// init tce
		/** @var t3lib_TCEmain $tce */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

			// start
		$tce->start(array(), array());

			// invoke the copy manually so we can actually override the categories field
		$overrideArray = array(
			'parent_category' => $parent_uid
		);

			// Hook: beforeCopy
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'beforeCopy')) {
				$hookObj->beforeCopy($uid, $uidLast, $overrideArray);
			}
		}

		$newUid = $tce->copyRecord('tx_commerce_categories', $uid, $uidLast, 1, $overrideArray);

			// Hook: afterCopy
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'afterCopy')) {
				$hookObj->afterCopy($newUid, $uid, $overrideArray);
			}
		}

		if (!is_numeric($newUid)) {
			return FALSE;
		}

			// chmod the new category since perms are not copied
		self::chmodCategoryByCategory($newUid, $uid);

			// copy locale
		if (is_array($locale) && 0 != count($locale)) {
			foreach ($locale as $loc) {
				self::copyLocale('tx_commerce_categories', $uid, $newUid, $loc);
			}
		}

			// copy all child products
		self::copyProductsByCategory($newUid, $uid, $locale);

			// copy all child categories
		self::copyCategoriesByCategory($newUid, $uid, $locale);

		return $newUid;
	}

	/**
	 * Changes the permissions of a category and applies the permissions of another category
	 * Note that this does ALSO change owner or group
	 *
	 * @param integer $uidToChmod uid of the category to chmod
	 * @param integer $uidFrom uid of the category from which we take the perms
	 * @return boolean Success
	 */
	public function chmodCategoryByCategory($uidToChmod, $uidFrom) {
			// check params
		if (!is_numeric($uidToChmod) || !is_numeric($uidFrom)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('chmodCategoryByCategory (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// select current perms
		$res  = $database->exec_SELECTquery(
			'perms_everybody, perms_group, perms_user, perms_groupid, perms_userid',
			'tx_commerce_categories',
			'uid = ' . $uidFrom . ' AND deleted = 0 AND ' . self::getCategoryPermsClause(self::getPermMask('show'))
		);
		$res2 = FALSE;

		while ($row = $database->sql_fetch_assoc($res)) {
				// apply the permissions
			$updateFields = array(
				'perms_everybody' => $row['perms_everybody'],
				'perms_group' => $row['perms_group'],
				'perms_user' => $row['perms_user'],
				'perms_userid' => $row['perms_userid'],
				'perms_groupid' => $row['perms_groupid'],
			);

			$res2 = $database->exec_UPDATEquery('tx_commerce_categories', 'uid = ' . $uidToChmod, $updateFields);
		}

		return ($res2 !== FALSE && $database->sql_error() == '');
	}

	/**
	 * Returns the pid new pid for the copied item - this is only used when inserting a record on front of another
	 *
	 * @param string $table Table from which we want to read
	 * @param integer $uid uid of the record that we want to move our element to - in front of it
	 * @return integer
	 */
	public function getCopyPid($table, $uid) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$res  = $database->exec_SELECTquery(
			'uid',
			$table,
			'sorting < (SELECT sorting FROM ' . $table . ' WHERE uid = ' . $uid . ') ORDER BY sorting DESC LIMIT 0,1'
		);

		$row = $database->sql_fetch_assoc($res);

		if ($row == NULL) {
				// the item we want to skip is the item with the lowest sorting - use pid of the 'Product' Folder
			$pid = self::getProductFolderUid();
		} else {
			$pid = - $row['uid'];
		}

		return $pid;
	}

	/**
	 * Copies all Products under a category to a new category
	 * Note that Products that are copied in this indirect way are not versioned
	 *
	 * @param integer $catUidTo uid of the category to which the products should be copied
	 * @param integer $catUidFrom uid of the category from which the products should come
	 * @param array $locale sys_langauges which are to be copied as well, null if none
	 * @return boolean Success
	 */
	public function copyProductsByCategory($catUidTo, $catUidFrom, $locale = NULL) {
			// check params
		if (!is_numeric($catUidTo) || !is_numeric($catUidFrom)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyProductsByCategory (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$res = $database->exec_SELECT_mm_query(
			'uid_local',
			'tx_commerce_products',
			'tx_commerce_products_categories_mm',
			'',
			' AND deleted = 0 AND uid_foreign = ' . (int) $catUidFrom,
			'tx_commerce_products.sorting ASC',
			'',
			''
		);

		$success = TRUE;

		while ($row = $database->sql_fetch_assoc($res)) {
			$productCopied = self::copyProduct($row['uid_local'], $catUidTo, TRUE, $locale);
				// keep false if one action was false
			$success = ($success) ? $productCopied : $success;
		}

		return $success;
	}

	/**
	 * Copies all Categories under a category to a new category
	 *
	 * @param integer $catUidTo uid of the category to which the categories should be copied
	 * @param integer $catUidFrom uid of the category from which the categories should come
	 * @param array $locale sys_langauges that should be copied as well
	 * @return boolean Success
	 */
	public function copyCategoriesByCategory($catUidTo, $catUidFrom, $locale = NULL) {
			// check params
		if (!is_numeric($catUidTo) || !is_numeric($catUidFrom) || $catUidTo == $catUidFrom) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyCategoriesByCategory (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$res = $database->exec_SELECT_mm_query(
			'uid_local',
			'tx_commerce_categories',
			'tx_commerce_categories_parent_category_mm',
			'',
			' AND deleted = 0 AND uid_foreign = ' . $catUidFrom . ' AND ' . self::getCategoryPermsClause(1),
			'tx_commerce_categories.sorting ASC',
			'',
			''
		);
		$success = TRUE;

		while ($row = $database->sql_fetch_assoc($res)) {
			$categoryCopied = self::copyCategory($row['uid_local'], $catUidTo, $locale);
			$success = ($success) ? $categoryCopied : $success;
		}

		return $success;
	}

	/**
	 * Copies all Articles from one Product to another
	 *
	 * @param integer $prodUidTo uid of product from which we copy the articles
	 * @param integer $prodUidFrom uid of product to which we copy the articles
	 * @param array $locale array with sys_languages to copy along, null if none
	 * @return boolean Success
	 */
	public function copyArticlesByProduct($prodUidTo, $prodUidFrom, $locale = NULL) {
			// check params
		if (!is_numeric($prodUidTo) || !is_numeric($prodUidFrom)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyArticlesByProduct (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$res = $database->exec_SELECTquery('uid', 'tx_commerce_articles', 'deleted = 0 AND uid_product = ' . $prodUidFrom);
		$success = TRUE;

		while ($row = $database->sql_fetch_assoc($res)) {
			$succ = self::copyArticle($row['uid'], $prodUidTo, $locale);
			$success = ($success) ? $succ : $success;
		}

		return $success;
	}

	/**
	 * This makes the Xml for the category Attributes
	 * Call this function if you have imported data to the database and haven't
	 * updated the Flexform
	 *
	 * @param integer $category_uid
	 * @return void
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use updateXML instead
	 */
	public function fix_category_atributte($category_uid = 0) {
		t3lib_div::logDeprecatedFunction();

		if ($category_uid == 0) {
			return;
		}

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$mm_table = 'tx_commerce_categories_attributes_mm';
		$where = 'uid_local= ' . (int) $category_uid;
		$rs = $database->exec_SELECTquery('uid_correlationtype,uid_foreign', $mm_table, $where, $groupBy = '', $orderBy = '', $limit = '');

		$xmlArray = array();
		while ($row = $database->sql_fetch_assoc($rs)) {
			if (empty($xmlArray['data']['sDEF']['lDEF']['ct_' . $row['uid_correlationtype']]['vDEF'])) {
				$xmlArray['data']['sDEF']['lDEF']['ct_' . $row['uid_correlationtype']]['vDEF'] = $row['uid_foreign'];
			} else {
				$xmlArray['data']['sDEF']['lDEF']['ct_' . $row['uid_correlationtype']]['vDEF'] .= ',' . $row['uid_foreign'];
			}
		}
		$arrayToSave = array();
		$xmlText = t3lib_div::array2xml_cs($xmlArray, 'T3FlexForms', $options = array(), $charset = '');
		$arrayToSave['attributes'] = $xmlText;

		$database->exec_UPDATEquery('tx_commerce_categories', 'uid=' . $category_uid, $arrayToSave);
	}

	/**
	 * Returns a WHERE-clause for the tx_commerce_categories-table where user permissions according to input argument,
	 * $perms, is validated. $perms is the "mask" used to select. Fx. if $perms is 1 then you'll get all categories that a user
	 * can actually see!
	 * 	 	2^0 = show (1)
	 * 		2^1 = edit (2)
	 * 		2^2 = delete (4)
	 * 		2^3 = new (8)
	 * If the user is 'admin' " 1=1" is returned (no effect)
	 * If the user is not set at all (->user is not an array), then " 1=0" is returned (will cause no selection results at all)
	 * The 95% use of this function is "->getCategoryPermsClause(1)" which will return WHERE clauses for *selecting*
	 * categories in backend listings - in other words this will check read permissions.
	 *
	 * @param integer $perms Permission mask to use, see function description
	 * @return string Part of where clause. Prefix " AND " to this.
	 */
	public static function getCategoryPermsClause($perms) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		if (is_array($backendUser->user)) {
			if ($backendUser->isAdmin()) {
				return ' 1=1';
			}

				// Make sure it's integer.
			$perms = (int) $perms;
			$str = ' (' .
					// Everybody
				'(perms_everybody & ' . $perms . ' = ' . $perms . ')' .
					// User
				'OR(perms_userid = ' . $backendUser->user['uid'] . ' AND perms_user & ' . $perms . ' = ' . $perms . ')';
			if ($backendUser->groupList) {
					// Group (if any is set)
				$str .= 'OR(perms_groupid in (' . $backendUser->groupList . ') AND perms_group & ' . $perms . ' = ' . $perms . ')';
			}
			$str .= ')';

			return $str;
		} else {
			return ' 1=0';
		}
	}

	/**
	 * Returns whether the Permission is set and allowed for the corresponding user
	 *
	 * @param string $perm Word rep. for the wanted right ('show', 'edit', 'editcontent', 'delete', 'new')
	 * @param array $record
	 * @return boolean $perm User allowed this action or not for the current category
	 */
	public static function isPSet($perm, &$record) {
		if (!is_string($perm) || is_null($record)) {
			return FALSE;
		}

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// If User is admin, he may do anything
		if ($backendUser->isAdmin()) {
			return TRUE;
		}

		$mask = self::getPermMask($perm);
			// if no mask is found, cancel.
		if ($mask == 0) {
			return FALSE;
		}

			// if editlock is enabled and we edit, cancel edit.
		if ($mask == 2 && $record['editlock']) {
			return FALSE;
		}

			// Check the rights of the current record
			// Check if anybody has the right to do the current operation
		if (isset($record['perms_everybody']) && (($record['perms_everybody'] & $mask) == $mask)) {
			return TRUE;
		}

			// Check if user is owner of category and the owner may do the current operation
		if (
			isset($record['perms_userid']) && isset($record['perms_user'])
			&& ($record['perms_userid'] == $backendUser->user['uid'])
			&& (($record['perms_user'] & $mask) == $mask)
		) {
			return TRUE;
		}

			// Check if the Group has the right to do the current operation
		if (isset($record['perms_groupid']) && isset($record['perms_group'])) {
			$usergroups = explode(',', $backendUser->groupList);

			for ($i = 0, $l = count($usergroups); $i < $l; $i ++) {
					// User is member of the Group of the category - check the rights
				if ($record['perms_groupid'] == $usergroups[$i]) {
					if (($record['perms_group'] & $mask) == $mask) {
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Returns the int Permission Mask for the String-Representation of the Permission. Returns 0 if not found.
	 *
	 * @param string $perm String-Representation of the Permission
	 * @return integer
	 */
	public static function getPermMask($perm) {
		if (!is_string($perm)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getPermMask (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return 0;
		}

		$mask = 0;

		switch ($perm) {
			case 'read':
			case 'show':
			case 'copy':
				$mask = 1;
			break;

			case 'edit':
			case 'move':
				$mask = 2;
			break;

			case 'delete':
				$mask = 4;
			break;

			case 'new':
				$mask = 8;
			break;

			case 'editcontent':
				$mask = 16;
			break;
		}

		return $mask;
	}

	/**
	 * Checks the permissions for a product (by checking for the permission in all parent categories)
	 *
	 * @param integer $uid Product UId
	 * @param string $perm String-Rep of the Permission
	 * @return boolean Right exists or not
	 */
	public function checkProductPerms($uid, $perm) {
			// check params
		if (!is_numeric($uid) || !is_string($perm)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('checkProductPerms (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// get mask
		$mask = self::getPermMask($perm);

		if (0 == $mask) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('checkProductPerms (belib) gets passed an invalid permission to check for.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// get parent categories
		$parents = self::getProductParentCategories($uid);

			// check the permissions
		if (0 < count($parents)) {
			$l = count($parents);

			for ($i = 0; $i < $l; $i ++) {
				if (!self::readCategoryAccess($parents[$i], self::getCategoryPermsClause($mask))) {
					return FALSE;
				}
			}
		} else {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Returns a category record (of category with $id) with an extra field "_thePath" set to the record path IF the WHERE clause,
	 * $perms_clause, selects the record. Thus is works as an access check that returns a category record if access was granted, otherwise not.
	 * If $id is zero a pseudo root-page with "_thePath" set is returned IF the current BE_USER is admin.
	 * In any case ->isInWebMount must return true for the user (regardless of $perms_clause)
	 * Usage: 21
	 *
	 * @param integer $id Category uid for which to check read-access
	 * @param string $perms_clause $perms_clause is typically a value generated with SELF->getCategoryPermsClause(1);
	 * @return array Returns category record if OK, otherwise false.
	 */
	public static function readCategoryAccess($id, $perms_clause) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		if ((string) $id != '') {
			$id = (int) $id;
			if (!$id) {
				if ($backendUser->isAdmin()) {
					$path = '/';
					$pageinfo['_thePath'] = $path;
					return $pageinfo;
				}
			} else {
					// $pageinfo = t3lib_BEfunc::getRecord('tx_commerce_categories', $id, '*', ($perms_clause ? ' AND ' . $perms_clause : ''));
				$pageinfo = self::getCategoryForRootline($id, ($perms_clause ? ' AND ' . $perms_clause : ''), FALSE);
				t3lib_BEfunc::workspaceOL('tx_commerce_categories', $pageinfo);
				if (is_array($pageinfo)) {
					t3lib_BEfunc::fixVersioningPid('tx_commerce_categories', $pageinfo);
					list($pageinfo['_thePath'], $pageinfo['_thePathFull']) = self::getCategoryPath((int) $pageinfo['uid'], $perms_clause, 15, 1000);
					return $pageinfo;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
	 * Each part of the path will be limited to $titleLimit characters
	 * Deleted pages are filtered out.
	 * Usage: 15
	 *
	 * @param integer $uid Page uid for which to create record path
	 * @param string $clause is additional where clauses, eg. "
	 * @param integer $titleLimit Title limit
	 * @param integer $fullTitleLimit Title limit of Full title (typ. set to 1000 or so)
	 * @return mixed Path of record (string) OR array with short/long title if $fullTitleLimit is set.
	 */
	public static function getCategoryPath($uid, $clause, $titleLimit, $fullTitleLimit = 0) {
		if (!$titleLimit) {
			$titleLimit = 1000;
		}

		$output = $fullOutput = '/';

		$clause = trim($clause);
		if ($clause !== '' && substr($clause, 0, 3) !== 'AND') {
			$clause = 'AND ' . $clause;
		}
		$data = self::BEgetRootLine($uid, $clause);

		foreach ($data as $record) {
			if ($record['uid'] === 0) {
				continue;
			}
				// Branch points
			if ($record['_ORIG_pid'] && $record['t3ver_swapmode'] > 0) {
					// Adding visual token - Versioning Entry Point - that tells that THIS position was where the versionized branch got connected to the main tree. I will have to find a better name or something...
				$output = ' [#VEP#]' . $output;
			}
			$output = '/' . t3lib_div::fixed_lgd_cs(strip_tags($record['title']), $titleLimit) . $output;
			if ($fullTitleLimit) {
				$fullOutput = '/' . t3lib_div::fixed_lgd_cs(strip_tags($record['title']), $fullTitleLimit) . $fullOutput;
			}
		}

		if ($fullTitleLimit) {
			return array($output, $fullOutput);
		} else {
			return $output;
		}
	}

	/**
	 * Returns what is called the 'RootLine'. That is an array with information about the page records from a page id ($uid) and back to the root.
	 * By default deleted pages are filtered.
	 * This RootLine will follow the tree all the way to the root. This is opposite to another kind of root line known from the frontend where the rootline stops when a root-template is found.
	 * Usage: 1
	 *
	 * @param integer $uid Page id for which to create the root line.
	 * @param string $clause can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
	 * @param boolean $workspaceOL If true, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
	 * @return array Root line array, all the way to the page tree root (or as far as $clause allows!)
	 */
	public static function BEgetRootLine($uid, $clause = '', $workspaceOL = FALSE) {
		static $BEgetRootLine_cache = array();

		$output = array();
		$pid = $uid;
		$ident = $pid . '-' . $clause . '-' . $workspaceOL;

		if (is_array($BEgetRootLine_cache[$ident])) {
			$output = $BEgetRootLine_cache[$ident];
		} else {
			$loopCheck = 100;
			$theRowArray = array();
			while ($uid != 0 && $loopCheck) {
				$loopCheck--;
				$row = self::getCategoryForRootline($uid, $clause, $workspaceOL);
				if (is_array($row)) {
					$uid = $row['pid'];
					$theRowArray[] = $row;
				} else {
					break;
				}
			}
			if ($uid == 0) {
				$theRowArray[] = array('uid' => 0, 'title' => '');
			}
			$c = count($theRowArray);

			foreach ($theRowArray as $val) {
				$c--;
				$output[$c] = array(
					'uid' => $val['uid'],
					'pid' => $val['pid'],
					'title' => $val['title'],
					'ts_config' => $val['ts_config'],
					't3ver_oid' => $val['t3ver_oid'],
				);
				if (isset($val['_ORIG_pid'])) {
					$output[$c]['_ORIG_pid'] = $val['_ORIG_pid'];
				}
			}
			$BEgetRootLine_cache[$ident] = $output;
		}
		return $output;
	}

	/**
	 * Gets the cached page record for the rootline
	 *
	 * @param integer $uid: Page id for which to create the root line.
	 * @param string $clause: can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
	 * @param boolean $workspaceOL: If true, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
	 * @return array Cached page record for the rootline
	 * @see BEgetRootLine
	 */
	protected static function getCategoryForRootline($uid, $clause, $workspaceOL) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		static $getPageForRootline_cache = array();
		$ident = $uid . '-' . $clause . '-' . $workspaceOL;

		if (is_array($getPageForRootline_cache[$ident])) {
			$row = $getPageForRootline_cache[$ident];
		} else {
			$res = $database->exec_SELECTquery(
				'mm.uid_foreign AS pid, uid, hidden, title, ts_config, t3ver_oid',
				'tx_commerce_categories JOIN tx_commerce_categories_parent_category_mm AS mm ON tx_commerce_categories.uid = mm.uid_local',
					// whereClauseMightContainGroupOrderBy
				'uid = ' . (int) $uid . ' ' . t3lib_BEfunc::deleteClause('tx_commerce_categories') . ' ' . $clause
			);

			$row = $database->sql_fetch_assoc($res);
			if ($row) {
				if ($workspaceOL) {
					t3lib_BEfunc::workspaceOL('tx_commerce_categories', $row);
				}
				if (is_array($row)) {
					t3lib_BEfunc::fixVersioningPid('tx_commerce_categories', $row);
					$getPageForRootline_cache[$ident] = $row;
				}
			}
			$database->sql_free_result($res);
		}
		return $row;
	}

	/**
	 * Checks whether the parent category of any content is given the right 'editcontent' for the specific user and returns true
	 * or false depending on the perms
	 *
	 * @param array $categoryUids uids of the categories
	 * @param array $perms string for permissions to check
	 * @return boolean
	 */
	public static function checkPermissionsOnCategoryContent($categoryUids, $perms) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// admin is allowed to do anything
		if ($backendUser->isAdmin()) {
			return TRUE;
		}

		$keys = array_keys($categoryUids);
		$l = count($keys);

		/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
		$mounts = t3lib_div::makeInstance('Tx_Commerce_Tree_CategoryMounts');
		$mounts->init($backendUser->user['uid']);

		for ($i = 0; $i < $l; $i ++) {
			/** @var Tx_Commerce_Domain_Model_Category $category */
			$category = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
			$category->init($categoryUids[$keys[$i]]);
				// check if the category is in the commerce mounts
			if (!$mounts->isInCommerceMounts($category->getUid())) {
				return FALSE;
			}

				// check perms
			for ($j = 0, $m = count($perms); $j < $m; $j ++) {
				if (!$category->isPSet($perms[$j])) {
						// return false if perms are not granted
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Returns if typo3 is running under a AJAX request
	 *
	 * @return boolean
	 */
	public function isAjaxRequest() {
		return (bool) (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX);
	}

	/**
	 * Returns the UID of the Product Folder
	 *
	 * @return integer UID
	 */
	public static function getProductFolderUid() {
		list($modPid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Commerce', 'commerce');
		list($prodPid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'commerce', $modPid);

		return $prodPid;
	}

	/**
	 * Overwrites a product record
	 *
	 * @param integer $uidFrom UID of the product we want to copy
	 * @param integer $uidTo UID of the product we want to overwrite
	 * @param array $locale
	 * @return boolean
	 */
	public static function overwriteProduct($uidFrom, $uidTo, $locale = array()) {
		$table = 'tx_commerce_products';

			// check params
		if (!is_numeric($uidFrom) || !is_numeric($uidTo) || $uidFrom == $uidTo) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('overwriteProduct (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check if we may actually copy the product (no permission check, only check if we are not accidentally copying a placeholder or shadow or deleted product)
		$recordFrom = t3lib_BEfunc::getRecordWSOL($table, $uidFrom, '*', ' AND pid != -1 AND t3ver_state != 1 AND deleted = 0');

		if (!$recordFrom) {
			return FALSE;
		}

			// check if we may actually overwrite the product (no permission check, only check if we are not accidentaly overwriting a placeholder or shadow or deleted product)
		$recordTo = t3lib_BEfunc::getRecordWSOL($table, $uidTo, '*', ' AND pid != -1 AND t3ver_state != 1 AND deleted = 0');

		if (!$recordTo) {
			return FALSE;
		}

			// check if we have the permissions to copy and overwrite (check category rights)
		if (!self::checkProductPerms($uidFrom, 'copy') || !self::checkProductPerms($uidTo, 'editcontent')) {
			return FALSE;
		}

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['overwriteProductClass'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_belib.php\'][\'overwriteProductClass\']
				is deprecated since commerce 0.14.0, it will be removed in commerce 0.16.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Utility/BackendUtility.php\'][\'overwriteProduct\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['overwriteProductClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/BackendUtility.php']['overwriteProduct'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/BackendUtility.php']['overwriteProduct'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		$data = self::getOverwriteData($table, $uidFrom, $uidTo);

			// do not overwrite uid, parent_categories, and create_date
		unset($data[$table][$uidTo]['uid'],
			$data[$table][$uidTo]['categories'],
			$data[$table][$uidTo]['crdate'],
			$data[$table][$uidTo]['cruser_id']
		);

		$datamap = $data;

			// execute
		/** @var t3lib_TCEmain $tce */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

			// Hook: beforeOverwrite
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'beforeOverwrite')) {
				$hookObj->beforeOverwrite($uidFrom, $uidTo, $datamap);
			}
		}

		$tce->start($datamap, array());
		$tce->process_datamap();

			// Hook: afterOverwrite
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'beforeCopy')) {
				$hookObj->beforeCopy($uidFrom, $uidTo, $datamap, $tce);
			}
		}

			// overwrite locales
		if (count($locale) > 0) {
			foreach ($locale as $loc) {
				self::overwriteLocale($table, $uidFrom, $uidTo, $loc);
			}
		}

			// overwrite articles which are existing - do NOT delete articles that are not in the overwritten product but in the overwriting one
		$articlesFrom = self::getArticlesOfProduct($uidFrom);

		if (is_array($articlesFrom)) {
				// the product has articles - check if they exist in the overwritten product
			$articlesTo = self::getArticlesOfProduct($uidTo);

				// simply copy if the overwritten product does not have articles
			if (FALSE === $articlesTo || !is_array($articlesTo)) {
				self::copyArticlesByProduct($uidTo, $uidFrom, $locale);
			} else {
					// go through each article of the overwriting product and check if it exists in the overwritten product
				$l = count($articlesFrom);
				$m = count($articlesTo);

					// walk the articles
				for ($i = 0; $i < $l; $i ++) {
					$overwrite = FALSE;
					$uid = $articlesFrom[$i]['uid'];

						// check if we need to overwrite
					for ($j = 0; $j < $m; $j ++) {
						if ($articlesFrom[$i]['ordernumber'] == $articlesTo[$j]['ordernumber']) {
							$overwrite = TRUE;
							break;
						}
					}

					if (!$overwrite) {
							// copy if we do not need to overwrite
						self::copyArticle($uid, $uidTo, $locale);
					} else {
							// overwrite if the article already exists
						self::overwriteArticle($uid, $articlesTo[$j]['uid'], $locale);
					}
				}
			}
		}

		return TRUE;
	}

	/**
	 * Retrieves the data object to make an overwrite
	 *
	 * @param string $table Tablename
	 * @param integer $uidFrom uid of the record we which to retrieve the data from
	 * @param integer $destPid uid of the record we want to overwrite
	 * @return array
	 */
	public function getOverwriteData($table, $uidFrom, $destPid) {
		/** @var t3lib_TCEmain $tce */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

		$tce->start(array(), array());

		$first = 0;
		$language = 0;
		$uid = $origUid = (int) $uidFrom;

			// Only copy if the table is defined in TCA, a uid is given
		if ($GLOBALS['TCA'][$table] && $uid) {
			t3lib_div::loadTCA($table);

				// This checks if the record can be selected which is all that a copy action requires.
			$data = Array();

			$nonFields = array_unique(t3lib_div::trimExplode(
				',',
				'uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,t3ver_oid,t3ver_wsid,t3ver_id,t3ver_label,
					t3ver_state,t3ver_swapmode,t3ver_count,t3ver_stage,t3ver_tstamp,',
				1
			));

				// So it copies (and localized) content from workspace...
			$row = t3lib_BEfunc::getRecordWSOL($table, $uid);
			if (is_array($row)) {

					// Initializing:
				$theNewID = $destPid;
				$enableField = isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']) ?
					$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] :
					'';
				$headerField = $GLOBALS['TCA'][$table]['ctrl']['label'];

					// Getting default data:
				$defaultData = $tce->newFieldArray($table);

					// Getting "copy-after" fields if applicable:
				$copyAfterFields = array();

					// Page TSconfig related:
					// NOT using t3lib_BEfunc::getTSCpid() because we need the real pid - not the ID of a page, if the input is a page...
				$tscPID = t3lib_BEfunc::getTSconfig_pidValue($table, $uid, - $destPid);
				$TSConfig = $tce->getTCEMAIN_TSconfig($tscPID);
				$tE = $tce->getTableEntries($table, $TSConfig);

					// Traverse ALL fields of the selected record:
				foreach ($row as $field => $value) {
					if (!in_array($field, $nonFields)) {

							// Get TCA configuration for the field:
						$conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

							// Preparation/Processing of the value:
							// "pid" is hardcoded of course:
						if ($field == 'pid') {
							$value = $destPid;
							// Override value...
						} elseif (isset($overrideValues[$field])) {
							$value = $overrideValues[$field];
							// Copy-after value if available:
						} elseif (isset($copyAfterFields[$field])) {
							$value = $copyAfterFields[$field];
							// Revert to default for some fields:
						} elseif (
							$GLOBALS['TCA'][$table]['ctrl']['setToDefaultOnCopy']
							&& t3lib_div::inList($GLOBALS['TCA'][$table]['ctrl']['setToDefaultOnCopy'], $field)
						) {
							$value = $defaultData[$field];
						} else {
								// Hide at copy may override:
							if (
								$first
								&& $field == $enableField
								&& $GLOBALS['TCA'][$table]['ctrl']['hideAtCopy']
								&& !$tce->neverHideAtCopy
								&& !$tE['disableHideAtCopy']
							) {
								$value = 1;
							}
								// Prepend label on copy:
							if ($first && $field == $headerField && $GLOBALS['TCA'][$table]['ctrl']['prependAtCopy'] && !$tE['disablePrependAtCopy']) {
									// @todo this can't work resolvePid and clearPrefixFromValue are not implement in any file of commerce
								$value = $tce->getCopyHeader($table, $this->resolvePid($table, $destPid), $field, $this->clearPrefixFromValue($table, $value), 0);
							}
								// Processing based on the TCA config field type (files, references, flexforms...)
							$value = $tce->copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $tscPID, $language);
						}

							// Add value to array.
						$data[$table][$theNewID][$field] = $value;
					}
				}

					// Overriding values:
				if ($GLOBALS['TCA'][$table]['ctrl']['editlock']) {
					$data[$table][$theNewID][$GLOBALS['TCA'][$table]['ctrl']['editlock']] = 0;
				}

					// Setting original UID:
				if ($GLOBALS['TCA'][$table]['ctrl']['origUid']) {
					$data[$table][$theNewID][$GLOBALS['TCA'][$table]['ctrl']['origUid']] = $uid;
				}

				return $data;
			}
		}

		return array();
	}

	/**
	 * Overwrites the article
	 *
	 * @param integer $uidFrom uid of article that provides the new data
	 * @param integer $uidTo uid of article that is to be overwritten
	 * @param array $locale uids of sys_languages to overwrite
	 * @return boolean Success
	 */
	public function overwriteArticle($uidFrom, $uidTo, $locale = NULL) {
		$table = 'tx_commerce_articles';

			// check params
		if (!is_numeric($uidFrom) || !is_numeric($uidTo)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('copyArticle (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

			// check show right for overwriting article
		$prodFrom = self::getProductOfArticle($uidFrom);

		if (!self::checkProductPerms($prodFrom['uid'], 'show')) {
			return FALSE;
		}

			// check editcontent right for overwritten article
		$prodTo = self::getProductOfArticle($uidTo);

		if (!self::checkProductPerms($prodTo['uid'], 'editcontent')) {
			return FALSE;
		}

			// get the records
		$recordFrom = t3lib_BEfunc::getRecordWSOL($table, $uidFrom, '*');
		$recordTo = t3lib_BEfunc::getRecordWSOL($table, $uidTo, '*');

		if (!$recordFrom || !$recordTo) {
			return FALSE;
		}

		$data = self::getOverwriteData($table, $uidFrom, $uidTo);

		unset($data[$table][$uidTo]['uid'],
			$data[$table][$uidTo]['cruser_id'],
			$data[$table][$uidTo]['crdate'],
			$data[$table][$uidTo]['uid_product']
		);

		$datamap = $data;

			// execute
		/** @var t3lib_TCEmain $tce */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$TCAdefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride)) {
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		$tce->start($datamap, array());

			// Write to session that we copy
			// this is used by the hook to the datamap class to figure out if it should call the dynaflex
			// so far this is the best (though not very clean) way to solve the issue we get when saving an article
		$backendUser->uc['txcommerce_copyProcess'] = 1;
		$backendUser->writeUC();

		$tce->process_datamap();

			// copying done, clear session
		$backendUser->uc['txcommerce_copyProcess'] = 0;
		$backendUser->writeUC();

			// overwrite locales
		if (is_array($locale) && 0 != count($locale)) {
			foreach ($locale as $loc) {
				self::overwriteLocale($table, $uidFrom, $uidTo, $loc);
			}
		}
		return TRUE;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Utility/BackendUtility.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Utility/BackendUtility.php']);
}

?>
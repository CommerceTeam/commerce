<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Thomas Hempel (thomas@work.de)
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
 *
 * @package		TYPO3
 * @subpackage	commerce
 * @author		Thomas Hempel <thomas@work.de>
 *
 * @maintainer	Thomas Hempel <thomas@work.de>
 *
 * $Id$
 */
class tx_commerce_belib {
	/** PRODUCTS **/

	/**
	 * This gets all categories for a product from the database.
	 *
	 * @param	integer		$pUid: The UID of the product
	 * @return	An array of UIDs of all categories for this product
	 */
	function getCategoriesForProductFromDB($pUid)	{
			// get categories that are directly stored in the product dataset
		$pCategories = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign', 'tx_commerce_products_categories_mm', 'uid_local=' .intval($pUid));
		$result = array();
		while ($cUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($pCategories))	{
			$this->getParentCategories($cUid['uid_foreign'], $result);
		}
		return $result;
	}


	/**
	 * Fetches all attribute relation from the database that are assigned to a product specified
	 * through pUid. It can also fetch information about the attribute and a list of attribute
	 * values if the attribute has a valuelist.
	 *
	 * @param	integer		$pUid: The uid of the product
	 * @param	boolean		$separateCT1: If this is true, all attributes with ct1 will be saved in a separated result section
	 * @param	boolean		$addAttributeData: If true, all information about the attributes will be fetched from the database (default is false)
	 * @param	boolean		$getValueListData: If this is true and additional data is fetched and an attribute has a valuelist, this gets the values for the list (default is false)
	 * @return	An array of attributes
	 */
	function getAttributesForProduct($pUid, $separateCT1 = false, $addAttributeData = false, $getValueListData = false)	{
		
		if (!$pUid) {
			return false;
		}
			// get all attributes for the product
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'distinct *',
			'tx_commerce_products_attributes_mm',
			'uid_local=' .intval($pUid),
			'',
			'sorting, uid_foreign DESC, uid_correlationtype ASC'
		);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) ==0){
			return false;
		}
		
		$result = array();
		while ($relData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if ($addAttributeData)	{
					// fetch the data from the attribute table
				$aRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_commerce_attributes',
					'uid=' .intval($relData['uid_foreign']) .$this->enableFields('tx_commerce_attributes'),
					'', 'uid'
				);
				$aData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($aRes);
				$relData['attributeData'] = $aData;
				if ($aData['has_valuelist'] && $getValueListData)	{
						// fetch values for this valuelist entry
					$vlRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						'tx_commerce_attribute_values',
						'attributes_uid=' .intval($aData['uid']) .$this->enableFields('tx_commerce_attribute_values'),
						'', 'uid'
					);
					$vlData = array();
					while ($vlEntry = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($vlRes))	{
						$vlData[$vlEntry['uid']] = $vlEntry;
					}
					
					$relData['valueList'] = $vlData;
				}
				if ($aData['has_valuelist']) {
					$relData['has_valuelist'] = '1';
				}else {
					$relData['has_valuelist'] = '0';
				}
			}
			if (empty($relData))	continue;
			
			if ($separateCT1)	{
				if ($relData['uid_correlationtype'] == 1 && $relData['attributeData']['has_valuelist'] == 1)	{
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
	 * @param	integer		$cUid: The UID of the category that is the startingpoint
	 * @param	array		$cUidList: A list of category UIDs. PASSED BY REFERENCE
	 * @param	integer		$dontAdd: A single UID, if this is found in the parent results, it's not added to the list
	 * @param	integer		$excludeUid: If the current cUid is like this UID the cUid is not processed at all
	 * @param	boolean		$recursive: If true, this method calls itself for each category if finds
	 * @return	void
	 */
	function getParentCategories($cUid, &$cUidList, $dontAdd = 0, $excludeUid = 0, $recursive = true)	{
		if (strlen((string)$cUid) == 0) return;

			// add the submitted uid to the list if it is bigger than 0 and not already in the list
		if ($cUid > 0 && $cUid != $excludeUid)	{
			if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) $cUidList[] = $cUid;

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid_foreign',
				'tx_commerce_categories_parent_category_mm',
				'uid_local=' .intval($cUid),
				'', 'uid_foreign'
			);

			while ($relData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if ($recursive)	{
					$this->getParentCategories($relData['uid_foreign'], $cUidList, $cUid, $excludeUid);
				} else {
					$cUid = $relData['uid_foreign'];
					if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) $cUidList[] = $cUid;
				}
			}

		}
	}

	/**
	 * Get all categories that have this one as parent.
	 *
	 * @param	integer		$cUid: The UID of the category that is the startingpoint
	 * @param	array		$cUidList: A list of category UIDs. PASSED BY REFERENCE
	 * @param	integer		$dontAdd: A single UID, if this is found in the parent results, it's not added to the list
	 * @param	integer		$excludeUid: If the current cUid is like this UID the cUid is not processed at all
	 * @param	boolean		$recursive: If true, this method calls itself for each category if finds
	 * @return	void
	 */
	function getChildCategories($cUid, &$cUidList, $dontAdd = 0, $excludeUid = 0, $recursive = true)	{
		if (strlen((string)$cUid) == 0) return;

			// add the submitted uid to the list if it is bigger than 0 and not already in the list
		if ($cUid > 0 && $cUid != $excludeUid)	{
			if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) $cUidList[] = $cUid;

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid_local',
				'tx_commerce_categories_parent_category_mm',
				'uid_foreign=' .intval($cUid),
				'',
				'uid_local'
			);
			
			if (!$res)	{
				// $GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0)	{
				return array();
			}

			while ($relData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if ($recursive)	{
					$this->getChildCategories($relData['uid_local'], $cUidList, $cUid, $excludeUid);
				} else {
					$cUid = $relData['uid_local'];
					if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) $cUidList[] = $cUid;
				}
			}

		}
	}

	/**
	 * Get all parent categories for a list of given categories. This method
	 * calls the "getParentCategories" method. That one will work recursive.
	 * The result will be written into the argument cUidList.
	 *
	 * @param	array		$cUidList: The list of category UIDs PASSED BY REFERENCE
	 * @return	void
	 */
	function getParentCategoriesFromList(&$cUidList)	{
		if(is_array($cUidList)){
			foreach ($cUidList as $cUid)	{
				$this->getParentCategories($cUid, $cUidList);
			}
		}		
	}

	/**
	 * Returns an array with the data for a single category.
	 *
	 * @param	integer		$cUid: The UID of the category
	 * @param	string		$select: The WHERE part of the query
	 * @param	string		$groupBy: The GROUP BY part of the query
	 * @param	string		$orderBy: The ORDER BY part of the query
	 * @return	An associative array with the data of the category
	 */
	function getCategoryData($cUid, $select = '*', $groupBy = '', $orderBy = '')	{
		$data = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$select,
			'tx_commerce_categories',
			'uid=' .intval($cUid),
			$groupBy,
			$orderBy
		);
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($data);
	}

	/**
	 * Returns all attributes for a list of categories.
	 *
	 * @param	array		$catList: A list of category UIDs
	 * @param	integer		$ct: the correlationtype (can be null)
	 * @param	string		$uidField: The name of the field in the excludeAttributes array that holds the uid of the attributes
	 * @param	array		$excludeAttributes: Array with attributes (the method expects a field called uid_foreign)
	 * @return	An array of attributes
	 */
	function getAttributesForCategoryList($catList, $ct = NULL, $uidField = 'uid', $excludeAttributes = array())	{
		$result = array();
		if (!is_array($catList)) return $result;
		    foreach ($catList as $catUid)	{
			$attributes = $this->getAttributesForCategory($catUid, $ct, $excludeAttributes, $uidField);
			if(is_array($attributes)){
				foreach ($attributes as $attribute)	{
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
	 * @param	integer		$cUid: the uid of the category
	 * @param	integer		$ct: the correlationtype (can be null)
	 * @param	array		$excludeAttributes: Array with attributes (the method expects a field called uid_foreign)
	 * @param	string		$uidField: The name of the field in the excludeAttributes array that holds the uid of the attributes
	 * @return	An array of attributes
	 */
	function getAttributesForCategory($cUid, $ct = NULL, $excludeAttributes = NULL, $uidField = 'uid')	{
			// build the basic query
		$where = 'uid_local=' .$cUid;

			// select only for a special correlation type?
		($ct == NULL) ? '' : $where .= ' AND uid_correlationtype=' .intval($ct);

			// should we exclude some attributes
		if (is_array($excludeAttributes) && count($excludeAttributes) > 0)	{
			$eAttributes = array();
			foreach ($excludeAttributes as $eAttribute) $eAttributes[] = (int)$eAttribute['uid_foreign'];
			$where .= ' AND uid_foreign NOT IN (' .implode(',', $eAttributes) .')';
		}

			// execute the query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_commerce_categories_attributes_mm',
			$where,
			'', 'sorting'
		);

			// build the result and return it...
		$result = array();
		while ($attribute = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$result[] = $attribute;
		}
		return $result;
	}

	/** ATTRIBUTES **/

	/**
	 * Returns the title of an attribute.
	 *
	 * @param	integer		$aUid: The UID of the attribute
	 * @return	The title of the attribute as string
	 */
	function getAttributeTitle($aUid)	{
		$attribute = $this->getAttributeData($aUid, 'title');
		return $attribute['title'];
	}

	/**
	 * Returns a list of Titles for a list of attributes.
	 *
	 * @param	array		$attributeList: An array of attributes (complete datasets with at least one field that contains the UID of an attribute)
	 * @param	string		$uidField: The name of the array key in the attributeList that contains the UID
	 * @return	An array of attribute titles as strings
	 */
	function getAttributeTitles($attributeList, $uidField = 'uid')	{
		$result = array();
		if (is_array($attributeList) && count($attributeList) > 0)	{
			foreach ($attributeList as $attribute)	{
				$result[] = $this->getAttributeTitle($attribute[$uidField]);
			}
		}
		return $result;
	}

	/**
	 * Returns the complete dataset of an attribute. You can select which fields should be fetched from the database.
	 *
	 * @param	integer		$aUid: The UID of the attribute
	 * @param	string		$select: Select here, which fields should be fetched (default is *)
	 * @return	An associative array with the attributeData
	 */
	function getAttributeData($aUid, $select = '*')	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, 'tx_commerce_attributes', 'uid=' .intval($aUid));
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	}


	/**
	 * This fetches the value for an attribute. It fetches the "default_value" from the table
	 * if the attribute has no valuelist, otherwise it fetches the title from the attribute_values
	 * table.
	 * You can submit only an attribute uid, then the mehod fetches the data from the databse itself,
	 * or you submit the data from the relation table and the data from the attribute table if this
	 * data is already present.
	 *
	 * @param	integer		$pUid: The product UID
	 * @param	integer		$aUid: The attribute UID
	 * @param	string		$relationTable: The table where the relations between prodcts and attributes are stored
	 * @param	array		$relationData: The relation dataset between the product and the attribute (default NULL)
	 * @param	array		$attributeData: The meta data (has_valuelist, unit) for the attribute you want to get the value from (default NULL)
	 * @return	The value of the attribute. It's maybe appended with the unit of the attribute
	 */
	function getAttributeValue($pUid, $aUid, $relationTable, $relationData = NULL, $attributeData = NULL)	{
		if ($relationData == NULL || $attributeData == NULL) {
				// data from database if one of the arguments is NULL. This nesccessary
				// to keep the data consistant
			$relRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid_valuelist,default_value,value_char',
				$relationTable,
				'uid_local=' .intval($pUid) .' AND uid_foreign=' .intval($aUid)
			);
			$relationData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($relRes);

			$attributeData = $this->getAttributeData($aUid, 'has_valuelist,unit');
		}
		if ($attributeData['has_valuelist'] == '1')	{
			if ($attributeData['multiple'] == 1)	{
				$result = array();
				if (is_array($relationData))	{
					foreach ($relationData as $relation)	{
						$valueRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'value',
							'tx_commerce_attribute_values',
							'uid=' .intval($relation['uid_valuelist']) .$this->enableFields('tx_commerce_attribute_values')
						);
						$value = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($valueRes);
						$result[] = $value['value'];
					}
				}
				return '<ul><li>' .implode('</li><li>', $result) .'</li></ul>';

			} else {
					// fetch data from attribute values table
				$valueRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'value',
					'tx_commerce_attribute_values',
					'uid=' .intval($relationData['uid_valuelist']) .$this->enableFields('tx_commerce_attribute_values')
				);
				$value = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($valueRes);
				return $value['value'];
			}
		} elseif (!empty($relationData['value_char']))	{
				// the value is in field default_value
			return $relationData['value_char'] .' ' .$attributeData['unit'];
		} else {
			return $relationData['default_value'] .' ' .$attributeData['unit'];
		}
	}

	/**
	 * Returns the correlationtype of a special attribute inside a product.
	 *
	 * @param	integer		$aUid: The UID of the attribute
	 * @param	integer		$pUid: The UID of the product
	 * @return	The correlationtype
	 */
	function getCtForAttributeOfProduct($aUid, $pUid)	{
		$ctRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_correlationtype',
			'tx_commerce_products_attributes_mm',
			'uid_local=' .intval($pUid) .' AND uid_foreign=' .intval($aUid)
		);
		$uidCT = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($ctRes);
		return $uidCT['uid_correlationtype'];
	}


	/** ARTICLES **/

	/**
	 * Return all articles that where created from a given product.
	 *
	 * @param	integer		$pUid: The UID of the product
	 * @return	An array of article datasets as assoc array or false if nothing was found
	 *
	 * @since 20.12.2005 Check if article exists
	 */
	function getArticlesOfProduct($pUid, $additionalWhere = '', $orderBy = '')	{
		$where = 'uid_product=' .intval($pUid);

		$where .= ' AND deleted=0';

		if ($additionalWhere != '') {
			$where .= ' AND ' .$additionalWhere;
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_articles', $where, '', $orderBy);
		if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0)	{
			$result = array();
			while ($article = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$result[] = $article;
			}
			return $result;
		} else {
			return false;
		}
	}
	
/**
	 * Return all articles that where created from a given product.
	 *
	 * @param	integer		$pUid: The UID of the product
	 * @return	An array of article UIDs, ready to implode for coma separed list
	 *
	 * @since 20.12.2005 Check if article exists
	 */
	function getArticlesOfProductAsUidList($pUid, $additionalWhere = '', $orderBy = '')	{
		$where = 'uid_product=' .$pUid;

		$where .= ' AND deleted=0';

		if ($additionalWhere != '') {
			$where .= ' AND ' .$additionalWhere;
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_articles', $where, '', $orderBy);
		if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0)	{
			$result = array();
			while ($article = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$result[] = $article['uid'];
			}
			return $result;
		} else {
			return false;
		}
	}
	

	/**
	 * Returns the product from which an article was created.
	 *
	 * @param	integer		$aUid: Article UID
	 * @param	string		$getProductData: which fields should be returned of the product. This is a comma separated list (default *)
	 * @return	If the "getProductData" param is empty, this returnss the UID as integer, otherwise it returns an associative array of the dataset
	 */
	function getProductOfArticle($aUid, $getProductData = '*')	{
		$where = 'uid=' .intval($aUid) .' AND deleted=0';
		
		$proRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_product', 'tx_commerce_articles', $where);
		$proRes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($proRes);

		if (strlen($getProductData) > 0)	{
			$where = 'uid=' .intval($proRes['uid_product']) ;
			$proRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($getProductData, 'tx_commerce_products', $where);
			
			$proRes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($proRes);
			return $proRes;
		} else {
			return $proRes['uid_product'];
		}
	}

	/**
	 * Returns all attributes for an article
	 *
	 * @param	integer		$aUid: The article UID
	 * @param	integer		$ct: The correlationtype
	 * @param	array		$excludeAttributes: An array of relation datasets where the field "uid_foreign" is the UID of the attribute you don't want to get
	 * @return	Return an array of attributes
	 */
	function getAttributesForArticle($aUid, $ct = NULL, $excludeAttributes = NULL)	{
			// build the basic query
		$where = 'uid_local=' .$aUid;

		if ($ct != NULL)	{
			$pUid = $this->getProductOfArticle($aUid, 'uid');
			
			$productAttributes = $this->getAttributesForProduct($pUid['uid']);
			$ctAttributes = array();
			if (is_array($productAttributes))	{
				foreach ($productAttributes as $productAttribute)	{
					if ($productAttribute['uid_correlationtype'] == $ct)	{
						$ctAttributes[] = $productAttribute['uid_foreign'];
					}
				}
				if (count($ctAttributes) > 0)	{
					$where .= ' AND uid_foreign IN (' .implode(',', $ctAttributes) .')';
				}
			}
		}

			// should we exclude some attributes
		if (is_array($excludeAttributes) && count($excludeAttributes) > 0)	{
			$eAttributes = array();
			foreach ($excludeAttributes as $eAttribute) $eAttributes[] = (int)$eAttribute['uid_foreign'];
			$where .= ' AND uid_foreign NOT IN (' .implode(',', $eAttributes) .')';
		}

			// execute the query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_commerce_articles_article_attributes_mm',
			$where
		);

			// build the result and return it...
		$result = array();
		while ($attribute = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$result[] = $attribute;
		}
		return $result;
	}

	/**
	 * Returns the hash value for the ct1 select attributes for an article.
	 *
	 * @param	integer		$aUid: the uid of the article
	 * @param	array		$fullAttributeList: The list of uids for the attributes that are assigned to the article
	 */
	function getArticleHash($aUid, $fullAttributeList)	{
		$hashData = array();

		if (count($fullAttributeList) > 0)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_commerce_articles_article_attributes_mm',
				'uid_local='.intval($aUid).' AND uid_foreign IN ('.implode(',', $fullAttributeList).')'
			);

			while ($attributeData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
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
	 * @param	integer		$aUid: the uid of the article
	 * @param	array		$fullAttributeList: The list of uids for the attributes that are assigned to the article
	 */
	function updateArticleHash($aUid, $fullAttributeList = NULL)	{
		if ($fullAttributeList == NULL)	{
			$fullAttributeList = array();
			$articleAttributes = $this->getAttributesForArticle($aUid, 1);
			if (count($articleAttributes) > 0)	{
				foreach ($articleAttributes as $articleAttribute)	{
					$fullAttributeList[] = $articleAttribute['uid_foreign'];
				}
			}
		}

		$hash = $this->getArticleHash($aUid, $fullAttributeList);

			// update the article
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_commerce_articles',
			'uid='.$aUid,
			array ('attribute_hash' => $hash)
		);
	}

	/** DIV **/


	/**
	 * proofs if there are non numeric chars in it
	 *
	 * @param	string		$data: string to check for a number
	 * @return length of wrong chars
	 */
	function isNumber($data)	{
		$charArray = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0, '.');
		return strlen(str_replace($charArray,array(),$data));
	}

	/**
	 * This method returns the last part of a string.
	 * It splits up the string at the underscores.
	 * If the key doesn't contain any underscores, it returns
	 * the intval of the key.
	 *
	 * @param	string		$key: The key string (e.g.: bla_fasel_12)
	 * @param	array		$keyData: the result from the explode method (PASSED BY REFERENCE)
	 * @return	Integer value of the last part from the key
	 *
	 * @since 13.12.2005 changed $keyData from refference to Array ingo schmitt <is@marketing-factory.de>
	 * @since 03.01.2006 changed $keyData to refference from Array ingo schmitt <is@marketing-factory.de>
	 *
	 */
	function getUidFromKey($key, &$keyData)	{
		if (strpos($key, '_') === false)	{
			return intval($key);
		} else {
			$keyData = @explode('_', $key);
			if (is_array($keyData))	{
				return intval($keyData[(count($keyData) -1)]);
			}

		}
	}

	/**
	
	 * Searches for a string in an array of arrays.
	 *
	 * @param	string		$needle: The string you want to check for
	 * @param	array		$array: The array you want to search in
	 * @param	string		$field: The fieldname of the inside arrays in the search array
	 * @return	True if the needle was found, otherwise false
	 */
	function checkArray($needle, $array, $field)	{
		if (!is_array($array))	return false;
		foreach ($array as $entry)	{
			if ($needle == $entry[$field]) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the "enable fields" for a sql query.
	 *
	 * @param	string		$table: The table for the query
	 * @param	boolean		$getDeleted: Flag if deleted entries schould be returned
	 * @return	A SQL-string with the enable fields
	 */
	function enableFields($table, $getDeleted = false)	{
		$result = t3lib_befunc::BEenableFields($table);
		if (!$getDeleted) $result .= t3lib_befunc::deleteClause($table);
		return $result;
	}

	/**
	 * Returns a list of UID from a list of strings that contains UIDs.
	 *
	 * @see $this->getUidFromKey
	 *
	 * @param	array		$list: The list of strings
	 * @return	An array with extracted UIDs
	 */
	function getUidListFromList($list)	{
		$result = array();
		if(is_array($list)){
		    foreach ($list as $item)	{
			$uid = $this->getUidFromKey($item, $keyData);
			if ($uid > 0) $result[] = $uid;
		    }
		}
		return $result;
	}

	/**
	 * Saves all relations between two tables. For example all relations between products and articles.
	 *
	 * @param	integer		$uid_local: The uid_local in the mm table
	 * @param	array		$relationData: An array with the data that should be stored additionally in the relation table
	 * @param	string		$relationTable: The table where the relations are stored
	 * @param	boolean		$delete: Delete all old relations
	 * @param	boolean		$withReference: If true, the field "is_reference" is inserted into the database
	 * @return	void
	 */
	function saveRelations($uid_local, $relationData, $relationTable, $delete = false, $withReference = true)	{
		$delWhere = array();
		$counter = 1;
		$usedRelations = array();

		if(is_array($relationData)){
			foreach ($relationData as $relation)	{
				$where = 'uid_local=' .intval($uid_local);
				$dataArray = array ('uid_local' => $uid_local);

				if(is_array($relation)){
					foreach ($relation as $key => $data)	{
						$dataArray[$key] = $data;
						$where .= ' AND ' .$key .'=\'' .$data .'\'';
					}
				}
				if ($withReference && ($counter > 1))	{
					$dataArray['is_reference'] = 1;
					$where .= ' AND is_reference=1';
				}

				$dataArray['sorting'] = $counter;
				$counter++;

				$checkRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_local', $relationTable, $where);
				$exists = ($GLOBALS['TYPO3_DB']->sql_num_rows($checkRes) > 0);

				if (!$exists)	{
				    $GLOBALS['TYPO3_DB']->exec_INSERTquery($relationTable, $dataArray);
				} else {
					
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($relationTable, $where, array('sorting' => $counter));
				}

				$delClause = 'uid_foreign=' .$relation['uid_foreign'];
	 			if (isset($relation['uid_correlationtype'])) $delClause .= ' AND uid_correlationtype=' .$relation['uid_correlationtype'];
 				$delWhere[] = $delClause;
		    }
		}

		if ($delete && is_array($delWhere))	{
			$where = '';
			if (count($delWhere) > 0)	{
				$where = ' AND NOT ((' .implode(') OR (', $delWhere) .'))';
			}
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$relationTable,
				'uid_local=' .$uid_local .$where
			);
		}
	}

	/**
	 * Get all existing correlation types.
	 *
	 * @return	array with correlation type entities
	 */
	function getAllCorrelationTypes()	{
		$ctRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_attribute_correlationtypes', '1');
		$result = array();
		while ($ct = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($ctRes))	{
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
	 * @param	array		$articleRelations: The relation dataset for the article
	 * @param	boolean		$add: If this is true, we fetch the existing data before. Otherwise we overwrite it
	 * @param	integer		$articleUid: The UID of the article
	 * @param	integer		$productUid: The UID of the product
	 * @return	void
	 */
	function updateArticleXML($articleRelations, $add = false, $articleUid = NULL, $productUid = NULL)	{
	   $xmlData = array();
	   if ($add && is_numeric($articleUid))	{
			$xmlData = $GLOBALS['TYPO3_DB']->exec_SELECTquery('attributesedit', 'tx_commerce_articles', 'uid=' .intval($articleUid));
			$xmlData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($xmlData);
			$xmlData = t3lib_div::xml2array($xmlData['attributesedit']);
		}
		
		
		$relationData= array();
		/**
		 * Build Relation Data
		 */
		if ($productUid) {
			$resRelationData = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_commerce_articles_article_attributes_mm.*','tx_commerce_articles, tx_commerce_articles_article_attributes_mm',' tx_commerce_articles.uid = tx_commerce_articles_article_attributes_mm.uid_local and tx_commerce_articles.uid_product = '.intval($productUid));
		}
		if ($articleUid) {
			$resRelationData = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_commerce_articles_article_attributes_mm.*','tx_commerce_articles, tx_commerce_articles_article_attributes_mm',' tx_commerce_articles.uid = tx_commerce_articles_article_attributes_mm.uid_local and tx_commerce_articles.uid = '.intval($articleUid));
		}
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($resRelationData)>0) {
			
			while($relationRows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resRelationData)) {
				$relationData[]=$relationRows;
			}
			
		}
		
		
		if(is_array($relationData)){
		    foreach ($articleRelations as $articleRelation)	{
				if ($articleRelation['uid_valuelist'] != 0)	{
					$value = $articleRelation['uid_valuelist'];
				} elseif (!empty($articleRelation['value_char']))	{
					$value = $articleRelation['value_char'];
				} else {
					if ($articleRelation['default_value']<>0) {
						$value = $articleRelation['default_value'];
						$value='';
					}
				}
				
				$xmlData['data']['sDEF']['lDEF']['attribute_' .$articleRelation['uid_foreign']] = array('vDEF' => $value);
		    }
		}
		
		$xmlData = t3lib_div::array2xml($xmlData, '', 0, 'T3FlexForms');
	
		if ($articleUid)	{
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_articles', 'uid=' .$articleUid .' AND deleted=0', array('attributesedit' => $xmlData));
		} elseif ($productUid) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_articles', 'uid_product=' .$productUid .' AND deleted=0', array('attributesedit' => $xmlData));
		}
	}

	/**
	 * Updates the XML of an FlexForm value field. This is almost the same as "updateArticleXML" but more general.
	 *
	 * @see $this->updateArticleXML
	 *
	 * @param	string		$xmlField: The fieldname where the FlexForm values are stored
	 * @param	string		$table: The table in which the FlexForm values are stored
	 * @param	integer		$uid: The UID of the entity inside the table
	 * @param	string		$type: The type of data we are handling (category, product)
	 * @param	array		$ctList: A list of correlationtype UID we should handle
	 * @return	array		array($xmlField => $xmlData)
	 */
	function updateXML($xmlField, $table, $uid, $type, $ctList)	{
		$xmlData = $GLOBALS['TYPO3_DB']->exec_SELECTquery($xmlField, $table, 'uid=' .intval($uid));
		$xmlData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($xmlData);
		if($xmlData[$xmlField]) {
			$xmlData = t3lib_div::xml2array($xmlData[$xmlField]);
			
			switch (strtolower($type))	{
				case 'category':
					$relList = $this->getAttributesForCategory($uid);
				break;
				case 'product':
					$relList = $this->getAttributesForProduct($uid);
				break;
			}
	
				// write the data
			if (is_array($ctList))	{
				foreach ($ctList as $ct)	{
					$value = array();
					if (is_array($relList)) {
						foreach ($relList as $relation)	{
							if ($relation['uid_correlationtype'] == $ct['uid'])	{
								$value[] = $relation['uid_foreign'];
							}
						}
					}
					
					if (count($value) > 0) {
						$xmlData['data']['sDEF']['lDEF']['ct_' .$ct['uid']] = array('vDEF' => (string)implode(',', $value));
					}
				}
			}
	
				// build new XML
			if (is_array($xmlData)) {
				// Dump Quickfix
				$xmlData = t3lib_div::array2xml($xmlData, '', 0, 'T3FlexForms');
			}else{
				$xmlData = '';
			}
	
				// update database entry
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' .$uid, array($xmlField => $xmlData));
			return array($xmlField => $xmlData);
		} else {
			return '';
		}
	}

	/**
	 * Merges an attribute list from a flexform together into one array.
	 *
	 * @param	array		$ffData: The FlexForm data as array
	 * @param	string		$prefix: The prefix that is used for the correlationtypes
	 * @param	array		$ctList: The list of correlations that should be processed
	 * @param	integer		$uid_local: The "uid_local" in the relation table
	 * @param	array		$paList: The list of product attributes (PASSED BY REFERENCE)
	 * @return	void
	 */
	function mergeAttributeListFromFFData($ffData, $prefix, $ctList, $uid_local, &$paList)	{
	  if(is_array($ctList)){	
	    foreach ($ctList as $ctUid)	{
			$ffaList = explode(',', $ffData[$prefix .$ctUid['uid']]['vDEF']);
			if (count($ffaList) == 1 && $ffaList[0] == '') continue;
			foreach ($ffaList as $aUid)	{
				if (!(
					$this->checkArray($uid_local, $paList, 'uid_local') &&
					$this->checkArray($aUid, $paList, 'uid_foreign') &&
					$this->checkArray($ctUid['uid'], $paList, 'uid_correlationtype')
				))	{
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

	/**
	 * Extracts a fieldvalue from an associative array.
	 *
	 * @param	array		$array: The data array
	 * @param	string		$field: The field that should be extracted
	 * @param	boolean		$makeArray: If true the result is returned as an array of arrays
	 * @param	array		$extraFields: Add some extra fields to the result
	 * @return	An array with fieldnames
	 */
	function extractFieldArray($array, $field, $makeArray = false, $extraFields = array())	{
		$result = array();
		if(is_array($array)){	
			foreach ($array as $data)	{
			    if ($data[$field] == '')	{
				$item[$field] = $data;
			    } else {
				$item = $data;
			    }
			    if ($makeArray)	{
				$newItem = array($field => $item[$field]);
				if (count($extraFields > 0))	{
					foreach ($extraFields as $extraFieldName) {
						$newItem[$extraFieldName] = $item[$extraFieldName];
					}
				}
			    } else {
				$newItem = $item[$field];
			    }
			    if (!in_array($newItem, $result)) $result[] = $newItem;
			}
	    }		
		return $result;
	}

	/**
	 * Return all productes that are related to a category.
	 *
	 * @param	uid		$cUid: The UID of the category.
	 * @return	An array with the entities of the found products
	 */
	function getProductsOfCategory($cUid)	{
		$result = array();
		$proRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_products_categories_mm', 'uid_foreign=' .intval($cUid));
		while ($proRel = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($proRes))	{
			$result[] = $proRel;
		}
		return $result;
	}

	/**
	 * Retruns and array for the UPDATEquery method. It fills different arrays with an attribute value.
	 * This wraper is needed because the fields have different names in different tables. I know that's stupid
	 * but this is a fact... :-/
	 *
	 * @param	array		$attributeData: The data that should be stored
	 * @param	mixed		$data: The value of the attribute
	 * @param	integer		$productUid: The UID of the produt
	 * @return	An array with two arrays as elements
	 */
	function getUpdateData($attributeData, $data, $productUid = '')	{
		$updateArray = array();
		$updateArray['uid_valuelist'] = '';
		$updateArray['default_value'] = '';
		$updateArray2 = $updateArray;
		$updateArray2['value_char'] = '';
		$updateArray2['uid_product'] = $productUid;

		if ($attributeData['has_valuelist'] == 1)	{
			$updateArray['uid_valuelist'] = $data;
			$updateArray2['uid_valuelist'] = $data;
		} else {
			if (!$this->isNumber($data))	{
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
	 * @author	Ingo Schmitt	<is@marketing-factory.de>
	 * @param	$flexValue		String	XML-Flex-Form
	 * @param	$langIdent		Language Ident
	 * @return	string 		String	XML-Flex-Form
	 */
	
	function buildLocalisedAttributeValues($flexValue,$langIdent){
		$attrFFData = t3lib_div::xml2array($flexValue);
	
		if (is_array($attrFFData))	{
			// change the language
			$attrFFData['data']['sDEF']['l' .$langIdent] = $attrFFData['data']['sDEF']['lDEF'];
			
			/**
			 * Decide on what to to on lokalisation, how to act
			 * @see ext_conf_template
			 * attributeLokalisationType[0|1|2]
			 * 0: set blank
			 * 1: Copy
			 * 2: prepend [Translate to .$langRec['title'].:]
			 */
			
			 $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
			 switch ($extConf['attributeLokalisationType'])
			 {
			 	case 0:
			 		unset($attrFFData['data']['sDEF']['lDEF']);
			 	break;
			 	case 1:
			 	break;
			 	case 2:
			 		/**
			 		 * Walk thru the array and prepend text
			 		 */	
			 		 $prepend= "[Translate to $langIdent:] ";
			 		 foreach ($attrFFData['data']['sDEF']['lDEF'] as  $attribKey => $attributes){
			 		 	$attrFFData['data']['sDEF']['lDEF'][$attribKey]['vDEF']=$prepend.$attrFFData['data']['sDEF']['lDEF'][$attribKey]['vDEF'];
			 		 }
			 	break;
			 						 
			}
			return  t3lib_div::array2xml($attrFFData, '', 0, 'T3FlexForms');
		}
		
	}
	
	/**
	 * save Price-Flexform with given Article-UID
	 * 
	 * @author	Joerg Sprung <jsp@marketing-factory>
	 * @param	integer	$priceUid		ID of Price-Dataset save as flexform
	 * @param	integer	$articleUid		ID of article which the flexform is for
	 * @param	array	$priceDataArray	Priceinformation for the article
	 * @return	boolean	Status of method
	 * @see tx_commerce_belib
	 */
	function savePriceFlexformWithArticle( $priceUid , $articleUid, $priceDataArray) {
				
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'prices',
			'tx_commerce_articles',
			'uid=' .intval($articleUid)
		);
		
		$prices = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if (strlen($prices['prices']) > 0)	{
			$data = t3lib_div::xml2array($prices['prices']);
		} else {
			$data = array('data' => array('sDEF' => array('lDEF')));
		}
		
		$data['data']['sDEF']['lDEF']['price_net_' .$priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['price_net'] /100)));
		$data['data']['sDEF']['lDEF']['price_gross_' .$priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['price_gross'] /100)));
		$data['data']['sDEF']['lDEF']['hidden_' .$priceUid] = array('vDEF' => $priceDataArray['hidden']);
		$data['data']['sDEF']['lDEF']['starttime_' .$priceUid] = array('vDEF' => $priceDataArray['starttime']);
		$data['data']['sDEF']['lDEF']['endtime_' .$priceUid] = array('vDEF' => $priceDataArray['endtime']);
		$data['data']['sDEF']['lDEF']['fe_group_' .$priceUid] = array('vDEF' => $priceDataArray['fe_group']);
		$data['data']['sDEF']['lDEF']['purchase_price_' .$priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['purchase_price'] /100)));
	 	$data['data']['sDEF']['lDEF']['price_scale_amount_start_' .$priceUid] = array('vDEF' => $priceDataArray['price_scale_amount_start']);
        $data['data']['sDEF']['lDEF']['price_scale_amount_end_' .$priceUid] = array('vDEF' => $priceDataArray['price_scale_amount_end']);                       
  
		$xml = t3lib_div::array2xml($data, '', 0, 'T3FlexForms');
		
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_commerce_articles',
			'uid=' .$articleUid,
			array('prices' => $xml)
		);
		
		return (bool)$res;
	 	
	}
	
	
	 	
	
	function getOrderFolderSelector($pid,$levels,$aktLevel =0) {
		$returnArray=array();
		/*
		 * Query the table to build dropdown list
 		 */
 		$prep='';
 		for ($i=0;$i<$aktLevel;$i++) {
 			$prep.='- ';
 		}
 		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$GLOBALS['TCA']['tx_commerce_orders']['columns']['newpid']['config']['foreign_table'],"pid = $pid" .t3lib_BEfunc::deleteClause($GLOBALS['TCA']['tx_commerce_orders']['columns']['newpid']['config']['foreign_table']),'','sorting' );
 	#	
 		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0) 		{
 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))		{
 				$return_data['title'] = $prep.$return_data['title'];
 			
 				$returnArray[]=array($return_data['title'],$return_data['uid']);	
 				$tmparray = tx_commerce_belib::getOrderFolderSelector($return_data['uid'],$levels-1,$aktLevel+1);
 				if (is_array($tmparray)) {
 					$returnArray = array_merge($returnArray,$tmparray);
 				}
 			}
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
  		}
  		if (count($returnArray)>0) {
  			return $returnArray;
  		}else {
  			return false;
  		}	
	}
	
	/**
         * update Flexform XML from Database
         * 
         * @author      Christian Sander <cs2@marketing-factory>
         * @param       integer $articleUid             ID of article
         * @return      boolean Status of method
         * @see tx_commerce_belib
         */

        function updatePriceXMLFromDatabase( $articleUid) {
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    '*',
                    'tx_commerce_article_prices',
                    'deleted=0 AND uid_article=' .intval($articleUid)
            );
            $data = array('data' => array('sDEF' => array('lDEF')));
            while($priceDataArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
                    $priceUid=$priceDataArray["uid"];
                    $data['data']['sDEF']['lDEF']['price_net_' .$priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['price_net'] /100)));
                    $data['data']['sDEF']['lDEF']['price_gross_' .$priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['price_gross'] /100)));
                    $data['data']['sDEF']['lDEF']['hidden_' .$priceUid] = array('vDEF' => $priceDataArray['hidden']);
                    $data['data']['sDEF']['lDEF']['starttime_' .$priceUid] = array('vDEF' => $priceDataArray['starttime']);
                    $data['data']['sDEF']['lDEF']['endtime_' .$priceUid] = array('vDEF' => $priceDataArray['endtime']);
                    $data['data']['sDEF']['lDEF']['fe_group_' .$priceUid] = array('vDEF' => $priceDataArray['fe_group']);
                    $data['data']['sDEF']['lDEF']['purchase_price_' .$priceUid] = array('vDEF' => sprintf('%.2f', ($priceDataArray['purchase_price'] /100)));
                    $data['data']['sDEF']['lDEF']['price_scale_amount_start_' .$priceUid] = array('vDEF' => $priceDataArray['price_scale_amount_start']);
                    $data['data']['sDEF']['lDEF']['price_scale_amount_end_' .$priceUid] = array('vDEF' => $priceDataArray['price_scale_amount_end']);                       
            }

            $xml = t3lib_div::array2xml($data, '', 0, 'T3FlexForms');

            $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                    'tx_commerce_articles',
                    'uid=' .$articleUid,
                    array('prices' => $xml)
            );

            return (bool)$res;
    }
	
	/**
     * This makes the Xml to draw the price form of an article
     * Call this function if you have imported data to the database and havn't
     * updated the Flexform
     * @author Ricardo Mieres <ricardo.mieres@502.cl>
     * @param	integer $article_uid
     * @see updatePriceXMLFromDatabase
     * 
     */
    function fix_articles_price($article_uid=0){
  		if($article_uid==0){
  			return ;
  		}
  		
		return updatePriceXMLFromDatabase($article_uid);
    }
    
    /**
     * This makes the Xml for the product Attributes
     * Call thos function if you have imported data to the database and havn't
     * updated the Flexform
     * @author Ricardo Mieres <ricardo.mieres@502.cl>
     * @param	integer $product_uid
     */

    function fix_product_atributte($product_uid=0){
    	if($product_uid==0){
  			return ;
  		}
  		$fieldSelected='*';
  		$mm_table='tx_commerce_products_attributes_mm';
  		$where='uid_local= '.intval($product_uid);
    	$rs=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_correlationtype,uid_foreign',$mm_table,$where,$groupBy='',$orderBy='',$limit='');
    	#$GLOBALS['TYPO3_DB']->debug('exec_SELECTquery');
		$xmlArray=array();
		 while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs)){
        			if(empty($xmlArray['data']['sDEF']['lDEF']['ct_'.$row['uid_correlationtype']]['vDEF']))
        				$xmlArray['data']['sDEF']['lDEF']['ct_'.$row['uid_correlationtype']]['vDEF']=$row['uid_foreign'];
        			else
        				$xmlArray['data']['sDEF']['lDEF']['ct_'.$row['uid_correlationtype']]['vDEF'].=','.$row['uid_foreign'];
		 }
		$arrayToSave=array();
		$xmlText=t3lib_div::array2xml_cs ($xmlArray, 'T3FlexForms', $options=array(), $charset='');
		$arrayToSave['attributes']=$xmlText;

		$rs=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_products','uid='.$product_uid,$arrayToSave,$no_quote_fields=false);
	#	$GLOBALS['TYPO3_DB']->debug('exec_UPDATEquery');

    }
    /**
     * This makes the Xml for the categoryt Attributes
     * Call thos function if you have imported data to the database and havn't
     * updated the Flexform
     * @author Ricardo Mieres <ricardo.mieres@502.cl>
     * @param	integer $category_uid
     */
    
    function fix_category_atributte($category_uid=0){
    	if($category_uid==0){
  			return ;
  		}
  		$fieldSelected='*';
  		$mm_table='tx_commerce_categories_attributes_mm';
  		$where='uid_local= '.intval($category_uid);
    	$rs=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_correlationtype,uid_foreign',$mm_table,$where,$groupBy='',$orderBy='',$limit='');
    	#$GLOBALS['TYPO3_DB']->debug('exec_SELECTquery');
		$xmlArray=array();
		 while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs)){
        			if(empty($xmlArray['data']['sDEF']['lDEF']['ct_'.$row['uid_correlationtype']]['vDEF']))
        				$xmlArray['data']['sDEF']['lDEF']['ct_'.$row['uid_correlationtype']]['vDEF']=$row['uid_foreign'];
        			else
        				$xmlArray['data']['sDEF']['lDEF']['ct_'.$row['uid_correlationtype']]['vDEF'].=','.$row['uid_foreign'];
		 }
		$arrayToSave=array();
		$xmlText=t3lib_div::array2xml_cs ($xmlArray, 'T3FlexForms', $options=array(), $charset='');
		$arrayToSave['attributes']=$xmlText;

		$rs=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_categories','uid='.$category_uid,$arrayToSave,$no_quote_fields=false);
		#$GLOBALS['TYPO3_DB']->debug('exec_UPDATEquery');

    }

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_belib.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_belib.php"]);
}

?>
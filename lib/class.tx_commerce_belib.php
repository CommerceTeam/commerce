<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 - 2009 Thomas Hempel (thomas@work.de)
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

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_folder_db.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_article.php'); 
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_product.php'); 
require_once (PATH_t3lib.'class.t3lib_tcemain.php');

class tx_commerce_belib {
	/** PRODUCTS **/

	/**
	 * This gets all categories for a product from the database (even those that are not direct).
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
	 * Gets all direct parent categories of a product
	 * 
	 * @return {array}
	 * @param $uid {int}	uid of the product
	 */
	function getProductParentCategories($uid) {
		$pCategories = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign', 'tx_commerce_products_categories_mm', 'uid_local=' .$uid);
		$result = array();
		
		while ($cUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($pCategories))	{
			$result[] = $cUid['uid_foreign'];
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
				return '<ul><li>' .implode('</li><li>', tx_commerce_div::removeXSSStripTagsArray($result)) .'</li></ul>';

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
					if ($articleRelation['default_value'] <> 0) {
						$value = $articleRelation['default_value'];
					} else {
						$value = '';
					}
				}
				
				$xmlData['data']['sDEF']['lDEF']['attribute_' .$articleRelation['uid_foreign']] = array('vDEF' => $value);
		    }
		}
		
		$xmlData = t3lib_div::array2xml($xmlData, '', 0, 'T3FlexForms');
	
		if ($articleUid) {
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
	function updateXML($xmlField, $table, $uid, $type, $ctList, $rebuild = false)	{
		$xmlData = $GLOBALS['TYPO3_DB']->exec_SELECTquery($xmlField, $table, 'uid=' .intval($uid));
		$xmlData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($xmlData);
		$xmlData = t3lib_div::xml2array($xmlData[$xmlField]);
		if (!is_array($xmlData)) {
			$xmlData = array();
		}
		switch (strtolower($type))	{
			case 'category':
				$relList = $this->getAttributesForCategory($uid);
			break;
			case 'product':
				$relList = $this->getAttributesForProduct($uid);
			break;
		}
		
		$cTypes = array();
		
			// write the data
		if (is_array($ctList))	{
			foreach ($ctList as $ct)	{
				$value = array();
				if (is_array($relList)) {
					foreach ($relList as $relation)	{
						if ($relation['uid_correlationtype'] == (int)$ct['uid'])	{
							
							// add ctype to checklist in case we need to rebuild
							if(!in_array($ct['uid'], $cTypes)) {
								$cTypes[] = (int)$ct['uid'];
							}
							
							$value[] = $relation['uid_foreign'];
						}
					}
				}
			
				if (count($value) > 0) {
					$xmlData['data']['sDEF']['lDEF']['ct_' .(string)$ct['uid']] = array('vDEF' => (string)implode(',', $value));
				}
			}
		}
		
		
		// rebuild
		if($rebuild && 0 < count($cTypes) && is_array($ctList)) {
			foreach ($ctList as $ct)	{
				if (!in_array($ct['uid'], $cTypes)) {
					$xmlData['data']['sDEF']['lDEF']['ct_' .(string)$ct['uid']] = array('vDEF' => '');
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
					
					if($aUid != '') {
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
			    if (!is_array($data) || (is_array($data) && !array_key_exists($field, $data)))	{
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
			
			 switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['attributeLokalisationType'])
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
	 * Removed with irre implementation. Stub left for api compatibility
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
     * @deprecated, see $this->updateXML
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
     * @deprecated, see $this->updateXML
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
	 * This function gives all attributes of one product to the other (only mm attributes, flexforms need to be handled separately [@see fix_product_atributte()])
	 * 
	 * @return {boolean}		Success
	 * @param {int} $pUidFrom	Product UID from which to take the Attributes
	 * @param {int}	$pUidTo		Product UID to which we give the Attributes
	 * @param {boolean} $copy	If set, the Attributes will only be copied - else cut (aka "swapped" in its true from)
	 */
	function swapProductAttributes($pUidFrom, $pUidTo, $copy = false) {
		
		//verify params
		if(!is_numeric($pUidFrom) || !is_numeric($pUidTo)) {
			if (TYPO3_DLOG) t3lib_div::devLog('swapProductAttributes (tx_commerce_belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check perms
		if(!self::checkProductPerms($pUidFrom, ($copy) ? 'show' : 'editcontent')) return false;
		if(!self::checkProductPerms($pUidTo, 'editcontent')) return false;
		
		if(!$copy) {
			//cut the attributes - or, update the mm table with the new uids of the product
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_products_attributes_mm', 'uid_local='.$pUidFrom, array('uid_local' => $pUidTo));
			
			$success = ('' == $GLOBALS['TYPO3_DB']->sql_error());
		} else {
			//copy the attributes - or, get all values from the original product relation and insert them with the new uid_local
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign, tablenames, sorting, uid_correlationtype, uid_valuelist, default_value', 'tx_commerce_products_attributes_mm', 'uid_local='.$pUidFrom);
		
			while($row = $GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$row['uid_local'] = $pUidTo;
				
				//insert
				$rs = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_products_attributes_mm', $row);
			}
			
			$success = ('' == $GLOBALS['TYPO3_DB']->sql_error());
		}
		
		return $success;
	}
	
	/**
	 * This function gives all articles of one product to another
	 * 
	 * @return {boolean} 		Success 
	 * @param {int} $pUidFrom	Product UID from which to take the Articles
	 * @param {int}	$pUidTo		Product UID to which we give the Articles
	 * @param {boolean} $copy	If set, the Articles will only be copied - else cut (aka "swapped" in its true from)
	 */
	function swapProductArticles($pUidFrom, $pUidTo, $copy = false) {
		
		//check params
		if(!is_numeric($pUidFrom) || !is_numeric($pUidTo)) {
			if (TYPO3_DLOG) t3lib_div::devLog('swapProductArticles (tx_commerce_belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check perms
		if(!self::checkProductPerms($pUidFrom, ($copy) ? 'show' : 'editcontent')) return false;
		if(!self::checkProductPerms($pUidTo, 'editcontent')) return false;
		
		if(!$copy) {
			//cut the articles - or, give all articles of the old product the product_uid of the new product	
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_articles', 'uid_product='.$pUidFrom, array('uid_product' => $pUidTo));
		
			$success = ('' == $GLOBALS['TYPO3_DB']->sql_error());
		} else {
			//copy the articles - or, read all article uids of the old product and invoke the copy command
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_articles', 'uid_product='.$pUidFrom);
			
			$success = true;
			
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				//copy
				$success = self::copyArticle($row['uid'], $pUidTo);
			
				if(!$success) return $success;
			}
		} 
		return $success;
	}
	
	/**
	 * Copies the specified Article to the new product
	 * @return {int}			UID of the new article or false on error
	 * @param $uid {int}		uid of existing article which is to be copied
	 * @param $uid_product {int}uid of product that is new parent
	 * @param $locale {array}	array with sys_langauges to copy along, none if null
	 */
	function copyArticle($uid, $uid_product, $locale = null) {
		global $BE_USER;
		
		//check params
		if(!is_numeric($uid) || !is_numeric($uid_product)) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyArticle (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check show right for this article under all categories of the current parent product
		$prod = self::getProductOfArticle($uid);

		if(!self::checkProductPerms($prod['uid'], 'show')) {
			return false;
		}
		
		//check editcontent right for this article under all categories of the new parent product
		if(!self::checkProductPerms($uid_product, 'editcontent')) {
			return false;
		}
		
		//get uid of the last article in the articles table
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_articles', 'deleted = 0', '', 'uid DESC', '0,1');
		
		//if there are no articles at all, abort.
		if(0 >= $GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			return false;	
		}
		
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		
		$uidLast = $row['uid'];	//uid of the last article (after this article we will copy the new article
		
		//init tce
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		
		//start
		$tce->start(array(), array());
		
		//invoke the copy manually so we can actually override the uid_product field
		$overrideArray = array('uid_product' => $uid_product);
		
		//Write to session that we copy 
		//this is used by the hook to the datamap class to figure out if it should call the dynaflex
		//so far this is the best (though not very clean) way to solve the issue we get when saving an article
		$BE_USER->uc['txcommerce_copyProcess'] = 1;
		$BE_USER->writeUC();
		
		$newUid = $tce->copyRecord('tx_commerce_articles', $uid, -$uidLast, 1, $overrideArray);
		
		//We also overwrite because Attributes and Prices will not be saved when we copy
		//this is because commerce hooks into _preProcessFieldArray when it wants to make the prices etc.
		//which is too early when we copy because at this point the uid does not exist
		//self::overwriteArticle($uid, $newUid, $locale); <-- REPLACED WITH OVERWRITE OF WHOLE PRODUCT BECAUSE THAT ACTUALLY WORKS
		
		//copying done, clear session
		$BE_USER->uc['txcommerce_copyProcess'] = 0;
		$BE_USER->writeUC();
		
		if(!is_numeric($newUid)) return false;
		
		// copy prices - not possible through datamap so we do it here
		self::copyPrices($uid, $newUid);
		
		// copy attributes - creating attributes doesn't work with normal copy because only
		// when a product is created in datamap, it creates the attributes for articles with hook
		// But by the time we copy articles, product is already created and we have to copy the attributes manually
		self::overwriteArticleAttributes($uid, $newUid);
		
		
		//copy locales 
		if(is_array($locale) && 0 != count($locale)) {
			foreach($locale as $loc) {
				$success = self::copyLocale('tx_commerce_articles', $uid, $newUid, $loc);
			}
		}
		
		return $newUid;
	}
	
	/**
	 * Copies the Prices of a Article
	 */
	function copyPrices($uidFrom, $uidTo) {
		global $BE_USER;
		
		// select all existing prices of the article
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_article_prices', 'deleted = 0 AND uid_article = '.$uidFrom, '');
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// copy them to the new article
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			
			$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride))	{
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}
			
			//start
			$tce->start(array(), array());
			
			//invoke the copy manually so we can actually override the uid_product field
			$overrideArray = array('uid_article' => $uidTo);
			
			$BE_USER->uc['txcommerce_copyProcess'] = 1;
			$BE_USER->writeUC();
			
			$newUid = $tce->copyRecord('tx_commerce_article_prices', $row['uid'], -$row['uid'], 1, $overrideArray);
			
			//copying done, clear session
			$BE_USER->uc['txcommerce_copyProcess'] = 0;
			$BE_USER->writeUC();
			
		}
		
		if(!is_numeric($newUid)) return false;
		return true;
	}
	
	/**
	 * Copies the article attributes from one article to the next
	 * Used when we copy an article
	 * To copy from locale to locale, just insert the uids of the localized 
	 * records; note that this function deletes the existing attributes
	 * 
	 * @param $uidFrom	int	uid of the article we get the attributes from
	 * @param $uidTo	int	uid of the article we want to copy the attributes to
	 * 
	 * @return boolean	Success
	 */
	function overwriteArticleAttributes($uidFrom, $uidTo, $loc = 0) {
		
		// delete existing attributes
		$table = 'tx_commerce_articles_article_attributes_mm';
		
		if($loc != 0) {
			// we want to overwrite the attributes of the locale
			// replace $uidFrom and $uidTo with their localized versions
			$uids = $uidFrom.','.$uidTo;
			$res  = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, l18n_parent', 'tx_commerce_articles', 'sys_language_uid = '.$loc.' AND l18n_parent IN ('.$uids.')');
			$newFrom = $uidFrom;
			$newTo   = $uidTo;
			
			// get uids
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if($row['l18n_parent'] == $uidFrom) {
					$newFrom = $row['uid'];
				} else if($row['l18n_parent'] == $uidTo) {
					$newTo   = $row['uid'];
				}
			}
			
			// abort if we didn't find the locale for any of the articles
			if($newFrom == $uidFrom || $newTo == $uidTo) {
				return false;
			}
			
			// replace uids
			$uidFrom = $newFrom;
			$uidTo   = $newTo;
		}
		
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$table,
			'uid_local='.$uidTo
		);
		
		// copy the attributes
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid_local=' .$uidFrom .' AND uid_valuelist = 0');
		
		while ($origRelation = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$origRelation['uid_local'] = $uidTo;
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $origRelation);
		}
		
		return true;
	}
	
	/**
	 * Copies the specified Product to the new category
	 * 
	 * @return {int}				UID of the new product or false on error
	 * @param $uid {int}			uid of existing product which is to be copied
	 * @param $uid_product {int}	uid of category that is new parent
	 * @param $ignoreWS {boolean} 	true if versioning should be disabled (be warned: use this only if you are 100% sure you know what you are doing)
	 * @param $locale {array}		array of languages that should be copied, or null if none are specified
	 * @param $sorting {int}		uid of the record behind which we insert this product, or 0 to just append
	 */
	function copyProduct($uid, $uid_category, $ignoreWS = false, $locale = null, $sorting = 0) {
		global $TYPO3_CONF_VARS;
		
		//check params
		if(!is_numeric($uid) || !is_numeric($uid_category) || !is_numeric($sorting)) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyProduct (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check if we may actually copy the product (no permission check, only check if we are not accidentally copying a placeholder or deleted product)
		//also hidden products are not allowed to be copied
		$record = t3lib_BEfunc::getRecordWSOL('tx_commerce_products', $uid, '*', ' AND hidden = 0 AND t3ver_state = 0');
		
		if(!$record) {
			return false;
		}
		
		//check if we are about to copy a version of a live product
		/*if($record['t3ver_oid'] != 0) {
			$uid 	= $record['t3ver_oid'];
			$record = t3lib_BEfunc::getRecordWSOL('tx_commerce_products', $uid, '*', ' AND t3ver_state = 0');
			
			if(!$record) {
				return false;
			}
		}*/
		
		//check if we have the permissions to copy (check category rights) - skip if we are copying locales
		if(!self::checkProductPerms($uid, 'copy')) {
			return false;
		}
		
		//check editcontent right for uid_category
		if(!self::readCategoryAccess($uid_category, self::getCategoryPermsClause(self::getPermMask('editcontent')))) {
			return false;
		}
		
		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyProductClass'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyProductClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		
		if(0 == $sorting) {
			//get uid of the last product in the products table
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_products', 'deleted = 0 AND pid != -1', '', 'uid DESC', '0,1');
			
			//if there are no products at all, abort.
			if(0 >= $GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				return false;	
			}
			
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			$uidLast = -$row['uid'];	//uid of the last product (after this product we will copy the new product)
		} else {
			//sorting position is specified
			$uidLast = (0 > $sorting) ? $sorting : self::getCopyPid('tx_commerce_products', $sorting);	
		}
		
		//init tce
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->bypassWorkspaceRestrictions = $ignoreWS; //set workspace bypass if requested
		
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		
		//start
		$tce->start(array(), array());
		
		//invoke the copy manually so we can actually override the categories field
		$overrideArray = array('categories' => $uid_category);
		
		
		//Hook: beforeCopy
		foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'beforeCopy')) {
					$hookObj->beforeCopy($uid, $uidLast, $overrideArray);
			}
		}
		
		$newUid = $tce->copyRecord('tx_commerce_products', $uid, $uidLast, 1, $overrideArray);
		
		//Hook: afterCopy
		foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'afterCopy')) {
					$hookObj->afterCopy($newUid, $uid, $$overrideArray);
			}
		}
		
		if(!is_numeric($newUid)) {
			return false;
		}
		
		//copy locales 
		if(is_array($locale) && 0 != count($locale)) {
			foreach($locale as $loc) {
				$success = self::copyLocale('tx_commerce_products', $uid, $newUid, $loc, $ignoreWS);
			}
		}
		
		//copy articles
		$success = self::copyArticlesByProduct($newUid, $uid, $locale);
		
		//Overwrite the Product we just created again - to fix that Attributes and Prices are not copied for Articles when they are only copied
		//This should be TEMPORARY - find a clean way to fix that problem
		//self::overwriteProduct($uid, $newUid, $locale); ###fixed###
		
		if(!$success) return false;
		
		return $newUid;
		
	}
	
	/**
	 * Copies any locale of a commerce items
	 * 
	 * @return {boolean}	  Success
	 * @param $table {string} Name of the table in which the locale is
	 * @param $uidCopied {int}uid of the record that is localized
	 * @param $uidNew {int}	  uid of the record that was copied and now needs a locale
	 * @param $loc {int} 	  id of the sys_language
	 */
	function copyLocale($table, $uidCopied, $uidNew, $loc, $ignoreWS = false) {
		global $BE_USER, $TCA;;
		
		//check params
		if(!is_string($table) || !is_numeric($uidCopied) || !is_numeric($uidNew) || !is_numeric($loc)) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyLocale (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		if ($TCA[$table] && $uidCopied)	{
			t3lib_div::loadTCA($table);
		
			//make data
			$rec 				= t3lib_BEfunc::getRecordLocalization($table, $uidCopied, $loc);
			
			//if the item is not localized, return
			if(false == $rec) return true;
			
			//overwrite l18n parent
			$rec[0]['l18n_parent'] = $uidNew;
			unset($rec[0]['uid']); //unset uid for cleanliness
			
			//echo t3lib_div::debug($rec,__LINE__.__FILE__);
			
			//unset all fields that are not supposed to be copied on localized versions
			foreach($TCA[$table]['columns'] as $fN => $fCfg)	{
				if (t3lib_div::inList('exclude,noCopy,mergeIfNotBlank',$fCfg['l10n_mode']) && $fN!=$TCA[$table]['ctrl']['languageField'] && $fN!=$TCA[$table]['ctrl']['transOrigPointerField']) {	 // Otherwise, do not copy field (unless it is the language field or pointer to the original language)
					unset($rec[0][$fN]);
				}
			}
			
			// if we localize an article, add the product uid of the $uidNew localized product
			if('tx_commerce_articles' == $table) {
				$article = t3lib_div::makeInstance('tx_commerce_article');
				$article->init($uidNew);
				$productUid = $article->getParentProductUid();
				
				// load uid of the localized product
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_products', 'l18n_parent = '.$productUid.' AND sys_language_uid = '.$loc);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				
				$rec[0]['uid_product'] = $row['uid'];
			}
			
			$data = array();
			
			$newUid 				= uniqid('NEW');
			$data[$table][$newUid] 	= $rec[0];
			
			//init tce
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$tce->bypassWorkspaceRestrictions = $ignoreWS; //set workspace bypass if requested
			
			$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride))	{
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}
			
			//start
			$tce->start($data, array());
			
			//Write to session that we copy 
			//this is used by the hook to the datamap class to figure out if it should call the dynaflex
			//so far this is the best (though not very clean) way to solve the issue we get when saving an article
			$BE_USER->uc['txcommerce_copyProcess'] = 1;
			$BE_USER->writeUC();
			
			$tce->process_datamap();
			
			//copying done, clear session
			$BE_USER->uc['txcommerce_copyProcess'] = 0;
			$BE_USER->writeUC();
			
			//get real uid
			$newUid = $tce->substNEWwithIDs[$newUid];
			
			// for articles we have to overwrite the attributes
			if('tx_commerce_articles' == $table) {
				self::overwriteArticleAttributes($uidCopied, $uidNew, $loc);
			}
		}
		return true;
	}
	
	/**
	 * Overwrites the localization of a record
	 * if the record does not have the localization, it is copied to the record
	 * 
	 * @return {boolean}			Success
	 * @param $table {string}		Name of the table in which we overwrite records
	 * @param $uidCopied {int}		uid of the record that is the overwriter
	 * @param $uidOverwrite {int}	uid of the record that is to be overwritten
	 * @param $loc {int}			uid of the syslang that is overwritten
	 */
	function overwriteLocale($table, $uidCopied, $uidOverwrite, $loc) {
		global $BE_USER, $TCA;;
		
		//check params
		if(!is_string($table) || !is_numeric($uidCopied) || !is_numeric($uidOverwrite) || !is_numeric($loc)) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyLocale (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check if table is defined in the TCA
		if ($TCA[$table] && $uidCopied)	{
			t3lib_div::loadTCA($table);
		
			//make data
			$recFrom			= t3lib_BEfunc::getRecordLocalization($table, $uidCopied, $loc);
			$recTo				= t3lib_BEfunc::getRecordLocalization($table, $uidOverwrite, $loc);
			
			//if the item is not localized, return
			if(false == $recFrom) return true;
			
			//if the overwritten record does not have the corresponding localization, just copy it
			if(false == $recTo) {
				return self::copyLocale($table, $uidCopied, $uidOverwrite, $loc);
			}
			
			//overwrite l18n parent
			$recFrom[0]['l18n_parent'] = $uidOverwrite;
			unset($recFrom[0]['uid']); //unset uid for cleanliness
			
			//unset all fields that are not supposed to be copied on localized versions
			foreach($TCA[$table]['columns'] as $fN => $fCfg)	{
				if (t3lib_div::inList('exclude,noCopy,mergeIfNotBlank',$fCfg['l10n_mode']) && $fN!=$TCA[$table]['ctrl']['languageField'] && $fN!=$TCA[$table]['ctrl']['transOrigPointerField']) {	 // Otherwise, do not copy field (unless it is the language field or pointer to the original language)
					unset($recFrom[0][$fN]);
				} else if (isset($fCfg['config']['type']) && 'flex' == $fCfg['config']['type'] && isset($recFrom[0][$fN])) {
					if('' != $recFrom[0][$fN]) {
						$recFrom[0][$fN] = t3lib_div::xml2array($recFrom[0][$fN]);
						
						if('' == trim($recFrom[0][$fN])) {
							unset($recFrom[0][$fN]);
						}
					} else {
						unset($recFrom[0][$fN]);
					}
				}
			}
			
			$data = array();
			
			$data[$table][$recTo[0]['uid']] = $recFrom[0];
			
			//init tce
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			//$tce->bypassWorkspaceRestrictions = true;	//overwrites are immediate
			
			$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
			if (is_array($TCAdefaultOverride))	{
				$tce->setDefaultsFromUserTS($TCAdefaultOverride);
			}
			
			//start
			$tce->start($data, array());
			
			//Write to session that we copy 
			//this is used by the hook to the datamap class to figure out if it should call the dynaflex
			//so far this is the best (though not very clean) way to solve the issue we get when saving an article
			$BE_USER->uc['txcommerce_copyProcess'] = 1;
			$BE_USER->writeUC();
			
			$tce->process_datamap();
			
			//copying done, clear session
			$BE_USER->uc['txcommerce_copyProcess'] = 0;
			$BE_USER->writeUC();
			
			// for articles we have to overwrite the attributes
			if('tx_commerce_articles' == $table) {
				self::overwriteArticleAttributes($uidCopied, $uidOverwrite, $loc);
			}
		}
		return true;
	}
	
	/**
	 * Deletes all localizations of a record 
	 * Note that no permission check is made whatsoever! Check perms if you implement this beforehand
	 * 
	 * @return {boolean}		Success
	 * @param $table {string}	Table name
	 * @param $uid	{int}		uid of the record
	 */
	function deleteL18n($table, $uid) {
		//check params
		if(!is_string($table) || !is_numeric($uid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('deleteL18n (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//get all locales
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'l18n_parent = '.$uid);
		
		//delete them
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid = '.$row['uid'], array('deleted' => 1));
		}
		
		return true;		
	}
	
	/**
	 * Copies the specified category into the new category
	 * note: you may NOT copy the same category into itself
	 * 
	 * @return {int}			UID of the new category or false on error
	 * @param $uid {int}		uid of existing category which is to be copied
	 * @param $parent_uid {int} uid of category that is new parent
	 * @param $locales {array}	Array with all uids of languages that should as well be copied - if null, no languages shall be copied
	 * @param $sorting {int}	uid of the record behind which we copy (like - 23), or 0 if none is given at it should just be appended
	 */
	function copyCategory($uid, $parent_uid, $locale = null, $sorting = 0) {
		global $TYPO3_CONF_VARS;
		
		//check params
		if(!is_numeric($uid) || !is_numeric($parent_uid) || $uid == $parent_uid) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyCategory (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check if we have the right to copy this category
		//show right
		if(!self::readCategoryAccess($uid, self::getCategoryPermsClause(self::getPermMask('copy')))) {
			return false;
		}
		
		//check if we have the right to insert into the parent Category
		//new right
		if(!self::readCategoryAccess($parent_uid, self::getCategoryPermsClause(self::getPermMask('new')))) {
			return false;
		}
		
		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyCategoryClass'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['copyCategoryClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		
		
		if(0 == $sorting) {
			//get uid of the last category in the category table
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_categories', 'deleted = 0', '', 'uid DESC', '0,1');
			
			//if there are no categories at all, abort.
			if(0 >= $GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				return false;	
			}
			
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			$uidLast = -$row['uid'];	//uid of the last category (after this product we will copy the new category)
		} else {
			//copy after the given sorting point
			$uidLast = (0 > $sorting) ? $sorting : self::getCopyPid('tx_commerce_categories', $sorting);	
		}
		
		//init tce
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		
		//start
		$tce->start(array(), array());
		
		//invoke the copy manually so we can actually override the categories field
		$overrideArray = array(
			'parent_category' => $parent_uid
		);
		
		//Hook: beforeCopy
		foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'beforeCopy')) {
					$hookObj->beforeCopy($uid, $uidLast, $overrideArray);
			}
		}
		
		$newUid = $tce->copyRecord('tx_commerce_categories', $uid, $uidLast, 1, $overrideArray);
		
		//Hook: afterCopy
		foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'afterCopy')) {
					$hookObj->afterCopy($newUid, $uid, $overrideArray);
			}
		}
		
		if(!is_numeric($newUid)) return false;
		
		//chmod the new category since perms are not copied
		self::chmodCategoryByCategory($newUid, $uid);
		
		//copy locale
		if(is_array($locale) && 0 != count($locale)) {
			foreach($locale as $loc) {
				$success = self::copyLocale('tx_commerce_categories', $uid, $newUid, $loc);
			}
		}
		
		//copy all child products
		$success = self::copyProductsByCategory($newUid, $uid, $locale);
		
		//if(!$success) return false; @comment : could be that one product wasn copied because of permissions restriction!
		
		//copy all child categories
		$success = self::copyCategoriesByCategory($newUid, $uid, $locale);
		
		//if(!$success) return false; @could be that one category wasn't copied because of permissions restriction!
		
		return $newUid;
		
	}
	
	/**
	 * Changes the permissions of a category and applies the permissions of another category
	 * Note that this does ALSO change owner or group
	 * 
	 * @return {boolean}	Success
	 * @param $uidToChmod {int}	uid of the category to chmod
	 * @param $uidFrom {int}	uid of the category from which we take the perms
	 */
	function chmodCategoryByCategory($uidToChmod, $uidFrom) {
		//check params
		if(!is_numeric($uidToChmod) || !is_numeric($uidFrom)) {
			if (TYPO3_DLOG) t3lib_div::devLog('chmodCategoryByCategory (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//select current perms
		$res  = $GLOBALS['TYPO3_DB']->exec_SELECTquery('perms_everybody, perms_group, perms_user, perms_groupid, perms_userid', 'tx_commerce_categories', 'uid = '.$uidFrom.' AND deleted = 0 AND '.self::getCategoryPermsClause(self::getPermMask('show')));
		$res2 = false;
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			//apply the permissions
			$updateFields = array(
				'perms_everybody' => $row['perms_everybody'],
				'perms_group' => $row['perms_group'],
				'perms_user' => $row['perms_user'],
				'perms_userid' => $row['perms_userid'],
				'perms_groupid' => $row['perms_groupid'],
			);
			
			$res2 = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_categories', 'uid='.$uidToChmod, $updateFields);
		}
		
		return (false !== $res2 && ('' == $GLOBALS['TYPO3_DB']->sql_error()));
	}
	
	/**
	 * Returns the pid new pid for the copied item - this is only used when inserting a record on front of another
	 * 
	 * @param $table {string}	Table from which we want to read
	 * @param $uid {int}		uid of the record that we want to move our element to - in front of it
	 */
	function getCopyPid($table, $uid) {
		$res  = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'sorting < (SELECT sorting FROM '.$table.' WHERE uid = '.$uid.') ORDER BY sorting DESC LIMIT 0,1');
	
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		
		$pid = 0;
		
		if(null == $row) {
			//the item we want to skip is the item with the lowest sorting - use pid of the 'Product' Folder
			$pid = self::getProductFolderUid();
		} else {
			$pid = -$row['uid'];
		}
		return $pid;
	}
	
	/**
	 * Copies all Products under a category to a new category
	 * Note that Products that are copied in this indirect way are not versioned
	 * 
	 * @return {boolean}		Success
	 * @param $catUidTo {int}	uid of the category to which the products should be copied
	 * @param $catUidFrom {int}	uid of the category from which the products should come
	 * @param $locale {array}	sys_langauges which are to be copied as well, null if none
	 */
	function copyProductsByCategory($catUidTo, $catUidFrom, $locale = null) {
		
		//check params
		if(!is_numeric($catUidTo) || !is_numeric($catUidFrom)) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyProductsByCategory (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('uid_local', 'tx_commerce_products', 'tx_commerce_products_categories_mm', '',' AND deleted = 0 AND uid_foreign = '.$catUidFrom, 'tx_commerce_products.sorting ASC','', '');
		
		$success = true;
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$succ	 = self::copyProduct($row['uid_local'], $catUidTo, true, $locale);
			$success = ($success) ? $succ : $success;	//keep false if one action was false
		}
		
		return $success;
	}
	
	/**
	 * Copies all Categories under a category to a new category
	 * 
	 * @return {boolean}		Success
	 * @param $catUidTo {int}	uid of the category to which the categories should be copied
	 * @param $catUidFrom {int}	uid of the category from which the categories should come
	 * @param $locale {array}	sys_langauges that should be copied as well
	 */
	function copyCategoriesByCategory($catUidTo, $catUidFrom, $locale = null) {
		
		//check params
		if(!is_numeric($catUidTo) || !is_numeric($catUidFrom) || $catUidTo == $catUidFrom) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyCategoriesByCategory (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('uid_local', 'tx_commerce_categories', 'tx_commerce_categories_parent_category_mm', '',' AND deleted = 0 AND uid_foreign = '.$catUidFrom.' AND '.self::getCategoryPermsClause(1), 'tx_commerce_categories.sorting ASC','', '');
		$success = true;
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$succ 	 = self::copyCategory($row['uid_local'], $catUidTo, $locale);
			$success = ($success) ? $succ : $success;
		}
		
		return $success;
	}
	
	/**
	 * Copies all Articles from one Product to another
	 * 
	 * @return {boolean}		Success
	 * @param $prodUidTo {int}	uid of product from which we copy the articles
	 * @param $prodUidFrom {int}uid of product to which we copy the articles
	 * @param $locale {array}	array with sys_languages to copy along, null if none
	 */
	function copyArticlesByProduct($prodUidTo, $prodUidFrom, $locale = null) {
		
		//check params
		if(!is_numeric($prodUidTo) || !is_numeric($prodUidFrom)) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyArticlesByProduct (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_commerce_articles', 'deleted = 0 AND uid_product = '.$prodUidFrom);
		$success = true;
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$succ 		= self::copyArticle($row['uid'], $prodUidTo, $locale);
			$success 	= ($success) ? $succ : $success;
		}
		
		return $success;
	}
	
    /**
     * This makes the Xml for the categoryt Attributes
     * Call thos function if you have imported data to the database and havn't
     * updated the Flexform
     * @author Ricardo Mieres <ricardo.mieres@502.cl>
     * @param	integer $category_uid
     * @deprecated, see $this->updateXML
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
    
    /**
	 * Returns a WHERE-clause for the tx_commerce_categories-table where user permissions according to input argument, $perms, is validated.
	 * $perms is the "mask" used to select. Fx. if $perms is 1 then you'll get all categories that a user can actually see!
	 * 	 	2^0 = show (1)
	 * 		2^1 = edit (2)
	 * 		2^2 = delete (4)
	 * 		2^3 = new (8)
	 * If the user is 'admin' " 1=1" is returned (no effect)
	 * If the user is not set at all (->user is not an array), then " 1=0" is returned (will cause no selection results at all)
	 * The 95% use of this function is "->getCategoryPermsClause(1)" which will return WHERE clauses for *selecting* categories in backend listings - in other words this will check read permissions.
	 *
	 * @param	integer		Permission mask to use, see function description
	 * @return	string		Part of where clause. Prefix " AND " to this.
	 */
	function getCategoryPermsClause($perms) {
		global $TYPO3_CONF_VARS;
		if (is_array($GLOBALS['BE_USER']->user))	{
			if ($GLOBALS['BE_USER']->isAdmin())	{
				return ' 1=1';
			}
		
			$perms = intval($perms);	// Make sure it's integer.
			$str= ' ('.
				'(perms_everybody & '.$perms.' = '.$perms.')'.	// Everybody
				'OR(perms_userid = '.$GLOBALS['BE_USER']->user['uid'].' AND perms_user & '.$perms.' = '.$perms.')';	// User
			if ($GLOBALS['BE_USER']->groupList)	{
				$str.= 'OR(perms_groupid in ('.$GLOBALS['BE_USER']->groupList.') AND perms_group & '.$perms.' = '.$perms.')';	// Group (if any is set)
			}
			$str.=')';

			return $str;
		} else {
			return ' 1=0';
		}
	}
	
	/**
	 * Returns whether the Permission is set and allowed for the corresponding user
	 * @return {boolean}		User allowed this action or not for the current category
	 * @param $perm {string} 	Word rep. for the wanted right ('show', 'edit', 'editcontent', 'delete', 'new')
	 */
	function isPSet($perm, &$record) {
		if(!is_string($perm) || is_null($record)) return false;
		
		//If User is admin, he may do anything
		if($GLOBALS['BE_USER']->isAdmin()) {
			return true;	
		}
		
		$mask = self::getPermMask($perm);
		
		//if no mask is found, cancel.
		if(0 == $mask) return false;
		
		//if editlock is enabled and we edit, cancel edit.
		if(2 == $mask && $record['editlock']) return false;
		
		//Check the rights of the current record
		//Check if anybody has the right to do the current operation
		if(isset($record['perms_everybody']) && (($record['perms_everybody'] & $mask) == $mask)) {
			return true;	
		}
		
		//Check if user is owner of category and the owner may do the current operation
		if(isset($record['perms_userid']) && isset($record['perms_user']) && ($record['perms_userid'] == $GLOBALS['BE_USER']->user['uid']) && (($record['perms_user'] & $mask) == $mask)) {
				return true;
		}
		
		//Check if the Group has the right to do the current operation
		if(isset($record['perms_groupid']) && isset($record['perms_group'])) {
				$usergroups = explode(',', $GLOBALS['BE_USER']->groupList);
				
				for($i = 0, $l = count($usergroups); $i < $l; $i ++) {
					//User is member of the Group of the category - check the rights
					if($record['perms_groupid'] == $usergroups[$i]) {
						if(($record['perms_group'] & $mask) == $mask) {
							return true;	
						}
					}
				}
		}
		return false;
	}
	
	/**
	 * Returns the int Permission Mask for the String-Representation of the Permission. Returns 0 if not found.
	 * @return {int}
	 * @param $perm {string}	String-Representation of the Permission
	 */
	function getPermMask($perm) {
		if(!is_string($perm)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getPermMask (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return 0;	
		}
		
		$mask = 0;
		
		switch ($perm) {
			case 'show':
			case 'copy':
				$mask = 1; break;
			case 'edit':
			case 'move':
				$mask = 2; break;
			case 'delete':
				$mask = 4; break;
			case 'new': 
				$mask = 8; break;
			case 'editcontent':
				$mask = 16; break;
		}
		
		return $mask;
	}
	
	/**
	 * Checks the permissions for a product (by checking for the permission in all parent categories)
	 * 
	 * @return {boolean}	Right exists or not 
	 * @param $uid {int}	Product UId
	 * @param $perm {string}String-Rep of the Permission
	 */
	function checkProductPerms($uid, $perm) {
		
		//check params
		if(!is_numeric($uid) || !is_string($perm)) {
			if (TYPO3_DLOG) t3lib_div::devLog('checkProductPerms (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;
		}
		
		//get mask
		$mask = self::getPermMask($perm);
		
		if(0 == $mask) {
			if (TYPO3_DLOG) t3lib_div::devLog('checkProductPerms (belib) gets passed an invalid permission to check for.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//get parent categories
		$parents = self::getProductParentCategories($uid);
		
		//check the permissions
		if(0 < count($parents)) {
			$l = count($parents);
			
			for($i = 0; $i < $l; $i ++) {
				if(!self::readCategoryAccess($parents[$i], self::getCategoryPermsClause($mask))) {
					return false;	
				}
			}	
		} else return false;
		
		return true;
	}
	
	/**
	 * Returns a category record (of category with $id) with an extra field "_thePath" set to the record path IF the WHERE clause, $perms_clause, selects the record. Thus is works as an access check that returns a category record if access was granted, otherwise not.
	 * If $id is zero a pseudo root-page with "_thePath" set is returned IF the current BE_USER is admin.
	 * In any case ->isInWebMount must return true for the user (regardless of $perms_clause)
	 * Usage: 21
	 *
	 * @param	integer		Category uid for which to check read-access
	 * @param	string		$perms_clause is typically a value generated with SELF->getCategoryPermsClause(1);
	 * @return	array		Returns category record if OK, otherwise false.
	 */
	function readCategoryAccess($id, $perms_clause) {
		if ((string)$id!='') {
			$id = intval($id);
			if (!$id) {
				if ($GLOBALS['BE_USER']->isAdmin()) {
					$path = '/';
					$pageinfo['_thePath'] = $path;
					return $pageinfo;
				}
			} else {
				
				$pageinfo = t3lib_BEfunc::getRecord('tx_commerce_categories', $id, '*', ($perms_clause ? ' AND '.$perms_clause : ''));
				if ($pageinfo['uid'] /*&& $GLOBALS['BE_USER']->isInWebMount($id, $perms_clause)*/) {
					//t3lib_BEfunc::workspaceOL('pages', $pageinfo);
					if (is_array($pageinfo)) {
						//t3lib_BEfunc::fixVersioningPid('pages', $pageinfo);
						//list($pageinfo['_thePath'], $pageinfo['_thePathFull']) = t3lib_BEfunc::getRecordPath(intval($pageinfo['uid']), $perms_clause, 15, 1000);
						return $pageinfo;
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * Checks whether the parent category of any content is given the right 'editcontent' for the specific user and returns true or false depending on the perms
	 * @param {array} $categoryUids		uids of the categories
	 * @param {array} $perms			string for permissions to check
	 * @return {boolean}
	 */
	function checkPermissionsOnCategoryContent($categoryUids, $perms) {
		
		//admin is allowed to do anything
		if($GLOBALS['BE_USER']->isAdmin()) {
			return true;	
		}
	
		$keys = array_keys($categoryUids);
		$l 	  = count($keys);
		
		$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
		$mounts->init($GLOBALS['BE_USER']->user['uid']);
		
		for($i = 0; $i < $l; $i ++) {
			
			$category = t3lib_div::makeInstance('tx_commerce_category');
			$category->init($categoryUids[$keys[$i]]);
			/**
			 * @TODO Check why not wiorking at kneipp
			 * @NOTE: Whoever wrote this TODO, next time, specify what EXACTLY is not working
			 * Can't reproduce. 20.11.2009, Erik Frister
			 */
			//check if the category is in the commerce mounts
			if(!$mounts->isInCommerceMounts($category->getUid())) return false;
			
			//check perms
			for($j = 0, $m = count($perms); $j < $m; $j ++) {
				if(!$category->isPSet($perms[$j])) {
					
					//return false if perms are not granted
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * Returns if typo3 is running under a AJAX request
	 * @return {boolean}
	 */
	function isAjaxRequest() {
		return (bool)(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX);
	}
	
	/**
	 * Returns the UID of the Product Folder
	 * @return {int}	UID
	 */
	function getProductFolderUid() {
		list($modPid,$defaultFolder,$folderList)  = tx_commerce_folder_db::initFolders('Commerce', 'commerce');
		list($prodPid,$defaultFolder,$folderList) = tx_commerce_folder_db::initFolders('Products', 'commerce',$modPid);
		
		return $prodPid;
	}
	
	/**
	 * Overwrites a product record
	 * @return 
	 * @param $uidFrom {int} UID of the product we want to copy
	 * @param $uidTo {int} UID of the product we want to overwrite
	 */
	function overwriteProduct($uidFrom, $uidTo, $locale = null) {
		global $TYPO3_CONF_VARS, $TCA;
		
		$table = 'tx_commerce_products';
		
		//check params
		if(!is_numeric($uidFrom) || !is_numeric($uidTo) || $uidFrom == $uidTo) {
			if (TYPO3_DLOG) t3lib_div::devLog('overwriteProduct (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check if we may actually copy the product (no permission check, only check if we are not accidentally copying a placeholder or shadow or deleted product)
		$recordFrom = t3lib_BEfunc::getRecordWSOL($table, $uidFrom, '*', ' AND pid != -1 AND t3ver_state != 1 AND deleted = 0');
		
		if(!$recordFrom) {
			return false;
		}
		
		//check if we may actually overwrite the product (no permission check, only check if we are not accidentaly overwriting a placeholder or shadow or deleted product)
		$recordTo = t3lib_BEfunc::getRecordWSOL($table, $uidTo, '*', ' AND pid != -1 AND t3ver_state != 1 AND deleted = 0');
		
		if(!$recordTo) {
			return false;
		}
		
		//check if we have the permissions to copy and overwrite (check category rights)
		if(!self::checkProductPerms($uidFrom, 'copy') || !self::checkProductPerms($uidTo, 'editcontent')) {
			return false;
		}
		
		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['overwriteProductClass'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF']['commerce/lib/class.tx_commerce_belib.php']['overwriteProductClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		
		$data = self::getOverwriteData($table, $uidFrom, $uidTo);
		
		//do not overwrite uid, parent_categories, and create_date
		unset($data[$table][$uidTo]['uid'], 
			  $data[$table][$uidTo]['categories'], 
			  $data[$table][$uidTo]['crdate'], 
			  $data[$table][$uidTo]['cruser_id']
		); 
		
		$datamap = $data;
		
		//execute
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		//$tce->bypassWorkspaceRestrictions = true;	//overwrites are immediate
		
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		
		//Hook: beforeOverwrite
		foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'beforeOverwrite')) {
					$hookObj->beforeOverwrite($uidFrom, $uidTo, $datamap);
			}
		}
		
		$tce->start($datamap, array());
		$tce->process_datamap();
		
		//Hook: afterOverwrite
		foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'afterOverwrite')) {
					$hookObj->beforeCopy($uidFrom, $uidTo, $datamap, $tce);
			}
		}
		
		//overwrite locales
		if(is_array($locale) && 0 != count($locale)) {
			foreach($locale as $loc) {
				$success = self::overwriteLocale($table, $uidFrom, $uidTo, $loc); 
			}
		}
		
		//overwrite articles which are existing - do NOT delete articles that are not in the overwritten product but in the overwriting one
		$articlesFrom= self::getArticlesOfProduct($uidFrom);
		
		if(false !== $articlesFrom && is_array($articlesFrom)) {
			//the product has articles - check if they exist in the overwritten product	
			$articlesTo = self::getArticlesOfProduct($uidTo);
			
			//simply copy if the overwritten product does not have articles
			if(false === $articlesTo || !is_array($articlesTo)) {
				self::copyArticlesByProduct($uidTo, $uidFrom, $locale);
			} else {
				//go through each article of the overwriting product and check if it exists in the overwritten product
				$l 			= count($articlesFrom);
				$m			= count($articlesTo);
				
				//walk the articles
				for($i = 0; $i < $l; $i ++) {
					$overwrite 	= false;
					$uid 		= $articlesFrom[$i]['uid'];
					
					//check if we need to overwrite
					for($j = 0; $j < $m; $j ++) {
						if($articlesFrom[$i]['ordernumber'] == $articlesTo[$j]['ordernumber']) {
							$overwrite = true;
							break;
						}
					}
					
					if(!$overwrite) {
						//copy if we do not need to overwrite
						self::copyArticle($uid, $uidTo, $locale);
					} else {
						//overwrite if the article already exists
						self::overwriteArticle($uid, $articlesTo[$j]['uid'], $locale);
					}
				}
			}
		}
	}
	
	/**
	 * Retrieves the data object to make an overwrite
	 * 
	 * @param $table string	Tablename
	 * @param $uid int	uid of the record we which to retrieve the data from
	 * @param $destPid int uid of the record we want to overwrite
	 */
	function getOverwriteData($table, $uidFrom, $destPid) {
		global $TCA;
		
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		
		$tce->start(array(), array());
		
		$first = 0;
		$language = 0;
		$uid = $origUid = intval($uidFrom);
			
		// Only copy if the table is defined in TCA, a uid is given
		if ($TCA[$table] && $uid)	{
			t3lib_div::loadTCA($table);

			if (true)	{		// This checks if the record can be selected which is all that a copy action requires.
				$data = Array();

				$nonFields = array_unique(t3lib_div::trimExplode(',','uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,t3ver_oid,t3ver_wsid,t3ver_id,t3ver_label,t3ver_state,t3ver_swapmode,t3ver_count,t3ver_stage,t3ver_tstamp,'.$excludeFields,1));

				// $row = $this->recordInfo($table,$uid,'*');
				$row = t3lib_BEfunc::getRecordWSOL($table,$uid);	// So it copies (and localized) content from workspace...
				if (is_array($row))	{

						// Initializing:
					$theNewID = $destPid;
					$enableField = isset($TCA[$table]['ctrl']['enablecolumns']) ? $TCA[$table]['ctrl']['enablecolumns']['disabled'] : '';
					$headerField = $TCA[$table]['ctrl']['label'];

						// Getting default data:
					$defaultData = $tce->newFieldArray($table);

						// Getting "copy-after" fields if applicable:
					$copyAfterFields = array();

						// Page TSconfig related:
					$tscPID = t3lib_BEfunc::getTSconfig_pidValue($table, $uid, -$destPid);	// NOT using t3lib_BEfunc::getTSCpid() because we need the real pid - not the ID of a page, if the input is a page...
					$TSConfig = $tce->getTCEMAIN_TSconfig($tscPID);
					$tE = $tce->getTableEntries($table,$TSConfig);

						// Traverse ALL fields of the selected record:
					foreach($row as $field => $value)	{
						if (!in_array($field,$nonFields))	{

								// Get TCA configuration for the field:
							$conf = $TCA[$table]['columns'][$field]['config'];

								// Preparation/Processing of the value:
							if ($field=='pid')	{	// "pid" is hardcoded of course:
								$value = $destPid;
							} elseif (isset($overrideValues[$field]))	{	// Override value...
								$value = $overrideValues[$field];
							} elseif (isset($copyAfterFields[$field]))	{	// Copy-after value if available:
								$value = $copyAfterFields[$field];
							} elseif ($TCA[$table]['ctrl']['setToDefaultOnCopy'] && t3lib_div::inList($TCA[$table]['ctrl']['setToDefaultOnCopy'],$field))	{	// Revert to default for some fields:
								$value = $defaultData[$field];
							} else {
									// Hide at copy may override:
								if ($first && $field==$enableField && $TCA[$table]['ctrl']['hideAtCopy'] && !$tce->neverHideAtCopy && !$tE['disableHideAtCopy'])	{
									$value=1;
								}
									// Prepend label on copy:
								if ($first && $field==$headerField && $TCA[$table]['ctrl']['prependAtCopy'] && !$tE['disablePrependAtCopy'])	{
									$value = $tce->getCopyHeader($table,$this->resolvePid($table,$destPid),$field,$this->clearPrefixFromValue($table,$value),0);
								}
									// Processing based on the TCA config field type (files, references, flexforms...)
								$value = $tce->copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $tscPID, $language);
							}

								// Add value to array.
							$data[$table][$theNewID][$field] = $value;
						}
					}

						// Overriding values:
					if ($TCA[$table]['ctrl']['editlock'])	{
						$data[$table][$theNewID][$TCA[$table]['ctrl']['editlock']] = 0;
					}

						// Setting original UID:
					if ($TCA[$table]['ctrl']['origUid'])	{
						$data[$table][$theNewID][$TCA[$table]['ctrl']['origUid']] = $uid;
					}

						
						// Getting the new UID:
					//$theNewSQLID = $copyTCE->substNEWwithIDs[$theNewID];
					//if ($theNewSQLID)	{
					//	$this->copyRecord_fixRTEmagicImages($table,t3lib_BEfunc::wsMapId($table,$theNewSQLID));
					//}

					return $data;
				}
			}
		}
		
		return array();
	}
	
	/**
	 * Overwrites the article
	 * 
	 * @return {boolean}	Success
	 * @param $uidFrom {int}	uid of article that provides the new data
	 * @param $uidTo {int}		uid of article that is to be overwritten
	 * @param $locale {array}	uids of sys_languages to overwrite
	 */
	function overwriteArticle($uidFrom, $uidTo, $locale = null) {
		global $BE_USER, $TCA;
		$table = 'tx_commerce_articles';
		
		//check params
		if(!is_numeric($uidFrom) || !is_numeric($uidTo)) {
			if (TYPO3_DLOG) t3lib_div::devLog('copyArticle (belib) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return false;	
		}
		
		//check show right for overwriting article
		$prodFrom = self::getProductOfArticle($uidFrom);

		if(!self::checkProductPerms($prodFrom['uid'], 'show')) {
			return false;
		}
		
		//check editcontent right for overwritten article
		$prodTo = self::getProductOfArticle($uidTo);

		if(!self::checkProductPerms($prodTo['uid'], 'editcontent')) {
			return false;
		}
		
		//get the records
		$recordFrom = t3lib_BEfunc::getRecordWSOL($table, $uidFrom, '*');
		$recordTo 	= t3lib_BEfunc::getRecordWSOL($table, $uidTo, '*');
		
		if(!$recordFrom || !$recordTo) return false;
		
		$data = self::getOverwriteData($table, $uidFrom, $uidTo);
		
		#echo t3lib_div::debug($data,__LINE__.__FILE__);
		
		unset($data[$table][$uidTo]['uid'],
			  $data[$table][$uidTo]['cruser_id'],
			  $data[$table][$uidTo]['crdate'],
			  $data[$table][$uidTo]['uid_product']
		);
		
		//correct flex values
		/*if ($TCA[$table])	{
			t3lib_div::loadTCA($table);
			
			foreach($TCA[$table]['columns'] as $fN => $fCfg)	{
				if (isset($fCfg['config']['type']) && 'flex' == $fCfg['config']['type'] && isset($recordFrom[$fN])) {
					if('' != trim($recordFrom[$fN])) {
						$recordFrom[$fN] = t3lib_div::xml2array($recordFrom[$fN]);
						
						//unset if the flex is not complete
						if('' == trim($recordFrom[$fN])) {
							unset($recordFrom[$fN]);
						}
					} else {
						unset($recordFrom[$fN]);
					}
				}
			}		
		}*/
		
		$datamap = $data;
		//$datamap = array();
		//$datamap[$table][$uidTo] = $recordFrom;
		
		//execute
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		$tce->start($datamap, array());
		
		//Write to session that we copy 
		//this is used by the hook to the datamap class to figure out if it should call the dynaflex
		//so far this is the best (though not very clean) way to solve the issue we get when saving an article
		$BE_USER->uc['txcommerce_copyProcess'] = 1;
		$BE_USER->writeUC();
		
		$tce->process_datamap();
		
		//copying done, clear session
		$BE_USER->uc['txcommerce_copyProcess'] = 0;
		$BE_USER->writeUC();
		
		//overwrite locales
		if(is_array($locale) && 0 != count($locale)) {
			foreach($locale as $loc) {
				$success = self::overwriteLocale($table, $uidFrom, $uidTo, $loc); 
			}
		}
		return true;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_belib.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_belib.php']);
}
?>

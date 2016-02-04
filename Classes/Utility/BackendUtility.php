<?php
namespace CommerceTeam\Commerce\Utility;

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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This metaclass provides several helper methods for handling relations in
 * the backend. This is quite useful because attributes for an article can
 * come from products and categories. And products can be assigned to several
 * categories and a category can have a lot of parent categories.
 *
 * Class \CommerceTeam\Commerce\Utility\BackendUtility
 *
 * @author Thomas Hempel <thomas@work.de>
 */
class BackendUtility
{
    /**
     * This gets all categories for a product from the database
     * (even those that are not direct).
     *
     * @param int $pUid Uid of the product
     *
     * @return array An array of UIDs of all categories for this product
     */
    public function getCategoriesForProductFromDb($pUid)
    {
        $database = self::getDatabaseConnection();

        // get categories that are directly stored in the product dataset
        $pCategories = $database->exec_SELECTquery(
            'uid_foreign',
            'tx_commerce_products_categories_mm',
            'uid_local = ' . (int) $pUid
        );

        $result = array();
        while (($categoryReference = $database->sql_fetch_assoc($pCategories))) {
            $this->getParentCategories($categoryReference['uid_foreign'], $result);
        }

        return $result;
    }

    /**
     * Gets all direct parent categories of a product.
     *
     * @param int $uid Uid of the product
     *
     * @return array
     */
    public function getProductParentCategories($uid)
    {
        $database = self::getDatabaseConnection();

        $pCategories = $database->exec_SELECTquery(
            'uid_foreign',
            'tx_commerce_products_categories_mm',
            'uid_local = ' . (int) $uid
        );

        $result = array();
        while (($categoryReference = $database->sql_fetch_assoc($pCategories))) {
            $result[] = $categoryReference['uid_foreign'];
        }

        return $result;
    }

    /**
     * Fetches all attribute relation from the database that are assigned to a
     * product specified through pUid. It can also fetch information about the
     * attribute and a list of attribute values if the attribute has a valuelist.
     *
     * @param int $pUid Uid of the product
     * @param bool $separateCorrelationType1 If this is true, all attributes with ct1
     *      will be saved in a separated result section
     * @param bool $addAttributeData If true, all information about the
     *      attributes will be fetched from the database (default is false)
     * @param bool $getValueListData If this is true and additional data is
     *      fetched and an attribute has a valuelist, this gets the values for the
     *      list (default is false)
     *
     * @return array of attributes
     */
    public function getAttributesForProduct(
        $pUid,
        $separateCorrelationType1 = false,
        $addAttributeData = false,
        $getValueListData = false
    ) {
        if (!$pUid) {
            return array();
        }

        $database = self::getDatabaseConnection();

        // get all attributes for the product
        $res = $database->exec_SELECTquery(
            'distinct *',
            'tx_commerce_products_attributes_mm',
            'uid_local = ' . (int) $pUid,
            '',
            'sorting, uid_foreign DESC, uid_correlationtype ASC'
        );

        $result = array();
        while (($relData = $database->sql_fetch_assoc($res))) {
            if ($addAttributeData) {
                // fetch the data from the attribute table
                $aRes = $database->exec_SELECTquery(
                    '*',
                    'tx_commerce_attributes',
                    'uid = ' . (int) $relData['uid_foreign'] . $this->enableFields('tx_commerce_attributes'),
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
                        'attributes_uid = ' . (int) $aData['uid'] . $this->enableFields('tx_commerce_attribute_values'),
                        '',
                        'uid'
                    );
                    $vlData = array();
                    while (($vlEntry = $database->sql_fetch_assoc($vlRes))) {
                        $vlData[$vlEntry['uid']] = $vlEntry;
                    }

                    $relData['valueList'] = $vlData;
                }

                $relData['has_valuelist'] = $aData['has_valuelist'] ? '1' : '0';
            }

            if (empty($relData)) {
                continue;
            }

            if ($separateCorrelationType1) {
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

    /* CATEGORIES */

    /**
     *  Fetch all parent categories for a given category.
     *
     * @param int $cUid UID of the category that is the startingpoint
     * @param array $cUidList A list of category UIDs. PASSED BY REFERENCE
     * @param int $dontAdd A single UID, if this is found in the parent
     *      results, it's not added to the list
     * @param int $excludeUid If the current cUid is like this UID the
     *      cUid is not processed at all
     * @param bool $recursive If true, this method calls itself for each
     *      category if finds
     *
     * @return void
     */
    public function getParentCategories($cUid, array &$cUidList, $dontAdd = 0, $excludeUid = 0, $recursive = true)
    {
        if (strlen((string) $cUid) > 0) {
            $database = self::getDatabaseConnection();

            // add the submitted uid to the list if it is bigger
            // than 0 and not already in the list
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

                while (($relData = $database->sql_fetch_assoc($res))) {
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
     * @param int $cUid UID of the category that is the startingpoint
     * @param array $categoryUidList A list of category uids. PASSED BY
     *      REFERENCE because this way the recursion is easier
     * @param int $dontAdd A single UID, if this is found in the parent
     *      results, it's not added to the list
     * @param int $excludeUid If the current cUid is like this UID the
     *      cUid is not processed at all
     * @param bool $recursive If true, this method calls itself for each
     *      category if finds
     *
     * @return void
     */
    public function getChildCategories($cUid, array &$categoryUidList, $dontAdd = 0, $excludeUid = 0, $recursive = true)
    {
        // @todo make $categoryUidList not a reference
        // add the submitted uid to the list if it is bigger
        // than 0 and not already in the list
        if ((int) $cUid && $cUid != $excludeUid) {
            $database = self::getDatabaseConnection();

            if (!in_array($cUid, $categoryUidList) && $cUid != $dontAdd) {
                $categoryUidList[] = $cUid;
            }

            $res = $database->exec_SELECTquery(
                'uid_local',
                'tx_commerce_categories_parent_category_mm',
                'uid_foreign = ' . (int) $cUid,
                '',
                'uid_local'
            );

            if ($res) {
                while (($relData = $database->sql_fetch_assoc($res))) {
                    if ($recursive) {
                        $this->getChildCategories($relData['uid_local'], $categoryUidList, $cUid, $excludeUid);
                    } else {
                        $cUid = $relData['uid_local'];
                        if (!in_array($cUid, $categoryUidList) && $cUid != $dontAdd) {
                            $categoryUidList[] = $cUid;
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
     * @param array $cUidList List of category UIDs PASSED BY REFERENCE
     *
     * @return void
     */
    public function getParentCategoriesFromList(array &$cUidList)
    {
        if (is_array($cUidList)) {
            foreach ($cUidList as $cUid) {
                $this->getParentCategories($cUid, $cUidList);
            }
        }
    }

    /**
     * Returns an array with the data for a single category.
     *
     * @param int $cUid UID of the category
     * @param string $select WHERE part of the query
     * @param string $groupBy GROUP BY part of the query
     * @param string $orderBy ORDER BY part of the query
     *
     * @return array An associative array with the data of the category
     */
    public function getCategoryData($cUid, $select = '*', $groupBy = '', $orderBy = '')
    {
        $database = self::getDatabaseConnection();

        $result = $database->exec_SELECTquery(
            $select,
            'tx_commerce_categories',
            'uid = ' . (int) $cUid,
            $groupBy,
            $orderBy
        );

        return $database->sql_fetch_assoc($result);
    }

    /**
     * Returns all attributes for a list of categories.
     *
     * @param array $catList List of category UIDs
     * @param int $ct Correlationtype (can be null)
     * @param string $uidField Name of the field in the excludeAttributes
     *      array that holds the uid of the attributes
     * @param array $excludeAttributes Attributes (the method
     *      expects a field called uid_foreign)
     *
     * @return array of attributes
     */
    public function getAttributesForCategoryList(
        array $catList,
        $ct = null,
        $uidField = 'uid',
        array $excludeAttributes = array()
    ) {
        $result = array();
        if (!is_array($catList)) {
            return $result;
        }

        foreach ($catList as $catUid) {
            $attributes = $this->getAttributesForCategory($catUid, $ct, $excludeAttributes);
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
     * @param int $categoryUid Uid of the category
     * @param int $correlationtype Correlationtype (can be null)
     * @param array $excludeAttributes Attributes
     *      (the method expects a field called uid_foreign)
     *
     * @return array of attributes
     */
    public function getAttributesForCategory($categoryUid, $correlationtype = null, array $excludeAttributes = null)
    {
        $database = self::getDatabaseConnection();

        // build the basic query
        $where = 'uid_local = ' . $categoryUid;

        // select only for a special correlation type?
        if ($correlationtype != null) {
            $where .= ' AND uid_correlationtype = ' . (int) $correlationtype;
        }

        // should we exclude some attributes
        if (is_array($excludeAttributes) && !empty($excludeAttributes)) {
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
            '',
            'sorting'
        );

        // build the result and return it...
        $result = array();
        while (($attribute = $database->sql_fetch_assoc($res))) {
            $result[] = $attribute;
        }

        return $result;
    }

    /* ATTRIBUTES */

    /**
     * Returns the title of an attribute.
     *
     * @param int $aUid UID of the attribute
     *
     * @return string The title of the attribute as string
     */
    public function getAttributeTitle($aUid)
    {
        $attribute = $this->getAttributeData($aUid, 'title');

        return $attribute['title'];
    }

    /**
     * Returns a list of Titles for a list of attributes.
     *
     * @param array $attributeList Attributes (complete datasets
     *      with at least one field that contains the UID of an attribute)
     * @param string $uidField Name of the array key in the attributeList
     *      that contains the UID
     *
     * @return array of attribute titles as strings
     */
    public function getAttributeTitles(array $attributeList, $uidField = 'uid')
    {
        $result = array();
        if (is_array($attributeList) && !empty($attributeList)) {
            foreach ($attributeList as $attribute) {
                $result[] = $this->getAttributeTitle($attribute[$uidField]);
            }
        }

        return $result;
    }

    /**
     * Returns the complete dataset of an attribute. You can select which
     * fields should be fetched from the database.
     *
     * @param int $aUid UID of the attribute
     * @param string $select Select here, which fields should be fetched
     *      (default is *)
     *
     * @return array associative array with the attributeData
     */
    public function getAttributeData($aUid, $select = '*')
    {
        return self::getDatabaseConnection()->exec_SELECTgetSingleRow(
            $select,
            'tx_commerce_attributes',
            'uid = ' . (int) $aUid
        );
    }

    /**
     * This fetches the value for an attribute. It fetches the "default_value"
     * from the table if the attribute has no valuelist, otherwise it fetches
     * the title from the attribute_values table.
     * You can submit only an attribute uid, then the mehod fetches the data
     * from the databse itself, or you submit the data from the relation table
     * and the data from the attribute table if this data is already present.
     *
     * @param int $pUid Product UID
     * @param int $aUid Attribute UID
     * @param string $relationTable Table where the relations between
     *      prodcts and attributes are stored
     * @param array $relationData  Relation dataset between the product
     *      and the attribute (default NULL)
     * @param array $attributeData Meta data (has_valuelist, unit) for
     *      the attribute you want to get the value from (default NULL)
     *
     * @return string The value of the attribute. It's maybe appended with the
     *      unit of the attribute
     */
    public function getAttributeValue(
        $pUid,
        $aUid,
        $relationTable,
        array $relationData = null,
        array $attributeData = null
    ) {
        $database = self::getDatabaseConnection();

        if ($relationData == null || $attributeData == null) {
            // data from database if one of the arguments is NULL. This nesccessary
            // to keep the data consistant
            $relRes = $database->exec_SELECTquery(
                'uid_valuelist, default_value, value_char',
                $relationTable,
                'uid_local = ' . (int) $pUid . ' AND uid_foreign = ' . (int) $aUid
            );
            $relationData = $database->sql_fetch_assoc($relRes);

            $attributeData = $this->getAttributeData($aUid, 'has_valuelist, unit');
        }

        if ($attributeData['has_valuelist'] == '1') {
            if ($attributeData['multiple'] == 1) {
                $result = array();
                if (is_array($relationData)) {
                    foreach ($relationData as $relation) {
                        $valueRes = $database->exec_SELECTquery(
                            'value',
                            'tx_commerce_attribute_values',
                            'uid = ' . (int) $relation['uid_valuelist'] .
                            $this->enableFields('tx_commerce_attribute_values')
                        );
                        $value = $database->sql_fetch_assoc($valueRes);
                        $result[] = $value['value'];
                    }
                }

                return '<ul><li>' . implode(
                    '</li><li>',
                    \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($result)
                ) . '</li></ul>';
            } else {
                // fetch data from attribute values table
                $valueRes = $database->exec_SELECTquery(
                    'value',
                    'tx_commerce_attribute_values',
                    'uid = ' . (int) $relationData['uid_valuelist'] .
                    $this->enableFields('tx_commerce_attribute_values')
                );
                $value = $database->sql_fetch_assoc($valueRes);

                return $value['value'];
            }
        } elseif (!empty($relationData['value_char'])) {
            // the value is in field default_value
            return $relationData['value_char'] . ' ' . $attributeData['unit'];
        }

        return $relationData['default_value'] . ' ' . $attributeData['unit'];
    }

    /**
     * Returns the correlationtype of a special attribute inside a product.
     *
     * @param int $aUid UID of the attribute
     * @param int $pUid UID of the product
     *
     * @return int The correlationtype
     */
    public function getCtForAttributeOfProduct($aUid, $pUid)
    {
        $uidCorrelationType = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid_correlationtype',
            'tx_commerce_products_attributes_mm',
            'uid_local = ' . (int) $pUid . ' AND uid_foreign = ' . (int) $aUid
        );

        return $uidCorrelationType['uid_correlationtype'];
    }

    /* ARTICLES */

    /**
     * Return all articles that where created from a given product.
     *
     * @param int $pUid UID of the product
     * @param string $additionalWhere Additional where string
     * @param string $orderBy Order by field
     *
     * @return array of article datasets as assoc array or false if nothing was found
     */
    public function getArticlesOfProduct($pUid, $additionalWhere = '', $orderBy = '')
    {
        $result = (array) self::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'tx_commerce_articles',
            'uid_product = ' . (int) $pUid . ' AND deleted = 0' . ($additionalWhere ? ' AND ' : '') . $additionalWhere,
            '',
            $orderBy
        );

        return $result;
    }

    /**
     * Return all articles that where created from a given product.
     *
     * @param int $pUid UID of the product
     * @param string $additionalWhere Additional where string
     * @param string $orderBy Order by field
     *
     * @return array of article UIDs, ready to implode for coma separed list
     */
    public static function getArticlesOfProductAsUidList($pUid, $additionalWhere = '', $orderBy = '')
    {
        $database = self::getDatabaseConnection();

        $where = 'uid_product = ' . (int) $pUid . ' AND deleted = 0';

        if ($additionalWhere != '') {
            $where .= ' AND ' . $additionalWhere;
        }

        $res = $database->exec_SELECTquery('uid', 'tx_commerce_articles', $where, '', $orderBy);
        if ($database->sql_num_rows($res)) {
            $result = array();
            while (($article = $database->sql_fetch_assoc($res))) {
                $result[] = $article['uid'];
            }

            return $result;
        }

        return false;
    }

    /**
     * Returns the product from which an article was created.
     *
     * @param int $aUid Article UID
     * @param string $getProductData Which fields should be returned of the
     *      product. This is a comma separated list (default *)
     *
     * @return int the "getProductData" param is empty, this returns the
     *      UID as int, otherwise it returns an associative array of the dataset
     */
    public function getProductOfArticle($aUid, $getProductData = '*')
    {
        $database = self::getDatabaseConnection();

        $where = 'uid = ' . (int) $aUid . ' AND deleted=0';

        $proRes = $database->exec_SELECTquery('uid_product', 'tx_commerce_articles', $where);
        $proRes = $database->sql_fetch_assoc($proRes);

        if (strlen($getProductData) > 0) {
            $where = 'uid = ' . (int) $proRes['uid_product'];
            $proRes = $database->exec_SELECTquery($getProductData, 'tx_commerce_products', $where);

            $proRes = $database->sql_fetch_assoc($proRes);

            return $proRes;
        }

        return $proRes['uid_product'];
    }

    /**
     * Returns all attributes for an article.
     *
     * @param int $aUid Article UID
     * @param int $ct Correlationtype
     * @param array $excludeAttributes Relation datasets where the
     *      field "uid_foreign" is the UID of the attribute you don't want to get
     *
     * @return array of attributes
     */
    public function getAttributesForArticle($aUid, $ct = null, array $excludeAttributes = array())
    {
        // build the basic query
        $where = 'uid_local = ' . $aUid;

        if ($ct != null) {
            $pUid = $this->getProductOfArticle($aUid, 'uid');

            $productAttributes = $this->getAttributesForProduct($pUid['uid']);
            $ctAttributes = array();
            if (is_array($productAttributes)) {
                foreach ($productAttributes as $productAttribute) {
                    if ($productAttribute['uid_correlationtype'] == $ct) {
                        $ctAttributes[] = $productAttribute['uid_foreign'];
                    }
                }
                if (!empty($ctAttributes)) {
                    $where .= ' AND uid_foreign IN (' . implode(',', $ctAttributes) . ')';
                }
            }
        }

        // should we exclude some attributes
        if (is_array($excludeAttributes) && !empty($excludeAttributes)) {
            $eAttributes = array();
            foreach ($excludeAttributes as $eAttribute) {
                $eAttributes[] = (int) $eAttribute['uid_foreign'];
            }
            $where .= ' AND uid_foreign NOT IN (' . implode(',', $eAttributes) . ')';
        }

        $database = $this->getDatabaseConnection();

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
     * @param int $aUid Uid of the article
     * @param array $fullAttributeList List of uids for the attributes
     *      that are assigned to the article
     *
     * @return string
     */
    public function getArticleHash($aUid, array $fullAttributeList)
    {
        $database = self::getDatabaseConnection();

        $hashData = array();

        if (!empty($fullAttributeList)) {
            $res = $database->exec_SELECTquery(
                '*',
                'tx_commerce_articles_article_attributes_mm',
                'uid_local = ' . (int) $aUid . ' AND uid_foreign IN (' . implode(',', $fullAttributeList) . ')'
            );

            while (($attributeData = $database->sql_fetch_assoc($res))) {
                $hashData[$attributeData['uid_foreign']] = $attributeData['uid_valuelist'];
            }
            asort($hashData);
        }

        $hash = md5(serialize($hashData));

        return $hash;
    }

    /**
     * Updates an article and recalculates the hash value for the assigned
     * attributes.
     *
     * @param int $aUid Uid of the article
     * @param array $fullAttributeList The list of uids for the attributes
     *      that are assigned to the article
     *
     * @return void
     */
    public function updateArticleHash($aUid, array $fullAttributeList = array())
    {
        if ($fullAttributeList == null) {
            $fullAttributeList = array();
            $articleAttributes = $this->getAttributesForArticle($aUid, 1);
            if (!empty($articleAttributes)) {
                foreach ($articleAttributes as $articleAttribute) {
                    $fullAttributeList[] = $articleAttribute['uid_foreign'];
                }
            }
        }

        $hash = $this->getArticleHash($aUid, $fullAttributeList);

        // update the article
        self::getDatabaseConnection()->exec_UPDATEquery(
            'tx_commerce_articles',
            'uid = ' . (int) $aUid,
            array('attribute_hash' => $hash)
        );
    }

    /* Diverse */

    /**
     * Proofs if there are non numeric chars in it.
     *
     * @param string $data String to check for a number
     *
     * @return int length of wrong chars
     */
    public function isNumber($data)
    {
        return strlen(preg_replace('/[0-9.]/', '', $data));
    }

    /**
     * This method returns the last part of a string.
     * It splits up the string at the underscores.
     * If the key doesn't contain any underscores, it returns
     * the int of the key.
     *
     * @param string $key Key string (e.g.: bla_fasel_12)
     * @param array $keyData Result from the explode method
     *      (PASSED BY REFERENCE)
     *
     * @return int value of the last part from the key
     */
    public function getUidFromKey($key, array &$keyData)
    {
        $uid = 0;

        if (strpos($key, '_') === false) {
            $uid = $key;
        } else {
            $keyData = @explode('_', $key);
            if (is_array($keyData)) {
                $uid = $keyData[(count($keyData) - 1)];
            }
        }

        return (int) $uid;
    }

    /**
     * Searches for a string in an array of arrays.
     *
     * @param string $needle Search value
     * @param array $array Data to search in
     * @param string $field Fieldname of the inside arrays in the search array
     *
     * @return bool if the needle was found, otherwise false
     */
    public function checkArray($needle, array $array, $field)
    {
        $result = false;

        if (is_array($array)) {
            foreach ($array as $entry) {
                if ($needle == $entry[$field]) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the "enable fields" for a sql query.
     *
     * @param string $table Table for the query
     * @param bool $getDeleted Flag if deleted entries schould be returned
     *
     * @return string A SQL-string with the enable fields
     */
    public function enableFields($table, $getDeleted = false)
    {
        $result = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
        if (!$getDeleted) {
            $result .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
        }

        return $result;
    }

    /**
     * Returns a list of UID from a list of strings that contains UIDs.
     *
     * @param array $list The list of strings
     *
     * @return array with extracted UIDs
     */
    public static function getUidListFromList(array $list)
    {
        $keyData = array();
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
     * Saves all relations between two tables. For example all relations
     * between products and articles.
     *
     * @param int $uidLocal Uid_local in the mm table
     * @param array $relationData Data that should be stored
     *      additionally in the relation table
     * @param string $relationTable Table where the relations are stored
     * @param bool $delete Delete all old relations
     * @param bool $withReference If true, the field "is_reference" is
     *      inserted into the database
     *
     * @return void
     */
    public static function saveRelations(
        $uidLocal,
        array $relationData,
        $relationTable,
        $delete = false,
        $withReference = true
    ) {
        $delWhere = array();
        $counter = 1;

        $database = self::getDatabaseConnection();

        if (is_array($relationData)) {
            foreach ($relationData as $relation) {
                $where = 'uid_local = ' . (int) $uidLocal;
                $dataArray = array('uid_local' => (int) $uidLocal);

                if (is_array($relation)) {
                    foreach ($relation as $key => $data) {
                        $dataArray[$key] = $data;
                        $where .= ' AND ' . $key . ' = \'' . $data . '\'';
                    }
                }
                if ($withReference && ($counter > 1)) {
                    $dataArray['is_reference'] = 1;
                    $where .= ' AND is_reference=1';
                }

                $dataArray['sorting'] = $counter;
                ++$counter;

                $checkRes = $database->exec_SELECTquery('uid_local', $relationTable, $where);
                $exists = $database->sql_num_rows($checkRes) > 0;

                if (!$exists) {
                    $database->exec_INSERTquery($relationTable, $dataArray);
                } else {
                    $database->exec_UPDATEquery($relationTable, $where, array('sorting' => $counter));
                }

                if (isset($relation['uid_foreign'])) {
                    $delClause = 'uid_foreign = ' . $relation['uid_foreign'];
                    if (isset($relation['uid_correlationtype'])) {
                        $delClause .= ' AND uid_correlationtype = ' . $relation['uid_correlationtype'];
                    }
                    $delWhere[] = $delClause;
                }
            }
        }

        if ($delete && !empty($delWhere)) {
            $where = ' AND NOT ((' . implode(') OR (', $delWhere) . '))';
            $database->exec_DELETEquery($relationTable, 'uid_local = ' . $uidLocal . $where);
        }
    }

    /**
     * Get all existing correlation types.
     *
     * @return array with correlation type entities
     */
    public function getAllCorrelationTypes()
    {
        $database = self::getDatabaseConnection();

        $ctRes = $database->exec_SELECTquery('uid', 'tx_commerce_attribute_correlationtypes', '1');
        $result = array();
        while (($correlationType = $database->sql_fetch_assoc($ctRes))) {
            $result[] = $correlationType;
        }

        return $result;
    }

    /**
     * Updates the XML of an article. This is neccessary because if we change
     * anything in a category we also change all related products and articles.
     * This has to be done in two steps. At first we have to update the
     * relations in the database. But if we do so, the user won't recognize the
     * changes in the backend because of the usage of flexforms.
     * So this method, updates all the flexform value data in the database for
     * the articles we change.
     *
     * @param array $articleRelations Relation dataset for the article
     * @param bool $add If this is true, we fetch the existing data before.
     *      Otherwise we overwrite it
     * @param int $articleUid UID of the article
     * @param int $productUid UID of the product
     */
    public function updateArticleXML(array $articleRelations, $add = false, $articleUid = null, $productUid = null)
    {
        $database = self::getDatabaseConnection();

        $xmlData = array();
        if ($add && is_numeric($articleUid)) {
            $result = $database->exec_SELECTquery('attributesedit', 'tx_commerce_articles', 'uid=' . (int) $articleUid);
            $xmlData = $database->sql_fetch_assoc($result);
            $xmlData = GeneralUtility::xml2array($xmlData['attributesedit']);
        }

        $relationData = array();
        /*
         * Build Relation Data
         */
        $resRelationData = null;
        if ($productUid) {
            $resRelationData = $database->exec_SELECTquery(
                'tx_commerce_articles_article_attributes_mm.*',
                'tx_commerce_articles, tx_commerce_articles_article_attributes_mm',
                'tx_commerce_articles.uid = tx_commerce_articles_article_attributes_mm.uid_local
                    AND tx_commerce_articles.uid_product = ' . (int) $productUid
            );
        }

        if ($articleUid) {
            $resRelationData = $database->exec_SELECTquery(
                'tx_commerce_articles_article_attributes_mm.*',
                'tx_commerce_articles, tx_commerce_articles_article_attributes_mm',
                'tx_commerce_articles.uid = tx_commerce_articles_article_attributes_mm.uid_local
                    AND tx_commerce_articles.uid = ' . (int) $articleUid
            );
        }

        if ($database->sql_num_rows($resRelationData)) {
            while (($relationRows = $database->sql_fetch_assoc($resRelationData))) {
                $relationData[] = $relationRows;
            }
        }

        if (!empty($relationData)) {
            foreach ($articleRelations as $articleRelation) {
                if ($articleRelation['uid_valuelist'] != 0) {
                    $value = $articleRelation['uid_valuelist'];
                } elseif (!empty($articleRelation['value_char'])) {
                    $value = $articleRelation['value_char'];
                } else {
                    if ($articleRelation['default_value'] != 0) {
                        $value = $articleRelation['default_value'];
                    } else {
                        $value = '';
                    }
                }

                $xmlData['data']['sDEF']['lDEF']['attribute_' . $articleRelation['uid_foreign']] =
                    array('vDEF' => $value);
            }
        }

        $xmlData = GeneralUtility::array2xml($xmlData, '', 0, 'T3FlexForms');

        if ($articleUid) {
            $database->exec_UPDATEquery(
                'tx_commerce_articles',
                'uid = ' . $articleUid . ' AND deleted = 0',
                array('attributesedit' => $xmlData)
            );
        } elseif ($productUid) {
            $database->exec_UPDATEquery(
                'tx_commerce_articles',
                'uid_product = ' . $productUid . ' AND deleted = 0',
                array('attributesedit' => $xmlData)
            );
        }
    }

    /**
     * Updates the XML of an FlexForm value field. This is almost the same as
     * "updateArticleXML" but more general.
     *
     * @param string $xmlField Fieldname where the FlexForm values are stored
     * @param string $table Table in which the FlexForm values are stored
     * @param int $uid UID of the entity inside the table
     * @param string $type Type of data we are handling (category, product)
     * @param array $ctList A list of correlationtype UID we should handle
     * @param bool $rebuild Wether the xmlData should be rebuild or not
     *
     * @return array
     */
    public function updateXML($xmlField, $table, $uid, $type, array $ctList, $rebuild = false)
    {
        $database = self::getDatabaseConnection();

        $xmlDataResult = $database->exec_SELECTquery($xmlField, $table, 'uid = ' . (int) $uid);
        $xmlData = $database->sql_fetch_assoc($xmlDataResult);
        $xmlData = GeneralUtility::xml2array($xmlData[$xmlField]);
        if (!is_array($xmlData)) {
            $xmlData = array();
        }

        $relList = null;
        switch (strtolower($type)) {
            case 'category':
                $relList = $this->getAttributesForCategory($uid);
                break;

            case 'product':
                $relList = $this->getAttributesForProduct($uid);
                break;

            default:
        }

        $cTypes = array();

        // write the data
        if (is_array($ctList)) {
            foreach ($ctList as $ct) {
                $value = array();
                if (is_array($relList)) {
                    foreach ($relList as $relation) {
                        if (isset($ct['uid']) && $relation['uid_correlationtype'] == (int) $ct['uid']) {
                            // add ctype to checklist in case we need to rebuild
                            if (!in_array($ct['uid'], $cTypes)) {
                                $cTypes[] = (int) $ct['uid'];
                            }

                            $value[] = $relation['uid_foreign'];
                        }
                    }
                }

                if (!empty($value)) {
                    $xmlData['data']['sDEF']['lDEF']['ct_' . (string) $ct['uid']] =
                        array('vDEF' => (string) implode(',', $value));
                }
            }
        }

        // rebuild
        if ($rebuild && !empty($cTypes) && is_array($ctList)) {
            foreach ($ctList as $ct) {
                if (!in_array($ct['uid'], $cTypes)) {
                    $xmlData['data']['sDEF']['lDEF']['ct_' . (string) $ct['uid']] = array('vDEF' => '');
                }
            }
        }

        // build new XML
        if (is_array($xmlData)) {
            // Dump Quickfix
            $xmlData = GeneralUtility::array2xml($xmlData, '', 0, 'T3FlexForms');
        } else {
            $xmlData = '';
        }

        // update database entry
        $database->exec_UPDATEquery($table, 'uid = ' . $uid, array($xmlField => $xmlData));

        return array($xmlField => $xmlData);
    }

    /**
     * Merges an attribute list from a flexform together into one array.
     *
     * @param array $ffData FlexForm data as array
     * @param string $prefix Prefix that is used for the correlationtypes
     * @param array $ctList List of correlations that should be processed
     * @param int $uidLocal Field in the relation table
     * @param array $paList List of product attributes (PASSED BY REFERENCE)
     */
    public function mergeAttributeListFromFFData(array $ffData, $prefix, array $ctList, $uidLocal, array &$paList)
    {
        if (is_array($ctList)) {
            foreach ($ctList as $ctUid) {
                $ffaList = explode(',', $ffData[$prefix . $ctUid['uid']]['vDEF']);
                if (count($ffaList) == 1 && $ffaList[0] == '') {
                    continue;
                }

                foreach ($ffaList as $aUid) {
                    if (!(
                        $this->checkArray($uidLocal, $paList, 'uid_local')
                        && $this->checkArray($aUid, $paList, 'uid_foreign')
                        && $this->checkArray($ctUid['uid'], $paList, 'uid_correlationtype')
                    )) {
                        if ($aUid != '') {
                            $newRel = array(
                                'uid_local' => $uidLocal,
                                'uid_foreign' => $aUid,
                                'uid_correlationtype' => $ctUid['uid'],
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
     * @param array $array Data
     * @param string $field Field that should be extracted
     * @param bool $makeArray If true the result is returned as an array of
     *      arrays
     * @param array $extraFields Add some extra fields to the result
     *
     * @return array with fieldnames
     */
    public static function extractFieldArray(array $array, $field, $makeArray = false, array $extraFields = array())
    {
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
                    if (!empty($extraFields)) {
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
     * @param int $categoryUid UID of the category.
     *
     * @return array with the entities of the found products
     */
    public function getProductsOfCategory($categoryUid)
    {
        return (array) self::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'tx_commerce_products_categories_mm',
            'uid_foreign = ' . (int) $categoryUid
        );
    }

    /**
     * Returns an array for the UPDATEquery method. It fills different arrays
     * with an attribute value. This wrapper is needed because the fields have
     * different names in different tables. I know that's stupid but this is a
     * fact... :-/.
     *
     * @param array $attributeData Data that should be stored
     * @param string|int $data Value of the attribute
     * @param int $productUid UID of the produt
     *
     * @return array with two arrays as elements
     */
    public function getUpdateData(array $attributeData, $data, $productUid = 0)
    {
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
     * Builds the lokalised Array for the attribute Felxform.
     *
     * @param string $flexValue String XML-Flex-Form
     * @param string $langIdent Language Ident
     *
     * @return string XML-Flex-Form
     */
    public function buildLocalisedAttributeValues($flexValue, $langIdent)
    {
        $attributeFlexformData = GeneralUtility::xml2array($flexValue);

        $result = '';
        if (is_array($attributeFlexformData)) {
            // change the language
            $attributeFlexformData['data']['sDEF']['l' . $langIdent] = $attributeFlexformData['data']['sDEF']['lDEF'];

            /*
             * Decide on what to to on lokalisation, how to act
             * @see ext_conf_template
             * attributeLocalizationType[0|1|2]
             * 0: set blank
             * 1: Copy
             * 2: prepend [Translate to .$langRec['title'].:]
             */
            switch (SettingsFactory::getInstance()->getExtConf('attributeLocalizationType')) {
                case 0:
                    unset($attributeFlexformData['data']['sDEF']['lDEF']);
                    break;

                case 1:
                    break;

                case 2:
                    /*
                     * Iterate over the array and prepend text
                     */
                    $prepend = '[Translate to ' . $langIdent . ':] ';
                    foreach (array_keys($attributeFlexformData['data']['sDEF']['lDEF']) as $attribKey) {
                        $attributeFlexformData['data']['sDEF']['lDEF'][$attribKey]['vDEF'] = $prepend .
                            $attributeFlexformData['data']['sDEF']['lDEF'][$attribKey]['vDEF'];
                    }
                    break;

                default:
            }
            $result = GeneralUtility::array2xml($attributeFlexformData, '', 0, 'T3FlexForms');
        }

        return $result;
    }

    /**
     * save Price-Flexform with given Article-UID
     *
     * @param int $priceUid ID of Price-Dataset save as flexform
     * @param int $articleUid ID of article which the flexform is for
     * @param array $priceDataArray Priceinformation for the article
     *
     * @return boolean Status of method
     */
    public function savePriceFlexformWithArticle($priceUid, $articleUid, array $priceDataArray)
    {
        $prices = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'prices',
            'tx_commerce_articles',
            'uid = ' . (int) $articleUid
        );

        if (strlen($prices['prices']) > 0) {
            $data = GeneralUtility::xml2array($prices['prices']);
        } else {
            $data = array('data' => array('sDEF' => array('lDEF')));
        }

        $data['data']['sDEF']['lDEF']['price_net_' . $priceUid] = array(
            'vDEF' => sprintf('%.2f', ($priceDataArray['price_net'] /100))
        );
        $data['data']['sDEF']['lDEF']['price_gross_' . $priceUid] = array(
            'vDEF' => sprintf('%.2f', ($priceDataArray['price_gross'] /100))
        );
        $data['data']['sDEF']['lDEF']['hidden_' . $priceUid] = array('vDEF' => $priceDataArray['hidden']);
        $data['data']['sDEF']['lDEF']['starttime_' . $priceUid] = array('vDEF' => $priceDataArray['starttime']);
        $data['data']['sDEF']['lDEF']['endtime_' . $priceUid] = array('vDEF' => $priceDataArray['endtime']);
        $data['data']['sDEF']['lDEF']['fe_group_' . $priceUid] = array('vDEF' => $priceDataArray['fe_group']);
        $data['data']['sDEF']['lDEF']['purchase_price_' . $priceUid] = array(
            'vDEF' => sprintf('%.2f', ($priceDataArray['purchase_price'] /100))
        );
        $data['data']['sDEF']['lDEF']['price_scale_amount_start_' . $priceUid] = array(
            'vDEF' => $priceDataArray['price_scale_amount_start']
        );
        $data['data']['sDEF']['lDEF']['price_scale_amount_end_' . $priceUid] = array(
            'vDEF' => $priceDataArray['price_scale_amount_end']
        );

        $xml = GeneralUtility::array2xml($data, '', 0, 'T3FlexForms');

        $res = $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_commerce_articles',
            'uid = ' . (int) $articleUid,
            array('prices' => $xml)
        );

        return (bool)$res;
    }

    /**
     * Get order folder selector.
     *
     * @param int $pid Page id
     * @param int $levels Levels
     * @param int $aktLevel Current level
     *
     * @return array|bool
     */
    public static function getOrderFolderSelector($pid, $levels, $aktLevel = 0)
    {
        $returnArray = array();
        /*
         * Query the table to build dropdown list
         */
        $prep = '';
        for ($i = 0; $i < $aktLevel; ++$i) {
            $prep .= '- ';
        }

        $database = self::getDatabaseConnection();

        $foreignTable = SettingsFactory::getInstance()
            ->getTcaValue('tx_commerce_orders.columns.newpid.config.foreign_table');
        $result = $database->exec_SELECTquery(
            '*',
            $foreignTable,
            'pid = ' . $pid . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($foreignTable),
            '',
            'sorting'
        );
        if ($database->sql_num_rows($result)) {
            while (($returnData = $database->sql_fetch_assoc($result))) {
                $returnData['title'] = $prep . $returnData['title'];

                $returnArray[] = array($returnData['title'], $returnData['uid']);
                $tmparray = self::getOrderFolderSelector($returnData['uid'], $levels - 1, $aktLevel + 1);
                if (is_array($tmparray)) {
                    $returnArray = array_merge($returnArray, $tmparray);
                }
            }
            $database->sql_free_result($result);
        }
        if (!empty($returnArray)) {
            return $returnArray;
        }

        return false;
    }

    /**
     * Update Flexform XML from Database.
     *
     * @param int $articleUid ID of article
     *
     * @return bool Status of method
     */
    public function updatePriceXMLFromDatabase($articleUid)
    {
        $database = self::getDatabaseConnection();

        $res = $database->exec_SELECTquery(
            '*',
            'tx_commerce_article_prices',
            'deleted = 0 AND uid_article = ' . (int) $articleUid
        );

        $data = array('data' => array('sDEF' => array('lDEF')));
        $lDef = &$data['data']['sDEF']['lDEF'];
        while (($price = $database->sql_fetch_assoc($res))) {
            $priceUid = $price['uid'];

            $lDef['price_net_' . $priceUid] = array('vDEF' => sprintf('%.2f', ($price['price_net'] / 100)));
            $lDef['price_gross_' . $priceUid] = array('vDEF' => sprintf('%.2f', ($price['price_gross'] / 100)));
            $lDef['purchase_price_' . $priceUid] = array('vDEF' => sprintf('%.2f', ($price['purchase_price'] / 100)));
            $lDef['hidden_' . $priceUid] = array('vDEF' => $price['hidden']);
            $lDef['starttime_' . $priceUid] = array('vDEF' => $price['starttime']);
            $lDef['endtime_' . $priceUid] = array('vDEF' => $price['endtime']);
            $lDef['fe_group_' . $priceUid] = array('vDEF' => $price['fe_group']);
            $lDef['price_scale_amount_start_' . $priceUid] = array('vDEF' => $price['price_scale_amount_start']);
            $lDef['price_scale_amount_end_' . $priceUid] = array('vDEF' => $price['price_scale_amount_end']);
        }

        $xml = GeneralUtility::array2xml($data, '', 0, 'T3FlexForms');

        $res = $database->exec_UPDATEquery(
            'tx_commerce_articles',
            'uid = ' . $articleUid,
            array('prices' => $xml)
        );

        return (bool) $res;
    }

    /**
     * This function gives all attributes of one product to the other (only
     * mm attributes, flexforms need to be handled separately
     * [@see fix_product_atributte()]).
     *
     * @param int $pUidFrom Product UID from which to take the Attributes
     * @param int $pUidTo Product UID to which we give the Attributes
     * @param bool $copy If set, the Attributes will only be copied - else
     *      cut (aka "swapped" in its true from)
     *
     * @return bool Success
     */
    public static function swapProductAttributes($pUidFrom, $pUidTo, $copy = false)
    {
        // verify params
        if (!is_numeric($pUidFrom) || !is_numeric($pUidTo)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'swapProductAttributes (' . self::class . ') gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        // check perms
        if (!self::checkProductPerms($pUidFrom, ($copy) ? 'show' : 'editcontent')) {
            return false;
        }
        if (!self::checkProductPerms($pUidTo, 'editcontent')) {
            return false;
        }

        $database = self::getDatabaseConnection();

        if (!$copy) {
            // cut the attributes - or, update the mm table with the new uids of the product
            $database->exec_UPDATEquery(
                'tx_commerce_products_attributes_mm',
                'uid_local = ' . (int) $pUidFrom,
                array('uid_local' => (int) $pUidTo)
            );

            $success = $database->sql_error() == '';
        } else {
            // copy the attributes - or, get all values from the original product
            // relation and insert them with the new uid_local
            $res = $database->exec_SELECTquery(
                'uid_foreign, tablenames, sorting, uid_correlationtype, uid_valuelist, default_value',
                'tx_commerce_products_attributes_mm',
                'uid_local = ' . $pUidFrom
            );

            while (($row = $database->sql_fetch_row($res))) {
                $row['uid_local'] = $pUidTo;

                    // insert
                $database->exec_INSERTquery('tx_commerce_products_attributes_mm', $row);
            }

            $success = $database->sql_error() == '';
        }

        return $success;
    }

    /**
     * This function gives all articles of one product to another.
     *
     * @param int $pUidFrom Product UID from which to take the Articles
     * @param int $pUidTo Product UID to which we give the Articles
     * @param bool $copy If set, the Articles will only be copied - else
     *      cut (aka "swapped" in its true from)
     *
     * @return bool Success
     */
    public static function swapProductArticles($pUidFrom, $pUidTo, $copy = false)
    {
        // check params
        if (!is_numeric($pUidFrom) || !is_numeric($pUidTo)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'swapProductArticles (' . self::class . ') gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        // check perms
        if (!self::checkProductPerms($pUidFrom, ($copy ? 'show' : 'editcontent'))) {
            return false;
        }
        if (!self::checkProductPerms($pUidTo, 'editcontent')) {
            return false;
        }

        $database = self::getDatabaseConnection();

        if (!$copy) {
            // cut the articles - or, give all articles of the old
            // product the product_uid of the new product
            $database->exec_UPDATEquery(
                'tx_commerce_articles',
                'uid_product = ' . $pUidFrom,
                array('uid_product' => $pUidTo)
            );

            $success = $database->sql_error() == '';
        } else {
            // copy the articles - or, read all article uids of the
            // old product and invoke the copy command
            $res = $database->exec_SELECTquery('uid', 'tx_commerce_articles', 'uid_product = ' . $pUidFrom);

            $success = true;

            while (($row = $database->sql_fetch_assoc($res))) {
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
     * Copies the specified Article to the new product.
     *
     * @param int $uid Uid of existing article which is to be copied
     * @param int $uidProduct Uid of product that is new parent
     * @param array $locale Array with sys_langauges to copy along, none if null
     *
     * @return int UID of the new article or false on error
     */
    public function copyArticle($uid, $uidProduct, array $locale = array())
    {
        $backendUser = self::getBackendUser();
        $database = self::getDatabaseConnection();

        // check params
        if (!is_numeric($uid) || !is_numeric($uidProduct)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyArticle (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        // check show right for this article under
        // all categories of the current parent product
        $prod = self::getProductOfArticle($uid);

        if (!self::checkProductPerms($prod['uid'], 'show')) {
            return false;
        }

        // check editcontent right for this article under
        // all categories of the new parent product
        if (!self::checkProductPerms($uidProduct, 'editcontent')) {
            return false;
        }

        // get uid of the last article in the articles table
        $res = $database->exec_SELECTquery('uid', 'tx_commerce_articles', 'deleted = 0', '', 'uid DESC', '0,1');

        // if there are no articles at all, abort.
        if (!$database->sql_num_rows($res)) {
            return false;
        }

        $row = $database->sql_fetch_assoc($res);
        // uid of the last article (after this article we will copy the new article
        $uidLast = $row['uid'];

        // init tce
        /**
         * TCE main.
         *
         * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
         */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
        if (is_array($tcaDefaultOverride)) {
            $tce->setDefaultsFromUserTS($tcaDefaultOverride);
        }

        // start
        $tce->start(array(), array());

        // invoke the copy manually so we can actually override the uid_product field
        $overrideArray = array('uid_product' => $uidProduct);

        // Write to session that we copy
        // this is used by the hook to the datamap class
        // to figure out if it should call the dynaflex
        // so far this is the best (though not very clean)
        // way to solve the issue we get when saving an article
        $backendUser->uc['txcommerce_copyProcess'] = 1;
        $backendUser->writeUC();

        $newUid = $tce->copyRecord('tx_commerce_articles', $uid, -$uidLast, 1, $overrideArray);

        // We also overwrite because Attributes and Prices will not be saved
        // when we copy this is because commerce hooks into
        // _preProcessFieldArray when it wants to make the prices etc. which is
        // too early when we copy because at this point the uid does not exist
        // self::overwriteArticle($uid, $newUid, $locale); <-- REPLACED WITH
        // OVERWRITE OF WHOLE PRODUCT BECAUSE THAT ACTUALLY WORKS

        // copying done, clear session
        $backendUser->uc['txcommerce_copyProcess'] = 0;
        $backendUser->writeUC();

        if (!is_numeric($newUid)) {
            return false;
        }

        // copy prices - not possible through datamap so we do it here
        self::copyPrices($uid, $newUid);

        // copy attributes - creating attributes doesn't work with normal
        // copy because only when a product is created in datamap, it creates
        // the attributes for articles with hook But by the time we copy
        // articles, product is already created and we have to copy the
        // attributes manually
        self::overwriteArticleAttributes($uid, $newUid);

        // copy locales
        if (!empty($locale)) {
            foreach ($locale as $loc) {
                self::copyLocale('tx_commerce_articles', $uid, $newUid, $loc);
            }
        }

        return $newUid;
    }

    /**
     * Copies the Prices of a Article.
     *
     * @param int $uidFrom Article uid from
     * @param int $uidTo Article uid to
     *
     * @return bool
     */
    public function copyPrices($uidFrom, $uidTo)
    {
        $backendUser = self::getBackendUser();
        $database = self::getDatabaseConnection();

        // select all existing prices of the article
        $res = $database->exec_SELECTquery(
            'uid',
            'tx_commerce_article_prices',
            'deleted = 0 AND uid_article = ' . $uidFrom
        );

        $newUid = 0;
        while (($row = $database->sql_fetch_assoc($res))) {
            // copy them to the new article
            /**
             * Data handler.
             *
             * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
             */
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

            $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
            if (is_array($tcaDefaultOverride)) {
                $tce->setDefaultsFromUserTS($tcaDefaultOverride);
            }

            // start
            $tce->start(array(), array());

            // invoke the copy manually so we can actually override the uid_product field
            $overrideArray = array('uid_article' => $uidTo);

            $backendUser->uc['txcommerce_copyProcess'] = 1;
            $backendUser->writeUC();

            $newUid = $tce->copyRecord('tx_commerce_article_prices', $row['uid'], -$row['uid'], 1, $overrideArray);

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
     * records; note that this function deletes the existing attributes.
     *
     * @param int $uidFrom Uid of the article we get the attributes from
     * @param int $uidTo Uid of the article we want to copy the attributes to
     * @param int $languageUid Language uid
     *
     * @return bool Success
     */
    public function overwriteArticleAttributes($uidFrom, $uidTo, $languageUid = 0)
    {
        $database = self::getDatabaseConnection();
        // delete existing attributes
        $table = 'tx_commerce_articles_article_attributes_mm';

        if ($languageUid != 0) {
            // we want to overwrite the attributes of the locale
            // replace $uidFrom and $uidTo with their localized versions
            $uids = $uidFrom . ',' . $uidTo;
            $res = $database->exec_SELECTquery(
                'uid, l18n_parent',
                'tx_commerce_articles',
                'sys_language_uid = ' . $languageUid . ' AND l18n_parent IN (' . $uids . ')'
            );
            $newFrom = $uidFrom;
            $newTo = $uidTo;

            // get uids
            while (($row = $database->sql_fetch_assoc($res))) {
                if ($row['l18n_parent'] == $uidFrom) {
                    $newFrom = $row['uid'];
                } elseif ($row['l18n_parent'] == $uidTo) {
                    $newTo = $row['uid'];
                }
            }

            // abort if we didn't find the locale for any of the articles
            if ($newFrom == $uidFrom || $newTo == $uidTo) {
                return false;
            }

            // replace uids
            $uidFrom = $newFrom;
            $uidTo = $newTo;
        }

        $database->exec_DELETEquery(
            $table,
            'uid_local = ' . $uidTo
        );

        // copy the attributes
        $res = $database->exec_SELECTquery('*', $table, 'uid_local = ' . $uidFrom . ' AND uid_valuelist = 0');

        while (($origRelation = $database->sql_fetch_assoc($res))) {
            $origRelation['uid_local'] = $uidTo;
            $database->exec_INSERTquery($table, $origRelation);
        }

        return true;
    }

    /**
     * Copies the specified Product to the new category.
     *
     * @param int $uid Uid of existing product which is to be copied
     * @param int $categoryUid Category uid
     * @param bool $ignoreWs If versioning should be disabled
     *      (be warned: use this only if you are 100% sure you know what you are doing)
     * @param array $locale Languages that should be copied,
     *      or null if none are specified
     * @param int $sorting Uid of the record behind which we
     *      insert this product, or 0 to just append
     *
     * @return int UID of the new product or false on error
     */
    public static function copyProduct($uid, $categoryUid, $ignoreWs = false, array $locale = null, $sorting = 0)
    {
        // check params
        if (!is_numeric($uid) || !is_numeric($categoryUid) || !is_numeric($sorting)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyProduct (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        // check if we may actually copy the product (no permission
        // check, only check if we are not accidentally copying a
        // placeholder or deleted product)
        // also hidden products are not allowed to be copied
        $record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL(
            'tx_commerce_products',
            $uid,
            '*',
            ' AND hidden = 0 AND t3ver_state = 0'
        );

        if (!$record) {
            return false;
        }

        // check if we have the permissions to copy
        // (check category rights) - skip if we are copying locales
        if (!self::checkProductPerms($uid, 'copy')) {
            return false;
        }

            // check editcontent right for uid_category
        if (!self::readCategoryAccess($categoryUid, self::getCategoryPermsClause(self::getPermMask('editcontent')))) {
            return false;
        }

        // First prepare user defined hooks
        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Utility/BackendUtility', 'copyProduct');

        $backendUser = self::getBackendUser();
        $database = self::getDatabaseConnection();

        if ($sorting == 0) {
            // get uid of the last product in the products table
            $res = $database->exec_SELECTquery(
                'uid',
                'tx_commerce_products',
                'deleted = 0 AND pid != -1',
                '',
                'uid DESC',
                '0,1'
            );

            // if there are no products at all, abort.
            if (!$database->sql_num_rows($res)) {
                return false;
            }

            $row = $database->sql_fetch_assoc($res);
            // uid of the last product (after this product we will copy the new product)
            $uidLast = -$row['uid'];
        } else {
            // sorting position is specified
            $uidLast = (0 > $sorting) ? $sorting : self::getCopyPid('tx_commerce_products', $sorting);
        }

        // init tce
        /**
         * Data handler.
         *
         * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
         */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        // set workspace bypass if requested
        $tce->bypassWorkspaceRestrictions = $ignoreWs;

        $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
        if (is_array($tcaDefaultOverride)) {
            $tce->setDefaultsFromUserTS($tcaDefaultOverride);
        }

        // start
        $tce->start(array(), array());

        // invoke the copy manually so we can actually override the categories field
        $overrideArray = array('categories' => $categoryUid);

        // Hook: beforeCopy
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'beforeCopy')) {
                $hookObj->beforeCopy($uid, $uidLast, $overrideArray);
            }
        }

        $newUid = $tce->copyRecord('tx_commerce_products', $uid, $uidLast, 1, $overrideArray);

        // Hook: afterCopy
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'afterCopy')) {
                $hookObj->afterCopy($newUid, $uid, $overrideArray);
            }
        }

        if (!is_numeric($newUid)) {
            return false;
        }

        // copy locales
        if (is_array($locale) && !empty($locale)) {
            foreach ($locale as $loc) {
                self::copyLocale('tx_commerce_products', $uid, $newUid, $loc, $ignoreWs);
            }
        }

        // copy articles
        $success = self::copyArticlesByProduct($newUid, $uid, $locale);

        // Overwrite the Product we just created again - to fix that Attributes
        // and Prices are not copied for Articles when they are only copied
        // This should be TEMPORARY - find a clean way to fix that problem
        // self::overwriteProduct($uid, $newUid, $locale); ###fixed###
        return !$success ? $success : $newUid;
    }

    /**
     * Copies any locale of a commerce items.
     *
     * @param string $table Name of the table in which the locale is
     * @param int $uidCopied Uid of the record that is localized
     * @param int $uidNew Uid of the record that was copied and now needs a locale
     * @param int $languageUid Uid of the sys_language
     * @param bool $ignoreWs Ignore workspace
     *
     * @return bool Success
     */
    public function copyLocale($table, $uidCopied, $uidNew, $languageUid, $ignoreWs = false)
    {
        // check params
        if (!is_string($table) || !is_numeric($uidCopied) || !is_numeric($uidNew) || !is_numeric($languageUid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyLocale (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        $backendUser = self::getBackendUser();
        $database = self::getDatabaseConnection();

        $tableConfig = SettingsFactory::getInstance()->getTcaValue($table);
        if ($tableConfig && $uidCopied) {
            // make data
            $rec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($table, $uidCopied, $languageUid);

            // if the item is not localized, return
            if (false == $rec) {
                return true;
            }

            // overwrite l18n parent
            $rec[0]['l18n_parent'] = $uidNew;
            // unset uid for cleanliness
            unset($rec[0]['uid']);

            // unset all fields that are not supposed to be copied on localized versions
            foreach ($tableConfig['columns'] as $fN => $fCfg) {
                // Otherwise, do not copy field (unless it is the
                // language field or pointer to the original language)
                if (GeneralUtility::inList('exclude,noCopy,mergeIfNotBlank', $fCfg['l10n_mode'])
                    && $fN != $tableConfig['ctrl']['languageField']
                    && $fN != $tableConfig['ctrl']['transOrigPointerField']
                ) {
                    unset($rec[0][$fN]);
                }
            }

            // if we localize an article, add the product
            // uid of the $uidNew localized product
            if ('tx_commerce_articles' == $table) {
                /**
                 * Article.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Article $article
                 */
                $article = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Article::class, $uidNew);
                $productUid = $article->getParentProductUid();

                // load uid of the localized product
                $res = $database->exec_SELECTquery(
                    'uid',
                    'tx_commerce_products',
                    'l18n_parent = ' . $productUid . ' AND sys_language_uid = ' . $languageUid
                );
                $row = $database->sql_fetch_assoc($res);

                $rec[0]['uid_product'] = $row['uid'];
            }

            $data = array();

            $newUid = uniqid('NEW');
            $data[$table][$newUid] = $rec[0];

            // init tce
            /**
             * Data handler.
             *
             * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
             */
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

            // set workspace bypass if requested
            $tce->bypassWorkspaceRestrictions = $ignoreWs;

            $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
            if (is_array($tcaDefaultOverride)) {
                $tce->setDefaultsFromUserTS($tcaDefaultOverride);
            }

            // start
            $tce->start($data, array());

            // Write to session that we copy
            // this is used by the hook to the datamap class to figure out if
            // it should call the dynaflex so far this is the best (though not
            // very clean) way to solve the issue we get when saving an article
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
                self::overwriteArticleAttributes($uidCopied, $newUid, $languageUid);
            }
        }

        return true;
    }

    /**
     * Overwrites the localization of a record
     * if the record does not have the localization, it is copied to the record.
     *
     * @param string $table Name of the table in which we overwrite records
     * @param int $uidCopied Uid of the record that is the overwriter
     * @param int $uidOverwrite Uid of the record that is to be overwritten
     * @param int $loc Uid of the syslang that is overwritten
     *
     * @return bool Success
     */
    public function overwriteLocale($table, $uidCopied, $uidOverwrite, $loc)
    {
        $backendUser = $this->getBackendUser();

        // check params
        if (!is_string($table) || !is_numeric($uidCopied) || !is_numeric($uidOverwrite) || !is_numeric($loc)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyLocale (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        $tableConfig = SettingsFactory::getInstance()->getTcaValue($table);
        // check if table is defined in the TCA
        if ($tableConfig && $uidCopied) {
            // make data
            $recFrom = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($table, $uidCopied, $loc);
            $recTo = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($table, $uidOverwrite, $loc);

            // if the item is not localized, return
            if (false == $recFrom) {
                return true;
            }

            // if the overwritten record does not have
            // the corresponding localization, just copy it
            if (false == $recTo) {
                return self::copyLocale($table, $uidCopied, $uidOverwrite, $loc);
            }

            // overwrite l18n parent
            $recFrom[0]['l18n_parent'] = $uidOverwrite;
            // unset uid for cleanliness
            unset($recFrom[0]['uid']);

            // unset all fields that are not supposed to be copied on localized versions
            foreach ($tableConfig['columns'] as $fN => $fCfg) {
                // Otherwise, do not copy field (unless it is the
                // language field or pointer to the original language)
                if (GeneralUtility::inList('exclude,noCopy,mergeIfNotBlank', $fCfg['l10n_mode'])
                    && $fN != $tableConfig['ctrl']['languageField']
                    && $fN != $tableConfig['ctrl']['transOrigPointerField']
                ) {
                    unset($recFrom[0][$fN]);
                } elseif (isset($fCfg['config']['type'])
                    && 'flex' == $fCfg['config']['type']
                    && isset($recFrom[0][$fN])
                ) {
                    if ($recFrom[0][$fN]) {
                        $recFrom[0][$fN] = GeneralUtility::xml2array($recFrom[0][$fN]);

                        if (trim($recFrom[0][$fN]) == '') {
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
            /**
             * Data handler.
             *
             * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
             */
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

            $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
            if (is_array($tcaDefaultOverride)) {
                $tce->setDefaultsFromUserTS($tcaDefaultOverride);
            }

            // start
            $tce->start($data, array());

            // Write to session that we copy
            // this is used by the hook to the datamap class to figure out if
            // it should call the dynaflex so far this is the best (though not
            // very clean) way to solve the issue we get when saving an article
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

        return true;
    }

    /**
     * Deletes all localizations of a record
     * Note that no permission check is made whatsoever!
     * Check perms if you implement this beforehand.
     *
     * @param string $table Table name
     * @param int $uid Uid of the record
     *
     * @return bool Success
     */
    public function deleteL18n($table, $uid)
    {
        // check params
        if (!is_string($table) || !is_numeric($uid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('deleteL18n (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        $database = self::getDatabaseConnection();

        // get all locales
        $res = $database->exec_SELECTquery('uid', $table, 'l18n_parent = ' . (int) $uid);

        // delete them
        while (($row = $database->sql_fetch_assoc($res))) {
            $database->exec_UPDATEquery($table, 'uid = ' . $row['uid'], array('deleted' => 1));
        }

        return true;
    }

    /**
     * Copies the specified category into the new category
     * note: you may NOT copy the same category into itself.
     *
     * @param int $uid Uid of existing category which is to be copied
     * @param int $parentUid Uid of category that is new parent
     * @param array $locale Array with all uids of languages that should
     *      as well be copied - if null, no languages shall be copied
     * @param int $sorting Uid of the record behind which we copy
     *      (like - 23), or 0 if none is given at it should just be appended
     *
     * @return int UID of the new category or false on error
     */
    public static function copyCategory($uid, $parentUid, array $locale = null, $sorting = 0)
    {
        // check params
        if (!is_numeric($uid) || !is_numeric($parentUid) || $uid == $parentUid) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyCategory (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        // check if we have the right to copy this category
        // show right
        if (!self::readCategoryAccess($uid, self::getCategoryPermsClause(self::getPermMask('copy')))) {
            return false;
        }

        // check if we have the right to insert into the parent Category
        // new right
        if (!self::readCategoryAccess($parentUid, self::getCategoryPermsClause(self::getPermMask('new')))) {
            return false;
        }

        // First prepare user defined hooks
        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Utility/BackendUtility', 'copyCategory');

        $database = self::getDatabaseConnection();

        if (0 == $sorting) {
            // get uid of the last category in the category table
            $res = $database->exec_SELECTquery('uid', 'tx_commerce_categories', 'deleted = 0', '', 'uid DESC', '0,1');

            // if there are no categories at all, abort.
            if (!$database->sql_num_rows($res)) {
                return false;
            }

            $row = $database->sql_fetch_assoc($res);

            // uid of the last category (after this product we will copy the new category)
            $uidLast = -$row['uid'];
        } else {
            // copy after the given sorting point
            $uidLast = (0 > $sorting) ? $sorting : self::getCopyPid('tx_commerce_categories', $sorting);
        }

        // init tce
        /**
         * Data handler.
         *
         * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
         */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        $backendUser = self::getBackendUser();

        $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
        if (is_array($tcaDefaultOverride)) {
            $tce->setDefaultsFromUserTS($tcaDefaultOverride);
        }

        // start
        $tce->start(array(), array());

        // invoke the copy manually so we can actually override the categories field
        $overrideArray = array(
            'parent_category' => $parentUid,
        );

        // Hook: beforeCopy
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'beforeCopy')) {
                $hookObj->beforeCopy($uid, $uidLast, $overrideArray);
            }
        }

        $newUid = $tce->copyRecord('tx_commerce_categories', $uid, $uidLast, 1, $overrideArray);

        // Hook: afterCopy
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'afterCopy')) {
                $hookObj->afterCopy($newUid, $uid, $overrideArray);
            }
        }

        if (!is_numeric($newUid)) {
            return false;
        }

        // chmod the new category since perms are not copied
        self::chmodCategoryByCategory($newUid, $uid);

        // copy locale
        if (is_array($locale) && !empty($locale)) {
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
     * Changes the permissions of a category and applies the permissions
     * of another category Note that this does ALSO change owner or group.
     *
     * @param int $uidToChmod Uid of the category to chmod
     * @param int $uidFrom Uid of the category from which we take the perms
     *
     * @return bool Success
     */
    public function chmodCategoryByCategory($uidToChmod, $uidFrom)
    {
        // check params
        if (!is_numeric($uidToChmod) || !is_numeric($uidFrom)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'chmodCategoryByCategory (belib) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        $database = $this->getDatabaseConnection();

        // select current perms
        $res = $database->exec_SELECTquery(
            'perms_everybody, perms_group, perms_user, perms_groupid, perms_userid',
            'tx_commerce_categories',
            'uid = ' . $uidFrom . ' AND deleted = 0 AND ' . self::getCategoryPermsClause(self::getPermMask('show'))
        );
        $res2 = false;

        while (($row = $database->sql_fetch_assoc($res))) {
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

        return ($res2 !== false && $database->sql_error() == '');
    }

    /**
     * Returns the pid new pid for the copied item - this is only used when
     * inserting a record on front of another.
     *
     * @param string $table Table from which we want to read
     * @param int $uid Uid of the record that we want to move our element to
     *      - in front of it
     *
     * @return int
     */
    public function getCopyPid($table, $uid)
    {
        $database = self::getDatabaseConnection();

        $res = $database->exec_SELECTquery(
            'uid',
            $table,
            'sorting < (SELECT sorting FROM ' . $table . ' WHERE uid = ' . $uid . ') ORDER BY sorting DESC LIMIT 0,1'
        );

        $row = $database->sql_fetch_assoc($res);

        if ($row == null) {
            // the item we want to skip is the item with the lowest
            // sorting - use pid of the 'Product' Folder
            $pid = self::getProductFolderUid();
        } else {
            $pid = -$row['uid'];
        }

        return $pid;
    }

    /**
     * Copies all Products under a category to a new category
     * Note that Products that are copied in this indirect way are not versioned.
     *
     * @param int $catUidTo Uid of category to which the products should be copied
     * @param int $catUidFrom Uid of category from which the products should come
     * @param array $locale Languages which are to be copied as well, null if none
     *
     * @return bool Success
     */
    public function copyProductsByCategory($catUidTo, $catUidFrom, array $locale = null)
    {
        // check params
        if (!is_numeric($catUidTo) || !is_numeric($catUidFrom)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'copyProductsByCategory (belib) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        $database = self::getDatabaseConnection();

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

        $success = true;

        while (($row = $database->sql_fetch_assoc($res))) {
            $productCopied = self::copyProduct($row['uid_local'], $catUidTo, true, $locale);
            // keep false if one action was false
            $success = ($success) ? $productCopied : $success;
        }

        return $success;
    }

    /**
     * Copies all Categories under a category to a new category.
     *
     * @param int $catUidTo Uid of category to which the categories should be copied
     * @param int $catUidFrom Uid of category from which the categories should come
     * @param array $locale Langauges that should be copied as well
     *
     * @return bool Success
     */
    public function copyCategoriesByCategory($catUidTo, $catUidFrom, array $locale = null)
    {
        // check params
        if (!is_numeric($catUidTo) || !is_numeric($catUidFrom) || $catUidTo == $catUidFrom) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'copyCategoriesByCategory (belib) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        $database = self::getDatabaseConnection();

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
        $success = true;

        while (($row = $database->sql_fetch_assoc($res))) {
            $categoryCopied = self::copyCategory($row['uid_local'], $catUidTo, $locale);
            $success = ($success) ? $categoryCopied : $success;
        }

        return $success;
    }

    /**
     * Copies all Articles from one Product to another.
     *
     * @param int $prodUidTo Uid of product from which we copy the articles
     * @param int $prodUidFrom Uid of product to which we copy the articles
     * @param array $locale Languages to copy along, null if none
     *
     * @return bool Success
     */
    public function copyArticlesByProduct($prodUidTo, $prodUidFrom, array $locale = null)
    {
        // check params
        if (!is_numeric($prodUidTo) || !is_numeric($prodUidFrom)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'copyArticlesByProduct (belib) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        $database = self::getDatabaseConnection();

        $rows = (array) $database->exec_SELECTgetRows(
            'uid',
            'tx_commerce_articles',
            'deleted = 0 AND uid_product = ' . $prodUidFrom
        );
        $success = true;

        foreach ($rows as $row) {
            $succ = self::copyArticle($row['uid'], $prodUidTo, (array) $locale);
            $success = $success ? $succ : $success;
        }

        return $success;
    }

    /**
     * Returns a WHERE-clause for the tx_commerce_categories-table where user
     * permissions according to input argument, $perms, is validated. $perms
     * is the "mask" used to select. Fx. if $perms is 1 then you'll get all
     * categories that a user can actually see!
     *      2^0 = show (1)
     *      2^1 = edit (2)
     *      2^2 = delete (4)
     *      2^3 = new (8)
     * If the user is 'admin' " 1=1" is returned (no effect)
     * If the user is not set at all (->user is not an array), then " 1=0" is
     * returned (will cause no selection results at all) The 95% use of this
     * function is "->getCategoryPermsClause(1)" which will return WHERE
     * clauses for *selecting* categories in backend listings - in other words
     * this will check read permissions.
     *
     * @param int $perms Permission mask to use, see function description
     *
     * @return string Part of where clause. Prefix " AND " to this.
     */
    public static function getCategoryPermsClause($perms)
    {
        $backendUser = self::getBackendUser();

        if (is_array($backendUser->user)) {
            if ($backendUser->isAdmin()) {
                return ' 1=1';
            }

            // Make sure it's int.
            $perms = (int) $perms;
            $str = ' (' .
                // Everybody
                '(tx_commerce_categories.perms_everybody & ' . $perms . ' = ' . $perms . ')' .
                // User
                'OR(tx_commerce_categories.perms_userid = ' . $backendUser->user['uid'] .
                    ' AND tx_commerce_categories.perms_user & ' . $perms . ' = ' . $perms . ')';
            if ($backendUser->groupList) {
                // Group (if any is set)
                $str .= 'OR(tx_commerce_categories.perms_groupid in (' . $backendUser->groupList .
                    ') AND tx_commerce_categories.perms_group & ' . $perms . ' = ' . $perms . ')';
            }
            $str .= ')';

            return $str;
        } else {
            return ' 1=0';
        }
    }

    /**
     * Returns whether the Permission is set and allowed for the corresponding user.
     *
     * @param string $perm Word rep. for the wanted right
     *      ('show', 'edit', 'editcontent', 'delete', 'new')
     * @param array $record Record data
     *
     * @return bool $perm User allowed this action or not for the current category
     */
    public static function isPermissionSet($perm, array &$record)
    {
        if (!is_string($perm) || is_null($record)) {
            return false;
        }

        $backendUser = self::getBackendUser();

        // If User is admin, he may do anything
        if ($backendUser->isAdmin()) {
            return true;
        }

        $mask = self::getPermMask($perm);
        // if no mask is found, cancel.
        if ($mask == 0) {
            return false;
        }

        // if editlock is enabled and we edit, cancel edit.
        if ($mask == 2 && $record['editlock']) {
            return false;
        }

        // Check the rights of the current record
        // Check if anybody has the right to do the current operation
        if (isset($record['perms_everybody']) && (($record['perms_everybody'] & $mask) == $mask)) {
            return true;
        }

        // Check if user is owner of category and the owner may do the current operation
        if (isset($record['perms_userid']) && isset($record['perms_user'])
            && ($record['perms_userid'] == $backendUser->user['uid'])
            && (($record['perms_user'] & $mask) == $mask)
        ) {
            return true;
        }

        // Check if the Group has the right to do the current operation
        if (isset($record['perms_groupid']) && isset($record['perms_group'])) {
            $usergroups = explode(',', $backendUser->groupList);

            for ($i = 0, $l = count($usergroups); $i < $l; ++$i) {
                // User is member of the Group of the category - check the rights
                if ($record['perms_groupid'] == $usergroups[$i]) {
                    if (($record['perms_group'] & $mask) == $mask) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns the int Permission Mask for the String-Representation
     * of the Permission. Returns 0 if not found.
     *
     * @param string $perm String-Representation of the Permission
     *
     * @return int
     */
    public static function getPermMask($perm)
    {
        if (!is_string($perm)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('getPermMask (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return 0;
        }

        $mask = 0;

        switch ($perm) {
            case 'read':
                // fall through
            case 'show':
                // fall through
            case 'copy':
                $mask = 1;
                break;

            case 'edit':
                // fall through
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

            default:
        }

        return $mask;
    }

    /**
     * Checks the permissions for a product
     * (by checking for the permission in all parent categories).
     *
     * @param int $uid Product UId
     * @param string $perm String-Rep of the Permission
     *
     * @return bool Right exists or not
     */
    public function checkProductPerms($uid, $perm)
    {
        // check params
        if (!is_numeric($uid) || !is_string($perm)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('checkProductPerms (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        // get mask
        $mask = self::getPermMask($perm);

        if (!$mask) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'checkProductPerms (belib) gets passed an invalid permission to check for.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        // get parent categories
        $parents = self::getProductParentCategories($uid);

        // check the permissions
        if (!empty($parents)) {
            foreach ($parents as $parent) {
                if (!self::readCategoryAccess($parent, self::getCategoryPermsClause($mask))) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Returns a category record (of category with $id) with an extra field
     * "_thePath" set to the record path IF the WHERE clause, $perms_clause,
     * selects the record. Thus is works as an access check that returns a
     * category record if access was granted, otherwise not.
     * If $id is zero a pseudo root-page with "_thePath" set is returned IF
     * the current BE_USER is admin.
     * In any case ->isInWebMount must return true for the user
     * (regardless of $perms_clause).
     *
     * @param int    $id          Category uid for which to check read-access
     * @param string $permsClause Permission clause is typically a value
     *                            generated with SELF->getCategoryPermsClause(1);
     *
     * @return array Returns category record if OK, otherwise false.
     */
    public static function readCategoryAccess($id, $permsClause)
    {
        $backendUser = self::getBackendUser();

        if ((string) $id != '') {
            $id = (int) $id;
            if (!$id) {
                if ($backendUser->isAdmin()) {
                    $path = '/';
                    $pageinfo['_thePath'] = $path;

                    return $pageinfo;
                }
            } else {
                $pageinfo = self::getCategoryForRootline($id, ($permsClause ? ' AND ' . $permsClause : ''), false);
                \TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tx_commerce_categories', $pageinfo);
                if (is_array($pageinfo)) {
                    \TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid('tx_commerce_categories', $pageinfo);
                    list($pageinfo['_thePath'], $pageinfo['_thePathFull']) = self::getCategoryPath(
                        (int) $pageinfo['uid'],
                        $permsClause,
                        15,
                        1000
                    );

                    return $pageinfo;
                }
            }
        }

        return false;
    }

    /**
     * Returns the path (visually) of a page $uid, fx. "/First page/Second
     * page/Another subpage"
     * Each part of the path will be limited to $titleLimit characters
     * Deleted pages are filtered out.
     *
     * @param int $uid Page uid for which to create record path
     * @param string $clause Is additional where clauses, eg. "
     * @param int $titleLimit Title limit
     * @param int $fullTitleLimit Title limit of Full title (typ. set to 1000 or so)
     *
     * @return mixed Path of record (string) OR array with
     *      short/long title if $fullTitleLimit is set.
     */
    public static function getCategoryPath($uid, $clause, $titleLimit, $fullTitleLimit = 0)
    {
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
                // Adding visual token - Versioning Entry Point - that tells
                // that THIS position was where the versionized branch got
                // connected to the main tree. I will have to find a better
                // name or something...
                $output = ' [#VEP#]' . $output;
            }
            $output = '/' . GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), $titleLimit) . $output;
            if ($fullTitleLimit) {
                $fullOutput = '/' . GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), $fullTitleLimit) .
                    $fullOutput;
            }
        }

        if ($fullTitleLimit) {
            return array($output, $fullOutput);
        } else {
            return $output;
        }
    }

    /**
     * Returns what is called the 'RootLine'. That is an array with information
     * about the page records from a page id ($uid) and back to the root.
     * By default deleted pages are filtered.
     * This RootLine will follow the tree all the way to the root. This is
     * opposite to another kind of root line known from the frontend where the
     * rootline stops when a root-template is found.
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Can be used to select other criteria. It would
     *      typically be where-clauses that stops the process if we meet a page,
     *      the user has no reading access to.
     * @param bool $workspaceOl If true, version overlay is applied. This must
     *      be requested specifically because it is usually only wanted when the
     *      rootline is used for visual output while for permission checking you
     *      want the raw thing!
     *
     * @return array Root line array,
     *      all the way to the page tree root (or as far as $clause allows!)
     */
    public static function BEgetRootLine($uid, $clause = '', $workspaceOl = false)
    {
        static $backendGetRootLineCache = array();

        $output = array();
        $pid = $uid;
        $ident = $pid . '-' . $clause . '-' . $workspaceOl;

        if (is_array($backendGetRootLineCache[$ident])) {
            $output = $backendGetRootLineCache[$ident];
        } else {
            $loopCheck = 100;
            $theRowArray = array();
            while ($uid != 0 && $loopCheck) {
                --$loopCheck;
                $row = self::getCategoryForRootline($uid, $clause, $workspaceOl);
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
                --$c;
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
            $backendGetRootLineCache[$ident] = $output;
        }

        return $output;
    }

    /**
     * Gets the cached page record for the rootline.
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Can be used to select other criteria. It would
     *      typically be where-clauses that stops the process if we meet a page,
     *      the user has no reading access to.
     * @param bool $workspaceOl If true, version overlay is applied. This must
     *      be requested specifically because it is usually only wanted when the
     *      rootline is used for visual output while for permission checking you
     *      want the raw thing!
     *
     * @return array Cached page record for the rootline
     */
    protected static function getCategoryForRootline($uid, $clause, $workspaceOl)
    {
        $database = self::getDatabaseConnection();

        static $getPageForRootlineCache = array();
        $ident = $uid . '-' . $clause . '-' . $workspaceOl;

        if (is_array($getPageForRootlineCache[$ident])) {
            $row = $getPageForRootlineCache[$ident];
        } else {
            $res = $database->exec_SELECTquery(
                'mm.uid_foreign AS pid, tx_commerce_categories.uid, tx_commerce_categories.hidden,
                    tx_commerce_categories.title, tx_commerce_categories.ts_config, tx_commerce_categories.t3ver_oid,
                    tx_commerce_categories.perms_userid, tx_commerce_categories.perms_groupid,
                    tx_commerce_categories.perms_user, tx_commerce_categories.perms_group,
                    tx_commerce_categories.perms_everybody',
                'tx_commerce_categories
                    INNER JOIN pages ON tx_commerce_categories.pid = pages.uid
                    INNER JOIN tx_commerce_categories_parent_category_mm AS mm
                        ON tx_commerce_categories.uid = mm.uid_local',
                'tx_commerce_categories.uid = ' . (int) $uid . ' ' .
                \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_commerce_categories') . ' ' . $clause
            );

            $row = $database->sql_fetch_assoc($res);
            if ($row) {
                if ($workspaceOl) {
                    \TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tx_commerce_categories', $row);
                }
                if (is_array($row)) {
                    \TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid('tx_commerce_categories', $row);
                    $getPageForRootlineCache[$ident] = $row;
                }
            }
            $database->sql_free_result($res);
        }

        return $row;
    }

    /**
     * Checks whether the parent category of any content is given the right
     * 'editcontent' for the specific user and returns true or false depending
     * on the perms.
     *
     * @param array $categoryUids Uids of the categories
     * @param array $perms String for permissions to check
     *
     * @return bool
     */
    public static function checkPermissionsOnCategoryContent(array $categoryUids, array $perms)
    {
        $backendUser = self::getBackendUser();

        // admin is allowed to do anything
        if ($backendUser->isAdmin()) {
            return true;
        }

        /**
         * Category mount.
         *
         * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
         */
        $mount = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\CategoryMounts::class);
        $mount->init((int) $backendUser->user['uid']);

        foreach ($categoryUids as $categoryUid) {
            // check if the category is in the commerce mounts
            if (!$mount->isInCommerceMounts($categoryUid)) {
                return false;
            }

            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $categoryUid);
            // check perms
            foreach ($perms as $perm) {
                if (!$category->isPermissionSet($perm)) {
                    // return false if perms are not granted
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns if typo3 is running under a AJAX request.
     *
     * @return bool
     */
    public function isAjaxRequest()
    {
        return (bool) (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX);
    }

    /**
     * Returns the UID of the product folder.
     *
     * @return int UID
     */
    public static function getProductFolderUid()
    {
        static $productPid = null;

        if (is_null($productPid)) {
            $productPid = FolderRepository::initFolders('Products', FolderRepository::initFolders('Commerce'));
        }

        return $productPid;
    }

    /**
     * Returns the UID of the order folder.
     *
     * @return int UID
     */
    public static function getOrderFolderUid()
    {
        static $orderPid = null;

        if (is_null($orderPid)) {
            $orderPid = FolderRepository::initFolders('Orders', FolderRepository::initFolders('Commerce'));
        }

        return $orderPid;
    }

    /**
     * Overwrites a product record.
     *
     * @param int $uidFrom UID of the product we want to copy
     * @param int $uidTo UID of the product we want to overwrite
     * @param array $locale Languages
     *
     * @return bool
     */
    public static function overwriteProduct($uidFrom, $uidTo, array $locale = array())
    {
        $table = 'tx_commerce_products';

        // check params
        if (!is_numeric($uidFrom) || !is_numeric($uidTo) || $uidFrom == $uidTo) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('overwriteProduct (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        // check if we may actually copy the product (no permission check, only
        // check if we are not accidentally copying a placeholder or shadow or
        // deleted product)
        $recordFrom = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL(
            $table,
            $uidFrom,
            '*',
            ' AND pid != -1 AND t3ver_state != 1 AND deleted = 0'
        );

        if (!$recordFrom) {
            return false;
        }

        // check if we may actually overwrite the product (no permission check,
        // only check if we are not accidentaly overwriting a placeholder or
        // shadow or deleted product)
        $recordTo = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL(
            $table,
            $uidTo,
            '*',
            ' AND pid != -1 AND t3ver_state != 1 AND deleted = 0'
        );

        if (!$recordTo) {
            return false;
        }

        // check if we have the permissions to
        // copy and overwrite (check category rights)
        if (!self::checkProductPerms($uidFrom, 'copy') || !self::checkProductPerms($uidTo, 'editcontent')) {
            return false;
        }

        // First prepare user defined hooks
        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Utility/BackendUtility', 'overwriteProduct');

        $data = self::getOverwriteData($table, $uidFrom, $uidTo);

        // do not overwrite uid, parent_categories, and create_date
        unset(
            $data[$table][$uidTo]['uid'],
            $data[$table][$uidTo]['categories'],
            $data[$table][$uidTo]['crdate'],
            $data[$table][$uidTo]['cruser_id']
        );

        $datamap = $data;

        // execute
        /**
         * Data handler.
         *
         * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
         */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        $backendUser = self::getBackendUser();

        $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
        if (is_array($tcaDefaultOverride)) {
            $tce->setDefaultsFromUserTS($tcaDefaultOverride);
        }

        // Hook: beforeOverwrite
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'beforeOverwrite')) {
                $hookObj->beforeOverwrite($uidFrom, $uidTo, $datamap);
            }
        }

        $tce->start($datamap, array());
        $tce->process_datamap();

        // Hook: afterOverwrite
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'beforeCopy')) {
                $hookObj->beforeCopy($uidFrom, $uidTo, $datamap, $tce);
            }
        }

        // Overwrite locales
        if (!empty($locale)) {
            foreach ($locale as $loc) {
                self::overwriteLocale($table, $uidFrom, $uidTo, $loc);
            }
        }

        // overwrite articles which are existing - do NOT delete articles
        // that are not in the overwritten product but in the overwriting one
        $articlesFrom = self::getArticlesOfProduct($uidFrom);

        if (is_array($articlesFrom)) {
            // the product has articles - check if they exist in the overwritten product
            $articlesTo = self::getArticlesOfProduct($uidTo);

            // simply copy if the overwritten product does not have articles
            if (false === $articlesTo || !is_array($articlesTo)) {
                self::copyArticlesByProduct($uidTo, $uidFrom, $locale);
            } else {
                // go through each article of the overwriting product
                // and check if it exists in the overwritten product
                $l = count($articlesFrom);
                $m = count($articlesTo);

                // walk the articles
                for ($i = 0; $i < $l; ++$i) {
                    $overwrite = false;
                    $uid = $articlesFrom[$i]['uid'];

                    // check if we need to overwrite
                    for ($j = 0; $j < $m; ++$j) {
                        if ($articlesFrom[$i]['ordernumber'] == $articlesTo[$j]['ordernumber']) {
                            $overwrite = true;
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

        return true;
    }

    /**
     * Retrieves the data object to make an overwrite.
     *
     * @param string $table Tablename
     * @param int $uidFrom Uid of the record we which to retrieve the data from
     * @param int $destPid Uid of the record we want to overwrite
     *
     * @return array
     */
    public function getOverwriteData($table, $uidFrom, $destPid)
    {
        /**
         * Data handler.
         *
         * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
         */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        $backendUser = self::getBackendUser();

        $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
        if (is_array($tcaDefaultOverride)) {
            $tce->setDefaultsFromUserTS($tcaDefaultOverride);
        }

        $tce->start(array(), array());

        $first = 0;
        $language = 0;
        $uid = $origUid = (int) $uidFrom;

        $tableConfig = SettingsFactory::getInstance()->getTcaValue($table);
        // Only copy if the table is defined in TCA, a uid is given
        if ($tableConfig && $uid) {
            // This checks if the record can be selected
            // which is all that a copy action requires.
            $data = array();

            $nonFields = array_unique(GeneralUtility::trimExplode(
                ',',
                'uid, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody, t3ver_oid, t3ver_wsid,
                    t3ver_id, t3ver_label, t3ver_state, t3ver_swapmode, t3ver_count, t3ver_stage, t3ver_tstamp,',
                1
            ));

            // So it copies (and localized) content from workspace...
            $row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
            if (is_array($row)) {
                // Initializing:
                $theNewId = $destPid;
                $enableField = isset($tableConfig['ctrl']['enablecolumns']) ?
                    $tableConfig['ctrl']['enablecolumns']['disabled'] :
                    '';
                $headerField = $tableConfig['ctrl']['label'];

                // Getting default data:
                $defaultData = $tce->newFieldArray($table);

                // Getting "copy-after" fields if applicable:
                $copyAfterFields = array();

                // Page TSconfig related:
                // NOT using BackendUtility::getTSCpid() because we need the
                // real pid - not the ID of a page, if the input is a page...
                $tscPid = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSconfig_pidValue($table, $uid, -$destPid);
                $tsConfig = $tce->getTCEMAIN_TSconfig($tscPid);
                $tE = $tce->getTableEntries($table, $tsConfig);

                // Traverse ALL fields of the selected record:
                foreach ($row as $field => $value) {
                    if (!in_array($field, $nonFields)) {
                        // Get TCA configuration for the field:
                        $conf = $tableConfig['columns'][$field]['config'];

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
                        } elseif ($tableConfig['ctrl']['setToDefaultOnCopy']
                            && GeneralUtility::inList($tableConfig['ctrl']['setToDefaultOnCopy'], $field)
                        ) {
                            $value = $defaultData[$field];
                        } else {
                            // Hide at copy may override:
                            if ($first
                                && $field == $enableField
                                && $tableConfig['ctrl']['hideAtCopy']
                                && !$tce->neverHideAtCopy
                                && !$tE['disableHideAtCopy']
                            ) {
                                $value = 1;
                            }

                            // Prepend label on copy:
                            if ($first
                                && $field == $headerField
                                && $tableConfig['ctrl']['prependAtCopy']
                                && !$tE['disablePrependAtCopy']
                            ) {
                                // @todo this can't work resolvePid and clearPrefixFromValue are not implement
                                // wasn't present bevor 0.11.x and was broken from the beginning
                                $value = $tce->getCopyHeader(
                                    $table,
                                    $this->resolvePid($table, $destPid),
                                    $field,
                                    $this->clearPrefixFromValue($table, $value),
                                    0
                                );
                            }
                            // Processing based on the TCA config field
                            // type (files, references, flexforms...)
                            $value = $tce->copyRecord_procBasedOnFieldType(
                                $table,
                                $uid,
                                $field,
                                $value,
                                $row,
                                $conf,
                                $tscPid,
                                $language
                            );
                        }

                        // Add value to array.
                        $data[$table][$theNewId][$field] = $value;
                    }
                }

                // Overriding values:
                if ($tableConfig['ctrl']['editlock']) {
                    $data[$table][$theNewId][$tableConfig['ctrl']['editlock']] = 0;
                }

                // Setting original UID:
                if ($tableConfig['ctrl']['origUid']) {
                    $data[$table][$theNewId][$tableConfig['ctrl']['origUid']] = $uid;
                }

                return $data;
            }
        }

        return array();
    }

    /**
     * Overwrites the article.
     *
     * @param int $uidFrom Uid of article that provides the new data
     * @param int $uidTo Uid of article that is to be overwritten
     * @param array $locale Uids of sys_languages to overwrite
     *
     * @return bool Success
     */
    public function overwriteArticle($uidFrom, $uidTo, array $locale = null)
    {
        $table = 'tx_commerce_articles';

        // check params
        if (!is_numeric($uidFrom) || !is_numeric($uidTo)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyArticle (belib) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        // check show right for overwriting article
        $prodFrom = self::getProductOfArticle($uidFrom);

        if (!self::checkProductPerms($prodFrom['uid'], 'show')) {
            return false;
        }

        // check editcontent right for overwritten article
        $prodTo = self::getProductOfArticle($uidTo);

        if (!self::checkProductPerms($prodTo['uid'], 'editcontent')) {
            return false;
        }

        // get the records
        $recordFrom = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uidFrom, '*');
        $recordTo = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uidTo, '*');

        if (!$recordFrom || !$recordTo) {
            return false;
        }

        $data = self::getOverwriteData($table, $uidFrom, $uidTo);

        unset(
            $data[$table][$uidTo]['uid'],
            $data[$table][$uidTo]['cruser_id'],
            $data[$table][$uidTo]['crdate'],
            $data[$table][$uidTo]['uid_product']
        );

        $datamap = $data;

        // execute
        /**
         * Data handler.
         *
         * @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce
         */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        $backendUser = self::getBackendUser();

        $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
        if (is_array($tcaDefaultOverride)) {
            $tce->setDefaultsFromUserTS($tcaDefaultOverride);
        }
        $tce->start($datamap, array());

        // Write to session that we copy
        // this is used by the hook to the datamap class
        // to figure out if it should call the dynaflex
        // so far this is the best (though not very clean)
        // way to solve the issue we get when saving an article
        $backendUser->uc['txcommerce_copyProcess'] = 1;
        $backendUser->writeUC();

        $tce->process_datamap();

        // copying done, clear session
        $backendUser->uc['txcommerce_copyProcess'] = 0;
        $backendUser->writeUC();

        // overwrite locales
        if (is_array($locale) && !empty($locale)) {
            foreach ($locale as $loc) {
                self::overwriteLocale($table, $uidFrom, $uidTo, $loc);
            }
        }

        return true;
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected static function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

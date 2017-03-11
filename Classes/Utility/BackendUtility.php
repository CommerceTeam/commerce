<?php
namespace CommerceTeam\Commerce\Utility;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository;
use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Domain\Repository\PageRepository;
use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This metaclass provides several helper methods for handling relations in
 * the backend. This is quite useful because attributes for an article can
 * come from products and categories. And products can be assigned to several
 * categories and a category can have a lot of parent categories.
 *
 * Class \CommerceTeam\Commerce\Utility\BackendUtility
 */
class BackendUtility
{
    /**
     * Fetches all attribute relation from the database that are assigned to a
     * product specified through pUid. It can also fetch information about the
     * attribute and a list of attribute values if the attribute has a valuelist.
     *
     * @param int $productUid Uid of the product
     * @param bool $separateCorrelationType1 If this is true, all attributes with ct1
     *      will be saved in a separated result section
     * @param bool $addAttributeData If true, all information about the
     *      attributes will be fetched from the database (default is false)
     * @param bool $getValueListData If this is true and additional data is
     *      fetched and an attribute has a valuelist, this gets the values for the
     *      list (default is false)

     * @return array of attributes
     */
    public function getAttributesForProduct(
        $productUid,
        $separateCorrelationType1 = false,
        $addAttributeData = false,
        $getValueListData = false
    ) {
        if (!$productUid) {
            return [];
        }

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);
        $correlationTypes = $attributeRepository->findAllCorrelationTypes();

        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        // get all attributes for the product
        $relations = $productRepository->getUniqueAttributeRelations($productUid);

        // prepare result array
        if ($separateCorrelationType1) {
            $result = [
                'ct1' => [],
                'rest' => [],
                'grouped' => [],
            ];
            foreach ($correlationTypes as $correlationType) {
                $result['grouped']['ct_' . $correlationType['uid']] = [];
            }
        } else {
            $result = [];
        }

        foreach ($relations as $relation) {
            if ($addAttributeData) {
                // fetch the data from the attribute table
                $attribute = $attributeRepository->findByUid($relation['uid_foreign']);
                $relation['attributeData'] = !empty($attribute) ? $attribute : '';
                if ($attribute['has_valuelist'] && $getValueListData) {
                    // fetch values for this valuelist entry
                    $relation['valueList'] = $attributeRepository->findValuesByAttribute($attribute['uid']);
                }

                $relation['has_valuelist'] = $attribute['has_valuelist'] ? '1' : '0';
            }

            if (empty($relation)) {
                continue;
            }

            if ($separateCorrelationType1) {
                if ($relation['uid_correlationtype'] == 1 && $relation['attributeData']['has_valuelist'] == 1) {
                    $result['ct1'][] = $relation;
                } else {
                    $result['rest'][] = $relation;
                }
                $result['grouped']['ct_' . $relation['uid_correlationtype']][] = $relation;
            } else {
                $result[] = $relation;
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
        $cUid = (int)$cUid;
        if ($cUid > 0) {
            // add the submitted uid to the list if it is bigger
            // than 0 and not already in the list
            if ($cUid != $excludeUid) {
                if (!in_array($cUid, $cUidList) && $cUid != $dontAdd) {
                    $cUidList[] = $cUid;
                }

                /** @var CategoryRepository $categoryRepository */
                $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
                $categories = $categoryRepository->getParentCategories($cUid);
                foreach ($categories as $category) {
                    if ($recursive) {
                        $this->getParentCategories($category, $cUidList, $cUid, $excludeUid);
                    } else {
                        if (!in_array($category, $cUidList) && $category != $dontAdd) {
                            $cUidList[] = $category;
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
            if (!in_array($cUid, $categoryUidList) && $cUid != $dontAdd) {
                $categoryUidList[] = $cUid;
            }

            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
            $categories = $categoryRepository->findByParentCategoryUid($cUid);

            if (!empty($categories)) {
                foreach ($categories as $relData) {
                    if ($recursive) {
                        $this->getChildCategories($relData['uid'], $categoryUidList, $cUid, $excludeUid);
                    } else {
                        $cUid = $relData['uid'];
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
     * Returns all attributes for a list of categories.
     *
     * @param array $catList List of category UIDs
     *
     * @return array of attributes
     */
    public function getAttributesForCategoryList(array $catList)
    {
        $result = [];
        if (!is_array($catList)) {
            return $result;
        }

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        foreach ($catList as $catUid) {
            $attributes = $categoryRepository->findAttributesByCategoryUid($catUid, $result);
            if (is_array($attributes)) {
                foreach ($attributes as $attribute) {
                    $result[] = $attribute;
                }
            }
        }

        return $result;
    }

    /* ARTICLES */

    /**
     * Returns all attributes for an article.
     *
     * @param int $articleUid Article UID
     * @param int $ct Correlationtype
     * @param array $excludeAttributes Relation datasets where the
     *      field "uid_foreign" is the UID of the attribute you don't want to get
     *
     * @return array of attributes
     */
    public function getAttributesForArticle($articleUid, $ct = null, array $excludeAttributes = [])
    {
        // should we exclude some attributes
        $excludeList = [];
        if (is_array($excludeAttributes) && !empty($excludeAttributes)) {
            foreach ($excludeAttributes as $excludeAttribute) {
                $excludeList[] = (int) $excludeAttribute['uid_foreign'];
            }
        }

        if ($ct != null) {
            /** @var ProductRepository $productRepository */
            $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
            $product = $productRepository->findByArticleUid($articleUid);

            $productAttributes = $this->getAttributesForProduct((int)$product['uid']);
            $ctAttributes = [];
            if (!empty($productAttributes)) {
                foreach ($productAttributes as $productAttribute) {
                    if ($productAttribute['uid_correlationtype'] == $ct) {
                        $ctAttributes[] = $productAttribute['uid_foreign'];
                    }
                }
                if (!empty($ctAttributes)) {
                    $excludeList = array_merge($excludeList, $ctAttributes);
                }
            }
        }

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);
        $result = $attributeRepository->findByArticleAndExcludingListed($articleUid, $excludeList);
        foreach ($result as &$row) {
            if ($row['has_valuelist']) {
                $row['valueList'] = $attributeRepository->findValuesByAttribute($row['uid']);
            }
        }

        return $result;
    }

    /**
     * Returns the hash value for the ct1 select attributes for an article.
     *
     * @param int $articleUid Uid of the article
     * @param array $fullAttributeList List of uids for the attributes
     *      that are assigned to the article
     *
     * @return string
     */
    public function getArticleHash($articleUid, array $fullAttributeList)
    {
        $hashData = [];

        if (!empty($fullAttributeList)) {
            /** @var ArticleRepository $articleRepository */
            $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
            $attributes = $articleRepository->getAttributeRelationsByArticleAndAttributeUid(
                $articleUid,
                $fullAttributeList
            );

            foreach ($attributes as $attributeData) {
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
     * @param int $articleUid Uid of the article
     * @param array $fullAttributeList The list of uids for the attributes
     *      that are assigned to the article
     *
     * @return void
     */
    public function updateArticleHash($articleUid, array $fullAttributeList = [])
    {
        if ($fullAttributeList == null) {
            $fullAttributeList = [];
            $articleAttributes = $this->getAttributesForArticle($articleUid, 1);
            if (!empty($articleAttributes)) {
                foreach ($articleAttributes as $articleAttribute) {
                    $fullAttributeList[] = $articleAttribute['uid_foreign'];
                }
            }
        }

        $hash = $this->getArticleHash($articleUid, $fullAttributeList);

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articleRepository->updateAttributeHash($articleUid, $hash);
    }

    /* Diverse */

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
    public static function getUidFromKey($key, array &$keyData)
    {
        if (strpos($key, '_') === false) {
            $uid = $key;
        } else {
            $keyData = explode('_', $key);
            $uid = array_pop($keyData);
            $keyData[] = $uid;
        }

        return (int) $uid;
    }

    /**
     * Searches for a string in an array of arrays.
     *
     * @param string $needle Search value
     * @param array $rows Data to search in
     * @param string $field Fieldname of the inside arrays in the search array

     * @return bool if the needle was found, otherwise false
     */
    public function checkArray($needle, array $rows, $field)
    {
        $result = false;

        foreach ($rows as $row) {
            if ($needle == $row[$field]) {
                $result = true;
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
        $keyData = [];
        $result = [];
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
        $delWhere = [];
        $counter = 1;

        /** @var AttributeRepository|CategoryRepository|ProductRepository $repository */
        $repository = null;
        switch ($relationTable) {
            case 'tx_commerce_articles_attributes_mm':
                $repository = GeneralUtility::makeInstance(AttributeRepository::class);
                break;
            case 'tx_commerce_categories_attributes_mm':
                $repository = GeneralUtility::makeInstance(AttributeRepository::class);
                break;
            case 'tx_commerce_categories_parent_category_mm':
                $repository = GeneralUtility::makeInstance(CategoryRepository::class);
                break;
            case 'tx_commerce_products_categories_mm':
                $repository = GeneralUtility::makeInstance(ProductRepository::class);
                break;
            case 'tx_commerce_products_attributes_mm':
                $repository = GeneralUtility::makeInstance(AttributeRepository::class);
                break;
        }

        if (is_array($relationData)) {
            foreach ($relationData as $relation) {
                $where = 'uid_local = ' . (int) $uidLocal;
                $dataArray = ['uid_local' => (int) $uidLocal];

                if (is_array($relation)) {
                    foreach ($relation as $key => $data) {
                        $dataArray[$key] = $data;
                        $where .= ' AND ' . $key . ' = \'' . $data . '\'';
                    }
                }
                if ($withReference && ($counter > 1)) {
                    $dataArray['is_reference'] = 1;
                    $where .= ' AND is_reference = 1';
                }

                $dataArray['sorting'] = $counter;
                ++$counter;

                $exists = $repository->countWithTableAndWhere($relationTable, $where);

                if ($exists) {
                    $repository->updateWithTable($relationTable, $where, ['sorting' => $counter]);
                } else {
                    $repository->insertWithTable($relationTable, $dataArray);
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

        if ($delete) {
            $where = '';
            if (!empty($delWhere)) {
                $where = ' AND NOT ((' . implode(') OR (', $delWhere) . '))';
            }
            $repository->deleteWithTable($relationTable, 'uid_local = ' . $uidLocal . $where);
        }
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
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        $xmlData = [];
        if ($add && is_numeric($articleUid)) {
            $xmlData = $articleRepository->findByUid($articleUid);
            if (is_array($xmlData)) {
                $xmlData = GeneralUtility::xml2array($xmlData['attributesedit']);
            } else {
                $xmlData = '';
            }
        }

        $relationData = [];
        /*
         * Get Relation Data
         */
        if ($articleUid) {
            $relationData = $articleRepository->getAttributeRelationsByArticleUid($articleUid);
        }
        if ($productUid) {
            $relationData = $articleRepository->getAttributeRelationsByProductUid($productUid);
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

                $xmlData['data']['sDEF']['lDEF']['attribute_' . $articleRelation['uid_foreign']] = ['vDEF' => $value];
            }
        }

        $xmlData = GeneralUtility::array2xml($xmlData, '', 0, 'T3FlexForms');

        if ($articleUid) {
            $articleRepository->updateRecord($articleUid, ['attributesedit' => $xmlData]);
        } elseif ($productUid) {
            $articleRepository->updateByProductUid($productUid, ['attributesedit' => $xmlData]);
        }
    }

    /**
     * Updates the XML of an FlexForm value field. This is almost the same as
     * "updateArticleXML" but more general.
     *
     * @param string $xmlField Fieldname where the FlexForm values are stored
     * @param string $table Table in which the FlexForm values are stored
     * @param int $uid UID of the entity inside the table
     * @param string $_ Type of data we are handling (category, product)
     * @param array $ctList A list of correlationtype UID we should handle
     * @param bool $rebuild Wether the xmlData should be rebuild or not
     *
     * @return array
     */
    public function updateXML($xmlField, $table, $uid, $_, array $ctList, $rebuild = false)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        $relList = null;
        switch ($table) {
            case 'tx_commerce_categories':
                $relList = $categoryRepository->findAttributesByCategoryUid($uid);
                $xmlData = $categoryRepository->findByUid($uid);
                break;

            case 'tx_commerce_products':
                $relList = $this->getAttributesForProduct($uid);
                $xmlData = $productRepository->findByUid($uid);
                break;

            default:
                $xmlData = [$xmlField => ''];
        }

        $xmlData = GeneralUtility::xml2array($xmlData[$xmlField]);
        if (!is_array($xmlData)) {
            $xmlData = [];
        }

        $cTypes = [];

        // write the data
        if (is_array($ctList)) {
            foreach ($ctList as $ct) {
                $value = [];
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
                        ['vDEF' => $value];
                }
            }
        }

        // rebuild
        if ($rebuild && !empty($cTypes) && is_array($ctList)) {
            foreach ($ctList as $ct) {
                if (!in_array($ct['uid'], $cTypes)) {
                    $xmlData['data']['sDEF']['lDEF']['ct_' . (string) $ct['uid']] = ['vDEF' => ''];
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

        if ($table === 'tx_commerce_products') {
            $productRepository->updateRecord($uid, [$xmlField => $xmlData]);
        } elseif ($table === 'tx_commerce_categories') {
            $categoryRepository->updateRecord($uid, [$xmlField => $xmlData]);
        }

        return [$xmlField => $xmlData];
    }

    /**
     * Merges an attribute list from a flexform together into one array.
     *
     * @param array $flexformData FlexForm data as array
     * @param string $prefix Prefix that is used for the correlationtypes
     * @param array $correlationTypesList List of correlations that should be processed
     * @param int $uidLocal Field in the relation table
     * @param array $paList List of product attributes (PASSED BY REFERENCE)
     */
    public function mergeAttributeListFromFlexFormData(
        array $flexformData,
        $prefix,
        array $correlationTypesList,
        $uidLocal,
        array &$paList
    ) {
        if (is_array($correlationTypesList)) {
            foreach ($correlationTypesList as $ctUid) {
                $ffaList = $flexformData[$prefix . $ctUid['uid']]['vDEF'];
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
                            $newRel = [
                                'uid_local' => $uidLocal,
                                'uid_foreign' => $aUid,
                                'uid_correlationtype' => $ctUid['uid'],
                            ];

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
    public static function extractFieldArray(array $array, $field, $makeArray = false, array $extraFields = [])
    {
        $result = [];
        if (is_array($array)) {
            foreach ($array as $data) {
                if (!is_array($data) || (is_array($data) && !array_key_exists($field, $data))) {
                    $item[$field] = $data;
                } else {
                    $item = $data;
                }
                if ($makeArray) {
                    $newItem = [$field => $item[$field]];
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
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        return $productRepository->findByCategoryUid($categoryUid);
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
        $updateArray = [];
        $updateArray['uid_valuelist'] = '';
        $updateArray['default_value'] = '';
        $updateArray2 = $updateArray;
        $updateArray2['value_char'] = '';
        $updateArray2['uid_product'] = $productUid;

        if ($attributeData['has_valuelist'] == 1) {
            $updateArray['uid_valuelist'] = $data;
            $updateArray2['uid_valuelist'] = $data;
        } else {
            if (MathUtility::canBeInterpretedAsInteger($data)) {
                $updateArray['default_value'] = $data;
                $updateArray2['default_value'] = $data;
            } else {
                $updateArray['default_value'] = $data;
                $updateArray2['value_char'] = $data;
            }
        }

        return [$updateArray, $updateArray2];
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
            switch (ConfigurationUtility::getInstance()->getExtConf('attributeLocalizationType')) {
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
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $prices = $articleRepository->findByUid($articleUid);

        if (strlen($prices['prices']) > 0) {
            $data = GeneralUtility::xml2array($prices['prices']);
        } else {
            $data = ['data' => ['sDEF' => ['lDEF']]];
        }

        $data['data']['sDEF']['lDEF']['price_net_' . $priceUid] = [
            'vDEF' => sprintf('%.2f', ($priceDataArray['price_net'] /100))
        ];
        $data['data']['sDEF']['lDEF']['price_gross_' . $priceUid] = [
            'vDEF' => sprintf('%.2f', ($priceDataArray['price_gross'] /100))
        ];
        $data['data']['sDEF']['lDEF']['hidden_' . $priceUid] = ['vDEF' => $priceDataArray['hidden']];
        $data['data']['sDEF']['lDEF']['starttime_' . $priceUid] = ['vDEF' => $priceDataArray['starttime']];
        $data['data']['sDEF']['lDEF']['endtime_' . $priceUid] = ['vDEF' => $priceDataArray['endtime']];
        $data['data']['sDEF']['lDEF']['fe_group_' . $priceUid] = ['vDEF' => $priceDataArray['fe_group']];
        $data['data']['sDEF']['lDEF']['purchase_price_' . $priceUid] = [
            'vDEF' => sprintf('%.2f', ($priceDataArray['purchase_price'] /100))
        ];
        $data['data']['sDEF']['lDEF']['price_scale_amount_start_' . $priceUid] = [
            'vDEF' => $priceDataArray['price_scale_amount_start']
        ];
        $data['data']['sDEF']['lDEF']['price_scale_amount_end_' . $priceUid] = [
            'vDEF' => $priceDataArray['price_scale_amount_end']
        ];

        $xml = GeneralUtility::array2xml($data, '', 0, 'T3FlexForms');

        return $articleRepository->updateRecord($articleUid, ['prices' => $xml]);
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
        $returnArray = [];
        /*
         * Query the table to build dropdown list
         */
        $prep = '';
        for ($i = 0; $i < $aktLevel; ++$i) {
            $prep .= '- ';
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $rows = $pageRepository->findByPid($pid);
        if (!empty($rows)) {
            foreach ($rows as $returnData) {
                $returnData['title'] = $prep . $returnData['title'];

                $returnArray[] = [$returnData['title'], $returnData['uid']];
                $tmparray = self::getOrderFolderSelector($returnData['uid'], $levels - 1, $aktLevel + 1);
                if (is_array($tmparray)) {
                    $returnArray = array_merge($returnArray, $tmparray);
                }
            }
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
        /** @var ArticlePriceRepository $articlePriceRepository */
        $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);
        $prices = $articlePriceRepository->findByArticleUid($articleUid);

        $data = [
            'data' => [
                'sDEF' => ['lDEF']
            ]
        ];
        $lDef = &$data['data']['sDEF']['lDEF'];
        foreach ($prices as $price) {
            $priceUid = $price['uid'];

            $lDef['price_net_' . $priceUid] = ['vDEF' => sprintf('%.2f', ($price['price_net'] / 100))];
            $lDef['price_gross_' . $priceUid] = ['vDEF' => sprintf('%.2f', ($price['price_gross'] / 100))];
            $lDef['purchase_price_' . $priceUid] = ['vDEF' => sprintf('%.2f', ($price['purchase_price'] / 100))];
            $lDef['hidden_' . $priceUid] = ['vDEF' => $price['hidden']];
            $lDef['starttime_' . $priceUid] = ['vDEF' => $price['starttime']];
            $lDef['endtime_' . $priceUid] = ['vDEF' => $price['endtime']];
            $lDef['fe_group_' . $priceUid] = ['vDEF' => $price['fe_group']];
            $lDef['price_scale_amount_start_' . $priceUid] = ['vDEF' => $price['price_scale_amount_start']];
            $lDef['price_scale_amount_end_' . $priceUid] = ['vDEF' => $price['price_scale_amount_end']];
        }

        $xml = GeneralUtility::array2xml($data, '', 0, 'T3FlexForms');

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        return $articleRepository->updateRecord($articleUid, ['prices' => $xml]);
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
                    'commerce',
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

        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);

        if (!$copy) {
            // update the mm table with the new uids of the product
            $success = $productRepository->updateAttributeRelation($pUidFrom, $pUidTo) == '';
        } else {
            // copy the attributes - or, get all values from the original product
            // relation and insert them with the new uid_local
            $rows = $productRepository->findAttributeRelationByProductUid($pUidFrom);

            $success = true;
            foreach ($rows as $row) {
                $success = $productRepository->addAttributeRelation($pUidTo, $row) == '';
            }
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
                    'commerce',
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

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        if (!$copy) {
            // give all articles of the old
            // product the product_uid of the new product
            $success = $articleRepository->updateProductUid($pUidFrom, $pUidTo) == '';
        } else {
            // copy the articles - or, read all article uids of the
            // old product and invoke the copy command
            $rows = $articleRepository->findByProductUid($pUidFrom);

            $success = true;
            foreach ($rows as $row) {
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
    public function copyArticle($uid, $uidProduct, array $locale = [])
    {
        $backendUser = self::getBackendUserAuthentication();

        // check params
        if (!is_numeric($uid) || !is_numeric($uidProduct)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyArticle (belib) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        // check show right for this article under
        // all categories of the current parent product
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $product = $productRepository->findByArticleUid($uid);

        if (!self::checkProductPerms((int)$product['uid'], 'show')) {
            return false;
        }

        // check editcontent right for this article under
        // all categories of the new parent product
        if (!self::checkProductPerms($uidProduct, 'editcontent')) {
            return false;
        }

        // get uid of the last article in the articles table
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $article = $articleRepository->findLatestArticle();

        // if there are no articles at all, abort.
        if (empty($article)) {
            return false;
        }

        // uid of the last article (after this article we will copy the new article
        $uidLast = (int)$article['uid'];

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
        $tce->start([], []);

        // invoke the copy manually so we can actually override the uid_product field
        $overrideArray = ['uid_product' => $uidProduct];

        // Write to session that we copy and not get stuck in a loop
        $backendUser->uc['txcommerce_copyProcess'] = 1;
        $backendUser->writeUC();

        $newUid = $tce->copyRecord('tx_commerce_articles', $uid, -$uidLast, 1, $overrideArray);

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
        $backendUser = self::getBackendUserAuthentication();

        // select all existing prices of the article
        /** @var ArticlePriceRepository $articlePriceRepository */
        $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);
        $rows = $articlePriceRepository->findByArticleUid($uidFrom);

        $newUid = 0;
        foreach ($rows as $row) {
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
            $tce->start([], []);

            // invoke the copy manually so we can actually override the uid_product field
            $overrideArray = ['uid_article' => $uidTo];

            // settings this user cache value prevents recursive copy actions that could happen
            // due to hooks while database actions of copyRecord
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
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        if ($languageUid != 0) {
            // we want to overwrite the attributes of the locale
            // replace $uidFrom and $uidTo with their localized versions
            $uids = [$uidFrom, $uidTo];
            $newFrom = $uidFrom;
            $newTo = $uidTo;
            $rows = $articleRepository->findTranslationsByParentUidsAndLanguage($uids, $languageUid);

            // get uids
            foreach ($rows as $row) {
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

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);

        // delete existing attributes
        $attributeRepository->deleteAttributeRelationsByArticleUid($uidTo);

        // copy the attributes
        $rows = $attributeRepository->findEmptyAttributesByArticle($uidFrom);
        foreach ($rows as $origRelation) {
            $origRelation['uid_local'] = $uidTo;
            $attributeRepository->insertRelation($origRelation);
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
                GeneralUtility::devLog('copyProduct (belib) gets passed invalid parameters.', 'commerce', 3);
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

        $backendUser = self::getBackendUserAuthentication();

        if ($sorting == 0) {
            // get uid of the last product in the products table
            /** @var ProductRepository $productRepository */
            $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
            $row = $productRepository->findLatestProduct();

            // if there are no products at all, abort.
            if (empty($row)) {
                return false;
            }

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
        $tce->start([], []);

        // invoke the copy manually so we can actually override the categories field
        $overrideArray = ['categories' => $categoryUid];

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
                GeneralUtility::devLog('copyLocale (belib) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        $backendUser = self::getBackendUserAuthentication();

        $tableConfig = ConfigurationUtility::getInstance()->getTcaValue($table);
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
                /** @var ProductRepository $productRepository */
                $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
                $row = $productRepository->findTranslationsByParentUidAndLanguage($productUid, $languageUid);

                $rec[0]['uid_product'] = $row['uid'];
            }

            $data = [];

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
            $tce->start($data, []);

            // Write to session that we copy to not get stuck in a loop
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
        $backendUser = $this->getBackendUserAuthentication();

        // check params
        if (!is_string($table) || !is_numeric($uidCopied) || !is_numeric($uidOverwrite) || !is_numeric($loc)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('copyLocale (belib) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        $tableConfig = ConfigurationUtility::getInstance()->getTcaValue($table);
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

            $data = [];
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
            $tce->start($data, []);

            // Write to session that we copy to not get stuck in a loop
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
                GeneralUtility::devLog('copyCategory (belib) gets passed invalid parameters.', 'commerce', 3);
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

        if (0 == $sorting) {
            // get uid of the last category in the category table
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
            $row = $categoryRepository->findLatestCategory();

            // if there are no categories at all, abort.
            if (empty($row)) {
                return false;
            }

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

        $backendUser = self::getBackendUserAuthentication();

        $tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
        if (is_array($tcaDefaultOverride)) {
            $tce->setDefaultsFromUserTS($tcaDefaultOverride);
        }

        // start
        $tce->start([], []);

        // invoke the copy manually so we can actually override the categories field
        $overrideArray = ['parent_category' => $parentUid];

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
*@param int $categoryUidTo Uid of the category to chmod
     * @param int $categoryUidFrom Uid of the category from which we take the perms
 * @return bool Success
     */
    public function chmodCategoryByCategory($categoryUidTo, $categoryUidFrom)
    {
        // check params
        if (!is_numeric($categoryUidTo) || !is_numeric($categoryUidFrom)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'chmodCategoryByCategory (BackendUtility) gets passed invalid parameters.',
                    'commerce',
                    3
                );
            }

            return false;
        }

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        // select current perms
        $row = $categoryRepository->findByUid(
            $categoryUidFrom,
            self::getCategoryPermsClause(self::getPermMask('show'))
        );

        $result = false;
        if (!empty($row)) {
            // apply the permissions
            $updateData = [
                'perms_everybody' => $row['perms_everybody'],
                'perms_group' => $row['perms_group'],
                'perms_user' => $row['perms_user'],
                'perms_userid' => $row['perms_userid'],
                'perms_groupid' => $row['perms_groupid'],
            ];

            $result = $categoryRepository->updateRecord($categoryUidTo, $updateData);
        }

        return $result !== false;
    }

    /**
     * Returns the pid new pid for the copied item - this is only used when
     * inserting a record on front of another.
     *
     * @param string $table Table from which we want to read
     * @param int $uid Uid of the record that we want to move our element to in front of it
     *
     * @return int
     */
    public function getCopyPid($table, $uid)
    {
        $row = [];
        if ($table === 'tx_commerce_products') {
            /** @var ProductRepository $productRepository */
            $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
            $row = $productRepository->findPreviousByUid($uid);
        } elseif ($table === 'tx_commerce_categories') {
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
            $row = $categoryRepository->findPreviousByUid($uid);
        }

        if (!empty($row)) {
            $pid = -$row['uid'];
        } else {
            // the item we want to skip is the item with the lowest
            // sorting - use pid of the 'Product' Folder
            $pid = FolderRepository::initFolders('Products', FolderRepository::initFolders());
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
                    'commerce',
                    3
                );
            }

            return false;
        }

        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $products = $productRepository->findByCategoryUid($catUidFrom);

        $success = true;
        foreach ($products as $product) {
            $productCopied = self::copyProduct($product['uid'], $catUidTo, true, $locale);
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
                    'commerce',
                    3
                );
            }

            return false;
        }

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $categoryRepository->findByParentCategoryUid($catUidFrom);

        $success = true;
        foreach ($categories as $category) {
            $categoryCopied = self::copyCategory($category['uid'], $catUidTo, $locale);
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
                    'commerce',
                    3
                );
            }

            return false;
        }

        $success = true;

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $rows = $articleRepository->findByProductUid($prodUidFrom);
        foreach ($rows as $row) {
            $singleSuccess = self::copyArticle($row['uid'], $prodUidTo, (array) $locale);
            $success = $success ? $singleSuccess : $success;
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
        $backendUser = self::getBackendUserAuthentication();
        /** @noinspection PhpInternalEntityUsedInspection */
        $backendUserData = $backendUser->user;

        if (is_array($backendUserData)) {
            if ($backendUser->isAdmin()) {
                return ' 1 = 1';
            }
            $perms = (int) $perms;
            // Make sure it's integer.
            $str = ' (' .
                // Everybody
                '(tx_commerce_categories.perms_everybody & ' . $perms . ' = ' . $perms . ')' .
                // User
                /** @noinspection PhpInternalEntityUsedInspection */
                'OR(tx_commerce_categories.perms_userid = ' . $backendUserData['uid'] .
                    ' AND tx_commerce_categories.perms_user & ' . $perms . ' = ' . $perms . ')';
            if ($backendUser->groupList) {
                // Group (if any is set)
                $str .= 'OR(tx_commerce_categories.perms_groupid in (' . $backendUser->groupList .
                    ') AND tx_commerce_categories.perms_group & ' . $perms . ' = ' . $perms . ')';
            }
            $str .= ')';

            return $str;
        } else {
            return ' 1 = 0';
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

        $backendUser = self::getBackendUserAuthentication();

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
            /** @noinspection PhpInternalEntityUsedInspection */
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
                GeneralUtility::devLog('getPermMask (belib) gets passed invalid parameters.', 'commerce', 3);
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
                GeneralUtility::devLog('checkProductPerms (belib) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        // get mask
        $mask = self::getPermMask($perm);

        if (!$mask) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'checkProductPerms (belib) gets passed an invalid permission to check for.',
                    'commerce',
                    3
                );
            }

            return false;
        }

        // get parent categories
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $parents = $productRepository->getParentCategories($uid);

        // check the permissions
        if (!empty($parents)) {
            foreach ($parents as $parent) {
                // @todo discuss if realy permission to all parents needs to be set
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
        if ((string) $id != '') {
            $id = (int) $id;
            if (!$id) {
                if (static::getBackendUserAuthentication()->isAdmin()) {
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

        return [];
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
            return [$output, $fullOutput];
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
        static $backendGetRootLineCache = [];

        $output = [];
        $pid = $uid;
        $ident = $pid . '-' . $clause . '-' . $workspaceOl;

        if (is_array($backendGetRootLineCache[$ident])) {
            $output = $backendGetRootLineCache[$ident];
        } else {
            $loopCheck = 100;
            $theRowArray = [];
            while ($uid != 0 && $loopCheck) {
                $loopCheck--;
                if ($loopCheck == 99) {
                    $row = self::getProductForRootline($uid, $clause, $workspaceOl);
                }
                if (empty($row) || $loopCheck < 99) {
                    $row = self::getCategoryForRootline($uid, $clause, $workspaceOl);
                }

                if (is_array($row)) {
                    $uid = $row['pid'];
                    $theRowArray[] = $row;
                } else {
                    break;
                }
            }

            if ($uid == 0) {
                $theRowArray[] = ['uid' => 0, 'title' => ''];
            }
            $c = count($theRowArray);

            foreach ($theRowArray as $val) {
                --$c;
                $output[$c] = [
                    'uid' => $val['uid'],
                    'pid' => $val['pid'],
                    'title' => $val['title'],
                    'ts_config' => $val['ts_config'],
                    't3ver_oid' => $val['t3ver_oid'],
                    't3ver_wsid' => $val['t3ver_wsid'],
                    't3ver_state' => $val['t3ver_state'],
                    't3ver_stage' => $val['t3ver_stage'],
                ];
                if (isset($val['_ORIG_pid'])) {
                    $output[$c]['_ORIG_pid'] = $val['_ORIG_pid'];
                }
            }
            $backendGetRootLineCache[$ident] = $output;
        }

        return $output;
    }

    /**
     * Gets the cached category record for the rootline.
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
        static $getCategoryForRootlineCache = [];
        $ident = $uid . '-' . $clause . '-' . $workspaceOl;

        if (!isset($getCategoryForRootlineCache[$ident])) {
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
            $row = $categoryRepository->findRootlineCategoryByUid($uid, $clause);
            if (!empty($row)) {
                if ($workspaceOl) {
                    \TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tx_commerce_categories', $row);
                }
                if (is_array($row)) {
                    \TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid('tx_commerce_categories', $row);
                }
                $row['_is_category'] = true;
            }

            $getCategoryForRootlineCache[$ident] = $row;
        }

        return $row = $getCategoryForRootlineCache[$ident];
    }

    /**
     * @param int $uid
     * @param string $clause
     * @param bool $workspaceOl
     * @return array
     */
    protected static function getProductForRootline($uid, $clause, $workspaceOl)
    {
        static $getProductForRootlineCache = [];
        $ident = $uid . '-' . $clause . '-' . $workspaceOl;

        if (!isset($getCategoryForRootlineCache[$ident])) {
            /** @var ProductRepository $productRepository */
            $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
            $row = $productRepository->findRootlineProductByUid($uid, $clause);
            if (!empty($row)) {
                if ($workspaceOl) {
                    \TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tx_commerce_products', $row);
                }
                if (is_array($row)) {
                    \TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid('tx_commerce_products', $row);
                }
                $row['_is_category'] = true;
            }

            $getCategoryForRootlineCache[$ident] = $row;
        }

        return $row = $getProductForRootlineCache[$ident];
    }

    /**
     * Checks whether the parent category of any content is given the right
     * 'editcontent' for the specific user and returns true or false depending
     * on the perms.
     *
     * @param array $categoryUids Uids of the categories
     * @param array $permissions String for permissions to check
     * @return bool
     */
    public static function checkPermissionsOnCategoryContent(array $categoryUids, array $permissions)
    {
        $backendUser = self::getBackendUserAuthentication();

        // admin is allowed to do anything
        if ($backendUser->isAdmin()) {
            return true;
        }

        /** @var BackendUserUtility $backendUserUtility */
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        foreach ($categoryUids as $categoryUid) {
            // check if the category is in the commerce mounts
            if (!$backendUserUtility->isInWebMount($categoryUid)) {
                return false;
            }

            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $categoryUid);
            // check perms
            foreach ($permissions as $permission) {
                if (!$category->isPermissionSet($permission)) {
                    // return false if perms are not granted
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected static function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}

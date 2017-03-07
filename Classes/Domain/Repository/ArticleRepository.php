<?php
namespace CommerceTeam\Commerce\Domain\Repository;

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

/**
 * Database Class for tx_commerce_articles. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_article to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\ArticleRepository
 */
class ArticleRepository extends AbstractRepository
{
    /**
     * Database table.
     *
     * @var string
     */
    public $databaseTable = 'tx_commerce_articles';

    /**
     * Database relation table.
     *
     * @var string
     */
    public $databaseAttributeRelationTable = 'tx_commerce_articles_attributes_mm';

    /**
     * Returns the parent Product uid.
     *
     * @param int $uid Article uid
     * @param bool $translationMode Translation mode
     *
     * @return int product uid
     */
    public function getParentProductUid($uid, $translationMode = false)
    {
        $data = parent::getData($uid, $translationMode);
        $result = false;

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
     * Get the highest sorting of all articles belonging to a product
     *
     * @param int $productUid
     * @return int
     */
    public function getHighestSortingByProductUid($productUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid', 'sorting')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();

        return is_array($result) && isset($result['sorting']) ? $result['sorting'] : 0;
    }

    /**
     * Gets all prices form database related to this product.
     *
     * @param int $uid Article uid
     * @param int $count Number of Articles for price_scale_amount, default 1
     * @param string $orderField Order field
     *
     * @return array of Price UID
     */
    public function getPrices($uid, $count = 1, $orderField = 'price_net')
    {
        $uid = (int) $uid;
        $count = (int) $count;
        $additionalWhere = '';

        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'Domain/Repository/ArticleRepository',
            'getPrices'
        );
        if (is_object($hookObject) && method_exists($hookObject, 'priceOrder')) {
            $orderField = $hookObject->priceOrder($orderField);
        }
        if (is_object($hookObject) && method_exists($hookObject, 'additionalPriceWhere')) {
            $additionalWhere = $hookObject->additionalPriceWhere($this, $uid);
        }

        if ($uid > 0) {
            $priceUidList = [];

            $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_article_prices');
            $queryBuilder
                ->select('uid', 'fe_group')
                ->from('tx_commerce_article_prices')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_article',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->lte(
                        'price_scale_amount_start',
                        $queryBuilder->createNamedParameter($count, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gte(
                        'price_scale_amount_end',
                        $queryBuilder->createNamedParameter($count, \PDO::PARAM_INT)
                    )
                )
                ->orderBy($orderField);

            if ($additionalWhere) {
                $queryBuilder->andWhere($additionalWhere);
            }

            $rows = $queryBuilder
                ->execute()
                ->fetchAll();

            if (!empty($rows)) {
                foreach ($rows as $data) {
                    $feGroups = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $data['fe_group'], true);
                    if (!empty($feGroups)) {
                        foreach ($feGroups as $feGroup) {
                            $priceUidList[(string) $feGroup][] = $data['uid'];
                        }
                    } else {
                        $priceUidList[(string) $data['fe_group']][] = $data['uid'];
                    }
                }

                return $priceUidList;
            } else {
                $this->error(
                    'SELECT uid FROM tx_commerce_article_prices WHERE uid_article = ' . $uid . '; # returns no Result'
                );

                return [];
            }
        }

        return [];
    }

    /**
     * Returns an array of all scale price amounts.
     *
     * @param int $uid Article uid
     * @param int $count Count
     *
     * @return array of Price UID
     */
    public function getPriceScales($uid, $count = 1)
    {
        $uid = (int) $uid;
        if ($uid > 0) {
            $priceUidList = [];

            $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_article_prices');
            $rows = $queryBuilder
                ->select('uid', 'price_scale_amount_start', 'price_scale_amount_end')
                ->from('tx_commerce_article_prices')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_article',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gte(
                        'price_scale_amount_start',
                        $queryBuilder->createNamedParameter($count, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetchAll();

            if (!empty($rows)) {
                foreach ($rows as $data) {
                    $priceUidList[$data['price_scale_amount_start']][$data['price_scale_amount_end']] = $data['uid'];
                }

                return $priceUidList;
            } else {
                $this->error(
                    'SELECT uid FROM tx_commerce_article_prices WHERE uid_article = ' . $uid .
                    ' AND price_scale_amount_start >= ' . $count . '; # returns no Result'
                );

                return [];
            }
        }

        return [];
    }

    /**
     * Gets all attributes from this article.
     *
     * @param int $uid Attribute uid
     *
     * @return array of attributes
     */
    public function getAttributes($uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('at.*')
            ->from($this->databaseTable, 'ar')
            ->innerJoin('ar', $this->databaseAttributeRelationTable, 'mm', 'ar.uid = mm.uid_local')
            ->innerJoin('mm', 'tx_commerce_attributes', 'at', 'mm.uid_foreign = at.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'ar.uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('mm.sorting')
            ->execute()
            ->fetchAll();

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $articleUid
     *
     * @return array
     */
    public function getAttributeRelationsByArticleUid($articleUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->select('mm.*')
            ->from($this->databaseTable, 'a')
            ->innerJoin('a', $this->databaseAttributeRelationTable, 'mm', 'a.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'a.uid',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $productUid
     *
     * @return array
     */
    public function getAttributeRelationsByProductUid($productUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->select('mm.*')
            ->from($this->databaseTable, 'a')
            ->innerJoin('a', $this->databaseAttributeRelationTable, 'mm', 'a.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'a.uid_product',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $uid
     * @param array $attributeList
     *
     * @return array
     */
    public function getAttributeRelationsByArticleAndAttributeUid($uid, array $attributeList)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'uid_foreign',
                    $attributeList
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Returns the attribute Value from the given Article attribute pair.
     *
     * @param int $uid Article UID
     * @param int $attributeUid Attribute UID
     * @param bool $valueListAsUid If true, returns not the value from
     *      the valuelist, instead the uid
     *
     * @return string
     */
    public function getAttributeValue($uid, $attributeUid, $valueListAsUid = false)
    {
        $uid = (int) $uid;
        $attributeUid = (int) $attributeUid;

        if ($uid > 0) {
            // First select attribute, to detecxt if is valuelist
            $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_attributes');
            $returnData = $queryBuilder
                ->select('uid', 'has_valuelist')
                ->from('tx_commerce_attributes')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                    )
                )
                ->groupBy('uid', 'has_valuelist')
                ->execute()
                ->fetch();
            if (!empty($returnData)) {
                if ($returnData['has_valuelist'] == 1) {
                    // Attribute has a valuelist, so do separate query
                    $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
                    $valueData = $queryBuilder
                        ->select('v.value', 'v.uid')
                        ->from($this->databaseAttributeRelationTable, 'mm')
                        ->innerJoin('mm', 'tx_commerce_attribute_values', 'v', 'mm.uid_valuelist = v.uid')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid_local',
                                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'uid_foreign',
                                $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                            )
                        )
                        ->groupBy('v.value', 'v.uid')
                        ->execute()
                        ->fetch();
                    if (!empty($valueData)) {
                        if ($valueListAsUid == true) {
                            return $valueData['uid'];
                        } else {
                            return $valueData['value'];
                        }
                    }
                } else {
                    // attribute has no valuelist, so do normal query
                    $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
                    $valueData = $queryBuilder
                        ->select('value_char', 'default_value')
                        ->from($this->databaseAttributeRelationTable)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid_local',
                                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'uid_foreign',
                                $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                            )
                        )
                        ->groupBy('value_char', 'default_value')
                        ->execute()
                        ->fetch();
                    if (!empty($valueData)) {
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
     * No return value as the relation table has no primary key to use as identifier of the new record
     *
     * @param int $articleUid
     * @param int $attributeUid
     * @param int $productUid
     * @param int $sorting
     * @param int $valueList
     * @param string $characterValue
     * @param float $defaultValue
     * @return void
     */
    public function addAttributeRelation(
        $articleUid,
        $attributeUid,
        $productUid = 0,
        $sorting = 0,
        $valueList = 0,
        $characterValue = '',
        $defaultValue = 0.00
    ) {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->insert($this->databaseAttributeRelationTable)
            ->values([
                'uid_local' => $articleUid,
                'uid_foreign' => $attributeUid,
                'uid_product' => $productUid,
                'sorting' => $sorting,
                'uid_valuelist' => $valueList,
                'value_char' => $characterValue,
                'default_value' => $defaultValue,
            ])
            ->execute();
    }

    /**
     * Rreturns the supplier name to a given UID, selected from tx_commerce_supplier.
     *
     * @param int $supplierUid Supplier uid
     *
     * @return string Supplier name
     */
    public function getSupplierName($supplierUid)
    {
        if ($supplierUid > 0) {
            $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_supplier');
            $result = $queryBuilder
                ->select('title')
                ->from('tx_commerce_supplier')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($supplierUid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
            if (is_array($result) && !empty($result)) {
                return $result['title'];
            }
        }

        return '';
    }

    /**
     * @param int $articleUid
     * @param int $attributeUid
     * @return array
     */
    public function findAttributeRelationsByArticleAndAttribute($articleUid, $attributeUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Find article by classname.
     *
     * @param string $classname Classname
     *
     * @return array
     */
    public function findByClassname($classname)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'classname',
                    $queryBuilder->createNamedParameter($classname, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $productUid
     * @param string $orderBy
     *
     * @return array
     */
    public function findByProductUid($productUid, $orderBy = 'sorting')
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_product',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy($orderBy)
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Finds articles by product uid and returns only the uids as flat array
     *
     * @param int $productUid
     * @param string $orderBy
     *
     * @return array
     */
    public function findUidsByProductUid($productUid, $orderBy = 'sorting')
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_product',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy($orderBy)
            ->execute();

        $articles = [];
        while ($row = $result->fetch()) {
            $articles[] = $row['uid'];
        }

        return $articles;
    }

    /**
     * @return array
     */
    public function findLatestArticle()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->orderBy('uid', 'DESC')
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param array $parentUids
     * @param int $sysLanguageUid
     *
     * @return array
     */
    public function findTranslationsByParentUidAndLanguage(array $parentUids, $sysLanguageUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLanguageUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'l18n_parent',
                    $parentUids
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $articleUid
     * @param int $attributeUid
     *
     * @return int
     */
    public function countAttributeRelations($articleUid, $attributeUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        return (int) $queryBuilder
            ->count('*')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($attributeUid. \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param int $productUid
     * @param array $data
     */
    public function updateByProductUid($productUid, $data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_product',
                    $queryBuilder->createNamedParameter($productUid)
                )
            );

        foreach ($data as $field => $value) {
            $queryBuilder->set($field, $value);
        }

        $queryBuilder->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }

    /**
     * @param int $articleUid
     * @param string $hash
     */
    public function updateAttributeHash($articleUid, $hash)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->set('attribute_hash', $hash)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }

    /**
     * @param int $articleUid
     * @param int $attributeUid
     * @param array $data
     */
    public function updateRelation($articleUid, $attributeUid, array $data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->update($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($articleUid)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($attributeUid)
                )
            );

        foreach ($data as $field => $value) {
            $queryBuilder->set($field, $value);
        }

        $queryBuilder
            ->execute();
    }

    /**
     * @param int $productUidFrom
     * @param int $productUidTo
     *
     * @return string
     */
    public function updateProductUid($productUidFrom, $productUidTo)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_product',
                    $queryBuilder->createNamedParameter($productUidFrom)
                )
            )
            ->set('uid_product', $productUidTo)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
        return $result->errorInfo();
    }

    /**
     * @param int $articleUid
     * @param int $attributeId
     */
    public function removeAttributeRelation($articleUid, $attributeId)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->delete($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($attributeId, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    /**
     * Set delete flag and timestamp to current date for given articles
     *
     * @param array $articleUids
     */
    public function deleteByUids(array $articleUids)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->in(
                        'uid',
                        $articleUids
                    ),
                    $queryBuilder->expr()->in(
                        'l18n_parent',
                        $articleUids
                    )
                )
            )
            ->set('deleted', 1)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }

    /**
     * @param int $articleUid
     * @param int $attributeUid
     */
    public function deleteByArticleAndAttribute($articleUid, $attributeUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->delete($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }
}

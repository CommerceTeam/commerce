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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 */
class ProductRepository extends AbstractRepository
{
    /**
     * Database table.
     *
     * @var string
     */
    public $databaseTable = 'tx_commerce_products';

    /**
     * Database attribute relation table.
     *
     * @var string
     */
    public $databaseAttributeRelationTable = 'tx_commerce_products_attributes_mm';

    /**
     * Database category relation table.
     *
     * @var string
     */
    public $databaseCategoryRelationTable = 'tx_commerce_products_categories_mm';

    /**
     * Database related product relation table.
     *
     * @var string
     */
    public $databaseProductsRelatedTable = 'tx_commerce_products_related_mm';

    /**
     * Gets all articles form database related to this product.
     *
     * @param int $uid Product uid
     *
     * @return array of Article UID
     */
    public function getArticles($uid)
    {
        $return = [];
        $uid = (int) $uid;
        if ($uid) {
            $orderField = 'sorting';
            $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
                'Domain/Repository/ProductRepository',
                'getArticles'
            );
            if (is_object($hookObject) && method_exists($hookObject, 'articleOrder')) {
                $orderField = $hookObject->articleOrder($this->orderField);
            }

            $where = 'uid_product = ' . $uid;
            $additionalWhere = '';
            if (is_object($hookObject) && method_exists($hookObject, 'additionalWhere')) {
                $additionalWhere = $hookObject->additionalWhere($where);
            }

            $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_articles');
            $queryBuilder
                ->select('uid')
                ->from('tx_commerce_articles')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_product',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->orderBy($orderField);
            if ($additionalWhere !== '') {
                $queryBuilder->andWhere($additionalWhere);
            }
            $result = $queryBuilder->execute();

            $articleUids = [];
            if ($result->rowCount() > 0) {
                while ($row = $result->fetch()) {
                    $articleUids[] = $row['uid'];
                }
                $return = $articleUids;
            } else {
                $this->error(
                    'SELECT uid FROM tx_commerce_articles WHERE uid_product = '
                    . $uid . '; #returns no Result'
                );
            }
        }

        return $return;
    }

    /**
     * Gets all attributes form database related to this product
     * where corelation type = 4.
     *
     * @param int $uid Product uid
     * @param array|int $correlationtypes Correlation types
     *
     * @return array of Article UID
     */
    public function getAttributes($uid, $correlationtypes)
    {
        $return = [];
        $uid = (int) $uid;
        if ($uid) {
            if (!is_array($correlationtypes)) {
                $correlationtypes = [$correlationtypes];
            }

            $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
            $result = $queryBuilder
                ->select('at.*')
                ->from($this->databaseTable, 'p')
                ->innerJoin('p', $this->databaseAttributeRelationTable, 'mm', 'p.uid = mm.uid_local')
                ->innerJoin('mm', 'tx_commerce_attributes', 'at', 'mm.uid_foreign = at.uid')
                ->where(
                    $queryBuilder->expr()->eq(
                        'p.uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'mm.uid_correlationtype',
                        $correlationtypes
                    )
                )
                ->groupBy('at.uid')
                ->orderBy('mm.sorting')
                ->execute();

            $attributeUids = [];
            if ($result->rowCount()) {
                while ($row = $result->fetch()) {
                    $attributeUids[] = (int) $row['uid'];
                }
                $return = $attributeUids;
            }
        }

        return $return;
    }

    /**
     * @param int $uid
     *
     * @return array
     */
    public function getAttributeRelations($uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->select('sorting', 'uid_local', 'uid_foreign')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $uid
     *
     * @return array
     */
    public function getUniqueAttributeRelations($uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->addSelectLiteral('DISTINCT *')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->addOrderBy('uid_foreign', 'DESC')
            ->addOrderBy('uid_correlationtype')
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Returns a list of uid's that are related to this product.
     *
     * @param int $uid Product uid
     *
     * @return array Product UIDs
     */
    public function getRelatedProductUids($uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseProductsRelatedTable);
        $result = $queryBuilder
            ->select('uid_foreign AS uid')
            ->from($this->databaseProductsRelatedTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute();
        $products = [];
        while ($row = $result->fetch()) {
            $products[$row['uid']] = $row;
        }
        return is_array($result) ? $result : [];
    }

    /**
     * Returns an array of sys_language_uids of the i18n products
     * Only use in BE.
     *
     * @param int $uid Uid of the product we want to get the i18n languages from
     *
     * @return array $uid uids
     */
    public function getL18nProducts($uid)
    {
        $rows = [];
        if ((int) $uid) {
            $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
            $result = $queryBuilder
                ->select('p.title', 'p.uid', 's.uid AS sys_language_uid', 's.flag')
                ->from($this->databaseTable, 'p')
                ->leftJoin('p', 'sys_language', 's', 'p.sys_language_uid = s.uid')
                ->where(
                    $queryBuilder->expr()->eq(
                        'p.l18n_parent',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute();

            if ($result->rowCount() > 0) {
                $rows = $result->fetchAll();
            }
        }

        return $rows;
    }

    /**
     * Get first category as master.
     *
     * @param int $uid Master parent category
     *
     * @return int
     */
    public function getMasterParentCategory($uid)
    {
        return reset($this->getParentCategories($uid));
    }

    /**
     * Gets the parent categories of th.
     *
     * @param int $uid Uid of the product
     *
     * @return array parent categories for products
     */
    public function getParentCategories($uid)
    {
        if (!(int) $uid) {
            $this->error('getParentCategories has not been delivered a proper uid');

            return [];
        }

        $uids = [];

        // read from sql
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseCategoryRelationTable);
        $result = $queryBuilder
            ->select('uid_foreign')
            ->from($this->databaseCategoryRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute();
        while ($row = $result->fetch()) {
            $uids[] = $row['uid_foreign'];
        }

        // If $uids is empty, the record might be a localized product
        if (empty($uids)) {
            $row = $this->findByUid($uid);
            if (!empty($row) && isset($row['l18n_parent']) && $row['l18n_parent'] > 0) {
                $uids = $this->getParentCategories($row['l18n_parent']);
            }
        }

        return $uids;
    }

    /**
     * Returns the Manuafacturer Title to a given Manufacturere UID.
     *
     * @param int $manufacturer Manufacturer
     *
     * @return string Title
     */
    public function getManufacturerTitle($manufacturer)
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_manufacturer');
        $result = $queryBuilder
            ->select('title')
            ->from('tx_commerce_manufacturer')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($manufacturer, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return is_array($result) && isset($result['title']) ? $result['title'] : '';
    }

    /**
     * Get relation.
     *
     * @param int $productUid Product uid
     *
     * @return array
     */
    public function findAttributeRelationByProductUid($productUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Get relation.
     *
     * @param int $foreignUid Foreign uid
     *
     * @return array
     */
    public function findRelationByCategoryUid($foreignUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseCategoryRelationTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseCategoryRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($foreignUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Find by uids.
     *
     * @param array|string $uids Product uids
     *
     * @return array
     */
    public function findByUids($uids)
    {
        if (!is_array($uids)) {
            GeneralUtility::intExplode(',', $uids, true);
        }

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid', 'manufacturer_uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $uids
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $categoryUid
     * @param string $uname
     *
     * @return array
     */
    public function findByCategoryAndUname($categoryUid, $uname)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('p.*')
            ->from($this->databaseTable)
            ->innerJoin('p', $this->databaseCategoryRelationTable, 'mm', 'p.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'p.uname',
                    $queryBuilder->createNamedParameter($uname, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $productUid
     * @param string $andWhere
     *
     * @return array|mixed
     */
    public function findRootlineProductByUid($productUid, $andWhere = '')
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select(
                'c.ts_config',
                'c.t3ver_oid',
                'c.t3ver_wsid',
                'c.t3ver_state',
                'c.t3ver_stage',
                'c.perms_userid',
                'c.perms_groupid',
                'c.perms_user',
                'c.perms_group',
                'c.perms_everybody',
                'mm.uid_foreign AS pid',
                'p.uid',
                'p.hidden',
                'p.title'
            )
            ->from($this->databaseTable, 'p')
            ->innerJoin('p', 'pages', 'pa', 'p.pid = pa.uid')
            ->innerJoin('p', $this->databaseCategoryRelationTable, 'mm', 'p.uid = mm.uid_local')
            ->innerJoin('mm', 'tx_commerce_categories', 'c', 'mm.uid_foreign = c.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'p.uid',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                ),
                $andWhere
            );

        $result = $queryBuilder
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * Find product translations by translation parent uid
     *
     * @param int $productUid
     *
     * @return array
     */
    public function findByTranslationParentUid($productUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'l18n_parent',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $articleUid
     *
     * @return array
     */
    public function findByArticleUid($articleUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('p.*')
            ->from($this->databaseTable, 'p')
            ->innerJoin('p', 'tx_commerce_articles', 'a', 'p.uid = a.uid_product')
            ->where(
                $queryBuilder->expr()->eq(
                    'a.uid',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $categoryUid
     *
     * @return array
     */
    public function findByCategoryUid($categoryUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('p.*')
            ->from($this->databaseTable, 'p')
            ->innerJoin('p', $this->databaseCategoryRelationTable, 'mm', 'p.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('p.sorting')
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result :[];
    }

    /**
     * @param int $uid
     *
     * @return array
     */
    public function findPreviousByUid($uid)
    {
        $row = $this->findByUid($uid);
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->lt(
                    'sorting',
                    $queryBuilder->createNamedParameter($row['sorting'], \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @return array
     */
    public function findLatestProduct()
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
     * @param int $parentUid
     * @param int $sysLanguageUid
     *
     * @return array
     */
    public function findTranslationsByParentUidAndLanguage($parentUid, $sysLanguageUid = 0)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'l18n_parent',
                    $queryBuilder->createNamedParameter($parentUid, \PDO::PARAM_INT)
                )
            );

        if ($sysLanguageUid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLanguageUid, \PDO::PARAM_INT)
                )
            );
        }

        $result = $queryBuilder
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $sysLanguageUid
     *
     * @return array
     */
    public function findSelectorProducts($sysLanguageUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->from($this->databaseTable, 'p')
            ->innerJoin('p', 'tx_commerce_articles', 'a', 'p.uid = a.uid_product')
            ->addSelectLiteral('DISTINCT p.title, p.uid, p.sys_language_uid, COUNT(a.uid) AS anzahl')
            ->where(
                $queryBuilder->expr()->eq(
                    'a.article_type_uid',
                    $queryBuilder->createNamedParameter(NORMALARTICLETYPE, \PDO::PARAM_INT)
                )
            )
            ->groupBy('p.title', 'p.uid', 'p.sys_language_uid')
            ->orderBy('p.title', 'p.sys_language_uid');

        if ($sysLanguageUid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'p.sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLanguageUid, \PDO::PARAM_INT)
                )
            );
        }

        $result = $queryBuilder
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $productUid
     * @param array $_
     * @param array $attributeUids
     * @param string $sortingTable
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findSortedAttributeByArticle($productUid, $_, $attributeUids, $sortingTable)
    {
        $map = [
            $this->databaseTable => 'p',
            $this->databaseAttributeRelationTable => 'mm',
            'tx_commerce_attributes' => 'at',
        ];

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select(
                'a.uid AS parent_uid',
                'at.uid AS attributes_uid',
                'at.pid AS attributes_pid',
                'at.sys_language_uid AS attributes_sys_language_uid',
                'at.title AS attributes_title',
                'at.unit AS attributes_unit',
                'at.valueformat AS attributes_valueformat',
                'at.internal_title AS attributes_internal_title',
                'at.icon AS attributes_icon',
                'mm.default_value',
                'mm.uid_valuelist',
                $map[$sortingTable] . '.sorting'
            )
            ->from($this->databaseTable, 'p')
            ->innerJoin('p', $this->databaseAttributeRelationTable, 'mm', 'p.uid = mm.uid_local')
            ->innerJoin('mm', 'tx_commerce_attributes', 'at', 'mm.uid_foreign = at.uid')
            ->innerJoin('p', 'tx_commerce_articles', 'a', 'a.uid_product = p.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'p.uid',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy($map[$sortingTable] . '.sorting');

        // Restrict attribute list if given
        if (is_array($attributeUids) && !empty($attributeUids)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'at.uid',
                    $attributeUids
                )
            );
        }

        return $queryBuilder->execute();
    }

    /**
     * @param int $articleUid
     * @param int $attributeUid
     *
     * @return array
     */
    public function findAttributeValuesByArticleAndAttribute($articleUid, $attributeUid)
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
     * @param int $productUid
     * @param int $categorUid
     */
    public function addCategoryRelation($productUid, $categorUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseCategoryRelationTable);
        $queryBuilder
            ->insert($this->databaseCategoryRelationTable)
            ->values(
                [
                    'uid_local' => $productUid,
                    'uid_foreign' => $categorUid,
                ]
            )
            ->execute();
    }

    /**
     * @param int $productUid
     * @param array $data
     *
     * @return string
     */
    public function addAttributeRelation($productUid, $data)
    {
        $data['uid_local'] = (int) $productUid;

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->insert($this->databaseAttributeRelationTable)
            ->values($data)
            ->execute();
        return $result->errorInfo();
    }

    /**
     * @param int $productUidFrom
     * @param int $productUidTo
     *
     * @return string
     */
    public function updateAttributeRelation($productUidFrom, $productUidTo)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->update($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($productUidFrom, \PDO::PARAM_INT)
                )
            )
            ->set('uid_local', $productUidTo)
            ->execute();
        return $result->errorInfo();
    }

    /**
     * @param int $productUid
     * @param int $attributeUid
     * @param array $data
     *
     * @return string
     */
    public function updateAttributeRelationValues($productUid, $attributeUid, array $data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->update($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            );

        foreach ($data as $field => $value) {
            $queryBuilder->set($field, $value);
        }

        $result = $queryBuilder->execute();
        return $result->errorInfo();
    }

    /**
     * @param int $productUid
     * @param int $correlationType
     *
     * @return int
     */
    public function countProductAttributesByProductAndCorrelationType($productUid, $correlationType)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = (int) $queryBuilder
            ->count('*')
            ->from($this->databaseTable, 'p')
            ->innerJoin('p', $this->databaseAttributeRelationTable, 'mm', 'p.uid = mm.uid_local')
            ->innerJoin('mm', 'tx_commerce_attributes', 'a', 'mm.uid_foreign = a.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_correlationtype',
                    $queryBuilder->createNamedParameter($correlationType, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'p.uid',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
        return $result;
    }

    /**
     * @param int $productUid
     * @param int $correlationType
     *
     * @return int
     */
    public function countCategoryAttributesByProductAndCorrelationType($productUid, $correlationType)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = (int) $queryBuilder
            ->count('*')
            ->from($this->databaseCategoryRelationTable, 'cm')
            ->innerJoin('cm', 'tx_commerce_categories', 'c', 'cm.uid_foreign = c.uid')
            ->innerJoin('c', 'tx_commerce_categories_attributes_mm', 'mm', 'c.uid = mm.uid_local')
            ->innerJoin('mm', 'tx_commerce_attributes', 'a', 'mm.uid_foreign = a.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_correlationtype',
                    $queryBuilder->createNamedParameter($correlationType, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'cm.uid_local',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
        return $result;
    }

    /**
     * Set delete flag and timestamp to current date for given products uids
     *
     * @param array $productUids
     */
    public function deleteByUids(array $productUids)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $productUids
                )
            )
            ->set('deleted', 1)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }

    /**
     * Set delete flag and timestamp to current date for given translated products
     * by translation parent
     *
     * @param array $productUids
     */
    public function deleteTranslationByParentUids(array $productUids)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'l18n_parent',
                    $productUids
                )
            )
            ->set('deleted', 1)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }

    /**
     * @param int $productUid
     * @param int $categoryUid
     */
    public function deleteCategoryRelation($productUid, $categoryUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseCategoryRelationTable);
        $queryBuilder
            ->delete($this->databaseCategoryRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    /**
     * @param int $productUid
     * @param int $attributeUid
     */
    public function deleteByProductAndAttribute($productUid, $attributeUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->delete($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }
}

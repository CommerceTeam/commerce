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
 * Database class for tx_commerce_attributes. All database calls should
 * be made by this class.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\AttributeRepository
 */
class AttributeRepository extends AbstractRepository
{
    /**
     * Database table.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_attributes';

    /**
     * Database relation table.
     *
     * @var string
     */
    protected $databaseAttributeRelationTable = 'tx_commerce_articles_attributes_mm';

    /**
     * Database value table.
     *
     * @var string Child database table
     */
    protected $childDatabaseTable = 'tx_commerce_attribute_values';

    /**
     * Database value table.
     *
     * @var string Child database table
     */
    protected $correlationTypeDatabaseTable = 'tx_commerce_attribute_correlationtypes';

    /**
     * Gets a list of attribute_value_uids.
     *
     * @param int $uid Uid
     *
     * @return array
     */
    public function getAttributeValueUids($uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'attributes_uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute();

        $attributeValueList = [];
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) {
                $attributeValueList[] = $row['uid'];
            }
        }

        return $attributeValueList;
    }

    /**
     * Get child attribute uids.
     *
     * @param int $uid Uid
     *
     * @return array
     */
    public function getChildAttributeUids($uid)
    {
        $childAttributeList = [];
        $uid = (int) $uid;
        if ($uid) {
            $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
            $result = $queryBuilder
                ->select('uid')
                ->from($this->databaseTable)
                ->where(
                    $queryBuilder->expr()->eq(
                        'parent',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->orderBy('sorting')
                ->execute();

            if ($result->rowCount() > 0) {
                while ($row = $result->fetch()) {
                    $childAttributeList[] = $row['uid'];
                }
            }
        }

        return $childAttributeList;
    }

    /**
     * @param int $pid
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findByPid($pid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('title')
            ->execute();
    }

    /**
     * @param int $pid
     * @param int $uid
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function findTranslationByParentUid($pid, $uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'l18n_parent',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sys_language_uid')
            ->execute();
    }

    /**
     * @param int $productUid
     *
     * @return array
     */
    public function findByProductUid($productUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('at.*')
            ->from($this->databaseTable, 'at')
            ->innerJoin('at', 'tx_commerce_products_attributes_mm', 'mm', 'at.uid = mm.uid_foreign')
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_correlationtype',
                    $queryBuilder->createNamedParameter(4, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'mm.uid_local',
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
            ->select('at.*', 'pmm.uid_correlationtype')
            ->from($this->databaseTable, 'at')
            ->innerJoin('at', $this->databaseAttributeRelationTable, 'amm', 'at.uid = amm.uid_foreign')
            ->innerJoin('amm', 'tx_commerce_articles', 'a', 'amm.uid_local = a.uid')
            ->innerJoin('a', 'tx_commerce_products', 'p', 'a.uid_product = p.uid')
            ->innerJoin(
                'p',
                'tx_commerce_products_attributes_mm',
                'pmm',
                'p.uid = pmm.uid_local AND at.uid = pmm.uid_foreign'
            )
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
     * @param array $articleUids
     *
     * @return array
     */
    public function findByArticleUids($articleUids)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select('at.*', 'mm.uid_local', 'mm.uid_valuelist')
            ->from($this->databaseTable, 'at')
            ->innerJoin('at', $this->databaseAttributeRelationTable, 'mm', 'at.uid = mm.uid_foreign')
            ->orderBy('mm.uid_local', 'ASC')
            ->addOrderBy('mm.sorting', 'ASC');

        if (is_array($articleUids) && !empty($articleUids)) {
            $queryBuilder->where(
                $queryBuilder->expr()->in(
                    'mm.uid_local',
                    $articleUids
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
     * @param array $articleList
     * @param array $attributesToInclude
     * @param string $sortingTable
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findSortedByProductArticleAndAttributes(
        $productUid,
        $articleList,
        $attributesToInclude,
        $sortingTable
    ) {
        $map = [
            $this->databaseTable => 'at',
            $this->databaseAttributeRelationTable => 'mm',
            'tx_commerce_articles' => 'a'
        ];

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->addSelectLiteral('DISTINCT at.uid, at.sys_language_uid, a.uid AS article, at.title, at.unit, 
                at.valueformat, at.internal_title, at.icon, at.iconmode, ' . $map[$sortingTable] . '.sorting')
            ->from($this->databaseTable, 'at')
            ->innerJoin('at', $this->databaseAttributeRelationTable, 'mm', 'at.uid = mm.uid_foreign')
            ->innerJoin('mm', 'tx_commerce_articles', 'a', 'mm.uid_local = a.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'a.uid_product',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy($map[$sortingTable]);

        if (is_array($attributesToInclude) && !is_null($attributesToInclude[0])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'at.uid',
                    $attributesToInclude
                )
            );
        }

        if (is_array($articleList) && !empty($articleList)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'a.uid',
                    $articleList
                )
            );
        }

        return $queryBuilder->execute();
    }

    /**
     * @param int $productUid
     * @param array $articleList
     * @param int $attributeUid
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findByProductArticleAndAttribute($productUid, $articleList, $attributeUid)
    {

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->addSelectLiteral('DISTINCT mm.uid_valuelist')
            ->from($this->databaseTable, 'at')
            ->innerJoin('at', $this->databaseAttributeRelationTable, 'mm', 'at.uid = mm.uid_foreign')
            ->innerJoin('mm', 'tx_commerce_articles', 'a', 'mm.uid_local = a.uid')
            ->where(
                $queryBuilder->expr()->gt(
                    'mm.uid_valuelist',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'a.uid_product',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'at.uid',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            );

        if (is_array($articleList) && !empty($articleList)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'a.uid',
                    $articleList
                )
            );
        }

        return $queryBuilder->execute();
    }

    /**
     * @return array
     */
    public function findAllCorrelationTypes()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->correlationTypeDatabaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->correlationTypeDatabaseTable)
            ->orderBy('uid')
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $attributeUid
     *
     * @return array
     */
    public function findValuesByAttribute($attributeUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->childDatabaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->childDatabaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'attributes_uid',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->addOrderBy('uid')
            ->execute();
        $return = [];
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) {
                $return[$row['uid']] = $row;
            }
        }

        return $return;
    }

    /**
     * @param int $articleUid
     * @param array $excludeList
     *
     * @return array
     */
    public function findByArticleAndExcludingListed($articleUid, array $excludeList)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select('at.*')
            ->from($this->databaseTable, 'at')
            ->innerJoin('at', $this->databaseAttributeRelationTable, 'mm', 'at.uid = mm.uid_foreign')
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_local',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            );

        if (!empty($excludeList)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'mm.uid_foreign',
                    $excludeList
                )
            );
        }

        $result = $queryBuilder
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $articleUid
     *
     * @return array
     */
    public function findEmptyAttributesByArticle($articleUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_valuelist',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @return int
     */
    public function countAttributes()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->count('*')
            ->from($this->databaseTable)
            ->execute()
            ->fetchColumn();
        return (int) $result;
    }

    /**
     * @param int $categoryUid
     *
     * @return int
     */
    public function countCategoryRelations($categoryUid)
    {
        $table = 'tx_commerce_categories_attributes_mm';
        $queryBuilder = $this->getQueryBuilderForTable($table);
        return (int) $queryBuilder
            ->count('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param int $productUid
     *
     * @return int
     */
    public function countProductRelations($productUid)
    {
        $table = 'tx_commerce_products_attributes_mm';
        $queryBuilder = $this->getQueryBuilderForTable($table);
        return (int) $queryBuilder
            ->count('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param int $articleUid
     *
     * @return int
     */
    public function countArticleRelations($articleUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        return (int) $queryBuilder
            ->count('*')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param array $data field values for use for new record
     *
     * @return int uid of the new record
     */
    public function insertRelation($data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->insert($this->databaseAttributeRelationTable)
            ->values($data)
            ->execute();

        return $queryBuilder->getConnection()->lastInsertId($this->databaseAttributeRelationTable);
    }

    /**
     * @param int $articleUid
     */
    public function deleteAttributeRelationsByArticleUid($articleUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->delete($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }
}

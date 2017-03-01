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
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_attribute to get informations for articles.
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
            ->innerJoin('at', 'tx_commerce_articles_attributes_mm', 'amm', 'at.uid = amm.uid_foreign')
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
     * @return array
     */
    public function findAllCorrelationTypes()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->correlationTypeDatabaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->correlationTypeDatabaseTable)
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

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
        $table = 'tx_commerce_articles_attributes_mm';
        $queryBuilder = $this->getQueryBuilderForTable($table);
        return (int) $queryBuilder
            ->count('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }
}

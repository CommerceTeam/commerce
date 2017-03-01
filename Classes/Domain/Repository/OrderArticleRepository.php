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
 * Class \CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository
 */
class OrderArticleRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_order_articles';

    /**
     * Find order articles by order id in page.
     *
     * @param string $orderId Order Id
     *
     * @return array
     */
    public function findByOrderId($orderId)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'order_id',
                    $queryBuilder->createNamedParameter($orderId, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Find order articles by order id in page.
     *
     * @param string $orderId Order Id
     * @param int $pageId Page id
     *
     * @return array
     */
    public function findByOrderIdInPage($orderId, $pageId)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'order_id',
                    $queryBuilder->createNamedParameter($orderId, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $orderUid
     * @param int $articleType
     *
     * @return array
     */
    public function findByOrderIdAndType($orderUid, $articleType = 0)
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_order_articles');
        $result = $queryBuilder
            ->select('*')
            ->from('tx_commerce_order_articles')
            ->where(
                $queryBuilder->expr()->eq(
                    'order_uid',
                    $queryBuilder->createNamedParameter($orderUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'article_type_uid',
                    $queryBuilder->createNamedParameter($articleType, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $timestamp
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findDistinctCreationDatesSince($timestamp)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->selectLiteral('DISTINCT crdate')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->gt(
                    'tstamp',
                    $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    /**
     * @return int
     */
    public function findHighestCreationDate()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return (int) $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('crdate')
            )
            ->from($this->databaseTable)
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return int
     */
    public function findLowestCreationDate()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return (int) $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->min('crdate')
            )
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->gt(
                    'crdate',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }
}

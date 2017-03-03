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
 * Database Class for tx_commerce_baskets. All database calls should
 * be made by this class.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\BasketRepository
 */
class BasketRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'tx_commerce_baskets';

    /**
     * @param string $sessionId
     */
    public function setOrderToFinished($sessionId)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'finished_time',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sid',
                    $queryBuilder->createNamedParameter($sessionId, \PDO::PARAM_STR)
                )
            )
            ->set('finished_time', $GLOBALS['EXEC_TIME'])
            ->execute();
    }

    /**
     * @param string $sessionId
     * @param int $storagePid
     *
     * @return array
     */
    public function findUnfinishedBySessionId($sessionId, $storagePid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'finished_time',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sid',
                    $queryBuilder->createNamedParameter($sessionId, \PDO::PARAM_STR)
                )
            )
            ->orderBy('pos');

        if ($storagePid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($storagePid, \PDO::PARAM_INT)
                )
            );
        }

        $result = $queryBuilder
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param string $sessionId
     */
    public function deleteBySessionId($sessionId)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->delete($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'sid',
                    $queryBuilder->createNamedParameter($sessionId, \PDO::PARAM_STR)
                )
            )
            ->execute();
    }

    /**
     * @param array $data
     */
    public function insert(array $data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->insert($this->databaseTable)
            ->values($data)
            ->execute();
    }
}

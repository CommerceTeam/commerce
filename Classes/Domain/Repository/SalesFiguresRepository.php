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
 * Class SalesFiguresRepository
 */
class SalesFiguresRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'tx_commerce_salesfigures';

    public function truncate()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getConnection()->truncate($this->databaseTable);
    }

    /**
     * @param int $pid
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function findDayInPidByMonthAndYear($pid, $month, $year)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->select('day')
            ->addSelectLiteral(
                $queryBuilder->expr()->sum('pricegross', 'turnover'),
                $queryBuilder->expr()->sum('amount', 'salesfigures'),
                $queryBuilder->expr()->sum('orders', 'sumorders')
            )
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'month',
                    $queryBuilder->createNamedParameter($month, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'year',
                    $queryBuilder->createNamedParameter($year, \PDO::PARAM_INT)
                )
            )
            ->groupBy('day')
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pid
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function findDowInPidByMonthAndYear($pid, $month, $year)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->select('dow')
            ->addSelectLiteral(
                $queryBuilder->expr()->sum('pricegross', 'turnover'),
                $queryBuilder->expr()->sum('amount', 'salesfigures'),
                $queryBuilder->expr()->sum('orders', 'sumorders')
            )
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'month',
                    $queryBuilder->createNamedParameter($month, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'year',
                    $queryBuilder->createNamedParameter($year, \PDO::PARAM_INT)
                )
            )
            ->groupBy('dow')
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pid
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function findHourInPidByMonthAndYear($pid, $month, $year)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->select('hour')
            ->addSelectLiteral(
                $queryBuilder->expr()->sum('pricegross', 'turnover'),
                $queryBuilder->expr()->sum('amount', 'salesfigures'),
                $queryBuilder->expr()->sum('orders', 'sumorders')
            )
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'month',
                    $queryBuilder->createNamedParameter($month, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'year',
                    $queryBuilder->createNamedParameter($year, \PDO::PARAM_INT)
                )
            )
            ->groupBy('hour')
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pid
     *
     * @return array
     */
    public function findYearMonthInPid($pid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->select('year', 'month')
            ->addSelectLiteral(
                $queryBuilder->expr()->sum('pricegross', 'turnover'),
                $queryBuilder->expr()->sum('amount', 'salesfigures'),
                $queryBuilder->expr()->sum('orders', 'sumorders')
            )
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                )
            )
            ->groupBy('year', 'month')
            ->execute()
            ->fetchAll();
    }

    /**
     * @return int
     */
    public function findHighestTimestamp()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return (int) $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('tstamp')
            )
            ->from($this->databaseTable)
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param array $data field values for use for new record
     *
     * @return bool
     */
    public function insertRecord($data): bool
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->insert($this->databaseTable)
            ->values($data)
            ->execute();

        return $result->errorCode() > 0;
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param array $data
     *
     * @return bool
     */
    public function updateWithYearMonthDayHour($year, $month, $day, $hour, array $data): bool
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'year',
                    $queryBuilder->createNamedParameter($year, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'month',
                    $queryBuilder->createNamedParameter($month, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'day',
                    $queryBuilder->createNamedParameter($day, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'hour',
                    $queryBuilder->createNamedParameter($hour, \PDO::PARAM_INT)
                )
            );

        foreach ($data as $field => $value) {
            $queryBuilder->set($field, $value);
        }

        $result = $queryBuilder
            ->execute();

        return $result->errorCode() > 0;
    }
}

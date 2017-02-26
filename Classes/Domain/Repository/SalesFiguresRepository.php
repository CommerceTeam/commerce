<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/**
 * Class SalesFiguresRepository
 *
 * @package CommerceTeam\Commerce\Domain\Repository#
 */
class SalesFiguresRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'tx_commerce_salesfigures';

    /**
     * @return void
     */
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
}

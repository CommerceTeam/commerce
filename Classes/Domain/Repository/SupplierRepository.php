<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/**
 * Class SupplierRepository
 *
 * @package CommerceTeam\Commerce\Domain\Repository
 */
class SupplierRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'tx_commerce_manufacturer';

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
}

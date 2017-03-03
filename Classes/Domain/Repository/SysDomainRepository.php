<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/**
 * Class SupplierRepository
 *
 * @package CommerceTeam\Commerce\Domain\Repository
 */
class SysDomainRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'sys_domain';

    /**
     * @param int $pid
     *
     * @return string
     */
    public function findFirstByPid($pid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('domainName')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute()
            ->fetch();
        return !empty($result) ? htmlspecialchars($result['domainName']) : '';
    }
}

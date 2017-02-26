<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/**
 * Class SysRefindexRepository
 *
 * @package CommerceTeam\Commerce\Domain\Repository
 */
class SysRefindexRepository extends \CommerceTeam\Commerce\Domain\Repository\AbstractRepository
{
    public function countByTablenameUid($tableName, $uid)
    {
        /** @var $queryBuilder \TYPO3\CMS\Core\Database\Query\QueryBuilder */
        $queryBuilder = $this->getQueryBuilderForTable('sys_refindex');
        return $queryBuilder
            ->count('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'ref_uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }
}

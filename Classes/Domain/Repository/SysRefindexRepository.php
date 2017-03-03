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

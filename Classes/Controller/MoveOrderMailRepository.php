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
 * Database Class for tx_commerce_moveordermails. All database calle should
 * be made by this class.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\MoveOrderMailRepository
 */
class MoveOrderMailRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'tx_commerce_moveordermails';

    /**
     * @param int $pid
     * @param int $mailKind
     *
     * @return array
     */
    public function findByPidAndMailKind($pid, $mailKind)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'mailkind',
                    $queryBuilder->createNamedParameter($mailKind, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }
}

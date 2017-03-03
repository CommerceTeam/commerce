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
 * Class NewClientsRepository
 *
 * @package CommerceTeam\Commerce\Domain\Repository
 */
class NewClientsRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'tx_commerce_newclients';

    /**
     * @return void
     */
    public function truncate()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getConnection()->truncate($this->databaseTable);
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

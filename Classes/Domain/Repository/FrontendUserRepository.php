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

use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Domain\Repository\FrontendUserRepository
 */
class FrontendUserRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'fe_users';

    /**
     * Find frontend user by address id.
     *
     * @param int $addressId Address id
     *
     * @return array
     */
    public function findByAddressId($addressId)
    {
        /** @var DeletedRestriction $deleteRestriction */
        $deleteRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add($deleteRestriction);

        $result = $queryBuilder
            ->select('uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_commerce_tt_address_id',
                    $queryBuilder->createNamedParameter($addressId, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return is_array($result) ? $result : [];
    }

    /**
     * @param string $username
     * @param int $folderId
     *
     * @return array
     */
    public function findByUsernameInFolder($username, $folderId)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($folderId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'username',
                    $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

        return is_array($result) ? $result : [];
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
     * @param int $timestart
     * @param int $timeend
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findInDateRange($timestart, $timeend)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('pid')
            ->addSelectLiteral($queryBuilder->expr()->count('*'))
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->lte(
                    'crdate',
                    $queryBuilder->createNamedParameter($timestart, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gte(
                    'crdate',
                    $queryBuilder->createNamedParameter($timeend, \PDO::PARAM_INT)
                )
            )
            ->groupBy('pid')
            ->execute();
        return $result;
    }
}

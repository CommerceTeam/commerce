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
        $row = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            $this->databaseTable,
            'tx_commerce_tt_address_id = ' . $addressId . ' AND deleted = 0'
        );
        $row = is_array($row) ? $row : [];
        return $row;
    }

    /**
     * @param string $username
     * @param int $folderId
     *
     * @return array
     */
    public function findByUsernameInFolder($username, $folderId)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            $this->databaseTable,
            'username = ' . $this->getDatabaseConnection()->fullQuoteStr($username, $this->databaseTable)
            . ' AND pid = ' . $folderId . $this->enableFields()
        );
        $row = is_array($row) ? $row : [];
        return $row;
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
}

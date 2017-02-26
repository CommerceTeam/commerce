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
 * Database Class for tt_address. All database calls should
 * be made by this class.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\AddressRepository
 */
class AddressRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'tt_address';

    /**
     * @param int $type
     *
     * @return array
     */
    public function findByType($type)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_commerce_address_type_id',
                    $queryBuilder->createNamedParameter($type, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $userUid
     * @param int $addressUid
     *
     * @return array
     */
    public function findByUserAndUid($userUid, $addressUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('a.*')
            ->from($this->databaseTable, 'a')
            ->innerJoin('a', 'fe_users', 'f', 'a.tx_commerce_fe_user_id = f.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'a.tx_commerce_fe_user_id',
                    $queryBuilder->createNamedParameter($userUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'a.uid',
                    $queryBuilder->createNamedParameter($addressUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $userUid
     * @param int $type
     *
     * @return array
     */
    public function findByUserAndType($userUid, $type)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('a.*')
            ->from($this->databaseTable, 'a')
            ->innerJoin('a', 'fe_users', 'f', 'a.tx_commerce_fe_user_id = f.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'a.tx_commerce_fe_user_id',
                    $queryBuilder->createNamedParameter($userUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'a.tx_commerce_address_type_id',
                    $queryBuilder->createNamedParameter($type, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return is_array($result) ? $result : [];
    }

    /**
     * Remove all "is main address" flags from addresses that are assigned to this user
     *
     * @param int $pid
     * @param int $feUserUid
     * @param int $addressType
     *
     * @return void
     */
    public function removeIsMainAddress($pid, $feUserUid, $addressType)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            $this->getDatabaseConnection(),
            'pid = ' . (int) $pid
            . ' AND tx_commerce_fe_user_id = ' . (int) $feUserUid
            . ' AND tx_commerce_address_type_id = ' . (int) $addressType,
            ['tx_commerce_is_main_address' => 0]
        );
    }

    /**
     * @param int $addressUid
     * @param int $userUid
     * @param array $data
     */
    public function updateAddressOfUser($addressUid, $userUid, $data)
    {
        $sWhere = 'uid = ' . (int) $addressUid . ' AND tx_commerce_fe_user_id = ' . (int) $userUid;

        $this->getDatabaseConnection()->exec_UPDATEquery('tt_address', $sWhere, $data);
    }

    /**
     * @param int $uid
     * @return string
     */
    public function deleteAddress($uid)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            'tt_address',
            'uid = ' . (int) $uid,
            ['deleted' => 1]
        );

        return $this->getDatabaseConnection()->sql_error();
    }
}

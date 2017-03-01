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
 * Class \CommerceTeam\Commerce\Domain\Repository\OrderRepository
 */
class OrderRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_orders';

    /**
     * @param string $orderId
     * @param int $userId
     *
     * @return array
     */
    public function findByOrderIdAndUser($orderId, $userId = 0)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'order_id',
                    $queryBuilder->createNamedParameter($orderId, \PDO::PARAM_STR)
                )
            );

        if ($userId) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'cust_fe_user',
                    $queryBuilder->createNamedParameter($userId, \PDO::PARAM_INT)
                )
            );
        }

        $result = $queryBuilder
            ->execute()
            ->fetch();

        return is_array($result) ? $result : [];
    }

    /**
     * Update data by order id.
     *
     * @param string $orderId Order id
     * @param array $data Data
     *
     * @return void
     */
    public function updateByOrderId($orderId, array $data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'order_id',
                    $queryBuilder->createNamedParameter($orderId, \PDO::PARAM_STR)
                )
            );

        foreach ($data as $field => $value) {
            $queryBuilder->set($field, $value);
        }

        $queryBuilder->set('tstamp', $GLOBALS['EXEC_TIME']);
        $queryBuilder->execute();
    }
}

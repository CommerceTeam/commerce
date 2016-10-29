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
        $queryString = 'order_id = ' . $this->getDatabaseConnection()->fullQuoteStr($orderId, $this->databaseTable);
        if ($userId) {
            $queryString .= ' AND cust_fe_user = ' . (int) $userId;
        }

        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            $this->databaseTable,
            $queryString . $this->enableFields('tx_commerce_orders')
        );
        $row = is_array($row) ? $row : [];
        return $row;
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
        $this->getDatabaseConnection()->exec_UPDATEquery(
            $this->databaseTable,
            'order_id = ' . $this->getDatabaseConnection()->fullQuoteStr($orderId, $this->databaseTable),
            $data
        );
    }
}

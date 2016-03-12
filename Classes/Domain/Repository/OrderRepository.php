<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class \CommerceTeam\Commerce\Domain\Repository\OrderRepository
 */
class OrderRepository extends Repository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_orders';

    /**
     * Update data.
     *
     * @param string $where Search
     * @param array $data Data
     *
     * @return void
     */
    protected function update($where, array $data)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery($this->databaseTable, $where, $data);
    }

    /**
     * Update order by uid.
     *
     * @param int $uid Order uid
     * @param array $data Data
     *
     * @return void
     */
    public function updateByUid($uid, array $data)
    {
        $this->update('uid = ' . (int) $uid, $data);
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
        $this->update(
            'order_id = ' . $this->getDatabaseConnection()->fullQuoteStr($orderId, $this->databaseTable),
            $data
        );
    }
}

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
 * Class \CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository
 */
class OrderArticleRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_order_articles';

    /**
     * Find order articles by order id in page.
     *
     * @param string $orderId Order Id
     *
     * @return array
     */
    public function findByOrderId($orderId)
    {
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->databaseTable,
            'order_id = ' . $this->getDatabaseConnection()->fullQuoteStr($orderId, $this->databaseTable)
        );
    }

    /**
     * Find order articles by order id in page.
     *
     * @param string $orderId Order Id
     * @param int $pageId Page id
     *
     * @return array
     */
    public function findByOrderIdInPage($orderId, $pageId)
    {
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->databaseTable,
            'pid = ' . $pageId . $this->enableFields() .
            ' AND order_id = ' . $this->getDatabaseConnection()->fullQuoteStr($orderId, $this->databaseTable)
        );
    }

    /**
     * @param int $orderUid
     * @param int $articleType
     *
     * @return array
     */
    public function findByOrderIdAndType($orderUid, $articleType = 0)
    {
        $where = 'order_uid = ' . (int) $orderUid . ' ';
        if ($articleType) {
            $where .= ' AND article_type_uid = ' . (int) $articleType . ' ';
        }

        $rows = (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'tx_commerce_order_articles',
            $where . $this->enableFields()
        );

        return $rows;
    }
}

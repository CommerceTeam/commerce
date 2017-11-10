<?php
namespace CommerceTeam\Commerce\Tests\Functional\Frontend\Controller;

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
 * Test case for \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 */
class CheckoutControllerTest extends \CommerceTeam\Commerce\Tests\Functional\Frontend\AbstractTestCase
{
    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet($this->fixturePath . 'tx_commerce_products.xml');
    }

    /**
     * @test
     */
    public function saveOrder()
    {
        $this->markTestIncomplete('not ready yet');

        $checkoutController = new \CommerceTeam\Commerce\Controller\CheckoutController();
        $pid = 100;
        $orderId = 'test.order';
        $dataHandler = $checkoutController->getDataHandler();

        $newUid = uniqid('NEW');
        $data = [];
        $data['tx_commerce_orders'][$newUid] = [
            'pid' => $pid,
            'crdate' => 2,
            'tstamp' => 2,
            'order_id' => $orderId,
        ];
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        $orderUid = $dataHandler->substNEWwithIDs[$newUid];

        $orderArticleData = [];
        $orderArticleData['pid'] = $pid;
        $orderArticleData['crdate'] = 2;
        $orderArticleData['tstamp'] = 2;
        $orderArticleData['article_uid'] = 1000;
        $orderArticleData['order_uid'] = $orderUid;
        $orderArticleData['order_id'] = $orderId;

        $newUid = uniqid('NEW');
        $data = [];
        $data['tx_commerce_order_articles'][$newUid] = $orderArticleData;
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        $orderArticleUid = $dataHandler->substNEWwithIDs[$newUid];
    }
}

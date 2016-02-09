<?php
namespace CommerceTeam\Commerce\Domain\Model;

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

use CommerceTeam\Commerce\Factory\HookFactory;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use CommerceTeam\Commerce\Utility\GeneralUtility;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Frontend library for handling the basket. This class should be used
 * when rendering the basket and changing the basket items.
 *
 * The basket object is singleton, you could access the Basket object via
 * \CommerceTeam\Commerce\Utility\GeneralUtility::getBasket()
 *
 * Do not access class variables directly, always use the get and set methods,
 * variables will be changed in php5 to private
 *
 * Basic class for basket_handling inherited from tx_commerce_basic_basket
 *
 * Class \CommerceTeam\Commerce\Domain\Model\Basket
 *
 * @author 2005-2013 Ingo Schmitt <is@marketing-factory.de>
 */
class Basket extends BasicBasket implements SingletonInterface
{
    /**
     * Storage-type for the data.
     *
     * @var string
     */
    protected $storageType = 'database';

    /**
     * Not session id, as session_id is PHP5 method.
     *
     * @var string
     */
    protected $sessionId = '';

    /**
     * Flag if already loaded.
     *
     * @var bool
     */
    protected $isAlreadyLoaded = false;

    /**
     * Basket storage pid.
     *
     * @var int
     */
    protected $basketStoragePid;

    /**
     * Constructor for a commerce basket.
     * Loads configuration data.
     *
     * @return self
     */
    public function __construct()
    {
        if (ConfigurationUtility::getInstance()->getExtConf('basketType') == 'persistent') {
            $this->storageType = 'persistent';
        }
    }

    /**
     * Set the session ID.
     *
     * @param string $sessionId Session ID
     *
     * @return void
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Returns the session ID.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Finish order.
     *
     * @return void
     */
    public function finishOrder()
    {
        switch ($this->storageType) {
            case 'persistent':
                $this->getFrontendUser()->setKey('ses', 'txCommercePersistantSessionId', '');
                $this->getFrontendUser()->storeSessionData();
                $this->finishOrderInDatabase();
                break;

            case 'database':
                $this->finishOrderInDatabase();
                break;

            default:
        }
    }

    /**
     * Set finish date in database.
     *
     * @return void
     */
    protected function finishOrderInDatabase()
    {
        $updateArray = array(
            'finished_time' => $GLOBALS['EXEC_TIME'],
        );

        $database = $this->getDatabaseConnection();

        $database->exec_UPDATEquery(
            'tx_commerce_baskets',
            'sid = ' . $database->fullQuoteStr($this->getSessionId(), 'tx_commerce_baskets') . ' AND finished_time = 0',
            $updateArray
        );
    }

    /**
     * Loads basket data from session / database depending
     * on $this->storageType
     * Only database storage is implemented until now
     * cloud be used as per session or per user /presistent).
     *
     * @return void
     */
    public function loadData()
    {
        if ($this->isAlreadyLoaded) {
            return;
        }

        switch ($this->storageType) {
            case 'persistent':
                $this->restoreBasket();
                break;

            case 'database':
                $this->loadDataFromDatabase();
                break;

            default:
        }

        // Method of Parent: Load the payment articcle if availiable
        parent::loadData();

        $this->setLoaded();
    }

    /**
     * Set unloaded.
     *
     * @return void
     */
    public function setUnloaded()
    {
        $this->isAlreadyLoaded = false;
    }

    /**
     * Set loaded.
     *
     * @return void
     */
    public function setLoaded()
    {
        $this->isAlreadyLoaded = true;
    }

    /**
     * Loads basket data from database.
     *
     * @return void
     */
    protected function loadDataFromDatabase()
    {
        $where = '';
        if ($this->getBasketStoragePid()) {
            $where .= ' AND pid = ' . $this->getBasketStoragePid();
        }

        $database = $this->getDatabaseConnection();

        $rows = $database->exec_SELECTgetRows(
            '*',
            'tx_commerce_baskets',
            'sid = ' . $database->fullQuoteStr($this->getSessionId(), 'tx_commerce_baskets') .
            ' AND finished_time = 0' . $where,
            '',
            'pos'
        );

        if (is_array($rows) && !empty($rows)) {
            $hooks = HookFactory::getHooks('Domain/Model/Basket', 'loadDataFromDatabase');

            $basketReadonly = false;
            foreach ($rows as $returnData) {
                if (($returnData['quantity'] > 0) && ($returnData['price_id'] > 0)) {
                    $this->addArticle($returnData['article_id'], $returnData['quantity'], $returnData['price_id']);
                    $this->changePrices(
                        $returnData['article_id'],
                        $returnData['price_gross'],
                        $returnData['price_net']
                    );
                    $this->crdate = $returnData['crdate'];
                    if (is_array($hooks)) {
                        foreach ($hooks as $hookObj) {
                            if (method_exists($hookObj, 'loadDataFromDatabase')) {
                                $hookObj->loadDataFromDatabase($returnData, $this);
                            }
                        }
                    }
                }
                if ($returnData['readonly'] == 1) {
                    $basketReadonly = true;
                }
            }

            if ($basketReadonly === true) {
                $this->setReadOnly();
            }
        }
    }

    /**
     * Loads the Basket Data from the database.
     *
     * @param string $sessionId Session id
     *
     * @return void
     * @todo handling for special prices
     */
    protected function loadPersistentDataFromDatabase($sessionId)
    {
        $database = $this->getDatabaseConnection();

        $rows = $database->exec_SELECTgetRows(
            '*',
            'tx_commerce_baskets',
            'sid = ' . $database->fullQuoteStr($sessionId, 'tx_commerce_baskets') .
            ' AND finished_time = 0 AND pid = ' . $this->getBasketStoragePid(),
            '',
            'pos'
        );

        if (is_array($rows) && !empty($rows)) {
            $hooks = HookFactory::getHooks('Domain/Model/Basket', 'loadPersistentDataFromDatabase');

            foreach ($rows as $returnData) {
                if ($returnData['quantity'] > 0 && $returnData['price_id'] > 0) {
                    $this->addArticle($returnData['article_id'], $returnData['quantity']);
                    $this->crdate = $returnData['crdate'];
                    if (is_array($hooks)) {
                        foreach ($hooks as $hookObj) {
                            if (method_exists($hookObj, 'loadPersistantDataFromDatabase')) {
                                $hookObj->loadPersistantDataFromDatabase($returnData, $this);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Restores the Basket from the persistent storage.
     *
     * @return void
     */
    private function restoreBasket()
    {
        if ($$this->getFrontendUser()->user) {
            $userSessionId = $this->getFrontendUser()->getKey('user', 'txCommercePersistantSessionId');
            if ($userSessionId && $userSessionId != $this->sessionId) {
                $this->loadPersistentDataFromDatabase($userSessionId);
                $this->loadDataFromDatabase();
                $this->getFrontendUser()->setKey('user', 'txCommercePersistantSessionId', $this->sessionId);
                $this->storeDataToDatabase();
            } else {
                $this->loadDataFromDatabase();
            }
        } else {
            $this->loadDataFromDatabase();
        }
    }

    /**
     * Store basket data in session / database depending
     * on $this->storageType
     * Only database storage is implemented until now.
     *
     * @return void
     */
    public function storeData()
    {
        switch ($this->storageType) {
            case 'persistent':
                // fallthrough
            case 'database':
                $this->storeDataToDatabase();
                break;

            default:
        }
    }

    /**
     * Store basket data to database.
     *
     * @return void
     */
    protected function storeDataToDatabase()
    {
        $database = $this->getDatabaseConnection();

        $database->exec_DELETEquery(
            'tx_commerce_baskets',
            'sid = ' . $database->fullQuoteStr($this->getSessionId(), 'tx_commerce_baskets') . ' AND finished_time = 0'
        );
        $hooks = HookFactory::getHooks('Domain/Model/Basket', 'storeDataToDatabase');

        // Get array keys from basket items to store correct position in basket
        $arBasketItemsKeys = array_keys($this->basketItems);
        // After getting the keys in a array, flip it to get the position of each item
        $arBasketItemsKeys = array_flip($arBasketItemsKeys);

        $oneuid = 0;
        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
         */
        foreach ($this->basketItems as $oneuid => $basketItem) {
            $insertData = array();
            $insertData['pid'] = $this->getBasketStoragePid();
            $insertData['pos'] = $arBasketItemsKeys[$oneuid];
            $insertData['sid'] = $this->sessionId;
            $insertData['article_id'] = $basketItem->getArticleUid();
            $insertData['price_id'] = $basketItem->getPriceUid();
            $insertData['price_net'] = $basketItem->getPriceNet();
            $insertData['price_gross'] = $basketItem->getPriceGross();
            $insertData['quantity'] = $basketItem->getQuantity();
            $insertData['readonly'] = $this->getReadOnly();
            $insertData['tstamp'] = $GLOBALS['EXEC_TIME'];

            if ($this->crdate > 0) {
                $insertData['crdate'] = $this->crdate;
            } else {
                $insertData['crdate'] = $insertData['tstamp'];
            }

            if (is_array($hooks)) {
                foreach ($hooks as $hookObj) {
                    if (method_exists($hookObj, 'storeDataToDatabase')) {
                        $insertData = $hookObj->storeDataToDatabase($basketItem, $insertData);
                    }
                }
            }

            $database->exec_INSERTquery('tx_commerce_baskets', $insertData);
        }

        $basketItem = $this->basketItems[$oneuid];
        if (is_object($basketItem)) {
            $basketItem->calculateNetSum();
            $basketItem->calculateGrossSum();
        }
    }

    /**
     * Gets the basket storage pid.
     *
     * @return int
     */
    public function getBasketStoragePid()
    {
        if (is_null($this->basketStoragePid)) {
            $this->basketStoragePid = GeneralUtility::getBasketStoragePid();
        }

        return $this->basketStoragePid;
    }
}

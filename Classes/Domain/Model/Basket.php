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
use CommerceTeam\Commerce\Factory\SettingsFactory;

/**
 * Frontend library for handling the basket. This class should be used
 * when rendering the basket and changing the basket items.
 *
 * The basket object is stored as object within the Frontend user
 * fe_user->tx_commerce_basket, you could access the Basket object in the
 * Frontend via frontend user basket;
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
class Basket extends BasicBasket {
	/**
	 * Storage-type for the data
	 *
	 * @var string
	 */
	protected $storageType = 'database';

	/**
	 * Not session id, as session_id is PHP5 method
	 *
	 * @var string
	 */
	protected $sessionId = '';

	/**
	 * Flag if already loaded
	 *
	 * @var bool
	 */
	protected $isAlreadyLoaded = FALSE;

	/**
	 * Constructor for a commerce basket.
	 * Loads configuration data
	 *
	 * @return self
	 */
	public function __construct() {
		if (SettingsFactory::getInstance()->getExtConf('basketType') == 'persistent') {
			$this->storageType = 'persistent';
		}
	}

	/**
	 * Set the session ID
	 *
	 * @param string $sessionId Session ID
	 *
	 * @return void
	 */
	public function setSessionId($sessionId) {
		$this->sessionId = $sessionId;
	}

	/**
	 * Returns the session ID
	 *
	 * @return string
	 */
	public function getSessionId() {
		return $this->sessionId;
	}


	/**
	 * Finish order
	 *
	 * @return void
	 */
	public function finishOrder() {
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
	 * Set finish date in database
	 *
	 * @return void
	 */
	protected function finishOrderInDatabase() {
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
	 * cloud be used as per session or per user /presistent)
	 *
	 * @return void
	 */
	public function loadData() {
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
	 * Set unloaded
	 *
	 * @return void
	 */
	public function setUnloaded() {
		$this->isAlreadyLoaded = FALSE;
	}

	/**
	 * Set loaded
	 *
	 * @return void
	 */
	public function setLoaded() {
		$this->isAlreadyLoaded = TRUE;
	}

	/**
	 * Loads basket data from database
	 *
	 * @return void
	 */
	protected function loadDataFromDatabase() {
		$where = '';
		if (SettingsFactory::getInstance()->getExtConf('BasketStoragePid') > 0) {
			$where .= ' AND pid = ' . SettingsFactory::getInstance()->getExtConf('BasketStoragePid');
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

		if (is_array($rows) && count($rows)) {
			$hooks = HookFactory::getHooks('Domain/Model/Basket', 'loadDataFromDatabase');

			$basketReadonly = FALSE;
			foreach ($rows as $returnData) {
				if (($returnData['quantity'] > 0) && ($returnData['price_id'] > 0)) {
					$this->addArticle($returnData['article_id'], $returnData['quantity'], $returnData['price_id']);
					$this->changePrices($returnData['article_id'], $returnData['price_gross'], $returnData['price_net']);
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
					$basketReadonly = TRUE;
				}
			}

			if ($basketReadonly === TRUE) {
				$this->setReadOnly();
			}
		}
	}

	/**
	 * Loads the Basket Data from the database
	 *
	 * @param string $sessionId Session id
	 *
	 * @return void
	 * @todo handling for special prices
	 */
	protected function loadPersistentDataFromDatabase($sessionId) {
		$database = $this->getDatabaseConnection();

		$rows = $database->exec_SELECTgetRows(
			'*',
			'tx_commerce_baskets',
			'sid = ' . $database->fullQuoteStr($sessionId, 'tx_commerce_baskets') . ' AND finished_time = 0 AND pid = ' .
				SettingsFactory::getInstance()->getExtConf('BasketStoragePid'),
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
	 * Restores the Basket from the persistent storage
	 *
	 * @return void
	 */
	private function restoreBasket() {
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
	 * Only database storage is implemented until now
	 *
	 * @return void
	 */
	public function storeData() {
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
	 * Store basket data to database
	 *
	 * @return void
	 */
	protected function storeDataToDatabase() {
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
		 * Basket item
		 *
		 * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
		 */
		foreach ($this->basketItems as $oneuid => $oneItem) {
			$insertData = array();
			$insertData['pid'] = SettingsFactory::getInstance()->getExtConf('BasketStoragePid');
			$insertData['pos'] = $arBasketItemsKeys[$oneuid];
			$insertData['sid'] = $this->sessionId;
			$insertData['article_id'] = $oneItem->getArticleUid();
			$insertData['price_id'] = $oneItem->getPriceUid();
			$insertData['price_net'] = $oneItem->getPriceNet();
			$insertData['price_gross'] = $oneItem->getPriceGross();
			$insertData['quantity'] = $oneItem->getQuantity();
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
						$insertData = $hookObj->storeDataToDatabase($oneItem, $insertData);
					}
				}
			}

			$database->exec_INSERTquery('tx_commerce_baskets', $insertData);
		}

		$oneItem = $this->basketItems[$oneuid];
		if (is_object($oneItem)) {
			$oneItem->calculateNetSum();
			$oneItem->calculateGrossSum();
		}
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}

	/**
	 * Get frontend user
	 *
	 * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
	 */
	protected function getFrontendUser() {
		return $this->getFrontendController()->fe_user;
	}
}

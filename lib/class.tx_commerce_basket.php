<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Frontend libary for handling the basket. This class should be used
 * when rendering the basket and changing the basket items.
 *
 * The basket object is stored as object within the Frontend user
 * fe_user->tx_commerce_basket, you could acces the Basket object in the Frontend
 * via $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * Basic class for basket_handeling inhertited from tx_commerce_basic_basket
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_basket extends tx_commerce_basic_basket {

	/**
	 * @var string  Storage-type for the data
	 */
	protected $storageType = 'database';

	/**
	 * @var string  Not session id, as session_id is PHP5 method
	 */
	protected $sessionId = '';

	/**
	 * @var array The unserialized commerce configuration from localconf.php
	 */
	protected $extensionConfigration = array();

	/**
	 * Constructor for a commerce basket. Loads configuration data
	 */
	public function __construct() {
		$this->extensionConfigration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
		if ($this->extensionConfigration['basketType'] == 'persistent') {
			$this->storageType = 'persistent';
		}

		$this->database = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Set the session ID
	 *
	 * @param string $sessionId Session ID
	 * @return void
	 */
	public function set_session_id($sessionId) {
		$this->sessionId = $sessionId;
	}

	/**
	 * Returns the session ID
	 *
	 * @return string session ID
	 */
	public function get_session_id() {
		return $this->sess_id;
	}

	/**
	 * Finish order
	 *
	 * @return void
	 */
	public function finishOrder() {
		switch ($this->storageType) {
			case 'persistent':
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'txCommercePersistantSessionId', '');
				$GLOBALS['TSFE']->fe_user->storeSessionData();
				$this->finishOrderInDatabase();
			break;
			case 'database':
				$this->finishOrderInDatabase();
			break;
		}
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
		switch ($this->storageType) {
			case 'persistent':
				$this->restoreBasket();
			break;
			case 'database':
				$this->load_data_from_database();
			break;
		}
			// Method of Parent: Load the payment articcle if availiable
		parent::loadData();
	}

	/**
	 * Store basket data in session / database depending
	 * on $this->storageType
	 * Only database storage is implemented until now
	 *
	 * @return void
	 */
	public function store_data() {
		switch($this->storageType) {
			case 'persistent':
			case 'database':
				$this->store_data_to_database();
			break;
		}
	}

	/**
	 * Restores the Basket from the persistent storage
	 */
	private function restoreBasket() {
		if ($GLOBALS['TSFE']->fe_user->user) {
			$userSessionID = $GLOBALS['TSFE']->fe_user->getKey('user', 'txCommercePersistantSessionId');
			if ($userSessionID && $userSessionID != $this->sessionId) {
				$this->loadPersistantDataFromDatabase($userSessionID);
				$this->load_data_from_database();
				$GLOBALS['TSFE']->fe_user->setKey('user', 'txCommercePersistantSessionId', $this->sessionId);
				$this->store_data_to_database();
			} else {
				$this->load_data_from_database();
			}
		} else {
			$this->load_data_from_database();
		}
	}

	/**
	 * Loads the Basket Data from the database
	 * @todo handling for special prices
	 */
	private function loadPersistantDataFromDatabase($sessionID) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = $database->exec_SELECTquery('*',
			'tx_commerce_baskets',
			'sid=\'' . $database->quoteStr($sessionID, 'tx_commerce_baskets') . '\' and finished_time =0 and pid= ' .
				$this->extensionConfigration['BasketStoragePid'],
			'',
			'pos'
		);
		if ($database->sql_num_rows($result)) {
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['loadPersistantDataFromDatabase'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['loadPersistantDataFromDatabase'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}

			while ($returnData = $database->sql_fetch_assoc($result)) {
				if ($returnData['quantity'] > 0 && $returnData['price_id'] > 0) {
					$this->addArticle($returnData['article_id'], $returnData['quantity']);
					$this->crdate = $returnData['crdate'];
					if (is_array($hookObjectsArr)) {
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'loadPersistantDataFromDatabase')) {
								$hookObj->loadPersistantDataFromDatabase($returnData, $this);
							}
						}
					}
				}
			}
		}
		$database->sql_free_result($result);
	}

	/**
	 * Loads basket data from database
	 *
	 * @return void
	 */
	protected function load_data_from_database() {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['BasketStoragePid'] > 0) {
			$result = $database->exec_SELECTquery(
				'*',
				'tx_commerce_baskets',
				"sid='" . $database->quoteStr($this->sessionId, 'tx_commerce_baskets') . "'" .
					' AND finished_time = 0' .
					' AND pid= ' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['BasketStoragePid'],
				'',
				'pos'
			);
		} else {
			$result = $database->exec_SELECTquery(
				'*',
				'tx_commerce_baskets',
				"sid='" . $database->quoteStr($this->sessionId, 'tx_commerce_baskets') . "'" .
					' AND finished_time=0 ',
				'',
				'pos'
			);
		}

		if ($database->sql_num_rows($result)) {
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'] as $classRef) {
					$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
				}
			}
			$basketReadonly = FALSE;
			while ($return_data = $database->sql_fetch_assoc($result)) {
				if (($return_data['quantity'] > 0) && ($return_data['price_id'] > 0)) {
					$this->addArticle($return_data['article_id'], $return_data['quantity'], $return_data['price_id']);
					$this->changePrices($return_data['article_id'], $return_data['price_gross'], $return_data['price_net']);
					$this->crdate = $return_data['crdate'];
					if (is_array($hookObjectsArr)) {
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'load_data_from_database')) {
								$hookObj->load_data_from_database($return_data, $this);
							}
						}
					}
				}
				if ($return_data['readonly'] == 1) {
					$basketReadonly = TRUE;
				}
			}
			if ($basketReadonly === TRUE) {
				$this->setReadOnly();
			}
			$database->sql_free_result($result);
		}
	}

	/**
	 * Store basket data to database
	 *
	 * @return void
	 */
	protected function store_data_to_database() {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$database->exec_DELETEquery(
			'tx_commerce_baskets',
			'sid = \'' . $database->quoteStr($this->sessionId, 'tx_commerce_baskets') . '\' AND finished_time = 0'
		);
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
			}
		}

			// Get array keys from basket items to store correct position in basket
		$ar_basket_items_keys = array_keys($this->basket_items);
			// After getting the keys in a array, flip it to get the position of each basket item
		$ar_basket_items_keys = array_flip($ar_basket_items_keys);

		$oneuid = 0;
		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneuid  => $oneItem) {
			$insertData = array();
			$insertData['pid'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['BasketStoragePid'];
			$insertData['pos'] = $ar_basket_items_keys[$oneuid];
			$insertData['sid'] = $this->sessionId;
			$insertData['article_id'] = $oneItem->get_article_uid();
			$insertData['price_id'] = $oneItem->getPriceUid();
			$insertData['price_net'] = $oneItem->getPriceNet();
			$insertData['price_gross'] = $oneItem->getPriceGross();
			$insertData['quantity'] = $oneItem->getQuantity();
			$insertData['readonly'] = $this->getIsReadOnly();
			$insertData['tstamp'] = $GLOBALS['EXEC_TIME'];

			if ($this->crdate > 0) {
				$insertData['crdate'] = $this->crdate;
			} else {
				$insertData['crdate'] = $insertData['tstamp'];
			}

			if (is_array($hookObjectsArr)) {
				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'store_data_to_database')) {
						$insertData = $hookObj->store_data_to_database($oneItem, $insertData);
					}
				}
			}

			$database->exec_INSERTquery('tx_commerce_baskets', $insertData);
		}

		$oneItem = $this->basket_items[$oneuid];
		if (is_object($oneItem)) {
			$oneItem->calculate_net_sum();
			$oneItem->calculate_gross_sum();
		}
	}

	/**
	 * Set finish date in database
	 *
	 * @return void
	 */
	protected function finishOrderInDatabase() {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$updateArray = array(
			'finished_time' => $GLOBALS['EXEC_TIME'],
		);

		$database->exec_UPDATEquery(
			'tx_commerce_baskets',
			'sid=\'' . $database->quoteStr($this->sessionId, 'tx_commerce_baskets') . '\' AND finished_time = 0',
			$updateArray
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basket.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basket.php']);
}

?>
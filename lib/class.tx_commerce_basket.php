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
	 * @var Stoarge-type for the data
	 */
	protected $storage_type = 'database';


	/**
	 * @var Not session id, as session_id is PHP5 method
	 */
	protected $sess_id = '';

	/**
	 * Set the session ID
	 *
	 * @param string Session ID
	 * @return void
	 */
	public function set_session_id($session_id) {
		$this->sess_id = $session_id;
	}

	/**
	 * Finish order
	 *
	 * @return void
	 */
	function finishOrder() {
		switch($this->storage_type) {
			case 'database':
				$this->finishOrderInDatabase();
			break;
		}
	}

	/**
	 * Loads basket data from session / database depending
	 * on $this->storage_type
	 * Only database storagi is implemented until now
	 *
	 * @return void
	 */
	public function load_data() {
		switch($this->storage_type){
			case 'database':
				$this->load_data_from_database();
			break;
		}
			// Method of Parent: Load the payment articcle if availiable
		parent::load_data();
	}

	/**
	 * Store basket data in session / database depending
	 * on $this->storage_type
	 * Only database storagi is implemented until now
	 *
	 * @return void
	 */
	public function store_data() {
		switch($this->storage_type) {
			case 'database':
				$this->store_data_to_database();
			break;
		}
	}

	/**
	 * Loads basket data from database
	 *
	 * @return void
	 */
	protected function load_data_from_database() {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['BasketStoragePid'] > 0) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_commerce_baskets',
				"sid='" . $GLOBALS['TYPO3_DB']->quoteStr($this->sess_id, 'tx_commerce_baskets') . "'" .
					" AND finished_time=0" .
					" AND pid=" . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['BasketStoragePid'],
				'',
				'pos'
			);
		} else {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_commerce_baskets',
				"sid='" . $GLOBALS['TYPO3_DB']->quoteStr($this->sess_id, 'tx_commerce_baskets') . "'" .
					' AND finished_time=0 ',
				'',
				'pos'
			);
		}

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0) {
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'] as $classRef) {
					$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
				}
			}
			$basketReadonly = FALSE;
			while ($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if (($return_data['quantity']>0) && ($return_data['price_id']>0)) {
					$this->add_article($return_data['article_id'], $return_data['quantity'], $return_data['price_id']);
					$this->changePrices($return_data['article_id'], $return_data['price_gross'], $return_data['price_net']);
					$this->crdate = $return_data['crdate'];
					if (is_array($hookObjectsArr)) {
						foreach($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'load_data_from_database')) {
								$hookObj->load_data_from_database($return_data,$this);
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
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
		}
	}

	/**
	 * Store basket data to database
	 *
	 * @return void
	 */
	protected function store_data_to_database() {
		$result = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_commerce_baskets',
			"sid='".$GLOBALS['TYPO3_DB']->quoteStr($this->sess_id,'tx_commerce_baskets')."'" .
				' AND finished_time = 0'
		);
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
			}
		}

			// Get array keys from basket items to store correct position in basket
		$ar_basket_items_keys = array_keys($this->basket_items);
			// After getting the keys in a array, flip it to get the position of each basket item
		$ar_basket_items_keys = array_flip($ar_basket_items_keys);

		foreach ($this->basket_items as $oneuid  => $one_item) {
			$insert_data['pid'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['BasketStoragePid'];
			$insert_data['pos'] = $ar_basket_items_keys[$oneuid];
			$insert_data['sid'] = $this->sess_id;
			$insert_data['article_id'] = $one_item->get_article_uid();
			$insert_data['price_id'] = $one_item->get_price_uid();
			$insert_data['price_net'] = $one_item->get_price_net();
			$insert_data['price_gross'] = $one_item->get_price_gross();
			$insert_data['quantity'] = $one_item->get_quantity();
			$insert_data['readonly'] = $this->isReadOnly();
			$insert_data['tstamp'] = $GLOBALS['EXEC_TIME'];

			if ($this->crdate >0 ) {
				$insert_data['crdate'] = $this->crdate;
			}else {
				$insert_data['crdate'] = $insert_data['tstamp'];
			}

			if (is_array($hookObjectsArr)) {
				foreach($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'store_data_to_database')) {
						$insert_data = $hookObj->store_data_to_database($one_item, $insert_data);
					}
				}
			}

			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_commerce_baskets',
				$insert_data
			);
		}

		if (is_object($this->basket_items[$oneuid])) {
			$this->basket_items[$oneuid]->calculate_net_sum();
			$this->basket_items[$oneuid]->calculate_gross_sum();
		}
	}

	/**
	 * Set finish date in database
	 *
	 * @return void
	 */
	protected function finishOrderInDatabase() {
		$update_array['finished_time'] = $GLOBALS['EXEC_TIME'];
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_commerce_baskets',
			"sid='" . $GLOBALS['TYPO3_DB']->quoteStr($this->sess_id,'tx_commerce_baskets') . "'" .
				' AND finished_time = 0',
			$update_array
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basket.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basket.php']);
}

?>
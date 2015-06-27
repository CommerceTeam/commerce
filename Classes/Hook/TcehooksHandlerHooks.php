<?php
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

use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * This class handles backend updates
 *
 * Class Tx_Commerce_Hook_TcehooksHandlerHooks
 *
 * @author 2005-2013 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Hook_TcehooksHandlerHooks {
	/**
	 * At this place we process prices, before they are written to the database.
	 * We use this for tax calculation
	 *
	 * @param array $incomingFieldArray The values from the form, by reference
	 * @param string $table The table we are working on
	 * @param int $id The uid we are working on
	 * @param DataHandler $pObj The caller
	 *
	 * @return void
	 */
	public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, $table, $id, DataHandler $pObj) {
		if ($table == 'tx_commerce_article_prices') {
			// Get the whole price, not only the tce-form fields
			foreach ($pObj->datamap['tx_commerce_articles'] as $v) {
				$uids = explode(',', $v['prices']);
				if (in_array($id, $uids) && ($incomingFieldArray['price_net'] || $incomingFieldArray['price_gross'])) {
					$this->calculateTax($incomingFieldArray, doubleval($v['tax']));
				}
			}

			foreach ($incomingFieldArray as $key => $value) {
				if ($key == 'price_net' || $key == 'price_gross' || $key == 'purchase_price') {
					if (is_numeric($value)) {
						// first convert the float value to a string - this is required
						// because of a php "bug" details on https://forge.typo3.org/issues/2986
						$incomingFieldArray[$key] = (int) strval($value * 100);
					}
				}
			}
		}
	}

	/**
	 * This function is called by the Hook in tce
	 * after processing insert & update database operations
	 *
	 * @param string $status Update or new
	 * @param string $table Database table
	 * @param string $id Database table
	 * @param array $fieldArray Reference to the incoming fields
	 *
	 * @return void
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, array &$fieldArray) {
		if ($table == 'tx_commerce_article_prices') {
			// ugly hack since typo3 makes ugly checks
			foreach ($fieldArray as $key => $value) {
				if ($key == 'price_net' || $key == 'price_gross' || $key == 'purchase_price') {
					$fieldArray[$key] = (int) $value;
				}
			}
		}
	}

	/**
	 * This function is called by the Hook in tce
	 * after processing insert & update database operations
	 *
	 * @param string $status Update or new
	 * @param string $table Database table
	 * @param string $id Database table
	 * @param array $fieldArray Reference to the incoming fields
	 * @param DataHandler $pObj Data handler
	 *
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, array &$fieldArray, DataHandler &$pObj) {
		if ($table == 'fe_users') {
			if ($status == 'new' || empty($fieldArray['tx_commerce_tt_address_id'])) {
				$this->notifyFeuserObserver($status, $table, $id, $fieldArray, $pObj);
			} else {
				$emptyArray = array();
				$this->notifyAddressObserver($status, $table, $fieldArray['tx_commerce_tt_address_id'], $emptyArray, $pObj);
			}
		}

		if ($table == 'tt_address') {
			$this->notifyAddressObserver($status, $table, $id, $fieldArray, $pObj);
		}

		if (
			$table == 'tx_commerce_articles'
			&& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode']
			&& ($articleId = $pObj->substNEWwithIDs[$id])
		) {
			$database = $this->getDatabaseConnection();

			// Now check, if the parent Product is already lokalised, so creat Article in
			// the localised version Select from Database different localisations
			$resOrigArticle = $database->exec_SELECTquery(
				'*',
				'tx_commerce_articles',
				'uid = ' . (int) $articleId . ' and deleted = 0'
			);
			$origArticle = $database->sql_fetch_assoc($resOrigArticle);
			$resLocalisedProducts = $database->exec_SELECTquery(
				'*',
				'tx_commerce_products',
				'l18n_parent = ' . (int) $origArticle['uid_product'] . ' and deleted = 0'
			);
			if ($resLocalisedProducts && $database->sql_num_rows($resLocalisedProducts) > 0) {
				// Only if there are products
				while (($localisedProducts = $database->sql_fetch_assoc($resLocalisedProducts))) {
						// create article data array
					$articleData = array(
						'pid' => (int) $fieldArray['pid'],
						'crdate' => $GLOBALS['EXEC_TIME'],
						'title' => $fieldArray['title'],
						'uid_product' => (int) $localisedProducts['uid'],
						'sys_language_uid' => (int) $localisedProducts['sys_language_uid'],
						'l18n_parent' => (int) $articleId,
						'sorting' => (int) $fieldArray['sorting'] * 2,
						'article_type_uid' => (int) $fieldArray['article_type_uid'],
					);

					// create the article
					$database->exec_INSERTquery('tx_commerce_articles', $articleData);
				}
			}
		}
	}

	/**
	 * Function is called by the Hook in tce
	 * before processing commands
	 *
	 * @param string $command Reference to: move,copy,version,delete or undelete
	 * @param string $table Database table
	 * @param string $id Database record uid
	 *
	 * @return void
	 */
	public function processCmdmap_preProcess(&$command, $table, $id) {
		if ($table == 'tt_address' && $command == 'delete') {
			if ($this->checkAddressDelete($id)) {
				// remove delete command
				$command = '';
			};
		}
	}

	/**
	 * Calculate tax
	 *
	 * @param array $fieldArray Field values
	 * @param int $tax Tax percentage
	 *
	 * @return void
	 */
	protected function calculateTax(array &$fieldArray, $tax) {
		$extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];
		if ($extConf['genprices'] > 0) {
			if (
				$extConf['genprices'] == 2
				|| !isset($fieldArray['price_gross'])
				|| $fieldArray['price_gross'] === ''
				|| strlen($fieldArray['price_gross']) == 0
				|| doubleval($fieldArray['price_gross']) === 0.0
			) {
				$fieldArray['price_gross'] = round(($fieldArray['price_net'] * 100) * (100 + $tax) / 100) / 100;
			}
			if (
				$extConf['genprices'] == 3
				|| !isset($fieldArray['price_net'])
				|| $fieldArray['price_net'] === ''
				|| strlen($fieldArray['price_net']) == 0
				|| doubleval($fieldArray['price_net']) === 0.0
			) {
				$fieldArray['price_net'] = round(($fieldArray['price_gross'] * 100) / (100 + $tax) * 100) / 100;
			}
		}
	}

	/**
	 * Notify feuser observer
	 * get id and notify observer
	 *
	 * @param string $status Status [update,new]
	 * @param string $table Database table
	 * @param string $id Id
	 * @param array $fieldArray Reference to the incoming fields
	 * @param DataHandler $pObj Data handler
	 *
	 * @return void
	 */
	protected function notifyFeuserObserver($status, $table, $id, array &$fieldArray, DataHandler &$pObj) {
		// get id
		if ($status == 'new') {
			$id = $pObj->substNEWwithIDs[$id];
		}

		// notify observer
		Tx_Commerce_Dao_FeuserObserver::update($status, $id);
	}

	/**
	 * Notify address observer
	 * check status and notify observer
	 *
	 * @param string $status Status [update,new]
	 * @param string $table Database table
	 * @param string $id Id
	 * @param array $fieldArray Reference to the incoming fields
	 * @param DataHandler $pObj Parent object
	 *
	 * @return void
	 */
	protected function notifyAddressObserver($status, $table, $id, array &$fieldArray, DataHandler &$pObj) {
		// get id
		if ($status == 'new') {
			$id = $pObj->substNEWwithIDs[$id];
		}

		// if address is updated
		if ($status == 'update') {
			// notify observer
			Tx_Commerce_Dao_AddressObserver::update($status, $id);
		}
	}

	/**
	 * Check if an address is deleted
	 *
	 * @param int $id Id
	 *
	 * @return bool|string
	 */
	protected function checkAddressDelete($id) {
		return Tx_Commerce_Dao_AddressObserver::checkDelete($id);
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

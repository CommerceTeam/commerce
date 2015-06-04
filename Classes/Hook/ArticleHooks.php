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

/**
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * Hook for article_class
 * This class is ment as programming-tutorial
 * for programming hooks for delivery_costs
 *
 * Class Tx_Commerce_Hook_ArticleHooks
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Hook_ArticleHooks {
	/**
	 * Basic Method to calculate the delivereycost (net)
	 * Ment as Programming tutorial. Mostly you have to change or add functionality
	 *
	 * @param int &$netPrice
	 * @param Tx_Commerce_Domain_Model_Article &$article
	 * @return void
	 */
	public function calculateDeliveryCostNet(&$netPrice, &$article) {
		$deliveryArticle = $this->getDeliveryArticle($article);
		if ($deliveryArticle) {
			$netPrice = $deliveryArticle->getPriceNet();
		} else {
			$netPrice = 0;
		}
	}

	/**
	 * Basic Method to calculate the delivereycost (gross)
	 * Ment as Programming tutorial. Mostly you have to change or add functionality
	 *
	 * @param int &$grossPrice
	 * @param Tx_Commerce_Domain_Model_Article &$article
	 * @return void
	 */
	public function calculateDeliveryCostGross(&$grossPrice, &$article) {
		$deliveryArticle = $this->getDeliveryArticle($article);
		if ($deliveryArticle) {
			$grossPrice = $deliveryArticle->getPriceGross();
		} else {
			$grossPrice = 0;
		}
	}

	/**
	 * Load the deliveryArticle
	 *
	 * @param Tx_Commerce_Domain_Model_Article &$article
	 * @return Tx_Commerce_Domain_Model_Article $result
	 */
	protected function getDeliveryArticle(&$article) {
		$deliveryConf = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS']['DELIVERY']['types']);
		$classname = array_shift(array_keys($deliveryConf));

		$database = $this->getDatabaseConnection();

		$row = $database->exec_SELECTgetSingleRow(
			'uid',
			'tx_commerce_articles',
			'classname = \'' . $classname . '\''
		);

		$result = FALSE;
		if (!empty($row)) {
			$deliveryArticleUid = $row['uid'];

			/**
			 * Instantiate article class
			 *
			 * @var Tx_Commerce_Domain_Model_Article $deliveryArticle
			 */
			$deliveryArticle = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Article', $deliveryArticleUid, $article->getLang());

			/**
			 * Do not call loadData at this point, since loadData recalls this hook,
			 * so we have a non endingrecursion
			 */
			if (is_object($deliveryArticle)) {
				$deliveryArticle->loadPrices();
			}
			$result = $deliveryArticle;
		}

		return $result;
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

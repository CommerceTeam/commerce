<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Basic class for basket_handling
 *
 * Abstract libary for Basket Handling. This class should not be used directly,
 * instead use tx_commerce_basket.
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @todo: Implement basket as singleton
 */
class Tx_Commerce_Domain_Model_BasicBasket {
	/**
	 * @var array Associative array for storing basket_items in the basket
	 */
	protected $basketItems = array();

	/**
	 * @var integer Net basket sum
	 */
	protected $basketSumNet = 0;

	/**
	 * @var integer Gross basket sum
	 */
	protected $basketSumGross = 0;

	/**
	 * @var integer Calculated pric from net price
	 */
	protected $pricefromnet = 0;

	/**
	 * @var Number of items in basket
	 */
	protected $items = 0;

	/**
	 * @var integer Creation timestamp of this basket
	 */
	protected $crdate = 0;

	/**
	 * @var boolean True if basket is set to read only
	 */
	protected $readOnly = FALSE;

	/**
	 * @var array
	 */
	public $conf = array();

	/**
	 * Get count of all articles of this type
	 * Useful for geting the delivery cost
	 *
	 * Example:
	 * $basket->getArticleTypeSumNet(PAYMENTARTICLETYPE)
	 * $basket->getArticleTypeSumNet(DELIVERYARTICLETYPE)
	 *
	 * @param integer $articleTypeUid
	 * @return integer Count
	 */
	public function getArticleTypeCount($articleTypeUid) {
		$count = 0;

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneItem) {
			if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get count of all articles of a specific type
	 *
	 * @param array $articleTypes
	 * @return integer
	 */
	public function getArticleTypeCountFromList($articleTypes) {
		$count = 0;

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneItem) {
			if (in_array($oneItem->getArticle()->getArticleTypeUid(), $articleTypes)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Return sum of all articles of this type
	 * Useful to geting the delivery cost
	 *
	 * Example:
	 * $basket->getArticleTypeSumNet(PAYMENTARTICLETYPE)
	 * $basket->getArticleTypeSumNet(DELIVERYARTICLETYPE)
	 *
	 * @param integer $articleTypeUid
	 * @return integer Price
	 */
	public function getArticleTypeSumNet($articleTypeUid) {
		$sumNet = 0;

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		if ($this->pricefromnet == 0) {
			$grossSumArray = array();
			foreach ($this->basketItems as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$grossSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumGross();
				}
			}
			foreach ($grossSumArray as $taxrate => $rateGrossSum) {
				$sumNet += (int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basketItems as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$sumNet += ($oneItem->getQuantity() * $oneItem->getPriceNet());
				}
			}
		}

		return $sumNet;
	}

	/**
	 * Return sum of all article prices of this type
	 * Useful for geting the delivery cost
	 *
	 * Example:
	 * $basket->getArticleTypeSumGross(PAYMENTARTICLETYPE)
	 * $basket->getArticleTypeSumGross(DELIVERYARTICLETYPE)
	 *
	 * @param integer $articleTypeUid
	 * @return integer sum
	 */
	public function getArticleTypeSumGross($articleTypeUid) {
		$sumGross = 0;

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		if ($this->pricefromnet == 1) {
			$netSumArray = array();
			foreach ($this->basketItems as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$netSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumNet();
				}
			}
			foreach ($netSumArray as $taxrate => $rateGrossSum) {
				$sumGross += (int)round($rateGrossSum * (1 + (((float) $taxrate) / 100)));
			}
		} else {
			foreach ($this->basketItems as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$sumGross += ($oneItem->getQuantity() * $oneItem->getPriceGross());
				}
			}
		}
		return $sumGross;
	}

	/**
	 * Returns an array of articles to a corresponding article_type
	 *
	 * @param integer $articleTypeUid Article type
	 * @return array or article_ids
	 */
	public function getArticlesByArticleTypeUidAsUidlist($articleTypeUid) {
		$result = array();

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $uid => $oneItem) {
			if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
				$result[] = $uid;
			}
		}

		return $result;
	}

	/**
	 * Create an array of assoc arrays from the basket articles
	 *
	 * array (uid => array(
	 * 		'article' => result form tx_commerce_article->return_assoc_array(),
	 * 		'product' => result form tx_commerce_product->return_assoc_array(),
	 * ),
	 * uid2 =>array(
	 * 		'article' => result form tx_commerce_article->return_assoc_array();
	 * 		'product' => result form tx_commerce_product->return_assoc_array();
	 * ),
	 *
	 * @param string $prefix Prefix for the keys
	 * @return array or arrays
	 */
	public function getAssocArrays($prefix = '') {
		$result = array();

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneuid => $oneItem) {
			$result[$oneuid] = $oneItem->getArrayOfAssocArray($prefix);
		}

		return $result;
	}

	/**
	 * Return basket has value
	 *
	 * @return string Basket hash value
	 */
	public function getBasketHashValue() {
		$result = FALSE;

		if (!$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_commerce_pi1.']['dontUseBasketHashValue'] && count($this->basketItems) > 0) {
			$result = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(serialize($this->basketItems));
		}

		return $result;
	}

	/**
	 * Get a specific item object from basket
	 *
	 * @param integer $itemUid The item uid to get
	 * @return Tx_Commerce_Domain_Model_BasketItem Item object
	 * @throws Exception
	 */
	public function getBasketItem($itemUid) {
		return $this->basketItems[$itemUid];
	}

	/**
	 * Getter
	 *
	 * @return array
	 */
	public function getBasketItems() {
		return $this->basketItems;
	}

	/**
	 * Get first title from of all articles concerning this type
	 *
	 * Eexample:
	 * $basket->getFirstArticleTypeTitle(PAYMENTARTICLETYPE)
	 * $basket->getFirstArticleTypeTitle(DELIVERYARTICLETYPE)
	 *
	 * @param integer $articleTypeUid
	 * @return string Title
	 */
	public function getFirstArticleTypeTitle($articleTypeUid) {
		$result = '';

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneItem) {
			if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
				if ($oneItem->getArticle()->getTitle() > '') {
					$result = $oneItem->getArticle()->getTitle();
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the first Description from of all Articles concerning this type
	 *
	 * Example:
	 * $basket->getFirstArticleTypeDescription(PAYMENTARTICLETYPE)
	 * $basket->getFirstArticleTypeDescription(DELIVERYARTICLETYPE)
	 *
	 * @param string $articleTypeUid
	 * @return string Description
	 */
	public function getFirstArticleTypeDescription($articleTypeUid) {
		$result = '';

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneItem) {
			if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
				if ($oneItem->getArticle()->getDescriptionExtra() > '') {
					$result = $oneItem->getArticle()->getDescriptionExtra();
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Get number of items in basket
	 *
	 * @return integer Number of items
	 */
	public function getItemsCount() {
		return count($this->basketItems);
	}

	/**
	 * Get price gross of an article in basket
	 *
	 * @param integer $articleUid Article uid
	 * @return mixed Integer price gross or FALSE if item is not in basket
	 */
	public function getPriceGross($articleUid) {
		if (is_object($this->basketItems[$articleUid])) {
			/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
			$basketItem = $this->basketItems[$articleUid];
			return $basketItem->getItemSumGross();
		}
		return FALSE;
	}

	/**
	 * Get price net of an article in basket
	 *
	 * @param integer $articleUid Article uid
	 * @return mixed Integer price net or FALSE if item is not in basket
	 */
	public function getPriceNet($articleUid) {
		if (is_object($this->basketItems[$articleUid])) {
			/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
			$basketItem = $this->basketItems[$articleUid];
			return $basketItem->getItemSumNet();
		}
		return FALSE;
	}

	/**
	 * Return quantity of an article in basket
	 *
	 * @param integer $articleUid Uid of article
	 * @return integer Current quantity
	 */
	public function getQuantity($articleUid) {
		if (is_object($this->basketItems[$articleUid])) {
			/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
			$basketItem = $this->basketItems[$articleUid];
			return $basketItem->getQuantity();
		}
		return 0;
	}

	/**
	 * Set to read only, for checkout
	 *
	 * @return void
	 */
	public function setReadOnly() {
		$this->readOnly = TRUE;
	}

	/**
	 * Get read only state
	 *
	 * @return boolean TRUE if read only
	 */
	public function getReadOnly() {
		return $this->readOnly;
	}

	/**
	 * Get basket gross sum
	 *
	 * @return integer Basket gross sum
	 */
	public function getSumGross() {
		$lokalSum = 0;

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		if ($this->pricefromnet == 1) {
			$netSumArray = array();

			foreach ($this->basketItems as $oneItem) {
				$netSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumNet();
			}

			foreach ($netSumArray as $taxrate => $rateNetSum) {
				$lokalSum += (int) round($rateNetSum * (1 + (((float) $taxrate) / 100)));
			}
		} else {
			foreach ($this->basketItems as $oneItem) {
				$lokalSum += $oneItem->getItemSumGross();
			}
		}
		$this->basketSumGross = $lokalSum;

		return $this->basketSumGross;
	}

	/**
	 * Get basket net sum
	 *
	 * @return integer Basket net sum
	 */
	public function getSumNet() {
		$lokalSum = 0;

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		if ($this->pricefromnet == 0) {
			$grossSumArray = array();
			foreach ($this->basketItems as $oneItem) {
				$grossSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumGross();
			}
			foreach ($grossSumArray as $taxrate => $rateGrossSum) {
				$lokalSum += (int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basketItems as $oneItem) {
				$lokalSum += $oneItem->getItemSumNet();
			}
		}
		$this->basketSumNet = $lokalSum;

		return $lokalSum;
	}

	/**
	 * Calculates the TAX-Sum for the complete Basket
	 *
	 * @return integer Sum
	 */
	public function getTaxSum() {
		$taxSum = 0;
		$taxRatesSums = $this->getTaxRateSums();
		foreach ($taxRatesSums as $taxRateSum) {
			$taxSum += $taxRateSum;
		}
		return $taxSum;
	}

	/**
	 * Calculates the TAX-Sum for the complete and different Tax-Rates
	 * depending on article
	 *
	 * @return array Taxratesums
	 */
	public function getTaxRateSums() {
		$taxes = array();

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneItem) {
			$taxRate = $oneItem->getTax();
			$taxRate = (string) $taxRate;

			if ($this->pricefromnet == 1) {
				$taxSum = ($oneItem->getItemSumNet() * (((float) $taxRate) / 100));
			} else {
				$taxSum = ($oneItem->getItemSumGross() * ((((float) $taxRate) / 100) / (1 + (((float)$taxRate) / 100))));
			}

			if (!isset($taxes[$taxRate]) AND $taxSum <= 0) {
				continue;
			}

			if (!isset($taxes[$taxRate])) {
				$taxes[$taxRate] = 0;
			}

			$taxes[$taxRate] += $taxSum;
		}
		foreach ($taxes as $taxRate => $taxSum) {
			$taxes[$taxRate] = (int) round($taxSum);
		}
		return $taxes;
	}


	/**
	 * Load basket data from session / database
	 *
	 * @return void
	 */
	public function loadData() {
			// Check if payment article is available and set default if not
		if (count($this->getArticlesByArticleTypeUidAsUidlist(PAYMENTARTICLETYPE)) < 1) {
			$this->addArticle($this->conf['defaultPaymentArticleId']);
		}
	}

	/**
	 * Add an article to the basket
	 *
	 * @param integer $articleUid Article uid
	 * @param integer $quantity Quantity of this basket item
	 * @param string $priceid
	 * @return boolean TRUE on successful change
	 */
	public function addArticle($articleUid, $quantity = 1, $priceid = '') {
		if ($articleUid && $this->isChangeable()) {
			if (is_object($this->basketItems[$articleUid]) || ($quantity == 0)) {
				$this->changeQuantity($articleUid, $quantity);
			} else {
				/** @var Tx_Commerce_Domain_Model_Article $article */
				$article = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_Article',
					$articleUid,
					$this->getFrontendController()->tmpl->setup['config.']['sys_language_uid']
				);
				$article->loadData('basket');

				$priceids = $article->getPriceUids();
				if (is_array($priceids)) {
						// Check if the given price id is related to the article
					if (!in_array($priceid, $priceids)) {
						$priceid = '';
					}
				}

				if ($priceid == '') {
						// no priceid is given,. get the price from article_object
					$priceid = $article->getActualPriceforScaleUid($quantity);
					if (!$priceid) {
						$priceid = $article->getPriceUid();
					}
				}

				/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
				$basketItem = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_BasketItem',
					$articleUid,
					$quantity,
					$priceid,
					$this->getFrontendController()->tmpl->setup['config.']['sys_language_uid']
				);
				if ($basketItem->article) {
					$basketItem->setTaxCalculationMethod($this->pricefromnet);
					$this->recalculateSums();
				}
				$this->basketItems[$articleUid] = $basketItem;
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Change the price_value of an article
	 *
	 * @param integer $articleUid Arcicle uid
	 * @param integer $newPriceGross New price gross
	 * @param integer $newPriceNet New price net
	 * @return void
	 */
	public function changePrices($articleUid, $newPriceGross, $newPriceNet) {
		if ($this->isChangeable()) {
			if ((is_object($this->basketItems[$articleUid])) && (is_a($this->basketItems[$articleUid], 'tx_commerce_basket_item'))) {
				/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
				$basketItem = $this->basketItems[$articleUid];
				$basketItem->setPriceNet($newPriceNet);
				$basketItem->setPriceGross($newPriceGross);
				$basketItem->recalculateItemSums(TRUE);
			}
		}
	}

	/**
	 * Change title of an article in basket
	 *
	 * @param int $articleUid Article uid
	 * @param string $newtitle New article title
	 * @return void
	 */
	public function changeTitle($articleUid, $newtitle) {
		if ($this->isChangeable()) {
			if (is_object($this->basketItems[$articleUid])) {
				/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
				$basketItem = $this->basketItems[$articleUid];
				$basketItem->setTitle($newtitle);
			}
		}
	}

	/**
	 * Change quantity of an article in basket
	 *
	 * @param integer $articleUid Article uid
	 * @param integer $quantity New quantity
	 * @return mixed TRUE on success, FALSE if quantity can not be changed,
	 * 		and integer sometimes as well ...
	 */
	public function changeQuantity($articleUid, $quantity = 1) {
		if ($this->isChangeable()) {
			if ($quantity == 0) {
				if (isset($this->basketItems[$articleUid])) {
					$this->deleteArticle($articleUid);
				}
				$items = $this->getArticlesByArticleTypeUidAsUidlist(NORMALARTICLETYPE);
				if (count($items) == 0) {
					$this->deleteAllArticles();
				}
				return TRUE;
			}
			$this->recalculateSums();

			/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
			$basketItem = $this->basketItems[$articleUid];
			return $basketItem->changeQuantity($quantity);
		}

		return FALSE;
	}

	/**
	 * Remove an article from basket
	 *
	 * @param integer $articleUid Article uid
	 * @return boolean TRUE on success
	 */
	public function deleteArticle($articleUid) {
		if ($this->isChangeable()) {
			if (!isset($this->basketItems[$articleUid])) {
				return FALSE;
			}
			unset($this->basketItems[$articleUid]);
			$this->recalculateSums();
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Clear basket
	 *
	 * @return boolean TRUE on success
	 */
	public function deleteAllArticles() {
		if ($this->isChangeable()) {
			$this->basketItems = array();
			$this->recalculateSums();
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Recalculate price sums
	 *
	 * @return void
	 */
	public function recalculateSums() {
		$this->getSumNet();
		$this->getSumGross();
	}

	/**
	 * Wether or not ther are article is basket
	 *
	 * @return boolean TRUE if count of articles is greater than 0
	 */
	public function hasArticles() {
		return count($this->basketItems) > 0;
	}

	/**
	 * This Method Sets the Tax Calculation method (pricefromnet)
	 *
	 * @param boolean $priceFromNet Switch if calculationg from net or not
	 * @return void
	 */
	public function setTaxCalculationMethod($priceFromNet) {
		$this->pricefromnet = $priceFromNet;

		/** @var Tx_Commerce_Domain_Model_BasketItem $oneItem */
		foreach ($this->basketItems as $oneItem) {
			$oneItem->setTaxCalculationMethod($this->pricefromnet);
		}
	}

	/**
	 * Wether or not basket is locket
	 *
	 * @return boolean TRUE if locked
	 */
	public function isChangeable() {
		return !$this->readOnly;
	}

	/**
	 * Set read only state to false
	 *
	 * @return void
	 */
	public function releaseReadOnly() {
		$this->readOnly = FALSE;
	}

	/**
	 * Returns first basket item by the given article type
	 *
	 * @param int $articleType
	 * @return Tx_Commerce_Domain_Model_BasketItem
	 */
	public function getCurrentBasketItemByArticleType($articleType) {
		$result = NULL;

		/** @var Tx_Commerce_Domain_Model_BasketItem $basketItem */
		foreach ($this->basketItems as $basketItem) {
			if ((int)$basketItem->getArticleTypeUid() === (int)$articleType) {
				$result = $basketItem;
				break;
			}
		}

		return $result;
	}

	/**
	 * Get current payment article
	 *
	 * @return Tx_Commerce_Domain_Model_BasketItem
	 */
	public function getCurrentPaymentBasketItem() {
		return $this->getCurrentBasketItemByArticleType(PAYMENTARTICLETYPE);
	}

	/**
	 * Get current delivery article
	 *
	 * @return Tx_Commerce_Domain_Model_BasketItem
	 */
	public function getCurrentDeliveryBasketItem() {
		return $this->getCurrentBasketItemByArticleType(DELIVERYARTICLETYPE);
	}

	/**
	 * Remove current payment article from basket
	 *
	 * @return void
	 */
	public function removeCurrentPaymentArticle() {
		$paymentBasketItem = $this->getCurrentPaymentBasketItem();
		if (is_object($paymentBasketItem)) {
			$this->deleteArticle($paymentBasketItem->getArticleUid());
		}
	}

	/**
	 * Remove current delivery article from basket
	 *
	 * @return void
	 */
	public function removeCurrentDeliveryArticle() {
		$deliveryBasketItem = $this->getCurrentDeliveryBasketItem();
		if (is_object($deliveryBasketItem)) {
			$this->deleteArticle($deliveryBasketItem->getArticleUid());
		}
	}

	/**
	 * Recalculate price sums
	 *
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use deleteAllArticles instead
	 */
	public function recalculate_sums() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->recalculateSums();
	}

	/**
	 * Clear basket
	 *
	 * @return boolean TRUE on success
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use deleteAllArticles instead
	 */
	public function delete_all_articles() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->deleteAllArticles();
	}

	/**
	 * Remove an article from basket
	 *
	 * @param integer $article_uid Article uid
	 * @return boolean TRUE on success
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use deleteArticle instead
	 */
	public function delete_article($article_uid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->deleteArticle($article_uid);
	}

	/**
	 * Change quantity of an article in basket
	 *
	 * @param integer $articleUid Article uid
	 * @param integer $quantity New quantity
	 * @return mixed TRUE on success, FALSE if quantity can not be changed, and integer sometimes as well ...
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use changeQuantity instead
	 */
	public function change_quantity($articleUid, $quantity = 1) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->changeQuantity($articleUid, $quantity);
	}

	/**
	 * Returns an array of articles to a corresponding article_type
	 *
	 * @param integer $articleTypeUid Article type
	 * @return array or article_ids
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getArticlesByArticleTypeUidAsUidlist instead
	 */
	public function get_articles_by_article_type_uid_asUidlist($articleTypeUid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->getArticlesByArticleTypeUidAsUidlist($articleTypeUid);
	}

	/**
	 * Create an array of assoc arrays from the basket articles
	 *
	 * array (uid => array(
	 * 		'article' => result form tx_commerce_article->return_assoc_array(),
	 * 		'product' => result form tx_commerce_product->return_assoc_array(),
	 * ),
	 * uid2 =>array(
	 * 		'article' => result form tx_commerce_article->return_assoc_array();
	 * 		'product' => result form tx_commerce_product->return_assoc_array();
	 * ),
	 *
	 * @param string $prefix Prefix for the keys
	 * @return array or arrays
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getAssocArrays instead
	 */
	public function get_assoc_arrays($prefix = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getAssocArrays($prefix);
	}

	/**
	 * Add an article to the basket
	 *
	 * @param integer $articleUid Article uid
	 * @param integer $quantity Quantity of this basket item
	 * @param string $priceid
	 * @return boolean TRUE on successful change
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use addArticle instead
	 */
	public function add_article($articleUid, $quantity = 1, $priceid = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->addArticle($articleUid, $quantity, $priceid);
	}

	/**
	 * @return boolean
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getReadOnly instead
	 */
	public function isReadOnly() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getReadOnly();
	}

	/**
	 * Get read only state
	 *
	 * @return boolean TRUE if read only
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getReadOnly instead
	 */
	public function getIsReadOnly() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getReadOnly();
	}

	/**
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getSumGross instead
	 */
	public function get_gross_sum() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getSumGross();
	}

	/**
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getSumNet instead
	 */
	public function get_net_sum() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->getSumNet();
	}


	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}
}

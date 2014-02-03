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
 * Basic class for basket_handling
 *
 * Abstract libary for Basket Handling. This class should not be used directly,
 * instead use tx_commerce_basket.
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 *
 * @TODO: Implement basket as singleton
 */
class tx_commerce_basic_basket {

	/**
	 * @var array Associative array for storing basket_items in the basket
	 * @TODO: Make protected
	 */
	protected $basket_items = array();

	/**
	 * @var integer Net basket sum
	 */
	protected $basket_sum_net = 0;

	/**
	 * @var integer Gross basket sum
	 */
	protected $basket_sum_gross = 0;

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
	 * Load basket data from session / database
	 *
	 * @return void
	 */
	public function loadData() {
			// Check if payment article is available and set default if not
		if (count($this->get_articles_by_article_type_uid_asUidlist(PAYMENTARTICLETYPE)) < 1) {
			$this->add_article($this->conf['defaultPaymentArticleId']);
		}
	}

	/**
	 * Add an article to the basket
	 *
	 * @param integer $articleUid Article uid
	 * @param integer $quantity Quantity of this basket item
	 * @param string $priceid
	 * @return boolean TRUE on successful change
	 * @TODO Implement methiod is_in_basket
	 */
	public function add_article($articleUid, $quantity = 1, $priceid = '') {
		if ($articleUid && $this->isChangeable()) {
			if (is_object($this->basket_items[$articleUid]) || ($quantity == 0)) {
				$this->change_quantity($articleUid, $quantity);
			} else {
				/** @var tx_commerce_article $article */
				$article = t3lib_div::makeInstance('tx_commerce_article');
				$article->init($articleUid, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
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

				/** @var tx_commerce_basket_item $basketItem */
				$basketItem = t3lib_div::makeInstance('tx_commerce_basket_item');
				if ($basketItem->init($articleUid, $quantity, $priceid, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'])) {
					$basketItem->setTaxCalculationMethod($this->pricefromnet);
					$this->recalculate_sums();
					$this->items++;
				}
				$this->basket_items[$articleUid] = $basketItem;
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Change the price_value of an article
	 *
	 * @param integer $articleUid Arcicle uid
	 * @param integer $new_price_gross New price gross
	 * @param integer $new_price_net New price net
	 * @return void
	 */
	public function changePrices($articleUid, $new_price_gross, $new_price_net) {
		if ($this->isChangeable()) {
			if ((is_object($this->basket_items[$articleUid])) && (is_a($this->basket_items[$articleUid], 'tx_commerce_basket_item'))) {
				/** @var tx_commerce_basket_item $basketItem */
				$basketItem = $this->basket_items[$articleUid];
				$basketItem->setPriceNet($new_price_net);
				$basketItem->setPriceGross($new_price_gross);
				$basketItem->recalculate_item_sums(TRUE);
			}
		}
	}

	/**
	 * Get price gross of an article in basket
	 *
	 * @param integer $articleUid Article uid
	 * @return mixed Integer price gross or FALSE if item is not in basket
	 */
	public function getPriceGross($articleUid) {
		if (is_object($this->basket_items[$articleUid])) {
			/** @var tx_commerce_basket_item $basketItem */
			$basketItem = $this->basket_items[$articleUid];
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
		if (is_object($this->basket_items[$articleUid])) {
			/** @var tx_commerce_basket_item $basketItem */
			$basketItem = $this->basket_items[$articleUid];
			return $basketItem->getItemSumNet();
		}
		return FALSE;
	}

	/**
	 * Change title of an article in basket
	 * @param integer $articleUid Article uid
	 * @param string $newtitle New article title
	 */
	public function changeTitle($articleUid,$newtitle) {
		if ($this->isChangeable()) {
			if (is_object($this->basket_items[$articleUid])) {
				/** @var tx_commerce_basket_item $basketItem */
				$basketItem = $this->basket_items[$articleUid];
				$basketItem->setTitle($newtitle);
			}
		}
	}

	/**
	 * Change quantity of an article in basket
	 *
	 * @param integer $articleUid Article uid
	 * @param integer $quantity New quantity
	 * @return mixed TRUE on success, FALSE if quantity can not be changed, and integer sometimes as well ...
	 */
	public function change_quantity($articleUid, $quantity = 1) {
		if ($this->isChangeable()) {
			if ($quantity == 0) {
				if (isset($this->basket_items[$articleUid])) {
					$this->delete_article($articleUid);
				}
				$items = $this->get_articles_by_article_type_uid_asUidlist(NORMALARTICLETYPE);
				if (count($items) == 0) {
					$this->delete_all_articles();
				}
				return TRUE;
			}
			$this->recalculate_sums();

			/** @var tx_commerce_basket_item $basketItem */
			$basketItem = $this->basket_items[$articleUid];
			return $basketItem->change_quantity($quantity);
		} else {
			return FALSE;
		}
	}

	/**
	 * Get a specific item object from basket
	 *
	 * @param integer $itemUid The item uid to get
	 * @return tx_commerce_basket_item Item object
	 * @throws Exception
	 */
	public function getBasketItem($itemUid) {
		if (!array_key_exists($itemUid, $this->basket_items)) {
			throw new Exception('Item id does not exist in basket', 1305046736);
		}
		return $this->basket_items[$itemUid];
	}

	/**
	 * Return quantity of an article in basket
	 *
	 * @param integer $articleUid Uid of article
	 * @return integer Current quantity
	 */
	public function getQuantity($articleUid) {
		if (is_object($this->basket_items[$articleUid])) {
			/** @var tx_commerce_basket_item $basketItem */
			$basketItem = $this->basket_items[$articleUid];
			return $basketItem->get_quantity();
		}
		return 0;
	}

	/**
	 * Remove an article from basket
	 *
	 * @param integer $article_uid Article uid
	 * @return boolean TRUE on success
	 */
	public function delete_article($article_uid) {
		if ($this->isChangeable()) {
			if (!isset($this->basket_items[$article_uid])) {
				return FALSE;
			}
			unset($this->basket_items[$article_uid]);
			$this->items--;
			$this->recalculate_sums();
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Clear basket
	 * @return boolean TRUE on success
	 */
	public function delete_all_articles() {
		if ($this->isChangeable()) {
			$this->basket_items = array();
			$this->items = '0';
			$this->recalculate_sums();
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Get number of items in basket
	 *
	 * @return integer Number of items
	 */
	public function getItemsCount() {
		return $this->items;
	}

	/**
	 * Recalculate price sums
	 *
	 * @return void
	 */
	public function recalculate_sums() {
		$this->getNetSum();
		$this->getGrossSum();
	}

	/**
	 * Get basket gross sum
	 *
	 * @return integer Basket gross sum
	 */
	public function getGrossSum() {
		$lokal_sum = 0;

		/** @var tx_commerce_basket_item $oneItem */
		if ($this->pricefromnet == 1) {
			$netSumArray = array();

			foreach ($this->basket_items as $oneItem) {
				$netSumArray[(string) $oneItem->get_tax()] += $oneItem->getItemSumNet();
			}

			foreach ($netSumArray as $taxrate => $rateNetSum) {
				$lokal_sum += (int) round($rateNetSum * (1 + (((float) $taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $oneItem) {
				$lokal_sum += $oneItem->getItemSumGross();
			}
		}
		$this->basket_sum_gross = $lokal_sum;

		return $this->basket_sum_gross;
	}

	/**
	 * @deprecated since 2011-05-12 this function will be removed in commerce 0.16.0, please use getGrossSum instead
	 */
	public function get_gross_sum() {
		t3lib_div::logDeprecatedFunction();
		return $this->getGrossSum();
	}

	/**
	 * Get basket net sum
	 *
	 * @return integer Basket net sum
	 */
	public function getNetSum() {
		$lokal_sum = 0;

		/** @var tx_commerce_basket_item $oneItem */
		if ($this->pricefromnet == 0) {
			$grossSumArray = array();
			foreach ($this->basket_items as $oneItem) {
				$grossSumArray[(string) $oneItem->get_tax()] += $oneItem->getItemSumGross();
			}
			foreach ($grossSumArray as $taxrate => $rateGrossSum) {
				$lokal_sum += (int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $oneItem) {
				$lokal_sum += $oneItem->getItemSumNet();
			}
		}
		$this->basket_sum_net = $lokal_sum;

		return $lokal_sum;
	}

	/**
	 * @deprecated since 2011-05-12 this function will be removed in commerce 0.16.0, please use getNetSum instead
	 */
	public function get_net_sum() {
		t3lib_div::logDeprecatedFunction();
		return $this->getNetSum();
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
	public function get_assoc_arrays($prefix = '') {
		$result_array = array();

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneuid => $oneItem) {
			$result_array[$oneuid] = $oneItem->get_array_of_assoc_array($prefix);
		}

		return $result_array;
	}

	/**
	 * Returns an array of articles to a corresponding article_type
	 *
	 * @param integer $articleTypeUid Article type
	 * @return array or article_ids
	 */
	public function get_articles_by_article_type_uid_asUidlist($articleTypeUid) {
		$result = array();

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneuid => $oneItem) {
			if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
				$result[] = $oneuid;
			}
		}

		return $result;
	}

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

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneItem) {
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

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneItem) {
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
	 * @param integer $articleTypeUid
	 * @return integer Price
	 */
	public function getArticleTypeSumNet($articleTypeUid) {
		$sumNet = 0;

		/** @var tx_commerce_basket_item $oneItem */
		if ($this->pricefromnet == 0) {
			$grossSumArray = array();
			foreach ($this->basket_items as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$grossSumArray[(string) $oneItem->get_tax()] += $oneItem->getItemSumGross();
				}
			}
			foreach ($grossSumArray as $taxrate => $rateGrossSum) {
				$sumNet += (int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$sumNet += ($oneItem->get_quantity() * $oneItem->get_price_net());
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

		/** @var tx_commerce_basket_item $oneItem */
		if ($this->pricefromnet == 1) {
			$netSumArray = array();
			foreach ($this->basket_items as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$netSumArray[(string) $oneItem->get_tax()] += $oneItem->getItemSumNet();
				}
			}
			foreach ($netSumArray as $taxrate => $rateGrossSum) {
				$sumGross += (int)round($rateGrossSum * (1 + (((float) $taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $oneItem) {
				if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
					$sumGross += ($oneItem->get_quantity() * $oneItem->get_price_gross());
				}
			}
		}
		return $sumGross;
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

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneItem) {
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
	 * @param string $article_type_uid
	 * @return string Description
	 */
	public function getFirstArticleTypeDescription($article_type_uid) {
		$result = '';

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneItem) {
			if ($oneItem->getArticle()->getArticleTypeUid() == $article_type_uid) {
				if ($oneItem->getArticle()->getDescriptionExtra() > '') {
					$result = $oneItem->getArticle()->getDescriptionExtra();
					break;
				}
			}
		}

		return $result;
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
	 * Calculates the TAX-Sum for the complete and different Tax-Rates depending on article
	 *
	 * @return array Taxratesums
	 */
	public function getTaxRateSums() {
		$taxes = array();

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneItem) {
			$taxRate = $oneItem->get_tax();
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
	 * Wether or not ther are article is basket
	 *
	 * @return boolean TRUE if count of articles is greater than 0
	 */
	public function hasArticles() {
		if (count($this->basket_items) > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Return basket has value
	 *
	 * @return string Basket hash value
	 */
	public function getBasketHashValue() {
		if (!$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_commerce_pi1.']['dontUseBasketHashValue'] && count($this->basket_items) > 0) {
			return t3lib_div::shortMD5(serialize($this->basket_items));
		} else {
			return FALSE;
		}
	}

	/**
	 * This Method Sets the Tax Calculation method (pricefromnet)
	 *
	 * @param boolean $priceFromNet Switch if calculationg from net or not
	 * @return void
	 */
	public function setTaxCalculationMethod($priceFromNet) {
		$this->pricefromnet = $priceFromNet;

		/** @var tx_commerce_basket_item $oneItem */
		foreach ($this->basket_items as $oneItem) {
			$oneItem->setTaxCalculationMethod($this->pricefromnet);
		}
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
	public function getIsReadOnly() {
		return $this->readOnly;
	}

	/**
	 * @deprecated since 2011-05-09
	 */
	public function isReadOnly() {
		return $this->getIsReadOnly();
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
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basic_basket.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basic_basket.php']);
}

?>
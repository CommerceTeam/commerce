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
	public $basket_items = array();

	/**
	 * @var integer Net basket sum
	 */
	protected $basket_sum_net = 0;

	/**
	 * @var integer Gross basket sum
	 */
	protected $basket_sum_gross = 0;

	/**
	 * @var Calculated pric from net price
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
	 * Load basket data from session / database
	 *
	 * @return void
	 */
	public function load_data() {
			// Check if payment article is available and set default if not
		if (count($this->get_articles_by_article_type_uid_asUidlist(PAYMENTArticleType)) < 1) {
			$this->add_article($this->conf['defaultPaymentArticleId']);
		}
	}

	/**
	 * Add an article to the basket
	 *
	 * @param integer $article_uid Article uid
	 * @param integer $quantity Quantity of this basket item
	 * @return boolean TRUE on successful change
	 * @TODO Implement methiod is_in_basket
	 */
	public function add_article($article_uid, $quantity = 1, $priceid = '') {
		if ($article_uid && $this->isChangeable()) {
			if (is_object($this->basket_items[$article_uid]) || ($quantity == 0)) {
				$this->change_quantity($article_uid, $quantity);
			} else {
				$article = t3lib_div::makeInstance('tx_commerce_article');
				$article->init($article_uid, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
				$article->load_data('basket');
				$priceids = $article->getPossiblePriceUids();
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
						$priceid = $article->get_article_price_uid();
					}
				}
				$this->basket_items[$article_uid] = t3lib_div::makeInstance('tx_commerce_basket_item');
				if ($this->basket_items[$article_uid]->init($article_uid, $quantity, $priceid, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'])) {
					$this->basket_items[$article_uid]->setTaxCalculationMethod($this->pricefromnet);
					$this->recalculate_sums();
					$this->items++;
				}
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Change the price_value of an article
	 * @param integer $article_uid Arcicle uid
	 * @param integer $new_price_gross New price gross
	 * @param integer $new_price_net New price net
	 */
	public function changePrices($article_uid,$new_price_gross,$new_price_net) {
		if ($this->isChangeable()) {
			if ((is_object($this->basket_items[$article_uid])) && (is_a($this->basket_items[$article_uid], 'tx_commerce_basket_item'))) {
				$this->basket_items[$article_uid]->setPriceNet($new_price_net);
				$this->basket_items[$article_uid]->setPriceGross($new_price_gross);
				$this->basket_items[$article_uid]->recalculate_item_sums(true);
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
			return $this->basket_items[$articleUid]->get_item_sum_gross();
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
			return $this->basket_items[$articleUid]->get_item_sum_net();
		}
		return FALSE;
	}

	/**
	 * Change title of an article in basket
	 * @param integer $article_uid Article uid
	 * @param string $newtitle New article title
	 */
	public function changeTitle($article_uid,$newtitle) {
		if ($this->isChangeable()) {
			if(is_object($this->basket_items[$article_uid])) {
				$this->basket_items[$article_uid]->setTitle($newtitle);
			}
		}
	}

	/**
	 * Change quantity of an article in basket
	 *
	 * @param integer $article_uid Article uid
	 * @param integer $quantity New quantity
	 * @return mixed TRUE on success, FALSE if quantity can not be changed, and integer sometimes as well ...
	 */
	public function change_quantity($article_uid, $quantity = 1) {
		if ($this->isChangeable()) {
			if ($quantity == 0) {
				if (isset($this->basket_items[$article_uid])) {
					$this->delete_article($article_uid);
				}
				$items = $this->get_articles_by_article_type_uid_asUidlist(NORMALArticleType);
				if(count($items) == 0) {
					$this->delete_all_articles();
				}
				return TRUE;
			}
			$this->recalculate_sums();
			return $this->basket_items[$article_uid]->change_quantity($quantity);
		} else {
			return false;
		}
	}

	/**
	 * Get a specific item object from basket
	 *
	 * @param  $itemUid The item uid to get
	 * @return tx_commerce_basket_item Item object
	 */
	public function getBasketItem($itemUid) {
		if (!array_key_exists($itemUid, $this->basket_items)) {
			throw new Exception(
				'Item id does not exist in basket',
				1305046736
			);
		}
		return $this->basket_items[$itemUid];
	}

	/**
	 * Return quantity of an article in basket
	 *
	 * @param integer $article_uid Uid of article
	 * @return integer Current quantity
	 */
	public function getQuantity($articleUid) {
		if (is_object($this->basket_items[$articleUid])){
			return $this->basket_items[$articleUid]->get_quantity();
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
			if(!isset($this->basket_items[$article_uid])) {
				return FALSE;
			}
			unset ($this->basket_items[$article_uid]);
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
			unset($this->basket_items);
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
		if ($this->pricefromnet == 1) {
			$netSumArray = array();
			foreach ($this->basket_items as $one_item) {
				$netSumArray[(string)$one_item->get_tax()] += $one_item->get_item_sum_net();
			}
			foreach ($netSumArray as $taxrate => $rateNetSum) {
				$lokal_sum += (int)round($rateNetSum * (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $one_item) {
				$lokal_sum += $one_item->get_item_sum_gross();
			}
		}
		$this->basket_sum_gross = $lokal_sum;

		return $this->basket_sum_gross;
	}
	/**
	 * @deprecated since 2011-05-12
	 */
	public function get_gross_sum($again = TRUE) {
		return $this->getGrossSum();
	}

	/**
	 * Get basket net sum
	 *
	 * @return integer Basket net sum
	 */
	public function getNetSum() {
		$lokal_sum = 0;
		if($this->pricefromnet == 0) {
			$grossSumArray = array();
			foreach ($this->basket_items as $one_item) {
				$grossSumArray[(string)$one_item->get_tax()]+=$one_item->get_item_sum_gross();
			}
			foreach ($grossSumArray as $taxrate => $rateGrossSum) {
				$lokal_sum += (int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $one_item) {
				$lokal_sum += $one_item->get_item_sum_net();
			}
		}
		$this->basket_sum_net = $lokal_sum;
		return $lokal_sum;
	}
	/**
	 * @deprecated since 2011-05-12
	 */
	public function get_net_sum() {
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
		foreach ($this->basket_items as $oneuid => $one_item) {
			$result_array[$oneuid] = $one_item->get_array_of_assoc_array($prefix);
		}
		return $result_array;
	}

	/**
	 * Returns an array of articles to a corresponding article_type
	 *
	 * @param integer $article_type_uid Article type
	 * @return array or article_ids
	 */
	public function get_articles_by_article_type_uid_asUidlist($article_type_uid) {
		$result_array = array();
		foreach ($this->basket_items as $oneuid => $one_item){
			if ($one_item->article->article_type_uid == $article_type_uid) {
				$result_array[] = $oneuid;
			}
		}
		return $result_array;
	}

	/**
	 * Get count of all articles of this type
	 * Useful for geting the delivery cost
	 *
	 * Example:
	 * $basket->getArticleTypeSumNet(PAYMENTArticleType)
	 * $basket->getArticleTypeSumNet(DELIVERYArticleType)
	 *
	 * @return integer Count
	 */
	public function getArticleTypeCount($article_type_uid) {
		$Count = 0;
		foreach ($this->basket_items as $oneuid  => $one_item) {
			if ($one_item->article->article_type_uid == $article_type_uid) {
					$Count++;
			}
		}
		return $Count;
	}

	/**
	 * Get count of all articles of a specific type
	 *
	 * @param array $articleType
	 */
	public function getArticleTypeCountFromList($articleTypes){
		$Count = 0;
		foreach ($this->basket_items as $oneuid => $one_item) {
			if (in_array($one_item->article->article_type_uid ,$articleTypes)) {
				$Count++;
			}
		}
		return $Count;
	}

	/**
	 * Return sum of all articles of this type
	 * Useful to geting the delivery cost
	 *
	 * Example:
	 * $basket->getArticleTypeSumNet(PAYMENTArticleType)
	 * $basket->getArticleTypeSumNet(DELIVERYArticleType)
	 * @return integer Price
	 */
	public function getArticleTypeSumNet($article_type_uid) {
		$sumNet = 0;
		if($this->pricefromnet == 0) {
			$grossSumArray = array();
			foreach ($this->basket_items as $oneuid => $one_item) {
				if ($one_item->article->article_type_uid == $article_type_uid) {
					$grossSumArray[(string)$one_item->get_tax()] += $one_item->get_item_sum_gross();
				}
			}
			foreach ($grossSumArray as $taxrate => $rateGrossSum) {
				$sumNet += (int)round($rateGrossSum / (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $oneuid  => $one_item) {
				if ($one_item->article->article_type_uid == $article_type_uid) {
					$sumNet+=($one_item->get_quantity()*$one_item->get_price_net());
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
	 * $basket->getArticleTypeSumGross(PAYMENTArticleType)
	 * $basket->getArticleTypeSumGross(DELIVERYArticleType)
	 *
	 * @return sum as integer
	 */
	public function getArticleTypeSumGross($article_type_uid) {
		$sumGross = 0;
		if($this->pricefromnet == 1) {
			$netSumArray = array();
			foreach ($this->basket_items as $oneuid  => $one_item) {
				if ($one_item->article->article_type_uid == $article_type_uid) {
					$netSumArray[(string)$one_item->get_tax()]+=$one_item->get_item_sum_net();
				}
			}
			foreach ($netSumArray as $taxrate => $rateGrossSum) {
				$sumGross += (int)round($rateGrossSum * (1 + (((float)$taxrate) / 100)));
			}
		} else {
			foreach ($this->basket_items as $oneuid  => $one_item)	{
				if ($one_item->article->article_type_uid == $article_type_uid) {
					$sumGross += ($one_item->get_quantity()*$one_item->get_price_gross());
				}
			}
		}
		return $sumGross;
	}

	/**
	 * Get first title from of all articles concerning this type
	 *
	 * Eexample:
	 * $basket->getFirstArticleTypeTitle(PAYMENTArticleType)
	 * $basket->getFirstArticleTypeTitle(DELIVERYArticleType)
	 *
	 * @return string Title
	 */
	public function getFirstArticleTypeTitle($article_type_uid) {
		foreach ($this->basket_items as $oneuid => $one_item) {
			if ($one_item->article->article_type_uid == $article_type_uid) {
				if ($one_item->article->get_title()>'') {
					return 	$one_item->article->get_title();
				}
			}
		}
	}

	/**
	 * Returns the first Description from of all Articles concerning this type
	 *
	 * Example:
	 * $basket->getFirstArticleTypeDescription(PAYMENTArticleType)
	 * $basket->getFirstArticleTypeDescription(DELIVERYArticleType)
	 *
	 * @return text Description
	 */
	public function getFirstArticleTypeDescription($article_type_uid){
		foreach ($this->basket_items as $oneuid => $one_item) {
			if ($one_item->article->article_type_uid == $article_type_uid) {
				if ($one_item->article->get_description_extra() > '') {
					return $one_item->article->get_description_extra();
				}
			}
		}
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
		foreach ($this->basket_items as $oneuid  => $one_item) {
			$taxRate = $one_item->get_tax();
			$taxRate = (string)$taxRate;
			if($this->pricefromnet == 1) {
				$taxSum = ($one_item->get_item_sum_net() * (((float)$taxRate) /100));
			} else {
				$taxSum = ($one_item->get_item_sum_gross() * ((((float)$taxRate) / 100) / (1 + (((float)$taxRate) / 100))));
			}
			if(!isset($taxes[$taxRate]) AND $taxSum <= 0) {
				continue;
			}
			if(!isset($taxes[$taxRate])) {
				$taxes[$taxRate] = 0;
			}
			$taxes[$taxRate] += $taxSum;
		}
		foreach ($taxes as $taxRate => $taxSum) {
			$taxes[$taxRate]= (int)round($taxSum);
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
			return true;
		} else {
			return false;
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
	 * @param boolean	Switch if calculationg from net or not
	 * @return void
	 */
	public function setTaxCalculationMethod($priceFromNet) {
		$this->pricefromnet = $priceFromNet;
		foreach ($this->basket_items as $one_item) {
			$one_item->setTaxCalculationMethod($this->pricefromnet);
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
	public function isReadOnly(){
		return $this->getIsReadOnly();
	}

	/**
	 * Wether or not basket is locket
	 *
	 * @return booloan TRUE if locked
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basic_basket.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basic_basket.php']);
}
?>
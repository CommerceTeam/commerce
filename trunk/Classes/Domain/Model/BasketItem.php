<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Ingo Schmitt <is@marketing-factory.de>
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
 * Basic class for basket_items.
 * Libary for handling basket-items in the Frontend.
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 */
class Tx_Commerce_Domain_Model_BasketItem {
	/**
	 * Article
	 *
	 * @var Tx_Commerce_Domain_Model_Article
	 */
	public $article;

	/**
	 * Product
	 *
	 * @var Tx_Commerce_Domain_Model_Product
	 */
	public $product;

	/**
	 * Price
	 *
	 * @var Tx_Commerce_Domain_Model_ArticlePrice
	 */
	protected $price;

	/**
	 * integer quantity for this article
	 *
	 * @var integer
	 */
	protected $quantity = 0;

	/**
	 * integer priceid for this item
	 *
	 * @var integer
	 */
	protected $priceid = 0;

	/**
	 * item summe from net_price
	 *
	 * @var integer
	 */
	protected $item_net_sum = 0;

	/**
	 * item summe from gross_price
	 *
	 * @var integer
	 */
	protected $item_gross_sum = 0;

	/**
	 * calculated price from net price
	 *
	 * @var integer
	 */
	protected $pricefromnet = 0;

	/**
	 * Net Price for this item
	 *
	 * @var integer
	 */
	protected $priceNet;

	/**
	 * Gross Price for this item
	 *
	 * @var integer
	 */
	protected $priceGross;

	/**
	 * Lang uid
	 *
	 * @var integer
	 */
	protected $lang_uid = 0;

	/**
	 * Constructor, basically calls init
	 *
	 * @param integer $uid
	 * @param integer $quantity
	 * @param integer $priceid
	 * @param integer $languageUid
	 * @return self
	 */
	public function __construct($uid, $quantity, $priceid, $languageUid = 0) {
		if ((int) $uid && $quantity && $priceid) {
			$this->init($uid, $quantity, $priceid, $languageUid);
		}
	}

	/**
	 * Initialises the object,
	 * checks if given uid is valid and loads the the article an product data
	 *
	 * @param integer $uid artcile UID
	 * @param integer $quantity amount for this article
	 * @param integer $priceid id of the price to use
	 * @param integer $langUid Language ID
	 * @return bool
	 */
	public function init($uid, $quantity, $priceid, $langUid = 0) {
		$uid = (int) $uid;
		$langUid = (int) $langUid;
		$priceid = (int) $priceid;

		if (is_numeric($quantity)) {
			if (is_float($quantity)) {
				$this->quantity = floatval($quantity);
			} else {
				$this->quantity = (int) $quantity;
			}
		} else {
			return FALSE;
		}

		$this->quantity = $quantity;
		$this->lang_uid = $langUid;

		if ($quantity < 1) {
			return FALSE;
		}

		/** @var Tx_Commerce_Domain_Model_Article $article */
		$article = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Article', $uid, $this->lang_uid);

		if (is_object($article)) {
			$article->loadData('basket');
			$this->article = $article;

			/** @var Tx_Commerce_Domain_Model_Product $product */
			$product = $article->getParentProduct();
			$product->loadData('basket');
			$this->product = $product;

			$this->priceid = $priceid;

			/** @var Tx_Commerce_Domain_Model_ArticlePrice $price */
			$price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
			$price->init($priceid, $this->lang_uid);
			$price->loadData('basket');
			$this->price = $price;

			$this->priceNet = $price->getPriceNet();
			$this->priceGross = $price->getPriceGross();

			$this->recalculateItemSums();

			return TRUE;
		}

		/**
		 * Article is not availiabe, so clear object
		 */
		$this->quantity = 0;
		$this->article = NULL;
		$this->product = NULL;

		return FALSE;
	}

	/**
	 * gets an array of get_article_assoc_array and get_product_assoc_array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 * @return array
	 */
	public function getArrayOfAssocArray($prefix = '') {
		return array(
			'article' => $this->getArticleAssocArray($prefix),
			'product' => $this->getProductAssocArray($prefix)
		);
	}

	/**
	 * Get article object
	 *
	 * @return Tx_Commerce_Domain_Model_Article Article object
	 */
	public function getArticle() {
		return $this->article;
	}

	/**
	 * returns the ArticleAssocArray
	 *
	 * @param string $prefix
	 * @return array
	 */
	public function getArticleAssocArray($prefix) {
		return $this->article->returnAssocArray($prefix);
	}

	/**
	 * gets the article type uid
	 *
	 * @return integer type of the article
	 */
	public function getArticleTypeUid() {
		return $this->article->getArticleTypeUid();
	}

	/**
	 * gets the uid from the article
	 *
	 * @return integer uid
	 */
	public function getArticleUid() {
		return $this->article->getUid();
	}

	/**
	 * return the Ordernumber of item
	 *
	 * @return string ean of Articles
	 */
	public function getEanCode() {
		return $this->article->getEanCode();
	}

	/**
	 * set a given field, only to use with custom field without own method
	 * Warning: commerce provides getMethods for all default fields. For Compatibility
	 * reasons always use the built in Methods. Only use this method with you own added fields
	 *
	 * @see add_fields_to_fieldlist
	 * @see add_field_to_fieldlist
	 * @param string $field : fieldname
	 * @param mixed $value : value
	 * @return void
	 */
	public function setField($field, $value) {
		$this->$field = $value;
	}

	/**
	 * get a given field value, only to use with custom field without own method
	 * Warning: commerce provides getMethods for all default fields. For Compatibility
	 * reasons always use the built in Methods. Only use this method with you own added fields
	 *
	 * @see add_fields_to_fieldlist
	 * @see add_field_to_fieldlist
	 * @param string $field : fieldname
	 * @return mixed value of the field
	 */
	public function getField($field) {
		return $this->$field;
	}

	/**
	 * retruns the item_sum_net
	 *
	 * @param boolean $recalculate if the sum should be recalculated, default false
	 * @return integer item sum net
	 */
	public function getItemSumNet($recalculate = FALSE) {
		return $recalculate === TRUE ? $this->calculateNetSum() : $this->item_net_sum;
	}

	/**
	 * Return calculated item sum gross
	 *
	 * @param boolean $recalculate True if sum should be recalculated
	 * @return integer Sum gross price
	 */
	public function getItemSumGross($recalculate = FALSE) {
		return $recalculate === TRUE ? $this->calculateGrossSum() : $this->item_gross_sum;
	}

	/**
	 * retruns the absolut TAX
	 *
	 * @param boolean $recalculate if the sum shoudl be recalculated, defaul false
	 * @return integer item sum gross
	 */
	public function getItemSumTax($recalculate = FALSE) {
		return ($this->getItemSumGross($recalculate) - $this->getItemSumNet($recalculate));
	}

	/**
	 * return the the gross price without the scale calculation
	 */
	public function getNoScalePriceGross() {
		return $this->article->getPriceGross();
	}

	/**
	 * return the the net price without the scale calculation
	 */
	public function getNoScalePriceNet() {
		return $this->article->getPriceNet();
	}

	/**
	 * return the Ordernumber of item
	 *
	 * @return string Ordernumber of Articles
	 */
	public function getOrderNumber() {
		return $this->article->getOrdernumber();
	}

	/**
	 * Sets pre gross price
	 *
	 * @param integer $value new Price Value
	 */
	public function setPriceGross($value) {
		$this->priceGross = $value;
		$this->calculateGrossSum();
	}

	/**
	 * gets the price_gross from thhe article
	 *
	 * @return integer
	 */
	public function getPriceGross() {
		return $this->priceGross;
	}

	/**
	 * Sets the net price
	 *
	 * @param integer $value new Price Value
	 * @return void
	 */
	public function setPriceNet($value) {
		$this->priceNet = $value;
		$this->calculateNetSum();
	}

	/**
	 * gets the price_net from thhe article
	 *
	 * @return integer
	 */
	public function getPriceNet() {
		return $this->priceNet;
	}

	/**
	 * gets the uid from thhe article
	 *
	 * @return integer uid
	 */
	public function getPriceUid() {
		return $this->priceid;
	}

	/**
	 * Get product object of item
	 *
	 * @return Tx_Commerce_Domain_Model_Product Product object
	 */
	public function getProduct() {
		return $this->product;
	}

	/**
	 * returns the ArticleAssocArray
	 *
	 * @param string $prefix
	 * @return array
	 */
	public function getProductAssocArray($prefix) {
		return $this->product->returnAssocArray($prefix);
	}

	/**
	 * gets the master parent category
	 *
	 * @return array category
	 * @see product
	 */
	public function getProductMasterparentCategorie() {
		return $this->product->getMasterparentCategory();
	}

	/**
	 * gets the uid from the product
	 *
	 * @return integer uid
	 */
	public function getProductUid() {
		return $this->product->getUid();
	}

	/**
	 * gets the quantity from thos item
	 *
	 * @return integer quantity
	 */
	public function getQuantity() {
		return $this->quantity;
	}

	/**
	 * Gets the subtitle of the basket item
	 *
	 * @param string $type of subtitle, possible values arte article and product
	 * @return string Subtitle of article (default) or product
	 */
	public function getSubtitle($type = 'article') {
		switch ($type) {
			case 'product':
				return $this->product->getSubtitle();

			case 'article':
			default:
				return $this->article->getSubtitle();
		}
	}

	/**
	 * gets the tax from the article
	 *
	 * @return float percantage of tax
	 */
	public function getTax() {
		$result = 0;

		if (is_object($this->article)) {
			$result = $this->article->getTax();
		}

		return $result;
	}

	/**
	 * This Method Sets the Tax Calculation method (pricefromnet)
	 *
	 * @param boolean $priceFromNet Switch if calculationg from net or not
	 * @return void
	 */
	public function setTaxCalculationMethod($priceFromNet) {
		$this->pricefromnet = $priceFromNet;
	}

	/**
	 * Sets the Title
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->article->setField('title', $title);
		$this->product->setField('title', $title);
	}

	/**
	 * Gets the title
	 *
	 * @param string $type of title, possible values arte article and product
	 * @return string title of article (default) or product
	 */
	public function getTitle($type = 'article') {
		switch ($type) {
			case 'product':
				return $this->product->getTitle();

			case 'article':
			default:
				return $this->article->getTitle();
		}
	}


	/**
	 * Change the basket item quantity
	 *
	 * @param quanitity
	 * @return true
	 * @access public
	 */
	public function changeQuantity($quantity) {
		$this->quantity = $quantity;
		$this->priceid = $this->article->getActualPriceforScaleUid($quantity);

		$this->price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
		$this->price->init($this->priceid, $this->lang_uid);
		$this->price->loadData();
		$this->priceNet = $this->price->getPriceNet();
		$this->priceGross = $this->price->getPriceGross();
		$this->recalculateItemSums();

		return TRUE;
	}

	/**
	 * Calculates the net_sum
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return integer net_sum
	 * @todo add hook for this function
	 */
	public function calculateNetSum($useValues = FALSE) {
		if (($this->pricefromnet == 0) && ($useValues == FALSE)) {
			$this->calculateGrossSum();
			$taxrate = $this->getTax();
			$this->item_net_sum = (int) round($this->item_gross_sum / (1 + ($taxrate / 100)));
		} else {
			$this->item_net_sum = $this->getPriceNet() * $this->quantity;
		}

		return $this->item_net_sum;
	}

	/**
	 * Calculates the gross_sum
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return integer gross_sum
	 * @todo add hook for this function
	 */
	public function calculateGrossSum($useValues = FALSE) {
		if (($this->pricefromnet == 1) && ($useValues == FALSE)) {
			$this->calculateNetSum();
			$taxrate = $this->getTax();
			$this->item_gross_sum = (int) round($this->item_net_sum * (1 + ($taxrate / 100)));
		} else {
			$this->item_gross_sum = $this->getPriceGross() * $this->quantity;
		}

		return $this->item_gross_sum;
	}

	/**
	 * recalculates the itm sums
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return void
	 */
	public function recalculateItemSums($useValues = FALSE) {
		$this->calculateNetSum($useValues);
		$this->calculateGrossSum($useValues);
	}


	/**
	 * recalculates the itm sums
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use recalculateItemSums instead
	 */
	public function recalculate_item_sums($useValues = FALSE) {
		t3lib_div::logDeprecatedFunction();
		$this->recalculateItemSums($useValues);
	}

	/**
	 * Calculates the gross_sum
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return integer gross_sum
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use calculateGrossSum instead
	 */
	public function calculate_gross_sum($useValues = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->calculateGrossSum($useValues);
	}

	/**
	 * Calculates the net_sum
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return integer net_sum
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use calculateNetSum instead
	 */
	public function calculate_net_sum($useValues = FALSE) {
		t3lib_div::logDeprecatedFunction();
		$this->calculateNetSum($useValues);
	}

	/**
	 * Change the basket item quantity
	 *
	 * @param quanitity
	 * @return true
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use changeQuantity instead
	 */
	public function change_quantity($quantity) {
		t3lib_div::logDeprecatedFunction();
		return $this->changeQuantity($quantity);
	}

	/**
	 * gets the uid from the article
	 *
	 * @return integer uid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getArticleUid instead
	 */
	public function get_article_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getArticleUid();
	}

	/**
	 * gets the tax from the article
	 *
	 * @return float percantage of tax
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getTax instead
	 */
	public function get_tax() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTax();
	}

	/**
	 * gets the price_gross from thhe article
	 *
	 * @return integer
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getPriceGross instead
	 */
	public function get_price_gross() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceGross();
	}

	/**
	 * gets the uid from thhe article
	 *
	 * @return integer uid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getPriceUid instead
	 */
	public function get_price_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceUid();
	}

	/**
	 * retruns the item_sum_net
	 *
	 * @param boolean $recalculate if the sum should be recalculated, default false
	 * @return integer item sum net
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getItemSumNet instead
	 */
	public function get_item_sum_net($recalculate = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->getItemSumNet($recalculate);
	}

	/**
	 * @param boolean $recalculate
	 * @return integer
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getItemSumGross instead
	 */
	public function get_item_sum_gross($recalculate = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->getItemSumGross($recalculate);
	}

	/**
	 * retruns the absolut TAX
	 *
	 * @param boolean $recalculate if the sum shoudl be recalculated, defaul false
	 * @return integer item sum gross
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getItemSumTax instead
	 */
	public function get_item_sum_tax($recalculate = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->getItemSumTax($recalculate);
	}

	/**
	 * gets the article_assoc_array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 * @return array
	 * @see tx_commerce_article <- Tx_Commerce_Domain_Model_AbstractEntity
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getArticleAssocArray instead
	 */
	public function get_article_assoc_array($prefix = '') {
		t3lib_div::logDeprecatedFunction();
		return $this->getArticleAssocArray($prefix);
	}

	/**
	 * gets the product_assoc_array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 * @return array
	 * @see tx_commerce_product <- Tx_Commerce_Domain_Model_AbstractEntity
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getProductAssocArray instead
	 */
	public function get_product_assoc_array($prefix = '') {
		t3lib_div::logDeprecatedFunction();
		return $this->getProductAssocArray($prefix);
	}

	/**
	 * gets an array of get_article_assoc_array and get_product_assoc_array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getArrayOfAssocArray instead
	 */
	public function get_array_of_assoc_array($prefix = '') {
		t3lib_div::logDeprecatedFunction();
		return $this->getArrayOfAssocArray($prefix);
	}

	/**
	 * @return integer
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getPriceNet instead
	 */
	public function get_price_net() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceNet();
	}

	/**
	 * gets the article Type uid
	 *
	 * @return integer article type uid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getArticleTypeUid instead
	 */
	public function get_article_article_type_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getArticleTypeUid();
	}

	/**
	 * gets the quantity from thos item
	 *
	 * @return integer
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getQuantity instead
	 */
	public function get_quantity() {
		t3lib_div::logDeprecatedFunction();
		return $this->getQuantity();
	}
}

class_alias('Tx_Commerce_Domain_Model_BasketItem', 'tx_commerce_basket_item');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/BasketItem.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/BasketItem.php']);
}

?>
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
 * Basic class for basket_items.
 * Libary for handling basket-items in the Frontend.
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 */
class tx_commerce_basket_item {
	/**
	 * Article
	 *
	 * @var tx_commerce_article
	 */
	public $article;

	/**
	 * Product
	 *
	 * @var tx_commerce_product
	 */
	public $product;

	/**
	 * Price
	 *
	 * @var tx_commerce_article_price
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
	protected $lang_id = 0;

	/**
	 * Call to $this->init
	 */
	public function __construct() {
		if ((func_num_args() >= 3) && (func_num_args() <= 4)) {
			$uid = func_get_arg(0);
			$quantity = func_get_arg(1);
			$priceid = func_get_arg(2);

			if (func_num_args() == 4) {
				$lang_uid = func_get_arg(3);
			} else {
				$lang_uid = 0;
			}

			$this->init($uid, $quantity, $priceid, $lang_uid);
		}
	}

	/**
	 * Initialises the object,
	 * checks if given uid is valid and loads the the article an product data
	 *
	 * @param integer $uid artcile UID
	 * @param integer $quantity amount for this article
	 * @param integer $priceid id of the price to use
	 * @param integer $lang_id Language ID
	 * @return bool
	 */
	public function init($uid, $quantity, $priceid, $lang_id = 0) {
		$uid = intval($uid);
		$lang_id = intval($lang_id);
		$priceid = intval($priceid);

		if (is_numeric($quantity)) {
			if (is_float($quantity)) {
				$this->quantity = floatval($quantity);
			} else {
				$this->quantity = intval($quantity);
			}
		} else {
			return FALSE;
		}

		$this->quantity = $quantity;
		$this->lang_id = $lang_id;
		$this->article = t3lib_div::makeInstance('tx_commerce_article');
		$this->article->init($uid, $this->lang_id);

		if ($quantity < 1) {
			return FALSE;
		}

		if (is_object($this->article)) {
			$this->article->loadData('basket');

			$this->product = $this->article->get_parent_product();
			$this->product->loadData('basket');

			$this->priceid = $priceid;

			$this->price = t3lib_div::makeInstance('tx_commerce_article_price');
			$this->price->init($priceid, $this->lang_id);
			$this->price->loadData('basket');

			$this->priceNet = $this->price->getPriceNet();
			$this->priceGross = $this->price->getPriceGross();

			$this->recalculate_item_sums();

			return TRUE;
		}

		/**
		 * Article is not availiabe, so clear object
		 */
		$this->quantity = 0;
		$this->article = '';
		$this->product = '';

		return FALSE;
	}

	/**
	 * Change the basket item quantity
	 *
	 * @param quanitity
	 * @return true
	 * @access public
	 */
	public function change_quantity($quantity) {
		$this->quantity = $quantity;
		$this->priceid = $this->article->getActualPriceforScaleUid($quantity);

		$this->price = t3lib_div::makeInstance('tx_commerce_article_price');
		$this->price->init($this->priceid, $this->lang_id);
		$this->price->loadData();
		$this->priceNet = $this->price->getPriceNet();
		$this->priceGross = $this->price->getPriceGross();
		$this->recalculate_item_sums();

		return TRUE;
	}

	/**
	 * recalculates the itm sums
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return void
	 */
	public function recalculate_item_sums($useValues = FALSE) {
		$this->calculate_net_sum($useValues);
		$this->calculate_gross_sum($useValues);
	}

	/**
	 * Calculates the net_sum
	 *
	 * @param $useValues boolean Use the stored values instead of calculating gross or net price
	 * @return integer net_sum
	 * @todo add hook for this function
	 */
	public function calculate_net_sum($useValues = FALSE) {
		if (($this->pricefromnet == 0) && ($useValues == FALSE)) {
			$this->calculate_gross_sum();
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
	public function calculate_gross_sum($useValues = FALSE) {
		if (($this->pricefromnet == 1) && ($useValues == FALSE)) {
			$this->calculate_net_sum();
			$taxrate = $this->getTax();
			$this->item_gross_sum = (int) round($this->item_net_sum * (1 + ($taxrate / 100)));
		} else {
			$this->item_gross_sum = $this->getPriceGross() * $this->quantity;
		}

		return $this->item_gross_sum;
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
	 * @return integer
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getPriceNet instead
	 */
	public function get_price_net() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceNet();
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
	 * Gets the subtitle of the basket item
	 *
	 * @param string $type of subtitle, possible values arte article and product
	 * @return string Subtitle of article (default) or product
	 */
	public function getSubtitle($type = 'article') {
		switch ($type) {
			case 'product':
				return $this->product->get_subtitle();

			case 'article':
			default:
				return $this->article->getSubtitle();
		}
	}

	/**
	 * Sets the net price
	 *
	 * @param integer $value new Price Value
	 * @return void
	 */
	public function setPriceNet($value) {
		$this->priceNet = $value;
		$this->calculate_net_sum();
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
	 * gets the price_gross from thhe article
	 *
	 * @return integer
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getPriceGross instead
	 */
	public function get_price_gross() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceGross();
	}

	/**
	 * Sets pre gross price
	 *
	 * @param integer $value new Price Value
	 */
	public function setPriceGross($value) {
		$this->priceGross = $value;
		$this->calculate_gross_sum();
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
	 * gets the tax from the article
	 *
	 * @return float percantage of tax
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getTax instead
	 */
	public function get_tax() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTax();
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
	 * gets the uid from thhe article
	 *
	 * @return integer uid
	 */
	public function getPriceUid() {
		return $this->priceid;
	}

	/**
	 * gets the uid from thhe article
	 *
	 * @return integer uid
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getPriceUid instead
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
	 */
	public function getItemSumNet($recalculate = FALSE) {
		if ($recalculate == TRUE) {
			return $this->calculate_net_sum();
		} else {
			return $this->item_net_sum;
		}
	}

	/**
	 * retruns the item_sum_net
	 *
	 * @param boolean $recalculate if the sum should be recalculated, default false
	 * @return integer item sum net
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getItemSumNet instead
	 */
	public function get_item_sum_net($recalculate = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->getItemSumNet($recalculate);
	}

	/**
	 * Return calculated item sum gross
	 *
	 * @param boolean $recalculate True if sum should be recalculated
	 * @return integer Sum gross price
	 */
	public function getItemSumGross($recalculate = FALSE) {
		if ($recalculate === TRUE) {
			return $this->calculate_gross_sum();
		} else {
			return $this->item_gross_sum;
		}
	}

	/**
	 * @param boolean $recalculate
	 * @return integer
	 * @deprecated since 2011-05-11 this function will be removed in commerce 0.16.0, please use getItemSumGross instead
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
	 */
	public function getItemSumTax($recalculate = FALSE) {
		return ($this->getItemSumGross($recalculate) - $this->getItemSumNet($recalculate));
	}

	/**
	 * ----------------------------------------------------------------------
	 * Article Methods
	 * ----------------------------------------------------------------------
	 */

	/**
	 * gets the article type uid
	 *
	 * @return integer type of the article
	 */
	public function getArticleTypeUid() {
		return $this->article->getArticleTypeUid();
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
	 * return the Ordernumber of item
	 *
	 * @return string ean of Articles
	 */
	public function getEanCode() {
		return $this->article->getEanCode();
	}

	/**
	 * gets the uid from the article
	 *
	 * @return integer uid
	 */
	public function get_article_uid() {
		return $this->article->getUid();
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
	 * Get article object
	 *
	 * @return tx_commerce_article Article object
	 */
	public function getArticle() {
		return $this->article;
	}

	/**
	 * ----------------------------------------------------------------------
	 * Product Methods
	 * ----------------------------------------------------------------------
	 */

	/**
	 * Get product object of item
	 *
	 * @return tx_commerce_product Product object
	 */
	public function getProduct() {
		return $this->product;
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
	 * gets the master parent category
	 *
	 * @return array category
	 * @see product
	 */
	public function getProductMasterparentCategorie() {
		return $this->product->getMasterparentCategory();
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
	 * --------------------------------------------------------------------
	 * Other methods, related to article and product
	 * --------------------------------------------------------------------
	 */

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
	 * This Method Sets the Tax Calculation method (pricefromnet)
	 *
	 * @param boolean $priceFromNet Switch if calculationg from net or not
	 * @return void
	 */
	public function setTaxCalculationMethod($priceFromNet) {
		$this->pricefromnet = $priceFromNet;
	}

	/**
	 * #######################################################################
	 * Depricated methods !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 */

	/**
	 * retruns the absolut TAX
	 *
	 * @param boolean $recalculate if the sum shoudl be recalculated, defaul false
	 * @return integer item sum gross
	 * @deprecated since 2011-05-11 this function will be removed in commerce 0.16.0, please use getItemSumTax instead
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
	 * @see tx_commerce_article <- tx_commerce_element_alib
	 * @deprecated since 2011-05-11 this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getArticleAssocArray instead
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
	 * @see tx_commerce_product <- tx_commerce_element_alib
	 * @deprecated since 2011-05-11 this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getProductAssocArray instead
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
	 * @deprecated since 2011-05-11 this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getArrayOfAssocArray instead
	 */
	public function get_array_of_assoc_array($prefix = '') {
		t3lib_div::logDeprecatedFunction();

		return $this->getArrayOfAssocArray($prefix);
	}

	/**
	 * gets the article Type uid
	 *
	 * @return integer article type uid
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getArticleTypeUid instead
	 */
	public function get_article_article_type_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getArticleTypeUid();
	}

	/**
	 * gets the quantity from thos item
	 *
	 * @return integer
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_basket_item::getQuantity instead
	 */
	public function get_quantity() {
		t3lib_div::logDeprecatedFunction();
		return $this->getQuantity();
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
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basket_item.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_basket_item.php']);
}

?>
<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Volker Graubaum <vg@e-netconsulting.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Basket pi for commerce. This class is used to handle all events concerning
 * the basket. E.g. Adding things to basket, changing basket
 *
 * The basket itself is stored inside $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
 *
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @author Ingo Schmitt <is@marketing-factory.de>
 *
 * @see tx_commerce_basket
 * @see tx_commerce_basic_basekt
 */
class tx_commerce_pi2 extends tx_commerce_pibase {
	/**
	 * Same as class name
	 *
	 * @var string
	 */
	public $prefixId = 'tx_commerce_pi1';

	/**
	 * Path to this script relative to the extension dir.
	 *
	 * @var string
	 */
	public $scriptRelPath = 'pi2/class.tx_commerce_pi2.php';

	/**
	 * The extension key.
	 *
	 * @var string
	 */
	public $extKey = 'commerce';

	/**
	 * @var string
	 */
	public $imgFolder = 'uploads/tx_commerce/';

	/**
	 * @var string
	 */
	public $currency = 'EUR';

	/**
	 * @var string
	 */
	public $noStock = '';

	/**
	 * @var tx_commerce_product
	 */
	public $delProd;

	/**
	 * @var array
	 */
	public $basketDel;

	/**
	 * @var array
	 */
	public $basketPay;

	/**
	 * @var tx_commerce_product
	 */
	public $payProd;

	/**
	 * @var tx_commerce_basket Basket object
	 */
	protected $basket = NULL;

	/**
	 * @var array Marker array
	 */
	protected $markerArray = array();

	/**
	 * @var string Template file content
	 */
	protected $templateCode = '';

	/**
	 * @var string Compiled content
	 */
	protected $content = '';

	/**
	 * @var int
	 */
	protected $priceLimitForBasket = 0;

	/**
	 * Standard Init Method for all
	 * pi plugins of tx_commerce
	 *
	 * @param array $conf Configuration
	 * @return string Error output in case of error else void
	 */
	protected function init(array $conf = array()) {
		parent::init($conf);

		$this->basket = $GLOBALS['TSFE']->fe_user->tx_commerce_basket;
		$this->basket->setTaxCalculationMethod($this->conf['priceFromNet']);
		$this->basket->loadData();

		if ($this->conf['defaultCode']) {
			$this->handle = strtoupper($this->conf['defaultCode']);
		}
		if ($this->cObj->data['select_key']) {
			$this->handle = strtoupper($this->cObj->data['select_key']);
		}

		if (empty($this->conf['templateFile'])) {
			$this->error('init', __LINE__, 'Template File not defined in TS: ');
		}
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
		if (empty($this->templateCode)) {
			$this->error('init', __LINE__, "Template File not loaded, maybe it doesn't exist: " . $this->conf['templateFile']);
		}

		$this->handleBasket();

			// Define the currency
		if (strlen($this->conf['currency']) > 0) {
			$this->currency = $this->conf['currency'];
		}
	}

	/**
	 * Main function called by insert plugin
	 *
	 * @param string $content Content
	 * @param array $conf Configuration
	 * @return string HTML-Content
	 */
	public function main($content = '', array $conf = array()) {
		$this->content = $content;

		$this->init($conf);

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['main'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['main'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postInit')) {
				$result = $hookObj->postInit($this);
				if ($result === FALSE) {
					return $this->pi_wrapInBaseClass($this->content);
				}
			}
		}

		if (($this->basket->getItemsCount() == 0) && ($this->basket->getArticleTypeCountFromList(explode(',', $this->conf['regularArticleTypes'])) == 0)) {
				// If basket is empty, it should be rewritable, release locks, if there are any
			$this->basket->releaseReadOnly();
			$this->basket->store_data();
		}

		if (($this->basket->getItemsCount() > 0) && ($this->basket->getArticleTypeCountFromList(explode(',', $this->conf['regularArticleTypes'])) > 0)) {
				// Get template
			switch ($this->handle) {
				case 'HANDLING':
					$this->handleBasket();
				break;
				case 'QUICKVIEW':
					$this->getQuickView();
				break;
				default:
					$this->generateBasket();
			}
		} else {
			if ($this->handle == 'QUICKVIEW') {
				$templateMarker = '###PRODUCT_BASKET_QUICKVIEW_EMPTY###';
			} else {
				$templateMarker = '###PRODUCT_BASKET_EMPTY###';
			}

			$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);

			$markerArray = $this->languageMarker;
			$markerArray['###EMPTY_BASKET###'] = $this->cObj->cObjGetSingle($this->conf['emptyContent'], $this->conf['emptyContent.']);
			$markerArray['###URL###'] = $this->pi_linkTP_keepPIvars_url(array(), 0, 1, $this->conf['basketPid']);
			$markerArray['###URL_CHECKOUT###'] = $this->pi_linkTP_keepPIvars_url(array(), 0, 1, $this->conf['checkoutPid']);
			$markerArray['###NO_STOCK MESSAGE###'] = $this->noStock;

				// Hook for additional markers in empty quick view basket template
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'additionalMarker')) {
					$markerArray = $hookObj->additionalMarker($markerArray, $this);
				}
			}

			$this->content = $this->substituteMarkerArrayNoCached($template, $markerArray);
		}

		return $this->pi_wrapInBaseClass($this->content);
	}

	/**
	 * Main method to handle the basket. Is called when data in the basket is changed
	 * Changes the basket object and stores the data in the frontend user session
	 *
	 * @return void
	 */
	public function handleBasket() {
		if ($this->piVars['delBasket']) {
			$this->basket->delete_all_articles();

				// Hook to process basket after deleting all articles from basket
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelBasket'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelBasket'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postdelBasket')) {
					$hookObj->postdelBasket($this->basket, $this);
				}
			}
		}

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['artAddUid'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['artAddUid'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

			// Hook to process basket before adding an article to basket
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'preartAddUid')) {
				$hookObj->preartAddUid($this->basket, $this);
			}
		}

		if ($this->piVars['artAddUid']) {
			while (list($k, $v) = each($this->piVars['artAddUid'])) {
				$k = intval($k);

					// Safe old quantity for price limit
				if ($this->basket->basket_items[$k]) {
					$oldCountValue = $this->basket->basket_items[$k]->getQuantity();
				} else {
					$oldCountValue = 0;
				}

				if ($v['count'] < 0) {
					$v['count'] = 1;
				}

				if ((int)$v['count'] === 0) {
					if ($this->basket->getQuantity($k) > 0) {
						$this->basket->delete_article($k);
					}

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'postDeleteArtUidSingle')) {
							$hookObj->postDeleteArtUidSingle($k, $v, $oldCountValue, $this->basket, $this);
						}
					}
				} else {
					/** @var $articleObj tx_commerce_article */
					$articleObj = t3lib_div::makeInstance('tx_commerce_article');
					$articleObj->init($k);
					$articleObj->loadData('basket');

					$productObj = $articleObj->getParentProduct();
					$productObj->loadData('basket');

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'preartAddUidSingle')) {
							$hookObj->preartAddUidSingle($k, $v, $productObj, $articleObj, $this->basket, $this);
						}
					}

					if ($articleObj->isAccessible() && $productObj->isAccessible()) {
							// Only if product and article are accessible
						if ($this->conf['checkStock'] == 1) {
								// Instance to calculate shipping costs
							if ($articleObj->hasStock($v['count'])) {
								if ((int)$v['price_id'] > 0) {
									$this->basket->add_article($k, $v['count'], $v['price_id']);
								} else {
									$this->basket->add_article($k, $v['count']);
								}
							} else {
								$this->noStock = $this->pi_getLL('noStock');
							}
						} else {
								// Add article by default
							if ((int)$v['price_id'] > 0) {
								$this->basket->add_article($k, $v['count'], $v['price_id']);
							} else {
								$this->basket->add_article($k, $v['count']);
							}
						}
					}

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'postartAddUidSingle')) {
							$hookObj->postartAddUidSingle($k, $v, $productObj, $articleObj, $this->basket, $this);
						}
					}

						// Check for basket price limit
					if (intval($this->conf['priceLimitForBasket']) > 0 && $this->basket->getGrossSum() > intval($this->conf['priceLimitForBasket'])) {
						$this->basket->add_article($k, $oldCountValue);
						$this->priceLimitForBasket = 1;
					}
				}
			}

			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postartAddUid')) {
					$hookObj->postartAddUid($this->basket, $this);
				}
			}
		}

			// Handle payment articles
		if ($this->piVars['payArt']) {
			$basketPay = $this->basket->get_articles_by_article_type_uid_asuidlist(PAYMENTARTICLETYPE);

				// Delete old payment article
			foreach ($basketPay as $actualPaymentArticle) {
				$this->basket->delete_article($actualPaymentArticle);
			}

				// Add new article
			if (is_array($this->piVars['payArt'])) {
				foreach ($this->piVars['payArt'] as $articleUid => $articleCount) {
						// Set to integer to be sure it is integer
					$articleUid = intval($articleUid);
					$articleCount = intval($articleCount);
					$this->basket->add_article($articleUid, $articleCount['count']);
				}
			} else {
				$this->basket->add_article((int)$this->piVars['payArt']);
			}

				// Hook to process the basket after adding payment article
			$hookObjectsArr = array();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postpayArt'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postpayArt'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postpayArt')) {
					$hookObj->postpayArt($this->basket, $this);
				}
			}
		}

			// Handle delivery articles
		if ($this->piVars['delArt']) {
			$basketDel = $this->basket->get_articles_by_article_type_uid_asuidlist(DELIVERYARTICLETYPE);

				// Delete old delivery article
			foreach ($basketDel as $actualDeliveryArticle) {
				$this->basket->delete_article($actualDeliveryArticle);
			}

				// Add new article
			if (is_array($this->piVars['delArt'])) {
				foreach ($this->piVars['delArt'] as $articleUid => $articleCount) {
					$articleUid = intval($articleUid);
					$articleCount = intval($articleCount);
					$this->basket->add_article($articleUid, $articleCount['count']);
				}
			} else {
				$this->basket->add_article((int)$this->piVars['delArt']);
			}

				// Hook to process the basket after adding delivery article
			$hookObjectsArr = array();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelArt'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelArt'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postdelArt')) {
					$hookObj->postdelArt($this->basket, $this);
				}
			}
		}

		$this->basket->store_data();
	}

	/**
	 * Returns a list of markers to generate a quick-view of the basket
	 *
	 * @TODO Complete coding
	 *
	 * @return array Marker array for rendering
	 */
	public function getQuickView() {
		$articleTypes = explode(',', $this->conf['regularArticleTypes']);

		$templateMarker = '###PRODUCT_BASKET_QUICKVIEW###';
		$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);

		$basketArray = $this->languageMarker;
		$basketArray['###PRICE_GROSS###'] = tx_moneylib::format($this->basket->getGrossSum(), $this->currency);
		$basketArray['###PRICE_NET###'] = tx_moneylib::format($this->basket->getNetSum(), $this->currency);

			// @Deprecated ###ITEMS###
		$basketArray['###ITEMS###'] = $this->basket->getArticleTypeCountFromList($articleTypes);

		$basketArray['###BASKET_ITEMS###'] = $this->basket->getArticleTypeCountFromList($articleTypes);
		$basketArray['###URL###'] = $this->pi_linkTP_keepPIvars_url(array(), TRUE, 1, $this->conf['basketPid']);
		$basketArray['###URL_CHECKOUT###'] = $this->pi_linkTP_keepPIvars_url(array(), FALSE, 1, $this->conf['checkoutPid']);

			// Hook for additional markers in quick view basket template
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['getQuickView'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['getQuickView'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'additionalMarker')) {
				$basketArray = $hookObj->additionalMarker($basketArray, $this);
			}
		}

		$this->content = $this->substituteMarkerArrayNoCached($template, $basketArray);
		return TRUE;
	}

	/**
	 * Generates HTML-Code of the basket and stores content to $this->content
	 *
	 * @return void
	 */
	public function generateBasket() {
		$templateMarker = '###BASKET###';
		$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);

			// Render locked information
		if ($this->basket->getIsReadOnly()) {
			$basketSubpart = $this->cObj->getSubpart($template, 'BASKETLOCKED');
			$template = $this->cObj->substituteSubpart($template, 'BASKETLOCKED', $basketSubpart);
		} else {
			$template = $this->cObj->substituteSubpart($template, 'BASKETLOCKED', '');
		}

		$basketArray['###BASKET_PRODUCT_LIST###'] = $this->makeProductList();

			// Generate basket hooks
		$hookObject = NULL;
		if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasket'])) {
			$hookObject = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasket']);
		}

			// No delivery article is present, so draw selector
		$contentDelivery = $this->cObj->getSubpart($this->templateCode, '###DELIVERYBOX###');

		if (method_exists($hookObject, 'makeDelivery')) {
			$contentDelivery = $hookObject->makeDelivery($this, $this->basket, $contentDelivery);
			$template = $this->cObj->substituteSubpart($template, '###DELIVERYBOX###', $contentDelivery);
		} else {
			$deliveryArray = $this->makeDelivery(array());
			$contentDelivery = $this->substituteMarkerArrayNoCached($contentDelivery, $deliveryArray);
			$template = $this->cObj->substituteSubpart($template, '###DELIVERYBOX###', $contentDelivery);
		}

		$contentPayment = $this->cObj->getSubpart($this->templateCode, '###PAYMENTBOX###');
		if (method_exists($hookObject, 'makePayment')) {
			$contentPayment = $hookObject->makePayment($this, $this->basket, $contentPayment);
			$template = $this->cObj->substituteSubpart($template, '###PAYMENTBOX###', $contentPayment);
		} else {
			$paymentArray = $this->makePayment(array());
			$contentPayment = $this->substituteMarkerArrayNoCached($contentPayment, $paymentArray);
			$template = $this->cObj->substituteSubpart($template, '###PAYMENTBOX###', $contentPayment);
		}

		$taxRateTemplate = $this->cObj->getSubpart($template, '###TAX_RATE_SUMS###');
		$taxRates = $this->basket->getTaxRateSums();
		$taxRateRows = '';
		foreach ($taxRates as $taxRate => $taxRateSum) {
			$taxRowArray = array();
			$taxRowArray['###TAX_RATE###'] = $taxRate;
			$taxRowArray['###TAX_RATE_SUM###'] = tx_moneylib::format($taxRateSum, $this->currency);
			$taxRateRows .= $this->cObj->substituteMarkerArray($taxRateTemplate, $taxRowArray);
		}

		$template = $this->cObj->substituteSubpart($template, '###TAX_RATE_SUMS###', $taxRateRows);

		$basketArray['###BASKET_NET_PRICE###'] = tx_moneylib::format($this->basket->getNetSum(), $this->currency);
		$basketArray['###BASKET_GROSS_PRICE###'] = tx_moneylib::format(intval($this->basket->getGrossSum()), $this->currency);
		$basketArray['###BASKET_TAX_PRICE###'] = tx_moneylib::format(intval($this->basket->getGrossSum() - $this->basket->getNetSum()), $this->currency);
		$basketArray['###BASKET_VALUE_ADDED_TAX###'] = tx_moneylib::format(intval($this->basket->getGrossSum()) - $this->basket->getNetSum(), $this->currency);
		$basketArray['###BASKET_ITEMS###'] = $this->basket->getItemsCount();
		$basketArray['###DELBASKET###'] = $this->pi_linkTP_keepPIvars($this->pi_getLL('delete_basket', 'delete basket'), array('delBasket' => 1), 0, 1);
		$basketArray['###BASKET_NEXTBUTTON###'] = $this->cObj->stdWrap($this->makeCheckOutLink(), $this->conf['nextbutton.']);
		$basketArray['###BASKET_ARTICLES_NET_SUM###'] = tx_moneylib::format($this->basket->getArticleTypeSumNet(NORMALARTICLETYPE), $this->currency);
		$basketArray['###BASKET_ARTICLES_GROSS_SUM###'] = tx_moneylib::format($this->basket->getArticleTypeSumGross(NORMALARTICLETYPE), $this->currency);
		$basketArray['###BASKET_DELIVERY_NET_SUM###'] = tx_moneylib::format($this->basket->getArticleTypeSumNet(DELIVERYARTICLETYPE), $this->currency);
		$basketArray['###BASKET_DELIVERY_GROSS_SUM###'] = tx_moneylib::format($this->basket->getArticleTypeSumGross(DELIVERYARTICLETYPE), $this->currency);
		$basketArray['###BASKET_PAYMENT_NET_SUM###'] = tx_moneylib::format($this->basket->getArticleTypeSumNet(PAYMENTARTICLETYPE), $this->currency);
		$basketArray['###BASKET_PAYMENT_GROSS_SUM###'] = tx_moneylib::format($this->basket->getArticleTypeSumGross(PAYMENTARTICLETYPE), $this->currency);
		$basketArray['###BASKET_PAYMENT_ITEMS###'] = $this->basket->getArticleTypeCount(PAYMENTARTICLETYPE);
		$basketArray['###BASKET_DELIVERY_ITEMS###'] = $this->basket->getArticleTypeCount(DELIVERYARTICLETYPE);
		$basketArray['###BASKET_ARTICLES_ITEMS###'] = $this->basket->getArticleTypeCount(NORMALARTICLETYPE);
		$basketArray['###BASKETURL###'] = $this->pi_linkTP_keepPIvars_url(array(), 0, 1, $this->conf['basketPid']);
		$basketArray['###URL_CHECKOUT###'] = $this->pi_linkTP_keepPIvars_url(array(), 0, 1, $this->conf['checkoutPid']);
		$basketArray['###NO_STOCK MESSAGE###'] = $this->noStock;
		$basketArray['###BASKET_LASTPRODUCTURL###'] = $this->cObj->stdWrap($GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_commerce_lastproducturl'), $this->conf['lastProduct']);

		if ($this->priceLimitForBasket == 1 && $this->conf['priceLimitForBasketMessage']) {
			$basketArray['###BASKET_PRICELIMIT###'] = $this->cObj->cObjGetSingle($this->conf['priceLimitForBasketMessage'], $this->conf['priceLimitForBasketMessage.']);
		} else {
			$basketArray['###BASKET_PRICELIMIT###'] = '';
		}

		$basketArray = array_merge($basketArray, $this->languageMarker);

		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasketMarker'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasketMarker'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'additionalMarker')) {
				$basketArray = $hookObj->additionalMarker($basketArray, $this);
			}
		}

		$this->content = $this->substituteMarkerArrayNoCached($template, $basketArray);

		$markerArrayGlobal = array();
		$markerArrayGlobal = $this->addFormMarker($markerArrayGlobal);

		$this->content = $this->cObj->SubstituteMarkerArray($this->content, $markerArrayGlobal, '###|###');
	}

	/**
	 * Generates the Markers for the delivery-selector
	 *
	 * @param array $basketArray Array of marker
	 * @return array Array of marker
	 */
	public function makeDelivery($basketArray) {
		$this->delProd = t3lib_div::makeInstance('tx_commerce_product');
		$this->delProd->init($this->conf['delProdId'], $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
		$this->delProd->loadData();
		$this->delProd->loadArticles();

		$this->basketDel = $this->basket->get_articles_by_article_type_uid_asuidlist(DELIVERYARTICLETYPE);

		$select = '<select name="' . $this->prefixId . '[delArt]" onChange="this.form.submit();">';

		$allowedArticles = array();
		if ($this->conf['delivery.']['allowedArticles']) {
			$allowedArticles = explode(',', $this->conf['delivery.']['allowedArticles']);
		}

			// Hook to define/overwrite individually, which delivery articles are allowed
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['deliveryArticles'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['deliveryArticles'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'deliveryAllowedArticles')) {
				$allowedArticles = $hookObj->deliveryAllowedArticles($this, $allowedArticles);
			}
		}

		$first = FALSE;
		$price_net = '';
		$price_gross = '';
		/** @var $articleObj tx_commerce_article */
		foreach ($this->delProd->articles as $articleUid => $articleObj) {
			if ((!is_array($allowedArticles)) || in_array($articleUid, $allowedArticles)) {
				$select .= '<option value="' . $articleUid . '"';
				if ($articleUid == $this->basketDel[0]) {
					$first = 1;
					$select .= ' selected="selected"';
					$price_net = tx_moneylib::format($articleObj->getPriceNet(), $this->currency);
					$price_gross = tx_moneylib::format($articleObj->getPriceGross(), $this->currency);
				} elseif (!$first) {
					$price_net = tx_moneylib::format($articleObj->getPriceNet(), $this->currency);
					$price_gross = tx_moneylib::format($articleObj->getPriceGross(), $this->currency);
					if (!is_array($this->basketDel) || count($this->basketDel) < 1) {
						$this->basket->add_article($articleUid);
						$this->basket->store_data();
					}
					$first = 1;
				}
				$select .= '>' . $articleObj->getTitle() . '</option>';
			}
		}

		$select .= '</select>';

		$basketArray['###DELIVERY_SELECT_BOX###'] = $select;
		$basketArray['###DELIVERY_PRICE_GROSS###'] = $price_gross;
		$basketArray['###DELIVERY_PRICE_NET###'] = $price_net;

		return $basketArray;
	}

	/**
	 * Generates payment drop down list for this shop
	 *
	 * @param array $basketArray Array of template marker
	 * @return array Array of template marker
	 */
	public function makePayment($basketArray) {
		$this->payProd = t3lib_div::makeInstance('tx_commerce_product');
		$this->payProd->init($this->conf['payProdId'], $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
		$this->payProd->loadData();
		$this->payProd->loadArticles();

		$this->basketPay = $this->basket->get_articles_by_article_type_uid_asuidlist(PAYMENTARTICLETYPE);

		$select = '<select name="' . $this->prefixId . '[payArt]" onChange="this.form.submit();">';

		$addPleaseSelect = FALSE;
		$addDefaultPaymentToBasket = FALSE;
			// Check if a Payment is selected if not, add standard payment
		if (count($this->basketPay) == 0) {
				// Check if Payment selection is forced
			if ($this->conf['payment.']['forceSelection']) {
					// Add Please Select Option
				$select .= '<option value="-1" selected="selected">' . $this->pi_getLL('lang_payment_force') . '</option>';
				$addPleaseSelect = TRUE;
			} else {
					// No payment article is in the basket, so add the first one
				$addDefaultPaymentToBasket = TRUE;
			}
		}

		$allowedArticles = array();
		if ($this->conf['payment.']['allowedArticles']) {
			$allowedArticles = explode(',', $this->conf['payment.']['allowedArticles']);
		}

			// Check if payment articles are allowed
		$newAllowedArticles = array();
		/** @var tx_commerce_article $articleObj */
		foreach ($this->payProd->articles as $articleUid => $articleObj) {
			if ((!is_array($allowedArticles)) || in_array($articleUid, $allowedArticles)) {
				$articleObj->loadData();
				$paymentType = $articleObj->getClassname();
				$payment = $this->getPaymentObject($paymentType);
				if (method_exists($payment, 'isAllowed')) {
					if ($payment->isAllowed()) {
						$newAllowedArticles[] = $articleUid;
					}
				} else {
						// This code is kept for backwards compatibility with
						// 'old' payment that had no 'isAllowed' handling.
						// @TODO: Remove
					$newAllowedArticles[] = $articleUid;
				}
			}
		}
			// If default Paymentarticle is, for example, credit card
			// but when we have an article in the basket with the only possible
			// payment method like debit, this ensures that there is still the correct
			// payment article in the basket.
			// @TODO: Refactor default handling
		if (count($newAllowedArticles) == 1 && $this->conf['defaultPaymentArticleId'] != $newAllowedArticles[0]) {
			$this->conf['defaultPaymentArticleId'] = $newAllowedArticles[0];
		}
		$allowedArticles = $newAllowedArticles;
		unset ($newAllowedArticles);

			// Hook to allow to define/overwrite individually, which payment articles are allowed
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['paymentArticles'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['paymentArticles'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'paymentAllowedArticles')) {
				$allowedArticles = $hookObj->paymentAllowedArticles($this, $allowedArticles);
			}
		}

		$first = FALSE;
		$price_net = '';
		$price_gross = '';
		/** @var $articleObj tx_commerce_article */
		foreach ($this->payProd->articles as $articleUid => $articleObj) {
			if ((!is_array($allowedArticles)) || in_array($articleUid, $allowedArticles)) {
				$select .= '<option value="' . $articleUid . '"';
				if (
					($articleUid == $this->basketPay[0]) || ($addDefaultPaymentToBasket
					&& ($articleUid == $this->conf['defaultPaymentArticleId']))
					&& !$addPleaseSelect
				) {
					$addDefaultPaymentToBasket = FALSE;
					$first = TRUE;
					$select .= ' selected="selected"';
					$this->basket->add_article($articleUid);
					$price_net = tx_moneylib::format($articleObj->getPriceNet(), $this->currency);
					$price_gross = tx_moneylib::format($articleObj->getPriceGross(), $this->currency);
				} elseif (!$first) {
					$price_net = tx_moneylib::format($articleObj->getPriceNet(), $this->currency);
					$price_gross = tx_moneylib::format($articleObj->getPriceGross(), $this->currency);
					$this->basket->delete_article($articleUid);
				}
				$select .= '>' . $articleObj->getTitle() . '</option>';
			}
		}

		$select .= '</select>';

			// Set Prices to 0, if "please select " is shown
		if ($addPleaseSelect) {
			$price_gross = tx_moneylib::format(0, $this->currency);
			$price_net = tx_moneylib::format(0, $this->currency);
		}

		$basketArray['###PAYMENT_SELECT_BOX###'] = $select;
		$basketArray['###PAYMENT_PRICE_GROSS###'] = $price_gross;
		$basketArray['###PAYMENT_PRICE_NET###'] = $price_net;

		$this->basket->store_data();

		return $basketArray;
	}

	/**
	 * Returns a link to the checkout page
	 *
	 * @return string Link to checkout page
	 */
	public function makeCheckOutLink() {
		return $this->pi_linkToPage($this->pi_getLL('checkoutlink'), $this->conf['checkoutPid']);
	}

	/**
	 * @param string $kind
	 * @param tx_commerce_article $article
	 * @param tx_commerce_product $product
	 * @return string
	 */
	public function makeArticleView($kind, $article, $product) {
			// Getting the select attributes for view
		if (is_object($product)) {
			$attributeArray = $product->getAttributeMatrix(array($article->getUid()), $this->select_attributes);

			if (is_array($attributeArray)) {
				$attCode = '';
				$templateAttr = $this->cObj->getSubpart($this->templateCode, '###BASKET_SELECT_ATTRIBUTES###');

				foreach ($attributeArray as $attribute_uid => $myAttribute) {
					/** @var $attributeObj tx_commerce_attribute */
					$attributeObj = t3lib_div::makeInstance('tx_commerce_attribute');
					$attributeObj->init($attribute_uid, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
					$attributeObj->loadData();

					$markerArray['###SELECT_ATTRIBUTES_TITLE###'] = $myAttribute['title'];
					list($k, $v) = each($myAttribute['values']);
					$markerArray['###SELECT_ATTRIBUTES_VALUE###'] = $v['value'];
					$markerArray['###SELECT_ATTRIBUTES_UNIT###'] = $myAttribute['unit'];

					$attCode .= $this->substituteMarkerArrayNoCached($templateAttr, $markerArray, array());
				}
			}
		}

		$markerArray = $article->getMarkerArray($this->cObj, $this->conf['articleTS.'], 'article_');
		$markerArray['###ARTICLE_SELECT_ATTRIBUTES###'] = $attCode;
		$markerArray['###ARTICLE_UID###'] = $article->getUid();
		$markerArray['###STARTFRM###'] = '<form name="basket_' . $article->getUid() . '" action="' . $this->pi_getPageLink($this->conf['basketPid']) . '" method="post">';
		$markerArray['###HIDDENFIELDS###'] = '<input type="hidden" name="' . $this->prefixId . '[catUid]" value="' . (int)$this->piVars['catUid'] . '" />';
		$markerArray['###HIDDENFIELDS###'].= '<input type="hidden" name="' . $this->prefixId . '[artAddUid][' . $article->getUid() . '][price_id]" value="' . $this->basket->basket_items[$art->uid]->get_price_uid() . '" />';
		$markerArray['###ARTICLE_HIDDENFIELDS###'] = '<input type="hidden" name="' . $this->prefixId . '[catUid]" value="' . (int)$this->piVars[catUid] . '" />';
		$markerArray['###ARTICLE_HIDDENFIELDS###'].= '<input type="hidden" name="' . $this->prefixId . '[artAddUid][' . $art->uid . '][price_id]" value="' . $this->basket->basket_items[$art->uid]->get_price_uid() . '" />';
		$markerArray['###QTY_INPUT_VALUE###'] = $this->basket->basket_items[$art->uid]->quantity;
		$markerArray['###QTY_INPUT_NAME###'] = $this->prefixId . '[artAddUid][' . $art->uid . '][count]';
		$markerArray['###BASKET_ITEM_PRICENET###'] = tx_moneylib::format($this->basket->basket_items[$art->uid]->get_price_net(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICEGROSS###'] = tx_moneylib::format($this->basket->basket_items[$art->uid]->get_price_gross(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICENETNOSCALE###'] = tx_moneylib::format($this->basket->basket_items[$art->uid]->getNoScalePriceNet(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICEGROSSNOSCALE###'] = tx_moneylib::format($this->basket->basket_items[$art->uid]->getNoScalePriceGross(), $this->currency);
		$markerArray['###BASKET_ITEM_COUNT###'] = $this->basket->basket_items[$art->uid]->get_quantity();
		$markerArray['###BASKET_ITEM_PRICESUM_NET###'] = tx_moneylib::format($this->basket->basket_items[$art->uid]->get_item_sum_net(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICESUM_GROSS###'] = tx_moneylib::format($this->basket->basket_items[$art->uid]->get_item_sum_gross(), $this->currency);

		// Link to delete this article in basket
		if (is_array($this->conf['deleteItem.'])) {
			$typoLinkConf = $this->conf['deleteItem.'];
		} else {
			$typoLinkConf = array();
		}
		$typoLinkConf['parameter'] = $this->conf['basketPid'];
		$typoLinkConf['useCacheHash'] = 1;
		$typoLinkConf['additionalParams'].= ini_get('arg_separator.output') . $this->prefixId . '[catUid]=' . (int)$this->piVars['catUid'];
		$typoLinkConf['additionalParams'].= ini_get('arg_separator.output') . $this->prefixId . '[artAddUid][' . $art->uid . '][price_id]=' . $this->basket->basket_items[$art->uid]->get_price_uid();
		$typoLinkConf['additionalParams'].= ini_get('arg_separator.output') . $this->prefixId . '[artAddUid][' . $art->uid . '][count]=0';
		$markerArray['###DELIOTMFROMBASKETLINK###'] = $this->cObj->typoLink($this->pi_getLL('lang_basket_delete_item'), $typoLinkConf);

		$templateMarker = '###PRODUCT_BASKET_FORM_SMALL###';
		$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);

		$markerArray = array_merge($markerArray, $this->languageMarker);

		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeArticleView'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeArticleView'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'additionalMarker')) {
				$markerArray = $hookObj->additionalMarker($markerArray, $this, $art, $prod);
			}
		}

		$content = $this->substituteMarkerArrayNoCached($template, $markerArray);

		return $content;
	}


	/**
	 * Renders the product list for the basket
	 *
	 * @return string HTML Content
	 */
	function makeProductList() {
		$content = '';

		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeProductList'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeProductList'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['alternativePrefixId'])) {
			$hookObject = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['alternativePrefixId']);
		}
		if (method_exists($hookObject, 'SingeDisplayPrefixId')) {
			$altPrefixSingle = $hookObject->SingeDisplayPrefixId();
		} else {
			$altPrefixSingle = $this->prefixId;
		}

		$list = array();
		$articleTypes = explode(',', $this->conf['regularArticleTypes']);
		while (list($k, $type) = each($articleTypes)) {
			$list = array_merge($list, $this->basket->get_articles_by_article_type_uid_asuidlist($type));
		}

		// ###########    product list    ######################
		$templateMarker[] = '###' . strtoupper($this->conf['templateMarker.']['items_listview']) . '###';
		$templateMarker[] = '###' . strtoupper($this->conf['templateMarker.']['items_listview2']) . '###';

		$changerowcount = 0;
		while (list($k, $v) = each($list)) {
			// Fill marker arrays with product/article values
			/** @var $myItem tx_commerce_basket_item */
			$myItem = $this->basket->basket_items[$v];

			// Check stock
			$stockOK = FALSE;
			if ($this->conf['checkStock'] == 1) {
				if ($myItem->article->hasStock($myItem->getQuantity())) {
					$stockOK = TRUE;
				}
			} else {
				$stockOK = TRUE;
			}

			// Check accessible
			if ($myItem->product->isAccessible() && $myItem->article->isAccessible()) {
				$access = TRUE;
			} else {
				$access = FALSE;
			}

			// Only if Stock is ok and Access is ok (could have been changed since the article was put into the basket
			if (($stockOK == TRUE) && ($access == TRUE)) {
				$safePrefix = $this->prefixId;

				$typoLinkConf = array();
				$typoLinkConf['parameter'] = $this->conf['listPid'];
				$typoLinkConf['useCacheHash'] = 1;
				$typoLinkConf['additionalParams'].= ini_get('arg_separator.output') . $this->prefixId . '[catUid]=' . $myItem->product->get_masterparent_categorie();
				$typoLinkConf['additionalParams'].= ini_get('arg_separator.output') . $this->prefixId . '[showUid]=' . $myItem->product->get_uid();
				if ($this->basketHashValue) {
					$typoLinkConf['additionalParams'].= ini_get('arg_separator.output') . $this->prefixId . '[basketHashValue]=' . $this->basketHashValue;
				}
				$lokalTSproduct = $this->addTypoLinkToTS($this->conf['fields.']['products.'], $typoLinkConf);
				$lokalTSArtikle = $this->addTypoLinkToTS($this->conf['fields.']['articles.'], $typoLinkConf);

				$this->prefixId = $altPrefixSingle;

				$wrapMarkerArray["###PRODUCT_LINK_DETAIL###"] = explode('|', $this->pi_list_linkSingle("|", $myItem->product->get_uid(), 1, array('catUid' => intval($myItem->product->get_masterparent_categorie())), FALSE, $this->conf['listPid']));

				$this->prefixId = $safePrefix;

				$markerArray = $this->generateMarkerArray($myItem->getProductAssocArray(''), $lokalTSproduct, 'product_');
				$this->articleMarkerArr = $this->generateMarkerArray($myItem->getArticleAssocArray(''), $lokalTSArtikle, 'article_');
				$this->select_attributes = $myItem->product->get_attributes(array(ATTRIB_selector));
				$markerArray["PRODUCT_BASKET_FOR_LISTVIEW"] = $this->makeArticleView($myItem->article, $myItem->product);
				$templateselector = $changerowcount % 2;

				foreach($hookObjectsArr as $hookObj)    {
					if (method_exists($hookObj, 'changeProductTemplate')) {
						$templateMarker =  $hookObj->changeProductTemplate($templateMarker, $myItem, $this);
					}
				}

				$template = $this->cObj->getSubpart($this->templateCode, $templateMarker[$templateselector]);
				$changerowcount++;

				$template = $this->cObj->substituteSubpart($template, '###PRODUCT_BASKET_FORM_SMALL###', '');
				$markerArray = array_merge($markerArray, $this->articleMarkerArr);

				foreach($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'additionalMarkerProductList')) {
						$markerArray = $hookObj->additionalMarkerProductList($markerArray, $myItem, $this);
					}
				}

				$tempContent = $this->cObj->substituteMarkerArray($template, $markerArray, '###|###', 1);
				$content .= $this->substituteMarkerArrayNoCached($tempContent, $this->languageMarker, array(), $wrapMarkerArray);
			} else {
				// Remove article from basket
				$this->basket->delete_article($myItem->article->getUid());
				$this->basket->store_data();
			}
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi2/class.tx_commerce_pi2.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi2/class.tx_commerce_pi2.php']);
}

?>
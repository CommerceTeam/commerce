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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basket pi for commerce. This class is used to handle all events concerning
 * the basket. E.g. Adding things to basket, changing basket
 *
 * The basket itself is stored inside
 * frontend user basket
 *
 * Class Tx_Commerce_Controller_BasketController
 *
 * @author Volker Graubaum <vg@e-netconsulting.de>
 */
class Tx_Commerce_Controller_BasketController extends Tx_Commerce_Controller_BaseController {
	/**
	 * Same as class name
	 *
	 * @var string
	 */
	public $prefixId = 'tx_commerce_pi1';

	/**
	 * Flag if object should be handled as user int
	 *
	 * @var bool
	 */
	public $pi_USER_INT_obj = TRUE;

	/**
	 * No stock handling
	 *
	 * @var string
	 */
	public $noStock = '';

	/**
	 * Delivery product
	 *
	 * @var Tx_Commerce_Domain_Model_Product
	 */
	public $deliveryProduct;

	/**
	 * Basket delivery articles
	 *
	 * @var array
	 */
	public $basketDeliveryArticles;

	/**
	 * Payment product
	 *
	 * @var Tx_Commerce_Domain_Model_Product
	 */
	public $paymentProduct;

	/**
	 * Basket payment articles
	 *
	 * @var array
	 */
	public $basketPaymentArticles;

	/**
	 * Basket object
	 *
	 * @var Tx_Commerce_Domain_Model_Basket
	 */
	protected $basket = NULL;

	/**
	 * Marker array
	 *
	 * @var array
	 */
	protected $markerArray = array();

	/**
	 * Compiled content
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Price limit for basket
	 *
	 * @var int
	 */
	protected $priceLimitForBasket = 0;

	/**
	 * Standard Init Method for all
	 * pi plugins of tx_commerce
	 *
	 * @param array $conf Configuration
	 *
	 * @return void
	 */
	protected function init(array $conf = array()) {
		parent::init($conf);

		$this->initBasket();

		if ($this->conf['defaultCode']) {
			$this->handle = strtoupper($this->conf['defaultCode']);
		}
		if ($this->cObj->data['select_key']) {
			$this->handle = strtoupper($this->cObj->data['select_key']);
		}

		if (empty($this->conf['templateFile'])) {
			$this->error('init', __LINE__, 'Template File not defined in TS: ');
		}
		$this->setTemplateCode($this->cObj->fileResource($this->conf['templateFile']));
		if (empty($this->getTemplateCode())) {
			$this->error('init', __LINE__, 'Template File not loaded, maybe it doesn\'t exist: ' . $this->conf['templateFile']);
		}

		$this->handleBasket();

		// Define the currency
		if (strlen($this->conf['currency']) > 0) {
			$this->currency = $this->conf['currency'];
		}
	}

	/**
	 * Initialize basket
	 *
	 * @return void
	 */
	public function initBasket() {
		$this->basket = $this->getFrontendController()->fe_user->tx_commerce_basket;
		$this->basket->setTaxCalculationMethod($this->conf['priceFromNet']);
		$this->basket->loadData();
	}

	/**
	 * Main function called by insert plugin
	 *
	 * @param string $content Content
	 * @param array $conf Configuration
	 *
	 * @return string HTML-Content
	 */
	public function main($content = '', array $conf = array()) {
		$this->init($conf);

		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['main'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'main\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'main\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['main'] as $classRef) {
				$hookObjectsArr[] = GeneralUtility::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['main'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['main'] as $classRef) {
				$hookObjectsArr[] = GeneralUtility::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postInit')) {
				$result = $hookObj->postInit($this);
				if ($result === FALSE) {
					return $this->pi_wrapInBaseClass($this->getContent());
				}
			}
		}

		$regularArticleCount = $this->basket->getArticleTypeCountFromList(explode(',', $this->conf['regularArticleTypes']));

		if (!$this->basket->getItemsCount() && !$regularArticleCount) {
			// If basket is empty, it should be rewritable, release locks, if there are any
			$this->basket->releaseReadOnly();
			$this->basket->storeData();
		}

		if ($this->basket->getItemsCount() && $regularArticleCount) {
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

			$template = $this->cObj->getSubpart($this->getTemplateCode(), $templateMarker);

			$markerArray = $this->languageMarker;
			$markerArray['###EMPTY_BASKET###'] = $this->cObj->cObjGetSingle($this->conf['emptyContent'], $this->conf['emptyContent.']);
			$markerArray['###NO_STOCK MESSAGE###'] = $this->noStock;
			$this->pi_linkTP('', array(), 0, $this->conf['basketPid']);
			$basketArray['###BASKETURL###'] = $this->cObj->lastTypoLinkUrl;
			$this->pi_linkTP('', array(), 0, $this->conf['checkoutPid']);
			$basketArray['###URL_CHECKOUT###'] = $this->cObj->lastTypoLinkUrl;

			// Hook for additional markers in empty quick view basket template
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'additionalMarker')) {
					$markerArray = $hookObj->additionalMarker($markerArray, $this);
				}
			}

			$this->setContent($this->cObj->substituteMarkerArray($template, $markerArray));
		}
		$this->setContent($this->cObj->substituteMarkerArray($this->getContent(), $this->languageMarker));

		return $this->pi_wrapInBaseClass($content . $this->getContent());
	}

	/**
	 * Main method to handle the basket. Is called when data in the basket is changed
	 * Changes the basket object and stores the data in the frontend user session
	 *
	 * @return void
	 */
	public function handleBasket() {
		$this->handleDeleteBasket();
		$this->handleAddArticle();
		$this->handlePaymentArticle();
		$this->handleDeliveryArticle();

		$this->basket->storeData();
	}

	/**
	 * Handle basket deletion
	 *
	 * @return void
	 */
	public function handleDeleteBasket() {
		if ($this->piVars['delBasket']) {
			$this->basket->deleteAllArticles();

			// Hook to process basket after deleting all articles from basket
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelBasket'])) {
				GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'postdelBasket\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'addArticleUid\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelBasket'] as $classRef) {
					$hookObj = &GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postdelBasket')) {
						$hookObj->postdelBasket($this->basket, $this);
					}
				}
			}
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['postDeleteBasket'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['postDeleteBasket'] as $classRef) {
					$hookObj = &GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postdelBasket')) {
						$hookObj->postdelBasket($this->basket, $this);
					}
				}
			}
		}
	}

	/**
	 * Handle adding article
	 *
	 * @return void
	 */
	public function handleAddArticle() {
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['artAddUid'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'artAddUid\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'addArticleUid\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['artAddUid'] as $classRef) {
				$hookObjectsArr[] = &GeneralUtility::getUserObj($classRef);
			}
		}
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['addArticleUid'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['addArticleUid'] as $classRef) {
				$hookObjectsArr[] = &GeneralUtility::getUserObj($classRef);
			}
		}

		// Hook to process basket before adding an article to basket
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'preartAddUid')) {
				$hookObj->preartAddUid($this->basket, $this);
			}
		}

		if (isset($this->piVars['artAddUid']) && is_array($this->piVars['artAddUid'])) {
			foreach ($this->piVars['artAddUid'] as $articleUid => $articleAddValues) {
				$articleUid = (int) $articleUid;

				/**
				 * Basket item
				 *
				 * @var Tx_Commerce_Domain_Model_BasketItem $basketItem
				 */
				$basketItem = $this->basket->getBasketItem($articleUid);

				// Safe old quantity for price limit
				if ($basketItem) {
					$oldCountValue = $basketItem->getQuantity();
				} else {
					$oldCountValue = 0;
				}

				if (!isset($articleAddValues['count']) || $articleAddValues['count'] < 0) {
					$articleAddValues['count'] = 1;
				}

				if ((int) $articleAddValues['count'] === 0) {
					if ($this->basket->getQuantity($articleUid) > 0) {
						$this->basket->deleteArticle($articleUid);
					}

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'postDeleteArtUidSingle')) {
							$hookObj->postDeleteArtUidSingle($articleUid, $articleAddValues, $oldCountValue, $this->basket, $this);
						}
					}
				} else {
					/**
					 * Article
					 *
					 * @var $articleObj Tx_Commerce_Domain_Model_Article
					 */
					$articleObj = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Article', $articleUid);
					$articleObj->loadData('basket');

					$productObj = $articleObj->getParentProduct();
					$productObj->loadData('basket');

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'preartAddUidSingle')) {
							$hookObj->preartAddUidSingle($articleUid, $articleAddValues, $productObj, $articleObj, $this->basket, $this);
						}
					}

					if ($articleObj->isAccessible() && $productObj->isAccessible()) {
						// Only if product and article are accessible
						if ($this->conf['checkStock'] == 1) {
							// Instance to calculate shipping costs
							if ($articleObj->hasStock($articleAddValues['count'])) {
								if ((int)$articleAddValues['price_id'] > 0) {
									$this->basket->addArticle($articleUid, $articleAddValues['count'], $articleAddValues['price_id']);
								} else {
									$this->basket->addArticle($articleUid, $articleAddValues['count']);
								}
							} else {
								$this->noStock = $this->pi_getLL('noStock');
							}
						} else {
							// Add article by default
							if ((int)$articleAddValues['price_id'] > 0) {
								$this->basket->addArticle($articleUid, $articleAddValues['count'], $articleAddValues['price_id']);
							} else {
								$this->basket->addArticle($articleUid, $articleAddValues['count']);
							}
						}
					}

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'postartAddUidSingle')) {
							$hookObj->postartAddUidSingle($articleUid, $articleAddValues, $productObj, $articleObj, $this->basket, $this);
						}
					}

					// Check for basket price limit
					if (
						(int) $this->conf['priceLimitForBasket'] > 0 &&
						$this->basket->getSumGross() > (int) $this->conf['priceLimitForBasket']
					) {
						$this->basket->addArticle($articleUid, $oldCountValue);
						$this->setPriceLimitForBasket(1);
					}
				}
			}

			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postartAddUid')) {
					$hookObj->postartAddUid($this->basket, $this);
				}
			}
		}
	}

	/**
	 * Handle payment articles
	 *
	 * @return void
	 */
	public function handlePaymentArticle() {
		if ($this->piVars['payArt']) {
			$basketPay = $this->basket->getArticlesByArticleTypeUidAsUidlist(PAYMENTARTICLETYPE);

			// Delete old payment article
			foreach ($basketPay as $actualPaymentArticle) {
				$this->basket->deleteArticle($actualPaymentArticle);
			}

			// Add new article
			if (is_array($this->piVars['payArt'])) {
				foreach ($this->piVars['payArt'] as $articleUid => $articleCount) {
					// Set to int to be sure it is int
					$articleUid = (int) $articleUid;
					$articleCount = (int) $articleCount;
					$this->basket->addArticle($articleUid, $articleCount['count']);
				}
			} else {
				$this->basket->addArticle((int) $this->piVars['payArt']);
			}

			// Hook to process the basket after adding payment article
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postpayArt'])) {
				GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'postpayArt\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'postPaymentArticle\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postpayArt'] as $classRef) {
					$hookObj = &GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postpayArt')) {
						$hookObj->postpayArt($this->basket, $this);
					}
				}
			}
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['postPaymentArticle'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['postPaymentArticle'] as $classRef) {
					$hookObj = &GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postpayArt')) {
						$hookObj->postpayArt($this->basket, $this);
					}
				}
			}
		}
	}

	/**
	 * Handle delivery articles
	 *
	 * @return void
	 */
	public function handleDeliveryArticle() {
		if ($this->piVars['delArt']) {
			$basketDeliveryArticles = $this->basket->getArticlesByArticleTypeUidAsUidlist(DELIVERYARTICLETYPE);

			// Delete old delivery article
			foreach ($basketDeliveryArticles as $singleDeliveryArticle) {
				$this->basket->deleteArticle($singleDeliveryArticle);
			}

			// Add new article
			if (is_array($this->piVars['delArt'])) {
				foreach ($this->piVars['delArt'] as $articleUid => $articleCount) {
					$articleUid = (int) $articleUid;
					$articleCount = (int) $articleCount;
					$this->basket->addArticle($articleUid, $articleCount['count']);
				}
			} else {
				$this->basket->addArticle((int) $this->piVars['delArt']);
			}

			// Hook to process the basket after adding delivery article
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelArt'])) {
				GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'postdelArt\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'postDeliveryArticle\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['postdelArt'] as $classRef) {
					$hookObj = &GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postdelArt')) {
						$hookObj->postdelArt($this->basket, $this);
					}
				}
			}
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['postDeliveryArticle'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['postDeliveryArticle'] as $classRef) {
					$hookObj = &GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postdelArt')) {
						$hookObj->postdelArt($this->basket, $this);
					}
				}
			}
		}
	}

	/**
	 * Returns a list of markers to generate a quick-view of the basket
	 *
	 * @todo: implement getQuickView
	 *
	 * @return array Marker array for rendering
	 */
	public function getQuickView() {
		$articleTypes = explode(',', $this->conf['regularArticleTypes']);

		$templateMarker = '###PRODUCT_BASKET_QUICKVIEW###';
		$template = $this->cObj->getSubpart($this->getTemplateCode(), $templateMarker);

		$basketArray = $this->languageMarker;
		$basketArray['###PRICE_GROSS###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getSumGross(), $this->currency);
		$basketArray['###PRICE_NET###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getSumNet(), $this->currency);

		// @deprecated ###ITEMS###
		$basketArray['###ITEMS###'] = 'DEPRECATED WILL BE REMOVED IN COMMERCE 3.0.0! ' .
			$this->basket->getArticleTypeCountFromList($articleTypes);

		$basketArray['###BASKET_ITEMS###'] = $this->basket->getArticleTypeCountFromList($articleTypes);
		$this->pi_linkTP('', array(), 0, $this->conf['basketPid']);
		$basketArray['###BASKETURL###'] = $this->cObj->lastTypoLinkUrl;
		$this->pi_linkTP('', array(), 0, $this->conf['checkoutPid']);
		$basketArray['###URL_CHECKOUT###'] = $this->cObj->lastTypoLinkUrl;

		// Hook for additional markers in quick view basket template
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['getQuickView'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'getQuickView\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'getQuickView\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['getQuickView'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'additionalMarker')) {
					$basketArray = $hookObj->additionalMarker($basketArray, $this);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['getQuickView'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['getQuickView'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'additionalMarker')) {
					$basketArray = $hookObj->additionalMarker($basketArray, $this);
				}
			}
		}

		$this->setContent($this->cObj->substituteMarkerArray($template, $basketArray));
		return TRUE;
	}

	/**
	 * Generates HTML-Code of the basket and stores content
	 *
	 * @return void
	 */
	public function generateBasket() {
		$template = $this->cObj->getSubpart($this->getTemplateCode(), '###BASKET###');

		// Render locked information
		if ($this->basket->getReadOnly()) {
			$basketSubpart = $this->cObj->getSubpart($template, 'BASKETLOCKED');
			$template = $this->cObj->substituteSubpart($template, 'BASKETLOCKED', $basketSubpart);
		} else {
			$template = $this->cObj->substituteSubpart($template, 'BASKETLOCKED', '');
		}

		$basketArray['###BASKET_PRODUCT_LIST###'] = $this->makeProductList();

		// Generate basket hooks
		$hookObject = NULL;
		if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasket'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'generateBasket\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'generateBasket\']
			');
			$hookObject = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasket']);
		}
		if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['generateBasket'])) {
			$hookObject = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['generateBasket']);
		}

		// No delivery article is present, so draw selector
		$contentDelivery = $this->cObj->getSubpart($this->getTemplateCode(), '###DELIVERYBOX###');

		if (method_exists($hookObject, 'makeDelivery')) {
			$contentDelivery = $hookObject->makeDelivery($this, $this->basket, $contentDelivery);
			$template = $this->cObj->substituteSubpart($template, '###DELIVERYBOX###', $contentDelivery);
		} else {
			$deliveryArray = $this->makeDelivery();
			$contentDelivery = $this->cObj->substituteMarkerArray($contentDelivery, $deliveryArray);
			$template = $this->cObj->substituteSubpart($template, '###DELIVERYBOX###', $contentDelivery);
		}

		$contentPayment = $this->cObj->getSubpart($this->getTemplateCode(), '###PAYMENTBOX###');
		if (method_exists($hookObject, 'makePayment')) {
			$contentPayment = $hookObject->makePayment($this, $this->basket, $contentPayment);
			$template = $this->cObj->substituteSubpart($template, '###PAYMENTBOX###', $contentPayment);
		} else {
			$paymentArray = $this->makePayment();
			$contentPayment = $this->cObj->substituteMarkerArray($contentPayment, $paymentArray);
			$template = $this->cObj->substituteSubpart($template, '###PAYMENTBOX###', $contentPayment);
		}

		$taxRateTemplate = $this->cObj->getSubpart($template, '###TAX_RATE_SUMS###');
		$taxRates = $this->basket->getTaxRateSums();
		$taxRateRows = '';
		foreach ($taxRates as $taxRate => $taxRateSum) {
			$taxRowArray = array();
			$taxRowArray['###TAX_RATE###'] = $taxRate;
			$taxRowArray['###TAX_RATE_SUM###'] = Tx_Commerce_ViewHelpers_Money::format($taxRateSum, $this->currency);
			$taxRateRows .= $this->cObj->substituteMarkerArray($taxRateTemplate, $taxRowArray);
		}

		$template = $this->cObj->substituteSubpart($template, '###TAX_RATE_SUMS###', $taxRateRows);

		$basketArray['###BASKET_NET_PRICE###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getSumNet(), $this->currency);
		$basketArray['###BASKET_GROSS_PRICE###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getSumGross(), $this->currency);
		$basketArray['###BASKET_TAX_PRICE###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getSumGross() - $this->basket->getSumNet(), $this->currency);
		$basketArray['###BASKET_VALUE_ADDED_TAX###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getSumGross() - $this->basket->getSumNet(), $this->currency);
		$basketArray['###BASKET_ITEMS###'] = $this->basket->getItemsCount();
		$basketArray['###DELBASKET###'] = $this->pi_linkTP_keepPIvars($this->pi_getLL('delete_basket', 'delete basket'), array('delBasket' => 1), 0, 1);
		$basketArray['###BASKET_NEXTBUTTON###'] = $this->cObj->stdWrap($this->makeCheckOutLink(), $this->conf['nextbutton.']);
		$basketArray['###BASKET_ARTICLES_NET_SUM###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getArticleTypeSumNet(NORMALARTICLETYPE), $this->currency);
		$basketArray['###BASKET_ARTICLES_GROSS_SUM###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getArticleTypeSumGross(NORMALARTICLETYPE), $this->currency);
		$basketArray['###BASKET_DELIVERY_NET_SUM###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getArticleTypeSumNet(DELIVERYARTICLETYPE), $this->currency);
		$basketArray['###BASKET_DELIVERY_GROSS_SUM###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getArticleTypeSumGross(DELIVERYARTICLETYPE), $this->currency);
		$basketArray['###BASKET_PAYMENT_NET_SUM###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getArticleTypeSumNet(PAYMENTARTICLETYPE), $this->currency);
		$basketArray['###BASKET_PAYMENT_GROSS_SUM###'] = Tx_Commerce_ViewHelpers_Money::format($this->basket->getArticleTypeSumGross(PAYMENTARTICLETYPE), $this->currency);
		$basketArray['###BASKET_PAYMENT_ITEMS###'] = $this->basket->getArticleTypeCount(PAYMENTARTICLETYPE);
		$basketArray['###BASKET_DELIVERY_ITEMS###'] = $this->basket->getArticleTypeCount(DELIVERYARTICLETYPE);
		$basketArray['###BASKET_ARTICLES_ITEMS###'] = $this->basket->getArticleTypeCount(NORMALARTICLETYPE);
		$this->pi_linkTP('', array(), 0, $this->conf['basketPid']);
		$basketArray['###BASKETURL###'] = $this->cObj->lastTypoLinkUrl;
		$this->pi_linkTP('', array(), 0, $this->conf['checkoutPid']);
		$basketArray['###URL_CHECKOUT###'] = $this->cObj->lastTypoLinkUrl;
		$basketArray['###NO_STOCK_MESSAGE###'] = $this->noStock;
		$basketArray['###BASKET_LASTPRODUCTURL###'] = $this->cObj->stdWrap(
			$this->getFrontendController()->fe_user->getKey('ses', 'tx_commerce_lastproducturl'),
			$this->conf['lastProduct']
		);

		if ($this->getPriceLimitForBasket() == 1 && $this->conf['priceLimitForBasketMessage']) {
			$basketArray['###BASKET_PRICELIMIT###'] = $this->cObj->cObjGetSingle(
				$this->conf['priceLimitForBasketMessage'], $this->conf['priceLimitForBasketMessage.']
			);
		} else {
			$basketArray['###BASKET_PRICELIMIT###'] = '';
		}

		$basketArray = array_merge($basketArray, $this->languageMarker);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasketMarker'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'generateBasketMarker\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'generateBasketMarker\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['generateBasketMarker'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'additionalMarker')) {
					$basketArray = $hookObj->additionalMarker($basketArray, $this, $template);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['generateBasketMarker'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['generateBasketMarker'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'additionalMarker')) {
					$basketArray = $hookObj->additionalMarker($basketArray, $this, $template);
				}
			}
		}

		$this->setContent($this->cObj->substituteMarkerArray($template, $basketArray));

		$markerArrayGlobal = $this->addFormMarker(array());

		$this->setContent($this->cObj->substituteMarkerArray($this->getContent(), $markerArrayGlobal, '###|###'));
	}

	/**
	 * Generates the Markers for the delivery-selector
	 *
	 * @param array $basketArray Array of marker
	 *
	 * @return array Markers
	 */
	public function makeDelivery(array $basketArray = array()) {
		$this->deliveryProduct = GeneralUtility::makeInstance(
			'Tx_Commerce_Domain_Model_Product',
			$this->conf['delProdId'],
			$this->getFrontendController()->tmpl->setup['config.']['sys_language_uid']
		);
		$this->deliveryProduct->loadData();
		$this->deliveryProduct->loadArticles();

		$deliverySelectTemplate = $this->cObj->getSubpart($this->getTemplateCode(), '###DELIVERY_ARTICLE_SELECT###');
		$deliveryOptionTemplate = $this->cObj->getSubpart($this->getTemplateCode(), '###DELIVERY_ARTICLE_OPTION###');

		$this->basketDeliveryArticles = $this->basket->getArticlesByArticleTypeUidAsUidlist(DELIVERYARTICLETYPE);

		$allowedArticles = array();
		if ($this->conf['delivery.']['allowedArticles']) {
			$allowedArticles = explode(',', $this->conf['delivery.']['allowedArticles']);
		}

		// Hook to define/overwrite individually, which delivery articles are allowed
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['deliveryArticles'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'deliveryArticles\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'deliveryArticles\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['deliveryArticles'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'deliveryAllowedArticles')) {
					$allowedArticles = $hookObj->deliveryAllowedArticles($this, $allowedArticles);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['deliveryArticles'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['deliveryArticles'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'deliveryAllowedArticles')) {
					$allowedArticles = $hookObj->deliveryAllowedArticles($this, $allowedArticles);
				}
			}
		}
		$allArticlesAllowed = !count($allowedArticles);
		$activeFlag = strpos($deliveryOptionTemplate, '<option') !== FALSE ? ' selected="selected"' : ' checked="checked"';

		$priceNet = '';
		$priceGross = '';
		$options = '';
		$first = FALSE;
		/**
		 * Article
		 *
		 * @var $deliveryArticle Tx_Commerce_Domain_Model_Article
		 */
		foreach ($this->deliveryProduct->getArticleObjects() as $deliveryArticle) {
			if ($allArticlesAllowed || in_array($deliveryArticle->getUid(), $allowedArticles)) {
				$selected = '';

				if ($deliveryArticle->getUid() == $this->basketDeliveryArticles[0]) {
					$selected = $activeFlag;
					$priceNet = Tx_Commerce_ViewHelpers_Money::format($deliveryArticle->getPriceNet(), $this->currency);
					$priceGross = Tx_Commerce_ViewHelpers_Money::format($deliveryArticle->getPriceGross(), $this->currency);
				} elseif (!$first) {
					if (empty($this->basketDeliveryArticles[0])) {
						$selected = $activeFlag;
					}

					$priceNet = Tx_Commerce_ViewHelpers_Money::format($deliveryArticle->getPriceNet(), $this->currency);
					$priceGross = Tx_Commerce_ViewHelpers_Money::format($deliveryArticle->getPriceGross(), $this->currency);
					if (!is_array($this->basketDeliveryArticles) || count($this->basketDeliveryArticles) < 1) {
						$this->getBasket()->addArticle($deliveryArticle->getUid());
						$this->getBasket()->storeData();
					}
				}

				$markerArray = array(
					'value' => $deliveryArticle->getUid(),
					'label' => $deliveryArticle->getTitle(),
					'selected' => $selected,
					'description' => $this->cObj->stdWrap(
						$deliveryArticle->getDescriptionExtra(),
						$this->conf['fields.']['articles.']['fields.']['description_extra.']
					),
				);
				$options .= $this->cObj->substituteMarkerArray($deliveryOptionTemplate, $markerArray, '###|###', TRUE);

				$first = TRUE;
			}
		}

		$basketArray['###DELIVERY_SELECT_BOX###'] = $this->cObj->substituteMarker($deliverySelectTemplate, '###OPTIONS###', $options);
		$basketArray['###DELIVERY_PRICE_GROSS###'] = $priceGross;
		$basketArray['###DELIVERY_PRICE_NET###'] = $priceNet;

		return $basketArray;
	}

	/**
	 * Generates payment drop down list for this shop
	 *
	 * @param array $basketArray Array of template marker
	 *
	 * @return array Template marker
	 */
	public function makePayment(array $basketArray = array()) {
		$this->paymentProduct = GeneralUtility::makeInstance(
			'Tx_Commerce_Domain_Model_Product',
			$this->conf['payProdId'],
			$this->getFrontendController()->tmpl->setup['config.']['sys_language_uid']
		);
		$this->paymentProduct->loadData();
		$this->paymentProduct->loadArticles();

		$this->basketPaymentArticles = $this->basket->getArticlesByArticleTypeUidAsUidlist(PAYMENTARTICLETYPE);

		$select = '<select name="' . $this->prefixId . '[payArt]" onChange="this.form.submit();">';

		$addPleaseSelect = FALSE;
		$addDefaultPaymentToBasket = FALSE;
		// Check if a Payment is selected if not, add standard payment
		if (count($this->basketPaymentArticles) == 0) {
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
		/**
		 * Article
		 *
		 * @var Tx_Commerce_Domain_Model_Article $article
		 */
		foreach ($this->paymentProduct->getArticleObjects() as $articleUid => $article) {
			if (!count($allowedArticles) || in_array($articleUid, $allowedArticles)) {
				$article->loadData();
				$payment = $this->getPaymentObject($article->getClassname());
				if ($payment->isAllowed()) {
					$newAllowedArticles[] = $articleUid;
				}
			}
		}

		// If default Paymentarticle is, for example, credit card
		// but when we have an article in the basket with the only possible
		// payment method like debit, this ensures that there is still the correct
		// payment article in the basket.
		// @todo: Refactor default handling
		if (count($newAllowedArticles) == 1 && $this->conf['defaultPaymentArticleId'] != $newAllowedArticles[0]) {
			$this->conf['defaultPaymentArticleId'] = $newAllowedArticles[0];
		}
		$allowedArticles = $newAllowedArticles;
		unset ($newAllowedArticles);

		// Hook to allow to define/overwrite individually, which payment
		// articles are allowed
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['paymentArticles'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'paymentArticles\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'paymentArticles\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['paymentArticles'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'paymentAllowedArticles')) {
					$allowedArticles = $hookObj->paymentAllowedArticles($this, $allowedArticles);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['paymentArticles'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['paymentArticles'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'paymentAllowedArticles')) {
					$allowedArticles = $hookObj->paymentAllowedArticles($this, $allowedArticles);
				}
			}
		}

		$first = FALSE;
		$priceNet = '';
		$priceGross = '';
		/**
		 * Article
		 *
		 * @var $article Tx_Commerce_Domain_Model_Article
		 */
		foreach ($this->paymentProduct->getArticleObjects() as $articleUid => $article) {
			if (!count($allowedArticles) || in_array($articleUid, $allowedArticles)) {
				$select .= '<option value="' . $articleUid . '"';
				if (
					($articleUid == $this->basketPaymentArticles[0]) || ($addDefaultPaymentToBasket
					&& ($articleUid == $this->conf['defaultPaymentArticleId']))
					&& !$addPleaseSelect
				) {
					$addDefaultPaymentToBasket = FALSE;
					$first = TRUE;
					$select .= ' selected="selected"';
					$this->basket->addArticle($articleUid);
					$priceNet = Tx_Commerce_ViewHelpers_Money::format($article->getPriceNet(), $this->currency);
					$priceGross = Tx_Commerce_ViewHelpers_Money::format($article->getPriceGross(), $this->currency);
				} elseif (!$first) {
					$priceNet = Tx_Commerce_ViewHelpers_Money::format($article->getPriceNet(), $this->currency);
					$priceGross = Tx_Commerce_ViewHelpers_Money::format($article->getPriceGross(), $this->currency);
					$this->basket->deleteArticle($articleUid);
				}
				$select .= '>' . $article->getTitle() . '</option>';
			}
		}

		$select .= '</select>';

		// Set Prices to 0, if "please select " is shown
		if ($addPleaseSelect) {
			$priceGross = Tx_Commerce_ViewHelpers_Money::format(0, $this->currency);
			$priceNet = Tx_Commerce_ViewHelpers_Money::format(0, $this->currency);
		}

		$basketArray['###PAYMENT_SELECT_BOX###'] = $select;
		$basketArray['###PAYMENT_PRICE_GROSS###'] = $priceGross;
		$basketArray['###PAYMENT_PRICE_NET###'] = $priceNet;

		$this->basket->storeData();

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
	 * Make article view
	 *
	 * @param Tx_Commerce_Domain_Model_Article $article Article
	 * @param Tx_Commerce_Domain_Model_Product $product Product
	 *
	 * @return string
	 */
	public function makeArticleView(Tx_Commerce_Domain_Model_Article $article, Tx_Commerce_Domain_Model_Product $product) {
		// Getting the select attributes for view
		$attCode = '';
		if (is_object($product)) {
			$attributeArray = $product->getAttributeMatrix(array($article->getUid()), $this->selectAttributes);

			if (is_array($attributeArray)) {
				$templateAttr = $this->cObj->getSubpart($this->getTemplateCode(), '###BASKET_SELECT_ATTRIBUTES###');

				foreach ($attributeArray as $attributeUid => $myAttribute) {
					/**
					 * Attribute
					 *
					 * @var $attributeObj Tx_Commerce_Domain_Model_Attribute
					 */
					$attributeObj = GeneralUtility::makeInstance(
						'Tx_Commerce_Domain_Model_Attribute',
						$attributeUid,
						$this->getFrontendController()->tmpl->setup['config.']['sys_language_uid']
					);
					$attributeObj->loadData();

					$markerArray['###SELECT_ATTRIBUTES_TITLE###'] = $myAttribute['title'];
					$value = current(array_slice(each($myAttribute['values']), 1, 1));
					$markerArray['###SELECT_ATTRIBUTES_VALUE###'] = $value['value'];
					$markerArray['###SELECT_ATTRIBUTES_UNIT###'] = $myAttribute['unit'];

					$attCode .= $this->cObj->substituteMarkerArray($templateAttr, $markerArray);
				}
			}
		}

		/**
		 * Basket item
		 *
		 * @var Tx_Commerce_Domain_Model_BasketItem $basketItem
		 */
		$basketItem = $this->basket->getBasketItem($article->getUid());

		$tmpArray = $this->generateMarkerArray(
			$article->returnAssocArray(),
			$this->conf['articleTS.'],
			'article_',
			'tx_commerce_articles'
		);
		$markerArray = array();
		foreach ($tmpArray as $key => $value) {
			if (strpos($key, '#') === FALSE) {
				$markerArray['###' . $key . '###'] = $value;
			}
		}
		unset($tmpArray);

		$markerArray['###ARTICLE_SELECT_ATTRIBUTES###'] = $attCode;
		$markerArray['###ARTICLE_UID###'] = $article->getUid();
		$markerArray['###STARTFRM###'] = '<form name="basket_' . $article->getUid() . '" action="' .
			$this->pi_getPageLink($this->conf['basketPid']) . '" method="post">';
		$markerArray['###HIDDENFIELDS###'] = '<input type="hidden" name="' . $this->prefixId .
			'[catUid]" value="' . (int) $this->piVars['catUid'] . '" />';
		$markerArray['###HIDDENFIELDS###'] .= '<input type="hidden" name="' . $this->prefixId .
			'[artAddUid][' . $article->getUid() . '][price_id]" value="' . $basketItem->getPriceUid() . '" />';
		$markerArray['###ARTICLE_HIDDENFIELDS###'] = '<input type="hidden" name="' . $this->prefixId .
			'[catUid]" value="' . (int) $this->piVars['catUid'] . '" />';
		$markerArray['###ARTICLE_HIDDENFIELDS###'] .= '<input type="hidden" name="' . $this->prefixId .
			'[artAddUid][' . $article->getUid() . '][price_id]" value="' . $basketItem->getPriceUid() . '" />';
		$markerArray['###QTY_INPUT_VALUE###'] = $basketItem->getQuantity();
		$markerArray['###QTY_INPUT_NAME###'] = $this->prefixId . '[artAddUid][' . $article->getUid() . '][count]';
		$markerArray['###BASKET_ITEM_PRICENET###'] = Tx_Commerce_ViewHelpers_Money::format($basketItem->getPriceNet(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICEGROSS###'] = Tx_Commerce_ViewHelpers_Money::format($basketItem->getPriceGross(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICENETNOSCALE###'] = Tx_Commerce_ViewHelpers_Money::format($basketItem->getNoScalePriceNet(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICEGROSSNOSCALE###'] = Tx_Commerce_ViewHelpers_Money::format($basketItem->getNoScalePriceGross(), $this->currency);
		$markerArray['###BASKET_ITEM_COUNT###'] = $basketItem->getQuantity();
		$markerArray['###BASKET_ITEM_PRICESUM_NET###'] = Tx_Commerce_ViewHelpers_Money::format($basketItem->getItemSumNet(), $this->currency);
		$markerArray['###BASKET_ITEM_PRICESUM_GROSS###'] = Tx_Commerce_ViewHelpers_Money::format($basketItem->getItemSumGross(), $this->currency);

		// Link to delete this article in basket
		if (is_array($this->conf['deleteItem.'])) {
			$typoLinkConf = $this->conf['deleteItem.'];
		} else {
			$typoLinkConf = array();
		}
		$typoLinkConf['parameter'] = $this->conf['basketPid'];
		$typoLinkConf['useCacheHash'] = 1;
		$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[catUid]=' . (int) $this->piVars['catUid'];
		$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId .
			'[artAddUid][' . $article->getUid() . '][price_id]=' . $basketItem->getPriceUid();
		$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId .
			'[artAddUid][' . $article->getUid() . '][count]=0';
		// DELIOTMFROMBASKETLINK is deprecated
		$markerArray['###DELETEFROMBASKETLINK###'] = $this->cObj->typoLink($this->pi_getLL('lang_basket_delete_item'), $typoLinkConf);
		$markerArray['###DELIOTMFROMBASKETLINK###'] = $markerArray['###DELETEFROMBASKETLINK###'];

		$templateMarker = '###PRODUCT_BASKET_FORM_SMALL###';
		$template = $this->cObj->getSubpart($this->getTemplateCode(), $templateMarker);

		$markerArray = array_merge($markerArray, $this->languageMarker);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeArticleView'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'makeArticleView\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'makeArticleView\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeArticleView'] as $classRef) {
				$hookObj = GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'additionalMarker')) {
					$markerArray = $hookObj->additionalMarker($markerArray, $this, $article, $product, $this->basket->getBasketItem($article->getUid()));
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['makeArticleView'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['makeArticleView'] as $classRef) {
				$hookObj = GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'additionalMarker')) {
					$markerArray = $hookObj->additionalMarker($markerArray, $this, $article, $product, $this->basket->getBasketItem($article->getUid()));
				}
			}
		}

		$content = $this->cObj->substituteMarkerArray($template, $markerArray);

		return $content;
	}

	/**
	 * Renders the product list for the basket
	 *
	 * @return string HTML Content
	 */
	protected function makeProductList() {
		$content = '';

		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeProductList'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'makeProductList\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'makeProductList\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['makeProductList'] as $classRef) {
				$hookObjectsArr[] = &GeneralUtility::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['makeProductList'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['makeProductList'] as $classRef) {
				$hookObjectsArr[] = &GeneralUtility::getUserObj($classRef);
			}
		}

		$hookObject = NULL;
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['alternativePrefixId']) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/pi2/class.tx_commerce_pi2.php\'][\'alternativePrefixId\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Controller/BasketController.php\'][\'alternativePrefixId\']
			');
			$hookObject = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi2/class.tx_commerce_pi2.php']['alternativePrefixId']);
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['alternativePrefixId']) {
			$hookObject = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Controller/BasketController.php']['alternativePrefixId']);
		}
		if (method_exists($hookObject, 'SingeDisplayPrefixId')) {
			$altPrefixSingle = $hookObject->SingeDisplayPrefixId();
		} else {
			$altPrefixSingle = $this->prefixId;
		}

		$list = array();
		$articleTypes = GeneralUtility::trimExplode(',', $this->conf['regularArticleTypes'], TRUE);
		foreach ($articleTypes as $articleType) {
			$list = array_merge($list, $this->basket->getArticlesByArticleTypeUidAsUidlist($articleType));
		}

		// ###########    product list    ######################
		$templateMarker[] = '###' . strtoupper($this->conf['templateMarker.']['items_listview']) . '###';
		$templateMarker[] = '###' . strtoupper($this->conf['templateMarker.']['items_listview2']) . '###';

		$changerowcount = 0;
		foreach ($list as $basketItemId) {
			// Fill marker arrays with product/article values
			/**
			 * Basket item
			 *
			 * @var $basketItem Tx_Commerce_Domain_Model_BasketItem
			 */
			$basketItem = $this->basket->getBasketItem($basketItemId);

			// Check stock
			$stockOk = TRUE;
			if ($this->conf['checkStock'] == 1) {
				if (!$basketItem->article->hasStock($basketItem->getQuantity())) {
					$stockOk = FALSE;
				}
			}

			// Check accessible
			$access = ($basketItem->getProduct()->isAccessible() && $basketItem->getArticle()->isAccessible());

			// Only if Stock is ok and Access is ok (could have been changed since
			// the article was put into the basket
			if ($stockOk && $access) {
				$safePrefix = $this->prefixId;

				$typoLinkConf = array();
				$typoLinkConf['parameter'] = $this->conf['listPid'];
				$typoLinkConf['useCacheHash'] = 1;
				$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[catUid]=' .
					$basketItem->getProduct()->getMasterparentCategory();
				$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[showUid]=' .
					$basketItem->getProduct()->getUid();

				if ($this->basketHashValue) {
					$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[basketHashValue]=' . $this->basketHashValue;
				}

				// @todo change link building to pure TypoScript, cObj->data usage required
				$lokalTsProduct = $this->addTypoLinkToTypoScript($this->conf['fields.']['products.'], $typoLinkConf);
				$lokalTsArticle = $this->addTypoLinkToTypoScript($this->conf['fields.']['articles.'], $typoLinkConf);

				$this->prefixId = $altPrefixSingle;

				$wrapMarkerArray['###PRODUCT_LINK_DETAIL###'] =
					explode(
						'|',
						$this->pi_list_linkSingle(
							'|', $basketItem->getProduct()->getUid(), 1,
							array('catUid' => (int) $basketItem->getProduct()->getMasterparentCategory()), FALSE, $this->conf['listPid']
						)
					);

				$this->prefixId = $safePrefix;

				$productMarkerArray = $this->generateMarkerArray(
					$basketItem->getProductAssocArray(''),
					$lokalTsProduct,
					'product_',
					'tx_commerce_products'
				);
				$articleMarkerArray = $this->generateMarkerArray(
					$basketItem->getArticleAssocArray(''),
					$lokalTsArticle,
					'article_',
					'tx_commerce_articles'
				);
				$this->selectAttributes = $basketItem->getProduct()->getAttributes(array(ATTRIB_SELECTOR));
				$productMarkerArray['PRODUCT_BASKET_FOR_LISTVIEW'] = $this->makeArticleView(
					$basketItem->getArticle(),
					$basketItem->getProduct()
				);
				$templateSelector = $changerowcount % 2;

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'changeProductTemplate')) {
						$templateMarker = $hookObj->changeProductTemplate($templateMarker, $basketItem, $this);
					}
				}

				$template = $this->cObj->getSubpart($this->getTemplateCode(), $templateMarker[$templateSelector]);
				$changerowcount++;

				$template = $this->cObj->substituteSubpart($template, '###PRODUCT_BASKET_FORM_SMALL###', '');
				$markerArray = array_merge($productMarkerArray, $articleMarkerArray);

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'additionalMarkerProductList')) {
						$markerArray = $hookObj->additionalMarkerProductList($markerArray, $basketItem, $this);
					}
				}

				$tempContent = $this->cObj->substituteMarkerArray($template, $markerArray, '###|###', 1);
				$content .= $this->substituteMarkerArrayNoCached($tempContent, $this->languageMarker, array(), $wrapMarkerArray);
			} else {
				// Remove article from basket
				$this->basket->deleteArticle($basketItem->getArticle()->getUid());
				$this->basket->storeData();
			}
		}

		return $content;
	}


	/**
	 * Get basket
	 *
	 * @return Tx_Commerce_Domain_Model_Basket
	 */
	public function getBasket() {
		return $this->basket;
	}

	/**
	 * Getter
	 *
	 * @return array
	 */
	public function getMarkerArray() {
		return $this->markerArray;
	}

	/**
	 * Setter
	 *
	 * @param array $markerArray Marker array
	 *
	 * @return void
	 */
	public function setMarkerArray(array $markerArray) {
		$this->markerArray = $markerArray;
	}

	/**
	 * Getter
	 *
	 * @return string
	 */
	public function getTemplateCode() {
		return $this->templateCode;
	}

	/**
	 * Setter
	 *
	 * @param string $templateCode Template code
	 *
	 * @return void
	 */
	public function setTemplateCode($templateCode) {
		$this->templateCode = $templateCode;
	}

	/**
	 * Getter
	 *
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Setter
	 *
	 * @param string $content Content
	 *
	 * @return void
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Getter
	 *
	 * @return int
	 */
	public function getPriceLimitForBasket() {
		return $this->priceLimitForBasket;
	}

	/**
	 * Setter
	 *
	 * @param int $priceLimitForBasket Limit for basket
	 *
	 * @return void
	 */
	public function setPriceLimitForBasket($priceLimitForBasket) {
		$this->priceLimitForBasket = $priceLimitForBasket;
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

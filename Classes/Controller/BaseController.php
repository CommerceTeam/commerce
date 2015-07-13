<?php
namespace CommerceTeam\Commerce\Controller;
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

use CommerceTeam\Commerce\Domain\Model\AbstractEntity;
use CommerceTeam\Commerce\Domain\Model\Basket;
use CommerceTeam\Commerce\Domain\Model\BasketItem;
use CommerceTeam\Commerce\Domain\Model\Category;
use CommerceTeam\Commerce\Domain\Model\Product;
use CommerceTeam\Commerce\Factory\HookFactory;
use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Controller\BaseController
 *
 * @author 2005-2013 Volker Graubaum <vg@e-netconsulting.de>
 */
abstract class BaseController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
	/**
	 * The extension key.
	 *
	 * @var string
	 */
	public $extKey = COMMERCE_EXTKEY;

	/**
	 * Path to this script relative to the extension dir.
	 *
	 * @var string
	 */
	public $scriptRelPath = 'Resources/Private/Language/locallang.xml';

	/**
	 * Configuration
	 *
	 * @param array
	 */
	public $conf = array();

	/**
	 * If set to TRUE some debug message will be printed.
	 *
	 * @var bool
	 */
	public $debug = FALSE;

	/**
	 * Image folder
	 *
	 * @todo migrate to fal handling
	 * @var string
	 */
	public $imgFolder = 'uploads/tx_commerce/';

	/**
	 * Flag if currency should be put out
	 *
	 * @var bool
	 */
	public $showCurrency = TRUE;

	/**
	 * Currency if no currency is set otherwise
	 *
	 * @var string
	 */
	public $currency = 'EUR';

	/**
	 * Holds the merged Array Langmarkers from locallang
	 *
	 * @var array
	 */
	public $languageMarker = array();

	/**
	 * Holds the basketItemHash for making the whole shop cachable
	 *
	 * @var string
	 */
	public $basketHashValue = FALSE;

	/**
	 * Holds the workspace, if one is used
	 *
	 * @var int
	 */
	public $workspace = FALSE;

	/**
	 * Flag if rootline information in url
	 *
	 * @var int [0-1]
	 */
	protected $useRootlineInformationToUrl = 0;

	/**
	 * A handle to do something
	 * Do not make protected to be able to handle different behaviour in a hook
	 *
	 * @var string
	 */
	public $handle = '';

	/**
	 * Category UID for rendering
	 *
	 * @var int
	 */
	public $cat;

	/**
	 * Category
	 *
	 * @var Category
	 */
	public $category;

	/**
	 * Category products
	 *
	 * @var array
	 */
	public $category_products;

	/**
	 * If rendering a category list this is the current
	 *
	 * @var Category
	 */
	public $currentCategory;

	/**
	 * Tpo products
	 *
	 * @var array
	 */
	public $top_products;

	/**
	 * Product
	 *
	 * @var Product
	 */
	public $product;

	/**
	 * Template code
	 *
	 * @var string
	 */
	public $templateCode;

	/**
	 * Template path
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Page id
	 *
	 * @var int
	 */
	public $pid;

	/**
	 * Product attributes
	 *
	 * @var array
	 */
	public $product_attributes = array();

	/**
	 * Can attributes
	 *
	 * @var array
	 */
	public $can_attributes = array();

	/**
	 * Shall products
	 *
	 * @var array
	 */
	public $shall_attributes = array();

	/**
	 * Select attributes
	 *
	 * @var array
	 */
	public $selectAttributes = array();

	/**
	 * Path depth
	 *
	 * @var int
	 */
	public $mDepth;

	/**
	 * TCA
	 *
	 * @var array
	 */
	public $TCA;

	/**
	 * Table
	 *
	 * @var string
	 */
	public $table;

	/**
	 * URL argument separator
	 *
	 * @var string
	 */
	protected $argSeparator = '&';

	/**
	 * Initializing
	 *
	 * @param array $conf Configuration
	 *
	 * @return void
	 */
	protected function init(array $conf = array()) {
		if ($this->getFrontendController()->beUserLogin) {
			$this->workspace = $this->getBackendUser()->workspace;
		}

		// enable typoscript objects for overridePid
		if (!empty($conf['overridePid.'])) {
			$conf['overridePid'] = $this->cObj->cObjGetSingle($conf['overridePid'], $conf['overridePid.']);
			unset($conf['overridePid.']);
		}

		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();

		\CommerceTeam\Commerce\Utility\GeneralUtility::initializeFeUserBasket();

		$this->pid = $this->getFrontendController()->id;
		$this->basketHashValue = $this->getBasket()->getBasketHashValue();
		$this->piVars['basketHashValue'] = $this->basketHashValue;
		$this->argSeparator = ini_get('arg_separator.output');
		$this->addAdditionalLocallang();

		$this->generateLanguageMarker();
		if (empty($this->conf['templateFile'])) {
			$this->error('init', __LINE__, 'Template File not defined in TS: ');
		}

		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
		if ($this->conf['useRootlineInformationToUrl']) {
			$this->useRootlineInformationToUrl = $this->conf['useRootlineInformationToUrl'];
		}
	}

	/**
	 * Returns the payment object for a specific payment type
	 * (creditcard, invoice, ...)
	 *
	 * @param string $paymentType Payment type to get
	 *
	 * @return \CommerceTeam\Commerce\Payment\PaymentAbstract Current payment object
	 * @throws \Exception If payment object can not be created or is invalid
	 */
	protected function getPaymentObject($paymentType = '') {
		if (!is_string($paymentType)) {
			throw new \Exception(
				'Expected variable of type string for ' . $paymentType . ' but a ' . getType($paymentType) . ' was given.',
				1305675802
			);
		}
		if (strlen($paymentType) < 1) {
			throw new \Exception('Empty payment type given.', 1307015821);
		}

		$config = SettingsFactory::getInstance()->getConfiguration('SYSPRODUCTS.PAYMENT.types.' . $paymentType);

		if (!is_array($config)) {
			throw new \Exception('No configuration found for payment type ' . $paymentType, 1305675991);
		}
		if (!isset($config['class'])) {
			throw new \Exception('No target implementation found for payment type ' . $paymentType, 1305676132);
		}

		$paymentObject = GeneralUtility::makeInstance($config['class'], $this);
		if (!$paymentObject instanceof \CommerceTeam\Commerce\Payment\PaymentInterface) {
			throw new \Exception($config['class'] . ' must implement \\CommerceTeam\\Commerce\\Payment\\PaymentInterface');
		}

		return $paymentObject;
	}

	/**
	 * Getting additional locallang-files through an Hook
	 *
	 * @return void
	 */
	public function addAdditionalLocallang() {
		$hooks = HookFactory::getHooks('Controller/BaseController', 'addAdditionalLocallang');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'loadAdditionalLocallang')) {
				$hook->loadAdditionalLocallang($this);
			}
		}
	}

	/**
	 * Gets all "lang_ and label_" Marker for substition with substituteMarkerArray
	 *
	 * @return void
	 */
	public function generateLanguageMarker() {
		if (
			is_array($this->LOCAL_LANG[$this->getFrontendController()->tmpl->setup['config.']['language']])
			&& is_array($this->LOCAL_LANG['default'])
		) {
			$markerArr = GeneralUtility::array_merge(
				$this->LOCAL_LANG['default'], $this->LOCAL_LANG[$this->getFrontendController()->tmpl->setup['config.']['language']]
			);
		} elseif (is_array($this->LOCAL_LANG['default'])) {
			$markerArr = $this->LOCAL_LANG['default'];
		} else {
			$markerArr = $this->LOCAL_LANG[$this->getFrontendController()->tmpl->setup['config.']['language']];
		}

		foreach (array_keys($markerArr) as $languageKey) {
			if (stristr($languageKey, 'lang_') OR stristr($languageKey, 'label_')) {
				$this->languageMarker['###' . strtoupper($languageKey) . '###'] = $this->pi_getLL($languageKey);
			}
		}
	}

	/**
	 * Renders Product Attribute List from given product, with possibility to
	 * define a number of templates for interations.
	 * when defining 2 templates you have an odd / even layout
	 *
	 * @param Product $prodObj Product Object
	 * @param array $subpartNameArray Subpart names
	 * @param bool|array $typoScript Typoscript
	 *
	 * @return string HTML-Output rendert
	 */
	public function renderProductAttributeList(Product $prodObj, array $subpartNameArray = array(),
			$typoScript = FALSE) {
		if ($typoScript == FALSE) {
			$typoScript = $this->conf['singleView.']['attributes.'];
		}

		$templateArray = array();
		foreach ($subpartNameArray as $oneSubpartName) {
			$templateArray[] = $this->cObj->getSubpart($this->templateCode, $oneSubpartName);
		}

		if (!$this->product_attributes) {
			$this->product_attributes = $prodObj->getAttributes(array(ATTRIB_PRODUCT));
		}

		// not needed write now, lets see later
		$showHiddenValues = $this->conf['showHiddenValues'] == 1;

		$matrix = $prodObj->getAttributeMatrix(
			FALSE, $this->product_attributes, $showHiddenValues, 'tx_commerce_products_attributes_mm', FALSE,
			'tx_commerce_products'
		);

		$i = 0;
		$productAttributes = '';
		if (is_array($this->product_attributes)) {
			foreach ($this->product_attributes as $myAttributeUid) {
				if (!$matrix[$myAttributeUid]['values'][0] && $this->conf['hideEmptyProdAttr']) {
					continue;
				}
				if ($i == count($templateArray)) {
					$i = 0;
				}

				$datas = array(
					'title' => $matrix[$myAttributeUid]['title'],
					'value' => $this->formatAttributeValue($matrix, $myAttributeUid),
					'unit' => $matrix[$myAttributeUid]['unit'],
					'icon' => $matrix[$myAttributeUid]['icon'],
					'internal_title' => $matrix[$myAttributeUid]['internal_title'],
				);

				$markerArray = $this->generateMarkerArray(
					$datas, $typoScript, $prefix = 'PRODUCT_ATTRIBUTES_', 'tx_commerce_attributes'
				);
				$marker['PRODUCT_ATTRIBUTES_TITLE'] = $matrix[$myAttributeUid]['title'];
				$productAttribute = $this->cObj->substituteMarkerArray($templateArray[$i], $markerArray, '###|###', 1);
				$productAttributes .= $this->cObj->substituteMarkerArray($productAttribute, $marker, '###|###', 1);
				$i++;
			}

			return $this->cObj->stdWrap($productAttributes, $typoScript);
		}

		return '';
	}

	/**
	 * Renders HTML output with list of attribute from a given product,
	 * reduced for some articles
	 * if article ids are givens
	 * with possibility to
	 * define a number of templates for interations.
	 * when defining 2 templates you have an odd / even layout
	 *
	 * @param Product $product Current product
	 * @param array $articleId ArticleIds for filtering attributss
	 * @param array $subpartNameArray Suppart Names
	 *
	 * @return string Stringoutput for attributes
	 */
	public function renderArticleAttributeList(Product &$product, array $articleId = array(),
			array $subpartNameArray = array()) {
		$templateArray = array();
		foreach ($subpartNameArray as $oneSubpartName) {
			$tmpCode = $this->cObj->getSubpart($this->templateCode, $oneSubpartName);
			if (strlen($tmpCode) > 0) {
				$templateArray[] = $tmpCode;
			}
		}

		if ($this->conf['showHiddenValues'] == 1) {
			$showHiddenValues = TRUE;
		} else {
			$showHiddenValues = FALSE;
		}

		$this->can_attributes = $product->getAttributes(array(ATTRIB_CAN));
		$this->shall_attributes = $product->getAttributes(array(ATTRIB_SHAL));

		$matrix = $product->getAttributeMatrix($articleId, $this->shall_attributes, $showHiddenValues);
		$articleShallAttributesString = '';
		$i = 0;
		if (is_array($this->shall_attributes)) {
			foreach ($this->shall_attributes as $myAttributeUid) {
				if (!$matrix[$myAttributeUid]['values'][0] && $this->conf['hideEmptyShalAttr'] || !$matrix[$myAttributeUid]) {
					continue;
				}
				if ($i == count($templateArray)) {
					$i = 0;
				}

				$datas = array(
					'title' => $matrix[$myAttributeUid]['title'],
					'value' => $this->formatAttributeValue($matrix, $myAttributeUid),
					'unit' => $matrix[$myAttributeUid]['unit'],
					'icon' => $matrix[$myAttributeUid]['icon'],
					'internal_title' => $matrix[$myAttributeUid]['internal_title'],
				);
				$markerArray = $this->generateMarkerArray(
					$datas, $this->conf['singleView.']['attributes.'], $prefix = 'ARTICLE_ATTRIBUTES_'
				);
				$marker['ARTICLE_ATTRIBUTES_TITLE'] = $matrix[$myAttributeUid]['title'];

				$articleShallAttributesString .= $this->cObj->substituteMarkerArray(
					$templateArray[$i], $markerArray, '###|###', 1
				);
				$i++;
			}
		}

		$articleShallAttributesString = $this->cObj->stdWrap(
			$articleShallAttributesString, $this->conf['articleShalAttributsWrap.']
		);

		$matrix = $product->getAttributeMatrix($articleId, $this->can_attributes, $showHiddenValues);
		$articleCanAttributesString = '';
		$i = 0;
		if (is_array($this->can_attributes)) {
			foreach ($this->can_attributes as $myAttributeUid) {
				if (!$matrix[$myAttributeUid]['values'][0] && $this->conf['hideEmptyCanAttr'] || !$matrix[$myAttributeUid]) {
					continue;
				}
				if ($i == count($templateArray)) {
					$i = 0;
				}

				$datas = array(
					'title' => $matrix[$myAttributeUid]['title'],
					'value' => $this->formatAttributeValue($matrix, $myAttributeUid),
					'unit' => $matrix[$myAttributeUid]['unit'],
					'icon' => $matrix[$myAttributeUid]['icon'],
					'internal_title' => $matrix[$myAttributeUid]['internal_title'],
				);
				$markerArray = $this->generateMarkerArray(
					$datas, $this->conf['singleView.']['attributes.'], $prefix = 'ARTICLE_ATTRIBUTES_'
				);
				$marker['ARTICLE_ATTRIBUTES_TITLE'] = $matrix[$myAttributeUid]['title'];

				$articleCanAttributesString .= $this->cObj->substituteMarkerArray($templateArray[$i], $markerArray, '###|###', 1);

				$i++;
			}
		}
		$articleCanAttributesString = $this->cObj->stdWrap($articleCanAttributesString, $this->conf['articleCanAttributsWrap.']);

		$articleAttributesString = $this->cObj->stdWrap(
			$articleShallAttributesString . $articleCanAttributesString, $this->conf['articleAttributsWrap.']
		);
		$articleAttributesString = $this->cObj->stdWrap(
			$articleAttributesString, $this->conf['singleView.']['attributes.']['stdWrap.']
		);

		return $articleAttributesString . ' ';
	}

	/**
	 * Makes the list view for the current categorys
	 *
	 * @return string the content for the list view
	 */
	public function makeListView() {
		/**
		 * Category LIST
		 */
		$categoryOutput = '';

		$this->template = $this->templateCode;

		if ($this->category->hasSubcategories()) {
			/**
			 * Category
			 *
			 * @var $oneCategory Category
			 */
			foreach ($this->category->getChildCategories() as $oneCategory) {
				$oneCategory->loadData();
				$this->currentCategory = & $oneCategory;

				if ($this->conf['hideEmptyCategories'] == 1) {
					// First check TS setting (ceap)
					// afterwards do the recursive call (expensive)
					if (
						!$oneCategory->hasProductsInSubCategories()
						|| ($this->conf['useStockHandling'] && !$oneCategory->hasProductsWithStock())
					) {
						// This category is empty, so
						// skip this iteration and do next
						continue;
					}
				}

				$linkArray['catUid'] = $oneCategory->getUid();
				if ($this->useRootlineInformationToUrl == 1) {
					$linkArray['path'] = $this->getPathCat($oneCategory);
					$linkArray['mDepth'] = $this->mDepth;
				} else {
					$linkArray['mDepth'] = '';
					$linkArray['path'] = '';
				}

				if ($this->basketHashValue) {
					$linkArray['basketHashValue'] = $this->basketHashValue;
				}

				/**
				 *  Build TS for Linking the Catergory Images
				 */
				$localTs = $this->conf['categoryListView.']['categories.'];

				if ($this->conf['listPid']) {
					$typoLinkConf['parameter'] = $this->conf['listPid'];
				} elseif ($this->conf['overridePid']) {
					$typoLinkConf['parameter'] = $this->conf['overridePid'];
				} else {
					$typoLinkConf['parameter'] = $this->pid;
				}
				$typoLinkConf['useCacheHash'] = 1;
				$typoLinkConf['additionalParams'] = $this->argSeparator . $this->prefixId . '[catUid]=' . $oneCategory->getUid();

				$productArray = $oneCategory->getProducts();
				if (1 == $this->conf['displayProductIfOneProduct'] && 1 == count($productArray)) {
					$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[showUid]=' . $productArray[0];
				}

				if ($this->useRootlineInformationToUrl == 1) {
					$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[path]=' . $this->getPathCat($oneCategory);
					$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[mDepth]=' . $this->mDepth;
				}

				if ($this->basketHashValue) {
					$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[basketHashValue]=' . $this->basketHashValue;
				}

				$localTs['fields.']['images.']['stdWrap.']['typolink.'] = $typoLinkConf;
				$localTs['fields.']['teaserimages.']['stdWrap.']['typolink.'] = $typoLinkConf;

				$localTs = $this->addTypoLinkToTypoScript($localTs, $typoLinkConf);

				$tmpCategory = $this->renderCategory($oneCategory, '###CATEGORY_LIST_ITEM###', $localTs, 'ITEM');

				/**
				 * Build the link
				 *
				 * @depricated
				 * Please use TYPOLINK instead
				 */
				$linkContent = $this->cObj->getSubpart($tmpCategory, '###CATEGORY_ITEM_DETAILLINK###');
				if ($linkContent) {
					$link = $this->pi_linkTP_keepPIvars($linkContent, $linkArray, TRUE, 0, $this->conf['overridePid']);
				} else {
					$link = '';
				}

				$tmpCategory = $this->cObj->substituteSubpart($tmpCategory, '###CATEGORY_ITEM_DETAILLINK###', $link);

				if (
					$this->conf['groupProductsByCategory']
					&& !$this->conf['hideProductsInList']
					&& !$this->conf['hideProductsInSubcategories']
				) {
					$categoryProducts = $oneCategory->getProducts();
					if ($this->conf['useStockHandling'] == 1) {
						$categoryProducts = \CommerceTeam\Commerce\Utility\GeneralUtility::removeNoStockProducts(
							$categoryProducts, $this->conf['products.']['showWithNoStock']
						);
					}
					$categoryProducts = array_slice($categoryProducts, 0, $this->conf['numberProductsInSubCategory']);
					$productList = $this->renderProductsForList(
						$categoryProducts, $this->conf['templateMarker.']['categoryProductList.'],
						$this->conf['templateMarker.']['categoryProductListIterations']
					);

					/**
					 * Insert the Productlist
					 */
					$tmpCategory = $this->cObj->substituteMarker($tmpCategory, '###CATEGORY_ITEM_PRODUCTLIST###', $productList);
				} else {
					$tmpCategory = $this->cObj->substituteMarker($tmpCategory, '###CATEGORY_ITEM_PRODUCTLIST###', '');
				}

				$categoryOutput .= $tmpCategory;
			}
		}

		$categoryListSubpart = $this->cObj->getSubpart($this->template, '###CATEGORY_LIST###');
		$markerArray['CATEGORY_SUB_LIST'] = $this->cObj->substituteSubpart(
			$categoryListSubpart, '###CATEGORY_LIST_ITEM###', $categoryOutput
		);
		$startPoint = ($this->piVars['pointer']) ? $this->internal['results_at_a_time'] * $this->piVars['pointer'] : 0;

		// Display TopProducts???
		// for this, make a few basicSettings for pageBrowser
		$internalStartPoint = $startPoint;
		$internalResults = $this->internal['results_at_a_time'];

		// set Empty default
		$markerArray['SUBPART_CATEGORY_ITEMS_LISTVIEW_TOP'] = '';

		if (!$this->conf['groupProductsByCategory'] && $this->conf['displayTopProducts'] && $this->conf['numberOfTopproducts']) {
			$this->top_products = array_slice($this->category_products, $startPoint, $this->conf['numberOfTopproducts']);
			$internalStartPoint = $startPoint + $this->conf['numberOfTopproducts'];
			$internalResults = $this->internal['results_at_a_time'] - $this->conf['numberOfTopproducts'];

			$markerArray['SUBPART_CATEGORY_ITEMS_LISTVIEW_TOP'] = $this->renderProductsForList(
				$this->top_products, $this->conf['templateMarker.']['categoryProductListTop.'],
				$this->conf['templateMarker.']['categoryProductListTopIterations'], $this->conf['topProductTSMarker']
			);
		}

		// ###################### product list ######################
		if (is_array($this->category_products)) {
			$this->category_products = array_slice($this->category_products, $internalStartPoint, $internalResults);
		}

		if (!$this->conf['hideProductsInList']) {
			// Write the current page to The session to have a back to last product link
			$this->getFrontendController()->fe_user->setKey('ses', 'tx_commerce_lastproducturl', $this->pi_linkTP_keepPIvars_url());
			$markerArray['SUBPART_CATEGORY_ITEMS_LISTVIEW'] = $this->renderProductsForList(
				$this->category_products,
				(array) $this->conf['templateMarker.']['categoryProductList.'],
				$this->conf['templateMarker.']['categoryProductListIterations']
			);
		}

		$templateMarker = '###' . strtoupper($this->conf['templateMarker.']['categoryView']) . '###';

		$markerArrayCat = $this->generateMarkerArray(
			$this->category->returnAssocArray(),
			(array) $this->conf['singleView.']['categories.'],
			'category_',
			'tx_commerce_categories'
		);
		$markerArray = array_merge($markerArrayCat, $markerArray);

		if (
			$this->conf['showPageBrowser'] == 1
			&& count($this->category_products) > $this->conf['maxRecords']
			&& is_array($this->conf['pageBrowser.']['wraps.'])
		) {
			$this->internal['pagefloat'] = (int) $this->piVars['pointer'];
			$this->internal['dontLinkActivePage'] = $this->conf['pageBrowser.']['dontLinkActivePage'];
			$this->internal['showFirstLast'] = $this->conf['pageBrowser.']['showFirstLast'];
			$this->internal['showRange'] = $this->conf['pageBrowser.']['showRange'];
			$hscText = !($this->conf['pageBrowser.']['hscText'] != 1);

			$markerArray['CATEGORY_BROWSEBOX'] = $this->pi_list_browseresults(
				$this->conf['pageBrowser.']['showItemCount'], $this->conf['pageBrowser.']['tableParams.'],
				$this->conf['pageBrowser.']['wraps.'], 'pointer', $hscText
			);
		} else {
			$markerArray['CATEGORY_BROWSEBOX'] = '';
		}

		$hooks = HookFactory::getHooks('Controller/BaseController', 'makeListView');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'additionalMarker')) {
				$markerArray = $hook->additionalMarker($markerArray, $this);
			}
		}

		$markerArray = $this->addFormMarker($markerArray);

		$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);
		$content = $this->cObj->substituteMarkerArray($template, $markerArray, '###|###', 1);
		$content = $this->cObj->substituteMarkerArray($content, $this->languageMarker);

		return $content;
	}

	/**
	 * Get category path
	 *
	 * @param Category $category Category
	 *
	 * @return string
	 */
	public function getPathCat(Category $category) {
		$rootline = $category->getParentCategoriesUidlist();
		array_pop($rootline);
		$active = array_reverse($rootline);
		$this->mDepth = 0;
		$path = '';
		foreach ($active as $actCat) {
			if ($path === '') {
				$path = $actCat;
			} else {
				$path .= ',' . $actCat;
				$this->mDepth++;
			}
		}

		return $path;
	}

	/**
	 * Renders the Article Marker and all additional informations needed for
	 * a basket form. This Method will not replace the Subpart, you have to
	 * replace your subpart in your template by you own
	 *
	 * @param \CommerceTeam\Commerce\Domain\Model\Article $article Article the
	 * 	marker based on
	 * @param bool $priceid If set true (default) the price-id will be rendered
	 *        into the hiddenfields, otherwhise not
	 *
	 * @return array $markerArray markers needed for the article and the basket form
	 */
	public function getArticleMarker(\CommerceTeam\Commerce\Domain\Model\Article $article, $priceid = FALSE) {
		if (
			$this->handle
			&& is_array($this->conf[$this->handle . '.'])
			&& is_array($this->conf[$this->handle . '.']['articles.'])
		) {
			$tsconf = $this->conf[$this->handle . '.']['articles.'];
		} else {
			// Set default
			$tsconf = $this->conf['singleView.']['articles.'];
		}
		$markerArray = $this->generateMarkerArray($article->returnAssocArray(), $tsconf, 'article_', 'tx_commerce_article');

		if ($article->getSupplierUid()) {
			$markerArray['ARTICLE_SUPPLIERNAME'] = $article->getSupplierName();
		} else {
			$markerArray['ARTICLE_SUPPLIERNAME'] = '';
		}

		/**
		 * STARTFRM and HIDDENFIELDS are old marker, used bevor Version 0.9.3
		 * Still existing for compatibility reasons
		 * Please use ARTICLE_HIDDENFIEDLS, ARTICLE_FORMACTION
		 * and ARTICLE_FORMNAME, ARTICLE_HIDDENCATUID
		 */
		$markerArray['STARTFRM'] = '<form name="basket_' . $article->getUid() . '" action="' .
			$this->pi_getPageLink($this->conf['basketPid']) . '" method="post">';
		$markerArray['HIDDENFIELDS'] = '<input type="hidden" name="' . $this->prefixId . '[catUid]" value="' .
			$this->category->getUid() . '" />';
		$markerArray['ARTICLE_FORMACTION'] = $this->pi_getPageLink($this->conf['basketPid']);
		$markerArray['ARTICLE_FORMNAME'] = 'basket_' . $article->getUid();
		$markerArray['ARTICLE_HIDDENCATUID'] = '<input type="hidden" name="' . $this->prefixId . '[catUid]" value="' .
			$this->category->getUid() . '" />';
		$markerArray['ARTICLE_HIDDENFIELDS'] = '';

		/**
		 * Build Link to put one of this article in basket
		 */
		if ($tsconf['addToBasketLink.']) {
			$typoLinkConf = $tsconf['addToBasketLink.'];
		}

		$typoLinkConf['parameter'] = $this->conf['basketPid'];
		$typoLinkConf['useCacheHash'] = 1;
		$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[catUid]=' . $this->category->getUid();

		if ($priceid == TRUE) {
			$markerArray['ARTICLE_HIDDENFIELDS'] .= '<input type="hidden" name="' . $this->prefixId . '[artAddUid][' .
				$article->getUid() . '][price_id]" value="' . $article->getPriceUid() . '" />';
			$markerArray['HIDDENFIELDS'] .= '<input type="hidden" name="' . $this->prefixId . '[artAddUid][' .
				$article->getUid() . '][price_id]" value="' . $article->getPriceUid() . '" />';
			$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[artAddUid][' .
				$article->getUid() . '][price_id]=' . $article->getPriceUid();
		} else {
			$markerArray['HIDDENFIELDS'] .= '<input type="hidden" name="' . $this->prefixId . '[artAddUid][' .
				$article->getUid() . '][price_id]" value="" />';
			$markerArray['ARTICLE_HIDDENFIELDS'] .= '<input type="hidden" name="' . $this->prefixId . '[artAddUid][' .
				$article->getUid() . '][price_id]" value="" />';
			$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[artAddUid][' .
				$article->getUid() . '][price_id]=';
		}
		$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[artAddUid][' .
			$article->getUid() . '][count]=1';

		$markerArray['LINKTOPUTINBASKET'] = $this->cObj->typoLink($this->pi_getLL('lang_addtobasketlink'), $typoLinkConf);

		$markerArray['QTY_INPUT_VALUE'] = $this->getArticleAmount($article->getUid(), $tsconf);
		$markerArray['QTY_INPUT_NAME'] = $this->prefixId . '[artAddUid][' . $article->getUid() . '][count]';
		$markerArray['ARTICLE_NUMBER'] = $article->getOrdernumber();
		$markerArray['ARTICLE_ORDERNUMBER'] = $article->getOrdernumber();

		$markerArray['ARTICLE_PRICE_NET'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$article->getPriceNet(),
			$this->currency
		);
		$markerArray['ARTICLE_PRICE_GROSS'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$article->getPriceGross(),
			$this->currency
		);
		$markerArray['DELIVERY_PRICE_NET'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$article->getDeliveryCostNet(),
			$this->currency
		);
		$markerArray['DELIVERY_PRICE_GROSS'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$article->getDeliveryCostGross(),
			$this->currency
		);

		$hooks = HookFactory::getHooks('Controller/BaseController', 'getArticleMarker');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'additionalMarkerArticle')) {
				$markerArray = $hook->additionalMarkerArticle($markerArray, $article, $this);
			}
		}

		return $markerArray;
	}

	/* Basker and Checkout Methods */

	/**
	 * Renders on Adress in the template
	 * This Method will not replace the Subpart, you have to replace your subpart
	 * in your template by you own
	 *
	 * @param array $addressArray Address Array (as result from Select DB or Session)
	 * @param string $subpartTemplate Subpart Template subpart
	 *
	 * @return string $content string HTML-Content from the given Subpart.
	 */
	public function makeAdressView(array $addressArray, $subpartTemplate) {
		$template = $this->cObj->getSubpart($this->templateCode, $subpartTemplate);

		$content = $this->cObj->substituteMarkerArray($template, $addressArray, '###|###', 1);

		return $content;
	}

	/**
	 * Renders the given Basket to the Template
	 * This Method will not replace the Subpart, you have to replace your subpart
	 * in your template by you own
	 *
	 * @param Basket $basketObj Basket
	 * @param string $subpartMarker Subpart Template Subpart
	 * @param array|bool $articletypes Articletypes
	 * @param string $lineTemplate Line templates
	 *
	 * @return string $content HTML-Ccontent from the given Subpart
	 */
	public function makeBasketView(Basket $basketObj, $subpartMarker, array $articletypes = array(),
			$lineTemplate = '###LISTING_ARTICLE###') {
		$template = $this->cObj->getSubpart($this->templateCode, $subpartMarker);

		if (!is_array($lineTemplate)) {
			$temp = $lineTemplate;
			$lineTemplate = array();
			$lineTemplate[] = $temp;
		} else {
			/**
			 * Check if the subpart is existing, and if not, remove from array
			 */
			$tmpArray = array();
			foreach ($lineTemplate as $subpartMarker) {
				$subpartContent = $this->cObj->getSubpart($template, $subpartMarker);
				if (!empty($subpartContent)) {
					$tmpArray[] = $subpartMarker;
				}
			}
			$lineTemplate = $tmpArray;
			unset($tmpArray);
		}

		$templateElements = count($lineTemplate);
		if ($templateElements) {
			/**
			 * Get All Articles in this basket and genarte HTMl-Content per row
			 */
			$articleLines = '';
			$count = 0;

			/**
			 * Item
			 *
			 * @var $itemObj BasketItem
			 */
			foreach ($basketObj->getBasketItems() as $itemObj) {
				$part = $count % $templateElements;

				if (is_array($articletypes) && count($articletypes)) {
					if (in_array($itemObj->getArticleTypeUid(), $articletypes)) {
						$articleLines .= $this->makeLineView($itemObj, $lineTemplate[$part]);
					}
				} else {
					$articleLines .= $this->makeLineView($itemObj, $lineTemplate[$part]);
				}

				++$count;
			}

			$content = $this->cObj->substituteSubpart($template, '###LISTING_ARTICLE###', $articleLines);
			// Unset Subparts, if not used
			foreach ($lineTemplate as $subpartMarker) {
				$content = $this->cObj->substituteSubpart($content, $subpartMarker, '');
			}
		} else {
			$content = $this->cObj->substituteSubpart($template, '###LISTING_ARTICLE###', '');
		}

		$hooks = HookFactory::getHooks('Controller/BaseController', 'makeBasketView');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'postBasketView')) {
				$content = $hook->postBasketView($content, $articletypes, $lineTemplate, $template, $basketObj, $this);
			}
		}

		$content = $this->cObj->substituteSubpart(
			$content, '###LISTING_BASKET_WEB###', $this->makeBasketInformation($basketObj, '###LISTING_BASKET_WEB###')
		);

		return $content;
	}

	/**
	 * Renders from the given Basket the Sum Information to HTML-Code
	 * This Method will not replace the Subpart, you have to replace your subpart
	 * in your template by you own
	 *
	 * @param Basket $basketObj Basket
	 * @param string $subpartTemplate Subpart Template Subpart
	 *
	 * @return string $content HTML-Ccontent from the given Subpart
	 * @abstract
	 * Renders the following MARKER
	 * ###LABEL_SUM_ARTICLE_NET### ###SUM_ARTICLE_NET###
	 * ###LABEL_SUM_ARTICLE_GROSS### ###SUM_ARTICLE_GROSS###
	 * ###LABEL_SUM_SHIPPING_NET### ###SUM_SHIPPING_NET###
	 * ###LABEL_SUM_SHIPPING_GROSS### ###SUM_SHIPPING_GROSS###
	 * ###LABEL_SUM_NET###
	 * ###SUM_NET###
	 * ###LABEL_SUM_TAX###
	 * ###SUM_TAX###
	 * ###LABEL_SUM_GROSS### ###SUM_GROSS###
	 */
	public function makeBasketInformation(Basket $basketObj, $subpartTemplate) {
		$template = $this->cObj->getSubpart($this->templateCode, $subpartTemplate);
		$basketObj->recalculateSums();
		$markerArray['###SUM_NET###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketObj->getSumNet(), $this->currency, $this->showCurrency
		);
		$markerArray['###SUM_GROSS###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketObj->getSumGross(), $this->currency, $this->showCurrency
		);

		$sumArticleNet = 0;
		$sumArticleGross = 0;
		$regularArticleTypes = GeneralUtility::intExplode(',', $this->conf['regularArticleTypes']);
		foreach ($regularArticleTypes as $regularArticleType) {
			$sumArticleNet += $basketObj->getArticleTypeSumNet($regularArticleType);
			$sumArticleGross += $basketObj->getArticleTypeSumGross($regularArticleType);
		}

		$markerArray['###SUM_ARTICLE_NET###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$sumArticleNet, $this->currency, $this->showCurrency
		);
		$markerArray['###SUM_ARTICLE_GROSS###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$sumArticleGross, $this->currency, $this->showCurrency
		);
		$markerArray['###SUM_SHIPPING_NET###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketObj->getArticleTypeSumNet(DELIVERYARTICLETYPE), $this->currency, $this->showCurrency
		);
		$markerArray['###SUM_SHIPPING_GROSS###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketObj->getArticleTypeSumGross(DELIVERYARTICLETYPE), $this->currency, $this->showCurrency
		);
		$markerArray['###SHIPPING_TITLE###'] = $basketObj->getFirstArticleTypeTitle(DELIVERYARTICLETYPE);
		$markerArray['###SUM_PAYMENT_NET###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketObj->getArticleTypeSumNet(PAYMENTARTICLETYPE), $this->currency, $this->showCurrency
		);
		$markerArray['###SUM_PAYMENT_GROSS###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketObj->getArticleTypeSumGross(PAYMENTARTICLETYPE), $this->currency, $this->showCurrency
		);
		$markerArray['###PAYMENT_TITLE###'] = $basketObj->getFirstArticleTypeTitle(PAYMENTARTICLETYPE);
		$markerArray['###PAYMENT_DESCRIPTION###'] = $basketObj->getFirstArticleTypeDescription(PAYMENTARTICLETYPE);
		$markerArray['###SUM_TAX###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketObj->getTaxSum(), $this->currency, $this->showCurrency
		);

		$taxRateTemplate = $this->cObj->getSubpart($template, '###TAX_RATE_SUMS###');
		$taxRates = $basketObj->getTaxRateSums();
		$taxRateRows = '';
		foreach ($taxRates as $taxRate => $taxRateSum) {
			$taxRowArray = array();
			$taxRowArray['###TAX_RATE###'] = $taxRate;
			$taxRowArray['###TAX_RATE_SUM###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
				$taxRateSum, $this->currency, $this->showCurrency
			);

			$taxRateRows .= $this->cObj->substituteMarkerArray($taxRateTemplate, $taxRowArray);
		}

		/**
		 * Hook for processing Taxes
		 */
		$hooks = HookFactory::getHooks('Controller/BaseController', 'makeBasketInformation');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'processMarkerTaxInformation')) {
				$taxRateRows = $hook->processMarkerTaxInformation($taxRateTemplate, $basketObj, $this);
			}
		}

		$template = $this->cObj->substituteSubpart($template, '###TAX_RATE_SUMS###', $taxRateRows);

		/**
		 * Hook for processing Marker Array
		 */
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'processMarkerBasketInformation')) {
				$markerArray = $hook->processMarkerBasketInformation($markerArray, $basketObj, $this);
			}
		}

		$content = $this->cObj->substituteMarkerArray($template, $markerArray);
		$content = $this->cObj->substituteMarkerArray($content, $this->languageMarker);

		return $content;
	}

	/**
	 * Renders the given Basket Ite,
	 * This Method will not replace the Subpart, you have to replace your subpart
	 * in your template by you own
	 *
	 * @param BasketItem $basketItemObj Basket Object
	 * @param string $subpartTemplate Subpart Template Subpart
	 *
	 * @return string $content HTML-Ccontent from the given Subpart
	 * @abstract
	 * Renders the following MARKER
	 * ###PRODUCT_TITLE###
	 * ###PRODUCT_IMAGES###<br />
	 * <SPAN>###PRODUCT_SUBTITLE###<BR/>
	 * ###LANG_ARTICLE_NUMBER### ###ARTICLE_EANCODE###<br/>
	 * ###PRODUCT_LINK_DETAIL###</SPAN>
	 * ###LANG_PRICE_NET### ###BASKET_ITEM_PRICENET###<br/>
	 * ###LANG_PRICE_GROSS### ###BASKET_ITEM_PRICEGROSS###<br/>
	 * ###LANG_TAX### ###BASKET_ITEM_TAX_VALUE### ###BASKET_ITEM_TAX_PERCENT###<br/>
	 * ###LANG_COUNT### ###BASKET_ITEM_COUNT###<br/>
	 * ###LANG_PRICESUM_NET### ###BASKET_ITEM_PRICESUM_NET### <br/>
	 * ###LANG_PRICESUM_GROSS### ###BASKET_ITEM_PRICESUM_GROSS### <br/>
	 */
	public function makeLineView(BasketItem $basketItemObj, $subpartTemplate) {
		$markerArray = array();
		$template = $this->cObj->getSubpart($this->templateCode, $subpartTemplate);

		/**
		 * Basket Item Elements
		 */
		$markerArray['###BASKET_ITEM_PRICENET###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketItemObj->getPriceNet(), $this->currency, $this->showCurrency
		);
		$markerArray['###BASKET_ITEM_PRICEGROSS###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketItemObj->getPriceGross(), $this->currency, $this->showCurrency
		);
		$markerArray['###BASKET_ITEM_PRICESUM_NET###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketItemObj->getItemSumNet(), $this->currency, $this->showCurrency
		);
		$markerArray['###BASKET_ITEM_PRICESUM_GROSS###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$basketItemObj->getItemSumGross(), $this->currency, $this->showCurrency
		);
		$markerArray['###BASKET_ITEM_ORDERNUMBER###'] = $basketItemObj->getOrderNumber();

		$markerArray['###BASKET_ITEM_TAX_PERCENT###'] = $basketItemObj->getTax();
		$markerArray['###BASKET_ITEM_TAX_VALUE###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			(int) $basketItemObj->getItemSumTax(), $this->currency, $this->showCurrency
		);
		$markerArray['###BASKET_ITEM_COUNT###'] = $basketItemObj->getQuantity();
		$markerArray['###PRODUCT_LINK_DETAIL###'] = $this->pi_linkTP_keepPIvars(
			$this->pi_getLL('detaillink', 'details'), array(
				'showUid' => $basketItemObj->getProductUid(),
				'catUid' => (int) $basketItemObj->getProductMasterparentCategorie()
			), TRUE, TRUE, $this->conf['listPid']
		);

		$hooks = HookFactory::getHooks('Controller/BaseController', 'makeLineView');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'processMarkerLineView')) {
				$markerArray = $hook->processMarkerLineView($markerArray, $basketItemObj, $this);
			}
		}

		$content = $this->cObj->substituteMarkerArray($template, $markerArray);

		/**
		 * Basket Artikcel Lementes
		 */
		$productArray = $basketItemObj->getProductAssocArray('PRODUCT_');
		$content = $this->cObj->substituteMarkerArray($content, $productArray, '###|###', 1);

		$articleArray = $basketItemObj->getArticleAssocArray('ARTICLE_');
		$content = $this->cObj->substituteMarkerArray($content, $articleArray, '###|###', 1);

		$content = $this->cObj->substituteMarkerArray($content, $this->languageMarker, '###|###', 1);

		return $content;
	}

	/**
	 * Adds the the commerce TYPO3 Link parameter for commerce to existing
	 * typoLink StdWarp if typolink.setCommerceValues = 1 is set.
	 *
	 * @param array $typoscript Existing TypoScriptConfiguration
	 * @param array $typoLinkConf TypoLink Configuration, buld bie view Method
	 *
	 * @return array Changed TypoScript Configuration
	 */
	public function addTypoLinkToTypoScript(array $typoscript, array $typoLinkConf) {
		foreach (array_keys($typoscript['fields.']) as $tsKey) {
			if (isset($typoscript['fields.'][$tsKey]['typolink.']) && is_array($typoscript['fields.'][$tsKey]['typolink.'])) {
				if ($typoscript['fields.'][$tsKey]['typolink.']['setCommerceValues'] == 1) {
					$typoscript['fields.'][$tsKey]['typolink.']['parameter'] = $typoLinkConf['parameter'];
					$typoscript['fields.'][$tsKey]['typolink.']['additionalParams'] .= $typoLinkConf['additionalParams'];
				}
			}
			if (is_array($typoscript['fields.'][$tsKey])) {
				if (isset($typoscript['fields.'][$tsKey]['stdWrap.']) && is_array($typoscript['fields.'][$tsKey]['stdWrap.'])) {
					if (is_array($typoscript['fields.'][$tsKey]['stdWrap.']['typolink.'])) {
						if ($typoscript['fields.'][$tsKey]['stdWrap.']['typolink.']['setCommerceValues'] == 1) {
							$typoscript['fields.'][$tsKey]['stdWrap.']['typolink.']['parameter'] = $typoLinkConf['parameter'];
							$typoscript['fields.'][$tsKey]['stdWrap.']['typolink.']['additionalParams'] .= $typoLinkConf['additionalParams'];
						}
					}
				}
			}
		}

		return $typoscript;
	}

	/**
	 * Generates a markerArray from given data and TypoScript
	 *
	 * @param array $data Assoc-Array with keys as Database fields and values
	 * @param array $typoscript TypoScript Configuration
	 * @param string $prefix For marker, default empty
	 * @param string $table Table name
	 *
	 * @return array Marker Array for using cobj Marker array methods
	 */
	public function generateMarkerArray(array $data, array $typoscript, $prefix = '', $table = '') {
		if (!$typoscript['fields.']) {
			$typoscript['fields.'] = $typoscript;
		}
		$markerArray = array();
		if (is_array($data)) {
			$dataBackup = $this->cObj->data;
			$this->cObj->start($data, $table);

			foreach ($data as $fieldName => $columnValue) {
				// get TS config
				$type = $typoscript['fields.'][$fieldName];
				$config = (array) $typoscript['fields.'][$fieldName . '.'];

				if (empty($type)) {
					$type = $typoscript['defaultField'];
					$config = (array) $typoscript['defaultField.'];
				}
				if ($type == 'IMAGE') {
					$config['altText'] = $data['title'];
				}

				$markerArray[strtoupper($prefix . $fieldName)] = $this->renderValue(
					$columnValue, $type, (array) $config, $fieldName, $table, $data['uid']
				);
			}

			$this->cObj->data = $dataBackup;
		}

		return $markerArray;
	}

	/**
	 * Renders one Value to TS
	 * Availiabe TS types are IMGTEXT, IMAGE, STDWRAP
	 *
	 * @param mixed $value Outputvalue
	 * @param string $typoscriptType TypoScript Type for this value
	 * @param array $typoscriptConfig TypoScript Config for this value
	 * @param string $field Database field name
	 * @param string $table Database table name
	 * @param int|string $uid Uid of record
	 *
	 * @return string html-content
	 */
	public function renderValue($value, $typoscriptType, array $typoscriptConfig, $field = '', $table = '', $uid = '') {
		/**
		 * If you add more TS Types using the imgPath, you should add
		 * these also to generateMarkerArray
		 */
		$output = '';
		if (!isset($typoscriptConfig['imgPath'])) {
			$typoscriptConfig['imgPath'] = $this->imgFolder;
		}
		switch (strtoupper($typoscriptType)) {
			case 'IMGTEXT':
				$typoscriptConfig['imgList'] = $value;
				$output = $this->cObj->IMGTEXT($typoscriptConfig);
				break;

			case 'RELATION':
				$singleValue = explode(',', $value);

				foreach ($singleValue as $uid) {
					$data = $this->pi_getRecord($typoscriptConfig['table'], $uid);
					if ($data) {
						$singleOutput = $this->renderTable(
							$data, $typoscriptConfig['dataTS.'], $typoscriptConfig['subpart'], $typoscriptConfig['table'] . '_'
						);
						$output .= $this->cObj->stdWrap($singleOutput, $typoscriptConfig['singleStdWrap.']);
					}
				}

				if ($output) {
					$output = $this->cObj->stdWrap($output, $typoscriptConfig['stdWrap.']);
				}
				break;

			case 'MMRELATION':
				$local = 'uid_local';
				$foreign = 'uid_foreign';
				if ($typoscriptConfig['switchFields']) {
					$foreign = 'uid_local';
					$local = 'uid_foreign';
				}

				$rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
					'distinct(' . $foreign . ')',
					$typoscriptConfig['tableMM'],
					$local . ' = ' . (int) $uid . '  ' . $typoscriptConfig['table.']['addWhere'],
					'',
					'sorting'
				);
				foreach ($rows as $row) {
					$data = $this->pi_getRecord($typoscriptConfig['table'], $row[$foreign]);
					if ($data) {
						$singleOutput = $this->renderTable(
							$data, $typoscriptConfig['dataTS.'], $typoscriptConfig['subpart'], $typoscriptConfig['table'] . '_'
						);
						$output .= $this->cObj->stdWrap($singleOutput, $typoscriptConfig['singleStdWrap.']);
					}
				}

				$output = trim(trim($output), ' ,:;');
				$output = $this->cObj->stdWrap($output, $typoscriptConfig['stdWrap.']);
				break;

			case 'FILES':
				$files = explode(',', $value);
				foreach ($files as $v) {
					$file = $this->imgFolder . $v;
					$text = $this->cObj->stdWrap($file, $typoscriptConfig['linkStdWrap.']) . $v;
					$output .= $this->cObj->stdWrap($text, $typoscriptConfig['stdWrap.']);
				}
				$output = $this->cObj->stdWrap($output, $typoscriptConfig['allStdWrap.']);
				break;

			case 'IMAGE':
				if (is_string($value) && !empty($value)) {
					foreach (explode(',', $value) as $oneValue) {
						if (!is_numeric($value)) {
							$this->cObj->setCurrentVal($typoscriptConfig['imgPath'] . $oneValue);
							if ($typoscriptConfig['file'] <> 'GIFBUILDER') {
								$typoscriptConfig['file'] = $typoscriptConfig['imgPath'] . $oneValue;
							}
						}
						$output .= $this->cObj->IMAGE($typoscriptConfig);
					}
				} elseif (strlen($typoscriptConfig['file']) && $typoscriptConfig['file'] <> 'GIFBUILDER') {
					$output .= $this->cObj->IMAGE($typoscriptConfig);
				}
				break;

			case 'IMG_RESOURCE':
				if (is_string($value) && !empty($value)) {
					$typoscriptConfig['file'] = $typoscriptConfig['imgPath'] . $value;
					$output = $this->cObj->IMG_RESOURCE($typoscriptConfig);
				}
				break;

			case 'NUMBERFORMAT':
				if ($typoscriptConfig['format']) {
					$value = number_format(
						(float) $value,
						$typoscriptConfig['format.']['decimals'],
						$typoscriptConfig['format.']['dec_point'],
						$typoscriptConfig['format.']['thousands_sep']
					);
				}
				// pass through
			case 'STDWRAP':
				if (is_array($typoscriptConfig['parseFunc.'])) {
					$output = $this->cObj->stdWrap($value, $typoscriptConfig);
				} else {
					$output = $this->cObj->stdWrap(strip_tags($value), $typoscriptConfig);
				}
				break;

			default:
				$output = htmlspecialchars(strip_tags($value));
		}

		$hooks = HookFactory::getHooks('Controller/BaseController', 'renderValue');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'postRenderValue')) {
				$output = $hook->postRenderValue(
					$output, array(
						$value,
						$typoscriptType,
						$typoscriptConfig,
						$field,
						$table,
						$uid
					)
				);
			}
		}

		/**
		 * Add admin panel
		 */
		if (is_string($table) && is_string($field)) {
			$this->cObj->currentRecord = $table . ':' . $uid;
		}

		return $output;
	}

	/**
	 * Reders a category as output
	 *
	 * @param Category $category Category
	 * @param string $subpartName Template subpart name
	 * @param array $typoscript TypoScript array for rendering
	 * @param string $prefix Prefix for Marker, optional#
	 * @param string $template Template
	 *
	 * @return string HTML-Content
	 */
	public function renderCategory(Category $category, $subpartName, array $typoscript, $prefix = '',
			$template = ''
	) {
		return $this->renderElement($category, $subpartName, $typoscript, $prefix, '###CATEGORY_', $template);
	}

	/**
	 * Reders an element as output
	 *
	 * @param AbstractEntity $element Element
	 * @param string $subpartName Template subpart name
	 * @param array $typoscript TypoScript array for rendering
	 * @param string $prefix Prefix for Marker, optional#
	 * @param string $markerWrap SecondPrefix for Marker, default ###
	 * @param string $template Template
	 *
	 * @return string HTML-Content
	 */
	public function renderElement(AbstractEntity $element, $subpartName, array $typoscript, $prefix = '',
			$markerWrap = '###', $template = ''
	) {
		if (empty($subpartName)) {
			return $this->error(
				'renderElement', __LINE__, 'No supart defined for class.CommerceTeam\\Commerce\\Controller\\BaseController::renderElement'
			);
		}
		if (strlen($template) < 1) {
			$template = $this->template;
		}
		if (empty($template)) {
			return $this->error(
				'renderElement', __LINE__, 'No Template given as parameter to method and no template loaded via TS'
			);
		}

		$output = $this->cObj->getSubpart($template, $subpartName);
		if (empty($output)) {
			return $this->error(
				'renderElement', __LINE__,
				'class.tx_commerce_pibase::renderElement: Subpart:' . $subpartName . ' not found in HTML-Code', $template
			);
		}

		$data = $element->returnAssocArray();

		$markerArray = $this->generateMarkerArray($data, $typoscript);

		$hooks = HookFactory::getHooks('Controller/BaseController', 'renderElement');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'additionalMarkerElement')) {
				$markerArray = $hook->additionalMarkerElement($markerArray, $element, $this);
			}
		}

		if ($prefix > '') {
			$markerWrap .= strtoupper($prefix) . '_';
		}
		$markerWrap .= '|###';

		if (is_array($markerArray) && count($markerArray)) {
			$output = $this->cObj->substituteMarkerArray($output, $markerArray, $markerWrap, 1);
			$output = $this->cObj->stdWrap($output, $typoscript['stdWrap.']);
		} else {
			$output = '';
		}

		return $output;
	}

	/**
	 * Formates the attribute value
	 * concerning the sprinf formating if value is a number
	 *
	 * @param array $matrix AttributeMatrix
	 * @param int $myAttributeUid Uid of attribute
	 *
	 * @return string Formated Value
	 */
	public function formatAttributeValue(array $matrix, $myAttributeUid) {
		$return = '';
		/**
		 * Return if empty
		 */
		if (!is_array($matrix)) {
			return $return;
		}

		$hookObject = HookFactory::getHook('Controller/BaseController', 'formatAttributeValue');

		$i = 0;
		$attributeValues = count($matrix[$myAttributeUid]['values']);

		foreach ((array) $matrix[$myAttributeUid]['values'] as $key => $value) {
			// Sometimes $value is the whole database array
			if (is_array($value) && isset($value['value']) && $value['value'] != '') {
				$value = $value['value'];
			}
			$return2 = $value;
			if (is_numeric($value)) {
				if ($matrix[$myAttributeUid]['valueformat']) {
					$return2 = sprintf($matrix[$myAttributeUid]['valueformat'], $value);
				}
			}
			if (is_object($hookObject) && method_exists($hookObject, 'formatAttributeValue')) {
				$return2 = $hookObject->formatAttributeValue(
					$key, $myAttributeUid, $matrix[$myAttributeUid]['valueuidlist'][$key], $return2, $this
				);
			}
			if ($attributeValues > 1) {
				$return2 = $this->cObj->stdWrap($return2, $this->conf['mutipleAttributeValueWrap.']);
			}
			if ($i > 0) {
				$return .= $this->conf['attributeLinebreakChars'];
			}
			$return .= $return2;
			$i++;
		}
		if ($attributeValues > 1) {
			$return = $this->cObj->stdWrap($return, $this->conf['mutipleAttributeValueSetWrap.']);
		}

		return $return;
	}

	/**
	 * Returns an string concerning the actial error
	 * plus adding debug of $this->conf;
	 *
	 * @param string $methodName Methdo Name from where thsi error is called
	 * @param int $line Line of code (normally should be __LINE__)
	 * @param string $errortext Text for this error
	 * @param bool|string $additionaloutput Aditional code output in <pre></pre>
	 *
	 * @return string HTML Code
	 */
	public function error($methodName, $line, $errortext, $additionaloutput = FALSE) {
		$errorOutput = __FILE__ . '<br />';
		$errorOutput .= get_class($this) . '<br />';
		$errorOutput .= $methodName . '<br />';
		$errorOutput .= 'Line ' . $line . '<br />';
		$errorOutput .= $errortext;
		if ($additionaloutput) {
			$errorOutput .= '<pre>' . $additionaloutput . '</pre>';
		}

		$return = '';
		if ($this->conf['showErrors']) {
			$this->debug($errorOutput, 'ERROR', '');

			$return = $errorOutput;
		}

		return $return;
	}

	/**
	 * Debugging var with header and group
	 *
	 * @param mixed $var Var
	 * @param string $header Header
	 * @param string $group Group
	 *
	 * @return void
	 */
	protected function debug($var, $header, $group) {
		if ($this->debug) {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug($var, $header, $group);
		}
	}

	/**
	 * Return the amount of articles for the basket input form
	 *
	 * @param int $articleId ArticleId check for the amount
	 * @param array|bool $typoscriptConfig Typoscript config
	 *
	 * @return int
	 */
	public function getArticleAmount($articleId, $typoscriptConfig = FALSE) {
		if (!$articleId) {
			return FALSE;
		}

		$amount = 0;
		$basket = $this->getBasket();
		/**
		 * Basket item
		 *
		 * @var BasketItem $basketItem
		 */
		$basketItem = $basket->getBasketItem($articleId);
		if (is_object($basketItem)) {
			$amount = $basketItem->getQuantity();
		} else {
			if ($typoscriptConfig == FALSE) {
				$amount = $this->conf['defaultArticleAmount'];
			} elseif ($typoscriptConfig['defaultQuantity']) {
				$amount = $typoscriptConfig['defaultQuantity'];
			}
		}

		return $amount;
	}

	/**
	 * Render products for list view
	 *
	 * @param array $categoryProducts Category product
	 * @param array $templateMarker Template marker
	 * @param int $iterations Iterations
	 * @param string $typoscriptMarker Marker
	 *
	 * @return string
	 */
	public function renderProductsForList(array $categoryProducts, array $templateMarker, $iterations, $typoscriptMarker = '') {
		$markerArray = array();

		$hooks = HookFactory::getHooks('Controller/BaseController', 'renderProductsForList');
		foreach ($hooks as $hook) {
			if (method_exists($hook, 'preProcessorProductsListView')) {
				$markerArray = $hook->preProcessorProductsListView(
					$categoryProducts, $templateMarker, $iterations, $typoscriptMarker, $this
				);
			}
		}

		$categoryItemsListview = '';
		$iterationCount = 0;
		$content = '';
		if (is_array($categoryProducts)) {
			foreach ($categoryProducts as $myProductId) {
				if ($iterationCount >= $iterations) {
					$iterationCount = 0;
				}
				$template = $this->cObj->getSubpart($this->templateCode, '###' . $templateMarker[$iterationCount] . '###');

				/**
				 * Product
				 *
				 * @var Product $myProduct
				 */
				$myProduct = GeneralUtility::makeInstance(
					'CommerceTeam\\Commerce\\Domain\\Model\\Product',
					$myProductId,
					$this->getFrontendController()->sys_language_uid
				);
				$myProduct->loadData();

				if ($this->conf['useStockHandling'] == 1 AND $myProduct->hasStock() === FALSE) {
					$typoScript = $this->conf['listView' . $typoscriptMarker . '.']['products.']['nostock.'];
					$tempTemplate = $this->cObj->getSubpart($this->templateCode, '###' . $templateMarker[$iterationCount] . '_NOSTOCK###');
					if ($tempTemplate != '') {
						$template = $tempTemplate;
					}
				} else {
					$typoScript = $this->conf['listView' . $typoscriptMarker . '.']['products.'];
				}
				$iterationCount++;
				$categoryItemsListview .= $this->renderProduct(
					$myProduct,
					$template,
					(array) $typoScript,
					(array) $this->conf['templateMarker.']['basketListView.'],
					$this->conf['templateMarker.']['basketListViewMarker']
				);
			}

			$markerArray = $this->addFormMarker($markerArray);

			$content = $this->cObj->stdWrap(
				$this->cObj->substituteMarkerArray($categoryItemsListview, $markerArray, '###|###', 1),
				$this->conf['listView.']['products.']['stdWrap.']
			);
		}

		return $content;
	}

	/**
	 * This method renders a product to a template
	 *
	 * @param Product $product Product
	 * @param string $template TYPO3 Template
	 * @param array $typoscript TypoScript
	 * @param array $articleMarker Marker for the article description
	 * @param string $articleSubpart Subpart
	 *
	 * @return string rendered HTML
	 */
	public function renderProduct(Product $product, $template, array $typoscript, array $articleMarker,
			$articleSubpart = ''
	) {
		if (!($product instanceof Product)) {
			return FALSE;
		}
		if (empty($articleMarker)) {
			return $this->error('renderProduct', __LINE__, 'No ArticleMarker defined in renderProduct ');
		}

		$hooks = HookFactory::getHooks('Controller/BaseController', 'renderProduct');

		$data = $product->returnAssocArray();

		// maybe this is a related product so category may be wrong
		$categoryUid = $this->category->getUid();
		$productCategories = $product->getParentCategories();
		if (!in_array($categoryUid, $productCategories, FALSE)) {
			$categoryUid = $productCategories[0];
		}

		/**
		 *  Build TS for Linking the Catergory Images
		 */
		$localTs = $typoscript;

		/**
		 * Generate TypoLink Configuration and ad to fields by addTypoLinkToTs
		 */
		if ($this->conf['singlePid']) {
			$typoLinkConf['parameter'] = $this->conf['singlePid'];
		} elseif ($this->conf['overridePid']) {
			$typoLinkConf['parameter'] = $this->conf['overridePid'];
		} else {
			$typoLinkConf['parameter'] = $this->pid;
		}
		$typoLinkConf['useCacheHash'] = 1;
		$typoLinkConf['additionalParams'] = $this->argSeparator . $this->prefixId . '[showUid]=' . $product->getUid();
		$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[catUid]=' . $categoryUid;

		if ($this->basketHashValue) {
			$typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[basketHashValue]=' . $this->basketHashValue;
		}

		$localTs = $this->addTypoLinkToTypoScript($localTs, $typoLinkConf);

		$markerArray = $this->generateMarkerArray($data, $localTs, '', 'CommerceTeam\\Commerce\\Domain\\Model\\Products');
		$markerArrayUp = array();
		foreach ($markerArray as $k => $v) {
			$markerArrayUp[strtoupper($k)] = $v;
		}
		$markerArray = $this->cObj->fillInMarkerArray(
			array(), $markerArrayUp, implode(',', array_keys($markerArrayUp)), FALSE, 'PRODUCT_'
		);

		$this->can_attributes = $product->getAttributes(array(ATTRIB_CAN));
		$this->selectAttributes = $product->getAttributes(array(ATTRIB_SELECTOR));
		$this->shall_attributes = $product->getAttributes(array(ATTRIB_SHAL));

		$productAttributesSubpartArray = array();
		$productAttributesSubpartArray[] = '###' . strtoupper($this->conf['templateMarker.']['productAttributes']) . '###';
		$productAttributesSubpartArray[] = '###' . strtoupper($this->conf['templateMarker.']['productAttributes2']) . '###';

		$markerArray['###SUBPART_PRODUCT_ATTRIBUTES###'] = $this->cObj->stdWrap(
			$this->renderProductAttributeList(
				$product, $productAttributesSubpartArray, $typoscript['productAttributes.']['fields.']
			), $typoscript['productAttributes.']
		);

		$linkArray['catUid'] = (int) $categoryUid;

		if ($this->basketHashValue) {
			$linkArray['basketHashValue'] = $this->basketHashValue;
		}
		if (is_numeric($this->piVars['manufacturer'])) {
			$linkArray['manufacturer'] = $this->piVars['manufacturer'];
		}
		if (is_numeric($this->piVars['mDepth'])) {
			$linkArray['mDepth'] = $this->piVars['mDepth'];
		}
		foreach ($hooks as $hookObj) {
			if (method_exists($hookObj, 'postProcessLinkArray')) {
				$linkArray = $hookObj->postProcessLinkArray($linkArray, $product, $this);
			}
		}
		$wrapMarkerArray['###PRODUCT_LINK_DETAIL###'] = explode(
			'|', $this->pi_list_linkSingle('|', $product->getUid(), TRUE, $linkArray, FALSE, $this->conf['overridePid'])
		);
		$articleTemplate = $this->cObj->getSubpart($template, '###' . strtoupper($articleSubpart) . '###');

		if ($this->conf['useStockHandling'] == 1) {
			$product = \CommerceTeam\Commerce\Utility\GeneralUtility::removeNoStockArticles(
				$product, $this->conf['articles.']['showWithNoStock']
			);
		}

		// Set RenderMaxArticles to TS value
		if ((!empty($localTs['maxArticles'])) && ((int) $localTs['maxArticles'] > 0)) {
			$product->setRenderMaxArticles((int) $localTs['maxArticles']);
		}

		$subpartArray = array();
		if (
			$this->conf['disableArticleViewForProductlist'] == 1
			&& !$this->piVars['showUid']
			|| $this->conf['disableArticleView'] == 1
		) {
			$subpartArray['###' . strtoupper($articleSubpart) . '###'] = '';
		} else {
			$subpartArray['###' . strtoupper($articleSubpart) . '###'] = $this->makeArticleView(
				'list', array(), $product, $articleMarker, $articleTemplate
			);
		}

		/**
		 * Get The Checapest Price
		 */
		$cheapestArticleUid = $product->getCheapestArticle();
		/**
		 * Cheapest Article
		 *
		 * @var \CommerceTeam\Commerce\Domain\Model\Article $cheapestArticle
		 */
		$cheapestArticle = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Article', $cheapestArticleUid);
		$cheapestArticle->loadData();
		$cheapestArticle->loadPrices();

		$markerArray['###PRODUCT_CHEAPEST_PRICE_GROSS###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$cheapestArticle->getPriceGross(), $this->currency
		);

		$cheapestArticleUid = $product->getCheapestArticle(1);
		/**
		 * Cheapest Article
		 *
		 * @var \CommerceTeam\Commerce\Domain\Model\Article $cheapestArticle
		 */
		$cheapestArticle = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Article', $cheapestArticleUid);
		$cheapestArticle->loadData();
		$cheapestArticle->loadPrices();

		$markerArray['###PRODUCT_CHEAPEST_PRICE_NET###'] = \CommerceTeam\Commerce\ViewHelpers\Money::format(
			$cheapestArticle->getPriceNet(),
			$this->currency
		);

		foreach ($hooks as $hookObj) {
			if (method_exists($hookObj, 'additionalMarkerProduct')) {
				$markerArray = $hookObj->additionalMarkerProduct($markerArray, $product, $this);
			}
		}
		foreach ($hooks as $hookObj) {
			if (method_exists($hookObj, 'additionalSubpartsProduct')) {
				$subpartArray = $hookObj->additionalSubpartsProduct($subpartArray, $product, $this);
			}
		}

		$content = $this->substituteMarkerArrayNoCached($template, $markerArray, $subpartArray, $wrapMarkerArray);
		if ($typoscript['editPanel'] == 1) {
			$content = $this->cObj->editPanel(
				$content, $typoscript['editPanel.'], '\CommerceTeam\Commerce\Domain\Model\Products:' . $product->getUid()
			);
		}

		foreach ($hooks as $hookObj) {
			if (method_exists($hookObj, 'modifyContentProduct')) {
				$content = $hookObj->modifyContentProduct($content, $product, $this);
			}
		}

		return $content;
	}

	/**
	 * Adds the global Marker for the formtags to the given marker array
	 *
	 * @param array $markerArray Array of marker
	 * @param string|bool $wrap If the marker should be wrapped
	 *
	 * @return array Marker Array with the new marker
	 */
	public function addFormMarker(array $markerArray, $wrap = FALSE) {
		$newMarkerArray['GENERAL_FORM_ACTION'] = $this->pi_getPageLink($this->conf['basketPid']);
		if (!empty($this->conf['basketPid.'])) {
			$basketConf = $this->conf['basketPid.'];
			$basketConf['returnLast'] = 'url';
			$newMarkerArray['GENERAL_FORM_ACTION'] = $this->cObj->typoLink('', $basketConf);
		}
		if (is_object($this->category)) {
			$newMarkerArray['GENERAL_HIDDENCATUID'] = '<input type="hidden" name="' . $this->prefixId .
				'[catUid]" value="' . $this->category->getUid() . '" />';
		}
		if ($wrap) {
			foreach ($newMarkerArray as $key => $value) {
				$markerArray[$this->cObj->wrap($key, $wrap)] = $value;
			}
		} else {
			$markerArray = array_merge($markerArray, $newMarkerArray);
		}

		return $markerArray;
	}

	/**
	 * Make article view
	 *
	 * @param string $kind Kind
	 * @param array $articles Articles
	 * @param Product $product Product
	 * @param string $articleMarker Article marker
	 * @param string $articleTemplate Article template
	 *
	 * @return string
	 */
	public function makeArticleView($kind, array $articles, Product $product, $articleMarker = '', $articleTemplate = '') {
		return '';
	}

	/**
	 * Render record table
	 *
	 * @param array $data Data
	 * @param array $typoscript TypoScript
	 * @param string $template Template
	 * @param string $prefix Prefix
	 *
	 * @return string
	 */
	public function renderTable(array $data, array $typoscript, $template, $prefix) {
		return '';
	}

	/**
	 * Render single view
	 *
	 * @param Product $product Product
	 * @param Category $category Category
	 * @param string $subpartName Subpart
	 * @param string $subpartNameNostock Subport no stock
	 *
	 * @return string
	 */
	public function renderSingleView(Product $product, Category $category, $subpartName, $subpartNameNostock) {
		return '';
	}

	/**
	 * Multi substitution function
	 *
	 * @param string $content The content stream, typically HTML template content.
	 * @param array $markContentArray Regular marker-array where the 'keys' are
	 *        substituted in $content with their values
	 * @param array $subpartContentArray Exactly like markContentArray only is
	 *        whole subparts substituted and not only a single marker.
	 * @param array $wrappedSubpartContentArray An array of arrays with 0/1 keys
	 *        where the subparts pointed to by the main key is wrapped with the 0/1
	 *        value alternating.
	 *
	 * @return string The output content stream
	 */
	public function substituteMarkerArrayNoCached($content, array $markContentArray = array(), array $subpartContentArray = array(),
		array $wrappedSubpartContentArray = array()
	) {
		$timeTrack = $this->getTimeTracker();
		$timeTrack->push('commerce: substituteMarkerArrayNoCache');

		// If not arrays then set them
		if (!is_array($markContentArray)) {
			$markContentArray = array();
		}
		if (!is_array($subpartContentArray)) {
			$subpartContentArray = array();
		}
		if (!is_array($wrappedSubpartContentArray)) {
			$wrappedSubpartContentArray = array();
		}
		// Finding keys and check hash:
		$sPkeys = array_keys($subpartContentArray);
		$wPkeys = array_keys($wrappedSubpartContentArray);
		$aKeys = array_merge(array_keys($markContentArray), $sPkeys, $wPkeys);
		if (!count($aKeys)) {
			$timeTrack->pull();

			return $content;
		}
		asort($aKeys);

		// Initialize storeArr
		$storeArr = array();

		// Finding subparts and substituting them with the subpart as a marker
		foreach ($sPkeys as $sPk) {
			$content = $this->cObj->substituteSubpart($content, $sPk, $sPk);
		}

		// Finding subparts and wrapping them with markers
		foreach ($wPkeys as $wPk) {
			$content = $this->cObj->substituteSubpart($content, $wPk, array($wPk, $wPk));
		}

		// traverse keys and quote them for reg ex.
		foreach ($aKeys as $tK => $tV) {
			$aKeys[$tK] = preg_quote($tV, '/');
		}
		$regex = '/' . implode('|', $aKeys) . '/';
		// Doing regex's
		$storeArr['c'] = preg_split($regex, $content);
		preg_match_all($regex, $content, $keyList);
		$storeArr['k'] = $keyList[0];

		// Substitution/Merging:
		// Merging content types together, resetting
		$valueArr = array_merge($markContentArray, $subpartContentArray, $wrappedSubpartContentArray);

		$wScaReg = array();
		$content = '';
		// traversing the keyList array and merging the static and dynamic content
		foreach ($storeArr['k'] as $n => $keyN) {
			$content .= $storeArr['c'][$n];
			if (!is_array($valueArr[$keyN])) {
				$content .= $valueArr[$keyN];
			} else {
				$content .= $valueArr[$keyN][((int) $wScaReg[$keyN] % 2)];
				$wScaReg[$keyN]++;
			}
		}
		$content .= $storeArr['c'][count($storeArr['k'])];

		$timeTrack->pull();

		return $content;
	}

	/**
	 * Getter
	 *
	 * @return string
	 */
	public function getHandle() {
		return $this->handle;
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Get time tracker
	 *
	 * @return \TYPO3\CMS\Core\TimeTracker\TimeTracker
	 */
	protected function getTimeTracker() {
		return $GLOBALS['TT'];
	}

	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}

	/**
	 * Get frontend user
	 *
	 * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
	 */
	protected function getFrontendUser() {
		return $this->getFrontendController()->fe_user;
	}

	/**
	 * Get basket
	 *
	 * @return \CommerceTeam\Commerce\Domain\Model\Basket
	 */
	protected function getBasket() {
		return $this->getFrontendUser()->tx_commerce_basket;
	}
}

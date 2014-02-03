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
 * Product list and single view
 */
class tx_commerce_pi1 extends tx_commerce_pibase {
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
	public $scriptRelPath = 'pi1/class.tx_commerce_pi1.php';

	/**
	 * The extension key.
	 *
	 * @var string
	 */
	public $extKey = 'commerce';

	/**
	 * @var string
	 */
	public $currency = 'EUR';

	/**
	 * @var boolean
	 */
	public $pi_checkCHash = TRUE;

	/**
	 * @var Boolean, will be set to TRUE, if the plugin was inserted as plugin to show one produkt, or one product was set via TypoScript.
	 */
	public $singleViewAsPlugin = FALSE;

	/**
	 * @var tx_commerce_category
	 */
	public $category;

	/**
	 * @var integer
	 */
	public $master_cat;

	/**
	 * @var tx_commerce_category
	 */
	public $masterCategoryObj;

	/**
	 * @var array
	 */
	public $category_array = array();

	/**
	 * @var string
	 */
	public $templateFolder = '';

	/**
	 * @var string
	 */
	public $templateCode = '';

	/**
	 * @var string
	 */
	public $template = '';

	/**
	 * @var array
	 */
	public $markerArray = array();

	/**
	 * @var string
	 */
	public $content = '';

	/**
	 * @var tx_commerce_product
	 */
	public $product;

	/**
	 * @var array
	 */
	public $product_array = array();

	/**
	 * Inits the main params for using in the script
	 *
	 * @param array $conf Configuration
	 * @return void
	 */
	public function init($conf) {
		parent::init($conf);

			// Merge default vars, if other prefix_id
		if ($this->prefixId <> 'tx_commerce_pi1') {
			$tx_commerce_vars = t3lib_div::_GP('tx_commerce');
			if (is_array($tx_commerce_vars)) {
				foreach ($tx_commerce_vars as $key => $value) {
					if (empty($this->piVars[$key])) {
						$this->piVars[$key] = $value;
					}
				}
			}
		}

		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi1/class.tx_commerce_pi1.php']['init'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi1/class.tx_commerce_pi1.php']['init'] as $classRef) {
				$hookObj = &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'preInit')) {
					$hookObj->preInit($this);
				}
			}
		}

		$this->imgFolder = 'uploads/tx_commerce/';
		$this->templateFolder = 'uploads/tx_commerce/';
		$this->pi_USER_INT_obj = 0;

		$this->conf['singleProduct'] = (int) $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'product_id', 's_product');

		if ($this->conf['singleProduct'] > 0) {
				// product UID was set by Plugin or TS
			$this->singleViewAsPlugin = TRUE;
		}

			// Unset variable, if smaller than 0, as -1 is returend when no product is selcted in form.
		if ($this->conf['singleProduct'] < 0) {
			$this->conf['singleProduct'] = FALSE;
		}

		$this->piVars['showUid'] = intval($this->piVars['showUid'] ?
			$this->piVars['showUid'] :
			($this->conf['singleProduct'] ? $this->conf['singleProduct'] : ''));
		$this->handle = $this->piVars['showUid'] ? 'singleView' : 'listView';

			// Define the currency
			// Use of curency is depricated as it was only a typo :-)
		if ($this->conf['curency'] > '') {
			$this->currency = $this->conf['curency'];
		}
		if ($this->conf['currency'] > '') {
			$this->currency = $this->conf['currency'];
		}
		if (empty($this->currency)) {
			$this->currency = 'EUR';
		}

			// Set some flexform values
		$this->master_cat = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'StartCategory', 's_product');
		if (!$this->master_cat) {
			$this->master_cat = $this->conf['catUid'];
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'displayPID', 's_template')) {
			$this->conf['overridePid'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'displayPID', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'numberOfTopproducts', 's_product')) {
			$this->conf['numberOfTopproducts'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'numberOfTopproducts', 's_product');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showPageBrowser', 's_template')) {
			$this->conf['showPageBrowser'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showPageBrowser', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxRecords', 's_template')) {
			$this->conf['maxRecords'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxRecords', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxPages', 's_template')) {
			$this->conf['maxPages'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxPages', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'basketPid', 's_template')) {
			$this->conf['basketPid'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'basketPid', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'dontLinkActivePage', 's_template')) {
			$this->conf['pageBrowser.']['dontLinkActivePage'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'dontLinkActivePage', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showFirstLast', 's_template')) {
			$this->conf['pageBrowser.']['showFirstLast'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showFirstLast', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showRange', 's_template')) {
			$this->conf['pageBrowser.']['showRange'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showRange', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showItemCount', 's_template')) {
			$this->conf['pageBrowser.']['showItemCount'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showItemCount', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hscText', 's_template')) {
			$this->conf['pageBrowser.']['hscText'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hscText', 's_template');
		}
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template', 's_template') && file_exists($this->templateFolder . $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template', 's_template'))) {
			$this->conf['templateFile'] = $this->templateFolder . $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template', 's_template');
			if ($this->cObj->fileResource($this->conf['templateFile'])) {
				$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
			}
		}

		$accessible = FALSE;
		$tmpCategory = NULL;
		if ($this->piVars['catUid']) {
			$tmpCategory = t3lib_div::makeinstance('tx_commerce_category');
			$this->cat = (int) $this->piVars['catUid'];
			$tmpCategory->init($this->cat, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
			$accessible = $tmpCategory->isAccessible();
		}

			// Validate given catUid, if it's given and accessible
		if (!$this->piVars['catUid'] || !$accessible) {
			$tmpCategory = t3lib_div::makeinstance('tx_commerce_category');
			$this->cat = (int) $this->master_cat;
			$tmpCategory->init($this->cat, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
		}
		if (!isset($this->piVars['catUid'])) {
			$this->piVars['catUid'] = $this->master_cat;
		}
		if (is_object($tmpCategory)) {
		$tmpCategory->loadData();
		}
		$this->category = $tmpCategory;

		$categorySubproducts = $this->category->getProductUids();

		/** @var tslib_fe $frontend */
		$frontend = & $GLOBALS['TSFE'];

		if ((!$this->conf['singleProduct']) && ((int)$this->piVars['showUid'] > 0) && (!$GLOBALS['TSFE']->beUserLogin)) {
			if (is_array($categorySubproducts)) {
				if (!in_array($this->piVars['showUid'], $categorySubproducts)) {
					$categoryAllSubproducts = $this->category->getAllProducts(PHP_INT_MAX);
					if (!in_array((int)$this->piVars['showUid'], $categoryAllSubproducts)) {
							// The requested product is not beblow the selected category
							// So exit with page not found
						$frontend->pageNotFoundAndExit($this->pi_getLL('error.productNotFound', 'Product not found', 1));
					}
				}
			} else {
				$categoryAllSubproducts = $this->category->getAllProducts(PHP_INT_MAX);
				if (!in_array($this->piVars['showUid'], $categoryAllSubproducts)) {
						// The requested product is not beblow the selected category
						// So exit with page not found
					$frontend->pageNotFoundAndExit($this->pi_getLL('error.productNotFound', 'Product not found', 1));
				}
			}
		}

		if (($this->piVars['catUid']) && ($this->conf['checkCategoryTree'] == 1)) {
				// Validate given CAT UID, if is below master_cat
			$this->masterCategoryObj = t3lib_div::makeinstance('tx_commerce_category');
			$this->masterCategoryObj->init($this->master_cat, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
			$this->masterCategoryObj->loadData();
			$masterCategorySubCategories = $this->masterCategoryObj->get_rec_child_categories_uidlist();
			if (in_array($this->piVars['catUid'], $masterCategorySubCategories)) {
				$this->cat = (int)$this->piVars['catUid'];
			} else {
					// Wrong UID, so start with page not found
				$frontend->pageNotFoundAndExit($this->pi_getLL('error.categoryNotFound', 'Product not found', 1));
			}
		} elseif (!isset($this->piVars['catUid'])) {
			$this->cat = (int) $this->master_cat;
		}

		if ($this->cat <> $this->category->getUid()) {
				// Only, if the category has been changed
			unset($this->category);
			$this->category = t3lib_div::makeinstance('tx_commerce_category');
			$this->category->init($this->cat, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
			$this->category->loadData();
		}

		$this->internal['results_at_a_time'] = $this->conf['maxRecords'];
		$this->internal['maxPages'] = $this->conf['maxPages'];

			// Going the long way ??? Just for list view
		$long = 1;
		switch ($this->handle) {
			case 'singleView':
				if ($this->initSingleView($this->piVars['showUid'])) {
					$long = 0;
				}
			break;
		}

		if ($this->cat > 0) {
			$this->category_array = $this->category->return_assoc_array();

			$catConf = $this->category->getCategoryTSconfig();
			if (is_array($catConf['catTS.'])) {
				$this->conf = t3lib_div::array_merge_recursive_overrule($this->conf, $catConf['catTS.']);
			}

			if ($long) {
				$this->category->setPageTitle();
				$this->category->get_child_categories();
				if ($this->conf['groupProductsByCategory']) {
					$this->category_products = $this->category->getAllProducts(0);
				} elseif ($this->conf['showProductsRecLevel']) {
					$this->category_products = $this->category->getAllProducts($this->conf['showProductsRecLevel']);
				} else {
					$this->category_products = $this->category->getAllProducts(0);
				}
				if ($this->conf['useStockHandling'] == 1) {
					$this->category_products = tx_commerce_div::removeNoStockProducts($this->category_products, $this->conf['products.']['showWithNoStock']);
				}
				$this->internal['res_count'] = count($this->category_products);
			}
		} else {
			$this->content = $this->cObj->stdWrap($this->conf['emptyCOA'], $this->conf['emptyCOA.']);
			$this->handle = FALSE;
		}

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi1/class.tx_commerce_pi1.php']['postInit'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi1/class.tx_commerce_pi1.php']['postInit'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postInit')) {
				$hookObj->postInit($this);
			}
		}
	}

	/**
	 * Main function called by insert plugin
	 *
	 * @param string $content Content
	 * @param string $conf Configuration
	 * @return string HTML-Content
	 */
	public function main($content, $conf) {
			// If product or categorie is inserted by insert record use uid from insert record cObj
		if (!empty($conf['insertRecord'])) {
			if ($conf['insertRecord'] == 'products') {
				$this->piVars['showUid'] = $this->cObj->data['uid'];
				$this->piVars['catUid'] = $this->cObj->data['categories'];
			} else {
				$this->piVars['catUid'] = $this->cObj->data['uid'];
			}
		}

		$this->init($conf);

			// Get the template
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

		$this->template = array();
		$this->markerArray = array();

		if ($this->handle == 'singleView') {
			$this->content = $this->makeSingleView();
		} elseif ($this->handle == 'listView') {
			$this->content = $this->makeListView($this->cat);
		}

		return $this->conf['wrapInBaseClass'] ? $this->pi_wrapInBaseClass($this->content) : $this->content;
	}

	/**
	 * Init the singleView for one product
	 *
	 * @param integer $prodID ProductID for single view
	 * @return boolean
	 */
	public function initSingleView($prodID) {
		$prodID = intval($prodID);

		if ($prodID > 0) {
			/** @var t3lib_db $database */
			$database = $GLOBALS['TYPO3_DB'];

				// Get not localized product
			$mainProductRes = $database->exec_SELECTquery('l18n_parent', 'tx_commerce_products', 'uid=' . $prodID);
			if ($database->sql_num_rows($mainProductRes) == 1 AND $row = $database->sql_fetch_assoc($mainProductRes) AND $row['l18n_parent'] != 0) {
				$prodID = $row['l18n_parent'];
			}

			$this->product = t3lib_div::makeInstance('tx_commerce_product');
			$this->product->init($prodID, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
			$this->product->loadData();

			if ($this->product->isAccessible()) {
				$this->select_attributes = $this->product->get_attributes(array(ATTRIB_SELECTOR));
				$this->product_attributes = $this->product->get_attributes(array(ATTRIB_PRODUCT));
				$this->can_attributes = $this->product->get_attributes(array(ATTRIB_CAN));
				$this->shall_attributes = $this->product->get_attributes(array(ATTRIB_SHAL));
				$this->product_array = $this->product->returnAssocArray();
				$this->product->loadArticles();

					// Check if the product was inserted as plugin on a page,
					// or if it was rendered as a leaf from the category view
				if ($this->conf['singleView.']['renderProductNameAsPageTitle'] == 1) {
					$this->product->setPageTitle();
				} elseif (($this->conf['singleView.']['renderProductNameAsPageTitle'] == 2) && ($this->singleViewAsPlugin === FALSE)) {
					$this->product->setPageTitle();
				}

				$this->master_cat = $this->product->get_masterparent_categorie();

					// Write the current page to the session to have a back to last product link
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_commerce_lastproducturl', $this->pi_linkTP_keepPIvars_url());
				return TRUE;
			} else {
					// If product ist not valid (url manipulation) go to listview
				$this->handle = 'listView';
			}
		}
		return FALSE;
	}

	/**
	 * Render the single view for the current products
	 *
	 * @param tx_commerce_product $prodObj Product object
	 * @param tx_commerce_category $catObj Category object
	 * @param string $subpartName A name of a subpart
	 * @param string $subpartNameNostock A name of a subpart for showing id product with no stock
	 * @return string The content for a single product
	 */
	public function renderSingleView($prodObj, $catObj, $subpartName, $subpartNameNostock) {
		$template = $this->cObj->getSubpart($this->templateCode, $subpartName);

		if ($this->conf['useStockHandling'] == 1 AND $prodObj->hasStock() === FALSE) {
			$typoScript = $this->conf['singleView.']['products.']['nostock.'];
			$tempTemplate = $this->cObj->getSubpart($this->templateCode, $subpartNameNostock);
			if ($tempTemplate != '') {
				$template = $tempTemplate;
			}
		} else {
			$typoScript = $this->conf['singleView.']['products.'];
		}

		$relatedProductsParentSubpart = $this->cObj->getSubpart($template, '###' . strtoupper($this->conf['templateMarker.']['relatedProductList']) . '###');
		$content = $this->renderProduct($prodObj, $template, $typoScript, $this->conf['templateMarker.']['basketSingleView.'], $this->conf['templateMarker.']['basketSingleViewMarker']);

			// Get category data
		$catObj->loadData();

			// Render it in the content
		$category = $this->renderCategory($catObj, '###' . strtoupper($this->conf['templateMarker.']['categorySingleViewMarker']) . '###', $this->conf['singleView.']['products.']['categories.'], 'ITEM', $content);

			// Substitude the subpart
		$content = $this->cObj->substituteSubpart($content, '###' . strtoupper($this->conf['templateMarker.']['categorySingleViewMarker']) . '###', $category);

			// Build the link to the category
		$linkContent = $this->cObj->getSubpart($content, '###CATEGORY_ITEM_DETAILLINK###');
		if ($linkContent) {
			$link = $this->pi_linkTP($linkContent, array('tx_commerce_pi1[catUid]' => $catObj->getUid()), TRUE);
		} else {
			$link = '';
		}
		$content = $this->cObj->substituteSubpart($content, '###CATEGORY_ITEM_DETAILLINK###', $link);

			// Render related products
		$relatedProducts = $prodObj->getRelatedProducts();
		$relatedProductsSubpart = '';
		$relatedProductsSubpartTemplateStock = $this->cObj->getSubpart($relatedProductsParentSubpart, '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '###');
		$relatedProductsSubpartTemplateNoStock = $this->cObj->getSubpart($relatedProductsParentSubpart, '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '_NOSTOCK###');
		foreach ($relatedProducts as $relatedProduct) {
			if ($this->conf['useStockHandling'] == 1 AND $prodObj->hasStock() === FALSE) {
				$localTemplate = $relatedProductsSubpartTemplateNoStock;
				$localTypoScript = $this->conf['singleView.']['products.']['relatedProducts.']['nostock.'];
			} else {
				$localTemplate = $relatedProductsSubpartTemplateStock;
				$localTypoScript = $this->conf['singleView.']['products.']['relatedProducts.'];
			}
				// Related products don't have articles here, to save render time
			$relatedProductsSubpart .= $this->renderProduct($relatedProduct, $localTemplate, $localTypoScript, '###no#artikel#subpart#here###');
		}

			// Additional headers for "related products" are overwritten by subparts
			// So we will change this here. In thought of sorting, we can't split the entries.
		if ($relatedProductsSubpart != '') {
				// Set first subpart empty
			$contentTmp = $this->cObj->substituteSubpart($content, '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '###', '');
				// Fill the second with our data
			$content = $this->cObj->substituteSubpart($contentTmp, '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '_NOSTOCK###', $relatedProductsSubpart);
		} else {
				// When we have no related products, then overwrite the header
			$content = $this->cObj->substituteSubpart($content, '###' . strtoupper($this->conf['templateMarker.']['relatedProductList']) . '###', '');
		}

		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['singleview'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['singleview'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		$markerArray = array();
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'additionalMarker')) {
				$markerArray = $hookObj->additionalMarker($markerArray, $this);
			}
		}

		$content = $this->substituteMarkerArrayNoCached($content, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Makes the rendering for all articles for a given product
	 * Renders different view, based on viewKind and number of articles
	 *
	 * @param string $viewKind Kind of view for choosing the right template
	 * @param array $conf TSconfig for handling the articles
	 * @param tx_commerce_product $prod The parent product for returning articles
	 * @param array|string $templateMarkerArray Current template marker array
	 * @param string $template Template text
	 * @return string the content for a single product
	 */
	public function makeArticleView($viewKind, $conf = array(), $prod, $templateMarkerArray = '', $template = '') {
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['articleview'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['articleview'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		$count = is_array($prod->articles_uids) ? count($prod->articles_uids) : FALSE;

			// do nothing if no articles, BE-user-error, should not happen
		if (strlen($template) < 1) {
			$template = $this->templateCode;
		}

		$templateMarker = array();
		$templateMarkerNostock = array();
		$templateMarkerMoreThanMax = array();
		if (is_array($templateMarkerArray)) {
			foreach ($templateMarkerArray as $v) {
				$templateMarker[] = '###' . strtoupper($v) . '###';
				$templateMarkerNostock[] = '###' . strtoupper($v) . '_NOSTOCK###';
				$templateMarkerMoreThanMax[] = '###' . strtoupper($v) . '_MORETHANMAX###';
			}
		} else {
			$templateMarker[] = '###' . strtoupper($this->conf['templateMarker.'][$viewKind . '_productArticleList']) . '###';
			$templateMarker[] = '###' . strtoupper($this->conf['templateMarker.'][$viewKind . '_productArticleList2']) . '###';
			$templateMarkerNostock[] = '###' . strtoupper($this->conf['templateMarker.'][$viewKind . '_productArticleList']) . '_NOSTOCK###';
			$templateMarkerNostock[] = '###' . strtoupper($this->conf['templateMarker.'][$viewKind . '_productArticleList2']) . '_NOSTOCK###';
		}

		$content = '';
		$markerArray = array();
		if ($prod->getRenderMaxArticles() > $prod->getNumberOfArticles()) {
				// Only if the number of articles is smaller than defined
			$templateAttrSelectorDropdown = $this->cObj->getSubpart($this->templateCode, '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorDropdown']) . '###');
			$templateAttrSelectorDropdownItem = $this->cObj->getSubpart($templateAttrSelectorDropdown, '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorDropdown']) . '_ITEM###');
			$templateAttrSelectorRadiobutton = $this->cObj->getSubpart($this->templateCode, '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorRadiobutton']) . '###');
			$templateAttrSelectorRadiobuttonItem = $this->cObj->getSubpart($templateAttrSelectorRadiobutton, '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorRadiobutton']) . '_ITEM###');

			$templateCount = count($templateMarker);

			$templateAttr = array();
			if (is_array($this->conf['templateMarker.'][$viewKind . '_selectAttributes.'])) {
				foreach ($this->conf['templateMarker.'][$viewKind . '_selectAttributes.'] as $oneMarker) {
					$templateMarkerAttr = '###' . strtoupper($oneMarker) . '###';
					$tCode = $this->cObj->getSubpart($this->templateCode, $templateMarkerAttr);
					if ($tCode) {
						$templateAttr[] = $tCode;
					}
				}
			} elseif ($this->conf['templateMarker.'][$viewKind . '_selectAttributes']) {
				$templateMarkerAttr = '###' . strtoupper($this->conf['templateMarker.'][$viewKind . '_selectAttributes']) . '###';
				$templateAttr[] = $this->cObj->getSubpart($this->templateCode, $templateMarkerAttr);
			}

			$countTemplateInterations = count($templateAttr);
			if ($this->conf['showHiddenValues'] == 1) {
				$showHiddenValues = TRUE;
			} else {
				$showHiddenValues = FALSE;
			}

				// Parse piVars for values and names of selected attributes
				// define $arrAttSubmit for finding the right article later
			$arrAttSubmit = array();
			foreach ($this->piVars as $key => $val) {
				if (strstr($key, 'attsel_') && $val) {
						// set only if it is the selected product - for listing mode
					if ($this->piVars['changedProductUid'] == $prod->getUid() || $this->piVars['showUid'] == $prod->getUid()) {
						$arrAttSubmit[intval(substr($key, 7))] = intval($val);
						if ($this->piVars['attList_' . $prod->getUid() . '_changed'] == intval(substr($key, 7))) {
							break;
						}
					}
				}
			}
			if (is_array($arrAttSubmit)) {
				$attributeMatrix = $prod->getSelectAttributeValueMatrix($arrAttSubmit);
			} else {
				$attributeMatrix = $prod->getSelectAttributeValueMatrix($arrAttSubmit);
			}

			if ($this->conf['allArticles'] || $count == 1) {
				for ($i = 0; $i < $count; $i ++) {
					$attributeArray = $prod->getAttributeMatrix(array($prod->articles_uids[$i]), $this->select_attributes, $showHiddenValues);

						$attCode = '';
					if (is_array($attributeArray)) {
						$ct = 0;
						foreach ($attributeArray as $attribute_uid => $myAttribute) {
							$attributeObj = t3lib_div::makeInstance('tx_commerce_attribute');
							$attributeObj->init($attribute_uid, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
							$attributeObj->loadData();
							$markerArray['###SELECT_ATTRIBUTES_TITLE###'] = $myAttribute['title'];
							$markerArray['###SELECT_ATTRIBUTES_ICON###'] = $myAttribute['icon'];
							list($k, $v) = each($myAttribute['values']);
							if (is_array($v) && isset($v['value']) && $v['value'] != '') {
								$v = $v['value'];
							}
							$markerArray['###SELECT_ATTRIBUTES_VALUE###'] = $v;
							$markerArray['###SELECT_ATTRIBUTES_UNIT###'] = $myAttribute['unit'];
							$numTemplate = $ct % $countTemplateInterations;
							$attCode .= $this->substituteMarkerArrayNoCached($templateAttr[$numTemplate], $markerArray, array());
							$ct ++;
						}
					}
					$markerArray = (array) $this->getArticleMarker($prod->articles[$prod->articles_uids[$i]]);
					$markerArray['SUBPART_ARTICLE_ATTRIBUTES'] = $this->cObj->stdWrap(
						$this->makeArticleAttributList($prod, array($prod->articles_uids[$i])),
						$this->conf['singleView.']['articleAttributesList.']
					);
					$markerArray['ARTICLE_SELECT_ATTRIBUTES'] = $this->cObj->stdWrap(
						$attCode,
						$this->conf['singleView.']['articleAttributesSelectList.']
					);

					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'additionalMarker')) {
							$markerArray = (array) $hookObj->additionalMarker($markerArray, $this, $prod->articles[$prod->articles_uids[$i]]);
						}
					}
					$template_att = $this->cObj->getSubpart($template, $templateMarker[($i % $templateCount)]);
					/** @var tx_commerce_article $article */
					$article = $prod->articles[$prod->articles_uids[$i]];
					if ($this->conf['useStockHandling'] == 1 and $article->getStock() <= 0) {
						$tempTemplate = $this->cObj->getSubpart($template, $templateMarkerNostock[($i % $templateCount)]);
						if ($tempTemplate != '') {
							$template_att = $tempTemplate;
						}
					}

					$content .= $this->cObj->substituteMarkerArray($template_att, $markerArray, '###|###', 1);
				}
			} else {
				$sortedAttributeArray = array();
				$i = 0;
				foreach ($arrAttSubmit as $attrUid => $attrValUid) {
					$sortedAttributeArray[$i]['AttributeUid'] = $attrUid;
					$sortedAttributeArray[$i]['AttributeValue'] = $attrValUid;
					$i++;
				}

				$artId = array_shift($prod->get_Articles_by_AttributeArray($sortedAttributeArray));
				$attCode = '';
				if (is_array($attributeMatrix)) {
					$getVarList = array('catUid','showUid','pointer');
					$getVars = array();
					foreach ($getVarList as $getVar) {
						if (isset($this->piVars[$getVar])) {
							$getVars[$this->prefixId . '[' . $getVar . ']'] = $this->piVars[$getVar];
						}
					}
						// Makes pi1 a user int so form values are updated as one selects an attribute
					$getVars['commerce_pi1_user_int'] = 1;
					$attCode = '<form name="attList_' . $prod->getUid() . '" id="attList_' . $prod->getUid() . '" action="' . $this->pi_getPageLink($GLOBALS['TSFE']->id, '_self', $getVars) . '#att"  method="post">' .
						'<input type="hidden" name="' . $this->prefixId . '[changedProductUid]" value="' . $prod->getUid() . '" />' .
						'<input type="hidden" name="' . $this->prefixId . '[attList_' . $prod->getUid() . '_changed]" id="attList_' . $prod->getUid() . '_changed" value="1" />' .
						'<input type="hidden" name="tx_commerce_pi1[catUid]" value="' . $this->piVars['catUid'] . '" />';
					$markerArray = array();
					foreach ($attributeMatrix as $attrUid => $values) {
						$attributeObj = t3lib_div::makeInstance('tx_commerce_attribute');
						$attributeObj->init($attrUid, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
						$attributeObj->loadData();
							// disable the icon mode by default
						$iconMode = FALSE;

							// if the icon mode is enabled in TS check if the iconMode is also enabled for this attribute
						if ($this->conf[$this->handle . '.']['products.']['productAttributes.']['iconMode'] == '1') {
							$iconMode = $attributeObj->isIconmode();
						}
						if ($iconMode) {
							$templateAttrSelector = $templateAttrSelectorRadiobutton;
							$templateAttrSelectorItem = $templateAttrSelectorRadiobuttonItem;
						} else {
							$templateAttrSelector = $templateAttrSelectorDropdown;
							$templateAttrSelectorItem = $templateAttrSelectorDropdownItem;
						}

						$markerArray['###SELECT_ATTRIBUTES_TITLE###'] = $attributeObj->get_title();
						$markerArray['###SELECT_ATTRIBUTES_ON_CHANGE###'] = 'document.getElementById(\'attList_' . $prod->getUid() .
							'_changed\').value = ' . $attrUid . ';document.getElementById(\'attList_' . $prod->getUid() . '\').submit();';
						$markerArray['###SELECT_ATTRIBUTES_HTML_ELEMENT_KEY###'] = $this->prefixId . '_' . $attrUid;
						$markerArray['###SELECT_ATTRIBUTES_HTML_ELEMENT_NAME###'] = $this->prefixId . '[attsel_' . $attrUid . ']';
							// @deprecated set to '' for old installation, will be removed in the next release
						$markerArray['###SELECT_ATTRIBUTES_ITEM_TEXT_ALL###'] = '';
						$markerArray['###SELECT_ATTRIBUTES_UNIT###'] = $attributeObj->get_unit();

						$itemsContent = '';
						$i = 1;
						$attributeValues = $attributeObj->get_all_values(TRUE, $prod);
						/** @var tx_commerce_attribute_value $val */
						foreach ($attributeValues as $val) {
							$markerArrayItem = $markerArray;
							$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_VALUE###'] = $val->getUid();
							$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_NAME###'] = $val->getValue();
							$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_ICON###'] = $this->renderValue(
								$val->getIcon(),
								'IMAGE',
								$this->conf[$this->handle . '.']['products.']['productAttributes.']['fields.']['icon.']
							);
							if ($values[$val->getUid()] == 'selected') {
								$attributeArray[$attrUid] = $val->getUid();
								if ($iconMode) {
									$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_STATUS###'] = 'checked="checked"';
								} else {
									$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_STATUS###'] = 'selected="selected"';
								}
								$i ++;
							} elseif ($values[$val->getUid()] == 'disabled') {
								$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_STATUS###'] = 'disabled="disabled"';
							} else {
								$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_STATUS###'] = '';
							}

								// @deprecated, used for backwards compatibility
							$markerArrayItem['###SELECT_ATTRIBUTES_VALUE_SELECTED###'] = $markerArrayItem['###SELECT_ATTRIBUTES_VALUE_STATUS###'];
							foreach ($hookObjectsArr as $hookObj) {
								if (method_exists($hookObj, 'additionalAttributeMarker')) {
									$markerArrayItem = $hookObj->additionalAttributeMarker($markerArrayItem, $this, $val->getUid());
								}
							}
							$itemsContent .= $this->cObj->substituteMarkerArray($templateAttrSelectorItem, $markerArrayItem);
							$i ++;
						}
						$attributeContent = $this->cObj->substituteMarkerArray($templateAttrSelector, $markerArray);

						if ($iconMode) {
							$attCode .= $this->cObj->substituteSubpart($attributeContent, '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorRadiobutton']) . '_ITEM###', $itemsContent);
						} else {
							$attCode .= $this->cObj->substituteSubpart($attributeContent, '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorDropdown']) . '_ITEM###', $itemsContent);
						}
					}
					$attCode .= '</form>';
				}

				$markerArray = (array) $this->getArticleMarker($prod->articles[$artId]);
				$markerArray['SUBPART_ARTICLE_ATTRIBUTES'] = $this->makeArticleAttributList($prod, array($artId));
				$markerArray['ARTICLE_SELECT_ATTRIBUTES'] = $attCode;

				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'additionalMarker')) {
						$markerArray = (array) $hookObj->additionalMarker($markerArray, $this, $prod->articles[$artId]);
					}
				}

				$template_att = $this->cObj->getSubpart($template, $templateMarker[0]);
				/** @var tx_commerce_article $article */
				$article = $prod->articles[$artId];
				if ($this->conf['useStockHandling'] == 1 and $article->getStock() <= 0) {
					$tempTemplate = $this->cObj->getSubpart($template, $templateMarkerNostock[0]);
					if ($tempTemplate != '') {
						$template_att = $tempTemplate;
					}
				}

				$content .= $this->cObj->substituteMarkerArray($template_att, $markerArray, '###|###', 1);
			}
		} else {
				// Special Marker and rendering when more articles are existing than are allowed to render
				// @see tx_commerce_products->getNumberOfArticles
			$localContent = $this->cObj->getSubpart($template, reset($templateMarkerMoreThanMax));

			$cat = $this->cat;
			$prod_cats = $prod->getParentCategories();
			if (! in_array($cat, $prod_cats, FALSE)) {
				$cat = $prod_cats[0];
			}

			/**
			 * Generate TypoLink Configuration and ad to fields by addTypoLinkToTs
			 */
			if ($this->conf['overridePid']) {
				$typoLinkConf['parameter'] = $this->conf['overridePid'];
			} else {
				$typoLinkConf['parameter'] = $this->pid;
			}
			$typoLinkConf['useCacheHash'] = 1;
			$typoLinkConf['additionalParams'] = ini_get('arg_separator.output') . $this->prefixId . '[showUid]=' . $prod->getUid();
			$typoLinkConf['additionalParams'] .= ini_get('arg_separator.output') . $this->prefixId . '[catUid]=' . $cat;

			if ($this->basketHashValue) {
				$typoLinkConf['additionalParams'] .= ini_get('arg_separator.output') . $this->prefixId . '[basketHashValue]=' . $this->basketHashValue;
			}
			$markerArray['LINKTOPRODUCT'] = $this->cObj->typoLink($this->pi_getLL('lang_toproduct'), $typoLinkConf);
			$content = $this->cObj->substituteMarkerArray($localContent, $markerArray, '###|###', 1);

			$markerArray = array();
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'additionalMarkerMakeArticleView')) {
					$markerArray = (array) $hookObj->additionalMarkerMakeArticleView($markerArray, $prod, $this);
				}
			}
		}
		$content = $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi1/class.tx_commerce_pi1.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi1/class.tx_commerce_pi1.php']);
}

?>
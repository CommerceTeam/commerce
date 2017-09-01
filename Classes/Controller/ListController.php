<?php
namespace CommerceTeam\Commerce\Controller;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Domain\Model\Category;
use CommerceTeam\Commerce\Domain\Model\Product;
use CommerceTeam\Commerce\Factory\HookFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Product list and single view.
 */
class ListController extends BaseController
{
    /**
     * Same as class name.
     *
     * @var string
     */
    public $prefixId = 'tx_commerce_pi1';

    /**
     * Whether to check chash or not.
     *
     * @var bool
     */
    public $pi_checkCHash = true;

    /**
     * Will be set to TRUE, if the plugin was inserted as plugin to show
     * one produkt, or one product was set via TypoScript.
     *
     * @var bool
     */
    public $singleViewAsPlugin = false;

    /**
     * Master category uid.
     *
     * @var int
     */
    public $master_cat;

    /**
     * Master category model.
     *
     * @var Category
     */
    public $masterCategoryObj;

    /**
     * Categories.
     *
     * @var array
     */
    public $category_array = [];

    /**
     * Template folder.
     *
     * @var string
     */
    public $templateFolder = 'uploads/tx_commerce/';

    /**
     * Markers.
     *
     * @var array
     */
    public $markerArray = [];

    /**
     * Do not make protected to be able to handle
     * different behaviour in a hook.
     *
     * @var string
     */
    public $content = '';

    /**
     * Products.
     *
     * @var array
     */
    public $product_array = [];

    /**
     * Inits the main params for using in the script.
     *
     * @param array $conf Configuration
     *
     * @return void
     */
    public function init(array $conf = [])
    {
        parent::init($conf);

        // Merge default vars, if other prefix_id
        if ($this->prefixId != 'tx_commerce_pi1') {
            $generellRequestVars = GeneralUtility::_GP('tx_commerce');
            if (is_array($generellRequestVars)) {
                foreach ($generellRequestVars as $key => $value) {
                    if (empty($this->piVars[$key])) {
                        $this->piVars[$key] = $value;
                    }
                }
            }
        }

        $hooks = HookFactory::getHooks('Controller/ListController', 'init');
        foreach ($hooks as $hook) {
            if (method_exists($hook, 'preInit')) {
                $hook->preInit($this);
            }
        }

        $this->conf['singleProduct'] = (int) $this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'],
            'product_id',
            's_product'
        );

        if ($this->conf['singleProduct'] > 0) {
            // product UID was set by Plugin or TS
            $this->singleViewAsPlugin = true;
        }

        // Unset variable, if smaller than 0, as -1 is returend
        // when no product is selcted in form.
        if ($this->conf['singleProduct'] < 0) {
            $this->conf['singleProduct'] = false;
        }

        $this->piVars['showUid'] = intval($this->piVars['showUid']) ?: 0;
        $this->piVars['showUid'] = $this->piVars['showUid'] ?: $this->conf['singleProduct'];
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
        } else {
            $this->master_cat = str_replace('tx_commerce_categories_', '', $this->master_cat);
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listPid', 's_template')) {
            $this->conf['listPid'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listPid', 's_template');
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'singlePid', 's_template')) {
            $this->conf['singlePid'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'singlePid',
                's_template'
            );
        }
        // @deprecated flexform value to be removed in 5.0.0
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'displayPID', 's_template')) {
            $this->conf['overridePid'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'displayPID',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'numberOfTopproducts', 's_product')) {
            $this->conf['numberOfTopproducts'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'numberOfTopproducts',
                's_product'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showPageBrowser', 's_template')) {
            $this->conf['showPageBrowser'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'showPageBrowser',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxRecords', 's_template')) {
            $this->conf['maxRecords'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'maxRecords',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxPages', 's_template')) {
            $this->conf['maxPages'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxPages', 's_template');
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'basketPid', 's_template')) {
            $this->conf['basketPid'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'basketPid',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'dontLinkActivePage', 's_template')) {
            $this->conf['pageBrowser.']['dontLinkActivePage'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'dontLinkActivePage',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showFirstLast', 's_template')) {
            $this->conf['pageBrowser.']['showFirstLast'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'showFirstLast',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showRange', 's_template')) {
            $this->conf['pageBrowser.']['showRange'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'showRange',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showItemCount', 's_template')) {
            $this->conf['pageBrowser.']['showItemCount'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'showItemCount',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hscText', 's_template')) {
            $this->conf['pageBrowser.']['hscText'] = $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'hscText',
                's_template'
            );
        }
        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template', 's_template')
            && file_exists($this->templateFolder . $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'template',
                's_template'
            ))
        ) {
            $this->conf['templateFile'] = $this->templateFolder . $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'template',
                's_template'
            );
            $templateCode = (string) file_get_contents(
                $this->getTypoScriptFrontendController()->tmpl->getFileName($this->conf['templateFile'])
            );
            if ($templateCode) {
                $this->templateCode = $templateCode;
            }
        }

        $accessible = false;
        /**
         * Temporary category.
         *
         * @var Category
         */
        $tmpCategory = null;
        if ($this->piVars['catUid']) {
            $this->cat = (int) $this->piVars['catUid'];
            $tmpCategory = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $this->cat,
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $accessible = $tmpCategory->isAccessible();
        }

        // Validate given catUid, if it's given and accessible
        if (!$this->piVars['catUid'] || !$accessible) {
            $this->cat = (int) $this->master_cat;
            $tmpCategory = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $this->cat,
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
        }
        if (!isset($this->piVars['catUid'])) {
            $this->piVars['catUid'] = $this->master_cat;
        }
        if (is_object($tmpCategory)) {
            $tmpCategory->loadData();
        }
        $this->category = $tmpCategory;

        $categorySubproducts = $this->category->getProductUids();

        $frontend = $this->getTypoScriptFrontendController();

        if (!$this->conf['singleProduct']
            && (int) $this->piVars['showUid']
            && !$this->getTypoScriptFrontendController()->beUserLogin
        ) {
            if (is_array($categorySubproducts)) {
                if (!in_array($this->piVars['showUid'], $categorySubproducts)) {
                    $categoryAllSubproducts = $this->category->getProducts();
                    if (!in_array((int) $this->piVars['showUid'], $categoryAllSubproducts)) {
                        // The requested product is not beblow the selected category
                        // So exit with page not found
                        $frontend->pageNotFoundAndExit(
                            $this->pi_getLL('error.productNotFound', 'Product not found', 1)
                        );
                    }
                }
            } else {
                $categoryAllSubproducts = $this->category->getProducts();
                if (!in_array($this->piVars['showUid'], $categoryAllSubproducts)) {
                    // The requested product is not beblow the selected category
                    // So exit with page not found
                    $frontend->pageNotFoundAndExit($this->pi_getLL('error.productNotFound', 'Product not found', 1));
                }
            }
        }

        if (($this->piVars['catUid']) && ($this->conf['checkCategoryTree'] == 1)) {
            // Validate given CAT UID, if is below master_cat
            $this->masterCategoryObj = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $this->master_cat,
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $this->masterCategoryObj->loadData();
            /*
             * Master category
             *
             * @var Category masterCategoryObj
             */
            $masterCategorySubCategories = $this->masterCategoryObj->getChildCategoriesUidlist();
            if (in_array($this->piVars['catUid'], $masterCategorySubCategories)) {
                $this->cat = (int) $this->piVars['catUid'];
            } else {
                // Wrong UID, so start with page not found
                $frontend->pageNotFoundAndExit($this->pi_getLL('error.categoryNotFound', 'Product not found', 1));
            }
        } elseif (!isset($this->piVars['catUid'])) {
            $this->cat = (int) $this->master_cat;
        }

        if ($this->cat != $this->category->getUid()) {
            // Only, if the category has been changed
            unset($this->category);
            /** @var Category category */
            $this->category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $this->cat,
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $this->category->loadData();
        }

        $this->internal['results_at_a_time'] = $this->conf['maxRecords'];
        $this->internal['maxPages'] = $this->conf['maxPages'];

        // Going the long way ??? Just for list view
        $long = 1;
        switch ($this->handle) {
            case 'singleView':
                if ($this->initSingleView((int) $this->piVars['showUid'])) {
                    $long = 0;
                }
                break;

            default:
        }

        if ($this->cat > 0) {
            $this->category_array = $this->category->returnAssocArray();

            $catConf = $this->category->getTyposcriptConfig();
            if (is_array($catConf['catTS.'])) {
                \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->conf, $catConf['catTS.']);
            }

            if ($long) {
                $this->category->setPageTitle();
                $this->category->getChildCategories();
                if ($this->conf['groupProductsByCategory']) {
                    $this->category_products = $this->category->getProducts(0);
                } elseif ($this->conf['showProductsRecLevel']) {
                    $this->category_products = $this->category->getProducts($this->conf['showProductsRecLevel']);
                } else {
                    $this->category_products = $this->category->getProducts(0);
                }
                if ($this->conf['useStockHandling'] == 1) {
                    $this->category_products = \CommerceTeam\Commerce\Utility\GeneralUtility::removeNoStockProducts(
                        $this->category_products,
                        $this->conf['products.']['showWithNoStock']
                    );
                }
                $this->internal['res_count'] = count($this->category_products);
            }
        } else {
            $this->content = $this->cObj->stdWrap($this->conf['emptyCOA'], $this->conf['emptyCOA.']);
            $this->handle = false;
        }

        foreach ($hooks as $hook) {
            if (method_exists($hook, 'postInit')) {
                $hook->postInit($this);
            }
        }
    }

    /**
     * Main function called by insert plugin.
     *
     * @param string $content Content
     * @param array $conf Configuration
     *
     * @return string HTML-Content
     */
    public function main($content, $conf)
    {
        // If product or categorie is inserted by insert record use uid
        // from insert record cObj
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
        $this->templateCode = (string) file_get_contents(
            $this->getTypoScriptFrontendController()->tmpl->getFileName($this->conf['templateFile'])
        );

        $this->template = [];
        $this->markerArray = [];

        switch ($this->handle) {
            case 'singleView':
                $subpartName = '###' . strtoupper($this->conf['templateMarker.']['productView']) . '###';
                $subpartNameNostock = '###' . strtoupper($this->conf['templateMarker.']['productView']) . '_NOSTOCK###';
                $this->content = $this->renderSingleView(
                    $this->product,
                    $this->category,
                    $subpartName,
                    $subpartNameNostock
                );
                $this->content = $this->templateService->substituteMarkerArray($this->content, $this->languageMarker);
                $this->content = $this->templateService->substituteMarkerArray(
                    $this->content,
                    $this->addFormMarker([]),
                    '###|###',
                    1
                );
                $this->getFrontendUser()->setKey(
                    'ses',
                    'tx_commerce_lastproducturl',
                    $this->pi_linkTP_keepPIvars_url([], 1)
                );
                break;

            case 'listView':
                $this->content = $this->makeListView();
                break;

            default:
        }

        $content .= $this->content;

        return $this->conf['wrapInBaseClass'] ? $this->pi_wrapInBaseClass($content) : $content;
    }

    /**
     * Init the singleView for one product.
     *
     * @param int $productId ProductID for single view
     *
     * @return bool
     */
    public function initSingleView($productId)
    {
        $productId = (int) $productId;

        $result = false;
        if ($productId > 0) {
            /** @var \CommerceTeam\Commerce\Domain\Repository\ProductRepository $productRepository */
            $productRepository = $this->getObjectManager()->get(
                \CommerceTeam\Commerce\Domain\Repository\ProductRepository::class
            );
            $row = $productRepository->findByUid($productId);
            if ($row['l18n_parent'] != 0) {
                $productId = $row['l18n_parent'];
            }

            $this->product = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Product::class,
                $productId,
                $this->getTypoScriptFrontendController()->sys_language_uid
            );
            $this->product->loadData();

            if ($this->product->isAccessible()) {
                $this->selectAttributes = $this->product->getAttributes([ATTRIB_SELECTOR]);
                $this->product_attributes = $this->product->getAttributes([ATTRIB_PRODUCT]);
                $this->can_attributes = $this->product->getAttributes([ATTRIB_CAN]);
                $this->shall_attributes = $this->product->getAttributes([ATTRIB_SHAL]);

                // Check if the product was inserted as plugin on a page,
                // or if it was rendered as a leaf from the category view
                if ($this->conf['singleView.']['renderProductNameAsPageTitle'] == 1) {
                    $this->product->setPageTitle();
                } elseif ($this->conf['singleView.']['renderProductNameAsPageTitle'] == 2
                    && $this->singleViewAsPlugin === false
                ) {
                    $this->product->setPageTitle();
                }

                $this->master_cat = $this->product->getMasterparentCategory();

                // Write the current page to the session to have a back to last product link
                $this->getTypoScriptFrontendController()->fe_user->setKey(
                    'ses',
                    'tx_commerce_lastproducturl',
                    $this->pi_linkTP_keepPIvars_url([], 1)
                );
                $result = true;
            } else {
                // If product ist not valid (url manipulation) go to listview
                $this->handle = 'listView';
            }
        }

        return $result;
    }

    /**
     * Render the single view for the current products.
     *
     * @param Product $product Product object
     * @param Category $category Category object
     * @param string $subpartName Name of a subpart
     * @param string $subpartNameNostock Name of a subpart for product without stock
     *
     * @return string The content for a single product
     */
    public function renderSingleView(Product $product, Category $category, $subpartName, $subpartNameNostock)
    {
        $hooks = HookFactory::getHooks('Controller/CheckoutController', 'renderSingleView');

        $result = null;
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'preRenderSingleView')) {
                $result = $hookObj->preRenderSingleView($product, $category, $subpartName, $subpartNameNostock, $this);
            }
        }
        if ($result) {
            return $result;
        }

        $template = $this->templateService->getSubpart($this->templateCode, $subpartName);
        if ($this->conf['useStockHandling'] == 1 && !$product->hasStock()) {
            $productTypoScript = $this->conf['singleView.']['products.']['nostock.'];
            $noStockTemplate = $this->templateService->getSubpart($this->templateCode, $subpartNameNostock);
            if ($noStockTemplate != '') {
                $template = $noStockTemplate;
            }
        } else {
            $productTypoScript = $this->conf['singleView.']['products.'];
        }

        $content = $this->renderProduct(
            $product,
            $template,
            $productTypoScript,
            $this->conf['templateMarker.']['basketSingleView.'],
            $this->conf['templateMarker.']['basketSingleViewMarker']
        );

        // Get category data
        $category->loadData();

        // Render category for content
        $categorySubpart = $this->renderCategory(
            $category,
            '###' . strtoupper($this->conf['templateMarker.']['categorySingleViewMarker']) . '###',
            $this->conf['singleView.']['products.']['categories.'],
            'ITEM',
            $content
        );

        // Substitute the subpart
        $content = $this->templateService->substituteSubpart(
            $content,
            '###' . strtoupper($this->conf['templateMarker.']['categorySingleViewMarker']) . '###',
            $categorySubpart
        );

        // Build the link to the category
        $categoryLinkContent = $this->templateService->getSubpart($content, '###CATEGORY_ITEM_DETAILLINK###');
        if ($categoryLinkContent) {
            $link = $this->pi_linkTP(
                $categoryLinkContent,
                ['tx_commerce_pi1[catUid]' => $category->getUid()],
                true
            );
        } else {
            $link = '';
        }
        $content = $this->templateService->substituteSubpart($content, '###CATEGORY_ITEM_DETAILLINK###', $link);

        // Render related products
        $relatedProductsSubpart = '';
        $relatedProductsParentSubpart = $this->templateService->getSubpart(
            $template,
            '###' . strtoupper($this->conf['templateMarker.']['relatedProductList']) . '###'
        );
        $relatedProductsSubpartTemplateStock = $this->templateService->getSubpart(
            $relatedProductsParentSubpart,
            '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '###'
        );
        $relatedProductsSubpartTemplateNoStock = $this->templateService->getSubpart(
            $relatedProductsParentSubpart,
            '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '_NOSTOCK###'
        );

        /**
         * Product.
         *
         * @var Product $relatedProduct
         */
        foreach ($product->getRelatedProducts() as $relatedProduct) {
            if ($this->conf['useStockHandling'] == 1 && !$relatedProduct->hasStock()) {
                $localTemplate = $relatedProductsSubpartTemplateNoStock;
                $localTypoScript = $this->conf['singleView.']['products.']['relatedProducts.']['nostock.'];
            } else {
                $localTemplate = $relatedProductsSubpartTemplateStock;
                $localTypoScript = $this->conf['singleView.']['products.']['relatedProducts.'];
            }

            // Related products don't have articles here, to save render time
            $relatedProductsSubpart .= $this->renderProduct(
                $relatedProduct,
                $localTemplate,
                $localTypoScript,
                $this->conf['templateMarker.']['basketSingleView.'],
                $this->conf['templateMarker.']['basketSingleViewMarker']
            );
        }

        // Additional headers for "related products" are overwritten by subparts
        // So we will change this here. In thought of sorting, we can't split entries.
        if ($relatedProductsSubpart != '') {
            // Set first subpart empty
            $content = $this->templateService->substituteSubpart(
                $content,
                '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '###',
                $relatedProductsSubpart
            );
            // Fill the second with our data
            $content = $this->templateService->substituteSubpart(
                $content,
                '###' . strtoupper($this->conf['templateMarker.']['relatedProductSingle']) . '_NOSTOCK###',
                ''
            );
        } else {
            // When we have no related products, then overwrite the header
            $content = $this->templateService->substituteSubpart(
                $content,
                '###' . strtoupper($this->conf['templateMarker.']['relatedProductList']) . '###',
                ''
            );
        }

        $markerArray = [];
        foreach ($hooks as $hookObj) {
            if (method_exists($hookObj, 'additionalMarker')) {
                $markerArray = $hookObj->additionalMarker($markerArray, $this);
            }
        }

        $content = $this->templateService->substituteMarkerArray($content, $markerArray);

        return $content;
    }

    /**
     * Makes the rendering for all articles for a given product
     * Renders different view, based on viewKind and number of articles.
     *
     * @param string $viewKind Kind of view for choosing the right template
     * @param mixed $_ TSconfig for handling the articles
     * @param Product $product The parent product
     * @param array $templateMarkers Current template marker array
     * @param string $template Template text
     * @return string the content for a single product
     */
    public function makeArticleView(
        $viewKind,
        $_,
        Product $product,
        $templateMarkers = [],
        $template = ''
    ) {
        $hooks = HookFactory::getHooks('Controller/ListController', 'makeArticleView');

        $count = is_array($product->getArticleUids()) ? count($product->getArticleUids()) : 0;

        // do nothing if no articles, BE-user-error, should not happen
        if (strlen($template) < 1) {
            $template = $this->templateCode;
        }

        $templateMarker = [];
        $templateMarkerNostock = [];
        $templateMarkerMoreThanMax = [];
        if (is_array($templateMarkers)) {
            foreach ($templateMarkers as $v) {
                $templateMarker[] = '###' . strtoupper($v) . '###';
                $templateMarkerNostock[] = '###' . strtoupper($v) . '_NOSTOCK###';
                $templateMarkerMoreThanMax[] = '###' . strtoupper($v) . '_MORETHANMAX###';
            }
        } else {
            $templateMarker[] = '###' . strtoupper(
                $this->conf['templateMarker.'][$viewKind . '_productArticleList']
            ) . '###';
            $templateMarker[] = '###' . strtoupper(
                $this->conf['templateMarker.'][$viewKind . '_productArticleList2']
            ) . '###';
            $templateMarkerNostock[] = '###' . strtoupper(
                $this->conf['templateMarker.'][$viewKind . '_productArticleList']
            ) . '_NOSTOCK###';
            $templateMarkerNostock[] = '###' . strtoupper(
                $this->conf['templateMarker.'][$viewKind . '_productArticleList2']
            ) . '_NOSTOCK###';
        }

        $content = '';
        $markerArray = [];
        if ($product->getRenderMaxArticles() > $product->getArticlesCount()) {
            // Only if the number of articles is smaller than defined
            $templateAttrSelectorDropdown = $this->templateService->getSubpart(
                $this->templateCode,
                '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorDropdown']) . '###'
            );
            $templateAttrSelectorDropdownItem = $this->templateService->getSubpart(
                $templateAttrSelectorDropdown,
                '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorDropdown']) . '_ITEM###'
            );
            $templateAttrSelectorRadiobutton = $this->templateService->getSubpart(
                $this->templateCode,
                '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorRadiobutton']) . '###'
            );
            $templateAttrSelectorRadiobuttonItem = $this->templateService->getSubpart(
                $templateAttrSelectorRadiobutton,
                '###' . strtoupper($this->conf['templateMarker.']['productAttributesSelectorRadiobutton']) . '_ITEM###'
            );

            $templateCount = count($templateMarker);

            $templateAttr = [];
            if (is_array($this->conf['templateMarker.'][$viewKind . '_selectAttributes.'])) {
                foreach ($this->conf['templateMarker.'][$viewKind . '_selectAttributes.'] as $oneMarker) {
                    $templateMarkerAttr = '###' . strtoupper($oneMarker) . '###';
                    $tCode = $this->templateService->getSubpart($this->templateCode, $templateMarkerAttr);
                    if ($tCode) {
                        $templateAttr[] = $tCode;
                    }
                }
            } elseif ($this->conf['templateMarker.'][$viewKind . '_selectAttributes']) {
                $templateMarkerAttr = '###' . strtoupper(
                    $this->conf['templateMarker.'][$viewKind . '_selectAttributes']
                ) . '###';
                $templateAttr[] = $this->templateService->getSubpart($this->templateCode, $templateMarkerAttr);
            }

            $countTemplateInterations = count($templateAttr);
            if ($this->conf['showHiddenValues'] == 1) {
                $showHiddenValues = true;
            } else {
                $showHiddenValues = false;
            }

            // Parse piVars for values and names of selected attributes
            // define $arrAttSubmit for finding the right article later
            $arrAttSubmit = [];
            foreach ($this->piVars as $key => $val) {
                if (strstr($key, 'attsel_') && $val) {
                    // set only if it is the selected product - for listing mode
                    if ($this->piVars['changedProductUid'] == $product->getUid()
                        || $this->piVars['showUid'] == $product->getUid()) {
                        $arrAttSubmit[(int) substr($key, 7)] = (int) $val;
                        if ($this->piVars['attList_' . $product->getUid() . '_changed'] == (int) substr($key, 7)) {
                            break;
                        }
                    }
                }
            }
            if (is_array($arrAttSubmit)) {
                $attributeMatrix = $product->getSelectAttributeValueMatrix($arrAttSubmit);
            } else {
                $attributeMatrix = $product->getSelectAttributeValueMatrix();
            }

            if ($this->conf['allArticles'] || $count == 1) {
                for ($i = 0; $i < $count; ++$i) {
                    $attributeArray = $product->getAttributeMatrix(
                        [$product->getArticleUid($i)],
                        $this->selectAttributes,
                        $showHiddenValues
                    );

                    $attCode = '';
                    if (is_array($attributeArray)) {
                        $ct = 0;
                        foreach ($attributeArray as $attributeUid => $myAttribute) {
                            /**
                             * Attribute.
                             *
                             * @var \CommerceTeam\Commerce\Domain\Model\Attribute $attribute
                             */
                            $attribute = GeneralUtility::makeInstance(
                                \CommerceTeam\Commerce\Domain\Model\Attribute::class,
                                $attributeUid,
                                $this->getTypoScriptFrontendController()->sys_language_uid
                            );
                            $attribute->loadData();

                            $markerArray['###SELECT_ATTRIBUTES_TITLE###'] = $myAttribute['title'];

                            // @todo check where the attribute values are
                            $attrIcon = '';
                            $attrValue = '';
                            if (!empty($myAttribute['values'])) {
                                $v = current(array_splice(each($myAttribute['values']), 1, 1));
                                if (is_array($v)) {
                                    if (isset($v['value']) && $v['value'] != '') {
                                        $attrValue = $v['value'];
                                    }
                                    if (isset($v['icon']) && $v['icon'] != '') {
                                        $handle = $this->handle . '.';
                                        $attrIcon = $this->renderValue(
                                            $v['icon'],
                                            'IMAGE',
                                            $this->conf[$handle]['products.']['productAttributes.']['fields.']['icon.']
                                        );
                                    }
                                }
                            }
                            $markerArray['###SELECT_ATTRIBUTES_ICON###'] = $attrIcon;
                            if (isset($myAttribute['valueformat']) && $myAttribute['valueformat']) {
                                $markerArray['###SELECT_ATTRIBUTES_VALUE###'] =
                                    sprintf($myAttribute['valueformat'], $attrValue);
                            } else {
                                $markerArray['###SELECT_ATTRIBUTES_VALUE###'] = $attrValue;
                            }
                            $markerArray['###SELECT_ATTRIBUTES_UNIT###'] = $myAttribute['unit'];
                            $numTemplate = $ct % $countTemplateInterations;
                            $attCode .= $this->templateService->substituteMarkerArray(
                                $templateAttr[$numTemplate],
                                $markerArray
                            );
                            ++$ct;
                        }
                    }

                    $markerArray = (array) $this->getArticleMarker($product->getArticle($product->getArticleUid($i)));
                    $markerArray['ARTICLE_SELECT_ATTRIBUTES'] = $this->cObj->stdWrap(
                        $attCode,
                        $this->conf['singleView.']['articleAttributesSelectList.']
                    );

                    foreach ($hooks as $hookObj) {
                        if (method_exists($hookObj, 'additionalMarker')) {
                            $markerArray = (array) $hookObj->additionalMarker(
                                $markerArray,
                                $this,
                                $product->getArticle($product->getArticleUid($i))
                            );
                        }
                    }
                    $templateAttributes = $this->templateService->getSubpart(
                        $template,
                        $templateMarker[($i % $templateCount)]
                    );
                    /**
                     * Article.
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\Article $article
                     */
                    $article = $product->getArticle($product->getArticleUid($i));
                    if ($this->conf['useStockHandling'] == 1 and $article->getStock() <= 0) {
                        $tempTemplate = $this->templateService->getSubpart(
                            $template,
                            $templateMarkerNostock[($i % $templateCount)]
                        );
                        if ($tempTemplate != '') {
                            $templateAttributes = $tempTemplate;
                        }
                    }

                    $content .= $this->templateService->substituteMarkerArray(
                        $templateAttributes,
                        $markerArray,
                        '###|###',
                        true
                    );
                }
            } else {
                $sortedAttributeArray = [];
                $i = 0;
                foreach ($arrAttSubmit as $attrUid => $attrValUid) {
                    $sortedAttributeArray[$i]['AttributeUid'] = $attrUid;
                    $sortedAttributeArray[$i]['AttributeValue'] = $attrValUid;
                    ++$i;
                }

                $artId = array_shift($product->getArticlesByAttributeArray($sortedAttributeArray));
                $attCode = '';
                if (is_array($attributeMatrix)) {
                    $getVarList = ['catUid', 'showUid', 'pointer'];
                    $getVars = [];
                    foreach ($getVarList as $getVar) {
                        if (isset($this->piVars[$getVar])) {
                            $getVars[$this->prefixId . '[' . $getVar . ']'] = $this->piVars[$getVar];
                        }
                    }
                    // Makes pi1 a user int so form values are updated as one selects an attribute
                    $getVars['commerce_pi1_user_int'] = 1;

                    $actionUrl = $this->cObj->typoLink_URL(
                        [
                            'parameter' => $GLOBALS['TSFE']->id,
                            'additionalParams' => GeneralUtility::implodeArrayForUrl('', $getVars),
                            'useCacheHash' => 1,
                            'section' => 'attList_' . $product->getUid(),
                        ]
                    );

                    $attCode = '<form name="attList_' . $product->getUid() . '" id="attList_' . $product->getUid() .
                        '" action="' . $actionUrl . '"  method="post">' . '<input type="hidden" name="' .
                        $this->prefixId . '[changedProductUid]" value="' . $product->getUid() . '" />' .
                        '<input type="hidden" name="' . $this->prefixId . '[attList_' . $product->getUid() .
                        '_changed]" id="attList_' . $product->getUid() . '_changed" value="1" />' .
                        '<input type="hidden" name="tx_commerce_pi1[catUid]" value="' . $this->piVars['catUid'] .
                        '" />';
                    $markerArray = [];
                    foreach ($attributeMatrix as $attrUid => $values) {
                        /**
                         * Attribute.
                         *
                         * @var \CommerceTeam\Commerce\Domain\Model\Attribute $attribute
                         */
                        $attribute = GeneralUtility::makeInstance(
                            \CommerceTeam\Commerce\Domain\Model\Attribute::class,
                            $attrUid,
                            $this->getTypoScriptFrontendController()->sys_language_uid
                        );
                        $attribute->loadData();

                        // disable the icon mode by default
                        $iconMode = false;

                        // if the icon mode is enabled in TS check if the iconMode is also enabled
                        // for this attribute
                        if ($this->conf[$this->handle . '.']['products.']['productAttributes.']['iconMode'] == '1') {
                            $iconMode = $attribute->isIconmode();
                        }
                        if ($iconMode) {
                            $templateAttrSelector = $templateAttrSelectorRadiobutton;
                            $templateAttrSelectorItem = $templateAttrSelectorRadiobuttonItem;
                        } else {
                            $templateAttrSelector = $templateAttrSelectorDropdown;
                            $templateAttrSelectorItem = $templateAttrSelectorDropdownItem;
                        }

                        $markerArray['###SELECT_ATTRIBUTES_TITLE###'] = $attribute->getTitle();
                        $markerArray['###SELECT_ATTRIBUTES_ON_CHANGE###'] = 'document.getElementById(\'attList_' .
                            $product->getUid() . '_changed\').value = ' . $attrUid .
                            ';document.getElementById(\'attList_' . $product->getUid() . '\').submit();';
                        $markerArray['###SELECT_ATTRIBUTES_HTML_ELEMENT_KEY###'] = $this->prefixId . '_' . $attrUid;
                        $markerArray['###SELECT_ATTRIBUTES_HTML_ELEMENT_NAME###'] = $this->prefixId . '[attsel_' .
                            $attrUid . ']';

                        $markerArray['###SELECT_ATTRIBUTES_UNIT###'] = $attribute->getUnit();

                        $itemsContent = '';
                        $i = 1;
                        $attributeValues = $attribute->getAllValues(true, $product);
                        /**
                         * Attribute value.
                         *
                         * @var \CommerceTeam\Commerce\Domain\Model\AttributeValue $val
                         */
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
                                ++$i;
                            } elseif ($values[$val->getUid()] == 'disabled') {
                                $markerArrayItem['###SELECT_ATTRIBUTES_VALUE_STATUS###'] = 'disabled="disabled"';
                            } else {
                                $markerArrayItem['###SELECT_ATTRIBUTES_VALUE_STATUS###'] = '';
                            }

                            foreach ($hooks as $hookObj) {
                                if (method_exists($hookObj, 'additionalAttributeMarker')) {
                                    $markerArrayItem = $hookObj->additionalAttributeMarker(
                                        $markerArrayItem,
                                        $this,
                                        $val->getUid()
                                    );
                                }
                            }
                            $itemsContent .= $this->templateService->substituteMarkerArray(
                                $templateAttrSelectorItem,
                                $markerArrayItem
                            );
                            ++$i;
                        }
                        $attributeContent = $this->templateService->substituteMarkerArray(
                            $templateAttrSelector,
                            $markerArray
                        );

                        if ($iconMode) {
                            $attCode .= $this->templateService->substituteSubpart(
                                $attributeContent,
                                '###' . strtoupper(
                                    $this->conf['templateMarker.']['productAttributesSelectorRadiobutton']
                                ) . '_ITEM###',
                                $itemsContent
                            );
                        } else {
                            $attCode .= $this->templateService->substituteSubpart(
                                $attributeContent,
                                '###' . strtoupper(
                                    $this->conf['templateMarker.']['productAttributesSelectorDropdown']
                                ) . '_ITEM###',
                                $itemsContent
                            );
                        }
                    }
                    $attCode .= '</form>';
                }

                $markerArray = (array) $this->getArticleMarker($product->getArticle($artId));
                $markerArray['ARTICLE_SELECT_ATTRIBUTES'] = $attCode;

                foreach ($hooks as $hookObj) {
                    if (method_exists($hookObj, 'additionalMarker')) {
                        $markerArray = (array) $hookObj->additionalMarker(
                            $markerArray,
                            $this,
                            $product->getArticle($artId)
                        );
                    }
                }

                $templateAttributes = $this->templateService->getSubpart($template, $templateMarker[0]);
                /**
                 * Article.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Article $article
                 */
                $article = $product->getArticle($artId);
                if ($this->conf['useStockHandling'] == 1 and $article->getStock() <= 0) {
                    $tempTemplate = $this->templateService->getSubpart($template, $templateMarkerNostock[0]);
                    if ($tempTemplate != '') {
                        $templateAttributes = $tempTemplate;
                    }
                }

                $content .= $this->templateService->substituteMarkerArray(
                    $templateAttributes,
                    $markerArray,
                    '###|###',
                    true
                );
            }
        } else {
            // Special Marker and rendering when more articles are existing than
            // are allowed to render
            $localContent = $this->templateService->getSubpart($template, reset($templateMarkerMoreThanMax));

            $cat = $this->cat;
            $productCategories = $product->getParentCategories();
            if (!in_array($cat, $productCategories, false)) {
                $cat = $productCategories[0];
            }

            /*
             * Generate TypoLink Configuration and ad to fields by addTypoLinkToTs
             */
            if ($this->conf['overridePid']) {
                $typoLinkConf['parameter'] = $this->conf['overridePid'];
            } else {
                $typoLinkConf['parameter'] = $this->pid;
            }
            $typoLinkConf['useCacheHash'] = 1;
            $typoLinkConf['additionalParams'] = $this->argSeparator . $this->prefixId . '[showUid]=' .
                $product->getUid();
            $typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[catUid]=' . $cat;

            if ($this->basketHashValue) {
                $typoLinkConf['additionalParams'] .= $this->argSeparator . $this->prefixId . '[basketHashValue]=' .
                    $this->basketHashValue;
            }
            $markerArray['LINKTOPRODUCT'] = $this->cObj->typoLink($this->pi_getLL('lang_toproduct'), $typoLinkConf);
            $content = $this->templateService->substituteMarkerArray($localContent, $markerArray, '###|###', 1);

            $markerArray = [];
            foreach ($hooks as $hookObj) {
                if (method_exists($hookObj, 'additionalMarkerMakeArticleView')) {
                    $markerArray = (array) $hookObj->additionalMarkerMakeArticleView($markerArray, $product, $this);
                }
            }
        }
        $content = $this->templateService->substituteMarkerArray($content, $markerArray);

        return $content;
    }
}

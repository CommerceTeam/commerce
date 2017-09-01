<?php
namespace CommerceTeam\Commerce\Hooks;

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

use CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository;
use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Domain\Repository\PageRepository;
use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use CommerceTeam\Commerce\Utility\BackendUserUtility;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Part of the COMMERCE (Advanced Shopping System) extension.
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 *
 * Class \CommerceTeam\Commerce\Hook\CommandMapHooks
 */
class CommandMapHook
{
    /**
     * Attribute without localized title.
     *
     * @var int
     */
    const ATTRIBUTE_LOCALIZATION_TITLE_EMPTY = 0;

    /**
     * Attribute without copied title.
     *
     * @var int
     */
    const ATTRIBUTE_LOCALIZATION_TITLE_COPY = 1;

    /**
     * Attribute without prepended title.
     *
     * @var int
     */
    const ATTRIBUTE_LOCALIZATION_TITLE_PREPENDED = 2;

    /**
     * Backend utility.
     *
     * @var \CommerceTeam\Commerce\Utility\BackendUtility
     */
    protected $belib;

    /**
     * Data handler.
     *
     * @var DataHandler
     */
    protected $pObj;

    /**
     * Constructor
     * Just instantiates the backend library.
     */
    public function __construct()
    {
        $this->belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
    }

    /**
     * This hook is processed Before a commandmap is processed (delete, etc.)
     * Do Nothing if the command is lokalize an table is article.
     *
     * @param string $command Command
     * @param string $table Table the data will be stored in
     * @param int $id The uid of the dataset we're working on
     * @param mixed $_ Value
     * @param DataHandler $dataHandler Parent
     */
    public function processCmdmap_preProcess(&$command, $table, &$id, $_, DataHandler $dataHandler)
    {
        $this->pObj = $dataHandler;

        switch ($table) {
            case 'tx_commerce_categories':
                $this->preProcessCategory($command, $id);
                break;

            case 'tx_commerce_products':
                $this->preProcessProduct($command, $id);
                break;

            case 'tx_commerce_articles':
                $this->preProcessArticle($command, $id);
                break;

            default:
        }
    }

    /**
     * Preprocess category.
     *
     * @param string $command Command
     * @param int $categoryUid Category uid
     */
    protected function preProcessCategory(&$command, &$categoryUid)
    {
        if ($command == 'delete') {
            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $categoryUid
            );
            $category->loadData();

            // check if category is a translation and get l18n parent for access rights
            if ($category->getL18nParent()) {
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Category::class,
                    $category->getL18nParent()
                );
            }

            /** @var BackendUserUtility $backendUserUtility */
            $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
            if (!$category->isPermissionSet($command) || !$backendUserUtility->isInWebMount($category->getUid())) {
                // Log the error
                $this->pObj->log(
                    'tx_commerce_categories',
                    $categoryUid,
                    3,
                    0,
                    1,
                    'Attempt to ' . $command . ' record without ' . $command . '-permissions'
                );
                // Set id to 0 (reference!) to prevent delete of the record
                $categoryUid = 0;
            }
        }
    }

    /**
     * Preprocess product.
     *
     * @param string $command Command
     * @param int $productUid Product uid
     */
    protected function preProcessProduct(&$command, &$productUid)
    {
        $backendUser = $this->getBackendUser();
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        if ($command == 'localize') {
            // check if product has articles
            if (empty($articleRepository->findByProductUid($productUid))) {
                // Error output, no articles
                $command = '';
                $this->error(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:product.localization_without_article'
                );
            }

            // Write to session that we copy
            // this is used by the hook to the datamap class to figure out if it should
            // check if the categories-field is filled - since it is mergeIfNotBlank, it
            // would always be empty so far this is the best (though not very clean) way
            // to solve the issue we get when localizing a product
            $backendUser->uc['txcommerce_copyProcess'] = 1;
            $backendUser->writeUC();
        } elseif ($command == 'delete') {
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $product
             */
            $product = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Product::class,
                $productUid
            );

            // check if product or if translated the translation parent category
            if (!current($product->getParentCategories())) {
                $product = GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Product::class,
                    $product->getL18nParent()
                );
            }

            // check existing categories
            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $product->getParentCategories(),
                ['editcontent']
            )) {
                // Log the error
                $this->pObj->log(
                    'tx_commerce_products',
                    $productUid,
                    3,
                    0,
                    1,
                    'Attempt to ' . $command . ' record without ' . $command . '-permissions'
                );
                // Set id to 0 (reference!) to prevent delete of the record
                $productUid = 0;
            }
        }
    }

    /**
     * Proprocess article.
     *
     * @param string $command Command
     * @param int $articleUid Article uid
     */
    protected function preProcessArticle(&$command, &$articleUid)
    {
        if ($command == 'localize') {
            $command = '';
            $this->error('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:article.localization');
        } elseif ($command == 'delete') {
            /**
             * Article.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Article $article
             */
            $article = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Article::class,
                $articleUid
            );
            $article->loadData();

            $product = $article->getParentProduct();

            // check if product has atleast one category or
            // if the translation parent has parent categories
            $categories = $product->getParentCategories();
            if (!current($categories)) {
                $product = GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Product::class,
                    $product->getL18nParent()
                );
            }

            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $product->getParentCategories(),
                ['editcontent']
            )) {
                // Log the error
                $this->pObj->log(
                    'tx_commerce_articles',
                    $articleUid,
                    3,
                    0,
                    1,
                    'Attempt to ' . $command . ' record without ' . $command . '-permissions'
                );
                // Set id to 0 (reference!) to prevent delete of the record
                $articleUid = 0;
            }
        }
    }

    /**
     * This hook is processed AFTER a commandmap is processed (delete, etc.)
     * Calculation of missing price.
     *
     * @param string $command Command
     * @param string $table Table the data will be stored in
     * @param int $id The uid of the dataset we're working on
     * @param int $value Value
     * @param DataHandler $dataHandler The instance of the BE data handler
     */
    public function processCmdmap_postProcess(&$command, $table, $id, $value, DataHandler $dataHandler)
    {
        $this->pObj = $dataHandler;

        switch ($table) {
            case 'tx_commerce_categories':
                $this->postProcessCategory($command, $id);
                break;

            case 'tx_commerce_products':
                $this->postProcessProduct($command, $id, $value);
                break;

            default:
        }

        if (TYPO3_MODE == 'BE' && $this->isUpdateSignalAllowed($dataHandler)) {
            BackendUtility::setUpdateSignal('updateCategoryTree');
        }
    }

    /**
     * Postprocess category.
     *
     * @param string $command Command
     * @param int $categoryUid Category uid
     */
    protected function postProcessCategory($command, $categoryUid)
    {
        if ($command == 'delete') {
            $this->deleteChildCategoriesProductsArticlesPricesOfCategory($categoryUid);
        } elseif ($command == 'copy') {
            $newCategoryUid = $this->pObj->copyMappingArray['tx_commerce_categories'][$categoryUid];
            $locale = $this->getLocale();

            $this->belib->copyCategoriesByCategory($newCategoryUid, $categoryUid, $locale);
            $this->belib->copyProductsByCategory($newCategoryUid, $categoryUid, $locale);
        }
    }

    /**
     * Postprocess product.
     *
     * @param string $command Command
     * @param int $productUid Product uid
     * @param int $value Value
     */
    protected function postProcessProduct(&$command, $productUid, $value)
    {
        if ($command == 'localize') {
            $this->translateArticlesAndAttributesOfProduct($productUid, $value);
        } elseif ($command == 'delete') {
            $this->deleteArticlesAndPricesOfProduct($productUid);
            $this->deleteProductTranslationsByProductList([$productUid]);
        } elseif ($command == 'copy') {
            $newProductUid = $this->pObj->copyMappingArray['tx_commerce_products'][$productUid];

            // $this->changeCategoryOfCopiedProduct($newProductUid);
            $this->copyProductTanslations($productUid, $newProductUid);
            $this->belib->copyArticlesByProduct($newProductUid, $productUid);
        }
    }

    /**
     * Localize all articles that are related to the current product
     * and localize all product attributes realted to this product from.
     *
     * @param int $productUid The uid of the dataset we're working on
     * @param int $value Value
     */
    protected function translateArticlesAndAttributesOfProduct($productUid, $value)
    {
        // get the uid of the newly created product
        $localizedProductUid = $this->pObj->copyMappingArray['tx_commerce_products'][$productUid];
        if ($localizedProductUid == null) {
            $this->error('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:product.no_find_uid');
        }

        $backendUser = $this->getBackendUser();

        // copying done, clear session
        $backendUser->uc['txcommerce_copyProcess'] = 0;
        $backendUser->writeUC();

        $this->translateAttributesOfProduct($productUid, $localizedProductUid, $value);
        $this->translateArticlesOfProduct($productUid, $localizedProductUid, $value);
    }

    /**
     * Localize attributes of product.
     *
     * @param int $productUid Product uid
     * @param int $localizedProductUid Localized product uid
     * @param int $value Value
     */
    protected function translateAttributesOfProduct($productUid, $localizedProductUid, $value)
    {
        // get all related attributes
        $productAttributes = $this->belib->getAttributesForProduct($productUid, false, true);
        // check if localized product has attributes
        $localizedProductAttributes = $this->belib->getAttributesForProduct($localizedProductUid);

        // Check product has attrinutes and no attributes are
        // avaliable for localized version
        if ($localizedProductAttributes == false && !empty($productAttributes)) {
            /** @var ProductRepository $productRepository */
            $productRepository = GeneralUtility::makeInstance(ProductRepository::class);

            // if true
            $langIsoCode = BackendUtility::getRecord('sys_language', (int) $value, 'language_isocode');
            $langIdent = BackendUtility::getRecord(
                'static_languages',
                (int) $langIsoCode['language_isocode'],
                'lg_typo3'
            );
            $langIdent = strtoupper($langIdent['lg_typo3']);

            foreach ($productAttributes as $productAttribute) {
                // only if we have attributes type 4 and no valuelist
                if ($productAttribute['uid_correlationtype'] == 4 && !$productAttribute['has_valuelist'] == 1) {
                    $localizedProductAttribute = $productAttribute;

                    unset($localizedProductAttribute['attributeData']);
                    unset($localizedProductAttribute['has_valuelist']);

                    switch (ConfigurationUtility::getInstance()->getExtConf('attributeLocalizationType')) {
                        case self::ATTRIBUTE_LOCALIZATION_TITLE_EMPTY:
                            unset($localizedProductAttribute['default_value']);
                            break;

                        case self::ATTRIBUTE_LOCALIZATION_TITLE_COPY:
                            break;

                        case self::ATTRIBUTE_LOCALIZATION_TITLE_PREPENDED:
                            // Walk through the array and prepend text
                            $prepend = '[Translate to ' . $langIdent . ':] ';
                            $localizedProductAttribute['default_value'] = $prepend
                                . $localizedProductAttribute['default_value'];
                            break;

                        default:
                    }

                    $productRepository->addAttributeRelation(
                        $localizedProductUid,
                        $localizedProductAttribute
                    );
                }
            }

            /*
             * Update the flexform
             */
            $product = $productRepository->findByUid($productUid);
            if (!empty($product)) {
                $localizedProductFields = [
                    'attributesedit' => $product['attributesedit'],
                    'attributes' => $product['attributes'],
                ];

                $localizedProductFields['attributesedit'] = $this->belib->buildLocalisedAttributeValues(
                    $localizedProductFields['attributesedit'],
                    $langIdent
                );
                $productRepository->updateRecord($localizedProductUid, $localizedProductFields);
            }
        }
    }

    /**
     * Localize articles of product.
     *
     * @param int $productUid Product uid
     * @param int $localizedProductUid Localized product uid
     * @param int $value Value
     */
    protected function translateArticlesOfProduct($productUid, $localizedProductUid, $value)
    {
        // get all related articles
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articles = $articleRepository->findByProductUid($productUid);
        if (empty($articles)) {
            // Error Output, no Articles
            $this->error(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:product.localization_without_article'
            );
        }

        // get articles of localized Product
        $localizedProductArticles = $articleRepository->findByProductUid($localizedProductUid);

        // Check if product has articles and localized product has no articles
        if (!empty($articles) && empty($localizedProductArticles)) {
            // determine language identifier
            // this is needed for updating the XML of the new created articles
            $langIsoCode = BackendUtility::getRecord('sys_language', (int) $value, 'language_isocode');
            $langIdent = BackendUtility::getRecord(
                'static_languages',
                (int) $langIsoCode['language_isocode'],
                'lg_typo3'
            );
            $langIdent = strtoupper($langIdent['lg_typo3']);
            if (empty($langIdent)) {
                $langIdent = 'DEF';
            }

            // process all existing articles and copy them
            foreach ($articles as $origArticle) {
                // make a localization version
                $locArticle = $origArticle;
                // unset some values
                unset($locArticle['uid']);

                // set new article values
                $locArticle['tstamp'] = $GLOBALS['EXEC_TIME'];
                $locArticle['crdate'] = $GLOBALS['EXEC_TIME'];
                $locArticle['sys_language_uid'] = $value;
                $locArticle['l18n_parent'] = $origArticle['uid'];
                $locArticle['uid_product'] = $localizedProductUid;

                // get XML for attributes
                // this has only to be changed if the language is something else than default.
                // The possibility that something else happens is very small but anyhow... ;-)
                if ($langIdent != 'DEF' && $origArticle['attributesedit']) {
                    $locArticle['attributesedit'] = $this->belib->buildLocalisedAttributeValues(
                        $origArticle['attributesedit'],
                        $langIdent
                    );
                }

                // create new article in DB
                $locatedArticleUid = $articleRepository->addRecord($locArticle);

                // get all relations to attributes from the old article and copy them to new article
                $originalRelations = $articleRepository->getAttributeRelationsByArticleUid((int) $origArticle['uid']);
                foreach ($originalRelations as $originalRelation) {
                    if ($originalRelation['uid_valuelist'] > 0) {
                        continue;
                    }
                    $articleRepository->addAttributeRelation(
                        $locatedArticleUid,
                        $originalRelation['uid_foreign'],
                        $originalRelation['uid_product'],
                        $originalRelation['sorting'],
                        $originalRelation['uid_valuelist'],
                        $originalRelation['value_char'],
                        $originalRelation['default_value']
                    );
                }
            }
        }
    }

    /**
     * Get available language uids of product folder.
     *
     * @return array
     */
    protected function getLocale()
    {
        $productPid = FolderRepository::initFolders('Products', FolderRepository::initFolders());

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $locale = $pageRepository->findLanguageUidsByUid($productPid);

        return $locale;
    }

    /**
     * Change category of copied product.
     *
     * @param int $productUid Product uid
     */
    protected function changeCategoryOfCopiedProduct($productUid)
    {
        // @todo maybe not needed anymore. remove if testing prooves obsolence
        $pasteData = GeneralUtility::_GP('CB');

        /**
         * Clipboard.
         *
         * @var \TYPO3\CMS\Backend\Clipboard\Clipboard $clipboard
         */
        $clipboard = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);
        $clipboard->initializeClipboard();
        $clipboard->setCurrentPad($pasteData['pad']);

        $fromCategorUid = array_pop(
            GeneralUtility::trimExplode('|', key($clipboard->clipData[$clipboard->current]['el']), true)
        );
        $toCategoryUid = abs(array_pop(GeneralUtility::trimExplode('|', $pasteData['paste'], true)));

        if ($fromCategorUid && $toCategoryUid) {
            /** @var ProductRepository $productRepository */
            $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
            $productRepository->deleteCategoryRelation($productUid, $fromCategorUid);
            $productRepository->addCategoryRelation($productUid, $toCategoryUid);
        }
    }

    /**
     * Copy product localizations.
     *
     * @param int $oldProductUid Old product uid
     * @param int $newProductUid New product uid
     */
    protected function copyProductTanslations($oldProductUid, $newProductUid)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $products = $productRepository->findByTranslationParentUid($oldProductUid);

        foreach ($products as $product) {
            $oldTranslationProductUid = $product['uid'];

            /**
             * Data handler.
             *
             * @var DataHandler
             */
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

            $tcaDefaultOverride = $this->getBackendUser()->getTSConfigProp('TCAdefaults');
            if (is_array($tcaDefaultOverride)) {
                $tce->setDefaultsFromUserTS($tcaDefaultOverride);
            }

            // start
            $tce->start([], []);

            $overrideArray = ['l18n_parent' => $newProductUid];

            $newTranslationProductUid = $tce->copyRecord(
                'tx_commerce_products',
                $oldTranslationProductUid,
                $product['pid'],
                1,
                $overrideArray
            );

            $this->belib->copyArticlesByProduct($newTranslationProductUid, $oldTranslationProductUid);
        }
    }

    /**
     * Delete all categories->products->articles
     * if a category should be deleted.
     * This one does NOT delete any relations!
     * This is not wanted because you might want to
     * restore deleted categories, products or articles.
     *
     * @param int $categoryUid Category uid
     */
    protected function deleteChildCategoriesProductsArticlesPricesOfCategory($categoryUid)
    {
        // we dont use
        // \CommerceTeam\Commerce\Domain\Model\Category::getChildCategoriesUidlist
        // because of performance issues
        // @todo is there realy a performance issue?
        $childCategories = [];
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $this->belib->getChildCategories($categoryUid, $childCategories, 0, 0, true);

        if (!empty($childCategories)) {
            foreach ($childCategories as $childCategoryUid) {
                $products = $this->belib->getProductsOfCategory($childCategoryUid);

                if (!empty($products)) {
                    $productList = [];
                    foreach ($products as $product) {
                        $productList[] = $product['uid_local'];

                        $articles = $articleRepository->findByProductUid($product['uid_local']);
                        if (!empty($articles)) {
                            $articleList = [];
                            foreach ($articles as $article) {
                                $articleList[] = $article['uid'];
                            }

                            $this->deletePricesByArticleList($articleList);
                            $this->deleteArticlesByArticleList($articleList);
                        }
                    }

                    $this->deleteProductsByProductList($productList);
                    $this->deleteProductTranslationsByProductList($productList);
                }
            }

            $this->deleteCategoriesByCategoryList($childCategories);
            $this->deleteCategoryTranslationsByCategoryList($childCategories);
        }
    }

    /**
     * If a product is deleted, delete all articles below and their locales.
     *
     * @param int $productUid Product uid
     */
    protected function deleteArticlesAndPricesOfProduct($productUid)
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articleUids = $articleRepository->findUidsByProductUid($productUid);

        if (!empty($articleUids)) {
            $this->deletePricesByArticleList($articleUids);
            $this->deleteArticlesByArticleList($articleUids);
        }
    }

    /**
     * Flag categories as deleted for category uid list.
     *
     * @param array $categoryUids Category list
     */
    protected function deleteCategoriesByCategoryList(array $categoryUids)
    {
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categoryRepository->deleteByUids($categoryUids);
    }

    /**
     * Flag category translations as deleted for category uid list.
     *
     * @param array $categoryUids Category list
     */
    protected function deleteCategoryTranslationsByCategoryList(array $categoryUids)
    {
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categoryRepository->deleteTranslationByParentUids($categoryUids);
    }

    /**
     * Flag product as deleted for product uid list.
     *
     * @param array $productUids Product list
     */
    protected function deleteProductsByProductList(array $productUids)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $productRepository->deleteByUids($productUids);
    }

    /**
     * Flag product translations as deleted for productList.
     *
     * @param array $productUids Product uid list
     */
    protected function deleteProductTranslationsByProductList(array $productUids)
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);

        $productRepository->deleteTranslationByParentUids($productUids);

        $translatedArticles = [];
        foreach ($productUids as $productId) {
            $articlesOfProduct = $articleRepository->findUidsByProductUid($productId);
            if (!empty($articlesOfProduct)) {
                $translatedArticles = array_merge($translatedArticles, $articlesOfProduct);
            }
        }
        $translatedArticles = array_unique($translatedArticles);

        if (!empty($translatedArticles)) {
            $this->deleteArticlesByArticleList($translatedArticles);
        }
    }

    /**
     * Flag articles and prices as deleted for article uids.
     *
     * @param array $articleUids Article list
     */
    protected function deleteArticlesByArticleList(array $articleUids)
    {
        $this->deletePricesByArticleList($articleUids);

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articleRepository->deleteByUids($articleUids);
    }

    /**
     * Flag prices as deleted for article uids.
     *
     * @param array $articleUids Article uid list
     */
    protected function deletePricesByArticleList(array $articleUids)
    {
        /** @var ArticlePriceRepository $articlePriceRepository */
        $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);
        $articlePriceRepository->deleteByArticleUids($articleUids);
    }

    /**
     * Prints out the error.
     *
     * @param string $error Error
     */
    protected function error($error)
    {
        $language = $this->getLanguageService();
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        /**
         * Document template.
         *
         * @var \TYPO3\CMS\Backend\Template\DocumentTemplate $errorDocument
         */
        $errorDocument = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);

        $errorHeadline = htmlspecialchars($language->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:error'
        ));
        $submitLabel = htmlspecialchars($language->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:continue'
        ));
        $onClickAction = 'onclick="document.location=\'' . htmlspecialchars($_SERVER['HTTP_REFERER']) .
            '\'; return false;"';

        $content = $errorDocument->startPage(self::class . ' error Output');
        $content .= '
			<br/>
			<br/>
			<table>
				<tr class="bgColor5">
					<td colspan="2" align="center"><strong>' . $errorHeadline . '</strong></td>
				</tr>
				<tr class="bgColor4">
					<td valign="top">' . $iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render()
            . '</td>
					<td>' . $language->sL($error) . '</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
					<br />
						<form action="' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '">
							<input type="submit" value="' . $submitLabel . '" ' . $onClickAction . ' />
						</form>
					</td>
				</tr>
			</table>';

        $content .= $errorDocument->endPage();
        echo $content;
        exit;
    }

    /**
     * @param DataHandler $dataHandler
     * @return bool
     */
    protected function isUpdateSignalAllowed($dataHandler)
    {
        $isEnableFieldSet = false;
        foreach ($dataHandler->cmdmap as $table => $commands) {
            if (!in_array($table, ['tx_commerce_categories', 'tx_commerce_products', 'tx_commerce_articles'])) {
                continue;
            }

            foreach ($commands as $uid => $command) {
                if (isset($command['delete'])
                    || isset($command['undelete'])
                    || isset($command['move'])
                    || isset($command['copy'])
                ) {
                    $isEnableFieldSet = true;
                    break;
                }
            }

            if ($isEnableFieldSet) {
                break;
            }
        }

        return $isEnableFieldSet;
    }

    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}

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

use CommerceTeam\Commerce\Domain\Model\Product;
use CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository;
use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository;
use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use CommerceTeam\Commerce\Factory\HookFactory;
use CommerceTeam\Commerce\Form\Container\ExistingArticleContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ArticleAjaxController
{
    /**
     * @var array
     */
    protected $conf = [];

    /**
     * Backend utility.
     *
     * @var \CommerceTeam\Commerce\Utility\BackendUtility
     */
    protected $belib;

    /**
     * Flatted attributes.
     *
     * @var array
     */
    protected $flattedAttributes = [];

    /**
     * Existing articles.
     *
     * @var array
     */
    protected $existingArticles = [];

    /**
     * Attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->conf = $request->getParsedBody();

        /**
         * Product.
         *
         * @var Product $product
         */
        $product = GeneralUtility::makeInstance(Product::class, (int)$this->conf['product']);
        $product->loadData();

        $content = '';
        if (!empty($product->getData())) {
            $attributeValue = array_map('intval', (array)$this->conf['attributeValue']);
            $this->init($product, $attributeValue);

            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $product->getParentCategories(),
                ['editcontent']
            )) {
                $content = 'You dont have the permissions to create a new article.';
            } else {
                switch ($this->conf['action']) {
                    case 'createArticle':
                        $content = $this->createArticle($product, $attributeValue);
                        break;
                }
            }
        }

        $response->getBody()->write($content);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Initializes the Article Creator if it's not called directly
     * from the Flexforms.
     *
     * @param Product $product
     * @param array $attributeValue
     *
     * @return void
     */
    protected function init($product, $attributeValue)
    {
        $this->belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);

        // get all attributes for this product, if they where not fetched yet
        if ($this->attributes == null) {
            $this->attributes = $this->belib->getAttributesForProduct($product->getUid(), true, true, true);
        }

        // get existing articles for this product, if they where not fetched yet
        if ($this->existingArticles == null) {
            $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
            $this->existingArticles = $articleRepository->findByProductUid($product->getUid());
        }

        if (!empty($attributeValue)) {
            /** @var AttributeValueRepository $attributeValueRepository */
            $attributeValueRepository = GeneralUtility::makeInstance(AttributeValueRepository::class);
            $values = $attributeValueRepository->findByUids(array_values($attributeValue));

            foreach ($values as $value) {
                $this->flattedAttributes[$value['uid']] = $value['value'];
            }
        }
    }

    /**
     * Creates an article in the database and all needed releations to attributes
     * and values. It also creates a new prices and assignes it to the new article.
     *
     * @param Product $product Product to add article too
     * @param array $attributeValue Attribute value(s)
     *
     * @return string Returns the new article row to add to list
     */
    protected function createArticle(Product $product, array $attributeValue)
    {
        // needs to use json_encode or the check against stored articles will result in a wrong result
        // this is because ajax handed attribute data are objects due to associativ array usage
        $attributeHash = md5(json_encode($attributeValue));

        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        $sorting = $articleRepository->getHighestSortingByProductUid($product->getUid());
        $sorting = $sorting ? $sorting + 20 : 0;

        $articleUid = $this->createParentArticleRecord($product, $attributeValue, $attributeHash, $sorting);

        $this->createArticleAttributeRelations($product, $attributeValue, $articleUid);

        // Now check, if the parent product is already lokalised, so create article in
        // the localised version select from Database different localisations
        $originalArticle = $articleRepository->findByUid($articleUid);

        $localizedProducts = $productRepository->getL18nProducts($product->getUid());
        // Only if there are products
        foreach ($localizedProducts as $localizedProduct) {
            // walk through and create articles
            $this->createTranslatedArticleRecord(
                $product,
                $attributeValue,
                $attributeHash,
                $originalArticle,
                $localizedProduct
            );
        }

        /** @var ExistingArticleContainer $existingArticleContainer */
        $existingArticleContainer = GeneralUtility::makeInstance(
            ExistingArticleContainer::class,
            'tx_commerce_articles',
            [],
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class)
        );

        return $existingArticleContainer->renderArticleRow([], $originalArticle, 1000);
    }

    /**
     * @param Product $product
     * @param array $attributeValue
     * @param string $attributeHash
     * @param int $sorting
     * @return int
     */
    protected function createParentArticleRecord($product, $attributeValue, $attributeHash, $sorting)
    {
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);

        // create article data array
        $articleData = [
            'pid' => $product->getPid(),
            'crdate' => $GLOBALS['EXEC_TIME'],
            'title' => strip_tags($this->createArticleTitleFromAttributes($product, $attributeValue)),
            'uid_product' => $product->getUid(),
            'sorting' => $sorting,
            'article_attributes' => count($this->attributes['rest']) + count($attributeValue),
            'attribute_hash' => $attributeHash,
            'article_type_uid' => 1,
        ];

        $moduleConfig = BackendUtility::getModTSconfig($product->getPid(), 'mod.commerce.category');
        if ($moduleConfig) {
            $defaultTax = (int) $moduleConfig['properties']['defaultTaxValue'];
            if ($defaultTax > 0) {
                $articleData['tax'] = $defaultTax;
            }
        }

        $hookObject = HookFactory::getHook('Utility/ArticleCreatorUtility', 'createArticle');
        if (!empty($hookObject)) {
            GeneralUtility::deprecationLog(
                'Hooks for Utility/ArticleCreatorUtility are deprecated,
                 please change to Controller/ArticleAjaxController.'
            );
        }

        $hookObject = HookFactory::getHook('Controller/ArticleAjaxController', 'createArticle');
        if (method_exists($hookObject, 'preinsert')) {
            $hookObject->preinsert($articleData);
        }

        // create the article
        $articleUid = $articleRepository->addRecord($articleData);

        // create a new price that is assigned to the new article
        $articlePriceRepository->addRecord([
            'pid' => $product->getPid(),
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'uid_article' => $articleUid,
        ]);

        return $articleUid;
    }

    /**
     * Now write all relations between article and attributes into the database
     *
     * @param Product $product
     * @param array $attributeValue
     * @param int $articleUid
     * @return void
     */
    protected function createArticleAttributeRelations($product, $attributeValue, $articleUid)
    {
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        $relationBaseData = [ 'uid_local' => $articleUid ];

        $createdArticleRelations = [];

        $productsAttributes = $productRepository->getAttributeRelations($product->getUid());
        $attributesSorting = [];
        foreach ($productsAttributes as $productsAttribute) {
            $attributesSorting[$productsAttribute['uid_foreign']] = $productsAttribute['sorting'];
        }

        foreach ($attributeValue as $selectAttributeUid => $selectAttributeValueUid) {
            $relationCreateData = $relationBaseData;
            $relationCreateData['uid_foreign'] = $selectAttributeUid;
            $relationCreateData['uid_valuelist'] = $selectAttributeValueUid;
            $relationCreateData['sorting'] = $attributesSorting[$selectAttributeUid];

            $createdArticleRelations[] = $relationCreateData;
            $articleRepository->addAttributeRelation(
                $articleUid,
                $selectAttributeUid,
                0,
                $attributesSorting[$selectAttributeUid],
                $selectAttributeValueUid
            );
        }

        if (is_array($this->attributes['rest'])) {
            foreach ($this->attributes['rest'] as $attribute) {
                if (empty($attribute['attributeData']['uid'])) {
                    continue;
                }

                $relationCreateData = $relationBaseData;

                $relationCreateData['sorting'] = $attribute['sorting'];
                $relationCreateData['uid_foreign'] = $attribute['attributeData']['uid'];
                if ($attribute['uid_correlationtype'] == 4) {
                    $relationCreateData['uid_product'] = $product->getUid();
                }

                $relationCreateData['default_value'] = '';
                $relationCreateData['value_char'] = '';
                $relationCreateData['uid_valuelist'] = $attribute['uid_valuelist'];

                if (!is_int($attribute['default_value'])) {
                    $relationCreateData['default_value'] = $attribute['default_value'];
                } else {
                    $relationCreateData['value_char'] = $attribute['default_value'];
                }

                $createdArticleRelations[] = $relationCreateData;
                $articleRepository->addAttributeRelation(
                    $relationCreateData['uid_local'],
                    $relationCreateData['uid_foreign'],
                    0,
                    $relationCreateData['sorting'],
                    $relationCreateData['uid_valuelist'],
                    $relationCreateData['value_char'],
                    $relationCreateData['default_value']
                );
            }
        }

        // update the article
        // we have to write the xml datastructure for this article. This is needed
        // to have the correct values inserted on first call of the article.
        $this->belib->updateArticleXML($createdArticleRelations, false, $articleUid);
    }

    /**
     * @param Product $product
     * @param $attributeValue
     * @param $attributeHash
     * @param $originalArticle
     * @param $localizedProduct
     */
    protected function createTranslatedArticleRecord(
        $product,
        $attributeValue,
        $attributeHash,
        $originalArticle,
        $localizedProduct
    ) {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        $destLanguage = $localizedProduct['sys_language_uid'];
        // get the highest sorting
        $langIsoCode = BackendUtility::getRecord('sys_language', (int) $destLanguage, 'language_isocode');
        $langIdent = BackendUtility::getRecord(
            'static_languages',
            (int) $langIsoCode['language_isocode'],
            'lg_typo3'
        );
        $langIdent = strtoupper($langIdent['lg_typo3']);

        // create article data array
        $articleData = [
            'pid' => $product->getPid(),
            'crdate' => $GLOBALS['EXEC_TIME'],
            'title' => $product->getTitle(),
            'uid_product' => $localizedProduct['uid'],
            'sys_language_uid' => $localizedProduct['sys_language_uid'],
            'l18n_parent' => $originalArticle['uid'],
            'sorting' => $originalArticle['sorting'] + 1,
            'article_attributes' => count($this->attributes['rest']) + count($attributeValue),
            'attribute_hash' => $attributeHash,
            'article_type_uid' => 1,
            'attributesedit' => $this->belib->buildLocalisedAttributeValues(
                $originalArticle['attributesedit'],
                $langIdent
            ),
        ];

        // create the article
        $localizedArticleUid = $articleRepository->addRecord($articleData);

        $createdArticleRelations = [];

        // get all relations to attributes from the old article and copy them
        // to new article
        $originalRelations = $articleRepository->getAttributeRelationsByArticleUid($originalArticle['uid']);
        foreach ($originalRelations as $originalRelation) {
            if ($originalRelation['uid_valuelist']) {
                continue;
            }
            $originalRelation['uid_local'] = $localizedArticleUid;

            $createdArticleRelations[] = $originalRelation;
            $articleRepository->addAttributeRelation(
                $localizedArticleUid,
                $originalRelation['uid_foreign'],
                $originalRelation['uid_product'],
                $originalRelation['sorting'],
                $originalRelation['uid_valuelist'],
                $originalRelation['value_char'],
                $originalRelation['default_value']
            );
        }

        // update the article
        // we have to write the xml datastructure for this article. This is needed
        // to have the correct values inserted on first call of the article.
        $this->belib->updateArticleXML($createdArticleRelations, false, $localizedArticleUid);
    }

    /**
     * Creates article title out of attributes.
     *
     * @param Product $product Product to get title of
     * @param array $attributeValue Attribute value(s)
     *
     * @return string Returns the product title + attribute titles for article title
     */
    protected function createArticleTitleFromAttributes(Product $product, array $attributeValue)
    {
        $content = $product->getTitle();

        if (!empty($attributeValue)) {
            $selectedValues = [];
            foreach ($attributeValue as $value) {
                if ($this->flattedAttributes[$value]) {
                    $selectedValues[] = $this->flattedAttributes[$value];
                }
            }
            if (!empty($selectedValues)) {
                $content .= ' (' . implode(', ', $selectedValues) . ')';
            }
        }

        return $content;
    }


    /**
     * Updates all articles.
     * This adds new attributes to all existing articles that where added
     * to the parent product or categories.
     *
     * @return void
     */
    public function updateArticles()
    {
        $fullAttributeList = [];

        if (!is_array($this->attributes['ct1'])) {
            return;
        }

        foreach ($this->attributes['ct1'] as $attributeData) {
            $fullAttributeList[] = $attributeData['uid_foreign'];
        }

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        if (is_array(GeneralUtility::_GP('updateData'))) {
            foreach (GeneralUtility::_GP('updateData') as $articleUid => $relData) {
                foreach ($relData as $attributeUid => $attributeValueUid) {
                    if ($attributeValueUid == 0) {
                        continue;
                    }

                    $articleRepository->updateRelation(
                        $articleUid,
                        $attributeUid,
                        ['uid_valuelist' => (int) $attributeValueUid]
                    );
                }

                $this->belib->updateArticleHash($articleUid, $fullAttributeList);
            }
        }
    }
}

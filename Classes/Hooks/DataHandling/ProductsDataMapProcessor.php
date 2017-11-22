<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use CommerceTeam\Commerce\Domain\Model\Product;
use CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository;
use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProductsDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * Preprocess product.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $productUid Id
     *
     * @return array
     */
    public function preProcess(array &$incomingFieldArray, $productUid)
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articles = $articleRepository->findByProductUid($productUid);
        if (is_array($articles)) {
            foreach ($articles as $article) {
                $this->belib->updateArticleHash($article['uid']);
            }
        }

        // direct preview
        if (GeneralUtility::_POST('_savedokview_x')) {
            // if "savedokview" has been pressed and  the beUser works in the LIVE workspace
            // open current record in single view get page TSconfig
            $pagesTypoScriptConfig = BackendUtility::getPagesTSconfig(GeneralUtility::_POST('popViewId'));
            if ($pagesTypoScriptConfig['tx_commerce.']['singlePid']) {
                $previewPageId = $pagesTypoScriptConfig['tx_commerce.']['singlePid'];
            } else {
                $previewPageId = ConfigurationUtility::getInstance()->getExtConf('previewPageID');
            }

            if ($previewPageId > 0) {
                // Get Parent CAT UID
                /**
                 * Product.
                 *
                 * @var Product $product
                 */
                $product = GeneralUtility::makeInstance(Product::class, $productUid);
                $product->loadData();

                $parentCategory = $product->getMasterparentCategory();
                $GLOBALS['_POST']['popViewId_addParams'] = (
                    $incomingFieldArray['sys_language_uid'] > 0 ?
                        '&L=' . $incomingFieldArray['sys_language_uid'] :
                        ''
                    ) .
                    '&ADMCMD_vPrev&no_cache=1&tx_commerce_pi1[showUid]=' . $productUid .
                    '&tx_commerce_pi1[catUid]=' . $parentCategory;
                $GLOBALS['_POST']['popViewId'] = $previewPageId;
            }
        }

        return GeneralUtility::trimExplode(',', $incomingFieldArray['categories']);
    }

    /**
     * Checks if the permissions we need to process the datamap are still in place.
     *
     * @param string $status Status
     * @param string $table Table
     * @param int|string $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     *
     * @return array
     */
    public function postProcess($status, $table, $id, array &$fieldArray, DataHandler $pObj)
    {
        $data = $pObj->datamap[$table][$id];

        // Read the old parent categories
        if ($status != 'new') {
            /**
             * Product.
             *
             * @var Product $product
             */
            $product = GeneralUtility::makeInstance(Product::class, $id);

            $parentCategories = $product->getParentCategories();

            // check existing categories
            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $parentCategories,
                ['editcontent']
            )) {
                $pObj->newlog('You dont have the permissions to edit the product.', 1);
                $fieldArray = [];
            }
        } else {
            // new products have to have a category
            // if a product is copied, we never check if it has categories
            // - this is MANDATORY, otherwise localize will not work at all!!!
            // remove this only if you decide to not define the l10n_mode of "categories"
            if (!trim($fieldArray['categories']) &&
                !isset($this->getBackendUserAuthentication()->uc['txcommerce_copyProcess'])) {
                $pObj->newlog('You have to specify at least 1 parent category for the product.', 1);
                $fieldArray = [];
            }

            $parentCategories = [];
        }

        // check new categories
        if (isset($data['categories'])) {
            $newCats = $this->singleDiffAssoc(
                GeneralUtility::trimExplode(',', GeneralUtility::uniqueList($data['categories'])),
                $parentCategories
            );

            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $newCats,
                ['editcontent']
            )) {
                $pObj->newlog('You do not have the permissions to add one or all categories you added.' .
                    GeneralUtility::uniqueList($data['categories']), 1);
                $fieldArray = [];
            }
        }

        if (isset($fieldArray['categories'])) {
            $fieldArray['categories'] = GeneralUtility::uniqueList($fieldArray['categories']);
        }

        return $data;
    }

    /**
     * This Function is simlar to array_diff but looks for array sorting too.
     *
     * @param array $array1 Array one - Reference is only to save memory
     * @param array $array2 Array two - Reference is only to save memory
     *
     * @return array $result different fields between array1 & array2
     */
    protected function singleDiffAssoc(array &$array1, array &$array2)
    {
        $result = [];

        // check for each value if in array2 the index is not set or
        // the value is not equal
        foreach ($array1 as $index => $value) {
            if (!isset($array2[$index]) || $array2[$index] != $value) {
                $result[$index] = $value;
            }
        }

        // check for each value if in array1 the index is not set or the value is not
        // equal and in result the index is not set
        foreach ($array2 as $index => $value) {
            if ((!isset($array1[$index]) || $array1[$index] != $value) && !isset($result[$index])) {
                $result[$index] = $value;
            }
        }

        return $result;
    }

    /**
     * After database product handling.
     *
     * @param string $table Table
     * @param string|int $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $dataHandler Parent object
     * @param string $status
     */
    public function afterDatabase($table, $id, array $fieldArray, DataHandler $dataHandler, $status)
    {
        if (!empty($fieldArray)) {
            $id = $this->getSubstitutedId($dataHandler, $id);

            /**
             * Product.
             *
             * @var Product $product
             */
            $product = GeneralUtility::makeInstance(Product::class, $id);
            $product->loadData();

            if (isset($fieldArray['categories'])) {
                $newId = '';
                foreach ($dataHandler->substNEWwithIDs as $newId => $uid) {
                    if ($uid == $id) {
                        break;
                    }
                }
                $categoryListUid = $newId ?: $id;
                $categories = explode(',', $dataHandler->datamap[$table][$categoryListUid]['categories']);
                $categories = $this->belib->extractFieldArray($categories, 'uid_foreign', true);

                // get id of the live placeholder instead if such exists
                $relId = ($status != 'new' && $product->getPid() == '-1') ? $product->getT3verOid() : $id;

                if (!$newId) {
                    $this->belib->saveRelations($relId, $categories, 'tx_commerce_products_categories_mm', true, false);
                }
            }

            // if the live shadow is saved, the product relations have to be saved
            // to the versioned version
            if ($status == 'new' && $fieldArray['pid'] == '-1') {
                ++$id;
            }

            $this->saveProductRelations($id, $fieldArray);
        }
    }

    /**
     * Saves all relations between products and his attributes.
     *
     * @param int $productUid The UID of the product
     * @param array $fieldArray Field array
     */
    protected function saveProductRelations($productUid, array $fieldArray = null)
    {
        $productUid = (int) $productUid;
        // first step is to save all relations between this product and all attributes of this product.
        // We don't have to check for any parent categories, because the attributes
        // from them should already be saved for this product.

        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);

        // create an article and a new price for a new product
        if (ConfigurationUtility::getInstance()->getExtConf('simpleMode') && $productUid != null) {
            /** @var ArticlePriceRepository $articlePriceRepository */
            $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);
            $article = $articleRepository->findByProductUid($productUid);

            if (!empty($article)) {
                $articleUid = $article['uid'];
            } else {
                // create a new article if no one exists
                $product = $productRepository->findByUid($productUid);

                $articleUid = $articleRepository->addRecord([
                    'pid' => $fieldArray['pid'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'uid_product' => $productUid,
                    'article_type_uid' => 1,
                    'title' => $product['title'],
                ]);
            }

            // check if the article has already a price
            $row = $articlePriceRepository->findByArticleUid($articleUid);
            if (empty($row) && $article['sys_language_uid'] < 1) {
                // create a new price if no one exists
                $articlePriceRepository->addRecord([
                    'pid' => $fieldArray['pid'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'uid_article' => $articleUid,
                ]);
            }
        }

        $delete = true;
        if (isset($fieldArray['categories'])) {
            $catList = $productRepository->getParentCategories($productUid);
            $paList = $this->belib->getAttributesForCategoryList($catList);
            $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, ['uid_correlationtype']);

            if (!empty($uidList)) {
                // Insert/Update relations and remove all remaining
                $this->belib->saveRelations($productUid, $uidList, 'tx_commerce_products_attributes_mm', true, false);
                $this->belib->updateXML(
                    'attributes',
                    'tx_commerce_products',
                    $productUid,
                    'product',
                    $catList
                );
                $delete = false;
            }
        }

        $articles = false;
        if (isset($fieldArray['attributes'])) {
            /** @var AttributeRepository $attributeRepository */
            $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);

            // get all correlation types
            $correlationTypeList = $attributeRepository->findAllCorrelationTypes();
            $paList = [];

            // extract all attributes from FlexForm
            $ffData = GeneralUtility::xml2array($fieldArray['attributes']);
            if (is_array($ffData)) {
                $this->belib->mergeAttributeListFromFlexFormData(
                    $ffData['data']['sDEF']['lDEF'],
                    'ct_',
                    $correlationTypeList,
                    $productUid,
                    $paList
                );
            }
            // get the list of uid_foreign and save relations for this category
            $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, ['uid_correlationtype']);

            // get all ct4 attributes
            $ct4Attributes = [];
            if (is_array($uidList)) {
                foreach ($uidList as $uidItem) {
                    if ($uidItem['uid_correlationtype'] == 4) {
                        $ct4Attributes[] = $uidItem['uid_foreign'];
                    }
                }
            }

            // Insert/Update relations and remove only remaining relations if they were not added earlier in this method
            $this->belib->saveRelations($productUid, $uidList, 'tx_commerce_products_attributes_mm', $delete, false);

            /*
             * Rebuild the XML (last param set to true)
             * Fixes that l10n of products had invalid XML attributes
             */
            $this->belib->updateXML(
                'attributes',
                'tx_commerce_products',
                $productUid,
                'product',
                $correlationTypeList,
                true
            );

            // update the XML for this product, we remove everything that is not
            // set for current attributes
            $pXml = $productRepository->findByUid($productUid);
            if (!empty($pXml) && !empty($pXml['attributesedit'])) {
                $pXml = GeneralUtility::xml2array($pXml['attributesedit']);

                if (is_array($pXml['data']['sDEF']['lDEF'])) {
                    foreach (array_keys($pXml['data']['sDEF']['lDEF']) as $key) {
                        $data = [];
                        $uid = $this->belib->getUidFromKey($key, $data);
                        if (!in_array($uid, $ct4Attributes)) {
                            unset($pXml['data']['sDEF']['lDEF'][$key]);
                        }
                    }
                }

                if (is_array($pXml) && is_array($pXml['data']) && is_array($pXml['data']['sDEF'])) {
                    $pXml = GeneralUtility::array2xml($pXml, '', 0, 'T3FlexForms');
                    $fieldArray['attributesedit'] = $pXml;
                }
            }

            // now get all articles that where created from this product
            $articles = $articleRepository->findByProductUid($productUid);

            // build relation table
            if (is_array($articles) && !empty($articles)) {
                $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true);
                foreach ($articles as $article) {
                    $this->belib->saveRelations(
                        (int)$article['uid'],
                        $uidList,
                        'tx_commerce_articles_attributes_mm',
                        true,
                        false
                    );
                }
            }
        }

        $updateArrays = [];
        // update all articles of this product
        if (!empty($fieldArray['attributesedit'])) {
            $ffData = (array) GeneralUtility::xml2array($fieldArray['attributesedit']);
            if (is_array($ffData['data']) && is_array($ffData['data']['sDEF']['lDEF'])) {
                /** @var AttributeRepository $attributeRepository */
                $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);

                // get articles if they are not already there
                if (!$articles) {
                    $articles = $articleRepository->findByProductUid($productUid);
                }

                // update this product
                $articleRelations = [];
                $counter = 0;
                foreach ($ffData['data']['sDEF']['lDEF'] as $ffDataItemKey => $ffDataItem) {
                    ++$counter;

                    $keyData = [];
                    $attributeKey = $this->belib->getUidFromKey($ffDataItemKey, $keyData);
                    $attributeData = $attributeRepository->findByUid($attributeKey);

                    // check if the attribute has more than one value, if that is true,
                    // we have to create a relation for each value
                    if ($attributeData['multiple'] == 1) {
                        // if we have a multiple valuelist we need to handle the attributes a little
                        // bit different first we delete all existing attributes
                        $productRepository->deleteByProductAndAttribute($productUid, $attributeKey);

                        // now explode the data
                        $attributeValues = GeneralUtility::trimExplode(',', $ffDataItem['vDEF'], true);

                        foreach ($attributeValues as $attributeValue) {
                            // The first time an attribute value is selected, TYPO3 returns them
                            // INCLUDING an empty value! This would cause an unnecessary entry in the
                            // database, so we have to filter this here.
                            if (empty($attributeValue)) {
                                continue;
                            }

                            $updateData = $this->belib->getUpdateData($attributeData, $attributeValue, $productUid);
                            $productRepository->insertWithTable(
                                'tx_commerce_products_attributes_mm',
                                array_merge(
                                    [
                                        'uid_local' => $productUid,
                                        'uid_foreign' => $attributeKey,
                                        'uid_correlationtype' => 4,
                                    ],
                                    $updateData[0]
                                )
                            );
                        }
                    } else {
                        // update a simple valuelist and normal attributes as usual
                        $updateArrays = $this->belib->getUpdateData($attributeData, $ffDataItem['vDEF'], $productUid);
                        $productRepository->updateAttributeRelationValues($productUid, $attributeKey, $updateArrays);
                    }

                    // update articles
                    if (is_array($articles) && !empty($articles)) {
                        foreach ($articles as $article) {
                            if ($attributeData['multiple'] == 1) {
                                // if we have a multiple valuelist we need to handle the attributes a little
                                // bit different first we delete all existing attributes
                                $articleRepository->deleteByArticleAndAttribute($article['uid'], $attributeKey);

                                // now explode the data
                                $attributeValues = GeneralUtility::trimExplode(',', $ffDataItem['vDEF'], true);
                                $attributeCount = 0;
                                $attributeValue = '';
                                foreach ($attributeValues as $attributeValue) {
                                    if (empty($attributeValue)) {
                                        continue;
                                    }

                                    ++$attributeCount;

                                    $updateData = $this->belib->getUpdateData(
                                        $attributeData,
                                        $attributeValue,
                                        $productUid
                                    );
                                    $articleRepository->insertWithTable(
                                        'tx_commerce_articles_attributes_mm',
                                        array_merge(
                                            [
                                                'uid_local' => $article['uid'],
                                                'uid_foreign' => $attributeKey,
                                                'uid_product' => $productUid,
                                                'sorting' => $counter,
                                            ],
                                            $updateData[1]
                                        )
                                    );
                                }

                                // create at least an empty relation if no attributes where set
                                if ($attributeCount == 0) {
                                    $updateData = $this->belib->getUpdateData([], $attributeValue, $productUid);
                                    $articleRepository->insertWithTable(
                                        'tx_commerce_articles_attributes_mm',
                                        array_merge(
                                            [
                                                'uid_local' => $article['uid'],
                                                'uid_foreign' => $attributeKey,
                                                'uid_product' => $productUid,
                                                'sorting' => $counter,
                                            ],
                                            $updateData[1]
                                        )
                                    );
                                }
                            } else {
                                // if the article has already this attribute, we have to update so try
                                // to select this attribute for this article
                                $relationCount = $articleRepository->countAttributeRelations(
                                    $article['uid'],
                                    $attributeKey
                                );
                                if ($relationCount) {
                                    $articleRepository->updateRelation(
                                        $article['uid'],
                                        $attributeKey,
                                        array_merge($updateArrays[1], ['sorting' => $counter])
                                    );
                                } else {
                                    $articleRepository->insertWithTable(
                                        'tx_commerce_articles_attributes_mm',
                                        array_merge(
                                            $updateArrays[1],
                                            [
                                                'uid_local' => $article['uid'],
                                                'uid_product' => $productUid,
                                                'uid_foreign' => $attributeKey,
                                                'sorting' => $counter,
                                            ]
                                        )
                                    );
                                }
                            }

                            $relArray = $updateArrays[0];
                            $relArray['uid_foreign'] = $attributeKey;
                            if (!in_array($relArray, $articleRelations)) {
                                $articleRelations[] = $relArray;
                            }

                            $this->belib->updateArticleHash((int)$article['uid']);
                        }
                    }
                }

                // Finally update the Felxform for this Product
                $this->belib->updateArticleXML($articleRelations, false, null, $productUid);

                // And add those datas from the database to the articles
                if (is_array($articles) && !empty($articles)) {
                    foreach ($articles as $article) {
                        $thisArticleRelations = $this->belib->getAttributesForArticle((int)$article['uid']);

                        $this->belib->updateArticleXML($thisArticleRelations, false, $article['uid'], null);
                    }
                }
            }
        }

        // Check if we do have some localized products an call the method recursivly
        $productTranslations = $productRepository->findTranslationsByParentUidAndLanguage($productUid);
        foreach ($productTranslations as $productTranslation) {
            $this->saveProductRelations($productTranslation['uid'], $fieldArray);
        }
    }
}

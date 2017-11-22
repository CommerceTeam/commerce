<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use CommerceTeam\Commerce\Domain\Model\Product;
use CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository;
use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ArticlesDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * Preprocess article.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     *
     * @return array
     */
    public function preProcess(array &$incomingFieldArray, $id)
    {
        $this->updateArticleAttributeRelations($incomingFieldArray, $id);

        // create a new price if the checkbox was toggled get pid of article
        $pricesCount = is_numeric($incomingFieldArray['create_new_scale_prices_count']) ?
            (int) $incomingFieldArray['create_new_scale_prices_count'] : 0;
        $pricesSteps = is_numeric($incomingFieldArray['create_new_scale_prices_steps']) ?
            (int) $incomingFieldArray['create_new_scale_prices_steps'] : 0;
        $pricesStartamount = is_numeric($incomingFieldArray['create_new_scale_prices_startamount']) ?
            (int) $incomingFieldArray['create_new_scale_prices_startamount'] : 0;

        if ($pricesCount > 0 && $pricesSteps > 0 && $pricesStartamount > 0) {
            // somehow hook is used two times sometime. So switch off new creating.
            $incomingFieldArray['create_new_scale_prices_count'] = 0;

            // get pid
            $productPid = FolderRepository::initFolders('Products', FolderRepository::initFolders());

            // set some status vars
            $myScaleAmountStart = $pricesStartamount;
            $myScaleAmountEnd = $pricesStartamount + $pricesSteps - 1;

            // create the different prices
            for ($myScaleCounter = 1; $myScaleCounter <= $pricesCount; ++$myScaleCounter) {
                $insertArr = [
                    'pid' => $productPid,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'uid_article' => $id,
                    'fe_group' => $incomingFieldArray['create_new_scale_prices_fe_group'],
                    'price_scale_amount_start' => $myScaleAmountStart,
                    'price_scale_amount_end' => $myScaleAmountEnd,
                ];

                /** @var ArticlePriceRepository $articlePriceRepository */
                $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);
                $articlePriceRepository->addRecord($insertArr);

                // @todo update articles XML

                $myScaleAmountStart += $pricesSteps;
                $myScaleAmountEnd += $pricesSteps;
            }
        }

        return [];
    }

    /**
     * Update article attribute relations.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     */
    protected function updateArticleAttributeRelations(array $incomingFieldArray, $id)
    {
        if (isset($incomingFieldArray['attributesedit'])) {
            /** @var ArticleRepository $articleRepository */
            $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
            /** @var AttributeRepository $attributeRepository */
            $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);

            // get the data from the flexForm
            $attributes = $incomingFieldArray['attributesedit']['data']['sDEF']['lDEF'];

            foreach ($attributes as $aKey => $aValue) {
                $value = $aValue['vDEF'];
                $attributeId = $this->belib->getUidFromKey($aKey, $aValue);
                $attributeData = $attributeRepository->findByUid($attributeId);

                if ($attributeData['multiple'] == 1) {
                    // remove relations before creating new relations this is needed because we dont
                    // know which attribute were removed
                    $articleRepository->removeAttributeRelation($id, $attributeId);

                    $relCount = 0;
                    $relations = GeneralUtility::trimExplode(',', $value, true);
                    foreach ($relations as $relation) {
                        $updateArrays = $this->belib->getUpdateData($attributeData, $relation);
                        $updateArrays = $updateArrays[1];

                        $articleRepository->addAttributeRelation(
                            $id,
                            $attributeId,
                            $updateArrays['uid_product'],
                            $attributeData['sorting'],
                            $updateArrays['uid_valuelist'],
                            $updateArrays['value_char'],
                            $updateArrays['default_value']
                        );
                        ++$relCount;
                    }

                    // insert at least one relation
                    if (!$relCount) {
                        $articleRepository->addAttributeRelation(
                            $id,
                            $attributeId,
                            0,
                            $attributeData['sorting']
                        );
                    }
                } else {
                    $updateArrays = $this->belib->getUpdateData($attributeData, $value);
                    $articleRepository->updateRelation($id, $attributeId, $updateArrays[1]);
                }

                // recalculate hash for this article
                $this->belib->updateArticleHash($id);
            }
        }
    }

    /**
     * Checks if the permissions we need to process the datamap are still in place.
     *
     * @param string $status Status
     * @param string $_
     * @param int|string $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     */
    public function postProcess($status, $_, $id, array &$fieldArray, DataHandler $pObj)
    {
        $parentCategories = [];

        // Read the old parent product - skip this if we are copying or
        // overwriting an article
        if ($status != 'new' && !$this->getBackendUserAuthentication()->uc['txcommerce_copyProcess']) {
            /**
             * Article.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Article $article
             */
            $article = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Article::class, $id);
            $article->loadData();

            // get the parent categories of the product
            /**
             * Product.
             *
             * @var Product $product
             */
            $product = GeneralUtility::makeInstance(Product::class, $article->getParentProductUid());
            $product->loadData();

            if ($product->getL18nParent()) {
                $product = GeneralUtility::makeInstance(Product::class, $product->getL18nParent());
                $product->loadData();
            }

            $parentCategories = $product->getParentCategories();
        }

        // read new assigned product
        if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
            $parentCategories,
            ['editcontent']
        )) {
            $pObj->newlog('You dont have the permissions to edit the article.', 1);
            $fieldArray = [];
        }
    }
}

<?php
namespace CommerceTeam\Commerce\Domain\Model;

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

use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository;
use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use CommerceTeam\Commerce\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic class for handling products
 * Libary for Frontend-Rendering of products. This class
 * should be used for all Frontend renderings. No database calls
 * to the commerce tables should be made directly.
 * This Class is inhertited from
 * \CommerceTeam\Commerce\Domain\Model\AbstractEntity, all
 * basic database calls are made from a separate database Class
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private.
 *
 * Class \CommerceTeam\Commerce\Domain\Model\Product
 */
class Product extends AbstractEntity
{
    /**
     * Database class name.
     *
     * @var string
     */
    protected $repositoryClass = \CommerceTeam\Commerce\Domain\Repository\ProductRepository::class;

    /**
     * Database connection.
     *
     * @var \CommerceTeam\Commerce\Domain\Repository\ProductRepository
     */
    public $databaseConnection;

    /**
     * Field list.
     *
     * @var array
     */
    protected $fieldlist = [
        'uid',
        'pid',
        'title',
        'subtitle',
        'description',
        'teaser',
        'relatedpage',
        'l18n_parent',
        'manufacturer_uid',
        't3ver_oid',
        't3ver_id',
        't3ver_label',
        't3ver_wsid',
        't3ver_stage',
        't3ver_state',
        't3ver_tstamp',
    ];

    /* Data Variables */

    /**
     * Pid.
     *
     * @var int
     */
    public $pid = 0;

    /**
     * Title.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Subtitle.
     *
     * @var string
     */
    protected $subtitle = '';

    /**
     * Description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * @var array
     */
    protected $teaserimages;

    /**
     * @var array
     */
    protected $images;

    /**
     * Teaser.
     *
     * @var string
     */
    protected $teaser = '';

    /**
     * Related page
     *
     * @var int
     */
    public $relatedpage;

    /**
     * Translation parent
     *
     * @var int
     */
    public $l18n_parent;

    /**
     * Manufacturer id
     *
     * @var int
     */
    public $manufacturer_uid = 0;

    /**
     * Array of child articles.
     *
     * @var array
     */
    protected $articles;

    /**
     * Array of article uids.
     *
     * @var array
     */
    protected $articles_uids = [];

    /**
     * Attributes.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Attribute uids.
     *
     * @var array
     */
    public $attributes_uids = [];

    /**
     * Related products.
     *
     * @var array
     */
    public $relatedProducts = [];

    /**
     * Related product uids.
     *
     * @var array
     */
    public $relatedProduct_uids = [];

    /**
     * Related products loaded.
     *
     * @var bool
     */
    public $relatedProducts_loaded = false;

    /**
     * Maximum Articles to render for this product. Normally PHP_INT_MAX.
     *
     * @var int
     */
    public $renderMaxArticles = PHP_INT_MAX;

    /* Versioning */

    /**
     * Version oid.
     *
     * @var int
     */
    public $t3ver_oid = 0;

    /**
     * Version id.
     *
     * @var int
     */
    public $t3ver_id = 0;

    /**
     * Version label.
     *
     * @var string
     */
    public $t3ver_label = '';

    /**
     * Version workspace id.
     *
     * @var int
     */
    public $t3ver_wsid = 0;

    /**
     * Version state.
     *
     * @var int
     */
    public $t3ver_state = 0;

    /**
     * Version stage.
     *
     * @var int
     */
    public $t3ver_stage = 0;

    /**
     * Version timestamp.
     *
     * @var int
     */
    public $t3ver_tstamp = 0;

    /**
     * Constructor, basically calls init.
     *
     * @param int $uid Product uid
     * @param int $languageUid Language uid
     */
    public function __construct($uid = 0, $languageUid = 0)
    {
        if ((int) $uid) {
            $this->init($uid, $languageUid);
        }
    }

    /**
     * Class initialization.
     *
     * @param int $uid Uid of product
     * @param int $langUid Language uid, default 0
     *
     * @return bool TRUE if initialization was successful
     */
    public function init($uid, $langUid = 0)
    {
        $uid = (int) $uid;
        $langUid = (int) $langUid;

        if ($uid) {
            $this->uid = $uid;
            $this->lang_uid = $langUid;

            $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Domain/Model/Product', 'init');
            foreach ($hooks as $hook) {
                if (method_exists($hook, 'postinit')) {
                    $hook->postinit($this);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getArticles()
    {
        return $this->articles;
    }

    /**
     * Get list of article uids.
     *
     * @param int $uid Article uid
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Article Article uids
     */
    public function getArticle($uid)
    {
        $this->loadArticles();

        return $this->articles[$uid];
    }

    /**
     * Get number of articles of this product.
     *
     * @return int Number of articles
     */
    public function getArticlesCount()
    {
        $this->loadArticles();

        return count($this->articles);
    }

    /**
     * Get list of article objects.
     *
     * @return array Article objects
     */
    public function getArticleObjects()
    {
        $this->loadArticles();

        return $this->articles;
    }

    /**
     * Get list of article uids.
     *
     * @param int $index Index
     *
     * @return int Article uid
     */
    public function getArticleUid($index)
    {
        $this->loadArticles();

        return $this->articles_uids[$index];
    }

    /**
     * Get list of article uids.
     *
     * @return array Article uids
     */
    public function getArticleUids()
    {
        $this->loadArticles();

        return $this->articles_uids;
    }

    /**
     * Get list of articles of this product filtered by given attribute UID
     * and attribute value.
     *
     * @param int $attributeUid Attribute uid
     * @param mixed $attributeValue Attribute value
     *
     * @return array of article uids Article uids
     */
    public function getArticlesByAttribute($attributeUid, $attributeValue)
    {
        return $this->getArticlesByAttributeArray(
            [
                [
                    'AttributeUid' => $attributeUid,
                    'AttributeValue' => $attributeValue,
                ],
            ]
        );
    }

    /**
     * Get list of articles of this product filtered by given attribute UID
     * and attribute value.
     *
     * @param array $attributes Attributes [
     *     ['AttributeUid' => $attributeUID, 'AttributeValue' => $attributeValue],
     *     ['AttributeUid' => $attributeUID, 'AttributeValue' => $attributeValue],
     *     ...
     * ]
     * @param bool|int $proofUid Proof if script is running without instance
     *     and so without a single product
     *
     * @return array of article uids
     */
    public function getArticlesByAttributeArray(array $attributes, $proofUid = 1)
    {
        $first = true;
        $articleUids = [];
        foreach ($attributes as $uidValuePair) {
            // @todo change to article repository usage
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_commerce_articles');

            $queryBuilder
                ->select('a.uid')
                ->from('tx_commerce_articles', 'a')
                ->innerJoin('a', 'tx_commerce_articles_attributes_mm', 'mm', 'a.uid = mm.uid_local')
                ->innerJoin('mm', 'tx_commerce_attributes', 'at', 'mm.uid_foreign = at.uid')
                ->groupBy('a.uid');

            if ($proofUid) {
                $queryBuilder->where(
                    $queryBuilder->expr()->eq(
                        'a.uid_product',
                        $queryBuilder->createNamedParameter($this->uid, \PDO::PARAM_INT)
                    )
                );
            }

            $searchParts = $queryBuilder->expr()->orX();

            // attribute char is not used, thats why we check for id
            if (is_string($uidValuePair['AttributeValue'])) {
                $searchParts->add(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'at.uid',
                            $queryBuilder->createNamedParameter($uidValuePair['AttributeUid'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'mm.value_char',
                            $queryBuilder->createNamedParameter($uidValuePair['AttributeValue'], \PDO::PARAM_STR)
                        )
                    )
                );
            }

            // Nach dem charwert immer ueberpruefen, solange value_char noch nicht drin ist.
            if (is_float($uidValuePair['AttributeValue']) || (int) $uidValuePair['AttributeValue']) {
                $searchParts->add(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'at.uid',
                            $queryBuilder->createNamedParameter($uidValuePair['AttributeUid'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->in(
                            'mm.default_value',
                            $uidValuePair['AttributeValue']
                        )
                    )
                );

                $searchParts->add(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'at.uid',
                            $queryBuilder->createNamedParameter($uidValuePair['AttributeUid'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->in(
                            'mm.uid_valuelist',
                            $uidValuePair['AttributeValue']
                        )
                    )
                );
            }

            $result = $queryBuilder
                ->andWhere($searchParts)
                ->execute();

            $next = [];
            while ($data = $result->fetch()) {
                $next[] = $data['uid'];
            }

            // Return only the first article that exists in all arrays that's why the
            // first array get set and then array intersect checks the matching
            if ($first) {
                $articleUids = $next;
                $first = false;
            } else {
                $articleUids = array_intersect($articleUids, $next);
            }
        }

        if (!empty($articleUids)) {
            sort($articleUids);
        }

        return $articleUids;
    }

    /**
     * Returns list of articles (from this product) filtered by price.
     *
     * @param int $priceMin Smallest unit (e.g. cents)
     * @param int $priceMax Biggest unit (e.g. cents)
     * @param bool|int $usePriceGrossInstead Normally we check for net price,
     *      switch to gross price
     * @param bool|int $proofUid If script is running without instance and
     *      so without a single product
     *
     * @return array of article uids
     */
    public function getArticlesByPrice($priceMin = 0, $priceMax = 0, $usePriceGrossInstead = 0, $proofUid = 1)
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        // first get all real articles, then create objects and check prices do not
        // get prices directly from DB because we need to take (price) hooks into account

        $result = $articleRepository->findByProductAndType($proofUid ? $this->uid : 0, 1);

        // Run price test
        $articleUidList = [];
        while ($rawArticleUid = $result->fetch()) {
            /**
             * Article.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Article $article
             */
            $article = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Article::class,
                $rawArticleUid['uid'],
                $this->lang_uid
            );
            $article->loadData();
            $myPrice = $usePriceGrossInstead ? $article->getPriceGross() : $article->getPriceNet();
            if ($priceMin <= $myPrice && $myPrice <= $priceMax) {
                $articleUidList[] = $article->getUid();
            }
        }

        return $articleUidList;
    }

    /**
     * Get attribute matrix of products and articles
     * Both products and articles have a mm relation to the attribute table
     * This method gets the attributes of a product or an article and compiles
     * them to an unified array of attributes
     * This method handles the different types of values of an attribute:
     * character values, int values and value lists.
     *
     * @param mixed $articleList Array of restricted product articles
     *      (usually shall, must, ...), FALSE for all, FALSE for product
     *      attribute list
     * @param mixed $attributeListInclude Array of restricted attributes,
     *      FALSE for all
     * @param bool $valueListShowValueInArticleProduct TRUE if
     *      'showvalue' field of value list table should be cared of
     * @param string $sortingTable Name of table with sorting field of
     *      table to order records
     * @param bool $localizationAttributeValuesFallbackToDefault TRUE if a
     *      fallback to default value should be done if a localization of an
     *      attribute value or value char is not available in localized row
     * @param string $parentTable Name of parent table
     *
     * @return mixed Array if attributes where found, else FALSE
     * @throws \Exception
     */
    public function getAttributeMatrix(
        $articleList = false,
        $attributeListInclude = false,
        $valueListShowValueInArticleProduct = true,
        $sortingTable = 'tx_commerce_articles_attributes_mm',
        $localizationAttributeValuesFallbackToDefault = false,
        $parentTable = 'tx_commerce_articles'
    ) {
        // Early return if no product is given
        if (!$this->uid > 0) {
            return false;
        }
        if (!in_array($parentTable, ['tx_commerce_products', 'tx_commerce_articles'])) {
            throw new \Exception('Unsupported table ' . $parentTable, 1489233522323);
        }

        /** @var AttributeValueRepository $attributeValueRepository */
        $attributeValueRepository = GeneralUtility::makeInstance(AttributeValueRepository::class);

        /** @var ArticleRepository|ProductRepository $repository */
        if ($parentTable == 'tx_commerce_articles') {
            $repository = GeneralUtility::makeInstance(ArticleRepository::class);

            if ($articleList === false) {
                $articleList = $this->loadArticles();
            }
        } else {
            $repository = GeneralUtility::makeInstance(ProductRepository::class);
        }

        // Accumulated result array
        $targetData = [];

        // Attributes uids are added to this array if there is no language overlay for
        // an attribute to prevent fetching of non-existing language overlays in
        // subsequent rows for the same attribute
        $attributeLanguageOverlayBlacklist = [];

        // Execute main query
        $result = $repository->findSortedAttributeByArticle(
            $this->uid,
            $articleList,
            $attributeListInclude,
            $sortingTable
        );
        // Compile target data array
        while ($attributeDataRow = $result->fetch()) {
            // AttributeUid affected by this reord
            $currentUid = $attributeDataRow['attributes_uid'];

            // Don't handle this row if a prior row was already unable to fetch a language
            // overlay of the attribute
            if ($this->lang_uid > 0
                && !empty(array_intersect([$currentUid], $attributeLanguageOverlayBlacklist))
            ) {
                continue;
            }

            // Initialize array for this attribute uid and fetch attribute language overlay
            // for localization
            if (!isset($targetData[$currentUid])) {
                // Initialize target row and fill in attribute values
                $targetData[$currentUid]['title'] = $attributeDataRow['attributes_title'];
                $targetData[$currentUid]['unit'] = $attributeDataRow['attributes_unit'];
                $targetData[$currentUid]['values'] = [];
                $targetData[$currentUid]['valueuidlist'] = [];
                $targetData[$currentUid]['valueformat'] = $attributeDataRow['attributes_valueformat'];
                $targetData[$currentUid]['Internal_title'] = $attributeDataRow['attributes_internal_title'];
                $targetData[$currentUid]['icon'] = $attributeDataRow['attributes_icon'];

                // Fetch language overlay of attribute if given
                // Overwrite title, unit and Internal_title (sic!) of attribute
                if ($this->lang_uid > 0) {
                    $overwriteValues = [];
                    $overwriteValues['uid'] = $currentUid;
                    $overwriteValues['pid'] = $attributeDataRow['attributes_pid'];
                    $overwriteValues['sys_language_uid'] = $attributeDataRow['attritubes_sys_language_uid'];
                    $overwriteValues['title'] = $attributeDataRow['attributes_title'];
                    $overwriteValues['unit'] = $attributeDataRow['attributes_unit'];
                    $overwriteValues['internal_title'] = $attributeDataRow['attributes_internal_title'];

                    $languageOverlayRecord = $this->getTypoScriptFrontendController()->sys_page->getRecordOverlay(
                        'tx_commerce_attributes',
                        $overwriteValues,
                        $this->lang_uid,
                        $this->translationMode
                    );
                    if ($languageOverlayRecord) {
                        $targetData[$currentUid]['title'] = $languageOverlayRecord['title'];
                        $targetData[$currentUid]['unit'] = $languageOverlayRecord['unit'];
                        $targetData[$currentUid]['Internal_title'] = $languageOverlayRecord['internal_title'];
                    } else {
                        // Throw away array if there is no lang overlay, add to blacklist
                        unset($targetData[$currentUid]);
                        $attributeLanguageOverlayBlacklist[] = $currentUid;
                        continue;
                    }
                }
            }

            // There is a nasty difference between article and product attributes regarding
            // default_value field:
            // For attributes: default_value must be an int value and string values are
            // stored in value_char
            // For products: Everything is stored in default_value
            $defaultValue = false;
            if ($parentTable == 'tx_commerce_articles') {
                if ($attributeDataRow['default_value'] > 0) {
                    $defaultValue = true;
                }
            } else {
                if (strlen($attributeDataRow['default_value']) > 0) {
                    $defaultValue = true;
                }
            }

            // Handle value, default_value and value lists of attributes
            if (strlen($attributeDataRow['value_char']) || $defaultValue) {
                // Localization of value_char
                if ($this->lang_uid) {
                    // Get uid of localized article
                    // (lang_uid = selected lang and l18n_parent = current article)
                    $localizedArticle = $repository->findTranslationsByParentUidAndLanguage(
                        $attributeDataRow['parent_uid'],
                        $this->lang_uid
                    );

                    // Fetch the article-attribute mm record with localized article uid
                    // and current attribute
                    $localizedArticleUid = (int) $localizedArticle['uid'];
                    if ($localizedArticleUid > 0) {
                        $selectFields = [];
                        $selectFields[] = 'default_value';
                        // Again difference between product->attribute and article->attribute
                        if ($parentTable == 'tx_commerce_articles') {
                            $selectFields[] = 'value_char';
                        }
                        // Fetch mm record with overlay values
                        $localizedArticleAttributeValues =
                            $repository->findAttributeValuesByArticleAndAttribute(
                                $localizedArticleUid,
                                $currentUid
                            );

                        // Use value_char if set, else check for default_value, else use non
                        // localized value if enabled fallback
                        if (strlen($localizedArticleAttributeValues[0]['value_char']) > 0) {
                            $targetData[$currentUid]['values'][] =
                                $localizedArticleAttributeValues[0]['value_char'];
                        } elseif (strlen($localizedArticleAttributeValues[0]['default_value']) > 0) {
                            $targetData[$currentUid]['values'][] =
                                $localizedArticleAttributeValues[0]['default_value'];
                        } elseif ($localizationAttributeValuesFallbackToDefault) {
                            $targetData[$currentUid]['values'][] = $attributeDataRow['value_char'];
                        }
                    }
                } else {
                    // Use value_char if set, else default_value
                    if (strlen($attributeDataRow['value_char']) > 0) {
                        $targetData[$currentUid]['values'][] = $attributeDataRow['value_char'];
                    } else {
                        $targetData[$currentUid]['values'][] = $attributeDataRow['default_value'];
                    }
                }
            } elseif ($attributeDataRow['uid_valuelist']) {
                // Get value list rows
                $valueListArrayRows = $attributeValueRepository->findByUids($attributeDataRow['uid_valuelist']);
                foreach ($valueListArrayRows as $valueListArrayRow) {
                    // Ignore row if this value list has already been calculated
                    // This might happen if method is called with multiple article uid's
                    if (!empty(
                        array_intersect([$valueListArrayRow['uid']], $targetData[$currentUid]['valueuidlist'])
                    )) {
                        continue;
                    }

                    // Value lists must be localized.
                    // So overwrite current row with localization record
                    if ($this->lang_uid) {
                        $valueListArrayRow = $this->getTypoScriptFrontendController()->sys_page->getRecordOverlay(
                            'tx_commerce_attribute_values',
                            $valueListArrayRow,
                            $this->lang_uid,
                            $this->translationMode
                        );
                    }
                    if (!$valueListArrayRow) {
                        continue;
                    }

                    // Add value list row to target array
                    if ($valueListShowValueInArticleProduct || $valueListArrayRow['showvalue'] == 1) {
                        $targetData[$currentUid]['values'][] = $valueListArrayRow;
                        $targetData[$currentUid]['valueuidlist'][] = $valueListArrayRow['uid'];
                    }
                }
            }
        }

        // Return "I didn't found anything, so I'm not an array"
        // This hack is a re-implementation of the original matrix behaviour
        if (empty($targetData)) {
            return false;
        }

        // Sort value lists by sorting value
        foreach ($targetData as $attributeUid => $attributeValues) {
            if (count($attributeValues['valueuidlist']) > 1) {
                // compareBySorting is a special callback function to order
                // the array by its sorting value
                usort(
                    $targetData[$attributeUid]['values'],
                    [
                        \CommerceTeam\Commerce\Domain\Model\Product::class,
                        'compareBySorting',
                    ]
                );

                // Sort valuelist as well to get deterministic array output
                sort($attributeValues['valueuidlist']);
                $targetData[$attributeUid]['valueuidlist'] = $attributeValues['valueuidlist'];
            }
        }

        return $targetData;
    }

    /**
     * Evaluates the cheapest article for current product by gross price.
     *
     * @param int $usePriceNet If true, Compare prices by net instead of gross
     *
     * @return int|bool article id, FALSE if no article
     */
    public function getCheapestArticle($usePriceNet = 0)
    {
        $this->loadArticles();
        if (empty($this->articles_uids)) {
            return false;
        }

        $priceArr = [];
        $articleCount = count($this->articles_uids);
        for ($j = 0; $j < $articleCount; ++$j) {
            $article = &$this->articles[$this->articles_uids[$j]];
            if (is_object($article) && $article instanceof \CommerceTeam\Commerce\Domain\Model\Article) {
                $priceArr[$article->getUid()] = $usePriceNet ? $article->getPriceNet() : $article->getPriceGross();
            }
        }
        asort($priceArr);
        reset($priceArr);

        return current(array_keys($priceArr));
    }

    /**
     * @return Article
     */
    public function getCheapestPrice()
    {
        $cheapestArticleUid = $this->getCheapestArticle();
        return $this->articles[$cheapestArticleUid];
    }

    /**
     * Get l18n overlays of this product.
     *
     * @return array l18n overlay uids
     */
    public function getL18nProducts()
    {
        return $this->getRepository()->getL18nProducts($this->uid);
    }

    /**
     * Get manufacturer title.
     *
     * @return string manufacturer title
     */
    public function getManufacturerTitle()
    {
        $result = '';

        if ($this->getManufacturerUid()) {
            $result = $this->getRepository()->getManufacturerTitle($this->getManufacturerUid());
        }

        return $result;
    }

    /**
     * Get manufacturer UID of the product if set.
     *
     * @return int UID of manufacturer
     */
    public function getManufacturerUid()
    {
        return $this->manufacturer_uid;
    }

    /**
     * Get category master parent category.
     *
     * @return int uid of category
     */
    public function getMasterparentCategory()
    {
        return $this->getRepository()->getMasterParentCategory($this->uid);
    }

    /**
     * Get all parent categories.
     *
     * @return array Parent categories of product
     */
    public function getParentCategories()
    {
        return $this->getRepository()->getParentCategories($this->uid);
    }

    /**
     * Return product pid.
     *
     * @return int Product pid
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Returns the related page of the product.
     *
     * @return int related page
     */
    public function getRelatedPage()
    {
        return $this->relatedpage;
    }

    /**
     * Get related products.
     *
     * @return array related products
     */
    public function getRelatedProducts()
    {
        if (!$this->relatedProducts_loaded) {
            $this->relatedProduct_uids = $this->getRepository()->getRelatedProductUids($this->uid);
            if (!empty($this->relatedProduct_uids)) {
                foreach (array_keys($this->relatedProduct_uids) as $productId) {
                    /**
                     * Product.
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\Product $product
                     */
                    $product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        self::class,
                        $productId,
                        $this->lang_uid
                    );
                    $product->loadData();

                    // Check if the user is allowed to access the product and if the product
                    // has at least one article
                    if ($product->isAccessible() && $product->getArticlesCount()) {
                        $this->relatedProducts[] = $product;
                    }
                }
            }
            $this->relatedProducts_loaded = true;
        }

        return $this->relatedProducts;
    }

    /**
     * Sets renderMaxArticles Value in the Object.
     *
     * @param int $count New Value
     */
    public function setRenderMaxArticles($count)
    {
        $this->renderMaxArticles = (int) $count;
    }

    /**
     * Get renderMaxArticles Value in the Object.
     *
     * @return int RenderMaxArticles
     */
    public function getRenderMaxArticles()
    {
        return $this->renderMaxArticles;
    }

    /**
     * Generates a Matrix from these concerning articles for all attributes
     * and the values therefor.
     *
     * @param mixed $articleList Uids of articles or FALSE
     * @param mixed $attributesToInclude Array of attribute uids to include
     *     or FALSE for all attributes
     * @param bool $showHiddenValues Wether or net hidden values should be shown
     * @param string $sortingTable Default order by of attributes
     *
     * @return bool|array
     */
    public function getSelectAttributeMatrix(
        $articleList = false,
        $attributesToInclude = false,
        $showHiddenValues = true,
        $sortingTable = 'tx_commerce_articles_attributes_mm'
    ) {
        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);
        /** @var AttributeValueRepository $attributeValueRepository */
        $attributeValueRepository = GeneralUtility::makeInstance(AttributeValueRepository::class);
        $return = [];

        // If no list is given, take complate arctile-list from product
        if ($this->uid > 0) {
            if ($articleList == false) {
                $articleList = $this->loadArticles();
            }

            $result = $attributeRepository->findSortedByProductArticleAndAttributes(
                $this->uid,
                $articleList,
                $attributesToInclude,
                $sortingTable
            );

            if ($result->rowCount()) {
                while ($data = $result->fetch()) {
                    // Language overlay
                    if ($this->lang_uid > 0) {
                        $attributeData = $attributeRepository->findByUid($data['uid']);

                        // Result should contain only one Dataset
                        if (!empty($attributeData)) {
                            $attributeData = $this->getTypoScriptFrontendController()->sys_page->getRecordOverlay(
                                'tx_commerce_attributes',
                                $attributeData,
                                $this->lang_uid,
                                $this->translationMode
                            );

                            if (!is_array($attributeData)) {
                                // No Translation possible, so next interation
                                continue;
                            }

                            $data['title'] = $attributeData['title'];
                            $data['unit'] = $attributeData['unit'];
                            $data['internal_title'] = $attributeData['internal_title'];
                        }
                    }

                    $valueshown = false;

                    // Only get select attributs, since we don't need any other in selectattribut
                    // Matrix and we need the arrayKeys in this case. Get the localized values
                    // from tx_commerce_articles_attributes_mm
                    $valuelist = [];

                    $attributeValueResult = $attributeRepository->findByProductArticleAndAttribute(
                        $this->uid,
                        $articleList,
                        $data['uid']
                    );
                    if ($valueshown == false && $attributeValueResult->rowCount()) {
                        while ($value = $attributeValueResult->fetch()) {
                            if ($value['uid_valuelist'] > 0) {
                                $row = $attributeValueRepository->findByUid($value['uid_valuelist']);
                                if ($this->lang_uid > 0) {
                                    $row = $this->getTypoScriptFrontendController()->sys_page->getRecordOverlay(
                                        'tx_commerce_attribute_values',
                                        $row,
                                        $this->lang_uid,
                                        $this->translationMode
                                    );
                                    if (!is_array($row)) {
                                        continue;
                                    }
                                }
                                if ($showHiddenValues == true
                                    || ($showHiddenValues == false && $row['showvalue'] == 1)
                                ) {
                                    $valuelist[$row['uid']] = $row;
                                    $valueshown = true;
                                }
                            }
                        }
                        usort(
                            $valuelist,
                            [
                                \CommerceTeam\Commerce\Domain\Model\Product::class,
                                'compareBySorting',
                            ]
                        );
                    }

                    if ($valueshown == true) {
                        $return[$data['uid']] = [
                            'title' => $data['title'],
                            'unit' => $data['unit'],
                            'values' => $valuelist,
                            'valueformat' => $data['valueformat'],
                            'Internal_title' => $data['internal_title'],
                            'icon' => $data['icon'],
                            'iconmode' => $data['iconmode'],
                        ];
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Generates the matrix for attribute values for attribute
     * select options in FE.
     *
     * @param array $attributeValues Of attribute->value pairs,
     *      used as default.
     *
     * @return array Values
     */
    public function getSelectAttributeValueMatrix(array &$attributeValues = [])
    {
        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);
        /** @var AttributeValueRepository $attributeValueRepository */
        $attributeValueRepository = GeneralUtility::makeInstance(AttributeValueRepository::class);
        $values = [];
        $levelAttributes = [];

        if ($this->uid > 0) {
            $articleList = $this->loadArticles();
            $articleAttributes = $attributeRepository->findByArticleUids($articleList);

            $levels = [];
            $article = false;
            $attributeValuesList = [];
            $attributeValueSortIndex = [];

            foreach ($articleAttributes as $articleAttribute) {
                $attributeValuesList[] = $articleAttribute['uid_valuelist'];
                if ($article != $articleAttribute['uid_local']) {
                    $current = &$values;
                    foreach ($levels as $level) {
                        if (!isset($current[$level])) {
                            $current[$level] = [];
                        }
                        $current = &$current[$level];
                    }

                    $levels = [];
                    $levelAttributes = [];
                    $article = $articleAttribute['uid_local'];
                }
                $levels[] = $articleAttribute['uid_valuelist'];
                $levelAttributes[] = $articleAttribute['uid'];
            }

            $current = &$values;
            foreach ($levels as $level) {
                if (!isset($current[$level])) {
                    $current[$level] = [];
                }
                $current = &$current[$level];
            }

            // Get the sorting value for all attribute values
            if (!empty($attributeValuesList)) {
                $attributeValuesList = array_unique($attributeValuesList);
                $attributeValueSorts = $attributeValueRepository->findByUids($attributeValuesList);

                foreach ($attributeValueSorts as $attributeValueSort) {
                    $attributeValueSortIndex[$attributeValueSort['uid']] = $attributeValueSort['sorting'];
                }
            }
        }

        $selectMatrix = [];
        $possible = $values;
        $impossible = [];

        foreach ($levelAttributes as $attributeUid) {
            $tImpossible = [];
            $tPossible = [];
            $selected = $attributeValues[$attributeUid];
            if (!$selected) {
                /**
                 * Attribute.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Attribute $attribute
                 */
                $attribute = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Attribute::class,
                    $attributeUid,
                    $this->getTypoScriptFrontendController()->sys_language_uid
                );
                $attribute->loadData();
                $attributeValues[$attributeUid] = $selected = $attribute->getFirstAttributeValueUid($possible);
            }

            foreach ($impossible as $key => $val) {
                $selectMatrix[$attributeUid][$key] = 'disabled';
                foreach ($val as $k => $v) {
                    $tImpossible[$k] = $v;
                }
            }

            foreach ($possible as $key => $val) {
                $selectMatrix[$attributeUid][$key] = $selected == $key ? 'selected' : 'possible';
                foreach ($val as $k => $v) {
                    if (!$selected || $key == $selected) {
                        $tPossible[$k] = $v;
                    } else {
                        $tImpossible[$k] = $v;
                    }
                }
            }

            $possible = $tPossible;
            $impossible = $tImpossible;
        }

        return $selectMatrix;
    }

    /**
     * Returns the uid of the live version of this product.
     *
     * @return int UID of live version of this product
     */
    public function getT3verOid()
    {
        return $this->t3ver_oid;
    }

    /**
     * Return product title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Return product subtitle.
     *
     * @return string Product subtitle
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Return Product description.
     *
     * @return string Product description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the product teaser.
     *
     * @return string Product teaser
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * @return array
     */
    public function getTeaserimages():array
    {
        $this->initializeFileReferences($this->teaserimages, 'teaserimages');

        return $this->teaserimages;
    }

    /**
     * @return array
     */
    public function getImages():array
    {
        $this->initializeFileReferences($this->images, 'images');

        return $this->images;
    }

    /**
     * Load article list of this product and store in private class variable.
     *
     * @return array Article uids
     */
    public function loadArticles()
    {
        if (!is_array($this->articles)) {
            $uidToLoadFrom = $this->uid;
            if ($this->getT3verOid() > 0
                && $this->getT3verOid() != $this->uid
                && (
                    is_object($this->getTypoScriptFrontendController())
                    && $this->getTypoScriptFrontendController()->beUserLogin
                )
            ) {
                $uidToLoadFrom = $this->getT3verOid();
            }

            $this->articles = [];
            if (($this->articles_uids = $this->getRepository()->getArticles($uidToLoadFrom))) {
                foreach ($this->articles_uids as $articleUid) {
                    /**
                     * Article.
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\Article $article
                     */
                    $article = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        \CommerceTeam\Commerce\Domain\Model\Article::class,
                        $articleUid,
                        $this->lang_uid
                    );
                    $article->loadData();
                    $this->articles[$articleUid] = $article;
                }

                return $this->articles_uids;
            }
            return [];
        }

        return $this->articles_uids;
    }

    /**
     * Returns TRUE if one Article of Product have more than
     * zero articles on stock.
     *
     * @return bool TRUE if one article of product has stock > 0
     */
    public function hasStock()
    {
        $this->loadArticles();
        $result = false;
        /**
         * Article.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\Article $article
         */
        foreach ($this->articles as $article) {
            if ($article->getStock()) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Carries out the move of the product to the new parent
     * Permissions are NOT checked, this MUST be done beforehand.
     *
     * @param int $uid Uid of the move target
     * @param string $op  Operation of move (can be 'after' or 'into'
     *
     * @return bool True on success
     */
    public function move($uid, $op = 'after')
    {
        if ($op == 'into') {
            // Uid is a future parent
            $parentUid = $uid;
        } else {
            return false;
        }

        // Update parent_category
        $set = $this->getRepository()->updateRecord($this->uid, ['categories' => $parentUid]);

        // Update relations only, if parent_category was successfully set
        if ($set) {
            $catList = [$parentUid];
            $catList = BackendUtility::getUidListFromList($catList);
            $catList = BackendUtility::extractFieldArray($catList, 'uid_foreign', true);
            BackendUtility::saveRelations($this->uid, $catList, 'tx_commerce_products_categories_mm', true);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Remove article uid from array by index.
     *
     * @param int $index Index
     */
    public function removeArticleUid($index)
    {
        $this->loadArticles();
        unset($this->articles_uids[$index]);
    }

    /**
     * Remove article object from array by uid.
     *
     * @param int $uid Uid
     */
    public function removeArticle($uid)
    {
        unset($this->articles[$uid]);
    }

    /**
     * Compare an array record by its sorting value.
     *
     * @param array $array1 Left
     * @param array $array2 Right
     *
     * @return int
     */
    public static function compareBySorting(array $array1, array $array2)
    {
        return $array1['sorting'] - $array2['sorting'];
    }

    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     * @deprecated since 6.0.0 will be removed in 7.0.0
     */
    protected function getDatabaseConnection()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        return $GLOBALS['TYPO3_DB'];
    }
}

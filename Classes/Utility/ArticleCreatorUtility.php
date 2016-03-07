<?php
namespace CommerceTeam\Commerce\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ArticleCreatorUtility
{
    /**
     * @var int
     */
    protected $uid = 0;

    /**
     * @var int
     */
    protected $pid = 0;

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
     * Initializes the Article Creator if it's not called directly
     * from the Flexforms.
     *
     * @param int $uid Uid of the product
     * @param int $pid Page id
     *
     * @return void
     */
    public function init($uid, $pid)
    {
        $this->uid = (int) $uid;
        $this->pid = (int) $pid;

        // get all attributes for this product, if they where not fetched yet
        if ($this->attributes == null) {
            $this->attributes = $this->belib->getAttributesForProduct($this->uid, true, true, true);
        }

        // get existing articles for this product, if they where not fetched yet
        if ($this->existingArticles == null) {
            $this->existingArticles = $this->belib->getArticlesOfProduct($this->uid, '', 'sorting');
        }
    }


    /**
     * Creates all articles that should be created (defined through the POST vars).
     *
     * @param array $parameter Parameter
     *
     * @return void
     */
    public function createArticles(array $parameter)
    {
        $createList = GeneralUtility::_GP('createList');
        if (is_array($createList)) {
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid, value',
                'tx_commerce_attribute_values',
                'deleted = 0'
            );

            foreach ($rows as $row) {
                $this->flattedAttributes[$row['uid']] = $row['value'];
            }

            foreach (array_keys($createList) as $key) {
                $this->createArticle($parameter, $key);
            }

            BackendUtility::setUpdateSignal('updateCategoryTree');
        }
    }

    /**
     * Creates an article in the database and all needed releations to attributes
     * and values. It also creates a new prices and assignes it to the new article.
     *
     * @param array $parameter Parameter
     * @param string $key The key in the POST var array
     *
     * @return int Returns the new articleUid if success
     */
    protected function createArticle(array $parameter, $key)
    {
        $database = $this->getDatabaseConnection();

        // get the create data
        $data = GeneralUtility::_GP('createData');
        $hash = '';
        if (is_array($data)) {
            $data = $data[$key];
            $hash = md5($data);
            $data = unserialize($data);
        }

        // get the highest sorting
        $sorting = $database->exec_SELECTgetSingleRow(
            'uid, sorting',
            'tx_commerce_articles',
            'uid_product = ' . $this->uid,
            '',
            'sorting DESC'
        );
        $sorting = (is_array($sorting) && isset($sorting['sorting'])) ? $sorting['sorting'] + 20 : 0;

        // create article data array
        $articleData = [
            'pid' => $this->pid,
            'crdate' => time(),
            'title' => strip_tags($this->createArticleTitleFromAttributes($parameter, (array) $data)),
            'uid_product' => (int) $this->uid,
            'sorting' => $sorting,
            'article_attributes' => count($this->attributes['rest']) + count($data),
            'attribute_hash' => $hash,
            'article_type_uid' => 1,
        ];

        $temp = BackendUtility::getModTSconfig($this->pid, 'mod.commerce.category');
        if ($temp) {
            $moduleConfig = BackendUtility::implodeTSParams($temp['properties']);
            $defaultTax = (int) $moduleConfig['defaultTaxValue'];
            if ($defaultTax > 0) {
                $articleData['tax'] = $defaultTax;
            }
        }

        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'Utility/ArticleCreatorUtility',
            'createArticle'
        );
        if (method_exists($hookObject, 'preinsert')) {
            $hookObject->preinsert($articleData);
        }

        // create the article
        $database->exec_INSERTquery('tx_commerce_articles', $articleData);
        $articleUid = $database->sql_insert_id();

        // create a new price that is assigned to the new article
        $database->exec_INSERTquery(
            'tx_commerce_article_prices',
            [
                'pid' => $this->pid,
                'crdate' => time(),
                'tstamp' => time(),
                'uid_article' => $articleUid,
            ]
        );

        // now write all relations between article and attributes into the database
        $relationBaseData = [
            'uid_local' => $articleUid,
        ];

        $createdArticleRelations = [];
        $relationCreateData = $relationBaseData;

        $productsAttributesRes = $database->exec_SELECTquery(
            'sorting, uid_local, uid_foreign',
            'tx_commerce_products_attributes_mm',
            'uid_local = ' . (int) $this->uid
        );
        $attributesSorting = [];
        while (($productsAttributes = $database->sql_fetch_assoc($productsAttributesRes))) {
            $attributesSorting[$productsAttributes['uid_foreign']] = $productsAttributes['sorting'];
        }

        if (is_array($data)) {
            foreach ($data as $selectAttributeUid => $selectAttributeValueUid) {
                $relationCreateData['uid_foreign'] = $selectAttributeUid;
                $relationCreateData['uid_valuelist'] = $selectAttributeValueUid;

                $relationCreateData['sorting'] = $attributesSorting[$selectAttributeUid];

                $createdArticleRelations[] = $relationCreateData;
                $database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
            }
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
                    $relationCreateData['uid_product'] = $this->uid;
                }

                $relationCreateData['default_value'] = '';
                $relationCreateData['value_char'] = '';
                $relationCreateData['uid_valuelist'] = $attribute['uid_valuelist'];

                if (!$this->belib->isNumber($attribute['default_value'])) {
                    $relationCreateData['default_value'] = $attribute['default_value'];
                } else {
                    $relationCreateData['value_char'] = $attribute['default_value'];
                }

                $createdArticleRelations[] = $relationCreateData;

                $database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
            }
        }

        // update the article
        // we have to write the xml datastructure for this article. This is needed
        // to have the correct values inserted on first call of the article.
        $this->belib->updateArticleXML($createdArticleRelations, false, $articleUid);

        // Now check, if the parent Product is already lokalised, so creat Article in
        // the lokalised version Select from Database different localisations
        $resOricArticle = $database->exec_SELECTquery(
            '*',
            'tx_commerce_articles',
            'uid = ' . (int) $articleUid . ' AND deleted = 0'
        );
        $origArticle = $database->sql_fetch_assoc($resOricArticle);

        $result = $database->exec_SELECTquery(
            '*',
            'tx_commerce_products',
            'l18n_parent = ' . (int) $this->uid . ' AND deleted = 0'
        );

        if ($database->sql_num_rows($result)) {
            // Only if there are products
            while (($localizedProducts = $database->sql_fetch_assoc($result))) {
                // walk thru and create articles
                $destLanguage = $localizedProducts['sys_language_uid'];
                // get the highest sorting
                $langIsoCode = BackendUtility::getRecord(
                    'sys_language',
                    (int) $destLanguage,
                    'static_lang_isocode'
                );
                $langIdent = BackendUtility::getRecord(
                    'static_languages',
                    (int) $langIsoCode['static_lang_isocode'],
                    'lg_typo3'
                );
                $langIdent = strtoupper($langIdent['lg_typo3']);

                // create article data array
                $articleData = [
                    'pid' => $this->pid,
                    'crdate' => time(),
                    'title' => $parameter['title'],
                    'uid_product' => $localizedProducts['uid'],
                    'sys_language_uid' => $localizedProducts['sys_language_uid'],
                    'l18n_parent' => $articleUid,
                    'sorting' => $sorting['sorting'] + 20,
                    'article_attributes' => count($this->attributes['rest']) + count($data),
                    'attribute_hash' => $hash,
                    'article_type_uid' => 1,
                    'attributesedit' => $this->belib->buildLocalisedAttributeValues(
                        $origArticle['attributesedit'],
                        $langIdent
                    ),
                ];

                // create the article
                $database->exec_INSERTquery('tx_commerce_articles', $articleData);
                $localizedArticleUid = $database->sql_insert_id();

                // get all relations to attributes from the old article and copy them
                // to new article
                $res = $database->exec_SELECTquery(
                    '*',
                    'tx_commerce_articles_article_attributes_mm',
                    'uid_local = ' . (int) $origArticle['uid'] . ' AND uid_valuelist = 0'
                );

                while (($origRelation = $database->sql_fetch_assoc($res))) {
                    $origRelation['uid_local'] = $localizedArticleUid;

                    $database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $origRelation);
                }
                $this->belib->updateArticleXML($createdArticleRelations, false, $localizedArticleUid);
            }
        }

        return $articleUid;
    }

    /**
     * Creates article title out of attributes.
     *
     * @param array $parameter Parameter
     * @param array $data Data
     *
     * @return string Returns the product title + attribute titles for article title
     */
    protected function createArticleTitleFromAttributes(array $parameter, array $data)
    {
        $content = $parameter['title'];
        if (is_array($data) && !empty($data)) {
            $selectedValues = [];
            foreach ($data as $value) {
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

        if (is_array(GeneralUtility::_GP('updateData'))) {
            foreach (GeneralUtility::_GP('updateData') as $articleUid => $relData) {
                foreach ($relData as $attributeUid => $attributeValueUid) {
                    if ($attributeValueUid == 0) {
                        continue;
                    }

                    $database = $this->getDatabaseConnection();

                    $database->exec_UPDATEquery(
                        'tx_commerce_articles_article_attributes_mm',
                        'uid_local = ' . $articleUid . ' AND uid_foreign = ' . $attributeUid,
                        ['uid_valuelist' => $attributeValueUid]
                    );
                }

                $this->belib->updateArticleHash($articleUid, $fullAttributeList);
            }
        }
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

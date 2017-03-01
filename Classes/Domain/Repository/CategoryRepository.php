<?php
namespace CommerceTeam\Commerce\Domain\Repository;

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

use CommerceTeam\Commerce\Utility\BackendUtility;

/**
 * Database Class for tx_commerce_categories. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_category to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\CategoryRepository
 */
class CategoryRepository extends AbstractRepository
{
    /**
     * Database table.
     *
     * @var string
     */
    public $databaseTable = 'tx_commerce_categories';

    /**
     * Database parent category relation table.
     *
     * @var string
     */
    protected $databaseParentCategoryRelationTable = 'tx_commerce_categories_parent_category_mm';

    /**
     * Database attribute relation table.
     *
     * @var string Attribute rel table
     */
    protected $databaseAttributeRelationTable = 'tx_commerce_categories_attributes_mm';

    /**
     * Category sorting field.
     *
     * @var string
     */
    protected $categoryOrderField = 'tx_commerce_categories.sorting';

    /**
     * Product sorting field.
     *
     * @var string
     */
    protected $productOrderField = 'tx_commerce_products.sorting';

    /**
     * Uid of current Category.
     *
     * @var int
     */
    protected $uid;

    /**
     * Language Uid.
     *
     * @var int
     */
    protected $lang_uid;

    /**
     * @return int
     */
    public function getSystemCategoryUid()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uname',
                    $queryBuilder->createNamedParameter('SYSTEM', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'parent_category',
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return is_array($result) && isset($result['uid']) ? $result['uid'] : 0;
    }

    /**
     * Gets the "master" category from this category.
     *
     * @param int $uid Category uid
     *
     * @return int Category uid
     */
    public function getParentCategory($uid)
    {
        $parentUid = 0;
        if ($uid && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            $this->uid = $uid;

            $queryBuilder = $this->getQueryBuilderForTable($this->databaseParentCategoryRelationTable);
            $result = $queryBuilder
                ->select('uid_foreign')
                ->from($this->databaseParentCategoryRelationTable)
                ->where(
                    $queryBuilder->expr()->eq(
                        'is_reference',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute();

            if ($result->rowCount() > 0) {
                $parentUid = $result->fetch()['uid_foreign'];
            }
        }

        return $parentUid;
    }

    /**
     * Returns the permissions information for the category with the uid.
     *
     * @param int $uid Category UID
     *
     * @return array with permission information
     */
    public function getPermissionsRecord($uid)
    {
        $result = [];
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid) && $uid) {
            $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
            $result = $queryBuilder
                ->select('perms_everybody', 'perms_user', 'perms_group', 'perms_userid', 'perms_groupid', 'editlock')
                ->from($this->databaseTable)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
        }

        return is_array($result) ? $result : [];
    }

    /**
     * Gets the parent categories from this category.
     *
     * @param int $uid Category uid
     *
     * @return array Parent categories Uids
     */
    public function getParentCategories($uid)
    {
        if (empty($uid) || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            return [];
        }

        $database = $this->getDatabaseConnection();
        $frontend = $this->getTypoScriptFrontendController();
        $this->uid = $uid;
        if (is_object($frontend->sys_page)) {
            $additionalWhere = $frontend->sys_page->enableFields(
                $this->databaseTable,
                $frontend->showHiddenRecords
            );
        } else {
            $additionalWhere = ' AND ' . $this->databaseTable . '.deleted = 0';
        }

        $result = $database->exec_SELECT_mm_query(
            'uid_foreign',
            $this->databaseTable,
            $this->databaseParentCategoryRelationTable,
            $this->databaseTable,
            ' AND ' . $this->databaseParentCategoryRelationTable . '.uid_local = ' . $uid . ' ' . $additionalWhere
        );

        if ($result) {
            $data = [];
            while (($row = $database->sql_fetch_assoc($result))) {
                // @todo access_check for data sets
                $data[] = $row['uid_foreign'];
            }
            $database->sql_free_result($result);

            return $data;
        }

        return [];
    }

    /**
     * Returns an array of sys_language_uids of the i18n categories
     * Only use in BE.
     *
     * @param int $uid Uid of the category we want to get the i18n languages from
     *
     * @return array Array of UIDs
     */
    public function getL18nCategories($uid)
    {
        if (empty($uid) || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            return [];
        }

        $this->uid = $uid;
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            't1.title, t1.uid, t2.flag, t2.uid as sys_language',
            $this->databaseTable . ' AS t1 LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
            'l18n_parent = ' . $uid . ' AND deleted = 0'
        );

        $uids = [];
        foreach ($rows as $row) {
            $uids[] = $row;
        }

        return $uids;
    }

    /**
     * Gets the child categories from this category.
     *
     * @param int $uid Product UID
     * @param int $languageUid Language UID
     *
     * @return array Array of child categories UID
     */
    public function getChildCategories($uid, $languageUid = 0)
    {
        if (empty($uid) || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            return [];
        }

        $frontend = $this->getTypoScriptFrontendController();
        if ($languageUid == 0 && $frontend->sys_language_uid) {
            $languageUid = $frontend->sys_language_uid;
        }
        $this->uid = $uid;
        $this->lang_uid = $languageUid;

        // @todo Sorting should be by database
        // 'tx_commerce_categories_parent_category_mm.sorting'
        // as TYPO3 isn't currently able to sort by MM tables
        // We are using $this->databaseTable.sorting

        $localOrderField = $this->categoryOrderField;
        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'Domain/Repository/CategoryRepository',
            'getChildCategories'
        );
        if (is_object($hookObject) && method_exists($hookObject, 'categoryOrder')) {
            $localOrderField = $hookObject->categoryOrder($localOrderField, $this);
        }

        $result = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid_local',
            $this->databaseTable
            . ' INNER JOIN ' . $this->databaseParentCategoryRelationTable
            . ' AS mm ON ' . $this->databaseTable . '.uid = mm.uid_local'
            . ' INNER JOIN ' . $this->databaseTable . ' AS parent ON mm.uid_foreign = parent.uid',
            'parent.uid = ' . $uid . $this->enableFields(),
            '',
            $localOrderField
        );

        $return = [];
        if ($result) {
            $data = [];
            foreach ($result as $row) {
                // @todo access_check for datasets
                if ($languageUid == 0) {
                    $data[] = $row['uid_local'];
                } else {
                    // Check if a localised product is available for this category
                    // @todo Check if this is correct in Multi Tree Sites
                    $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
                    $translationCount = $queryBuilder
                        ->count('uid')
                        ->from($this->databaseTable)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'l18n_parent',
                                $queryBuilder->createNamedParameter($row['uid_local'], \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'sys_language_uid',
                                $queryBuilder->createNamedParameter($this->lang_uid, \PDO::PARAM_INT)
                            )
                        )
                        ->execute()
                        ->fetchColumn();

                    if ($translationCount > 0) {
                        $data[] = $row['uid_local'];
                    }
                }
            }

            if (is_object($hookObject) && method_exists($hookObject, 'categoryQueryPostHook')) {
                $data = $hookObject->categoryQueryPostHook($data, $this);
            }

            $return = $data;
        }

        return $return;
    }

    /**
     * Gets child products from this category.
     *
     * @param int $uid Product uid
     * @param int $languageUid Language uid
     *
     * @return array Array of child products UIDs
     */
    public function getChildProducts($uid, $languageUid = 0)
    {
        if (empty($uid) || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            return [];
        }

        $frontend = $this->getTypoScriptFrontendController();
        if ($languageUid == 0 && $frontend->sys_language_uid) {
            $languageUid = $frontend->sys_language_uid;
        }
        $this->uid = $uid;
        $this->lang_uid = $languageUid;

        $localOrderField = $this->productOrderField;

        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'Domain/Repository/CategoryRepository',
            'getChildProducts'
        );
        if (is_object($hookObject) && method_exists($hookObject, 'productOrder')) {
            $localOrderField = $hookObject->productOrder($localOrderField, $this);
        }

        $whereClause = 'tx_commerce_products_categories_mm.uid_foreign = ' . $uid;

        if (is_object($frontend->sys_page)) {
            $whereClause .= $this->enableFields('tx_commerce_products');
            $whereClause .= $this->enableFields('tx_commerce_articles');
            $whereClause .= $this->enableFields('tx_commerce_article_prices');
        }

        $queryArray = [
            'SELECT' => 'tx_commerce_products.uid',
            'FROM' => 'tx_commerce_products
                INNER JOIN tx_commerce_products_categories_mm 
                    ON tx_commerce_products.uid = tx_commerce_products_categories_mm.uid_local
                INNER JOIN tx_commerce_articles 
                    ON tx_commerce_products.uid = tx_commerce_articles.uid_product
                INNER JOIN tx_commerce_article_prices 
                    ON tx_commerce_articles.uid = tx_commerce_article_prices.uid_article',
            'WHERE' => $whereClause,
            'GROUPBY' => 'tx_commerce_products.uid',
            'ORDERBY' => $localOrderField,
            'LIMIT' => '',
        ];

        if (is_object($hookObject) && method_exists($hookObject, 'productQueryPreHook')) {
            $queryArray = $hookObject->productQueryPreHook($queryArray, $this);
        }

        $database = $this->getDatabaseConnection();

        $return = [];
        $rows = $database->exec_SELECTgetRows(
            $queryArray['SELECT'],
            $queryArray['FROM'],
            $queryArray['WHERE'],
            $queryArray['GROUPBY'],
            $queryArray['ORDERBY'],
            $queryArray['LIMIT']
        );
        if (!empty($rows)) {
            foreach ($rows as $row) {
                if ($languageUid == 0) {
                    $return[] = (int) $row['uid'];
                } else {
                    // Check if a localized product for current language is available
                    $lresult = $database->exec_SELECTquery(
                        'uid',
                        'tx_commerce_products',
                        'l18n_parent = ' . (int) $row['uid'] . ' AND sys_language_uid = ' . $this->lang_uid
                        . $this->enableFields('tx_commerce_products')
                    );
                    if ($database->sql_num_rows($lresult)) {
                        $return[] = (int) $row['uid'];
                    }
                }
            }

            if (is_object($hookObject) && method_exists($hookObject, 'productQueryPostHook')) {
                $return = $hookObject->productQueryPostHook($return, $this);
            }
        }

        return $return;
    }

    /**
     * Returns an array of array for the TS rootline
     * Recursive Call to build rootline.
     *
     * @param int $categoryUid Category uid
     * @param string $clause Where clause
     * @param array $result Result
     *
     * @return array
     */
    public function getCategoryRootline($categoryUid, $clause = '', array $result = [])
    {
        if (!empty($categoryUid) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($categoryUid)) {
            $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                'tx_commerce_categories.uid, mm.uid_foreign AS parent',
                'tx_commerce_categories
                    INNER JOIN tx_commerce_categories_parent_category_mm AS mm
                        ON tx_commerce_categories.uid = mm.uid_local',
                'tx_commerce_categories.uid = ' . $categoryUid . $this->enableFields()
            );

            if (!empty($row) && $row['parent'] != $categoryUid) {
                $result = $this->getCategoryRootline((int) $row['parent'], $clause, $result);
                $result = is_array($result) ? $result : [];
            }

            $result[] = [
                'uid' => $row['uid'],
            ];
        }

        return $result;
    }

    /**
     * @param int $parentCategoryUid
     * @return array
     */
    public function findByParentCategoryUid($parentCategoryUid)
    {
        $categories = (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            $this->databaseTable . '.*',
            $this->databaseTable
            . ' INNER JOIN ' . $this->databaseParentCategoryRelationTable . ' AS mm ON '
            . $this->databaseTable . '.uid = mm.uid_local',
            'mm.uid_foreign = ' . (int) $parentCategoryUid . $this->enableFields()
            . ' AND ' . BackendUtility::getCategoryPermsClause(1),
            '',
            $this->categoryOrderField
        );

        return $categories;
    }

    /**
     * Get relation.
     *
     * @param int $foreignUid Foreign uid
     *
     * @return array
     */
    public function findRelationByForeignUid($foreignUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseParentCategoryRelationTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseParentCategoryRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($foreignUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();

        return is_array($result) ? $result : [];
    }

    /**
     * This fetches all attributes that are assigned to a category.
     *
     * @param int $categoryUid Uid of the category
     * @param array $excludeAttributes Excluded attribute uids
     *
     * @return array of attributes
     */
    public function findAttributesByCategoryUid($categoryUid, array $excludeAttributes = null)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseAttributeRelationTable);
        $queryBuilder
            ->select('*')
            ->from($this->databaseAttributeRelationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting');

        // should we exclude some attributes
        if (is_array($excludeAttributes) && !empty($excludeAttributes)) {
            $excludeUids = [];
            foreach ($excludeAttributes as $excludeAttribute) {
                $excludeUids[] = (int) $excludeAttribute['uid_foreign'];
            }
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'uid_foreign',
                    $excludeUids
                )
            );
        }

        $result = $queryBuilder
            ->execute()
            ->fetchAll();

        return is_array($result) ? $result : [];
    }

    /**
     * Set delete flag and timestamp to current date for given translated products
     * by translation parent
     *
     * @param array $categoryUids
     */
    public function deleteByUids(array $categoryUids)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $categoryUids
                )
            )
            ->set('deleted', 1)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }

    /**
     * Set delete flag and timestamp to current date for given translated category
     * by translation parent
     *
     * @param array $categoryUids
     */
    public function deleteTranslationByParentUids(array $categoryUids)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'l18n_parent',
                    $categoryUids
                )
            )
            ->set('deleted', 1)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }
}

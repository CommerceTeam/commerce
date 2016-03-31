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
        $category = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            $this->databaseTable,
            'uname = \'SYSTEM\' AND parent_category = \'\' AND deleted = 0'
        );
        return isset($category['uid']) ? $category['uid'] : 0;
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
        $database = $this->getDatabaseConnection();

        $result = 0;
        if ($uid && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            $this->uid = $uid;
            $row = (array) $database->exec_SELECTgetSingleRow(
                'uid_foreign',
                $this->databaseParentCategoryRelationTable,
                'uid_local = ' . $uid . ' AND is_reference = 0'
            );
            if (!empty($row)) {
                $result = $row['uid_foreign'];
            }
        }

        return $result;
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
        $database = $this->getDatabaseConnection();

        $result = [];
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid) && $uid) {
            $result = (array) $database->exec_SELECTgetSingleRow(
                'perms_everybody, perms_user, perms_group, perms_userid, perms_groupid, editlock',
                $this->databaseTable,
                'uid = ' . $uid
            );
        }

        return $result;
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
        $frontend = $this->getFrontendController();
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
    public function getChildCategories($uid, $languageUid = -1)
    {
        if (empty($uid) || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            return [];
        }

        if ($languageUid == -1) {
            $languageUid = 0;
        }
        $this->uid = $uid;
        $frontend = $this->getFrontendController();
        if ($languageUid == 0 && $frontend->sys_language_uid) {
            $languageUid = $frontend->sys_language_uid;
        }
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
            $localOrderField = $hookObject->categoryOrder($this->categoryOrderField, $this);
        }

        $additionalWhere = $this->enableFields();

        $database = $this->getDatabaseConnection();

        $result = $database->exec_SELECT_mm_query(
            'uid_local',
            $this->databaseTable,
            $this->databaseParentCategoryRelationTable,
            $this->databaseTable,
            ' AND ' . $this->databaseParentCategoryRelationTable . '.uid_foreign = ' . $uid . ' ' . $additionalWhere,
            '',
            $localOrderField
        );

        $return = [];
        if ($result) {
            $data = [];
            while (($row = $database->sql_fetch_assoc($result))) {
                // @todo access_check for datasets
                if ($languageUid == 0) {
                    $data[] = (int) $row['uid_local'];
                } else {
                    // Check if a localised product is availiabe for this product
                    // @todo Check if this is correct in Multi Tree Sites
                    $lresult = $database->exec_SELECTquery(
                        'uid',
                        $this->databaseTable,
                        'l18n_parent = ' . (int) $row['uid_local'] . ' AND sys_language_uid = ' . $this->lang_uid
                        . $this->enableFields()
                    );

                    if ($database->sql_num_rows($lresult)) {
                        $data[] = (int) $row['uid_local'];
                    }
                }
            }

            if (is_object($hookObject) && method_exists($hookObject, 'categoryQueryPostHook')) {
                $data = $hookObject->categoryQueryPostHook($data, $this);
            }

            $database->sql_free_result($result);
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
    public function getChildProducts($uid, $languageUid = -1)
    {
        if (empty($uid) || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
            return [];
        }

        if ($languageUid == -1) {
            $languageUid = 0;
        }
        $this->uid = $uid;
        $frontend = $this->getFrontendController();
        if ($languageUid == 0 && $frontend->sys_language_uid) {
            $languageUid = $frontend->sys_language_uid;
        }
        $this->lang_uid = $languageUid;

        $localOrderField = $this->productOrderField;

        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'Domain/Repository/CategoryRepository',
            'getChildProducts'
        );
        if (is_object($hookObject) && method_exists($hookObject, 'productOrder')) {
            $localOrderField = $hookObject->productOrder($localOrderField, $this);
        }

        $whereClause = 'AND tx_commerce_products_categories_mm.uid_foreign = ' . $uid . '
            AND tx_commerce_products.uid = tx_commerce_articles.uid_product
            AND tx_commerce_articles.uid = tx_commerce_article_prices.uid_article ';
        $whereClause .= ' AND tx_commerce_article_prices.price_gross > 0';

        if (is_object($frontend->sys_page)) {
            $whereClause .= $this->enableFields('tx_commerce_products');
            $whereClause .= $this->enableFields('tx_commerce_articles');
            $whereClause .= $this->enableFields('tx_commerce_article_prices');
        }

            // Versioning - no deleted or versioned records, nor live placeholders
        $whereClause .= ' AND tx_commerce_products.deleted = 0
            AND tx_commerce_products.pid != -1
            AND tx_commerce_products.t3ver_state != 1';
        $queryArray = [
            'SELECT' => 'tx_commerce_products.uid',
            'FROM' => 'tx_commerce_products, tx_commerce_products_categories_mm, tx_commerce_articles,
                tx_commerce_article_prices',
            'WHERE' => 'tx_commerce_products.uid = tx_commerce_products_categories_mm.uid_local ' . $whereClause,
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
                    // Check if a localized product is available
                    // @todo Check if this is correct in multi tree sites
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
            $row = (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                'tx_commerce_categories.uid, mm.uid_foreign AS parent',
                'tx_commerce_categories
                    INNER JOIN tx_commerce_categories_parent_category_mm AS mm
                        ON tx_commerce_categories.uid = mm.uid_local',
                'tx_commerce_categories.uid = ' . $categoryUid . $this->enableFields()
            );

            if (!empty($row) && $row['parent'] != $categoryUid) {
                $result = $this->getCategoryRootline((int) $row['parent'], $clause, $result);
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
            . BackendUtility::getCategoryPermsClause(1),
            '',
            $this->databaseTable . '.sorting'
        );

        return $categories;
    }

    /**
     * Find by uid.
     *
     * @param int $uid Product uid
     * @param string $additionalWhere
     * @return array
     */
    public function findByUid($uid, $additionalWhere = '')
    {
        return (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            $this->databaseTable,
            'uid = ' . (int) $uid . $this->enableFields() . ($additionalWhere ? ' AND ' . $additionalWhere : '')
        );
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
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->databaseParentCategoryRelationTable,
            'uid_foreign = ' . (int) $foreignUid
        );
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
        // build the basic query
        $where = 'uid_local = ' . $categoryUid;

        // should we exclude some attributes
        if (is_array($excludeAttributes) && !empty($excludeAttributes)) {
            $excludeUids = [];
            foreach ($excludeAttributes as $excludeAttribute) {
                $excludeUids[] = (int) $excludeAttribute['uid_foreign'];
            }
            $where .= ' AND uid_foreign NOT IN (' . implode(',', $excludeUids) . ')';
        }

        // execute the query
        $result = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->databaseAttributeRelationTable,
            $where,
            '',
            'sorting'
        );
        return $result;
    }
}

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
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

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

        $this->uid = $uid;

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getRestrictions()->removeByType(
            HiddenRestriction::class
        );

        $result = $queryBuilder
            ->select('d.uid')
            ->from($this->databaseTable, 's')
            ->innerJoin('s', $this->databaseParentCategoryRelationTable, 'mm', 's.uid = mm.uid_local')
            ->innerJoin('mm', $this->databaseTable, 'd', 'mm.uid_foreign = d.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    's.uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        $data = [];
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) {
                $data[] = $row['uid'];
            }
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
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('c.title', 'c.uid', 's.flag', 's.uid AS sys_language')
            ->from($this->databaseTable, 'c')
            ->leftJoin('c', 'sys_language', 's', 'c.sys_language_uid = s.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'c.l18n_parent',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        $uids = [];
        while ($row = $result->fetch()) {
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

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('mm.uid_local')
            ->from($this->databaseTable, 's')
            ->innerJoin('s', $this->databaseParentCategoryRelationTable, 'mm', 's.uid = mm.uid_local')
            ->innerJoin('mm', $this->databaseTable, 'd', 'mm.uid_foreign = d.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'd.uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy(str_replace($this->databaseTable, 's', $localOrderField))
            ->execute();

        $return = [];
        if ($result->rowCount()) {
            $data = [];
            while ($row = $result->fetch()) {
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

        $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
        $queryBuilder
            ->select('p.uid')
            ->from('tx_commerce_products', 'p')
            ->innerJoin('p', 'tx_commerce_products_categories_mm', 'mm', 'p.uid = mm.uid_local')
            ->innerJoin('p', 'tx_commerce_articles', 'a', 'p.uid = a.uid_product')
            ->innerJoin('a', 'tx_commerce_article_prices', 'ap', 'a.uid = ap.uid_article')
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->groupBy('p.uid')
            ->orderBy(str_replace('tx_commerce_products', 'p', $localOrderField));

        if (is_object($hookObject) && method_exists($hookObject, 'productQueryPreHook')) {
            $queryBuilder = $hookObject->productQueryPreHook($queryBuilder, $this);
        }

        $result = $queryBuilder->execute();

        $return = [];
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) {
                if ($languageUid == 0) {
                    $return[] = (int) $row['uid'];
                } else {
                    $queryBuilder = $this->getQueryBuilderForTable('tx_commerce_products');
                    $translationCount = $queryBuilder
                        ->count('uid')
                        ->from('tx_commerce_products')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'l18n_parent',
                                $queryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'sys_language_uid',
                                $queryBuilder->createNamedParameter($this->lang_uid, \PDO::PARAM_INT)
                            )
                        )
                        ->execute()
                        ->fetchColumn();

                    // Check if a localized product for current language is available
                    if ($translationCount) {
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
            $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
            $row = $queryBuilder
                ->select('s.uid', 'mm.uid_foreign AS parent')
                ->from($this->databaseTable, 's')
                ->innerJoin('s', $this->databaseParentCategoryRelationTable, 'mm', 's.uid = mm.uid_local')
                ->where(
                    $queryBuilder->expr()->eq(
                        's.uid',
                        $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();

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
        $permissionClause = str_replace($this->databaseTable, 'c', BackendUtility::getCategoryPermsClause(1));

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('c.*')
            ->from($this->databaseTable, 'c')
            ->innerJoin('c', $this->databaseParentCategoryRelationTable, 'mm', 'c.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($parentCategoryUid, \PDO::PARAM_INT)
                )
            )
            ->andWhere($permissionClause)
            ->orderBy('c.sorting')
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
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
     * @param array $uidList
     *
     * @return array
     */
    public function findUntranslatedByUidList(array $uidList)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('uid')
            ->addSelectLiteral('CONCAT(uid, \'|\', title) AS value')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'uid',
                    $uidList
                )
            )
            ->groupBy('uid')
            ->execute();

        $return =  [];
        if ($result->rowCount()) {
            while ($row = $result->fetch()) {
                $return[$row['uid']] = $row;
            }
        }

        return $return;
    }

    /**
     * @param string $mmTable
     * @param int $uid
     *
     * @return array
     */
    public function findUntranslatedByRelationTable($mmTable, $uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('c.uid')
            ->addSelectLiteral('CONCAT(c.uid, \'|\', c.title) AS value')
            ->from($this->databaseTable, 'c')
            ->innerJoin('c', $mmTable, 'mm', 'c.uid = mm.uid_foreign')
            ->where(
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    [-1, 0]
                ),
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->groupBy('c.uid')
            ->orderBy('c.sorting')
            ->execute();

        $return =  [];
        if ($result->rowCount()) {
            while ($row = $result->fetch()) {
                $return[$row['uid']] = $row;
            }
        }

        return $return;
    }

    /**
     * @param int $categoryUid
     * @param string $andWhere
     * @return array|mixed
     */
    public function findRootlineCategoryByUid($categoryUid, $andWhere = '')
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder
            ->select(
                'c.uid',
                'c.hidden',
                'c.title',
                'c.ts_config',
                'c.t3ver_oid',
                'c.t3ver_wsid',
                'c.t3ver_state',
                'c.t3ver_stage',
                'c.perms_userid',
                'c.perms_groupid',
                'c.perms_user',
                'c.perms_group',
                'c.perms_everybody',
                'mm.uid_foreign AS pid'
            )
            ->from($this->databaseTable, 'c')
            ->innerJoin('c', 'pages', 'pa', 'c.pid = pa.uid')
            ->innerJoin('c', $this->databaseParentCategoryRelationTable, 'mm', 'c.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                )
            );

        if ($andWhere !== '') {
            $queryBuilder->andWhere($andWhere);
        }

        $result = $queryBuilder
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $uid
     *
     * @return array
     */
    public function findPreviousByUid($uid)
    {
        $row = $this->findByUid($uid);
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->lt(
                    'sorting',
                    $queryBuilder->createNamedParameter($row['sorting'], \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @return array
     */
    public function findLatestCategory()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->orderBy('uid', 'DESC')
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findWithoutParentReference()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('c.uid')
            ->from($this->databaseTable, 'c')
            ->leftJoin('c', $this->databaseParentCategoryRelationTable, 'mm', 'c.uid = mm.uid_local')
            ->where(
                $queryBuilder->expr()->isNull(
                    'mm.uid_local'
                )
            )
            ->execute();
        return $result;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findWithoutPermissionsSet()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'perms_user',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'perms_group',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'perms_everybody',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->execute();
        return $result;
    }

    /**
     * @param int $childUid
     * @param int $parentUid
     * @param int $sorting
     */
    public function insertParentRelation($childUid, $parentUid, $sorting)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseParentCategoryRelationTable);
        $queryBuilder
            ->insert($this->databaseParentCategoryRelationTable)
            ->values([
                'uid_local' => $childUid,
                'uid_foreign' => $parentUid,
                'sorting' => $sorting
            ])
            ->execute();
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

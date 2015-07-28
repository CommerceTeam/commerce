<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 * Inherited from \CommerceTeam\Commerce\Domain\Repository\Repository.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class ProductRepository extends Repository
{
    /**
     * Database table.
     *
     * @var string
     */
    public $databaseTable = 'tx_commerce_products';

    /**
     * Database attribute relation table.
     *
     * @var string
     */
    public $databaseAttributeRelationTable = 'tx_commerce_products_attributes_mm';

    /**
     * Database category relation table.
     *
     * @var string
     */
    public $databaseCategoryRelationTable = 'tx_commerce_products_categories_mm';

    /**
     * Database related product relation table.
     *
     * @var string
     */
    public $databaseProductsRelatedTable = 'tx_commerce_products_related_mm';

    /**
     * Sorting field.
     *
     * @var string
     */
    public $orderField = 'sorting';

    /**
     * Gets all articles form database related to this product.
     *
     * @param int $uid Product uid
     *
     * @return array of Article UID
     */
    public function getArticles($uid)
    {
        $uid = (int) $uid;
        $articleUids = array();

        $return = false;
        if ($uid) {
            $localOrderField = $this->orderField;
            $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
                'Domain/Repository/ProductRepository',
                'getArticles'
            );
            if (is_object($hookObject) && method_exists($hookObject, 'articleOrder')) {
                $localOrderField = $hookObject->articleOrder($this->orderField);
            }

            $where = 'uid_product = ' . $uid . $this->enableFields(
                'tx_commerce_articles',
                $this->getFrontendController()->showHiddenRecords
            );
            $additionalWhere = '';

            if (is_object($hookObject) && method_exists($hookObject, 'additionalWhere')) {
                $additionalWhere = $hookObject->additionalWhere($where);
            }

            $database = $this->getDatabaseConnection();
            $result = $database->exec_SELECTquery(
                'uid',
                'tx_commerce_articles',
                $where . ' ' . $additionalWhere,
                '',
                $localOrderField
            );
            if ($database->sql_num_rows($result)) {
                while (($data = $database->sql_fetch_assoc($result))) {
                    $articleUids[] = $data['uid'];
                }
                $database->sql_free_result($result);
                $return = $articleUids;
            } else {
                $this->error(
                    'exec_SELECTquery("uid", "tx_commerce_articles", "uid_product = ' . $uid . '"); returns no Result'
                );
            }
        }

        return $return;
    }

    /**
     * Gets all attributes form database related to this product
     * where corelation type = 4.
     *
     * @param int $uid Product uid
     * @param array|int $correlationtypes Correlation types
     *
     * @return array of Article UID
     */
    public function getAttributes($uid, $correlationtypes)
    {
        $return = false;
        if ((int) $uid) {
            if (!is_array($correlationtypes)) {
                $correlationtypes = array($correlationtypes);
            }

            $database = $this->getDatabaseConnection();
            $articleUids = array();
            $result = $database->exec_SELECTquery(
                'DISTINCT(uid_foreign) AS uid',
                $this->databaseAttributeRelationTable,
                'uid_local = ' . (int) $uid . ' AND uid_correlationtype IN (' . implode(',', $correlationtypes) . ')',
                '',
                $this->databaseAttributeRelationTable . '.sorting'
            );

            if ($database->sql_num_rows($result)) {
                while (($data = $database->sql_fetch_assoc($result))) {
                    $articleUids[] = (int) $data['uid'];
                }
                $database->sql_free_result($result);
                $return = $articleUids;
            } else {
                $this->error(
                    'exec_SELECTquery(\'DISTINCT(uid_foreign)\', ' . $this->databaseAttributeRelationTable .
                    ', \'uid_local = ' . (int) $uid . '\'); returns no Result'
                );
            }
        }

        return $return;
    }

    /**
     * Returns a list of uid's that are related to this product.
     *
     * @param int $uid Product uid
     *
     * @return array Product UIDs
     */
    public function getRelatedProductUids($uid)
    {
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'r.uid_foreign as uid',
            $this->databaseProductsRelatedTable . ' AS r',
            'r.uid_local = ' . (int) $uid,
            '',
            'r.sorting ASC',
            '',
            'uid'
        );
    }

    /**
     * Returns an array of sys_language_uids of the i18n products
     * Only use in BE.
     *
     * @param int $uid Uid of the product we want to get the i18n languages from
     *
     * @return array $uid uids
     */
    public function getL18nProducts($uid)
    {
        if (!(int) $uid) {
            return false;
        }

        $this->uid = $uid;

        $rows = (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            't1.title, t1.uid, t2.flag, t2.uid AS sys_language',
            $this->databaseTable . ' AS t1 LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
            'l18n_parent = ' . (int) $uid . ' AND deleted = 0'
        );

        return $rows;
    }

    /**
     * Get first category as master.
     *
     * @param int $uid Master parent category
     *
     * @return int
     */
    public function getMasterParentCategory($uid)
    {
        return reset($this->getParentCategories($uid));
    }

    /**
     * Gets the parent categories of th.
     *
     * @param int $uid Uid of the product
     *
     * @return array parent categories for products
     */
    public function getParentCategories($uid)
    {
        if (!(int) $uid) {
            $this->error('getParentCategories has not been delivered a proper uid');

            return null;
        }

        $uids = array();

        // read from sql
        $rows = (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid_foreign',
            $this->databaseCategoryRelationTable,
            'uid_local = ' . (int) $uid,
            '',
            'sorting ASC'
        );
        foreach ($rows as $row) {
            $uids[] = $row['uid_foreign'];
        }

        // If $uids is empty, the record might be a localized product
        if (empty($uids)) {
            $row = (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                'l18n_parent',
                $this->databaseTable,
                'uid = ' . $uid
            );
            if (!empty($row)) {
                $uids = $this->getParentCategories($row['l18n_parent']);
            }
        }

        return $uids;
    }

    /**
     * Returns the Manuafacturer Title to a given Manufacturere UID.
     *
     * @param int $manufacturer Manufacturer
     *
     * @return string Title
     */
    public function getManufacturerTitle($manufacturer)
    {
        $row = (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'title',
            'tx_commerce_manufacturer',
            'uid = ' . (int) $manufacturer
        );

        return isset($row['title']) ? $row['title'] : '';
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
            $this->databaseCategoryRelationTable,
            'uid_foreign = ' . (int) $foreignUid
        );
    }

    /**
     * Find by uids.
     *
     * @param array|string $uids Product uids
     *
     * @return array
     */
    public function findByUids($uids)
    {
        if (!is_array($uids)) {
            GeneralUtility::intExplode(',', $uids, true);
        }

        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, manufacturer_uid',
            $this->databaseTable,
            'uid IN (' . implode(',', $uids) . ')' . $this->enableFields($this->databaseTable)
        );
    }

    /**
     * Find by uid.
     *
     * @param int $uid Product uid
     *
     * @return array
     */
    public function findByUid($uid)
    {
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->databaseTable,
            'uid = ' . (int) $uid . $this->enableFields($this->databaseTable)
        );
    }
}

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 */
class ProductRepository extends AbstractRepository
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
        $return = false;
        $uid = (int) $uid;
        if ($uid) {
            $localOrderField = $this->orderField;
            $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
                'Domain/Repository/ProductRepository',
                'getArticles'
            );
            if (is_object($hookObject) && method_exists($hookObject, 'articleOrder')) {
                $localOrderField = $hookObject->articleOrder($this->orderField);
            }

            $where = 'uid_product = ' . $uid . $this->enableFields('tx_commerce_articles');
            $additionalWhere = '';
            if (is_object($hookObject) && method_exists($hookObject, 'additionalWhere')) {
                $additionalWhere = $hookObject->additionalWhere($where);
            }

            $articleUids = [];
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid',
                'tx_commerce_articles',
                $where . ' ' . $additionalWhere,
                '',
                $localOrderField
            );
            if (!empty($rows)) {
                foreach ($rows as $data) {
                    $articleUids[] = $data['uid'];
                }
                $return = $articleUids;
            } else {
                $this->error(
                    'exec_SELECTquery(\'uid\', \'tx_commerce_articles\', \'uid_product = '
                    . $uid . '\'); returns no Result'
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
        $uid = (int) $uid;
        if ((int) $uid) {
            if (!is_array($correlationtypes)) {
                $correlationtypes = [$correlationtypes];
            }

            $attributeUids = [];
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'DISTINCT(uid_foreign) AS uid',
                $this->databaseAttributeRelationTable,
                'uid_local = ' . (int) $uid . ' AND uid_correlationtype IN (' . implode(',', $correlationtypes) . ')',
                '',
                $this->databaseAttributeRelationTable . '.sorting'
            );
            if (!empty($rows)) {
                foreach ($rows as $data) {
                    $attributeUids[] = (int) $data['uid'];
                }
                $return = $attributeUids;
            } else {
                $this->error(
                    'exec_SELECTquery(\'DISTINCT(uid_foreign)\', ' . $this->databaseAttributeRelationTable
                    . ', \'uid_local = ' . (int) $uid . '\'); returns no Result'
                );
            }
        }

        return $return;
    }

    /**
     * @param int $uid
     * @return array
     */
    public function getAttributeRelations($uid)
    {
        $productsAttributes = (array)$this->getDatabaseConnection()->exec_SELECTgetRows(
            'sorting, uid_local, uid_foreign',
            $this->databaseAttributeRelationTable,
            'uid_local = ' . (int) $uid
        );

        return $productsAttributes;
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

        $rows = (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            't1.title, t1.uid, t2.flag, t2.uid AS sys_language_uid',
            $this->databaseTable . ' AS t1
            LEFT JOIN sys_language AS t2 ON t1.sys_language_uid = t2.uid',
            't1.l18n_parent = ' . (int) $uid . ' AND t1.deleted = 0'
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

        $uids = [];

        // read from sql
        $rows = (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid_foreign',
            $this->databaseCategoryRelationTable,
            'uid_local = ' . (int) $uid,
            '',
            'sorting'
        );
        foreach ($rows as $row) {
            $uids[] = $row['uid_foreign'];
        }

        // If $uids is empty, the record might be a localized product
        if (empty($uids)) {
            $row = $this->findByUid($uid);
            if (!empty($row) && isset($row['l18n_parent']) && $row['l18n_parent'] > 0) {
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
     * @param int $productUid Product uid
     *
     * @return array
     */
    public function findAttributeRelationByProductUid($productUid)
    {
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->databaseAttributeRelationTable,
            'uid_local = ' . (int) $productUid
        );
    }

    /**
     * Get relation.
     *
     * @param int $foreignUid Foreign uid
     *
     * @return array
     */
    public function findRelationByCategoryUid($foreignUid)
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
            'uid IN (' . implode(',', $uids) . ')' . $this->enableFields()
        );
    }

    /**
     * @param int $categoryUid
     * @param string $uname
     * @return array
     */
    public function findByCategoryAndUname($categoryUid, $uname)
    {
        $product = (array) $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'tx_commerce_products.*',
            $this->databaseCategoryRelationTable
            . ' AS mm
            INNER JOIN tx_commerce_products ON mm.uid_local = tx_commerce_products.uid',
            'tx_commerce_products.deleted = 0 AND tx_commerce_products.hidden = 0 AND mm.uid_foreign = '
            . (int) $categoryUid . ' AND uname = \'' . $uname . '\''
        );

        return $product;
    }

    /**
     * @param int $productUid
     * @param int $categorUid
     */
    public function addCategoryRelation($productUid, $categorUid)
    {
        $this->getDatabaseConnection()->exec_INSERTquery(
            $this->databaseCategoryRelationTable,
            [
                'uid_local' => $productUid,
                'uid_foreign' => $categorUid,
            ]
        );
    }

    /**
     * @param int $productUid
     * @param array $data
     * @return string
     */
    public function addAttributeRelation($productUid, $data)
    {
        $data['uid_local'] = (int) $productUid;

        $this->getDatabaseConnection()->exec_INSERTquery(
            $this->databaseAttributeRelationTable,
            $data
        );

        return $this->getDatabaseConnection()->sql_error();
    }

    /**
     * @param int $productUidFrom
     * @param int $productUidTo
     * @return string
     */
    public function updateAttributeRelation($productUidFrom, $productUidTo)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            $this->databaseAttributeRelationTable,
            'uid_local = ' . (int) $productUidFrom,
            ['uid_local' => (int) $productUidTo]
        );

        return $this->getDatabaseConnection()->sql_error();
    }

    /**
     * @param int $articleUid
     * @return array
     */
    public function findByArticleUid($articleUid)
    {
        $products = (array)$this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            $this->databaseTable . '.*',
            $this->databaseTable . '
            INNER JOIN tx_commerce_articles ON ' . $this->databaseTable . '.uid = tx_commerce_articles.uid_product',
            'tx_commerce_articles.uid = ' . (int) $articleUid
            . $this->enableFields()
            . $this->enableFields('tx_commerce_articles')
        );

        return $products;
    }
}

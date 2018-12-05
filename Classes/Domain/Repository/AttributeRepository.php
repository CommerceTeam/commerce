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

/**
 * Database class for tx_commerce_attributes. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_attribute to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\AttributeRepository
 */
class AttributeRepository extends AbstractRepository
{
    /**
     * Database table.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_attributes';

    /**
     * Database value table.
     *
     * @var string Child database table
     */
    protected $childDatabaseTable = 'tx_commerce_attribute_values';

    /**
     * Database value table.
     *
     * @var string Child database table
     */
    protected $correlationTypeDatabaseTable = 'tx_commerce_attribute_correlationtypes';

    /**
     * @param int $productUid
     *
     * @return array
     */
    public function findByProductUid($productUid)
    {
        $attributes = (array)$this->getDatabaseConnection()->exec_SELECTgetRows(
            'at.*',
            $this->databaseTable . ' AS at
            INNER JOIN tx_commerce_products_attributes_mm AS mm ON at.uid = mm.uid_foreign',
            'mm.uid_local = ' . $productUid . ' AND mm.uid_correlationtype = 4'
            . $this->enableFields($this->databaseTable, 'at')
        );

        return $attributes;
    }

    /**
     * @param int $articleUid
     *
     * @return array
     */
    public function findByArticleUid($articleUid)
    {
        // @todo fix this query to realy get attributes of article
        $attributes = (array)$this->getDatabaseConnection()->exec_SELECTgetRows(
            'at.*, pmm.uid_correlationtype',
            $this->databaseTable . ' AS at
            INNER JOIN tx_commerce_articles_attributes_mm AS amm ON at.uid = amm.uid_foreign
            INNER JOIN tx_commerce_articles AS a ON amm.uid_local = a.uid
            INNER JOIN tx_commerce_products AS p ON a.uid_product = p.uid
            INNER JOIN tx_commerce_products_attributes_mm AS pmm 
                ON (p.uid = pmm.uid_local AND at.uid = pmm.uid_foreign)',
            'a.uid = ' . (int)$articleUid
            . $this->enableFields($this->databaseTable, 'at')
            . $this->enableFields('tx_commerce_articles', 'a')
            . $this->enableFields('tx_commerce_products', 'p')
        );

        return $attributes;
    }

    /**
     * @return array
     */
    public function findAllCorrelationTypes()
    {
        $correlationTypes = (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->correlationTypeDatabaseTable,
            ''
        );

        return $correlationTypes;
    }

    /**
     * Gets a list of attribute_value_uids.
     *
     * @param int $uid Uid
     *
     * @return array
     */
    public function getAttributeValueUids($uid)
    {
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid',
            $this->childDatabaseTable,
            'attributes_uid = ' . (int) $uid . $this->enableFields($this->childDatabaseTable),
            '',
            'sorting'
        );

        $attributeValueList = [];
        if (!empty($rows)) {
            foreach ($rows as $data) {
                $attributeValueList[] = $data['uid'];
            }
        }

        return $attributeValueList;
    }

    /**
     * Get child attribute uids.
     *
     * @param int $uid Uid
     *
     * @return array
     */
    public function getChildAttributeUids($uid)
    {
        $childAttributeList = [];
        $uid = (int) $uid;
        if ($uid) {
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid',
                $this->databaseTable,
                'parent = ' . $uid . $this->enableFields(),
                '',
                'sorting'
            );

            if (!empty($rows)) {
                foreach ($rows as $data) {
                    $childAttributeList[] = $data['uid'];
                }
            }
        }

        return $childAttributeList;
    }
}

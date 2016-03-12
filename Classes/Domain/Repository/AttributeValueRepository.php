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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository
 */
class AttributeValueRepository extends Repository
{
    /**
     * Database table.
     *
     * @var string
     */
    public $databaseTable = 'tx_commerce_attribute_values';

    /**
     * Find by attribute in page.
     *
     * @param int $attributeUid Attribute uid
     * @param int $pageId Page id
     *
     * @return array
     */
    public function findByAttributeInPage($attributeUid, $pageId)
    {
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->databaseTable,
            'pid = ' . $pageId . ' AND attributes_uid = ' . $attributeUid .
            BackendUtility::deleteClause($this->databaseTable)
        );
    }
}

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

use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database Class for tx_commerce_products. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository
 */
class AttributeValueRepository extends AbstractRepository
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
    public function findByAttributeInPage($attributeUid, $pageId): array
    {
        /** @var DeletedRestriction $deleteRestriction */
        $deleteRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add($deleteRestriction);

        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'attributes_uid',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();

        return is_array($result) ? $result :[];
    }

    /**
     * @param array $uids
     *
     * @return array
     */
    public function findByUids(array $uids): array
    {
        $uids = array_map('intval', $uids);

        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $uids
                )
            )
            ->execute()
            ->fetchAll();

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $attributeUid
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function findByAttributeUid($attributeUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'attributes_uid',
                    $queryBuilder->createNamedParameter($attributeUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute();
    }
}

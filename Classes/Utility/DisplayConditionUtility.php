<?php
namespace CommerceTeam\Commerce\Utility;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class DisplayConditionUtility
 */
class DisplayConditionUtility
{
    /**
     * @param array $parameter
     * @return bool
     */
    public function checkProductCorrelationType($parameter)
    {
        $count = 0;
        if (!empty($parameter['record']) && isset($parameter['record']['uid'])) {
            $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                'tx_commerce_products
                INNER JOIN tx_commerce_products_attributes_mm AS mm ON tx_commerce_products.uid = mm.uid_local
                INNER JOIN tx_commerce_attributes ON mm.uid_foreign = tx_commerce_attributes.uid',
                'mm.uid_correlationtype = 4 AND tx_commerce_products.uid = ' . (int) $parameter['record']['uid']
                . BackendUtility::deleteClause('tx_commerce_products')
                . BackendUtility::deleteClause('tx_commerce_attributes')
            );

            if (!$count) {
                $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
                    '*',
                    'tx_commerce_products_categories_mm AS cm
                    INNER JOIN tx_commerce_categories ON cm.uid_foreign = tx_commerce_categories.uid
                    INNER JOIN tx_commerce_categories_attributes_mm AS mm ON tx_commerce_categories.uid = mm.uid_local
                    INNER JOIN tx_commerce_attributes ON mm.uid_foreign = tx_commerce_attributes.uid',
                    'mm.uid_correlationtype = 4 AND cm.uid_local = ' . (int) $parameter['record']['uid']
                    . BackendUtility::deleteClause('tx_commerce_categories')
                    . BackendUtility::deleteClause('tx_commerce_attributes')
                );
            }
        }
        return $count > 0;
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

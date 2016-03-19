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
                'mm.uid_correlationtype = 4 AND tx_commerce_products.uid = ' . $parameter['record']['uid']
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
                    'mm.uid_correlationtype = 4 AND cm.uid_local = ' . $parameter['record']['uid']
                    . BackendUtility::deleteClause('tx_commerce_categories')
                    . BackendUtility::deleteClause('tx_commerce_attributes')
                );
            }
        }
        return $count > 0;
    }


    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

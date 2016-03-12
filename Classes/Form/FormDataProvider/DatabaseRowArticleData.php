<?php
namespace CommerceTeam\Commerce\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle article values on row.
 */
class DatabaseRowArticleData implements FormDataProviderInterface
{
    /**
     * Initialize new row with default values from various sources
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if (!$this->isValidRecord($result)) {
            return $result;
        }

        $belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
        $attributes = $belib->getAttributesForProduct($result['vanillaUid'], true, true, true);
        $articles = $belib->getArticlesOfProduct($result['vanillaUid'], '', 'sorting');

        if (is_array($attributes['ct1'])) {
            foreach ($articles as &$article) {
                $article['_reference_count'] = $this->getArticleReferenceCount($article['uid']);

                foreach ($attributes['ct1'] as &$attribute) {
                    // get all article attribute relations
                    $attribute['values'] = $this->getDatabaseConnection()->exec_SELECTgetRows(
                        'uid_valuelist, default_value, value_char',
                        'tx_commerce_articles_article_attributes_mm',
                        'uid_local = ' . $article['uid'] . ' AND uid_foreign = ' . $attribute['uid_foreign']
                    );
                }
            }
        }

        $result['databaseRow']['attributes'] = $attributes;
        $result['databaseRow']['articles'] = $articles;

        return $result;
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    protected function isValidRecord($result)
    {
        return $result['tableName'] == 'tx_commerce_products';
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param int $uid UID of the referenced record, must be > 0
     *
     * @return int the number of references to record $uid in table
     *      $tableName, will be >= 0
     */
    protected function getArticleReferenceCount($uid)
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'sys_refindex',
            'deleted = 0 AND ref_table = \'tx_commerce_articles\' AND ref_uid = ' . (int) $uid
        );
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

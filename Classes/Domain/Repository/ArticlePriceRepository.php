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
 * Database Class for tx_commerce_article_prices. All database calle should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_article_price to get informations for articles.
 *
 * Basic abtract Class for Database Query for
 * Database retrival class fro product
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository
 */
class ArticlePriceRepository extends Repository
{
    /**
     * Table concerning the prices.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_article_prices';

    /**
     * Get data.
     * Special Implementation for prices, as they don't have a localisation'
     *
     * @param int $uid UID for Data
     *
     * @return array assoc array with data
     * @todo implement access_check concering category tree
     */
    public function getData($uid)
    {
        $proofSql = '';
        if (is_object($this->getFrontendController()->sys_page)) {
            $proofSql = $this->enableFields($this->databaseTable, $this->getFrontendController()->showHiddenRecords);
        }

        $returnData = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            $this->databaseTable,
            'uid = ' . (int)$uid . $proofSql
        );

        // Result should contain only one Dataset
        if (count($returnData) == 1) {
            return $returnData;
        }

        $this->error(
            'exec_SELECTquery(\'*\',' . $this->databaseTable . ',\'uid = '
            . $uid . '\'); returns no or more than one Result'
        );

        return false;
    }
}

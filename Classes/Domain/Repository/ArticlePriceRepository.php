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
 * Database class for tx_commerce_article_prices. All database calls should
 * be made by this class. In most cases you should use the methods
 * provided by tx_commerce_article_price to get information for articles.
 *
 * Basic abstract class for database query for
 * database retrieval class from product
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository
 */
class ArticlePriceRepository extends AbstractRepository
{
    /**
     * Table concerning the prices.
     *
     * @var string
     */
    protected $databaseTable = 'tx_commerce_article_prices';

    /**
     * Get data.
     * Special Implementation for prices, as they don't have a localisation
     *
     * @param int $uid UID for Data
     * @param int $langUid Language Uid
     * @param bool $translationMode Translation Mode for record set
     *
     * @return array assoc array with data
     */
    public function getData($uid, $langUid = -1, $translationMode = false): array
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        // result should contain only one data set
        if ($result->rowCount() == 1) {
            return $result->fetch();
        }

        $this->error(
            'query tx_commerce_article_prices with uid ' . $uid . '; # returns no or more than one result'
        );

        return [];
    }

    /**
     * @param int $articleUid uid of the article to get the price off
     *
     * @return array
     */
    public function findByArticleUid($articleUid): array
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $prices = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_article',
                    $queryBuilder->createNamedParameter($articleUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();

        return is_array($prices) ? $prices : [];
    }

    /**
     * Set delete flag and timestamp to current date for given articles
     *
     * @param array $articleUids
     */
    public function deleteByArticleUids(array $articleUids)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->update($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid_article',
                    $articleUids
                )
            )
            ->set('deleted', 1)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->execute();
    }
}

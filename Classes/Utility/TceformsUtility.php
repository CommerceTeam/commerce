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

/**
 * ItemProc Methods for flexforms.
 *
 * Class \CommerceTeam\Commerce\Utility\TceformsUtility
 */
class TceformsUtility
{
    /**
     * Products selector
     * Result is returned via reference.
     *
     * @param array $data Data
     */
    public function productsSelector(array &$data = [])
    {
        $database = $this->getDatabaseConnection();

        $numArticleNumbersShow = 3;

        $addWhere = 'tx_commerce_articles.article_type_uid = ' . NORMALARTICLETYPE;
        $addWhere .= ' AND tx_commerce_products.deleted = 0 AND tx_commerce_articles.deleted = 0';
        if ($data['row']['sys_language_uid'] > 0) {
            $addWhere .= ' AND tx_commerce_products.sys_language_uid = ' . $data['row']['sys_language_uid'] . ' ';
        }
        $products = $database->exec_SELECTgetRows(
            'DISTINCT tx_commerce_products.title, tx_commerce_products.uid, tx_commerce_products.sys_language_uid,
                count(tx_commerce_articles.uid) as anzahl',
            'tx_commerce_products
                INNER JOIN tx_commerce_articles ON tx_commerce_products.uid = tx_commerce_articles.uid_product',
            $addWhere,
            'tx_commerce_products.title, tx_commerce_products.uid, tx_commerce_products.sys_language_uid',
            'tx_commerce_products.title, tx_commerce_products.sys_language_uid'
        );
        $data['items'] = [];
        $items = [['', -1]];
        foreach ($products as $product) {
            // Select Languages
            $language = '';

            if ($product['sys_language_uid'] > 0) {
                $rowLanguage = $database->exec_SELECTgetSingleRow(
                    'title',
                    'sys_language',
                    'uid = ' . $product['sys_language_uid']
                );
                if (!empty($rowLanguage)) {
                    $language = $rowLanguage['title'];
                }
            }

            $title = $product['title'] . ($language ? ' [' . $language . '] ' : '');

            if ($product['anzahl'] > 0) {
                $articles = $database->exec_SELECTgetRows(
                    'eancode, l18n_parent, ordernumber',
                    'tx_commerce_articles',
                    'tx_commerce_articles.uid_product = ' . $product['uid'] .
                    ' AND tx_commerce_articles.deleted = 0'
                );

                if (!empty($articles)) {
                    $rowCount = count($articles);
                    $count = 0;
                    $eancodes = [];
                    $ordernumbers = [];

                    foreach ($articles as $article) {
                        if (($count == $numArticleNumbersShow)) {
                            break;
                        }
                        if ($article['l18n_parent'] > 0) {
                            $articleTranslationParent = $database->exec_SELECTgetSingleRow(
                                'eancode, ordernumber',
                                'tx_commerce_articles',
                                'tx_commerce_articles.uid = ' . $article['l18n_parent']
                            );

                            if (!empty($articleTranslationParent)) {
                                $article = $articleTranslationParent;
                            }
                        }
                        if ($article['eancode'] != '') {
                            $eancodes[] = $article['eancode'];
                        }
                        if ($article['ordernumber'] != '') {
                            $ordernumbers[] = $article['ordernumber'];
                        }
                        ++$count;
                    }

                    if (count($ordernumbers) >= count($eancodes)) {
                        $numbers = implode(',', $ordernumbers);
                    } else {
                        $numbers = implode(',', $eancodes);
                    }

                    if ($rowCount > $count) {
                        $numbers .= ',...';
                    }
                    $title .= ' (' . $numbers . ')';
                }
            }

            $items[] = [$title, $product['uid']];
        }

        $data['items'] = $items;
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

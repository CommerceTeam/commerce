<?php
namespace CommerceTeam\Commerce\Utility;

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
        $resProducts = $database->exec_SELECTquery(
            'DISTINCT tx_commerce_products.title, tx_commerce_products.uid, tx_commerce_products.sys_language_uid,
                count(tx_commerce_articles.uid) as anzahl',
            'tx_commerce_products
                INNER JOIN tx_commerce_articles ON tx_commerce_products.uid = tx_commerce_articles.uid_product',
            $addWhere,
            'tx_commerce_products.title, tx_commerce_products.uid, tx_commerce_products.sys_language_uid',
            'tx_commerce_products.title, tx_commerce_products.sys_language_uid'
        );
        $data['items'] = [];
        $items = [];
        $items[] = ['', -1];
        while (($product = $database->sql_fetch_assoc($resProducts))) {
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
                $resArticles = $database->exec_SELECTquery(
                    'eancode, l18n_parent, ordernumber',
                    'tx_commerce_articles',
                    'tx_commerce_articles.uid_product = ' . $product['uid'] .
                    ' AND tx_commerce_articles.deleted = 0'
                );

                if ($resArticles) {
                    $rowCount = $database->sql_num_rows($resArticles);
                    $count = 0;
                    $eancodes = [];
                    $ordernumbers = [];

                    while (($rowArticles = $database->sql_fetch_assoc($resArticles))
                        && ($count < $numArticleNumbersShow)
                    ) {
                        if ($rowArticles['l18n_parent'] > 0) {
                            $articleTranslationParent = $database->exec_SELECTgetSingleRow(
                                'eancode, ordernumber',
                                'tx_commerce_articles',
                                'tx_commerce_articles.uid = ' . $rowArticles['l18n_parent']
                            );

                            if (!empty($articleTranslationParent)) {
                                $rowArticles = $articleTranslationParent;
                            }
                        }
                        if ($rowArticles['eancode'] != '') {
                            $eancodes[] = $rowArticles['eancode'];
                        }
                        if ($rowArticles['ordernumber'] != '') {
                            $ordernumbers[] = $rowArticles['ordernumber'];
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
        $database->sql_free_result($resProducts);

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

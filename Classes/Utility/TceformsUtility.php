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
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class TceformsUtility
{
    /**
     * Products selector
     * Result is returned via reference.
     *
     * @param array $data Data
     */
    public function productsSelector(array &$data = array())
    {
        $database = $this->getDatabaseConnection();

        $numArticleNumbersShow = 3;

        $addWhere = ' AND tx_commerce_articles.article_type_uid = ' . NORMALARTICLETYPE . ' ';
        if ($data['row']['sys_language_uid'] > 0) {
            $addWhere .= ' and tx_commerce_products.sys_language_uid = ' . $data['row']['sys_language_uid'] . ' ';
        }
        $addWhere .= ' and tx_commerce_products.deleted = 0 and tx_commerce_articles.deleted =0 ';
        $resProducts = $database->exec_SELECTquery(
            'DISTINCT tx_commerce_products.title, tx_commerce_products.uid, tx_commerce_products.sys_language_uid,
                count(tx_commerce_articles.uid) as anzahl',
            'tx_commerce_products,tx_commerce_articles',
            'tx_commerce_products.uid=tx_commerce_articles.uid_product ' . $addWhere,
            'tx_commerce_products.title,tx_commerce_products.uid, tx_commerce_products.sys_language_uid',
            'tx_commerce_products.title,tx_commerce_products.sys_language_uid'
        );
        $data['items'] = array();
        $items = array();
        $items[] = array('', -1);
        while (($rowProducts = $database->sql_fetch_assoc($resProducts))) {
            // Select Languages
            $language = '';

            if ($rowProducts['sys_language_uid'] > 0) {
                $resLanguage = $database->exec_SELECTquery(
                    'title',
                    'sys_language',
                    'uid = ' . $rowProducts['sys_language_uid']
                );
                if (($rowLanguage = $database->sql_fetch_assoc($resLanguage))) {
                    $language = $rowLanguage['title'];
                }
            }

            if ($language) {
                $title = $rowProducts['title'] . ' [' . $language . '] ';
            } else {
                $title = $rowProducts['title'];
            }

            if ($rowProducts['anzahl'] > 0) {
                $resArticles = $database->exec_SELECTquery(
                    'eancode, l18n_parent, ordernumber',
                    'tx_commerce_articles',
                    'tx_commerce_articles.uid_product = ' . $rowProducts['uid'] .
                    ' and tx_commerce_articles.deleted = 0'
                );

                if ($resArticles) {
                    $rowCount = $database->sql_num_rows($resArticles);
                    $count = 0;
                    $eancodes = array();
                    $ordernumbers = array();

                    while (($rowArticles = $database->sql_fetch_assoc($resArticles))
                        && ($count < $numArticleNumbersShow)
                    ) {
                        if ($rowArticles['l18n_parent'] > 0) {
                            $resL18nParent = $database->exec_SELECTquery(
                                'eancode,ordernumber',
                                'tx_commerce_articles',
                                'tx_commerce_articles.uid=' . $rowArticles['l18n_parent']
                            );

                            if ($resL18nParent) {
                                $rowL18nParents = $database->sql_fetch_assoc($resL18nParent);
                                if ($rowL18nParents['eancode'] != '') {
                                    $eancodes[] = $rowL18nParents['eancode'];
                                }
                                if ($rowL18nParents['ordernumber'] != '') {
                                    $ordernumbers[] = $rowL18nParents['ordernumber'];
                                }
                            } else {
                                if ($rowArticles['eancode'] != '') {
                                    $eancodes[] = $rowArticles['eancode'];
                                }
                                if ($rowArticles['ordernumber'] != '') {
                                    $ordernumbers[] = $rowArticles['ordernumber'];
                                }
                            }
                        } else {
                            if ($rowArticles['eancode'] != '') {
                                $eancodes[] = $rowArticles['eancode'];
                            }
                            if ($rowArticles['ordernumber'] != '') {
                                $ordernumbers[] = $rowArticles['ordernumber'];
                            }
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

            $items[] = array($title, $rowProducts['uid']);
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

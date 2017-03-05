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

use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
use CommerceTeam\Commerce\Domain\Repository\SysLanguageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        /** @var SysLanguageRepository $sysLanguageRepository */
        $sysLanguageRepository = GeneralUtility::makeInstance(SysLanguageRepository::class);
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        $numArticleNumbersShow = 3;

        $items = [['', -1]];
        $products = $productRepository->findSelectorProducts($data['row']['sys_language_uid']);
        foreach ($products as $product) {
            // Select Languages
            $language = '';

            if ($product['sys_language_uid'] > 0) {
                $rowLanguage = $sysLanguageRepository->findByUid($product['sys_language_uid']);
                if (!empty($rowLanguage)) {
                    $language = $rowLanguage['title'];
                }
            }

            $title = $product['title'] . ($language ? ' [' . $language . '] ' : '');

            if ($product['anzahl'] > 0) {
                $articles = $articleRepository->findByProductUid($product['uid']);
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
                            $articleTranslationParent = $articleRepository->findByUid($article['l18n_parent']);
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
}

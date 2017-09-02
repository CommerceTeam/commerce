<?php
namespace CommerceTeam\Commerce\Hooks;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * Hook for article_class
 * This class is ment as programming-tutorial
 * for programming hooks for delivery_costs
 *
 * Class \CommerceTeam\Commerce\Hook\ArticleHooks
 */
class ArticleHook
{
    /**
     * Basic Method to calculate the delivereycost (net)
     * Ment as Programming tutorial. Mostly you have to change or add functionality.
     *
     * @param int $netPrice Net price
     * @param \CommerceTeam\Commerce\Domain\Model\Article $article Article
     */
    public function calculateDeliveryCostNet(&$netPrice, \CommerceTeam\Commerce\Domain\Model\Article &$article)
    {
        $deliveryArticle = $this->getDeliveryArticle($article);
        if ($deliveryArticle) {
            $netPrice = $deliveryArticle->getPriceNet();
        } else {
            $netPrice = 0;
        }
    }

    /**
     * Basic Method to calculate the delivereycost (gross)
     * Ment as Programming tutorial. Mostly you have to change or add functionality.
     *
     * @param int $grossPrice Gross price
     * @param \CommerceTeam\Commerce\Domain\Model\Article $article Article
     */
    public function calculateDeliveryCostGross(&$grossPrice, \CommerceTeam\Commerce\Domain\Model\Article &$article)
    {
        $deliveryArticle = $this->getDeliveryArticle($article);
        if ($deliveryArticle) {
            $grossPrice = $deliveryArticle->getPriceGross();
        } else {
            $grossPrice = 0;
        }
    }

    /**
     * Load the deliveryArticle.
     *
     * @param \CommerceTeam\Commerce\Domain\Model\Article $article Article
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Article $result
     */
    protected function getDeliveryArticle(\CommerceTeam\Commerce\Domain\Model\Article &$article)
    {
        $deliveryConf = ConfigurationUtility::getInstance()->getConfiguration('SYSPRODUCTS.DELIVERY.types');
        $classname = array_shift(array_keys($deliveryConf));

        /**
         * Article repository.
         *
         * @var \CommerceTeam\Commerce\Domain\Repository\ArticleRepository
         */
        $articleRepository = GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Repository\ArticleRepository::class
        );
        $row = $articleRepository->findByClassname($classname);

        $result = false;
        if (!empty($row)) {
            /**
             * Instantiate article class.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Article $deliveryArticle
             */
            $deliveryArticle = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Article::class,
                $row['uid'],
                $article->getLang()
            );

            /*
             * Do not call loadData at this point, since loadData recalls this hook,
             * so we have a non ending recursion
             */
            if (is_object($deliveryArticle)) {
                $deliveryArticle->loadPrices();
            }
            $result = $deliveryArticle;
        }

        return $result;
    }
}

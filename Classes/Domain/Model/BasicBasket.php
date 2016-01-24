<?php
namespace CommerceTeam\Commerce\Domain\Model;

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
 * Basic class for basket_handling.
 *
 * Abstract libary for Basket Handling. This class should not be used directly,
 * instead use tx_commerce_basket.
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * Class \CommerceTeam\Commerce\Domain\Model\BasicBasket
 *
 * @todo: Implement basket as singleton
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class BasicBasket
{
    /**
     * Array for storing basket_items in the basket.
     *
     * @var array
     */
    protected $basketItems = array();

    /**
     * Net basket sum.
     *
     * @var int
     */
    protected $basketSumNet = 0;

    /**
     * Gross basket sum.
     *
     * @var int
     */
    protected $basketSumGross = 0;

    /**
     * Calculated pric from net price.
     *
     * @var int
     */
    protected $pricefromnet = 0;

    /**
     * Number of items in basket.
     *
     * @var
     */
    protected $items = 0;

    /**
     * Creation timestamp of this basket.
     *
     * @var int
     */
    protected $crdate = 0;

    /**
     * True if basket is set to read only.
     *
     * @var bool
     */
    protected $readOnly = false;

    /**
     * Configuration.
     *
     * @var array
     */
    public $conf = array();

    /**
     * Get count of all articles of this type
     * Useful for geting the delivery cost.
     *
     * Example:
     * $basket->getArticleTypeSumNet(PAYMENTARTICLETYPE)
     * $basket->getArticleTypeSumNet(DELIVERYARTICLETYPE)
     *
     * @param int $articleTypeUid Article type uid
     *
     * @return int Count
     */
    public function getArticleTypeCount($articleTypeUid)
    {
        $count = 0;

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        foreach ($this->basketItems as $oneItem) {
            if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Get count of all articles of a specific type.
     *
     * @param array $articleTypes Article types
     *
     * @return int
     */
    public function getArticleTypeCountFromList(array $articleTypes)
    {
        $count = 0;

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        foreach ($this->basketItems as $oneItem) {
            if (in_array($oneItem->getArticle()->getArticleTypeUid(), $articleTypes)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Return sum of all articles of this type
     * Useful to geting the delivery cost.
     *
     * Example:
     * $basket->getArticleTypeSumNet(PAYMENTARTICLETYPE)
     * $basket->getArticleTypeSumNet(DELIVERYARTICLETYPE)
     *
     * @param int $articleTypeUid Article type uid
     *
     * @return int Price
     */
    public function getArticleTypeSumNet($articleTypeUid)
    {
        $sumNet = 0;

        /**
         * Basket item
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        if ($this->pricefromnet == 0) {
            $grossSumArray = array();
            foreach ($this->basketItems as $oneItem) {
                if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                    $grossSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumGross();
                }
            }
            foreach ($grossSumArray as $taxrate => $rateGrossSum) {
                $sumNet += (int) round($rateGrossSum / (1 + (((float) $taxrate) / 100)));
            }
        } else {
            foreach ($this->basketItems as $oneItem) {
                if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                    $sumNet += ($oneItem->getQuantity() * $oneItem->getPriceNet());
                }
            }
        }

        return $sumNet;
    }

    /**
     * Return sum of all article prices of this type
     * Useful for geting the delivery cost.
     *
     * Example:
     * $basket->getArticleTypeSumGross(PAYMENTARTICLETYPE)
     * $basket->getArticleTypeSumGross(DELIVERYARTICLETYPE)
     *
     * @param int $articleTypeUid Article type uid
     *
     * @return int sum
     */
    public function getArticleTypeSumGross($articleTypeUid)
    {
        $sumGross = 0;

        /**
         * Basket item
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        if ($this->pricefromnet == 1) {
            $netSumArray = array();
            foreach ($this->basketItems as $oneItem) {
                if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                    $netSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumNet();
                }
            }
            foreach ($netSumArray as $taxrate => $rateGrossSum) {
                $sumGross += (int) round($rateGrossSum * (1 + (((float) $taxrate) / 100)));
            }
        } else {
            foreach ($this->basketItems as $oneItem) {
                if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                    $sumGross += ($oneItem->getQuantity() * $oneItem->getPriceGross());
                }
            }
        }

        return $sumGross;
    }

    /**
     * Returns an array of articles to a corresponding article_type.
     *
     * @param int $articleTypeUid Article type
     *
     * @return array or article ids
     */
    public function getArticlesByArticleTypeUidAsUidlist($articleTypeUid)
    {
        $result = array();

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        foreach ($this->basketItems as $uid => $oneItem) {
            if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                $result[] = $uid;
            }
        }

        return $result;
    }

    /**
     * Create an array of assoc arrays from the basket articles.
     *
     * Array(
     *     uid => array(
     *         'article' => result form tx_commerce_article->return_assoc_array(),
     *         'product' => result form tx_commerce_product->return_assoc_array(),
     *     ),
     *     uid2 =>array(
     *         'article' => result form tx_commerce_article->return_assoc_array();
     *         'product' => result form tx_commerce_product->return_assoc_array();
     *     ),
     * )
     *
     * @param string $prefix Prefix for the keys
     *
     * @return array or arrays
     */
    public function getAssocArrays($prefix = '')
    {
        $result = array();

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        foreach ($this->basketItems as $oneuid => $oneItem) {
            $result[$oneuid] = $oneItem->getArrayOfAssocArray($prefix);
        }

        return $result;
    }

    /**
     * Return basket has value.
     *
     * @return string Basket hash value
     */
    public function getBasketHashValue()
    {
        $result = false;

        if (!$this->getFrontendController()->tmpl->setup['plugin.']['tx_commerce_pi1.']['dontUseBasketHashValue']
            && !empty($this->basketItems)
        ) {
            $result = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(serialize($this->basketItems));
        }

        return $result;
    }

    /**
     * Get a specific item object from basket.
     *
     * @param int $itemUid The item uid to get
     *
     * @return \CommerceTeam\Commerce\Domain\Model\BasketItem Item object
     */
    public function getBasketItem($itemUid)
    {
        return $this->basketItems[$itemUid];
    }

    /**
     * Getter.
     *
     * @return array
     */
    public function getBasketItems()
    {
        return $this->basketItems;
    }

    /**
     * Get first title from of all articles concerning this type.
     *
     * Eexample:
     * $basket->getFirstArticleTypeTitle(PAYMENTARTICLETYPE)
     * $basket->getFirstArticleTypeTitle(DELIVERYARTICLETYPE)
     *
     * @param int $articleTypeUid Article type uid
     *
     * @return string Title
     */
    public function getFirstArticleTypeTitle($articleTypeUid)
    {
        $result = '';

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        foreach ($this->basketItems as $oneItem) {
            if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                if ($oneItem->getArticle()->getTitle() > '') {
                    $result = $oneItem->getArticle()->getTitle();
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the first Description from of all Articles concerning this type
     *
     * Example:
     * $basket->getFirstArticleTypeDescription(PAYMENTARTICLETYPE)
     * $basket->getFirstArticleTypeDescription(DELIVERYARTICLETYPE)
     *
     * @param string $articleTypeUid Article type uid
     *
     * @return string Description
     */
    public function getFirstArticleTypeDescription($articleTypeUid)
    {
        $result = '';

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        foreach ($this->basketItems as $oneItem) {
            if ($oneItem->getArticle()->getArticleTypeUid() == $articleTypeUid) {
                if ($oneItem->getArticle()->getDescriptionExtra() > '') {
                    $result = $oneItem->getArticle()->getDescriptionExtra();
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get number of items in basket.
     *
     * @return int Number of items
     */
    public function getItemsCount()
    {
        return count($this->basketItems);
    }

    /**
     * Get price gross of an article in basket.
     *
     * @param int $articleUid Article uid
     *
     * @return mixed Price gross or FALSE if item is not in basket
     */
    public function getPriceGross($articleUid)
    {
        $result = false;

        if (is_object($this->basketItems[$articleUid])) {
            /**
             * Basket item.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
             */
            $basketItem = $this->basketItems[$articleUid];
            $result = $basketItem->getItemSumGross();
        }

        return $result;
    }

    /**
     * Get price net of an article in basket.
     *
     * @param int $articleUid Article uid
     *
     * @return mixed Price net or FALSE if item is not in basket
     */
    public function getPriceNet($articleUid)
    {
        if (is_object($this->basketItems[$articleUid])) {
            /**
             * Basket item.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
             */
            $basketItem = $this->basketItems[$articleUid];

            return $basketItem->getItemSumNet();
        }

        return false;
    }

    /**
     * Return quantity of an article in basket.
     *
     * @param int $articleUid Uid of article
     *
     * @return int Current quantity
     */
    public function getQuantity($articleUid)
    {
        if (is_object($this->basketItems[$articleUid])) {
            /**
             * Basket item.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
             */
            $basketItem = $this->basketItems[$articleUid];

            return $basketItem->getQuantity();
        }

        return 0;
    }

    /**
     * Set to read only, for checkout.
     *
     * @return void
     */
    public function setReadOnly()
    {
        $this->readOnly = true;
    }

    /**
     * Get read only state.
     *
     * @return bool TRUE if read only
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Getter for pricefromnet.
     *
     * @return int
     */
    public function getPricefromnet()
    {
        return $this->pricefromnet;
    }

    /**
     * Setter for pricefromnet.
     *
     * @param int $pricefromnet Price from net
     *
     * @return void
     */
    public function setPricefromnet($pricefromnet)
    {
        $this->pricefromnet = $pricefromnet;
    }

    /**
     * Get basket gross sum.
     *
     * @return int Basket gross sum
     */
    public function getSumGross()
    {
        $lokalSum = 0;

        /**
         * Basket item
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        if ($this->pricefromnet == 1) {
            $netSumArray = array();

            foreach ($this->basketItems as $oneItem) {
                $netSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumNet();
            }

            foreach ($netSumArray as $taxrate => $rateNetSum) {
                $lokalSum += (int) round($rateNetSum * (1 + (((float) $taxrate) / 100)));
            }
        } else {
            foreach ($this->basketItems as $oneItem) {
                $lokalSum += $oneItem->getItemSumGross();
            }
        }
        $this->basketSumGross = $lokalSum;

        return $this->basketSumGross;
    }

    /**
     * Get basket net sum.
     *
     * @return int Basket net sum
     */
    public function getSumNet()
    {
        $lokalSum = 0;

        /**
         * Basket item
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        if ($this->pricefromnet == 0) {
            $grossSumArray = array();
            foreach ($this->basketItems as $oneItem) {
                $grossSumArray[(string) $oneItem->getTax()] += $oneItem->getItemSumGross();
            }
            foreach ($grossSumArray as $taxrate => $rateGrossSum) {
                $lokalSum += (int) round($rateGrossSum / (1 + (((float) $taxrate) / 100)));
            }
        } else {
            foreach ($this->basketItems as $oneItem) {
                $lokalSum += $oneItem->getItemSumNet();
            }
        }
        $this->basketSumNet = $lokalSum;

        return $lokalSum;
    }

    /**
     * Calculates the TAX-Sum for the complete Basket.
     *
     * @return int Sum
     */
    public function getTaxSum()
    {
        $taxSum = 0;

        $taxRatesSums = $this->getTaxRateSums();
        foreach ($taxRatesSums as $taxRateSum) {
            $taxSum += $taxRateSum;
        }

        return $taxSum;
    }

    /**
     * Calculates the TAX-Sum for the complete and different Tax-Rates
     * depending on article.
     *
     * @return array Taxratesums
     */
    public function getTaxRateSums()
    {
        $taxes = array();

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $oneItem
         */
        foreach ($this->basketItems as $oneItem) {
            $taxRate = $oneItem->getTax();
            $taxRate = (string) $taxRate;

            if ($this->pricefromnet == 1) {
                $taxSum = (
                    $oneItem->getItemSumNet() *
                    ((float) $taxRate / 100)
                );
            } else {
                $taxSum = (
                    $oneItem->getItemSumGross() *
                    (
                        ((float) $taxRate / 100) /
                        (1 + ((float) $taxRate / 100))
                    )
                );
            }

            if (!isset($taxes[$taxRate]) and $taxSum <= 0) {
                continue;
            }

            if (!isset($taxes[$taxRate])) {
                $taxes[$taxRate] = 0;
            }

            $taxes[$taxRate] += $taxSum;
        }
        foreach ($taxes as $taxRate => $taxSum) {
            $taxes[$taxRate] = (int) round($taxSum);
        }

        return $taxes;
    }

    /**
     * Load basket data from session / database.
     *
     * @return void
     */
    public function loadData()
    {
        // Check if payment article is available and set default if not
        if (empty($this->getArticlesByArticleTypeUidAsUidlist(PAYMENTARTICLETYPE))) {
            $this->addArticle((int) $this->conf['defaultPaymentArticleId']);
        }
    }

    /**
     * Add an article to the basket.
     *
     * @param int $articleUid Article uid
     * @param int $quantity Quantity of this basket item
     * @param string $priceid Price id
     *
     * @return bool TRUE on successful change
     */
    public function addArticle($articleUid, $quantity = 1, $priceid = '')
    {
        $result = false;

        if ($articleUid && $this->isChangeable()) {
            if (is_object($this->basketItems[$articleUid]) || ($quantity == 0)) {
                $this->changeQuantity($articleUid, $quantity);
            } else {
                /**
                 * Article.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Article $article
                 */
                $article = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Article::class,
                    $articleUid,
                    $this->getFrontendController()->sys_language_uid
                );
                $article->loadData('basket');

                $priceids = $article->getPriceUids();
                if (is_array($priceids)) {
                    // Check if the given price id is related to the article
                    if (!in_array($priceid, $priceids)) {
                        $priceid = '';
                    }
                }

                if ($priceid == '') {
                    // no priceid is given,. get the price from article_object
                    $priceid = $article->getActualPriceforScaleUid($quantity);
                    if (!$priceid) {
                        $priceid = $article->getPriceUid();
                    }
                }

                /**
                 * Basket item.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
                 */
                $basketItem = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\BasketItem::class,
                    $articleUid,
                    $quantity,
                    $priceid,
                    $this->getFrontendController()->sys_language_uid
                );

                if ($basketItem->article) {
                    $basketItem->setTaxCalculationMethod($this->pricefromnet);
                    $this->recalculateSums();
                }

                $this->basketItems[$articleUid] = $basketItem;
            }

            $result = true;
        }

        return $result;
    }

    /**
     * Change the price_value of an article.
     *
     * @param int $articleUid Arcicle uid
     * @param int $newPriceGross New price gross
     * @param int $newPriceNet New price net
     *
     * @return void
     */
    public function changePrices($articleUid, $newPriceGross, $newPriceNet)
    {
        if ($this->isChangeable()) {
            if (is_object($this->basketItems[$articleUid])
                && $this->basketItems[$articleUid] instanceof \CommerceTeam\Commerce\Domain\Model\BasketItem
            ) {
                /**
                 * Basket item.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
                 */
                $basketItem = $this->basketItems[$articleUid];
                $basketItem->setPriceNet($newPriceNet);
                $basketItem->setPriceGross($newPriceGross);
                $basketItem->recalculateItemSums(true);
            }
        }
    }

    /**
     * Change title of an article in basket.
     *
     * @param int $articleUid Article uid
     * @param string $newtitle New article title
     *
     * @return void
     */
    public function changeTitle($articleUid, $newtitle)
    {
        if ($this->isChangeable()) {
            if (is_object($this->basketItems[$articleUid])
                && $this->basketItems[$articleUid] instanceof \CommerceTeam\Commerce\Domain\Model\BasketItem
            ) {
                /**
                 * Basket item.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
                 */
                $basketItem = $this->basketItems[$articleUid];
                $basketItem->setTitle($newtitle);
            }
        }
    }

    /**
     * Change quantity of an article in basket.
     *
     * @param int $articleUid Article uid
     * @param int $quantity   New quantity
     *
     * @return mixed TRUE on success, FALSE if quantity can not be changed,
     *     and int sometimes as well ...
     */
    public function changeQuantity($articleUid, $quantity = 1)
    {
        if ($this->isChangeable()) {
            if ($quantity == 0) {
                if (isset($this->basketItems[$articleUid])) {
                    $this->deleteArticle($articleUid);
                }
                $items = $this->getArticlesByArticleTypeUidAsUidlist(NORMALARTICLETYPE);
                if (empty($items)) {
                    $this->deleteAllArticles();
                }

                return true;
            }
            $this->recalculateSums();

            /**
             * Basket item.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
             */
            $basketItem = $this->basketItems[$articleUid];

            return $basketItem->changeQuantity($quantity);
        }

        return false;
    }

    /**
     * Remove an article from basket.
     *
     * @param int $articleUid Article uid
     *
     * @return bool TRUE on success
     */
    public function deleteArticle($articleUid)
    {
        $result = false;

        if ($this->isChangeable()) {
            if (isset($this->basketItems[$articleUid])) {
                unset($this->basketItems[$articleUid]);
                $this->recalculateSums();
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Clear basket.
     *
     * @return bool TRUE on success
     */
    public function deleteAllArticles()
    {
        $result = false;

        if ($this->isChangeable()) {
            $this->basketItems = array();
            $this->recalculateSums();
            $result = true;
        }

        return $result;
    }

    /**
     * Recalculate price sums.
     *
     * @return void
     */
    public function recalculateSums()
    {
        $this->getSumNet();
        $this->getSumGross();
    }

    /**
     * Whether or not there are article is basket.
     *
     * @return bool TRUE if count of articles is greater than 0
     */
    public function hasArticles()
    {
        return !empty($this->basketItems);
    }

    /**
     * This Method Sets the Tax Calculation method (pricefromnet).
     *
     * @param bool $priceFromNet Switch if calculating from net or not
     *
     * @return void
     */
    public function setTaxCalculationMethod($priceFromNet)
    {
        $this->pricefromnet = $priceFromNet;

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
         */
        foreach ($this->basketItems as $basketItem) {
            $basketItem->setTaxCalculationMethod($this->pricefromnet);
        }
    }

    /**
     * Whether or not basket is locket.
     *
     * @return bool TRUE if locked
     */
    public function isChangeable()
    {
        return !$this->readOnly;
    }

    /**
     * Set read only state to false.
     *
     * @return void
     */
    public function releaseReadOnly()
    {
        $this->readOnly = false;
    }

    /**
     * Returns first basket item by the given article type.
     *
     * @param int $articleType Article type
     *
     * @return \CommerceTeam\Commerce\Domain\Model\BasketItem
     */
    public function getCurrentBasketItemByArticleType($articleType)
    {
        $result = null;

        /**
         * Basket item.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\BasketItem $basketItem
         */
        foreach ($this->basketItems as $basketItem) {
            if ((int) $basketItem->getArticleTypeUid() === (int) $articleType) {
                $result = $basketItem;
                break;
            }
        }

        return $result;
    }

    /**
     * Get current payment article.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\BasketItem
     */
    public function getCurrentPaymentBasketItem()
    {
        return $this->getCurrentBasketItemByArticleType(PAYMENTARTICLETYPE);
    }

    /**
     * Get current delivery article.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\BasketItem
     */
    public function getCurrentDeliveryBasketItem()
    {
        return $this->getCurrentBasketItemByArticleType(DELIVERYARTICLETYPE);
    }

    /**
     * Remove current payment article from basket.
     *
     * @return void
     */
    public function removeCurrentPaymentArticle()
    {
        $paymentBasketItem = $this->getCurrentPaymentBasketItem();
        if (is_object($paymentBasketItem)) {
            $this->deleteArticle($paymentBasketItem->getArticleUid());
        }
    }

    /**
     * Remove current delivery article from basket.
     *
     * @return void
     */
    public function removeCurrentDeliveryArticle()
    {
        $deliveryBasketItem = $this->getCurrentDeliveryBasketItem();
        if (is_object($deliveryBasketItem)) {
            $this->deleteArticle($deliveryBasketItem->getArticleUid());
        }
    }


    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}

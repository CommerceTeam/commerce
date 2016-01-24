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
 * Basic class for basket_items.
 * Libary for handling basket-items in the Frontend.
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * Class \CommerceTeam\Commerce\Domain\Model\BasketItem
 *
 * @author 2005-2013 Ingo Schmitt <is@marketing-factory.de>
 */
class BasketItem
{
    /**
     * Article.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\Article
     */
    public $article;

    /**
     * Product.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\Product
     */
    public $product;

    /**
     * Price.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\ArticlePrice
     */
    protected $price;

    /**
     * Quantity for this article.
     *
     * @var int
     */
    protected $quantity = 0;

    /**
     * Priceid for this item.
     *
     * @var int
     */
    protected $priceid = 0;

    /**
     * Item summe from net_price.
     *
     * @var int
     */
    protected $item_net_sum = 0;

    /**
     * Item summe from gross_price.
     *
     * @var int
     */
    protected $item_gross_sum = 0;

    /**
     * Calculated price from net price.
     *
     * @var int
     */
    protected $pricefromnet = 0;

    /**
     * Net Price for this item.
     *
     * @var int
     */
    protected $priceNet;

    /**
     * Gross Price for this item.
     *
     * @var int
     */
    protected $priceGross;

    /**
     * Lang uid.
     *
     * @var int
     */
    protected $lang_uid = 0;

    /**
     * Constructor, basically calls init.
     *
     * @param int $uid Article uid
     * @param int $quantity Amount for this article
     * @param int $priceid Id of the price to use
     * @param int $languageUid Language id
     *
     * @return self
     */
    public function __construct($uid, $quantity, $priceid, $languageUid = 0)
    {
        if ((int) $uid && $quantity && $priceid) {
            $this->init($uid, $quantity, $priceid, $languageUid);
        }
    }

    /**
     * Initialise the object,
     * checks if given uid is valid and loads the the article an product data.
     *
     * @param int $uid Article uid
     * @param int $quantity Amount for this article
     * @param int $priceid Id of the price to use
     * @param int $langUid Language id
     *
     * @return bool
     */
    public function init($uid, $quantity, $priceid, $langUid = 0)
    {
        $uid = (int) $uid;
        $langUid = (int) $langUid;
        $priceid = (int) $priceid;

        if (is_numeric($quantity)) {
            if (is_float($quantity)) {
                $this->quantity = floatval($quantity);
            } else {
                $this->quantity = (int) $quantity;
            }
        } else {
            return false;
        }

        $this->quantity = $quantity;
        $this->lang_uid = $langUid;

        if ($quantity < 1) {
            return false;
        }

        /**
         * Article.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\Article $article
         */
        $article = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Model\Article::class,
            $uid,
            $this->lang_uid
        );

        if (is_object($article)) {
            $article->loadData('basket');
            $this->article = $article;

            $product = $article->getParentProduct();
            $product->loadData('basket');
            $this->product = $product;

            $this->priceid = $priceid;

            /**
             * Price.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\ArticlePrice $price
             */
            $price = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\ArticlePrice::class,
                $priceid,
                $this->lang_uid
            );
            $price->loadData('basket');
            $this->price = $price;

            $this->priceNet = $price->getPriceNet();
            $this->priceGross = $price->getPriceGross();

            $this->recalculateItemSums();

            return true;
        }

        /*
         * Article is not available, so clear object
         */
        $this->quantity = 0;
        $this->article = null;
        $this->product = null;

        return false;
    }

    /**
     * Get an array of get_article_assoc_array and get_product_assoc_array.
     *
     * @param string $prefix Prefix for the keys or returnung array optional
     *
     * @return array
     */
    public function getArrayOfAssocArray($prefix = '')
    {
        return array(
            'article' => $this->getArticleAssocArray($prefix),
            'product' => $this->getProductAssocArray($prefix),
        );
    }

    /**
     * Get article object.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Article Article object
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * ArticleAssocArray.
     *
     * @param string $prefix Prefix
     *
     * @return array
     */
    public function getArticleAssocArray($prefix)
    {
        return $this->article->returnAssocArray($prefix);
    }

    /**
     * Gets the article type uid.
     *
     * @return int type of the article
     */
    public function getArticleTypeUid()
    {
        return $this->article->getArticleTypeUid();
    }

    /**
     * Gets the uid from the article.
     *
     * @return int uid
     */
    public function getArticleUid()
    {
        return is_object($this->article) ? $this->article->getUid() : 0;
    }

    /**
     * Ordernumber of item.
     *
     * @return string ean of Articles
     */
    public function getEanCode()
    {
        return $this->article->getEanCode();
    }

    /**
     * Set a given field, only to use with custom field without own method
     * Warning: commerce provides getMethods for all default fields. For
     * Compatibility reasons always use the built in Methods. Only use this
     * method with you own added fields.
     *
     * @param string $field Fieldname
     * @param mixed $value Value
     *
     * @return void
     */
    public function setField($field, $value)
    {
        $this->$field = $value;
    }

    /**
     * Get a given field value, only to use with custom field without own
     * method. Warning: commerce provides getMethods for all default fields.
     * For compatibility reasons always use the built in Methods. Only use
     * this method with you own added fields.
     *
     * @param string $field Fieldname
     *
     * @return mixed value of the field
     */
    public function getField($field)
    {
        return $this->$field;
    }

    /**
     * Returns the item_sum_net.
     *
     * @param bool $recalculate If the sum should be recalculated, default false
     *
     * @return int item sum net
     */
    public function getItemSumNet($recalculate = false)
    {
        return $recalculate === true ? $this->calculateNetSum() : $this->item_net_sum;
    }

    /**
     * Return calculated item sum gross.
     *
     * @param bool $recalculate True if sum should be recalculated
     *
     * @return int Sum gross price
     */
    public function getItemSumGross($recalculate = false)
    {
        return $recalculate === true ? $this->calculateGrossSum() : $this->item_gross_sum;
    }

    /**
     * Returns the absolut TAX.
     *
     * @param bool $recalculate The sum shoudl be recalculated, defaul false
     *
     * @return int item sum gross
     */
    public function getItemSumTax($recalculate = false)
    {
        return ($this->getItemSumGross($recalculate) - $this->getItemSumNet($recalculate));
    }

    /**
     * Gross price without the scale calculation.
     *
     * @return float
     */
    public function getNoScalePriceGross()
    {
        return $this->article->getPriceGross();
    }

    /**
     * Net price without the scale calculation.
     *
     * @return float
     */
    public function getNoScalePriceNet()
    {
        return $this->article->getPriceNet();
    }

    /**
     * Ordernumber of item.
     *
     * @return string Ordernumber of Articles
     */
    public function getOrderNumber()
    {
        return $this->article->getOrdernumber();
    }

    /**
     * Sets pre gross price.
     *
     * @param int $value New Price Value
     *
     * @return void
     */
    public function setPriceGross($value)
    {
        $this->priceGross = $value;
        $this->calculateGrossSum();
    }

    /**
     * Gets the price_gross from thhe article.
     *
     * @return int
     */
    public function getPriceGross()
    {
        return $this->priceGross;
    }

    /**
     * Sets the net price.
     *
     * @param int $value New Price Value
     *
     * @return void
     */
    public function setPriceNet($value)
    {
        $this->priceNet = $value;
        $this->calculateNetSum();
    }

    /**
     * Gets the price_net from the article.
     *
     * @return int
     */
    public function getPriceNet()
    {
        return $this->priceNet;
    }

    /**
     * Gets the uid from the article.
     *
     * @return int uid
     */
    public function getPriceUid()
    {
        return $this->priceid;
    }

    /**
     * Get product object of item.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Product Product object
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * ArticleAssocArray.
     *
     * @param string $prefix Prefix
     *
     * @return array
     */
    public function getProductAssocArray($prefix)
    {
        return $this->product->returnAssocArray($prefix);
    }

    /**
     * Gets the master parent category.
     *
     * @return array category
     */
    public function getProductMasterparentCategorie()
    {
        return $this->product->getMasterparentCategory();
    }

    /**
     * Gets the uid from the product.
     *
     * @return int uid
     */
    public function getProductUid()
    {
        return $this->product->getUid();
    }

    /**
     * Gets the quantity from thos item.
     *
     * @return int quantity
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Gets the subtitle of the basket item.
     *
     * @param string $type Type possible values arte article and product
     *
     * @return string Subtitle of article (default) or product
     */
    public function getSubtitle($type = 'article')
    {
        switch ($type) {
            case 'product':
                return $this->product->getSubtitle();

            case 'article':
                // fall through
            default:
                return $this->article->getSubtitle();
        }
    }

    /**
     * Gets the tax from the article.
     *
     * @return float percantage of tax
     */
    public function getTax()
    {
        $result = 0;

        if (is_object($this->article)) {
            $result = $this->article->getTax();
        }

        return $result;
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
    }

    /**
     * Sets the Title.
     *
     * @param string $title Title
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->article->setField('title', $title);
        $this->product->setField('title', $title);
    }

    /**
     * Gets the title.
     *
     * @param string $type Type possible values arte article and product
     *
     * @return string title of article (default) or product
     */
    public function getTitle($type = 'article')
    {
        switch ($type) {
            case 'product':
                return $this->product->getTitle();

            case 'article':
                // fall through
            default:
                return $this->article->getTitle();
        }
    }

    /**
     * Change the basket item quantity.
     *
     * @param int $quantity Quantity
     *
     * @return true
     */
    public function changeQuantity($quantity)
    {
        $this->quantity = $quantity;
        $this->priceid = $this->article->getActualPriceforScaleUid($quantity);

        $this->price = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Model\ArticlePrice::class,
            $this->priceid,
            $this->lang_uid
        );
        $this->price->loadData();
        $this->priceNet = $this->price->getPriceNet();
        $this->priceGross = $this->price->getPriceGross();
        $this->recalculateItemSums();

        return true;
    }

    /**
     * Calculates the net_sum.
     *
     * @param bool $useValues Use the stored values
     *      instead of calculating gross or net price
     *
     * @return int net_sum
     * @todo add hook for this function
     */
    public function calculateNetSum($useValues = false)
    {
        if (($this->pricefromnet == 0) && ($useValues == false)) {
            $this->calculateGrossSum();
            $taxrate = $this->getTax();
            $this->item_net_sum = (int) round($this->item_gross_sum / (1 + ($taxrate / 100)));
        } else {
            $this->item_net_sum = $this->getPriceNet() * $this->quantity;
        }

        return $this->item_net_sum;
    }

    /**
     * Calculates the gross_sum.
     *
     * @param bool $useValues Use the stored values
     *      instead of calculating gross or net price
     *
     * @return int gross_sum
     * @todo add hook for this function
     */
    public function calculateGrossSum($useValues = false)
    {
        if (($this->pricefromnet == 1) && ($useValues == false)) {
            $this->calculateNetSum();
            $taxrate = $this->getTax();
            $this->item_gross_sum = (int) round($this->item_net_sum * (1 + ($taxrate / 100)));
        } else {
            $this->item_gross_sum = $this->getPriceGross() * $this->quantity;
        }

        return $this->item_gross_sum;
    }

    /**
     * Recalculates the item sums.
     *
     * @param bool $useValues Use the stored values instead
     *      of calculating gross or net price
     *
     * @return void
     */
    public function recalculateItemSums($useValues = false)
    {
        $this->calculateNetSum($useValues);
        $this->calculateGrossSum($useValues);
    }
}

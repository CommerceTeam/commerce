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

use CommerceTeam\Commerce\Factory\HookFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main script class for the handling of articles. Normaly used
 * for frontend rendering. This class provides basic methodes for acessing
 * articles.
 * Inherited from \CommerceTeam\Commerce\Domain\Model\AbstractEntity.
 *
 * Class \CommerceTeam\Commerce\Domain\Model\Article
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Article extends AbstractEntity
{
    /**
     * Database class name.
     *
     * @var string
     */
    protected $databaseClass = 'CommerceTeam\\Commerce\\Domain\\Repository\\ArticleRepository';

    /**
     * Database connection.
     *
     * @var \CommerceTeam\Commerce\Domain\Repository\ArticleRepository
     */
    public $databaseConnection;

    /**
     * Field list.
     *
     * @var array
     */
    protected $fieldlist = array(
        'uid',
        'title',
        'subtitle',
        'description_extra',
        'teaser',
        'tax',
        'ordernumber',
        'eancode',
        'uid_product',
        'article_type_uid',
        'images',
        'classname',
        'relatedpage',
        'supplier_uid',
        'plain_text',
    );

    /**
     * Title of the article, e.g. articlename.
     *
     * @var string
     */
    protected $title;

    /**
     * Subtitle of the article.
     *
     * @var string
     */
    protected $subtitle;

    /**
     * Description.
     *
     * @var string
     */
    protected $description_extra;

    /**
     * Normal Tax for this article in Percent.
     *
     * @var int
     */
    protected $tax;

    /**
     * Images.
     *
     * @var string
     */
    protected $images = '';

    /**
     * Images for the article.
     *
     * @var array
     */
    protected $images_array = array();

    /**
     * Ordernumber.
     *
     * @var string
     */
    protected $ordernumber;

    /**
     * Eancode.
     *
     * @var string
     */
    protected $eancode;

    /**
     * Parent product uid.
     *
     * @var int
     */
    protected $uid_product;

    /**
     * Parent product.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\Product
     */
    protected $parentProduct;

    /**
     * Related page.
     *
     * @var int
     */
    protected $relatedpage;

    /**
     * Uid for the article Type
     * (should be refered to table tx_commerce_article_types).
     *
     * @var int
     */
    protected $article_type_uid;

    /**
     * Supplier uid.
     *
     * @var int
     */
    protected $supplier_uid;

    /**
     * Cost for displaying the article delivery cost on the page
     * needed for german Law Preisauszeichnung.
     *
     * @var int
     */
    protected $deliveryCostNet;

    /**
     * Cost for displaying the article delivery cost on the page
     * needed for german Law Preisauszeichnung.
     *
     * @var int
     */
    protected $deliveryCostGross;

    /**
     * Uid from actual article price.
     *
     * @var int
     */
    protected $price_uid;

    /**
     * List of all price uids concerning this article.
     *
     * @var array
     */
    protected $prices_uids = array();

    /**
     * Price object.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\ArticlePrice
     */
    protected $price;

    /**
     * If the price is loaded from the database.
     *
     * @var bool
     */
    protected $prices_loaded = false;

    /**
     * Special price.
     *
     * @var array
     */
    protected $specialPrice;

    /**
     * Stock.
     *
     * @var bool
     */
    protected $stock = true;

    /**
     * Classname for selecting by type.
     *
     * @var string
     */
    protected $classname;

    /**
     * Constructor Method, calles init method.
     *
     * @param int $uid         Uid
     * @param int $languageUid Language uid
     *
     * @return self
     */
    public function __construct($uid, $languageUid = 0)
    {
        if ((int) $uid) {
            $this->init($uid, $languageUid);
        }
    }

    /**
     * Init Method, called by constructor.
     *
     * @param int $uid         Uid of article
     * @param int $languageUid Language uid
     *
     * @return bool
     */
    public function init($uid, $languageUid = 0)
    {
        $this->uid = (int) $uid;
        $languageUid = (int) $languageUid;

        $return = false;
        if ($this->uid > 0) {
            $this->lang_uid = $languageUid;
            $this->databaseConnection = GeneralUtility::makeInstance($this->databaseClass);

            $hooks = HookFactory::getHooks('Domain/Model/Article', 'init');
            foreach ($hooks as $hook) {
                if (method_exists($hook, 'postinit')) {
                    $hook->postinit($this);
                }
            }

            $return = true;
        }

        return $return;
    }

    /**
     * Get the priceUid for a sepcific amount for this article.
     *
     * @param int $count Count for this article
     *
     * @return int Price Uid
     */
    public function getActualPriceforScaleUid($count)
    {
        // Hook for doing your own calculation
        $hookObject = HookFactory::getHook('Domain/Model/Article', 'getActualPriceforScaleUid');
        if (is_object($hookObject) && method_exists($hookObject, 'getActualPriceforScaleUid')) {
            return $hookObject->getActualPriceforScaleUid($count, $this);
        }

        $arrayOfPrices = $this->getPriceScales();
        if (!$arrayOfPrices) {
            return $this->getPriceUid();
        }

        if (count($arrayOfPrices) == 1) {
            /*
             * When only one scale is given
             */
            return $this->getPriceUid();
        } else {
            foreach ($arrayOfPrices as $startCount => $tempArray) {
                if ($startCount <= $count) {
                    foreach ($tempArray as $endCount => $priceUid) {
                        if ($endCount >= $count) {
                            return $priceUid;
                        }
                    }
                }
            }
        }

        return $this->getPriceUid();
    }

    /**
     * Returns the article attributes
     * array ( attribut_uid =>
     *   array ('title =>' $title,
     *     'value' => $value,
     *     'unit' => $unit),
     *     ...
     * ).
     *
     * @return array of arrays
     */
    public function getArticleAttributes()
    {
        $localTable = 'tx_commerce_articles';
        $mmTable = 'tx_commerce_articles_article_attributes_mm';
        $foreignTable = 'tx_commerce_attributes';
        $select = 'DISTINCT '.$foreignTable.'.uid, '.$foreignTable.'.title';
        $ignore = array('fe_group' => 1);

        /**
         * Page repository.
         *
         * @var \TYPO3\CMS\Frontend\Page\PageRepository
         */
        $pageSelect = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $whereClause = $pageSelect->enableFields('tx_commerce_attributes', '', $ignore);

        $database = $this->getDatabaseConnection();

        $setArticleAttributesResult = $database->exec_SELECT_mm_query(
            $select,
            $localTable,
            $mmTable,
            $foreignTable,
            $whereClause,
            '',
            '',
            ''
        );

        $attributesUidList = array();
        while (($data = $database->sql_fetch_assoc($setArticleAttributesResult))) {
            if (!empty($data['uid'])) {
                $attributesUidList[$data['uid']] = $data['title'];
            }
        }
        $database->sql_free_result($setArticleAttributesResult);

        $values = array();
        foreach ($attributesUidList as $uid => $title) {
            $value = $this->getAttributeValue($uid);
            if (!empty($value)) {
                $values[$uid] = array(
                    'title' => $title,
                    'value' => $this->getAttributeValue($uid),
                );
            }
        }

        return $values;
    }

    /**
     * Getter.
     *
     * @return int article_type_uid
     */
    public function getArticleTypeUid()
    {
        return $this->article_type_uid;
    }

    /**
     * Gets the Value from one distinct attribute of this article.
     *
     * @param int  $attributeUid   Attribute uid
     * @param bool $valueListAsUid Value list as uid
     *
     * @return string Value
     */
    public function getAttributeValue($attributeUid, $valueListAsUid = false)
    {
        return $this->databaseConnection->getAttributeValue($this->uid, $attributeUid, $valueListAsUid);
    }

    /**
     * Classname.
     *
     * @return string
     */
    public function getClassname()
    {
        return $this->classname;
    }

    /**
     * Delivery Cost for this article.
     *
     * @return int
     */
    public function getDeliveryCostNet()
    {
        return $this->deliveryCostNet;
    }

    /**
     * Delivery Cost for this article.
     *
     * @return int
     */
    public function getDeliveryCostGross()
    {
        return $this->deliveryCostGross;
    }

    /**
     * Description extra.
     *
     * @return string
     */
    public function getDescriptionExtra()
    {
        return $this->description_extra;
    }

    /**
     * Eancode.
     *
     * @return string
     */
    public function getEancode()
    {
        return $this->eancode;
    }

    /**
     * Images.
     *
     * @return array
     */
    public function getImages()
    {
        return $this->images_array;
    }

    /**
     * Order number.
     *
     * @return string ordernumber
     */
    public function getOrdernumber()
    {
        return $this->ordernumber;
    }

    /**
     * Price gross.
     *
     * @return float
     */
    public function getPriceGross()
    {
        $result = 'no valid price';

        if ($this->price instanceof \CommerceTeam\Commerce\Domain\Model\ArticlePrice) {
            $result = $this->price->getPriceGross();
        }

        return $result;
    }

    /**
     * Price net.
     *
     * @return float
     */
    public function getPriceNet()
    {
        $result = 'no valid price';

        if ($this->price instanceof \CommerceTeam\Commerce\Domain\Model\ArticlePrice) {
            $result = $this->price->getPriceNet();
        }

        return $result;
    }

    /**
     * Get price object.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\ArticlePrice Price object
     */
    public function getPriceObj()
    {
        return $this->price;
    }

    /**
     * Get Article price scales.
     *
     * @param int $startCount Count where to start with the
     *                        listing of the sacles, default 1
     *
     * @return array or prices grouped by the different scales
     */
    public function getPriceScaleObjects($startCount = 1)
    {
        $return = array();
        $arrayOfPricesUids = $this->getPriceScales($startCount);

        if (is_array($arrayOfPricesUids)) {
            foreach ($arrayOfPricesUids as $startCount => $tmpArray) {
                foreach ($tmpArray as $endCount => $pricdUid) {
                    /**
                     * Article price.
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\ArticlePrice
                     */
                    $price = GeneralUtility::makeInstance(
                        'CommerceTeam\\Commerce\\Domain\\Model\\ArticlePrice',
                        $pricdUid
                    );
                    $price->loadData();

                    $return[$startCount][$endCount] = $price;
                }
            }
        }

        return $return;
    }

    /**
     * Get Article price scales.
     *
     * @param int $startCount Count where to start with the
     *                        listing of the sacles, default 1
     *
     * @return array or priceUid grouped by the different scales
     */
    public function getPriceScales($startCount = 1)
    {
        return $this->databaseConnection->getPriceScales($this->uid, $startCount);
    }

    /**
     * Returns the price Uid.
     *
     * @return int
     */
    public function getPriceUid()
    {
        return $this->price_uid;
    }

    /**
     * Get Article all possible prices as uid array.
     *
     * @return array or priceUid
     */
    public function getPriceUids()
    {
        return $this->databaseConnection->getPrices($this->uid);
    }

    /**
     * Parent product as object.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Product Product object
     */
    public function getParentProduct()
    {
        if ($this->parentProduct == null) {
            $this->parentProduct = GeneralUtility::makeInstance(
                'CommerceTeam\\Commerce\\Domain\\Model\\Product',
                $this->getParentProductUid()
            );
        }

        return $this->parentProduct;
    }

    /**
     * Parent product uid.
     *
     * @return int uid of tx_commerce_products
     */
    public function getParentProductUid()
    {
        if ($this->uid_product == null) {
            $this->uid_product = $this->databaseConnection->getParentProductUid($this->uid);
        }

        return $this->uid_product;
    }

    /**
     * Related page for the product.
     *
     * @return int
     */
    public function getRelatedpage()
    {
        return $this->relatedpage;
    }

    /**
     * Default price object, which doesn't have any start or stoptime.
     *
     * @return int price_uid
     */
    public function getSpecialPrice()
    {
        $this->loadPrices();

        $this->specialPrice = array(
            'object' => $this->price,
            'uid' => $this->price_uid,
        );

        $hookObject = HookFactory::getHook('Domain/Model/Article', 'getSpecialPrice');
        if (is_object($hookObject) && method_exists($hookObject, 'specialPrice')) {
            $hookObject->specialPrice($this->specialPrice, $this->prices_uids);
        }

        return $this->specialPrice;
    }

    /**
     * Subtitle.
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Supplier name of an article, if set.
     *
     * @return string Name of the supplier
     */
    public function getSupplierName()
    {
        $result = '';

        if ($this->getSupplierUid()) {
            $result = $this->databaseConnection->getSupplierName($this->getSupplierUid());
        }

        return $result;
    }

    /**
     * Supplier uid of the article.
     *
     * @return int UID of supplier
     */
    public function getSupplierUid()
    {
        return $this->supplier_uid;
    }

    /**
     * Getter.
     *
     * @return float tax
     */
    public function getTax()
    {
        return doubleval($this->tax);
    }

    /**
     * Title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Loads the data and divides comma separated images in array.
     *
     * @param bool $translationMode Translation mode
     *
     * @return void
     */
    public function loadData($translationMode = false)
    {
        parent::loadData($translationMode);
        $this->loadPrices($translationMode);
        $this->images_array = GeneralUtility::trimExplode(',', $this->images);
        $this->calculateDeliveryCosts();
    }

    /**
     * Gets the price of this article and stores in private variable.
     *
     * @param bool $translationMode Translation mode
     *
     * @return int
     */
    public function loadPrices($translationMode = false)
    {
        if ($this->prices_loaded == false) {
            $this->prices_uids = $this->databaseConnection->getPrices($this->uid);

            if ($this->prices_uids) {
                $priceData = array_shift($this->prices_uids);
                $this->price_uid = $priceData[0];

                $this->price = GeneralUtility::makeInstance(
                    'CommerceTeam\\Commerce\\Domain\\Model\\ArticlePrice',
                    $this->price_uid
                );
                if ($this->price) {
                    $this->price->loadData($translationMode);
                } else {
                    return 0;
                }

                $this->prices_loaded = true;

                $return = $this->price_uid;
            } else {
                $return = 0;
            }
        } else {
            $return = $this->price_uid;
        }

        return $return;
    }

    /**
     * Calculates the Net deliverycost for this article
     * Called by $this->loadData().
     */
    public function calculateDeliveryCosts()
    {
        /*
         * Just one Hook as there is no sense for more than one delievery cost
         */
        $hookObject = HookFactory::getHook('Domain/Model/Article', 'calculateDeliveryCosts');
        if (is_object($hookObject) && method_exists($hookObject, 'calculateDeliveryCostNet')) {
            $hookObject->calculateDeliveryCostNet($this->deliveryCostNet, $this);
        }
        if (is_object($hookObject) && method_exists($hookObject, 'calculateDeliveryCostGross')) {
            $hookObject->calculateDeliveryCostGross($this->deliveryCostGross, $this);
        }
    }

    /**
     * Returns the number of articles in Stock with calling one or more Services.
     * if no Service is found or the hasStock Method is not implemented in Service,
     * it always returns one.
     *
     * @param string $subType      Sub type like file extensions or similar.
     *                             Defined by the service.
     * @param array  $serviceChain List of service keys which should be exluded in
     *                             the search for a service. Array or comma list.
     *
     * @return int amount of articles in stock
     */
    public function getStock($subType = '', array $serviceChain = array())
    {
        $counter = 0;
        $articlesInStock = 0;

        while (is_object($serviceObj = GeneralUtility::makeInstanceService('stockHandling', $subType, $serviceChain))) {
            $serviceChain .= ','.$serviceObj->getServiceKey();
            if (method_exists($serviceObj, 'getStock')) {
                $articlesInStock += (int) $serviceObj->getStock($this);
                ++$counter;
            }
        }
        if ($counter == 0) {
            return 1;
        }

        return $articlesInStock;
    }

    /**
     * Returns the availability of wanted amount of articles.
     *
     * @param int    $wantedArticles Amount of Articles which should be added to basket
     * @param string $subType        Sub type like file extensions or similar. Defined by
     *                               the service.
     * @param array  $serviceChain   List of service keys which should be exluded in the
     *                               search for a service. Array or comma list.
     *
     * @return bool availability of wanted amount of articles
     */
    public function hasStock($wantedArticles = 0, $subType = '', array $serviceChain = array())
    {
        $counter = 0;
        $available = false;
        $articlesInStock = $this->getStock($subType, $serviceChain);

        while (is_object($serviceObj = GeneralUtility::makeInstanceService('stockHandling', $subType, $serviceChain))) {
            $serviceChain .= ','.$serviceObj->getServiceKey();
            if (method_exists($serviceObj, 'hasStock')) {
                ++$counter;
                if (($available = (int) $serviceObj->hasStock($this, $wantedArticles, $articlesInStock))) {
                    break;
                }
            }
        }

        if ($counter == 0) {
            return true;
        }

        return $available;
    }

    /**
     * Substract the wanted Articles from stock. If you have more than one stock
     * which is handled to more than one Service please implement the Service due
     * to Reference on $wantedArticles so you can reduce this amount steplike.
     *
     * @param int    $wantedArticles Amount of Articles which should reduced from stock
     * @param string $subType        Sub type like file extensions or similar. Defined
     *                               by the service.
     * @param array  $serviceChain   List of service keys which should be exluded in
     *                               the search for a service. Array or comma list.
     *
     * @return bool Describes the result of going through the chains
     */
    public function reduceStock($wantedArticles = 0, $subType = '', array $serviceChain = array())
    {
        $counter = 0;

        while (is_object($serviceObj = GeneralUtility::makeInstanceService('stockHandling', $subType, $serviceChain))) {
            $serviceChain .= ','.$serviceObj->getServiceKey();
            if (method_exists($serviceObj, 'reduceStock')) {
                $serviceObj->reduceStock($wantedArticles, $this);
            }
        }
        if ($counter == 0) {
            return false;
        }

        return true;
    }

    /**
     * Returns the data of this object als array.
     *
     * @param string $prefix Prefix for the keys or returnung array optional
     *
     * @return array Assoc Arry of data
     */
    public function returnAssocArray($prefix = '')
    {
        $data = parent::returnAssocArray($prefix);
        $data[$prefix.'stock'] = $this->getStock();

        return $data;
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

<?php
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
 * Main script class for the handling of articles. Normaly used
 * for frontend rendering. This class provides basic methodes for acessing
 * articles.
 * Inherited from Tx_Commerce_Domain_Model_AbstractEntity
 *
 * Class Tx_Commerce_Domain_Model_Article
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Model_Article extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * Database class name
	 *
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_ArticleRepository';

	/**
	 * Database connection
	 *
	 * @var Tx_Commerce_Domain_Repository_ArticleRepository
	 */
	public $databaseConnection;

	/**
	 * Field list
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
		'plain_text'
	);

	/**
	 * Title of the article, e.g. articlename
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Subtitle of the article
	 *
	 * @var string
	 */
	protected $subtitle;

	/**
	 * Description
	 *
	 * @var string
	 */
	protected $description_extra;

	/**
	 * Normal Tax for this article in Percent
	 *
	 * @var int
	 */
	protected $tax;

	/**
	 * Images
	 *
	 * @var string
	 */
	protected $images = '';

	/**
	 * Images for the article
	 *
	 * @var array
	 */
	protected $images_array = array();

	/**
	 * Ordernumber
	 *
	 * @var string
	 */
	protected $ordernumber;

	/**
	 * Eancode
	 *
	 * @var string
	 */
	protected $eancode;

	/**
	 * Parent product uid
	 *
	 * @var int
	 */
	protected $uid_product;

	/**
	 * Parent product
	 *
	 * @var Tx_Commerce_Domain_Model_Product
	 */
	protected $parentProduct;

	/**
	 * Related page
	 *
	 * @var int
	 */
	protected $relatedpage;

	/**
	 * Uid for the article Type
	 * (should be refered to table tx_commerce_article_types)
	 *
	 * @var int
	 */
	protected $article_type_uid;

	/**
	 * Supplier uid
	 *
	 * @var int
	 */
	protected $supplier_uid;

	/**
	 * Cost for displaying the article delivery cost on the page
	 * needed for german Law Preisauszeichnung
	 *
	 * @var int
	 */
	protected $deliveryCostNet;

	/**
	 * Cost for displaying the article delivery cost on the page
	 * needed for german Law Preisauszeichnung
	 *
	 * @var int
	 */
	protected $deliveryCostGross;

	/**
	 * Uid from actual article price
	 *
	 * @var int
	 */
	protected $price_uid;

	/**
	 * List of all price uids concerning this article
	 *
	 * @var array
	 */
	protected $prices_uids = array();

	/**
	 * Price object
	 *
	 * @var Tx_Commerce_Domain_Model_ArticlePrice
	 */
	protected $price;

	/**
	 * If the price is loaded from the database
	 *
	 * @var bool
	 */
	protected $prices_loaded = FALSE;

	/**
	 * Special price
	 *
	 * @var array
	 */
	protected $specialPrice;

	/**
	 * Stock
	 *
	 * @var bool
	 */
	protected $stock = TRUE;

	/**
	 * Classname for selecting by type
	 *
	 * @var string
	 */
	protected $classname;

	/**
	 * Constructor Method, calles init method
	 *
	 * @param int $uid Uid
	 * @param int $languageUid Language uid
	 *
	 * @return self
	 */
	public function __construct($uid, $languageUid = 0) {
		if ((int) $uid) {
			$this->init($uid, $languageUid);
		}
	}

	/**
	 * Init Method, called by constructor
	 *
	 * @param int $uid Uid of article
	 * @param int $languageUid Language uid
	 *
	 * @return bool
	 */
	public function init($uid, $languageUid = 0) {
		$this->uid = (int) $uid;
		$languageUid = (int) $languageUid;

		$return = FALSE;
		if ($this->uid > 0) {
			$this->lang_uid = $languageUid;
			$this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['postinit'])) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'postinit\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'postinit\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['postinit'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['postinit'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}

			$return = TRUE;
		}

		return $return;
	}

	/**
	 * Get the priceUid for a sepcific amount for this article
	 *
	 * @param int $count Count for this article
	 *
	 * @return int Price Uid
	 */
	public function getActualPriceforScaleUid($count) {
		// Hook for doing your own calculation
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['getActualPriceforScaleUid']) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'getActualPriceforScaleUid\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'getActualPriceforScaleUid\']
			');
			$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['getActualPriceforScaleUid']
			);
			if (is_object($hookObject) && (method_exists($hookObject, 'getActualPriceforScaleUid'))) {
				return $hookObject->getActualPriceforScaleUid($count, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['getActualPriceforScaleUid']) {
			$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['getActualPriceforScaleUid']
			);
			if (is_object($hookObject) && (method_exists($hookObject, 'getActualPriceforScaleUid'))) {
				return $hookObject->getActualPriceforScaleUid($count, $this);
			}
		}

		$arrayOfPrices = $this->getPriceScales();
		if (!$arrayOfPrices) {
			return $this->getPriceUid();
		}

		if (count($arrayOfPrices) == 1) {
			/**
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
	 * )
	 *
	 * @return array of arrays
	 */
	public function getArticleAttributes() {
		$localTable = 'tx_commerce_articles';
		$mmTable = 'tx_commerce_articles_article_attributes_mm';
		$foreignTable = 'tx_commerce_attributes';
		$select = 'DISTINCT ' . $foreignTable . '.uid, ' . $foreignTable . '.title';
		$ignore = array('fe_group' => 1);

		/**
		 * Page repository
		 *
		 * @var \TYPO3\CMS\Frontend\Page\PageRepository $pageSelect
		 */
		$pageSelect = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$whereClause = $pageSelect->enableFields('tx_commerce_attributes', '', $ignore);

		$database = $this->getDatabaseConnection();

		$setArticleAttributesResult = $database->exec_SELECT_mm_query(
			$select, $localTable, $mmTable, $foreignTable, $whereClause, '', '', ''
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
					'value' => $this->getAttributeValue($uid)
				);
			}
		}

		return $values;
	}

	/**
	 * Getter
	 *
	 * @return int article_type_uid
	 */
	public function getArticleTypeUid() {
		return $this->article_type_uid;
	}

	/**
	 * Gets the Value from one distinct attribute of this article
	 *
	 * @param int $attributeUid Attribute uid
	 * @param bool $valueListAsUid Value list as uid
	 *
	 * @return string Value
	 */
	public function getAttributeValue($attributeUid, $valueListAsUid = FALSE) {
		return $this->databaseConnection->getAttributeValue($this->uid, $attributeUid, $valueListAsUid);
	}

	/**
	 * Classname
	 *
	 * @return string
	 */
	public function getClassname() {
		return $this->classname;
	}

	/**
	 * Delivery Cost for this article
	 *
	 * @return int
	 */
	public function getDeliveryCostNet() {
		return $this->deliveryCostNet;
	}

	/**
	 * Delivery Cost for this article
	 *
	 * @return int
	 */
	public function getDeliveryCostGross() {
		return $this->deliveryCostGross;
	}

	/**
	 * Description extra
	 *
	 * @return string
	 */
	public function getDescriptionExtra() {
		return $this->description_extra;
	}

	/**
	 * Eancode
	 *
	 * @return string
	 */
	public function getEancode() {
		return $this->eancode;
	}

	/**
	 * Images
	 *
	 * @return array
	 */
	public function getImages() {
		return $this->images_array;
	}

	/**
	 * Order number
	 *
	 * @return string ordernumber
	 */
	public function getOrdernumber() {
		return $this->ordernumber;
	}

	/**
	 * Price gross
	 *
	 * @return float
	 */
	public function getPriceGross() {
		$result = 'no valid price';

		if ($this->price instanceof Tx_Commerce_Domain_Model_ArticlePrice) {
			$result = $this->price->getPriceGross();
		}

		return $result;
	}

	/**
	 * Price net
	 *
	 * @return float
	 */
	public function getPriceNet() {
		$result = 'no valid price';

		if ($this->price instanceof Tx_Commerce_Domain_Model_ArticlePrice) {
			$result = $this->price->getPriceNet();
		}

		return $result;
	}

	/**
	 * Get price object
	 *
	 * @return Tx_Commerce_Domain_Model_ArticlePrice Price object
	 */
	public function getPriceObj() {
		return $this->price;
	}

	/**
	 * Get Article price scales
	 *
	 * @param int $startCount Count where to start with the
	 * 		listing of the sacles, default 1
	 *
	 * @return array or prices grouped by the different scales
	 */
	public function getPriceScaleObjects($startCount = 1) {
		$return = array();
		$arrayOfPricesUids = $this->getPriceScales($startCount);

		if (is_array($arrayOfPricesUids)) {
			foreach ($arrayOfPricesUids as $startCount => $tmpArray) {
				foreach ($tmpArray as $endCount => $pricdUid) {
					/**
					 * Article price
					 *
					 * @var Tx_Commerce_Domain_Model_ArticlePrice $price
					 */
					$price = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'Tx_Commerce_Domain_Model_ArticlePrice',
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
	 * Get Article price scales
	 *
	 * @param int $startCount Count where to start with the
	 * 		listing of the sacles, default 1
	 *
	 * @return array or priceUid grouped by the different scales
	 */
	public function getPriceScales($startCount = 1) {
		return $this->databaseConnection->getPriceScales($this->uid, $startCount);
	}

	/**
	 * Returns the price Uid
	 *
	 * @return int
	 */
	public function getPriceUid() {
		return $this->price_uid;
	}

	/**
	 * Get Article all possible prices as uid array
	 *
	 * @return array or priceUid
	 */
	public function getPriceUids() {
		return $this->databaseConnection->getPrices($this->uid);
	}

	/**
	 * Parent product as object
	 *
	 * @return Tx_Commerce_Domain_Model_Product Product object
	 */
	public function getParentProduct() {
		if ($this->parentProduct == NULL) {
			$this->parentProduct = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'Tx_Commerce_Domain_Model_Product',
				$this->getParentProductUid()
			);
		}
		return $this->parentProduct;
	}

	/**
	 * Parent product uid
	 *
	 * @return int uid of tx_commerce_products
	 */
	public function getParentProductUid() {
		if ($this->uid_product == NULL) {
			$this->uid_product = $this->databaseConnection->getParentProductUid($this->uid);
		}

		return $this->uid_product;
	}

	/**
	 * Related page for the product
	 *
	 * @return int
	 */
	public function getRelatedpage() {
		return $this->relatedpage;
	}

	/**
	 * Default price object, which doesn't have any start or stoptime
	 *
	 * @return int price_uid
	 */
	public function getSpecialPrice() {
		$this->loadPrices();

		$this->specialPrice = array(
			'object' => $this->price,
			'uid' => $this->price_uid
		);

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['specialPrice']) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'specialPrice\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'specialPrice\']
			');
			$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['specialPrice']
			);
			if (method_exists($hookObj, 'specialPrice')) {
				$hookObj->specialPrice($this->specialPrice, $this->prices_uids);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['specialPrice']) {
			$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['specialPrice']
			);
			if (method_exists($hookObj, 'specialPrice')) {
				$hookObj->specialPrice($this->specialPrice, $this->prices_uids);
			}
		}

		return $this->specialPrice;
	}

	/**
	 * Subtitle
	 *
	 * @return string
	 */
	public function getSubtitle() {
		return $this->subtitle;
	}

	/**
	 * Supplier name of an article, if set
	 *
	 * @return string Name of the supplier
	 */
	public function getSupplierName() {
		$result = '';

		if ($this->getSupplierUid()) {
			$result = $this->databaseConnection->getSupplierName($this->getSupplierUid());
		}

		return $result;
	}

	/**
	 * Supplier uid of the article
	 *
	 * @return int UID of supplier
	 */
	public function getSupplierUid() {
		return $this->supplier_uid;
	}

	/**
	 * Getter
	 *
	 * @return double tax
	 */
	public function getTax() {
		return doubleval($this->tax);
	}

	/**
	 * Title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * Loads the data and divides comma separated images in array
	 *
	 * @param bool $translationMode Translation mode
	 *
	 * @return void
	 */
	public function loadData($translationMode = FALSE) {
		parent::loadData($translationMode);
		$this->loadPrices($translationMode);
		$this->images_array = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->images);
		$this->calculateDeliveryCosts();
	}

	/**
	 * Gets the price of this article and stores in private variable
	 *
	 * @param bool $translationMode Translation mode
	 *
	 * @return int
	 */
	public function loadPrices($translationMode = FALSE) {
		if ($this->prices_loaded == FALSE) {
			$this->prices_uids = $this->databaseConnection->getPrices($this->uid);

			if ($this->prices_uids) {
				$priceData = array_shift($this->prices_uids);
				$this->price_uid = $priceData[0];

				$this->price = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_ArticlePrice',
					$this->price_uid
				);
				if ($this->price) {
					$this->price->loadData($translationMode);
				} else {
					return 0;
				}

				$this->prices_loaded = TRUE;

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
	 * Called by $this->loadData()
	 *
	 * @return void
	 */
	public function calculateDeliveryCosts() {
		/**
		 * Just one Hook as there is no sense for more than one delievery cost
		 */
		if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'calculateDeliveryCost\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'calculateDeliveryCost\']
			');
			$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost']
			);

			if (method_exists($hookObject, 'calculateDeliveryCostNet')) {
				$hookObject->calculateDeliveryCostNet($this->deliveryCostNet, $this);
			}

			if (method_exists($hookObject, 'calculateDeliveryCostGross')) {
				$hookObject->calculateDeliveryCostGross($this->deliveryCostGross, $this);
			}
		}
		if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'])) {
			$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost']
			);

			if (method_exists($hookObject, 'calculateDeliveryCostNet')) {
				$hookObject->calculateDeliveryCostNet($this->deliveryCostNet, $this);
			}

			if (method_exists($hookObject, 'calculateDeliveryCostGross')) {
				$hookObject->calculateDeliveryCostGross($this->deliveryCostGross, $this);
			}
		}
	}

	/**
	 * Returns the number of articles in Stock with calling one or more Services.
	 * if no Service is found or the hasStock Method is not implemented in Service,
	 * it always returns one.
	 *
	 * @param string $subType Sub type like file extensions or similar.
	 * 		Defined by the service.
	 * @param array $serviceChain List of service keys which should be exluded in
	 * 		the search for a service. Array or comma list.
	 *
	 * @return int amount of articles in stock
	 */
	public function getStock($subType = '', array $serviceChain = array()) {
		$counter = 0;
		$articlesInStock = 0;

		while (is_object($serviceObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService(
			'stockHandling', $subType, $serviceChain
		))) {
			$serviceChain .= ',' . $serviceObj->getServiceKey();
			if (method_exists($serviceObj, 'getStock')) {
				$articlesInStock += (int) $serviceObj->getStock($this);
				$counter++;
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
	 * @param int $wantedArticles Amount of Articles which should be added to basket
	 * @param string $subType Sub type like file extensions or similar. Defined by
	 * 		the service.
	 * @param array $serviceChain List of service keys which should be exluded in the
	 * 		search for a service. Array or comma list.
	 *
	 * @return bool availability of wanted amount of articles
	 */
	public function hasStock($wantedArticles = 0, $subType = '', array $serviceChain = array()) {
		$counter = 0;
		$available = FALSE;
		$articlesInStock = $this->getStock($subType, $serviceChain);

		while (is_object($serviceObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService(
			'stockHandling', $subType, $serviceChain
		))) {
			$serviceChain .= ',' . $serviceObj->getServiceKey();
			if (method_exists($serviceObj, 'hasStock')) {
				$counter++;
				if (($available = (int) $serviceObj->hasStock($this, $wantedArticles, $articlesInStock))) {
					break;
				}
			}
		}

		if ($counter == 0) {
			return TRUE;
		}

		return $available;
	}

	/**
	 * Substract the wanted Articles from stock. If you have more than one stock
	 * which is handled to more than one Service please implement the Service due
	 * to Reference on $wantedArticles so you can reduce this amount steplike.
	 *
	 * @param int $wantedArticles Amount of Articles which should reduced from stock
	 * @param string $subType Sub type like file extensions or similar. Defined
	 * 		by the service.
	 * @param array $serviceChain List of service keys which should be exluded in
	 * 		the search for a service. Array or comma list.
	 *
	 * @return bool Describes the result of going through the chains
	 */
	public function reduceStock($wantedArticles = 0, $subType = '', array $serviceChain = array()) {
		$counter = 0;

		while (is_object($serviceObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService(
			'stockHandling', $subType, $serviceChain
		))) {
			$serviceChain .= ',' . $serviceObj->getServiceKey();
			if (method_exists($serviceObj, 'reduceStock')) {
				$serviceObj->reduceStock($wantedArticles, $this);
			}
		}
		if ($counter == 0) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Returns the data of this object als array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 *
	 * @return array Assoc Arry of data
	 */
	public function returnAssocArray($prefix = '') {
		$data = parent::returnAssocArray($prefix);
		$data[$prefix . 'stock'] = $this->getStock();

		return $data;
	}


	/**
	 * Title of article
	 *
	 * @return string title of article
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getTitle instead
	 */
	public function get_title() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getTitle();
	}

	/**
	 * Subtitle
	 *
	 * @return string title of article
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getSubtitle instead
	 */
	public function get_subtitle() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getSubtitle();
	}

	/**
	 * Classname
	 *
	 * @return string classname
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getClassname instead
	 */
	public function get_classname() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getClassname();
	}

	/**
	 * Description exta
	 *
	 * @return string description exta
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getDescriptionExtra instead
	 */
	public function get_description_extra() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getDescriptionExtra();
	}

	/**
	 * Article price uid
	 *
	 * @return int valid priceid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceUid instead
	 */
	public function get_article_price_uid() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getPriceUid();
	}

	/**
	 * Price gross
	 *
	 * @return double price_gross
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceGross instead
	 */
	public function get_price_gross() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getPriceGross();
	}

	/**
	 * Price net
	 *
	 * @return double price_net
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceNet instead
	 */
	public function get_price_net() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getPriceNet();
	}

	/**
	 * Article price uid
	 *
	 * @return int valid priceid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceUid instead
	 */
	public function getArticlePriceUid() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getPriceUid();
	}

	/**
	 * Get Article all possible prices as uid Array
	 *
	 * @return array or priceUid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceUids instead
	 */
	public function getPossiblePriceUids() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getPriceUids();
	}

	/**
	 * Tax
	 *
	 * @return double tax
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getTax instead
	 */
	public function get_tax() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getTax();
	}

	/**
	 * Order number
	 *
	 * @return string ordernumber
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getOrdernumber instead
	 */
	public function get_ordernumber() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getOrdernumber();
	}

	/**
	 * Parent product as object
	 *
	 * @see tx_commerce_product
	 * @return Tx_Commerce_Domain_Model_Product Product object
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getParentProduct instead
	 */
	public function get_parent_product() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getParentProduct();
	}

	/**
	 * Returns the article attributes
	 * array ( attribut_uid =>
	 *     array ('title =>' $title,
	 * 'value' => $value,
	 * 'unit' => $unit),
	 *  ...
	 * )
	 *
	 * @return array of arrays
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getArticleAttributes instead
	 */
	public function get_article_attributes() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getArticleAttributes();
	}

	/**
	 * Article type uid
	 *
	 * @return int article_type
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getArticleTypeUid instead
	 */
	public function get_article_type_uid() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getArticleTypeUid();
	}

	/**
	 * Gets the price of this article and stores in private variable
	 *
	 * @param bool $translationMode Translation mode
	 *
	 * @return int
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::loadPrices instead
	 */
	public function load_prices($translationMode = FALSE) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->loadPrices($translationMode);
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

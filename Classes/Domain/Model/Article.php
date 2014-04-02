<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Main script class for the handling of articles. Normaly used
 * for frontend rendering. This class provides basic methodes for acessing
 * articles.
 * Inherited from Tx_Commerce_Domain_Model_AbstractEntity
 */
class Tx_Commerce_Domain_Model_Article extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_ArticleRepository';

	/**
	 * @var Tx_Commerce_Domain_Repository_ArticleRepository;
	 */
	public $databaseConnection;

	/**
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
	 * article description
	 *
	 * @var string
	 */
	protected $descriptionExtra;

	/**
	 * Normal Tax for this article in Percent
	 *
	 * @var integer
	 */
	protected $tax;

	/**
	 * Images for the article
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
	 * ordernumber for this article
	 *
	 * @var string
	 */
	protected $ordernumber;

	/**
	 * Eancode for this article
	 *
	 * @var string
	 */
	protected $eancode;

	/**
	 * Parent product Uid
	 *
	 * @var integer
	 */
	protected $uid_product;

	/**
	 * Related page
	 *
	 * @var integer
	 */
	protected $relatedpage;

	/**
	 * UID for the article Type (should be refered to table tx_commerce_article_types)
	 *
	 * @var integer
	 */
	protected $article_type_uid;

	/**
	 * @var integer
	 */
	protected $supplier_uid;

	/**
	 * cost for displaying the article delivery cost on the page
	 * needed for german Law Preisauszeichnung
	 *
	 * @var integer
	 */
	protected $deliveryCostNet;

	/**
	 * cost for displaying the article delivery cost on the page
	 * needed for german Law Preisauszeichnung
	 *
	 * @var integer
	 */
	protected $deliveryCostGross;

	/**
	 * uid from actual article price
	 *
	 * @var integer
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
	 * if the price is loaded from the database
	 *
	 * @var boolean
	 */
	protected $prices_loaded = FALSE;

	/**
	 * @var array
	 */
	protected $specialPrice;

	/**
	 * Stock for this article
	 *
	 * @var boolean
	 */
	protected $stock = TRUE;

	/**
	 * classname if the article is a payment type
	 *
	 * @var string
	 */
	protected $classname;

	/**
	 * Constructor Method, calles init method
	 *
	 * @param int $uid
	 * @param int $languageUid
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
	 * @param int $uid uid of article
	 * @param int $languageUid language uid, default 0
	 * @return boolean
	 */
	public function init($uid, $languageUid = 0) {
		$this->uid = (int) $uid;
		$languageUid = (int) $languageUid;

		$return = FALSE;
		if ($this->uid > 0) {
			$this->lang_uid = $languageUid;
			$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['postinit'])) {
				t3lib_div::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'postinit\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'postinit\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['postinit'] as $classRef) {
					$hookObj = & t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['postinit'] as $classRef) {
					$hookObj = & t3lib_div::getUserObj($classRef);
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
	 * @param integer $count    Count for this article
	 * @return integer Price Uid
	 */
	public function getActualPriceforScaleUid($count) {
			// Hook for doing your own calculation
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['getActualPriceforScaleUid']) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'getActualPriceforScaleUid\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'getActualPriceforScaleUid\']
			');
			$hookObject = & t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['getActualPriceforScaleUid']);
			if (is_object($hookObject) && (method_exists($hookObject, 'getActualPriceforScaleUid'))) {
				return $hookObject->getActualPriceforScaleUid($count, $this);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['getActualPriceforScaleUid']) {
			$hookObject = & t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['getActualPriceforScaleUid']);
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

		/** @var t3lib_pageSelect $pageSelect */
		$pageSelect = t3lib_div::makeInstance('t3lib_pageSelect');
		$whereClause = $pageSelect->enableFields('tx_commerce_attributes', '', $ignore);

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

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
	 * @return integer article_type
	 */
	public function getArticleTypeUid() {
		return $this->article_type_uid;
	}

	/**
	 * Gets the Value from one distinct attribute of this article
	 *
	 * @param integer $attributeUid
	 * @param boolean $valueListAsUid
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
	 * @return integer
	 */
	public function getDeliveryCostNet() {
		return $this->deliveryCostNet;
	}

	/**
	 * Delivery Cost for this article
	 *
	 * @return integer
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
		return $this->descriptionExtra;
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
	 * Returns an Array of Images
	 *
	 * @return array;
	 */
	public function getImages() {
		return $this->images_array;
	}

	/**
	 * Getter
	 *
	 * @return string ordernumber
	 */
	public function getOrdernumber() {
		return $this->ordernumber;
	}

	/**
	 * price_gross
	 *
	 * @return double
	 */
	public function getPriceGross() {
		$result = 'no valid price';

		if ($this->price instanceof Tx_Commerce_Domain_Model_ArticlePrice) {
			$result = $this->price->getPriceGross();
		}

		return $result;
	}

	/**
	 * price_net
	 *
	 * @return double
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
	 * @param integer $startCount Count where to start with the
	 * 		listing of the sacles, default 1
	 * @return array or prices grouped by the different scales
	 */
	public function getPriceScaleObjects($startCount = 1) {
		$return = array();
		$arrayOfPricesUids = $this->getPriceScales($startCount);

		if (is_array($arrayOfPricesUids)) {
			foreach ($arrayOfPricesUids as $startCount => $tmpArray) {
				foreach ($tmpArray as $endCount => $pricdUid) {
					/** @var Tx_Commerce_Domain_Model_ArticlePrice $price */
					$price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
					$price->init($pricdUid);
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
	 * @param integer $startCount Count where to start with the
	 * 		listing of the sacles, default 1
	 * @return array or priceUid grouped by the different scales
	 */
	public function getPriceScales($startCount = 1) {
		return $this->databaseConnection->getPriceScales($this->uid, $startCount);
	}

	/**
	 * Returns the price Uid
	 *
	 * @return integer
	 */
	public function getPriceUid() {
		return $this->price_uid;
	}

	/**
	 * Get Article all possivle  prices as UDI Array
	 *
	 * @return array or priceUid
	 */
	public function getPriceUids() {
		return $this->databaseConnection->getPrices($this->uid);
	}

	/**
	 * returns the parent product as object
	 *
	 * @return Tx_Commerce_Domain_Model_Product Product object
	 */
	public function getParentProduct() {
		if ($this->uid_product) {
			$productsUid = $this->uid_product;
		} else {
			$productsUid = $this->databaseConnection->getParentProductUid($this->getUid());
		}

		/** @var $product Tx_Commerce_Domain_Model_Product */
		$product = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Product', $productsUid);
		return $product;
	}

	/**
	 * returns the parent Product Uid
	 *
	 * @see tx_commerce_product
	 * @return integer uid of tx_commerce_products
	 */
	public function getParentProductUid() {
		$result = FALSE;

		if ($this->uid_product) {
			$result = $this->uid_product;
		} else {
			$productsUid = $this->databaseConnection->getParentProductUid($this->uid);
			if ($productsUid > 0) {
				$result = $productsUid;
			}
		}

		return $result;
	}

	/**
	 * Returns the related page for the product
	 *
	 * @return integer
	 */
	public function getRelatedpage() {
		return $this->relatedpage;
	}

	/**
	 * returns the default price Object, which doesn't have any start or stoptime
	 *
	 * @return integer price_uid
	 */
	public function getSpecialPrice() {
		$this->loadPrices();

		$this->specialPrice = array(
			'object' => $this->price,
			'uid' => $this->price_uid
		);

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['specialPrice']) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'specialPrice\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'specialPrice\']
			');
			$hookObj = & t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['specialPrice']);
			if (method_exists($hookObj, 'specialPrice')) {
				$hookObj->specialPrice($this->specialPrice, $this->prices_uids);
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['specialPrice']) {
			$hookObj = & t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['specialPrice']);
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
	 * returns the Supplier Name of an Article, if set
	 *
	 * @return string Name of the supplier
	 */
	public function getSupplierName() {
		if ($this->getSupplierUid()) {
			return $this->databaseConnection->getSupplierName($this->getSupplierUid());
		}

		return '';
	}

	/**
	 * Returns the Supplier UID of the Article if set
	 *
	 * @return integer UID of supplier
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
	 * Loads the data and divides comma sparated images in array
	 *
	 * @param boolean $translationMode
	 * @return void
	 */
	public function loadData($translationMode = FALSE) {
		parent::loadData($translationMode);
		$this->loadPrices($translationMode);
		$this->images_array = t3lib_div::trimExplode(',', $this->images);
		$this->calculateDeliveryCosts();
	}

	/**
	 * Gets the price of this article and stores in private variable
	 *
	 * @param boolean $translationMode
	 * @return integer
	 */
	public function loadPrices($translationMode = FALSE) {
		if ($this->prices_loaded == FALSE) {
			$arrayOfPrices = $this->databaseConnection->getPrices($this->uid);
			$this->prices_uids = $arrayOfPrices;

			if ($this->prices_uids) {
				// If we do have a Logged in usergroup walk thrue and check if
				// there is a special price for this group
				if (
					(empty($GLOBALS['TSFE']->fe_user->groupData['uid']) == FALSE)
					&& ($GLOBALS['TSFE']->loginUser || count( $GLOBALS['TSFE']->fe_user->groupData['uid']) > 0)
				) {
					$tempGroups = $GLOBALS['TSFE']->fe_user->groupData['uid'];
					$groups = array();
					foreach ($tempGroups as $values) {
						$groups[] = $values;
					}

					$i = 0;
					while (!$this->prices_uids[$groups[$i]] && $groups[$i]) {
						$i++;
					}
					if ($groups[$i]) {
						$this->price_uid = $this->prices_uids[$groups[$i]][0];

						$this->price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
						$this->price->init($this->price_uid);
						$this->price->loadData($translationMode);
					} else {
						if ($this->prices_uids['-2']) {
							$this->price_uid = $this->prices_uids['-2'][0];

							$this->price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
							$this->price->init($this->price_uid);
							$this->price->loadData($translationMode);
						} else {
							$this->price_uid = $this->prices_uids[0][0];

							$this->price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
							$this->price->init($this->price_uid);
							if ($this->price) {
								$this->price->loadData($translationMode);
							} else {
								return FALSE;
							}
						}
					}
				} else {
						// No special Handling if no special usergroup is logged in
					if ($this->prices_uids['-1']) {
						$this->price_uid = $this->prices_uids['-1'][0];

						$this->price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
						$this->price->init($this->price_uid);
						$this->price->loadData($translationMode);
					} else {
						$this->price_uid = $this->prices_uids[0][0];

						$this->price = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_ArticlePrice');
						$this->price->init($this->price_uid);
						if ($this->price) {
							$this->price->loadData($translationMode);
						} else {
							return 0;
						}
					}
				}
				$this->prices_loaded = TRUE;

				$return = $this->price_uid;
			} else {
				return 0;
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
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_article.php\'][\'calculateDeliveryCost\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Article.php\'][\'calculateDeliveryCost\']
			');
			$hookObject = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost']);

			if (method_exists($hookObject, 'calculateDeliveryCostNet')) {
				$hookObject->calculateDeliveryCostNet($this->deliveryCostNet, $this);
			}

			if (method_exists($hookObject, 'calculateDeliveryCostGross')) {
				$hookObject->calculateDeliveryCostGross($this->deliveryCostGross, $this);
			}
		}
		if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost'])) {
			$hookObject = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Article.php']['calculateDeliveryCost']);

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
	 * @param string $subType string  Sub type like file extensions or similar.
	 * 		Defined by the service.
	 * @param array $serviceChain List of service keys which should be exluded in
	 * 		the search for a service. Array or comma list.
	 * @return integer amount of articles in stock
	 */
	public function getStock($subType = '', $serviceChain = array()) {
		$counter = 0;
		$articlesInStock = 0;

		while (is_object($serviceObj = t3lib_div::makeInstanceService('stockHandling', $subType, $serviceChain))) {
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
	 * Returns the avalibility of wanted amount of articles.
	 *
	 * @param int $wantedArticles amount of Articles which should be added to basket
	 * @param string $subType Sub type like file extensions or similar. Defined by
	 * 		the service.
	 * @param array $serviceChain List of service keys which should be exluded in the
	 * 		search for a service. Array or comma list.
	 * @return boolean avalibility of wanted amount of articles
	 */
	public function hasStock($wantedArticles = 0, $subType = '', $serviceChain = array()) {
		$counter = 0;
		$available = FALSE;
		$articlesInStock = $this->getStock($subType, $serviceChain);

		while (is_object($serviceObj = t3lib_div::makeInstanceService('stockHandling', $subType, $serviceChain))) {
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
	 * substract the wanted Articles from stock. If you have more than one stock
	 * which is handled to more than one Service please implement the Service due
	 * to Reference on $wantedArticles so you can reduce this amount steplike.
	 *
	 * @param int $wantedArticles amount of Articles which should reduced from stock
	 * @param string $subType Sub type like file extensions or similar. Defined
	 * 		by the service.
	 * @param array $serviceChain List of service keys which should be exluded in
	 * 		the search for a service. Array or comma list.
	 * @return boolean Decribes the result of going through the chains
	 */
	public function reduceStock($wantedArticles = 0, $subType = '', $serviceChain = array()) {
		$counter = 0;

		while (is_object($serviceObj = t3lib_div::makeInstanceService('stockHandling', $subType, $serviceChain))) {
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
	 * @return array Assoc Arry of data
	 */
	public function returnAssocArray($prefix = '') {
		$data = parent::returnAssocArray($prefix);
		$data[$prefix . 'stock'] = $this->getStock();

		return $data;
	}


	/**
	 * @return string title of article
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getTitle instead
	 */
	public function get_title() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTitle();
	}

	/**
	 * @return string title of article
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getSubtitle instead
	 */
	public function get_subtitle() {
		t3lib_div::logDeprecatedFunction();
		return $this->getSubtitle();
	}

	/**
	 * @return string title of article
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getClassname instead
	 */
	public function get_classname() {
		t3lib_div::logDeprecatedFunction();
		return $this->getClassname();
	}

	/**
	 * @return string title of article
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getDescriptionExtra instead
	 */
	public function get_description_extra() {
		t3lib_div::logDeprecatedFunction();
		return $this->getDescriptionExtra();
	}

	/**
	 * @return integer valid priceid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceUid instead
	 */
	public function get_article_price_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceUid();
	}

	/**
	 * @return double price_gross
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceGross instead
	 */
	public function get_price_gross() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceGross();
	}

	/**
	 * @return double price_net
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceNet instead
	 */
	public function get_price_net() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceNet();
	}

	/**
	 * @return integer valid priceid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceUid instead
	 */
	public function getArticlePriceUid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceUid();
	}

	/**
	 * Get Article all possivle  prices as UDI Array
	 *
	 * @return array or priceUid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getPriceUids instead
	 */
	public function getPossiblePriceUids() {
		t3lib_div::logDeprecatedFunction();
		return $this->getPriceUids();
	}

	/**
	 * @return double tax
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getTax instead
	 */
	public function get_tax() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTax();
	}

	/**
	 * @return string ordernumber
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getOrdernumber instead
	 */
	public function get_ordernumber() {
		t3lib_div::logDeprecatedFunction();
		return $this->getOrdernumber();
	}

	/**
	 * returns the parent product as object
	 *
	 * @see tx_commerce_product
	 * @return Tx_Commerce_Domain_Model_Product Product object
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getParentProduct instead
	 */
	public function get_parent_product() {
		t3lib_div::logDeprecatedFunction();
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
		t3lib_div::logDeprecatedFunction();
		return $this->getArticleAttributes();
	}

	/**
	 * @return integer article_type
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::getArticleTypeUid instead
	 */
	public function get_article_type_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getArticleTypeUid();
	}

	/**
	 * Gets the price of this article and stores in private variable
	 *
	 * @param boolean $translationMode
	 * @return integer
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_article::loadPrices instead
	 */
	public function load_prices($translationMode = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->loadPrices($translationMode);
	}
}

class_alias('Tx_Commerce_Domain_Model_Article', 'tx_commerce_article');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/Article.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/Article.php']);
}

?>
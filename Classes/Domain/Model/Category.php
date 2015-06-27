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
 * Main script class for the handling of categories. Categories contains
 * categories (Reverse data structure) and products
 *
 * Class Tx_Commerce_Domain_Model_Category
 *
 * @author 2005-2012 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Domain_Model_Category extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * Database class name
	 *
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_CategoryRepository';

	/**
	 * Database connection
	 *
	 * @var Tx_Commerce_Domain_Repository_CategoryRepository
	 */
	public $databaseConnection;

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Subtitle
	 *
	 * @var string
	 */
	protected $subtitle = '';

	/**
	 * Description
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Images
	 *
	 * @var string
	 */
	protected $images = '';

	/**
	 * Images as array
	 *
	 * @var array
	 */
	protected $images_array = array();

	/**
	 * Title for navigation an Menu Rendering
	 *
	 * @var string
	 */
	protected $navtitle = '';

	/**
	 * Keywords for meta informations
	 *
	 * @var string
	 */
	protected $keywords = '';

	/**
	 * Array with child category uids
	 *
	 * @var array
	 */
	protected $categories_uid = array();

	/**
	 * Parent category uid
	 *
	 * @var int
	 */
	protected $parent_category_uid = 0;

	/**
	 * Parent category object
	 *
	 * @var Tx_Commerce_Domain_Model_Category
	 */
	protected $parent_category = FALSE;

	/**
	 * Array with product uids
	 *
	 * @var array
	 */
	protected $products_uid = array();

	/**
	 * Array with category objects
	 *
	 * @var array Array of tx_commerce_categories
	 */
	protected $categories = NULL;

	/**
	 * Array of product objects
	 *
	 * @var array
	 */
	protected $products = NULL;

	/**
	 * Teaser text
	 *
	 * @var string
	 */
	protected $teaser = '';

	/**
	 * Teaser images
	 *
	 * @var string
	 */
	protected $teaserimages = '';

	/**
	 * Images as array
	 *
	 * @var array
	 */
	protected $teaserImagesArray = array();

	/**
	 * Is true when data is loaded
	 *
	 * @var bool
	 */
	protected $data_loaded = FALSE;

	/**
	 * The permissions array
	 *
	 * @var array
	 */
	public $perms_record = array();

	/**
	 * The uid of the user owning the category
	 *
	 * @var int
	 */
	public $perms_userid = 0;

	/**
	 * The uid of the group owning the category
	 *
	 * @var int
	 */
	public $perms_groupid = 0;

	/**
	 * User permissions
	 *
	 * @var int
	 */
	public $perms_user = 0;

	/**
	 * Group permissions
	 *
	 * @var int
	 */
	public $perms_group = 0;

	/**
	 * Everybody permissions
	 *
	 * @var int
	 */
	public $perms_everybody = 0;

	/**
	 * Editlock-flag
	 *
	 * @var int
	 */
	public $editlock = 0;

	/**
	 * Flag if permissions have been loaded
	 *
	 * @var bool
	 */
	public $permsLoaded = FALSE;

	/**
	 * Category typoscript config
	 *
	 * @var array
	 */
	protected $categoryTSconfig = array();

	/**
	 * Typoscript config
	 *
	 * @var array
	 */
	protected $tsConfig = array();

	/**
	 * Field list
	 *
	 * @var array
	 */
	protected $fieldlist = array(
		'uid',
		'title',
		'subtitle',
		'description',
		'teaser',
		'teaserimages',
		'navtitle',
		'keywords',
		'images',
		'ts_config',
		'l18n_parent'
	);

	/**
	 * Constructor, basically calls init
	 *
	 * @param int $uid Category uid
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
	 * Init called by the constructor
	 *
	 * @param int $uid Uid of category
	 * @param int $languageUid Language_uid , default 0
	 *
	 * @return bool TRUE on success, FALSE if no $uid is submitted
	 */
	public function init($uid, $languageUid = 0) {
		$uid = (int) $uid;
		$languageUid = (int) $languageUid;

		if ($uid > 0) {
			$this->uid = $uid;
			$this->lang_uid = $languageUid;
			$this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_category.php']['postinit'])) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_category.php\'][\'postinit\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Category.php\'][\'postinit\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_category.php']['postinit'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Category.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Category.php']['postinit'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns recursivly the category path as text
	 * path segments are glued with $separatora
	 *
	 * @param string $separator Default ','
	 *
	 * @return string Category path segment
	 */
	public function getCategoryPath($separator = ',') {
		if ($this->parent_category_uid > 0) {
			$parent = $this->getParentCategory();
			$parent->loadData();
			$result = $parent->getCategoryPath($separator) . $separator . $this->getTitle();
		} else {
			$result = $this->getTitle();
		}

		return $result;
	}

	/**
	 * Returns the child categories as an list of UIDs
	 *
	 * @return array Array of child category UIDs
	 */
	public function getCategoryUids() {
		return $this->categories_uid;
	}

	/**
	 * Loads the child categories in the categories array
	 *
	 * @return array of categories as array of category objects
	 */
	public function getChildCategories() {
		if (is_null($this->categories) && is_array($this->categories_uid)) {
			$this->categories = array();
			foreach ($this->categories_uid as $childCategoryUid) {
				/**
				 * Child category
				 *
				 * @var Tx_Commerce_Domain_Model_Category $childCategory
				 */
				$childCategory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_Category',
					$childCategoryUid,
					$this->lang_uid
				);

				$this->categories[$childCategoryUid] = $childCategory;
			}
		}

		return $this->categories;
	}

	/**
	 * Set child categories
	 *
	 * @param array $categories Child categories
	 *
	 * @return void
	 */
	public function setChildCategories(array $categories) {
		if (is_array($categories)) {
			$this->categories = $categories;
		}
	}

	/**
	 * Returns a list of all child categories from this category
	 *
	 * @param bool|int $depth Maximum depth for going recursive
	 *
	 * @return array List of category uids
	 */
	public function getChildCategoriesUidlist($depth = FALSE) {
		if ($depth) {
			$depth--;
		}
		$this->loadData();
		$this->getChildCategories();

		$returnList = array();
		if (count($this->categories) > 0) {
			if (($depth === FALSE) || ($depth > 0)) {
				/**
				 * Category
				 *
				 * @var Tx_Commerce_Domain_Model_Category $category
				 */
				foreach ($this->categories as $category) {
					$returnList = array_merge($returnList, $category->getChildCategoriesUidlist($depth));
				}
			}
			$returnList = array_merge($returnList, $this->categories_uid);
		}

		return $returnList;
	}

	/**
	 * Returns the number of child categories
	 *
	 * @return int Number of child categories
	 */
	public function getChildCategoriesCount() {
		return is_array($this->categories_uid) ? count($this->categories_uid) : 0;
	}

	/**
	 * Loads the child products in the products array
	 *
	 * @return array Array of products as array of products objects
	 */
	public function getChildProducts() {
		if ($this->products === NULL) {
			foreach ($this->products_uid as $productUid) {
				/**
				 * Child product
				 *
				 * @var Tx_Commerce_Domain_Model_Product $childProduct
				 */
				$childProduct = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_Product',
					$productUid,
					$this->lang_uid
				);

				$this->products[$productUid] = $childProduct;
			}
		}

		return $this->products;
	}

	/**
	 * Returns the category description
	 *
	 * @return string Description;
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Returns the editlock flag
	 *
	 * @return int Editlock-Flag
	 */
	public function getEditlock() {
		return $this->editlock;
	}

	/**
	 * Returns an array of categoryimages
	 *
	 * @return array Array of images;
	 */
	public function getImages() {
		return $this->images_array;
	}

	/**
	 * Returns the category keywords
	 *
	 * @return string Keywords;
	 */
	public function getKeywords() {
		return $this->keywords;
	}

	/**
	 * Returns an array with the different l18n for the category
	 *
	 * @return array Categories
	 */
	public function getL18nCategories() {
		return $this->databaseConnection->getL18nCategories($this->uid);
	}

	/**
	 * Returns the category navigationtitle
	 *
	 * @return string Navigationtitle;
	 */
	public function getNavtitle() {
		return $this->navtitle;
	}

	/**
	 * Loads the parent category in the parent-category variable
	 *
	 * @return Tx_Commerce_Domain_Model_Category|FALSE category object or FALSE
	 * 		if this category is already the topmost category
	 */
	public function getParentCategory() {
		if ($this->parent_category_uid && !$this->parent_category) {
			$this->parent_category = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'Tx_Commerce_Domain_Model_Category',
				$this->parent_category_uid,
				$this->lang_uid
			);
		}

		return $this->parent_category;
	}

	/**
	 * Returns an array of category objects (unloaded)
	 * that serve as category's parent
	 *
	 * @return array Array of category objects
	 */
	public function getParentCategories() {
		$parents = $this->databaseConnection->getParentCategories($this->uid);
		$parentCats = array();
		foreach ($parents as $parent) {
			/**
			 * Category
			 *
			 * @var Tx_Commerce_Domain_Model_Category $category
			 */
			$category = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'Tx_Commerce_Domain_Model_Category',
				$parent,
				$this->lang_uid
			);
			$parentCats[] = $category;
		}

		return $parentCats;
	}

	/**
	 * Returns all category ID's above this uid
	 *
	 * @return array List of category uids
	 */
	public function getParentCategoriesUidlist() {
		$returnList = array();
		$this->loadData();
		if (($parentCategory = $this->getParentCategory())) {
			$returnList = $parentCategory->getParentCategoriesUidlist();
		}
		$returnList = array_merge($returnList, array($this->uid));

		return array_unique($returnList);
	}

	/**
	 * Returns the Group-ID of the category
	 *
	 * @return int UID of group
	 */
	public function getPermsGroupId() {
		return $this->perms_groupid;
	}

	/**
	 * Returns the User-ID of the category
	 *
	 * @return int UID of user
	 */
	public function getPermsUserId() {
		return $this->perms_userid;
	}

	/**
	 * Returns the permissions for everybody
	 *
	 * @return int Permissions for everybody
	 */
	public function getPermsEverybody() {
		return $this->perms_everybody;
	}

	/**
	 * Returns the Permissions for the group
	 *
	 * @return int Permissions for group
	 */
	public function getPermsGroup() {
		return $this->perms_group;
	}

	/**
	 * Returns the Permissions for the user
	 *
	 * @return int Permissions for user
	 */
	public function getPermsUser() {
		return $this->perms_user;
	}

	/**
	 * Returns a list of all products under this category
	 *
	 * @param bool|int $depth Depth maximum depth for going recursive
	 *
	 * @return array Array with list of product UIDs
	 */
	public function getProducts($depth = FALSE) {
		$returnList = $this->getProductUids();
		if ($depth === FALSE) {
			$depth = PHP_INT_MAX;
		}
		if ($depth > 0) {
			$childCategoriesList = $this->getChildCategoriesUidlist($depth);
			foreach ($childCategoriesList as $oneCategoryUid) {
				/**
				 * Category
				 *
				 * @var Tx_Commerce_Domain_Model_Category $category
				 */
				$category = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_Category',
					$oneCategoryUid,
					$this->lang_uid
				);
				$category->loadData();
				$returnList = array_merge($returnList, $category->getProductUids());
			}
		}

		return array_unique($returnList);
	}

	/**
	 * Returns the childproducts as unique UID list
	 *
	 * @return array Array of child products UIDs
	 */
	public function getProductUids() {
		return array_unique($this->products_uid);
	}

	/**
	 * Returns the subtitle of the category
	 *
	 * @return string Subtitle;
	 */
	public function getSubtitle() {
		return $this->subtitle;
	}

	/**
	 * Returns the category teaser
	 *
	 * @return string Teaser
	 */
	public function getTeaser() {
		return $this->teaser;
	}

	/**
	 * Returns the first image, if not availiabe, walk recursive up, to get the image
	 *
	 * @return mixed Image/FALSE, if no image found
	 */
	public function getTeaserImage() {
		if (!empty($this->images_array[0])) {
			return $this->images_array[0];
		} else {
			if (($parentCategory = $this->getParentCategory())) {
				$parentCategory->loadData();

				return $parentCategory->getTeaserImage();
			}
		}

		return FALSE;
	}

	/**
	 * Returns an array of teaserimages
	 *
	 * @return array Teaserimages;
	 */
	public function getTeaserImages() {
		return $this->teaserImagesArray;
	}

	/**
	 * Returns the title of the category
	 *
	 * @return string Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Returns the category TSconfig array based on the currect->rootLine
	 *
	 * @todo Make recursiv category TS merging
	 * @return array
	 */
	public function getTyposcriptConfig() {
		if (!is_array($this->categoryTSconfig)) {
			$tSdataArray[] = $this->tsConfig;
			$tSdataArray = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines_array($tSdataArray);
			$categoryTs = implode(chr(10) . '[GLOBAL]' . chr(10), $tSdataArray);

			/**
			 * Typoscript parser
			 *
			 * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser $parseObj
			 */
			$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
			$parseObj->parse($categoryTs);
			$this->categoryTSconfig = $parseObj->setup;
		}

		return $this->categoryTSconfig;
	}

	/**
	 * Returns the uid of the category
	 *
	 * @return int
	 */
	public function getUid() {
		return $this->uid;
	}


	/**
	 * Loads the data
	 *
	 * @param bool $translationMode Translation mode of the record,
	 * 		default FALSE to use the default way of translation
	 *
	 * @return void
	 */
	public function loadData($translationMode = FALSE) {
		if ($this->data_loaded == FALSE) {
			parent::loadData($translationMode);
			$this->images_array = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->images, TRUE);
			$this->teaserImagesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->teaserimages, TRUE);

			$this->categories_uid = array_unique($this->databaseConnection->getChildCategories($this->uid, $this->lang_uid));
			$this->parent_category_uid = $this->databaseConnection->getParentCategory($this->uid);
			$this->products_uid = array_unique($this->databaseConnection->getChildProducts($this->uid, $this->lang_uid));
			$this->data_loaded = TRUE;
		}
	}

	/**
	 * Loads the permissions
	 *
	 * @return void
	 */
	public function loadPermissions() {
		if (!$this->permsLoaded && $this->uid) {
			$this->permsLoaded = TRUE;

			$this->perms_record = $this->databaseConnection->getPermissionsRecord($this->uid);

				// if the record is´nt loaded, abort.
			if (count($this->perms_record) <= 0) {
				$this->perms_record = NULL;

				return;
			}

			$this->perms_userid = $this->perms_record['perms_userid'];
			$this->perms_groupid = $this->perms_record['perms_groupid'];
			$this->perms_user = $this->perms_record['perms_userid'];
			$this->perms_group = $this->perms_record['perms_group'];
			$this->perms_everybody = $this->perms_record['perms_everybody'];
			$this->editlock = $this->perms_record['editlock'];
		}
	}

	/**
	 * Returns whether the permission is set and allowed for the current usera
	 *
	 * @param int $perm Permission
	 *
	 * @return bool TRUE if permission is set, FALSE if permission is not set
	 */
	public function isPermissionSet($perm) {
		if (!is_string($perm)) {
			return FALSE;
		}
		$this->loadPermissions();

		return Tx_Commerce_Utility_BackendUtility::isPermissionSet($perm, $this->perms_record);
	}

	/**
	 * Returns if the actual category has subcategories
	 *
	 * @return bool TRUE if the category has subcategories, FALSE if not
	 */
	public function hasSubcategories() {
		return count($this->categories_uid) > 0;
	}

	/**
	 * Returns if this category has products
	 *
	 * @return bool TRUE, if this category has products, FALSE if not
	 */
	public function hasProducts() {
		return count($this->getProductUids());
	}

	/**
	 * Returns if this category has products with stock
	 *
	 * @return bool TRUE, if this category has products with stock, FALSE if not
	 */
	public function hasProductsWithStock() {
		$result = FALSE;

		if ($this->hasProducts()) {
			$result = count(Tx_Commerce_Utility_GeneralUtility::removeNoStockProducts($this->getProducts(), 0));
		}

		return $result;
	}

	/**
	 * Returns TRUE if this category has active products or
	 * if sub categories have active products
	 *
	 * @param bool|int $depth maximum depth for going recursive,
	 * 		if not set go for maximum
	 *
	 * @return bool Returns TRUE, if category/subcategories hav active products
	 */
	public function hasProductsInSubCategories($depth = FALSE) {
		if ($this->hasProducts()) {
			return TRUE;
		}
		if ($depth === FALSE) {
			$depth = PHP_INT_MAX;
		}
		if ($depth > 0) {
			$childCategoriesList = $this->getChildCategoriesUidlist($depth);
			foreach ($childCategoriesList as $oneCategoryUid) {
				/** @var Tx_Commerce_Domain_Model_Category $category */
				$category = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $oneCategoryUid, $this->lang_uid);
				$category->loadData();
				$returnValue = $category->hasProductsInSubCategories($depth);
				if ($returnValue == TRUE) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Carries out the move of the category to the new parent
	 * Permissions are NOT checked, this MUST be done beforehand
	 *
	 * @param int $uid UID of the move target
	 * @param string $op Operation of move (can be 'after' or 'into')
	 *
	 * @return bool TRUE if the move was successful, FALSE if not
	 */
	public function move($uid, $op = 'after') {
		if ($op == 'into') {
				// the $uid is a future parent
			$parentUid = $uid;
		} else {
			return FALSE;
		}
			// Update parent_category
		$set = $this->databaseConnection->updateRecord($this->uid, array('parent_category' => $parentUid));
			// Only update relations if parent_category was successfully set
		if ($set) {
			$catList = array($parentUid);
			$catList = Tx_Commerce_Utility_BackendUtility::getUidListFromList($catList);
			$catList = Tx_Commerce_Utility_BackendUtility::extractFieldArray($catList, 'uid_foreign', TRUE);

			Tx_Commerce_Utility_BackendUtility::saveRelations($this->uid, $catList, 'tx_commerce_categories_parent_category_mm', TRUE);
		} else {
			return FALSE;
		}

		return TRUE;
	}
}

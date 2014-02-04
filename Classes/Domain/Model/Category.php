<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2012 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Main script class for the handling of categories. Categories contains
 * categories (Reverse data structure) and products
 */
class Tx_Commerce_Domain_Model_Category extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_CategoryRepository';

	/**
	 * @var Tx_Commerce_Domain_Repository_CategoryRepository
	 */
	public $databaseConnection;

	/**
	 * @var string Title
	 */
	protected $title = '';

	/**
	 * @var string Subtitle
	 */
	protected $subtitle = '';

	/**
	 * @var string Description
	 */
	protected $description = '';

	/**
	 * @var string Images for the category
	 */
	protected $images = '';

	/**
	 * @var array Image-Array for the category
	 */
	protected $images_array = array();

	/**
	 * @var string Title for navigation an Menu Rendering
	 */
	protected $navtitle = '';

	/**
	 * @var string Keywords for meta informations
	 */
	protected $keywords = '';

	/**
	 * @var array Array of tx_commerce_category_uid
	 */
	protected $categories_uid = array();

	/**
	 * @var integer UID of parent category
	 */
	protected $parent_category_uid = 0;

	/**
	 * @var object Parent category object
	 */
	protected $parent_category = '';

	/**
	 * @var array Array of tx_commerce_product_uid
	 */
	protected $products_uid = array();

	/**
	 * @var array Array of tx_commerce_categories
	 */
	protected $categories = NULL;

	/**
	 * @var array Array of tx_commerce_products
	 */
	protected $products = NULL;

	/**
	 * @var string Teaser text
	 */
	protected $teaser = '';

	/**
	 * @var string Images database field
	 */
	protected $teaserimages = '';

	/**
	 * @var array Images for the category
	 */
	protected $teaserImagesArray = array();

	/**
	 * @var boolean Is true when data is loaded
	 */
	protected $data_loaded = FALSE;

	/**
	 * @var array The permissions array with the fields from the category
	 */
	public $perms_record = array();

	/**
	 * @var integer The uid of the user owning the category
	 */
	public $perms_userid = 0;

	/**
	 * @var integer The uid of the group owning the category
	 */
	public $perms_groupid = 0;

	/**
	 * @var integer User permissions
	 */
	public $perms_user = 0;

	/**
	 * @var integer Group permissions
	 */
	public $perms_group = 0;

	/**
	 * @var integer Everybody permissions
	 */
	public $perms_everybody = 0;

	/**
	 * @var integer Editlock-flag
	 */
	public $editlock = 0;

	/**
	 * @var boolean Flag if permissions have been loaded
	 */
	public $permsLoaded = FALSE;

	/**
	 * @var array
	 */
	protected $categoryTSconfig = array();

	/**
	 * @var array
	 */
	protected $tsConfig = array();

	/**
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
	 * @param integer $uid
	 * @param integer $languageUid
	 * @return self
	 */
	public function __construct($uid, $languageUid = 0) {
		if ((int) $uid && (int) $languageUid) {
			$this->init($uid, $languageUid);
		}
	}

	/**
	 * Init called by the constructor
	 *
	 * @param integer $uid Uid of category
	 * @param integer $languageUid Language_uid , default 0
	 * @return boolean TRUE on success, FALSE if no $uid is submitted
	 */
	public function init($uid, $languageUid = 0) {
		$uid = (int) $uid;
		$languageUid = (int) $languageUid;

		if ($uid > 0) {
			$this->uid = $uid;
			$this->lang_uid = $languageUid;
			$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_category.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_category.php']['postinit'] as $classRef) {
					$hookObj = & t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}

			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns recursivly the category path as text
	 * path segments are glued with $separatora
	 *
	 * @param string $separator default '-'
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
		if (is_null($this->categories)) {
			$this->categories = array();
			foreach ($this->categories_uid as $childCategoryUid) {
				$childCategory = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
				$childCategory->init($childCategoryUid, $this->lang_uid);

				$this->categories[$childCategoryUid] = $childCategory;
			}
		}

		return $this->categories;
	}

	/**
	 * Returns a list of all child categories from this category
	 *
	 * @param boolean|integer $depth Maximum depth for going recursive
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
				/** @var Tx_Commerce_Domain_Model_Category $category */
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
	 * @return integer Number of child categories
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
				/** @var Tx_Commerce_Domain_Model_Product $childProduct */
				$childProduct = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Product');
				$childProduct->init($productUid, $this->lang_uid);

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
	 * @return integer Editlock-Flag
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
		$uid_lang = $this->databaseConnection->get_l18n_categories($this->uid);

		return $uid_lang;
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
	 * @return Tx_Commerce_Domain_Model_Category|FALSE category object or FALSE if this category is already the topmost category
	 */
	public function getParentCategory() {
		if ($this->parent_category_uid > 0) {
			$this->parent_category = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
			$this->parent_category->init($this->parent_category_uid, $this->lang_uid);

			return $this->parent_category;
		}

		return FALSE;
	}

	/**
	 * Returns an array of category objects (unloaded) that serve as the category's parent
	 *
	 * @return array Array of category objects
	 */
	public function getParentCategories() {
		$parents = $this->databaseConnection->get_parent_categories($this->uid);
		$parentCats = array();
		for ($i = 0, $l = count($parents); $i < $l; $i++) {
			/** @var Tx_Commerce_Domain_Model_Category $cat */
			$category = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
			$category->init($parents[$i]);
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
	 * @return integer UID of group
	 */
	public function getPermsGroupId() {
		return $this->perms_groupid;
	}

	/**
	 * Returns the User-ID of the category
	 *
	 * @return integer UID of user
	 */
	public function getPermsUserId() {
		return $this->perms_userid;
	}

	/**
	 * Returns the permissions for everybody
	 *
	 * @return integer Permissions for everybody
	 */
	public function getPermsEverybody() {
		return $this->perms_everybody;
	}

	/**
	 * Returns the Permissions for the group
	 *
	 * @return integer Permissions for group
	 */
	public function getPermsGroup() {
		return $this->perms_group;
	}

	/**
	 * Returns the Permissions for the user
	 *
	 * @return integer Permissions for user
	 */
	public function getPermsUser() {
		return $this->perms_user;
	}

	/**
	 * Returns a list of all products under this category
	 *
	 * @param boolean|integer $depth Depth maximum depth for going recursive
	 * @return array Array with list of product UIDs
	 */
	public function getProducts($depth = FALSE) {
		$return_list = $this->getProductUids();
		if ($depth === FALSE) {
			$depth = PHP_INT_MAX;
		}
		if ($depth > 0) {
			$childCategoriesList = $this->getChildCategoriesUidlist($depth);
			foreach ($childCategoriesList as $oneCategoryUid) {
				/** @var Tx_Commerce_Domain_Model_Category $category */
				$category = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
				$category->init($oneCategoryUid, $this->lang_uid);
				$category->loadData();
				$return_list = array_merge($return_list, $category->getProductUids());
			}
		}

		return array_unique($return_list);
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
	 * @return string Teaser;
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
			} else {
				return FALSE;
			}
		}
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
			$tSdataArray = t3lib_TSparser::checkIncludeLines_array($tSdataArray);
			$categoryTS = implode(chr(10) . '[GLOBAL]' . chr(10), $tSdataArray);

			/** @var t3lib_TSparser $parseObj */
			$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
			$parseObj->parse($categoryTS);
			$this->categoryTSconfig = $parseObj->setup;
		}

		return $this->categoryTSconfig;
	}

	/**
	 * Returns the UID of the category
	 *
	 * @return integer UID of the category
	 */
	public function getUid() {
		return $this->uid;
	}


	/**
	 * Loads the data
	 *
	 * @param boolean $translationMode Transaltionmode of the record, default FALSE to use the default way of translation
	 * @return void
	 */
	public function loadData($translationMode = FALSE) {
		if ($this->data_loaded == FALSE) {
			parent::loadData($translationMode);
			$this->images_array = t3lib_div::trimExplode(',', $this->images, TRUE);
			$this->teaserImagesArray = t3lib_div::trimExplode(',', $this->teaserimages, TRUE);

			$this->categories_uid = $this->databaseConnection->get_child_categories($this->uid, $this->lang_uid);
			$this->parent_category_uid = $this->databaseConnection->get_parent_category($this->uid);
			$this->products_uid = $this->databaseConnection->get_child_products($this->uid, $this->lang_uid);
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
	 * @param integer $perm Permission
	 * @return boolean TRUE if permission is set, FALSE if permission is not set
	 */
	public function isPSet($perm) {
		if (!is_string($perm)) {
			return FALSE;
		}
		$this->loadPermissions();

		return Tx_Commerce_Utility_BackendUtility::isPSet($perm, $this->perms_record);
	}

	/**
	 * Returns if the actual category has subcategories
	 *
	 * @return boolean TRUE if the category has subcategories, FALSE if not
	 */
	public function hasSubcategories() {
		return count($this->categories_uid) > 0;
	}

	/**
	 * Returns if this category has products
	 *
	 * @return boolean TRUE, if this category has products, FALSE if not
	 */
	public function hasProducts() {
		return count($this->getProductUids()) > 0;
	}

	/**
	 * Returns TRUE if this category has active products or if sub categories have active products
	 *
	 * @param boolean|integer $depth maximum deepth for going recursive, if not set go for maximum
	 * @return boolean Returns TRUE, if category/subcategories hav active products
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
				$category = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
				$category->init($oneCategoryUid, $this->lang_uid);
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
	 * @param integer $uid UID of the move target
	 * @param string $op Operation of move (can be 'after' or 'into')
	 * @return boolean TRUE if the move was successfull, FALSE if not
	 */
	public function move($uid, $op = 'after') {
		if ($op == 'into') {
				// the $uid is a future parent
			$parent_uid = $uid;
		} else {
			return FALSE;
		}
			// Update parent_category
		$set = $this->databaseConnection->updateRecord($this->uid, array('parent_category' => $parent_uid));
			// Only update relations if parent_category was successfully set
		if ($set) {
			$catList = array($parent_uid);
			$catList = Tx_Commerce_Utility_BackendUtility::getUidListFromList($catList);
			$catList = Tx_Commerce_Utility_BackendUtility::extractFieldArray($catList, 'uid_foreign', TRUE);

			Tx_Commerce_Utility_BackendUtility::saveRelations($this->uid, $catList, 'tx_commerce_categories_parent_category_mm', TRUE);
		} else {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Returns a list of all child categories from this category
	 *
	 * @param boolean|integer $depth Maximum depth for going recursive
	 * @return array List of category uids
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getChildCategoriesUidlist instead
	 */
	public function get_rec_child_categories_uidlist($depth = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->getChildCategoriesUidlist($depth);
	}

	/**
	 * Returns all category ID's above this uid
	 *
	 * @return array List of category uids
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getParentCategoriesUidlist instead
	 */
	public function get_categorie_rootline_uidlist() {
		t3lib_div::logDeprecatedFunction();
		return $this->getParentCategoriesUidlist();
	}

	/**
	 * Returns TRUE if this category has active products or if sub categories have active products
	 *
	 * @param boolean|integer $depth maximum deepth for going recursive, if not set go for maximum
	 * @return boolean Returns TRUE, if category/subcategories hav active products
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use hasProductsInSubCategories instead
	 */
	public function ProductsBelowCategory($depth = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->hasProductsInSubCategories($depth);
	}

	/**
	 * Returns an array with the different l18n for the category
	 *
	 * @return array Categories
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getTyposcritConfig instead
	 */
	public function get_l18n_categories() {
		t3lib_div::logDeprecatedFunction();
		return $this->getL18nCategories();
	}

	/**
	 * Returns the category TSconfig array based on the currect->rootLine
	 *
	 * @return array
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getTyposcritConfig instead
	 */
	public function getCategoryTSconfig() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTyposcriptConfig();
	}

	/**
	 * Returns the number of child categories
	 *
	 * @return integer Number of child categories
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getChildCategoriesCount instead
	 */
	public function numOfChildCategories() {
		t3lib_div::logDeprecatedFunction();
		return $this->getChildCategoriesCount();
	}

	/**
	 * Returns childproducts from the existing categories
	 *
	 * @return array Array og childproducts
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getChildProducts instead
	 */
	public function get_subproducts() {
		t3lib_div::logDeprecatedFunction();
		if (count($this->products) == 0) {
			return $this->getChildProducts();
		} else {
			return $this->products;
		}
	}

	/**
	 * Loads the child products in the products array
	 *
	 * @return array Array of products as array of products objects
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getChildProducts instead
	 */
	public function get_child_products() {
		t3lib_div::logDeprecatedFunction();
		return $this->getChildProducts();
	}

	/**
	 * Returns if the actual category has subproducts
	 *
	 * @return boolean TRUE if the category has subproducts, FALSE if not
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use hasProducts instead
	 */
	public function has_subproducts() {
		t3lib_div::logDeprecatedFunction();
		if (count($this->products_uid) > 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns if the actual category has subcategories
	 *
	 * @return boolean TRUE if the category has subcategories, FALSE if not
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use hasSubcategories instead
	 */
	public function has_subcategories() {
		t3lib_div::logDeprecatedFunction();
		return $this->hasSubcategories();
	}

	/**
	 * Returns Subcategories from the existiog categories
	 *
	 * @return array Array of subcategories
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getChildCategories instead
	 */
	public function getSubcategories() {
		t3lib_div::logDeprecatedFunction();
		if (count($this->categories) == 0) {
			return $this->getChildCategories();
		} else {
			return $this->categories;
		}
	}

	/**
	 * Loads the child categories in the categories array
	 *
	 * @return array of categories as array of category objects
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getChildCategories instead
	 */
	public function get_child_categories() {
		t3lib_div::logDeprecatedFunction();
		return $this->getChildCategories();
	}

	/**
	 * Returns recursivly the category path as text
	 * path segments are glued with $separatora
	 *
	 * @param string $separator default '-'
	 * @return string Category path segment
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getCategoryPath instead
	 */
	public function get_category_path($separator = ',') {
		t3lib_div::logDeprecatedFunction();
		return $this->getCategoryPath($separator);
	}

	/**
	 * Returns the category keywords
	 *
	 * @return string Keywords;
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getKeywords instead
	 */
	public function get_keywords() {
		t3lib_div::logDeprecatedFunction();
		return $this->getKeywords();
	}

	/**
	 * Loads the parent category in the parent-category variable
	 *
	 * @return Tx_Commerce_Domain_Model_Category|FALSE category object or FALSE if this category is already the topmost category
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getParentCategory instead
	 */
	public function get_parent_category() {
		t3lib_div::logDeprecatedFunction();
		return $this->getParentCategory();
	}

	/**
	 * Returns a list of all products under this category
	 *
	 * @param bool|int $depth Depth maximum depth for going recursive
	 * @return array Array with list of product UIDs
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getProducts instead
	 */
	public function getAllProducts($depth = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->getProducts($depth);
	}

	/**
	 * Loads the permissions
	 *
	 * @return void
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getNavtitle instead
	 */
	public function load_perms() {
		t3lib_div::logDeprecatedFunction();
		$this->loadPermissions();
	}

	/**
	 * Returns the category navigationtitle
	 *
	 * @return string Navigationtitle;
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getNavtitle instead
	 */
	public function get_navtitle() {
		t3lib_div::logDeprecatedFunction();
		return $this->getNavtitle();
	}

	/**
	 * Returns the category description
	 *
	 * @return string Description;
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getDescription instead
	 */
	public function get_description() {
		t3lib_div::logDeprecatedFunction();
		return $this->getDescription();
	}

	/**
	 * Returns an Array of Images
	 *
	 * @return array Array of images
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getImages instead
	 */
	public function get_images() {
		t3lib_div::logDeprecatedFunction();
		return $this->getImages();
	}

	/**
	 * Returns the category teaser
	 *
	 * @return string Teaser;
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getTeaser instead
	 */
	public function get_teaser() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTeaser();
	}

	/**
	 * Returns the subtitle of the category
	 *
	 * @return string Subtitle;
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getSubtitle instead
	 */
	public function get_subtitle() {
		t3lib_div::logDeprecatedFunction();
		return $this->getSubtitle();
	}

	/**
	 * Returns the category title
	 *
	 * @return string Returns the Category title
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use getTitle instead
	 */
	public function get_title() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTitle();
	}

	/**
	 * Constructor, basically calls init
	 *
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use loadData instead
	 */
	public function load_data($translationMode = FALSE) {
		t3lib_div::logDeprecatedFunction();
		$this->loadData($translationMode = FALSE);
	}
}

class_alias('Tx_Commerce_Domain_Model_Category', 'tx_commerce_category');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/Category.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/Category.php']);
}

?>
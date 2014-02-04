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
	protected $databaseClass = 'tx_commerce_db_category';

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
	protected $categories = array();

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
	 * @var tx_commerce_db_category
	 */
	public $databaseConnection;

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
	 * @return self
	 */
	public function __construct() {
		if ((func_num_args() > 0) && (func_num_args() <= 2)) {
			$uid = func_get_arg(0);
			if (func_num_args() > 1) {
				$languageUid = func_get_arg(1);
			} else {
				$languageUid = 0;
			}

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
		$uid = intval($uid);
		$languageUid = intval($languageUid);

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
	 * Constructor, basically calls init
	 *
	 * @deprecated since commerce 0.14.0, will be removed in commerce 0.15.0 - Use tx_commerce_category::loadData instead
	 */
	public function load_data() {
		t3lib_div::logDeprecatedFunction();
		call_user_func_array('loadData', $this, func_get_args());
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
			$this->parent_category_uid = intval($this->databaseConnection->get_parent_category($this->uid));
			$this->products_uid = $this->databaseConnection->get_child_products($this->uid, $this->lang_uid);
			$this->data_loaded = TRUE;
		}
	}

	/**
	 * Public methods for data retrieval
	 */

	/**
	 * Loads the permissions
	 *
	 * @return void
	 */
	public function load_perms() {
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
		$this->load_perms();

		return Tx_Commerce_Utility_BackendUtility::isPSet($perm, $this->perms_record);
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
	 * Returns the title of the category
	 *
	 * @return string Title
	 */
	public function getTitle() {
		return $this->title;
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
	 * Returns the subtitle of the category
	 *
	 * @return string Subtitle;
	 */
	public function get_subtitle() {
		return $this->subtitle;
	}

	/**
	 * Returns the category teaser
	 *
	 * @return string Teaser;
	 */
	public function get_teaser() {
		return $this->teaser;
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
	 * Returns the category description
	 *
	 * @return string Description;
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Returns the category navigationtitle
	 *
	 * @return string Navigationtitle;
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use tx_commerce_category::getNavtitle instead
	 */
	public function get_navtitle() {
		t3lib_div::logDeprecatedFunction();
		return $this->getNavtitle();
	}

	/**
	 * Returns the category keywords
	 *
	 * @return string Keywords;
	 */
	public function get_keywords() {
		return $this->keywords;
	}

	/**
	 * Returns Subcategories from the existiog categories
	 *
	 * @return array Array of subcategories
	 */
	public function get_subcategories() {
		if (count($this->categories) == 0) {
			return $this->get_child_categories();
		} else {
			return $this->categories;
		}
	}

	/**
	 * Returns childproducts from the existing categories
	 *
	 * @return array Array og childproducts
	 */
	public function get_subproducts() {
		if (count($this->products) == 0) {
			return $this->get_child_products();
		} else {
			return $this->products;
		}
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
	 * Returns the editlock flag
	 *
	 * @return integer Editlock-Flag
	 */
	public function getEditlock() {
		return $this->editlock;
	}

	/**
	 * Returns if the actual category has subcategories
	 *
	 * @return boolean TRUE if the category has subcategories, FALSE if not
	 */
	public function has_subcategories() {
		if (count($this->categories_uid) > 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns if the actual category has subproducts
	 *
	 * @return boolean TRUE if the category has subproducts, FALSE if not
	 */
	public function has_subproducts() {
		if (count($this->products_uid) > 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns an array with the different l18n for the category
	 *
	 * @return array Categories
	 */
	public function get_l18n_categories() {
		$uid_lang = $this->databaseConnection->get_l18n_categories($this->uid);

		return $uid_lang;
	}

	/**
	 * Loads the child categories in the categories array
	 *
	 * @return array of categories as array of category objects
	 */
	public function get_child_categories() {
		foreach ($this->categories_uid as $childCategoryUid) {
			$childCategory = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
			$childCategory->init($childCategoryUid, $this->lang_uid);

			$this->categories[$childCategoryUid] = $childCategory;
		}

		return $this->categories;
	}

	/**
	 * Returns the number of child categories
	 *
	 * @return integer Number of child categories
	 */
	public function numOfChildCategories() {
		if (is_array($this->categories_uid)) {
			return count($this->categories_uid);
		}

		return 0;
	}

	/**
	 * Loads the child products in the products array
	 *
	 * @return array Array of products as array of products objects
	 */
	public function get_child_products() {
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
	 * Returns the childproducts as unique UID list
	 *
	 * @return array Array of child products UIDs
	 */
	public function getProductUids() {
		return array_unique($this->products_uid);
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
	 * Loads the parent category in the parent-category variable
	 *
	 * @return Tx_Commerce_Domain_Model_Category|FALSE category object or FALSE if this category is already the topmost category
	 */
	public function get_parent_category() {
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
	 * Returns recursivly the category path as text
	 * path segments are glued with $separatora
	 *
	 * @param string $separator default '-'
	 * @return string Category path segment
	 */
	public function get_category_path($separator = ',') {
		if ($this->parent_category_uid > 0) {
			$parent = $this->get_parent_category();
			$parent->loadData();
			$result = $parent->get_category_path($separator) . $separator . $this->getTitle();
		} else {
			$result = $this->getTitle();
		}

		return $result;
	}

	/**
	 * Returns a list of all child categories from this category
	 *
	 * @param boolean|integer $depth Maximum depth for going recursive
	 * @return array List of category uids
	 */
	public function get_rec_child_categories_uidlist($depth = FALSE) {
		if ($depth) {
			$depth--;
		}
		$this->loadData();
		$this->get_child_categories();

		$returnList = array();
		if (count($this->categories) > 0) {
			if (($depth === FALSE) || ($depth > 0)) {
				/** @var Tx_Commerce_Domain_Model_Category $category */
				foreach ($this->categories as $category) {
					$returnList = array_merge($returnList, $category->get_rec_child_categories_uidlist($depth));
				}
			}
			$returnList = array_merge($returnList, $this->categories_uid);
		}

		return $returnList;
	}

	/**
	 * Returns a list of all products under this category
	 *
	 * @param bool|int $depth Depth maximum depth for going recursive
	 * @return array Array with list of product UIDs
	 */
	public function getAllProducts($depth = FALSE) {
		$return_list = $this->getProductUids();
		if ($depth === FALSE) {
			$depth = PHP_INT_MAX;
		}
		if ($depth > 0) {
			$childCategoriesList = $this->get_rec_child_categories_uidlist($depth);
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
	 * Returns if this category has products
	 *
	 * @return boolean TRUE, if this category has products, FALSE if not
	 */
	public function hasProducts() {
		if (count($this->getProductUids()) > 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns TRUE if this category has active products or if sub categories have active products
	 *
	 * @param boolean|integer $depth maximum deepth for going recursive, if not set go for maximum
	 * @return boolean Returns TRUE, if category/subcategories hav active products
	 */
	public function ProductsBelowCategory($depth = FALSE) {
		if ($this->hasProducts()) {
			return TRUE;
		}
		if ($depth === FALSE) {
			$depth = PHP_INT_MAX;
		}
		if ($depth > 0) {
			$childCategoriesList = $this->get_rec_child_categories_uidlist($depth);
			foreach ($childCategoriesList as $oneCategoryUid) {
				/** @var Tx_Commerce_Domain_Model_Category $category */
				$category = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
				$category->init($oneCategoryUid, $this->lang_uid);
				$category->loadData();
				$returnValue = $category->ProductsBelowCategory($depth);
				if ($returnValue == TRUE) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Returns all category ID's above this uid
	 *
	 * @return array List of category uids
	 */
	public function get_categorie_rootline_uidlist() {
		$returnList = array();
		$this->loadData();
		if (($parentCategory = $this->get_parent_category())) {
			$returnList = $parentCategory->get_categorie_rootline_uidlist();
		}
		$returnList = array_merge($returnList, array($this->uid));

		return array_unique($returnList);
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
			if (($parentCategory = $this->get_parent_category())) {
				$parentCategory->loadData();

				return $parentCategory->getTeaserImage();
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Returns the category TSconfig array based on the currect->rootLine
	 *
	 * @todo Make recursiv category TS merging
	 * @return array
	 */
	public function getCategoryTSconfig() {
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
	 * Returns an Array of Images
	 *
	 * @deprecated
	 * @return array Array of images
	 */
	public function get_images() {
		return $this->getImages();
	}

	/**
	 * Returns the category title
	 *
	 * @deprecated
	 * @return string Returns the Category title
	 */
	public function get_title() {
		return $this->title;
	}
}

class_alias('Tx_Commerce_Domain_Model_Category', 'tx_commerce_category');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_category.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_category.php']);
}

?>
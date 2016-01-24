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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main script class for the handling of categories. Categories contains
 * categories (Reverse data structure) and products.
 *
 * Class \CommerceTeam\Commerce\Domain\Model\Category
 *
 * @author 2005-2012 Ingo Schmitt <is@marketing-factory.de>
 */
class Category extends AbstractEntity
{
    /**
     * Database class name.
     *
     * @var string
     */
    protected $databaseClass = \CommerceTeam\Commerce\Domain\Repository\CategoryRepository::class;

    /**
     * Database connection.
     *
     * @var \CommerceTeam\Commerce\Domain\Repository\CategoryRepository
     */
    public $databaseConnection;

    /**
     * Title.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Subtitle.
     *
     * @var string
     */
    protected $subtitle = '';

    /**
     * Description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Images.
     *
     * @var string
     */
    protected $images = '';

    /**
     * Images as array.
     *
     * @var array
     */
    protected $images_array = array();

    /**
     * Title for navigation an Menu Rendering.
     *
     * @var string
     */
    protected $navtitle = '';

    /**
     * Keywords for meta informations.
     *
     * @var string
     */
    protected $keywords = '';

    /**
     * Array with child category uids.
     *
     * @var array
     */
    protected $categories_uid = array();

    /**
     * Parent category uid.
     *
     * @var int
     */
    protected $parent_category_uid = 0;

    /**
     * Parent category object.
     *
     * @var \CommerceTeam\Commerce\Domain\Model\Category
     */
    protected $parent_category = false;

    /**
     * Array with product uids.
     *
     * @var array
     */
    protected $products_uid = array();

    /**
     * Array with category objects.
     *
     * @var array Array of tx_commerce_categories
     */
    protected $categories = null;

    /**
     * Array of product objects.
     *
     * @var array
     */
    protected $products = null;

    /**
     * Teaser text.
     *
     * @var string
     */
    protected $teaser = '';

    /**
     * Teaser images.
     *
     * @var string
     */
    protected $teaserimages = '';

    /**
     * Images as array.
     *
     * @var array
     */
    protected $teaserImagesArray = array();

    /**
     * Is true when data is loaded.
     *
     * @var bool
     */
    protected $data_loaded = false;

    /**
     * The permissions array.
     *
     * @var array
     */
    public $perms_record = array();

    /**
     * The uid of the user owning the category.
     *
     * @var int
     */
    public $perms_userid = 0;

    /**
     * The uid of the group owning the category.
     *
     * @var int
     */
    public $perms_groupid = 0;

    /**
     * User permissions.
     *
     * @var int
     */
    public $perms_user = 0;

    /**
     * Group permissions.
     *
     * @var int
     */
    public $perms_group = 0;

    /**
     * Everybody permissions.
     *
     * @var int
     */
    public $perms_everybody = 0;

    /**
     * Editlock-flag.
     *
     * @var int
     */
    public $editlock = 0;

    /**
     * Flag if permissions have been loaded.
     *
     * @var bool
     */
    public $permsLoaded = false;

    /**
     * Category typoscript config.
     *
     * @var array
     */
    protected $categoryTSconfig = array();

    /**
     * Typoscript config.
     *
     * @var array
     */
    protected $tsConfig = array();

    /**
     * Field list.
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
        'l18n_parent',
    );

    /**
     * Constructor, basically calls init.
     *
     * @param int $uid Category uid
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
     * Init called by the constructor.
     *
     * @param int $uid Uid of category
     * @param int $languageUid Language_uid , default 0
     *
     * @return bool TRUE on success, FALSE if no $uid is submitted
     */
    public function init($uid, $languageUid = 0)
    {
        $uid = (int) $uid;
        $languageUid = (int) $languageUid;

        if ($uid > 0) {
            $this->uid = $uid;
            $this->lang_uid = $languageUid;
            $this->databaseConnection = parent::getDatabaseConnection();

            $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Domain/Model/Category', 'init');
            foreach ($hooks as $hook) {
                if (method_exists($hook, 'postinit')) {
                    $hook->postinit($this);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Returns recursivly the category path as text
     * path segments are glued with $separatora.
     *
     * @param string $separator Default ','
     *
     * @return string Category path segment
     */
    public function getCategoryPath($separator = ',')
    {
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
     * Returns the child categories as an list of UIDs.
     *
     * @return array Array of child category UIDs
     */
    public function getCategoryUids()
    {
        return $this->categories_uid;
    }

    /**
     * Loads the child categories in the categories array.
     *
     * @return array of categories as array of category objects
     */
    public function getChildCategories()
    {
        if (is_null($this->categories) && is_array($this->categories_uid)) {
            $this->categories = array();
            foreach ($this->categories_uid as $childCategoryUid) {
                /**
                 * Child category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $childCategory
                 */
                $childCategory = GeneralUtility::makeInstance(self::class, $childCategoryUid, $this->lang_uid);

                $this->categories[$childCategoryUid] = $childCategory;
            }
        }

        return $this->categories;
    }

    /**
     * Set child categories.
     *
     * @param array $categories Child categories
     *
     * @return void
     */
    public function setChildCategories(array $categories)
    {
        if (is_array($categories)) {
            $this->categories = $categories;
        }
    }

    /**
     * Returns a list of all child categories from this category.
     *
     * @param bool|int $depth Maximum depth for going recursive
     *
     * @return array List of category uids
     */
    public function getChildCategoriesUidlist($depth = false)
    {
        if ($depth) {
            --$depth;
        }
        $this->loadData();
        $this->getChildCategories();

        $returnList = array();
        if (!empty($this->categories)) {
            if (($depth === false) || ($depth > 0)) {
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
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
     * Returns the number of child categories.
     *
     * @return int Number of child categories
     */
    public function getChildCategoriesCount()
    {
        return is_array($this->categories_uid) ? count($this->categories_uid) : 0;
    }

    /**
     * Loads the child products in the products array.
     *
     * @return array Array of products as array of products objects
     */
    public function getChildProducts()
    {
        if ($this->products === null) {
            foreach ($this->products_uid as $productUid) {
                /**
                 * Child product.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Product $product
                 */
                $product = GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Product::class,
                    $productUid,
                    $this->lang_uid
                );

                $this->products[$productUid] = $product;
            }
        }

        return $this->products;
    }

    /**
     * Returns the category description.
     *
     * @return string Description;
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the editlock flag.
     *
     * @return int Editlock-Flag
     */
    public function getEditlock()
    {
        return $this->editlock;
    }

    /**
     * Returns an array of categoryimages.
     *
     * @return array Array of images;
     */
    public function getImages()
    {
        return $this->images_array;
    }

    /**
     * Returns the category keywords.
     *
     * @return string Keywords;
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Returns an array with the different l18n for the category.
     *
     * @return array Categories
     */
    public function getL18nCategories()
    {
        return $this->databaseConnection->getL18nCategories($this->uid);
    }

    /**
     * Returns the category navigationtitle.
     *
     * @return string Navigationtitle;
     */
    public function getNavtitle()
    {
        return $this->navtitle;
    }

    /**
     * Loads the parent category in the parent-category variable.
     *
     * @return \CommerceTeam\Commerce\Domain\Model\Category|FALSE category
     *     or FALSE if this category is already the topmost category
     */
    public function getParentCategory()
    {
        if ($this->parent_category_uid && !$this->parent_category) {
            $this->parent_category = GeneralUtility::makeInstance(
                self::class,
                $this->parent_category_uid,
                $this->lang_uid
            );
        }

        return $this->parent_category;
    }

    /**
     * Returns an array of category objects (unloaded)
     * that serve as category's parent.
     *
     * @return array of category objects
     */
    public function getParentCategories()
    {
        $parents = $this->databaseConnection->getParentCategories($this->uid);
        $parentCats = array();
        foreach ($parents as $parent) {
            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(self::class, $parent, $this->lang_uid);
            $parentCats[] = $category;
        }

        return $parentCats;
    }

    /**
     * Returns all category ID's above this uid.
     *
     * @return array List of category uids
     */
    public function getParentCategoriesUidlist()
    {
        $returnList = array();
        $this->loadData();
        if (($parentCategory = $this->getParentCategory())) {
            $returnList = $parentCategory->getParentCategoriesUidlist();
        }
        $returnList = array_merge($returnList, array($this->uid));

        return array_unique($returnList);
    }

    /**
     * Returns the Group-ID of the category.
     *
     * @return int UID of group
     */
    public function getPermsGroupId()
    {
        return $this->perms_groupid;
    }

    /**
     * Returns the User-ID of the category.
     *
     * @return int UID of user
     */
    public function getPermsUserId()
    {
        return $this->perms_userid;
    }

    /**
     * Returns the permissions for everybody.
     *
     * @return int Permissions for everybody
     */
    public function getPermsEverybody()
    {
        return $this->perms_everybody;
    }

    /**
     * Returns the Permissions for the group.
     *
     * @return int Permissions for group
     */
    public function getPermsGroup()
    {
        return $this->perms_group;
    }

    /**
     * Returns the Permissions for the user.
     *
     * @return int Permissions for user
     */
    public function getPermsUser()
    {
        return $this->perms_user;
    }

    /**
     * Returns a list of all products under this category.
     *
     * @param int $depth Depth maximum depth for going recursive
     *
     * @return array with list of product UIDs
     */
    public function getProducts($depth = -1)
    {
        $returnList = $this->getProductUids();
        if ($depth === -1) {
            $depth = PHP_INT_MAX;
        }
        if ($depth > 0) {
            $childCategoriesList = $this->getChildCategoriesUidlist($depth);
            foreach ($childCategoriesList as $oneCategoryUid) {
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance(self::class, $oneCategoryUid, $this->lang_uid);
                $category->loadData();
                $returnList = array_merge($returnList, $category->getProductUids());
            }
        }

        return array_unique($returnList);
    }

    /**
     * Returns the childproducts as unique UID list.
     *
     * @return array of child products UIDs
     */
    public function getProductUids()
    {
        return array_unique($this->products_uid);
    }

    /**
     * Returns the subtitle of the category.
     *
     * @return string Subtitle;
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Returns the category teaser.
     *
     * @return string Teaser
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * Returns the first image, if not availiabe,
     * walk recursive up, to get the image.
     *
     * @return mixed Image/FALSE, if no image found
     */
    public function getTeaserImage()
    {
        if (!empty($this->images_array[0])) {
            return $this->images_array[0];
        } else {
            if (($parentCategory = $this->getParentCategory())) {
                $parentCategory->loadData();

                return $parentCategory->getTeaserImage();
            }
        }

        return false;
    }

    /**
     * Returns an array of teaserimages.
     *
     * @return array Teaserimages;
     */
    public function getTeaserImages()
    {
        return $this->teaserImagesArray;
    }

    /**
     * Returns the title of the category.
     *
     * @return string Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns the category TSconfig array based on the currect->rootLine.
     *
     * @return array
     * @todo Make recursiv category TS merging
     */
    public function getTyposcriptConfig()
    {
        if (!is_array($this->categoryTSconfig)) {
            $tSdataArray[] = $this->tsConfig;
            $tSdataArray = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines_array($tSdataArray);
            $categoryTs = implode(LF . '[GLOBAL]' . LF, $tSdataArray);

            /**
             * Typoscript parser.
             *
             * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser $parseObj
             */
            $parseObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
            $parseObj->parse($categoryTs);
            $this->categoryTSconfig = $parseObj->setup;
        }

        return $this->categoryTSconfig;
    }

    /**
     * Returns the uid of the category.
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Loads the data.
     *
     * @param bool $translationMode Translation mode of the record,
     *      default FALSE to use the default way of translation
     *
     * @return void
     */
    public function loadData($translationMode = false)
    {
        if ($this->data_loaded == false) {
            parent::loadData($translationMode);
            $this->images_array = GeneralUtility::trimExplode(',', $this->images, true);
            $this->teaserImagesArray = GeneralUtility::trimExplode(',', $this->teaserimages, true);

            $this->categories_uid = array_unique(
                $this->databaseConnection->getChildCategories($this->uid, $this->lang_uid)
            );
            $this->parent_category_uid = $this->databaseConnection->getParentCategory($this->uid);
            $this->products_uid = array_unique(
                $this->databaseConnection->getChildProducts($this->uid, $this->lang_uid)
            );
            $this->data_loaded = true;
        }
    }

    /**
     * Loads the permissions.
     *
     * @return void
     */
    public function loadPermissions()
    {
        if (!$this->permsLoaded && $this->uid) {
            $this->permsLoaded = true;

            $this->perms_record = $this->databaseConnection->getPermissionsRecord($this->uid);

            // if the record isn't loaded, abort.
            if (empty($this->perms_record)) {
                $this->perms_record = null;

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
    public function isPermissionSet($perm)
    {
        if (!is_string($perm)) {
            return false;
        }
        $this->loadPermissions();

        return \CommerceTeam\Commerce\Utility\BackendUtility::isPermissionSet($perm, $this->perms_record);
    }

    /**
     * Returns if the actual category has subcategories.
     *
     * @return bool TRUE if the category has subcategories, FALSE if not
     */
    public function hasSubcategories()
    {
        return !empty($this->categories_uid);
    }

    /**
     * Returns if this category has products.
     *
     * @return bool TRUE, if this category has products, FALSE if not
     */
    public function hasProducts()
    {
        return !empty($this->getProductUids());
    }

    /**
     * Returns if this category has products with stock.
     *
     * @return bool TRUE, if this category has products with stock, FALSE if not
     */
    public function hasProductsWithStock()
    {
        $result = false;

        if ($this->hasProducts()) {
            $result = !empty(
                \CommerceTeam\Commerce\Utility\GeneralUtility::removeNoStockProducts($this->getProducts(), 0)
            );
        }

        return $result;
    }

    /**
     * Returns TRUE if this category has active products or
     * if sub categories have active products.
     *
     * @param bool|int $depth Maximum depth for going recursive,
     *      if not set go for maximum
     *
     * @return bool Returns TRUE, if category/subcategories hav active products
     */
    public function hasProductsInSubCategories($depth = false)
    {
        if ($this->hasProducts()) {
            return true;
        }
        if ($depth === false) {
            $depth = PHP_INT_MAX;
        }
        if ($depth > 0) {
            $childCategoriesList = $this->getChildCategoriesUidlist($depth);
            foreach ($childCategoriesList as $oneCategoryUid) {
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance(self::class, $oneCategoryUid, $this->lang_uid);
                $category->loadData();
                $returnValue = $category->hasProductsInSubCategories($depth);
                if ($returnValue == true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Carries out the move of the category to the new parent
     * Permissions are NOT checked, this MUST be done beforehand.
     *
     * @param int $uid UID of the move target
     * @param string $op  Operation of move (can be 'after' or 'into')
     *
     * @return bool TRUE if the move was successful, FALSE if not
     */
    public function move($uid, $op = 'after')
    {
        if ($op == 'into') {
            // the $uid is a future parent
            $parentUid = $uid;
        } else {
            return false;
        }
            // Update parent_category
        $set = $this->databaseConnection->updateRecord($this->uid, array('parent_category' => $parentUid));
            // Only update relations if parent_category was successfully set
        if ($set) {
            $catList = array($parentUid);
            $catList = \CommerceTeam\Commerce\Utility\BackendUtility::getUidListFromList($catList);
            $catList = \CommerceTeam\Commerce\Utility\BackendUtility::extractFieldArray($catList, 'uid_foreign', true);

            \CommerceTeam\Commerce\Utility\BackendUtility::saveRelations(
                $this->uid,
                $catList,
                'tx_commerce_categories_parent_category_mm',
                true
            );
        } else {
            return false;
        }

        return true;
    }
}

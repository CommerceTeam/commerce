<?php
namespace CommerceTeam\Commerce\ViewHelpers\Browselinks;

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

use CommerceTeam\Commerce\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Implements a Categorytree for the Link-Commerce Module
 * A tree can have n leafs, and leafs can in itself contain other leafs.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\Browselinks\CategoryTree
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class CategoryTree extends \CommerceTeam\Commerce\Tree\Browsetree
{
    /**
     * Set the Tree Name.
     *
     * @var string
     */
    protected $treeName = 'txcommerceCategoryTree';

    /**
     * Min category perms.
     *
     * @var string
     */
    protected $minCategoryPerms = 'show';

    /**
     * No click list.
     *
     * @var string
     */
    protected $noClickList = '';

    /**
     * The linked product.
     *
     * @var int
     */
    protected $openProduct = 0;

    /**
     * The linked category.
     *
     * @var int
     */
    protected $openCategory = 0;

    /**
     * Initializes the Categorytree.
     */
    public function init()
    {
        parent::init();

        // Create the category leaf
        /**
         * Category leaf.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\Category $categoryLeaf
         */
        $categoryLeaf = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\Leaf\\Category');

        // Instantiate the categorydata, -view and set
        // the permission mask (or the string rep.)
        /**
         * Category data.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\CategoryData $categorydata
         */
        $categorydata = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\Leaf\\CategoryData');
        $categorydata->setPermsMask(BackendUtility::getPermMask($this->minCategoryPerms));

        /**
         * Category view.
         *
         * @var \CommerceTeam\Commerce\ViewHelpers\Browselinks\CategoryView $categoryview
         */
        $categoryview = GeneralUtility::makeInstance(
            'CommerceTeam\\Commerce\\ViewHelpers\\Browselinks\\CategoryView'
        );
        // disable the root onclick if the perms are set to editcontent
        // - this way we cannot select the root as a parent for any content item
        $categoryview->noRootOnclick(($this->minCategoryPerms == 'editcontent'));

        // Configure the noOnclick for the leaf
        if (GeneralUtility::inList($this->noClickList, 'CommerceTeam\\Commerce\\Tree\\Leaf\\Category')) {
            $categoryview->noOnclick();
        }

        $categoryLeaf->initBasic($categoryview, $categorydata);

        $this->addLeaf($categoryLeaf);

        // Add Product - Productleaf will be added to Categoryleaf
        /**
         * Product leaf.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\Product $productleaf
         */
        $productleaf = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\Leaf\\Product');

        /**
         * Product view.
         *
         * @var \CommerceTeam\Commerce\ViewHelpers\Browselinks\ProductView $productview
         */
        $productview = GeneralUtility::makeInstance(
            'CommerceTeam\\Commerce\\ViewHelpers\\Browselinks\\ProductView'
        );

        // Configure the noOnclick for the leaf
        if (GeneralUtility::inList($this->noClickList, 'CommerceTeam\\Commerce\\Tree\\Leaf\\Product')) {
            $productview->noOnclick();
        }

        /**
         * Product data.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\ProductData $productData
         */
        $productData = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\Leaf\\ProductData');

        $productleaf->initBasic($productview, $productData);

        $categoryLeaf->addLeaf($productleaf);
    }

    /**
     * Sets the minimum Permissions needed for the Category Leaf
     * Must be called BEFORE calling init.
     *
     * @param string $perm String-Representation of the right.
     *                     Can be 'show, new, delete, editcontent, cut, move, copy, edit'
     */
    public function setMinCategoryPerms($perm)
    {
        if (!$this->isInit) {
            // store the string and let it be added once init is called
            $this->minCategoryPerms = $perm;
        }
    }

    /**
     * Sets the noclick list for the leafs.
     *
     * @param string $noClickList Comma-separated list of disallowed leafs
     */
    public function disallowClick($noClickList = '')
    {
        $this->noClickList = $noClickList;
    }

    /**
     * Sets the linked product.
     *
     * @param int $uid Linked product
     */
    public function setOpenProduct($uid)
    {
        $this->openProduct = $uid;

        // set the open product for the view
        /**
         * Product view.
         *
         * @var \CommerceTeam\Commerce\ViewHelpers\Browselinks\ProductView $productView
         */
        $productView = $this->getLeaf(0)->getChildLeaf(0)->view;
        $productView->setOpenProduct($uid);
    }

    /**
     * Sets the linked category.
     *
     * @param int $uid Linked category
     */
    public function setOpenCategory($uid)
    {
        $this->openCategory = $uid;

        // set the open category for the view
        /**
         * Category view.
         *
         * @var \CommerceTeam\Commerce\ViewHelpers\Browselinks\CategoryView $categoryView
         */
        $categoryView = $this->getLeaf(0)->view;
        $categoryView->setOpenCategory($uid);
    }

    /**
     * Returns the record of the category with the corresponding uid
     * Categories must have been loaded already - the DB is NOT queried.
     *
     * @param int $uid Category
     *
     * @return array record
     */
    public function getCategory($uid)
    {
        // test parameters
        if (!is_numeric($uid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'getCategory (categorytree) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return array();
        }

        $categoryLeaf = $this->getLeaf(0);

        // check if there is a category leaf
        if (is_null($categoryLeaf)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'getCategory (categorytree) cannot find the category leaf.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return array();
        }

            // return the record
        return $categoryLeaf->data->getChildByUid($uid);
    }

    /**
     * Will initialize the User Position
     * Saves it in the Session and gives the Position
     * UIDs to the \CommerceTeam\Commerce\Tree\Leaf\Data.
     */
    protected function initializePositionSaving()
    {
        // Get stored tree structure:
        $positions = unserialize($this->getBackendUser()->uc['browseTrees'][$this->treeName]);

        // In case the array is not set, initialize it
        if (!is_array($positions) || empty($positions) || key($positions[0][key($positions[0])]) !== 'items') {
            // reinitialize damaged array
            $positions = array();
            $this->savePosition($positions);
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'Resetting the Positions of the Browsetree. Were damaged.',
                    COMMERCE_EXTKEY,
                    2
                );
            }
        }

        $plusMinus = GeneralUtility::_GP('PM');
        // IE takes # as anchor
        if (($plusMinsPosition = strpos($plusMinus, '#')) !== false) {
            $plusMinus = substr($plusMinus, 0, $plusMinsPosition);
        }
        // 0: treeName, 1: leafIndex, 2: Mount,
        // 3: set/clear [4:,5:,.. further leafIndices], 5[+++]: Item UID
        $plusMinus = explode('_', $plusMinus);

        // PM has to be at LEAST 5 Items (up to a (theoratically) unlimited count)
        if (count($plusMinus) >= 5 && $plusMinus[0] == $this->treeName) {
            // Get the value - is always the last item
            // so far this is 'current UID|Parent UID'
            $value = explode('|', $plusMinus[count($plusMinus) - 1]);
            // now it is 'current UID'
            $value = $value[0];

            // Prepare the Array
            $c = count($plusMinus);
            // We get the Mount-Array of the corresponding leaf index
            $field = &$positions[$plusMinus[1]][$plusMinus[2]];

            // Move the field forward if necessary
            if ($c > 5) {
                $c -= 4;
                // Walk the PM
                $i = 4;

                // Leave out last value of the $PM Array since that
                // is the value and no longer a leaf Index
                while ($c > 1) {
                    // Mind that we increment $i on the fly on this line
                    $field = &$field[$plusMinus[$i++]];
                    --$c;
                }
            }

            // set
            if ($plusMinus[3]) {
                $field['items'][$value] = 1;
                $this->savePosition($positions);
                // clear
            } else {
                unset($field['items'][$value]);
                $this->savePosition($positions);
            }
        }

        // CHANGE
        // we also set the uid of the selected category
        // so we can highlight the category and its product
        if (0 != $this->openCategory) {
            if (empty($positions)) {
                // we simply add the category and all its parents,
                // starting from the mountpoint, to the positions
                $positions[0] = array();
            }

            /**
             * Category mount.
             *
             * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
             */
            $mount = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\CategoryMounts');
            $mount->init($this->getBackendUser()->user['uid']);

            // only go if the item is in the mounts
            if ($mount->isInCommerceMounts($this->openCategory)) {
                $mountUids = $mount->getMountData();

                // get the category parents so we can open them as well
                // load the category and go up the tree until we either reach a mount or a root
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance(
                    'CommerceTeam\\Commerce\\Domain\\Model\\Category',
                    $this->openCategory
                );
                $category->loadData();

                $parentCategories = $category->getParentCategories();
                $tmpParents = null;
                $i = 1000;

                // array with all the uids
                $cats = array($this->openCategory);

                while (!is_null($category = @array_pop($parentCategories))) {
                    // Prevent endless recursion
                    if ($i < 0) {
                        if (TYPO3_DLOG) {
                            GeneralUtility::devLog(
                                'initializePositionSaving (link_categorytree) has aborted
                                because $i has reached its allowed recursive maximum.',
                                COMMERCE_EXTKEY,
                                3
                            );
                        }
                        $cats = array();
                        break;
                    }

                    // true if we can find any parent category
                    // of this category in the commerce mounts
                    $cats[] = $category->getUid();

                    $tmpParents = $category->getParentCategories();

                    if (is_array($tmpParents) && !empty($tmpParents)) {
                        $parentCategories = array_merge($parentCategories, $tmpParents);
                    }
                    --$i;
                }

                foreach ($mountUids as $muid) {
                    // if the user has the root mount, add positions anyway
                    // - else if the mount is in the category array
                    if (0 == $muid || in_array($muid, $cats)) {
                        if (!is_array($positions[0][$muid]['items'])) {
                            $positions[0][$muid]['items'] = array();
                        }

                        // open the mount itself
                        $positions[0][$muid]['items'][$muid] = 1;

                        // open the parents of the open category
                        foreach ($cats as $newOpen) {
                            $positions[0][$muid]['items'][$newOpen] = 1;
                        }
                    }
                }

                // save new positions
                $this->savePosition($positions);
            }
        }
        // END OF CHANGE

        // Set the Positions for each leaf
        for ($i = 0; $i < $this->leafcount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Leaf $leaf
             */
            $leaf = $this->leafs[$i];
            $leaf->setDataPositions($positions);
        }
    }
}

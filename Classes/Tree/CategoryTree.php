<?php
namespace CommerceTeam\Commerce\Tree;

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
 * Implements a Categorytree
 * A tree can have n leafs, and leafs can in itself contain other leafs.
 *
 * Class \CommerceTeam\Commerce\Tree\CategoryTree
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class CategoryTree extends Browsetree
{
    /**
     * Set the Tree Name.
     *
     * @var string
     */
    protected $treeName = 'txcommerceCategoryTree';

    /**
     * Should the tree be only Categories? Or also Products and Articles?
     *
     * @var bool
     */
    protected $bare = true;

    /**
     * Min category permission.
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
     * Simple mode.
     *
     * @var bool
     */
    protected $simpleMode = false;

    /**
     * If tree gets rendered in navigation frame
     *
     * @var bool
     */
    protected $isNavigationFrame = false;

    /**
     * Initializes the Categorytree.
     *
     * @return void
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
        $categoryLeaf = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\Category::class);

        // Instantiate the categorydata, -view and set
        // the permission mask (or the string rep.)
        /**
         * Category data.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\CategoryData $categorydata
         */
        $categorydata = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\CategoryData::class);
        $categorydata->setPermsMask(BackendUtility::getPermMask($this->minCategoryPerms));

        /**
         * Category view.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\CategoryView $categoryview
         */
        $categoryview = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\CategoryView::class);
        // disable the root onclick if the perms are set to editcontent
        // this way we cannot select the root as a parent for any content item
        $categoryview->noRootOnclick(($this->minCategoryPerms == 'editcontent'));

        // Configure real values
        if (!$this->isNavigationFrame) {
            $categoryview->substituteRealValues();
        }

        // Configure the noOnclick for the leaf
        if (GeneralUtility::inList($this->noClickList, \CommerceTeam\Commerce\Tree\Leaf\Category::class)) {
            $categoryview->noOnclick();
        }

        $categoryLeaf->initBasic($categoryview, $categorydata);

        $this->addLeaf($categoryLeaf);

        // Add Product and Article Leafs if wanted
        // - Productleaf will be added to Categoryleaf,
        // - Articleleaf will be added to Productleaf
        if (!$this->bare) {
            /**
             * Product leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Product $productleaf
             */
            $productleaf = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\Product::class);
            /**
             * Article leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Article $articleleaf
             */
            $articleleaf = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\Article::class);

            /**
             * Product view.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\ProductView $productview
             */
            $productview = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\ProductView::class);

            // Configure the noOnclick for the leaf
            if (GeneralUtility::inList($this->noClickList, \CommerceTeam\Commerce\Tree\Leaf\Product::class)) {
                $productview->noOnclick();
            }

            /**
             * Article view.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\ArticleView $articleview
             */
            $articleview = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\ArticleView::class);

            // Configure the noOnclick for the leaf
            if (GeneralUtility::inList($this->noClickList, \CommerceTeam\Commerce\Tree\Leaf\Article::class)) {
                $articleview->noOnclick();
            }

            // Configure real values
            if (!$this->isNavigationFrame) {
                $productview->substituteRealValues();
                $articleview->substituteRealValues();
            }

            /**
             * Product data.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\ProductData $productData
             */
            $productData = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\ProductData::class);
            $productleaf->initBasic($productview, $productData);

            /**
             * Article data.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\ArticleData $articleData
             */
            $articleData = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\Leaf\ArticleData::class);
            $articleleaf->initBasic($articleview, $articleData);

            $categoryLeaf->addLeaf($productleaf);

                // Do not show articles in simple mode.
            if (!$this->simpleMode) {
                $productleaf->addLeaf($articleleaf);
            }
        }
    }

    /**
     * Sets the minimum Permissions needed for the Category Leaf
     * Must be called BEFORE calling init.
     *
     * @param string $perm String-Representation of the right.
     *     Can be 'show, new, delete, editcontent, cut, move, copy, edit'
     *
     * @return void
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
     * @param string $noClickList Comma-separated list
     *     of leafs to disallow clicks
     *
     * @return void
     */
    public function disallowClick($noClickList = '')
    {
        $this->noClickList = $noClickList;
    }

    /**
     * Sets the tree's Bare Mode - bare means only category leaf is added.
     *
     * @param bool $bare Flag
     *
     * @return void
     */
    public function setBare($bare = true)
    {
        if (!is_bool($bare)) {
            // only issue warning but transform the value to bool anyways
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('Bare-Mode of the tree was set with a non-bool flag!', 'commerce', 2);
            }
        }
        $this->bare = $bare;
    }

    /**
     * Sets if we are running in simple mode.
     *
     * @param int $simpleMode SimpleMode
     *
     * @return void
     */
    public function setSimpleMode($simpleMode = 1)
    {
        $this->simpleMode = $simpleMode;
    }

    /**
     * Setter
     *
     * @param $isNavigationFrame
     *
     * @return void
     */
    public function setNavigationFrame($isNavigationFrame)
    {
        $this->isNavigationFrame = $isNavigationFrame;
    }

    /**
     * Returns the record of the category with the corresponding uid
     * Categories must have been loaded already - the DB is NOT queried.
     *
     * @param int $uid Uid of the category
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
                    'commerce',
                    3
                );
            }

            return array();
        }

        $categoryLeaf = $this->getLeaf(0);

        // check if there is a category leaf
        if (is_null($categoryLeaf)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('getCategory (categorytree) cannot find the category leaf.', 'commerce', 3);
            }

            return array();
        }

        // return the record
        return $categoryLeaf->data->getChildByUid($uid);
    }
}

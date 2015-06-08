<?php
/**
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
 * Implements a Categorytree
 * A tree can have n leafs, and leafs can in itself contain other leafs
 *
 * Class Tx_Commerce_Tree_CategoryTree
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class Tx_Commerce_Tree_CategoryTree extends Tx_Commerce_Tree_Browsetree {
	/**
	 * Set the Tree Name
	 *
	 * @var string
	 */
	protected $treeName = 'txcommerceCategoryTree';

	/**
	 * Should the tree be only Categories? Or also Products and Articles?
	 *
	 * @var bool
	 */
	protected $bare = TRUE;

	/**
	 * @var string
	 */
	protected $minCategoryPerms = 'show';

	/**
	 * @var string
	 */
	protected $noClickList = '';

	/**
	 * @var bool
	 */
	protected $simpleMode = FALSE;

	/**
	 * @var bool
	 */
	protected $realValues = FALSE;

	/**
	 * Initializes the Categorytree
	 *
	 * @return void
	 */
	public function init() {
		// Call parent constructor
		parent::init();

		// Create the category leaf
		/** @var Tx_Commerce_Tree_Leaf_Category $categoryLeaf */
		$categoryLeaf = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_Category');

		// Instantiate the categorydata, -view and set the permission mask (or the string rep.)
		/** @var Tx_Commerce_Tree_Leaf_CategoryData $categorydata */
		$categorydata = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_CategoryData');
		$categorydata->setPermsMask(Tx_Commerce_Utility_BackendUtility::getPermMask($this->minCategoryPerms));

		/** @var Tx_Commerce_Tree_Leaf_CategoryView $categoryview */
		$categoryview = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_CategoryView');
		// disable the root onclick if the perms are set to editcontent - this way we cannot select the root as a parent for any content item
		$categoryview->noRootOnclick(($this->minCategoryPerms == 'editcontent'));

			// Configure real values
		if ($this->realValues) {
			$categoryview->substituteRealValues();
		}

			// Configure the noOnclick for the leaf
		if (GeneralUtility::inList($this->noClickList, 'Tx_Commerce_Tree_Leaf_Category')) {
			$categoryview->noOnclick();
		}

		$categoryLeaf->initBasic($categoryview, $categorydata);

		$this->addLeaf($categoryLeaf);

		// Add Product and Article Leafs if wanted
		// - Productleaf will be added to Categoryleaf,
		// - Articleleaf will be added to Productleaf
		if (!$this->bare) {
			/** @var Tx_Commerce_Tree_Leaf_Product $productleaf */
			$productleaf = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_Product');
			/** @var Tx_Commerce_Tree_Leaf_Article $articleleaf */
			$articleleaf = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_Article');

			/** @var Tx_Commerce_Tree_Leaf_ProductView $productview */
			$productview = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_ProductView');

				// Configure the noOnclick for the leaf
			if (GeneralUtility::inList($this->noClickList, 'Tx_Commerce_Tree_Leaf_Product')) {
				$productview->noOnclick();
			}

				// Configure real values
			if ($this->realValues) {
				$productview->substituteRealValues();
			}

			/** @var Tx_Commerce_Tree_Leaf_ArticleView $articleview */
			$articleview = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_ArticleView');

				// Configure the noOnclick for the leaf
			if (GeneralUtility::inList($this->noClickList, 'Tx_Commerce_Tree_Leaf_Article')) {
				$articleview->noOnclick();
			}

				// Configure real values
			if ($this->realValues) {
				$articleview->substituteRealValues();
			}

			/** @var Tx_Commerce_Tree_Leaf_ProductData $productData */
			$productData = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_ProductData');
			$productleaf->initBasic($productview, $productData);
			/** @var Tx_Commerce_Tree_Leaf_ArticleData $articleData */
			$articleData = GeneralUtility::makeInstance('Tx_Commerce_Tree_Leaf_ArticleData');
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
	 * Must be called BEFORE calling init
	 *
	 * @param string $perm String-Representation of the right. Can be 'show, new, delete, editcontent, cut, move, copy, edit'
	 * @return void
	 */
	public function setMinCategoryPerms($perm) {
		if (!$this->isInit) {
				// store the string and let it be added once init is called
			$this->minCategoryPerms = $perm;
		}
	}

	/**
	 * Sets the noclick list for the leafs
	 *
	 * @param string $noClickList comma-separated list of leafs to disallow clicks
	 * @return void
	 */
	public function disallowClick($noClickList = '') {
		$this->noClickList = $noClickList;
	}

	/**
	 * Sets the tree's Bare Mode - bare means only category leaf is added
	 *
	 * @param bool $bare Flag
	 *
	 * @return void
	 */
	public function setBare($bare = TRUE) {
		if (!is_bool($bare)) {
				// only issue warning but transform the value to bool anyways
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('Bare-Mode of the tree was set with a non-bool flag!', COMMERCE_EXTKEY, 2);
			}
		}
		$this->bare = $bare;
	}

	/**
	 * Sets if we are running in simple mode.
	 *
	 * @param int $simpleMode SimpleMode?
	 *
	 * @return void
	 */
	public function setSimpleMode($simpleMode = 1) {
		$this->simpleMode = $simpleMode;
	}

	/**
	 * Will set the real values to the views
	 * for products and articles, instead of "edit"
	 *
	 * @return void
	 */
	public function substituteRealValues() {
		$this->realValues = TRUE;
	}

	/**
	 * Returns the record of the category with the corresponding uid
	 * Categories must have been loaded already - the DB is NOT queried
	 *
	 * @param int $uid Uid of the category
	 *
	 * @return array record
	 */
	public function getCategory($uid) {
			// test parameters
		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('getCategory (categorytree) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

		$categoryLeaf = $this->getLeaf(0);

			// check if there is a category leaf
		if (is_null($categoryLeaf)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('getCategory (categorytree) cannot find the category leaf.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

			// return the record
		return $categoryLeaf->data->getChildByUid($uid);
	}
}

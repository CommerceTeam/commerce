<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Erik Frister <typo3@marketing-factory.de>
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
 * Implements a Categorytree for the Link-Commerce Module
 * A tree can have n leafs, and leafs can in itself contain other leafs
 */
class Tx_Commerce_ViewHelpers_Browselinks_CategoryTree extends Tx_Commerce_Tree_Browsetree {
	/**
	 * Set the Tree Name
	 *
	 * @var string
	 */
	protected $treeName = 'txcommerceCategoryTree';

	/**
	 * @var string
	 */
	protected $minCategoryPerms = 'show';

	/**
	 * @var string
	 */
	protected $noClickList = '';

	/**
	 * the linked product
	 *
	 * @var integer
	 */
	protected $openProduct = 0;

	/**
	 * the linked category
	 *
	 * @var integer
	 */
	protected $openCategory = 0;

	/**
	 * Initializes the Categorytree
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

			// Create the category leaf
		/** @var Tx_Commerce_Tree_Leaf_Category $categoryLeaf */
		$categoryLeaf = t3lib_div::makeInstance('Tx_Commerce_Tree_Leaf_Category');

			// Instantiate the categorydata, -view and set the permission mask (or the string rep.)
		/** @var Tx_Commerce_Tree_Leaf_CategoryData $categorydata */
		$categorydata = t3lib_div::makeInstance('Tx_Commerce_Tree_Leaf_CategoryData');
		$categorydata->setPermsMask(Tx_Commerce_Utility_BackendUtility::getPermMask($this->minCategoryPerms));

		/** @var Tx_Commerce_ViewHelpers_Browselinks_CategoryView $categoryview */
		$categoryview = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_Browselinks_CategoryView');
			// disable the root onclick if the perms are set to editcontent - this way we cannot select the root as a parent for any content item
		$categoryview->noRootOnclick(($this->minCategoryPerms == 'editcontent'));

			// Configure the noOnclick for the leaf
		if (t3lib_div::inList($this->noClickList, 'Tx_Commerce_Tree_Leaf_Category')) {
			$categoryview->noOnclick();
		}

		$categoryLeaf->initBasic($categoryview, $categorydata);

		$this->addLeaf($categoryLeaf);

			// Add Product - Productleaf will be added to Categoryleaf
		/** @var Tx_Commerce_Tree_Leaf_Product $productleaf */
		$productleaf = t3lib_div::makeInstance('Tx_Commerce_Tree_Leaf_Product');

		/** @var Tx_Commerce_ViewHelpers_Browselinks_ProductView $productview */
		$productview = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_Browselinks_ProductView');

			// Configure the noOnclick for the leaf
		if (t3lib_div::inList($this->noClickList, 'Tx_Commerce_Tree_Leaf_Product')) {
			$productview->noOnclick();
		}

		/** @var Tx_Commerce_Tree_Leaf_ProductData $productData */
		$productData = t3lib_div::makeInstance('Tx_Commerce_Tree_Leaf_ProductData');

		$productleaf->initBasic($productview, $productData);

		$categoryLeaf->addLeaf($productleaf);
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
	 * @param string $noClickList comma-separated list of leafs to disallow clicks for
	 * @return void
	 */
	public function disallowClick($noClickList = '') {
		$this->noClickList = $noClickList;
	}

	/**
	 * Sets the linked product
	 *
	 * @param integer $uid uid of the linked product
	 * @return void
	 */
	public function setOpenProduct($uid) {
		$this->openProduct = $uid;

			// set the open product for the view
		/** @var Tx_Commerce_ViewHelpers_Browselinks_ProductView $productView */
		$productView = $this->getLeaf(0)->getChildLeaf(0)->view;
		$productView->setOpenProduct($uid);
	}

	/**
	 * Sets the linked category
	 *
	 * @param integer $uid uid of the linked category
	 * @return void
	 */
	public function setOpenCategory($uid) {
		$this->openCategory = $uid;

			// set the open category for the view
		/** @var Tx_Commerce_ViewHelpers_Browselinks_CategoryView $categoryView */
		$categoryView = $this->getLeaf(0)->view;
		$categoryView->setOpenCategory($uid);
	}

	/**
	 * Returns the record of the category with the corresponding uid
	 * Categories must have been loaded already - the DB is NOT queried
	 *
	 * @param integer $uid of the category
	 * @return array record
	 */
	public function getCategory($uid) {
			// test parameters
		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getCategory (categorytree) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

		$categoryLeaf = $this->getLeaf(0);

			// check if there is a category leaf
		if (is_null($categoryLeaf)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('getCategory (categorytree) cannot find the category leaf.', COMMERCE_EXTKEY, 3);
			}
			return array();
		}

			// return the record
		return $categoryLeaf->data->getChildByUid($uid);
	}

	/**
	 * Will initialize the User Position
	 * Saves it in the Session and gives the Position UIDs to the Tx_Commerce_Tree_Leaf_Data
	 *
	 * @return void
	 */
	protected function initializePositionSaving() {
			// Get stored tree structure:
		$positions = unserialize($GLOBALS['BE_USER']->uc['browseTrees'][$this->treeName]);

			// In case the array is not set, initialize it
		if (!is_array($positions) || 0 >= count($positions) || key($positions[0][key($positions[0])]) !== 'items') {
				// reinitialize damaged array
			$positions = array();
			$this->savePosition($positions);
			if (TYPO3_DLOG) {
				t3lib_div::devLog('Resetting the Positions of the Browsetree. Were damaged.', COMMERCE_EXTKEY, 2);
			}
		}

		$PM = t3lib_div::_GP('PM');
			// IE takes # as anchor
		if (($PMpos = strpos($PM, '#')) !== FALSE) {
			$PM = substr($PM, 0, $PMpos);
		}
			// 0: treeName, 1: leafIndex, 2: Mount, 3: set/clear [4:,5:,.. further leafIndices], 5[+++]: Item UID
		$PM = explode('_', $PM);

			// PM has to be at LEAST 5 Items (up to a (theoratically) unlimited count)
		if (count($PM) >= 5 && $PM[0] == $this->treeName) {

				// Get the value - is always the last item
				// so far this is 'current UID|Parent UID'
			$value = explode('|', $PM[count($PM) - 1]);
				// now it is 'current UID'
			$value = $value[0];

				// Prepare the Array
			$c = count($PM);
				// We get the Mount-Array of the corresponding leaf index
			$field = &$positions[$PM[1]][$PM[2]];

				// Move the field forward if necessary
			if ($c > 5) {
				$c -= 4;
					// Walk the PM
				$i = 4;

					// Leave out last value of the $PM Array since that is the value and no longer a leaf Index
				while ($c > 1) {
						// Mind that we increment $i on the fly on this line
					$field = &$field[$PM[$i++]];
					$c --;
				}
			}

				// set
			if ($PM[3]) {
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

			if (0 >= count($positions)) {
					// we simply add the category and all its parents, starting from the mountpoint, to the positions
				$positions[0] = array();
			}

			/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
			$mounts = t3lib_div::makeInstance('Tx_Commerce_Tree_CategoryMounts');
			$mounts->init($GLOBALS['BE_USER']->user['uid']);

				// only go if the item is in the mounts
			if ($mounts->isInCommerceMounts($this->openCategory)) {
				$mountUids = $mounts->getMountData();

				// get the category parents so we can open them as well
				// load the category and go up the tree until we either reach a mount or a root
				/** @var Tx_Commerce_Domain_Model_Category $cat */
				$cat = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category', $this->openCategory);
				$cat->loadData();

				$tmpCats = $cat->getParentCategories();
				$tmpParents = NULL;
				$i = 1000;

					// array with all the uids
				$cats = array($this->openCategory);

				while (!is_null($cat = @array_pop($tmpCats))) {
						// Prevent endless recursion
					if ($i < 0) {
						if (TYPO3_DLOG) {
							t3lib_div::devLog('initializePositionSaving (link_categorytree) has aborted because $i has reached its allowed recursive maximum.', COMMERCE_EXTKEY, 3);
						}
						$cats = array();
						break;
					}

						// true if we can find any parent category of this category in the commerce mounts
					$cats[] = $cat->getUid();

					$tmpParents = $cat->getParentCategories();

					if (is_array($tmpParents) && 0 < count($tmpParents)) {
						$tmpCats = array_merge($tmpCats, $tmpParents);
					}
					$i --;
				}

				foreach ($mountUids as $muid) {
						// if the user has the root mount, add positions anyway - else if the mount is in the category array
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
		for ($i = 0; $i < $this->leafcount; $i ++) {
			/** @var Tx_Commerce_Tree_Leaf_Leaf $leaf */
			$leaf = $this->leafs[$i];
			$leaf->setDataPositions($positions);
		}
	}
}

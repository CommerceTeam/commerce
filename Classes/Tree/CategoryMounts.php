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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Gives functionality for Categorymounts
 */
class Tx_Commerce_Tree_CategoryMounts extends Tx_Commerce_Tree_Leaf_Mounts {
	/**
	 * Overwrite necessary variable
	 *
	 * @var string
	 */
	protected $field = 'tx_commerce_mountpoints';

	/**
	 * Returns the Mountdata, but not just as an array with ids, but with an array
	 * with arrays(id, category)
	 *
	 * @return array
	 */
	public function getMountDataLabeled() {
		$backendUser = $this->getBackendUser();

		$this->resetPointer();

			// Walk the Mounts and create the tupels of 'uid' and 'label'
		$tupels = array();

		while (FALSE !== ($id = $this->walk())) {

				// If the mountpoint is the root
			if ($id == 0) {
				$tupels[] = $backendUser->isAdmin() ?
					array($id, $this->getLL('leaf.category.root')) :
					array($id, $this->getLL('leaf.restrictedAccess'));
			} else {
					// Get the title
				/** @var Tx_Commerce_Domain_Model_Category $cat */
				$cat = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $id);
				$cat->loadData();

				$title = ($cat->isPermissionSet('show') && $this->isInCommerceMounts($cat->getUid())) ?
					$cat->getTitle() :
					$this->getLL('leaf.restrictedAccess');

				$tupels[] = array($id, $title);
			}
		}

		$this->resetPointer();

		return $tupels;
	}

	/**
	 * Returns false if the category is not in the categorymounts of the user
	 *
	 * @param integer $categoryUid
	 * @return boolean Is in mounts?
	 */
	public function isInCommerceMounts($categoryUid) {
		$backendUser = $this->getBackendUser();

		$categories = $this->getMountData();

			// is user admin? has mount 0? is parentcategory in mounts?
		if ($backendUser->isAdmin() || in_array(0, $categories) || in_array($categoryUid, $categories)) {
			return TRUE;
		}

			// if the root is not a mount, return if we got here
		if ($categoryUid == 0) {
			return FALSE;
		}

		// load the category and go up the tree until
		// we either reach a mount or we reach root
		/** @var Tx_Commerce_Domain_Model_Category $cat */
		$cat = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $categoryUid);
		$cat->loadData();

		$tmpCats = $cat->getParentCategories();
		$tmpParents = NULL;
		$i = 1000;

		while (!is_null($cat = @array_pop($tmpCats))) {
				// Prevent endless recursion
			if ($i < 0) {
				if (TYPO3_DLOG) {
					GeneralUtility::devLog(
						'isInCommerceMounts (categorymounts) has aborted because $i has reached its allowed recursive maximum.',
						COMMERCE_EXTKEY,
						3
					);
				}
				return FALSE;
			}

			// true if we can find any parent category of
			// this category in the commerce mounts
			if (in_array($cat->getUid(), $categories)) {
				return TRUE;
			}

			$tmpParents = $cat->getParentCategories();

			if (is_array($tmpParents) && 0 < count($tmpParents)) {
				$tmpCats = array_merge($tmpCats, $tmpParents);
			}
			$i--;
		}

		return FALSE;
	}


	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}

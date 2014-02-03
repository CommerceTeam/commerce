<?php
/**
 * Gives functionality for Categorymounts
 */
class tx_commerce_categorymounts extends mounts {
	/**
	 * Overwrite necessary variable
	 *
	 * @var string
	 */
	protected $field = 'tx_commerce_mountpoints';

	/**
	 * Returns the Mountdata, but not just as an array with ids, but with an array with arrays(id, category)
	 *
	 * @return array
	 */
	public function getMountDataLabeled() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$this->resetPointer();

			// Walk the Mounts and create the tupels of 'uid' and 'label'
		$tupels = array();

		while (FALSE !== ($id = $this->walk())) {

				// If the mountpoint is the root
			if ($id == 0) {
				$tupels[] = ($backendUser->isAdmin()) ? array($id, $this->getLL('leaf.category.root')) : array($id, $this->getLL('leaf.restrictedAccess'));
			} else {
					// Get the title
				$cat = t3lib_div::makeInstance('tx_commerce_category');
				$cat->init($id);
				$cat->loadData();

				$title = ($cat->isPSet('show') && $this->isInCommerceMounts($cat->getUid())) ? $cat->getTitle() : $this->getLL('leaf.restrictedAccess');

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
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$categories = $this->getMountData();

			// is user admin? has mount 0? is parentcategory in mounts?
		if ($backendUser->isAdmin() || in_array(0, $categories) || in_array($categoryUid, $categories)) {
			return TRUE;
		}

			// if the root is not a mount, return if we got here
		if ($categoryUid == 0) {
			return FALSE;
		}

			// load the category and go up the tree until we either reach a mount or we reach root
		$cat = t3lib_div::makeInstance('tx_commerce_category');
		$cat->init($categoryUid);
		$cat->loadData();

		$tmpCats = $cat->getParentCategories();
		$tmpParents = NULL;
		$i = 1000;

		while (!is_null($cat = @array_pop($tmpCats))) {
				// Prevent endless recursion
			if ($i < 0) {
				if (TYPO3_DLOG) {
					t3lib_div::devLog('isInCommerceMounts (categorymounts) has aborted because $i has reached its allowed recursive maximum.', COMMERCE_EXTKEY, 3);
				}
				return FALSE;
			}

				// true if we can find any parent category of this category in the commerce mounts
			if (in_array($cat->getUid(), $categories)) {
				return TRUE;
			}

			$tmpParents = $cat->getParentCategories();

			if (is_array($tmpParents) && 0 < count($tmpParents)) {
				$tmpCats = array_merge($tmpCats, $tmpParents);
			}
			$i --;
		}

		return FALSE;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_categorymounts.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_categorymounts.php']);
}

?>
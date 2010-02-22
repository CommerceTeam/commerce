<?php
/**
 * Created on 29.07.2008
 * Gives functionality for Categorymounts
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_category.php'); 
require_once(t3lib_extmgm::extPath('commerce').'tree/class.mounts.php'); 

class tx_commerce_categorymounts extends mounts {
	
	//Overwrite necessary variable
	protected $field = 'tx_commerce_mountpoints';
	
	/**
	 * Returns the Mountdata, but not just as an array with ids, but with an array with arrays(id, category)
	 * 
	 * @return {array} 
	 */
	function getMountDataLabeled() {
		$this->resetPointer();
		
		//Walk the Mounts and create the tupels of 'uid' and 'label'
		$tupels = array();
		
		while(false !== ($id = $this->walk())) {
		
			//If the mountpoint is the root
			if(0 == $id) {
				$tupels[] = ($GLOBALS['BE_USER']->isAdmin()) ? array($id, $this->getLL('leaf.category.root')) : array($id, $this->getLL('leaf.restrictedAccess'));
			} else {
				//Get the title 
				$cat = t3lib_div::makeInstance('tx_commerce_category');
				$cat->init($id);
				$cat->load_data();
				
				$title = ($cat->isPSet('show') && $this->isInCommerceMounts($cat->getUid())) ? $cat->getTitle() : $this->getLL('leaf.restrictedAccess');
				
				$tupels[] = array($id, $title);
			}
		}
		
		$this->resetPointer();
		
		return $tupels;
	}
	
	/**
	 * Returns false if the category is not in the categorymounts of the user
	 * @return {boolean}		Is in mounts?
	 */
	function isInCommerceMounts($categoryUid) {
		$categories = $this->getMountData();
		
		//is user admin? has mount 0? is parentcategory in mounts?
		if($GLOBALS['BE_USER']->isAdmin() || in_array(0, $categories) || in_array($categoryUid, $categories)) return true;
		
		//if the root is not a mount, return if we got here
		if(0 == $categoryUid) {
			return false;	
		}
		
		//load the category and go up the tree until we either reach a mount or we reach root
		$cat = t3lib_div::makeInstance('tx_commerce_category');
		$cat->init($categoryUid);
		$cat->load_data();

		$tmpCats 	= $cat->getParentCategories();
		$tmpParents = null;
		$i 			= 1000;
		
		while(!is_null($cat = @array_pop($tmpCats))) {
			//Prevent endless recursion
			if($i < 0) {
				if (TYPO3_DLOG) t3lib_div::devLog('isInCommerceMounts (categorymounts) has aborted because $i has reached its allowed recursive maximum.', COMMERCE_EXTkey, 3);	
				return false;
			}
			
			//true if we can find any parent category of this category in the commerce mounts
			if(in_array($cat->getUid(), $categories)) {
				return true;	
			}
			
			$tmpParents = $cat->getParentCategories();

			if(is_array($tmpParents) && 0 < count($tmpParents)) {
				$tmpCats = array_merge($tmpCats, $tmpParents);	
			}
			$i --;
		}
		return false;
	}
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_categorymounts.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_categorymounts.php']);
}
?>

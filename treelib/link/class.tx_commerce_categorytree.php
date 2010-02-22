<?php
/**
 * Implements a Categorytree for the Link-Commerce Module
 * A tree can have n leafs, and leafs can in itself contain other leafs
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */
 
//Require Base Class
require_once (t3lib_extmgm::extPath('commerce').'tree/class.browsetree.php');

//Require Mounts
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_categorymounts.php');

//Require Leafs
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_category.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_product.php');

require_once (t3lib_extmgm::extPath('commerce').'treelib/link/class.tx_commerce_leaf_categoryview.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_categorydata.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/link/class.tx_commerce_leaf_productview.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_productdata.php');

class tx_commerce_categorytree extends browsetree {
	
	//Set the Tree Name
	protected $treeName 		= 'txcommerceCategoryTree';
	protected $minCategoryPerms = 'show'; 
	protected $noClickList		= '';
	protected $openProduct		= 0;	// the linked product
	protected $openCategory  	= 0;	// the linked category
	
	/**
	 * Initializes the Categorytree
	 * 
	 * @param {boolean} $onlyCategories - Flag if Categories should be the only leafs
	 * @return {void}
	 */
	function init() {
		
		//Call parent constructor
		parent::init();
		
		//Create the category leaf
		$categoryLeaf = t3lib_div::makeInstance('tx_commerce_leaf_category');
		
		//Instantiate the categorydata, -view and set the permission mask (or the string rep.)
		$categorydata = t3lib_div::makeInstance('tx_commerce_leaf_categorydata');
		$categorydata->setPermsMask(tx_commerce_belib::getPermMask($this->minCategoryPerms));
		$categoryview = t3lib_div::makeInstance('tx_commerce_leaf_categoryview');
		$categoryview->noRootOnclick(($this->minCategoryPerms == 'editcontent')); //disable the root onclick if the perms are set to editcontent - this way we cannot select the root as a parent for any content item
		
		//Configure the noOnclick for the leaf
		if(t3lib_div::inList($this->noClickList, 'tx_commerce_leaf_category')) {
			$categoryview->noOnclick();
		}
		
		$categoryLeaf->initBasic($categoryview, $categorydata);
		
		$this->addLeaf($categoryLeaf);
		
		//Add Product - Productleaf will be added to Categoryleaf
		$productleaf = t3lib_div::makeInstance('tx_commerce_leaf_product');
		$productview = t3lib_div::makeInstance('tx_commerce_leaf_productview');
		
		//Configure the noOnclick for the leaf
		if(t3lib_div::inList($this->noClickList, 'tx_commerce_leaf_product')) {
			$productview->noOnclick();
		}
		
		$productleaf->initBasic($productview, t3lib_div::makeInstance('tx_commerce_leaf_productdata'));

		$categoryLeaf->addLeaf($productleaf);
	}
	
	/**
	 * Sets the minimum Permissions needed for the Category Leaf
	 * Must be called BEFORE calling init
	 * @return {void}
	 * @param $perm {string}	String-Representation of the right. Can be 'show, new, delete, editcontent, cut, move, copy, edit'
	 */
	function setMinCategoryPerms($perm) {
		if(!$this->isInit) {
			//store the string and let it be added once init is called
			$this->minCategoryPerms = $perm;
		}
	}
	
	/**
	 * Sets the noclick list for the leafs
	 * 
	 * @return {void}
	 * @param $noClickList {string}	comma-separated list of leafs to disallow clicks for
	 */
	public function disallowClick($noClickList = '') {
		$this->noClickList = $noClickList;
	}
	
	/**
	 * Sets the linked product
	 * 
	 * @param $uid int	uid of the linked product
	 * @return void
	 */
	public function setOpenProduct($uid) {
		$this->openProduct = $uid;
		
		// set the open product for the view
		$this->getLeaf(0)->getChildLeaf(0)->view->setOpenProduct($uid);
	}
	
	/**
	 * Sets the linked category
	 * 
	 * @param $uid int	uid of the linked category
	 * @return void
	 */
	public function setOpenCategory($uid) {
		$this->openCategory = $uid;
		
		// set the open category for the view
		$this->getLeaf(0)->view->setOpenCategory($uid);
	}
	
	/**
	 * Returns the record of the category with the corresponding uid
	 * Categories must have been loaded already - the DB is NOT queried
	 * 
	 * @return {array}		record
	 * @param $uid {int}	uid of the category
	 */
	public function getCategory($uid) {
		
		//test parameters
		if(!is_numeric($uid)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getCategory (categorytree) gets passed invalid parameters.', COMMERCE_EXTkey, 3);	
			return array();	
		}
		
		$categoryLeaf = $this->getLeaf(0);
		
		//check if there is a category leaf
		if(is_null($categoryLeaf)) {
			if (TYPO3_DLOG) t3lib_div::devLog('getCategory (categorytree) cannot find the category leaf.', COMMERCE_EXTkey, 3);	
			return array();	
		}
		
		//return the record
		return $categoryLeaf->data->getChildByUid($uid);
	}
	
	/**
	 * Will initialize the User Position
	 * Saves it in the Session and gives the Position UIDs to the LeafData
	 * 
	 * @return {void} 
	 */
	protected function initializePositionSaving() {
		// Get stored tree structure:
		$positions = unserialize($GLOBALS['BE_USER']->uc['browseTrees'][$this->treeName]);
		
		//In case the array is not set, initialize it
		if(!is_array($positions) || 0 >= count($positions) || key($positions[0][key($positions[0])]) !== 'items') {
			$positions = array(); // reinitialize damaged array
			$this->savePosition($positions);
			if (TYPO3_DLOG) t3lib_div::devLog('Resetting the Positions of the Browsetree. Were damaged.', COMMERCE_EXTkey, 2);
		}
		
		$PM = t3lib_div::_GP('PM');
		if(($PMpos = strpos($PM, '#')) !== false) { $PM = substr($PM, 0, $PMpos); } //IE takes # as anchor
		$PM = explode('_',$PM);	//0: treeName, 1: leafIndex, 2: Mount, 3: set/clear [4:,5:,.. further leafIndices], 5[+++]: Item UID
		
		//PM has to be at LEAST 5 Items (up to a (theoratically) unlimited count)
		if (count($PM) >= 5 && $PM[0] == $this->treeName)	{
				
				//Get the value - is always the last item
				$value = explode('|', $PM[count($PM) - 1]); //so far this is 'current UID|Parent UID'
				$value = $value[0];							//now it is 'current UID'
				
				//Prepare the Array
				$c 		= count($PM);
				$field  = &$positions[$PM[1]][$PM[2]]; //We get the Mount-Array of the corresponding leaf index
				
				//Move the field forward if necessary
				if($c > 5) {
					$c -= 4;

					//Walk the PM
					$i = 4;

					//Leave out last value of the $PM Array since that is the value and no longer a leaf Index
					while($c > 1) {
						//Mind that we increment $i on the fly on this line
						$field = &$field[$PM[$i++]];
						$c --;
					}
				}
				
				if ($PM[3])	{	// set
					$field['items'][$value]=1;
					$this->savePosition($positions);
				} else {	// clear
					unset($field['items'][$value]);
					$this->savePosition($positions);
				}
		}
	
		// CHANGE
		// we also set the uid of the selected category 
		// so we can highlight the category and its product
		if(0 != $this->openCategory) {
			
			if(0 >= count($positions)) {
				// we simply add the category and all its parents, starting from the mountpoint, to the positions
				$positions[0] = array();
			}
			
			$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
			$mounts->init($GLOBALS['BE_USER']->user['uid']);
			
			// only go if the item is in the mounts
			if($mounts->isInCommerceMounts($this->openCategory)) {
				$mountUids = $mounts->getMountData();
			
				// get the category parents so we can open them as well
				//load the category and go up the tree until we either reach a mount or we reach root
				$cat = t3lib_div::makeInstance('tx_commerce_category');
				$cat->init($this->openCategory);
				$cat->load_data();
		
				$tmpCats 	= $cat->getParentCategories();
				$tmpParents = null;
				$i 			= 1000;
				
				// array with all the uids
				$cats = array($this->openCategory);
				
				while(!is_null($cat = @array_pop($tmpCats))) {
					//Prevent endless recursion
					if($i < 0) {
						if (TYPO3_DLOG) t3lib_div::devLog('initializePositionSaving (link_categorytree) has aborted because $i has reached its allowed recursive maximum.', COMMERCE_EXTkey, 3);	
						$cats = array();
						break;
					}
					
					//true if we can find any parent category of this category in the commerce mounts
					$cats[] = $cat->getUid();
					
					$tmpParents = $cat->getParentCategories();
		
					if(is_array($tmpParents) && 0 < count($tmpParents)) {
						$tmpCats = array_merge($tmpCats, $tmpParents);	
					}
					$i --;
				}
			
				foreach($mountUids as $muid) {
					// if the user has the root mount, add positions anyway - else if the mount is in the category array
					if(0 == $muid || in_array($muid, $cats)) {
						if(!is_array($positions[0][$muid]['items'])) {	
							$positions[0][$muid]['items'] = array();
						}
						
						// open the mount itself
						$positions[0][$muid]['items'][$muid] = 1;
						
						// open the parents of the open category
						foreach($cats as $newOpen) {
							$positions[0][$muid]['items'][$newOpen] = 1;
						}
					}
				}
				
				// save new positions
				$this->savePosition($positions);
			}
		}
		// END OF CHANGE
	
		//Set the Positions for each leaf
		for($i = 0; $i < $this->leafcount; $i ++) {
			$this->leafs[$i]->setDataPositions($positions);
		}
	}
}

//XClass Statements
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/link/class.tx_commerce_categorytree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/commerce/treelib/link/class.tx_commerce_categorytree.php']);
}
?>

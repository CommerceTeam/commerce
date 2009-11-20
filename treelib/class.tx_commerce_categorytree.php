<?php
/**
 * Implements a Categorytree
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
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_article.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_product.php');

require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_categoryview.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_categorydata.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_productview.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_productdata.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_articleview.php');
require_once (t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_leaf_articledata.php');

// Require ext update script.
require_once(t3lib_extmgm::extPath('commerce').'class.ext_update.php'); 

class tx_commerce_categorytree extends browsetree {
	
	//Set the Tree Name
	protected $treeName 		= 'txcommerceCategoryTree';
	protected $bare				= true;							//Should the tree be only Categories? Or also Products and Articles?
	protected $minCategoryPerms = 'show'; 
	protected $noClickList		= '';
	protected $simpleMode  		= false;
	protected $realValues 		= false;
	
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
		
		//Add Product and Article Leafs if wanted - Productleaf will be added to Categoryleaf, and Articleleaf will be added to Productleaf
		if(!$this->bare) {
			
			$productleaf = t3lib_div::makeInstance('tx_commerce_leaf_product');
			$articleleaf = t3lib_div::makeInstance('tx_commerce_leaf_article');

			
			$productview = t3lib_div::makeInstance('tx_commerce_leaf_productview');
			
			// Configure the noOnclick for the leaf
			if(t3lib_div::inList($this->noClickList, 'tx_commerce_leaf_product')) {
				$productview->noOnclick();
			}
			
			// Configure real values
			if ($this->realValues) {
				$productview->substituteRealValues();
			}
			
			$articleview = t3lib_div::makeInstance('tx_commerce_leaf_articleview');
			
			// Configure the noOnclick for the leaf
			if(t3lib_div::inList($this->noClickList, 'tx_commerce_leaf_article')) {
				$articleview->noOnclick();
			}
			
			// Configure real values
			if ($this->realValues) {
				$articleview->substituteRealValues();
			}
			
			$productleaf->initBasic($productview, t3lib_div::makeInstance('tx_commerce_leaf_productdata'));
			$articleleaf->initBasic($articleview, t3lib_div::makeInstance('tx_commerce_leaf_articledata'));

			$categoryLeaf->addLeaf($productleaf);
			
			// Do not show articles in simple mode.
			if(!$this->simpleMode) {
				$productleaf->addLeaf($articleleaf);
			}
		}
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
	 * Sets the tree's Bare Mode - bare means only category leaf is added
	 * @return {void}
	 * @param $bare {boolean}[optional]	Flag
	 */
	public function setBare($bare = true) {
		if(!is_bool($bare)) {
			//only issue warning but transform the value to bool anyways
			if (TYPO3_DLOG) t3lib_div::devLog('Bare-Mode of the tree was set with a non-boolean flag!', COMMERCE_EXTkey, 2);	
		}
		$this->bare = $bare;
	}
	
	/**
	 * Sets if we are running in simple mode.
	 * 
	 * @param int $sm	SimpleMode?
	 * @return void
	 */
	public function setSimpleMode($sm = 1) {
		$this->simpleMode = $sm;
	}
	
	/**
	 * Will set the real values to the views
	 * for products and articles, instead of "edit"
	 * 
	 * @return void
	 */
	public function substituteRealValues() {
		$this->realValues = true;
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
}

//XClass Statements
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_categorytree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_categorytree.php']);
}
?>

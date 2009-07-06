<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2008 Ingo Schmitt <is@marketing-factory.de>
*  All   rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Libary for Frontend-Rendering of categores . This class 
 * should be used for all Fronten-Rendering, no Database calls 
 * to the commerce tables should be made directly
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic Database calls are made from a separate Database Class
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_category
 * @see tx_commerce_element_alib
 * 
 * Basic class for handeling categories
 * 
 * $Id$
 */
 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_category.php');
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_element_alib.php'); 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_belib.php'); 

 /**
 * Main script class for the handling of categories. Categories contains
 * categoreis (Reverse datat structure) and products
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
  class tx_commerce_category extends tx_commerce_element_alib{
  
  	/**
  	 * @var title
  	 * @acces private
  	 */
  	var  $title=''; 				
  	
  	/**
  	 * @var subtitle
  	 * @acces private
  	 */
  	var  $subtitle='';			
  	/**
  	 * @var description
  	 * @acces private
  	 */
  	var  $description='';		
  	
   	var  $images=array(); 		// Images for the product( private ) 
   	
   	var $images_array = array(); 		// Images for the product  	(private)
   	
   	var  $navtitle='';			// Title for navigation an Menu Rendering( private )
   	
   	var  $keywords='';			// keywords for meta informations ( private )
  	
  	var  $categories_uid=array();	// array of tx_commerce_category_uid ( private )
  	
  	var  $parent_category_uid='';			// UID of parent categorie ( private )
  	
  	var  $parent_category='';			// parent category object ( private )
  	
  	var  $products_uid=array();		// array of tx_commerce_product_uid ( private )
  	
  	var  $categories=array();	// array of tx_commerce_category ( private )
  	
  	var  $products=array();		// array of tx_commerce_product ( private )
  	
  	var $teaser = '';
  	var $teaserImages='';				// images database field (private) 	
  	var $teaserImagesArray = array(); 		// Images for the categorie  	(private)
  	
  	/**
  	 * @var is truee when data is loaded
  	 * @acces orivate
  	 */
  	var $data_loaded=false;
  	
	var $perms_record;	//the perms array with the fields in it
	
	//explicit fields
  	var $perms_userid;
	var $perms_groupid;
	var $perms_user;
	var $perms_group;
	var $perms_everybody;
	var $editlock;
	
	var $permsLoaded;	//flag if perms have been loaded
	
	/**
	 * Constructor, basically calls init 
	 * @param integer uid of category
	 * @param integer integer language_uid , default 0
	 */
	function tx_commerce_category() {
		
		if ((func_num_args()>0) && (func_num_args()<=2)){
			$uid = func_get_arg(0); 
			if (func_num_args()==2){
				$lang_uid=func_get_arg(1);
			}else{
				$lang_uid=0;
			}
			return $this->init($uid,$lang_uid);
		}
	}
	
	/**
	 * Constructor
	 * @param integer uid of category
	 * @param integer integer language_uid , default 0
	 */
	function init($uid,$lang_uid=0) {
		 $uid = intval($uid);
	     $lang_uid = intval($lang_uid);
		 $this->fieldlist=array('uid','title','subtitle','description','teaser','teaserimages','navtitle','keywords','images','ts_config','l18n_parent');
		 $this->database_class='tx_commerce_db_category';
		
		 if ($uid>0 )	 {
				$this->uid=$uid;
				$this->lang_uid=$lang_uid;
				
				$this->conn_db=new $this->database_class;
				$hookObjectsArr = array();
				if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_category.php']['postinit'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_category.php']['postinit'] as $classRef) {
							$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
					}
				}
				foreach($hookObjectsArr as $hookObj)	{
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
				return true;
		 } else {
			 	return false;	
		 }
		
	}
  	
  	/*
  	 * ******************************************************************************************
  	 * Public Methods
  	 * for
  	 * data
  	 * retrival
  	 * ******************************************************************************************
  	 */
  	
  	/**
  	 * Returns the category title
  	 * @return string;
  	 * @acces public
  	 * @depricated
  	 * @see getTitle()
  	 */
  	
  	function get_title() 	{
  		return $this->title;	
  	}
	
	/**
	 * Loads the perms
	 * @return {void}
	 */
	function load_perms() {
		if(!$this->permsLoaded) {
			$this->permsLoaded = true;
			
			$this->perms_record 	= 	$this->conn_db->getPermissionsRecord($this->uid);
		
			//if the record isnt loaded, abort.
			if(0 >= count($this->perms_record)) {
				$this->perms_record = null;
				return;	
			}
			
			$this->perms_userid 	= $this->perms_record['perms_userid'];
			$this->perms_groupid 	= $this->perms_record['perms_groupid'];
			$this->perms_user 		= $this->perms_record['perms_userid'];
			$this->perms_group 		= $this->perms_record['perms_group'];
			$this->perms_everybody 	= $this->perms_record['perms_everybody'];
			$this->editlock 		= $this->perms_record['editlock'];
		}
	}
	
	/**
	 * Returns whether the permission is set and allowed for the current user
	 * @return {boolean}
	 */
	function isPSet($perm) {
		if(!is_string($perm)) return false;
		$this->load_perms();
		
		return tx_commerce_belib::isPSet($perm, $this->perms_record);
	}
	
	/**
	 * Returns the UID of the Category
	 * @return {int}
	 */
	function getUid() {
		return $this->uid;	
	}
  	
  	/**
  	 * Returns the category title
  	 * @return string;
  	 * @acces public
  	 * 
  	 */
  	
  	function getTitle() 	{
  		return $this->title;	
  	}
  	
  	/**
  	 * Returns the category subtitle
  	 * @return string;
  	 * @acces public
  	 */
    function get_subtitle()  	{
  		return $this->subtitle;	
  	}
  	
    /**
  	 * Returns the category teaser
  	 * @return string;
  	 * @access public
  	 */
  	function get_teaser()  	{
  		return $this->teaser;
  	}  	
  	
    /**
  	 * Returns an Array of Images
  	 * @return array;
  	 * @access public
  	 */
  	
  	function getTeaserImages()  	{
  		return $this->teaserImagesArray;
  	}  	
  	
  	/**
  	 * Returns the category description
  	 * @return string;
  	 * @acces public
  	 */
    function get_description()  	{
  		return $this->description;
  	}
  	
  	/**
  	 * Returns the category navtitle
  	 * @return string;
  	 * @acces public
  	 */
   function get_navtitle()  	{
  		return $this->navtitle;
  	}
  	
  	/**
  	 * Returns the category keywords
  	 * @return string;
  	 * @acces public
  	 */
     function get_keywords() 	{
  		return $this->keywords;
  	}
  	
  	 	
  	
  	/**
  	 * Returns Subcategories from the existiong categories
  	 * @return array 
  	 * @acces public
  	 */
  	
    function get_subcategories()  	{
		if (count($this->categories) == 0){
			return $this->get_child_categories() ;
		}else{
  			return $this->categories;
		}
  	} 
  	
  	/**
  	 * Returns subproducts from the existing categories
  	 * @return array 
  	 * @acces public
  	 */
     function get_subproducts() 	 {
	 	if (count($this->products) == 0) {
			return $this->get_child_products();
		}else{
  			return $this->products;
  		}
  	 }
  	/**
  	 * Returns an Array of Images
  	 * @return array;
  	 * @access public
  	 */
  	
  	function getImages()  	{
  		return $this->images_array;
  	}
	
	/**
	 * Returns the Group-ID of the Category
	 * @return {int}
	 */
	function getPermsGroupId() {
		return $this->perms_groupid;
	}
	
	/**
	 * Returns the User-ID
	 * @return {int}
	 */
	function getPermsUserId() {
		return $this->perms_userid;
	}
	
	/**
	 * Returns the Permissions for everybody
	 * @return {int}
	 */
	function getPermsEverybody() {
		return $this->perms_everybody;
	}
	
	/**
	 * Returns the Permissions for the group
	 * @return {int}
	 */
	function getPermsGroup(){
		return $this->perms_group;
	}
	
	/**
	 * Returns the Permissions for the user
	 * @return {int}
	 */
	function getPermsUser() {
		return $this->perms_user;
	}
	
	/**
	 * Returns the editlock flag
	 * @return {int}
	 */
	function getEditlock() {
		return $this->editlock;
	}
	
	
  	/**
  	 * true if the actual categorie has subcategories
  	 * @return boolean 
  	 * @acces public
  	 */
  	
    function has_subcategories()  	{
  		if (count($this->categories_uid) > 0)
  		{
  			return true;	
  		}	else	{
  			return false;	
  		}
  	}
  	
  	/**
  	 * true if the actual categorie has subcategories
  	 * @return boolean 
  	 * @acces public
  	 */
  	
  	 function has_subproducts()  	 {
  		if (count($this->products_uid) > 0) 		{
  			return true;	
  		}	else	{
  			return false;	
  		}
  	 }
  	
  	/**
  	 * Load the data
  	 * @param $translationMode Transaltio Mode of the record, default false to use the default way of translation
  	 */
  	function load_data($translationMode = false)  	{
  		if ($this->data_loaded==false)		{
	  		parent::load_data($translationMode);	
	  		$this->images_array=t3lib_div::trimExplode(',',$this->images);
	  		$this->categories_uid=$this->conn_db->get_child_categories($this->uid);
			$this->parent_category_uid=intval($this->conn_db->get_parent_category($this->uid));
	  		$this->products_uid=$this->conn_db->get_child_products($this->uid, $this->lang_uid);
	  		$this->teaserImagesArray=t3lib_div::trimExplode(',',$this->teaserImages);
	  		$this->data_loaded=true;
  		}
  	}
  	
  	/**
  	 * Returns an array with the different l18n for the category
  	 * 
  	 * @return {array}	Categories
  	 */
  	function get_l18n_categories() {
  		$uid_lang = $this->conn_db->get_l18n_categories($this->uid);
  		
		return $uid_lang;
  	} 
  	
  	/**
  	 * Loads the child categories in the categories array
  	 * @return array of categories as array of category objects
  	 */
  	
  	function get_child_categories()  	{
  		
		foreach ($this->categories_uid as $load_uid){
			$this->categories[$load_uid]=t3lib_div::makeInstance('tx_commerce_category');
			$this->categories[$load_uid]->init($load_uid,$this->lang_uid);	
			
		}  		
  		return $this->categories;
  	}
  	
  	/**
  	 * returns the number of child categories
  	 * @author	Ingo Schmitt <is@marketing-factory.de>
  	 * @return	integer	Number of child categories
  	 */
  	
  	function numOfChildCategories() {
  		if (is_array($this->categories_uid)) {
  			return count($this->categories_uid );
  		} 
		return 0;
  		
  	}
  	
  	/**
  	 * Loads the child products in the products array
  	 * @return array of products asarray of products objects
  	 */
  	 
  	 function get_child_products()  	 {
  	 	foreach ($this->products_uid as $load_uid)	 	{
  	 		$this->products[$load_uid] = t3lib_div::makeInstance('tx_commerce_product');
  	 		$this->products[$load_uid]->init($load_uid,$this->lang_uid);
  	 	}	
  	 	return $this->products;
  	 	
  	 }
  	 
  	 /**
  	  * @since 2005 11 03
  	  * @return array  of child products as uid list unique array
  	  */
  	 function getProductUids()  	 {
  	 	return array_unique($this->products_uid);
  	 }
	 
	  /**
  	  * @since 2005 11 03
  	  * @return array of child category as uid list array
  	  */
  	 function getCategoryUids()  	 {
  	 	return $this->categories_uid;
  	 }
  	 
  	 /**
  	 * Loads the parent categoriy in the parenT-category variable
  	 * @return cateory object or false if this category is already the topmost category
  	 */
  	
  	function get_parent_category()  	{
		if ($this->parent_category_uid>0)	{
		
			$this->parent_category= t3lib_div::makeInstance('tx_commerce_category');
			$this->parent_category->init($this->parent_category_uid,$this->lang_uid);	
			return 	$this->parent_category;
		} else {
			return false;	
		}
		
  	}
	
	/**
	 * Returns an array with category objects (unloaded) that serve as the category's parent
	 * @return {array}
	 */
	function getParentCategories() {
		$parents = $this->conn_db->get_parent_categories($this->uid);
		$parentCats = array();
		
		for($i = 0, $l = count($parents); $i < $l; $i ++) {
			$cat = t3lib_div::makeInstance('tx_commerce_category');
			$cat->init($parents[$i]);
			
			$parentCats[] = $cat;
		}
		
		return $parentCats;
	}
	
	/**
	 * Returns false if the current category is not in the categorymounts of the user
	 * @return {boolean}		Is in mounts?
	 */
	/*function isInCommerceMounts() {
		 
		 require_once(t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_categorymounts.php'); 
		
		//get all mounts
		$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
		$mounts->init($GLOBALS['BE_USER']->user['uid']);
		
		$categories = $mounts->getMountData();
		
		//is user admin? has mount 0? is parentcategory in mounts?
		if($GLOBALS['BE_USER']->isAdmin() || in_array(0, $categories) || in_array($this->uid, $categories)) return true;
		
		//load the category and go up the tree until we either reach a mount or we reach root
		$tmpCats 	= $this->getParentCategories();
		$tmpParents = null;
		$i 			= 1000;
		
		while(!is_null($cat = @array_pop($tmpCats))) {
			//Prevent endless recursion
			if($i < 0) {
				if (TYPO3_DLOG) t3lib_div::devLog('isInCommerceMounts (category) has aborted because $i has reached its allowed recursive maximum.', COMMERCE_EXTkey, 3);	
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
	}*/
	
	/**
	 * Carries out the move of the category to the new parent
	 * Permissions are NOT checked, this MUST be done beforehand
	 * @return {bool}			Success
	 * @param $uid {int}		uid of the move target
	 * @param $op {string}		Operation of move (can be 'after' or 'into'
	 */
	function move($uid, $op = 'after') {
		
		if('into' == $op) {
			//the $uid is a future parent
			$parent_uid = $uid;
		} else {
			return false;	
		}
		
		//update parent_category
		$set = $this->conn_db->updateRecord($this->uid, array('parent_category' => $parent_uid));
		
		//only update relations if parent_category was successfully set
		if($set) {
			$catList = array($parent_uid);
			$catList = tx_commerce_belib::getUidListFromList($catList);
			$catList = tx_commerce_belib::extractFieldArray($catList, 'uid_foreign', true);
	
			tx_commerce_belib::saveRelations($this->uid, $catList, 'tx_commerce_categories_parent_category_mm', true);
		} else return false;
		return true;
	}
  	
  	
  	/**
  	 * returns recursvly the category path as text
  	 * path segments are glued with $separator
  	 * @param	[optional] string	$separator default '-'
  	 * @return category path segment
  	 * @since 2005 November 18th
  	 */
  	
  	function get_category_path($separator=',') {
  	
  		if ($this->parent_category_uid>0)  		{
  			$parent=$this->get_parent_category();
  			$parent->load_data();
  			$result=$parent->get_category_path($separator).$separator.$this->get_title();;
  			
  		} else {
  			$result=$this->get_title();
  		}
  		return $result;
  	}
  	
  	
  	  	
  	/**
  	 * 
  	 * Returns a list of all child categories from thos categorie
  	 * @param deepth maximum deepth for going recursive 
  	 * @return array list of category uids
  	 */
  	
  	function get_rec_child_categories_uidlist($depth=false){
  		
  		$return_list=array();
  		if ($depth)  		{
  			$depth--;	
   		}
  		$this->load_data();
  		$this->get_child_categories();
  	
  		if (count($this->categories)>0)		{
  			if (($depth===false) || ($depth>0))		{
	  			foreach ($this->categories as $c_uid => $one_category)		{
	  				$return_list=array_merge($return_list, $one_category->get_rec_child_categories_uidlist($depth));		
	  			}
  			}
  			$return_list=array_merge($return_list,$this->categories_uid);
  		}
  		return $return_list;
  		 		
  	}
  	
  	/**
  	 * @since 2005 11 02
  	 * Returns a list of all Products unter this categores
  	 * @since 12 November 2005 
	 * added array_unique to result set
  	 * @param deepth maximum deepth for going recursive 
  	 * @return array list of product uids
  	 * @since 18th November 2005
  	 * Check if deepth is gerater than 0
  	 * 
  	 */
  	 function getAllProducts($depth=false){
  	 	$return_list=$this->getProductUids();
  	 	if ($depth > 0) 	{
	  	 	$childCategoriesList=$this->get_rec_child_categories_uidlist($depth);
	  	 	
	  	 	foreach ($childCategoriesList as $oneCategoryUid)	{
	  	 		$category = t3lib_div::makeInstance('tx_commerce_category');
	  	 		$category->init($oneCategoryUid,$this->lang_uid);
	  	 		$category->load_data();
	  	 		
	  	 		$return_list=array_merge($return_list,$category->getProductUids());
	  	 		
	  	 	}
  	 	}
  	  	return array_unique($return_list);
  	 	
  	 }
  	
  		/**
  	 * @TODO
  	 */
  	
	 /**
	  * gets all catogores ID's above this uid
	  * 
	  * @since 12 November 2005 
	  * added array_unique to result set
	  * @return array list of category uids
	  * 
	  * 
	  */  	
  	
  	 function get_categorie_rootline_uidlist()	 {
  	 		$return_list=array();
  	 		
  	 		$this->load_data();
  	 		
  	 		if($parentCategory=$this->get_parent_category()) 	 		{
  	 			$return_list=$parentCategory->	get_categorie_rootline_uidlist();
  	 		}
  	 		$return_list=array_merge($return_list,array($this->uid));
  	 		
  	 		return array_unique($return_list);
  	 	
  	 }
  	 
  	 /**
  	  * getTeaserImage
  	  * returns the first image, if not availiabe, walk recusrive up, to get the image
  	  * If no IMage found, return false
  	  * @return 	string 	image
  	  */
  	  function getTeaserImage() 	  {
  	  		if (!empty($this->images_array[0]))  	  		{
  	  			return 	$this->images_array[0];
  	  		}	else  		{
  	  			if($parentCategory=$this->get_parent_category())			{
  	 				$parentCategory->load_Data();
  	 				return $parentCategory->getTeaserImage();
  	 			}	else	{
  	 				return false;	
  	 			}
  	  		}
  	  	
  	  }


        /**
         * Returns the category TSconfig array based on the currect->rootLine
         *
         * @return      array
         */
        function getCategoryTSconfig()     {
                if (!is_array($this->categoryTSconfig))    {
                       

			# @ToDo make recursiv category TS merging
			# reset($this->rootLine);
                        # $TSdataArray = array();
                        # $TSdataArray[] = $this->TYPO3_CONF_VARS['BE']['defaultPageTSconfig'];   // Setting default configurat
                        # while(list($k,$v)=each($this->rootLine))        {
                        #         $TSdataArray[]=$v['TSconfig'];
                        # }
                        #        // Parsing the user TS (or getting from cache)                      


			$TSdataArray[] = $this->ts_config;
			$TSdataArray = t3lib_TSparser::checkIncludeLines_array($TSdataArray);
			$categoryTS = implode(chr(10).'[GLOBAL]'.chr(10),$TSdataArray);

                        $parseObj = t3lib_div::makeInstance('t3lib_TSparser');
                        $parseObj->parse($categoryTS);
                        $this->categoryTSconfig = $parseObj->setup;
                }
                return $this->categoryTSconfig;
        }
        
        
        /**
      * Deprecated Methods
      * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      */
      
     /**
  	 * Returns an Array of Images
  	 * @return array;
  	 * @access public
  	 * @depricated
  	 */
  	
  	function get_images()  	{
  		return $this->getImages();
  	}
  	
  }

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_category.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_category.php']);
}
 ?>

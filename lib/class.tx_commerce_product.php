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
 * Libary for Frontend-Rendering of products. This class 
 * should be used for all Fronten-Rendering, no Database calls 
 * to the commerce tables should be made directly
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic database calls are made from a separate database Class
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_product
 * @see tx_commere_element_alib
 * @see tx_commerce_db_product
 * 
 *  Basic class for handling products
 * 
 * $Id$
 */
 /**
  * @todo
  * 
  */
  
 /**
 * Main script class for the handling of products. Products
 * contain articles
 *
 * @author		Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_product.php');
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_element_alib.php'); 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_article.php'); 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_attribute.php'); 
 
 class tx_commerce_product extends tx_commerce_element_alib{
  	
  	/*
  	 * Data Variables
  	 */
  	var $title = ''; 				// Title of the product e.g.productname  	(private)
  	var $pid = 0;
  	var $subtitle = '';			// Subtitle of the product  (private)	
  	var $description = '';		//  product description  (private)	
  	var $teaser = '';
  	var $images='';				// images database field (private) 	
  	var $images_array = array(); 		// Images for the product  	(private)
  	var $teaserImages='';				// images database field (private) 	
  	var $teaserImagesArray = array(); 		// Images for the product  	(private)
  	var $articles = array();		// array of tx_commcerc_article  (private)	
  	var $articles_uids=array();     // Array of tx_commerce_article_uid (private)
  	
  	var $attributes = array();
  	var $attributes_uids=array();
  	
  	var $relatedProducts = array();
	var $relatedProduct_uids = array();
  	var $relatedProducts_loaded=false;
  	
	//Versioning
	var $t3ver_oid		= 0;
	var $t3ver_id  		= 0;
	var $t3ver_label 	= '';
	var $t3ver_wsid 	= 0;
	var $t3ver_state	= 0;
	var $t3ver_stage	= 0;
	var $t3ver_tstamp 	= 0;
	
  	/**
  	 * @var articles_loaded
  	 * is true when artciles are loaded, so that load articles can simply return with the values from the object
  	 * @acces private
  	 */
  	var $articles_loaded=false;
  	
  	
  
	
	
	/**
	 * 
	 * Constructor
	 */
	 
	/**
	 * basically calls init
	 * @param integer uid of product
	 * @param integer integer language_uid , default 0
	 */
	function tx_commerce_product() {
		if ((func_num_args()>0) && (func_num_args()<=2)){
			$uid = func_get_arg(0); 
			if (func_num_args()==2){
				$lang_uid=func_get_arg(1);
			}else
			{
				$lang_uid=0;
			}
			return $this->init($uid,$lang_uid);
		}
	}
	
	/**
	 * 
	 * Constructor
	 */
	 
	/**
	 * @param integer uid of product
	 * @param integer integer language_uid , default 0
	 */
	function init($uid,$lang_uid=0) {
		
		 $uid = intval($uid);
	     $lang_uid = intval($lang_uid);
		 $this->database_class='tx_commerce_db_product';
		 $this->fieldlist=array('uid','title','pid','subtitle','description','teaser','images','teaserimages','relatedpage','l18n_parent','manufacturer_uid', 't3ver_oid', 't3ver_id', 't3ver_label', 't3ver_wsid', 't3ver_stage', 't3ver_state', 't3ver_tstamp');
		
		 if  ($uid > 0) {
		 	
			$this->uid=$uid;
			$this->lang_uid=$lang_uid;
	  		$this->conn_db=new $this->database_class;
	  		$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['postinit'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['postinit'] as $classRef) {
							$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
					}
			}
			foreach($hookObjectsArr as $hookObj)	{
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
				}
			}
	  		return true;
		 }
		 else
		 {
		 	return false;	
		 }
		
	}
	
	
	
  	/*
  	 * ******************************************************************************************
  	 * Public Methods
  	 * ******************************************************************************************
  	 */
  	
  	/**
  	 * Returns the product title
  	 * @return string;
  	 * @access public
  	 */
  	
  	 function get_title() 	{
  		return $this->title;	
  	}

	/**
	 * Returns the product pid
	 * @return {int}
	 * @access public
	 */
	function get_pid() {
		return $this->pid;
	}
	
	/**
	 * Returns the uid of the live version of this 
	 * @return 
	 */
	function get_t3ver_oid() {
		return $this->t3ver_oid;
	}

  	/**
  	 * Returns the related page for the product
  	 * @return int;
  	 * @access public
  	 */
  	
  	 function getRelatedPage() 	{
  		return $this->relatedpage;
  	}
  	
  	/**
  	 * Returns the product subtitle
  	 * @return string;
  	 * @access public
  	 */
  	function get_subtitle() 	{
  		return $this->subtitle;	
  	}
  	
  	/**
  	 * Returns the product description
  	 * @return string;
  	 * @access public
  	 */
	   function get_description() 	{
  		return $this->description;
  	}
  	
  	/**
  	 * Returns the product teaser
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
  	 * @depricated
  	 */
  	function getImages() 	{
  		return $this->images_array;
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
  	 * Returns the list of articles
  	 * @return array of article UIDS
  	 */
  	function getArticleUids()  	{
  		return $this->articles_uids;
  	}
  	
  	/**
  	 * Depricated, please use getArticleUids()
  	 * @see tx_comemrce_product::getARticleUids();
  	 */
  	function getArticles()  	{
  		return $this->getArticleUids();
  	}
  	
  	/**
  	 * Returns the list of articles
  	 * @return array of article Objects
  	 */
  	 
  	function getArticleObjects()  	{
  		return $this->articles;
  	}
  	
  	/**
  	 * Returns the number of artiles for this Product
  	 * @return	integer	Number of articles
  	 */
  	function getNumberOfArticles () {
  			return count($this->articles);	
  	}
  	
  	
  	/**
  	 * Gets the article_list of this product and stores in private variable
  	 * @since 28.08.2005 Check Class valiable article_loaded for more performace
  	 * @return array of uid's
  	 */
  	function load_articles()  	{
  		if ($this->articles_loaded==false)
  		{
  			$uidToLoadFrom  = $this->uid;
  			if (($this->get_t3ver_oid() >0) && ($this->get_t3ver_oid() <> $this->uid) && (is_Object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->beUserLogin)){
  				$uidToLoadFrom = $this->get_t3ver_oid();
  			}
			if ($this->articles_uids=$this->conn_db->get_articles($uidToLoadFrom))
	  		{
	  			foreach ($this->articles_uids as $article_uid)
	  			{
	  				// initialise Array of articles 
	  				$this->articles[$article_uid] = t3lib_div::makeInstance('tx_commerce_article'); 
	  				$this->articles[$article_uid]->init($article_uid,$this->lang_uid);
					$this->articles[$article_uid]->load_data();	  				
	  			}
	  			$this->articles_loaded=true;
	  			return $this->articles_uids;
	  		}
			else
			{
					return false;
			}
  		}
  		else
  		{
  			return $this->articles_uids;
  		}
 		
  	}
  	


  	
  	/**
  	 * Loads the data and divides comma sparated images in array
  	 * inherited from parent 
  	 * @param $translationMode Transaltio Mode of the record, default false to use the default way of translation
  	 */
  	
  	function load_data($translationMode = false){
  
  		$return=parent::load_data($translationMode);	
  		$this->images_array=t3lib_div::trimExplode(',',$this->images);
  		$this->teaserImagesArray=t3lib_div::trimExplode(',',$this->teaserimages);
  		return $return;
  	}
  	/**
  	 * gets the category master parent
  	 * @depricated
  	 * @return uid of category
  	 */
  	
  	function getMasterparentCategorie(){
  		return $this->conn_db->get_parent_categorie($this->uid);
  		 			
  	}
  	
  	/**
  	 * gets the category master parent
  	 * 
  	 * @return uid of category
  	 */
  	
  	function getMasterparentCategory(){
  		return $this->conn_db->get_parent_categorie($this->uid);
  		 			
  	}

	/**
	 * Gets related products
	 * @return array instances of Related products
	 * @TODO:Check for stock/show_with_no_stock=1 ?
	 */
	 function getRelatedProducts(){
		if ( !$this->relatedProducts_loaded ) { 
			$this->relatedProduct_uids=$this->conn_db->get_related_product_uids($this->uid);
			if ( count($this->relatedProduct_uids)>0){
				foreach ($this->relatedProduct_uids as $productID => $categoryID ){
	 				$product = t3lib_div::makeInstance('tx_commerce_product');
	 				$product->init($productID,$this->lang_uid);
					$product->load_data();	#TODO: is it our job to load here?
					if ($product->isAccessible()) { //Check if the user is alowed to acces the product

							$this->relatedProducts[]=$product;
					}
				}
			}
			$this->relatedProducts_loaded=true;
		}	
		return $this->relatedProducts;
	 }

  	/**
  	 * gets all parent categories 
  	 * @return array of uid of category
  	 * @deprecated use getParentCategories instead, this function only returns 1 parent category
  	 */
  	
  	function get_parent_categories()
  	{
  		return $this->conn_db->get_parent_categories($this->uid);	
  		
  	}
  	
  	/**
  	 * Returns an array with the different l18n for the product
  	 * 
  	 * @return {array}	Categories
  	 */
  	function get_l18n_products() {
  		$uid_lang = $this->conn_db->get_l18n_products($this->uid);
  		
		return $uid_lang;
  	} 
	
	/**
	 * Gets all parent categories
	 * @return {array}	Array of category uids that serve as parent
	 */
	function getParentCategories() {
		return $this->conn_db->getParentCategories($this->uid);
	}
  	
  	
  	 /**
  	 * Returns list of articles from this product filtered by given attribute UID and Attribute Value
  	 * 
  	 * @param param_array array (array('AttributeUid'=>$attributreUID, 'AttributeValue'=>$attributeValue), array('AttributeUid'=>$attributreUID, 'AttributeValue'=>$attributeValue),...) 
  	 * @param proof if script is running without instance and so without a single product
  	 * 
  	 * @return array of article uids
  	 * @todo Move DB connector to db_product
  	 */
  	
  	 function get_Articles_by_AttributeArray($attribute_Array,$proofUid=1){
		if($proofUid){  	 	
	  	 	 $whereUid = ' and tx_commerce_articles.uid_product = '.intval($this->uid);
		}
  	 	$first = 1;
		// Setzen der Arrays damit array_intersect keine Fehlermeldung ausgibt	
		$first_array = array();
		$next_array = array();
  	 	$addwhere='';
  	 	if (is_array($attribute_Array))	 {
	  	 	foreach ($attribute_Array as $uid_val_pair) 	{
			$addwheretmp = '';
	
	  	 		// attribute char wird noch nicht verwendet, dafuer muss eine Pruefung auf die ID
		  	 	if (is_string($uid_val_pair['AttributeValue']))	 	{
		  	 		$addwheretmp .=	" OR (tx_commerce_attributes.uid = ".intval($uid_val_pair['AttributeUid'])."  and tx_commerce_articles_article_attributes_mm.value_char='".
										$GLOBALS['TYPO3_DB']->quoteStr($uid_val_pair['AttributeValue'],'tx_commerce_articles_article_attributes_mm')."' )";
		  	 	}
			  //  Nach dem charwert immer ueberpruefen, solange value_char noch nicht drin ist.
	    
		  	 	if (is_float($uid_val_pair['AttributeValue']) || is_integer(intval($uid_val_pair['AttributeValue'])))	 	{
		  	 		$addwheretmp.=	" OR (tx_commerce_attributes.uid = ".intval($uid_val_pair['AttributeUid'])."  and tx_commerce_articles_article_attributes_mm.default_value in ('".
										$GLOBALS['TYPO3_DB']->quoteStr($uid_val_pair['AttributeValue'],'tx_commerce_articles_article_attributes_mm')."' ) )";
		  	 	}
		 
		  	 	if (is_float($uid_val_pair['AttributeValue']) || is_integer(intval($uid_val_pair['AttributeValue']))) 	{
		  	 		$addwheretmp.=	" OR (tx_commerce_attributes.uid = ".intval($uid_val_pair['AttributeUid'])."  and tx_commerce_articles_article_attributes_mm.uid_valuelist in ('".
										$GLOBALS['TYPO3_DB']->quoteStr($uid_val_pair['AttributeValue'],'tx_commerce_articles_article_attributes_mm')."') )";
		  	 	}
				$addwhere = ' AND (0 '.$addwheretmp. ') ';	
	  	 	
  	 			
  	 	  	 		
  	 			$result=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_articles.uid',
  	 							'tx_commerce_articles ',
 								'tx_commerce_articles_article_attributes_mm',
								'tx_commerce_attributes',	
								"".$addwhere." and tx_commerce_articles.hidden = 0 and tx_commerce_articles.deleted = 0".$whereUid 
								);
				
	  	 		if (($result) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0)){
	 				while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
						if($first){
		 					$first_array[] = $return_data['uid'];
	 					}else{
							$next_array[] = $return_data['uid'];
						}
					}
	 				$GLOBALS['TYPO3_DB']->sql_free_result($result);
	 			}
	
				// Es sollen nur Artikel zur?ckgeliefert werden, die in allen Array's vorkommen.
				// Daher das Erste Array setzen und dann mit Array Intersect nur noch die ?bereinstimmungen
				// behalten.
				if($first){
					$attribute_uid_list = $first_array;
					$first = 0;
				}else{
					$attribute_uid_list = array_intersect($attribute_uid_list,$next_array);
					$next_array = array();
				}
		  	} 				
	 		if(count($attribute_uid_list)>0){
	 			sort($attribute_uid_list);
				return $attribute_uid_list;
 			}else{
				return false;			
			}	
		}
		  	 	  	 	
  	 	
  	 }
  	 
  	 /**
  	 * returns the list or articles from this product filtered by given AttributeUID and Attribute Value
  	 * @param attribute_UID 
  	 * @param attribute_value
  	 * @return array of article uids 
  	 * @TODO handling of valuelists
  	 */
  	 function get_Articles_by_Attribute($attributeUid,$attributeValue){
  	 	
  	 	return $this->get_Articles_by_AttributeArray(array(array('AttributeUid'=>$attributeUid,'AttributeValue'=>$attributeValue)));
  	 	
  	 }
  	 
  	 	/**
  	 	 * compares an array record by the sorting value
  	 	 * @param $arr1 array left
  	 	 * @param $arr2 array right
  	 	 */
 		public static function compareBySorting($arr1, $arr2) {
 			return $arr1['sorting'] - $arr2['sorting'];
 		}
   
  	 
  	 	/**
	 	 * Generates a Matrix fro these concerning artciles for all Attributes and the values therfor
	 	 * Realy complex array, so have a lokk at the source
	 	 * 
	 	 * @param $articleList [optional]
	 	 * @param $attribute Exclude List array (list auf attriubute uids to exclkude)
	 	 * @param $showHiddenValues default true (if hidden values should be shown)
	 	 * @Param $fallbackToDefault if set to true, the default language value will beused, if no local value is existent
	 	 * @return array of arrays
	 	 * @todo split DB connects to db_class
	 	 * @since 2005 11 02 $showHiddenValues
	 	 * @since 2005 11 02 Array of arrays also contains valueformat
	 	 * @since 2005 11 02 Array of arrays also contains internal_title
	 	 */
	 
	 	function get_attribute_matrix($articleList=false, $attribute_include=false, $showHiddenValues=true,$sortingTable = 'tx_commerce_articles_article_attributes_mm',$fallbackToDefault = false){
	 
	 		$return_array=array();
	 		/**
	 		 * if no list is given, take complate arctile-list from product
	 		 */
	 
	 
	 		if ($this->uid>0) { 
	 			if ($articleList==false){
	 				$articleList=$this->load_articles();
	 			}
	 
	 			if (is_array($attribute_include)){
	 				if (!is_null($attribute_include[0])) {
	 					$addwhere.=' AND tx_commerce_attributes.uid in ('.implode(',',$attribute_include).')';
	 				}	
	 			}
	 			if(is_array($articleList) && count($articleList)>0) {
	 				$query_article_list=  implode(',',$articleList);
	 				$addwhere2 =' AND tx_commerce_articles.uid in ('.$query_article_list.')';
	 			}
	 
	 			$result=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_attributes.uid,tx_commerce_attributes.sys_language_uid,tx_commerce_articles.uid as article ,tx_commerce_attributes.title, tx_commerce_attributes.unit, tx_commerce_attributes.valueformat, tx_commerce_attributes.internal_title,tx_commerce_attributes.icon, '.$sortingTable.'.sorting',
	 									'tx_commerce_articles',
	 									'tx_commerce_articles_article_attributes_mm',
	 									'tx_commerce_attributes',	
	 									' AND tx_commerce_articles.uid_product = '.$this->uid.' '.$addwhere.$addwhere2.' order by '.$sortingTable.'.sorting'
	 									);
	 			$addwhere = $addwhere2;
	 
	 			if (($result) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0))	{
	 				while ($data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
	 
	 					/** 
	 					 * Do the language overlay
	 					 */
	 					if ($this->lang_uid>0) {
	 						if(is_object($GLOBALS['TSFE']->sys_page)){
	 								$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes',$GLOBALS['TSFE']->showHiddenRecords);
	 						}
	 						$result2=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
	 							'tx_commerce_attributes',
	 							'uid = '.$data['uid'].' '.$proofSQL
	 							);
	 
	 
	 						// Result should contain only one Dataset
	 						if ($GLOBALS['TYPO3_DB']->sql_num_rows($result2)==1)	{
	 							$return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result2);
	 							$GLOBALS['TYPO3_DB']->sql_free_result($result2);
	 							$return_data=$GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attributes',$return_data,$this->lang_uid,$this->translationMode);
	 							if (!is_array($return_data)){
	 							/**
	 							 * No Translation possible, so next interation
	 							 */	
	 								continue;
	 							}
	 						}
	 
	 						$data['title']=$return_data['title'];
	 						$data['unit']=$return_data['unit'];
	 						$data['internal_title']=$return_data['internal_title'];
	 
	 
	 
	 					}
	 
	 					$valueshown=false;
	 					/**
	 					 * get the different possible values form value_char an value
	 					 */
	 					/**
	 					 * @since 13.12.2005 Get the lokalized values from tx_commerce_articles_article_attributes_mm
	 					 * @author Ingo Schmitt <is@marketing-factory.de>
	 					 */
	 
	 					$valuelist=array();
						$valueUidList = array();
	 					$attribute_uid=$data['uid'];
	 					$article=$data['article'];
	 					$result_value=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_articles_article_attributes_mm.value_char, tx_commerce_articles.uid article_uid, tx_commerce_attributes.uid attribute_uid',
	 									'tx_commerce_articles',
	 									'tx_commerce_articles_article_attributes_mm',
	 									'tx_commerce_attributes',	
	 									' AND tx_commerce_articles.uid_product = '.$this->uid.' AND tx_commerce_attributes.uid='.$attribute_uid.$addwhere.' order by tx_commerce_articles.sorting '
	 									);
	 					if (($result_value) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result_value)>0))	{
	 							while ($value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result_value))		{
	 
	 								if (strlen($value['value_char'])>0)	{
	 										$lokAval = false;
	 									if ($this->lang_uid>0)	{
	 										/**
	 										 * Do the lokalization
	 										 */
	 
	 										$proofSQL_attributes = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes',$GLOBALS['TSFE']->showHiddenRecords);
	 										$proofSQL_articles = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles',$GLOBALS['TSFE']->showHiddenRecords);
	 										$res_value_lok=$GLOBALS['TYPO3_DB']->exec_SELECTquery('distinct tx_commerce_articles_article_attributes_mm.value_char, tx_commerce_articles_article_attributes_mm.default_value',
	 										'tx_commerce_articles_article_attributes_mm, tx_commerce_articles, tx_commerce_attributes',
	 										"tx_commerce_articles_article_attributes_mm.uid_foreign=".$value['attribute_uid'].
	 													" and tx_commerce_articles_article_attributes_mm.uid_local=tx_commerce_articles.uid and tx_commerce_articles.sys_language_uid=".$this->lang_uid.
	 													" and tx_commerce_articles.uid_product>0 and tx_commerce_articles.l18n_parent=".$value['article_uid'].
	 													" ".$proofSQL_attributes.$proofSQL_articles
	 										);
	 
	 										if (($res_value_lok) && ($GLOBALS['TYPO3_DB']->sql_num_rows($res_value_lok)>0)) {
	 											while ($lok_value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_value_lok)){
	 
	 												if (strlen($lok_value['value_char'])>0){
	 													$valuelist[]=$lok_value['value_char'];
	 													$valueshown=true;
	 												}elseif (strlen($lok_value['default_value'])>0){
	 													$valuelist[]=$lok_value['default_value'];
	 													$valueshown=true;
	 												}
	 												$lokAval = true;
	 											}
	 										}
	 										if ($fallbackToDefault && $lokAval == false) {
	 											$valuelist[]=$value['value_char'];
	 											$valueshown=true;
	 										}
	 
	 									}else	{
	 
	 										$valuelist[]=$value['value_char'];
	 										$valueshown=true;
	 									}
	 								}
	 							}
	 					} 		
	 
	 					$result_value=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_articles_article_attributes_mm.default_value,  tx_commerce_articles.uid article_uid, tx_commerce_attributes.uid attribute_uid ',
	 									'tx_commerce_articles',
	 									'tx_commerce_articles_article_attributes_mm',
	 									'tx_commerce_attributes',	
	 									' AND tx_commerce_articles.uid_product = '.$this->uid." AND tx_commerce_attributes.uid=$attribute_uid".$addwhere.' order by tx_commerce_articles.sorting '
	 									);
	 					if (($valueshown == false) && ($result_value) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result_value)>0)){
	 							while ($value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result_value))	{
	 
	 								if ($value['default_value']>0)	{
	 									$lokAval = false;
	 									if ($this->lang_uid>0){
	 										/**
	 										 * Do the lokalization
	 										 */
	 
	 										$proofSQL_attributes = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes',$GLOBALS['TSFE']->showHiddenRecords);
	 										$proofSQL_articles = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles',$GLOBALS['TSFE']->showHiddenRecords);
	 										$res_value_lok=$GLOBALS['TYPO3_DB']->exec_SELECTquery('distinct tx_commerce_articles_article_attributes_mm.default_value, tx_commerce_articles_article_attributes_mm.value_char',
	 										'tx_commerce_articles_article_attributes_mm, tx_commerce_articles, tx_commerce_attributes',
	 										"tx_commerce_articles_article_attributes_mm.uid_foreign=".$value['attribute_uid'].
	 													" and tx_commerce_articles_article_attributes_mm.uid_local=tx_commerce_articles.uid and tx_commerce_articles.sys_language_uid=".$this->lang_uid.
	 													" and tx_commerce_articles.l18n_parent=".$value['article_uid'].
	 													" ".$proofSQL_attributes.$proofSQL_articles
	 										);
	 										if (($res_value_lok) && ($GLOBALS['TYPO3_DB']->sql_num_rows($res_value_lok)>0)) {
	 											while ($lok_value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_value_lok)){
	 												if (strlen($lok_value['default_value'])>0){
	 													$valuelist[]=$lok_value['default_value'];
	 													$valueshown=true;
	 												}elseif(strlen($lok_value['value_char'])>0) {
	 													$valuelist[]=$lok_value['value_char'];
	 													$valueshown=true;
	 												}
	 											}
	 											$lokAval = true;
	 										}
	 										
	 										if ($fallbackToDefault && $lokAval == false) {
	 											$valuelist[]=$value['default_value'];
	 											$valueshown=true;
	 										}
	 									}else
	 									{
	 										$valuelist[]=$value['default_value'];
	 										$valueshown=true;
	 									}
	 								}
	 							}
	 					}
	 
	 					$result_value=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_articles_article_attributes_mm.uid_valuelist ',
	 							'tx_commerce_articles',
	 							'tx_commerce_articles_article_attributes_mm',
	 							'tx_commerce_attributes',	
	 							' AND tx_commerce_articles_article_attributes_mm.uid_valuelist>0 AND tx_commerce_articles.uid_product = '.$this->uid." AND tx_commerce_attributes.uid=$attribute_uid".$addwhere.' order by tx_commerce_articles.sorting '
	 						);
	 					if (($valueshown == false) && ($result_value) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result_value)>0)){
	 							while ($value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result_value)){
	 
	 								if ($value['uid_valuelist']>0){
	 
	 								    $resvalue = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_attribute_values','uid = '.$value['uid_valuelist']);
	 								    $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resvalue);
	 								    if ($this->lang_uid>0) {
		 									$row=$GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attribute_values',$row,$this->lang_uid,$this->translationMode);
		 									if (!is_array($row)){
		 										continue;	
		 									}
	 								    }
	 								    if (($showHiddenValues==true) || ($row['showvalue']==1)){
	 								    	$valuelist[] = $row;
											 $valueUidList[] = $row['uid'];
	 										 $valueshown=true;
	 								    }
	 								}
	 							}
	 							usort($valuelist, array('tx_commerce_product', 'compareBySorting'));
	 					}
	 					if ($valueshown == false) {
	 						$return_array[$attribute_uid]=array('title' => $data['title'],
	 													  'unit' => $data['unit'],
	 													  'values' => array(),
														  'valueuidlist' => array(),
	 													  'valueformat' => $data['valueformat'],
	 													  'Internal_title' => $data['internal_title'],
	 													  'icon' => $data['icon']
	 													);
	 						
	 					}
	 
	 					if ($valueshown==true){
	 						$return_array[$attribute_uid]=array('title' => $data['title'],
	 													  'unit' => $data['unit'],
	 													  'values' => $valuelist,
														  'valueuidlist' => $valueUidList,
	 													  'valueformat' => $data['valueformat'],
	 													  'Internal_title' => $data['internal_title'],
	 													  'icon' => $data['icon']
	 													);
	 					}
	 					
	 
	 				}
	 
	 				return $return_array;
	 			}
	 		}
	 		return false;
	 
	}
  	 
  	/**
  	 * Generates a Matrix fro these concerning artciles for all Attributes and the values therfor
  	 * Realy complex array, so have a lokk at the source
  	 * 
  	 * @param $articleList [optional]
  	 * @param $attribute Exclude List array (list auf attriubute uids to exclkude)
  	 * @param $showHiddenValues default true (if hidden values should be shown)
  	 * @return array of arrays
  	 * @todo split DB connects to db_class
  	 * @since 2005 11 02 $showHiddenValues
  	 * @since 2005 11 02 Array of arrays also contains valueformat
  	 * @since 2005 11 02 Array of arrays also contains internal_title
  	 */
  	
  	function get_selectattribute_matrix($articleList=false, $attribute_include=false, $showHiddenValues=true,$sortingTable = 'tx_commerce_articles_article_attributes_mm'){
  		
  		
  		$return_array=array();
  		/**
  		 * if no list is given, take complate arctile-list from product
  		 */
  		
  		
  		if ($this->uid>0) { 
	  		if ($articleList==false){
	  			$articleList=$this->load_articles();
	  		}
	
	        if (is_array($attribute_include)){
	        	if (!is_null($attribute_include[0])) {
		  			$addwhere.=' AND tx_commerce_attributes.uid in ('.implode(',',$attribute_include).')';
				}	
	  		}
	  		if(is_array($articleList) && count($articleList)>0) {
				$query_article_list=  implode(',',$articleList);
	  			$addwhere2 =' AND tx_commerce_articles.uid in ('.$query_article_list.')';
			}
	  		
	  		$result=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_attributes.uid,tx_commerce_attributes.sys_language_uid,tx_commerce_articles.uid as article ,tx_commerce_attributes.title, tx_commerce_attributes.unit, tx_commerce_attributes.valueformat, tx_commerce_attributes.internal_title,tx_commerce_attributes.icon,tx_commerce_attributes.iconmode, '.$sortingTable.'.sorting',
	  	 							'tx_commerce_articles',
	 								'tx_commerce_articles_article_attributes_mm',
									'tx_commerce_attributes',	
									' AND tx_commerce_articles.uid_product = '.$this->uid.' '.$addwhere.$addwhere2.' order by '.$sortingTable.'.sorting'
									);
	 		$addwhere = $addwhere2;
			
			if (($result) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0))	{
	 			while ($data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
	 				/** 
	 				 * Do the language overlay
	 				 */
	 				 
	 				if ($this->lang_uid>0) {
	 					if(is_object($GLOBALS['TSFE']->sys_page)){
			   					$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes',$GLOBALS['TSFE']->showHiddenRecords);
						}
				 		$result2=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
				 			'tx_commerce_attributes',
							'uid = '.$data['uid'].' '.$proofSQL
							);
						
						
				 		// Result should contain only one Dataset
				 		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result2)==1)	{
				 			$return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result2);
				 			$GLOBALS['TYPO3_DB']->sql_free_result($result2);
				 			$return_data=$GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attributes',$return_data,$this->lang_uid,$this->translationMode);
				 			if (!is_array($return_data)){
				 			/**
				 			 * No Translation possible, so next interation
				 			 */	
				 				continue;
				 			}
	 					}
	 					
	 					$data['title']=$return_data['title'];
	 					$data['unit']=$return_data['unit'];
	 					$data['internal_title']=$return_data['internal_title'];
	 							     	
	 				}
	 				
	 				$valueshown=false;
	 				/**
	 				 * only get select attributs, since we don't need any other in selectattribut Matrix and we need the arrayKeys in this case
	 				 */
	 				/**
	 				 * @since 13.12.2005 Get the lokalized values from tx_commerce_articles_article_attributes_mm
	 				 * @author Ingo Schmitt <is@marketing-factory.de>
	 				 */
	 				
					
					$valuelist=array();
					$valueUidList = array();
					$attribute_uid=$data['uid'];
	 				$article=$data['article'];
					
					$result_value=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_articles_article_attributes_mm.uid_valuelist ',
	  	 					'tx_commerce_articles',
	 						'tx_commerce_articles_article_attributes_mm',
							'tx_commerce_attributes',	
							' AND tx_commerce_articles_article_attributes_mm.uid_valuelist>0 AND tx_commerce_articles.uid_product = '.$this->uid." AND tx_commerce_attributes.uid=$attribute_uid".$addwhere
						);
					if (($valueshown == false) && ($result_value) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result_value)>0)){
	 						while ($value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result_value)){
	 							if ($value['uid_valuelist']>0){
	 							    	
	 							    $resvalue = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_attribute_values','uid = '.$value['uid_valuelist']);
	 							    $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resvalue);
	 							    if ($this->lang_uid>0) {
	 							     	$row=$GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attribute_values',$row,$this->lang_uid,$this->translationMode);
	 							     	if (!is_array($row)){
	 							     		continue;	
	 							     	}
	 							     }
	 							    if (($showHiddenValues==true) || (($showHiddenValues==false) && ($row['showvalue']==1))){
	 							     
	 							   	 $valuelist[$row['uid']] = $row;
	 							   	 $valueshown=true;
	 							    }
	 							}
	 						}

	 						usort($valuelist, array('tx_commerce_product', 'compareBySorting'));
							/**
							 * Sort values by the sorting field.
							 * Is there a better way to do this?
							 * Yes, don't repeat this every row!
							 */
							/* $valuelist_temp = $valuelist;
							$valuelist = array();
							$valuelist_temp_sort = array();
							foreach ($valuelist_temp as $value_temp) {
								if (!isset($valuelist_temp_sort[$value_temp['sorting']])) {
									$valuelist_temp_sort[$value_temp['sorting']] = $value_temp;
								} else {
									$valuelist_temp_sort[] = $value_temp;
								}
							}
							ksort($valuelist_temp_sort);
							foreach ($valuelist_temp_sort as $value_temp) {
								$valuelist[] = $value_temp;
							} */
	 				}
	 				
	 				if ($valueshown==true){
	 					$return_array[$attribute_uid]=array('title' => $data['title'],
	 												  'unit' => $data['unit'],
	 												  'values' => $valuelist,
	 												  'valueformat' => $data['valueformat'],
	 												  'Internal_title' => $data['internal_title'],
	 												  'icon' => $data['icon'],
	 												  'iconmode' => $data['iconmode'],
	 												);
	 				}
	 				
	 			}
	 			
	 			return $return_array;
	 		}
  		}
 		return false;
  		
  	}
  	
  	
 /**
	 * Generates a Matrix fro these concerning products for all Attributes and the values therefor
	 * Realy complex array, so have a look at the source
	 * 
	 * 
	 *
	 *
	 */
	 
	 function getRelevantArticles($attributeArray = false){
  	
	 	//first of all we need all possible Attribute id's (not attribute value id's)
	 	foreach ($this->attribute as $attribute) {
	 		$att_is_in_array = false;
	 		foreach ($attributeArray as $attribute_temp) {
	 			if($attribute_temp['AttributeUid'] == $attribute->uid) $att_is_in_array = true;
	 		}
	 		if(!$att_is_in_array) $attributeArray[] = array('AttributeUid'=>$attribute->uid,'AttributeValue'=>NULL);
	 	}
	
  		if ($this->uid>0 && is_array($attributeArray) && count($attributeArray)) {
  			foreach($attributeArray as $key=>$attr){
  				if($attr['AttributeValue']){
		  			$unionSelects[] = 'SELECT uid_local AS article_id,uid_valuelist FROM tx_commerce_articles_article_attributes_mm,tx_commerce_articles WHERE uid_local = uid AND uid_valuelist = '.intval($attr['AttributeValue']).' AND tx_commerce_articles.uid_product = '.$this->uid.' AND uid_foreign = '.intval($attr['AttributeUid']).$GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles',$GLOBALS['TSFE']->showHiddenRecords);;
		  		} else {
		  			$unionSelects[] = 'SELECT uid_local AS article_id,uid_valuelist FROM tx_commerce_articles_article_attributes_mm,tx_commerce_articles WHERE uid_local = uid AND tx_commerce_articles.uid_product = '.$this->uid.' AND uid_foreign = '.intval($attr['AttributeUid']).$GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles',$GLOBALS['TSFE']->showHiddenRecords);;
		  		}
	  		}
	  		if(is_array($unionSelects)){
	  			$sql  = ' SELECT count(article_id) AS counter, article_id FROM ( ';
	  			$sql .= implode(" \n UNION \n ",$unionSelects);
	  			$sql .= ') AS data GROUP BY article_id having COUNT(article_id) >= '.(count($unionSelects)-1).'';
	  		}
	  		
	  		$res = $GLOBALS['TYPO3_DB']->sql_query($sql);
	  		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
	  			$article_uid_list[] = $row['article_id'];
	  		}
	  		
			return $article_uid_list;
		}
  	
		return false;
  	}
  	
  	
  	/**
  	 * Generates a Matrix fro these concerning products for all Attributes and the values therfor
  	 * Realy complex array, so have a lokk at the source
  	 * 
  	 * @param $attribute Exclude List array (list auf attriubute uids to exclkude)
  	 * @param $showHiddenValues default true (if hidden values should be shown)
  	 * @return array of arrays
  	 * @todo split DB connects to db_class
  	 * @since 2005 11 02 $showHiddenValues
  	 * @since 2005 11 02 Array of arrays also contains valueformat
  	 * @since 2005 11 02 Array of arrays also contains internal_title
  	 * 
  	 */
  	
  	function get_product_attribute_matrix($attribute_include=false, $showHiddenValues=true,$sortingTable = 'tx_commerce_products_attributes_mm')
  	{
  		
  		
  		$return_array=array();
  		/**
  		 * if no list is given, take complate arctile-list from product
  		 */
  		
  		
  		if ($this->uid>0) { 
	  		
	
	        if (is_array($attribute_include)){
	        	if (!is_null($attribute_include[0])) {
		  			$addwhere.=' AND tx_commerce_attributes.uid in ('.implode(',',$attribute_include).')';
				}	
	  		}
	  		
	  		
	  		$result=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_attributes.uid,tx_commerce_attributes.sys_language_uid,tx_commerce_products.uid as product ,tx_commerce_attributes.title, tx_commerce_attributes.unit, tx_commerce_attributes.valueformat, tx_commerce_attributes.internal_title,tx_commerce_attributes.icon, '.$sortingTable.'.sorting',
	  	 							'tx_commerce_products',
	 								'tx_commerce_products_attributes_mm',
									'tx_commerce_attributes',	
									' AND tx_commerce_products.uid = '.$this->uid.' '.$addwhere.' order by '.$sortingTable.'.sorting'
									);

			if (($result) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0))	{
	 			while ($data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
	 				/** 
	 				 * Do the language overlay
	 				 */
	 				if ($this->lang_uid>0) {
	 					if(is_object($GLOBALS['TSFE']->sys_page)){
			   					$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes',$GLOBALS['TSFE']->showHiddenRecords);
						}
				 		$result2=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
				 			'tx_commerce_attributes',
							'uid = '.$data['uid'].' '.$proofSQL
							);
						
						
				 		// Result should contain only one Dataset
				 		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result2)==1)	{
				 			$return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result2);
				 			$GLOBALS['TYPO3_DB']->sql_free_result($result2);
				 			$return_data=$GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attributes',$return_data,$this->lang_uid,$this->translationMode);
				 			if (!is_array($return_data)){
				 			/**
				 			 * No Translation possible, so next interation
				 			 */	
				 				continue;
				 			}
	 					}
	 					
	 					$data['title']=$return_data['title'];
	 					$data['unit']=$return_data['unit'];
	 					$data['internal_title']=$return_data['internal_title'];
	 					
	 					
	 							     	
	 				}
	 				
	 				$valueshown=false;
	 				/**
	 				 * get the different possible values form value_char an value
	 				 */
	 				/**
	 				 * @since 13.12.2005 Get the lokalized values from tx_commerce_products_attributes_mm
	 				 * @author Ingo Schmitt <is@marketing-factory.de>
	 				 */
	 				
					$valuelist=array();
	 				$attribute_uid=$data['uid'];
	 				$article=$data['product'];
	 				$result_value=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_products_attributes_mm.default_value, tx_commerce_products.uid product_uid, tx_commerce_attributes.uid attribute_uid',
	  	 							'tx_commerce_products',
	 								'tx_commerce_products_attributes_mm',
									'tx_commerce_attributes',	
									' AND tx_commerce_products.uid = '.$this->uid.' AND tx_commerce_attributes.uid='.$attribute_uid
									);
					if (($result_value) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result_value)>0))
	 				{
	 						while ($value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result_value))
	 						{
	 							if (strlen($value['default_value'])>0){
	 								
	 								if ($this->lang_uid>0)
	 								{
	 									/**
	 									 * Do the lokalization
	 									 */
	 									
	 									$proofSQL_attributes = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes',$GLOBALS['TSFE']->showHiddenRecords);
	 									$proofSQL_articles = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_products',$GLOBALS['TSFE']->showHiddenRecords);
	 									$res_value_lok=$GLOBALS['TYPO3_DB']->exec_SELECTquery('distinct tx_commerce_products_attributes_mm.default_value',
										'tx_commerce_products_attributes_mm, tx_commerce_products, tx_commerce_attributes',
										"tx_commerce_products_attributes_mm.uid_foreign=".$value['attribute_uid'].
													" and tx_commerce_products_attributes_mm.uid_local=tx_commerce_products.uid and tx_commerce_products.sys_language_uid=".$this->lang_uid.
													" and tx_commerce_products.uid>0 and tx_commerce_products.l18n_parent=".$value['product_uid'].
													" ".$proofSQL_attributes.$proofSQL_articles
										);
										if (($res_value_lok) && ($GLOBALS['TYPO3_DB']->sql_num_rows($res_value_lok)>0)) {
											while ($lok_value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_value_lok)){
												if (strlen($lok_value['default_value'])>0){
													$valuelist[]=$lok_value['default_value'];
													$valueUidList[] = 0;
													$valueshown=true;
												}
	 										}
										}
										
									}else
	 								{
	 															
	 									$valuelist[]=$value['default_value'];
										$valueUidList[] = 0;
										$valueshown=true;
	 								}
	 							}
	 						}
	 				} 		
					$result_value=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('distinct tx_commerce_products_attributes_mm.uid_valuelist ',
	  	 					'tx_commerce_products',
	 						'tx_commerce_products_attributes_mm',
							'tx_commerce_attributes',	
							' AND tx_commerce_products.uid = '.$this->uid." AND tx_commerce_attributes.uid=$attribute_uid"
						);
					if (($result_value) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result_value)>0))
	 				{
	 						while ($value=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result_value))
	 						{
	 							if ($value['uid_valuelist'])
	 							{
	 							    	
	 							    $resvalue = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_attribute_values','uid = '.$value['uid_valuelist']);
	 							    $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resvalue);
	 							    if ($this->lang_uid>0) {
	 							     	$row=$GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attribute_values',$row,$this->lang_uid,$this->translationMode);
	 							     	if (!is_array($row)){
	 							     		continue;	
	 							     	}
	 							     }
	 							    if (($showHiddenValues==true) || (($showHiddenValues==false) && ($row['showvalue']==1))){
	 							     
	 							     
	 							   	 $valuelist[] = $row;
									 $valueUidList[] = $value['uid_valuelist'];
	 							   	 $valueshown=true;
	 							    }
	
	 							}
	 						}
	 						/**
	 						 * Sort values by the sorting field.
	 						 * Is there a better way to do this?
	 						 */
	 						$valuelist_temp = $valuelist;
	 						$valuelist = array();
	 						$valuelist_temp_sort = array();
	 						foreach ($valuelist_temp as $value_temp) {
	 							if($valuelist_temp_sort[$value_temp['sorting']]) {
	 								$valuelist_temp_sort[] = $value_temp;
								}else{
									$valuelist_temp_sort[$value_temp['sorting']] = $value_temp;
								}
	 						}
	 						ksort($valuelist_temp_sort);
	 						foreach ($valuelist_temp_sort as $value_temp) {
	 							$valuelist[] = $value_temp;
	 						}
	 					}
	 				if ($valueshown==false){
	 					$return_array[$attribute_uid]=array('title' => $data['title'],
	 												  'unit' => $data['unit'],
	 												  'values' =>array(),
													  'valueuidlist' => array(),
	 												  'valueformat' => $data['valueformat'],
	 												  'Internal_title' => $data['internal_title'],
	 												  'icon' => $data['icon']
	 												);
	 				}
	 				if ($valueshown==true){
	 					$return_array[$attribute_uid]=array('title' => $data['title'],
	 												  'unit' => $data['unit'],
	 												  'values' => $valuelist,
													  'valueuidlist' => $valueUidList,
	 												  'valueformat' => $data['valueformat'],
	 												  'Internal_title' => $data['internal_title'],
	 												  'icon' => $data['icon']
	 												);
	 				}
	 				
	 			}
	 		
	 			return $return_array;
	 		}
  		}
 		return false;
  		
  	}
  	
  	
  	
  	
         /**
          * Returns list of articles (from this product) filtered by price
          *
          * @author Franz Ripfel
	  * @param priceMin long in smallest unit (e.g. cents)
	  * @param priceMax long in smallest unit (e.g. cents)
	  * @param usePriceNetInstead boolean normally we check for net price, switch to gross price
	  * @param proof if script is running without instance and so without a single product
	  *
	  * @return array of article uids
	  * @todo Move DB connector to db_product
	*/
	    
	    function getArticlesByPrice($priceMin=0, $priceMax=0, $usePriceGrossInstead=0, $proofUid=1){
                    //first get all real articles, then create objects and check prices
	            //do not get prices directly from DB because we need to take (price) hooks into account
	                $table = 'tx_commerce_articles';
	                    $where = '1=1';
	                if($proofUid){
	                            $where.= ' and tx_commerce_articles.uid_product = '.$this->uid;
	                }	
               //todo: put correct constant here
	           $where.= ' and article_type_uid=1';
	           $where.= $this->cObj->enableFields($table);
	           $groupBy = '';
	           $orderBy = 'sorting';
	           $limit = '';
	           $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
	                   'uid', $table,
	                   $where, $groupBy,
	                   $orderBy,$limit
	           );
	           $rawArticleUidList = array();
	           while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))  {
	                       $rawArticleUidList[] = $row['uid'];
	           }
	           $GLOBALS['TYPO3_DB']->sql_free_result($res);
	   
	             //now run the price test
	           $articleUidList = array();
	           foreach ($rawArticleUidList as $rawArticleUid) {
	           	 		$tmpArticle  = t3lib_div::makeInstance('tx_commerce_article');
		               	$tmpArticle->init($rawArticleUid,$this->lang_uid);
		               	$tmpArticle->load_data();
			       $myPrice = $usePriceGrossInstead ? $tmpArticle->get_price_gross() : $tmpArticle->get_price_net();
			       if (($priceMin <= $myPrice) && ($myPrice <= $priceMax)) {
			                   $articleUidList[] = $tmpArticle->get_uid();
			         }
		   }
                  if(count($articleUidList)>0){
                            return $articleUidList;
                  }else{
                            return false;
	          }
	}		

    /**
     * evaluates the cheapest article for current product by gross price
     *
	 * @author Franz Ripfel
	 * @param $usePriceNet compare prices by net instead of gross
	 * @return article id, false if no article
	 */
	
	function getCheapestArticle($usePriceNet=0) {
		$this->load_articles();
		$priceArr = array();
		if (!is_array($this->articles_uids)) return false;
		for($j=0;$j<count($this->articles_uids);$j++) {
			if (is_object($this->articles[$this->articles_uids[$j]]) && ($this->articles[$this->articles_uids[$j]] instanceof tx_commerce_article)) {
				$priceArr[$this->articles[$this->articles_uids[$j]]->get_uid()] = ($usePriceNet) ? $this->articles[$this->articles_uids[$j]]->get_price_net() : $this->articles[$this->articles_uids[$j]]->get_price_gross();
			}
		}
		asort($priceArr);
		reset($priceArr);
		foreach($priceArr as $key => $value) {
			return $key;
		}
	}
          
     /**
      * Returns the Manufacturer UID of the Product if set
      * 
      * @author Joerg Sprung <jsp@marketing-factory.de>
      * @return integer UID of Manufacturer
      */
     function getManufacturerUid() {
     	if(isset($this->manufacturer_uid)) {
     		return $this->manufacturer_uid;
     	}
     	return false;
     }
     /**
      * Returns the manufacturere Title
      * @return	string	Title of manufacturer
      */
     
     function getManufacturerTitle() {
     	if ($this->getManufacturerUid()) {
     		return $this->conn_db->getManufacturerTitle($this->getManufacturerUid());
     	}
     	
     }
     
     /**
      * Returns true if one Article of Product have more than 
      * null articles on stock
      * 
      * @return	boolean	result of check
      */
     function hasStock() {
     	$this->load_articles();
     	foreach ( $this->articles as $articleObj ) {
     		if ( $articleObj->getStock() > 0 ) {
     			return true;
     		} 
     	}
     	return false;
     }
	 
	 /**
	 * Carries out the move of the product to the new parent
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
		$set = $this->conn_db->updateRecord($this->uid, array('categories' => $parent_uid));
		
		//only update relations if parent_category was successfully set
		if($set) {
			$catList = array($parent_uid);
			$catList = tx_commerce_belib::getUidListFromList($catList);
			$catList = tx_commerce_belib::extractFieldArray($catList, 'uid_foreign', true);
	
			tx_commerce_belib::saveRelations($this->uid, $catList, 'tx_commerce_products_categories_mm', true);
		} else return false;
		return true;
	}
          
     /**
      * Depricated Methods
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
  	
  	/**
  	 * gets the category master parent
  	 * @return uid of category
  	 */
  	
  	function get_masterparent_categorie() 	{
  		return $this->getMasterparentCategory();
  		 			
  	}
  	
  	/**
  	 * sets  a short description
  	 * @param $leng 
	 * @depricated
  	 */
  	
  	function set_leng_description($leng = 150)	{
  		$this->description= substr($this->description, 0, $leng).'...';
  		 			
  	} 
  	
  	/**
	 * returns the attribut matrix
	 * @see: get_attribute_matrix()
	 * @depricated
  	 */
  	
  	function get_atrribute_matrix($articleList=false, $attribute_include=false, $showHiddenValues=true,$sortingTable = 'tx_commerce_articles_article_attributes_mm'){
  		return $this->get_attribute_matrix($articleList, $attribute_include, $showHiddenValues,$sortingTable);
  	}

 
  	/**
  	 * Generates a Matrix fro these concerning products for all Attributes and the values therfor
  	 * Realy complex array, so have a lokk at the source
  	 * 
  	 * @param $attribute Exclude List array (list auf attriubute uids to exclkude)
  	 * @param $showHiddenValues default true (if hidden values should be shown)
  	 * @return array of arrays
  	 * @todo split DB connects to db_class
  	 * @since 2005 11 02 $showHiddenValues
  	 * @since 2005 11 02 Array of arrays also contains valueformat
  	 * @since 2005 11 02 Array of arrays also contains internal_title
  	 * @drepricated
  	 */
  	
  	function get_product_atrribute_matrix($attribute_include=false, $showHiddenValues=true,$sortingTable = 'tx_commerce_products_attributes_mm'){
  		
  		return $this->get_product_attribute_matrix($attribute_include, $showHiddenValues,$sortingTable );
  	}
  	
}			


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_product.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_product.php']);
}
?>

<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005 - 2006 Volker Graubaum <vg@e-netconsulting.de>
*  All rights reserved
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
 * Plugin  main plugin for listing in the 'commerce' extension.
 *
 * @author	Volker Graubaum <vg@e-netconsulting.de>
 * @author	Franz Ripfel <fr@abezet.de>
 * @see		tx_commerce_pibase
 * 
 * $Id: class.tx_commerce_pi1.php 576 2007-03-22 22:38:22Z ingo $
 */-
 

/**
 * tx_commerce includes
 */
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_pibase.php');


class tx_commerce_pi1 extends tx_commerce_pibase {
	var $prefixId = "tx_commerce_pi1";		// Same as class name
	var $scriptRelPath = "pi1/class.tx_commerce_pi1.php";	// Path to this script relative to the extension dir.
	var $extKey = "commerce";	// The extension key.
	var $currency = 'EUR';
	var $pi_checkCHash = TRUE;
	
	/**
	 * Inits the main params for using in the script
	 *
	 * @param string $conf:Configuration
	 * 
	 */ 

	function init($conf){

		parent::init($conf);
	 
	    //todo: is there a TYPO3 constant or variable with that information for every pi-class?
	    $this->imgFolder = "uploads/tx_commerce/";
	    $this->templateFolder = "uploads/tx_commerce/";
		        
		$this->pi_USER_INT_obj=0;
		
	    $this->conf['singleProduct'] = (int)$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'product_id', 's_product');
	    // Unset Variable, if smaler than 0, as -1 is returend when no product is selcted in form.
	    if ($this->conf['singleProduct'] < 0) {
	    	$this->conf['singleProduct'] = false;
	    }
	    $this->piVars[showUid] =  $this->piVars[showUid] ?  $this->piVars[showUid] : ($this->conf['singleProduct']  ? $this->conf['singleProduct'] : '');  
	    $this->handle =   $this->piVars[showUid] ? 'singleView' : 'listView';
	    
	    /**
	     * @TODO: Auf TS endern
	     */

            /**
	     	 * define the currency
             * Use of curency is depricated as it was only a typo :-)
             */
          if ($this->conf['curency']>''){
		  $this->currency = $this->conf['curency'];
	      }
	      if ($this->conf['currency']>''){
	          $this->currency = $this->conf['currency'];
	      }
	      if (empty($this->currency)) {
	          $this->currency = 'EUR';
	      }
																													      
		// set some flexforms values
		
		$this->master_cat = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'StartCategory', 's_product');
		if(!$this->master_cat){
			$this->master_cat = $this->conf['catUid'];			
		}
		
		
		
		if($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'displayPID', 's_template')){
			$this->conf['overridePid'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'displayPID', 's_template');			
		}
		if($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'numberOfTopproducts', 's_product')){
			$this->conf['numberOfTopproducts'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'numberOfTopproducts', 's_product');			
		}		
		if($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showPageBrowser', 's_template')){
	               $this->conf['showPageBrowser'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'showPageBrowser', 's_template');
	        }
		if($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxRecords', 's_template')){
			$this->conf['maxRecords'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxRecords', 's_template');
		}
		if($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxPages', 's_template')){
			$this->conf['maxPages'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxPages', 's_template');
		}
		if($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'basketPid', 's_template')){
			$this->conf['basketPid'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'basketPid', 's_template');			
		}
	
	       if($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template', 's_template') && file_exists($this->templateFolder.$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template', 's_template'))){
    	            $this->conf['templateFile'] = $this->templateFolder.$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template', 's_template');
                }

       
			/**	
			  * Validate given showUid, it it's below cat
			  */
			if($this->piVars['catUid']){ 
				 $this->cat = (int)$this->piVars['catUid'];
			}else{
				  $this->cat = (int)$this->master_cat;
			}
			
			$this->category=t3lib_div::makeinstance('tx_commerce_category');
			$this->category->init($this->cat,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
			$this->category->load_data();
			$categorySubproducts = $this->category->getProductUids() ;
			
			if (!$this->conf['singleProduct']) {
				if (is_array($categorySubproducts)) {
					if (!in_array($this->piVars['showUid'],$categorySubproducts)) {
						$categoryAllSubproducts = $this->category-> getAllProducts(9999999999);
						
						if (!in_array($this->piVars['showUid'],$categoryAllSubproducts)) {
							$this->handle='listView';
							$this->piVars['showUid']=false;
							$GLOBALS['TSFE']->set_no_cache();
						}
					}
				}else{
					$categoryAllSubproducts = $this->category-> getAllProducts(9999999999);
				
					if (!in_array($this->piVars['showUid'],$categoryAllSubproducts)) {
						$this->handle='listView';
						$this->piVars['showUid']=false;
						$GLOBALS['TSFE']->set_no_cache();
					}
				}
        	}
		if($this->piVars['catUid']){
				/**
				  * Validate given CAT UID, if is below master_cat
				  **/
				$this->masterCategoryObj = t3lib_div::makeinstance('tx_commerce_category');
				$this->masterCategoryObj -> init($this->master_cat,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
				$this->masterCategoryObj -> load_data();
				$masterCategorySubCategories = $this->masterCategoryObj->get_rec_child_categories_uidlist();
				
				if (in_array($this->piVars['catUid'],$masterCategorySubCategories)) {
			   		 $this->cat = (int)$this->piVars['catUid'];
				}else{
				 /**
				  * Wrong UID, so start with default UID
				  **/
					$this->cat = (int)$this->master_cat;
					$GLOBALS['TSFE']->set_no_cache();
				}
		}else{
			  $this->cat = (int)$this->master_cat;
			  $GLOBALS['TSFE']->set_no_cache();
		}
		
		if ( $this->cat <> $this->category->getUid()){
			/**
			  * Only, if the category has been changed
			  **/
			unset($this->category);
			$this->category=t3lib_div::makeinstance('tx_commerce_category');
			$this->category->init($this->cat,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
		}
		
		
		
		
	    $this->internal['results_at_a_time']= $this->conf['maxRecords'];
		$this->internal['maxPages'] = $this->conf['maxPages'];

	    // Going the long way ??? Just for list view	     
	    $long = 1;

	    switch($this->handle) {
	        case 'singleView' : 
	        		if ($this->initSingleView($this->piVars['showUid'])){
	        		
				   	 $long = 0;
	        		}
	        break; 	    
	    }	
	 
	  
		if($this->cat>0){			
	  	    
		    if(!$this->category->isValidUid($this->category->getUid())){
				unset($this->category);
				$this->category=t3lib_div::makeinstance('tx_commerce_category');
				$this->category->init($this->master_cat,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
		    }
	 	    $this->category->load_data();	
	 	     
	 	    $this->category_array=$this->category->return_assoc_array();
		    $catConf = $this->category->getCategoryTSconfig();
		    if(is_array($catConf['catTS.'])){
			    $this->conf = t3lib_div::array_merge_recursive_overrule($this->conf,$catConf['catTS.']);
			}
		    if($long) {
				$this->category->setPageTitle();
				$this->category->get_child_categories();				
				if ($this->conf['groupProductsByCategory']){
					$this->category_products = $this->category->getAllProducts(0);
			    }elseif ($this->conf['showProductsRecLevel']){				
			  		$this->category_products = $this->category->getAllProducts($this->conf['showProductsRecLevel']);
				}else{
					$this->category_products = $this->category->getAllProducts();
				}
				
			  	if($this->conf['useStockHandling'] == 1) {
			  		$this->category_products = tx_commerce_div::removeNoStockProducts($this->category_products,$this->conf['products.']['showWithNoStock']);
			  	}	
			    $this->internal['res_count'] = count($this->category_products);
	
	    	}
		}else{
			$this->content = $this->cObj->stdWrap($this->conf['emptyCOA'],$this->conf['emptyCOA.']);
			$this->handle = FALSE;
		}	
	}	
	
	/**
	 * Main function called by insert plugin
	 * 
	 * @param string content
	 * @param string Configuration
	 * @return string HTML-Content
	 * 
	 */
	
	function main($content,$conf)	{

		$this->init($conf);

		// get the template
		$this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);

		$this->template = array();
		$this->markerArray = array();
		
		if($this->handle == 'singleView'){
				
		    $this->content = $this->makeSingleView();	
		}elseif($this->handle == 'listView'){	
		    $this->content = $this->makeListView($this->cat);
		}
		
		
		
		return $this->pi_wrapInBaseClass($this->content);
	}
	
	
	/**
	 * Init the singleView for one product
	 * 
	 * @param integer ProductID for single view
	 * @return void
	 * 
	 */

	function initSingleView($prodID){

		if($prodID>0){	
	 	// product
	 		// get not localized Product
	 		
	 		$mainProductRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('l18n_parent','tx_commerce_products','uid='.$prodID);
	 		if($GLOBALS['TYPO3_DB']->sql_num_rows($mainProductRes) == 1 AND $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mainProductRes) AND $row['l18n_parent'] != 0) {
	 			$prodID = $row['l18n_parent'];
	 		}
	 		$this->product=new tx_commerce_product($prodID,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
			$this->product->load_data();	
			if ($this->product->isAccessible()) {
		 		foreach($this->product->articles as $article)
		 		{
				 		$this->article_array=$article->return_assoc_array();
		 	 	}
	
				$this->select_attributes = $this->product->get_attributes(array(ATTRIB_selector));
				#$this->product_attributes = $this->product->get_attributes(array(ATTRIB_produkt));
				$this->product_attributes = $this->product->get_attributes(array(ATTRIB_product));
				$this->can_attributes = $this->product->get_attributes(array(ATTRIB_can));
				$this->shall_attributes = $this->product->get_attributes(array(ATTRIB_shal));
		 		$this->product_array=$this->product->returnAssocArray();
		
	
		     	$this->product->load_articles();
	    		$this->product->setPageTitle();
				$this->master_cat=$this->product->get_masterparent_categorie();
				
				# Write the current page to The session to have a back to last product link
				$GLOBALS["TSFE"]->fe_user->setKey('ses','tx_commerce_lastproducturl',$this->pi_linkTP_keepPIvars_url());
				
			}else{
				// If product ist not valid (url manipulation)
				// go to listview
				$this->handle = 'listView';
				return false;
			}
		
		}
	}
	
	/**
	 * Renders the single view for the current products
	 * @param	object	$prodObject: Product Object
	 * @param	object	$catObject: CategoryObject
	 * @param	string	$subpartrName: A name of a subpart
	 * @param	string	$subpartNameNostock	A name of a subpart dor showing id product with no stock
	 * @return	string	the content for a single product	
	 */
	function renderSingleView($prodObj,$catObj,$subpartName, $subpartNameNostock){																																						  
		$template = $this->cObj->getSubpart($this->templateCode,$subpartName);
		if($this->conf['useStockHandling'] == 1 AND $prodObj->hasStock() === false) {
	  		$typoScript = $this->conf['singleView.']['products.']['nostock.'];
	  		$tempTemplate = $this->cObj->getSubpart($this->templateCode,$subpartNameNostock);
	  		if($tempTemplate != '' ) {
	  			$template = $tempTemplate;
	  		}
		} else {
			$typoScript = $this->conf['singleView.']['products.'];
		}
		
		$content = $this->renderProduct($prodObj,$template,$typoScript,$this->conf['templateMarker.']['basketSingleView.'],$this->conf['templateMarker.']['basketSingleViewMarker']);
		
		// get Category Data
		$catObj->load_Data();	
		// render it in the content
		$category = $this->renderCategory($catObj, '###'.strtoupper($this->conf['templateMarker.']['categorySingleViewMarker']).'###', $this->conf['singleView.']['products.']['categories.'],'ITEM',$content);
    		
		// substitude the subpart
		
		$content=$this->cObj->substituteSubpart($content,'###'.strtoupper($this->conf['templateMarker.']['categorySingleViewMarker']).'###',$category);
		
		
		/**
		 * Build the link to the category
		 * TODO make it possible to have more than one link, to each of the productCategories
		 */
		$linkContent=$this->cObj->getSubpart($content,'###CATEGORY_ITEM_DETAILLINK###');
		$link=$this->pi_linkTP($linkContent,array('catUid'=>$catObj->get_uid()),true);
		$content=$this->cObj->substituteSubpart($content,'###CATEGORY_ITEM_DETAILLINK###',$link);
	
	
	
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['singleview'])) {
		   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['singleview'] as $classRef) {
                         $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
        	    }
    		}
	        foreach($hookObjectsArr as $hookObj)    {
	    	     if (method_exists($hookObj, 'additionalMarker')) {
                	     $markerArray =  $hookObj->additionalMarker($markerArray,$this);
            	    }
		}

		$content = $this->cObj->substituteMarkerArrayCached($content, $markerArray, array(),  array());
		
		return ($content);
	}
	
	
		/**
	 * Makes the rendering for all articles for an given product 
	 * renders different view, based on viewKind and number of articless
	 * 
	 * @TODO	clean up, make it more flexible
	 * @param	string	$viewKind: Kind of view for choosing the right template
	 * @param	array	$conf: TSconfig for handling the articles
	 * @param	object	$prod:	The parent product for returning articless
	 * @return	string	the content for a single product	
	 */
		
	
	function makeArticleView($viewKind,$conf=array(),$prod,$templateMarkerArray = '',$template = ''){

		
	    $hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['articleview'])) {
		   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['articleview'] as $classRef) {
                  $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
              }
            }
	
    	
    	
    	
    	$count = is_array($prod->articles_uids) ? count($prod->articles_uids) : FALSE;
		//do nothing if no articles, BE-user-error, should not happen
		
		if(strlen($template)<1){
			$template = $this->templateCode;			
		}
		
		if(is_array($templateMarkerArray)){
			while(list($k,$v) = each($templateMarkerArray)){
				$templateMarker[] = '###'.strtoupper($v).'###'; 
				$templateMarkerNostock[] = '###'.strtoupper($v).'_NOSTOCK###'; 
			}
			
		}else {
			$templateMarker[] = '###'.strtoupper($this->conf['templateMarker.'][$viewKind.'_produktArticleList']).'###';
			$templateMarker[] = '###'.strtoupper($this->conf['templateMarker.'][$viewKind.'_produktArticleList2']).'###';
			$templateMarkerNostock[] = '###'.strtoupper($this->conf['templateMarker.'][$viewKind.'_produktArticleList']).'_NOSTOCK###';
			$templateMarkerNostock[] = '###'.strtoupper($this->conf['templateMarker.'][$viewKind.'_produktArticleList2']).'_NOSTOCK###';
		}
	
		
		$templateCount = count($templateMarker);
		
		if ($this->conf['templateMarker.'][$viewKind.'_selectAttributes'])	{
			$templateMarkerAttr = '###'.strtoupper($this->conf['templateMarker.'][$viewKind.'_selectAttributes']).'###';
			$templateAttr = $this->cObj->getSubpart($this->templateCode, $templateMarkerAttr);
		}
		if ($this->conf['showHiddenValues']==1)	{
			$showHiddenValues = true;
		
		}else{
			$showHiddenValues = false;
		}
		
		if($count > 1) {
			
			//parse piVars for values and names of selected attributes
			foreach($this->piVars as $key=>$val) {
				if (strstr($key,'attsel_') && $val) {
					$arrAttNames[] = substr($key, 7);
					$arrAttValues[] = $val;
					$attributeArray[] = array('AttributeUid'=> substr($key, 7),'AttributeValue'=>$val);
				}
			}
			if (is_array($arrAttNames)) {
				$articles_uids = $prod->get_Articles_by_AttributeArray($attributeArray,1);
				//TODO: for now we put this so it looks like working, check workflow and results
				if(count($this->select_attributes)>1){
					$attributeArray = $prod->get_selectattribute_matrix($articles_uids, $this->select_attributes,$showHiddenValues);
				}else{
					$attributeArray = $prod->get_selectattribute_matrix($articles_uids, $this->select_attributes,$showHiddenValues);
				}
				
			} else {
				$articles_uids = $prod->getArticleUids();
				$attributeArray = $prod->get_selectattribute_matrix($articles_uids, $this->select_attributes,$showHiddenValues);
				$articles_uids[0] = $prod->articles_uids[0];
			}
			
			if($this->conf['allArticles']){
				
				//TODO: correct like this?
				for ($i=0;$i<$count;$i++) {
	
					$attributeArray = $prod->get_Atrribute_Matrix(array($prod->articles_uids[$i]), $this->select_attributes,$showHiddenValues);
					
	        	    if(is_array($attributeArray)) {
						$attCode = '';
	                   	foreach($attributeArray as $attribute_uid => $myAttribute) {
	                   		
	                        $attributeObj = new tx_commerce_attribute($attribute_uid,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
	                      	$attributeObj->load_data();
	                        $markerArray["###SELECT_ATTRIBUTES_TITLE###"] = $myAttribute['title'];
			        		$markerArray["###SELECT_ATTRIBUTES_ICON###"] = $myAttribute['icon'];
		
							list($k,$v) = each($myAttribute['values']);
							$markerArray["###SELECT_ATTRIBUTES_VALUE###"] = $v;
							$markerArray["###SELECT_ATTRIBUTES_UNIT###"] = $myAttribute['unit'];
							
							$attCode .= $this->cObj->substituteMarkerArrayCached($templateAttr, $markerArray , array());
						}
	     			}
			
					$markerArray = $this->getArticleMarker($prod->articles[$prod->articles_uids[$i]]);			
					$markerArray['SUBPART_ARTICLE_ATTRIBUTES'] = $this->makeArticleAttributList($prod,array($prod->articles_uids[$i]));
					$markerArray['ARTICLE_SELECT_ATTRIBUTES'] = $attCode;

					foreach($hookObjectsArr as $hookObj)    {
						if (method_exists($hookObj, 'additionalMarker')) {
						      $markerArray =  $hookObj->additionalMarker($markerArray,$this,$prod->articles[$prod->articles_uids[$i]]);
					       }
					}

					$template_att = $this->cObj->getSubpart($template, $templateMarker[($i%$templateCount)]);
					if($this->conf['useStockHandling'] == 1 AND $prod->articles[$prod->articles_uids[$i]]->getStock() <= 0) {
						$tempTemplate = $this->cObj->getSubpart($template, $templateMarkerNostock[($i%$templateCount)]);
						if($tempTemplate != '' ) {
							$template_att = $tempTemplate;
						}
					} 
					$content.= $this->cObj->substituteMarkerArray($template_att, $markerArray,'###|###',1);
									 
								            
				}

	    	}else{
			
				if(is_array($attributeArray)) {
					$attCode = '<form name="attList_'.$prod->get_uid().'" id="attList_'.$prod->get_uid().'" action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id, '_self', array($this->prefixId.'[catUid]'=>$this->piVars['catUid'],$this->prefixId.'[showUid]'=>$this->piVars['showUid'])).'"  method="post"><input type="hidden" name="no_cache" value="1" />';
					foreach($attributeArray as $attribute_uid => $myAttribute) {
						$attributeObj = new tx_commerce_attribute($attribute_uid,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
						$attributeObj->load_data();
						$attCode.= $myAttribute['title'].': '; 
						$attCode.= '<select onchange="document.getElementById(\'attList_'.$prod->get_uid().'\').submit();" name="'.$this->prefixId.'[attsel_'.$attribute_uid.']" '.$this->conf['selectAttributesParams'].'><option value="">'.$this->pi_getLL('all_options','all',1).'</option>'."\n";
						foreach($myAttribute['values'] as $key => $val) {
							$attCode.='<option value="'.$key.'"'.($key == $this->piVars['attsel_'.$attribute_uid] ? 'selected="selected"' : '').'>'."\n".
							$val.'</option>';
						}
						$attCode.= '</select>'."\n";
						$attCode.= ' '.$myAttribute['unit'].'<br />'; 
					}
					$attCode.= '</form>';
				}

				// TODO: correct like this?
				if(is_array($articles_uids)){
					$artId = $articles_uids[0];
				}else{
					$artId = $prod->articles_uids[0];
				}
				
				$markerArray = $this->getArticleMarker($prod->articles[$artId]);			
				$markerArray['SUBPART_ARTICLE_ATTRIBUTES'] = $this->makeArticleAttributList($prod,array($artId));		
				$markerArray['ARTICLE_SELECT_ATTRIBUTES'] = $attCode;
				foreach($hookObjectsArr as $hookObj)    {
					if (method_exists($hookObj, 'additionalMarker')) {
						$markerArray =  $hookObj->additionalMarker($markerArray,$this,$prod->articles[$artId]);
					}
		    	}
		    		
				$template_att = $this->cObj->getSubpart($template, $templateMarker[0]);
				if($this->conf['useStockHandling'] == 1 AND $prod->articles[$artId]->getStock() <= 0) {
					$tempTemplate = $this->cObj->getSubpart($template, $templateMarkerNostock[0]);
					if($tempTemplate != '' ) {
						$template_att = $tempTemplate;
					}
				} 
				$content.= $this->cObj->substituteMarkerArray($template_att, $markerArray,'###|###',1);
	
			 
	    	    }
			} elseif($count==1){
		    	
	                  $attributeArray = $prod->get_Atrribute_Matrix(array($prod->articles_uids[0]),$this->select_attributes,$showHiddenValues);
								      
                      if(is_array($attributeArray)) {
	                      $attCode = '';
	                      foreach($attributeArray as $attribute_uid => $myAttribute) {
	                          $attributeObj = new tx_commerce_attribute($attribute_uid,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
                              $attributeObj->load_data();
                              $markerArray["###SELECT_ATTRIBUTES_TITLE###"] = $myAttribute['title'];
                              $markerArray["###SELECT_ATTRIBUTES_ICON###"] = $myAttribute['icon'];
                              list($k,$v) = each($myAttribute['values']);
                              $markerArray["###SELECT_ATTRIBUTES_VALUE###"] = $v;
                              $markerArray["###SELECT_ATTRIBUTES_UNIT###"] = $myAttribute['unit'];
	                          $attCode .= $this->cObj->substituteMarkerArrayCached($templateAttr,$markerArray , array());
	                      }	
                      }																																																																							      
		
						$prod->load_articles();
						
						$markerArray = $this->getArticleMarker($prod->articles[$prod->articles_uids[0]]);			
						$markerArray['SUBPART_ARTICLE_ATTRIBUTES'] = $this->makeArticleAttributList($prod,array($prod->articles_uids[0]));			
						$markerArray['ARTICLE_SELECT_ATTRIBUTES'] = '';
						
						foreach($hookObjectsArr as $hookObj)    {
				    	    	if (method_exists($hookObj, 'additionalMarker')) {
						              $markerArray =  $hookObj->additionalMarker($markerArray,$this,$prod->articles[$prod->articles_uids[0]]);
				        	    }
			    		}
             
						$template_att = $this->cObj->getSubpart($template, $templateMarker[0]);
						if($this->conf['useStockHandling'] == 1 AND $prod->articles[$prod->articles_uids[0]]->getStock() <= 0) {
				  			$tempTemplate = $this->cObj->getSubpart($template, $templateMarkerNostock[0]);
				  			if($tempTemplate != '' ) {
				  				$template_att = $tempTemplate;
				  			}
						} 
						$content.= $this->cObj->substituteMarkerArray($template_att, $markerArray,'###|###',1);
				 
		}
		
		return $content;
		
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/pi1/class.tx_commerce_pi1.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/commerce/pi1/class.tx_commerce_pi1.php"]);
}

?>
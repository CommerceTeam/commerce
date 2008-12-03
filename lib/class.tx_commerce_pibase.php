<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Volker Graubaum <vg@e-netconsulting.de>
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
 *
 *
 * @author	Volker Graubaum <vg@e-netconsulting.de>
 * @author	Franz Ripfel <fr@abezet.de>
 * @author  Ingo Schmitt <is@markeing-factory.de>
 * 
 * $Id$
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * tx_commerce includes
 */
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_product.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_category.php');
require_once (t3lib_extMgm::extPath('moneylib').'class.tx_moneylib.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_div.php');

class tx_commerce_pibase extends tslib_pibase {

	var $extKey = 'commerce';	// The extension key.
	var $imgFolder = '';
	var $showCurrency = true; // extension to moneylib, if currency should be put out
	var $currency = 'EUR';  // currency if no currency is set otherwise

	/**
 	 * Holds the merged Array Langmarkers from locallang
 	 * @var Array
 	 *
 	 */

	 var $languageMarker = array();

	/**
 	 * holds the basketItemHash for making the whole shop cachable
 	 * @var char
 	 *
 	 */

	var $basketHashValue = false;

	/**
	 * Standard Init Method for all
	 * pi plugins fro tx_commerce
	 * @param 	array	$conf
	 */

	  /**
     * @var	integer	[0-1]	
     * @access private
     */
    var $useRootlineInformationToUrl = 0;
    
	/**
 	* Category UID for rendering
 	*
 	* @var integer
 	*/
    
    var $cat;
    
	function init($conf){



	    $this->conf = $conf;
	    $this->pi_setPiVarDefaults();
	    $this->pi_loadLL();
	    $this->pi_initPIflexForm();
	    
		tx_commerce_div::initializeFeUserBasket();
	    
	    $this->basketHashValue = $GLOBALS['TSFE']->fe_user->tx_commerce_basket->getBasketHashValue();
	    $this->piVars['basketHashValue'] = $this->basketHashValue;
	    $this->imgFolder = 'uploads/tx_commerce/';
	    $this->addAdditionalLocallang();
	    
	    $this->generateLanguageMarker();
	    if (empty($this->conf['templateFile'])) {
	  		return $this->error('init',__LINE__,'Template File not defined in TS: ');
	  	}
	    $this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
	  	if (empty($this->templateCode)) {
	  		return $this->error('init',__LINE__,"Template File not loaded, maybe it doesn't exist: ".$this->conf['templateFile']);
	  	}		
	    if ($this->conf['useRootlineInformationToUrl']) {
			$this->useRootlineInformationToUrl = $this->conf['useRootlineInformationToUrl'];
		}

	}
	
	/**
	 * Getting additional locallang-files through an Hook
	 */
	function addAdditionalLocallang() {
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['locallang'])){
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['locallang'] as $classRef){
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
  
        foreach($hookObjectsArr as $hookObj)   {
			if (method_exists($hookObj, 'loadAdditionalLocallang')) {
             	$hookObj->loadAdditionalLocallang($this);
			}
        }
	}
	
	
	/**
	 * Gets all "lang_ and label_" Marker for substition with substituteMarkerArray
	 * @since 10.02.06 Changed to XML
	 * @coauthor Frank Kroeber <fk@marketing-factory.de>
	 * @return	void
	 */
	function generateLanguageMarker(){
		if (
		(is_array($this->LOCAL_LANG[$GLOBALS['TSFE']->tmpl->setup['config.']['language']]))
		&&
		(is_array($this->LOCAL_LANG['default']))
		){
			$markerArr = t3lib_div::array_merge($this->LOCAL_LANG['default'],$this->LOCAL_LANG[$GLOBALS['TSFE']->tmpl->setup['config.']['language']]);
		}elseif (is_array($this->LOCAL_LANG['default']))
		{
			$markerArr=$this->LOCAL_LANG['default'];
		}else{
			$markerArr=$this->LOCAL_LANG[$GLOBALS['TSFE']->tmpl->setup['config.']['language']];
		}
		foreach ($markerArr as $k => $v) {
			if(stristr($k,'lang_') OR stristr($k,'label_')) {
				$this->languageMarker['###'.strtoupper($k).'###'] = $this->pi_getLL($k);
			}
		}
	}


	/**
	 * Renders Product Attribute List from given product, with possibility to
	 * define a number of templates for interations.
	 * when defining 2 templates you have an odd / even layout
	 * @param	object	$prodObj: Product Object
	 * @param	array	[optional] $subpartNameArray: array of suppart Names
	 * @param	array	[optional]	$TS Configuration
	 * @return	string	HTML-Output rendert
	 */

	function renderProductAttributeList($prodObj,$subpartNameArray=array(),$TS=false){
		if ($TS ==false) {
			$TS = $this->conf['singleView.']['attributes.'];
		}
		
		foreach ($subpartNameArray as $oneSubpartName)	{
			$templateArray[]=$this->cObj->getSubpart($this->templateCode, $oneSubpartName);
		}
		
		if(!$this->product_attributes){
			$this->product_attributes = $prodObj->get_attributes(array(ATTRIB_product));
		}

		// not needed write now, lets see later
		if ($this->conf['showHiddenValues']==1)	{
			$showHiddenValues = true;

		}	else	{
			$showHiddenValues = false;
		}

  		#$matrix = $prodObj->get_atrribute_matrix('',$this->product_attributes,$showHiddenValues);
  		$matrix = $prodObj->get_product_attribute_matrix($this->product_attributes,$showHiddenValues);

		$i = 0;
		if (is_array($this->product_attributes)){
            foreach ($this->product_attributes as $myAttributeUid) {
				  	if(!$matrix[$myAttributeUid]['values'][0] && $this->conf['hideEmptyProdAttr']){
						continue;
			  	  	}
		  			if($i==count($templateArray)){
	                      $i = 0;
	                }
	                /**
	                 * @Since 2006.07.13
	                 * Output for Attribute Icons
	                 * @author Joerg Sprung <jsp@marketing-factory.de>
	                 */
	                $datas = array (
	                	'title' => $matrix[$myAttributeUid]['title'],
	                	'value' => $this->formatAttributeValue($matrix,$myAttributeUid),
	                	'unit'	=> $matrix[$myAttributeUid]['unit'],
	                	'icon'	=> $matrix[$myAttributeUid]['icon'],
	                );
	               
	                $markerArray = $this->generateMarkerArray($datas,$TS,$prefix='PRODUCT_ATTRIBUTES_');
					$marker['PRODUCT_ATTRIBUTES_TITLE'] = $matrix[$myAttributeUid]['title'];
					$product_attributes = $this->cObj->substituteMarkerArray($templateArray[$i],$markerArray,'###|###',1);
	                $product_attributes_string.= $this->cObj->substituteMarkerArray($product_attributes,  $marker,'###|###',1);
            		$i++;

        	 }
    	     return $this->cObj->stdWrap($product_attributes_string,$TS);
		}
		return '';
	}




	/**
	 * Renders HTML output with list of attribute from a given product, reduced for some articles
	 * if article ids are givens
	 * with possibility to
	 * define a number of templates for interations.
	 * when defining 2 templates you have an odd / even layout
	 *
	 * @TODO	Real alternative Layout
	 * @param	object	$prodObj: Object for the current product, the attributes are taken from
	 * @param	array	$article: array with articleIds for filtering attributss
	 * @param 	array	$subpartNameArray: array of suppart Names
	 * @return	string	Stringoutput for attributes
	 *
	 */


	function renderArticleAttributeList(&$prodObj,$articleId =array(),$subpartNameArray=array()){

		foreach ($subpartNameArray as $oneSubpartName)	{
			$tmpCode= $this->cObj->getSubpart($this->templateCode, $oneSubpartName);
			if (strlen($tmpCode)> 0) {
				$templateArray[]= $tmpCode;
			}
			
		}


		if ($this->conf['showHiddenValues']==1)	{
			$showHiddenValues = true;

		}else{
			$showHiddenValues = false;
		}


		$this->can_attributes = $prodObj->get_attributes(array(ATTRIB_can));
		$this->shall_attributes = $prodObj->get_attributes(array(ATTRIB_shal));
		
		
  		$matrix = $prodObj->get_atrribute_matrix($articleId,$this->shall_attributes,$showHiddenValues);
	 	$i = 0;
		if(is_array($this->shall_attributes)){
          	 foreach ($this->shall_attributes as $myAttributeUid) {
          	 	
			    if(!$matrix[$myAttributeUid]['values'][0] && $this->conf['hideEmptyShalAttr']||!$matrix[$myAttributeUid]){
					continue;
		      	}
        	    if($i==count($templateArray)){
	            	     $i = 0;
      	        }
      	        /**
                 * @Since 2006.07.18
                 * Output for Attribute Icons
                 * @author Joerg Sprung <jsp@marketing-factory.de>
                 */
                $datas = array (
                	'title' => $matrix[$myAttributeUid]['title'],
                	'value' => $this->formatAttributeValue($matrix,$myAttributeUid),
                	'unit'	=> $matrix[$myAttributeUid]['unit'],
                	'icon'	=> $matrix[$myAttributeUid]['icon'],
                );
    	        $markerArray = $this->generateMarkerArray($datas,$this->conf['singleView.']['attributes.'],$prefix='ARTICLE_ATTRIBUTES_');
		    	$marker['ARTICLE_ATTRIBUTES_TITLE'] = $matrix[$myAttributeUid]['title'];

  	            $article_shalAttributes_string .= $this->cObj->substituteMarkerArray($templateArray[$i],$markerArray,'###|###',1);
//        	    $markerArray["###ARTICLE_ATTRIBUTES_TITLE###"] = $matrix[$myAttributeUid]['title'];
//	            $markerArray["###ARTICLE_ATTRIBUTES_VALUE###"] =  $this->formatAttributeValue($matrix,$myAttributeUid);
//    	        $markerArray["###ARTICLE_ATTRIBUTES_UNIT###"] = $matrix[$myAttributeUid]['unit'];
//        	    $article_shalAttributes_string.= $this->substituteMarkerArrayNoCached($templateArray[$i], $markerArray , array());
        	    $i++;

		 	}
	    }

		$article_shalAttributes_string = $this->cObj->stdWrap($article_shalAttributes_string,$this->conf['articleShalAttributsWrap.']) ;

        $matrix = $prodObj->get_atrribute_matrix($articleId,$this->can_attributes,$showHiddenValues);
		
		$i = 0;
		if(is_array($this->can_attributes)){
              foreach ($this->can_attributes as $myAttributeUid) {
				  if(!$matrix[$myAttributeUid]['values'][0] && $this->conf['hideEmptyCanAttr']||!$matrix[$myAttributeUid]){

					continue;
				  }
                  if($i==count($templateArray)){
                      $i = 0;
                  }

                 /**
                  * @Since 2006.07.18
                  * Output for Attribute Icons
                  * @author Joerg Sprung <jsp@marketing-factory.de>
                  */
                 $datas = array (
                	'title' => $matrix[$myAttributeUid]['title'],
                	'value' => $this->formatAttributeValue($matrix,$myAttributeUid),
                	'unit'	=> $matrix[$myAttributeUid]['unit'],
                	'icon'	=> $matrix[$myAttributeUid]['icon'],
                 );
                 $markerArray = $this->generateMarkerArray($datas,$this->conf['singleView.']['attributes.'],$prefix='ARTICLE_ATTRIBUTES_');
  	             $marker['ARTICLE_ATTRIBUTES_TITLE'] = $matrix[$myAttributeUid]['title'];

  	             $article_canAttributes_string .= $this->cObj->substituteMarkerArray($templateArray[$i],$markerArray,'###|###',1);

                 $i++;

//                 $markerArray["###ARTICLE_ATTRIBUTES_TITLE###"] = $matrix[$myAttributeUid]['title'];
//                 $markerArray["###ARTICLE_ATTRIBUTES_VALUE###"]=   $this->formatAttributeValue($matrix,$myAttributeUid);
//                 $markerArray["###ARTICLE_ATTRIBUTES_UNIT###"] = $matrix[$myAttributeUid]['unit'];;
//                 $article_canAttributes_string.= $this->substituteMarkerArrayNoCached($templateArray[$i],$markerArray , array());

              }
		}
		$article_canAttributes_string = $this->cObj->stdWrap($article_canAttributes_string,$this->conf['articleCanAttributsWrap.']) ;
	
		$article_attributes_string = $this->cObj->stdWrap($article_shalAttributes_string.$article_canAttributes_string,$this->conf['articleAttributsWrap.']) ;
		$article_attributes_string = $this->cObj->stdWrap($article_attributes_string,$this->conf['singleView.']['attributes.']['stdWrap.']);
		
    	return $article_attributes_string.' ';
	}



	/**
	 * Makes the list view for the current categorys
	 *
	 * @TODO	clean up, make it more flexibles
	 * @return	string	the content for the list view
	 */


	function makeListView(){

		/**
		 * Category LIST
		 *
		 */
		$categoryOutput='';

		$this->template=$this->templateCode;
		/*
		 * @TODO own function for recursive Call
		 */
		
		
		if ($this->category->has_subcategories ( )){
			foreach ($this->category->categories as $categoryUid => $oneCategory)	{

				$oneCategory->load_Data();
				
				$linkArray['catUid']=$oneCategory->getUid();
				if ($this->useRootlineInformationToUrl == 1) {
					$linkArray['path']=$this->getPathCat($oneCategory);
					$linkArray['mDepth']=$this->mDepth;
				}else{
					$linkArray['mDepth'] = '';
					$linkArray['path'] = '';
				}
				/**
				 * Since 29.09.2006 -> Added Hash for basket to array
				 */
				
				if($this->basketHashValue){
					$linkArray['basketHashValue'] = $this->basketHashValue;
				}	
				/**
				 *  Build TS for Linking the Catergory Images
				 */
				$lokalTS = $this->conf['categoryListView.']['categories.'];
				// check if no TYPOLink is already in TS
					
				if ($this->conf['overridePid']) {
					$typoLinkConf['parameter']=$this->conf['overridePid'];
				}else{
					$typoLinkConf['parameter']=$this->pid;
				}
				$typoLinkConf['useCacheHash'] = 1;
				$typoLinkConf['additionalParams'] = ini_get('arg_separator.output').$this->prefixId.'[catUid]='.$oneCategory->getUid();
				if ($this->useRootlineInformationToUrl == 1) {
					$typoLinkConf['additionalParams'].= ini_get('arg_separator.output').$this->prefixId.'[path]='.$this->getPathCat($oneCategory);
					$typoLinkConf['additionalParams'].= ini_get('arg_separator.output').$this->prefixId.'[mDepth]='.$this->mDepth;
				}
				if($this->basketHashValue){
					$typoLinkConf['additionalParams'].= ini_get('arg_separator.output').$this->prefixId.'[basketHashValue]='.$this->basketHashValue;
				}
				$lokalTS['fields.']['images.']['stdWrap.']['typolink.'] = $typoLinkConf;
					
				$lokalTS = $this->addTypoLinkToTS($lokalTS,$typoLinkConf);
				
				
				
				
				$tmpCategory=$this->renderCategory($oneCategory, '###CATEGORY_LIST_ITEM###', $lokalTS,'ITEM');

				/**
				 * Build the link
				 * @depricated
				 * Please use TYPOLINK instead
				 */
				$linkContent=$this->cObj->getSubpart($tmpCategory,'###CATEGORY_ITEM_DETAILLINK###');
				if ($linkContent) {
					$link=$this->pi_linkTP_keepPIvars($linkContent,$linkArray,true,0,$this->conf['overridePid']);
				}else{
					$link = '';
				}
				
				$tmpCategory=$this->cObj->substituteSubpart($tmpCategory,'###CATEGORY_ITEM_DETAILLINK###',$link);

				
				if($this->conf['groupProductsByCategory'] && !$this->conf['hideProductsInList']){

					$categoryProducts = $oneCategory->getAllProducts();
					if($this->conf['useStockHandling'] == 1) {
			  			$categoryProducts = tx_commerce_div::removeNoStockProducts($categoryProducts,$this->conf['products.']['showWithNoStock']);
			  		}
					$categoryProducts = array_slice($categoryProducts,0,$this->conf['numberProductsInSubCategory']);
					$productList = $this->renderProductsForList($categoryProducts,$this->conf['templateMarker.']['categoryProductList.'],$this->conf['templateMarker.']['categoryProductListIterations']);

				/**
				 * Insert the Productlist
				 */

					$tmpCategory=$this->cObj->substituteSubpart($tmpCategory,'###CATEGORY_ITEM_PRODUCTLIST###',$productList);

				}else{
					$tmpCategory=$this->cObj->substituteMarker($tmpCategory,'###CATEGORY_ITEM_PRODUCTLIST###','');
				}
				$categoryOutput.=$tmpCategory;

			}
		}

		$categoryListSubpart= $this->cObj->getSubpart($this->template,'###CATEGORY_LIST###');
		$markerArray['CATEGORY_SUB_LIST'] = $this->cObj->substituteSubpart($categoryListSubpart,'###CATEGORY_LIST_ITEM###',$categoryOutput);
		$startPoint = ($this->piVars['pointer']) ? $this->internal['results_at_a_time']*$this->piVars['pointer'] : 0;


		// Display TopProducts???
		// for this, make a few basicSettings for pageBrowser

		$internalStartPoint = $startPoint;
		$internalResults = $this->internal['results_at_a_time'] ;

		// set Empty default

		$markerArray['SUBPART_CATEGORY_ITEMS_LISTVIEW_TOP'] = '';

		if((!$this->conf['groupProductsByCategory']) && $this->conf['displayTopProducts'] && $this->conf['numberOfTopproducts']){

	 		$this->top_products = array_slice($this->category_products,$startPoint,$this->conf['numberOfTopproducts']);
			$internalStartPoint = $startPoint + $this->conf['numberOfTopproducts'];
			$internalResults  =  $this->internal['results_at_a_time'] -  $this->conf['numberOfTopproducts'];

			//###CATEGORY_ITEMS_LISTVIEW_1###
			$templateMarker = '###'.strtoupper($this->conf['templateMarker.']['categoryProductListTop']).'###';
			$category_items_listview_1 = '';
			$markerArray['SUBPART_CATEGORY_ITEMS_LISTVIEW_TOP'] = $this->renderProductsForList($this->top_products,$this->conf['templateMarker.']['categoryProductListTop.'],$this->conf['templateMarker.']['categoryProductListTopIterations'],$this->conf['topProductTSMarker']);


		}


		// ###########    product list    ######################
		if (is_array($this->category_products)){
     		$this->category_products = array_slice($this->category_products,$internalStartPoint, $internalResults);
		}

	 	#$this->category_products = array_slice($this->category_products,$internalStartPoint,$internalResults);

		// ###CATEGORY_LIST###

		//###CATEGORY_ITEMS_LISTVIEW_1###
		$templateMarker = '###'.strtoupper($this->conf['templateMarker.']['categoryProductList']).'###';

		$category_items_listview_1 = '';
		
		if(!$this->conf['hideProductsInList']){
			# Write the current page to The session to have a back to last product link
			$GLOBALS["TSFE"]->fe_user->setKey('ses','tx_commerce_lastproducturl',$this->pi_linkTP_keepPIvars_url());
			$markerArray['SUBPART_CATEGORY_ITEMS_LISTVIEW'] = $this->renderProductsForList($this->category_products,$this->conf['templateMarker.']['categoryProductList.'],$this->conf['templateMarker.']['categoryProductListIterations']);
		}
	

		//###CATEGORY_VIEW_DISPLAY###
		$templateMarker = '###'.strtoupper($this->conf['templateMarker.']['categoryView']).'###';


		$markerArrayCat =  $this->generateMarkerArray($this->category->returnAssocArray(),$this->conf['singleView.']['categories.'],'category_','tx_commerce_categories');
		$markerArray = array_merge($markerArrayCat,$markerArray);
		
	
		/*
		 * @TODO Track this issue down
		 * 
		 * The pibase pagebrowser checks if all given GET Parametres Values are Interger and are lower than 5
		 * This is done in the pi_isOnlyFields. Check why this only is valid for integer and only working for values
		 * less than 4
		 */
		
		if(($this->conf['showPageBrowser']==1) && (is_array($this->conf['pageBrowser.']['wraps.']))){
			$this->internal['pagefloat']=(int)$this->piVars['pointer'];
			$this->internal['dontLinkActivePage'] = $this->conf['pageBrowser.']['dontLinkActivePage'];
			$this->internal['showFirstLast'] = $this->conf['pageBrowser.']['showFirstLast'];
			$this->internal['showRange'] = $this->conf['pageBrowser.']['showRange'];
			if ($this->conf['pageBrowser.']['hscText'] != 1) {
				$hscText = 0;
			} else {
				$hscText = 1;
			}
			$markerArray['CATEGORY_BROWSEBOX'] = $this->pi_list_browseresults($this->conf['pageBrowser.']['showItemCount'],$this->conf['pageBrowser.']['tableParams.'],$this->conf['pageBrowser.']['wraps.'],'pointer',$hscText);
		}else{
			$markerArray['CATEGORY_BROWSEBOX'] = '';
		}

	        $hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['listview'])) {
		   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['listview'] as $classRef) {
                         $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
                  }
               }
                foreach($hookObjectsArr as $hookObj)    {
		         if (method_exists($hookObj, 'additionalMarker')) {
		                  $markerArray =  $hookObj->additionalMarker($markerArray,$this);
                       }
	        }
		$markerArray=$this->addFormMarker($markerArray);
		
		$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);
		$content = $this->cObj->substituteMarkerArray($template, $markerArray ,'###|###',1);
		$content = $this->cObj->substituteMarkerArray($content,$this->languageMarker);
		return $content;
	}

	function makeNaviLink($cat){


	}
	function getPathCat($cat) {
		$active=array();
		$rootline = $cat->get_categorie_rootline_uidlist();
		array_pop($rootline);
		$active=array_reverse($rootline);
		$this->mDepth=0;
		foreach($active as $actCat) {
			if (!isset($path)){
				$path=$actCat;
			}
			else{
			 $path.=','.$actCat;
			 $this->mDepth++;
			}

		}
		return $path;
	}

	/**
	 * Renders the Article Marker and all additional informations needed for a basket form
	 * This Method will not replace the Subpart, you have to replace your subpart in your template
	 * by you own
	 * @param  article	Article Object the marker based on
	 * @param  priceid	boolean	if set tu true (default) the price-id will berendered into the hiddenfields, otherwhise not
	 * @return $markerArray Array with all marker needed for the article and the basket form
	 * @author Volker Graubaum <vg_typo3@e-netconsulting.de>
	 */

	function getArticleMarker($article, $priceid=true){

		$tsconf=$this->conf['singleView.']['articles.'];
		$markerArray = $this->generateMarkerArray($article->returnAssocArray(),$tsconf,'article_','tx_commerce_article');
		
		if ($article->getSupplierUid()) {
			$markerArray['ARTICLE_SUPPLIERNAME'] = $article->getSupplierName();
		}else {
			$markerArray['ARTICLE_SUPPLIERNAME']= '';
		}
		
		/**
		 * STARTFRM and HIDDENFIELDS are old marker, used bevor Version 0.9.3
		 * Still existing for compatibility reasons
		 * 
		 * Please use ARTICLE_HIDDENFIEDLS, ARTICLE_FORMACTION and ARTICLE_FORMNAME, ARTICLE_HIDDENCATUID
		 * 
		 **/
		$markerArray['STARTFRM'] = '<form name="basket_'.$article->getUid().'" action="'.$this->pi_getPageLink($this->conf['basketPid']).'" method="post">';
		$markerArray['HIDDENFIELDS'] = '<input type="hidden" name="'.$this->prefixId.'[catUid]" value="'.$this->cat.'" />';
		$markerArray['ARTICLE_FORMACTION'] = $this->pi_getPageLink($this->conf['basketPid']);
		$markerArray['ARTICLE_FORMNAME'] = 'basket_'.$article->getUid();
		$markerArray['ARTICLE_HIDDENCATUID'] = '<input type="hidden" name="'.$this->prefixId.'[catUid]" value="'.$this->cat.'" />';
		$markerArray['ARTICLE_HIDDENFIELDS'] = '';
		/**
   		  * Bild Link to put one of this article in basket
   		  * 
   		  **/
		if ($tsconf['addToBasketLink.']) {
			$typoLinkConf=$tsconf['addToBasketLink.'];
		}
		$typoLinkConf['parameter'] = $this->conf['basketPid'];
		$typoLinkConf['useCacheHash'] = 1;
		$typoLinkConf['additionalParams'].= ini_get('arg_separator.output').$this->prefixId.'[catUid]='.$this->cat;
	
		if ($priceid==true) {
			$markerArray['ARTICLE_HIDDENFIELDS'] .='<input type="hidden" name="'.$this->prefixId.'[artAddUid]['.$article->getUid().'][price_id]" value="'.$article->get_article_price_uid().'" />';
			$markerArray['HIDDENFIELDS'] .= '<input type="hidden" name="'.$this->prefixId.'[artAddUid]['.$article->getUid().'][price_id]" value="'.$article->get_article_price_uid().'" />';
			$typoLinkConf['additionalParams'] .= ini_get('arg_separator.output').$this->prefixId.'[artAddUid]['.$article->getUid().'][price_id]='.$article->get_article_price_uid();
		}else{
			$markerArray['HIDDENFIELDS'] .= '<input type="hidden" name="'.$this->prefixId.'[artAddUid]['.$article->getUid().'][price_id]" value="" />';
			$markerArray['ARTICLE_HIDDENFIELDS'] .='<input type="hidden" name="'.$this->prefixId.'[artAddUid]['.$article->getUid().'][price_id]" value="" />';	
			$typoLinkConf['additionalParams'] .= ini_get('arg_separator.output').$this->prefixId.'[artAddUid]['.$article->getUid().'][price_id]=';
		}
		$typoLinkConf['additionalParams'] .= ini_get('arg_separator.output').$this->prefixId.'[artAddUid]['.$article->getUid().'][count]=1';
		
		$markerArray['LINKTOPUTINBASKET'] = $this->cObj->typoLink($this->pi_getLL('lang_addtobasketlink'),$typoLinkConf);
		
		$markerArray['QTY_INPUT_VALUE'] = $this->getArticleAmount($article->getUid(),$tsconf);
		$markerArray['QTY_INPUT_NAME'] = $this->prefixId.'[artAddUid]['.$article->getUid().'][count]';
		$markerArray['ARTICLE_NUMBER'] = $article->get_ordernumber();
		$markerArray['ARTICLE_ORDERNUMBER'] = $article->get_ordernumber();
		
		$markerArray['ARTICLE_PRICE_NET'] = tx_moneylib::format($article->get_price_net(),$this->currency);
		$markerArray['ARTICLE_PRICE_GROSS'] = tx_moneylib::format($article->get_price_gross(),$this->currency);
		$markerArray['DELIVERY_PRICE_NET'] = tx_moneylib::format($article->getDeliveryCostNet(),$this->currency);
		$markerArray['DELIVERY_PRICE_GROSS'] = tx_moneylib::format($article->getDeliveryCostGross(),$this->currency);
		
		
		$hookObjectsArr = array();
        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['articleMarker'])){
                 foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['articleMarker'] as $classRef){
                              $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
                      }
            }
  
            foreach($hookObjectsArr as $hookObj)   {
                  if (method_exists($hookObj, 'additionalMarkerArticle')) {
                       $markerArray =  $hookObj->additionalMarkerArticle($markerArray,$article,$this);
               }
        }
		
    


		return $markerArray;
	}

	/**
	 * Basker and Checkout Methods
	 */

	/**
	 * Renders on Adress in the template
	 * This Method will not replace the Subpart, you have to replace your subpart in your template
	 * by you own
	 * @param Address Array (als Resultset from Select DB or Session)
	 * @param Subpart Template subpart
	 * @return $content HTML-Content from the given Subpart.
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 */
	 function makeAdressView($addressArray,$subpartMarker){

	 	$markerArray=array();
	 	$template = $this->cObj->getSubpart($this->templateCode, $subpartMarker);

	 	$content=$this->cObj->substituteMarkerArray($template,$addressArray,'###|###',1);

	 	return $content;
	 }

	 /**
	  * Renders the given Basket to the Template
	  * This Method will not replace the Subpart, you have to replace your subpart in your template
	  * by you own
	  * @param BasketObj Basket Object
	  * @param Subpart Template Subpart
	  * @param array of articletypes
	  * @return $content HTML-Ccontent from the given Subpart
	  * @author Ingo Schmitt <is@marketing-factory.de>
	  */

	 function makeBasketView($basketObj,$subpartMarker,$articletypes=false,$lineTemplate = '###LISTING_ARTICLE###') {
	 	$content='';
	 	$template = $this->cObj->getSubpart($this->templateCode, $subpartMarker);
		
	 
	 	
		if(!is_array($lineTemplate)) {
			$temp = $lineTemplate;
			$lineTemplate = array();
			$lineTemplate[] = $temp;
		}else{
			/**
			 * Check if the subpart is existing, and if not, remove from array
			 */
			$tmpArray=array();
			foreach($lineTemplate as $subpartMarker) {
				$test = $this->cObj->getSubpart($template, $subpartMarker);
				if (!empty($test)) {
					$tmpArray[]=$subpartMarker;
				}
			}
			$lineTemplate = $tmpArray;
			unset($tmpArray);
		}
		
	 	$templateElements = count($lineTemplate);
		if ($templateElements > 0) {
		 	/**
		 	 * Get All Articles in this basket and genarte HTMl-Content per row
		 	 *
		 	 */
		 	 $articleLines='';
		 	 $count = 0;
		 	foreach ($basketObj->basket_items as $ArticleUid => $itemObj)
		 	{
		 		$part = $count % $templateElements;
		 		/**
		 		 * Only if valid parameter
		 		 */
		 		if (($articletypes) && (is_array($articletypes)) && (count($articletypes)>0)){
	
		 			if (in_array($itemObj->getArticleTypeUid(),$articletypes)){
		 				$articleLines .= $this->makeLineView($itemObj,$lineTemplate[$part]);
		 			}
		 		}
		 		else{
		 			$articleLines .= $this->makeLineView($itemObj,$lineTemplate[$part]);
		 		}
	
		 		++$count;
		 	}
			
	
		 	$content = $this->cObj->substituteSubpart($template,'###LISTING_ARTICLE###',$articleLines);
		 	//Unset Subparts, if not used
			foreach($lineTemplate as $subpartMarker) {
		 		$content = $this->cObj->substituteSubpart($content,$subpartMarker,'');
		 	}
		}else{
			$content = $this->cObj->substituteSubpart($template,'###LISTING_ARTICLE###','');
		}

	 	$content = $this->cObj->substituteSubpart(
	 			$content,
	 			'###LISTING_BASKET_WEB###',
	 			$this->makeBasketInformation($basketObj,'###LISTING_BASKET_WEB###')
	 			);

	 	return $content;
	 }

	 /**
	  * Renders from the given Basket the Sum Information to HTML-Code
	  * This Method will not replace the Subpart, you have to replace your subpart in your template
	  * by you own
	  * @param BasketObj Basket Object
	  * @param Subpart Template Subpart
	  * @param array of articletypes
	  * @return $content HTML-Ccontent from the given Subpart
	  * @author Ingo Schmitt <is@marketing-factory.de>
	  * @abstract
	  * Redersn the following MARKER
	  * ###LABEL_SUM_ARTICLE_NET### ###SUM_ARTICLE_NET###
	  * ###LABEL_SUM_ARTICLE_GROSS### ###SUM_ARTICLE_GROSS###
	  * ###LABEL_SUM_SHIPPING_NET### ###SUM_SHIPPING_NET###
	  * ###LABEL_SUM_SHIPPING_GROSS### ###SUM_SHIPPING_GROSS###
	  * ###LABEL_SUM_NET###
	  * ###SUM_NET###
	  * ###LABEL_SUM_TAX###
          * ###SUM_TAX###
	  * ###LABEL_SUM_GROSS### ###SUM_GROSS###
	  */

	 function makeBasketInformation($basketObj,$subpartMarker)
	 {

	 	$content='';
	 	$template = $this->cObj->getSubpart($this->templateCode, $subpartMarker);
	 	$basketObj->recalculate_sums();
	 	$markerArray['###SUM_NET###']			= tx_moneylib::format($basketObj->get_net_sum(true),$this->currency,$this->showCurrency);
	 	$markerArray['###SUM_GROSS###']			= tx_moneylib::format($basketObj->get_gross_sum(true),$this->currency,$this->showCurrency);

		$sumArticleNet = 0;
		$sumArticleGross = 0;
		$regularArticleTypes = t3lib_div::intExplode(',', $this->conf['regularArticleTypes']);
		foreach ($regularArticleTypes as $regularArticleType) {
			$sumArticleNet+= $basketObj->getArticleTypeSumNet($regularArticleType,1);
			$sumArticleGross+= $basketObj->getArticleTypeSumGross($regularArticleType,1);
		}

	 	$markerArray['###SUM_ARTICLE_NET###']   = tx_moneylib::format($sumArticleNet,$this->currency,$this->showCurrency);
	 	$markerArray['###SUM_ARTICLE_GROSS###'] = tx_moneylib::format($sumArticleGross,$this->currency,$this->showCurrency);
	 	$markerArray['###SUM_SHIPPING_NET###']   = tx_moneylib::format($basketObj->getArticleTypeSumNet(DELIVERYArticleType,1),$this->currency,$this->showCurrency);
	 	$markerArray['###SUM_SHIPPING_GROSS###'] = tx_moneylib::format($basketObj->getArticleTypeSumGross(DELIVERYArticleType,1),$this->currency,$this->showCurrency);
		$markerArray['###SHIPPING_TITLE###']		= $basketObj->getFirstArticleTypeTitle(DELIVERYArticleType);
		$markerArray['###SUM_PAYMENT_NET###']   = tx_moneylib::format($basketObj->getArticleTypeSumNet(PAYMENTArticleType,1),$this->currency,$this->showCurrency);
	 	$markerArray['###SUM_PAYMENT_GROSS###'] = tx_moneylib::format($basketObj->getArticleTypeSumGross(PAYMENTArticleType,1),$this->currency,$this->showCurrency);
		$markerArray['###PAYMENT_TITLE###']		= $basketObj->getFirstArticleTypeTitle(PAYMENTArticleType);
		$markerArray['###PAYMENT_DESCRIPTION###']		= $basketObj->getFirstArticleTypeDescription(PAYMENTArticleType);
		$markerArray['###SUM_TAX###']   		 = tx_moneylib::format($basketObj->getTaxSum(),$this->currency,$this->showCurrency);
		
		$taxRateTemplate = $this->cObj->getSubpart($template, '###TAX_RATE_SUMS###');
		$taxRates =  $basketObj->getTaxRateSums();
		$taxRateRows = '';
		foreach($taxRates as $taxRate => $taxRateSum) {
			$taxRowArray = array();
			$taxRowArray['###TAX_RATE###'] = $taxRate;
			$taxRowArray['###TAX_RATE_SUM###'] = tx_moneylib::format($taxRateSum,$this->currency,$this->showCurrency);
			
			$taxRateRows .= $this->cObj->substituteMarkerArray($taxRateTemplate,$taxRowArray);
		}
		
		/**
	    * Hook for processing Taxes
	    * Inspired by tx_commerce
	    * @author Michael Duttlinger 
		* @since 29.06.2008
		*
		*/
		 $hookObjectsArr = array();
		 if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['makeBasketInformation'])) {
		 	foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['makeBasketInformation'] as $classRef) {
		 		$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
		 	}
		 }
		 foreach($hookObjectsArr as $hookObj) {
		 	if (method_exists($hookObj, 'processMarkerTaxInformation')) {
		 		$taxRateRows = $hookObj->processMarkerTaxInformation($taxRateTemplate, $basketObj, $this);
		 	}
		 }
		
		$template = $this->cObj->substituteSubpart($template,'###TAX_RATE_SUMS###',$taxRateRows);
		
		
		

	          /**
	            * Hook for processing Marker Array
	            * Inspired by tt_news
		    * @since 01.02.2006
		    *
		    */
		
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['makeBasketInformation'])) {
		    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['makeBasketInformation'] as $classRef) {
                  $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
            }
        	
            foreach($hookObjectsArr as $hookObj)    {
            	
                if (method_exists($hookObj, 'processMarkerBasketInformation')) {
                   $markerArray=$hookObj->processMarkerBasketInformation($markerArray,$basketObj,$this);
                }
            }
		}
	 	$content = $this->substituteMarkerArrayNoCached($template,$markerArray);
	 	$content = $this->cObj->substituteMarkerArray($content,$this->languageMarker);

	 	return $content;
	 }

	 /**
	  * Renders the given Basket Ite,
	  * This Method will not replace the Subpart, you have to replace your subpart in your template
	  * by you own
	  * @param BasketItemObj Basket Object
	  * @param Subpart Template Subpart
	  * @return $content HTML-Ccontent from the given Subpart
	  * @author Ingo Schmitt <is@marketing-factory.de>
	  * @abstract
	  * Renders the following MARKER
	  * ###PRODUCT_TITLE###
	  * ###PRODUCT_IMAGES###<br />
      * <SPAN>###PRODUCT_SUBTITLE###<BR/>###LANG_ARTICLE_NUMBER### ###ARTICLE_EANCODE###<br/>###PRODUCT_LINK_DETAIL###</SPAN>
      * ###LANG_PRICE_NET### ###BASKET_ITEM_PRICENET###<br/>
      * ###LANG_PRICE_GROSS### ###BASKET_ITEM_PRICEGROSS###<br/>
      * ###LANG_TAX### ###BASKET_ITEM_TAX_VALUE### ###BASKET_ITEM_TAX_PERCENT###<br/>
      * ###LANG_COUNT### ###BASKET_ITEM_COUNT###<br/>
      * ###LANG_PRICESUM_NET### ###BASKET_ITEM_PRICESUM_NET### <br/>
      * ###LANG_PRICESUM_GROSS### ###BASKET_ITEM_PRICESUM_GROSS### <br/>
	  * @TODO Locallang Handling in language ..
	  */

	 function makeLineView($basketItemObj,$subpartMarker){

	 	$content='';
	  	$markerArray=array();
	 	$template = $this->cObj->getSubpart($this->templateCode, $subpartMarker);

	 	/**
	 	 * Basket Item Elements
	 	 */

	 	$markerArray['###BASKET_ITEM_PRICENET###']	= tx_moneylib::format($basketItemObj->get_price_net(),$this->currency,$this->showCurrency);
	 	$markerArray['###BASKET_ITEM_PRICEGROSS###']	= tx_moneylib::format($basketItemObj->get_price_gross(),$this->currency,$this->showCurrency);
	 	$markerArray['###BASKET_ITEM_PRICESUM_NET###']	= tx_moneylib::format($basketItemObj->get_item_sum_net(),$this->currency,$this->showCurrency);
	 	$markerArray['###BASKET_ITEM_PRICESUM_GROSS###']= tx_moneylib::format($basketItemObj->get_item_sum_gross(),$this->currency,$this->showCurrency);
		$markerArray['###BASKET_ITEM_ORDERNUMBER###']	= $basketItemObj->getOrderNumber();

		/**
		 * @TODO: TypoScript formationg of percentage
		 */
	 	$markerArray['###BASKET_ITEM_TAX_PERCENT###']		= $basketItemObj->get_tax();
	 	$markerArray['###BASKET_ITEM_TAX_VALUE###']		= tx_moneylib::format(intval($basketItemObj->get_item_sum_tax()),$this->currency,$this->showCurrency);

	  	$markerArray['###BASKET_ITEM_COUNT###'] 	= $basketItemObj->getQuantity();

		$markerArray['###PRODUCT_LINK_DETAIL###'] = 	$this->pi_linkTP_keepPIvars(
							$this->pi_getLL('detaillink','details'),
							array('showUid'=>$basketItemObj->getProductUid(),
							'catUid'=>intval($basketItemObj->getProductMasterparentCategorie()) ),
							true,true,$this->conf['listPid']);


	          /**
	            * Hook for processing Marker Array
	            * Inspired by tt_news
		    * @since 01.02.2006
		    *
		    */
		   $hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['makeLineView'])) {
			   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['makeLineView'] as $classRef) {
                                   $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
	                     }
	               }
	             foreach($hookObjectsArr as $hookObj)    {
	                   if (method_exists($hookObj, 'processMarkerLineView')) {
	                         $markerArray=$hookObj->processMarkerLineView($markerArray,$basketItemObj,$this);
	                   }
	             }

	 	$content = $this->substituteMarkerArrayNoCached($template,$markerArray);
	 	/**
	 	 * Basket Artikcel Lementes
	 	 */

	 	$product_array 	= $basketItemObj->getProductAssocArray('PRODUCT_');
	 	$content = $this->cObj->substituteMarkerArray($content,	$product_array,'###|###',1);


	 	$article_array 	= $basketItemObj->getArticleAssocArray('ARTICLE_');
	 	$content = $this->cObj->substituteMarkerArray($content, $article_array,'###|###',1);


	 	$content = $this->cObj->substituteMarkerArray($content, $this->MergedLangMarker,'###|###',1);

	 	return $content;
	 }

	 /**
	  * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	  * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	  * + New Methods for rendering
	  * @since 2005 11 8
	  */
	/**
	 * Adds the the commerce TYPO3 Link parameter for commerce to existing typoLink StdWarp
	 * if typolink.setCommerceValues =1
	 * is set. 
	 * @param	$TSArray	Array	Existing TypoScriptConfiguration
	 * @param	$TypoLinkConf	Array	TypoLink Configuration, buld bie view Method
	 * @return	Array	Changed TypoScript Configuration
	 * @Author	Ingo Schmitt <is@marketing-factory.de>
	 * @since 	12. August 2007
	 * 
	 *
	 */
	 
	 
	 function addTypoLinkToTS($TSArray,$TypoLinkConf) {
	 
	 	foreach ($TSArray['fields.'] as $tsKey => $tsValue) {
	 		if (is_array($TSArray['fields.'][$tsKey]['typolink.'])) {
	 			if ($TSArray['fields.'][$tsKey]['typolink.']['setCommerceValues'] == 1){
	 				$TSArray['fields.'][$tsKey]['typolink.']['parameter'] = $TypoLinkConf['parameter'];
	 				$TSArray['fields.'][$tsKey]['typolink.']['additionalParams'] .= $TypoLinkConf['additionalParams'];
	 			}
	 		}
	 		if (is_array($TSArray['fields.'][$tsKey])) { 
	 			if (is_array($TSArray['fields.'][$tsKey]['stdWrap.'])) {
			 		if (is_array($TSArray['fields.'][$tsKey]['stdWrap.']['typolink.'])) {
			 			if ($TSArray['fields.'][$tsKey]['stdWrap.']['typolink.']['setCommerceValues'] == 1){
			 				$TSArray['fields.'][$tsKey]['stdWrap.']['typolink.']['parameter'] = $TypoLinkConf['parameter'];
			 				$TSArray['fields.'][$tsKey]['stdWrap.']['typolink.']['additionalParams'] .= $TypoLinkConf['additionalParams'];
			 			}
			 		}
		 		}
	 		}
	 	}
	 	return $TSArray;
	 }

	/**
	 * Generates a markerArray from given data and TypoScript
	 * @param	array	$data	Assoc-Array with keys as Database fields and
	 * values as Values
	 * @param	array	$TS	TypoScript Configuration
	 * @param	string	prefix for marker, default empty
	 * @param	string	tx_commerce table name
	 * @return	array		Marker Array for using cobj Marker array methods
     * @todo create how to use this method when coding new stuff
	 */
	function generateMarkerArray($data,$TS,$prefix='',$table='') {
		
		if(!$TS['fields.']){
		    $TS['fields.'] = $TS;
		}
		$markerArray=array();
		if (is_array($data))	{
			foreach ($data as $fieldName => $columnValue)	{
					// get TS config
				$type = $TS['fields.'][$fieldName];
				$config = $TS['fields.'][$fieldName.'.'];

				if (empty($type)) {
					$type = $TS['defaultField'];
					$config = $TS['defaultField.'];
				}
				if ($type == 'IMAGE') {
                    $config['altText'] = $data['title'];
                }
				// Table should be set and as all tx_commerce tables are prefiex with
				// tx_commerce (12 chars) at least 11 chars long
				if (isset($table) && (strlen($table) > 11)){
					// Load only TCA if field is a image type, see  renderValue
					if ($type == 'IMGTEXT' || $type == 'IMAGE' || $type == 'IMG_RESOURCE' ){
						t3lib_div::loadTCA($table);
											
					}
				}
				
				$markerArray[strtoupper($prefix.$fieldName)]=$this->renderValue($columnValue,$type,$config,$fieldName,$table,$data['uid']);

			}
		}
		return $markerArray;

	}

	/**
	* Renders one Value to TS
	* Availiabe TS types are IMGTEXT, IMAGE, STDWRAP
	* @param	mixed 	$value	Outputvalue
	* @param 	string	$TStype	TypoScript Type for this value
	* @param	array	$TSconf	TypoScript Config for this value
	* @param	string	$field	Database field name
	* @param	string	$table	Database table name
	* @param	integer	$uid	Uid of record
    * @todo create how to use this method when coding new stuff 
	* @return 	string		html-content
	*/

	function renderValue($value, $TStype,$TSconf,$field='',$table = '',$uid = '') {

		/**
		  * If you add more TS Types using the imgPath, you should add these also to generateMarkerArray 
		  */
		if (!isset($TSconf['imgPath'])) {
			$TSconf['imgPath'] = $this->imgFolder;
		}
		switch(strtoupper($TStype)) {
			case 'IMGTEXT' :
				
				$TSconf['imgList'] = $value;
				$output = $this->cObj->IMGTEXT($TSconf);

			break;
			case 'RELATION' :
				$singleValue = explode(',',$value);
				#debug($singleValue);
				#$var = array_flip($singleValue);

				#debug(array($singleValue,$TSconf),$value);
				while(list($k,$uid) = each($singleValue)) {
					$data = $this->pi_getRecord($TSconf['table'],$uid);
					if ($data) {
						$singleOutput = $this->renderTable($data,$TSconf['dataTS.'],$TSconf['subpart'],$TSconf['table'].'_');
						$output .= $this->cObj->stdWrap($singleOutput,$TSconf['singleStdWrap.']);
					}
				}
				if ($output){
					$output = $this->cObj->stdWrap($output,$TSconf['stdWrap.']);
				}
			break;
			case 'MMRELATION' :
				$local 		= 'uid_local';
				$foreign	= 'uid_foreign';
				if ($TSconf['switchFields']){
					$foreign = 'uid_local';
					$local = 'uid_foreign';
				}
				$res = $GLOBALS['TYPO3_DB']->SELECTquery("distinct( $foreign )",$TSconf['tableMM'],$local.' = '.intval($uid).'  '.$TSconf['table.']['addWhere'],'',' sorting ');
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					"distinct( $foreign )",
					$TSconf['tableMM'],
					$local.' = '.intval($uid).'  '.$TSconf['table.']['addWhere'],
					'',
					' sorting '
				);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
					$data = $this->pi_getRecord($TSconf['table'],$row[$foreign]);
					if ($data){
						$singleOutput  = $this->renderTable($data,$TSconf['dataTS.'],$TSconf['subpart'],$TSconf['table'].'_');
						$output .= $this->cObj->stdWrap($singleOutput,$TSconf['singleStdWrap.']);
					}
				}
				#if ($output){
					$output = trim(trim($output),' ,:;');
					$output = $this->cObj->stdWrap($output,$TSconf['stdWrap.']);
				#}
			break;
			case 'FILES' :
				  $files = explode(',',$value);
			    	  while(list($k,$v) = each($files)){
				          $file = $this->imgFolder.$v;
				          $text = $this->cObj->stdWrap($file,$TSconf['linkStdWrap.']).$v;
				          $output .= $this->cObj->stdWrap($text,$TSconf['stdWrap.']);
				   }
			$output = $this->cObj->stdWrap($output,$TSconf['allStdWrap.']);
	    		break;
			case 'IMAGE' :
				if (is_string($value) && !empty($value)) {
					foreach (split(',',$value) as $oneValue) {
					    $this->cObj->setCurrentVal($TSconf['imgPath'].$oneValue);
					    if($TSconf['file']<> 'GIFBUILDER'){	
							$TSconf['file'] = $TSconf['imgPath'].$oneValue;
					    }
					    $output .= $this->cObj->IMAGE($TSconf);
					}
				}elseif(strlen($TSconf['file']) && $TSconf['file']<>'GIFBUILDER'){
				    $output .= $this->cObj->IMAGE($TSconf);
				}
			break;
			case 'IMG_RESOURCE' :
				if (is_string($value) && !empty($value)) {
					$TSconf['file'] = $TSconf['imgPath'].$value;
					$output = $this->cObj->IMG_RESOURCE($TSconf);
				}
			break;
			case 'TCA' :
				$ctrl = $this->makeControl($table);
				$savevalue = $value;
				$value = $this->getLabelFromTCA($ctrl,$field,$value);
			case 'NUMBERFORMAT' :
				if($TSconf['format']){
					$value = number_format((float)$value,$TSconf['format.']['decimals'],$TSconf['format.']['dec_point'],$TSconf['format.']['thousands_sep']);
				}
			case 'STDWRAP' :
			default :
				    $output = $this->cObj->stdWrap($value,$TSconf);
			break;

		}
		
		
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['locallang'])){
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['locallang'] as $classRef){
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

		if(is_array($hookObjectsArr)){
			foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'postRenderValue')) {
					$output = $hookObj->postRenderValue($output,array($value, $TStype,$TSconf,$field,$table,$uid));
				}
			}
		}

		
		/**
		 * Add admin panel
		 */
		if (is_string($table ) && is_string($field) ){
			$this->cObj->currentRecord = $table.':'.$uid;
			#$content = $this->cObj->editIcons($content,$table.':'.'$field')
		}
		return $output;
	}


	/**
	 * Reders a category as output
	 * @param 	object	$category 	tx_commerce_category object
	 * @param	string	$subpartName	template-subpart-name
	 * @param	array	$TS			TypoScript array for rendering
	 * @param	string	$prefix		Prefix for Marker, optional#
	 * @return 	string				HTML-Content
	 *
     * @todo create how to use this method when coding new stuff
     * 	 *
	 */

	function renderCategory($category, $subpartName, $TS, $prefix='',$template = '') {
			
			return $this->renderElement($category, $subpartName, $TS, $prefix,'###CATEGORY_',$template);
	}

	/**
	 * Reders an element as output
	 * @param 	object	$element	tx_commerce_* object
	 * @param	string	$subpartName	template-subpart-name
	 * @param	array	$TS			TypoScript array for rendering
	 * @param	string	$prefix		Prefix for Marker, optional#
	 * @param 	string 	$markerWrap $secondPrefix for Marker, default ###
	 * @return 	string				HTML-Content
	 *
     * @todo create how to use this method when coding new stuff
     *
	 */

	function renderElement($element, $subpartName, $TS, $prefix='',$markerWrap = '###',$template = '') {
		
		
		if (empty($subpartName)) {
			return $this->error('renderElement',__LINE__,'No supart defined for class.tx_commerce_pibase::renderElement ');
		}
		if(strlen($template)< 1){
			$template = $this->template;
		}
		if (empty($template)) {
			 return $this->error('renderElement',__LINE__,'No Template given as parameter to method and no template loaded via TS');
		}
		
		$output=$this->cObj->getSubpart($template,$subpartName );
		if (empty($output)) {
			
			return $this->error('renderElement',__LINE__,'class.tx_commerce_pibase::renderElement: Subpart:'.$subpartName.' not found in HTML-Code',$template);
			
		}
	
		$data = $element->return_assoc_array();
		
		$markerArray=$this->generateMarkerArray($data,$TS);
		
			$hookObjectsArr = array();
	        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['generalElement'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['generalElement'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach($hookObjectsArr as $hookObj)   {
		       if (method_exists($hookObj, 'additionalMarkerElement')) {
		                $markerArray =  $hookObj->additionalMarkerElement($markerArray,$element,$this);
		        }
		}

		if ($prefix>'')
		{
			$markerWrap.=strtoupper($prefix).'_';
		}
		$markerWrap.='|###';
		
		$output = $this->cObj->substituteMarkerArray($output,$markerArray,$markerWrap,1);
		$output = $this->cObj->stdWrap($output,$TS['stdWrap.']);

		return $output;
	}

      /**
	  * Formates the attribute value
	  * concerning the sprinf formating if value is a number
	  * @param       array   $matrix         AttributeMatrix
	  * @param       integer $myAttributeUid Uid of attribute
	  * @return Formated Value
	  * @author Ingo Schmitt <is@marketing-factory.de>
      * @todo create how to use this method when coding new stuff
	  *
	  *	  */

	  function formatAttributeValue($matrix,$myAttributeUid) {
	       /**
	        * Default return
			*/

//	       $return=$matrix[$myAttributeUid]['values'][0];
//
//	       if (is_numeric($matrix[$myAttributeUid]['values'][0])) {
//	           if ($matrix[$myAttributeUid]['valueformat']) {
//	                $return=sprintf($matrix[$myAttributeUid]['valueformat'],$matrix[$myAttributeUid]['values'][0]);
//	           }
//		 }
 		$return = '';
 		 /**
 		  * return if empty
 		  */
		if (!is_array($matrix)) {
			return $return;
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['formatAttributeValue']) {
			$hookObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['formatAttributeValue']);
		}
		$i=0;
		$AttributeValues=count($matrix[$myAttributeUid]['values']);

		
		foreach ( (array)$matrix[$myAttributeUid]['values'] as $key => $value) {
		 	$return2 = $value;
		 	if (is_numeric($value)) {
	           if ($matrix[$myAttributeUid]['valueformat']) {
	                $return2 =sprintf($matrix[$myAttributeUid]['valueformat'],$value);
	           }
		 	}
			if ($hookObj && method_exists($hookObj, 'formatAttributeValue')) {
				$return2 = $hookObj->formatAttributeValue($key, $myAttributeUid, $matrix[$myAttributeUid]['valueuidlist'][$key], $return2, $this);
			}
		 	if ( $AttributeValues > 1) {
		 		$return2=$this->cObj->stdWrap($return2,$this->conf['mutipleAttributeValueWrap.']) ;
		 	}
		 	if($i > 0) {
		 		$return .= $this->conf['attributeLinebreakChars'];
		 	}
		 	$return .= $return2;
		 	$i++;
		 }
		 if ( $AttributeValues > 1) {
		 	$return=$this->cObj->stdWrap($return,$this->conf['mutipleAttributeValueSetWrap.']) ;
		 }
		return  $return;
	  }

	/**
	 * Returns an string concerning the actial error
	 * plus adding debug of $this->conf;
	 * @param	string	$methodName	Methdo Name from where thsi error is called
	 * @param	integer	$line		line of code (normally should be __LINE__)
	 * @param	string	$errortext 	Text for this error
	 * @param	string 	$aditionaloutput Aditional code output in <pre></pre>
	 * @return	string	HTML Code
	 */
	function error($methodName,$line,$errortext,$aditionaloutput = false) {
	#	debug($this->conf,'TYPOSCRIPT Configuration');
		

		$errorOutput = __FILE__.'<br />';
		$errorOutput .= get_class($this).'<br />';
		$errorOutput .= $methodName.'<br />';
		$errorOutput .= 'Line '.$line.'<br />';
		$errorOutput .= $errortext;
		if ($aditionaloutput) {
			$errorOutput .= '<pre>'.$aditionaloutput.'</pre>';
		}
		
	    if($this->conf['showErrors']){
		t3lib_div::debug($errorOutput,'ERROR');
		return $errorOutput;
	    }
	}

	  /**
	   * Depricated Methods, do not Use
	   * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	   */
	/**
	 * calls renderProductAtrributeList with parametres from $this
	 * @see renderProductAttributeList
	 * @return	string	Stringoutput for attributes
	 * @depricated
	 *
	 */

	function makeproductAttributList($myProduct){

		$subpartArray[] = '###'.strtoupper($this->conf['templateMarker.']['productAttributes']).'###';
		$subpartArray[] = '###'.strtoupper($this->conf['templateMarker.']['productAttributes2']).'###';

		return $this->renderProductAttributeList($myProduct,$subpartArray);

	}

	/**
	 * Make the HTML output with list of attribute from a given product, reduced for some articles
	 * if article ids are givens
	 *
	 *
	 * @TODO	Real alternative Layout
	 * @param	object	$prodObj: Object for the current product, the attributes are taken from
	 * @param	array	$article: array with articleIds for filtering attributss
	 * @return	string	Stringoutput for attributes
	 * @depricated
	 */


	function makeArticleAttributList(&$prodObj, $articleId =array()){

		if (strlen($this->conf['templateMarker.']['articleAttributes'])> 0) {
			$subpartArray[] = '###'.strtoupper($this->conf['templateMarker.']['articleAttributes']).'###';
		}
		if (strlen($this->conf['templateMarker.']['articleAttributes2'])> 0) {
			$subpartArray[] = '###'.strtoupper($this->conf['templateMarker.']['articleAttributes2']).'###';
		}
		if (count($subpartArray)> 1) {
			return $this->renderArticleAttributeList($prodObj,$articleId,$subpartArray);
		}
		return false;
		
		
	}

	/**
	 * Makes the single view for the current products
	 *
	 * @TODO	clean up, make it more flexibles
	 * @return	string	the content for a single product
	 * @depricated
	 */

	function makeSingleView(){

		
		$subpartName = '###'.strtoupper($this->conf['templateMarker.']['productView']).'###';
		$subpartNameNostock = '###'.strtoupper($this->conf['templateMarker.']['productView']).'_NOSTOCK###';
		
		
		// ###########    product single    ######################

		$content = $this->renderSingleView($this->product,$this->category, $subpartName, $subpartNameNostock);
		$content = $this->cObj->substituteMarkerArray($content,$this->languageMarker);
		$globalMarker = array();
		$globalMarker = $this->addFormMarker($globalMarker);
		$content = $this->cObj->substituteMarkerArray($content, $globalMarker ,'###|###',1);
		$GLOBALS["TSFE"]->fe_user->setKey('ses','tx_commerce_lastproducturl',$this->pi_linkTP_keepPIvars_url());
		return $content;


	}

	/**
	 * Return the amount of articles for the basket input form
	 *
	 * @param integer $articleId the articleId check for the amount
	 */

	function getArticleAmount($articleId, $TSconf=false){

		if(!$articleId) return false;

			if (is_object($GLOBALS['TSFE']->fe_user->tx_commerce_basket->basket_items[$articleId])) {
					$amount = $GLOBALS['TSFE']->fe_user->tx_commerce_basket->basket_items[$articleId]->getQuantity();
			}else{
				if ($TSconf==false) {
					$amount = $this->conf['defaultArticleAmount'];
				}
				if ($TSconf['defaultQuantity']) {
					$amount = $TSconf['defaultQuantity'];
				}
				
			}

			return $amount;
	}

	function renderProductsForList($categoryProducts,$templateMarker,$iterations,$TS_marker=''){
		$hookObjectsArr = array();
	   	if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['renderProductsForList'])) {
	       		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['renderProductsForList'] as $classRef) {
	                    	$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
	    		}
	    	}
	    	foreach($hookObjectsArr as $hookObj)   {
			    if (method_exists($hookObj, 'preProcessorProductsListView')) {
		   	         $markerArray =  $hookObj->preProcessorProductsListView($categoryProducts,$templateMarker,$iterations,$TS_marker,$this);
		        }
		}

	
		$iterationCount = 0;
		if (is_array($categoryProducts)){
			foreach ($categoryProducts as $myProductId) {

				if(!($iterationCount < $iterations)){
					$iterationCount = 0;
				}
				$template = $this->cObj->getSubpart($this->templateCode, '###'.$templateMarker[$iterationCount].'###');
				
				$myProduct = t3lib_div::makeInstance(tx_commerce_product);
				
				$myProduct->init($myProductId,$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
				$myProduct->load_data();
				$myProduct->load_articles();
				if($this->conf['useStockHandling'] == 1 AND $myProduct->hasStock() === false) {
			  		$typoScript = $this->conf['listView'.$TS_marker.'.']['products.']['nostock.'];
			  		$tempTemplate = $this->cObj->getSubpart($this->templateCode, '###'.$templateMarker[$iterationCount].'_NOSTOCK###');
			  		if($tempTemplate != '' ) {
			  			$template = $tempTemplate;
			  		}
			  	} else {
					$typoScript = $this->conf['listView'.$TS_marker.'.']['products.'];
				}
				$iterationCount++;
			  	$category_items_listview .=
				$this->renderProduct($myProduct,$template,$typoScript,$this->conf['templateMarker.']['basketListView.'],$this->conf['templateMarker.']['basketListViewMarker'],$iterationCount);
			}
			$markerArray= array();
			$markerArray=$this->addFormMarker($markerArray);
			return  $this->cObj->substituteMarkerArray($category_items_listview, $markerArray ,'###|###',1);
		}
	}


	/**
	 * This method renders a product to a template
	 * @param	$myProduct		tx_commerce_product object
	 * @param	$template		TYPO3 Template
	 * @param	$TS			Typoscript Objkect
	 * @param	$articleMarker	Marker for the article description to be filled up with makeArticleView
	 * @param	articleSubpart	[optional]
	 * @param	iteration [optional] Number of iteration, not used, only for own implementation needed
	 * @see		makeArticleView
	 * @return 	string	renderd HTML
	 */

	function renderProduct($myProduct,$template,$TS,$articleMarker,$articleSubpart='',$iteration=''){
		if (empty($articleMarker)) {
			return $this->error('renderProduct',__LINE__,'No ArticleMarker defined in renderProduct ');
		}
		
		$hookObjectsArr = array();
	   	if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['product'])) {
	       		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_pibase.php']['product'] as $classRef) {
                     	$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
	    		}
	    	}
		if (!is_object($myProduct)) {
			return false;
		}
		$data = $myProduct->return_assoc_array();

		
		/**
		 *  Build TS for Linking the Catergory Images
		 */
		$lokalTS = $TS;
		
		
		/**
		 * Generate TypoLink Configuration and ad to fields by addTypoLinkToTs
		 */
		
			
		if ($this->conf['overridePid']) {
			$typoLinkConf['parameter']=$this->conf['overridePid'];
		}else{
			$typoLinkConf['parameter']=$this->pid;
		}
		$typoLinkConf['useCacheHash'] = 1;
		$typoLinkConf['additionalParams'] = ini_get('arg_separator.output').$this->prefixId.'[showUid]='.$myProduct->getUid();
		
		$typoLinkConf['additionalParams'].= ini_get('arg_separator.output').$this->prefixId.'[catUid]='.$this->cat;
		
		if($this->basketHashValue){
			$typoLinkConf['additionalParams'].= ini_get('arg_separator.output').$this->prefixId.'[basketHashValue]='.$this->basketHashValue;
		}
				
			
		$lokalTS = $this->addTypoLinkToTS($lokalTS, $typoLinkConf);
		
		$markerArray=$this->generateMarkerArray($data,$lokalTS,'','tx_commerce_products');
		foreach ($markerArray as $k => $v){
		    $markerArrayUp[strtoupper($k)] = $v;
		}
		$markerArray = $this->cObj->fillInMarkerArray(array(),$markerArrayUp,implode(',',array_keys($markerArrayUp)),FALSE,'PRODUCT_');
		
		$this->can_attributes = $myProduct->get_attributes(array(ATTRIB_can));
		$this->select_attributes = $myProduct->get_attributes(array(ATTRIB_selector));
		$this->shall_attributes = $myProduct->get_attributes(array(ATTRIB_shal));
		
		$ProductAttributesSubpartArray = array();
		$ProductAttributesSubpartArray[] = '###'.strtoupper($this->conf['templateMarker.']['productAttributes']).'###';
		$ProductAttributesSubpartArray[] = '###'.strtoupper($this->conf['templateMarker.']['productAttributes2']).'###';
		
		$markerArray['###SUBPART_PRODUCT_ATTRIBUTES###'] = $this->cObj->stdWrap($this->renderProductAttributeList($myProduct,$ProductAttributesSubpartArray,$TS['productAttributes.']['fields.']),$TS['productAttributes.']);
 		
		
		$linkArray['catUid']=(int)$this->piVars['catUid'];
		if($this->basketHashValue){
			$linkArray['basketHashValue'] = $this->basketHashValue;
		}
		if(is_numeric($this->piVars["manufacturer"])){
			$linkArray["manufacturer"] = $this->piVars["manufacturer"];
		}
		if(is_numeric($this->piVars["mDepth"])){
			$linkArray["mDepth"] = $this->piVars["mDepth"];
		}
		foreach($hookObjectsArr as $hookObj)   {
		    if (method_exists($hookObj, 'postProcessLinkArray')) {
    	    	         $markerArray =  $hookObj->postProcessLinkArray($linkArray,$myProduct,$this);
	        }
    	}
		$wrapMarkerArray['###PRODUCT_LINK_DETAIL###'] = explode('|',$this->pi_list_linkSingle('|',$myProduct->getUid(),true,$linkArray,FALSE,$this->conf['overridePid']));
		$articleTemplate=$this->cObj->getSubpart($template,'###'.strtoupper($articleSubpart).'###');
		
		if($this->conf['useStockHandling'] == 1) {
			$myProduct = tx_commerce_div::removeNoStockArticles($myProduct , $this->conf['articles.']['showWithNoStock']);
		}
		
		$subpartArray['###'.strtoupper($articleSubpart).'###'] = $this->makeArticleView('list',array(),$myProduct,$articleMarker,$articleTemplate);

		/**
		 * Get The Checapest Price
		 * 
		 */
		$cheapestArticleUid = $myProduct->getCheapestArticle();
		$cheapestArticle = t3lib_div::makeInstance('tx_commerce_article');
		$cheapestArticle->init($cheapestArticleUid);
		$cheapestArticle->load_data();
		$cheapestArticle->load_prices();
		
		$markerArray['###PRODUCT_CHEAPEST_PRICE_GROSS###']=tx_moneylib::format($cheapestArticle->get_price_gross(),$this->currency);
		
		$cheapestArticleUid = $myProduct->getCheapestArticle(1);
		$cheapestArticle = t3lib_div::makeInstance('tx_commerce_article');
		$cheapestArticle->init($cheapestArticleUid);
		$cheapestArticle->load_data();
		$cheapestArticle->load_prices();
		
		$markerArray['###PRODUCT_CHEAPEST_PRICE_NET###']=tx_moneylib::format($cheapestArticle->get_price_net(),$this->currency);
		

    	
		foreach($hookObjectsArr as $hookObj)   {
		    if (method_exists($hookObj, 'additionalMarkerProduct')) {
    	    	         $markerArray =  $hookObj->additionalMarkerProduct($markerArray,$myProduct,$this);
	        }
    	}
		foreach($hookObjectsArr as $hookObj)   {
		    if (method_exists($hookObj, 'additionalSubpartsProduct')) {
    	    	         $subpartArray =  $hookObj->additionalSubpartsProduct($subpartArray,$myProduct,$this);
	        }
    	}
		
    	$content = $this->substituteMarkerArrayNoCached($template, $markerArray , $subpartArray ,$wrapMarkerArray);
    	if ($TS['editPanel']== 1) {
			$content = $this->cObj->editPanel($content,$TS['editPanel.'],'tx_commerce_products:'.$myProduct->getUid());
    	}
    	
		return 	$content;

	
	}
	
	/**
	  * Addsd the global Marker for the formtags to the given marker array
	  * @author	Ingo Schmitt <is@marketing-factory.de>
	  * @param	$markerArray	array	Array of marker
	  * @param 	$wrap		[default=false] if the marker should be wrapped by $wrap.
	  * @return	array	Marker Array with the new marker 
	  * 
	 **/
	
	function addFormMarker($markerArray,$wrap=false) {
		$NewmarkerArray['GENERAL_FORM_ACTION'] =  $this->pi_getPageLink($this->conf['basketPid']);
		if (is_integer($this->cat)) {
			$NewmarkerArray['GENERAL_HIDDENCATUID'] = '<input type="hidden" name="'.$this->prefixId.'[catUid]" value="'.$this->cat.'" />';
		}
		if ($wrap){
			foreach ($NewmarkerArray as $key=>$value){
				$markerArray[$this->cObj->wrap($key,$wrap)]=$value;
			}
		}else{
			$markerArray=array_merge($markerArray,$NewmarkerArray);
		}
		return $markerArray;
	}

	function makeArticleView($kind,$articles,$product){

	    // to define in sub class
	}
	
	
	/**
	 * Returns the TCA for either $this->table(if neither $table nor $this->TCA is set), $table(if set) or $this->TCA
	 *
	 * @param	string		$table: The table to use
	 * @return	array		The TCA
	 */
	
	function makeControl($table=''){
		if(!$table && !$this->TCA){
			t3lib_div::loadTCA($this->table);
			$this->TCA = $GLOBALS['TCA'][$this->table];
		}
		if(!$table){
			return $this->TCA;
		}
	
		t3lib_div::loadTCA($table);
		$localTCA = $GLOBALS['TCA'][$table];
		return $localTCA;
	}
	
 	/* Multi substitution function with caching.
 	 * Copy from tslib_content -> substituteMarkerArrayNoCached
 	 * Without caching 
 	 * @see substituteMarkerArrayNoCached
	 *
	 * This function should be a one-stop substitution function for working with HTML-template. It does not substitute by str_replace but by splitting. This secures that the value inserted does not themselves contain markers or subparts.
	 * This function takes three kinds of substitutions in one:
	 * $markContentArray is a regular marker-array where the 'keys' are substituted in $content with their values
	 * $subpartContentArray works exactly like markContentArray only is whole subparts substituted and not only a single marker.
	 * $wrappedSubpartContentArray is an array of arrays with 0/1 keys where the subparts pointed to by the main key is wrapped with the 0/1 value alternating.
	 *
	 * @param	string		The content stream, typically HTML template content.
	 * @param	array		Regular marker-array where the 'keys' are substituted in $content with their values
	 * @param	array		Exactly like markContentArray only is whole subparts substituted and not only a single marker.
	 * @param	array		An array of arrays with 0/1 keys where the subparts pointed to by the main key is wrapped with the 0/1 value alternating.
	 * @return	string		The output content stream
	 * @see substituteSubpart(), substituteMarker(), substituteMarkerInObject(), TEMPLATE()
	 */
	function substituteMarkerArrayNoCached($content,$markContentArray=array(),$subpartContentArray=array(),$wrappedSubpartContentArray=array())	{
		$GLOBALS['TT']->push('substituteMarkerArrayNoCache');

			// If not arrays then set them
		if (!is_array($markContentArray))	$markContentArray=array();	// Plain markers
		if (!is_array($subpartContentArray))	$subpartContentArray=array();	// Subparts being directly substituted
		if (!is_array($wrappedSubpartContentArray))	$wrappedSubpartContentArray=array();	// Subparts being wrapped
			// Finding keys and check hash:
		$sPkeys = array_keys($subpartContentArray);
		$wPkeys = array_keys($wrappedSubpartContentArray);
		$aKeys = array_merge(array_keys($markContentArray),$sPkeys,$wPkeys);
		if (!count($aKeys))	{
			$GLOBALS['TT']->pull();
			return $content;
		}
		asort($aKeys);
		
		
			// Initialize storeArr
		$storeArr=array();

			// Finding subparts and substituting them with the subpart as a marker
		reset($sPkeys);
		while(list(,$sPK)=each($sPkeys))	{
			$content =$this->cObj->substituteSubpart($content,$sPK,$sPK);
		}

			// Finding subparts and wrapping them with markers
		reset($wPkeys);
		while(list(,$wPK)=each($wPkeys))	{
			$content =$this->cObj->substituteSubpart($content,$wPK,array($wPK,$wPK));
		}

			// traverse keys and quote them for reg ex.
		reset($aKeys);
		while(list($tK,$tV)=each($aKeys))	{
			$aKeys[$tK]=quotemeta($tV);
		}
		$regex = implode('|',$aKeys);
			// Doing regex's
		$storeArr['c'] = split($regex,$content);
		preg_match_all('/'.$regex.'/',$content,$keyList);
		$storeArr['k']=$keyList[0];
	

		$GLOBALS['TT']->setTSlogMessage('Parsing',0);
		
		

			// Substitution/Merging:
			// Merging content types together, resetting
		$valueArr = array_merge($markContentArray,$subpartContentArray,$wrappedSubpartContentArray);

		$wSCA_reg=array();
		reset($storeArr['k']);
		$content = '';
			// traversin the keyList array and merging the static and dynamic content
		while(list($n,$keyN)=each($storeArr['k']))	{
			$content.=$storeArr['c'][$n];
			if (!is_array($valueArr[$keyN]))	{
				$content.=$valueArr[$keyN];
			} else {
				$content.=$valueArr[$keyN][(intval($wSCA_reg[$keyN])%2)];
				$wSCA_reg[$keyN]++;
			}
		}
		$content.=$storeArr['c'][count($storeArr['k'])];

		$GLOBALS['TT']->pull();
		return $content;
	}
	 
	

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_pibase.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_pibase.php']);
}
?>
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
 * Libary for Frontend-Rendering of articles . This class 
 * should be used for all Fronten-Rendering, no Database calls 
 * to the commerce tables should be made directly
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic Database calls are made from a separate Database Class
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 * @author      Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_article
 * @see tx_commerce_element_alib
 * @see tx_commerce_article_db
 * 
 * Basic class for handeling articles
 * @Version 1.11
 */
 /**
  * @todo changes fomr pascal to perl style {}
  * @todo move functionality from constructor class to init method for php5 compaitbilty
  * @todo implementation of language
  * 
  */
  
 /**
 * Main script class for the handling of articles. Normaly used
 * for frontend rendering. This class provides basic methodes for acessing
 * articles
 * inherited from tx_commerce_element_alib
 *
 * @author              Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */
 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_db_article.php');
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_element_alib.php'); 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_product.php'); 
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_article_price.php'); 
 
 class tx_commerce_article extends tx_commerce_element_alib{
        
        var  $title;    // Title of the article, e.g. articlename (private)
        
        var  $subtitle; // Subtitle of the article (private)
        
        var  $description_extra;       // article description (private)
        
        var  $tax;                     // Normal Tax for this article in Percent (private)
        
        var  $images=array();           // Images for the article (private)
        
        var  $images_array=array(); // Images for the article (private)
        
        var  $ordernumber;                      // ordernumber for this article (private)
        
        var  $eancode;                          // Eancode for this article (private)
        
        var $uid_product;					// Parent product Uid
        
        var  $article_type_uid;         // UID for the article Type (should be refered to table tx_commerce_article_types) (private)
        
        /**
         * @var delivery cost for displaying the article delivery cost on the page
         * needed for german Law Preisauszeichnung
         * @acces private
         */
        
        var $deliveryCostNet;
        
        
          /**
         * @var delivery cost for displaying the article delivery cost on the page
         * needed for german Law Preisauszeichnung
         * @acces private
         */
        
        var $deliveryCostGross;
        
        /**
         * uid from actual article price
         * @access private
         */
        var $price_uid;
       
        /**
         * List of all price uids concerning this article
         * @access private
         */
        var $prices_uids=array();
        
        /**
         * Price object
         * @acces private
         * 
         */
        var $price;
        
        /**
         * if the price is loaded from the database
         * @access private
         */
        
        var $prices_loaded=false;
        
        /**
         * @var	Stock for this article
         * default true
         */
        
       	var $stock = true;
        
        
        
        
        var $classname;					// classname if the article is a payment type
        /*
         *  Database identify Variables
         */
        /*
         *  Database identify Variables
         */
        
       
        
       
       
        
       
        
        
        /**
         * Construcroe Method, calles init method
         * @param uid integer uid of article
         * @param lang_uid integer language uid, default 0
         */
        function tx_commerce_article(){
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
  		 * Init Method, called by constructor
         * @param uid integer uid of article
         * @param lang_uid integer language uid, default 0
         */
      	function init($uid,$lang_uid=0){
      		$uid = intval($uid);
	   		$lang_uid = intval($lang_uid);
      		$this->database_class='tx_commerce_db_article';
      		$this->fieldlist=array('uid','title','subtitle','description_extra','teaser','tax','ordernumber','eancode','uid_product','article_type_uid','images','classname','relatedpage','supplier_uid','plain_text');
        
      		 if ($uid>0) {
					$this->uid=$uid;
					$this->lang_uid=$lang_uid;
					$this->conn_db=new $this->database_class;
					$hookObjectsArr = array();
					if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['postinit'])) {
							foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['postinit'] as $classRef) {
								$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
							}
					}
					foreach($hookObjectsArr as $hookObj)	{
						if (method_exists($hookObj, 'postinit')) {
							$hookObj->postinit($this);
						}
					}
					return true;
			 } else	{
			 	return false;	
			 }
      	}
      	  
	
	
	
	/**
         * @ToDo Handling for article_type and data retrival for article_type
         */
        
        /** @return string title of article
         *  @access public
         * 
         */
         
        function get_title() {
                return $this->title;    
        }
        
        function get_classname()    {
                return $this->classname;    
        }
        
        /**
         * @return string subtitle
         * @access public
         */
         function getSubtitle()     {
                return $this->subtitle;
          }
        /**
          * @return text description_extra+
          * @access public
          */ 
        function get_description_extra()    {
                return  $this->description_extra;
        }

		/**
		 * Get Article all possivle  prices as UDI Array
		 * 
		 * @return array or priceUid 
		 */
		function getPossiblePriceUids(){
			return $this->conn_db->getPrices($this->uid);
		}

		/**
		 * Get Article price scales
		 * @param	$startcount	Count where to start with th listing of the sacles, default 1
		 * @return array or priceUid grouped by the different scales
		 */
		function getPriceScales($startCount=1){
			
			  return $this->conn_db->getPriceScales($this->uid,$startCount);
		}
		
		/**
		 * Get the priceUid for a sepcific amount for this article
		 * @param	count	Count for this article
		 * @return 	integer Price Uid 
		 */
		
		function getActualPriceforScaleUid($count){

			//Hook for doing your own calculation
         	if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['getActualPriceforScaleUid']) {
				$hookObject = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['getActualPriceforScaleUid']);
			}

			if (is_object($hookObject) && (method_exists($hookObject, 'getActualPriceforScaleUid'))) {
				return $hookObject->getActualPriceforScaleUid($count,$this);
			}

			$arrayOfPrices=$this->getPriceScales();
			if (!$arrayOfPrices) {
				return $this->get_article_price_uid();
			}
			if (count($arrayOfPrices)==1) {
				/**
				 * When only one scale is given, this should be valid :-)
				 */
			
				return $this->get_article_price_uid();
			}else{
			
				foreach ($arrayOfPrices as $startCount => $tempArray) {
					if ($startCount <= $count) {
						foreach ($tempArray as $endCount => $priceUid){
							if ($endCount >= $count) {
								return $priceUid;
							}
						}
					}
				}	
			}
			return $this->get_article_price_uid();
		}
		
		/**
	
	 * 
		 *
		 * Get Article price scales
		 * @param	$startcount	Count where to start with teh listing of the sacles, default 1
		 * @return array or prices grouped by the different scales
		 */
		
		function getPriceScaleObjects($startCount=1) {
			
			$return=array();
			$arrayOfPricesUids=$this->getPriceScales($startCount);
			if (is_array($arrayOfPricesUids)) {
				foreach ($arrayOfPricesUids as $startCount => $tmpArray) {
					foreach ($tmpArray as $endCount => $pricdUid) {
						$return[$startCount][$endCount] = t3lib_div::makeInstance('tx_commerce_article_price');
						$return[$startCount][$endCount]->init($pricdUid);
						$return[$startCount][$endCount]->load_data();
					}
				}
				
				return $return;
			} else {
				return false;
			}
			
		}
         
        /**
          * @return double price_gross
          * @TODO use locallang
          * @access public
          */
        function get_price_gross()  {
	         if(is_object($this->price)){	
	        	return $this->price->get_price_gross();
        	}else{
        		return 'no valid price';
        	}      
        }


        /**
          *  @return double price_net
          *  @TODO use locallang
          *  @access public
          */      
        function get_price_net()   {
        	if(is_object($this->price)){	
	        	return $this->price->get_price_net();
        	}else{
        		return 'no valid price';
        	}
        }
       /**
         * @return int valid priceid
         * @access public
         */
        function get_article_price_uid() {
	        return $this->price_uid;
        }
        
        
        /**
          * @return int Delivery Cost for this article
          * @access public
          * @since 06.10.2005 
          */
        function getDeliveryCostNet()  {
	        return $this->deliveryCostNet;
        }
/**
          * @return int Delivery Cost for this article
          * @access public
          * @since 06.10.2005 
          */
		function getDeliveryCostGross() {
	        return $this->deliveryCostGross;
        }
         
        /**
         * Returns the price Uid
         * @return Uid of tx_commerce_price
         * @see tx_commerce_price
         * @since 11.10.05
         */
      	function getPriceUid() 	{
       		return $this->price_uid;
       	}
       
       
       /**
        * Get the price Object
        * @Return 	object price
        */
       function getPriceObj(){
       		return $this->price;
       	
       }
          
        /**
          * @return double tax
         * @access public
         */
        function get_tax()     {
            return doubleval($this->tax);
        }
        
          
          
         /**
        * @return string eancode
        * @access public
        */
        function getEanCode()   {
                return $this->eancode;  
        }     
           
        
        /**
         * Returns the related page for the product
         * @return int;
	 	* @access public
	 	*/
					    
		 function getRelatedPage()	 {
		     return $this->relatedpage;
		 }
										     	
	
        
        /**
        * @return integer article_type
        * @access public
        */
        function getArticleTypeUid() {
                return $this->article_type_uid;  
        }        
            
        /**
        * Returns an Array of Images
        * @return array;
        * @access public
        */

        function getImages() {
                return $this->images_array;
        }
        
		/**
		 * Returns the Supplier UID of the Article if set
		 * 
		 * @author Joerg Sprung <jsp@marketing-factory.de>
		 * @return integer UID of supplier
		 */
		function getSupplierUid() {
			if(isset($this->supplier_uid)) {
				return $this->supplier_uid;
			}
			return false;
		}
		/**
		 * returns the Supplier Name of an Article, if set
		 * @author	Ingo Schmitt	<is@marketing-factory.de>
		 * @return	string	Name of the supplier
		 */
		
         function getSupplierName() {
         	if ($this->getSupplierUid()){
         		return $this->conn_db->getSupplierName($this->getSupplierUid());
         	}
         	return '';
         } 
         
            
        /**
         * Loads the data and divides comma sparated images in array
         * @access public
         * @return void
         */
        
        function load_data($translationMode = false)   {
            parent::load_data($translationMode);  
            $this->load_prices($translationMode);
			$this->images_array=t3lib_div::trimExplode(',',$this->images);
			$this->calculateDeliveryCosts();
                
        }
       
         
        /**
         * Calculates the Net deliverycost for this article
         * Called by $this->load_data() 
         * @return delivery_cost
         */      
        function calculateDeliveryCosts() {
        	      	
        	/**
        	 * Just one Hook
        	 * as there is no sence for mor than one delievery cost claculation
        	 */
        	$hookObject='';
			if (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost'])) {
				
				$hookObject = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['calculateDeliveryCost']);
						
				
			}
        
			if (method_exists($hookObject, 'calculateDeliveryCostNet')) {
					$hookObject->calculateDeliveryCostNet($this->deliveryCostNet,$this);
			}
			  
		
			if (method_exists($hookObject, 'calculateDeliveryCostGross')) {
					$hookObject->calculateDeliveryCostGross($this->deliveryCostGross,$this);
			}
			  	
        	
        }
        
        
        /**
         * returns the parent product as object 
         * @see tx_commerce_product
         * @return product object
         */        
        function get_parent_product(){
        	
        	if ($this->uid_product) {
        		$products_uid =  $this->uid_product;
        	}else{
            	$products_uid=$this->conn_db->get_parent_product_uid($this->uid);
        	}
            $product = t3lib_div::makeInstance('tx_commerce_product');
        	$product->init($products_uid);
        	return $product;
                	
        }   
        /**
         * returns the parent Product Uid
         * @see tx_commerce_product
         * @return	uid of tx_commerce_products
         */
        
        function getParentProductUid(){
        	if ($this->uid_product) {
        		return $this->uid_product;
        	}
        	$products_uid=$this->conn_db->get_parent_product_uid($this->uid);
        	if ($products_uid>0) {
        		return $products_uid;
        	}
        	return false;
        	
        }
        /**
         *  @return string ordernumber
         *  @access public
      
         */
         function getOrdernumber() {
                    return $this->ordernumber;
         }  
        
        /**
         * Returns the article attributes
         * array ( attribut_uid =>
         *     array ('title =>' $title,
         * 'value' => $value,
         * 'unit' => $unit),
         *  ...
         * )
         *          
         * @author Sebastian Boettger - Cross Content Media <dev@cross-content.com> 
         * @return array of arrays
         */   
                
         function get_article_attributes()
         {
            $local_table = 'tx_commerce_articles';
            $mm_table = 'tx_commerce_articles_article_attributes_mm';
            $foreign_table = 'tx_commerce_attributes';
            $select = 'DISTINCT '.$foreign_table.'.uid, '.$foreign_table.'.title';
            $ignore = array('fe_group' => 1);
            $whereClause = t3lib_pageSelect::enableFields('tx_commerce_attributes', '', $ignore);				
            
            $groupBy='';
            $orderBy='';
            $limit='';
            
         		$setArticleAttributesResult = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
              $select,
              $local_table,
              $mm_table,
              $foreign_table,
              $whereClause,
              $groupBy,
              $orderBy,
              $limit);
         	
         	  while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($setArticleAttributesResult))	{
         	    if (!empty($return_data['uid']))
      	 				$attributes_uid_list[$return_data['uid']]=$return_data['title'];
	 	     		}
	 			    $GLOBALS['TYPO3_DB']->sql_free_result($setArticleAttributesResult);
	 			    foreach ($attributes_uid_list as $uid => $title){
	 			     $value = $this->getAttributeValue($uid);
	 			     if (!empty($value))
  	 			     $vals[$uid] = array (
  	 			       'title' => $title,
                 'value' => $this->getAttributeValue($uid)
                );
            }
            return $vals;
         }
         
         /**
          * Gets the Value from one distinct attribute of this article
          * @return Attribute Value
          * 
          */
         function getAttributeValue($attribute_uid,$valueListAsUid=false){
		
			return $this->conn_db->getAttributeValue($this->uid,$attribute_uid,$valueListAsUid);
		
         	
         }


      /**
       * returns the default price Object, which doesn't have any start or stoptime
       * @since 03.01.2007 Check Class valiable article_loaded for more performace
       * @author Volker Graubaum
       * @return the price_uid
       */
 	function getSpecialPrice(){
	
	        $this->load_prices();
		
		$this->specialPrice = array('object'=>$this->price,'uid'=>$this->price_uid);
		
                if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['specialPrice']) {
                       $hookObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_article.php']['specialPrice']);
                }
		if (method_exists($hookObj, 'specialPrice')) {
		        $hookObj->specialPrice($this->specialPrice,$this->prices_uids);
		}	     
		return $this->specialPrice;

	}
                
  
      /**
       * Gets the price of this article and stores in private variable
       * @since 28.08.2005 Check Class valiable article_loaded for more performace
       * @author Volker Graubaum
       * @return the price_uid
       */
 	   function load_prices($translationMode = false){
	
		 if ($this->prices_loaded==false){
		 	
		 	$arrayOfPrices=$this->conn_db->get_prices($this->uid);
		  	$this->prices_uids=$arrayOfPrices;
			
		 	
			if ($this->prices_uids) {
				 // If we do have a Logged in usergroup walk thrue and check if there is a special price for this group
				 if ((empty($GLOBALS['TSFE']->fe_user->groupData['uid'])==false) &&
			             ($GLOBALS['TSFE']->loginUser ||  count($GLOBALS['TSFE']->fe_user->groupData['uid'])>0 )){
	    	        	
					$tempGroups = $GLOBALS['TSFE']->fe_user->groupData['uid'];
					foreach ($tempGroups as $values) {
					   		$groups[] =$values;
					}
					
					$i = 0;
					while(!$this->prices_uids[$groups[$i]]&&$groups[$i]){
					    $i++;
					}
					if($groups[$i]){
					    $this->price = t3lib_div::makeInstance('tx_commerce_article_price');
					    $this->price->init($this->prices_uids[$groups[$i]][0]);
					    $this->price->load_data($translationMode);
					    $this->price_uid = $this->prices_uids[$groups[$i]][0];
					}else{
					    if($this->prices_uids['-2']){
					    $this->price = t3lib_div::makeInstance('tx_commerce_article_price');
						$this->price->init($this->prices_uids['-2'][0]);
						$this->price->load_data($translationMode);
						$this->price_uid = $this->prices_uids['-2'][0];
					    }else{
					    	 $this->price = t3lib_div::makeInstance('tx_commerce_article_price');
							 $this->price->init($this->prices_uids[0][0]);
							 if($this->price){
							    $this->price->load_data($translationMode);
							    $this->price_uid = $this->prices_uids['0'][0];
							 }else{
							    return false;
						     }
						}					
					}				
				}else{
					// No special Handling if no special usergroup is logged in
				
				    if($this->prices_uids['-1']){
				    	$this->price = t3lib_div::makeInstance('tx_commerce_article_price');
						$this->price->init($this->prices_uids['-1'][0]);
						$this->price->load_data($translationMode);
						$this->price_uid = $this->prices_uids['-1'][0];
				    }else{
				    	$this->price = t3lib_div::makeInstance('tx_commerce_article_price');
					    $this->price->init($this->prices_uids[0][0]);
					    if($this->price){
						    $this->price->load_data($translationMode);
						    $this->price_uid = $this->prices_uids['0'][0];
					    }else{
						    return false;
					    }
				    }
				}						 
				$this->prices_loaded=true;
				return $this->price_uid;
 
			}else{
				
			    return false;
			}
		}else{
		        return $this->price_uid;
		}		
	}
	/**
	 * Returns the data of this object als array
  	 * @param prefix Prefix for the keys or returnung array optional
  	 * @return array Assoc Arry of data
  	 * @acces public
  	 * @since 2006 07 27
  	 * 
  	 */
  	function returnAssocArray($prefix=''){
  		
  		$data=parent::returnAssocArray($prefix);
  		$data[$prefix.'stock']=$this->getStock();
  		return $data;
  	
  	}
  	
  	
  	/**
	 * Returns the avalibility of wanted amount of articles.
	 * 
	 * @param	$wantedArticles	= 0		Integer amount of Articles which should be added to basket 
	 * @param	$serviceChain=array()	mixed 	List of service keys which should be exluded in the search for a service. Array or comma list.
	 * @param	$subType=''				string  Sub type like file extensions or similar. Defined by the service.
	 * @return	boolean					avalibility of wanted amount of articles
	 * 
	 */
	function hasStock($wantedArticles = 0 , $subType = '',$serviceChain = array()) { 
		$counter = 0;
		$available = false;
		$articlesInStock = $this->getStock($subType,$serviceChain);
		while (is_object($serviceObj = t3lib_div::makeInstanceService('stockHandling', $subType, $serviceChain))) {
			$serviceChain.=','.$serviceObj->getServiceKey();
			if(method_exists ( $serviceObj, 'hasStock')) {
				$counter++;
				if($available = (int)$serviceObj->hasStock($this,$wantedArticles,$articlesInStock)) {
					break;
				}
			}
		}
		
		if( $counter == 0 ) {
			return true;
		} 
		
		return $available;
	}
	
	/**
	 * Returns the number of articles in Stock with calling one or more Services.
	 * if no Service is found or the hasStock Method is not implemented in Service,
	 * it always returns one.
	 * 
	 * 
	 * @param	$serviceChain=array()	mixed 	List of service keys which should be exluded in the search for a service. Array or comma list.
	 * @param	$subType=''				string  Sub type like file extensions or similar. Defined by the service.
	 * @return	integer					amount of articles in stock
	 * 
	 */
	function getStock($subType = '',$serviceChain = array()) { 
		$counter = 0;
		$articlesInStock = 0;
		while (is_object($serviceObj = t3lib_div::makeInstanceService('stockHandling', $subType, $serviceChain))) {
			$serviceChain.=','.$serviceObj->getServiceKey();
			if(method_exists ( $serviceObj, 'getStock')) {
				$articlesInStock += (int)$serviceObj->getStock($this);
				$counter++;
			}
		}
		if( $counter == 0 ) {
			return 1;
		}
		return $articlesInStock;
	}
	
	
	/**
	 * substract the wanted Articles from stock. If you have more than one stock which
	 * is handled to more than one Service please implement the Service due to Reference
	 * on $wantedArticles so you can reduce this amount steplike.
	 *
	 * @param	$wantedArticles	= 0		Integer amount of Articles which should reduced from stock
	 * @param	$serviceChain=array()	mixed 	List of service keys which should be exluded in the search for a service. Array or comma list.
	 * @param	$subType=''				string  Sub type like file extensions or similar. Defined by the service.
	 * @return	boolean					Decribes the result of going through the chains
	 * 
	 */
	function reduceStock($wantedArticles = 0 , $subType = '',$serviceChain = array()) { 
		$counter = 0;
		$articlesInStock = 0;
		while (is_object($serviceObj = t3lib_div::makeInstanceService('stockHandling', $subType, $serviceChain))) {
			$serviceChain.=','.$serviceObj->getServiceKey();
			if(method_exists ( $serviceObj, 'reduceStock')) {
				$serviceObj->reduceStock($wantedArticles,$this);
			}
		}
		if( $counter == 0 ) {
			return false;
		}
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
        * @return integer article_type
        * @access public
        * @depricated 
        * @see getArticleTypeUid()
        */
        function get_article_type_uid() {
                return $this->getArticleTypeUid();
        }   
        
        /**
         *  @return string ordernumber
         *  @access public
         *  @depricated
         *  @see getOrdernumber()
         */
         function get_ordernumber() {
                    return $this->getOrdernumber();
         }
         
         /**
         * @return string subtitle
         * @access public
         * @depricated;
         * @see getSubtitle()
         */
         function get_subtitle() {
                return $this->getSubtitle();;
          }
	    
		/**
        * @return string eancode
        * @access public
        * @depricated
        * @see getEanCode()
        */
        function get_eancode()
        {
                return $this->getEanCode();  
        
	}
	
	/**
	 * 
	 *
	 * Get Article price scales
	 * @param	$startcount	Count where to start with teh listing of the sacles, default 1
	 * @deprecated
	 * @see getPriceScaleObjects()
	 */
	
	function getPricsScaleObjects($startCount=1) {
		return $this->getPriceScaleObjects($startCount);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_article.php']);
}
?>

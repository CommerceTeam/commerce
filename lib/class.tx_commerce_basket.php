<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2009 Ingo Schmitt <is@marketing-factory.de>
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
 * Frontend libary for handling the basket. This class should be used
 * when rendering the basket and changing the basket items. 
 * 
 * The basket object is stored as object within the Frontend user
 * fe_user->tx_commerce_basket, you could acces the Basket object in the Frontend
 * via $GLOBALS['TSFE']->fe_user->tx_commerce_basket; 
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 * 
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * @internal Maintainer Ingo Schmitt
 * @package TYPO3
 * @subpackage tx_commerce
 * @subpackage tx_commerce_basket 
 * @see tx_commerce_basic_basket
 * Basic class for basket_handeling inhertited from tx_commerce_basic_basket
 * 
 * $Id$
 **/
 require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_basic_basket.php');
 
 /**
  * @since 22.10.05
  * Method to finish basket, after finalising the order
  * Stored the actuel data in the datanbse and stehs the finish time
  */
 
 class tx_commerce_basket extends tx_commerce_basic_basket{
 	
 	

	/**
	 * @var Stoarge-type for the data
	 * @access private
	 */ 	 
 	 private $storage_type='database';
 	 
 	 
 	 /**
 	  * @var sess_id (note not session id, as ssesison_id ist PHP5 method )
 	  * @access private
 	  */
 	 private $sess_id='';
 	
 	/**
 	 * Dummy instanatiate class
 	 * 
 	 */
	 
 	public function tx_commerce_basket(){		
		
		if($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['basketType']=='persistent'){
		    $this->storage_type='persistent';
		} 			
 	} 	
 		
 	
 	/**
 	 * Sets the session ID
 	 * @acces public
 	 */
 	
 	
 	
 	public function set_session_id($session_id) {
 		$this->sess_id=$session_id;	
 	}

	/**
	 * Finishes the order
	 * @access public
	 */ 	
 	public function finishOrder() 	{
 		switch($this->storage_type){
			case 'persistent' : 
                    	    // unset sessionvalue from user
	                    $GLOBALS['TSFE']->fe_user->setKey('ses', 'txCommercePersistantSessionId', '');
    	                    $GLOBALS["TSFE"]->fe_user->storeSessionData();												    
 			case 'database':
 			    $this->finishOrderInDatabase();
 			    break;
 				
 		} 		
 	}
 	/**
 	 * Loads the Basket_data from the session/database
 	 * depending on $this->storage_type
 	 * implemented now online database
 	 * @acces public
 	 */
 	
 	public function load_data(){
 		
 		switch($this->storage_type){
			case 'persistent' : $this->restoreBasket();
			break;
 			case 'database':
 				$this->load_data_from_database();
 				break;
 				
 		}
 		// Method of Parent, load the payentArticle, if availiable
 		parent::load_data();
 		
 	}
 	
 	/**
 	 * Stores the Basket_data from the session/database
 	 * depending on $this->storage_type
 	 * implemented now online database
 	 * @acces public
 	 */
 	
 	public function store_data(){
 		
 		switch($this->storage_type){
			case 'persistent' :		
 			case 'database':
 				$this->store_data_to_database();
 				break;
 				
 		}
 		
 	}
	
	private function restoreBasket(){
	    if($GLOBALS['TSFE']->fe_user->user){
		    $userSessionID = $GLOBALS['TSFE']->fe_user->getKey('user','txCommercePersistantSessionId');
		    if($userSessionID && $userSessionID != $this->sess_id){			
			    $this->loadPersistantDataFromDatabase($userSessionID);			
			    $this->load_data_from_database();
			    $GLOBALS['TSFE']->fe_user->setKey('user','txCommercePersistantSessionId',$this->sess_id);
			    $this->store_data_to_database();
		    }else{
			    $this->load_data_from_database();		    
		    }
	    }else{
		$this->load_data_from_database();
	    }
	
	}
	
 	
 	/**
 	 * Loads the Basket Data from the database
 	 * @todo handling for special prices
 	 */
 	private function loadPersistantDataFromDatabase($sessionID){ 		
		
		

			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
 				'tx_commerce_baskets',
				"sid='".$GLOBALS['TYPO3_DB']->quoteStr($sessionID,'tx_commerce_baskets')."' and finished_time =0 and pid=".$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['BasketStoragePid'],
				'',
				'pos');

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0){

	
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['loadPersistantDataFromDatabase'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['loadPersistantDataFromDatabase'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
						
			$basketReadonly = false;
 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{ 			
 				if (($return_data['quantity']>0) && ($return_data['price_id']>0))  {					
 					$this->add_article($return_data['article_id'],$return_data['quantity']) ;	
 					$this->crdate = $return_data['crdate'];
 					if (is_array($hookObjectsArr)){
	 					foreach($hookObjectsArr as $hookObj)	{
							if (method_exists($hookObj, 'loadPersistantDataFromDatabase')) {
								$hookObj->loadPersistantDataFromDatabase($return_data,$this);
							}
						}
 					} 
					
 				}
 			}
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 			
 		}
 	
 		
	 	
 	}

 	/**
 	 * Loads the Basket Data from the database
 	 * @todo handling for special prices
 	 */
 	private function load_data_from_database()	{
 		
 		$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
 			'tx_commerce_baskets',
			"sid='".$GLOBALS['TYPO3_DB']->quoteStr($this->sess_id,'tx_commerce_baskets')."' and finished_time =0 and pid=".$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['BasketStoragePid'],
			'',
			'pos');
		
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0){
			
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['load_data_from_database'] as $classRef) {
							$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
					}
			}
			$basketReadonly = false;
 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{ 			
 				if (($return_data['quantity']>0) && ($return_data['price_id']>0))  {
 					$this->add_article($return_data['article_id'],$return_data['quantity'],$return_data['price_id']) ;	
 					$this->changePrices($return_data['article_id'],$return_data['price_gross'],$return_data['price_net']);
 					$this->crdate = $return_data['crdate'];
 					if (is_array($hookObjectsArr)){
	 					foreach($hookObjectsArr as $hookObj)	{
							if (method_exists($hookObj, 'load_data_from_database')) {
								$hookObj->load_data_from_database($return_data,$this);
							}
						}
 					} 
					
 				}
	 			if ($return_data['readonly'] == 1) {
	 				$basketReadonly  = true;
	 			}
 							
 			}
			if ($basketReadonly === true) {
	 			$this->setReadOnly();
	 		} 			
 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
 			
 		}
 	
 		
	 	
 	}

	
 	/**
 	 * stores the Basket Data to the database
 	 * @todo handling f�r special prices
	 * @change real delete the data
 	 */
 	private function store_data_to_database() { 
 		/**
 		 * First delete all records from session
 		 */	

 		$result=$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_commerce_baskets',
			"sid='".$GLOBALS['TYPO3_DB']->quoteStr($this->sess_id,'tx_commerce_baskets')."' and finished_time = 0");
		
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_basket.php']['store_data_to_database'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
			
		//Get the arraykeys from basketitems to store correct position in basket
		$ar_basket_items_keys = array_keys($this->basket_items);
		
		//After getting the keys in a array, flip it and you can get the position of each basketitem		 		
		$ar_basket_items_keys = array_flip($ar_basket_items_keys);
		
		
		/**
		 * And insert data
		 */	
		foreach ($this->basket_items as $oneuid  => $one_item) {
			$insert_data['pid']=$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['BasketStoragePid'];
 			$insert_data['pos']=$ar_basket_items_keys[$oneuid];
 			$insert_data['sid']=$this->sess_id;
 			$insert_data['article_id']=$one_item->get_article_uid();
 			$insert_data['price_id']=$one_item->get_price_uid();
 			$insert_data['price_net']=$one_item->get_price_net();
 			$insert_data['price_gross']=$one_item->get_price_gross();
 			$insert_data['quantity']=$one_item->get_quantity();
 			$insert_data['readonly']=$this->isReadOnly();
 			$insert_data['tstamp'] = time();
 			if ($this->crdate >0 ) {
 				$insert_data['crdate'] = $this->crdate;
 			}else {
 				$insert_data['crdate'] = $insert_data['tstamp'];
 			}
 			if (is_array($hookObjectsArr)){
	 			foreach($hookObjectsArr as $hookObj)	{
					if (method_exists($hookObj, 'store_data_to_database')) {
						$insert_data = $hookObj->store_data_to_database($one_item,$insert_data);
					}
				} 
 			}
			$result=$GLOBALS['TYPO3_DB']->exec_INSERTquery(
 					'tx_commerce_baskets',
 					$insert_data
					); 		
 		}
		if (is_object($this->basket_items[$oneuid])) {
			$this->basket_items[$oneuid]->calculate_net_sum();
			$this->basket_items[$oneuid]->calculate_gross_sum();
		}
 	}
 	
 	/**
 	 * Sets the finishDate in the database
 	 * to finihs the datanasbe
 	 */
 	
 	private function finishOrderInDatabase() {
 		$update_array['finished_time']=time();
 		$result=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_baskets',
			"sid='".$GLOBALS['TYPO3_DB']->quoteStr($this->sess_id,'tx_commerce_baskets')."' and finished_time = 0",
			$update_array
			
			);
		
 		
 	}
 }

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_basket.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_basket.php"]);
}
 ?>
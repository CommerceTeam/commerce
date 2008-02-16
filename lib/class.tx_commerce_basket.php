<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2005 - 2006 Ingo Schmitt <is@marketing-factory.de>
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
 	 var $storage_type='database';
 	 
 	 
 	 /**
 	  * @var sess_id (note not session id, as ssesison_id ist PHP5 method )
 	  * @access private
 	  */
 	 var $sess_id='';
 	
 	/**
 	 * Dummy instanatiate class
 	 * 
 	 */
 	function tx_commerce_basket()
 	{
 			
 	}
 	
 	
 		
 	
 	/**
 	 * Sets the session ID
 	 * @acces public
 	 */
 	
 	
 	
 	function set_session_id($session_id)
 	{
 		$this->sess_id=$session_id;	
 	}

	/**
	 * Finishes the order
	 * @access public
	 */ 	
 	function finishOrder()
 	{
 		switch($this->storage_type)
 		{
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
 	
 	function load_data()
 	{
 		
 		switch($this->storage_type)
 		{
 			case 'database':
 				$this->load_data_from_database();
 				break;
 				
 		}
 		
 	}
 	
 	/**
 	 * Stores the Basket_data from the session/database
 	 * depending on $this->storage_type
 	 * implemented now online database
 	 * @acces public
 	 */
 	
 	function store_data()
 	{
 		
 		switch($this->storage_type)
 		{
 			
 			case 'database':
 				$this->store_data_to_database();
 				break;
 				
 		}
 		
 	}
 	
 	/**
 	 * Loads the Basket Data from the database
 	 * @todo handling f�r special prices
 	 */
 	function load_data_from_database()	{
 		
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
 	function store_data_to_database()
 	{
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
		
		/**
		 * And insert data
		 */	
		foreach ($this->basket_items as $oneuid  => $one_item)
 		{
			$insert_data['pid']=$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['BasketStoragePid'];
 			$insert_data['pos']=$oneuid;
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
 	}
 	
 	/**
 	 * Sets the finishDate in the database
 	 * to finihs the datanasbe
 	 */
 	
 	function finishOrderInDatabase()
 	{
 		$update_array['finished_time']=time();
 		$result=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_baskets',
			"sid='".$GLOBALS['TYPO3_DB']->quoteStr($this->sess_id,'tx_commerce_baskets')."' and finished_time = 0",
			$update_array
			
			);
		
 		
 	}
 }

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']["ext/commerce/lib/class.tx_commerce_basket.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']["ext/commerce/lib/class.tx_commerce_basket.php"]);
}
 ?>
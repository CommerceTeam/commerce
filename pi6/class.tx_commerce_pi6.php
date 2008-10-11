<?php
/***************************************************************
*  Copyright notice
*
*  First Version (c) 2005 - 2006 Franz Ripfel (fr@abezet.de)
*  Second Version written by Sudara (williams@web-crossing.com)
*  This Version written by Tom Rüther (tr@e-netconsulting.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * Plugin 'commerce_invoice' for the 'commerce_invoice' extension.
 *
 * @author	Sudara <williams@web-crossing.com>
 * @author	Franz Ripfel <fr@abezet.de>
 * @author  Tom Rüther <tr@e-netconsulting.de>
 * @author Ingo Schmitt <is@marketing-factory.de>
 * 
 * $Id: class.tx_commerce_pi6.php 328 2006-08-03 17:50:20Z ingo $
 */



/*****************************
* ToDo:  Multilanguage for Systemarticles
******************************/



require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_pibase.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once (t3lib_extMgm::extPath('moneylib').'class.tx_moneylib.php');

class tx_commerce_pi6 extends tx_commerce_pibase{
	var $prefixId = 'tx_commerce_pi6';		// Same as class name
	var $scriptRelPath = 'pi6/class.tx_commerce_pi6.php';	// Path to this script relative to the extension dir.
	var $extKey = 'commerce';	// The extension key.
	var $pi_checkCHash = TRUE;
	var $order_id;
	/**
	 * Main Method
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$extConf = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["commerce"]);
		
		# Checking backend user login?
		$this->invoiceBackendOnly($extConf["invoiceBackendOnly"]);
		
		# check for the logged in USER
		# It could be an FE USer, a BE User or an automated skript
		
		if ((empty($GLOBALS['TSFE']->fe_user->user)) && (!$GLOBALS['BE_USER']->user['uid']) && ($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"])){
			return $this->pi_getLL('not_logged_in');
		} elseif(($GLOBALS['TSFE']->fe_user->user) && (!$GLOBALS['BE_USER']->user['uid'])) {
			$this->user = $GLOBALS['TSFE']->fe_user->user;
		}
		
		# If it's an automated process, no caching
		if ($_SERVER["REMOTE_ADDR"] == $_SERVER["SERVER_ADDR"]){
			$GLOBALS['TSFE']->set_no_cache();
			
		}
		
		# Lets make this multilingual, eh?
	  	$this->generateLanguageMarker();
	 
		# we may need to do some character conversion tricks
		$convert = t3lib_div::makeInstance("t3lib_cs");
	
		# if there is no order id, this plugin serves no pupose
		#$this->confirmOrderId($extConf["invoiceEnableFE"]);
		
		$this->order_id = $this->piVars['order_id'];
		# todo - in the case of a FE user this should not give a hint about what's wrong, but instead redirect the user
		if (empty($this->order_id)) return $this->pi_wrapInBaseClass($this->pi_getLL('error_orderid')); 
	
		if (empty($this->conf['templateFile'])) {
	  		return $this->error('init',__LINE__,'Template File not defined in TS: ');
	  	}
		// grab the template
		$this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);
		if (empty($this->templateCode)) {
	  		return $this->error('init',__LINE__,"Template File not loaded, maybe it doesn't exist: ".$this->conf['templateFile']);
	  	}	
	  		
		// get subparts
		$templateMarker = "###TEMPLATE###";
		$this->template['invoice'] = $this->cObj->getSubpart($this->templateCode,$templateMarker);
		$this->template['item'] =  $this->cObj->getSubpart($this->template['invoice'],'###LISTING_ARTICLE###');
		
		# markers and content, ready to be populated
		$markerArray = array();
		$this->content = '';

		$this->order = $this->getOrderData();
		
		if($this->order) {
		
			$this->orderPayment = $this->getOrderSystemArticles($this->order['uid'],'2','PAYMENT_');
			#$markerArray = array_merge($markerArray,$this->orderPayment);
			
			$this->orderDelivery = $this->getOrderSystemArticles($this->order['uid'],'3','SHIPPING_');
			#$markerArray = array_merge($markerArray,$this->orderDelivery);
				
			$markerArray['###ORDER_TAX###'] = tx_moneylib::format($this->order['sum_price_gross'] - $this->order['sum_price_net'],$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['###ORDER_TOTAL###'] = tx_moneylib::format($this->order['sum_price_gross'],$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['###ORDER_NET_TOTAL###'] = tx_moneylib::format($this->order['sum_price_net'],$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['###ORDER_GROSS_TOTAL###'] = tx_moneylib::format($this->order['sum_price_gross'],$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['###ORDER_ID###'] = $this->order['order_id'];
			$markerArray['###ORDER_DATE###'] = strftime("%d.%m.%y", $this->order['crdate']);	
				
			# Fill some of the content from typoscript settings, to ease the 
			$markerArray['###INVOICE_HEADER###'] = $this->cObj->cObjGetSingle($this->conf['invoiceheader'],$this->conf['invoiceheader.']);
			$markerArray['###INVOICE_SHOP_NAME###'] = $this->cObj->TEXT($this->conf['shopname.']);
			$markerArray['###INVOICE_SHOP_ADDRESS###'] = $this->cObj->cObjGetSingle($this->conf['shopdetails'],$this->conf['shopdetails.']);
			$markerArray['###INVOICE_INTRO_MESSAGE###'] = $this->cObj->TEXT($this->conf['intro.']);
			$markerArray['###INVOICE_THANKYOU###'] = $this->cObj->TEXT($this->conf['thankyou.']);
	
			/*********************************************
			* Hook for processing the new/changed marker
			**********************************************/
			
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi6/class.tx_commerce_pi6.php']['invoice'])) {
			   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi6/class.tx_commerce_pi6.php']['invoice'] as $classRef) {
	                         $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
	           }
	        }
	        foreach($hookObjectsArr as $hookObj)    {
		         if (method_exists($hookObj, 'additionalMarker')) {
	                  $markerArray =  $hookObj->additionalMarker($markerArray,&$subpartArray,$this);
	             }
			}
	
			$subpartArray['###LISTING_ARTICLE###'] = $this->getOrderArticles($this->order['uid'],$this->conf['OrderArticles.'],'ARTICLE_');
			$subpartArray['###ADDRESS_BILLING_DATA###'] = $this->getAddressData($this->order['cust_invoice'],$this->conf['addressBilling.'],'ADDRESS_BILLING_');
			$subpartArray['###ADDRESS_DELIVERY_DATA###'] =$this->getAddressData($this->order['cust_deliveryaddress'],$this->conf['addressDelivery.'],'ADDRESS_DELIVERY_');
		
			$this->content = $this->cObj->substituteMarkerArrayCached($this->template['invoice'], array(), $subpartArray);
			// buid content from template + array		
			$this->content = $this->cObj->substituteSubpart($this->content,'###LISTING_PAYMENT_ROW###',$this->orderPayment);
			$this->content = $this->cObj->substituteSubpart($this->content,'###LISTING_SHIPPING_ROW###',$this->orderDelivery);
			$this->content = $this->cObj->substituteMarkerArrayCached($this->content,  $markerArray, array(), array());
			$this->content = $this->cObj->substituteMarkerArrayCached($this->content, $this->languageMarker,array());	 
				
		} else {
			$this->content = $this->pi_getLL('error_nodata');
		}
		if($this->conf['decode']=='1') $this->content = $convert->specCharsToASCII('utf-8',$this->content);
		return $this->pi_wrapInBaseClass($this->content);
	}

	 /**
	 * Check Access
	 * @param	string	[optional]	$enabled
	 * @return	
	 */

	function invoiceBackendOnly($enabled=false){
		if($enabled && !$GLOBALS["BE_USER"]->user["uid"] && ($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"]))	{
			t3lib_BEfunc::typo3PrintError ("Login-error","No user logged in! Sorry, I can't proceed then!",0);
			exit;
		}
	}
	
	 /**
	 * Renders Ordered Articles 
	 * @param	array	[optional]	$TS Configuration
	 * @return	string	HTML-Output rendert
	 */

	function getOrderArticles($orderUid,$TS=false,$prefix) {
		if ($TS ==false) {
			$TS = $this->conf['OrderArticles.'];
		}
		
		$queryString = 'order_uid='.$orderUid . ' AND article_type_uid < 2 ' ;
		$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString, '', '');
		$orderpos = 1;
 				
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$markerArray = $this->generateMarkerArray($row,$TS,$prefix);
			$markerArray['ARTICLE_PRICE'] = tx_moneylib::format ($row['price_gross'], $this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['ARTICLE_PRICE_GROSS'] = tx_moneylib::format ($row['price_gross'], $this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['ARTICLE_PRICE_NET'] = tx_moneylib::format ($row['price_net'], $this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			
			$markerArray['ARTICLE_TOTAL'] = tx_moneylib::format(($row['amount']*$row['price_gross']),$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['ARTICLE_TOTAL_GROSS'] = tx_moneylib::format(($row['amount']*$row['price_gross']),$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			$markerArray['ARTICLE_TOTAL_NET'] = tx_moneylib::format(($row['amount']*$row['price_net']),$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
			
			$markerArray['ARTICLE_POSITION'] = $orderpos++;
			$out .= $this->cObj->substituteMarkerArray($this->template['item'], $markerArray ,'###|###',1);
		}
				
	   return $this->cObj->stdWrap($out,$TS);	     				
	}
	
	
	 /**
	 * Renders Address data 
	 * @param	array	[optional]	$TS Configuration
	 * @return	string	HTML-Output rendert
	 */
	
	function getAddressData($addressUid='',$TS=false,$prefix) {

			if ($TS ==false) {
				$TS = $this->conf['address.'];
			}
			if($this->user){
 				$queryString = 'tt_address.tx_commerce_fe_user_id='.$this->order['cust_fe_user'];
 				$queryString.= ' AND tt_address.tx_commerce_fe_user_id = fe_users.uid';
				if($addressUid) {
					$queryString.= ' AND tt_address.uid = '.$addressUid;
				} else {
					$queryString.= ' AND tt_address.tx_commerce_address_type_id=1';
				}
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_address.* ', 'tt_address, fe_users',$queryString, '', '', '1');		
			}else{
				$queryString  = ' 1 = 1 ';
				if($addressUid) {
					$queryString.= ' AND tt_address.uid = '.$addressUid;
				} else {
					$queryString.= ' AND tt_address.tx_commerce_address_type_id=1';
				}
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_address.* ', 'tt_address',$queryString, '', '', '1');		
			}
 			$markerArray = $this->generateMarkerArray($GLOBALS['TYPO3_DB']->sql_fetch_assoc($res),$TS,$prefix);

			$template = $this->cObj->getSubpart($this->templateCode, '###'.$prefix.'DATA###');			
			$content = $this->cObj->substituteMarkerArray($template, $markerArray ,'###|###',1);
			$content = $this->cObj->substituteMarkerArray($content,$this->languageMarker);			
			
    	    return $this->cObj->stdWrap($content,$TS);	     			
	}
	
	 /**
	 * Render Data for Orders
	 * @param	array	[optional]	$TS Configuration
	 * @return	array	orderData 
	 */
	
	function getOrderData() {

		$queryString = 'order_id="'.mysql_real_escape_string($this->order_id).'"';
		$queryString.= $this->cObj->enableFields("tx_commerce_orders");
		if($this->user){
			$queryString.= ' AND cust_fe_user = '.$this->user['uid'].' ' ;
		}	
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_orders', $queryString, '', '', '1');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		return $row;     			
	}	
	
	 /**
	 * Render MarkerArray for System Articles
	 * @param	array	[optional]	$TS Configuration
	 * @return	array	System Articles 
	 */
	
	function getOrderSystemArticles($orderUid,$articleType='',$prefix) {
		
			$queryString = 'order_uid='.$orderUid .' ';
			if($articleType) $queryString .= ' AND article_type_uid = '.$articleType.' ' ;
			$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
			
	 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString);
	 		$content = '';
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
				
				$subpart = $this->cObj->getSubpart($this->templateCode,'###LISTING_'.$prefix.'ROW###');
				/**
				 * @todo: Use 	$markerArray = $this->generateMarkerArray($row,'',$prefix);
				 */
				$markerArray['###'.$prefix.'AMOUNT###'] = $row['amount'];	
				$markerArray['###'.$prefix.'METHOD###'] = $row['title'];	
				$markerArray['###'.$prefix.'COST###'] = tx_moneylib::format(($row['amount']*$row['price_gross']),$this->conf['currency'],(boolean)$this->conf['showCurrencySign']);
				
				$content.= $this->cObj->substituteMarkerArray($subpart,$markerArray);
			}
			
    	    return $content;	     		
	}	

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi6/class.tx_commerce_pi6.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi6/class.tx_commerce_pi6.php']);
}

?>
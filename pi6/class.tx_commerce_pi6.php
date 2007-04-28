<?php
/***************************************************************
*  Copyright notice
*
*  First Version (c) 2005 - 2006 Franz Ripfel (fr@abezet.de)
*  This Version written by Sudara (williams@web-crossing.com)
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
 * 
 * $Id: class.tx_commerce_pi6.php 328 2006-08-03 17:50:20Z ingo $
 */


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
	 * [Put your description here]
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$extConf = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["commerce"]);
		
		# Checking backend user login?
		$this->invoiceBackendOnly($extConf["invoiceBackendOnly"]);
		
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
		$this->template['item'] =  $this->cObj->getSubpart($this->template['invoice'],'###ORDER_ITEM###');
		
		# markers and content, ready to be populated
		$markerArray = array();
		$this->content = '';
	
		# grab the order id
		$queryString = 'order_id="'.$this->order_id.'"';
		$queryString.= $this->cObj->enableFields("tx_commerce_orders");
		/**
		 * Add the frontend USer Check to the Invoice Display, to have no
		 * Security problem, is somone gets the invoice URL
		 */
		if(!$GLOBALS['BE_USER']->user['uid']) {
			$queryString.= ' AND  tx_commerce_fe_user_id = '.$GLOBALS['TSFE']->fe_user->user['uid'].' ' ;
		}  
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_orders', $queryString, '', '', '1');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	
		$phonenumbers = array();
		
		if ($row) {
			//get invoice address
			//todo: correct table join, linking to fe_users necessary?
			$queryString = 'tt_address.tx_commerce_fe_user_id='.$row['cust_fe_user'];
			$queryString.= ' AND tt_address.tx_commerce_fe_user_id = fe_users.uid';
			$queryString.= ' AND tt_address.tx_commerce_address_type_id=1';
 		#	$queryString.= $this->cObj->enableFields("tt_address");
 		#	$queryString.= $this->cObj->enableFields("fe_users");
 			$res_address_invoice = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.phone ', 'tt_address, fe_users',$queryString, '', '', '1');			
 			if ($row_address_invoice = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_address_invoice)) {
				$address_invoice = $row_address_invoice['name'].' ' .$row_address_invoice['surname'].'<br />';
				$markerArray['###INVOICE_NAME###'] = $row_address_invoice['name'].' ' .$row_address_invoice['surname'];
				$address_invoice.= $row_address_invoice['address'].'<br />';
				$address_invoice.= $row_address_invoice['zip'].' '.$row_address_invoice['city'].'<br />';
			} else $address_invoice = '';
			//if set, get delivery_address
			if ($row['cust_deliveryaddress']) {
				//todo: correct table join, linking to fe_users necessary?
				$queryString = 'tx_commerce_fe_user_id='.$row['cust_fe_user'];
				$queryString.= ' AND tt_address.tx_commerce_fe_user_id = fe_users.uid';
				//todo: set to correct value
				$queryString.= ' AND tt_address.tx_commerce_address_type_id=2';
		#		$queryString.= $this->cObj->enableFields("tt_address");
		#		$queryString.= $this->cObj->enableFields("fe_users");
		 		$res_address_delivery = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.phone ', 'tt_address, fe_users',$queryString, '', '', '1');
				if ($row_address_delivery = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_address_delivery)) {
					$address_delivery = '';
					$address_delivery.= $row_address_delivery['name'].' ' .$row_address_delivery['surname'] .'<br />';
					$address_delivery.= $row_address_delivery['address'].'<br />';
					$address_delivery.= $row_address_delivery['zip'].' '.$row_address_delivery['city'].'<br />';
					$phonenumbers["###ADDRESS_DELIVERY_PHONE###"] = $row_address_delivery['phone'];
				}
			}
		
			
			
			
			$queryString = 'order_uid='.$row['uid'] . ' AND article_type_uid = 2 ' ;
			$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
	 		$res_orderlist = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString, '', '');
			$orderpos = 1;
			//todo: page break if too many products for one page? is enough, what pdf_generator can do?
			while ($row_orderlist = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_orderlist)) {
				$markerArray['###PAYMENT_METHOD###'] = $row_orderlist['title'];			
				$markerArray['###PAYMENT_COST###']  = tx_moneylib::format(($row_orderlist['amount']*$row_orderlist['price_gross']),$this->conf['currency']);				
				$paymentmethod = $row_orderlist['title'];
			}
			
			$queryString = 'order_uid='.$row['uid'] . ' AND article_type_uid = 3 ' ;
			$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
	 		$res_orderlist = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString, '', '');
			$orderpos = 1;
			//todo: page break if too many products for one page? is enough, what pdf_generator can do?
			while ($row_orderlist = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_orderlist)) {
				$markerArray['###SHIPPING_METHOD###'] = $row_orderlist['title'];
				$markerArray['###SHIPPING_COST###'] = tx_moneylib::format(($row_orderlist['amount']*$row_orderlist['price_gross']),$this->conf['currency']);				
			}
			/**
			 * @TODO Check if this tax calcuation is correct, or if we do 
			 * have to hav a tax line for every TAX 
			 */
			$markerArray['###ORDER_TAX###'] = tx_moneylib::format($row['sum_price_gross'] - $row['sum_price_net'],$this->conf['currency']);
			$markerArray['###ORDER_TOTAL###'] = tx_moneylib::format($row['sum_price_gross'],$this->conf['currency']);


			$markerArray["###ORDER_ID###"] = $this->piVars['order_id'];
			$markerArray["###ORDER_DATE###"] = strftime("%d.%m.%y", $row['crdate']);
			
			$markerArray["###INVOICE_BILLING_ADDRESS###"] = $address_invoice;
			$markerArray["###INVOICE_DELIVERY_ADDRESS###"] = $address_delivery;
			
			# Fill some of the content from typoscript settings, to ease the 
			$markerArray['###INVOICE_HEADER###'] = $this->cObj->cObjGetSingle($this->conf['invoiceheader'],$this->conf['invoiceheader.']);
			$markerArray['###INVOICE_SHOP_NAME###'] = $this->cObj->TEXT($this->conf['shopname.']);
			$markerArray['###INVOICE_SHOP_ADDRESS###'] = $this->cObj->cObjGetSingle($this->conf['shopdetails'],$this->conf['shopdetails.']);
			$markerArray['###INVOICE_INTRO_MESSAGE###'] = $this->cObj->TEXT($this->conf['intro.']);
			$markerArray['###INVOICE_THANKYOU###'] = $this->cObj->TEXT($this->conf['thankyou.']);

			# allow a hook here
			# always pass reference!!! Please!
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi6/class.tx_commerce_pi6.php']['invoice'])) {
			   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/pi6/class.tx_commerce_pi6.php']['invoice'] as $classRef) {
	                         $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
	           }
	        }
	        foreach($hookObjectsArr as $hookObj)    {
		         if (method_exists($hookObj, 'additionalMarker')) {
	                  $markerArray =  $hookObj->additionalMarker($markerArray,&$subpartArray,&$this);
	             }
			}
			
			# Fill in the order items
			$subpartArray['###ORDER_ITEMS###'] = $this->getOrderArticles($row['uid']);
			$this->content = $this->cObj->substituteMarkerArrayCached($this->template['invoice'], array(), $subpartArray);
			// buid content from template + array		
			$this->content = $this->cObj->substituteMarkerArrayCached($this->content, array(), $markerArray, array());
			$this->content = $this->cObj->substituteMarkerArrayCached($this->content, $this->languageMarker,array());	 
			
		} else {
			$this->content = $this->pi_getLL('error_nodata');
		}
		if($this->conf['decode']=='1') $this->content = $convert->specCharsToASCII('utf-8',$this->content);
		return $this->pi_wrapInBaseClass($this->content);
	}

	function invoiceBackendOnly($enabled=false){
		if($enabled && !$GLOBALS["BE_USER"]->user["uid"] && ($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"]))	{
			t3lib_BEfunc::typo3PrintError ("Login-error","No user logged in! Sorry, I can't proceed then!",0);
			exit;
		}
	}
	
	
	function getOrderArticles($order_id){
		$queryString = 'order_uid='.$order_id . ' AND article_type_uid < 2 ' ;
		$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
 		$res_orderlist = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString, '', '');
		$orderpos = 1;
		$this->orderItems = '';
		//todo: page break if too many products for one page? is enough, what pdf_generator can do?
		while ($row_orderlist = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_orderlist)) {
			$orderArray['###POSITION###'] = $orderpos++;
			$orderArray['###ARTICLE_NUMBER###'] = $row_orderlist['article_number'];
			$orderArray['###ARTICLE_TITLE###'] = $row_orderlist['title'];
			$orderArray['###QUANTITY###'] = $row_orderlist['amount'];
			$orderArray['###PRICE###'] = tx_moneylib::format ($row_orderlist['price_gross'], $this->conf['currency']);
			$orderArray['###TOTAL###'] = tx_moneylib::format(($row_orderlist['amount']*$row_orderlist['price_gross']),$this->conf['currency']);
			$this->orderItems .= $this->cObj->substituteMarkerArrayCached($this->template['item'], $orderArray);
		}
		return $this->orderItems;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/pi6/class.tx_commerce_pi6.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/pi6/class.tx_commerce_pi6.php']);
}

?>

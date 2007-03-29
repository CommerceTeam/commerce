<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Franz Ripfel (fr@abezet.de)
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
 * @author	Franz Ripfel <fr@abezet.de>
 * 
 * $Id: class.tx_commerce_pi6.php 554 2007-02-06 16:25:30Z ingo $
 */


require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once (t3lib_extMgm::extPath('moneylib').'class.tx_moneylib.php');

class tx_commerce_pi6 extends tslib_pibase {
	var $prefixId = 'tx_commerce_pi6';		// Same as class name
	var $scriptRelPath = 'pi6/class.tx_commerce_pi6.php';	// Path to this script relative to the extension dir.
	var $extKey = 'commerce';	// The extension key.
	var $pi_checkCHash = TRUE;
	var $order_id;
	/**
	 * [Put your description here]
	 */
	function main($content,$conf)	{
		/* todo: allgemein:
		 * - strings to i18n
		 */
		//todo: remove after programming
		$GLOBALS['TYPO3_DB']->debugOutput= true;

		// ******************************
		// Checking backend user login?
		// ******************************
		$extConf = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["commerce"]);
		if ($extConf["invoiceBackendOnly"])	{
			if (!$GLOBALS["BE_USER"]->user["uid"] && $_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"])	{
				t3lib_BEfunc::typo3PrintError ("Login-error","No user logged in! Sorry, I can't proceed then!",0);
				exit;
			}
			#$GLOBALS["BE_USER"]->modAccess($GLOBALS["MCONF"],1);
		}

		$myt3lib_cs = t3lib_div::makeInstance("t3lib_cs");
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->order_id = $this->piVars['order_id'];

		if (empty($this->order_id)) return $this->pi_wrapInBaseClass('no order_id!'); 
		//read data from DB
		$queryString = 'order_id="'.$this->order_id.'"';
		$queryString.= $this->cObj->enableFields("tx_commerce_orders");
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_orders', $queryString, '', '', '1');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$phonenumbers = array();
		
		if ($row) {
			//get invoice address
			//todo: correct table join, linking to fe_users necessary?
			$queryString = 'tx_commerce_fe_user_id='.$row['cust_fe_user'];
			$queryString.= ' AND tt_address.uid = '.$row['cust_invoice'];
			$queryString.= ' AND tt_address.tx_commerce_address_type_id=1';
			$queryString.= $this->cObj->enableFields("tt_address");
			$queryString.= $this->cObj->enableFields("fe_users");
 			$res_address_invoice = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_address.company,tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.phone ', 'tt_address, fe_users',$queryString, '', '', '1');			
 			if ($row_address_invoice = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_address_invoice)) {
 				$address_invoice = '';
 				if ($row_address_invoice['company']) {
 					$address_invoice = $row_address_invoice['company'].'<br />';
 				}
				$address_invoice.= $row_address_invoice['name'].' ' .$row_address_invoice['surname'].'<br />';
				$address_invoice.= $row_address_invoice['address'].'<br />';
				$address_invoice.= $row_address_invoice['zip'].' '.$row_address_invoice['city'].'<br />';
				$phonenumbers["###ADDRESS_INVOICE_PHONE###"] = $row_address_invoice['phone'];
			}
			//if set, get delivery_address
			if ($row['cust_deliveryaddress']) {
				//todo: correct table join, linking to fe_users necessary?
				$queryString = 'tx_commerce_fe_user_id='.$row['cust_fe_user'];
				$queryString.= ' AND tt_address.uid = '.$row['cust_deliveryaddress'];
				//todo: set to correct value
			#	$queryString.= ' AND tt_address.tx_commerce_address_type_id=2';
				$queryString.= $this->cObj->enableFields("tt_address");
				$queryString.= $this->cObj->enableFields("fe_users");
		 		$res_address_delivery = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_address.company,tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.phone ', 'tt_address, fe_users',$queryString, '', '', '1');
				if ($row_address_delivery = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_address_delivery)) {
					$address_delivery = '<P>Lieferung an: </P>';
					
 					if ($row_address_delivery['company']) {
 						$address_delivery .= $row_address_delivery['company'].'<br />';
 					}
					$address_delivery.= $row_address_delivery['name'].' ' .$row_address_delivery['surname'] .'<br />';
					$address_delivery.= $row_address_delivery['address'].'<br />';
					$address_delivery.= $row_address_delivery['zip'].' '.$row_address_delivery['city'].'<br />';
					$phonenumbers["###ADDRESS_DELIVERY_PHONE###"] = $row_address_delivery['phone'];
				}
			}
		
			//get order_articles
			//todo: maybe define fields by TS?
			$orderlist = '<TABLE border="1" cellpadding="3" cellspacing="0" width="100%">';
			$orderlist.= '<TR><TD align="left">Pos</TD><TD align="left">Art.-Nr.</TD><TD align="left">Bezeichnung</TD><TD align="right">Anz</TD><TD align="right">Preis</TD><TD align="right">Gesamt</TD></TR>';
			$queryString = 'order_uid='.$row['uid'] . ' AND article_type_uid < 2 ' ;
			$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
	 		$res_orderlist = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString, '', '');
			$orderpos = 1;
			//todo: page break if too many products for one page? is enough, what pdf_generator can do?
			while ($row_orderlist = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_orderlist)) {
				$orderlist .='<TR>';
				$orderlist .= '<TD>'.($orderpos++).'</TD>';				
				$orderlist .= '<TD>'.$row_orderlist['article_number'].'</TD>';				
				$orderlist .= '<TD>'.$row_orderlist['title'].'</TD>';				
				$orderlist .= '<TD align="right">'.$row_orderlist['amount'].'</TD>';				
				$orderlist .= '<TD align="right">'.tx_moneylib::format ($row_orderlist['price_gross'], $this->conf['currency'] , false).' EUR</TD>';				
				$orderlist .= '<TD align="right">'.(tx_moneylib::format(($row_orderlist['amount']*$row_orderlist['price_gross']),$this->conf['currency'], false)).' EUR</TD>';				
				$orderlist .='</TR>';
			}
			
			$queryString = 'order_uid='.$row['uid'] . ' AND article_type_uid = 2 ' ;
			$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
	 		$res_orderlist = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString, '', '');
			$orderpos = 1;
			//todo: page break if too many products for one page? is enough, what pdf_generator can do?
			while ($row_orderlist = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_orderlist)) {
				$orderlist .='<TR>';
				$orderlist .='<TD colspan="5" align="right">'.$row_orderlist['title'].'</TD>';				
				$orderlist .= '<TD align="right">'.(tx_moneylib::format(($row_orderlist['amount']*$row_orderlist['price_gross']),$this->conf['currency'], false)).' EUR</TD>';				
				$orderlist .='</TR>';
				$paymentmethod = $row_orderlist['title'];
			}
			
			$queryString = 'order_uid='.$row['uid'] . ' AND article_type_uid = 3 ' ;
			$queryString.= $this->cObj->enableFields("tx_commerce_order_articles");
	 		$res_orderlist = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_order_articles', $queryString, '', '');
			$orderpos = 1;
			//todo: page break if too many products for one page? is enough, what pdf_generator can do?
			while ($row_orderlist = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_orderlist)) {
				$orderlist .='<TR>';
				$orderlist .='<TD colspan="5" align="right">'.$row_orderlist['title'].'</TD>';				
				$orderlist .= '<TD align="right">'.(tx_moneylib::format(($row_orderlist['amount']*$row_orderlist['price_gross']),$this->conf['currency'], false)).' EUR</TD>';				
				$orderlist .='</TR>';
			}
			
			
			
			$orderlist.= '<TR><TD colspan="5" align="right"><B>Gesamt-Brutto</B></TD><TD align="right">'.tx_moneylib::format($row['sum_price_gross'],$this->conf['currency'], false).' EUR</TD></TR>';
			$orderlist.= '<TR><TD colspan="5" align="right">inkl. 19% MWSt</TD><TD align="right">'.tx_moneylib::format($row['sum_price_gross'] - $row['sum_price_net'],$this->conf['currency'], false).' EUR</TD></TR>';
			$orderlist.= '</TABLE>';
			// get the template
			$this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);
			
			// get main subpart
			$templateMarker = "###TEMPLATE###";
			$template = array();
			$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);
		
			// create the content by replacing the marker in the template
			$markerArray = array();
			$markerArray["###ORDER_ID###"] = $this->piVars['order_id'];
			$markerArray["###ADDRESS_INVOICE###"] = $address_invoice;
			$markerArray["###ADDRESS_DELIVERY###"] = $address_delivery;
			$markerArray["###ADDRESS_INVOICE_PHONE###"] = $phonenumbers["###ADDRESS_INVOICE_PHONE###"];
            $markerArray["###ADDRESS_DELIVERY_PHONE###"] = $phonenumbers["###ADDRESS_DELIVERY_PHONE###"];
			//todo: correct field for invoice date? 
			$markerArray["###INVOICE_DATE###"] = strftime("%d.%m.%y", $row['crdate']);
			//todo: get key value
			$markerArray["###ORDERLIST###"] = $orderlist;
			$markerArray["###PAYMENTTYPE###"] = $paymentmethod;
			
			// buid content from template + array		
			$content = $this->cObj->substituteMarkerArrayCached($template, array(), $markerArray , array());
		} else {
			$content = "no data for order_id: ".$this->order_id;
		}
		$content = 	$myt3lib_cs->utf8_to_entities($content);	
		return $this->pi_wrapInBaseClass($content);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/pi6/class.tx_commerce_pi6.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/pi6/class.tx_commerce_pi6.php']);
}

?>
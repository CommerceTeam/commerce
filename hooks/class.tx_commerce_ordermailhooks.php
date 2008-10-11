<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Joerg Sprung (jsp@marketing-factory.de)
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
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 *
 * @package commerce
 * @author Joerg Sprung <jsp@marketing-factory.de>
 *
 * $Id: class.tx_commerce_ordermailhooks.php 459 2006-12-14 18:16:52Z ingo $
 */

require_once(t3lib_extmgm::extPath('cms').'tslib/class.tslib_content.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once(PATH_t3lib.'class.t3lib_htmlmail.php');
require_once(PATH_t3lib.'class.t3lib_formmail.php');
require_once(PATH_t3lib.'class.t3lib_page.php');


class tx_commerce_ordermailhooks  {

	/**
	 * The cObj from class.tslib_content.php
	 *
	 * @var	Object
	 */
	var $cObj;

	/**
	 * The Conversionobject from class.t3lib_cs.php
	 *
	 * @var Object
	 */
	var $csConvObj;

	/**
	 * the content of the TEmplate in Progress
	 *
	 * @var	String
	 */
	var $templateCode;

	/**
	 * Path where finding Templates in CMS-File Structure
	 *
	 * @var	String
	 */
	var $templatePath;

	/**
	 * Caontaing the actual Usermailadress which is in Progress
	 *
	 * @var	String
	 */
	var $customermailadress;

	/**
	 * Tablename of table containing the Template for the specified Situations
	 *
	 * @var String
	 */
	var $tablename;

	/**
	 * Containing the Module configurationoptions
	 *
	 * @var Array
	 */
	var $extConf;
	
	/**
	 * This is just a constructor to instanciate the backend library
	 *
	 * @author Joerg Sprung <jsp@marketing-factory.de>
	 */
	function tx_commerce_ordermailhooks() {
		$this->extConf = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["commerce"]);
		$this->cObj = t3lib_div::makeInstance("tslib_cObj");
		$this->csConvObj = t3lib_div::makeInstance("t3lib_cs");
		$this->templatePath = PATH_site.'/uploads/tx_commerce/';
		$this->templateCode = '';
		$this->customermailadress = '';
		$this->tablename = 'tx_commerce_moveordermails';
	}

	/**
	 * This method converts an sends mails.
	 *
	 * @param	String		$mailcontent		Containing the Mailcontent with subject in Line1^
	 * @param	String		$mailfromname		The Sending E-Mailname
	 * @param	String		$mailfromaddress	The Sending E-Mailadress
	 * @param	String		$userMail			Mailadress of Customer
	 * @return	return		of t3lib_div::plainMailEncoded
	 */
    function ordermoveSendMail($mailcontent,$mailfromname,$mailfromaddress,$userMail,$bcc,&$orderdata,&$template) {
    	
    	
		if (!strstr($userMail, '@') || eregi("\r",$userMail) || eregi("\n",$userMail)) {
			return false;
		}


		/**
		 * @since 2005 12th November
		 * Noved to plainMailEncoded
		 */
		$parts = split(chr(10),$mailcontent,2);		// First line is subject
		$subject=trim($parts[0]);
		$plain_message=trim($parts[1]);

		$headers=array();
		$headers[]='From: '.$mailfromname.' <'.$mailfromaddress.'>';
		$headers[]='Reply-To: '.$mailfromaddress;
		if($bcc!= '' AND strstr($bcc, '@') AND !eregi("\r",$bcc) AND !eregi("\n",$bcc)) {
			$headers[]='Bcc: '.$bcc;
		}
		/**
		 * Check if charset ist set by TS
		 * Otherwise set to default Charset
		 * Checck if mailencoding ist set
		 * otherwise set to 8bit
		 */
//				if (!$this->conf['usermail.']['charset'])
//				{
//					$this->conf['usermail.']['charset']=$GLOBALS['TSFE']->renderCharset;
//				}
//				if (!$this->conf['usermail.']['encoding '])
//				{
//				 	$this->conf['usermail.']['encoding ']='8bit';
//				}
		/**
		 * Convert Text to charset
		 */
		$this->csConvObj->initCharset('utf-8');
		$this->csConvObj->initCharset('8bit');
		$plain_message=$this->csConvObj->conv($plain_message,'utf-8','utf-8');
		$subject=$this->csConvObj->conv($subject,'utf-8','utf-8');

//		if ($this->debugMail==true)
//		{
//			print"<b>Usermail to $userMail</b><pre>$plain_message</pre>\n";
//		}

		#debug($userMail)
		$myHTMLmailobj = t3lib_div::makeInstance('t3lib_htmlmail');
		$myHTMLmailobj->messageid = md5(microtime()).'@' . $_SERVER['SERVER_NAME'];
		unset($myHTMLmailobj->organisation);
		unset($myHTMLmailobj->mailer);
		$myHTMLmailobj->from_email = $mailfromaddress;
		$myHTMLmailobj->from_name = $mailfromname;
		$myHTMLmailobj->replyto_email = $mailfromaddress;
		$myHTMLmailobj->replyto_name = $mailfromname; 
		$myHTMLmailobj->setHeaders();
		if($bcc!= '' AND strstr($bcc, '@') AND !eregi("\r",$bcc) AND !eregi("\n",$bcc)) {
			$myHTMLmailobj->add_header('Bcc: '.$bcc);
		}
		$myHTMLmailobj->recipient=$userMail;
		$myHTMLmailobj->subject=$subject;
		$myHTMLmailobj->setplain($plain_message);
		$myHTMLmailobj->plain_text_header='Content-Type: text/plain; charset=utf-8'.chr(10).'Content-Transfer-Encoding: 8bit';
		
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_ordermailhooks.php']['ordermoveSendMail'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_ordermailhooks.php']['ordermoveSendMail'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		if(is_array($hookObjectsArr)){
			foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'attachToOrdermail')) {
					$hookObj->attachToOrdermail($myHTMLmailobj,$orderdata,$template);
			    }
			}
		}
				
		$myHTMLmailobj->setContent();
		return $myHTMLmailobj->sendTheMail();
	}
	


	/**
	 * Getting a template with all Templatenames in the Mailtemplaterecords
	 * according to the given mailkind and pid
	 *
	 * @param	Integer		$mailkind	0 move in and 1 move out the Order in the Orderfolder
	 * @param	Integer		$pid		The PID of the order to move
	 * @return	Array		Array of templatenames found in Filelist
	 */
	function generateTemplatearray($mailkind,$pid,$order_sys_language_uid) {
		$templates = array();
		$fields = t3lib_BEfunc::BEenableFields($this->tablename);
		#debug($fields);
		$fields = "sys_language_uid=0 AND pid=".$pid . " AND mailkind=" . $mailkind . $fields;
		$res_templates = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->tablename, $fields);
		$t3libPage = t3lib_div::makeInstance('t3lib_pageSelect');
		if($res_templates) {
			while ($row_templates = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_templates)) {
				$temprow = $row_templates;
				$temprow = $t3libPage->getRecordOverlay($this->tablename,$temprow,$order_sys_language_uid);
				$templates[] = $temprow;

			}
		}
		return $templates;
	}

	/**
	 * This method will be used by the initial methods before and after the Order
	 * will be moved to another Orderstate
	 *
	 * @param	Array		$orderdata	Containing the orderdatea like UID and PID
	 * @param	Array		$dataildata	Containing the detaildata to Order like order_id and CustomerUIDs
	 * @param	[type]		$mailkind: ...
	 * @return	void
	 */
	function processOrdermails(&$orderdata,&$detaildata,$mailkind) {
		#$this->customermailadress = '';
		$templates = $this->generateTemplatearray($mailkind,$orderdata['pid'],$detaildata['order_sys_language_uid']);
	
		foreach($templates as $template) {
			
			
			$this->templateCode = t3lib_div::getURL($this->templatePath . $template['mailtemplate']);
			
			$senderemail = $template['senderemail'] == '' ? $this->extConf['defEmailAddress'] : $template['senderemail'];
			if($template['sendername'] == '') {
				if($senderemail == $this->extConf['defEmailAddress']) {
					$sendername = $this->extConf['defEmailSendername'];
				} else {
					$sendername = $senderemail;
				}
			} else {
				$sendername = $template['sendername'];
			}
			$mail = $this->generateMail($orderdata['order_id'],$detaildata);
			
			if ($template['otherreceiver'] != '') {
				
				
				$this->ordermoveSendMail($mail,$sendername,$senderemail,$template['otherreceiver'],$template['BCC'],$orderdata,$template);
			} else {
				
				
				$this->ordermoveSendMail($mail,$sendername,$senderemail,$this->customermailadress,$template['BCC'],$orderdata,$template);
			}
		}
	}

	/**
	 * Initial method for hook that will be performed after the Order
	 * will be moved to another Orderstate
	 *
	 * @param	Array		$orderdata	Containing the orderdatea like UID and PID after moving
	 * @param	Array		$dataildata	Containing the detaildata to Order like order_id and CustomerUIDs
	 * @return	void
	 */
	function moveOrders_preMoveOrder(&$orderdata,&$detaildata) {
		$mailkind = 1;
		$this->processOrdermails($orderdata,$detaildata,$mailkind);
	}

	/**
	 * Initial method for hook that will be performed before the Order
	 * will be moved to another Orderstate
	 *
	 * @param	Array		$orderdata	Containing the orderdatea like UID and PID before moving
	 * @param	Array		$dataildata	Containing the detaildata to Order like order_id and CustomerUIDs
	 * @return	void
	 */
	function moveOrders_postMoveOrder(&$orderdata,&$detaildata) {
		$mailkind = 0;
		$this->processOrdermails($orderdata,$detaildata,$mailkind);
	}

	/**
	 * Renders on Adress in the template
	 * This Method will not replace the Subpart, you have to replace your subpart in your template
	 * by you own
	 *
	 * @param	Address		Array (als Resultset from Select DB or Session)
	 * @param	Subpart		Template subpart
	 * @return	$content		HTML-Content from the given Subpart.
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 */
	function makeAdressView($addressArray,$subpartMarker){

		$markerArray=array();
		$template = $this->cObj->getSubpart($this->templateCode, $subpartMarker);

		$content=$this->cObj->substituteMarkerArray($template,$addressArray,'###|###',1);

		return $content;
	}

	/**
	 * This Method generates a Mailcontent with $this->templatecode
	 * as Mailtemplate. First Line in Template represents the Mailsubject.
	 * The other required data can be queried from database by Parameters.
	 *
	 * @param	String		$orderUID	The uid for the specified Order
	 * @param	Array		$orderData	Contaning additional data like Customer UIDs.
	 * @return	String		The built Mailcontent
	 */
	function generateMail($orderUid, $orderData)
	{
		$content='';
		$markeArray=array();
		#$ordertable = $this->generateOrderlist($orderUid);
		#debug($ordertable);
		$markerArray['###ORDERID###']=$orderUid;

		/**
		 * Since The first line of the mail is the Subject, trim the template
		 */
		$content = ltrim($this->cObj->getSubpart($this->templateCode, '###MAILCONTENT###'));

		// Get The addresses
		$deliveryAdress='';
		if ($orderData['cust_deliveryaddress'])
		{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_address', 'uid='.$orderData['cust_deliveryaddress']);
			if ($data= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
			{
				$deliveryAdress = $this->makeAdressView($data,'###DELIVERY_ADDRESS###');
			}

		}
		$content = $this->cObj->substituteSubpart($content,'###DELIVERY_ADDRESS###',$deliveryAdress);

		$billingAdress='';
		if ($orderData['cust_invoice'])
		{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_address', 'uid='.$orderData['cust_invoice']);
			if ($data= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
			{
				$billingAdress = $this->makeAdressView($data,'###BILLING_ADDRESS###');
				#$markerArray['###CUST_NAME###']=$data['NAME'];
				$this->customermailadress=$data['email'];
			}

		}
		$content = $this->cObj->substituteSubpart($content,'###BILLING_ADDRESS###',$billingAdress);
		
		$invoicelist='';
		
		$content = $this->cObj->substituteSubpart($content,'###INVOICE_VIEW###',$invoicelist);

		/**
		 * Hook for processing Marker Array
		 * Inspired by tt_news
		 * @since 21.01.2006
		 *
		 */
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce_ordermails/mod1/class.tx_commerce_moveordermail.php']['generateMail'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce_ordermails/mod1/class.tx_commerce_moveordermail.php']['generateMail'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj, 'ProcessMarker')) {
				$markerArray=$hookObj->ProcessMarker($markerArray,$this);
			}
		}
	 	$content = $this->cObj->substituteMarkerArray($content, $markerArray);

		return ltrim($content);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_ordermailhooks.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks//class.tx_commerce_ordermailhooks.php"]);
}
?>
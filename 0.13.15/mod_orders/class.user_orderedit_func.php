<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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

require_once (PATH_typo3.'class.db_list.inc');
require_once (PATH_typo3.'class.db_list_extra.inc');

/**
 * User Class for displaying Orders
 *
 * @package commerce
 * @subpackage order view
 * @author Ingo Schmitt <is@marketing-factory.de>
 */
class user_orderedit_func {

 	/**
 	 * Artcile order_id
 	 * Just a hidden field
 	 * @param $PA
 	 * @param $fobj
 	 * @return HTML-Content
 	 */
 	
 	function article_order_id($PA, $fobj){
  		$content.=htmlspecialchars($PA['itemFormElValue']);
 		$content.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'">';
 		return $content;
 	}
	
	

 	/**
 	 * Artcile order_id
 	 * Just a hidden field
 	 * @param $PA
 	 * @param $fobj
 	 * @return HTML-Content
 	 */
 	
 	function sum_price_gross_format($PA, $fobj)	 	{		 	
 	    #	$content.= tx_commerce_div::formatPrice($PA['itemFormElValue']);
 		$content.='<input type="text" disabled name="'.$PA['itemFormElName'].'" value="'.tx_commerce_div::formatPrice($PA['itemFormElValue']/100).'">';
 		return $content;
 	}
	
	
 	/**
 	 * Oder Articles
 	 * Renders the List of aricles
 	 * @param $PA
 	 * @param $fobj
 	 * @return HTML-Content
 	 */
 	
 	 
 	function order_articles($PA, $fobj)	 	{
 		global $TCA;
 		$content='';
 		$foreign_table='tx_commerce_order_articles';
 		$table='tx_commerce_orders';
 		$doc = t3lib_div::makeInstance('smallDoc');
		$doc->backPath = $GLOBALS['BACK_PATH'];
		/**
		 * Load the table TCA into local variable
		 *
		 */
 		t3lib_div::loadTCA($foreign_table);
 		$tca=$GLOBALS['TCA'];
 		$table_tca=$tca[$foreign_table];
 		
 		/**
 		 * GET Storage PID and order_id from Data
 		 */
 		$order_storage_pid=$PA['row']['pid'];
 		$order_id=$PA['row']['order_id'];
 		/**
 		 * Select Order_articles
 		 */
 	
			 
			 
			 /** 
			 * @TODO TS config of fields in list
			 * 
			 */
			 $field_rows=array('amount','title','article_number','price_net','price_gross');
			 $field_row_list=implode(',', $field_rows);
			 
			
			 /**
			  * Taken from class.db_list_extra.php
			  */
			 $titleCol = $TCA[$foreign_table]['ctrl']['label'];
			 $thumbsCol = $TCA[$foreign_table]['ctrl']['thumbnail'];
		
			
			// Check if Orders in this folder are editable
			// 
			$orderEditable=false;
			$check_result=$GLOBALS['TYPO3_DB']->exec_SELECTquery( 'tx_commerce_foldereditorder','pages',"uid = $order_storage_pid " );
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($check_result)==1) {
				if ($res_check=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($check_result)) {
					if ($res_check['tx_commerce_foldereditorder']==1) {
						$orderEditable=true;
					}
				}
				
				
			}
		
			 // Create the SQL query for selecting the elements in the listing:
			
			
			
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery( '*',
 									$foreign_table,
									"pid = $order_storage_pid " 
										.t3lib_BEfunc::deleteClause($foreign_table).
										' AND '."order_id='".$GLOBALS['TYPO3_DB']->quoteStr($order_id,$foreign_table)."'"
										 );
 		
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
			
			 if ($dbCount) {
			 	
			 	
			 /**
			  * Only if we have a result
			  */	
			  		$theData[$titleCol] = '<span class="c-table">'.$GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be.php:order_view.items.article_list',1).'</span> ('.$dbCount.')';
			 		
			 		
			 	    $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf'];
					
					if ($extConf["invoicePageID"] > 0)	{
						$theData[$titleCol] .= ' <a href="../index.php?id='.$extConf["invoicePageID"].'&amp;tx_commerce_pi6[order_id]='.$order_id.'&amp;type='.$extConf["invoicePageType"] . '" target="_blank">'.$GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be.php:order_view.items.print_invoice',1).' *</a>';
					}
			 		
			 		
			 		
			 		$num_cols=count($field_rows);
			 		$out.='
					<tr>
						<td class="c-headLineTable" style="width:95%;" colspan="'.($num_cols+1).'"'.$theData[$titleCol].'</td>
					</tr>';
					/**
					 * Header colum
					 * */
					
						
					
					foreach($field_rows as $field)		{
								
							$out.='<td class="c-headLineTable"><b>'.
								$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($foreign_table,$field)).
								'</b></td>';
								
					}
						$out.='<td class="c-headLineTable"></td>';
					$out.='</tr>';
						
					/**
					 * @TODO: Switch to moneylib to use formating
					 */
					/**
					 * Walk true Data
					 */
					$cc=0;
					while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
						$cc++;
						$sum['amount']+=$row['amount'];
						
						if($PA['row']['pricefromnet'] == 1) {
							$row['price_net'] = $row['price_net'] * $row['amount'];
							$row['price_gross'] = $row['price_net'] * (1 + (((float)$row['tax']) / 100));
						} else {
							$row['price_gross'] = $row['price_gross'] * $row['amount'];
							$row['price_net'] = $row['price_gross'] / (1 + (((float)$row['tax']) / 100));
						}
						
												
						$sum['price_net_value']+=$row['price_net']/100;
						$sum['price_gross_value']+=$row['price_gross']/100;
						
						$row['price_net'] = tx_commerce_div::formatPrice($row['price_net']/100);
						$row['price_gross'] = tx_commerce_div::formatPrice($row['price_gross']/100);	
					
						$row_bgColor=(($cc%2)?'' :' bgcolor="'.t3lib_div::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4,+10,+10,+10).'"');
						
						/**
						 * Not very noice to render html_code directly
						 * @TODO change rendering html code here
						 * 
						 * */
						 $iOut.='<tr '.$row_bgColor.'>';
						 foreach ($field_rows as $field) {
						 	
						 	$wrap=array('','');
						 	switch ($field)	 	{
						 		case $titleCol:
						 			$iOut.='<td>';
						 			 if ( $orderEditable) {
							 			 $params = '&edit['.$foreign_table.']['.$row['uid'].']=edit';
							 			 $wrap=array(
							 					'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'">',
							 					'</a>'
							 					);
						 			 }
						 			break;
						 		case 'amount':		{
						 			 $iOut.='<td>';
						 			 if ( $orderEditable ) {
							 			 $params = '&edit['.$foreign_table.']['.$row['uid'].']=edit&columnsOnly=amount';
							 			 $wrap=array(
							 					'<b><a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/edit2.gif','width="11" height="12"').' title="Edit me" border="0" alt="" />',
							 					'</a></b>'
							 					);
						 			 }
						 			break;
						 		}
						 		
						 		case 'price_net':
						 		case 'price_gross': 	{
						 			$iOut.='<td style="text-align: right">';
						 			break;	
						 		}
						 		
						 		
						 		default:	{
						 			$iOut.='<td>';
						 		}
						 		
						
						 		
						 	}
						 	$iOut.=implode(t3lib_BEfunc::getProcessedValue($foreign_table,$field,$row[$field],100),$wrap);
						 	
						 	
						 	$iOut.='</td>';
						 }
						 /**
						  * Trash icon
						  */
						$iOut.='<td>';
					#	if ( $orderEditable) {
					#		$params='&edit['.$foreign_table.']['.$row['uid'].']=delete';
					#		$iOut.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/delete_record.gif','width="11" height="12"').' title="Delete" border="0" alt="Delete" /></a>';
					#	}
						$iOut.='</td>';
						$iOut.='</tr>';
						
					}

			 		$out.=$iOut;
			 		/**
			 		 * Cerate the summ row
			 		 */
			 		$out.='<tr>';
			 		$sum['price_net'] = tx_commerce_div::formatPrice($sum['price_net_value']);
					$sum['price_gross'] = tx_commerce_div::formatPrice($sum['price_gross_value']);							
				
					foreach($field_rows as $field)	{
						
						switch ($field) {
						 	
						 		
							case 'price_net':
							case 'price_gross':		{

								$out.='<td class="c-headLineTable" style="text-align: right"><b>';
								break;	
							}
						 		
						 		
							default:	{
								$out.='<td class="c-headLineTable"><b>';
							}
						 		
						
						 		
						}
						if ($sum[$field]>0)	{
							$out.=t3lib_BEfunc::getProcessedValueExtra($foreign_table,$field,$sum[$field],100);	
						}
						
						$out.='</b></td>';
					}
					$out.='<td class="c-headLineTable"></td>';
					$out.='</tr>';
			 		 
			 }
			
			 $out='

			

			<!--
				DB listing of elements:	"'.htmlspecialchars($table).'"
			-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
					'.$out.'
				</table>';
			$content.=$out;
			
			/**
			 * 
			 * New article
			 */
			 /**
			  * Deaktivated as first step, will see if remove
			  * @todo should realy delated ?
			$params='&edit['.$foreign_table.']['.$order_storage_pid.']=new&defVals['.$foreign_table.'][order_id]='.urlencode($order_id);
			$content.='<div id="typo3-newRecordLink">';
			$content.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'">';
			$content.=$GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be.php:order_view.items.add_article',1);
			$content.='</a>';
			$content.='</div>';
			*/
			/**
			 * Always
			 * Update sum_price_net and sum_price_gross
			 * To Be shure everything is ok
			 */
			 $values=array('sum_price_gross' => $sum['price_gross_value']*100,
			 			   'sum_price_net' => $sum['price_net_value']*100);
 			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,"order_id='".$GLOBALS['TYPO3_DB']->quoteStr($order_id,$foreign_table)."'",$values);
 		
 	
 		return $content;
 	}
 	/**
 	 * Oder Status
 	 * Selects only the oder folders from the pages List
 	 * @param($data)
 	 * @see tcafiles/tx_commerce_orders.tca.php
 
 	 */
 	
 	
 	function order_status(&$data,&$pObj)	{
 		
 		
 		/**
 		 * Ggf folder anlegen, wenn Sie nicht da sind
 		 * 
 		 */
 		
 		
		tx_commerce_create_folder::init_folders();
		
		/**
		 * create a new data item array
		 */
		
		$data['items']=array();
		
		# Find the right pid for the Ordersfolder 
				 
		list($orderPid,$defaultFolder,$folderList) = array_unique(tx_commerce_folder_db::initFolders('Orders','Commerce',0,'Commerce'));
 		
 		/**
 		 * Get the poages below $order_pid
 		 */
 		
 		/**
 		 * Check if the Current PID is below $orderPid,
 		 * id is below orderPid we could use the parent of this record to build up the select Drop Down
 		 * otherwhise use the default PID
 		 */
 		$myPID=$data['row']['pid'];
 		
 		$rootline=t3lib_BEfunc::BEgetRootLine($myPID);
 		$rootlinePIDs=array();
 		foreach ($rootline as $pages){
 			if (isset($pages['uid'])) {
 				$rootlinePIDs[]=$pages['uid'];	
 			}
 		}
 		
 		if (in_array($orderPid,$rootlinePIDs)) {
 			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery('pid ','pages',"uid = $myPID" .t3lib_BEfunc::deleteClause('pages'),'','sorting' );
 			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)>0) 		{
	 			while ($return_data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))		{
	 				$orderPid=$return_data['pid'];
	 				
	 			}
	 			$GLOBALS['TYPO3_DB']->sql_free_result($result);
	 			
 			}
 		}
	 	$data['items'] = tx_commerce_belib::getOrderFolderSelector($orderPid, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['OrderFolderRecursiveLevel']);
	}


 	/**
 	 * Invoice Adresss
 	 * Renders the invoice adresss
 	 * @param $PA
 	 * @param $fobj
 	 * @return HTML-Content
 	 */
 	 
 	function invoice_adress($PA, $fobj)		{
 		$content='';
 		
 		
 		/**
 		 * Normal
 		 */
 		$content.= $this->adress($PA,$fobj,'tt_address',$PA['itemFormElValue']);
 		return $content;
 	}
 	
	/*
 	 * Renders the crdate
	 * @author Volker Graubaum
 	 * @param $PA
 	 * @param $fobj
 	 * @return HTML-Content
	*/
	
	
	function crdate($PA,$fObj)	{
	    $PA['itemFormElValue'] = date('d.m.y',$PA['itemFormElValue']);
	
 		/**
 		 * Normal
 		 */
		$content.= $fObj->getSingleField_typeNone_render(array(),$PA['itemFormElValue']);
		return $content;
 	}
	
 	/**
 	 * Invoice Adresss
 	 * Renders the invoice adresss
 	 * @param $PA
 	 * @param $fobj
 	 * @return HTML-Content
 	 */
 	 
 	function delivery_adress($PA, $fobj){
 		
 		
 		/**
 		 * Normal
 		 */
 		$content.= $this->adress($PA,$fobj,'tt_address',$PA['itemFormElValue']);
 		return $content;
 	}
 	
 
 	
 	/**
 	 * Adresss
 	 * Renders the an Adress adresss block
 	 * @param $PA
 	 * @param $fobj
 	 * @param $table table_name
 	 * @param $uid Record UID
 	 * @return HTML-Content
 	 */
 	
 	function adress($PA, $fobj,$table,$uid)	{
 		/**
 		 * instatiate Template Class
 		 * as this class is included via alt_doc we don't have to require template.php
 		 * in fact an require would cause an error
 		 * 
 		 */
 		$doc = t3lib_div::makeInstance('smallDoc');
		$doc->backPath = $GLOBALS['BACK_PATH'];
		/**
		 * Load the table TCA into local variable
		 *
		 */
 		t3lib_div::loadTCA($table);
 		$tca=$GLOBALS['TCA'];
 		$table_tca=$tca[$table];
 		
 		$content='';
 		/**
 		 * 
 		 * Fist select Data from Database
 		 * 
 		 */
 		
		if ($data_row=t3lib_BEfunc::getRecord($table,$uid,'uid,'.$table_tca['interface']['showRecordFieldList'])) {
               
 			/**
 			 * We should get just one Result
 			 * So Render Result as $arr for template::table()
 			 */
 			
 			 /**
 			  * Better formating via template class
 			  * @todo locallang for 'blub'
 			  */
 			# $content.=$doc->sectionheader(t3lib_BEfunc::getRecordTitle($table, $data_row,1));
 			 $content.=$doc->spacer(10);
 			 /**
 			 * TYPO3 Core API's Page 63
 			 */
 			
 			 $params = '&edit['.$table.']['.$uid.']=edit';
 			 
 			 $wrap_the_header=array('<b><a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'">','</a></b>');
 			 $content.=$doc->getHeader($table,$data_row,'Local Lang definition is missing',1, $wrap_the_header);
 			 $content.=$doc->spacer(10);
 			 $display_arr=array();
 			
 			 foreach ($data_row as $key => $value) 	 {
 			 	/**
 			 	 * Walk thrue rowset,
 			 	 * get TCA values
 			 	 * and LL Names
 			 	 */
 			 	
 		
 				 if (t3lib_div::inList($table_tca['interface']['showRecordFieldList'],$key)) { 
 				 	/**
 				 	 * Get The label
 				 	 */
 				 	
 				 	 $local_row_name=$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($table,$key));
 			 		 $display_arr[$key]=array( $local_row_name,htmlspecialchars($value));
 				 }
 			 	
 			 }
 			 $tableLayout = array (
 			 		'table' =>  array('<table border="0" cellspacing="2" cellpadding="2">','</table>'),
 			 		'defRowEven' => array (
 			 			'defCol' => array('<td valign="top" class="bgColor5">','</td>')
 			 		),
 			 		
					'defRowOdd' => array (
					
						'defCol' => array('<td valign="top" class="bgColor4">','</td>')
					)
			 );
 			 $content.=$doc->table($display_arr,  $tableLayout);
 			
 			
 			 
 			
 		}
 		/**
 		 * 
 		 */
 		$content.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'">';
 		return $content;
 	}
 	
 	
 	function fe_user_orders($PA, $fobj){
 		global $BE_USER;
 	
 		$dblist = t3lib_div::makeInstance('tx_commerce_order_localRecordlist');
		#$dblist->additionalOutTop = $this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->script = 'index.php';
		$dblist->calcPerms = $BE_USER->calcPerms($this->pageinfo);
		$dblist->thumbs = $BE_USER->uc['thumbnailsByDefault'];
		$dblist->returnUrl=$this->returnUrl;
		#$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
		$dblist->allFields = 1;
		if($this->userID){
		    $dblist->onlyUser = $this->userID;
		}
		
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 0;	
		$CB = t3lib_div::_GET('CB');	// CB is the clipboard command array
		if ($this->cmd=='setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge(t3lib_div::_POST('CBH'),t3lib_div::_POST('CBC')),$this->cmd_table);
		}
		$dblist->onlyUser=$PA['row']['uid'];
		$dblist->start(null,'tx_commerce_orders',0);
		
		$dblist->generateList();
		$dblist->writeBottom();
	
		return $dblist->HTMLcode;
 		
 	}
 		/*
 		 .
 				'<h2>My Own Form Field:</h2>';
 				/* .
 				'<input�name="'.
   				$PA['itemFormElName'].
				'"�value="'.
				htmlspecialchars($PA['itemFormElValue']).
				'"�onchange="'.
				htmlspecialchars(implode('',$PA['fieldChangeFunc'])).
				'"'.$PA['onFocus'].
				'�/></div>';*/
 		
 		
 	
 	
 	
 	
 }
 
 if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_orders/class.user_orderedit_func.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_orders/class.user_orderedit_func.php']);
}
 
 ?>
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Ingo Schmitt <is@marketing-factory.de>
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
 * User Class for displaying Orders
 *
 * @package commerce
 * @subpackage order view
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @maintainer Ingo Schmitt <is@marketing-factory.de>
 * 
 * $Id$
 */

require_once (PATH_t3lib.'class.t3lib_recordlist.php');
require_once (PATH_t3lib.'class.t3lib_div.php');
require_once (PATH_typo3.'class.db_list.inc');
require_once (PATH_typo3.'class.db_list_extra.inc');
 
require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_div.php');

class user_attributeedit_func
 {
 	
 	
 	/**
 	 * valuelis
 	 * renders the valulist to a value
 	 * @param  $PA
 	 * @param $fobj
 	 * @return HTML-Content
 	 */
	function valuelist($PA, $fobj)	
	{
		global $TCA;
		$content='';
		global $TCA;
 		$content='';
 		$foreign_table='tx_commerce_attribute_values';
 		$table='tx_commerce_attributes';
 		$doc = t3lib_div::makeInstance('smallDoc');
		$doc->backPath = $GLOBALS['BACK_PATH'];
		/**
		 * Load the table TCA into local variable
		 *
		 */
 		t3lib_div::loadTCA($foreign_table);
 		$tca=$GLOBALS['TCA'];
 		$table_tca=$tca[$foreign_table];
 		
 		$attributeStoragePid=$PA['row']['pid'];
 		$attributeUid=$PA['row']['uid'];
		/**
		 * Select Attribute Values
		 */
 	
			 
			 
			 /** 
			 * @TODO TS config of fields in list
			 * 
			 */
			 $field_rows=array('attributes_uid','value');
			 $field_row_list=implode(',', $field_rows);
			 
			
			 /**
			  * Taken from class.db_list_extra.php
			  */
			 $titleCol = $TCA[$foreign_table]['ctrl']['label'];
			 $thumbsCol = $TCA[$foreign_table]['ctrl']['thumbnail'];
		
			
		
			 // Create the SQL query for selecting the elements in the listing:
			
			
			
			$result=$GLOBALS['TYPO3_DB']->exec_SELECTquery( '*',
 									$foreign_table,
									"pid = $attributeStoragePid " 
										.t3lib_BEfunc::deleteClause($foreign_table).
										' AND '."attributes_uid='".$GLOBALS['TYPO3_DB']->quoteStr($attributeUid,$foreign_table)."'"
										 );
 		
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
			
			 if ($dbCount)	
			 {
			 	
			 	
			 /**
			  * Only if we have a result
			  */	
			  		$theData[$titleCol] = '<span class="c-table">'.$GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be.php:attributeview.valuelist',1).'</span> ('.$dbCount.')';
			 		$num_cols=count($field_rows);
			 		$out.='
					<tr>
						<td class="c-headLineTable" style="width:95%;" colspan="'.($num_cols+1).'">'.$theData[$titleCol].'</td>
					</tr>';
					/**
					 * Header colum
					 * */
					
						
					$out.='<tr>';
					foreach($field_rows as $field)
					{
								
							$out.='<td class="c-headLineTable"><b>'.
								$GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($foreign_table,$field)).
								'</b></td>';
								
					}
						$out.='<td class="c-headLineTable"></td>';
					$out.='</tr>';
						
						
					/**
					 * Walk true Data
					 */
					$cc=0;
					while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	
					{
							
						$cc++;
						$row_bgColor=(($cc%2)?'' :' bgcolor="'.t3lib_div::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4,+10,+10,+10).'"');
						
						/**
						 * Not very noice to render html_code directly
						 * @todo chan ge rendering html code here
						 * 
						 * */
						 $iOut.='<tr '.$row_bgColor.'>';
						 foreach ($field_rows as $field)
						 {
						 	$iOut.='<td>';
						 	$wrap=array('','');
						 	switch ($field)
						 	{
						 		case $titleCol:
						 			
						 			 $params = '&edit['.$foreign_table.']['.$row['uid'].']=edit';
						 			 $wrap=array(
						 					'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'">',
						 					'</a>'
						 					);
						 		
						 			break;
						 		
						 		
						
						 		
						 	}
						 	$iOut.=implode(t3lib_BEfunc::getProcessedValue($foreign_table,$field,$row[$field],100),$wrap);
						 	
						 	
						 	$iOut.='</td>';
						 }
						 /**
						  * Trash icon
						  */
						$iOut.='<td>&nbsp;';
						#$params='&delete['.$foreign_table.']['.$row['uid'].']=delete';
						#$iOut.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/delete_record.gif','width="11" height="12"').' title="Delete" border="0" alt="" /></a>';
						$iOut.= '<a href="#" onclick="deleteRecord(\''.$foreign_table.'\', ' .$row['uid'] .', \'alt_doc.php?edit[tx_commerce_attributes][' .$attributeUid .']=edit\');"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/delete_record.gif','width="11" height="12"').' title="Delete" border="0" alt="" /></a></td>';
						
						#$iOut.='</td>';
						$iOut.='</tr>';
					
					}

			 		$out.=$iOut;
			 		/**
			 		 * Cerate the summ row
			 		 */
			 		$out.='<tr>';
			 									
				
					foreach($field_rows as $field)
					{
						$out.='<td class="c-headLineTable"><b>';
						if ($sum[$field]>0)
						{
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
			
		
			$params='&edit['.$foreign_table.']['.$attributeStoragePid.']=new&defVals['.$foreign_table.'][attributes_uid]='.urlencode($attributeUid);
			$content.='<div id="typo3-newRecordLink">';
			$content.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'">';
			$content.=$GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be.php:attributeview.addvalue',1);
			$content.='</a>';
			$content.='</div>';
			
			/**
			 * Always
			 * Update sum_price_net and sum_price_gross
			 * To Be shure everything is ok
			 */
			 /*
			 $values=array('sum_price_gross' => $sum['price_gross_value']*100,
			 			   'sum_price_net' => $sum['price_net_value']*100);
 			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,"order_id='".$GLOBALS['TYPO3_DB']->quoteStr($order_id,$foreign_table)."'",$values);
 		*/
		
		return $content;	
	}
	

 	
 	
 	
 	
 	
 }
 
 if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/mod_category/class.user_attributeedit_func.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/mod_category/class.user_attributeedit_func.php']);
}
 
 ?>
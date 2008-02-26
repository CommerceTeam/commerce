<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999 - 2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2005 - 2006 Daniel Sch�ttgen <ds@marketing-factory.de>
*  (c) 2005 - 2006 Ingo Schmitt <is@marketing-factory.de>  
*   All  rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Renders the Orderlist in the BE oredrmodule
 *
 * @internal Maintainer Ingo Schmitt <is@marketing-factory.de> 
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Daniel Sch�ttgen <ds@marketing-factory.de>
 * @author  Ingo Schmitt <is@marketing-factory.de>
 * @author  J�rg Sprung <jsp@marketing-factory.de>
 * 
 * $Id$
 */

 /**
  * @TODO Check Xclass inclusion in inhertation
  */

require_once (PATH_typo3.'class.db_list.inc');
require_once (PATH_typo3.'class.db_list_extra.inc');
require_once (t3lib_extMgm::extPath('moneylib').'class.tx_moneylib.php');
require_once(PATH_t3lib.'class.t3lib_tceforms.php');

 
 class tx_commerce_order_localRecordlist extends localRecordList {
  var $alternateBgColors = 1;
  
 	function makeQueryArray($table, $id, $addWhere="",$fieldList='*')
 	{
 		if ($this->sortField){
 			$orderby=$this->sortField.' ';
 			if ($this->sortRev==1)
 			{
 				$orderby.='DESC';
 			}
 		}
 		else
 		{
 			$orderby='crdate DESC';
 		}
 		
 		if ($id>0){
 			$query_array=array(
 			'SELECT' => 'DISTINCT tx_commerce_order_articles.order_id, delivery_table.order_id as order_number, tx_commerce_order_articles.article_type_uid, tx_commerce_order_articles.title as payment, delivery_table.title as delivery, tx_commerce_orders.uid,tx_commerce_orders.pid, tx_commerce_orders.crdate, tx_commerce_orders.order_id, tx_commerce_orders.sum_price_gross, tt_address.tx_commerce_address_type_id, tt_address.company ,tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.email,tt_address.phone as phone_1, tt_address.mobile as phone_2,tx_commerce_orders.cu_iso_3_uid, tx_commerce_orders.tstamp, tx_commerce_orders.uid as articles, tx_commerce_orders.comment, tx_commerce_orders.internalcomment, tx_commerce_orders.order_type_uid as order_type_uid_noName',
 			'FROM' =>'tx_commerce_orders,tt_address, tx_commerce_order_articles, tx_commerce_order_articles as delivery_table',
 			'WHERE' =>'delivery_table.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.article_type_uid = '.PAYMENTArticleType.' AND delivery_table.article_type_uid = '.DELIVERYArticleType.' AND tx_commerce_orders.deleted = 0 and tx_commerce_orders.cust_deliveryaddress = tt_address.uid AND tx_commerce_orders.pid='.$id.$addWhere ,
 			'GROUPBY' => '',
 			'ORDERBY' => $orderby,
 			'sorting' => '',
			'LIMIT' => ''
 	
 		
 		);
 		}else{
 			
 		
			require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_create_folder.php');
			tx_commerce_create_folder::init_folders();
			
			/**
			 * @TODO bitte aus der ext config nehmen, volker angefragt
			 */
					 
			# Find the right pid for the Ordersfolder 
			 
			list($orderPid,$defaultFolder,$folderList) = array_unique(tx_graytree_folder_db::initFolders('Orders','Commerce',0,'Commerce'));
 			
 			$ret = tx_commerce_belib::getOrderFolderSelector($orderPid, PHP_INT_MAX ) ;
 			
 			foreach ($ret as $elements) {
 			
 				$list[]=$elements[1];
 				
 			}
 			$list = implode(',',$list);
 			
 			$query_array=array(
 			'SELECT' => 'DISTINCT tx_commerce_order_articles.order_id,delivery_table.order_id as order_number, tx_commerce_order_articles.article_type_uid, tx_commerce_order_articles.title as payment, delivery_table.title as delivery, tx_commerce_orders.uid,tx_commerce_orders.pid, tx_commerce_orders.crdate, tx_commerce_orders.order_id, tx_commerce_orders.sum_price_gross, tt_address.tx_commerce_address_type_id, tt_address.company,tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.email,tt_address.phone as phone_1, tt_address.mobile as phone_2,tx_commerce_orders.cu_iso_3_uid, tx_commerce_orders.tstamp, tx_commerce_orders.uid as articles, tx_commerce_orders.comment, tx_commerce_orders.internalcomment, tx_commerce_orders.order_type_uid as order_type_uid_noName',
 			'FROM' =>'tx_commerce_orders,tt_address, tx_commerce_order_articles, tx_commerce_order_articles as delivery_table',
 			'WHERE' =>'delivery_table.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.article_type_uid = '.PAYMENTArticleType.' AND delivery_table.article_type_uid = '.DELIVERYArticleType.' AND tx_commerce_orders.deleted = 0 and tx_commerce_orders.cust_deliveryaddress = tt_address.uid AND tx_commerce_orders.pid in ('.$list.') '.$addWhere ,
 			'GROUPBY' => '',
 			'ORDERBY' => $orderby,
 			'sorting' => '',
			'LIMIT' => ''
			);
 		}
 	
 		$this->dontShowClipControlPanels = 1;
 		return $query_array;
 		#return parent::makeQueryArray($table, $id, $addWhere="",$fieldList='*');
 	}
 	


	/**
	 * Writes the top of the full listing
	 *
	 * @param	array		Current page record
	 * @return	void		(Adds content to internal variable, $this->HTMLcode)
	 */
	function writeTop($row)	{
		global $LANG;
		#echo t3lib_div::debug($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
			// Makes the code for the pageicon in the top
		$this->pageRow = $row;
		$this->counter++;
		$alttext = t3lib_BEfunc::getRecordIconAltText($row,'pages');
		
		
		$iconImg = t3lib_iconWorks::getIconImage('pages',$row,$this->backPath,'class="absmiddle" title="'.htmlspecialchars($alttext).'"');	
	
		$titleCol = 'test';	// pseudo title column name
		$this->fieldArray = Array($titleCol,'up');		// Setting the fields to display in the list (this is of course "pseudo fields" since this is the top!)


			// Filling in the pseudo data array:
		$theData = Array();
		$theData[$titleCol] = $this->widthGif;

			// Get users permissions for this row:
		$localCalcPerms = $GLOBALS['BE_USER']->calcPerms($row);

		$theData['up']=array();

			// Initialize control panel for currect page ($this->id):
			// Some of the controls are added only if $this->id is set - since they make sense only on a real page, not root level.
		$theCtrlPanel =array();


			// If edit permissions are set (see class.t3lib_userauthgroup.php)
		if ($localCalcPerms&2)	{

				// Adding "New record" icon:
			if (!$GLOBALS['SOBE']->modTSconfig['properties']['noCreateRecordsLink']) 	{
				$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars('return jumpExt(\'db_new.php?id='.$this->id.'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_el.gif','width="11" height="12"').' title="'.$LANG->getLL('newRecordGeneral',1).'" alt="" />'.
								'</a>';
			}

				// Adding "Hide/Unhide" icon:
			if ($this->id)	{

				//@TODO: cange the return path

				if ($row['hidden'])	{
					$params='&data[pages]['.$row['uid'].'][hidden]=0';
					$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params,-1).'\');').'">'.
									'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="'.$LANG->getLL('unHidePage',1).'" alt="" />'.
									'</a>';
				} else {
					$params='&data[pages]['.$row['uid'].'][hidden]=1';
					$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params,-1).'\');').'">'.
									'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="'.$LANG->getLL('hidePage',1).'" alt="" />'.
									'</a>';
				}
			}

		}

			// "Paste into page" link:
		if (($localCalcPerms&8) || ($localCalcPerms&16))	{
			$elFromTable = $this->clipObj->elFromTable('');
			if (count($elFromTable))	{
				$theCtrlPanel[]='<a href="'.htmlspecialchars($this->clipObj->pasteUrl('',$this->id)).'" onclick="'.htmlspecialchars('return '.$this->clipObj->confirmMsg('pages',$this->pageRow,'into',$elFromTable)).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/clip_pasteafter.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_paste',1).'" alt="" />'.
								'</a>';
			}
		}

			// Finally, compile all elements of the control panel into table cells:
		if (count($theCtrlPanel))	{
			$theData['up'][]='

				<!--
					Control panel for page
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-ctrltop">
					<tr>
						<td>'.implode('</td>
						<td>',$theCtrlPanel).'</td>
					</tr>
				</table>';
		}


		// Add "CSV" link, if a specific table is shown:
		if ($this->table)	{
			$theData['up'][]='<a href="'.htmlspecialchars($this->listURL().'&csv=1').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/csv.gif','width="27" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.csv',1).'" alt="" />'.
							'</a>';
		}

			// Add "Export" link, if a specific table is shown:
		if ($this->table && t3lib_extMgm::isLoaded('impexp'))	{
			$theData['up'][]='<a href="'.htmlspecialchars($this->backPath.t3lib_extMgm::extRelPath('impexp').'app/index.php?tx_impexp[action]=export&tx_impexp[list][]='.rawurlencode($this->table.':'.$this->id)).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath('impexp').'export.gif',' width="18" height="16"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.export',1).'" alt="" />'.
							'</a>';
		}

			// Add "refresh" link:
		$theData['up'][]='<a href="'.htmlspecialchars($this->listURL()).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/refresh_n.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.reload',1).'" alt="" />'.
						'</a>';


			// Add icon with clickmenu, etc:
		if ($this->id)	{	// If there IS a real page...:

				// Setting title of page + the "Go up" link:
			$theData[$titleCol].='<br /><span title="'.htmlspecialchars($row['_thePathFull']).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($row['_thePath'],-$this->fixedL)).'</span>';
			$theData['up'][]='<a href="'.htmlspecialchars($this->listURL($row['pid'])).'" onclick="setHighlight('.$row['pid'].')">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/pages_up.gif','width="18" height="16"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel',1).'" alt="" />'.
							'</a>';

				// Make Icon:
			$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg,'pages',$this->id) : $iconImg;
		} else {	// On root-level of page tree:

				// Setting title of root (sitename):
			$theData[$titleCol].='<br />'.htmlspecialchars(t3lib_div::fixed_lgd_cs($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],-$this->fixedL));

				// Make Icon:
			$theIcon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif','width="18" height="16"').' alt="" />';
		}

			// If there is a returnUrl given, add a back-link:
		if ($this->returnUrl)	{
			$theData['up'][]='<a href="'.htmlspecialchars(t3lib_div::linkThisUrl($this->returnUrl,array('id'=>$this->id))).'" class="typo3-goBack">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/goback.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.goBack',1).'" alt="" />'.
							'</a>';
		}

		$theData['up'][]= $this->additionalOutTop;
			// Finally, the "up" pseudo field is compiled into a table - has been accumulated in an array:
		$theData['up']='
			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>'.implode('</td>
					<td>',$theData['up']).'</td>
				</tr>
			</table>';

			// ... and the element row is created:
		$out.=$this->addelement(1,$theIcon,$theData,'',$this->leftMargin);

			// ... and wrapped into a table and added to the internal ->HTMLcode variable:
		$this->HTMLcode.='


		<!--
			Page header for db_list:
		-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-dblist-top">
				'.$out.'
			</table>';
	}

 	
 	function generateList()	{
		global $TCA;
		t3lib_div::loadTCA("tx_commerce_orders");
			// Traverse the TCA table array:
		reset($TCA);
	
		/**
		 * @TODO auf eine tabell ebeschraenken
		 */
		foreach ($TCA as $tableName){
			
				// Checking if the table should be rendered:
			if ((!$this->table || $tableName==$this->table) && (!$this->tableList || t3lib_div::inList($this->tableList,$tableName)) && $GLOBALS['BE_USER']->check('tables_select',$tableName))	{		// Checks that we see only permitted/requested tables:
			
					// Load full table definitions:
				t3lib_div::loadTCA($tableName);

					// iLimit is set depending on whether we're in single- or multi-table mode
				if ($this->table)	{
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxSingleDBListItems'])?intval($TCA[$tableName]['interface']['maxSingleDBListItems']):$this->itemsLimitSingleTable);
				} else {
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxDBListItems'])?intval($TCA[$tableName]['interface']['maxDBListItems']):$this->itemsLimitPerTable);
				}
				if ($this->showLimit)	$this->iLimit = $this->showLimit;
				$this->iLimit = PHP_INT_MAX;
				/**
				 * @TODO Change this hard limit
				 */
					// Setting fields to select:
//				if ($this->allFields)	{
//					$fields = $this->makeFieldList($tableName);
//					
//					$fields[]='_PATH_';
//					$fields[]='_CONTROL_';
//					
//					if (is_array($this->setFields[$tableName]))	{
//						
//						$fields = array_intersect($fields,$this->setFields[$tableName]);
//					} else {
//						
//						//$fields = array();
//					}
//				} else {
//					
//					$fields = array();
//				}

					// Finally, render the list:		
				
					// Check for order_number and order_title view
				$_EXTKEY = 'commerce';
				if($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['showArticleNumber'] == 1 && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['showArticleTitle'] == 1){
					$this->myfields=array('order_type_uid_noName',"order_id","crdate","delivery","payment","numarticles","sum_price_gross","company","surname","name","address","zip","city","email","phone_1","phone_2","articles", "order_number","article_number","article_name");				
				}else if($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['showArticleNumber'] == 1){
					$this->myfields=array('order_type_uid_noName',"order_id","crdate","delivery","payment","numarticles","sum_price_gross","company","surname","name","address","zip","city","email","phone_1","phone_2","articles", "order_number","article_number");
				}else if($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['showArticleTitle'] == 1){
					$this->myfields=array('order_type_uid_noName',"order_id","crdate","delivery","payment","numarticles","sum_price_gross","company","surname","name","address","zip","city","email","phone_1","phone_2","articles", "order_number","article_name");
				}else{
					$this->myfields=array('order_type_uid_noName',"order_id","crdate","delivery","payment","numarticles","sum_price_gross","company","surname","name","address","zip","city","email","phone_1","phone_2","articles", "order_number");
				}
					//CSV Export
				if ($this->csvOutput){
					$this->myfields=array("order_id","crdate","delivery","payment","numarticles","sum_price_gross","company","surname","name","address","zip","city","email","phone_1","phone_2", "comment", "internalcomment","articles");
				}
				
				$this->HTMLcode.=$this->getTable($tableName, $this->id,implode(',',$this->myfields));
			}
		}
	}
	
	
	/**
	 * Wrapping input code in link to URL or email if $testString is either.
	 * @see TYPO3 4.0.0, coped from there
	 * @return	string		Link-Wrapped $code value, if $testString was URL or email.
	 */
	function mylinkUrlMail($code,$testString)	{

			// Check for URL:
		$schema = parse_url($testString);
		if ($schema['scheme'] && t3lib_div::inList('http,https,ftp',$schema['scheme']))	{
			return '<a href="'.htmlspecialchars($testString).'" target="_blank">'.$code.'</a>';
		}

			// Check for email:
		if (t3lib_div::validEmail($testString))	{
			return '<a href="mailto:'.htmlspecialchars($testString).'" target="_blank">'.$code.'</a>';
		}

			// Return if nothing else...
		return $code;
	}

	/**
	 * Rendering a single row for the list
	 *
	 * @param	string		Table name
	 * @param	array		Current record
	 * @param	integer		Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param	string		Table field (column) where header value is found
	 * @param	string		Table field (column) where (possible) thumbnails can be found
	 * @param	integer		Indent from left.
	 * @return	string		Table row for the element
	 * @access private
	 * @see getTable()
	 */
	function renderListRow($table,$row,$cc,$titleCol,$thumbsCol,$indent=0)	{
		$iOut = '';

		
		if (substr(TYPO3_version, 0, 3)  >= '4.0') {
			// In offline workspace, look for alternative record:
			t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace);
		}
			// Background color, if any:
		$row_bgColor=
			$this->alternateBgColors ?
			(($cc%2)?'' :' bgcolor="'.t3lib_div::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4,+10,+10,+10).'"') :
			'';

			// Overriding with versions background color if any:
		$row_bgColor = $row['_CSSCLASS'] ? ' class="'.$row['_CSSCLASS'].'"' : $row_bgColor;

			// Initialization
		$alttext = t3lib_BEfunc::getRecordIconAltText($row,$table);
		$recTitle = t3lib_BEfunc::getRecordTitle($table,$row);

			// Incr. counter.
		$this->counter++;

			// The icon with link
		$iconImg = t3lib_iconWorks::getIconImage($table,$row,$this->backPath,'title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
		
			// Icon for order comment and delivery address
		if ($row['comment'] != '' && $row['internalcomment'] != ''){
			if($row['tx_commerce_address_type_id'] == 2){
				$iconImg = '<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders_add_user_int.gif','title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
			}else{
				$iconImg = '<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders_user_int.gif','title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
			} 
		}else if($row['comment'] != ''){
			if($row['tx_commerce_address_type_id'] == 2){
				$iconImg = '<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders_add_user.gif','title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));				
			}else{
				$iconImg = '<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders_user.gif','title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
			}
		}else if($row['internalcomment'] != ''){
			if($row['tx_commerce_address_type_id'] == 2){
				$iconImg = '<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders_add_int.gif','title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));				
			}else{
				$iconImg = '<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders_int.gif','title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
			}
		}else{
			if($row['tx_commerce_address_type_id'] == 2){
				$iconImg = '<img'.t3lib_iconWorks::skinImg($this->backPath,t3lib_extMgm::extRelPath(COMMERCE_EXTkey).'res/icons/table/orders_add.gif','title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
			}else{
				$iconImg = t3lib_iconWorks::getIconImage($table,$row,$this->backPath,'title="'.htmlspecialchars($alttext).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
			}
		}
		
		$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg,$table,$row['uid']) : $iconImg;
		$extConf=unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["commerce"]);
			// Preparing and getting the data-array
		$theData = Array();
		
		foreach($this->fieldArray as $fCol)	{
			if ($fCol=='pid') {
				$theData[$fCol]=$row[$fCol];
			} elseif ($fCol=='sum_price_gross') {
				if ($this->csvOutput) {
					$row[$fCol]=$row[$fCol]/100;
				}else {
					$theData[$fCol]=tx_moneylib::format($row[$fCol],'EUR');
				}
			} elseif ($fCol=='crdate') {
				$theData[$fCol] = t3lib_BEfunc::date($row[$fCol]);
			
				$row[$fCol]=t3lib_BEfunc::date($row[$fCol]);
			}	elseif ($fCol=='tstamp') {
				$theData[$fCol] = t3lib_BEfunc::date($row[$fCol]);
			
				$row[$fCol]=t3lib_BEfunc::date($row[$fCol]);
			} elseif ($fCol=='articles') {
				$articleNumber = array();
				$articleName = array();
				$res_articles=$GLOBALS['TYPO3_DB']->exec_SELECTquery('article_number,title,order_uid','tx_commerce_order_articles','order_uid = '.$row['uid']);
				while (($lokalRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_articles))) {
					$articles[]=$lokalRow['article_number'].':'.$lokalRow['title'];
					$articleNumber[] = $lokalRow['article_number'];
					$articleName[] = $lokalRow['title'];
				}
							
				if ($this->csvOutput) {
					$theData[$fCol] = implode(',',$articles);
					$row[$fCol]  = implode(',',$articles);
				}else{
					$theData[$fCol] = '<input type="checkbox" name="orderUid[]" value="'.$row['uid'].'">';
				}
			}elseif ($fCol=='numarticles') { 
				$res_articles=$GLOBALS['TYPO3_DB']->exec_SELECTquery('sum(amount) anzahl','tx_commerce_order_articles','order_uid = '.$row['uid'].' and article_type_uid ='.NORMALArticleType);
				if (($lokalRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_articles))) {
					
					$theData[$fCol] = $lokalRow['anzahl'];
					$row[$fCol]  = $lokalRow['anzahl'];
					
				}	
			}elseif ($fCol=='article_number') { 
				$articleNumber = array();
			
				$res_articles=$GLOBALS['TYPO3_DB']->exec_SELECTquery('article_number','tx_commerce_order_articles','order_uid = '.$row['uid'].' and article_type_uid ='.NORMALArticleType);
				while (($lokalRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_articles))) {
					$articleNumber[] = $lokalRow['article_number'];
					/**
					 * @TODO: implement default value, if number is not defined
					 **/
				}
				$theData[$fCol] = 		implode(',',$articleNumber);
			}elseif ($fCol=='article_name') { 
				$articleName = array();
			
				$res_articles=$GLOBALS['TYPO3_DB']->exec_SELECTquery('title','tx_commerce_order_articles','order_uid = '.$row['uid'].' and article_type_uid ='.NORMALArticleType);
				while (($lokalRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_articles))) {
					$articleName[] = $lokalRow['title'];
					/**
					 * @TODO: implement default value, if title is not defined
					 **/
				}
				$theData[$fCol] = 		implode(',',$articleName);
			}elseif ($fCol=='order_type_uid_noName') { 
			
				$res_type=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_order_types','uid = '.$row['order_type_uid_noName']);
				while (($localRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_type))) {
					
					if ($localRow['icon']) {
						$filepath = '../'.$GLOBALS['TCA']['tx_commerce_order_types']['columns']['icon']['config']['uploadfolder'].'/'.$localRow['icon'];
						
						$theData[$fCol] = '<img'.t3lib_iconWorks::skinImg($this->backPath,$filepath,'title="'.htmlspecialchars($localRow['title']).'"'.($indent ? ' style="margin-left: '.$indent.'px;"' : ''));
			
					}else{
						$theData[$fCol] = $localRow['title'];
					}
				
				}
				
				
			}elseif ($fCol=='_PATH_') {
				$theData[$fCol]=$this->recPath($row['pid']);
			} elseif ($fCol=='_CONTROL_') {
				$theData[$fCol]=$this->makeControl($table,$row);
			} elseif ($fCol=='_CLIPBOARD_') {
				$theData[$fCol]=$this->makeClip($table,$row);
			} elseif ($fCol=='_LOCALIZATION_') {
				list($lC1, $lC2) = $this->makeLocalizationPanel($table,$row);
				$theData[$fCol] = $lC1;
				$theData[$fCol.'b'] = $lC2;
			} elseif ($fCol=='_LOCALIZATION_b') {
				// Do nothing, has been done above.
			} else {
				/**
				 * Use own method, if typo3 4.0.0 is not installed
				 */
				if (substr(TYPO3_version, 0, 3) >= '4.0') {
					$theData[$fCol] = $this->linkUrlMail(htmlspecialchars(t3lib_BEfunc::getProcessedValueExtra($table,$fCol,$row[$fCol],100,$row['uid'])),$row[$fCol]);
				} else {
					$theData[$fCol] = $this->mylinkUrlMail(htmlspecialchars(t3lib_BEfunc::getProcessedValueExtra($table,$fCol,$row[$fCol],100,$row['uid'])),$row[$fCol]);
					
				}
			}
		}

			// Add row to CSV list:
		if ($this->csvOutput) {
			
		
				// Charset Conversion
			$csObj=t3lib_div::makeInstance('t3lib_cs');
			$csObj->initCharset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);	
			
			if (!$extConf['BECSVCharset']){
				$extConf['BECSVCharset']='iso-8859-1';
			}
			$csObj->initCharset($extConf['BECSVCharset']);
			
			$csObj->convArray($row,$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'],$extConf['BECSVCharset']);
			
			#print_r($row);
			#die();
			$this->addToCSV($row,$table);	
		}

			// Create element in table cells:
		$iOut.=$this->addelement(1,$theIcon,$theData,$row_bgColor);
			// Render thumbsnails if a thumbnail column exists and there is content in it:
		if ($this->thumbs && trim($row[$thumbsCol]))	{
			$iOut.=$this->addelement(4,'', Array($titleCol=>$this->thumbCode($row,$table,$thumbsCol)),$row_bgColor);
		}
			
			// Finally, return table row element:
		return $iOut;
	}



	/**
	 * Rendering the header row for a table
	 *
	 * @param	string		Table name
	 * @param	array		Array of the currectly displayed uids of the table
	 * @return	string		Header table row
	 * @access private
	 * @see class.db_list_extra.php 
	 */
	function renderListHeader($table,$currentIdList)	{
		global $TCA, $LANG;

			// Init:
		$theData = Array();

			// Traverse the fields:
			
		
		foreach($this->fieldArray as $fCol)	{

				// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table=='pages'?2:16);

			switch((string)$fCol)	{
				
//				
				default:			// Regular fields header:
					$theData[$fCol]='';
					if ($this->table && is_array($currentIdList))	{

							// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol].='<a href="'.htmlspecialchars($this->listURL('',-1).'&duplicateField='.$fCol).'">'.
											'<img'.t3lib_iconWorks::skinImg('','gfx/select_duplicates.gif','width="11" height="11"').' title="'.$LANG->getLL('clip_duplicates',1).'" alt="" />'.
											'</a>';
						}
						
					}
					
					/**
					 * Modified from this point to use relationla table queris
					 */
					$temp_data = '';
					$tables=array('tt_address','tx_commerce_orders');
					if ($LANG->getLL($fCol)) {
						foreach ($tables as $work_table){
							$temp_data = $this->addSortLink($LANG->getLL($fCol),$fCol,$table);
						}
					}else{
						foreach ($tables as $work_table){
							if ($TCA[$work_table]['columns'][$fCol])
							{
								$temp_data=$this->addSortLink($LANG->sL(t3lib_BEfunc::getItemLabel($work_table,$fCol,'<i>[|]</i>')),$fCol,$table);
							}	
							
						}
					}
					if ($temp_data)
					{
						// Only if we have a entry in locallang
						$theData[$fCol]=$temp_data;
					}
					else
					{
						
						// Handling for
						// Elements in the special Locallang file inside of mod_orders
						
						$theData[$fCol]=$this->addSortLink('<i>'.$fCol.'</i>',$fCol,$table);
						
					}
				
				break;
			}
		}
	
			// Create and return header table row:
		return $this->addelement(1,'',$theData,' class="c-headLine"','');
	}
 	


 
 
 function getTable($table,$id,$rowlist)	{
		global $TCA;

			// Loading all TCA details for this table:
		
		#t3lib_div::loadTCA($table);
		t3lib_div::loadTCA('tx_commerce_order_types');
			// Init
		$addWhere = '';
		$titleCol = $TCA[$table]['ctrl']['label'];
		$thumbsCol = $TCA[$table]['ctrl']['thumbnail'];
		$l10nEnabled = $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'] && !$TCA[$table]['ctrl']['transOrigPointerTable'];

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray=array();
		$this->fieldArray[] = $titleCol;	// Add title column
		
		
		if ($this->localizationView && $l10nEnabled)	{
			$this->fieldArray[] = '_LOCALIZATION_';
			$addWhere.=' AND '.$TCA[$table]['ctrl']['languageField'].'<=0';
		}
		if (!t3lib_div::inList($rowlist,'_CONTROL_'))	{
			//$this->fieldArray[] = '_CONTROL_';
		}
		if ($this->showClipboard)	{
			$this->fieldArray[] = '_CLIPBOARD_';
		}
		if ($this->searchLevels)	{
			$this->fieldArray[]='_PATH_';
		}
			// Cleaning up:
		$this->fieldArray=array_unique(array_merge($this->fieldArray,t3lib_div::trimExplode(',',$rowlist,1)));
		if ($this->noControlPanels)	{
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}

			// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		if ($thumbsCol)	$selectFields[] = $thumbsCol;	// adding column for thumbnails
		if ($table=='pages')	{
			if (t3lib_extMgm::isLoaded('cms'))	{
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
			}
			$selectFields[] = 'doktype';
		}
		if (is_array($TCA[$table]['ctrl']['enablecolumns']))	{
			$selectFields = array_merge($selectFields,$TCA[$table]['ctrl']['enablecolumns']);
		}
		if ($TCA[$table]['ctrl']['type'])	{
			$selectFields[] = $TCA[$table]['ctrl']['type'];
		}
		if($this->onlyUser){
		    $addWhere .= ' AND cust_fe_user = \''.$this->onlyUser.'\' ';
		}
		
		if ($TCA[$table]['ctrl']['typeicon_column'])	{
			$selectFields[] = $TCA[$table]['ctrl']['typeicon_column'];
		}
		if ($TCA[$table]['ctrl']['versioning'])	{
			$selectFields[] = 't3ver_id';
		}
		if ($l10nEnabled)	{
			$selectFields[] = $TCA[$table]['ctrl']['languageField'];
			$selectFields[] = $TCA[$table]['ctrl']['transOrigPointerField'];
		}
		if ($TCA[$table]['ctrl']['label_alt'])	{
			$selectFields = array_merge($selectFields,t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['label_alt'],1));
		}
		
		$selectFields = array_unique($selectFields);		// Unique list!
		$selectFields = array_intersect($selectFields,$this->makeFieldList($table,1));		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		//print_r($selectFields);
		$selFieldList = implode(',',$selectFields);		// implode it into a list of fields for the SQL-statement.

			// Create the SQL query for selecting the elements in the listing:
		$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
		$this->setTotalItems($queryParts);		// Finding the total amount of records on the page (API function from class.db_list.inc)
	#
			// Init:
		$dbCount = 0;
		$out = '';
		
			// If the count query returned any number of records, we perform the real query, selecting records.
		if ($this->totalItems)	{
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		}
		$LOISmode = $this->listOnlyInSingleTableMode && !$this->table;

			// If any records was selected, render the list:
		if ($dbCount)	{

				// Half line is drawn between tables:
			if (!$LOISmode)	{
				$theData = Array();
				if (!$this->table && !$rowlist)	{
		   			$theData[$titleCol] = '<img src="clear.gif" width="'.($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel']?'230':'350').'" height="1" alt="" />';
					//if (in_array('_CONTROL_',$this->fieldArray))	$theData['_CONTROL_']='';
					//if (in_array('_CLIPBOARD_',$this->fieldArray))	$theData['_CLIPBOARD_']='';
				}
				$out.=$this->addelement(0,'',$theData,'',$this->leftMargin);
			}

				// Header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView)	{
				$theData[$titleCol] = '<span class="c-table">'.$GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'],1).'</span> ('.$this->totalItems.')';
			} else {
				$theData[$titleCol] = $this->linkWrapTable($table,'<span class="c-table">'.$GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'],1).'</span> ('.$this->totalItems.') <img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/'.($this->table?'minus':'plus').'bullet_list.gif','width="18" height="12"').' hspace="10" class="absmiddle" title="'.$GLOBALS['LANG']->getLL(!$this->table?'expandView':'contractView',1).'" alt="" />');
			}

				// CSH:
			$theData[$titleCol].= t3lib_BEfunc::cshItem($table,'',$this->backPath,'',FALSE,'margin-bottom:0px; white-space: normal;');

			if ($LOISmode)	{
				$out.='
					<tr>
						<td class="c-headLineTable" style="width:95%;"'.$theData[$titleCol].'</td>
					</tr>';
				
				if ($GLOBALS['BE_USER']->uc["edit_showFieldHelp"])	{
					$GLOBALS['LANG']->loadSingleTableDescription($table);
					if (isset($GLOBALS['TCA_DESCR'][$table]['columns']['']))	{
						$onClick = 'vHWin=window.open(\'view_help.php?tfID='.$table.'.\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
						$out.='
					<tr>
						<td class="c-tableDescription">'.t3lib_BEfunc::helpTextIcon($table,'',$this->backPath,TRUE).$GLOBALS['TCA_DESCR'][$table]['columns']['']['description'].'</td>
					</tr>';
					}
				}
			} else {
				
				$theUpIcon = ($table=='pages'&&$this->id&&isset($this->pageRow['pid'])) ? '<a href="'.htmlspecialchars($this->listURL($this->pageRow['pid'])).'"><img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/pages_up.gif','width="18" height="16"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel',1).'" alt="" /></a>':'';
				$out.=$this->addelement(1,$theUpIcon,$theData,' class="c-headLineTable"','');
			}


			If (!$LOISmode)	{
					// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($TCA[$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;
				$accRows = array();	// Accumulate rows here
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
					$accRows[] = $row;
					$currentIdList[] = $row['uid'];
					if ($doSort)	{
						if ($prevUid)	{
							$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
							$this->currentTable['next'][$prevUid] = '-'.$row['uid'];
							$this->currentTable['prevUid'][$row['uid']] = $prevUid;
						}
						$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
						$prevUid=$row['uid'];
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);

					// CSV initiated
				if ($this->csvOutput) $this->initCSV();

					// Render items:
				$this->CBnames=array();
				$this->duplicateStack=array();
				$this->eCounter=$this->firstElementNumber;

				$iOut = '';
				$cc = 0;
				foreach($accRows as $row)	{

						// Forward/Backwards navigation links:
					list($flag,$code) = $this->fwd_rwd_nav($table);
					$iOut.=$code;

						// If render item, increment counter and call function
					if ($flag)	{
						$cc++;
						if (!$this->csvOutput) {
							$params="&edit[".$table."][".$row['uid']."]=edit";
							$row[$titleCol] = '<a href="#" onclick="' .htmlspecialchars(t3lib_BEfunc::editOnCLick($params,$GLOBALS['BACK_PATH'])).'">'.$row[$titleCol].'</a>';
						}
						$iOut.= $this->renderListRow($table,$row,$cc,$titleCol,$thumbsCol);
							// If localization view is enabled it means that the selected records are either default or All language and here we will not select translations which point to the main record:
						if ($this->localizationView && $l10nEnabled)	{

								// Look for translations of this record:
							$translations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								$selFieldList,
								$table,
								'pid='.$row['pid'].
									' AND '.$TCA[$table]['ctrl']['languageField'].'>0'.
									' AND '.$TCA[$table]['ctrl']['transOrigPointerField'].'='.intval($row['uid']).
									t3lib_BEfunc::deleteClause($table)
							);

								// For each available translation, render the record:
							foreach($translations as $lRow)	{
								$iOut.=$this->renderListRow($table,$lRow,$cc,$titleCol,$thumbsCol,18);
							}
						}
					}

						// Counter of total rows incremented:
					$this->eCounter++;
				}

					// The header row for the table is now created:
				$out.=$this->renderListHeader($table,$currentIdList);
			}

				// The list of records is added after the header:
			$out.=$iOut;

				// ... and it is all wrapped in a table:
			$out='



			<!--
				DB listing of elements:	"'.htmlspecialchars($table).'"
			-->
				
						<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist'.($LOISmode?' typo3-dblist-overview':'').'">
					'.$out.'
				';
			$out.='
					<tr>
						<td class="c-headLineTable" style="width:95%;"></td>';
			$colspan = (count ($this->myfields)+2);
			$out.='	<td class="c-headLineTable" style="width:95%;" colspan="'.$colspan .'" align="right">';	
			
			// Build the selector
			 /** 
			  * Query the table to build dropdown list
 		 	  */
 		
			$myPid = t3lib_div::_GP(id);
			if (!empty($myPid)) {
					
				$resParentes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid','pages','uid ='.$myPid.' '.t3lib_BEfunc::deleteClause($GLOBALS['TCA']['tx_commerce_orders']['columns']['newpid']['config']['foreign_table']));
		 		if ($rowParentes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resParentes)) {
		 			
		 		
		 		/**
		 		 * Get the poages below $order_pid
		 		 */
		 			$ExtConf=unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["commerce"]);
		 			list($orderPid,$defaultFolder,$folderList) = array_unique(tx_graytree_folder_db::initFolders('Orders','Commerce',0,'Commerce'));
		 		 	$ret = tx_commerce_belib::getOrderFolderSelector($orderPid,$ExtConf['OrderFolderRecursiveLevel']) ;
		 		 	global $LANG;
		 		 	$out.=$LANG->getLL("moveorderto");
		 		 	$out.='<select name="modeDestUid" size="1">';
		 		 	$out.="<option value='' selected='selected'>".'--------------------'."</option>";
		 		 	foreach ($ret as $displayArray) {
		 		 		$out.="<option value='".$displayArray[1]."'>".$displayArray[0]."</option>";
		 		 	}
		 		 	
		 		 	$out.='</select>';
		 		 	$out.="<input type='submit' name='OK' value='ok'>";
		 		 	
	 				#$out.= t3lib_div::debug($ret);
		 		}}
			$out.='</tr>';
		$out.='</table>';
				// Output csv if...
			if ($this->csvOutput)	$this->outputCSV($table);	// This ends the page with exit.
		}

			// Return content:
		return $out;
	}
	
	
	
 
  }
  
  
 

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_order_localrecordlist.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_order_localrecordlist.php']);
}

 ?>
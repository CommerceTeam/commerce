<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2004 Joerg Sprung (jsp@web-factory.de)
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
 * Module 'Statistics' for the 'commerce' extension.
 *
 * @author	Joerg Sprung <jsp@web-factory.de>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);	
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$LANG->includeLLFile("EXT:commerce/mod_statistic/locallang.php");
$LANG->includeLLFile("EXT:commerce/mod_statistic/locallang_weekday.php");
#include ("locallang.php");
require_once (PATH_t3lib."class.t3lib_scbase.php");
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]


/**
 * Load TYPO3 core libaries
 */

require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_pagetree.php');
require_once (PATH_t3lib.'class.t3lib_recordlist.php');
require_once (PATH_t3lib.'class.t3lib_clipboard.php');

require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_order_localrecordlist.php');
require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_feusers_localrecordlist.php');

require_once (t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_folder_db.php');

/**
 * Load Locallang
 */

$LANG->includeLLFile('EXT:lang/locallang_mod_web_list.php');





class tx_commerce_statistic extends t3lib_SCbase {
	var $pageinfo;
	var $order_pid;
	/**
	 * 
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		
		parent::init();
		$this->extConf = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["commerce"]);
		$this->excludePids = $this->extConf['excludeStatisticFolders'] != '' ? $this->extConf['excludeStatisticFolders'] : 0;
		$order_pid = array_unique(tx_graytree_folder_db::initFolders('Orders','Commerce',0,'Commerce'));
		$this->order_pid = $order_pid[0];
		/**
		 * @TODO Find a better solution for the fist array element
		 * 
		 */
		/**
		 * If we get an id via GP use this, else use the default id
		 */
		if (t3lib_div::_GP('id'))
		{
			$this->id=t3lib_div::_GP('id');
		}
		else
		{
			$this->id = $order_pid[0];
		}
		
		/*
		if (t3lib_div::_GP("clear_all_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
				"1" => $LANG->getLL("statistics"),
				"2" => $LANG->getLL("incremental_aggregation"),
				"3" => $LANG->getLL("complete_aggregation"),
			)
		);
		parent::menuConfig();
	}

		// If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{
	
				// Draw the header.
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				</script>
			';

			$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br>".$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").": ".t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
			$this->content.=$this->doc->divider(5);

			
			// Render content:
			$this->moduleContent();

			
			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}
		
			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
		
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
		
			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	
	/**
	 * Generates the module content
	 */
	function moduleContent()	{
		global $LANG;
		switch((string)$this->MOD_SETTINGS["function"])	{
			case 1:
				$content= $this->showStatistics(); 
				$this->content.=$this->doc->section($LANG->getLL("statistics"). ": ",$content,0,1);
			break;
			case 2:
				$content=$this->incrementalAggregation();
				$this->content.=$this->doc->section($LANG->getLL("incremental_aggregation"). ": ",$content,0,1);
			break;
			case 3:
				$content=$this->completeAggregation();
				$this->content.=$this->doc->section($LANG->getLL("complete_aggregation"). ": ",$content,0,1);
			break;
		} 
	}
	
	/**
	 * Generates an initialize the complete Aggregation
	 * 
	 * @return String Content to show in BE
	 */
	function completeAggregation()	{
		global $LANG;
		$result = '';
		if(isset($GLOBALS['HTTP_POST_VARS']['fullaggregation'])) {
			
			
			$endselect = 'SELECT max(crdate) FROM tx_commerce_order_articles';
			$endres = $GLOBALS['TYPO3_DB']->sql_query($endselect);
			if( $endres AND ( $endrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $endres ) ) ) {
				$endtime2 = $endrow[0];
			}
			
			$endtime =  $endtime2 > mktime(0,0,0) ? mktime(0,0,0) : strtotime('+1 hour',$endtime2);
			
			$startselect = 'SELECT min(crdate) FROM tx_commerce_order_articles WHERE crdate > 0';
			$startres = $GLOBALS['TYPO3_DB']->sql_query($startselect);
			if( $startres AND ( $startrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $startres ) ) AND $startrow[0] != NULL) {
				$starttime = $startrow[0];
				$GLOBALS['TYPO3_DB']->sql_query('truncate tx_commerce_salesfigures');
				$result .= $this->doSalesAggregation($starttime,$endtime);
			} else {
				$result .= 'no sales data available';
			}
			
			$endselect = 'SELECT max(crdate) FROM fe_users';
			$endres = $GLOBALS['TYPO3_DB']->sql_query($endselect);
			if( $endres AND ( $endrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $endres ) ) ) {
				$endtime2 = $endrow[0];
			}
			
			$endtime =  $endtime2 > mktime(0,0,0) ? mktime(0,0,0) : strtotime('+1 hour',$endtime2);
			
			$startselect = 'SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0';
			$startres = $GLOBALS['TYPO3_DB']->sql_query($startselect);
			if( $startres AND ( $startrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $startres ) ) AND $startrow[0] != NULL) {
				$starttime = $startrow[0];
				$GLOBALS['TYPO3_DB']->sql_query('truncate tx_commerce_newclients');
				$result = $this->doClientAggregation($starttime,$endtime);
			} else {
				$result .= '<br />no client data available';
			}
		} else {
			$result = "Dieser Vorgang kann eventuell lange dauern<br /><br />";
			$result .= sprintf ('<input type="submit" name="fullaggregation" value="%s" />', $LANG->getLL("complete_aggregation"));
		}
		
		return $result;
	}
	
	/**
	 * Generates an initialize the complete Aggregation
	 * 
	 * @return String Content to show in BE
	 */
	function incrementalAggregation()	{
		global $LANG;
		$result = '';
		if(isset($GLOBALS['HTTP_POST_VARS']['incrementalaggregation'])) {
			$lastAggregationTime = 'SELECT max(tstamp) FROM tx_commerce_salesfigures';
			$lastAggregationTimeres = $GLOBALS['TYPO3_DB']->sql_query($lastAggregationTime);
			$lastAggregationTimeValue = 0;
			if( $lastAggregationTimeres AND ( $lastAggregationTimerow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $lastAggregationTimeres ) ) AND $lastAggregationTimerow[0] != NULL ) {
				$lastAggregationTimeValue = $lastAggregationTimerow[0];
			}
			$endselect = 'SELECT max(crdate) FROM tx_commerce_order_articles';
			$endres = $GLOBALS['TYPO3_DB']->sql_query($endselect);
			if( $endres AND ( $endrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $endres ) ) ) {
				$endtime2 = $endrow[0];
			}
				
			if(strtotime("0",$lastAggregationTimeValue) <= strtotime("0",$endtime2) AND $endtime2 != NULL) {
				$endtime =  $endtime2 > mktime(0,0,0) ? mktime(0,0,0) : strtotime('+1 hour',$endtime2);
				$starttime = strtotime("0",$lastAggregationTimeValue);
				$result .= $this->doSalesAggregation($starttime,$endtime);
			} else {
				$result .= 'No new Orders<br />';
			}

			$changeselect = 'SELECT crdate FROM tx_commerce_order_articles where tstamp > ' . $lastAggregationTimeValue;
			$changeres = $GLOBALS['TYPO3_DB']->sql_query($changeselect);
			$changeDaysArray = array();
			$changes = 0;
			while($changeres AND $changerow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($changeres)) {
				$starttime = strtotime("0",$changerow['crdate']);
				$endtime = strtotime("23:59:59",$changerow['crdate']);
				#$result .= date('r',$starttime) . '<br />';
				if(!in_array($starttime,$changeDaysArray)) {
					$changeDaysArray[] = $starttime;
					$result .= $this->doSalesUpdateAggregation($starttime,$endtime);
					++$changes;
				}
			}
			
			$result .= $changes . ' Days changed<br />';
			
			$lastAggregationTime = 'SELECT max(tstamp) FROM tx_commerce_newclients';
			$lastAggregationTimeres = $GLOBALS['TYPO3_DB']->sql_query($lastAggregationTime);
			if( $lastAggregationTimeres AND ( $lastAggregationTimerow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $lastAggregationTimeres ) ) ) {
				$lastAggregationTimeValue = $lastAggregationTimerow[0];
			}
			$endselect = 'SELECT max(crdate) FROM fe_users';
			$endres = $GLOBALS['TYPO3_DB']->sql_query($endselect);
			if( $endres AND ( $endrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $endres ) ) ) {
				$endtime2 = $endrow[0];
			}
			if($lastAggregationTimeValue <= $endtime2 AND $endtime2 != NULL AND $lastAggregationTimeValue != NULL) {
			
			
				$endtime =  $endtime2 > mktime(0,0,0) ? mktime(0,0,0) : strtotime('+1 hour',$endtime2);
				
				$startselect = 'SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0';
				$startres = $GLOBALS['TYPO3_DB']->sql_query($startselect);

				$starttime = strtotime("0",$lastAggregationTimeValue);
				$result .= $this->doClientAggregation($starttime,$endtime);
			} else {
				$result .= "No new Customers<br />";
			}
			
			
		} else {
			$result = "Dieser Vorgang kann eventuelle eine hohe Laufzeit haben<br /><br />";
			$result .= sprintf ('<input type="submit" name="incrementalaggregation" value="%s" />', $LANG->getLL("incremental_aggregation"));
		}
		
		return $result;
	}
	
	/**
	 * Aggregate ans Insert the Salesfigures per Hour in the timespare from
	 * $starttime to $enttime
	 * 
	 * @param integer $starttime Timestamp of timecode to start the aggregation
	 * @param integer $endtime Timestamp of timecode to end the aggregation
	 * @return boolean result of aggregation
	 */
	function doSalesAggregation($starttime,$endtime) {
		$hour   =       date('h',$starttime);
		$day    =       date('d',$starttime);
		$month  =       date('m',$starttime);
		$year   =       date('Y',$starttime);
		$today  =       $endtime;
		$stats  =       '';
		$result = true;
		$oldtimestart=  mktime($hour,0,0,$month,$day,$year);
		$oldtimeend  =  mktime($hour,59,59,$month,$day,$year);
		while($oldtimeend < $endtime) {
	        $statquery = sprintf('
	                        SELECT
	                                sum(toa.amount),
	                                sum(toa.amount * toa.price_gross),
	                                count(distinct toa.order_id),
	                                toa.pid,
	                                sum(toa.amount * toa.price_net)
	                        FROM
	                                tx_commerce_order_articles toa,
	                                tx_commerce_orders tco
	                        WHERE                        	
	                                toa.article_type_uid <= 1
	                        AND
	                                toa.crdate >= %u
	                        AND
	                                toa.crdate <= %u
	                        AND
	                        		toa.pid not in(%s)
	                        AND
	                                toa.order_id = tco.order_id
	                        AND
	                                tco.deleted = 0
	                        GROUP BY
	                                toa.pid',
	                        $oldtimestart,
	                        $oldtimeend,
	                        $this->excludePids
	                        );
	        $statres = $GLOBALS['TYPO3_DB']->sql_query($statquery);
	        while($statrow = $GLOBALS['TYPO3_DB']->sql_fetch_row($statres)) {
            	$insertStatArray = array( 	'pid' 		=> $statrow[3],
            								'year'		=> date('Y',$oldtimeend),
            								'month'		=> date('m',$oldtimeend),
            								'day'		=> date('d',$oldtimeend),
            								'dow'		=> date('w',$oldtimeend),
            								'hour'		=> date('H',$oldtimeend),
            								'pricegross'=> $statrow[1],
            								'amount'	=> $statrow[0],
            								'orders'	=> $statrow[2],
            								'pricenet'	=> $statrow[4],
            								'crdate'	=> time(),
            								'tstamp'	=> time()
            							);

            	$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_salesfigures',$insertStatArray);
            	if(!$res) {
            		$result = false;
            	}
            	
            	
	        }
	        $oldtimestart = mktime(++$hour,0,0,$month,$day,$year);
	        $oldtimeend   = mktime($hour,59,59,$month,$day,$year);
		}
		
		return $result;
	}
	
	
	/**
	 * Aggregate and Update the Salesfigures per Hour in the timespare from
	 * $starttime to $enttime
	 * 
	 * @param integer $starttime Timestamp of timecode to start the aggregation
	 * @param integer $endtime Timestamp of timecode to end the aggregation
	 * @return boolean result of aggregation
	 */
	function doSalesUpdateAggregation($starttime,$endtime) {
		$hour   =       date('h',$starttime);
		$day    =       date('d',$starttime);
		$month  =       date('m',$starttime);
		$year   =       date('Y',$starttime);
		$today  =       $endtime;
		$stats  =       '';
		$result = 		true;
		$oldtimestart=  mktime($hour,0,0,$month,$day,$year);
		$oldtimeend  =  mktime($hour,59,59,$month,$day,$year);
		while($oldtimeend < $endtime) {
	        
	        $statquery = sprintf('
	                        SELECT
	                                sum(toa.amount),
	                                sum(toa.amount * toa.price_gross),
	                                count(distinct toa.order_id),
	                                toa.pid,
	                                sum(toa.amount * toa.price_net)
	                        FROM
	                                tx_commerce_order_articles toa,
	                                tx_commerce_orders tco
	                        WHERE                        	
	                                toa.article_type_uid <= 1
	                        AND
	                                toa.crdate >= %u
	                        AND
	                                toa.crdate <= %u
	                        AND
	                        		toa.pid not in(%s)
	                        AND
	                                toa.order_id = tco.order_id
	                        AND
	                                tco.deleted = 0
	                        GROUP BY
	                                toa.pid',
	                        $oldtimestart,
	                        $oldtimeend,
	                        $this->excludePids
	                        );

	        $statres = $GLOBALS['TYPO3_DB']->sql_query($statquery);
	        while($statrow = $GLOBALS['TYPO3_DB']->sql_fetch_row($statres)) {
            	$updateStatArray = array( 	'pid' 		=> $statrow[3],
            								'year'		=> date('Y',$oldtimeend),
            								'month'		=> date('m',$oldtimeend),
            								'day'		=> date('d',$oldtimeend),
            								'dow'		=> date('w',$oldtimeend),
            								'hour'		=> date('H',$oldtimeend),
            								'pricegross'=> $statrow[1],
            								'amount'	=> $statrow[0],
            								'orders'	=> $statrow[2],
            								'pricenet'	=> $statrow[4],
            								'tstamp'	=> time()
            							);
				$whereClause = 	'year = ' .date('Y',$oldtimeend) .
								' AND month = ' . date('m',$oldtimeend) .
								' AND day = ' . date('d',$oldtimeend) .
								' AND hour = ' . date('H',$oldtimeend);
            	$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_salesfigures',$whereClause,$updateStatArray);
       			if(!$res) {
            		$result = false;
            	}
       				
	        }
	        $oldtimestart = mktime(++$hour,0,0,$month,$day,$year);
	        $oldtimeend   = mktime($hour,59,59,$month,$day,$year);
		}
		
		return $stats;
	}
	
	/**
	 * Aggregate and Insert the New Users (Registrations in fe_user)) per hour
	 * in the timespare from $starttime to $enttime
	 * 
	 * @param integer $starttime Timestamp of timecode to start the aggregation
	 * @param integer $endtime Timestamp of timecode to end the aggregation
	 * @return boolean result of aggregation
	 */	
	function doClientAggregation($starttime,$endtime) {
		$hour   =       date('h',$starttime);
		$day    =       date('d',$starttime);
		$month  =       date('m',$starttime);
		$year   =       date('Y',$starttime);
		$today  =       $endtime;
		$stats  =       '';
		$oldtimestart=  mktime($hour,0,0,$month,$day,$year);
		$oldtimeend  =  mktime($hour,59,59,$month,$day,$year);
		while($oldtimeend < $endtime) {
	        $statquery = sprintf('
	                        SELECT  
	                        		count(*),
	                                pid
	                        FROM
	                                fe_users
	                        WHERE
	                                crdate >= %u
	                        AND
	                                crdate <= %u
	                        GROUP BY
	                                pid',
	                        $oldtimestart,
	                        $oldtimeend);
	        #echo $statquery , "\n";
	        $statres = $GLOBALS['TYPO3_DB']->sql_query($statquery);
	        while($statrow = $GLOBALS['TYPO3_DB']->sql_fetch_row($statres)) {
            	$insertStatArray = array( 	'pid' 		=> $statrow[1],
            								'year'		=> date('Y',$oldtimeend),
            								'month'		=> date('m',$oldtimeend),
            								'day'		=> date('d',$oldtimeend),
            								'dow'		=> date('w',$oldtimeend),
            								'hour'		=> date('H',$oldtimeend),
            								'registration'	=> $statrow[0],
            								'crdate'	=> time(),
            								'tstamp'	=> time()
            							);

            	$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_newclients',$insertStatArray);
            	
	        }
	        $oldtimestart = mktime(++$hour,0,0,$month,$day,$year);
	        $oldtimeend   = mktime($hour,59,59,$month,$day,$year);
		}
		
		return $stats;
	}
	
	/**
	 * Generate the Statistictables
	 * 
	 * @return string statistictables in HTML
	 */
	function showStatistics() {
		global $LANG;
		$whereClause = '';
		if($this->id != $this->order_pid) {
			$whereClause = 'pid = '. $this->id;
		}
		$weekdays = array($LANG->getLL('sunday'),$LANG->getLL('monday'),$LANG->getLL('tuesday'),$LANG->getLL('wednesday'),$LANG->getLL('thursday'),$LANG->getLL('friday'),$LANG->getLL('saturday'));
		
		if(t3lib_div::_GP('show')) {
			#$GLOBALS['TYPO3_DB']->debugOutput = true;
			$whereClause = $whereClause != '' ? $whereClause . ' AND' : '';
			$whereClause .=  " month = ".t3lib_div::_GP('month')."  AND year = ". t3lib_div::_GP('year'); 
			
			$tables .= '<h2>'.t3lib_div::_GP('month').' - '.t3lib_div::_GP('year').'</h2><table><tr><th>Days</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery('sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,day','tx_commerce_salesfigures',$whereClause ,'day');
			$daystat = array();
			while($statRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($statResult) ) {
				$daystat[$statRow['day']] = $statRow;
			}
			$lastday = date('d',mktime(0, 0, 0, t3lib_div::_GP('month') + 1 , 0, t3lib_div::_GP('year')));
			for($i=1; $i <= $lastday; ++$i) {
				if(array_key_exists($i, $daystat)) {
					$tablestemp = "<tr><td>".$daystat[$i]['day'] . "</a></td><td align='right'>%01.2f</td><td align='right'>" .$daystat[$i]['salesfigures']."</td><td align='right'>" . $daystat[$i]['sumorders'] . "</td></tr>";
					$tables .= sprintf($tablestemp,($daystat[$i]['turnover']/100));
				} else {
					$tablestemp = "<tr><td>". $i . "</a></td><td align='right'>%01.2f</td><td align='right'>0</td><td align='right'>0</td></tr>";
					$tables .= sprintf($tablestemp,(0));
				}
			}
			$tables .= '</table>';
			
			$tables .= '<table><tr><th>Weekday</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery('sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,dow','tx_commerce_salesfigures',$whereClause ,'dow');
			
			$daystat = array();
			while($statRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($statResult) ) {
				$daystat[$statRow['dow']] = $statRow;
			}
			for($i=0; $i <= 6; ++$i) {
				if(array_key_exists($i, $daystat)) {
					$tablestemp = "<tr><td>".$weekdays[$daystat[$i]['dow']] . "</a></td><td align='right'>%01.2f</td><td align='right'>" .$daystat[$i]['salesfigures']."</td><td align='right'>" . $daystat[$i]['sumorders'] . "</td></tr>";
					$tables .= sprintf($tablestemp,($daystat[$i]['turnover']/100));
				} else {
					$tablestemp = "<tr><td>". $weekdays[$i] . "</a></td><td align='right'>%01.2f</td><td align='right'>0</td><td align='right'>0</td></tr>";
					$tables .= sprintf($tablestemp,(0));
				}
			}
			$tables .= '</table>';
			
			$tables .= '<table><tr><th>Hour</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery('sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,hour','tx_commerce_salesfigures',$whereClause  ,'hour');

			$daystat = array();
			while($statRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($statResult) ) {
				$daystat[$statRow['hour']] = $statRow;
			}
			for($i=0; $i <= 23; ++$i) {
				if(array_key_exists($i, $daystat)) {
					$tablestemp = "<tr><td>". $i . "</a></td><td align='right'>%01.2f</td><td align='right'>" .$daystat[$i]['salesfigures']."</td><td align='right'>" . $daystat[$i]['sumorders'] . "</td></tr>";
					$tables .= sprintf($tablestemp,($daystat[$i]['turnover']/100));
				} else {
					$tablestemp = "<tr><td>". $i . "</a></td><td align='right'>%01.2f</td><td align='right'>0</td><td align='right'>0</td></tr>";
					$tables .= sprintf($tablestemp,(0));
				}
			}
			$tables .= '</table>';
			
			
			$tables .= '</table>';
			
		} else {
			$tables = '<table><tr><th>Month</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery('sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,year,month','tx_commerce_salesfigures',$whereClause,'year,month');
			while($statRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($statResult) ) {
				$tablestemp = "<tr><td><a href=\"?id=".$this->id."&amp;month=".$statRow['month']."&amp;year=".$statRow['year']."&amp;show=details\">" . $statRow['month'] .'.'.$statRow['year'] . "</a></td><td align='right'>%01.2f</td><td align='right'>" .$statRow['salesfigures']."</td><td align='right'>" . $statRow['sumorders'] . "</td></tr>";
				$tables .= sprintf($tablestemp,($statRow['turnover']/100));
			}
			$tables .= '</table>';
		}
		return $tables;
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/commerce/mod_statistic/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/commerce/mod_statistic/index.php"]);
}




// Make instance:
$SOBE = t3lib_div::makeInstance("tx_commerce_statistic");
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
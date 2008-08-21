<?php



/***************************************************************
*  Copyright notice
*
*  (c) 2008 Ingo Schmitt <is@marketing-factory,de>
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
 * This class inculdes all methods for generating statistcs data,
 * used for the statistics module and for the cli script
 *
 * @package		TYPO3
 * @subpackage	commerce
 * @author		Ingo Schmitt <is@marketing-factory,de>
 *
 * @maintainer	Ingo Schmitt <is@marketing-factory,de>
 *

 */
class tx_commerce_statistics {
	
	/**
	 * List of exclude PIDs, PIDs whcih should not be used when calculation the statistics. This List should
	 * be definable in Extension configuration
	 *
	 * @var string
	 */
	var 	$excludePids;
	/**
	 * How mayn dasys the update agregation wil recaluclate
	 *
	 * @var integer	
	 */
	var 	$daysback = 10;
	
	
	function init($excludePids){
		$this->excludePids = $excludePids;
		
	}
	
	/**
	 * Public method to return days back
	 * 	
	 * @return integer
	 */
	function getDaysBack(){
		return $this->daysback;
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
		$hour   =       date('H',$starttime);
		$day    =       date('d',$starttime);
		$month  =       date('m',$starttime);
		$year   =       date('Y',$starttime);
		$today  =       $endtime;
		$stats  =       '';
		$result = true;
		$oldtimestart=  mktime($hour,0,0,$month,$day,$year);
		$oldtimeend  =  mktime($hour,59,59,$month,$day,$year);
		while($oldtimeend <= $endtime) {
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
	 * @param boolen $doOutput Boolen, if output should be genared whiel caluclating, shoudl be fals for cli
	 * @return boolean result of aggregation
	 */
	function doSalesUpdateAggregation($starttime,$endtime,$doOutput = true) {
		$hour   =       date('H',$starttime);
		$day    =       date('d',$starttime);
		$month  =       date('m',$starttime);
		$year   =       date('Y',$starttime);
		$today  =       $endtime;
		$stats  =       '';
		$result = 		true;
		$oldtimestart=  mktime($hour,0,0,$month,$day,$year);
		$oldtimeend  =  mktime($hour,59,59,$month,$day,$year);
		while($oldtimeend <= $endtime) {
	        
		
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
            	#echo  $GLOBALS['TYPO3_DB']->UPDATEquery('tx_commerce_salesfigures',$whereClause,$updateStatArray);
       			if(!$res) {
            		$result = false;
            	#	print "Error on ".$GLOBALS['TYPO3_DB']->UPDATEquery('tx_commerce_salesfigures',$whereClause,$updateStatArray);
            	}
            	if ($doOutput) {
	            	print ".";
	            	flush();
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
		$hour   =       date('H',$starttime);
		$day    =       date('d',$starttime);
		$month  =       date('m',$starttime);
		$year   =       date('Y',$starttime);
		$today  =       $endtime;
		$stats  =       '';
		$return =		true;
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
		
		return $return;
	}
	
	/**
	 * Retursn the first second of a day as Timestamp
	 *
	 * @param integer $timestamp
	 * @return	integer Timestamp
	 */
	function  firstSecondOfDay($timestamp) {
		return (int)mktime(0,0,0,strftime("%m",$timestamp),strftime("%d",$timestamp),strftime("%Y",$timestamp));
	}
	
/**
	 * Retursn the last second of a day as Timestamp
	 *
	 * @param integer $timestamp
	 * @return	integer Timestamp
	 */
	function  lastSecondOfDay($timestamp) {
		return (int)mktime(23,59,59,strftime("%m",$timestamp),strftime("%d",$timestamp),strftime("%Y",$timestamp));
	}
	

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_statistics.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_statistics.php"]);
}

?>
<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 - 2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
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
 * tx_commerce command line interface
 *
 * The shell call is
 * /www/typo3/cli_dispatch.phpsh commerce MAINTASK SUBTASK
 *
 * real example
 * Calculation the Statistics incremental or complete
 * /www/typo3/cli_dispatch.phpsh commerce  statistics incrementalAggregation
 * /www/typo3/cli_dispatch.phpsh commerce  statistics completeAggregation
 *
 * @author Ingo Schmitt <jk@marketing-factory.de>
 * @package TYPO3
 * @subpackage commerce
 */

if (!defined('TYPO3_cliMode')) {
	die('You cannot run this script directly!');
}

class tx_commerce_cli extends t3lib_cli {
	
	/**
	 * Constructor
	 *
	 * @return tx_cliexample_cli
	 */
    function init () {

        // Running parent class constructor
        parent::t3lib_cli();

        // Setting help texts:
        $this->cli_help['name'] = 'class.cli_commerce.php';        
        $this->cli_help['synopsis'] = '###OPTIONS###';
        $this->cli_help['description'] = "CLI Wrapper for commerce";
        $this->cli_help['options'] = "statistics [Tasktype] run Statistics Tasks, Task Types are [incrementalAggregation|completeAggregation], if no type is given, completeAggregation is calculated";
        $this->cli_help['examples'] = "/.../cli_dispatch.phpsh commerce  statistics incrementalAggregation \n/.../cli_dispatch.phpsh commerce  statistics completeAggregation";
        $this->cli_help['author'] = "Ingo Schmitt, (c) 2008 <is@marketing-factory.de>";
    }

    /**
     * CLI engine
     *
     * @param    array        Command line arguments
     * @return    void
     */
    function cli_main($argv) {
        // get task (function)
      
        $this->MainTask = (string)$this->cli_args['_DEFAULT'][1];
        $this->subTask = (string)$this->cli_args['_DEFAULT'][2];
        if (!$this->MainTask){
            $this->cli_validateArgs();
            $this->cli_help();
            exit;
        }
		
        switch ($this->MainTask) {
        	case 'statistics':
        		
        		$this->runStatisticsTask($this->subTask);
        		
        	break;
        		
        	
        }
       
    }
    /**
     * Runs the Statistics Tasks form command Line interface
     * 
     *
     * @param string $subTaks	Which SubTask should be und, possible: completeAggregation,incrementalAggregation
     */
    function runStatisticsTask($subTaks) {
    	$this->statistics = t3lib_div::makeInstance('tx_commerce_statistics');
		$this->statistics->init($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['excludeStatisticFolders'] != '' ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['excludeStatisticFolders'] : 0);
		
		
    	switch ($subTaks) {
    		
    		case 'incrementalAggregation':
		    		$lastAggregationTime = 'SELECT max(tstamp) FROM tx_commerce_salesfigures';
					$lastAggregationTimeres = $GLOBALS['TYPO3_DB']->sql_query($lastAggregationTime);
					$lastAggregationTimeValue = 0;
					if( $lastAggregationTimeres AND ( $lastAggregationTimerow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $lastAggregationTimeres ) ) AND $lastAggregationTimerow[0] != NULL ) {
						$lastAggregationTimeValue = $lastAggregationTimerow[0];
					}
					$lastAggretagionTimeValue = $this->statistics->firstSecondOfDay($lastAggretagionTimeValue);
					$endselect = 'SELECT max(crdate) FROM tx_commerce_order_articles';
					$endres = $GLOBALS['TYPO3_DB']->sql_query($endselect);
					if( $endres AND ( $endrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $endres ) ) ) {
						$endtime2 = $endrow[0];
					}
    				$starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);
    				
					if($starttime <= $this->statistics->firstSecondOfDay($endtime2) AND $endtime2 != NULL) {
						$endtime =  $endtime2 > mktime(0,0,0) ? mktime(0,0,0) : strtotime('+1 hour',$endtime2);
					
						
						$this->cli_echo('Incremental Sales Agregation for sales for the period from '.$starttime.' to '.$endtime." (Timestamp)\n");
						$this->cli_echo('Incremental Sales Agregation for sales for the period from '.strftime("%d.%m.%Y",$starttime).' to '.strftime("%d.%m.%Y",$endtime)." (DD.MM.YYYY)\n");
						if (!$this->statistics->doSalesAggregation($starttime,$endtime)) {
							$this->cli_echo('Problems with incremetal Aggregation of orders');
						}
						
					} else {
						$this->cli_echo("No new Orders\n");
					}
		
					$changeselect = 'SELECT distinct crdate FROM tx_commerce_order_articles where tstamp > ' . ($lastAggregationTimeValue - ($this->statistics->getDaysBack()*24*60*60));
					$changeres = $GLOBALS['TYPO3_DB']->sql_query($changeselect);
					$changeDaysArray = array();
					$changes = 0;
					while($changeres AND $changerow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($changeres)) {
						$starttime =  $this->statistics->firstSecondOfDay($changerow['crdate']);
						$endtime =  $this->statistics->lastSecondOfDay($changerow['crdate']);
						#$result .= date('r',$starttime) . '<br />';
						if(!in_array($starttime,$changeDaysArray)) {
							$changeDaysArray[] = $starttime;
							$this->cli_echo('Incremental Sales UpdateAgregation for sales for the period from '.$starttime.' to '.$endtime." (Timestamp)\n");
							$this->cli_echo('Incremental Sales UpdateAgregation for sales for the period from '.strftime("%d.%m.%Y",$starttime).' to '.strftime("%d.%m.%Y",$endtime)." (DD.MM.YYYY)\n");
							
							$result .= $this->statistics->doSalesUpdateAggregation($starttime,$endtime,false);
							++$changes;
						}
					}
					
					$this->cli_echo( $changes . ' Days changed'."\n");
					
					$lastAggregationTime = 'SELECT max(tstamp) FROM tx_commerce_newclients';
					$lastAggregationTimeres = $GLOBALS['TYPO3_DB']->sql_query($lastAggregationTime);
					if( $lastAggregationTimeres AND ( $lastAggregationTimerow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $lastAggregationTimeres ) ) ) {
						$lastAggregationTimeValue = $lastAggregationTimerow[0];
					}
					$lastAggregationTimeValue =  $this->statistics->firstSecondOfDay($lastAggregationTimeValue);
    				
					$endselect = 'SELECT max(crdate) FROM fe_users';
					$endres = $GLOBALS['TYPO3_DB']->sql_query($endselect);
					if( $endres AND ( $endrow = $GLOBALS['TYPO3_DB']->sql_fetch_row( $endres ) ) ) {
						$endtime2 = $endrow[0];
					}
					if($lastAggregationTimeValue <= $endtime2 AND $endtime2 != NULL AND $lastAggregationTimeValue != NULL) {
					
					
						$endtime =  $endtime2 > mktime(0,0,0) ? mktime(0,0,0) : strtotime('+1 hour',$endtime2);
						
						$startselect = 'SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0';
						$startres = $GLOBALS['TYPO3_DB']->sql_query($startselect);
					
						$starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);
						
						
						$this->cli_echo('Incremental Client Agregation for sales for the period from '.$starttime.' to '.$endtime."\n");
						$this->cli_echo('Incremental Client Agregation for sales for the period from '.strftime("%d.%m.%Y",$starttime).' to '.strftime("%d.%m.%Y",$endtime)."\n");
						
						
						if (!$this->statistics->doClientAggregation($starttime,$endtime)) {
							$this->cli_echo('Problems with CLient agregation');
						}
						
						
						
					} else {
						$this->cli_echo("No new Customers \n");
					}
					
    			break;
    		default:
    		case 'completeAggregation': 
    				
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
						if (!$this->statistics->doSalesAggregation($starttime,$endtime)) {
							$this->cli_echo('problems with completeAgregation of Sales');
						}
					
					} else {
						$this->cli_echo('no sales data available');
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
						if (!$this->statistics->doClientAggregation($starttime,$endtime)) {
							$this->cli_echo('Probvlems with cle complete agregation Clients');
						}
					} else {
						$this->cli_echo('no client data available');
					}	
    			
    			break;
    		
    		
    	}
		
    	
    }
    
	
	
}

// Call the functionality
$cleanerObj = t3lib_div::makeInstance('tx_commerce_cli');
$cleanerObj->init();
$cleanerObj->cli_main($_SERVER['argv']);

?>
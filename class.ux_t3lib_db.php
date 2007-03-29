<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Ingo Schmitt (is@marketing-factory.de)
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
 * Extension for t3lib_db for generation mm_query with aliases
 * as table-names for generation recursice mm select queris
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>
 * 
 * $Id: class.ux_t3lib_db.php 147 2006-04-04 10:43:22Z thomas $
 */
 
 class ux_t3lib_DB extends t3lib_DB {
 
 	/**
	 * Creates and executes a SELECT SQL-statement for recursive datat structures
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 *
	 *
	 * @param	string		List of fields to select from the local table. This is what comes right after "SELECT ...". Required value. As of recursive Datastructure local and foreigen table ar the same, in the query the foreigen_tabel will be named as ft
	 * @param 	string 		mm_table, table where the relation to the foreigen_tabel is stored
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->quoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
 		function exec_SELECT_mm_rec_query($select,$local_table,$mm_table,$whereClause='',$groupBy='',$orderBy='',$limit='')	{
		$mmWhere = $local_table ? $local_table.'.uid='.$mm_table.'.uid_local' : '';
		
		$mmWhere.=  '  AND ft.uid='.$mm_table.'.uid_foreign';
		#debug ($mmWhere.$whereClause);
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$select,
					($local_table ? $local_table.',' : '').$mm_table.($local_table ? ','.$local_table.' as ft ' : ''),
					$mmWhere.' '.$whereClause,		// whereClauseMightContainGroupOrderBy
					$groupBy,
					$orderBy,
					$limit
				);
	}	
 	
 }
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/class.ux_t3lib_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/class.ux_t3lib_db.php']);
}
 ?>
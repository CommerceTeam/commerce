<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * This class is the base for listing of database records and files in the modules Web>List and File>Filelist
 *
 * @author	Ingo Schmitt <is@marketing-factory.de>

 * @see t3lib/class.t3librecordlist.php
 */
class ux_localRecordList extends localRecordList {

	
	
	 /** Creates part of query for searching after a word ($this->searchString) fields in input table
	 *
	 * @param	string		Table, in which the fields are being searched.
	 * @return	string		Returns part of WHERE-clause for searching, if applicable.
	 * 
	 * Changed by
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 * Added special treatment for special commerce tables
	 */
	function makeSearchString($table)	{
		global $TCA;
		
			if (!($table == 'tx_commerce_orders')) {
				return parent::makeSearchString($table);
			}else{
				// added type none to search filed types
				// Make query, only if table is valid and a search string is actually defined:
				if ($TCA[$table] && $this->searchString)	{
		
						// Loading full table description - we need to traverse fields:
					t3lib_div::loadTCA($table);
		
						// Initialize field array:
					$sfields=array();
					$sfields[]='uid';	// Adding "uid" by default.
		
						// Traverse the configured columns and add all columns that can be searched:
					foreach($TCA[$table]['columns'] as $fieldName => $info)	{
						if ($info['config']['type']=='text' || $info['config']['type']=='none'   || ($info['config']['type']=='input' && !ereg('date|time|int',$info['config']['eval'])))	{
							$sfields[]=$fieldName;
						}
						
					}
		
						// If search-fields were defined (and there always are) we create the query:
					if (count($sfields))	{
						$like = ' LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($this->searchString, $table).'%\'';		// Free-text searching...
						$queryPart = ' AND ('.implode($like.' OR ',$sfields).$like.')';
		
							// Return query:
						return $queryPart;
					}
				}
			}
		
	}
	
	
	
	
	

	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['commerce/ux_localRecordList.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['commerce/ux_localRecordList.php']);
}
?>
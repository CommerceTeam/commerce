<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Daniel Sch�ttgen <ds@marketing-factory.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Contains standard selection trees/rules.
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * @author	Daniel Sch�ttgen <ds@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */


require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_leafdata.php');


/**
 * order leaf data class
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_leafOrderData extends tx_graytree_leafData {

	var $name = 'category title';

	/**
	 * Constructor
	 * 
	 * @param	void
	 * @return	void
	 */
	function tx_commerce_leafOrderData()	{
		global $LANG, $BACK_PATH;

		$this->title=$LANG->sL('LLL:EXT:commerce/locallang_be.php:order',1);
	

		$this->table='tx_commerce_orders';
		$this->parentTable='tx_commerce_categories';
		$this->parentField='';
		$this->mm_field='parent_category';
		$this->mm_table='tx_commerce_categories_parent_category_mm';
		$this->clause=' AND NOT deleted ORDER BY sorting,title';
		$this->fieldArray = Array('uid','title');
		$this->defaultList = 'uid,pid,tstamp,sorting';
	}

}




if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leaforderdata.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leaforderdata.php']);
}
?>
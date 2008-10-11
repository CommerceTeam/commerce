<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005- Franz Holzinger <kontakt@fholzinger.com>
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
 * product leaf data class
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id: class.tx_commerce_leafproductdata.php 8328 2008-02-20 18:02:10Z ischmittis $
 */

require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_leafdata.php');


class tx_commerce_leafProductData extends tx_graytree_leafData {

	var $name = 'product title';

	/**
	 * Constructor
	 * 
	 * @param	void
	 * @return	void
	 */
	function tx_commerce_leafProductData()	{
		global $LANG, $BACK_PATH;
		
		if(is_object($LANG)){
			$this->title=$LANG->sL('LLL:EXT:commerce/locallang_be.php:product',1);
		}
		$this->table='tx_commerce_products';
		$this->parentTable='tx_commerce_categories';
		$this->parentField='';
		$this->mm_field='categories';
		$this->mm_table='tx_commerce_products_categories_mm ';
		$this->clause=' AND NOT deleted ORDER BY sorting,title';
		$this->fieldArray = Array('uid','title');
		$this->defaultList = 'uid,pid,tstamp,sorting';
	}


}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafproductdata.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafproductdata.php']);
}
?>
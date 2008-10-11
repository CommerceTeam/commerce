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
 * $Id$
 */

require_once(PATH_txgraytree.'lib/class.tx_graytree_leafdata.php');
require_once(PATH_txcommerce.'lib/class.tx_commerce_leafproductdata.php');


class tx_commerce_leafArticleData extends tx_graytree_leafData {

	var $name = 'article title';

	/**
	 * Constructor
	 * 
	 * @param	void
	 * @return	void
	 */
	function tx_commerce_leafArticleData()	{
		global $LANG, $BACK_PATH;
		
		if(is_object($LANG)){
			$this->title=$LANG->sL('LLL:EXT:commerce/locallang_be.php:article',1);
		}
		$this->table='tx_commerce_articles';
		$this->parentTable='tx_commerce_products';
		$this->parentTableAlias='products';
		$this->parentField='uid_product';
		$this->parentLeafData='tx_commerce_leafProductData';
		$this->mm_field='';
		$this->mm_table='';
		$this->clause=' AND NOT deleted ORDER BY sorting,title';
		$this->fieldArray = Array('uid','title');
		$this->defaultList = 'uid,pid,tstamp,sorting';
	}


}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafarticledata.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafarticledata.php']);
}
?>
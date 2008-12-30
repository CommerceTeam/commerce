<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * Contains the definition of tree view class which form the category tree
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */



//require_once(PATH_t3lib.'class.t3lib_foldertree.php');
//
//require_once(PATH_txcommerce.'lib/class.tx_commerce_div.php');

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_treeview.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafcategorydata.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafproductdata.php');
require_once(PATH_txcommerce.'lib/class.tx_commerce_leafarticledata.php');

require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_browsetree.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_db.php');




/**
 * category tree class with view and data member arrays
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_treeCategory extends tx_graytree_browseTree {

	/**
	 * Constructor which builds the category tree
	 * 
	 * @param	void
	 * @return	void
	 */
	function tx_commerce_treeCategory()	{
		global $LANG, $BACK_PATH;

		$this->treeInfoArray = array ('data' => 'tx_graytree_db', 'view' => 'tx_commerce_treeView');
		
		$tempArray = array ('data' => 'tx_commerce_leafCategoryData', 'view' => 'tx_commerce_leafCategoryView');
		$this->leafInfoArray[] = $tempArray;
		$tempArray = array ('data' => 'tx_commerce_leafProductData', 'view' => 'tx_commerce_leafProductView');
		$this->leafInfoArray[] = $tempArray;
		$tempArray = array ('data' => 'tx_commerce_leafArticleData', 'view' => 'tx_commerce_leafArticleView');
		$this->leafInfoArray[] = $tempArray;
		
		$simpleMode = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
		$simpleMode = $simpleMode['simpleMode'];
		if(!$simpleMode) {
			$tempArray = array ('data' => 'tx_commerce_leafArticleData', 'view' => 'tx_commerce_leafArticleView');
			$this->leafInfoArray[] = $tempArray;
		}
		
			//+++ TODO: nicht im Konstruktor! $this->init($this->leafArray);
	}

}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_treecategory.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_treecategory.php']);
}
?>
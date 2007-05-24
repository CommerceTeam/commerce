<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */

//require_once(t3lib_extmgm::extPath('commerce')'lib/class.tx_commerce_db.php');
//require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_div.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_tcefunc.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_browsetree.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_db.php');


require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_treecategory.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafcategorydata.php');
//require_once(PATH_txcommerce.'lib/class.tx_commerce_leafproductview.php'); // This is to make the products visible in the tree
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_tcecategoryview.php');


/**
 * category tree class
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_tceFunc_categoryTree extends tx_graytree_browseTree {
	var $treeName = 'tx_commerce_tceFunc_categoryTree';	// name of the tree

	/**
	 * initialisation
	 * 
	 * @param 	table name for following data
	 * @param	startRow		the row where the tree has been started in TCE
	 * @param	limitCatArray	Array of categories from which no subcategories shall be shown
	 * @param 	leafInfoArray   Array of leaves to be shown in the TCE field tree
				example how to display products:
				'leafInfoArray' => Array (
					Array (
						'data' => 'tx_commerce_leafProductData',
						'view' => 'tx_commerce_leafProductView'
					)
				),
	 * @return	void
	 */
	function init($table='', $startRow=array(), $limitCatArray=array(), $leafInfoArray=array())	{
		global $LANG, $BACK_PATH;

		$this->treeInfoArray = array ('data' => 'tx_graytree_db', 'view' => 'tx_commerce_treeView');
		
		$tempArray = array ('data' => 'tx_commerce_leafCategoryData', 'view' => 'tx_commerce_tceCategoryView');
		$this->leafInfoArray[] = $tempArray;
		$this->leafInfoArray = array_merge((array)$this->leafInfoArray, (array)$leafInfoArray);
		
			// this must not be called in the constructor! $this->init($this->leafArray);
		parent::init($table, $startRow, $limitCatArray);
		$this->setScript(t3lib_div::getIndpEnv('REQUEST_URI')); 

	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_tcefunc_categorytree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_tcefunc_categorytree.php']);
}

?>
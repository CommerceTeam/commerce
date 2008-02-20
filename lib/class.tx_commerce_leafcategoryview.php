<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006- Franz Holzinger <kontakt@fholzinger.com>
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
 * Contains the category leaf view
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */


require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_treeview.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafcategorydata.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_browseleafview.php');

 
 
class tx_commerce_leafCategoryView extends tx_commerce_browseLeafView {
	var $title = 'txcommerceCategory';
	var $name =  'txcommerceCategory';
	var $isCategory = true;
  	var $usePM = true;

	
	/**
	 * initialisation
	 * 
	 * @param	object	reference to the leaf data object
	 * @param	object	reference to the tree object
	 * @return	void
	 */
	function init(&$graytree_leafData, &$tree)	{
		global $BACK_PATH;
		
		$this->iconPath = $BACK_PATH.PATH_txcommerce_icon_tree_rel;
		parent::init($graytree_leafData, $tree);
	}

	/**
	 * Constructor
	 * 
	 * @param	void
	 * @return	void
	 */
	function tx_commerce_leafCategoryView()	{
		global $LANG, $TCA, $BACK_PATH;

		$table = 'tx_commerce_categories';	
		$this->title = $TCA[$table]['ctrl']['title']; 
		$this->isTreeViewClass = TRUE;

		if(is_object($LANG))	{
			$this->title = $LANG->sL('LLL:EXT:commerce/locallang_be.php:category',1);
		}
		$this->leafName = 'txcommerceCategory';
		$this->domIdPrefix = $this->leafName;
		$this->ext_IconMode = true; // no context menu on icons
	}

}




if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafcategoryview.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafcategoryview.php']);
}
?>
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
 * Contains the product leaf class
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id: class.tx_commerce_leafproductview.php 569 2007-03-05 17:02:07Z franz $
 */

//
//
//require_once(PATH_t3lib.'class.t3lib_foldertree.php');;
//
//require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_div.php');

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_treeview.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafproductdata.php');
//require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_browsetree.php');


class tx_commerce_leafProductView extends tx_commerce_browseLeafView {
	var $title = 'txcommerceProduct';
	var $name =  'txcommerceProduct';
	var $isCategory = false;
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
	function tx_commerce_leafProductView()	{
		global $LANG, $TCA, $BACK_PATH;

		$table='tx_commerce_products';

		$this->isTreeViewClass = TRUE;
		if(is_object($LANG)){		
			$this->title=$LANG->sL($TCA[$table]['ctrl']['title'],1);
		}
		$this->leafName='txcommerceProduct';
		$this->domIdPrefix=$this->leafName;
		$this->ext_IconMode = true; // no context menu on icons
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafproductview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafproductview.php']);
}
?>
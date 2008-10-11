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
 * Contains the article leaf class
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id: class.tx_commerce_leafarticleview.php 8328 2008-02-20 18:02:10Z ischmittis $
 */


require_once(PATH_txcommerce.'lib/class.tx_commerce_treeview.php');
require_once(PATH_txcommerce.'lib/class.tx_commerce_leafarticledata.php');


class tx_commerce_leafArticleView extends tx_commerce_browseLeafView {
	var $title = 'txcommerceArticle';
	var $name =  'txcommerceArticle';
	var $isCategory = false;
  	var $usePM = false;

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
	function tx_commerce_leafArticleView()	{
		global $LANG, $TCA, $BACK_PATH;

		$table='tx_commerce_products';

		$this->isTreeViewClass = TRUE;
		if(is_object($LANG)){		
			$this->title=$LANG->sL($TCA[$table]['ctrl']['title'],1);
		}
		$this->leafName='txcommerceArticle';
		$this->domIdPrefix=$this->leafName;
		$this->ext_IconMode = true; // no context menu on icons
	}


}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafarticleview.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_leafarticleview.php']);
}
?>
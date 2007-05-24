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
 * Commerce base class for tree view classes
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */

require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_view.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_div.php');


define('COMMERCE_TREEVIEW_DLOG', '0');


class tx_commerce_treeView extends tx_graytree_View {
	var $rootIconName = 'commerce_globus.gif';  // Icon for the root of the tree
	var $treeName = 'txcommerceCategoryTree';
	var $title = 'Category';	
	var $domIdPrefix = 'txcommerceCategoryTree';


	/**
	 * Initialize the tree class. Needs to be overwritten
	 * gets the Root-pid's for tx_commerce and sets the values to  $this->rootPid   $this->orderPid   $this->modPid
	 * The commerce sysfolders are created and initialized here.
	 *
	 * @param   graytree_db 	Treelib data model
	 * @param	leafInfoArray	Array of leaf data and view classes
	 * @param 	table name for following data
	 * @param	startRow		the row where the tree has been started in TCE
	 * @param	limitCatArray	Array of categories from which no subcategories shall be shown
	 * @return	void
	 * @see tx_graytree_View::init
	 */
	function init(&$graytree_db, $leafInfoArray, $table, $startRow, $limitCatArray)	{
		parent::init($graytree_db, $leafInfoArray, $table, $startRow, $limitCatArray);

		/**
		* Initialize The Folders
		*/
		require_once (t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_create_folder.php');
		tx_commerce_create_folder::init_folders();
		list($modPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Commerce', 'commerce');
		list($prodPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Products', 'commerce',$modPid);
		$this->rootPid = $prodPid;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_treeview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_treeview.php']);
}

?>
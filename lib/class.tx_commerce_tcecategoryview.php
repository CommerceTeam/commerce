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
 * Category view class for building of the category tree in a TCE form
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */


require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_div.php');

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_treecategory.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafcategoryview.php');



class tx_commerce_tceCategoryView extends tx_commerce_leafCategoryView {


	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @return	string
	 */

	function wrapTitle($title,$row)	{
		if($row['uid']>0) {
			if (in_array($row['uid'],$this->tree->TCEforms_nonSelectableItemsArray)) {
				return '<span style="color:grey">'.$title.'</span>';
			} else {
				$aOnClick = 'setFormValueFromBrowseWin(\''.$this->tree->TCEforms_itemFormElName.'\','.$row['uid'].',\''.tx_graytree_div::slashJS($title).'\'); return false;';
				return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
			}
		} else {
			return $title;
		}
	}

}




if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_tcecategoryview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_tcecategoryview.php']);
}

?>
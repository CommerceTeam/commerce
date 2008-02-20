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
 * Commerce base class for selection tree leaf view classes
 * All table leaf view classes in commerce must be derived from this class.
 * So this will fit into the Graytree Library tree view display.
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */

require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_leafview.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_div.php');



#define('COMMERCE_BROWSELEAFVIEW_DLOG', '1');


class tx_commerce_browseLeafView extends tx_graytree_leafView {

	/**
	 * element browser mode
	 */
	var $modeEB = false;

	/**
	 * enables selection icons: + = -
	 */
	var $modeSelIcons = true;

	var $deselectValue = 0;

	var $clickMenuScript = ''; // use the clickmenu of the Graytree Library
	var $extKey = COMMERCE_EXTkey; // extension key needed for a Commerce specific clickmenu



	/**
	 * Wrap around the title string
	 *
	 * @param	string		$title
	 * @param	array		$row
	 * @return	string		HTML
	 */
	function wrapTitle($title,$row,$bank=0)	{
		global $BACK_PATH;
		$res = '';

		$extra = '';
		/*if($row['uid'] AND $this->modeSelIcons){				
			$extra1 = ' &nbsp;<span class="txcommerce-editbar">';
			$extra2 = '</span>';
		}*/
		$res = parent::wrapTitle($title,$row);

		return $res;
	}



	/**
	 * returns the HTML code for the tree depending on the modeEB settings
	 *
	 * @param	[type]		$treeArr: ...
	 * @return	[type]		...
	 */
	function printTree($treeArr='')	{
		$res = parent::printTree($treeArr);
		
		return $res;
	}


}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_browseleafview.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/lib/class.tx_commerce_browseleafview.php"]);
}
?>
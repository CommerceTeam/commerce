<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 20062005 Franz Holzinger (kontakt@fholzinger.com)
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
 * COMMERCE edit nav frame.
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * @author	Thomas Hempel <thomas@work.de>
 * @package	TYPO3
 * @subpackage tx_commerce
 *
 * $Id: class.tx_commerce_category_navframe.php 562 2007-03-02 10:16:12Z ingo $
 */

unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile("EXT:commerce/mod_systemdata/locallang.xml");

/**
 * Main script class for the tree edit navigation frame
 *
 * @author	Thomas Hempel <thomas@work.de>
 * @package	TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_category_navframe	{
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TYPO3_CONF_VARS;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;

		$this->doc->JScode=$this->doc->wrapScriptTags('
	function jumpTo(func,linkObj)	{	//
		var theUrl = top.TS.PATH_typo3+top.currentSubScript+"?SET[function]="+func;

		if (top.condensedMode)	{
			top.content.document.location=theUrl;
		} else {
			parent.list_frame.document.location=theUrl;
		}

		'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}').'
		return false;
	}
		');
	}

	/**
	 * Main function, rendering the browsable page tree
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG,$BACK_PATH;

		$this->content.= $this->doc->startPage('Navigation');
		
			// attributes
		$this->content .= '<table border="0">
			<tr><td rowspan="2" valign="top"><img src="../res/icons/table/attributes.gif" /></td>
			<td><a href="#" onclick="return jumpTo(1, this);">' .$LANG->getLL('title_attributes') .'</a></td>
			<tr><td>' .$LANG->getLL('desc_attributes') .'<br /><br /></td></tr>
			
			<tr><td rowspan="2" valign="top"><img src="../res/icons/table/manufacturer.gif" /></td>
			<td><a href="#" onclick="return jumpTo(2, this);">' .$LANG->getLL('title_manufacturer') .'</a></td>
			<tr><td>' .$LANG->getLL('desc_manufacturer') .'<br /><br /></td></tr>
			
			<tr><td rowspan="2" valign="top"><img src="../res/icons/table/supplier.gif" /></td>
			<td><a href="#" onclick="return jumpTo(3, this);">' .$LANG->getLL('title_supplier') .'</a></td>
			<tr><td>' .$LANG->getLL('desc_supplier') .'<br /><br /></td></tr>
					</table>';
		
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/mod_systemdata/class.tx_commerce_category_navframe.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/mod_systemdata/class.tx_commerce_category_navframe.php']);
}




// Make instance:

$SOBE = t3lib_div::makeInstance('tx_commerce_category_navframe');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();


?>
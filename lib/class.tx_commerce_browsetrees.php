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
*  the  Free Software Forundation; either version 2 of the License, or  (at your
* option) any later version.
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
 * Main script class for the tree navigation frame.
 * This is used in the nav frame or the element browser.
 * This class makes it possible to display several tree views at a time.
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_commerce
 * 
 * $Id$
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_treecategory.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafcategoryview.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_leafproductview.php');
require_once(PATH_txcommerce.'lib/class.tx_commerce_leafarticleview.php');

#define('COMMERCE_BROWSETREES_DLOG', '1');


class tx_commerce_browseTrees {

	var $arrayTree = array();

	/**
	 * initialize the browsable trees
	 *
	 * @param	string		script name to link to
	 * @param	boolean		Element browser mode
	 * @return	void
	 */
	function init($thisScript, $modeEB=false)	{
		global $BE_USER,$LANG,$BACK_PATH;

		$this->initLeafClasses($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['leafClasses'], $thisScript, $modeEB);
	}

	/**
	 * initialize the browsable trees
	 *
	 * @param	array		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['leafClasses']
	 * @param	string		script name to link to
	 * @param	boolean		Element browser mode - unused (TODO: delete this parameter)
	 * @return	void
	 */
	function initLeafClasses($leafClassesArr, $thisScript, $modeEB=false)	{
		global $BE_USER,$LANG,$BACK_PATH;

		if (TYPO3_DLOG && COMMERCE_BROWSETREES_DLOG)   
			t3lib_div::devLog('initLeafClasses vor if', COMMERCE_EXTkey);						

		if (is_array($leafClassesArr))	{
			foreach($leafClassesArr as $k1 => $leafClassArr) {
				foreach($leafClassArr as $classKey => $classRef)	{
					
					if (TYPO3_DLOG && COMMERCE_BROWSETREES_DLOG)   
						t3lib_div::devLog('initLeafClasses $classKey = '.$classKey, COMMERCE_EXTkey);						
					if (TYPO3_DLOG && COMMERCE_BROWSETREES_DLOG)   
						t3lib_div::devLog('initLeafClasses $classRef = '.$classRef, COMMERCE_EXTkey);						
					
					if (is_object($obj = &t3lib_div::getUserObj($classRef)))	{
						if (TYPO3_DLOG && COMMERCE_BROWSETREES_DLOG)   
							t3lib_div::devLog('tx_commerce_browsetrees getUserObj', COMMERCE_EXTkey);						

						if (!$obj->isPureSelectionClass)	{
							// The first element is also the tree selection class
							if ($k1 == 0)	{
									// object is a treeview class itself or just no tree class
									// This must be the category
								$this->arrayTree[$classKey] = &$obj;
								$obj->init();
								$obj->setScript($thisScript);
								$obj->BE_USER = $BE_USER;
								$obj->modeEB = $modeEB; // TODO: delete this line
								$obj->setExtIconMode(true);  // no context menu on icons
								if (TYPO3_DLOG && COMMERCE_BROWSETREES_DLOG)   
									t3lib_div::devLog('initLeafClasses $this->arrayTree['.$classKey.'] wird gef�llt', COMMERCE_EXTkey);						

							} else {
								// the object is an element class like category or product
							}
						} else	{
							if (TYPO3_DLOG && COMMERCE_BROWSETREES_DLOG)   
								t3lib_div::devLog('*** ERROR in TYPO3 tx_commerce_browsetrees kein getUserObj', COMMERCE_EXTkey);						
						}
	
					} else {
						if (TYPO3_DLOG && COMMERCE_BROWSETREES_DLOG)   
							t3lib_div::devLog('*** ERROR in TYPO3 initLeafClasses no object for class '.$classKey.' in '.$classRef, COMMERCE_EXTkey);						
					}
				}
			}
		} else {
			t3lib_div::devLog('*** ERROR in TYPO3 tx_commerce_browsetrees selection class is not an array', COMMERCE_EXTkey);						
		}
	}



	/**
	 * rendering the browsable trees
	 *
	 * @return	string		tree HTML content
	 */
	function getTrees()	{
		global $LANG,$BACK_PATH;

		$tree = '';
		if (is_array($this->arrayTree)) {
			foreach($this->arrayTree as $treeName => $treeObj)	{
				$tree .= $treeObj->printTree();
			}
		}

		return $tree;
	}



}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_browsetrees.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_browsetrees.php']);
}
?>

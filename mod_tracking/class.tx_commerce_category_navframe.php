<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger (kontakt@fholzinger.com)
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
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tx_comm
 * 
 * $Id: class.tx_commerce_category_navframe.php 575 2007-03-19 20:29:59Z franz $
 */

unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');




require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_div.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_browsetrees.php');

#define('COMMERCE_NAVFRAME_DLOG', '1');



/**
 * Main script class for the tree edit navigation frame
 *
 * @author	@author	Ren� Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage tx_comm
 * siehe dam
 */
class tx_commerce_category_navframe {

	var $categoryTree;

	var $doc;
	var $content;

		// Internal, static: _GP
	var $currentSubScript;

		// Constructor:
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TYPO3_CONF_VARS;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;

		$this->currentSubScript = t3lib_div::_GP('currentSubScript');

			// Setting highlight mode:
		$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');


		$this->doc->JScode='';


			// Setting JavaScript for menu.
		$this->doc->JScode=$this->doc->wrapScriptTags(
			($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'

			function jumpTo(params,linkObj,highLightID)	{
				var theUrl = top.TS.PATH_typo3+top.currentSubScript+"?"+params;

				if (top.condensedMode)	{
					top.content.document.location=theUrl;
				} else {
					parent.list_frame.document.location=theUrl;
				}
		        '.($this->doHighlight?'hilight_row("row"+top.fsMod.recentIds["txcommerceM1"],highLightID);':'').'
				'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}').'
				return false;
			}


				// Call this function, refresh_nav(), from another script in the backend if you want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
				// See t3lib_BEfunc::getSetUpdateSignal()
			function refresh_nav()	{
				window.setTimeout("_refresh_nav();",0);
			}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
			function _refresh_nav()	{
				document.location="class.tx_commerce_categ_navframe.php?unique='.time().'";
			}

				// Highlighting rows in the page tree:
			function hilight_row(frameSetModule,highLightID) {	//

					// Remove old:
				theObj = document.getElementById(top.fsMod.navFrameHighlightedID[frameSetModule]);
				if (theObj)	{
					theObj.style.backgroundColor="";
				}

					// Set new:
				top.fsMod.navFrameHighlightedID[frameSetModule] = highLightID;
				theObj = document.getElementById(highLightID);
				if (theObj)	{
					theObj.style.backgroundColor="'.t3lib_div::modifyHTMLColorAll($this->doc->bgColor,-20).'";
				}
			}
		');

		if (TYPO3_DLOG && COMMERCE_NAVFRAME_DLOG)   t3lib_div::devLog('TYPO3 tx_commerce_category_navframe::init  $this->doc->JScode = '.$this->doc->JScode, COMMERCE_EXTkey);
	
		$CMparts=$this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode.=$CMparts[0];
		$this->doc->postCode.= $CMparts[2];

			// should be float but gives bad results
		$this->doc->inDocStyles .= '
			.txcommerce-editbar, .txcommerce-editbar > a >img {
				background-color:'.t3lib_div::modifyHTMLcolor($this->doc->bgColor,-15,-15,-15).';
			}
			';
			
			// the trees
		$this->browseTrees = t3lib_div::makeInstance('tx_commerce_browseTrees');
		
		#debug($this->browseTrees);
		
		$selClass = array();
					// only a browsee tree for categories:
					// the first element is the category tree
		$selClass[] = array ('txcommerceCategoryTree' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['leafClasses']['txcommerceCategoryTree']);
				
					// the next elements are the leaves 			
		$selClass[] = array ('txcommerceCategory' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['leafClasses']['txcommerceCategory']);
		$selClass[] = array ('txcommerceProduct' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['leafClasses']['txcommerceProduct']);
		
		#debug($selClass, 'Aufruf2 $selClass class.tx_commerce_category_navframe.php', __LINE__, __FILE__);
		$this->browseTrees->initLeafClasses($selClass, 'class.tx_commerce_category_navframe.php');
		
		#debug($var, 'vor setExtIconMode(false)', __LINE__, __FILE__);
		// there will only be an arrayTree for the categories
		$this->browseTrees->arrayTree['txcommerceCategoryTree']->setExtIconMode(false); // context menu on icons
		$this->browseTrees->arrayTree['txcommerceCategoryTree']->modeSelIcons = false;
		#debug($this->browseTrees->arrayTree['txcommerceCategoryTree'], ' $this->browseTrees->arrayTree[\'txcommerceCategory\']', __LINE__, __FILE__);
	}

	/**
	 * Main function, rendering the browsable page tree
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG,$BACK_PATH;

		$this->content = '';
		$this->content.= $this->doc->startPage('Navigation');
		if (TYPO3_DLOG && COMMERCE_NAVFRAME_DLOG)   t3lib_div::devLog('tx_commerce_category_navframe::main vor getTrees   ', COMMERCE_EXTkey);

		$this->content.= $this->browseTrees->getTrees();

	#debug($this->content, 'nach getBrowsableTree  $this->content', __LINE__, __FILE__);


		$this->content.= '
			<p class="c-refresh">
				<a href="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'">'.
				'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/refresh_n.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'" alt="" />'.
				$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'</a>
			</p>
			<br />';

		if (TYPO3_DLOG && COMMERCE_NAVFRAME_DLOG)   t3lib_div::devLog('tx_commerce_category_navframe::main $this->content = '.$this->content, COMMERCE_EXTkey);
	
			// Adding highlight - JavaScript
		if ($this->doHighlight)	$this->content .=$this->doc->wrapScriptTags('
			hilight_row("",top.fsMod.navFrameHighlightedID["web"]);
		');
	}


	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	Content
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_tracking/class.tx_commerce_category_navframe.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_tracking/class.tx_commerce_category_navframe.php']);
}




// Make instance:

$SOBE = t3lib_div::makeInstance('tx_commerce_category_navframe');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();


?>
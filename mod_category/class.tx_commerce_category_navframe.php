<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 - 2010 Marketing Factory Consulting GmbH <typo3@marketing-factory.de>
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
 * Implements the navframe for the categories
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
unset($MCONF);

/**
 * @TODO: Find a better solution for the @ at this place, since some globals could not be defined
*/
if (!(defined('TYPO3_REQUESTTYPE') || defined('TYPO3_REQUESTTYPE_AJAX'))) {
	require_once('conf.php');
	require_once($BACK_PATH.'init.php');
	require_once(PATH_typo3.'template.php');

	$LANG->includeLLFile('EXT:commerce/mod_category/locallang.xml');

} else {
	//In case of an AJAX Request the script including this script is ajax.php, from which the BACK PATH is ''
	require_once('init.php');
	require('template.php');
}

// Require ext update script.
require_once(t3lib_extmgm::extPath('commerce').'class.ext_update.php');

class tx_commerce_category_navframe {

	var $categoryTree;
	var $BACK_PATH = '../../../../typo3/'; ###MAKE THIS BE CALCULATED###
	var $doc;
	var $content;

		// Internal, static: _GP
	var $currentSubScript;

	/**
	 * Initializes the Tree
	 */
	function init() {
		//Get the Category Tree
		$this->categoryTree = t3lib_div::makeInstance('tx_commerce_categorytree');
		$this->categoryTree->setBare(false);

		// Get SimpleMode.
		$sm = (int)$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode'];

		// Assign config.
		$this->categoryTree->setSimpleMode($sm);

		$this->categoryTree->init();


	}

	/**
	 * Initializes the Page
	 */
	function initPage() {
		global $BE_USER;


		// Create template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $this->BACK_PATH;
		$this->doc->setModuleTemplate('../typo3conf/ext/commerce/mod_category/templates/alt_db_navframe.html'); ###MAKE THIS PATH BE CALCULATED###
		$this->doc->docType  = 'xhtml_trans';

		$this->doc->JScode='';


		// Setting JavaScript for menu.
		$this->doc->JScode=$this->doc->wrapScriptTags(
			($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'

			function jumpTo(id,linkObj,highLightID,script)	{
				var theUrl;

				if (script)	{
					theUrl = top.TS.PATH_typo3+script;
				} else {
					theUrl = top.TS.PATH_typo3+top.currentSubScript;
				}

				theUrl = theUrl+"?"+id;

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
		');

		$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
		$this->doc->loadJavascriptLib('../typo3conf/ext/commerce/mod_access/tree.js'); ###MAKE PATH BE CALCULATED, NOT FIXED### ###WHAT TO DO WITH THOSE FILES? BETTER MAKE RES FOLDER###
		$this->doc->JScode .= $this->doc->wrapScriptTags('Tree.ajaxID = "tx_commerce_category_navframe::ajaxExpandCollapse";');
		// Adding javascript code for AJAX (prototype), drag&drop and the pagetree as well as the click menu code
		//$this->doc->getDragDropCode('pages');
		$this->doc->getContextMenuCode();
		//$this->doc->loadJavascriptLib('contrib/scriptaculous/scriptaculous.js?load=effects');

		/*$this->doc->JScode .= $this->doc->wrapScriptTags(
		($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'
		// setting prefs for pagetree and drag & drop
		'.($this->doHighlight ? 'Tree.highlightClass = "'.$hlClass.'";' : '').'

		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank)	{ //
			var theUrl = top.TS.PATH_typo3 + top.currentSubScript + "?id=" + id;
			top.fsMod.currentBank = bank;

			if (top.condensedMode) top.content.location.href = theUrl;
			else                   parent.list_frame.location.href=theUrl;

			'.($this->doHighlight ? 'Tree.highlightActiveItem("web", highlightID + "_" + bank);' : '').'
			'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) linkObj.blur(); ').'
			return false;
		}
		'.($this->cMR?"jumpTo(top.fsMod.recentIds['web'],'');":'').

			($this->hasFilterBox ? 'var TYPO3PageTreeFilter = new PageTreeFilter();' : '') . '

		');*/

		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	function main() {
		global $LANG,$CLIENT;

		// Check if commerce needs to be updated.
		if($this->isUpdateNecessary()) {
			$tree = $LANG->getLL('ext.update');
		} else {
			//Get the Browseable Tree
			$tree = $this->categoryTree->getBrowseableTree();
		}


		// Outputting page tree:
		$this->content .= '<div id="PageTreeDiv">'.$tree.'</div>';

		$markers = array(
			'IMG_RESET'     => '',//'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/close_gray.gif', ' width="16" height="16"').' id="treeFilterReset" alt="Reset Filter" />',
			'WORKSPACEINFO' => '',//$this->getWorkspaceInfo(),
			'CONTENT'       => $this->content
		);
		$subparts = array();

		if (!$this->hasFilterBox) {
			$subparts['###SECOND_ROW###'] = '';
		}

		// Build the <body> for the module
		$this->content = $this->doc->startPage('Commerce Category List');
		$this->content.= $this->doc->moduleBody('', '', $markers, $subparts);
		$this->content.= $this->doc->endPage();

		//$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	function printContent() {
		echo $this->content;
	}

	/**
	 * Checks if an update of the commerce extension is necessary
	 *
	 * @author	Erik Frister
	 *
	 * @return boolean
	 */
	protected function isUpdateNecessary() {
		$updater = t3lib_div::makeInstance('ext_update');

		return $updater->access();
	}

	/**
	 * Makes the AJAX call to expand or collapse the categorytree.
	 * Called by typo3/ajax.php
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: reference of the TYPO3AJAX object of this request
	 * @return	void
	 */
	function ajaxExpandCollapse($params, &$ajaxObj) {
		global $LANG;

		//Extract the ID and Bank
		$id   = 0;
		$bank = 0;

		$PM = t3lib_div::_GP('PM');
		// IE takes anchor as parameter
		if(($PMpos = strpos($PM, '#')) !== false) { $PM = substr($PM, 0, $PMpos); }
		$PM = explode('_', $PM);

		//Now we should have a PM Array looking like:
		//0: treeName, 1: leafIndex, 2: Mount, 3: set/clear [4:,5:,.. further leafIndices], 5[+++]: Item UID

		if(is_array($PM) && count($PM) >= 4) {
			$id 	= $PM[count($PM)-1]; //ID is always the last Item
			$bank 	= $PM[2];
		}

		//Load the tree
		$this->init();
		$tree = $this->categoryTree->getBrowseableAjaxTree($PM);

		//if (!$this->categoryTree->ajaxStatus) { ###CHECK THE AJAX ERROR###
		//	$ajaxObj->setError($tree);
		//} else	{
			$ajaxObj->addContent('tree', $tree);
		//}
	}

}

//XClass Statement
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/class.tx_commerce_category_navframe.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/class.tx_commerce_category_navframe.php']);
}

// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$SOBE = t3lib_div::makeInstance('tx_commerce_category_navframe');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}
?>
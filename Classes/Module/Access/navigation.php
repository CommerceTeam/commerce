<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2011 Marketing Factory Consulting GmbH <typo3@marketing-factory.de>
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
 * Main script class for the tree edit navigation frame
 */
unset($MCONF);

if (!(defined('TYPO3_REQUESTTYPE') || defined('TYPO3_REQUESTTYPE_AJAX'))) {
	require_once('conf.php');
	/** @noinspection PhpIncludeInspection */
	require_once($BACK_PATH . 'init.php');
	/** @noinspection PhpIncludeInspection */
	require_once(PATH_typo3 . 'template.php');
} else {
		// In case of an AJAX Request the script including this script is ajax.php, from which the BACK PATH is ''
	/** @noinspection PhpIncludeInspection */
	require_once('init.php');
	/** @noinspection PhpIncludeInspection */
	require('template.php');
}

class Tx_Commerce_Module_Access_Navigation {
	/**
	 * @var tx_commerce_categorytree
	 */
	protected $categoryTree;

	/**
	 * @var string
	 */
	protected $BACK_PATH = '../../../../typo3/';

	/**
	 * @var template
	 */
	public $doc;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var string
	 */
	protected $currentSubScript;

	/**
	 * @var boolean
	 */
	protected $doHighlight;

	/**
	 * @var boolean
	 */
	protected $hasFilterBox;

	/**
	 * Initializes the Tree
	 */
	public function init() {
			// Get the Category Tree without the Products and the Articles
		$this->categoryTree = t3lib_div::makeInstance('tx_commerce_categorytree');
		$this->categoryTree->init();
	}

	/**
	 * Initializes the Page
	 */
	public function initPage() {
			// Create template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $this->BACK_PATH;
			// @todo MAKE THIS PATH BE CALCULATED
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_access_navframe.html');
		$this->doc->docType  = 'xhtml_trans';
		$this->doc->JScode = '';

			// Setting JavaScript for menu.
		$this->doc->JScode = $this->doc->wrapScriptTags(
			($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '

			function jumpTo(id, linkObj, highLightID, script) {
				var theUrl;

				if (script) {
					theUrl = top.TS.PATH_typo3 + script;
				} else {
					theUrl = top.TS.PATH_typo3 + top.currentSubScript;
				}

				theUrl = theUrl + "?" + id;

				if (top.condensedMode) {
					top.content.document.location = theUrl;
				} else {
					parent.list_frame.document.location = theUrl;
				}
				' . ($this->doHighlight ? 'hilight_row("row" + top.fsMod.recentIds["txcommerceM1"], highLightID);' : '') . '
				' . (!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) { linkObj.blur(); }') . '
				return false;
			}

				// Call this function, refresh_nav(), from another script in the backend if you want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
				// See t3lib_BEfunc::getSetUpdateSignal()
			function refresh_nav() {
				window.setTimeout("_refresh_nav();", 0);
			}
		');

		$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
			// @todo MAKE PATH BE CALCULATED, NOT FIXED
		$this->doc->loadJavascriptLib('../' . PATH_TXCOMMERCE_REL . 'Resources/Public/Javascript/tree.js');
			// Adding javascript code for AJAX (prototype), drag&drop and the pagetree as well as the click menu code
		$this->doc->getContextMenuCode();

		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	/**
	 * @return void
	 */
	public function main() {
			// Get the Browseable Tree
		$tree = $this->categoryTree->getBrowseableTree();

			// Outputting page tree:
		$this->content .= '<div id="PageTreeDiv">' . $tree . '</div>';

		$markers = array(
			'IMG_RESET' => '',
			'WORKSPACEINFO' => '',
			'CONTENT' => $this->content
		);
		$subparts = array();

		if (!$this->hasFilterBox) {
			$subparts['###SECOND_ROW###'] = '';
		}

			// Build the <body> for the module
		$this->content = $this->doc->startPage('Commerce Access List');
		$this->content .= $this->doc->moduleBody('', '', $markers, $subparts);
		$this->content .= $this->doc->endPage();
	}

	/**
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Makes the AJAX call to expand or collapse the categorytree.
	 * Called by typo3/ajax.php
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: reference of the TYPO3AJAX object of this request
	 * @return	void
	 */
	public function ajaxExpandCollapse($params, &$ajaxObj) {
			// Extract the ID and Bank
		$PM = t3lib_div::_GP('PM');
			// IE takes anchor as parameter
		if (($PMpos = strpos($PM, '#')) !== FALSE) {
			$PM = substr($PM, 0, $PMpos);
		}
		$PM = explode('_', $PM);

			// Load the tree
		$this->init();
		$tree = $this->categoryTree->getBrowseableAjaxTree($PM);

		$ajaxObj->addContent('tree', $tree);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_access/class.tx_commerce_access_navframe.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_access/class.tx_commerce_access_navframe.php']);
}

	// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	/** @var Tx_Commerce_Module_Access_Navigation $SOBE */
	$SOBE = t3lib_div::makeInstance('Tx_Commerce_Module_Access_Navigation');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

?>
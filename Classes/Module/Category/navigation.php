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
 */
unset($MCONF);

/**
 * @TODO: Find a better solution for the @ at this place, since some globals could not be defined
*/
if (!(defined('TYPO3_REQUESTTYPE') || defined('TYPO3_REQUESTTYPE_AJAX'))) {
	require_once('conf.php');
	/** @noinspection PhpIncludeInspection */
	require_once($BACK_PATH . 'init.php');
	/** @noinspection PhpIncludeInspection */
	require_once($BACK_PATH . 'template.php');

	$LANG->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_category.xml');
} else {
		// In case of an AJAX Request the script including this script is ajax.php, from which the BACK PATH is ''
	/** @noinspection PhpIncludeInspection */
	require_once('init.php');
	/** @noinspection PhpIncludeInspection */
	require('template.php');
}

class Tx_Commerce_Module_Category_Navigation extends t3lib_SCbase {
	/**
	 * @var Tx_Commerce_Tree_CategoryTree
	 */
	protected $categoryTree;

	/**
	 * @var string
	 */
	protected $BACK_PATH = '../../../../../../typo3/';

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
	 *
	 * @return void
	 */
	public function init() {
			// Get the Category Tree
		$this->categoryTree = t3lib_div::makeInstance('Tx_Commerce_Tree_CategoryTree');
		$this->categoryTree->setBare(FALSE);
		$this->categoryTree->setSimpleMode((int) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode']);
		$this->categoryTree->init();
	}

	/**
	 * Initializes the Page
	 *
	 * @return void
	 */
	public function initPage() {
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_navigation.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set navframeTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_navigation.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_navigation.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_navigation.html';
			$this->doc->moduleTemplate = t3lib_div::getURL(PATH_site . $templateFile);
		}

			// Setting JavaScript for menu.
		$this->doc->JScode = $this->doc->wrapScriptTags(
			($this->currentSubScript ? 'top.currentSubScript = unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '

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
		$this->doc->loadJavascriptLib(PATH_TXCOMMERCE_REL . 'Resources/Public/Javascript/tree.js');
		$this->doc->JScode .= $this->doc->wrapScriptTags('Tree.ajaxID = "Tx_Commerce_Module_Category_Navigation::ajaxExpandCollapse";');
			// Adding javascript code for AJAX (prototype), drag&drop and the pagetree as well as the click menu code
		$this->doc->getContextMenuCode();

		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	/**
	 * @return void
	 */
	public function main() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Check if commerce needs to be updated.
		if ($this->isUpdateNecessary()) {
			$tree = $language->getLL('ext.update');
		} else {
				// Get the Browseable Tree
			$tree = $this->categoryTree->getBrowseableTree();
		}

		$markers = array(
			'IMG_RESET' => '',
			'WORKSPACEINFO' => '',
			'CONTENT' => $tree
		);
		$subparts = array();

		if (!$this->hasFilterBox) {
			$subparts['###SECOND_ROW###'] = '';
		}

			// Build the <body> for the module
		$this->content = $this->doc->startPage($language->sl('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:mod_category.navigation_title'));
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
	 * Checks if an update of the commerce extension is necessary
	 *
	 * @return boolean
	 */
	protected function isUpdateNecessary() {
		/** @var Tx_Commerce_Utility_UpdateUtility $updater */
		$updater = t3lib_div::makeInstance('Tx_Commerce_Utility_UpdateUtility');

		return $updater->access();
	}

	/**
	 * Makes the AJAX call to expand or collapse the categorytree.
	 * Called by typo3/ajax.php
	 *
	 * @param array $params: additional parameters (not used here)
	 * @param TYPO3AJAX &$ajaxObj: reference of the TYPO3AJAX object of this request
	 * @return void
	 */
	public function ajaxExpandCollapse($params, &$ajaxObj) {
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/class.tx_commerce_category_navframe.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_category/class.tx_commerce_category_navframe.php']);
}

	// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	/** @var Tx_Commerce_Module_Category_Navigation $SOBE */
	$SOBE = t3lib_div::makeInstance('Tx_Commerce_Module_Category_Navigation');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

?>
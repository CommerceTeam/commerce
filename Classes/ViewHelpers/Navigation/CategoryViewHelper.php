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

class Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper extends t3lib_SCbase {
	/**
	 * @var Tx_Commerce_Tree_CategoryTree
	 */
	protected $categoryTree;

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
		$this->doc->loadJavascriptLib($this->doc->backPath . 'js/tree.js');
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			Tree.thisScript = "../../../../../../typo3/ajax.php";
			Tree.ajaxID = "Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper::ajaxExpandCollapse";
		');
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

		$docHeaderButtons = $this->getButtons();

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
		$this->content .= $this->doc->moduleBody('', $docHeaderButtons, $markers, $subparts);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'refresh' => '',
		);

			// Refresh
		$buttons['refresh'] = '<a href="' . htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-system-refresh') .
		'</a>';

			// CSH
		$buttons['csh'] = str_replace(
			'typo3-csh-inline',
			'typo3-csh-inline show-right',
			t3lib_BEfunc::cshItem('xMOD_csh_commercebe', 'categorytree', $this->doc->backPath)
		);

		return $buttons;
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/Navigation/CategoryViewHelper.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/Navigation/CategoryViewHelper.php']);
}

?>
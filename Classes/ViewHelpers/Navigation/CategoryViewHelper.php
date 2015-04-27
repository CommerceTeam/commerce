<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper
 */
class Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper extends \TYPO3\CMS\Backend\Module\BaseScriptClass {
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
	 * @param bool $bare if TRUE only categories get rendered
	 * @return void
	 */
	public function init($bare = FALSE) {
		// Get the Category Tree
		$this->categoryTree = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryTree');
		$this->categoryTree->setBare($bare);
		$this->categoryTree->setSimpleMode((int) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['simpleMode']);
		$this->categoryTree->init();
	}

	/**
	 * Initializes the Page
	 *
	 * @param bool $bare if TRUE only categories get rendered
	 * @return void
	 */
	public function initPage($bare = FALSE) {
		/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate $doc */
		$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc = $doc;
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:commerce/Resources/Private/Backend/mod_navigation.html');
		$this->doc->showFlashMessages = FALSE;

		$this->doc->inDocStyles .= '
		#typo3-pagetree .x-tree-root-ct ul {
			padding-left: 19px;
			margin: 0;
		}

		.x-tree-root-ct ul li.expanded ul {
			background: url("/' . TYPO3_mainDir . '/sysext/t3skin/icons/gfx/ol/line.gif") repeat-y scroll left top transparent;
		}

		.x-tree-root-ct ul li.expanded.last ul {
			background: none;
		}

		.x-tree-root-ct li {
			clear: left;
			margin-bottom: 0;
		}
		';

		$currentSubScript = ($this->currentSubScript ?
			'top.currentSubScript = unescape("' . rawurlencode($this->currentSubScript) . '");' :
			'');
		$doHighlight = ($this->doHighlight ?
			'hilight_row("row" + top.fsMod.recentIds["txcommerceM1"], highLightID);' :
			'');
		$formStyle = (!$GLOBALS['CLIENT']['FORMSTYLE'] ?
			'' :
			'if (linkObj) { linkObj.blur(); }');

		// Setting JavaScript for menu.
		$this->doc->JScode = $this->doc->wrapScriptTags(
			$currentSubScript . '

			function jumpTo(id, linkObj, highLightID, script) {
				var theUrl;

				if (script) {
					theUrl = top.TS.PATH_typo3 + script;
				} else {
					theUrl = top.TS.PATH_typo3 + top.currentSubScript;
				}

				theUrl = theUrl + id;

				if (top.condensedMode) {
					top.content.document.location = theUrl;
				} else {
					parent.list_frame.document.location = theUrl;
				}
				' . $doHighlight . '
				' . $formStyle . '
				return false;
			}

			// Call this function, refresh_nav(), from another script in the backend
			// if you want to refresh the navigation frame (eg. after having changed
			// a page title or moved pages etc.)
			// See BackendUtility::getSetUpdateSignal()
			function refresh_nav() {
				window.setTimeout(\'Tree.refresh();\', 0);
			}
		'
		);

		$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
		$this->doc->loadJavascriptLib('js/tree.js');
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			Tree.ajaxID = "Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper::ajaxExpandCollapse' .
			($bare ? 'WithoutProduct' : '') . '";
		');

		// Adding javascript code for AJAX (prototype), drag&drop and the
		// pagetree as well as the click menu code
		$this->doc->getContextMenuCode();

		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	/**
	 * Main method
	 *
	 * @return void
	 */
	public function main() {
		$language = $this->getLanguageService();

		// Check if commerce needs to be updated.
		if ($this->isUpdateNecessary()) {
			$tree = $language->getLL('ext.update');
		} else {
			// Get the browseable Tree
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
		$this->content = $this->doc->startPage(
			$language->sl('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:mod_category.navigation_title')
		);
		$this->content .= $this->doc->moduleBody('', $docHeaderButtons, $markers, $subparts);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print content
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the
	 * form or otherwise perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'refresh' => '',
		);

		// Refresh
		$buttons['refresh'] = '<a href="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '">' .
			\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';

		// CSH
		$buttons['csh'] = str_replace(
			'typo3-csh-inline',
			'typo3-csh-inline show-right',
			BackendUtility::cshItem('xMOD_csh_commercebe', 'categorytree', $this->doc->backPath)
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
		$updater = GeneralUtility::makeInstance('Tx_Commerce_Utility_UpdateUtility');

		return $updater->access();
	}

	/**
	 * Makes the AJAX call to expand or collapse the categorytree.
	 * Called by typo3/ajax.php
	 *
	 * @param array $params : additional parameters (not used here)
	 * @param TYPO3AJAX &$ajaxObj : reference of the TYPO3AJAX object of this request
	 * @return void
	 */
	public function ajaxExpandCollapse($params, &$ajaxObj) {
		$parameter = $this->getParameter();

		// Get the Category Tree
		$this->init();
		$tree = $this->categoryTree->getBrowseableAjaxTree($parameter);

		$ajaxObj->addContent('tree', $tree);
	}

	/**
	 * Makes the AJAX call to expand or collapse the categorytree.
	 * Called by typo3/ajax.php
	 *
	 * @param array $params additional parameters (not used here)
	 * @param TYPO3AJAX &$ajaxObj reference of the TYPO3AJAX object of this request
	 * @return void
	 */
	public function ajaxExpandCollapseWithoutProduct($params, &$ajaxObj) {
		$parameter = $this->getParameter();

		// Get the category tree without the products and the articles
		$this->init(TRUE);
		$tree = $this->categoryTree->getBrowseableAjaxTree($parameter);

		$ajaxObj->addContent('tree', $tree);
	}

	/**
	 * @return array
	 */
	protected function getParameter() {
		$parameter = GeneralUtility::_GP('PM');
		// IE takes anchor as parameter
		if (($parameterPosition = strpos($parameter, '#')) !== FALSE) {
			$parameter = substr($parameter, 0, $parameterPosition);
		}
		return explode('_', $parameter);
	}
}

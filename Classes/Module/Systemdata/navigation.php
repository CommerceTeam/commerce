<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2005 - 2012 Ingo Schmitt <is@marketing-factory.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

unset($MCONF);
require_once('conf.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xml');

	// This checks permissions and exits if the users has no permission for entry.
/** @noinspection PhpUndefinedVariableInspection */
$BE_USER->modAccess($MCONF, 1);

/**
 * Main script class for the systemData navigation frame
 */
class Tx_Commerce_Module_Systemdata_Navigation extends t3lib_SCbase {
	/**
	 * @var template
	 */
	public $doc;

	/**
	 * @var language
	 */
	protected $language;

	/**
	 * @return void
	 */
	public function init() {
		$this->language = & $GLOBALS['LANG'];

		$this->id = reset(tx_commerce_folder_db::initFolders('Commerce', 'commerce'));
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
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_systemdata_navframe.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set navframeTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_systemdata_navframe.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_systemdata_navframe.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_access_navframe.html';
			$this->doc->moduleTemplate = t3lib_div::getURL(PATH_site . $templateFile);
		}

			// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpTo(func, linkObj) {
				var theUrl = top.TS.PATH_typo3 + top.currentSubScript+"?SET[function]=" + func;

				if (top.condensedMode)	{
					top.content.document.location=theUrl;
				} else {
					parent.list_frame.document.location=theUrl;
				}

				' . (!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}') . '
				return false;
			}
		');

		$this->doc->postCode = $this->doc->wrapScriptTags('
			script_ended = 1;
			if (top.fsMod) {
				top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
			}
		');

		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	/**
	 * @return void
	 */
	public function main() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$markers = array(
			'ATTRIBUTES_TITLE' => $this->language->getLL('title_attributes'),
			'ATTRIBUTES_DESCRIPTION' => $this->language->getLL('desc_attributes'),

			'MANUFACTURER_TITLE' => $this->language->getLL('title_manufacturer'),
			'MANUFACTURER_DESCRIPTION' => $this->language->getLL('desc_manufacturer'),

			'SUPPLIER_TITLE' => $this->language->getLL('title_supplier'),
			'SUPPLIER_DESCRIPTION' => $this->language->getLL('desc_supplier'),
		);

			// put it all together
		$this->content = $this->doc->startPage($language->sl('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:mod_category.navigation_title'));
		$this->content .= $this->doc->moduleBody(array(), array(), $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_systemdata/class.tx_commerce_category_navframe.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_systemdata/class.tx_commerce_category_navframe.php']);
}

if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	/** @var $SOBE Tx_Commerce_Module_Systemdata_Navigation */
	$SOBE = t3lib_div::makeInstance('Tx_Commerce_Module_Systemdata_Navigation');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

?>
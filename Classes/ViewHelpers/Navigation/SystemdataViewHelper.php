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

/**
 * Main script class for the systemData navigation frame
 */
class Tx_Commerce_ViewHelpers_Navigation_SystemdataViewHelper extends t3lib_SCbase {
	/**
	 * @var template
	 */
	public $doc;

	/**
	 * @var language
	 */
	protected $language;

	/**
	 * @var boolean
	 */
	protected $hasFilterBox = FALSE;

	/**
	 * @return void
	 */
	public function init() {
		$this->language = & $GLOBALS['LANG'];

		$this->id = reset(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Commerce', 'commerce'));
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
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_systemdata_navigation.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set navframeTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_systemdata_navigation.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_systemdata_navigation.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_systemdata_navigation.html';
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
				top.fsMod.recentIds["web"] = ' . (int) $this->id . ';
			}
		');

		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	/**
	 * @return void
	 */
	public function main() {
		$docHeaderButtons = $this->getButtons();

		$markers = array(
			'ATTRIBUTES_TITLE' => $this->language->getLL('title_attributes'),
			'ATTRIBUTES_DESCRIPTION' => $this->language->getLL('desc_attributes'),

			'MANUFACTURER_TITLE' => $this->language->getLL('title_manufacturer'),
			'MANUFACTURER_DESCRIPTION' => $this->language->getLL('desc_manufacturer'),

			'SUPPLIER_TITLE' => $this->language->getLL('title_supplier'),
			'SUPPLIER_DESCRIPTION' => $this->language->getLL('desc_supplier'),
		);

		$subparts = array();
		if (!$this->hasFilterBox) {
			$subparts['###SECOND_ROW###'] = '';
		}

			// put it all together
		$this->content = $this->doc->startPage($this->language->sl('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:mod_category.navigation_title'));
		$this->content .= $this->doc->moduleBody('', $docHeaderButtons, $markers, $subparts);
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
			t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'filetree', $this->doc->backPath)
		);

		return $buttons;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/Navigation/SystemdataViewHelper.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/Navigation/SystemdataViewHelper.php']);
}

?>
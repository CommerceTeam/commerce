<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2005 - 2012 Ingo Schmitt <is@marketing-factory.de>
 * (c) 2012 - 2013 Sebastian Fischer <typo3@marketing-factory.de>
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
require_once($BACK_PATH . 'init.php');
require_once($BACK_PATH . 'template.php');

$GLOBALS['LANG']->includeLLFile('EXT:commerce/mod_systemdata/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
	// This checks permissions and exits if the users has no permission for entry.
$GLOBALS['BE_USER']->modAccess($MCONF, 1);

/**
 * Main script class for the systemData navigation frame
 *
 * @author Thomas Hempel <thomas@work.de>
 * @author Sebastian Fischer <typo3@marketing-factory.de> complete rewrite
 * @package TYPO3
 * @subpackage commerce
 */
class Tx_Commerce_SystemData_NavFrame extends t3lib_SCbase {
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
	 * @return void
	 */
	public function main() {
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('../typo3conf/ext/commerce/mod_systemdata/templates/mod_systemdata_navframe.html');
		$this->doc->docType = 'xhtml_trans';

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set navframeTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_systemdata_navframe.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_systemdata_navframe.html']
			));
			$templateFile = t3lib_extMgm::siteRelPath('commerce') . 'mod_systemdata/templates/mod_systemdata_navframe.html';
			$this->doc->moduleTemplate = @file_get_contents(PATH_site . $templateFile);
		}

			// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpTo(func,linkObj)	{	//
				var theUrl = top.TS.PATH_typo3+top.currentSubScript+"?SET[function]="+func;

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

		$markers = array(
			'ATTRIBUTES_TITLE' => $this->language->getLL('title_attributes'),
			'ATTRIBUTES_DESCRIPTION' => $this->language->getLL('desc_attributes'),

			'MANUFACTURER_TITLE' => $this->language->getLL('title_manufacturer'),
			'MANUFACTURER_DESCRIPTION' => $this->language->getLL('desc_manufacturer'),

			'SUPPLIER_TITLE' => $this->language->getLL('title_supplier'),
			'SUPPLIER_DESCRIPTION' => $this->language->getLL('desc_supplier'),
		);

		// put it all together
		$this->content = $this->doc->startPage($this->language->getLL('title'));
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
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_systemdata/class.tx_commerce_category_navframe.php']);
}


if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	/** @var $SOBE Tx_Commerce_SystemData_NavFrame */
	$SOBE = t3lib_div::makeInstance('Tx_Commerce_SystemData_NavFrame');
	$SOBE->init();

	foreach ($SOBE->include_once as $includeFile) {
		include_once($includeFile);
	}

	$SOBE->main();
	$SOBE->printContent();
}

?>
<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Ingo Schmitt <is@marketing-factory.de>
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
 * This class replaces the version preview of version index.php
 */
class ux_tx_version_cm1 extends tx_version_cm1 {
	/**
	 * document template object
	 *
	 * @var mediumDoc
	 */
	public $doc;

	/**
	 * Administrative links for a table / record
	 *
	 * @param string $table Table name
	 * @param array $row Record for which administrative links are generated.
	 * @return string HTML link tags.
	 */
	public function adminLinks($table, $row) {
		if ($table !== 'tx_commerce_products') {
			return parent::adminLinks($table, $row);
		} else {
			/** @var language $language */
			$language = $GLOBALS['LANG'];

				// Edit link:
			$adminLink = '<a href="#" onclick="' .
				htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[' . $table . '][' . $row['uid'] . ']=edit', $this->doc->backPath)) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $language->sL('LLL:EXT:lang/locallang_core.xml:cm.edit', TRUE))) . '</a>';

				// Delete link:
			$adminLink .= '<a href="' .
				htmlspecialchars($this->doc->issueCommand('&cmd[' . $table . '][' . $row['uid'] . '][delete]=1')) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:cm.delete', TRUE))) . '</a>';

			if ($row['pid'] == -1) {
					// get page TSconfig
				$pagesTSC = t3lib_BEfunc::getPagesTSconfig($GLOBALS['_POST']['popViewId']);
				if ($pagesTSC['tx_commerce.']['singlePid']) {
					$previewPageID = $pagesTSC['tx_commerce.']['singlePid'];
				} else {
					$previewPageID = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['previewPageID'];
				}

				$sysLanguageUid = (int) $row['sys_language_uid'];

				/** @var $product Tx_Commerce_Domain_Model_Product */
				$product = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Product');
				$product->init($row['t3ver_oid'], $sysLanguageUid);
				$product->loadData();

				$getVars = ($sysLanguageUid > 0 ? '&L=' . $sysLanguageUid : '') .
					'&ADMCMD_vPrev&no_cache=1&tx_commerce[showUid]=' . $row['t3ver_oid'] .
					'&tx_commerce[catUid]=' . current($product->getMasterparentCategory());

				$adminLink .= '<a href="#" onclick="' .
					htmlspecialchars(t3lib_BEfunc::viewOnClick($previewPageID, $this->doc->backPath, t3lib_BEfunc::BEgetRootLine($row['_REAL_PID']), '', '', $getVars)) .
					'">' . t3lib_iconWorks::getSpriteIcon('actions-document-view') . '</a>';
			}

			return $adminLink;
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/ux_versinondex.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['commerce/ux_versinondex.php']);
}

?>
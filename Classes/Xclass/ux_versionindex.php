<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
class ux_tx_version_cm1 extends \TYPO3\CMS\Version\Controller\VersionModuleController {
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
			/** @var \TYPO3\CMS\Lang\LanguageService $language */
			$language = $GLOBALS['LANG'];

			// Edit link:
			$adminLink = '<a href="#" onclick="' .
				htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(
					'&edit[' . $table . '][' . $row['uid'] . ']=edit', $this->doc->backPath)
				) . '">' .
				\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(
					'actions-document-open',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.xml:cm.edit', TRUE))
				) . '</a>';

			// Delete link:
			$adminLink .= '<a href="' .
				htmlspecialchars($this->doc->issueCommand('&cmd[' . $table . '][' . $row['uid'] . '][delete]=1')) . '">' .
				\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(
					'actions-edit-delete',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:cm.delete', TRUE))
				) . '</a>';

			if ($row['pid'] == -1) {
				// get page TSconfig
				$pagesTyposcriptConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($GLOBALS['_POST']['popViewId']);
				if ($pagesTyposcriptConfig['tx_commerce.']['singlePid']) {
					$previewPageId = $pagesTyposcriptConfig['tx_commerce.']['singlePid'];
				} else {
					$previewPageId = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['previewPageID'];
				}

				$sysLanguageUid = (int) $row['sys_language_uid'];

				/** @var $product Tx_Commerce_Domain_Model_Product */
				$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'Tx_Commerce_Domain_Model_Product',
					$row['t3ver_oid'],
					$sysLanguageUid
				);
				$product->loadData();

				$getVars = ($sysLanguageUid > 0 ? '&L=' . $sysLanguageUid : '') .
					'&ADMCMD_vPrev[' . rawurlencode($table . ':' . $row['t3ver_oid']) . ']=' . $row['uid'] .
					'&no_cache=1&tx_commerce_pi1[showUid]=' . $product->getUid() .
					'&tx_commerce_pi1[catUid]=' . current($product->getMasterparentCategory());

				$adminLink .= '<a href="#" onclick="' .
					htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick(
						$previewPageId,
						$this->doc->backPath,
						\TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($row['_REAL_PID']
					), '', '', $getVars)) .
					'">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . '</a>';
			}

			return $adminLink;
		}
	}
}

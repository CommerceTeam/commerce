<?php
namespace CommerceTeam\Commerce\Xclass;
/*
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

use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class replaces the version preview of version index.php
 *
 * Class ux_tx_version_cm1
 *
 * @author 2008-2013 Ingo Schmitt <is@marketing-factory.de>
 */
class VersionModuleController extends \TYPO3\CMS\Version\Controller\VersionModuleController {
	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Administrative links for a table / record
	 *
	 * @param string $table Table name
	 * @param array $row Record for which administrative links are generated.
	 *
	 * @return string HTML link tags.
	 */
	public function adminLinks($table, array $row) {
		if ($table !== 'tx_commerce_products') {
			return parent::adminLinks($table, $row);
		} else {
			$language = $this->getLanguageService();

			// Edit link:
			$adminLink = '<a href="#" onclick="' .
				htmlspecialchars(BackendUtility::editOnClick('&edit[' . $table . '][' . $row['uid'] . ']=edit', $this->getBackPath())) .
				'">' .
				IconUtility::getSpriteIcon(
					'actions-document-open',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.xml:cm.edit', TRUE))
				) . '</a>';

			// Delete link:
			$adminLink .= '<a href="' .
				htmlspecialchars($this->doc->issueCommand('&cmd[' . $table . '][' . $row['uid'] . '][delete]=1')) . '">' .
				IconUtility::getSpriteIcon(
					'actions-edit-delete',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:cm.delete', TRUE))
				) . '</a>';

			if ($row['pid'] == -1) {
				// get page TSconfig
				$pagesTyposcriptConfig = BackendUtility::getPagesTSconfig(GeneralUtility::_POST('popViewId'));
				if ($pagesTyposcriptConfig['tx_commerce.']['singlePid']) {
					$previewPageId = $pagesTyposcriptConfig['tx_commerce.']['singlePid'];
				} else {
					$previewPageId = SettingsFactory::getInstance()->getExtConf('previewPageID');
				}

				$sysLanguageUid = (int) $row['sys_language_uid'];

				/**
				 * Product
				 *
				 * @var $product \CommerceTeam\Commerce\Domain\Model\Product
				 */
				$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'CommerceTeam\\Commerce\\Domain\\Model\\Product',
					$row['t3ver_oid'],
					$sysLanguageUid
				);
				$product->loadData();

				$getVars = ($sysLanguageUid > 0 ? '&L=' . $sysLanguageUid : '') .
					'&ADMCMD_vPrev[' . rawurlencode($table . ':' . $row['t3ver_oid']) . ']=' . $row['uid'] .
					'&no_cache=1&tx_commerce_pi1[showUid]=' . $product->getUid() .
					'&tx_commerce_pi1[catUid]=' . current($product->getMasterparentCategory());

				$adminLink .= '<a href="#" onclick="' .
					htmlspecialchars(BackendUtility::viewOnClick(
						$previewPageId,
						$this->getBackPath(),
						BackendUtility::BEgetRootLine($row['_REAL_PID']), '', '', $getVars)
					) .
					'">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';
			}

			return $adminLink;
		}
	}


	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Get back path
	 *
	 * @return string
	 */
	protected function getBackPath() {
		return $GLOBALS['BACK_PATH'];
	}
}

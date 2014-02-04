<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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

unset($MCONF);
require_once('conf.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'template.php');

/**
 * Main script class for the tree edit navigation frame
 */
class Tx_Commerce_Module_Orders_Navigation {
	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var Tx_Commerce_Tree_OrderTree
	 */
	protected $pagetree;

	/**
	 * @var template
	 */
	protected $doc;

	/**
	 * Temporary mount point (record), if any
	 *
	 * @var integer
	 */
	protected $active_tempMountPoint = 0;

	/**
	 * @var string
	 */
	protected $currentSubScript;

	/**
	 * @var string
	 */
	protected $cMR;

	/**
	 * If not '' (blank) then it will clear (0) or set (>0) Temporary DB mount.
	 *
	 * @var string
	 */
	protected $setTempDBmount;

	/**
	 * @var boolean
	 */
	protected $doHighlight;

	/**
	 * Initialiation of the class
	 *
	 * @todo Check with User Permissions
	 * @return void
	 */
	public function init() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Setting GPvars:
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		$this->cMR = t3lib_div::_GP('cMR');
		$this->setTempDBmount = t3lib_div::_GP('setTempDBmount');

			// Generate Folder if necessary
		Tx_Commerce_Utility_FolderUtility::init_folders();

			// Create page tree object:
		$this->pagetree = t3lib_div::makeInstance('Tx_Commerce_Tree_OrderTree');
		$this->pagetree->ext_IconMode = $backendUser->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
		$this->pagetree->ext_showPageId = $backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$this->pagetree->thisScript = $GLOBALS['BACK_PATH'] . PATH_TXCOMMERCE_REL . 'Classes/Module/Orders/navigation.php';
		$this->pagetree->addField('alias');
		$this->pagetree->addField('shortcut');
		$this->pagetree->addField('shortcut_mode');
		$this->pagetree->addField('mount_pid');
		$this->pagetree->addField('mount_pid_ol');
		$this->pagetree->addField('nav_hide');
		$this->pagetree->addField('url');

			// Temporary DB mounts:
		$this->pagetree->MOUNTS = array_unique(tx_commerce_folder_db::initFolders('Orders', 'Commerce', 0, 'Commerce'));
		$this->initializeTemporaryDBmount();

			// Setting highlight mode:
		$this->doHighlight = !$backendUser->getTSConfigVal('options.pageTree.disableTitleHighlight');

			// Create template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType = 'xhtml_trans';

			// Setting backPath
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

			// Setting JavaScript for menu.
		$this->doc->JScode = $this->doc->wrapScriptTags(
			($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '

				// Function, loading the list frame from navigation tree:
			function jumpTo(id, linkObj, highLightID) {
				var theUrl = top.TS.PATH_typo3 + top.currentSubScript + "?id=" + id;

				if (top.condensedMode) {
					top.content.document.location = theUrl;
				} else {
					parent.list_frame.document.location = theUrl;
				}

				' . ($this->doHighlight ? 'hilight_row("txcommerceM1",highLightID);' : '') . '

				' . (!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) { linkObj.blur(); }') . '
				return false;
			}

				// Call this function, refresh_nav(), from another script in the backend if you want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
				// See t3lib_BEfunc::getSetUpdateSignal()
			function refresh_nav() {
				window.setTimeout("_refresh_nav();", 0);
			}
			function _refresh_nav() {
				document.location="' . $this->pagetree->thisScript . '?unique=' . time() . '";
			}

				// Highlighting rows in the page tree:
			function hilight_row(frameSetModule, highLightID) {
					// Remove old:
				theObj = document.getElementById(top.fsMod.navFrameHighlightedID[frameSetModule] + "_0");

				if (theObj) {
					theObj.style.backgroundColor = "";
				}

					// Set new:
				top.fsMod.navFrameHighlightedID[frameSetModule] = highLightID;
				theObj = document.getElementById(highLightID + "_0");

				if (theObj) {
					theObj.style.backgroundColor = "' . t3lib_div::modifyHTMLColorAll($this->doc->bgColor, -20) . '";
				}
			}

			' . ($this->cMR ? "jumpTo(top.fsMod.recentIds['web'], '');" : '') . ';
		');

			// Click menu code is added:
		$CMparts = $this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode .= $CMparts[0];
		$this->doc->postCode .= $CMparts[2];
	}

	/**
	 * Main function, rendering the browsable page tree
	 *
	 * @return void
	 */
	public function main() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Produce browse-tree:
		$tree = $this->pagetree->getBrowsableTree();

			// Start page:
		$this->content = '';
		$this->content .= $this->doc->startPage('Orders');

			// Outputting Temporary DB mount notice:
		if ($this->active_tempMountPoint) {
			$this->content .= '
				<div class="bgColor4 c-notice">
					<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/icon_note.gif', 'width="18" height="16"') .
					' align="top" alt="" /><a href="' . htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => 0))) .
					'">' . $language->sl('LLL:EXT:lang/locallang_core.php:labels.temporaryDBmount', 1) . '</a><br/>' .
					$language->sl('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span title="' .
					htmlspecialchars($this->active_tempMountPoint['_thePathFull']) . '">' .
					htmlspecialchars(t3lib_div::fixed_lgd_cs($this->active_tempMountPoint['_thePath'], -50)) . '</span>
				</div>
			';
		}

			// Outputting page tree:
		$this->content .= $tree;

			// Outputting refresh-link
		$refreshUrl = t3lib_div::getIndpEnv('REQUEST_URI');
		$this->content .= '
			<p class="c-refresh">
				<a href="' . htmlspecialchars($refreshUrl) . '"><img' .
				t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/refresh_n.gif', 'width="14" height="14"') .
				' title="' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.refresh', 1) . '" alt="" /></a><a href="' .
				htmlspecialchars($refreshUrl) . '">' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.refresh', 1) . '</a>
			</p>
			<br />';

			// CSH icon:
		$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'pagetree', $GLOBALS['BACK_PATH']);

			// Adding highlight - JavaScript
		if ($this->doHighlight) {
			$this->content .= $this->doc->wrapScriptTags('
				hilight_row("", top.fsMod.navFrameHighlightedID["web"]);
			');
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**********************************
	 * Temporary DB mounts
	 **********************************/

	/**
	 * Getting temporary DB mount
	 *
	 * @return void
	 */
	protected function initializeTemporaryDBmount() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Set/Cancel Temporary DB Mount:
		if (strlen($this->setTempDBmount)) {
			$set = t3lib_div::intInRange($this->setTempDBmount, 0);
				// Setting...:
			if ($set > 0 && $backendUser->isInWebMount($set)) {
				$this->settingTemporaryMountPoint($set);
				// Clear:
			} else {
				$this->settingTemporaryMountPoint(0);
			}
		}

			// Getting temporary mount point ID:
		$temporaryMountPoint = (int) $backendUser->getSessionData('pageTree_temporaryMountPoint_orders');

			// If mount point ID existed and is within users real mount points, then set it temporarily:
		if ($temporaryMountPoint > 0 && $backendUser->isInWebMount($temporaryMountPoint)) {
			$this->pagetree->MOUNTS = array($temporaryMountPoint);
			$this->active_tempMountPoint = t3lib_BEfunc::readPageAccess($temporaryMountPoint, $backendUser->getPagePermsClause(1));
		}
	}

	/**
	 * @param integer $pageId
	 * @return void
	 */
	protected function settingTemporaryMountPoint($pageId) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Setting temporary mount point ID:
		$backendUser->setAndSaveSessionData('pageTree_temporaryMountPoint_orders', (int) $pageId);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_orders/class.tx_commerce_order_navframe.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_orders/class.tx_commerce_order_navframe.php']);
}

/** @var Tx_Commerce_Module_Orders_Navigation $SOBE */
$SOBE = t3lib_div::makeInstance('Tx_Commerce_Module_Orders_Navigation');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
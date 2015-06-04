<?php
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main script class for the tree edit navigation frame
 *
 * Class Tx_Commerce_ViewHelpers_Navigation_OrdersViewHelper
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_ViewHelpers_Navigation_OrdersViewHelper extends \TYPO3\CMS\Backend\Module\BaseScriptClass {
	/**
	 * @var Tx_Commerce_Tree_OrderTree
	 */
	protected $pagetree;

	/**
	 * Temporary mount point (record), if any
	 *
	 * @var integer
	 */
	protected $activeTemporaryMountPoint = 0;

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
	protected $setTemporaryDatabaseMount;

	/**
	 * @var boolean
	 */
	protected $doHighlight;

	/**
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Initialiation of the class
	 *
	 * @todo Check with User Permissions
	 * @return void
	 */
	public function init() {
		$backendUser = $this->getBackendUser();

			// Setting GPvars:
		$this->currentSubScript = GeneralUtility::_GP('currentSubScript');
		$this->cMR = GeneralUtility::_GP('cMR');
		$this->setTemporaryDatabaseMount = GeneralUtility::_GP('setTempDBmount');

			// Generate Folder if necessary
		Tx_Commerce_Utility_FolderUtility::initFolders();

			// Create page tree object:
		$this->pagetree = GeneralUtility::makeInstance('Tx_Commerce_Tree_OrderTree');
		$this->pagetree->ext_IconMode = $backendUser->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
		$this->pagetree->ext_showPageId = $backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$this->pagetree->thisScript = 'Classes/Module/Orders/navigation.php';
		$this->pagetree->addField('alias');
		$this->pagetree->addField('shortcut');
		$this->pagetree->addField('shortcut_mode');
		$this->pagetree->addField('mount_pid');
		$this->pagetree->addField('mount_pid_ol');
		$this->pagetree->addField('nav_hide');
		$this->pagetree->addField('url');

			// Temporary DB mounts:
		$this->pagetree->MOUNTS = array_unique(
			Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce')
		);
		$this->initializeTemporaryDatabaseMount();

			// Setting highlight mode:
		$this->doHighlight = !$backendUser->getTSConfigVal('options.pageTree.disableTitleHighlight');
	}

	/**
	 * Initializes the Page
	 *
	 * @return void
	 */
	public function initPage() {
		/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate $doc */
		$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc = $doc;
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:commerce/Resources/Private/Backend/mod_navigation.html');
		$this->doc->showFlashMessages = FALSE;

		$subScript = $this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '';
		$highlight = $this->doHighlight ? 'hilight_row("txcommerceM1",highLightID);' : '';
		$formstyle = !$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) { linkObj.blur(); }';

			// Setting JavaScript for menu.
		$this->doc->JScode = $this->doc->wrapScriptTags(
			$subScript . '

				// Function, loading the list frame from navigation tree:
			function jumpTo(id, linkObj, highLightID) {
				var theUrl = top.TS.PATH_typo3 + top.currentSubScript + "&id=" + id;

				if (top.condensedMode) {
					top.content.document.location = theUrl;
				} else {
					parent.list_frame.document.location = theUrl;
				}

				' . $highlight . '
				' . $formstyle . '
				return false;
			}

			// Call this function, refresh_nav(), from another script in the backend if you
			// want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
			// See BackendUtility::getSetUpdateSignal()
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
					theObj.style.backgroundColor = "' . GeneralUtility::modifyHTMLColorAll($this->doc->bgColor, -20) . '";
				}
			}

			' . ($this->cMR ? "jumpTo(top.fsMod.recentIds['web'], '');" : '') . ';
		');

		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	/**
	 * Main function, rendering the browsable page tree
	 *
	 * @return void
	 */
	public function main() {
		// Produce browse-tree:
		$tree = $this->pagetree->getBrowsableTree();

		$docHeaderButtons = $this->getButtons();

		$markers = array(
			'IMG_RESET' => '',
			'WORKSPACEINFO' => '',
			'CONTENT' => $tree
		);

			// Build the <body> for the module
		$this->content = $this->doc->startPage(
			$this->getLanguageService()->sl('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:mod_orders.navigation_title')
		);
		$this->content .= $this->doc->moduleBody('', $docHeaderButtons, $markers);
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
	 * Create the panel of buttons for submitting the form
	 * or otherwise perform operations.
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
			\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-refresh') .
		'</a>';

		// CSH
		$buttons['csh'] = str_replace(
			'typo3-csh-inline',
			'typo3-csh-inline show-right',
			BackendUtility::cshItem('xMOD_csh_commercebe', 'orderstree', $this->doc->backPath)
		);

		return $buttons;
	}

	/**
	 * Getting temporary DB mount
	 *
	 * @return void
	 */
	protected function initializeTemporaryDatabaseMount() {
		$backendUser = $this->getBackendUser();

			// Set/Cancel Temporary DB Mount:
		if (strlen($this->setTemporaryDatabaseMount)) {
			$set = max($this->setTemporaryDatabaseMount, 0);
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

		// If mount point ID existed and is within users
		// real mount points, then set it temporarily:
		if ($temporaryMountPoint > 0 && $backendUser->isInWebMount($temporaryMountPoint)) {
			$this->pagetree->MOUNTS = array($temporaryMountPoint);
			$this->activeTemporaryMountPoint = BackendUtility::readPageAccess($temporaryMountPoint, $backendUser->getPagePermsClause(1));
		}
	}

	/**
	 * @param integer $pageId
	 * @return void
	 */
	protected function settingTemporaryMountPoint($pageId) {
		// Setting temporary mount point ID:
		$this->getBackendUser()->setAndSaveSessionData('pageTree_temporaryMountPoint_orders', (int) $pageId);
	}


	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}

<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2012 Erik Frister <typo3@marketing-factory.de>
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
 * Extended Functionality for the Clickmenu when commerce-tables are hit
 * Basically does the same as the alt_clickmenu.php, only that for Categories the output needs to be overridden depending on the rights
 */
class Tx_Commerce_Utility_ClickmenuUtility extends clickMenu {
	/**
	 * @var string
	 */
	public $backPath = '../../../../../../typo3/';

	/**
	 * @var array
	 */
	public $rec;

	/**
	 * @var clickMenu
	 */
	protected $clickMenu;

	/**
	 * @var array
	 */
	protected $commerceTables = array(
		'tx_commerce_articles',
		'tx_commerce_categories',
		'tx_commerce_products'
	);

	/**
	 * Changes the clickmenu Items for the Commerce Records
	 *
	 * @param clickMenu $clickMenu clickenu object
	 * @param array $menuItems current menu Items
	 * @param string $table db table
	 * @param integer $uid uid of the record
	 * @return array Menu Items Array
	 */
	public function main(&$clickMenu, $menuItems, $table, $uid) {
			// Only modify the menu Items if we have the correct table
		if (!in_array($table, $this->commerceTables)) {
			return $menuItems;
		}

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Check for List allow
		if (!$backendUser->check('tables_select', $table)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('Clickmenu not allowed for user.', COMMERCE_EXTKEY, 1);
			}
			return '';
		}

			// Configure the parent clickmenu
		$this->clickMenu = $clickMenu;
		$this->clipObj = $this->clickMenu->clipObj;
		$this->clickMenu->backPath = $this->backPath;
			// @todo do not allow the entry 'history' in the clickmenu
		$this->clickMenu->disabledItems[]  = '';

			// Get record:
		$this->rec = t3lib_BEfunc::getRecordWSOL($table, $uid);

			// Initialize the rights-variables
		$root = 0;
		$copyType = 'after';
		$delete = FALSE;
		$edit = FALSE;
		$new = FALSE;
		$editLock = FALSE;
		$DBmount = FALSE;
		$copy = FALSE;
		$paste = FALSE;
		$version = FALSE;
		$review = FALSE;

			// used to hide cut,copy icons for l10n-records
		$l10nOverlay = FALSE;
			// should only be performed for overlay-records within the same table
		if (t3lib_BEfunc::isTableLocalizable($table) && !isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])) {
			$l10nOverlay = intval($this->rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]) != 0;
		}

			// get category uid depending on where the clickmenu is called
		switch($table) {
			case 'tx_commerce_categories':
					// check if current item is root
				$root = !strcmp($uid, '0') ? 1 : 0;

					// find uid of category or translation parent category
				$categoryToCheckRightsOn = $uid;
				if ($this->rec['sys_language_uid']) {
					$categoryToCheckRightsOn = $this->rec['l18n_parent'];
				}

					// get the rights for this category
				$delete = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(array($categoryToCheckRightsOn), array('delete'));
				$edit = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(array($categoryToCheckRightsOn), array('edit'));
				$new = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(array($categoryToCheckRightsOn), array('new'));

					// check if we may paste into this category
				if (count($this->clickMenu->clipObj->elFromTable('tx_commerce_categories'))) {
						// if category is in clipboard, check new-right
					$paste = $new;

						// make sure we dont offer pasting one category into itself. that would lead to endless recursion
					$clipRecord = $this->clickMenu->clipObj->getSelectedRecord();
					$paste = ($uid == $clipRecord['uid']) ? FALSE : $paste;

				} elseif (count($this->clickMenu->clipObj->elFromTable('tx_commerce_products'))) {
						// if product is in clipboard, check editcontent right
					$paste = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(array($uid), array('editcontent'));
				}

				$editLock = ($backendUser->isAdmin()) ? FALSE : $this->rec['editlock'];

					// check if the current item is a db mount
				/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
				$mounts = t3lib_div::makeInstance('Tx_Commerce_Tree_CategoryMounts');
				$mounts->init($backendUser->user['uid']);
				$DBmount = (in_array($uid, $mounts->getMountData()));

					// if the category has no parent categories treat as root
				/** @var Tx_Commerce_Domain_Model_Category $category */
				$category = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Category');
				$category->init($categoryToCheckRightsOn);
				$DBmount = count($category->getParentCategories()) ? $DBmount : TRUE;

				$copy = ($this->rec['sys_language_uid'] == 0);
				$copyType = 'into';

					// pasting or new into translations is not allowed
				if ($this->rec['sys_language_uid']) {
					$new = FALSE;
					$paste = FALSE;
				}
			break;

			case 'tx_commerce_products':
					// get all parent categories
				/** @var Tx_Commerce_Domain_Model_Product $product */
				$product = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Product');
				$product->init($uid);

				$parentCategories = $product->getParentCategories();

					// store the rights in the flags
				$delete = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'));
				$edit = $delete;
				$new = $delete;
				$copy = ($this->rec['t3ver_state'] == 0 && $this->rec['sys_language_uid'] == 0);
				$paste = (($this->rec['t3ver_state'] == 0) && $delete);

					// make sure we do not allowed to overwrite a product with itself
				if (count($this->clickMenu->clipObj->elFromTable('tx_commerce_products'))) {
					$clipRecord = $this->clickMenu->clipObj->getSelectedRecord();
					$paste = ($uid == $clipRecord['uid']) ? FALSE : $paste;
				}

				$version = ($backendUser->check('modules', 'web_txversionM1')) && t3lib_extMgm::isLoaded('version');
				$review = ($version && ($this->rec['t3ver_oid'] != 0) && (($this->rec['t3ver_stage'] == 0) || ($this->rec['t3ver_stage'] == 1)));
			break;

			case 'tx_commerce_articles':
					// get all parent categories for the parent product
				/** @var Tx_Commerce_Domain_Model_Article $article */
				$article = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Article');
				$article->init($uid);

				$productUid = $article->getParentProductUid();

					// get the parent categories of the product
				/** @var Tx_Commerce_Domain_Model_Product $product */
				$product = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Product');
				$product->init($productUid);

				$parentCategories = $product->getParentCategories();

					// store the rights in the flags
				$delete = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'));
				$edit = $delete;
				$new = $delete;
			break;
		}

			// get the UID of the Products SysFolder
		$prodPid = Tx_Commerce_Utility_BackendUtility::getProductFolderUid();

		$menuItems = array();

			// If record found, go ahead and fill the $menuItems array which will contain data for the elements to render.
		if (is_array($this->rec)) {
				// Edit:
			if (!$root && !$editLock && $edit) {
				if (!in_array('edit', $this->clickMenu->disabledItems)) {
					$menuItems['edit'] = $this->DB_edit($table, $uid);
				}
				$this->clickMenu->editOK = 1;
			}

				// New: fix: always give the UID of the products page to create any commerce object
			if (!in_array('new', $this->clickMenu->disabledItems) && $new) {
				$menuItems['new'] = $this->DB_new($table, $prodPid);
			}

				// Info:
			if (!in_array('info', $this->clickMenu->disabledItems) && !$root) {
				$menuItems['info'] = $this->DB_info($table, $uid);
			}

			$menuItems['spacer1'] = 'spacer';

				// Cut not included
				// Copy:
			if (!in_array('copy', $this->disabledItems) && !$root && !$DBmount && !$l10nOverlay && $copy) {
				$menuItems['copy'] = $this->clickMenu->DB_copycut($table, $uid, 'copy');
			}

				// Cut:
			if (!in_array('cut', $this->disabledItems) && !$root && !$DBmount && !$l10nOverlay && $copy) {
				$menuItems['cut'] = $this->clickMenu->DB_copycut($table, $uid, 'cut');
			}

				// Paste
			$elFromAllTables = count($this->clickMenu->clipObj->elFromTable(''));
			if (!in_array('paste', $this->clickMenu->disabledItems) && $elFromAllTables) {
				$selItem = $this->clickMenu->clipObj->getSelectedRecord();
				$elInfo = array(
					t3lib_div::fixed_lgd_cs($selItem['_RECORD_TITLE'], $backendUser->uc['titleLen']),
					(
						$root ?
						$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] :
						t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table, $this->clickMenu->rec), $backendUser->uc['titleLen'])
					),
					$this->clickMenu->clipObj->currentMode()
				);

				$elFromTable = count($this->clickMenu->clipObj->elFromTable($table));
				if (!$root && !$DBmount && $elFromTable && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && 'tx_commerce_categories' == $table && $paste) {
						// paste into - for categories
					$menuItems['pasteafter'] = $this->DB_paste($table, $uid, $copyType, $elInfo);
				} elseif (!$root && $paste && !$DBmount && $elFromTable && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && 'tx_commerce_products' == $table) {
						// overwrite product with product
					$menuItems['overwrite'] =  $this->DB_overwrite($table, $uid, $elInfo);
				} elseif (!$root && $paste && !$DBmount && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && 'tx_commerce_categories' == $table && count($this->clickMenu->clipObj->elFromTable('tx_commerce_products'))) {
						// paste product into category
					$menuItems['pasteafter'] = $this->DB_paste($table, $uid, $copyType, $elInfo);
				}
			}

			$menuItems['spacer3'] = 'spacer';

				// versioning
			$elInfo = array();
			if (!in_array('versioning', $this->clickMenu->disabledItems) && $version) {
				$menuItems['versioning'] = $this->DB_versioning($table, $uid, $elInfo);
			}

				// send to review
			if (!in_array('review', $this->clickMenu->disabledItems) && $review) {
				$menuItems['review'] = $this->DB_review($table, $uid, $elInfo);
			}

				// Delete:
			$elInfo = array(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table, $this->rec), $backendUser->uc['titleLen']));

			if (!$editLock && !in_array('delete', $this->clickMenu->disabledItems) && !$root && !$DBmount && $delete) {
				$menuItems['spacer2'] = 'spacer';
				$menuItems['delete'] = $this->clickMenu->DB_delete($table, $uid, $elInfo);
			}

			if (!in_array('history', $this->clickMenu->disabledItems)) {
				$menuItems['history'] = $this->clickMenu->DB_history($table, $uid, $elInfo);
			}
		}

		return $menuItems;
	}

	/**
	 * Adding CM element for Clipboard "copy" and "cut"
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @param string $type Type: "copy" or "cut"
	 * @return array Item array, element in $menuItems
	 */
	public function DB_copycut($table, $uid, $type) {
		$isSel = FALSE;
		if ($this->clipObj->current == 'normal') {
			$isSel = $this->clipObj->isSelected($table, $uid);
		}

		$addParam = array();
		if ($this->listFrame) {
			$addParam['reloadListFrame'] = ($this->alwaysContentFrame ? 2 : 1);
		}

		return $this->linkItem(
			$this->label($type),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-' . $type . ($isSel === $type ? '-release' : ''))),
			"top.loadTopMenu('" . $this->clickMenu->clipObj->selUrlDB($table, $uid, ($type == 'copy' ? 1: 0), ($isSel == $type), $addParam) . "');return false;"
		);
	}

	/**
	 * Adding CM element for Clipboard "paste into"/"paste after"
	 * NOTICE: $table and $uid should follow the special syntax for paste, see clipboard-class :: pasteUrl();
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record. NOTICE: Special syntax!
	 * @param string $type Type: "into" or "after"
	 * @param array $elInfo Contains instructions about whether to copy or cut an element.
	 * @return array Item array, element in $menuItems
	 */
	public function DB_paste($table, $uid, $type, $elInfo) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$loc = 'top.content.list_frame';
		if ($backendUser->jsConfirmation(2)) {
			$conf = $loc . ' && confirm(' . $language->JScharCode(sprintf(
				$language->sL('LLL:EXT:lang/locallang_core.php:mess.' . ($elInfo[2] == 'copy' ? 'copy' : 'move') . '_' . $type),
				$elInfo[0],
				$elInfo[1]
			)) . ')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' .
			$this->clipObj->pasteUrl($table, $uid, 0) . '&redirect=\'+top.rawurlencode(' .
			$this->frameLocation($loc . '.document') . '.pathname+' .
			$this->frameLocation($loc . '.document') . '.search); hideCM();}';

		return $this->linkItem(
			$this->label('paste' . $type),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-paste-' . $type)),
			$editOnClick . 'return false;'
		);
	}

	/**
	 * Adding CM element for History
	 *
	 * @param string $table Table name
	 * @param integer $uid UID for the current record.
	 * @return array Item array, element in $menuItems
	 */
	public function DB_history($table, $uid) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$url = 'show_rechis.php?element=' . rawurlencode($table . ':' . $uid);
		return $this->linkItem(
			$language->makeEntities($language->getLL('CM_history')),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-history-open')),
			$this->urlRefForCM($url, 'returnUrl'),
			0
		);
	}

	/**
	 * Displays the paste option
	 *
	 * @param string $table Object
	 * @param integer $uid Object
	 * @param array $elInfo Object
	 * @return string
	 */
	public function _DB_paste($table, $uid, $elInfo) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$loc = 'top.content' . ($this->clickMenu->listFrame && !$this->clickMenu->alwaysContentFrame ? '.list_frame' : '');

		if ($backendUser->jsConfirmation(2)) {
			$conf = $loc . ' && confirm(' . $language->JScharCode(
				sprintf(
					$language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:clickmenu.pasteConfirm'),
					$elInfo[0],
					$elInfo[1]
				)
			) . ')';
		} else {
			$conf = $loc;
		}

		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' .
			$this->pasteUrl($table, $uid, 0) . '&redirect=\'+top.rawurlencode(' .
			$this->clickMenu->frameLocation($loc . '.document') .
			'); hideCM();}';

		return $this->clickMenu->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:clickmenu.paste', 1)),
			$this->clickMenu->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-paste-into')),
			$editOnClick . 'return false;'
		);
	}

	/**
	 * Displays the overwrite option
	 *
	 * @param string $table Table that is to be host of the overwrite
	 * @param integer $uid uid of the item that is to be overwritten
	 * @param array $elInfo Info Array
	 * @return string
	 */
	protected function DB_overwrite($table, $uid, $elInfo) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$loc = 'top.content' . ($this->clickMenu->listFrame && !$this->clickMenu->alwaysContentFrame ? '.list_frame' : '');

		if ($backendUser->jsConfirmation(2)) {
			$conf = $loc . ' && confirm(' . $language->JScharCode(
				sprintf(
					$language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:clickmenu.overwriteConfirm'),
					$elInfo[0],
					$elInfo[1]
				)
			) . ')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' .
			$this->overwriteUrl($table, $uid, 0) . '&redirect=\'+top.rawurlencode(' .
			$this->clickMenu->frameLocation($loc . '.document') . '); hideCM();}';

		return $this->clickMenu->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:clickmenu.overwrite', 1)),
			$this->clickMenu->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-paste-into')),
			$editOnClick . 'return false;'
		);
	}

	/**
	 * Displays the versioning option
	 *
	 * @param string $table Table that is to be host of the versioning
	 * @param integer $uid uid of the item that is to be versionized
	 * @return string
	 */
	protected function DB_versioning($table, $uid) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$url = t3lib_extMgm::extRelPath('version') . 'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . $uid;

		return $this->clickMenu->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:version/locallang.xml:title', 1)),
			$this->clickMenu->excludeIcon(t3lib_iconWorks::getSpriteIcon('status-version-no-version')),
			$this->clickMenu->urlRefForCM($url),
			1
		);
	}

	/**
	 * Displays the 'Send to review/public' option
	 *
	 * @param string $table Table that is to be host of the sending
	 * @param integer $uid uid of the item that is to be send
	 * @return string
	 */
	protected function DB_review($table, $uid) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$url = t3lib_extMgm::extRelPath('version') . 'cm1/index.php?id=' . ($table == 'pages' ? $uid : $this->clickMenu->rec['pid']) .
			'&table=' . rawurlencode($table) . '&uid=' . $uid . '&sendToReview=1';

		return $this->clickMenu->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:version/locallang.xml:title_review', 1)),
			$this->clickMenu->excludeIcon(t3lib_iconWorks::getSpriteIcon('status-version-no-version')),
			$this->clickMenu->urlRefForCM($url),
			1
		);
	}

	/**
	 * overwriteUrl of the element (database)
	 *
	 * @param string $table Tablename
	 * @param integer $uid uid of the record that should be overwritten
	 * @param integer $redirect [optional] If set, then the redirect URL will point back to the current script, but with CB reset.
	 * @return string
	 */
	protected function overwriteUrl($table, $uid, $redirect = 1) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$rU = $this->clickMenu->clipObj->backPath . PATH_TXCOMMERCE_REL . 'Classes/Utility/DataHandlerUtility.php?' .
			($redirect ? 'redirect=' . rawurlencode(t3lib_div::linkThisScript(array('CB' => ''))) : '') .
			'&vC=' . $backendUser->veriCode() .
			'&prErr=1&uPT=1' .
			'&CB[overwrite]=' . rawurlencode($table . '|' . $uid) .
			'&CB[pad]=' . $this->clickMenu->clipObj->current .
			t3lib_BEfunc::getUrlToken('tceAction');
		return $rU;
	}

	/**
	 * pasteUrl of the element (database)
	 * For the meaning of $table and $uid, please read from ->makePasteCmdArray!!!
	 *
	 * @param string $table Tablename
	 * @param integer $uid uid that should be paste into
	 * @param integer $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
	 * @return string
	 */
	protected function pasteUrl($table, $uid, $setRedirect = 1) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$rU = $this->clickMenu->clipObj->backPath . PATH_TXCOMMERCE_REL . 'Classes/Utility/DataHandlerUtility.php?' .
			($setRedirect ? 'redirect=' . rawurlencode(t3lib_div::linkThisScript(array('CB' => ''))) : '') .
			'&vC=' . $backendUser->veriCode() .
			'&prErr=1&uPT=1' .
			'&CB[paste]=' . rawurlencode($table . '|' . $uid) .
			'&CB[pad]=' . $this->clickMenu->clipObj->current .
			t3lib_BEfunc::getUrlToken('tceAction');
		return $rU;
	}
}

class_alias('Tx_Commerce_Utility_ClickmenuUtility', 'tx_commerce_clickmenu');

?>
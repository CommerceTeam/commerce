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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extended Functionality for the Clickmenu when commerce-tables are hit
 * Basically does the same as the alt_clickmenu.php, only that for Categories
 * the output needs to be overridden depending on the rights
 */
class Tx_Commerce_Utility_ClickmenuUtility extends \TYPO3\CMS\Backend\ClickMenu\ClickMenu {
	/**
	 * @var string
	 */
	protected $extKey = 'commerce';

	/**
	 * @var string
	 */
	public $backPath = '../../../../../../typo3/';

	/**
	 * @var array
	 */
	public $rec;

	/**
	 * @var \TYPO3\CMS\Backend\ClickMenu\ClickMenu
	 */
	protected $clickMenu;

	/**
	 * @var t3lib_clipboard
	 */
	protected $clipObj;

	/**
	 * @var array
	 */
	protected $additionalParameter = array();

	/**
	 * @var string
	 */
	protected $newWizardAddParams = '';

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
				GeneralUtility::devLog('Clickmenu not allowed for user.', COMMERCE_EXTKEY, 1);
			}
			return '';
		}

		// Configure the parent clickmenu
		$this->clickMenu = $clickMenu;
		$this->ajax = $this->clickMenu->ajax;
		$this->listFrame = $this->clickMenu->listFrame;
		$this->alwaysContentFrame = $this->clickMenu->alwaysContentFrame;
		$this->clipObj = $this->clickMenu->clipObj;
		$this->disabledItems = $this->clickMenu->disabledItems;
		$this->clickMenu->backPath = $this->backPath;

		$this->additionalParameter = GeneralUtility::explodeUrl2Array(urldecode(GeneralUtility::_GET('addParams')));
		$this->newWizardAddParams = '&parentCategory=' . $this->additionalParameter['parentCategory'];

		$this->rec = BackendUtility::getRecordWSOL($table, $this->additionalParameter['control[' . $table . '][uid]']);

			// Initialize the rights-variables
		$rights = array(
			'delete' => FALSE,
			'edit' => FALSE,
			'new' => FALSE,
			'editLock' => FALSE,
			'DBmount' => FALSE,
			'copy' => FALSE,
			'paste' => FALSE,
			'overwrite' => FALSE,
			'version' => FALSE,
			'review' => FALSE,
			'l10nOverlay' => FALSE,

				// not realy rights but needed for correct rights handling
			'root' => 0,
			'copyType' => 'after',
		);
			// used to hide cut,copy icons for l10n-records
			// should only be performed for overlay-records within the same table
		if (BackendUtility::isTableLocalizable($table) && !isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])) {
			$rights['l10nOverlay'] = intval($this->rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]) != 0;
		}

			// get rights based on the table
		switch ($table) {
			case 'tx_commerce_categories':
				$rights = $this->calculateCategoryRights($this->rec['uid'], $rights);
				break;

			case 'tx_commerce_products':
				$rights = $this->calculateProductRights($this->rec['uid'], $rights);
				break;

			case 'tx_commerce_articles':
				$rights = $this->calculateArticleRights($this->rec['uid'], $rights);
				break;

			default:
		}

		$menuItems = array();

		// If record found, go ahead and fill the $menuItems array which will contain
		// data for the elements to render.
		if (is_array($this->rec)) {
				// Edit:
			if (!$rights['root'] && !$rights['editLock'] && $rights['edit']) {
				if (
					!in_array('hide', $this->disabledItems) &&
					is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']) &&
					$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']
				) {
					$menuItems['hide'] = $this->DB_hideUnhide($table, $this->rec, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']);
				}

				if (!in_array('edit', $this->disabledItems)) {
					$menuItems['edit'] = $this->DB_edit($table, $uid);
				}
				$this->clickMenu->editOK = 1;
			}

			// fix: always give the UID of the products page to create any commerce object
			if (!in_array('new', $this->disabledItems) && $rights['new']) {
				$menuItems['new'] = $this->DB_new($table, $uid, Tx_Commerce_Utility_BackendUtility::getProductFolderUid());
			}

				// Info:
			if (!in_array('info', $this->disabledItems) && !$rights['root']) {
				$menuItems['info'] = $this->DB_info($table, $uid);
			}

			$menuItems['spacer1'] = 'spacer';

				// Cut not included
				// Copy:
			if (
				!in_array('copy', $this->disabledItems) &&
				!$rights['root'] &&
				!$rights['DBmount'] &&
				!$rights['l10nOverlay'] &&
				$rights['copy']
			) {
				$clipboardUid = $uid;
				if ($this->additionalParameter['category']) {
					$clipboardUid .= '|' . $this->additionalParameter['category'];
				}
				$menuItems['copy'] = $this->DB_copycut($table, $clipboardUid, 'copy');
			}

				// Cut:
			if (
				!in_array('cut', $this->disabledItems) &&
				!$rights['root'] &&
				!$rights['DBmount'] &&
				!$rights['l10nOverlay'] &&
				$rights['copy']
			) {
				$menuItems['cut'] = $this->DB_copycut($table, $uid, 'cut');
			}

				// Paste
			$elFromAllTables = count($this->clickMenu->clipObj->elFromTable(''));
			if (!in_array('paste', $this->disabledItems) && $elFromAllTables && $rights['paste']) {
				$selItem = $this->clipObj->getSelectedRecord();
				$elInfo = array(
					GeneralUtility::fixed_lgd_cs($selItem['_RECORD_TITLE'], $backendUser->uc['titleLen']),
					(
						$rights['root'] ?
						$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] :
						GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $this->rec), $backendUser->uc['titleLen'])
					),
					$this->clipObj->currentMode()
				);

				$pasteUid = $uid;
				if ($this->additionalParameter['category']) {
					$pasteUid .= '|' . $this->additionalParameter['category'];
				}

				$elFromTable = count($this->clipObj->elFromTable($table));
				if ($table == 'tx_commerce_products' && $rights['overwrite'] && $elFromTable) {
						// overwrite product with product
					$menuItems['overwrite'] = $this->DB_overwrite($table, $pasteUid, $elInfo);
				}

				if ($table == 'tx_commerce_categories') {
					$pasteIntoUid = $this->rec['pid'];
					if ($this->additionalParameter['category']) {
						$pasteIntoUid .= '|' . $this->additionalParameter['category'];
					}

					if ($elFromAllTables) {
						$menuItems['pasteinto'] = $this->DB_paste('', $pasteIntoUid, 'into', $elInfo);
					}
				}

				if (!$rights['root'] && !$rights['DBmount'] && $elFromTable && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
					$menuItems['pasteafter'] = $this->DB_paste($table, '-' . $pasteUid, 'after', $elInfo);
				}
			}

				// Delete:
			$elInfo = array(GeneralUtility::fixed_lgd_cs(
				BackendUtility::getRecordTitle($table, $this->rec),
				$backendUser->uc['titleLen']
			));

			if (
				!$rights['editLock'] &&
				!in_array('delete', $this->disabledItems) &&
				!$rights['root'] &&
				!$rights['DBmount'] &&
				$rights['delete']
			) {
				$menuItems['spacer2'] = 'spacer';
				$menuItems['delete'] = $this->DB_delete($table, $uid, $elInfo);
			}

			if (!in_array('history', $this->disabledItems)) {
				$menuItems['history'] = $this->DB_history($table, $uid, $elInfo);
			}
		} else {
			// if no item was found we clicked the top most node
			if (!in_array('new', $this->disabledItems) && $rights['new']) {
				$menuItems = array();
				$menuItems['new'] = $this->DB_new($table, $uid, Tx_Commerce_Utility_BackendUtility::getProductFolderUid());
			}
		}

		return $menuItems;
	}

	/**
	 * @param integer $uid
	 * @param array $rights
	 * @return array
	 */
	protected function calculateCategoryRights($uid, $rights) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// check if current item is root
		$rights['root'] = (int)($uid == '0');

			// find uid of category or translation parent category
		$categoryToCheckRightsOn = $uid;
		if ($this->rec['sys_language_uid']) {
			$categoryToCheckRightsOn = $this->rec['l18n_parent'];
		}

			// get the rights for this category
		$rights['delete'] = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(
			array($categoryToCheckRightsOn),
			array('delete')
		);
		$rights['edit'] = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(
			array($categoryToCheckRightsOn),
			array('edit')
		);
		$rights['new'] = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(
			array($categoryToCheckRightsOn),
			array('new')
		);

			// check if we may paste into this category
		if (count($this->clickMenu->clipObj->elFromTable('tx_commerce_categories'))) {
				// if category is in clipboard, check new-right
			$rights['paste'] = $rights['new'];

			// make sure we dont offer pasting one category into itself. that
			// would lead to endless recursion
			$clipRecord = $this->clickMenu->clipObj->getSelectedRecord();
			/** @var Tx_Commerce_Domain_Model_Category $category */
			$category = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $clipRecord['uid']);
			$category->loadData();
			$childCategories = $category->getChildCategories();

			/** @var Tx_Commerce_Domain_Model_Category $childCategory */
			foreach ($childCategories as $childCategory) {
				if ($uid == $childCategory->getUid()) {
					$rights['paste'] = FALSE;
					break;
				}
			}
		} elseif (count($this->clickMenu->clipObj->elFromTable('tx_commerce_products'))) {
				// if product is in clipboard, check editcontent right
			$rights['paste'] = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(array($uid), array('editcontent'));
		}

		$rights['editLock'] = ($backendUser->isAdmin()) ? FALSE : $this->rec['editlock'];

			// check if the current item is a db mount
		/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
		$mounts = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryMounts');
		$mounts->init($backendUser->user['uid']);
		$rights['DBmount'] = (in_array($uid, $mounts->getMountData()));

		// if the category has no parent categories treat as root
		/** @var Tx_Commerce_Domain_Model_Category $category */
		$category = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $categoryToCheckRightsOn);
		if ($categoryToCheckRightsOn) {
			$rights['DBmount'] = count($category->getParentCategories()) ? $rights['DBmount'] : TRUE;
		} else {
			// to enable new link on top most node
			$rights['new'] = TRUE;
		}

		$rights['copy'] = ($this->rec['sys_language_uid'] == 0);
		$rights['copyType'] = 'into';

			// pasting or new into translations is not allowed
		if ($this->rec['sys_language_uid']) {
			$rights['new'] = FALSE;
			$rights['paste'] = FALSE;
		}

		return $rights;
	}

	/**
	 * @param integer $uid
	 * @param array $rights
	 * @return array
	 */
	protected function calculateProductRights($uid, $rights) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// get all parent categories
		/** @var Tx_Commerce_Domain_Model_Product $product */
		$product = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $uid);

		$parentCategories = $product->getParentCategories();

			// store the rights in the flags
		$rights['delete'] = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(
			$parentCategories,
			array('editcontent')
		);
		$rights['edit'] = $rights['delete'];
		$rights['new'] = $rights['delete'];
		$rights['copy'] = ($this->rec['t3ver_state'] == 0 && $this->rec['sys_language_uid'] == 0);
		$rights['paste'] = $rights['overwrite'] = (($this->rec['t3ver_state'] == 0) && $rights['delete']);

			// make sure we do not allowed to overwrite a product with itself
		if (count($this->clipObj->elFromTable('tx_commerce_products'))) {
			$set = 0;
			if ($this->clipObj->clipData[$this->clipObj->current]['el']['tx_commerce_products|' . $uid . '|' . $this->additionalParameter['category']]) {
				$set = 1;
				$this->clipObj->clipData[$this->clipObj->current]['el']['tx_commerce_products|' . $uid] = 1;
			}
			$clipRecord = $this->clipObj->getSelectedRecord();
			$rights['overwrite'] = ($uid != $clipRecord['uid']) ? FALSE : $rights['overwrite'];

			if ($set) {
				unset($this->clipObj->clipData[$this->clipObj->current]['el']['tx_commerce_products|' . $uid]);
			}
		}

		$rights['version'] = ($backendUser->check('modules', 'web_txversionM1')) && ExtensionManagementUtility::isLoaded('version');
		$rights['review'] = $rights['version'] &&
			$this->rec['t3ver_oid'] != 0 &&
			(
				$this->rec['t3ver_stage'] == 0 ||
				$this->rec['t3ver_stage'] == 1
			);

		return $rights;
	}

	/**
	 * @param integer $uid
	 * @param array $rights
	 * @return array
	 */
	protected function calculateArticleRights($uid, $rights) {
			// get all parent categories for the parent product
		/** @var Tx_Commerce_Domain_Model_Article $article */
		$article = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Article', $uid);

		$productUid = $article->getParentProductUid();

			// get the parent categories of the product
		/** @var Tx_Commerce_Domain_Model_Product $product */
		$product = GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $productUid);

		$parentCategories = $product->getParentCategories();

			// store the rights in the flags
		$rights['delete'] = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(
			$parentCategories,
			array('editcontent')
		);
		$rights['edit'] = $rights['delete'];

		return $rights;
	}


	/**
	 * @param string $table
	 * @param int $uid
	 * @return array
	 */
	public function DB_new($table, $uid) {
		$loc = 'top.content.list_frame';
		$editOnClick = 'if (' . $loc . ') {' . $loc . ".location.href=top.TS.PATH_typo3+'" .
			(
				$this->listFrame ?
				"alt_doc.php?returnUrl='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . '.pathname+' .
					$this->frameLocation($loc . '.document') . ".search)+'&edit[" . $table . '][-' . $uid . ']=new&' .
					$this->newWizardAddParams . "'" :
				'db_new.php?id=' . intval($uid) . $this->newWizardAddParams . "'"
			) .
			';} ';

		return $this->linkItem(
			$this->label('new'),
			$this->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new')),
			$editOnClick . 'return hideCM();'
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
	public function DB_overwrite($table, $uid, $elInfo) {
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
			$language->makeEntities(
				$language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xml:clickmenu.overwrite', 1)
			),
			$this->clickMenu->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-paste-into')),
			$editOnClick . 'return false;'
		);
	}

	/**
	 * Displays the 'Send to review/public' option
	 *
	 * @param string $table Table that is to be host of the sending
	 * @param integer $uid uid of the item that is to be send
	 * @return string
	 */
	public function DB_review($table, $uid) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$url = ExtensionManagementUtility::extRelPath('version') . 'cm1/index.php?id=' .
			($table == 'pages' ? $uid : $this->rec['pid']) .
			'&table=' . rawurlencode($table) . '&uid=' . $uid . '&sendToReview=1';

		return $this->clickMenu->linkItem(
			$language->sL('LLL:EXT:version/locallang.xml:title_review', 1),
			$this->excludeIcon('<img src="' . $this->backPath . ExtensionManagementUtility::extRelPath('version') .
				'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
			$this->clickMenu->urlRefForCM($url),
			1
		);
	}


	/**
	 * overwriteUrl of the element (database)
	 *
	 * @param string $table Tablename
	 * @param integer $uid uid of the record that should be overwritten
	 * @param integer $redirect [optional] If set, then the redirect URL will point
	 * 		back to the current script, but with CB reset.
	 * @return string
	 */
	protected function overwriteUrl($table, $uid, $redirect = 1) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$rU = $this->clickMenu->clipObj->backPath . PATH_TXCOMMERCE_REL . 'Classes/Utility/DataHandlerUtility.php?' .
			($redirect ? 'redirect=' . rawurlencode(GeneralUtility::linkThisScript(array('CB' => ''))) : '') .
			'&vC=' . $backendUser->veriCode() .
			'&prErr=1&uPT=1' .
			'&CB[overwrite]=' . rawurlencode($table . '|' . $uid) .
			'&CB[pad]=' . $this->clickMenu->clipObj->current .
			BackendUtility::getUrlToken('tceAction');
		return $rU;
	}
}

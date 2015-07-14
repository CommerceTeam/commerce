<?php
namespace CommerceTeam\Commerce\Utility;
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
use TYPO3\CMS\Backend\ClickMenu\ClickMenu;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extended Functionality for the Clickmenu when commerce-tables are hit
 * Basically does the same as the alt_clickmenu.php, only that for Categories
 * the output needs to be overridden depending on the rights
 *
 * Class \CommerceTeam\Commerce\Utility\ClickmenuUtility
 *
 * @author 2008-2012 Erik Frister <typo3@marketing-factory.de>
 */
class ClickmenuUtility extends ClickMenu {
	/**
	 * Back path
	 *
	 * @var string
	 */
	public $backPath = '../../../../../../typo3/';

	/**
	 * Record
	 *
	 * @var array
	 */
	public $rec;

	/**
	 * Click menu
	 *
	 * @var ClickMenu
	 */
	protected $clickMenu;

	/**
	 * Clip board
	 *
	 * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
	 */
	protected $clipObj;

	/**
	 * Additional parameter
	 *
	 * @var array
	 */
	protected $additionalParameter = array();

	/**
	 * New wizard parameters
	 *
	 * @var string
	 */
	protected $newWizardAddParams = '';

	/**
	 * Table names
	 *
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
	 * @param ClickMenu $clickMenu Clickenu object
	 * @param array $menuItems Current menu Items
	 * @param string $table Table
	 * @param int $uid Uid
	 *
	 * @return array Menu Items Array
	 */
	public function main(ClickMenu &$clickMenu, array $menuItems, $table, $uid) {
		// Only modify the menu Items if we have the correct table
		if (!in_array($table, $this->commerceTables)) {
			return $menuItems;
		}

		$backendUser = $this->getBackendUser();

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

		$settingsFactory = SettingsFactory::getInstance();

		// used to hide cut,copy icons for l10n-records
		// should only be performed for overlay-records within the same table
		if (BackendUtility::isTableLocalizable($table) && !$settingsFactory->getTcaValue($table . '.ctrl.transOrigPointerTable')) {
			$rights['l10nOverlay'] = intval($this->rec[$settingsFactory->getTcaValue($table . '.ctrl.transOrigPointerField')]) != 0;
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
					!in_array('hide', $this->disabledItems)
					&& $settingsFactory->getTcaValue($table . '.ctrl.enablecolumns.disabled')
				) {
					$menuItems['hide'] = $this->DB_hideUnhide(
						$table,
						$this->rec,
						$settingsFactory->getTcaValue($table . '.ctrl.enablecolumns.disabled')
					);
				}

				if (!in_array('edit', $this->disabledItems)) {
					$menuItems['edit'] = $this->DB_edit($table, $uid);
				}
				$this->clickMenu->editOK = 1;
			}

			// fix: always give the UID of the products page to create any commerce object
			if (!in_array('new', $this->disabledItems) && $rights['new']) {
				$menuItems['new'] = $this->DB_new($table, $uid);
			}

			// Info:
			if (!in_array('info', $this->disabledItems) && !$rights['root']) {
				$menuItems['info'] = $this->DB_info($table, $uid);
			}

			$menuItems['spacer1'] = 'spacer';

			// Cut not included
			// Copy:
			if (
				!in_array('copy', $this->disabledItems)
				&& !$rights['root']
				&& !$rights['DBmount']
				&& !$rights['l10nOverlay']
				&& $rights['copy']
			) {
				$clipboardUid = $uid;
				if ($this->additionalParameter['category']) {
					$clipboardUid .= '|' . $this->additionalParameter['category'];
				}
				$menuItems['copy'] = $this->DB_copycut($table, $clipboardUid, 'copy');
			}

			// Cut:
			if (
				!in_array('cut', $this->disabledItems)
				&& !$rights['root']
				&& !$rights['DBmount']
				&& !$rights['l10nOverlay']
				&& $rights['copy']
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
						$rights['root'] ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] : GeneralUtility::fixed_lgd_cs(
							BackendUtility::getRecordTitle($table, $this->rec), $backendUser->uc['titleLen']
						)
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

				if (!$rights['root'] && !$rights['DBmount'] && $elFromTable && $settingsFactory->getTcaValue($table . '.ctrl.sortby')) {
					$menuItems['pasteafter'] = $this->DB_paste($table, '-' . $pasteUid, 'after', $elInfo);
				}
			}

			// Delete:
			$elInfo = array(GeneralUtility::fixed_lgd_cs(
				BackendUtility::getRecordTitle($table, $this->rec),
				$backendUser->uc['titleLen']
			));

			if (
				!$rights['editLock']
				&& !in_array('delete', $this->disabledItems)
				&& !$rights['root']
				&& !$rights['DBmount']
				&& $rights['delete']
			) {
				$menuItems['spacer2'] = 'spacer';
				$menuItems['delete'] = $this->DB_delete($table, $uid, $elInfo);
			}

			if (!in_array('history', $this->disabledItems)) {
				$menuItems['history'] = $this->DB_history($table, $uid);
			}
		} else {
			// if no item was found we clicked the top most node
			if (!in_array('new', $this->disabledItems) && $rights['new']) {
				$menuItems = array();
				$menuItems['new'] = $this->DB_new($table, $uid);
			}
		}

		return $menuItems;
	}

	/**
	 * Calculate category rights
	 *
	 * @param int $uid Uid
	 * @param array $rights Rights
	 *
	 * @return array
	 */
	protected function calculateCategoryRights($uid, array $rights) {
		$backendUser = $this->getBackendUser();

		// check if current item is root
		$rights['root'] = (int)($uid == '0');

		// find uid of category or translation parent category
		$categoryToCheckRightsOn = $uid;
		if ($this->rec['sys_language_uid']) {
			$categoryToCheckRightsOn = $this->rec['l18n_parent'];
		}

		// get the rights for this category
		$rights['delete'] = \CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
			array($categoryToCheckRightsOn),
			array('delete')
		);
		$rights['edit'] = \CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
			array($categoryToCheckRightsOn),
			array('edit')
		);
		$rights['new'] = \CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
			array($categoryToCheckRightsOn),
			array('new')
		);

		// check if we may paste into this category
		if (!empty($this->clickMenu->clipObj->elFromTable('tx_commerce_categories'))) {
			// if category is in clipboard, check new-right
			$rights['paste'] = $rights['new'];

			// make sure we dont offer pasting one category into itself. that
			// would lead to endless recursion
			$clipRecord = $this->clickMenu->clipObj->getSelectedRecord();

			/**
			 * Category
			 *
			 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
			 */
			$category = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Category', $clipRecord['uid']);
			$category->loadData();
			$childCategories = $category->getChildCategories();

			/**
			 * Child category
			 *
			 * @var \CommerceTeam\Commerce\Domain\Model\Category $childCategory
			 */
			foreach ($childCategories as $childCategory) {
				if ($uid == $childCategory->getUid()) {
					$rights['paste'] = FALSE;
					break;
				}
			}
		} elseif (!empty($this->clickMenu->clipObj->elFromTable('tx_commerce_products'))) {
			// if product is in clipboard, check editcontent right
			$rights['paste'] = \CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
				array($uid),
				array('editcontent')
			);
		}

		$rights['editLock'] = ($backendUser->isAdmin()) ? FALSE : $this->rec['editlock'];

		// check if the current item is a db mount
		/**
		 * Category mounts
		 *
		 * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mounts
		 */
		$mounts = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\CategoryMounts');
		$mounts->init($backendUser->user['uid']);
		$rights['DBmount'] = (in_array($uid, $mounts->getMountData()));

		// if the category has no parent categories treat as root
		/**
		 * Category
		 *
		 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
		 */
		$category = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Category', $categoryToCheckRightsOn);
		if ($categoryToCheckRightsOn) {
			$rights['DBmount'] = !empty($category->getParentCategories()) ? $rights['DBmount'] : TRUE;
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
	 * Calculate product rights
	 *
	 * @param int $uid Uid
	 * @param array $rights Rights
	 *
	 * @return array
	 */
	protected function calculateProductRights($uid, array $rights) {
		$backendUser = $this->getBackendUser();

		// get all parent categories
		/**
		 * Product
		 *
		 * @var \CommerceTeam\Commerce\Domain\Model\Product $product
		 */
		$product = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Product', $uid);

		$parentCategories = $product->getParentCategories();

			// store the rights in the flags
		$rights['delete'] = \CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
			$parentCategories,
			array('editcontent')
		);
		$rights['edit'] = $rights['delete'];
		$rights['new'] = $rights['delete'];
		$rights['copy'] = ($this->rec['t3ver_state'] == 0 && $this->rec['sys_language_uid'] == 0);
		$rights['paste'] = $rights['overwrite'] = (($this->rec['t3ver_state'] == 0) && $rights['delete']);

			// make sure we do not allowed to overwrite a product with itself
		if (!empty($this->clipObj->elFromTable('tx_commerce_products'))) {
			$set = 0;
			if ($this->clipObj->clipData[$this->clipObj->current]['el']['tx_commerce_products|' . $uid . '|' .
				$this->additionalParameter['category']]
			) {
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
		$rights['review'] = $rights['version']
			&& $this->rec['t3ver_oid'] != 0
			&& ($this->rec['t3ver_stage'] == 0 || $this->rec['t3ver_stage'] == 1);

		return $rights;
	}

	/**
	 * Calculate article rights
	 *
	 * @param int $uid Uid
	 * @param array $rights Rights
	 *
	 * @return array
	 */
	protected function calculateArticleRights($uid, array $rights) {
		// get all parent categories for the parent product
		/**
		 * Article
		 *
		 * @var \CommerceTeam\Commerce\Domain\Model\Article $article
		 */
		$article = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Article', $uid);

		// get the parent categories of the product
		$parentCategories = $article->getParentProduct()->getParentCategories();

		// store the rights in the flags
		$rights['edit'] = $rights['delete'] = \CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
			$parentCategories,
			array('editcontent')
		);

		return $rights;
	}


	/**
	 * Add new menu item
	 *
	 * @param string $table Table
	 * @param int $uid Uid
	 *
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
	 * @param int $uid Uid of the item that is to be overwritten
	 * @param array $elInfo Info Array
	 *
	 * @return string
	 */
	public function DB_overwrite($table, $uid, array $elInfo) {
		$language = $this->getLanguageService();
		$backendUser = $this->getBackendUser();

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
	 * @param int $uid Uid of the item that is to be send
	 *
	 * @return string
	 */
	public function DB_review($table, $uid) {
		$language = $this->getLanguageService();

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
	 * Overwrite url of the element (database)
	 *
	 * @param string $table Tablename
	 * @param int $uid Uid of the record that should be overwritten
	 * @param int $redirect If set, then the redirect URL will point
	 * 		back to the current script, but with CB reset.
	 *
	 * @return string
	 */
	protected function overwriteUrl($table, $uid, $redirect = 1) {
		$backendUser = $this->getBackendUser();

		return $this->clickMenu->clipObj->backPath . PATH_TXCOMMERCE_REL . 'Classes/Utility/DataHandlerUtility.php?' .
			($redirect ? 'redirect=' . rawurlencode(GeneralUtility::linkThisScript(array('CB' => ''))) : '') .
			'&vC=' . $backendUser->veriCode() .
			'&prErr=1&uPT=1' .
			'&CB[overwrite]=' . rawurlencode($table . '|' . $uid) .
			'&CB[pad]=' . $this->clickMenu->clipObj->current .
			BackendUtility::getUrlToken('tceAction');
	}


	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}

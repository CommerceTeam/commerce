<?php
/**
 * Extended Functionality for the Clickmenu when commerce-tables are hit
 * Basically does the same as the alt_clickmenu.php, only that for Categories the output needs to be overridden depending on the rights
 */
class Tx_Commerce_Utility_ClickmenuUtility {
	/**
	 * @var string
	 */
	protected $backPath = '../../../../../../typo3/';

	/**
	 * @var array
	 */
	protected $rec;

	/**
	 * @var clickMenu
	 */
	protected $pObj;

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
	 * @param clickMenu $pObj clickenu object
	 * @param array $menuItems current menu Items
	 * @param string $table db table
	 * @param integer $uid uid of the record
	 * @return array Menu Items Array
	 */
	public function main(&$pObj, $menuItems, $table, $uid) {
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
		$pObj->backPath = $this->backPath;
			// do not allow the entry 'history' in the clickmenu
		$pObj->disabledItems[]  = 'history';

			// Get record:
		$this->rec = t3lib_BEfunc::getRecordWSOL($table, $uid);
		$this->pObj = $pObj;

			// Initialize the rights-variables
		$delete = FALSE;
		$edit = FALSE;
		$new = FALSE;
		$editLock = FALSE;
		$root = 0;
		$DBmount = FALSE;
		$copy = FALSE;
		$paste = FALSE;
		$version = FALSE;
		$review = FALSE;

			// get category uid depending on where the clickmenu is called
		switch($table) {
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
				if (count($this->pObj->clipObj->elFromTable('tx_commerce_products'))) {
					$clipRecord = $this->pObj->clipObj->getSelectedRecord();
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

			case 'tx_commerce_categories':
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
				if (count($this->pObj->clipObj->elFromTable('tx_commerce_categories'))) {
						// if category is in clipboard, check new-right
					$paste = $new;

						// make sure we dont offer pasting one category into itself. that would lead to endless recursion
					$clipRecord = $this->pObj->clipObj->getSelectedRecord();
					$paste = ($uid == $clipRecord['uid']) ? FALSE : $paste;

				} elseif (count($this->pObj->clipObj->elFromTable('tx_commerce_products'))) {
						// if product is in clipboard, check editcontent right
					$paste = Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(array($uid), array('editcontent'));
				}

				$editLock = ($backendUser->isAdmin()) ? FALSE : $this->rec['editlock'];

					// check if the current item is a db mount
				/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
				$mounts = t3lib_div::makeInstance('Tx_Commerce_Tree_CategoryMounts');
				$mounts->init($backendUser->user['uid']);

				$DBmount = (in_array($uid, $mounts->getMountData()));
				$copy = ($this->rec['sys_language_uid'] == 0);

					// check if current item is root
				$root = (int)(0 == $uid);

					// pasting or new into translations is not allowed
				if ($this->rec['sys_language_uid']) {
					$new = FALSE;
					$paste = FALSE;
				}
			break;
		}

			// get the UID of the Products SysFolder
		$prodPid = Tx_Commerce_Utility_BackendUtility::getProductFolderUid();

		$menuItems = array();

			// If record found (or root), go ahead and fill the $menuItems array which will contain data for the elements to render.
		if (is_array($this->rec) || $root) {
				// Edit:
			if (!$root && !$editLock && $edit) {
				if (!in_array('edit', $pObj->disabledItems)) {
					$menuItems['edit'] = $pObj->DB_edit($table, $uid);
				}
				$pObj->editOK = 1;
			}

				// New: fix: always give the UID of the products page to create any commerce object
			if (!in_array('new', $pObj->disabledItems) && $new) {
				$menuItems['new'] = $pObj->DB_new($table, $prodPid);
			}

				// Info:
			if (!in_array('info', $pObj->disabledItems) && !$root) {
				$menuItems['info'] = $pObj->DB_info($table, $uid);
			}

			$menuItems['spacer1'] = 'spacer';

				// Cut not included
				// Copy:
			if (!in_array('copy', $pObj->disabledItems) && $copy && !$root && !$DBmount) {
				$menuItems['copy'] = $pObj->DB_copycut($table, $uid, 'copy');
			}

				// Paste
			$elFromAllTables = count($this->pObj->clipObj->elFromTable(''));
			$elInfo = array();
			if (!in_array('paste', $pObj->disabledItems) && $elFromAllTables) {
				$selItem = $this->pObj->clipObj->getSelectedRecord();
				$elInfo = array(
					t3lib_div::fixed_lgd_cs($selItem['_RECORD_TITLE'], $backendUser->uc['titleLen']),
					($root ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] : t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table, $pObj->rec), $backendUser->uc['titleLen'])),
					$this->pObj->clipObj->currentMode()
				);

				$elFromTable = count($this->pObj->clipObj->elFromTable($table));
				if (!$root && !$DBmount && $elFromTable && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && 'tx_commerce_categories' == $table && $paste) {
						// paste into - for categories
					$menuItems['pasteafter'] = $this->DB_paste($table, $uid, $elInfo);
				} elseif (!$root && $paste && !$DBmount && $elFromTable && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && 'tx_commerce_products' == $table) {
						// overwrite product with product
					$menuItems['overwrite'] =  $this->DB_overwrite($table, $uid, $elInfo);
				} elseif (!$root && $paste && !$DBmount && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && 'tx_commerce_categories' == $table && count($this->pObj->clipObj->elFromTable('tx_commerce_products'))) {
						// paste product into category
					$menuItems['pasteafter'] = $this->DB_paste($table, $uid, $elInfo);
				}
			}

				// versioning
			if (!in_array('versioning', $pObj->disabledItems) && $version) {
				$menuItems['versioning'] = $this->DB_versioning($table, $uid, $elInfo);
			}

				// send to review
			if (!in_array('review', $pObj->disabledItems) && $review) {
				$menuItems['review'] = $this->DB_review($table, $uid, $elInfo);
			}

				// Delete:
			$elInfo = array(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table, $this->rec), $backendUser->uc['titleLen']));

			if (!$editLock && !in_array('delete', $pObj->disabledItems) && !$root && !$DBmount && $delete) {
				$menuItems['spacer2'] = 'spacer';
				$menuItems['delete'] = $pObj->DB_delete($table, $uid, $elInfo);
			}

			if (!in_array('history', $pObj->disabledItems)) {
				$menuItems['history'] = $pObj->DB_history($table, $uid, $elInfo);
			}
		}

		return $menuItems;
	}

	/**
	 * Displays the paste option
	 *
	 * @param string $table Object
	 * @param integer $uid Object
	 * @param array $elInfo Object
	 * @return string
	 */
	protected function DB_paste($table, $uid, $elInfo) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$loc = 'top.content' . ($this->pObj->listFrame && !$this->pObj->alwaysContentFrame ? '.list_frame' : '');

		if ($backendUser->jsConfirmation(2)) {
			$conf = $loc . ' && confirm(' . $language->JScharCode(
				sprintf(
					$language->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.pasteConfirm'),
					$elInfo[0],
					$elInfo[1]
				)
			) . ')';
		} else {
			$conf = $loc;
		}

		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' .
			$this->pasteUrl($table, $uid, 0) . '&redirect=\'+top.rawurlencode(' . $this->pObj->frameLocation($loc . '.document') .
			'); hideCM();}';

		return $this->pObj->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.paste', 1)),
			$this->pObj->excludeIcon('<img' . t3lib_iconWorks::skinImg($this->pObj->PH_backPath, 'gfx/clip_pasteinto.gif', 'width="12" height="12"') . ' alt="" />'),
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

		$loc = 'top.content' . ($this->pObj->listFrame && !$this->pObj->alwaysContentFrame ? '.list_frame' : '');

		if ($backendUser->jsConfirmation(2)) {
			$conf = $loc . ' && confirm(' . $language->JScharCode(
				sprintf(
					$language->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.overwriteConfirm'),
					$elInfo[0],
					$elInfo[1]
				)
			) . ')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' .
			$this->overwriteUrl($table, $uid, 0) . '&redirect=\'+top.rawurlencode(' .
			$this->pObj->frameLocation($loc . '.document') . '); hideCM();}';

		return $this->pObj->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:commerce/locallang_treelib.php:clickmenu.overwrite', 1)),
			$this->pObj->excludeIcon('<img' . t3lib_iconWorks::skinImg($this->pObj->PH_backPath, 'gfx/clip_pasteinto.gif', 'width="12" height="12"') . ' alt="" />'),
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

		return $this->pObj->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:version/locallang.xml:title', 1)),
			$this->pObj->excludeIcon(
				'<img' . t3lib_iconWorks::skinImg(
					$this->pObj->PH_backPath,
					t3lib_extMgm::extRelPath('version') . 'cm1/cm_icon.gif',
					'width="15" height="12"'
				) . ' alt="" />'
			),
			$this->pObj->urlRefForCM($url),
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

		$url = t3lib_extMgm::extRelPath('version') . 'cm1/index.php?id=' . ($table == 'pages' ? $uid : $this->pObj->rec['pid']) .
			'&table=' . rawurlencode($table) . '&uid=' . $uid . '&sendToReview=1';

		return $this->pObj->linkItem(
			$language->makeEntities($language->sL('LLL:EXT:version/locallang.xml:title_review', 1)),
			$this->pObj->excludeIcon(
				'<img' . t3lib_iconWorks::skinImg(
					$this->pObj->PH_backPath,
					t3lib_extMgm::extRelPath('version') . 'cm1/cm_icon.gif',
					'width="15" height="12"'
				) . ' alt="" />'
			),
			$this->pObj->urlRefForCM($url),
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

		$rU = $this->pObj->clipObj->backPath . PATH_TXCOMMERCE_REL . 'Classes/Utility/DataHandlerUtility.php?' .
			($redirect ? 'redirect=' . rawurlencode(t3lib_div::linkThisScript(array('CB' => ''))) : '') .
			'&vC=' . $backendUser->veriCode() .
			'&prErr=1&uPT=1' .
			'&CB[overwrite]=' . rawurlencode($table . '|' . $uid) .
			'&CB[pad]=' . $this->pObj->clipObj->current .
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

		$rU = $this->pObj->clipObj->backPath . PATH_TXCOMMERCE_REL . 'Classes/Utility/DataHandlerUtility.php?' .
			($setRedirect ? 'redirect=' . rawurlencode(t3lib_div::linkThisScript(array('CB' => ''))) : '') .
			'&vC=' . $backendUser->veriCode() .
			'&prErr=1&uPT=1' .
			'&CB[paste]=' . rawurlencode($table . '|' . $uid) .
			'&CB[pad]=' . $this->pObj->clipObj->current .
			t3lib_BEfunc::getUrlToken('tceAction');
		return $rU;
	}
}

class_alias('Tx_Commerce_Utility_ClickmenuUtility', 'tx_commerce_clickmenu');

?>
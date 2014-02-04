<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_Commerce_Controller_OrdersController extends t3lib_SCbase {
	/**
	 * Page Id for which to make the listing
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Pointer - for browsing list of records.
	 *
	 * @var integer
	 */
	protected $pointer = 0;

	/**
	 * Thumbnails or not
	 *
	 * @var boolean
	 */
	protected $imagemode;

	/**
	 * Which table to make extended listing for
	 *
	 * @var string
	 */
	protected $table = 'tx_commerce_orders';

	/**
	 * Which table to make extended listing for
	 *
	 * @var string
	 */
	protected $table_user = 'fe_users';

	/**
	 * Search-fields
	 *
	 * @var string
	 */
	protected $search_field;

	/**
	 * Search-levels
	 *
	 * @var integer
	 */
	protected $search_levels;

	/**
	 * Show-limit
	 *
	 * @var integer
	 */
	protected $showLimit;

	/**
	 * Return URL
	 *
	 * @var string
	 */
	protected $returnUrl;

	/**
	 * Clear-cache flag - if set, clears page cache for current id.
	 *
	 * @var
	 */
	protected $clear_cache;

	/**
	 * Command: Eg. "delete" or "setCB" (for TCEmain / clipboard operations)
	 *
	 * @var string
	 */
	protected $cmd;

	/**
	 * Table on which the cmd-action is performed.
	 *
	 * @var string
	 */
	protected $cmd_table;

	/**
	 * Page select perms clause
	 *
	 * @var integer
	 */
	public $perms_clause;

	/**
	 * Module TSconfig
	 *
	 * @var array
	 */
	public $modTSconfig;

	/**
	 * Current ids page record
	 *
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * Document template object
	 *
	 * @var template
	 */
	public $doc;

	/**
	 * Module configuration
	 *
	 * @var array
	 */
	public $MCONF = array();

	/**
	 * Menu configuration
	 *
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * Module settings (session variable)
	 *
	 * @var array
	 */
	public $MOD_SETTINGS = array();

	/**
	 * @var array
	 */
	protected $buttons = array();

	/**
	 * @var integer
	 */
	protected $clickMenuEnabled = 0;

	/**
	 * @var boolean
	 */
	protected $dontShowClipControlPanels;

	/**
	 * @var boolean
	 */
	protected $noTopView;

	/**
	 * @var integer
	 */
	protected $userID;

	/**
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->table = 'tx_commerce_orders';
		$this->clickMenuEnabled = 1;
		Tx_Commerce_Utility_FolderUtility::init_folders();

		/**
		 * If we get an id via GP use this, else use the default id
		 */
		$this->id = t3lib_div::_GP('id');
		if (!$this->id) {
			$this->id = current(array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce')));
		}

			// Initialize the listing object, dblist, for rendering the list:
		if (t3lib_div::_GP('pointer')) {
			$this->pointer = t3lib_div::intInRange(t3lib_div::_GP('pointer'), 0, PHP_INT_MAX);
		}

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_orders.html');

		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set navframeTemplate', 'commerce', 2, array(
				'backpath' => $this->doc->backPath,
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_orders.html'],
				'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['commerce/Resources/Private/Backend/mod_orders.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_order_navframe.html';
			$this->doc->moduleTemplate = t3lib_div::getURL(PATH_site . $templateFile);
		}

		$this->doc->form = '<form action="" method="POST">';
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 */
	public function main() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Access check!
			// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);

		if (($this->id && $access) || $backendUser->isAdmin()) {
				// Fist check if we should move some orders
			$this->doaction();

				// Render content:
			$this->moduleContent();
		} else {
				// If no access or if ID == zero
			$this->content .= $this->doc->header($language->getLL('orders'));
		}

		$docHeaderButtons = $this->getHeaderButtons();

		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content,
		);

			// put it all together
		$this->content = $this->doc->startPage($language->getLL('orders'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return void
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return void
	 */
	public function moduleContent() {
		$this->orderList();
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getHeaderButtons() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$buttons = $this->buttons;

			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_commerce_statistic', '', $GLOBALS['BACK_PATH'], '', TRUE);

			// Shortcut
		if ($backendUser->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon(
				'id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit',
				implode(',', array_keys($this->MOD_MENU)),
				$this->MCONF['name']
			);
		}

			// If access to Web>List for user, then link to that module.
		if ($backendUser->check('modules', 'web_list')) {
			$href = $GLOBALS['BACK_PATH'] . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' .
				rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'apps-filetree-folder-list',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1))
				) . '</a>';
		}
		return $buttons;
	}

	/**
	 * Handle post request
	 */
	protected function doaction() {
		$orderuids = t3lib_div::_GP('orderUid');
		$destPid = t3lib_div::_GP('modeDestUid');

			// Only if we have a list of orders
		if ((is_array($orderuids)) and ($destPid)) {
			foreach ($orderuids as $oneUid) {
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;

				$data['tx_commerce_orders'][$oneUid] = t3lib_befunc::getRecordRaw(
					'tx_commerce_orders',
					'uid = ' . $oneUid,
					'cust_deliveryaddress,cust_fe_user,cust_invoice'
				);
				$data['tx_commerce_orders'][$oneUid]['newpid'] = $destPid;
				$tce->start($data, array());
				$tce->process_datamap();
			}
		}
	}

	/**
	 * generates the orderlist for the module orders
	 * HTML Output will be put to $this->content;
	 *
	 * @return void
	 */
	protected function orderList() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Start document template object:
		$this->dontShowClipControlPanels = 1;

			// Initialize the dblist object:
		/** @var Tx_Commerce_ViewHelpers_OrderRecordList $dblist */
		$dblist = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_OrderRecordList');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->script = 'index.php';
		$dblist->calcPerms = $backendUser->calcPerms($this->pageinfo);
		$dblist->thumbs = $backendUser->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;

		$dblist->allFields = 1;
		if ($this->userID) {
			$dblist->onlyUser = $this->userID;
		}

		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 0;

			// Clipboard is initialized:
			// Start clipboard
		$dblist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			// Initialize - reads the clipboard content from the user session
		$dblist->clipObj->initializeClipboard();

			// Clipboard actions are handled:
			// CB is the clipboard command array
		$CB = t3lib_div::_GET('CB');
		if ($this->cmd == 'setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we
				// get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge(t3lib_div::_POST('CBH'), t3lib_div::_POST('CBC')), $this->cmd_table);
		}

		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpToUrl(URL) {
				document.location = URL;
				return false;
			}
			function jumpExt(URL, anchor) {
				var anc = anchor ? anchor : "";
				document.location = URL + (T3_THIS_LOCATION ? "&returnUrl=" + T3_THIS_LOCATION : "") + anc;
				return false;
			}
			function jumpSelf(URL) {
				document.location = URL + (T3_RETURN_URL ? "&returnUrl=" + T3_RETURN_URL : "");
				return false;
			}
			' . $this->doc->redirectUrls($dblist->listURL()) . '
			' . $dblist->CBfunctions() . '
			function editRecords(table, idList, addParams, CBflag) {
				document.location="' . $this->doc->backPath . 'alt_doc.php?returnUrl=' .
					rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
					'&edit[" + table + "][" + idList + "]=edit" + addParams;
			}
			function editList(table, idList) {
				var list = "";

					// Checking how many is checked, how many is not
				var pointer = 0;
				var pos = idList.indexOf(",");
				while (pos != -1) {
					if (cbValue(table + "|" + idList.substr(pointer, pos-pointer))) {
						list += idList.substr(pointer, pos-pointer) + ",";
					}
					pointer = pos + 1;
					pos = idList.indexOf(",", pointer);
				}
				if (cbValue(table + "|" + idList.substr(pointer))) {
					list += idList.substr(pointer) + ",";
				}

				return list ? list : idList;
			}

			if (top.fsMod) {
				top.fsMod.recentIds["web"] = ' . (int) $this->id . ';
			}
		');

		$dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);

		$this->buttons = $dblist->getHeaderButtons($this->pageinfo);

			// Render versioning selector:
		$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);

			// Render the list of tables:
		$dblist->generateList($this->id, $this->table);

			// Write the bottom of the page:
		$dblist->writeBottom();

			// Begin to compile the whole page, starting out with page header:
		$this->content .= $this->doc->startPage('DB list');
		$this->doc->form = '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';

			// List Module CSH:
		if (!strlen($this->id)) {
			$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_noId', $GLOBALS['BACK_PATH'], '<br/>|');
		} elseif (!$this->id) {
				// zero...
			$this->content .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_root', $GLOBALS['BACK_PATH'], '<br/>|');
		}

			// Add listing HTML code:
		$this->content .= $dblist->HTMLcode;
		$this->content .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
	}
}

class_alias('Tx_Commerce_Controller_OrdersController', 'tx_commerce_orders');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/OrdersController.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Controller/OrdersController.php']);
}

?>
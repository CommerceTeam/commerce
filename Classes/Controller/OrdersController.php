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
	protected $pointer;

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

		$order_pid = array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce'));

		/**
		 * If we get an id via GP use this, else use the default id
		 */
		if (t3lib_div::_GP('id')) {
			$this->id = t3lib_div::_GP('id');
		} else {
			$this->id = $order_pid[0];
		}

			// Initialize page browser
		$this->pointer = 0;
		if (t3lib_div::_GP('pointer')) {
			$this->pointer = intval(t3lib_div::_GP('pointer'));
		}
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
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($backendUser->user['admin'] && !$this->id)) {

				// Fist check if we should move some orders
			$this->doaction();

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];
			$this->doc->form = '<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL) {
					document.location = URL;
				}
			');
			$this->doc->postCode = $this->doc->wrapScriptTags('
				script_ended = 1;
				if (top.fsMod) {
					top.fsMod.recentIds["web"] = ' . (int) $this->id . ';
				}
			');

			$headerSection = $this->doc->getHeader(
					'pages',
					$this->pageinfo,
					$this->pageinfo['_thePath']
				) . '<br/>' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.path') . ': ' .
				t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'], -50);

			$this->content .= $this->doc->startPage($language->getLL('title'));
			$this->content .= $this->doc->header($language->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->section(
					'',
					$this->doc->funcMenu(
						$headerSection,
						t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])
					)
				);
			$this->content .= $this->doc->divider(5);

				// Render content:
			$this->moduleContent();

				// ShortCut
			if ($backendUser->mayMakeShortcut()) {
				$this->content .= $this->doc->spacer(20) . $this->doc->section(
						'',
						$this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name'])
					);
			}

			$this->content .= $this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];

			$this->content .= $this->doc->startPage($language->getLL('title'));
			$this->content .= $this->doc->header($language->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->spacer(10);
		}
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
		$this->content = '';
		$this->orderList($this->content);
	}

	/**
	 * generates the orderlist for the module orders
	 * HTML Output will be put to $this->content;
	 *
	 * @param string $content
	 * @return void
	 */
	protected function orderList($content = '') {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		$this->table = 'tx_commerce_orders';
		$this->content = $content;

			// Start document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->dontShowClipControlPanels = 1;
			// Loading current page record and checking access:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// Initialize the dblist object:
		/** @var tx_commerce_order_localRecordlist $dblist */
		$dblist = t3lib_div::makeInstance('tx_commerce_order_localRecordlist');
			// @todo what the heck
		$dblist->additionalOutTop = $this->doc->section(
				'',
				$this->doc->funcMenu(
					$headerSection,
					t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])
				)
			);
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
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
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
				document.location="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
					rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
					'&edit[" + table + "][" + idList + "]=edit" + addParams;
			}
			function editList(table,idList) {
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

			// Setting up the context sensitive menu:
		$CMparts = $this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode .= $CMparts[0];
		$this->doc->postCode .= $CMparts[2];

			// If there is access to the page, then render the list contents and set up the document template object:
		if ($access) {
				// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = t3lib_div::intInRange($this->pointer, 0, PHP_INT_MAX);

			$dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);

				// Render the page header:
			if (!$this->noTopView) {
				$dblist->writeTop($this->pageinfo);
			}
				// Render versioning selector:
			$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);

				// Render the list of tables:
			$dblist->generateList($this->id, $this->table);

				// Write the bottom of the page:
			$dblist->writeBottom();

				// Add JavaScript functions to the page:
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function jumpToUrl(URL) {
					document.location = URL;
					return false;
				}
				function jumpExt(URL,anchor) {
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
					document.location="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' .
						rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
						'&edit[" + table + "][" + idList + "]=edit" + addParams;
				}
				function editList(table,idList) {
					var list = "";

						// Checking how many is checked, how many is not
					var pointer = 0;
					var pos = idList.indexOf(",");
					while (pos!=-1) {
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
					top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
				}
			');

				// Setting up the context sensitive menu:
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode .= $CMparts[0];
			$this->doc->postCode .= $CMparts[2];
		}

			// Begin to compile the whole page, starting out with page header:
		$this->content .= $this->doc->startPage('DB list');
		$dblist->additionalOutTop .= $this->doc->section(
				'',
				$this->doc->funcMenu(
					$headerSection,
					t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])
				)
			);

		$this->content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';

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
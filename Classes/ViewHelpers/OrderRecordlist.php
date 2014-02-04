<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Daniel Schöttgen <ds@marketing-factory.de>
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
 * Renders order list in the BE order module
 */
class Tx_Commerce_ViewHelpers_OrderRecordlist extends localRecordList {
	/**
	 * @var integer
	 */
	public $alternateBgColors = 1;

	/**
	 * @var string
	 */
	public $additionalOutTop;

	/**
	 * @var integer
	 */
	public $onlyUser;

	/**
	 * @var array
	 */
	protected $myfields = array();

	/**
	 * @var boolean
	 */
	protected $disableSingleTableView;

	/**
	 * @param string $table
	 * @param integer $id
	 * @param string $addWhere
	 * @param string $fieldList
	 * @return array
	 */
	public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*') {
		if ($this->sortField) {
			$orderby = $this->sortField . ' ';
			if ($this->sortRev == 1) {
				$orderby .= 'DESC';
			}
		} else {
			$orderby = 'crdate DESC';
		}

		$limit = '';
		if ($this->iLimit) {
			$limit = ($this->firstElementNumber ? intval($this->firstElementNumber) . ',' : '') . (intval($this->iLimit + 1));
		}

		if ($id > 0) {
			$query_array = array(
				'SELECT' => 'DISTINCT tx_commerce_order_articles.order_id, delivery_table.order_id as order_number, tx_commerce_order_articles.article_type_uid, tx_commerce_order_articles.title as payment, delivery_table.title as delivery, tx_commerce_orders.uid,tx_commerce_orders.pid, tx_commerce_orders.crdate, tx_commerce_orders.tstamp, tx_commerce_orders.order_id, tx_commerce_orders.sum_price_gross, tt_address.tx_commerce_address_type_id, tt_address.company ,tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.email,tt_address.phone as phone_1, tt_address.mobile as phone_2,tx_commerce_orders.cu_iso_3_uid, tx_commerce_orders.tstamp, tx_commerce_orders.uid as articles, tx_commerce_orders.comment, tx_commerce_orders.internalcomment, tx_commerce_orders.order_type_uid as order_type_uid_noName, static_currencies.cu_iso_3',
				'FROM' => 'tx_commerce_orders,tt_address, tx_commerce_order_articles, tx_commerce_order_articles as delivery_table, static_currencies',
				'WHERE' => 'static_currencies.uid = tx_commerce_orders.cu_iso_3_uid and delivery_table.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.article_type_uid = ' . PAYMENTARTICLETYPE . ' AND delivery_table.article_type_uid = ' . DELIVERYARTICLETYPE . ' AND tx_commerce_orders.deleted = 0 and tx_commerce_orders.cust_deliveryaddress = tt_address.uid AND tx_commerce_orders.pid=' . $id . ' ' . $addWhere,
				'GROUPBY' => '',
				'ORDERBY' => $orderby,
				'sorting' => '',
				'LIMIT' => $limit,
			);
		} else {
			Tx_Commerce_Utility_FolderUtility::init_folders();

			/**
			 * @todo bitte aus der ext config nehmen, volker angefragt
			 */
				// Find the right pid for the Ordersfolder
			list($orderPid) = array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce'));

			$ret = Tx_Commerce_Utility_BackendUtility::getOrderFolderSelector($orderPid, PHP_INT_MAX);

			$list = array();
			foreach ($ret as $elements) {
				$list[] = $elements[1];
			}
			$list = implode(',', $list);

			$query_array = array(
				'SELECT' => 'DISTINCT tx_commerce_order_articles.order_id,delivery_table.order_id as order_number, tx_commerce_order_articles.article_type_uid, tx_commerce_order_articles.title as payment, delivery_table.title as delivery, tx_commerce_orders.uid,tx_commerce_orders.pid, tx_commerce_orders.crdate, tx_commerce_orders.tstamp, tx_commerce_orders.order_id, tx_commerce_orders.sum_price_gross, tt_address.tx_commerce_address_type_id, tt_address.company,tt_address.name,tt_address.surname, tt_address.address, tt_address.zip, tt_address.city, tt_address.email,tt_address.phone as phone_1, tt_address.mobile as phone_2,tx_commerce_orders.cu_iso_3_uid, tx_commerce_orders.tstamp, tx_commerce_orders.uid as articles, tx_commerce_orders.comment, tx_commerce_orders.internalcomment, tx_commerce_orders.order_type_uid as order_type_uid_noName, static_currencies.cu_iso_3',
				'FROM' => 'tx_commerce_orders,tt_address, tx_commerce_order_articles, tx_commerce_order_articles as delivery_table, static_currencies',
				'WHERE' => 'static_currencies.uid = tx_commerce_orders.cu_iso_3_uid and delivery_table.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.order_id = tx_commerce_orders.order_id AND tx_commerce_order_articles.article_type_uid = ' . PAYMENTARTICLETYPE . ' AND delivery_table.article_type_uid = ' . DELIVERYARTICLETYPE . ' AND tx_commerce_orders.deleted = 0 and tx_commerce_orders.cust_deliveryaddress = tt_address.uid AND tx_commerce_orders.pid in (' . $list . ') ' . $addWhere,
				'GROUPBY' => '',
				'ORDERBY' => $orderby,
				'sorting' => '',
				'LIMIT' => $limit,
			);
		}

			// get Module TSConfig
		$temp = t3lib_BEfunc::getModTSconfig($id, 'mod.commerce.orders');
		$moduleConfig = t3lib_BEfunc::implodeTSParams($temp['properties']);
		$delProdUid = $moduleConfig['delProdUid'];
		$payProdUid = $moduleConfig['payProdUid'];
		if ($delProdUid > 0) {
			$delArticles = Tx_Commerce_Utility_BackendUtility::getArticlesOfProductAsUidList($delProdUid);
			$delArticlesList = implode(',', $delArticles);

			if ($delArticlesList) {
				$query_array['WHERE'] .= ' AND delivery_table.article_uid in (' . $delArticlesList . ') ';
			}
		}

		if ($payProdUid > 0) {
			$payArticles = Tx_Commerce_Utility_BackendUtility::getArticlesOfProductAsUidList($payProdUid);
			$payArticlesList = implode(',', $payArticles);

			if ($payArticlesList) {
				$query_array['WHERE'] .= ' AND delivery_table.article_uid in (' . $payArticlesList . ') ';
			}
		}

		$this->dontShowClipControlPanels = 1;
		return $query_array;
	}

	/**
	 * Writes the top of the full listing
	 *
	 * @param array $row Current page record
	 * @return void (Adds content to internal variable, $this->HTMLcode)
	 */
	public function writeTop($row) {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Makes the code for the pageicon in the top
		$this->pageRow = $row;
		$this->counter++;
		$alttext = t3lib_BEfunc::getRecordIconAltText($row, 'pages');
		$iconImg = t3lib_iconWorks::getIconImage('pages', $row, $this->backPath, 'class="absmiddle" title="' . htmlspecialchars($alttext) . '"');

			// pseudo title column name
		$titleCol = 'test';
			// Setting the fields to display in the list (this is of course "pseudo fields" since this is the top!)
		$this->fieldArray = Array($titleCol, 'up');

			// Filling in the pseudo data array:
		$theData = Array();
		$theData[$titleCol] = $this->widthGif;

			// Get users permissions for this row:
		$localCalcPerms = $backendUser->calcPerms($row);

		$theData['up'] = array();

			// Initialize control panel for currect page ($this->id):
			// Some of the controls are added only if $this->id is set - since they make sense only on a real page, not root level.
		$theCtrlPanel = array();

			// If edit permissions are set (see class.t3lib_userauthgroup.php)
		if ($localCalcPerms & 2) {
				// Adding "New record" icon:
			if (!$GLOBALS['SOBE']->modTSconfig['properties']['noCreateRecordsLink']) {
				$theCtrlPanel[] = '<a href="#" onclick="' . htmlspecialchars('return jumpExt(\'db_new.php?id=' . $this->id . '\');') . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-new', array('title' => $language->getLL('newRecordGeneral', 1))) .
					'</a>';
			}

				// Adding "Hide/Unhide" icon:
			if ($this->id) {
					// @todo: change the return path
				if ($row['hidden']) {
					$params = '&data[pages][' . $row['uid'] . '][hidden]=0';
					$theCtrlPanel[] = '<a href="#" onclick="' .
						htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-edit-unhide', array('title' => $language->getLL('unHidePage', 1))) .
						'</a>';
				} else {
					$params = '&data[pages][' . $row['uid'] . '][hidden]=1';
					$theCtrlPanel[] = '<a href="#" onclick="' .
						htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-edit-hide', array('title' => $language->getLL('hidePage', 1))) .
						'</a>';
				}
			}
		}

			// "Paste into page" link:
		if (($localCalcPerms & 8) || ($localCalcPerms & 16)) {
			$elFromTable = $this->clipObj->elFromTable('');
			if (count($elFromTable)) {
				$theCtrlPanel[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $this->id)) . '" onclick="' .
					htmlspecialchars('return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into', $elFromTable)) . '">' .
					t3lib_iconWorks::getSpriteIcon(
						'actions-document-paste-into',
						array('title' => $language->getLL('clip_paste', 1))
					) . '</a>';
			}
		}

			// Finally, compile all elements of the control panel into table cells:
		if (count($theCtrlPanel)) {
			$theData['up'][] = '
				<!--
					Control panel for page
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-ctrltop">
					<tr>
						<td>' . implode('</td><td>', $theCtrlPanel) . '</td>
					</tr>
				</table>';
		}

			// Add "CSV" link, if a specific table is shown:
		if ($this->table) {
			$theData['up'][] = '<a href="' . htmlspecialchars($this->listURL() . '&csv=1') . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'mimetypes-text-csv',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.csv', 1))
				) . '</a>';
		}

			// Add "Export" link, if a specific table is shown:
		if ($this->table && t3lib_extMgm::isLoaded('impexp')) {
			$theData['up'][] = '<a href="' . htmlspecialchars($this->backPath . t3lib_extMgm::extRelPath('impexp') .
				'app/index.php?tx_impexp[action]=export&tx_impexp[list][]=' . rawurlencode($this->table . ':' . $this->id)) . '">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-document-export-t3d',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:rm.export', 1))
				) . '</a>';
		}

			// Add "refresh" link:
		$theData['up'][] = '<a href="' . htmlspecialchars($this->listURL()) . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-system-refresh', array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.reload', 1))) .
			'</a>';

			// Add icon with clickmenu, etc:
			// If there IS a real page...:
		if ($this->id) {
				// Setting title of page + the "Go up" link:
			$theData[$titleCol] .= '<br /><span title="' . htmlspecialchars($row['_thePathFull']) . '">' .
				htmlspecialchars(t3lib_div::fixed_lgd_cs($row['_thePath'], - $this->fixedL)) . '</span>';
			$theData['up'][] = '<a href="' . htmlspecialchars($this->listURL($row['pid'])) . '" onclick="setHighlight(' . $row['pid'] . ')">' .
				t3lib_iconWorks::getSpriteIcon(
					'actions-view-go-up',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', 1))
				) .
				'</a>';

				// Make Icon:
			$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', $this->id) : $iconImg;
			// On root-level of page tree:
		} else {
				// Setting title of root (sitename):
			$theData[$titleCol] .= '<br />' . htmlspecialchars(t3lib_div::fixed_lgd_cs($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], - $this->fixedL));

				// Make Icon:
			$theIcon = t3lib_iconWorks::getSpriteIcon('apps-pagetree-root');
		}

			// If there is a returnUrl given, add a back-link:
		if ($this->returnUrl) {
			$theData['up'][] = '<a href="' . htmlspecialchars(t3lib_div::linkThisUrl($this->returnUrl, array('id' => $this->id))) .
				'" class="typo3-goBack">'  .
				t3lib_iconWorks::getSpriteIcon(
					'actions-view-go-back',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.goBack', 1))
				) . '</a>';
		}

		$theData['up'][] = $this->additionalOutTop;
			// Finally, the "up" pseudo field is compiled into a table - has been accumulated in an array:
		$theData['up'] = '
			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>' . implode('</td><td>', $theData['up']) . '</td>
				</tr>
			</table>';

			// ... and the element row is created:
		$out = $this->addelement(1, $theIcon, $theData, '', $this->leftMargin);

			// ... and wrapped into a table and added to the internal ->HTMLcode variable:
		$this->HTMLcode .= '
			<!--
				Page header for db_list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-dblist-top">
				' . $out . '
			</table>';
	}

	/**
	 * @return void
	 */
	public function generateList() {
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

		t3lib_div::loadTCA('tx_commerce_orders');

			// Traverse the TCA table array:
		foreach ($GLOBALS['TCA'] as $tableName => $dummy) {
				// Checking if the table should be rendered:
				// Checks that we see only permitted/requested tables:
			if (
				(!$this->table || $tableName == $this->table)
				&& (!$this->tableList || t3lib_div::inList($this->tableList, $tableName))
				&& $backendUser->check('tables_select', $tableName)
			) {
					// Load full table definitions:
				t3lib_div::loadTCA($tableName);

					// iLimit is set depending on whether we're in single- or multi-table mode
				if ($this->table) {
					$this->iLimit = (isset($GLOBALS['TCA'][$tableName]['interface']['maxSingleDBListItems']) ?
						(int) $GLOBALS['TCA'][$tableName]['interface']['maxSingleDBListItems'] :
						$this->itemsLimitSingleTable);
				} else {
					$this->iLimit = (isset($GLOBALS['TCA'][$tableName]['interface']['maxDBListItems']) ?
						(int) $GLOBALS['TCA'][$tableName]['interface']['maxDBListItems'] :
						$this->itemsLimitPerTable);
				}
				if ($this->showLimit) {
					$this->iLimit = $this->showLimit;
				}
					// Finally, render the list:

				$this->myfields = array('order_type_uid_noName', 'order_id', 'tstamp', 'crdate', 'delivery', 'payment',
					'numarticles', 'sum_price_gross', 'cu_iso_3', 'company', 'surname', 'name', 'address', 'zip', 'city',
					'email', 'phone_1', 'phone_2', 'articles', 'order_number');

					// Check for order_number and order_title view
				if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['showArticleNumber'] == 1 &&
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['showArticleTitle'] == 1) {
					$this->myfields[] = 'article_number';
					$this->myfields[] = 'article_name';
				} elseif ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['showArticleNumber'] == 1) {
					$this->myfields[] = 'article_number';
				} elseif ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['showArticleTitle'] == 1) {
					$this->myfields[] = 'article_name';
				}

					// CSV Export
				if ($this->csvOutput) {
					$this->myfields = array('order_id', 'crdate', 'tstamp', 'delivery', 'payment', 'numarticles', 'sum_price_gross',
						'cu_iso_3', 'company', 'surname', 'name', 'address', 'zip', 'city', 'email', 'phone_1', 'phone_2', 'comment',
						'internalcomment', 'articles');
				}

				$this->HTMLcode .= $this->getTable($tableName, $this->id, implode(',', $this->myfields));
			}
		}
	}

	/**
	 * Wrapping input code in link to URL or email if $testString is either.
	 *
	 * @param string $code
	 * @param string $testString
	 * @return string Link-Wrapped $code value, if $testString was URL or email.
	 */
	protected function mylinkUrlMail($code,$testString) {
			// Check for URL:
		$schema = parse_url($testString);
		if ($schema['scheme'] && t3lib_div::inList('http,https,ftp', $schema['scheme'])) {
			return '<a href="' . htmlspecialchars($testString) . '" target="_blank">' . $code . '</a>';
		}

			// Check for email:
		if (t3lib_div::validEmail($testString)) {
			return '<a href="mailto:' . htmlspecialchars($testString) . '" target="_blank">' . $code . '</a>';
		}

			// Return if nothing else...
		return $code;
	}

	/**
	 * Rendering a single row for the list
	 *
	 * @param string $table Table name
	 * @param array $row Current record
	 * @param integer $cc Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param string $titleCol Table field (column) where header value is found
	 * @param string $thumbsCol Table field (column) where (possible) thumbnails can be found
	 * @param integer $indent Indent from left.
	 * @return string Table row for the element
	 * @access private
	 * @see getTable()
	 */
	public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$iOut = '';

		if (substr(TYPO3_version, 0, 3)  >= '4.0') {
				// In offline workspace, look for alternative record:
			t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace);
		}
			// Background color, if any:
		$row_bgColor =
			$this->alternateBgColors ?
			(($cc % 2) ? '' : ' bgcolor="' . t3lib_div::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4, 10, 10, 10) . '"') :
			'';

			// Overriding with versions background color if any:
		$row_bgColor = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : $row_bgColor;

			// Initialization
		$alttext = t3lib_BEfunc::getRecordIconAltText($row, $table);

			// Incr. counter.
		$this->counter++;

		$indentStyle = ($indent ? ' style="margin-left: ' . $indent . 'px;"' : '');
		$iconAttributes = 'title="' . htmlspecialchars($alttext) . '"' . $indentStyle;

			// Icon for order comment and delivery address
		if ($row['comment'] != '' && $row['internalcomment'] != '') {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add_user_int.gif',
					$iconAttributes
				);
			} else {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_user_int.gif',
					$iconAttributes
				);
			}
		} elseif ($row['comment'] != '') {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add_user.gif',
					$iconAttributes
				);
			} else {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_user.gif',
					$iconAttributes
				);
			}
		} elseif ($row['internalcomment'] != '') {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add_int.gif',
					$iconAttributes
				);
			} else {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_int.gif',
					$iconAttributes
				);
			}
		} else {
			if ($row['tx_commerce_address_type_id'] == 2) {
				$iconImg = '<img' . t3lib_iconWorks::skinImg(
					$this->backPath,
					PATH_TXCOMMERCE_REL . 'Resources/Public/Icons/Table/orders_add.gif',
					$iconAttributes
				);
			} else {
				$iconImg = t3lib_iconWorks::getIconImage($table, $row, $this->backPath, $iconAttributes);
			}
		}

		$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;

		$extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];
			// Preparing and getting the data-array
		$theData = Array();

		foreach ($this->fieldArray as $fCol) {
			if ($fCol == 'pid') {
				$theData[$fCol] = $row[$fCol];
			} elseif ($fCol == 'sum_price_gross') {
				if ($this->csvOutput) {
					$row[$fCol] = $row[$fCol] / 100;
				} else {
					$theData[$fCol] = tx_moneylib::format($row[$fCol], $row['cu_iso_3'], FALSE);
				}
			} elseif ($fCol == 'crdate') {
				$theData[$fCol] = t3lib_BEfunc::date($row[$fCol]);

				$row[$fCol] = t3lib_BEfunc::date($row[$fCol]);
			} elseif ($fCol == 'tstamp') {
				$theData[$fCol] = t3lib_BEfunc::date($row[$fCol]);
				$row[$fCol] = t3lib_BEfunc::date($row[$fCol]);
			} elseif ($fCol == 'articles') {
				$articleNumber = array();
				$articleName = array();

				$res_articles = $database->exec_SELECTquery(
					'article_number, title, order_uid',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid']
				);
				$articles = array();
				while (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$articles[] = $lokalRow['article_number'] . ':' . $lokalRow['title'];
					$articleNumber[] = $lokalRow['article_number'];
					$articleName[] = $lokalRow['title'];
				}

				if ($this->csvOutput) {
					$theData[$fCol] = implode(',', $articles);
					$row[$fCol]  = implode(',', $articles);
				} else {
					$theData[$fCol] = '<input type="checkbox" name="orderUid[]" value="' . $row['uid'] . '">';
				}
			} elseif ($fCol == 'numarticles') {
				$res_articles = $database->exec_SELECTquery(
					'sum(amount) anzahl',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid'] . ' and article_type_uid =' . NORMALARTICLETYPE
				);
				if (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$theData[$fCol] = $lokalRow['anzahl'];
					$row[$fCol]  = $lokalRow['anzahl'];
				}
			} elseif ($fCol == 'article_number') {
				$articleNumber = array();

				$res_articles = $database->exec_SELECTquery(
					'article_number',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid'] . ' and article_type_uid =' . NORMALARTICLETYPE
				);
				while (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$articleNumber[] = $lokalRow['article_number'];
					/**
					 * @TODO: implement default value, if number is not defined
					 */
				}
				$theData[$fCol] = implode(',', $articleNumber);
			} elseif ($fCol == 'article_name') {
				$articleName = array();

				$res_articles = $database->exec_SELECTquery(
					'title',
					'tx_commerce_order_articles',
					'order_uid = ' . (int) $row['uid'] . ' and article_type_uid =' . NORMALARTICLETYPE
				);
				while (($lokalRow = $database->sql_fetch_assoc($res_articles))) {
					$articleName[] = $lokalRow['title'];
					/**
					 * @TODO: implement default value, if title is not defined
					 */
				}
				$theData[$fCol] = implode(',', $articleName);
			} elseif ($fCol == 'order_type_uid_noName') {
				$res_type = $database->exec_SELECTquery(
					'*',
					'tx_commerce_order_types',
					'uid = ' . (int) $row['order_type_uid_noName']
				);
				while (($localRow = $database->sql_fetch_assoc($res_type))) {
					if ($localRow['icon']) {
						$filepath = $this->backPath . $GLOBALS['TCA']['tx_commerce_order_types']['columns']['icon']['config']['uploadfolder'] . '/' . $localRow['icon'];

						$theData[$fCol] = '<img' . t3lib_iconWorks::skinImg(
							$this->backPath,
							$filepath,
							'title="' . htmlspecialchars($localRow['title']) . '"' . $indentStyle
						);
					} else {
						$theData[$fCol] = $localRow['title'];
					}
				}
			} elseif ($fCol == '_PATH_') {
				$theData[$fCol] = $this->recPath($row['pid']);
			} elseif ($fCol == '_CONTROL_') {
				$theData[$fCol] = $this->makeControl($table, $row);
			} elseif ($fCol == '_CLIPBOARD_') {
				$theData[$fCol] = $this->makeClip($table, $row);
			} elseif ($fCol == '_LOCALIZATION_') {
				list($lC1, $lC2) = $this->makeLocalizationPanel($table, $row);
				$theData[$fCol] = $lC1;
				$theData[$fCol . 'b'] = $lC2;
			} elseif ($fCol == '_LOCALIZATION_b') {
				// Do nothing, has been done above.
			} elseif ($fCol == 'order_id') {
				$theData[$fCol] = $row[$fCol];
			} else {
				/**
				 * Use own method, if typo3 4.0.0 is not installed
				 */
				if (substr(TYPO3_version, 0, 3) >= '4.0') {
					$theData[$fCol] = $this->linkUrlMail(htmlspecialchars(t3lib_BEfunc::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'])), $row[$fCol]);
				} else {
					$theData[$fCol] = $this->mylinkUrlMail(htmlspecialchars(t3lib_BEfunc::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'])), $row[$fCol]);
				}
			}
		}

			// Add row to CSV list:
		if ($this->csvOutput) {
				// Charset Conversion
			/** @var t3lib_cs $csObj */
			$csObj = t3lib_div::makeInstance('t3lib_cs');
			$csObj->initCharset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);

			if (!$extConf['BECSVCharset']) {
				$extConf['BECSVCharset'] = 'iso-8859-1';
			}
			$csObj->initCharset($extConf['BECSVCharset']);
			$csObj->convArray($row, $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'], $extConf['BECSVCharset']);
			$this->addToCSV($row, $table);
		}

			// Create element in table cells:
		$iOut .= $this->addelement(1, $theIcon, $theData, $row_bgColor);
			// Render thumbsnails if a thumbnail column exists and there is content in it:
		if ($this->thumbs && trim($row[$thumbsCol])) {
			$iOut .= $this->addelement(4, '', array($titleCol => $this->thumbCode($row, $table, $thumbsCol)), $row_bgColor);
		}

			// Finally, return table row element:
		return $iOut;
	}

	/**
	 * Rendering the header row for a table
	 *
	 * @param string $table Table name
	 * @param array $currentIdList Array of the currectly displayed uids of the table
	 * @return string Header table row
	 * @access private
	 */
	public function renderListHeader($table, $currentIdList) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

			// Init:
		$theData = Array();

			// Traverse the fields:
		foreach ($this->fieldArray as $fCol) {
			switch ((string) $fCol) {
					// Regular fields header:
				default:
					$theData[$fCol] = '';

					if ($this->table && is_array($currentIdList)) {
							// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol] .= '<a href="' . htmlspecialchars($this->listURL('', -1) . '&duplicateField=' . $fCol) . '">' .
								t3lib_iconWorks::getSpriteIcon(
									'actions-document-duplicates-select',
									array('title' => $language->getLL('clip_duplicates', 1))
								) . '</a>';
						}
					}

					/**
					 * Modified from this point to use relationla table queris
					 */
					$tables = array('tt_address', 'tx_commerce_orders');
					$temp_data = '';
					if ($language->getLL($fCol)) {
						foreach ($tables as $table) {
							$temp_data = $this->addSortLink($language->getLL($fCol), $fCol, $table);
						}
					} else {
						foreach ($tables as $work_table) {
							if ($GLOBALS['TCA'][$work_table]['columns'][$fCol]) {
								$temp_data = $this->addSortLink($language->sL(t3lib_BEfunc::getItemLabel($work_table, $fCol, '<i>[|]</i>')), $fCol, $table);
							}
						}
					}
					if ($temp_data) {
							// Only if we have a entry in locallang
						$theData[$fCol] = $temp_data;
					} else {
							// Handling for
							// Elements in the special Locallang file inside of mod_orders
						$theData[$fCol] = $this->addSortLink('<i>' . $fCol . '</i>', $fCol, $table);
					}
				break;
			}
		}

			// Create and return header table row:
		return $this->addelement(1, '', $theData, ' class="c-headLine"', '');
	}

	/**
	 * @param string $table
	 * @param integer $id
	 * @param string $rowlist
	 * @return string
	 */
	public function getTable($table, $id, $rowlist) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];

			// Loading all TCA details for this table:
		t3lib_div::loadTCA('tx_commerce_order_types');
			// Init
		$addWhere = '';
		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField']
			&& $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
			&& !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray = array();
			// Add title column
		$this->fieldArray[] = $titleCol;

		if ($this->localizationView && $l10nEnabled) {
			$this->fieldArray[] = '_LOCALIZATION_';
			$addWhere .= ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '<=0';
		}
		if (!t3lib_div::inList($rowlist, '_CONTROL_')) {
			// $this->fieldArray[] = '_CONTROL_';
		}
		if ($this->showClipboard) {
			$this->fieldArray[] = '_CLIPBOARD_';
		}
		if ($this->searchLevels) {
			$this->fieldArray[] = '_PATH_';
		}
			// Cleaning up:
		$this->fieldArray = array_unique(array_merge($this->fieldArray, t3lib_div::trimExplode(',', $rowlist, 1)));
		if ($this->noControlPanels) {
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}

			// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
			// adding column for thumbnails
		if ($thumbsCol) {
			$selectFields[] = $thumbsCol;
		}
		if ($table == 'pages') {
			if (t3lib_extMgm::isLoaded('cms')) {
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
			}
			$selectFields[] = 'doktype';
		}
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
			$selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['type']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['type'];
		}
		if ($this->onlyUser) {
			$addWhere .= ' AND cust_fe_user = \'' . $this->onlyUser . '\' ';
		}

		if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['versioning']) {
			$selectFields[] = 't3ver_id';
		}
		if ($l10nEnabled) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
			$selectFields = array_merge($selectFields, t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1));
		}

			// Unique list!
		$selectFields = array_unique($selectFields);
			// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selectFields = array_intersect($selectFields, $this->makeFieldList($table, 1));
			// implode it into a list of fields for the SQL-statement.
		$selFieldList = implode(',', $selectFields);

			// Create the SQL query for selecting the elements in the listing:
			// (API function from class.db_list.inc)
		$queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
			// Finding the total amount of records on the page (API function from class.db_list.inc)
		$this->setTotalItems($queryParts);

			// Init:
		$dbCount = 0;
		$out = '';

			// If the count query returned any number of records, we perform the real query, selecting records.
		$result = FALSE;
		if ($this->totalItems) {
			$result = $database->exec_SELECT_queryArray($queryParts);
			$dbCount = $database->sql_num_rows($result);
		}
		$LOISmode = $this->listOnlyInSingleTableMode && !$this->table;

			// If any records was selected, render the list:
		if ($dbCount) {
				// Half line is drawn between tables:
			if (!$LOISmode) {
				$theData = Array();
				if (!$this->table && !$rowlist) {
					$theData[$titleCol] = '<img src="/typo3/clear.gif" width="' .
						($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] ? '230' : '350') . '" height="1" alt="" />';
				}
				$out .= $this->addelement(0, '', $theData, '', $this->leftMargin);
			}

				// Header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView) {
				$theData[$titleCol] = '<span class="c-table">' . $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], 1) .
					'</span> (' . $this->totalItems . ')';
			} else {
				$title = $language->getLL(!$this->table ? 'expandView' : 'contractView', 1);
				$icon = t3lib_iconWorks::getSpriteIcon('actions-view-table-' . ($this->table ? 'collapse' : 'expand'), array('title' => $title));
				$theData[$titleCol] = $this->linkWrapTable(
					$table,
					'<span class="c-table">' . $language->sL($GLOBALS['TCA'][$table]['ctrl']['title'], 1) . '</span> (' .
					$this->totalItems . ') ' . $icon
				);
			}

				// CSH:
			$theData[$titleCol] .= t3lib_BEfunc::cshItem($table, '', $this->backPath, '', FALSE, 'margin-bottom:0px; white-space: normal;');

			if ($LOISmode) {
				$out .= '
					<tr>
						<td class="c-headLineTable" style="width:95%;"' . $theData[$titleCol] . '</td>
					</tr>';

				if ($backendUser->uc['edit_showFieldHelp']) {
					$language->loadSingleTableDescription($table);
					if (isset($GLOBALS['TCA_DESCR'][$table]['columns'][''])) {
						$out .= '
					<tr>
						<td class="c-tableDescription">' . t3lib_BEfunc::helpTextIcon(
							$table,
							'',
							$this->backPath,
							TRUE
						) . $GLOBALS['TCA_DESCR'][$table]['columns']['']['description'] . '</td>
					</tr>';
					}
				}
			} else {
				$theUpIcon = ($table == 'pages' && $this->id && isset($this->pageRow['pid'])) ?
					'<a href="' . htmlspecialchars($this->listURL($this->pageRow['pid'])) . '">' .
						t3lib_iconWorks::getSpriteIcon(
							'actions-view-go-up',
							array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', 1))
						) . '</a>' :
					'';
				$out .= $this->addelement(1, $theUpIcon, $theData, ' class="c-headLineTable"', '');
			}

			$iOut = '';
			if (!$LOISmode) {
					// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;
					// Accumulate rows here
				$accRows = array();
				while ($row = $database->sql_fetch_assoc($result)) {
					$accRows[] = $row;
					$currentIdList[] = $row['uid'];
					if ($doSort) {
						if ($prevUid) {
							$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
							$this->currentTable['next'][$prevUid] = '-' . $row['uid'];
							$this->currentTable['prevUid'][$row['uid']] = $prevUid;
						}
						$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? - $prevUid : $row['pid'];
						$prevUid = $row['uid'];
					}
				}
				$database->sql_free_result($result);

					// CSV initiated
				if ($this->csvOutput) {
					$this->initCSV();
				}

					// Render items:
				$this->CBnames = array();
				$this->duplicateStack = array();
				$this->eCounter = $this->firstElementNumber;

				$iOut = '';
				$cc = 0;
				foreach ($accRows as $row) {
						// Forward/Backwards navigation links:
					list($flag,$code) = $this->fwd_rwd_nav($table);
					$iOut .= $code;

						// If render item, increment counter and call function
					if ($flag) {
						$cc++;
						if (!$this->csvOutput) {
							$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
							$row[$titleCol] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnCLick($params, $GLOBALS['BACK_PATH'])) .
								'">' . $row[$titleCol] . '</a>';
						}
						$iOut .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
							// If localization view is enabled it means that the selected records are either default or All
							// language and here we will not select translations which point to the main record:
						if ($this->localizationView && $l10nEnabled) {
								// Look for translations of this record:
							$translations = $database->exec_SELECTgetRows(
								$selFieldList,
								$table,
								'pid=' . $row['pid'] . ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . ' > 0' .
									' AND ' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . '=' . (int) $row['uid'] .
									t3lib_BEfunc::deleteClause($table)
							);

								// For each available translation, render the record:
							foreach ($translations as $lRow) {
								$iOut .= $this->renderListRow($table, $lRow, $cc, $titleCol, $thumbsCol, 18);
							}
						}
					}

						// Counter of total rows incremented:
					$this->eCounter++;
				}

					// The header row for the table is now created:
				$out .= $this->renderListHeader($table, $currentIdList);
			}

				// The list of records is added after the header:
			$out .= $iOut;

				// ... and it is all wrapped in a table:
			$out = '
				<!--
					DB listing of elements:	"' . htmlspecialchars($table) . '"
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist' . ($LOISmode ? ' typo3-dblist-overview' : '') . '">
					' . $out . '
				';
			$out .= '
					<tr>
						<td class="c-headLineTable" style="width:95%;"></td>';
			$colspan = (count($this->myfields) + 2);
			$out .= '	<td class="c-headLineTable" style="width:95%;" colspan="' . $colspan . '" align="right">';

				// Build the selector
			/**
			 * Query the table to build dropdown list
			 */
			$myPid = t3lib_div::_GP('id');
			if (!empty($myPid)) {
				$resParentes = $database->exec_SELECTquery(
					'pid',
					'pages',
					'uid = ' . $myPid . ' ' .
						t3lib_BEfunc::deleteClause($GLOBALS['TCA']['tx_commerce_orders']['columns']['newpid']['config']['foreign_table'])
				);
				if ($rowParentes = $database->sql_fetch_assoc($resParentes)) {

				/**
				 * Get the poages below $order_pid
				 */
				list($orderPid) = array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce'));
				$ret = Tx_Commerce_Utility_BackendUtility::getOrderFolderSelector($orderPid, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['OrderFolderRecursiveLevel']);
				$out .= $language->getLL('moveorderto');
				$out .= '<select name="modeDestUid" size="1">
					<option value="" selected="selected">--------------------</option>';
				foreach ($ret as $displayArray) {
					$out .= '<option value="' . $displayArray[1] . '">' . $displayArray[0] . '</option>';
				}

				$out .= '</select>
					<input type="submit" name="OK" value="ok">';
			}
		}
		$out .= '</tr>';
			$out .= '</table>';
				// Output csv if...
				// This ends the page with exit.
			if ($this->csvOutput) {
				$this->outputCSV($table);
			}
		}

			// Return content:
		return $out;
	}

	/**
	 * @todo fix that this can get removed
	 * @NOTE: Since Typo3 4.5 we can't use listURL from parent class ("class.db_list.inc" - class recordList) anymore. It would lead to wrong url linking to web_list.
	 * This is just a copy of function listURL from Typo3 4.2
	 *
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returlUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
	 *
	 * @param string $altId Alternative id value. Enter blank string for the current id ($this->id)
	 * @param string $table Tablename to display. Enter "-1" for the current table.
	 * @param string $exclList Commalist of fields NOT to include ("sortField" or "sortRev")
	 * @return string URL
	 */
	public function listURL($altId = '', $table = -1, $exclList = '') {
		return $this->script .
			'?id=' . (strcmp($altId, '') ? $altId : $this->id) .
			'&table=' . rawurlencode($table == -1 ? $this->table : $table) .
			($this->thumbs ? '&imagemode=' . $this->thumbs : '') .
			($this->returnUrl ? '&returnUrl=' . rawurlencode(t3lib_div::sanitizeLocalUrl($this->returnUrl)) : '') .
			($this->searchString ? '&search_field=' . rawurlencode($this->searchString) : '') .
			($this->searchLevels ? '&search_levels=' . rawurlencode($this->searchLevels) : '') .
			($this->showLimit ? '&showLimit=' . rawurlencode($this->showLimit) : '') .
			($this->firstElementNumber ? '&pointer=' . rawurlencode($this->firstElementNumber) : '') .
			(!$exclList || !t3lib_div::inList($exclList, 'sortField') && $this->sortField ? '&sortField=' . rawurlencode($this->sortField) : '') .
			(!$exclList || !t3lib_div::inList($exclList, 'sortRev') && $this->sortRev ? '&sortRev=' . rawurlencode($this->sortRev) : '');
	}
}

class_alias('Tx_Commerce_ViewHelpers_OrderRecordlist', 'tx_commerce_order_localRecordlist');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/OrderRecordlist.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/ViewHelpers/OrderRecordlist.php']);
}

?>
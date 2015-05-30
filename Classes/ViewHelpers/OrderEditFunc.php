<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * User Class for displaying Orders
 */
class Tx_Commerce_ViewHelpers_OrderEditFunc {
	/**
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * @var string
	 */
	protected $returnUrl;

	/**
	 * @var string
	 */
	protected $cmd;

	/**
	 * @var string
	 */
	protected $cmd_table;

	/**
	 * @var array
	 */
	protected $MOD_SETTINGS = array();

	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $PA
	 * @return string HTML-Content
	 */
	public function articleOrderId($PA) {
		$content = htmlspecialchars($PA['itemFormElValue']);
		$content .= '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '">';
		return $content;
	}

	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $parameter
	 * @return string HTML-Content
	 */
	public function sumPriceGrossFormat($parameter) {
		$content = '<input type="text" disabled name="' . $parameter['itemFormElName'] . '" value="' .
			Tx_Commerce_ViewHelpers_Money::format($parameter['itemFormElValue'] / 100, '') . '">';
		return $content;
	}

	/**
	 * Oder Articles
	 * Renders the List of aricles
	 *
	 * @param array $PA
	 * @return string HTML-Content
	 */
	public function orderArticles($PA) {
		$database = $this->getDatabaseConnection();
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$content = '';
		$foreign_table = 'tx_commerce_order_articles';
		$table = 'tx_commerce_orders';

		/** @var smallDoc $doc */
		$doc = GeneralUtility::makeInstance('smallDoc');
		$doc->backPath = $GLOBALS['BACK_PATH'];

		/**
		 * GET Storage PID and order_id from Data
		 */
		$order_storage_pid = $PA['row']['pid'];
		$order_id = $PA['row']['order_id'];
		/**
		 * Select Order_articles
		 */

		/**
		 * @TODO TS config of fields in list
		 */
		$field_rows = array('amount', 'title', 'article_number', 'price_net', 'price_gross');

		/**
		 * Taken from class.db_list_extra.php
		 */
		$titleCol = $GLOBALS['TCA'][$foreign_table]['ctrl']['label'];

			// Check if Orders in this folder are editable
		$orderEditable = FALSE;
		$check_result = $database->exec_SELECTquery( 'tx_commerce_foldereditorder', 'pages', 'uid = ' . $order_storage_pid);
		if ($database->sql_num_rows($check_result) == 1) {
			if ($res_check = $database->sql_fetch_assoc($check_result)) {
				if ($res_check['tx_commerce_foldereditorder'] == 1) {
					$orderEditable = TRUE;
				}
			}
		}

			// Create the SQL query for selecting the elements in the listing:
		$result = $database->exec_SELECTquery(
			'*',
			$foreign_table,
			'pid = ' . $order_storage_pid . BackendUtility::deleteClause($foreign_table) .
			' AND order_id=\'' . $database->quoteStr($order_id, $foreign_table) . '\''
		);

		$dbCount = $database->sql_num_rows($result);

		$sum = array();
		$out = '';
		if ($dbCount) {
			/**
			* Only if we have a result
			*/
			$theData[$titleCol] = '<span class="c-table">' . $language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:order_view.items.article_list', 1) .
				'</span> (' . $dbCount . ')';

			$extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];

			if ($extConf['invoicePageID'] > 0) {
				$theData[$titleCol] .= ' <a href="../index.php?id=' . $extConf['invoicePageID'] . '&amp;tx_commerce_pi6[order_id]=' .
					$order_id . '&amp;type=' . $extConf['invoicePageType'] . '" target="_blank">' .
					$language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:order_view.items.print_invoice', 1) . ' *</a>';
			}

			$num_cols = count($field_rows);
			$out .= '
				<tr>
				<td class="c-headLineTable" style="width:95%;" colspan="' . ($num_cols + 1) . '"' . $theData[$titleCol] . '</td>
				</tr>';

			/**
			 * Header colum
			 */
			foreach ($field_rows as $field) {
				$out .= '<td class="c-headLineTable"><b>' .
					$language->sL(BackendUtility::getItemLabel($foreign_table, $field)) .
					'</b></td>';
			}

			$out .= '<td class="c-headLineTable"></td></tr>';

			/**
			 * @TODO: Switch to moneylib to use formating
			 */
			$cc = 0;
			$iOut = '';
			while ($row = $database->sql_fetch_assoc($result)) {
				$cc++;
				$sum['amount'] += $row['amount'];

				if ($PA['row']['pricefromnet'] == 1) {
					$row['price_net'] = $row['price_net'] * $row['amount'];
					$row['price_gross'] = $row['price_net'] * (1 + (((float) $row['tax']) / 100));
				} else {
					$row['price_gross'] = $row['price_gross'] * $row['amount'];
					$row['price_net'] = $row['price_gross'] / (1 + (((float) $row['tax']) / 100));
				}

				$sum['price_net_value'] += $row['price_net'] / 100;
				$sum['price_gross_value'] += $row['price_gross'] / 100;

				$row['price_net'] = Tx_Commerce_ViewHelpers_Money::format($row['price_net'] / 100, '');
				$row['price_gross'] = Tx_Commerce_ViewHelpers_Money::format($row['price_gross'] / 100, '');

				$row_bgColor = (($cc % 2) ? '' : ' bgcolor="'  . GeneralUtility::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4, + 10, + 10, + 10) . '"');

				/**
				 * Not very noice to render html_code directly
				 * @TODO change rendering html code here
				 */
				$iOut .= '<tr ' . $row_bgColor . '>';
				foreach ($field_rows as $field) {
					$wrap = array('', '');
					switch ($field) {
						case $titleCol:
							$iOut .= '<td>';
							if ($orderEditable) {
								$params = '&edit[' . $foreign_table . '][' . $row['uid'] . ']=edit';
								$wrap = array(
									'<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">',
									'</a>'
								);
							}
							break;

						case 'amount':
							$iOut .= '<td>';
							if ($orderEditable) {
								$params = '&edit[' . $foreign_table . '][' . $row['uid'] . ']=edit&columnsOnly=amount';
								$wrap = array(
									'<b><a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">' .
									IconUtility::getSpriteIcon('actions-document-open'),
									'</a></b>'
								);
							}
							break;

						case 'price_net':
						case 'price_gross':
							$iOut .= '<td style="text-align: right">';
							break;

						default:
							$iOut .= '<td>';
					}

					$iOut .= implode(BackendUtility::getProcessedValue($foreign_table, $field, $row[$field], 100), $wrap);
					$iOut .= '</td>';
				}

				/**
				 * Trash icon
				 */
				$iOut .= '<td></td>
					</tr>';
			}

			$out .= $iOut;
			/**
			 * Cerate the summ row
			 */
			$out .= '<tr>';
			$sum['price_net'] = Tx_Commerce_ViewHelpers_Money::format($sum['price_net_value'], '');
			$sum['price_gross'] = Tx_Commerce_ViewHelpers_Money::format($sum['price_gross_value'], '');

			foreach ($field_rows as $field) {
				switch ($field) {
					case 'price_net':
					case 'price_gross':
						$out .= '<td class="c-headLineTable" style="text-align: right"><b>';
						break;

					default:
						$out .= '<td class="c-headLineTable"><b>';
				}

				if ($sum[$field] > 0) {
					$out .= BackendUtility::getProcessedValueExtra($foreign_table, $field, $sum[$field], 100);
				}

				$out .= '</b></td>';
			}

			$out .= '<td class="c-headLineTable"></td></tr>';

			/**
			 * Always
			 * Update sum_price_net and sum_price_gross
			 * To Be shure everything is ok
			 */
			$values = array('sum_price_gross' => $sum['price_gross_value'] * 100, 'sum_price_net' => $sum['price_net_value'] * 100);
			$database->exec_UPDATEquery($table, 'order_id=\'' . $database->quoteStr($order_id, $foreign_table) . '\'', $values);
		}

		$out = '
			<!--
				DB listing of elements:	"' . htmlspecialchars($table) . '"
			-->
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
				' . $out . '
			</table>';
		$content .= $out;

		return $content;
	}

	/**
	 * Oder Status
	 * Selects only the oder folders from the pages List
	 *
	 * @param array $data
	 * @see Configuration/TCA/Orders.php
	 */
	public function orderStatus(&$data) {
		/**
		 * Ggf folder anlegen, wenn Sie nicht da sind
		 */
		Tx_Commerce_Utility_FolderUtility::initFolders();

		/**
		 * create a new data item array
		 */
		$data['items'] = array();

			// Find the right pid for the Ordersfolder
		list($orderPid) = array_unique(Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce'));

		/**
		 * Get the poages below $order_pid
		 */

		/**
		 * Check if the Current PID is below $orderPid,
		 * id is below orderPid we could use the parent of this record to build up the select Drop Down
		 * otherwhise use the default PID
		 */
		$myPID = $data['row']['pid'];

		$rootline = BackendUtility::BEgetRootLine($myPID);
		$rootlinePIDs = array();
		foreach ($rootline as $pages) {
			if (isset($pages['uid'])) {
				$rootlinePIDs[] = $pages['uid'];
			}
		}

		if (in_array($orderPid, $rootlinePIDs)) {
			$database = $this->getDatabaseConnection();

			$result = $database->exec_SELECTquery('pid ', 'pages', 'uid = ' . $myPID . BackendUtility::deleteClause('pages'), '', 'sorting' );
			if ($database->sql_num_rows($result) > 0) {
				while ($return_data = $database->sql_fetch_assoc($result)) {
					$orderPid = $return_data['pid'];
				}
				$database->sql_free_result($result);
			}
		}

		$data['items'] = Tx_Commerce_Utility_BackendUtility::getOrderFolderSelector(
			$orderPid,
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['OrderFolderRecursiveLevel']
		);
	}

	/**
	 * Invoice Adresss
	 * Renders the invoice adresss
	 *
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj
	 * @return string HTML-Content
	 */
	public function invoiceAddress($PA, $fobj) {
		/**
		 * Normal
		 */
		return $this->address($PA, $fobj, 'tt_address', $PA['itemFormElValue']);
	}

	/**
	 * Renders the crdate
	 *
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fObj
	 * @return string HTML-Content
	 */
	public function crdate($PA, $fObj) {
		$PA['itemFormElValue'] = date('d.m.y', $PA['itemFormElValue']);

		/**
		 * Normal
		 */
		return $fObj->getSingleField_typeNone_render(array(), $PA['itemFormElValue']);
	}

	/**
	 * Invoice Adresss
	 * Renders the invoice adresss
	 *
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj
	 * @return string HTML-Content
	 */
	public function deliveryAddress($PA, $fobj) {
		/**
		 * Normal
		 */
		return $this->address($PA, $fobj, 'tt_address', $PA['itemFormElValue']);
	}

	/**
	 * Address
	 * Renders an address block
	 *
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj
	 * @param string $table
	 * @param integer $uid Record UID
	 * @return string HTML-Content
	 */
	public function address($PA, $fobj, $table, $uid) {
		/**
		 * instatiate Template Class
		 * as this class is included via alt_doc we don't have to require template.php
		 * in fact an require would cause an error
		 *
		 * @var smallDoc $doc
		 */
		$doc = GeneralUtility::makeInstance('smallDoc');
		$doc->backPath = $GLOBALS['BACK_PATH'];

		/**
		 * Load the table TCA into local variable
		 */
		GeneralUtility::loadTCA($table);

		$content = '';

		/**
		 *
		 * Fist select Data from Database
		 *
		 */
		if ($data_row = BackendUtility::getRecord($table, $uid, 'uid,' . $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'])) {
			/**
			 * We should get just one Result
			 * So Render Result as $arr for template::table()
			 */

			/**
			 * Better formating via template class
			 */
			$content .= $doc->spacer(10);

			/**
			 * TYPO3 Core API's Page 63
			 */
			$params = '&edit[' . $table . '][' . $uid . ']=edit';

			$wrap_the_header = array('<b><a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">', '</a></b>');
			$content .= $doc->getHeader($table, $data_row, 'Local Lang definition is missing', 1, $wrap_the_header);
			$content .= $doc->spacer(10);
			$display_arr = array();

			/** @var language $language */
			$language = $GLOBALS['LANG'];

			foreach ($data_row as $key => $value) {
				/**
				 * Walk through rowset,
				 * get TCA values
				 * and LL Names
				 */
				if (GeneralUtility::inList($GLOBALS['TCA'][$table]['interface']['showRecordFieldList'], $key)) {
					/**
					 * Get The label
					 */
					$local_row_name = $language->sL(BackendUtility::getItemLabel($table, $key));
					$display_arr[$key] = array($local_row_name, htmlspecialchars($value));
				}
			}

			$tableLayout = array (
				'table' =>  array('<table>', '</table>'),
				'defRowEven' => array (
					'defCol' => array('<td class="bgColor5">', '</td>')
				),
				'defRowOdd' => array (
					'defCol' => array('<td class="bgColor4">', '</td>')
				)
			);
			$content .= $doc->table($display_arr, $tableLayout);
		}

		$content .= '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '">';
		return $content;
	}

	/**
	 * @return string
	 */
	public function feUserOrders() {
		$backendUser = $this->getBackendUser();

		/** @var Tx_Commerce_ViewHelpers_OrderRecordlist $dblist */
		$dblist = GeneralUtility::makeInstance('Tx_Commerce_ViewHelpers_OrderRecordlist');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->script = 'index.php';
		$dblist->calcPerms = $backendUser->calcPerms($this->pageinfo);
		$dblist->thumbs = $GLOBALS['BE_USER']->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;
		$dblist->allFields = 1;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 0;

			// CB is the clipboard command array
		$CB = GeneralUtility::_GET('CB');
		if ($this->cmd == 'setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge(GeneralUtility::_POST('CBH'), GeneralUtility::_POST('CBC')), $this->cmd_table);
		}
		$dblist->start(NULL, 'tx_commerce_orders', 0);

		$dblist->generateList();
		$dblist->writeBottom();

		return $dblist->HTMLcode;
	}


	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $PA
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::articleOrderId instead
	 */
	public function article_order_id($PA) {
		GeneralUtility::logDeprecatedFunction();
		return $this->articleOrderId($PA);
	}

	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $PA
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::sumPriceGrossFormat instead
	 */
	public function sum_price_gross_format($PA) {
		GeneralUtility::logDeprecatedFunction();
		return $this->sumPriceGrossFormat($PA);
	}

	/**
	 * Oder Articles
	 * Renders the List of aricles
	 *
	 * @param array $PA
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::orderArticles instead
	 */
	public function order_articles($PA) {
		GeneralUtility::logDeprecatedFunction();
		return $this->orderArticles($PA);
	}

	/**
	 * Oder Status
	 * Selects only the oder folders from the pages List
	 *
	 * @param array $data
	 * @see Configuration/TCA/Orders.php
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::orderStatus instead
	 */
	public function order_status(&$data) {
		GeneralUtility::logDeprecatedFunction();
		$this->orderStatus($data);
	}

	/**
	 * Invoice Adresss
	 * Renders the invoice adresss
	 *
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::invoiceAddress instead
	 */
	public function invoice_adress($PA, $fobj) {
		GeneralUtility::logDeprecatedFunction();
		return $this->invoiceAddress($PA, $fobj);
	}

	/**
	 * Invoice Adresss
	 * Renders the invoice adresss
	 *
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::deliveryAddress instead
	 */
	public function delivery_adress($PA, $fobj) {
		GeneralUtility::logDeprecatedFunction();
		return $this->deliveryAddress($PA, $fobj);
	}

	/**
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj
	 * @param string $table
	 * @param integer $uid
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::getAttributes instead
	 */
	public function adress($PA, $fobj, $table, $uid) {
		GeneralUtility::logDeprecatedFunction();
		return $this->address($PA, $fobj, $table, $uid);
	}

	/**
	 * @param array $PA
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::feUserOrders instead
	 */
	public function fe_user_orders($PA) {
		GeneralUtility::logDeprecatedFunction();
		return $this->feUserOrders($PA);
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
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

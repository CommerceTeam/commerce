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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * User Class for displaying Orders
 *
 * Class Tx_Commerce_ViewHelpers_OrderEditFunc
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_ViewHelpers_OrderEditFunc {
	/**
	 * Page info
	 *
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * Return url
	 *
	 * @var string
	 */
	protected $returnUrl;

	/**
	 * Commands
	 *
	 * @var string
	 */
	protected $cmd;

	/**
	 * Command table
	 *
	 * @var string
	 */
	protected $cmd_table;

	/**
	 * Module settings
	 *
	 * @var array
	 */
	protected $MOD_SETTINGS = array();

	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string HTML-Content
	 */
	public function articleOrderId(array $parameter) {
		$content = htmlspecialchars($parameter['itemFormElValue']);
		$content .= '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
			htmlspecialchars($parameter['itemFormElValue']) . '">';
		return $content;
	}

	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string HTML-Content
	 */
	public function sumPriceGrossFormat(array $parameter) {
		$content = '<input type="text" disabled name="' . $parameter['itemFormElName'] . '" value="' .
			Tx_Commerce_ViewHelpers_Money::format(intval(round($parameter['itemFormElValue'])), '') . '">';
		return $content;
	}

	/**
	 * Oder Articles
	 * Renders the List of aricles
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string HTML-Content
	 */
	public function orderArticles(array $parameter) {
		$database = $this->getDatabaseConnection();
		$language = $this->getLanguageService();

		$content = '';
		$foreignTable = 'tx_commerce_order_articles';
		$table = 'tx_commerce_orders';

		/**
		 * Document template
		 *
		 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
		 */
		$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$doc->backPath = $GLOBALS['BACK_PATH'];

		/**
		 * GET Storage PID and order_id from Data
		 */
		$orderStoragePid = $parameter['row']['pid'];
		$orderId = $parameter['row']['order_id'];

		/**
		 * Select Order_articles
		 */

		// @todo TS config of fields in list
		$fieldRows = array('amount', 'title', 'article_number', 'price_net', 'price_gross');

		/**
		 * Taken from class.db_list_extra.php
		 */
		$titleCol = $GLOBALS['TCA'][$foreignTable]['ctrl']['label'];

		// Check if Orders in this folder are editable
		$orderEditable = FALSE;
		$checkResult = $database->exec_SELECTquery('tx_commerce_foldereditorder', 'pages', 'uid = ' . $orderStoragePid);
		if ($database->sql_num_rows($checkResult) == 1) {
			if (($checkRow = $database->sql_fetch_assoc($checkResult))) {
				if ($checkRow['tx_commerce_foldereditorder'] == 1) {
					$orderEditable = TRUE;
				}
			}
		}

		// Create the SQL query for selecting the elements in the listing:
		$result = $database->exec_SELECTquery(
			'*',
			$foreignTable,
			'pid = ' . $orderStoragePid . BackendUtility::deleteClause($foreignTable) .
				' AND order_id = \'' . $database->quoteStr($orderId, $foreignTable) . '\''
		);

		$dbCount = $database->sql_num_rows($result);

		$sum = array();
		$out = '';
		if ($dbCount) {
			/**
			* Only if we have a result
			*/
			$theData[$titleCol] = '<span class="c-table">' .
				$language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:order_view.items.article_list', 1) .
				'</span> (' . $dbCount . ')';

			$extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];

			if ($extConf['invoicePageID'] > 0) {
				$theData[$titleCol] .= '<a href="../index.php?id=' . $extConf['invoicePageID'] . '&amp;tx_commerce_pi6[order_id]=' .
					$orderId . '&amp;type=' . $extConf['invoicePageType'] . '" target="_blank">' .
					$language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:order_view.items.print_invoice', 1) . ' *</a>';
			}

			$colCount = count($fieldRows);
			$out .= '
				<tr>
					<td class="c-headLineTable" style="width: 95%;" colspan="' . ($colCount + 1) . '">' . $theData[$titleCol] . '</td>
				</tr>';

			/**
			 * Header colum
			 */
			foreach ($fieldRows as $field) {
				$out .= '<td class="c-headLineTable"><b>' .
						$language->sL(BackendUtility::getItemLabel($foreignTable, $field)) .
					'</b></td>';
			}

			$out .= '<td class="c-headLineTable"></td></tr>';

			// @todo Switch to moneylib to use formating
			$cc = 0;
			$iOut = '';
			while (($row = $database->sql_fetch_assoc($result))) {
				$cc++;
				$sum['amount'] += $row['amount'];

				if ($parameter['row']['pricefromnet'] == 1) {
					$row['price_net'] = $row['price_net'] * $row['amount'];
					$row['price_gross'] = $row['price_net'] * (1 + (((float) $row['tax']) / 100));
				} else {
					$row['price_gross'] = $row['price_gross'] * $row['amount'];
					$row['price_net'] = $row['price_gross'] / (1 + (((float) $row['tax']) / 100));
				}

				$sum['price_net_value'] += $row['price_net'];
				$sum['price_gross_value'] += $row['price_gross'];

				$row['price_net'] = Tx_Commerce_ViewHelpers_Money::format(intval(round($row['price_net'])), '');
				$row['price_gross'] = Tx_Commerce_ViewHelpers_Money::format(intval(round($row['price_gross'])), '');

				$rowBgColor = ($cc % 2 ? '' : ' bgcolor="' .
					GeneralUtility::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4, + 10, + 10, + 10) . '"');

				/**
				 * Not very noice to render html_code directly
				 * @todo change rendering html code here
				 */
				$iOut .= '<tr ' . $rowBgColor . '>';
				foreach ($fieldRows as $field) {
					$wrap = array('', '');
					switch ($field) {
						case $titleCol:
							$iOut .= '<td>';
							if ($orderEditable) {
								$params = '&edit[' . $foreignTable . '][' . $row['uid'] . ']=edit';
								$wrap = array(
									'<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">',
									'</a>'
								);
							}
							break;

						case 'amount':
							$iOut .= '<td>';
							if ($orderEditable) {
								$params = '&edit[' . $foreignTable . '][' . $row['uid'] . ']=edit&columnsOnly=amount';
								$onclickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '"';
								$wrap = array(
									'<b><a href="#" ' . $onclickAction . '>' . IconUtility::getSpriteIcon('actions-document-open'),
									'</a></b>'
								);
							}
							break;

						case 'price_net':
							// fall through
						case 'price_gross':
							$iOut .= '<td style="text-align: right">';
							break;

						default:
							$iOut .= '<td>';
					}

					$iOut .= implode(BackendUtility::getProcessedValue($foreignTable, $field, $row[$field], 100), $wrap);
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
			 * Cerate the sum row
			 */
			$out .= '<tr>';
			$sum['price_net'] = Tx_Commerce_ViewHelpers_Money::format(intval(round($sum['price_net_value'])), '');
			$sum['price_gross'] = Tx_Commerce_ViewHelpers_Money::format(intval(round($sum['price_gross_value'])), '');

			foreach ($fieldRows as $field) {
				switch ($field) {
					case 'price_net':
						// fall through
					case 'price_gross':
						$out .= '<td class="c-headLineTable" style="text-align: right"><b>';
						break;

					default:
						$out .= '<td class="c-headLineTable"><b>';
				}

				if ($sum[$field] != '') {
					$out .= BackendUtility::getProcessedValueExtra($foreignTable, $field, $sum[$field], 100);
				}

				$out .= '</b></td>';
			}

			$out .= '<td class="c-headLineTable"></td></tr>';
		}

		$out = '
			<!--
				DB listing of elements: "' . htmlspecialchars($table) . '"
			-->
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
				' . $out . '
			</table>';
		$content .= $out;

		return $content;
	}

	/**
	 * Order Status
	 * Selects only the order folders from the pages List
	 *
	 * @param array $data Data
	 *
	 * @return void
	 */
	public function orderStatus(array &$data) {
		/**
		 * Create folder if not existing
		 */
		Tx_Commerce_Utility_FolderUtility::initFolders();

		/**
		 * Create a new data item array
		 */
		$data['items'] = array();

		// Find the right pid for the Ordersfolder
		list($orderPid) = array_unique(
			Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce')
		);

		/**
		 * Get the pages below $order_pid
		 */

		/**
		 * Check if the Current PID is below $orderPid,
		 * id is below orderPid we could use the parent of
		 * this record to build up the select Drop Down
		 * otherwhise use the default PID
		 */
		$localOrderPid = $data['row']['pid'];

		$rootline = BackendUtility::BEgetRootLine($localOrderPid);
		$rootlinePids = array();
		foreach ($rootline as $pages) {
			if (isset($pages['uid'])) {
				$rootlinePids[] = $pages['uid'];
			}
		}

		if (in_array($orderPid, $rootlinePids)) {
			$database = $this->getDatabaseConnection();

			$result = $database->exec_SELECTquery(
				'pid ',
				'pages',
				'uid = ' . $localOrderPid . BackendUtility::deleteClause('pages'),
				'',
				'sorting'
			);

			if ($database->sql_num_rows($result) > 0) {
				while (($row = $database->sql_fetch_assoc($result))) {
					$orderPid = $row['pid'];
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
	 * @param array $parameter Parameter
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj Form engine
	 *
	 * @return string HTML-Content
	 */
	public function invoiceAddress(array $parameter, \TYPO3\CMS\Backend\Form\FormEngine $fobj) {
		return $this->address($parameter, $fobj, 'tt_address', $parameter['itemFormElValue']);
	}

	/**
	 * Renders the crdate
	 *
	 * @param array $parameter Parameter
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fObj Form engine
	 *
	 * @return string HTML-Content
	 */
	public function crdate(array $parameter, \TYPO3\CMS\Backend\Form\FormEngine $fObj) {
		$parameter['itemFormElValue'] = date('d.m.y', $parameter['itemFormElValue']);

		return $fObj->getSingleField_typeNone_render(array(), $parameter['itemFormElValue']);
	}

	/**
	 * Invoice Adresss
	 * Renders the invoice adresss
	 *
	 * @param array $parameter Parameter
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj Form engine
	 *
	 * @return string HTML-Content
	 */
	public function deliveryAddress(array $parameter, \TYPO3\CMS\Backend\Form\FormEngine $fobj) {
		return $this->address($parameter, $fobj, 'tt_address', $parameter['itemFormElValue']);
	}

	/**
	 * Address
	 * Renders an address block
	 *
	 * @param array $parameter Parameter
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj Form engine
	 * @param string $table Table
	 * @param int $uid Record UID
	 *
	 * @return string HTML-Content
	 */
	public function address(array $parameter, \TYPO3\CMS\Backend\Form\FormEngine $fobj, $table, $uid) {
		/**
		 * Intialize Template Class
		 * as this class is included via alt_doc we don't have to require template.php
		 * in fact an require would cause an error
		 *
		 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
		 */
		$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$doc->backPath = $GLOBALS['BACK_PATH'];

		$content = '';

		/**
		 * First select Data from Database
		 */
		if (($data = BackendUtility::getRecord($table, $uid, 'uid,' . $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']))) {
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

			$onclickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '"';
			$headerWrap = array(
				'<b><a href="#" ' . $onclickAction . '>',
				'</a></b>'
			);
			$content .= $doc->getHeader($table, $data, 'Local Lang definition is missing', 1, $headerWrap);
			$content .= $doc->spacer(10);

			$display = array();
			foreach ($data as $key => $value) {
				/**
				 * Walk through rowset,
				 * get TCA values
				 * and LL Names
				 */
				if (GeneralUtility::inList($GLOBALS['TCA'][$table]['interface']['showRecordFieldList'], $key)) {
					/**
					 * Get The label
					 */
					$translatedLabel = $this->getLanguageService()->sL(BackendUtility::getItemLabel($table, $key));
					$display[$key] = array($translatedLabel, htmlspecialchars($value));
				}
			}

			$tableLayout = array (
				'table' => array('<table>', '</table>'),
				'defRowEven' => array (
					'defCol' => array('<td class="bgColor5">', '</td>')
				),
				'defRowOdd' => array (
					'defCol' => array('<td class="bgColor4">', '</td>')
				)
			);
			$content .= $doc->table($display, $tableLayout);
		}

		$content .= '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
			htmlspecialchars($parameter['itemFormElValue']) . '">';
		return $content;
	}

	/**
	 * Frontend user orders
	 *
	 * @return string
	 */
	public function feUserOrders() {
		/**
		 * Order record list
		 *
		 * @var Tx_Commerce_ViewHelpers_OrderRecordlist $dblist
		 */
		$dblist = GeneralUtility::makeInstance('Tx_Commerce_ViewHelpers_OrderRecordlist');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->script = 'index.php';
		$dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
		$dblist->thumbs = $GLOBALS['BE_USER']->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;
		$dblist->allFields = 1;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 0;

		// CB is the clipboard command array
		$clipBoardCommands = GeneralUtility::_GET('CB');
		if ($this->cmd == 'setCB') {
			// CBH is all the fields selected for the clipboard, CBC is the checkbox fields
			// which were checked. By merging we get a full array of checked/unchecked
			// elements
			// This is set to the 'el' array of the CB after being parsed so only the table
			// in question is registered.
			$clipBoardCommands['el'] = $dblist->clipObj->cleanUpCBC(
				array_merge(GeneralUtility::_POST('CBH'), GeneralUtility::_POST('CBC')),
				$this->cmd_table
			);
		}
		$dblist->start(NULL, 'tx_commerce_orders', 0);

		$dblist->generateList();

		return $dblist->HTMLcode;
	}


	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $PA Parameter
	 *
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::articleOrderId instead
	 */
	public function article_order_id($PA) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->articleOrderId($PA);
	}

	/**
	 * Article order_id
	 * Just a hidden field
	 *
	 * @param array $PA Parameter
	 *
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::sumPriceGrossFormat instead
	 */
	public function sum_price_gross_format($PA) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->sumPriceGrossFormat($PA);
	}

	/**
	 * Oder Articles
	 * Renders the List of aricles
	 *
	 * @param array $PA Parameter
	 *
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::orderArticles instead
	 */
	public function order_articles($PA) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->orderArticles($PA);
	}

	/**
	 * Oder Status
	 * Selects only the oder folders from the pages List
	 *
	 * @param array $data Data
	 *
	 * @return void
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::orderStatus instead
	 */
	public function order_status(array &$data) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$this->orderStatus($data);
	}

	/**
	 * Invoice Adresss
	 * Renders the invoice adresss
	 *
	 * @param array $PA Parameter
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj Form engine
	 *
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::invoiceAddress instead
	 */
	public function invoice_adress($PA, $fobj) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->invoiceAddress($PA, $fobj);
	}

	/**
	 * Invoice Adresss
	 * Renders the invoice adresss
	 *
	 * @param array $PA Parameter
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj Form engine
	 *
	 * @return string HTML-Content
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::deliveryAddress instead
	 */
	public function delivery_adress($PA, $fobj) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->deliveryAddress($PA, $fobj);
	}

	/**
	 * Address
	 *
	 * @param array $PA Parameter
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $fobj Form engine
	 * @param string $table Table
	 * @param int $uid Uid
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::getAttributes instead
	 */
	public function adress($PA, $fobj, $table, $uid) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->address($PA, $fobj, $table, $uid);
	}

	/**
	 * Frontend user orders
	 *
	 * @param array $PA Parameter
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use Tx_Commerce_ViewHelpers_OrderEditFunc::feUserOrders instead
	 */
	public function fe_user_orders($PA) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->feUserOrders();
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

	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}

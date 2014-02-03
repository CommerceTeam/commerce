<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004 - 2011 Joerg Sprung (typo3@marketing-factory.de)
 *  (c) 2008 - 2011 Ingo Schmitt (is@marketing-factory.de)
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

unset($MCONF);
require_once('conf.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xml');
$LANG->includeLLFile('EXT:lang/locallang_mod_web_list.php');

	// This checks permissions and exits if the users has no permission for entry.
/** @noinspection PhpUndefinedVariableInspection */
$BE_USER->modAccess($MCONF, 1);

/**
 * Module 'Statistics' for the 'commerce' extension.
 */
class tx_commerce_statistic extends t3lib_SCbase {
	/**
	 * @var array
	 */
	protected $extConf;

	/**
	 * @var string
	 */
	protected $excludePids;

	/**
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * @var array
	 */
	protected $order_pid;

	/**
	 * @var tx_commerce_statistics
	 */
	protected $statistics;

	/**
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];

		$this->excludePids = $this->extConf['excludeStatisticFolders'] != '' ? $this->extConf['excludeStatisticFolders'] : 0;

		$order_pid = array_unique(tx_commerce_folder_db::initFolders('Orders', 'Commerce', 0, 'Commerce'));
		$this->order_pid = $order_pid[0];

		$this->statistics = t3lib_div::makeInstance('tx_commerce_statistics');
		$this->statistics->init($this->extConf['excludeStatisticFolders'] != '' ? $this->extConf['excludeStatisticFolders'] : 0);
			// @todo Find a better solution for the fist array element
		/**
		 * If we get an id via GP use this, else use the default id
		 */
		if (t3lib_div::_GP('id')) {
			$this->id = t3lib_div::_GP('id');
		} else {
			$this->id = $order_pid[0];
		}
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return void
	 */
	public function menuConfig() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['allowAggregation'] == 1) {
			$this->MOD_MENU = array(
				'function' => array(
					'1' => $language->getLL('statistics'),
					'2' => $language->getLL('incremental_aggregation'),
					'3' => $language->getLL('complete_aggregation'),
				)
			);
		} else {
			$this->MOD_MENU = array(
				'function' => array(
					'1' => $language->getLL('statistics'),
				)
			);
		}

		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return void
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

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		$this->content .= $this->doc->startPage($language->getLL('title'));
		$this->content .= $this->doc->header($language->getLL('title'));

		if (($this->id && $access) || ($backendUser->user['admin'] && !$this->id)) {
			$this->doc->form = '<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL) {
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode = '
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
				</script>
			';

			$headerSection = $this->doc->getHeader(
				'pages',
				$this->pageinfo,
				$this->pageinfo['_thePath']) . '<br>' . $language->sL('LLL:EXT:lang/locallang_core.php:labels.path') . ': ' .
					t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'],
				-50
			);

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
	 */
	protected function moduleContent() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		switch((string) $this->MOD_SETTINGS['function']) {
			case 1:
				$content = $this->showStatistics();
				$this->content .= $this->doc->section($language->getLL('statistics') . ': ', $content, 0, 1);
			break;
			case 2:
				$content = $this->incrementalAggregation();
				$this->content .= $this->doc->section($language->getLL('incremental_aggregation') . ': ', $content, 0, 1);
			break;
			case 3:
				$content = $this->completeAggregation();
				$this->content .= $this->doc->section($language->getLL('complete_aggregation') . ': ', $content, 0, 1);
			break;
		}
	}

	/**
	 * Generates an initialize the complete Aggregation
	 *
	 * @return string Content to show in BE
	 */
	protected function completeAggregation() {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = '';
		if (isset($GLOBALS['HTTP_POST_VARS']['fullaggregation'])) {
			$endselect = 'SELECT max(crdate) FROM tx_commerce_order_articles';
			$endres = $database->sql_query($endselect);
			$endtime2 = 0;
			if ($endres AND $endrow = $database->sql_fetch_row($endres)) {
				$endtime2 = $endrow[0];
			}

			$endtime =  $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

			$startselect = 'SELECT min(crdate) FROM tx_commerce_order_articles WHERE crdate > 0';
			$startres = $database->sql_query($startselect);
			if ($startres AND $startrow = $database->sql_fetch_row($startres) AND $startrow[0] != NULL) {
				$starttime = $startrow[0];
				$database->sql_query('truncate tx_commerce_salesfigures');
				$result .= $this->statistics->doSalesAggregation($starttime, $endtime);
			} else {
				$result .= 'no sales data available';
			}

			$endselect = 'SELECT max(crdate) FROM fe_users';
			$endres = $database->sql_query($endselect);
			if ($endres AND $endrow = $database->sql_fetch_row($endres)) {
				$endtime2 = $endrow[0];
			}

			$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

			$startselect = 'SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0';
			$startres = $database->sql_query($startselect);
			if ($startres AND $startrow = $database->sql_fetch_row($startres) AND $startrow[0] != NULL) {
				$starttime = $startrow[0];
				$database->sql_query('truncate tx_commerce_newclients');
				$result = $this->statistics->doClientAggregation($starttime, $endtime);
			} else {
				$result .= '<br />no client data available';
			}
		} else {
			/** @var language $language */
			$language = $GLOBALS['LANG'];

			$result = 'Dieser Vorgang kann eventuell lange dauern<br /><br />';
			$result .= sprintf ('<input type="submit" name="fullaggregation" value="%s" />', $language->getLL('complete_aggregation'));
		}

		return $result;
	}

	/**
	 * Generates an initialize the complete Aggregation
	 *
	 * @return String Content to show in BE
	 */
	protected function incrementalAggregation() {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$result = '';
		if (isset($GLOBALS['HTTP_POST_VARS']['incrementalaggregation'])) {
			$lastAggregationTime = 'SELECT max(tstamp) FROM tx_commerce_salesfigures';
			$lastAggregationTimeres = $database->sql_query($lastAggregationTime);
			$lastAggregationTimeValue = 0;
			if (
				$lastAggregationTimeres
				AND $lastAggregationTimerow = $database->sql_fetch_row( $lastAggregationTimeres )
				AND $lastAggregationTimerow[0] != NULL
			) {
				$lastAggregationTimeValue = $lastAggregationTimerow[0];
			}
			$lastAggretagionTimeValue = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

			$endselect = 'SELECT max(crdate) FROM tx_commerce_order_articles';
			$endres = $database->sql_query($endselect);
			$endtime2 = 0;
			if ($endres AND $endrow = $database->sql_fetch_row($endres)) {
				$endtime2 = $endrow[0];
			}
			$starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

			if ($starttime <= $this->statistics->firstSecondOfDay($endtime2) AND $endtime2 != NULL) {
				$endtime =  $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

				echo 'Incremental Sales Agregation for sales for the period from ' . strftime('%d.%m.%Y', $starttime) . ' to ' .
					strftime('%d.%m.%Y', $endtime) . ' (DD.MM.YYYY)<br />' . LF;
				flush();
				$result .= $this->statistics->doSalesAggregation($starttime, $endtime);
			} else {
				$result .= 'No new Orders<br />';
			}

			$changeselect = 'SELECT DISTINCT crdate FROM tx_commerce_order_articles where tstamp > ' .
				($lastAggregationTimeValue - ($this->statistics->getDaysBack() * 24 * 60 * 60));
			$changeres = $database->sql_query($changeselect);
			$changeDaysArray = array();
			$changes = 0;
			while ($changeres AND $changerow = $database->sql_fetch_assoc($changeres)) {

				$starttime =  $this->statistics->firstSecondOfDay($changerow['crdate']);
				$endtime =  $this->statistics->lastSecondOfDay($changerow['crdate']);

				if (!in_array($starttime, $changeDaysArray)) {
					$changeDaysArray[] = $starttime;

					echo 'Incremental Sales Udpate Agregation for sales for the day ' . strftime('%d.%m.%Y', $starttime) . ' <br />' . LF;
					flush();
					$result .= $this->statistics->doSalesUpdateAggregation($starttime, $endtime);
					++$changes;
				}
			}

			$result .= $changes . ' Days changed<br />';

			$lastAggregationTime = 'SELECT max(tstamp) FROM tx_commerce_newclients';
			$lastAggregationTimeres = $database->sql_query($lastAggregationTime);
			if ($lastAggregationTimeres AND $lastAggregationTimerow = $database->sql_fetch_row($lastAggregationTimeres)) {
				$lastAggregationTimeValue = $lastAggregationTimerow[0];
			}
			$endselect = 'SELECT max(crdate) FROM fe_users';
			$endres = $database->sql_query($endselect);
			if ($endres AND $endrow = $database->sql_fetch_row($endres)) {
				$endtime2 = $endrow[0];
			}
			if ($lastAggregationTimeValue <= $endtime2 AND $endtime2 != NULL AND $lastAggregationTimeValue != NULL) {
				$endtime =  $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

				$startres = $database->sql_query('SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0');

				$starttime = strtotime('0', $lastAggregationTimeValue);
				if (empty($starttime)) {
					$starttime = $lastAggregationTimeValue;
				}
				echo 'Incremental Sales Udpate Agregation for sales for the period from ' . strftime('%d.%m.%Y', $starttime) .
					' to ' . strftime('%d.%m.%Y', $endtime) . ' (DD.MM.YYYY)<br />' . LF;
				flush();
				$result .= $this->statistics->doClientAggregation($starttime, $endtime);
			} else {
				$result .= 'No new Customers<br />';
			}
		} else {
			/** @var language $language */
			$language = $GLOBALS['LANG'];

			$result = 'Dieser Vorgang kann eventuelle eine hohe Laufzeit haben<br /><br />';
			$result .= sprintf ('<input type="submit" name="incrementalaggregation" value="%s" />', $language->getLL('incremental_aggregation'));
		}

		return $result;
	}

	/**
	 * Generate the Statistictables
	 *
	 * @return string statistictables in HTML
	 */
	protected function showStatistics() {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$whereClause = '';
		if ($this->id != $this->order_pid) {
			$whereClause = 'pid = ' . $this->id;
		}
		$weekdays = array(
			$language->getLL('sunday'),
			$language->getLL('monday'),
			$language->getLL('tuesday'),
			$language->getLL('wednesday'),
			$language->getLL('thursday'),
			$language->getLL('friday'),
			$language->getLL('saturday')
		);

		$tables = '';
		if (t3lib_div::_GP('show')) {
			$whereClause = $whereClause != '' ? $whereClause . ' AND' : '';
			$whereClause .=  ' month = ' . t3lib_div::_GP('month') . '  AND year = ' . t3lib_div::_GP('year');

			$tables .= '<h2>' . t3lib_div::_GP('month') . ' - ' . t3lib_div::_GP('year') .
				'</h2><table><tr><th>Days</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $database->exec_SELECTquery(
				'sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,day',
				'tx_commerce_salesfigures',
				$whereClause,
				'day'
			);
			$daystat = array();
			while ($statRow = $database->sql_fetch_assoc($statResult)) {
				$daystat[$statRow['day']] = $statRow;
			}
			$lastday = date('d', mktime(0, 0, 0, t3lib_div::_GP('month') + 1, 0, t3lib_div::_GP('year')));
			for ($i = 1; $i <= $lastday; ++$i) {
				if (array_key_exists($i, $daystat)) {
					$tablestemp = '<tr><td>' . $daystat[$i]['day'] . '</a></td><td align="right">%01.2f</td><td align="right">' .
						$daystat[$i]['salesfigures'] . '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
					$tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
				} else {
					$tablestemp = '<tr><td>' . $i . '</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
					$tables .= sprintf($tablestemp, (0));
				}
			}
			$tables .= '</table>';

			$tables .= '<table><tr><th>Weekday</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $database->exec_SELECTquery(
				'sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,dow',
				'tx_commerce_salesfigures',
				$whereClause,
				'dow'
			);

			$daystat = array();
			while ($statRow = $database->sql_fetch_assoc($statResult)) {
				$daystat[$statRow['dow']] = $statRow;
			}
			for ($i = 0; $i <= 6; ++$i) {
				if (array_key_exists($i, $daystat)) {
					$tablestemp = '<tr><td>' . $weekdays[$daystat[$i]['dow']] . '</a></td><td align="right">%01.2f</td><td align="right">' .
						$daystat[$i]['salesfigures'] . '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
					$tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
				} else {
					$tablestemp = '<tr><td>' . $weekdays[$i] . '</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
					$tables .= sprintf($tablestemp, 0);
				}
			}
			$tables .= '</table>';

			$tables .= '<table><tr><th>Hour</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $database->exec_SELECTquery(
				'sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,hour',
				'tx_commerce_salesfigures',
				$whereClause,
				'hour'
			);

			$daystat = array();
			while ($statRow = $database->sql_fetch_assoc($statResult)) {
				$daystat[$statRow['hour']] = $statRow;
			}
			for ($i = 0; $i <= 23; ++$i) {
				if (array_key_exists($i, $daystat)) {
					$tablestemp = '<tr><td>' . $i . '</a></td><td align="right">%01.2f</td><td align="right">' .
						$daystat[$i]['salesfigures'] . '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
					$tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
				} else {
					$tablestemp = '<tr><td>' . $i . '</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
					$tables .= sprintf($tablestemp, 0);
				}
			}
			$tables .= '</table>';
			$tables .= '</table>';

		} else {
			$tables = '<table><tr><th>Month</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $database->exec_SELECTquery(
				'sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,year,month',
				'tx_commerce_salesfigures',
				$whereClause,
				'year,month'
			);
			while ($statRow = $database->sql_fetch_assoc($statResult) ) {
				$tablestemp = '<tr><td><a href="?id=' . $this->id . '&amp;month=' . $statRow['month'] . '&amp;year=' .
					$statRow['year'] . '&amp;show=details">' . $statRow['month'] . '.' . $statRow['year'] .
					'</a></td><td align="right">%01.2f</td><td align="right">' . $statRow['salesfigures'] .
					'</td><td align="right">' . $statRow['sumorders'] . '</td></tr>';
				$tables .= sprintf($tablestemp, ($statRow['turnover'] / 100));
			}
			$tables .= '</table>';
		}
		return $tables;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_statistic/index.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_statistic/index.php']);
}

	// Make instance:
$SOBE = t3lib_div::makeInstance('tx_commerce_statistic');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
<?php
namespace CommerceTeam\Commerce\Controller;
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

/**
 * Module 'Statistics' for the 'commerce' extension.
 *
 * Class \CommerceTeam\Commerce\Controller\StatisticModuleController
 *
 * @author 2004-2011 Joerg Sprung <jsp@marketing-factory.de>
 */
class StatisticModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {
	/**
	 * Document template
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Extension configuration
	 *
	 * @var array
	 */
	protected $extConf;

	/**
	 * Exclude pids
	 *
	 * @var string
	 */
	protected $excludePids;

	/**
	 * Page information
	 *
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * Order page id
	 *
	 * @var array
	 */
	protected $orderPageId;

	/**
	 * Statistics
	 *
	 * @var \CommerceTeam\Commerce\Utility\StatisticsUtility
	 */
	protected $statistics;

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init() {
		$language = $this->getLanguageService();
		$language->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xml');
		$language->includeLLFile('EXT:lang/locallang_mod_web_list.php');

		parent::init();
		$this->extConf = SettingsFactory::getInstance()->getExtConfComplete();

		$this->excludePids = $this->extConf['excludeStatisticFolders'] != '' ? $this->extConf['excludeStatisticFolders'] : 0;

		$this->statistics = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Utility\\StatisticsUtility');
		$this->statistics->init($this->extConf['excludeStatisticFolders'] != '' ? $this->extConf['excludeStatisticFolders'] : 0);

		$this->orderPageId = current(array_unique(
			\CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders('Orders', 'Commerce', 0, 'Commerce')
		));

		/**
		 * If we get an id via GP use this, else use the default id
		 */
		$this->id = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
		if (!$this->id) {
			$this->id = $this->orderPageId;
		}

		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $this->getBackPath();
		$this->doc->docType = 'xhtml_trans';
		$this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_index.html');

		if (!$this->doc->moduleTemplate) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('cannot set moduleTemplate', 'commerce', 2, array(
				'backpath' => $this->getBackPath(),
				'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_index.html'],
				'full path' => $this->getBackPath() . $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_index.html']
			));
			$templateFile = PATH_TXCOMMERCE_REL . 'Resources/Private/Backend/mod_index.html';
			$this->doc->moduleTemplate = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL(PATH_site . $templateFile);
		}

		$this->doc->form = '<form action="" method="POST" name="editform">';

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
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return void
	 */
	public function menuConfig() {
		$language = $this->getLanguageService();

		if (SettingsFactory::getInstance()->getExtConf('allowAggregation') == 1) {
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
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();

		// Access check!
		// The page will show only if there is a valid page and if
		// this page may be viewed by the user
		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);

		// Checking access:
		if (($this->id && $access) || $backendUser->isAdmin()) {
			// Render content:
			$this->moduleContent();
		} else {
			// If no access or if ID == zero
			$this->content .= $this->doc->header($language->getLL('statistic'));
		}

		$docHeaderButtons = $this->getHeaderButtons();

		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content,
		);
		$markers['FUNC_MENU'] = $this->doc->funcMenu(
			'',
			\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
				$this->id,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			)
		);

		// put it all together
		$this->content = $this->doc->startPage($language->getLL('statistic'));
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
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return void
	 */
	protected function moduleContent() {
		switch ((int)$this->MOD_SETTINGS['function']) {
			case 2:
				$this->content .= $this->incrementalAggregation();
				break;

			case 3:
				$this->content .= $this->completeAggregation();
				break;

			case 1:
			default:
				$this->content .= $this->showStatistics();
		}
	}

	/**
	 * Create the panel of buttons for submitting the form
	 * or otherwise perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getHeaderButtons() {
		$backendUser = $this->getBackendUser();
		$language = $this->getLanguageService();

		$buttons = array(
			'csh' => '',
			// group left 1
			'level_up' => '',
			'back' => '',
			// group left 2
			'new_record' => '',
			'paste' => '',
			// group left 3
			'view' => '',
			'edit' => '',
			'move' => '',
			'hide_unhide' => '',
			// group left 4
			'csv' => '',
			'export' => '',
			// group right 1
			'cache' => '',
			'reload' => '',
			'shortcut' => '',
		);

		// CSH
		$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem(
			'_MOD_commerce_statistic', '', $this->getBackPath(), '', TRUE
		);

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
			$href = $this->getBackPath() . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' .
				rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
				\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(
					'apps-filetree-folder-list',
					array('title' => $language->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1))
				) . '</a>';
		}
		return $buttons;
	}

	/**
	 * Generates an initialize the complete Aggregation
	 *
	 * @return string Content to show in BE
	 */
	protected function completeAggregation() {
		$database = $this->getDatabaseConnection();

		$result = '';
		if (isset($GLOBALS['HTTP_POST_VARS']['fullaggregation'])) {
			$endselect = 'SELECT max(crdate) FROM tx_commerce_order_articles';
			$endres = $database->sql_query($endselect);
			$endtime2 = 0;
			if ($endres && ($endrow = $database->sql_fetch_row($endres))) {
				$endtime2 = $endrow[0];
			}

			$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

			$startselect = 'SELECT min(crdate) FROM tx_commerce_order_articles WHERE crdate > 0';
			$startres = $database->sql_query($startselect);
			if ($startres AND ($startrow = $database->sql_fetch_row($startres)) AND $startrow[0] != NULL) {
				$starttime = $startrow[0];
				$database->sql_query('truncate tx_commerce_salesfigures');
				$result .= $this->statistics->doSalesAggregation($starttime, $endtime);
			} else {
				$result .= 'no sales data available';
			}

			$endselect = 'SELECT max(crdate) FROM fe_users';
			$endres = $database->sql_query($endselect);
			if ($endres AND ($endrow = $database->sql_fetch_row($endres))) {
				$endtime2 = $endrow[0];
			}

			$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

			$startselect = 'SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0';
			$startres = $database->sql_query($startselect);
			if ($startres AND ($startrow = $database->sql_fetch_row($startres)) AND $startrow[0] != NULL) {
				$starttime = $startrow[0];
				$database->sql_query('truncate tx_commerce_newclients');
				$result = $this->statistics->doClientAggregation($starttime, $endtime);
			} else {
				$result .= '<br />no client data available';
			}
		} else {
			$language = $this->getLanguageService();

			$result = $language->getLL('may_take_long_periode') . '<br /><br />';
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
		$database = $this->getDatabaseConnection();

		$result = '';
		if (isset($GLOBALS['HTTP_POST_VARS']['incrementalaggregation'])) {
			$lastAggregationTime = 'SELECT max(tstamp) FROM tx_commerce_salesfigures';
			$lastAggregationTimeres = $database->sql_query($lastAggregationTime);
			$lastAggregationTimeValue = 0;
			if (
				$lastAggregationTimeres
				AND ($lastAggregationTimerow = $database->sql_fetch_row($lastAggregationTimeres))
				AND $lastAggregationTimerow[0] != NULL
			) {
				$lastAggregationTimeValue = $lastAggregationTimerow[0];
			}

			$endselect = 'SELECT max(crdate) FROM tx_commerce_order_articles';
			$endres = $database->sql_query($endselect);
			$endtime2 = 0;
			if ($endres AND ($endrow = $database->sql_fetch_row($endres))) {
				$endtime2 = $endrow[0];
			}
			$starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

			if ($starttime <= $this->statistics->firstSecondOfDay($endtime2) AND $endtime2 != NULL) {
				$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

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
			while ($changeres AND ($changerow = $database->sql_fetch_assoc($changeres))) {
				$starttime = $this->statistics->firstSecondOfDay($changerow['crdate']);
				$endtime = $this->statistics->lastSecondOfDay($changerow['crdate']);

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
			if ($lastAggregationTimeres AND ($lastAggregationTimerow = $database->sql_fetch_row($lastAggregationTimeres))) {
				$lastAggregationTimeValue = $lastAggregationTimerow[0];
			}
			$endselect = 'SELECT max(crdate) FROM fe_users';
			$endres = $database->sql_query($endselect);
			if ($endres AND ($endrow = $database->sql_fetch_row($endres))) {
				$endtime2 = $endrow[0];
			}
			if ($lastAggregationTimeValue <= $endtime2 AND $endtime2 != NULL AND $lastAggregationTimeValue != NULL) {
				$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

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
			$language = $this->getLanguageService();

			$result = $language->getLL('may_take_long_periode') . '<br /><br />';
			$result .= sprintf(
				'<input type="submit" name="incrementalaggregation" value="%s" />',
				$language->getLL('incremental_aggregation')
			);
		}

		return $result;
	}

	/**
	 * Generate the Statistictables
	 *
	 * @return string statistictables in HTML
	 */
	protected function showStatistics() {
		$language = $this->getLanguageService();
		$database = $this->getDatabaseConnection();

		$whereClause = '';
		if ($this->id != $this->orderPageId) {
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
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show')) {
			$whereClause = $whereClause != '' ? $whereClause . ' AND' : '';
			$whereClause .= ' month = ' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('month') . '  AND year = ' .
				\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('year');

			$tables .= '<h2>' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('month') . ' - ' .
				\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('year') .
				'</h2><table><tr><th>Days</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
			$statResult = $database->exec_SELECTquery(
				'sum(pricegross) as turnover,sum(amount) as salesfigures ,sum(orders) as sumorders,day',
				'tx_commerce_salesfigures',
				$whereClause,
				'day'
			);
			$daystat = array();
			while (($statRow = $database->sql_fetch_assoc($statResult))) {
				$daystat[$statRow['day']] = $statRow;
			}
			$lastday = date(
				'd',
				mktime(
					0,
					0,
					0,
					\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('month') + 1,
					0,
					\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('year')
				)
			);
			for ($i = 1; $i <= $lastday; ++$i) {
				if (array_key_exists($i, $daystat)) {
					$tablestemp = '<tr><td>' . $daystat[$i]['day'] . '</a></td><td align="right">%01.2f</td><td align="right">' .
						$daystat[$i]['salesfigures'] . '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
					$tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
				} else {
					$tablestemp = '<tr><td>' . $i .
						'</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
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
			while (($statRow = $database->sql_fetch_assoc($statResult))) {
				$daystat[$statRow['dow']] = $statRow;
			}
			for ($i = 0; $i <= 6; ++$i) {
				if (array_key_exists($i, $daystat)) {
					$tablestemp = '<tr><td>' . $weekdays[$daystat[$i]['dow']] . '</a></td><td align="right">%01.2f</td><td align="right">' .
						$daystat[$i]['salesfigures'] . '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
					$tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
				} else {
					$tablestemp = '<tr><td>' . $weekdays[$i] .
						'</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
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
			while (($statRow = $database->sql_fetch_assoc($statResult))) {
				$daystat[$statRow['hour']] = $statRow;
			}
			for ($i = 0; $i <= 23; ++$i) {
				if (array_key_exists($i, $daystat)) {
					$tablestemp = '<tr><td>' . $i . '</a></td><td align="right">%01.2f</td><td align="right">' .
						$daystat[$i]['salesfigures'] . '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
					$tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
				} else {
					$tablestemp = '<tr><td>' . $i .
						'</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
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
			while (($statRow = $database->sql_fetch_assoc($statResult))) {
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

	/**
	 * Get back path
	 *
	 * @return string
	 */
	protected function getBackPath() {
		return $GLOBALS['BACK_PATH'];
	}
}

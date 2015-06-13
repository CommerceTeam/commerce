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

/**
 * Class Tx_Commerce_Task_StatisticTask
 *
 * @author 2013 Sebastian Fischer <typo3@marketing-factory.de>
 */
class Tx_Commerce_Task_StatisticTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {
	/**
	 * @var string
	 */
	protected $selectedAggregation = '';

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $backendUser;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $database;

	/**
	 * @var Tx_Commerce_Utility_StatisticsUtility
	 */
	protected $statistics;

	/**
	 * @var array
	 */
	protected $extConf = array();

	/**
	 * @return void
	 */
	protected function init() {
		$this->backendUser = $GLOBALS['BE_USER'];

		$this->database = $GLOBALS['TYPO3_DB'];

		$this->statistics = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Utility_StatisticsUtility');
		$this->statistics->init(
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['excludeStatisticFolders'] != '' ?
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['excludeStatisticFolders'] :
				0
		);

		$this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];
	}

	/**
	 * @param string $selectedAggregation
	 * @return void
	 */
	public function setSelectedAggregation($selectedAggregation) {
		$this->selectedAggregation = $selectedAggregation;
	}

	/**
	 * @return string
	 */
	public function getSelectedAggregation() {
		return $this->selectedAggregation;
	}

	/**
	 * Execute garbage collection, called by scheduler.
	 *
	 * @return bool
	 */
	public function execute() {
		$this->init();

		switch ($this->selectedAggregation) {
			case 'incrementalAggregation':
				$this->incrementalAggregation();
			break;

			case 'completeAggregation':
			default:
				$this->completeAggregation();
			break;
		}

		return TRUE;
	}

	/**
	 * @return void
	 */
	protected function incrementalAggregation() {
		$lastAggregationTimeres = $this->database->sql_query('SELECT max(tstamp) FROM tx_commerce_salesfigures');
		$lastAggregationTimeValue = 0;
		if (
			$lastAggregationTimeres
			AND ($lastAggregationTimerow = $this->database->sql_fetch_row($lastAggregationTimeres))
			AND $lastAggregationTimerow[0] != NULL
		) {
			$lastAggregationTimeValue = $lastAggregationTimerow[0];
		}

		$endres = $this->database->sql_query('SELECT max(crdate) FROM tx_commerce_order_articles');
		$endtime2 = 0;
		if ($endres AND ($endrow = $this->database->sql_fetch_row($endres))) {
			$endtime2 = $endrow[0];
		}
		$starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

		if ($starttime <= $this->statistics->firstSecondOfDay($endtime2) AND $endtime2 != NULL) {
			$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

			$this->log(
				'Incremental Sales Aggregation for sales for the period from ' . $starttime . ' to ' . $endtime . ' (Timestamp)'
			);
			$this->log(
				'Incremental Sales Aggregation for sales for the period from ' . strftime('%d.%m.%Y', $starttime) .
					' to ' . strftime('%d.%m.%Y', $endtime) . ' (DD.MM.YYYY)'
			);
			if (!$this->statistics->doSalesAggregation($starttime, $endtime)) {
				$this->log('Problems with incremetal Aggregation of orders');
			}
		} else {
			$this->log('No new Orders');
		}

		$changeselect = 'SELECT distinct crdate FROM tx_commerce_order_articles where tstamp > ' .
			($lastAggregationTimeValue - ($this->statistics->getDaysBack() * 24 * 60 * 60));
		$changeres = $this->database->sql_query($changeselect);
		$changeDaysArray = array();
		$changes = 0;
		$result = '';
		while ($changeres && ($changerow = $this->database->sql_fetch_assoc($changeres))) {
			$starttime = $this->statistics->firstSecondOfDay($changerow['crdate']);
			$endtime = $this->statistics->lastSecondOfDay($changerow['crdate']);

			if (!in_array($starttime, $changeDaysArray)) {
				$changeDaysArray[] = $starttime;
				$this->log(
					'Incremental Sales UpdateAggregation for sales for the period from ' . $starttime . ' to ' . $endtime .
						' (Timestamp)'
				);
				$this->log(
					'Incremental Sales UpdateAggregation for sales for the period from ' . strftime('%d.%m.%Y', $starttime) .
						' to ' . strftime('%d.%m.%Y', $endtime) . ' (DD.MM.YYYY)'
				);

				$result .= $this->statistics->doSalesUpdateAggregation($starttime, $endtime, FALSE);
				$changes++;
			}
		}

		$this->log($changes . ' Days changed');

		$lastAggregationTimeres = $this->database->sql_query('SELECT max(tstamp) FROM tx_commerce_newclients');
		if (
			$lastAggregationTimeres
			AND ($lastAggregationTimerow = $this->database->sql_fetch_row($lastAggregationTimeres))
		) {
			$lastAggregationTimeValue = $lastAggregationTimerow[0];
		}
		$lastAggregationTimeValue = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

		$endres = $this->database->sql_query('SELECT max(crdate) FROM fe_users');
		if ($endres AND ($endrow = $this->database->sql_fetch_row($endres))) {
			$endtime2 = $endrow[0];
		}

		if ($lastAggregationTimeValue <= $endtime2 AND $endtime2 != NULL AND $lastAggregationTimeValue != NULL) {
			$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);
			$starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

			$this->log(
				'Incremental Client Agregation for sales for the period from ' . $starttime . ' to ' . $endtime
			);
			$this->log(
				'Incremental Client Agregation for sales for the period from ' . strftime('%d.%m.%Y', $starttime) .
					' to ' . strftime('%d.%m.%Y', $endtime)
			);

			if (!$this->statistics->doClientAggregation($starttime, $endtime)) {
				$this->log('Problems with CLient agregation');
			}
		} else {
			$this->log('No new Customers ');
		}
	}

	/**
	 * @return void
	 */
	protected function completeAggregation() {
		$endres = $this->database->sql_query('SELECT max(crdate) FROM tx_commerce_order_articles');
		$endtime2 = 0;
		if ($endres AND ($endrow = $this->database->sql_fetch_row($endres))) {
			$endtime2 = $endrow[0];
		}

		$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

		$startres = $this->database->sql_query('SELECT min(crdate) FROM tx_commerce_order_articles WHERE crdate > 0');
		if ($startres AND ($startrow = $this->database->sql_fetch_row($startres)) AND $startrow[0] != NULL) {
			$starttime = $startrow[0];
			$this->database->sql_query('truncate tx_commerce_salesfigures');
			if (!$this->statistics->doSalesAggregation($starttime, $endtime)) {
				$this->log('problems with completeAgregation of Sales');
			}
		} else {
			$this->log('no sales data available');
		}

		$endres = $this->database->sql_query('SELECT max(crdate) FROM fe_users');
		if ($endres AND ($endrow = $this->database->sql_fetch_row($endres))) {
			$endtime2 = $endrow[0];
		}

		$endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

		$startres = $this->database->sql_query('SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0');
		if ($startres AND ($startrow = $this->database->sql_fetch_row($startres)) AND $startrow[0] != NULL) {
			$starttime = $startrow[0];
			$this->database->sql_query('truncate tx_commerce_newclients');
			if (!$this->statistics->doClientAggregation($starttime, $endtime)) {
				$this->log('Probvlems with cle complete agregation Clients');
			}
		} else {
			$this->log('no client data available');
		}
	}

	/**
	 * @param string $message
	 * @param int $status
	 * @param string $code
	 *
	 * @return void
	 */
	public function log($message, $status = 0, $code = 'commerce') {
		$this->backendUser->writelog(
			4,
			0,
			$status,
			$code,
			'[commerce]: ' . $message,
			array()
		);
	}
}

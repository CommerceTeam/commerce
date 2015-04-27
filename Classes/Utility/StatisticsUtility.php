<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Ingo Schmitt <is@marketing-factory.de>
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
 * This class inculdes all methods for generating statistics data,
 * used for the statistics module and for the cli script
 */
class Tx_Commerce_Utility_StatisticsUtility {
	/**
	 * List of exclude PIDs, PIDs whcih should not be used when calculation
	 * the statistics. This List should be definable in Extension configuration
	 *
	 * @var string
	 */
	public $excludePids;

	/**
	 * How mayn dasys the update agregation wil recaluclate
	 *
	 * @var integer
	 */
	public $daysback = 10;

	/**
	 * Initialization
	 *
	 * @param string $excludePids
	 * @return void
	 */
	public function init($excludePids) {
		$this->excludePids = $excludePids;
	}

	/**
	 * Public method to return days back
	 *
	 * @return integer
	 */
	public function getDaysBack() {
		return $this->daysback;
	}

	/**
	 * Aggregate ans Insert the Salesfigures per Hour in the timespare from
	 * $starttime to $enttime
	 *
	 * @param integer $starttime Timestamp of timecode to start the aggregation
	 * @param integer $endtime Timestamp of timecode to end the aggregation
	 * @return boolean result of aggregation
	 */
	public function doSalesAggregation($starttime, $endtime) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$hour = date('H', $starttime);
		$day = date('d', $starttime);
		$month = date('m', $starttime);
		$year = date('Y', $starttime);
		$result = TRUE;
		$oldtimestart = mktime($hour, 0, 0, $month, $day, $year);
		$oldtimeend = mktime($hour, 59, 59, $month, $day, $year);

		while ($oldtimeend <= $endtime) {
			$statres = $database->exec_SELECTquery(
				'sum(toa.amount),
					sum(toa.amount * toa.price_gross),
					count(distinct toa.order_id),
					toa.pid,
					sum(toa.amount * toa.price_net)',
				'tx_commerce_order_articles toa,
					tx_commerce_orders tco',
				'toa.article_type_uid <= 1
					AND toa.crdate >= ' . $oldtimestart . '
					AND toa.crdate <= ' . $oldtimeend . '
					AND toa.pid not in(' . $this->excludePids . ')
					AND toa.order_id = tco.order_id
					AND tco.deleted = 0',
				'toa.pid'
			);

			while ($statrow = $database->sql_fetch_row($statres)) {
				$insertStatArray = array(
					'pid' => $statrow[3],
					'year' => date('Y', $oldtimeend),
					'month' => date('m', $oldtimeend),
					'day' => date('d', $oldtimeend),
					'dow' => date('w', $oldtimeend),
					'hour' => date('H', $oldtimeend),
					'pricegross' => $statrow[1],
					'amount' => $statrow[0],
					'orders' => $statrow[2],
					'pricenet' => $statrow[4],
					'crdate' => time(),
					'tstamp' => time()
				);

				$res = $database->exec_INSERTquery('tx_commerce_salesfigures', $insertStatArray);
				if (!$res) {
					$result = FALSE;
				}
			}
			$oldtimestart = mktime(++$hour, 0, 0, $month, $day, $year);
			$oldtimeend = mktime($hour, 59, 59, $month, $day, $year);
		}

		return $result;
	}

	/**
	 * Aggregate and Update the Salesfigures per Hour in the timespare from
	 * $starttime to $enttime
	 *
	 * @param integer $starttime Timestamp of timecode to start the aggregation
	 * @param integer $endtime Timestamp of timecode to end the aggregation
	 * @param boolean $doOutput Boolen, if output should be genared whiel caluclating, shoudl be fals for cli
	 * @return boolean result of aggregation
	 */
	public function doSalesUpdateAggregation($starttime, $endtime, $doOutput = TRUE) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$hour = date('H', $starttime);
		$day = date('d', $starttime);
		$month = date('m', $starttime);
		$year = date('Y', $starttime);
		$stats = '';
		$oldtimestart = mktime($hour, 0, 0, $month, $day, $year);
		$oldtimeend = mktime($hour, 59, 59, $month, $day, $year);

		while ($oldtimeend <= $endtime) {
			$statres = $database->exec_SELECTquery(
				'sum(toa.amount),
					sum(toa.amount * toa.price_gross),
					count(distinct toa.order_id),
					toa.pid,
					sum(toa.amount * toa.price_net)',
				'tx_commerce_order_articles toa,
					tx_commerce_orders tco',
				'toa.article_type_uid <= 1
					AND toa.crdate >= ' . $oldtimestart . '
					AND toa.crdate <= ' . $oldtimeend . '
					AND toa.pid not in(' . $this->excludePids . ')
					AND toa.order_id = tco.order_id
					AND tco.deleted = 0',
				'toa.pid'
			);
			while ($statrow = $database->sql_fetch_row($statres)) {
				$updateStatArray = array(
					'pid' => $statrow[3],
					'year' => date('Y', $oldtimeend),
					'month' => date('m', $oldtimeend),
					'day' => date('d', $oldtimeend),
					'dow' => date('w', $oldtimeend),
					'hour' => date('H', $oldtimeend),
					'pricegross' => $statrow[1],
					'amount' => $statrow[0],
					'orders' => $statrow[2],
					'pricenet' => $statrow[4],
					'tstamp' => time()
				);
				$whereClause = 'year = ' . date('Y', $oldtimeend) . ' AND month = ' . date(
						'm', $oldtimeend
					) . ' AND day = ' . date('d', $oldtimeend) . ' AND hour = ' . date('H', $oldtimeend);
				$res = $database->exec_UPDATEquery('tx_commerce_salesfigures', $whereClause, $updateStatArray);

				if (!$res) {
					$stats = FALSE;
				}
				if ($doOutput) {
					print '.';
					flush();
				}
			}

			$oldtimestart = mktime(++$hour, 0, 0, $month, $day, $year);
			$oldtimeend = mktime($hour, 59, 59, $month, $day, $year);
		}

		return $stats;
	}

	/**
	 * Aggregate and Insert the New Users (Registrations in fe_user)) per hour
	 * in the timespare from $starttime to $enttime
	 *
	 * @param integer $starttime Timestamp of timecode to start the aggregation
	 * @param integer $endtime Timestamp of timecode to end the aggregation
	 * @return boolean result of aggregation
	 */
	public function doClientAggregation($starttime, $endtime) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$hour = date('H', $starttime);
		$day = date('d', $starttime);
		$month = date('m', $starttime);
		$year = date('Y', $starttime);
		$return = TRUE;
		$oldtimestart = mktime($hour, 0, 0, $month, $day, $year);
		$oldtimeend = mktime($hour, 59, 59, $month, $day, $year);

		while ($oldtimeend < $endtime) {
			$statres = $database->exec_SELECTquery(
				'count(*), pid',
				'fe_users',
				'crdate >= ' . $oldtimestart . ' AND crdate <= ' . $oldtimeend,
				'pid'
			);
			while ($statrow = $database->sql_fetch_row($statres)) {
				$insertStatArray = array(
					'pid' => $statrow[1],
					'year' => date('Y', $oldtimeend),
					'month' => date('m', $oldtimeend),
					'day' => date('d', $oldtimeend),
					'dow' => date('w', $oldtimeend),
					'hour' => date('H', $oldtimeend),
					'registration' => $statrow[0],
					'crdate' => time(),
					'tstamp' => time()
				);

				$database->exec_INSERTquery('tx_commerce_newclients', $insertStatArray);
			}
			$oldtimestart = mktime(++$hour, 0, 0, $month, $day, $year);
			$oldtimeend = mktime($hour, 59, 59, $month, $day, $year);
		}

		return $return;
	}

	/**
	 * Retursn the first second of a day as Timestamp
	 *
	 * @param integer $timestamp
	 * @return integer Timestamp
	 */
	public function firstSecondOfDay($timestamp) {
		return (int) mktime(0, 0, 0, strftime('%m', $timestamp), strftime('%d', $timestamp), strftime('%Y', $timestamp));
	}

	/**
	 * Retursn the last second of a day as Timestamp
	 *
	 * @param integer $timestamp
	 * @return integer Timestamp
	 */
	public function lastSecondOfDay($timestamp) {
		return (int) mktime(23, 59, 59, strftime('%m', $timestamp), strftime('%d', $timestamp), strftime('%Y', $timestamp));
	}
}

<?php
namespace CommerceTeam\Commerce\Task;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Task\StatisticTask
 */
class StatisticTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Selected aggregation.
     *
     * @var string
     */
    protected $selectedAggregation = '';

    /**
     * Statistics utility.
     *
     * @var \CommerceTeam\Commerce\Utility\StatisticsUtility
     */
    protected $statistics;

    /**
     * Initialization.
     *
     * @return void
     */
    protected function init()
    {
        $excludeStatisticFolders = 0;
        if (ConfigurationUtility::getInstance()->getExtConf('excludeStatisticFolders') != '') {
            $excludeStatisticFolders = ConfigurationUtility::getInstance()->getExtConf('excludeStatisticFolders');
        }

        $this->statistics = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\StatisticsUtility::Class);
        $this->statistics->init($excludeStatisticFolders);
    }

    /**
     * Set selected aggregation.
     *
     * @param string $selectedAggregation Selected aggregation
     */
    public function setSelectedAggregation($selectedAggregation)
    {
        $this->selectedAggregation = $selectedAggregation;
    }

    /**
     * Get selected aggregation.
     *
     * @return string
     */
    public function getSelectedAggregation()
    {
        return $this->selectedAggregation;
    }

    /**
     * Execute garbage collection, called by scheduler.
     *
     * @return bool
     */
    public function execute()
    {
        $this->init();

        switch ($this->selectedAggregation) {
            case 'incrementalAggregation':
                $this->incrementalAggregation();
                break;

            case 'completeAggregation':
                // fall through
            default:
                $this->completeAggregation();
        }

        return true;
    }

    /**
     * Incremental aggregation.
     *
     * @return void
     */
    protected function incrementalAggregation()
    {
        $database = $this->getDatabaseConnection();

        $lastAggregationTimeres = $database->sql_query('SELECT max(tstamp) FROM tx_commerce_salesfigures');
        $lastAggregationTimeValue = 0;
        if ($lastAggregationTimeres
            && ($lastAggregationTimerow = $database->sql_fetch_row($lastAggregationTimeres))
            && $lastAggregationTimerow[0] != null
        ) {
            $lastAggregationTimeValue = $lastAggregationTimerow[0];
        }

        $endres = $database->sql_query('SELECT max(crdate) FROM tx_commerce_order_articles');
        $endtime2 = 0;
        if ($endres && ($endrow = $database->sql_fetch_row($endres))) {
            $endtime2 = $endrow[0];
        }
        $starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

        if ($starttime <= $this->statistics->firstSecondOfDay($endtime2) and $endtime2 != null) {
            $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

            $this->log(
                'Incremental Sales Aggregation for sales for the period from ' . $starttime . ' to ' . $endtime .
                ' (Timestamp)'
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

        $changeres = $database->sql_query(
            'SELECT distinct crdate FROM tx_commerce_order_articles where tstamp > '.
            ($lastAggregationTimeValue - ($this->statistics->getDaysBack() * 24 * 60 * 60))
        );
        $changeDaysArray = [];
        $changes = 0;
        $result = '';
        while ($changeres && ($changerow = $database->sql_fetch_assoc($changeres))) {
            $starttime = $this->statistics->firstSecondOfDay($changerow['crdate']);
            $endtime = $this->statistics->lastSecondOfDay($changerow['crdate']);

            if (!in_array($starttime, $changeDaysArray)) {
                $changeDaysArray[] = $starttime;
                $this->log(
                    'Incremental Sales UpdateAggregation for sales for the period from ' . $starttime . ' to ' .
                    $endtime . ' (Timestamp)'
                );
                $this->log(
                    'Incremental Sales UpdateAggregation for sales for the period from ' .
                    strftime('%d.%m.%Y', $starttime) . ' to ' . strftime('%d.%m.%Y', $endtime) . ' (DD.MM.YYYY)'
                );

                $result .= $this->statistics->doSalesUpdateAggregation($starttime, $endtime, false);
                ++$changes;
            }
        }

        $this->log($changes . ' Days changed');

        $lastAggregationTimeres = $database->sql_query('SELECT max(tstamp) FROM tx_commerce_newclients');
        if ($lastAggregationTimeres
            && ($lastAggregationTimerow = $database->sql_fetch_row($lastAggregationTimeres))
        ) {
            $lastAggregationTimeValue = $lastAggregationTimerow[0];
        }
        $lastAggregationTimeValue = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

        $endres = $database->sql_query('SELECT max(crdate) FROM fe_users');
        if ($endres && ($endrow = $database->sql_fetch_row($endres))) {
            $endtime2 = $endrow[0];
        }

        if ($lastAggregationTimeValue <= $endtime2 and $endtime2 != null and $lastAggregationTimeValue != null) {
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
     * Complete aggregation.
     *
     * @return void
     */
    protected function completeAggregation()
    {
        $database = $this->getDatabaseConnection();

        $endres = $database->sql_query('SELECT max(crdate) FROM tx_commerce_order_articles');
        $endtime2 = 0;
        if ($endres and ($endrow = $database->sql_fetch_row($endres))) {
            $endtime2 = $endrow[0];
        }

        $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

        $startres = $database->sql_query('SELECT min(crdate) FROM tx_commerce_order_articles WHERE crdate > 0');
        if ($startres and ($startrow = $database->sql_fetch_row($startres)) and $startrow[0] != null) {
            $starttime = $startrow[0];
            $database->sql_query('truncate tx_commerce_salesfigures');
            if (!$this->statistics->doSalesAggregation($starttime, $endtime)) {
                $this->log('problems with completeAgregation of Sales');
            }
        } else {
            $this->log('no sales data available');
        }

        $endres = $database->sql_query('SELECT max(crdate) FROM fe_users');
        if ($endres and ($endrow = $database->sql_fetch_row($endres))) {
            $endtime2 = $endrow[0];
        }

        $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

        $startres = $database->sql_query('SELECT min(crdate) FROM fe_users WHERE crdate > 0 AND deleted = 0');
        if ($startres and ($startrow = $database->sql_fetch_row($startres)) and $startrow[0] != null) {
            $starttime = $startrow[0];
            $database->sql_query('truncate tx_commerce_newclients');
            if (!$this->statistics->doClientAggregation($starttime, $endtime)) {
                $this->log('Probvlems with cle complete agregation Clients');
            }
        } else {
            $this->log('no client data available');
        }
    }

    /**
     * Log message.
     *
     * @param string $message Message
     * @param int $status Status
     * @param string $code Code
     *
     * @return void
     */
    public function log($message, $status = 0, $code = 'commerce')
    {
        $this->getBackendUser()->writelog(
            4,
            0,
            $status,
            $code,
            '[commerce]: ' . $message,
            []
        );
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

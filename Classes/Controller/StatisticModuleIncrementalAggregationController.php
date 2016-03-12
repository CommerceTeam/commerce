<?php
namespace CommerceTeam\Commerce\Controller;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class StatisticModuleIncrementalAggregationController extends StatisticModuleController
{
    /**
     * @return string
     */
    public function getSubModuleContent()
    {
        $database = $this->getDatabaseConnection();

        $result = '';
        if (GeneralUtility::_POST('incrementalaggregation')) {
            $lastAggregationRow = $database->exec_SELECTgetSingleRow('MAX(tstamp)', 'tx_commerce_salesfigures', '1');
            $lastAggregationTimeValue = 0;
            if ($lastAggregationRow && $lastAggregationRow[0] != null) {
                $lastAggregationTimeValue = $lastAggregationRow[0];
            }

            $endrow = $database->exec_SELECTgetSingleRow('MAX(crdate)', 'tx_commerce_order_articles', '1');
            $endtime2 = 0;
            if ($endrow && $endrow[0] != null) {
                $endtime2 = $endrow[0];
            }
            $starttime = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);

            if ($starttime <= $this->statistics->firstSecondOfDay($endtime2) and $endtime2 != null) {
                $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

                echo 'Incremental Sales Agregation for sales for the period from ' . strftime('%d.%m.%Y', $starttime) .
                    ' to ' . strftime('%d.%m.%Y', $endtime) . ' (DD.MM.YYYY)<br />' . LF;
                flush();
                $result .= $this->statistics->doSalesAggregation($starttime, $endtime);
            } else {
                $result .= 'No new Orders<br />';
            }

            $changeWhere = 'tstamp > ' . (
                $lastAggregationTimeValue -
                ($this->statistics->getDaysBack() * 24 * 60 * 60)
            );
            $changeres = $database->exec_SELECTquery('DISTINCT crdate', 'tx_commerce_order_articles', $changeWhere);
            $changeDaysArray = [];
            $changes = 0;
            while ($changeres && ($changerow = $database->sql_fetch_assoc($changeres))) {
                $starttime = $this->statistics->firstSecondOfDay($changerow['crdate']);
                $endtime = $this->statistics->lastSecondOfDay($changerow['crdate']);

                if (!in_array($starttime, $changeDaysArray)) {
                    $changeDaysArray[] = $starttime;

                    echo 'Incremental Sales Udpate Agregation for sales for the day ' .
                        strftime('%d.%m.%Y', $starttime) . ' <br />' . LF;
                    flush();
                    $result .= $this->statistics->doSalesUpdateAggregation($starttime, $endtime);
                    ++$changes;
                }
            }

            $result .= $changes . ' Days changed<br />';

            $lastAggregationRow = $database->exec_SELECTgetSingleRow('MAX(tstamp)', 'tx_commerce_newclients', '1');
            if ($lastAggregationRow && $lastAggregationRow[0] != null) {
                $lastAggregationTimeValue = $lastAggregationRow[0];
            }

            $endrow = $database->exec_SELECTgetSingleRow('MAX(crdate)', 'fe_users', '1');
            if ($endrow && $endrow[0] != null) {
                $endtime2 = $endrow[0];
            }
            if ($lastAggregationTimeValue <= $endtime2 && $endtime2 != null && $lastAggregationTimeValue != null) {
                $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

                $starttime = strtotime('0', $lastAggregationTimeValue);
                if (empty($starttime)) {
                    $starttime = $lastAggregationTimeValue;
                }

                echo 'Incremental Sales Udpate Agregation for sales for the period from ' .
                    strftime('%d.%m.%Y', $starttime) . ' to ' . strftime('%d.%m.%Y', $endtime) .
                    ' (DD.MM.YYYY)<br />' . LF;
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
}

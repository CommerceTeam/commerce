<?php
namespace CommerceTeam\Commerce\Controller;

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
            $lastAggregationTimeres = $database->exec_SELECTquery('MAX(tstamp)', 'tx_commerce_salesfigures', '1=1');
            $lastAggregationTimeValue = 0;
            if ($lastAggregationTimeres
                and ($lastAggregationTimerow = $database->sql_fetch_row($lastAggregationTimeres))
                and $lastAggregationTimerow[0] != null
            ) {
                $lastAggregationTimeValue = $lastAggregationTimerow[0];
            }

            $endres = $database->exec_SELECTquery('MAX(crdate)', 'tx_commerce_order_articles', '1=1');
            $endtime2 = 0;
            if ($endres and ($endrow = $database->sql_fetch_row($endres))) {
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
            while ($changeres and ($changerow = $database->sql_fetch_assoc($changeres))) {
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

            $lastAggregationTimeres = $database->exec_SELECTquery('MAX(tstamp)', 'tx_commerce_newclients', '1=1');
            if ($lastAggregationTimeres
                && ($lastAggregationTimerow = $database->sql_fetch_row($lastAggregationTimeres))
            ) {
                $lastAggregationTimeValue = $lastAggregationTimerow[0];
            }

            $endres = $database->exec_SELECTquery('MAX(crdate)', 'fe_users', '1=1');
            if ($endres and ($endrow = $database->sql_fetch_row($endres))) {
                $endtime2 = $endrow[0];
            }
            if ($lastAggregationTimeValue <= $endtime2 and $endtime2 != null and $lastAggregationTimeValue != null) {
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

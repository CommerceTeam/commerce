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

class StatisticModuleCompleteAggregationController extends StatisticModuleController
{
    /**
     * @return string
     */
    public function getSubModuleContent()
    {
        $database = $this->getDatabaseConnection();

        $result = '';
        if (GeneralUtility::_POST('fullaggregation')) {
            $endres = $database->exec_SELECTquery('MAX(crdate)', 'tx_commerce_order_articles', '1=1');
            $endtime2 = 0;
            if ($endres && ($endrow = $database->sql_fetch_row($endres))) {
                $endtime2 = $endrow[0];
            }

            $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

            $startres = $database->exec_SELECTquery(
                'MIN(crdate)',
                'tx_commerce_order_articles',
                'crdate > 0 AND deleted = 0'
            );
            if ($startres and ($startrow = $database->sql_fetch_row($startres)) and $startrow[0] != null) {
                $starttime = $startrow[0];
                $database->sql_query('truncate tx_commerce_salesfigures');
                $result .= $this->statistics->doSalesAggregation($starttime, $endtime);
            } else {
                $result .= 'no sales data available';
            }

            $endres = $database->exec_SELECTquery('MAX(crdate)', 'fe_users', '1=1');
            if ($endres and ($endrow = $database->sql_fetch_row($endres))) {
                $endtime2 = $endrow[0];
            }

            $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

            $startres = $database->exec_SELECTquery('MIN(crdate)', 'fe_users', 'crdate > 0 AND deleted = 0');
            if ($startres and ($startrow = $database->sql_fetch_row($startres)) and $startrow[0] != null) {
                $starttime = $startrow[0];
                $database->sql_query('truncate tx_commerce_newclients');
                $result = $this->statistics->doClientAggregation($starttime, $endtime);
            } else {
                $result .= '<br />no client data available';
            }
        } else {
            $language = $this->getLanguageService();

            $result = $language->getLL('may_take_long_periode') . '<br /><br />';
            $result .= sprintf(
                '<input type="submit" name="fullaggregation" value="%s" />',
                $language->getLL('complete_aggregation')
            );
        }

        return $result;
    }
}

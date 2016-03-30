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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;

class StatisticModuleShowStatisticsController extends StatisticModuleController
{
    /**
     * @return string
     */
    public function getSubModuleContent()
    {
        $language = $this->getLanguageService();
        $database = $this->getDatabaseConnection();

        $orderPageId = FolderRepository::initFolders('Orders', FolderRepository::initFolders());

        $whereClause = '';
        if ($this->id != $orderPageId) {
            $whereClause = 'pid = ' . $this->id;
        }
        $weekdays = [
            $language->getLL('sunday'),
            $language->getLL('monday'),
            $language->getLL('tuesday'),
            $language->getLL('wednesday'),
            $language->getLL('thursday'),
            $language->getLL('friday'),
            $language->getLL('saturday'),
        ];

        $tables = '';
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show')) {
            $whereClause = $whereClause != '' ? $whereClause . ' AND' : '';
            $whereClause .= ' month = ' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('month') . '  AND year = ' .
                \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('year');

            $tables .= '<h2>' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('month') . ' - ' .
                \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('year') .
                '</h2><table><tr><th>Days</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
            $statRows = $database->exec_SELECTgetRows(
                'SUM(pricegross) AS turnover, SUM(amount) AS salesfigures, SUM(orders) AS sumorders, day',
                'tx_commerce_salesfigures',
                $whereClause,
                'day'
            );
            $daystat = [];
            foreach ($statRows as $statRow) {
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
                    $tablestemp = '<tr><td>' . $daystat[$i]['day'] .
                        '</a></td><td align="right">%01.2f</td><td align="right">' . $daystat[$i]['salesfigures'] .
                        '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
                    $tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
                } else {
                    $tablestemp = '<tr><td>' . $i .
                        '</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
                    $tables .= sprintf($tablestemp, 0);
                }
            }
            $tables .= '</table>';

            $tables .= '<table><tr><th>Weekday</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
            $statRows = $database->exec_SELECTgetRows(
                'SUM(pricegross) AS turnover, SUM(amount) AS salesfigures, SUM(orders) AS sumorders, dow',
                'tx_commerce_salesfigures',
                $whereClause,
                'dow'
            );

            $daystat = [];
            foreach ($statRows as $statRow) {
                $daystat[$statRow['dow']] = $statRow;
            }
            for ($i = 0; $i <= 6; ++$i) {
                if (array_key_exists($i, $daystat)) {
                    $tablestemp = '<tr><td>' . $weekdays[$daystat[$i]['dow']] .
                        '</a></td><td align="right">%01.2f</td><td align="right">' . $daystat[$i]['salesfigures'] .
                        '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
                    $tables .= sprintf($tablestemp, ($daystat[$i]['turnover'] / 100));
                } else {
                    $tablestemp = '<tr><td>' . $weekdays[$i] .
                        '</a></td><td align="right">%01.2f</td><td align="right">0</td><td align="right">0</td></tr>';
                    $tables .= sprintf($tablestemp, 0);
                }
            }
            $tables .= '</table>';

            $tables .= '<table><tr><th>Hour</th><th>turnover</th><th>amount</th><th>orders</th></tr>';
            $statRows = $database->exec_SELECTgetRows(
                'SUM(pricegross) AS turnover, SUM(amount) AS salesfigures, SUM(orders) AS sumorders, hour',
                'tx_commerce_salesfigures',
                $whereClause,
                'hour'
            );

            $daystat = [];
            foreach ($statRows as $statRow) {
                $daystat[$statRow['hour']] = $statRow;
            }
            for ($i = 0; $i <= 23; ++$i) {
                if (array_key_exists($i, $daystat)) {
                    $tablestemp = '<tr><td>' . $i .
                        '</a></td><td align="right">%01.2f</td><td align="right">' . $daystat[$i]['salesfigures'] .
                        '</td><td align="right">' . $daystat[$i]['sumorders'] . '</td></tr>';
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
                'SUM(pricegross) AS turnover, SUM(amount) AS salesfigures, SUM(orders) AS sumorders, year, month',
                'tx_commerce_salesfigures',
                $whereClause,
                'year, month'
            );

            while (($statRow = $database->sql_fetch_assoc($statResult))) {
                $tablestemp = '<tr><td><a href="?id=' . $this->id . '&amp;month=' . $statRow['month'] .
                    '&amp;year=' . $statRow['year'] . '&amp;show=details">' . $statRow['month'] . '.' .
                    $statRow['year'] . '</a></td><td align="right">%01.2f</td><td align="right">' .
                    $statRow['salesfigures'] . '</td><td align="right">' . $statRow['sumorders'] . '</td></tr>';
                $tables .= sprintf($tablestemp, ($statRow['turnover'] / 100));
            }
            $tables .= '</table>';
        }

        return $tables;
    }
}

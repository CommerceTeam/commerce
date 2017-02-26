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

use CommerceTeam\Commerce\Domain\Repository\FrontendUserRepository;
use CommerceTeam\Commerce\Domain\Repository\NewClientsRepository;
use CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\SalesFiguresRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StatisticModuleIncrementalAggregationController extends StatisticModuleController
{
    /**
     * @return string
     */
    public function getSubModuleContent()
    {
        $result = '';
        if (GeneralUtility::_GP('incrementalaggregation')) {
            /** @var OrderArticleRepository $orderArticleRepository */
            $orderArticleRepository = $this->getObjectManager()->get(OrderArticleRepository::class);
            /** @var SalesFiguresRepository $salesFiguresRepository */
            $salesFiguresRepository = $this->getObjectManager()->get(SalesFiguresRepository::class);
            /** @var NewClientsRepository $newClientsRepository */
            $newClientsRepository = $this->getObjectManager()->get(NewClientsRepository::class);
            /** @var FrontendUserRepository $frontendUserRepository */
            $frontendUserRepository = $this->getObjectManager()->get(FrontendUserRepository::class);

            $lastAggregationTimeValue = $salesFiguresRepository->findHighestTimestamp();
            $endtime2 = $orderArticleRepository->findHighestCreationDate();

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

            $changeResult = $orderArticleRepository->findDistinctCreationDatesSince(
                $lastAggregationTimeValue - ($this->statistics->getDaysBack() * 24 * 60 * 60)
            );
            $changeDaysArray = [];
            $changes = 0;
            while ($changerow = $changeResult->fetch()) {
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

            $lastAggregationTimeValue = $newClientsRepository->findHighestTimestamp();
            $endtime2 = $frontendUserRepository->findHighestTimestamp();
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

            $onClickAction = 'onClick="window.location.href=\'' . BackendUtility::getModuleUrl(
                $this->moduleName,
                [
                    'id' => $this->id,
                    'SET' => [
                        'function' => self::class
                    ],
                    'incrementalaggregation' => $language->getLL('incremental_aggregation'),
                ]
            ) . '\'"';

            $result = $language->getLL('may_take_long_periode') . '<br /><br />';
            $result .= '<input type="submit" name="fullaggregation" value="' .
                $language->getLL('incremental_aggregation') .  '" ' . $onClickAction . ' />';
        }

        return $result;
    }
}

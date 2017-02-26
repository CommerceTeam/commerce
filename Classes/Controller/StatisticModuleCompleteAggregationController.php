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

class StatisticModuleCompleteAggregationController extends StatisticModuleController
{
    /**
     * @return string
     */
    public function getSubModuleContent()
    {
        /** @var OrderArticleRepository $orderArticleRepository */
        $orderArticleRepository = $this->getObjectManager()->get(OrderArticleRepository::class);
        /** @var FrontendUserRepository $frontendUserRepository */
        $frontendUserRepository = $this->getObjectManager()->get(FrontendUserRepository::class);

        $result = '';
        if (GeneralUtility::_GP('fullaggregation')) {
            $endtime2 = $orderArticleRepository->findHighestCreationDate();
            $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

            $starttime = $orderArticleRepository->findLowestCreationDate();
            if ($starttime > 0) {
                /** @var SalesFiguresRepository $salesFiguresRepository */
                $salesFiguresRepository = $this->getObjectManager()->get(SalesFiguresRepository::class);
                $salesFiguresRepository->truncate();
                $result .= $this->statistics->doSalesAggregation($starttime, $endtime);
            } else {
                $result .= 'no sales data available';
            }

            $endtime2 = $frontendUserRepository->findHighestCreationDate();
            $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

            $starttime = $frontendUserRepository->findLowestCreationDate();
            if ($starttime > 0) {
                $newClientsRepository = $this->getObjectManager()->get(NewClientsRepository::class);
                $newClientsRepository->truncate();
                $result = $this->statistics->doClientAggregation($starttime, $endtime);
            } else {
                $result .= '<br />no client data available';
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
                    'fullaggregation' => $language->getLL('complete_aggregation'),
                ]
            ) . '\'"';

            $result = $language->getLL('may_take_long_periode') . '<br /><br />';
            $result .= '<input type="submit" name="fullaggregation" value="' .
                $language->getLL('complete_aggregation') .  '" ' . $onClickAction . ' />';
        }

        return $result;
    }
}

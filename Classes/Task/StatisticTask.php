<?php
namespace CommerceTeam\Commerce\Task;

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
        $result = '';
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

        if ($starttime <= $this->statistics->firstSecondOfDay($endtime2) && $endtime2 != null) {
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

        $lastAggregationTimeValue = $newClientsRepository->findHighestTimestamp();
        $lastAggregationTimeValue = $this->statistics->firstSecondOfDay($lastAggregationTimeValue);
        $endtime2 = $frontendUserRepository->findHighestTimestamp();
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
            $this->log('No new Customers');
        }
    }

    /**
     * Complete aggregation.
     *
     * @return void
     */
    protected function completeAggregation()
    {
        /** @var OrderArticleRepository $orderArticleRepository */
        $orderArticleRepository = $this->getObjectManager()->get(OrderArticleRepository::class);
        /** @var FrontendUserRepository $frontendUserRepository */
        $frontendUserRepository = $this->getObjectManager()->get(FrontendUserRepository::class);

        $endtime2 = $orderArticleRepository->findHighestCreationDate();
        $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

        $starttime = $orderArticleRepository->findLowestCreationDate();
        if ($starttime > 0) {
            /** @var SalesFiguresRepository $salesFiguresRepository */
            $salesFiguresRepository = $this->getObjectManager()->get(SalesFiguresRepository::class);
            $salesFiguresRepository->truncate();
            if (!$this->statistics->doSalesAggregation($starttime, $endtime)) {
                $this->log('Problems with completeAggregation of Sales');
            }
        } else {
            $this->log('no sales data available');
        }

        $endtime2 = $frontendUserRepository->findHighestCreationDate();
        $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

        $starttime = $frontendUserRepository->findLowestCreationDate();
        if ($starttime > 0) {
            /** @var NewClientsRepository $newClientsRepository */
            $newClientsRepository = $this->getObjectManager()->get(NewClientsRepository::class);
            $newClientsRepository->truncate();
            if (!$this->statistics->doClientAggregation($starttime, $endtime)) {
                $this->log('Problems with completeAggregation of Clients');
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
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager(): \TYPO3\CMS\Extbase\Object\ObjectManager
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Extbase\Object\ObjectManager::class
        );
        return $objectManager;
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

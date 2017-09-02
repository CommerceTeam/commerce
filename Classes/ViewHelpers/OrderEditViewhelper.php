<?php
namespace CommerceTeam\Commerce\ViewHelpers;

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
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * User Class for displaying Orders.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\OrderEditViewhelper
 */
class OrderEditViewhelper
{
    /**
     * Oder Articles
     * Renders the List of aricles.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function orderArticles(array $parameter)
    {
        $settingsFactory = ConfigurationUtility::getInstance();

        // GET Storage PID and order_id from Data
        $orderStoragePid = $parameter['row']['pid'];
        $orderId = $parameter['row']['order_id'];

        $fields = GeneralUtility::trimExplode(',', $settingsFactory->getConfiguration('orderArticleFields'));
        $titleField = $settingsFactory->getTcaValue('tx_commerce_order_articles.ctrl.label');

        // Check if orders in this folder are editable
        /**
         * Page repository.
         *
         * @var \CommerceTeam\Commerce\Domain\Repository\PageRepository $pageRepository
         */
        $pageRepository = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Repository\PageRepository::class);
        $orderEditable = !empty($pageRepository->findEditFolderByUid($orderStoragePid));

        /**
         * Order article repository.
         *
         * @var \CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository $orderArticleRepository
         */
        $orderArticleRepository = GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository::class
        );
        $orderArticles = $orderArticleRepository->findByOrderIdInPage($orderId, $orderStoragePid);

        $sum = [];
        $items = [];
        $taxCache = [];
        if (!empty($orderArticles)) {
            foreach ($orderArticles as $row) {
                $sum['amount'] += $row['amount'];

                if (!isset($taxCache[$row['tax']])) {
                    $taxCache[$row['tax']] = 100 + $row['tax'];
                }

                if ($parameter['row']['pricefromnet'] == 1) {
                    $row['price_net'] = $row['price_net'] * $row['amount'];
                    $row['price_gross'] = $row['price_net'] * $taxCache[$row['tax']] / 100;
                } else {
                    $row['price_gross'] = $row['price_gross'] * $row['amount'];
                    $row['price_net'] = $row['price_gross'] / $taxCache[$row['tax']] * 100;
                }

                $sum['price_net'] += $row['price_net'];
                $sum['price_gross'] += $row['price_gross'];

                $row['price_net'] = $row['price_net'] / 100;
                $row['price_gross'] = $row['price_gross'] / 100;
                $items[] = $row;
            }

            /*
             * Always
             * Update sum_price_net and sum_price_gross
             * To Be shure everything is ok
             */
            $sum['price_gross'] = $sum['price_gross'] / 100;
            $sum['price_net'] = $sum['price_net'] / 100;

            $values = [
                'sum_price_gross' => $sum['price_gross'],
                'sum_price_net' => $sum['price_net'],
            ];
            /**
             * Order repository.
             *
             * @var \CommerceTeam\Commerce\Domain\Repository\OrderRepository $orderRepository
             */
            $orderRepository = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Repository\OrderRepository::class
            );
            $orderRepository->updateByOrderId($orderId, $values);
        }

        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([1 => 'EXT:commerce/Resources/Private/Backend/']);
        $view->setTemplate('OrderItems');

        $view->assign('table', 'tx_commerce_order_articles');
        $view->assign('fields', $fields);
        $view->assign('titleField', $titleField);
        $view->assign('orderEditable', $orderEditable);
        $view->assign('orderId', $orderId);
        $view->assign('invoicePageId', $settingsFactory->getExtConf('invoicePageID'));
        $view->assign('invoicePageType', $settingsFactory->getExtConf('invoicePageType'));
        $view->assign('items', $items);
        $view->assign('sum', $sum);

        $content = $view->render();

        return $content;
    }

    /**
     * Order Status
     * Selects only the order folders from the pages List.
     *
     * @param array $data Data
     */
    public function orderStatus(array &$data)
    {
        // Create a new data item array
        $data['items'] = [];

        // Find the right pid for the Ordersfolder
        $orderPid = FolderRepository::initFolders('Orders', FolderRepository::initFolders());

        /*
         * Get the pages below $orderPid
         */

        /*
         * Check if the Current PID is below $orderPid,
         * id is below orderPid we could use the parent of
         * this record to build up the select Drop Down
         * otherwhise use the default PID
         */
        $localOrderPid = $data['row']['pid'];

        $rootline = BackendUtility::BEgetRootLine($localOrderPid);
        $rootlinePids = [];
        foreach ($rootline as $pages) {
            if (isset($pages['uid'])) {
                $rootlinePids[] = $pages['uid'];
            }
        }

        if (in_array($orderPid, $rootlinePids)) {
            /**
             * Page repository.
             *
             * @var \CommerceTeam\Commerce\Domain\Repository\PageRepository $pageRepository
             */
            $pageRepository = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Repository\PageRepository::class
            );
            $page = $pageRepository->findByUid($localOrderPid);
            if (!empty($page)) {
                $orderPid = (int) $page['pid'];
            }
        }

        $items = \CommerceTeam\Commerce\Utility\BackendUtility::getOrderFolderSelector(
            $orderPid,
            ConfigurationUtility::getInstance()->getExtConf('OrderFolderRecursiveLevel')
        );
        $data['items'] = array_merge([['' => 0]], $items);
    }

    /**
     * Renders an address block by uid
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function address(array $parameter)
    {
        $table = 'tt_address';
        $uid = (int) $parameter['itemFormElValue'];
        $fields = 'uid,' . ConfigurationUtility::getInstance()->getTcaValue($table . '.interface.showRecordFieldList');
        $content = '';

        /*
         * If records is available in database
         */
        if ($data = BackendUtility::getRecord($table, $uid, $fields)) {
            $address = [];
            foreach ($data as $key => $value) {
                $address[$key] = [
                    'value' => $value,
                    'label' => $GLOBALS['TCA'][$table]['columns'][$key]['label'],
                ];
            }
            unset($address['uid']);

            /** @var StandaloneView $view */
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplateRootPaths([1 => 'EXT:commerce/Resources/Private/Backend/']);
            $view->setTemplate('Address');

            $view->assign('table', $table);
            $view->assign('data', $data);
            $view->assign('address', $address);

            $content .= $view->render();
        }

        $content .= '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' . $uid . '">';

        return $content;
    }
}

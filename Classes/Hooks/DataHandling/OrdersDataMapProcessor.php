<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\OrderRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OrdersDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * Process Data when saving Order
     * Change the PID from this order via the new field newpid
     * As TYPO3 don't allows changing the PId directly.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     *
     * @return array
     */
    public function preProcess(array &$incomingFieldArray, $id)
    {
        $incomingFieldArray['crdate'] = null;

        if (isset($incomingFieldArray['newpid'])) {
            /** @var OrderRepository $orderRepository */
            $orderRepository = GeneralUtility::makeInstance(OrderRepository::class);
            /** @var OrderArticleRepository $orderArticleRepository */
            $orderArticleRepository = GeneralUtility::makeInstance(OrderArticleRepository::class);

            $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Hook/DataHandlerHook', 'preProcessOrder');

            // Add first the pid filled
            $incomingFieldArray['pid'] = $incomingFieldArray['newpid'];

            // Move Order articles
            $order = $orderRepository->findByUid($id);
            if (!empty($order)) {
                if ($order['pid'] != $incomingFieldArray['newpid']) {
                    // order_sys_language_uid is not always set in fieldArray so we overwrite
                    // it with our order data
                    if ($incomingFieldArray['order_sys_language_uid'] === null) {
                        $incomingFieldArray['order_sys_language_uid'] = $order['order_sys_language_uid'];
                    }

                    foreach ($hooks as $hookObj) {
                        if (method_exists($hookObj, 'moveOrdersPreMoveOrder')) {
                            $hookObj->moveOrdersPreMoveOrder($order, $incomingFieldArray);
                        }
                    }

                    $orderArticelRows = $orderArticleRepository->findByOrderId($order['order_id']);
                    if (!empty($orderArticelRows)) {
                        // Run trough all articles from this order and move it to other storage folder
                        foreach ($orderArticelRows as $orderArticelRow) {
                            $orderArticelRow['pid'] = $incomingFieldArray['newpid'];
                            $orderArticelRow['tstamp'] = $GLOBALS['EXEC_TIME'];

                            $orderArticleRepository->updateRecord($orderArticelRow['uid'], $orderArticelRow);
                        }
                    }
                    $order['pid'] = $incomingFieldArray['newpid'];
                    $order['tstamp'] = $GLOBALS['EXEC_TIME'];

                    $orderRepository->updateRecord((int) $order['uid'], $order);

                    foreach ($hooks as $hookObj) {
                        if (method_exists($hookObj, 'moveOrdersPostMoveOrder')) {
                            $hookObj->moveOrdersPostMoveOrder($order, $incomingFieldArray);
                        }
                    }
                }
            }
        }

        return [];
    }
}

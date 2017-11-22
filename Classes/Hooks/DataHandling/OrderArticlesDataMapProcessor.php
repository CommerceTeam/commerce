<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\OrderRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OrderArticlesDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * Recalculate Order sum when saving order articles
     *
     * @param array $_
     * @param int $orderArticleUid Order article id
     *
     * @return array
     */
    public function preProcess(array &$_, $orderArticleUid)
    {
        /** @var OrderArticleRepository $orderArticleRepository */
        $orderArticleRepository = GeneralUtility::makeInstance(OrderArticleRepository::class);
        $orderArticleRow = $orderArticleRepository->findByUid($orderArticleUid);
        if (!empty($orderArticleRow)) {
            $orderId = $orderArticleRow['order_id'];
            $sum = [
                'sum_price_gross' => 0,
                'sum_price_net' => 0
            ];

            $orderArticles = $orderArticleRepository->findByOrderId($orderId);
            if (!empty($orderArticles)) {
                foreach ($orderArticles as $orderArticle) {
                    /*
                     * Calculate Sums
                     */
                    $sum['sum_price_gross'] += $orderArticle['amount'] * $orderArticle['price_net'];
                    $sum['sum_price_net'] += $orderArticle['amount'] * $orderArticle['price_gross'];
                }
            }

            /** @var OrderRepository $orderRepository */
            $orderRepository = GeneralUtility::makeInstance(OrderRepository::class);
            $orderRepository->updateByOrderId($orderId, $sum);
        }

        return [];
    }
}

<?php
namespace CommerceTeam\Commerce\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Provider to fix centurion division
 *
 * @package CommerceTeam\Commerce\Form\FormDataProvider
 */
class DatabaseRowPriceData implements FormDataProviderInterface
{
    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if ($result['tableName'] == 'tx_commerce_article_prices') {
            $databaseRow = &$result['databaseRow'];
            $priceFields = ['price_net', 'price_gross', 'purchase_price'];
            foreach ($priceFields as $priceField) {
                if (isset($databaseRow[$priceField])) {
                    $databaseRow[$priceField] = sprintf('%01.2f', round($databaseRow[$priceField] / 100, 2));
                }
            }
        }

        return $result;
    }
}

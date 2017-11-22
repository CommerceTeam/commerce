<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

class ArticlePricesDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * Preprocess article price.
     *
     * @param array $incomingFields Incoming field array
     * @param mixed $_ unused
     *
     * @return array
     */
    public function preProcess(array &$incomingFields, $_)
    {
        if (isset($incomingFields['price_gross']) && $incomingFields['price_gross']) {
            $incomingFields['price_gross'] = $this->centurionMultiplication($incomingFields['price_gross']);
        }

        if (isset($incomingFields['price_net']) && $incomingFields['price_net']) {
            $incomingFields['price_net'] = $this->centurionMultiplication($incomingFields['price_net']);
        }

        if (isset($incomingFields['purchase_price']) && $incomingFields['purchase_price']) {
            $incomingFields['purchase_price'] = $this->centurionMultiplication($incomingFields['purchase_price']);
        }

        return [];
    }

    /**
     * Centurion multiplication.
     *
     * @param float $price Price
     *
     * @return int
     */
    protected function centurionMultiplication($price)
    {
        return intval(strval(str_replace(',', '.', $price) * 100));
    }
}

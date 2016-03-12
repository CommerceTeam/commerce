<?php
namespace CommerceTeam\Commerce\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CommerceTeam\Commerce\Domain\Repository\CurrencyRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Code library for display of different currencies
 * widely used in EXT: commerce.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\MoneyViewHelper
 */
class MoneyViewHelper
{
    /**
     * Use this function from TS, example:
     * price_net = stdWrap
     * price_net {
     *      postUserFunc = \CommerceTeam\Commerce\ViewHelpers\MoneyViewHelper->tsFormat
     *      postUserFunc.currency = EUR
     *      postUserFunc.withSymbol = 0
     * }
     *
     * @param string $content Content
     * @param array $conf Config
     *
     * @return string representation of the amount including currency symbol(s)
     */
    public function tsFormat($content, array $conf)
    {
        $withSymbol = is_null($conf['withSymbol']) ? true : (bool)$conf['withSymbol'];

        return static::format($content, $conf['currency'], $withSymbol);
    }

    /**
     * Returns the given amount as a formatted string according to the
     * given currency.
     * IMPORTANT NOTE:
     * The amount must always be the smallest unit passed as a string
     * or int! It is a very bad idea to use float for monetary
     * calculations if you need exact values, therefore
     * this method won't accept float values.
     * Examples:
     *      format (500, 'EUR');      --> '5,00 EUR'
     *      format (4.23, 'EUR');     --> FALSE
     *      format ('872331', 'EUR'); --> '8.723,31 EUR'.
     *
     * @param int|string $amount Amount to be formatted. Must be the smalles unit
     * @param string $currencyKey ISO 3 letter code of the currency
     * @param bool $withSymbol If set the currency symbol will be rendered
     *
     * @return string|bool String representation of the amount including currency
     *      symbol(s) or FALSE if $amount was of the type float
     */
    public static function format($amount, $currencyKey, $withSymbol = true)
    {
        if (is_float($amount)) {
            return false;
        }

        /**
         * Currency repository.
         *
         * @var CurrencyRepository
         */
        $currencyRepository = GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Repository\CurrencyRepository::class
        );
        $currency = $currencyRepository->findByIso3($currencyKey);

        if (empty($currency)) {
            return false;
        }

        $formattedAmount = number_format(
            $amount / $currency['cu_sub_divisor'],
            $currency['cu_decimal_digits'],
            $currency['cu_decimal_point'],
            $currency['cu_thousands_point']
        );

        if ($withSymbol) {
            $wholeString = $formattedAmount;
            if (!empty($currency['cu_symbol_left'])) {
                $wholeString = $currency['cu_symbol_left'] . ' ' . $wholeString;
            }
            if (!empty($currency['cu_symbol_right'])) {
                $wholeString .= ' ' . $currency['cu_symbol_right'];
            }
        } else {
            $wholeString = $formattedAmount;
        }

        return (string)$wholeString;
    }
}

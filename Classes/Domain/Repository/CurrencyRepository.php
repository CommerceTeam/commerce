<?php
namespace CommerceTeam\Commerce\Domain\Repository;

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

/**
 * Class \CommerceTeam\Commerce\Domain\Repository\CurrencyRepository
 */
class CurrencyRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'static_currencies';

    /**
     * Find by iso 3 key.
     *
     * @param string $iso3 Iso 3 key of currency
     *
     * @return array
     */
    public function findByIso3($iso3)
    {
        $queryBrowser = $this->getQueryBuilderForTable($this->databaseTable);
        $row = $queryBrowser
            ->select(
                'cu_symbol_left',
                'cu_symbol_right',
                'cu_sub_symbol_left',
                'cu_sub_symbol_right',
                'cu_decimal_point',
                'cu_thousands_point',
                'cu_decimal_digits',
                'cu_sub_divisor'
            )
            ->from($this->databaseTable)
            ->where(
                $queryBrowser->expr()->eq(
                    'cu_iso_3',
                    $queryBrowser->createNamedParameter(strtoupper($iso3), \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

        return is_array($row) ? $row : [];
    }
}

<?php

namespace CommerceTeam\Commerce\Domain\Repository;

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

/**
 * Class \CommerceTeam\Commerce\Domain\Repository\FrontendUserRepository.
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class FrontendUserRepository extends Repository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'fe_users';

    /**
     * Find frontend user by address id.
     *
     * @param int $addressId Address id
     *
     * @return array
     */
    public function findByAddressId($addressId)
    {
        return (array) self::getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            $this->databaseTable,
            'tx_commerce_tt_address_id = '.$addressId.' AND deleted = 0'
        );
    }
}

<?php
namespace CommerceTeam\Commerce\Hooks;

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

use CommerceTeam\Commerce\Controller\AddressesController;

/**
 * Hook for the extension openbc feuser extension
 * This class handles backend updates.
 *
 * Class \CommerceTeam\Commerce\Hook\Pi4Hooks
 */
class Pi4Hook
{
    /**
     * This function is called by the Hook in
     * \CommerceTeam\Commerce\Controller\AddressesController before processing
     * delete address operations return false to permit delete operation.
     *
     * @param int $uid Reference to the incoming fields
     * @param AddressesController $parentObject Parent object
     *
     * @return string error: do not delete message
     */
    public function deleteAddress($uid, AddressesController $parentObject)
    {
        return \CommerceTeam\Commerce\Dao\AddressObserver::checkDelete($uid, $parentObject);
    }

    /**
     * This function is called by the Hook in
     * \CommerceTeam\Commerce\Controller\AddressesController after processing
     * insert address operations.
     *
     * @param int $uid Address uid
     *
     * @return void
     */
    public function afterAddressSave($uid)
    {
        // notify address observer
        \CommerceTeam\Commerce\Dao\AddressObserver::update('new', $uid);
    }

    /**
     * This function is called by the Hook in
     * \CommerceTeam\Commerce\Controller\AddressesController before processing
     * update address operations.
     *
     * @param int $uid Uid
     *
     * @return void
     */
    public function afterAddressEdit($uid)
    {
        // notify address observer
        \CommerceTeam\Commerce\Dao\AddressObserver::update('update', $uid);
    }
}

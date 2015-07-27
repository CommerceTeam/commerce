<?php
namespace CommerceTeam\Commerce\Dao;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * For the takeaday feuser extension
 * The class satisfies the observer design pattern.
 * The method update() from this class is called as static by "hooksHandler"
 * classes
 * This class handles feuser updates.
 *
 * Class \CommerceTeam\Commerce\Dao\FeuserObserver
 *
 * @author 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
 */
class FeuserObserver
{
    /**
     * Link to observable.
     *
     * @var object
     */
    public $observable;

    /**
     * Constructor
     * Link observer and observable
     * Not needed for typo3 hook concept.
     *
     * @param object $observable Observed object
     *
     * @return self
     */
    public function __construct(&$observable)
    {
        $this->observable = &$observable;
        $observable->addObserver($this);
    }

    /**
     * Handle update event.
     * Is called from observable or hook handlers upon event.
     * Keep this method static for efficient integration into hookHandlers.
     * Communicate using push principle to avoid errors.
     *
     * @param string $status Status [update,new]
     * @param string $id Database table
     *
     * @return void
     */
    public static function update($status, $id)
    {
        /**
         * Frontend user data access object.
         *
         * @var FeuserDao
         */
        $feuserDao = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Dao\\FeuserDao', $id);

        // get main address id from feuser object
        $topId = $feuserDao->get('tx_commerce_tt_address_id');

        if (empty($topId)) {
            // get new address object
            /**
             * Address data access object.
             *
             * @var AddressDao
             */
            $addressDao = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Dao\\AddressDao');

            // set feuser uid and main address flag
            $addressDao->set('tx_commerce_fe_user_id', $feuserDao->get('id'));
            $addressDao->set('tx_commerce_is_main_address', '1');

            // set address type if not yet defined
            if (!$addressDao->issetProperty('tx_commerce_address_type_id')) {
                $addressDao->set('tx_commerce_address_type_id', 1);
            }
        } else {
            // get existing address object
            $addressDao = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Dao\\AddressDao', $topId);
        }

        // apply changes to address object
        /**
         * Field mapper.
         *
         * @var FeuserAddressFieldmapper
         */
        $fieldMapper = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Dao\\FeuserAddressFieldmapper');
        $fieldMapper->mapFeuserToAddress($feuserDao, $addressDao);

        // save address object
        $addressDao->save();

        // update main address id
        if ($topId != $addressDao->get('id')) {
            $feuserDao->set('tx_commerce_tt_address_id', $addressDao->get('id'));
            $feuserDao->save();
        }
    }
}

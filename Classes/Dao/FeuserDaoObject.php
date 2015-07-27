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

/**
 * Feuser object & Dao database access classes
 * These classes handle feuser objects.
 *
 * Class \CommerceTeam\Commerce\Dao\FeuserDaoObject
 *
 * @author 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
 */
class FeuserDaoObject extends BasicDaoObject
{
    /**
     * Address id.
     *
     * @var int
     */
    public $tx_commerce_tt_address_id;

    /**
     * Name.
     *
     * @var string
     */
    protected $name;

    /**
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        // add any mapped fields to object
        /**
         * Frontend user address mapper.
         *
         * @var FeuserAddressFieldmapper
         */
        $feuserAddressMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'CommerceTeam\\Commerce\\Dao\\FeuserAddressFieldmapper'
        );
        $fields = $feuserAddressMapper->getFeuserFields();

        foreach ($fields as $field) {
            $this->$field = null;
        }
    }

    /**
     * Getter.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter.
     *
     * @param string $name Name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Getter.
     *
     * @return int
     */
    public function getTx_commerce_tt_address_id()
    {
        return $this->tx_commerce_tt_address_id;
    }

    /**
     * Setter.
     *
     * @param int $value Value
     *
     * @return void
     */
    public function setTx_commerce_tt_address_id($value)
    {
        $this->tx_commerce_tt_address_id = (int) $value;
    }
}

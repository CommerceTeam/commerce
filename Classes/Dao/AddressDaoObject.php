<?php
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
 * Address object & Dao database access classes
 * These classes handle tt_address objects.
 *
 * Class Tx_Commerce_Dao_AddressDaoObject
 *
 * @author 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Dao_AddressDaoObject extends Tx_Commerce_Dao_BasicDaoObject {
	/**
	 * Frontend user uid
	 *
	 * @var int
	 */
	public $tx_commerce_fe_user_id;

	/**
	 * Address type uid
	 *
	 * @var int
	 */
	public $tx_commerce_address_type_id;

	/**
	 * Flag if address is main
	 *
	 * @var bool
	 */
	public $tx_commerce_is_main_address;

	/**
	 * Name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
			// add mapped fields to object
		/** @var Tx_Commerce_Dao_FeuserAddressFieldmapper $feuserAddressMapper */
		$feuserAddressMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserAddressFieldmapper');
		$fields = $feuserAddressMapper->getAddressFields();

		foreach ($fields as $field) {
			$this->$field = '';
		}
	}

	/**
	 * Getter
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Setter
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressDaoObject.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressDaoObject.php']);
}

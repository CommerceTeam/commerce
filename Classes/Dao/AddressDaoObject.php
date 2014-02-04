<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * address object & Dao database access classes
 * These classes handle tt_address objects.
 */
class Tx_Commerce_Dao_AddressDaoObject extends Tx_Commerce_Dao_BasicDaoObject {
	/**
	 * @var integer
	 */
	public $tx_commerce_fe_user_id;

	/**
	 * @var integer
	 */
	public $tx_commerce_address_type_id;

	/**
	 * @var boolean
	 */
	public $tx_commerce_is_main_address;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @return self
	 */
	public function __construct() {
			// add mapped fields to object
		/** @var Tx_Commerce_Dao_FeuserAddressFieldmapper $feuserAddressMapper */
		$feuserAddressMapper = t3lib_div::makeInstance('Tx_Commerce_Dao_FeuserAddressFieldmapper');
		$fields = $feuserAddressMapper->getAddressFields();

		foreach ($fields as $field) {
			$this->$field = '';
		}
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressDaoObject.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressDaoObject.php']);
}

?>
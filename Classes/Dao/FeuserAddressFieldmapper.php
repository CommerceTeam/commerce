<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 Carsten Lausen <cl@e-netconsulting.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * class feuser address mapper
 * This class handles basic database storage by object mapping.
 * It defines how to insert, update, find and delete a transfer object in the database.
 * The class needs a parser for object <-> model (transfer object) mapping.
 */
class Tx_Commerce_Dao_FeuserAddressFieldmapper {
	/**
	 * @var string
	 */
	protected $mapping;

	/**
	 * @var array
	 */
	protected $feuserFields = array();

	/**
	 * @var array
	 */
	protected $addressFields = array();

	/**
	 * @return self
	 */
	public function __construct() {
		$this->mapping = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['feuser_address_mapping'], ' ;');
	}

	/**
	 * @return array
	 */
	public function getAddressFields() {
		if (empty($this->addressFields)) {
			$this->explodeMapping();
		}

		return $this->addressFields;
	}

	/**
	 * @return array
	 */
	public function getFeuserFields() {
		if (empty($this->feuserFields)) {
			$this->explodeMapping();
		}

		return $this->feuserFields;
	}

	/**
	 * @param Tx_Commerce_Dao_FeuserDao $feuser
	 * @param Tx_Commerce_Dao_AddressDao $address
	 * @return void
	 */
	public function mapFeuserToAddress(&$feuser, &$address) {
		if (empty($this->feuserFields)) {
			$this->explodeMapping();
		}
		foreach ($this->feuserFields as $key => $field) {
			$address->set($this->addressFields[$key], $feuser->get($field));
		}
	}

	/**
	 * @param Tx_Commerce_Dao_AddressDao $address
	 * @param Tx_Commerce_Dao_FeuserDao $feuser
	 * @return void
	 */
	public function mapAddressToFeuser(&$address, &$feuser) {
		if (empty($this->addressFields)) {
			$this->explodeMapping();
		}
		foreach ($this->addressFields as $key => $field) {
			$feuser->set($this->feuserFields[$key], $address->get($field));
		}
	}

	/**
	 * @return void
	 */
	protected function explodeMapping() {
		$map = explode(';', $this->mapping);
		foreach ($map as $singleMap) {
			$singleFields = explode(',', $singleMap);
			$this->feuserFields[] = $singleFields[0];
			$this->addressFields[] = $singleFields[1];
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserAddressFieldmapper.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserAddressFieldmapper.php']);
}

?>
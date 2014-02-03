<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Carsten Lausen
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
* address object & dao database access classes
*
* These classes handle tt_address objects.
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/
class address_object extends basic_object {
	/**
	 * @var
	 */
	public $tx_commerce_fe_user_id;

	/**
	 * @var
	 */
	public $tx_commerce_address_type_id;

	/**
	 * @var
	 */
	public $tx_commerce_is_main_address;

	/**
	 * @var string
	 */
	protected $name;

	public function __construct() {
			// add mapped fields to object
		$fieldmapper = t3lib_div::makeInstance('feuser_address_fieldmapper');
		$field_arr = $fieldmapper->get_address_fieldarray();
		foreach ($field_arr as $field) {
			$this->$field = '';
		}
	}

	/**
	 * @deprecated since commerce 0.14.0, this function will be removed in commerce 0.16.0, please use __construct instead
	 */
	public function address_object() {
		t3lib_div::logDeprecatedFunction();
		$this->__construct();
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_object.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_object.php']);
}

?>
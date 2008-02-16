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
* class feuser address mapper
* This class handles basic database storage by object mapping.
* It defines how to insert, update, find and delete a transfer object in the database.
*
* The class needs a parser for object <-> model (transfer object) mapping.
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/


class feuser_address_fieldmapper {

	var $mapping;
 	var $feuser_field_arr;
 	var $address_field_arr;

 	function feuser_address_fieldmapper() {
	 	$this->mapping = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['feuser_address_mapping']," ;");
 	}

	function get_address_fieldarray() {
		if (empty($this->address_field_arr)) $this->explode_mapping();
		return $this->address_field_arr;
	}

	function get_feuser_fieldarray() {
		if (empty($this->feuser_field_arr)) $this->explode_mapping();
		return $this->feuser_field_arr;
	}

	function map_feuser_to_address(&$feuser_dao, &$address_dao) {
		if (empty($this->feuser_field_arr)) $this->explode_mapping();
		foreach ($this->feuser_field_arr as $key => $field) {
			$address_dao->set($this->address_field_arr[$key],$feuser_dao->get($field));
		}
	}

	function map_address_to_feuser( &$address_dao, &$feuser_dao) {
		if (empty($this->address_field_arr)) $this->explode_mapping();
		foreach ($this->address_field_arr as $key => $field) {
			$feuser_dao->set($this->feuser_field_arr[$key],$address_dao->get($field));
		}
	}


	function explode_mapping() {
		$map_arr = explode(";",$this->mapping);
		foreach($map_arr as $single_map) {
			$single_map_arr = explode(",",$single_map);
			$this->feuser_field_arr[]=$single_map_arr[0];
			$this->address_field_arr[]=$single_map_arr[1];
		}
	}


}
 // Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/dao/class.feuser_address_fieldmapper.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS']['TYPO3_MODE']['XCLASS']['ext/commerce/dao/class.feuser_address_fieldmapper.php']);
}

?>
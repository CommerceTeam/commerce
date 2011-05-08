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

require_once(dirname(__FILE__).'/class.basic_dao.php');
require_once(dirname(__FILE__).'/class.feuser_address_fieldmapper.php');

class address_object extends basic_object {

	var $tx_commerce_fe_user_id;
	var $tx_commerce_address_type_id;
	var $tx_commerce_is_main_address;

	function address_object() {
		//add mapped fields to object
		$fieldmapper =& new feuser_address_fieldmapper;
		$field_arr = $fieldmapper->get_address_fieldarray();
		foreach($field_arr as $field) {
			$this->$field='';
		}
	}

	function getName() {
		return $this->name;
	}

	function setName($name) {
		$this->name = $name;
	}
}

/**
 * address dao parser
 * This class is used by the dao for object/model parsing.
 * It extends the basic dao parser.
 *
 * @author Carsten Lausen <cl@e-netconsulting.de>
 */
class address_dao_parser extends basic_dao_parser {
}


/**
 * address dao mapping
 * This class used by the dao for database storage.
 * It extends the basic dao mapper.
 *
 * @author Carsten Lausen <cl@e-netconsulting.de>
 */
class address_dao_mapper extends basic_dao_mapper {

	function init() {
		$this->dbTable = 'tt_address';	//dbtable for persistence
		$this->createPid = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['create_address_pid'];   //new record pid
	}
}


/**
 * address dao
 * This class handles object persistence using the dao design pattern.
 * It extends the basic dao object.
 *
 * @author Carsten Lausen <cl@e-netconsulting.de>
 */
class address_dao extends basic_dao {

	function init() {
		$this->parser =& new address_dao_parser();
		$this->mapper =& new address_dao_mapper($this->parser);
		$this->obj =& new address_object;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_object.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_object.php']);
}

?>
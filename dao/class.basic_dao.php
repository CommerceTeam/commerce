<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Carsten Lausen
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
* class basic dao
* This class handles basic object persistence using the dao design pattern.
* It defines parsing and database storage of an object.
* It can create objects and object Lists.
*
* Extend this class to fit specific needs.
*
* The class needs an object (to be stored).
* The class needs a mapper for database storage.
* The class needs a parser for object <-> model (transfer object) mapping.
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/
class basic_dao {
	var $obj;
	var $parser;
	var $mapper;

	function init() {
		$this->parser = t3lib_div::makeInstance('basic_dao_parser');
		$this->mapper = t3lib_div::makeInstance('basic_dao_mapper', $this->parser);
		$this->obj = t3lib_div::makeInstance('basic_object');
	}


	//----------------------- constructor -------------------------

 	function basic_dao($id=null) {
 		$this->init();
 		if(!empty($id)) {
 			$this->obj->setId($id);
 			$this->load();
 		}
 	}


	//---------------------- object access ------------------------

	function &getObject() {
		return $this->obj;
	}

	function setObject(&$obj) {
		$this->obj =& $obj;
	}


	//------------------ object getter / setter -------------------


	function getId() {
		return $this->obj->getId();
	}

	function setId($value) {
		$this->obj->setId($value);
	}

	function get($property) {
		$arr = get_object_vars($this->obj);
		if(method_exists($this->obj, 'get'.ucfirst($property))) {
			$value= call_user_func(array(&$this->obj, 'get'.ucfirst($property)),null);
		} else {
			$value= $arr[$property];
		}
		return $value;
	}

	function set($property,$value) {
		//if($property=='id') return null;
		$arr = get_object_vars($this->obj);
		if (array_key_exists($property,$arr)) {
			if(method_exists($this->obj, 'set'.ucfirst($property))) {
				call_user_func(array(&$this->obj,'set'.ucfirst($property)),$value);
			} else {
				$this->obj->$property = $value;
			}
		}
	}

	function isEmpty($property) {
		$arr = get_object_vars($this->obj);
		return empty($arr[$property]);
	}

	function issetProperty($property) {
		$arr = get_object_vars($this->obj);
		return isset($arr[$property]);
	}


	//------------------ database mapper access --------------------

 	function load() {
 		$this->mapper->load($this->obj);
 	}

 	function save() {
 		$this->mapper->save($this->obj);
 	}

 	function remove() {
 		$this->mapper->remove($this->obj);
 	}

}
  // Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.basic_dao.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.basic_dao.php']);
}

?>
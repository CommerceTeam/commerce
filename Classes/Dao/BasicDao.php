<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2011 Carsten Lausen <cl@e-netconsulting.de>
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
 * class basic Dao
 * This class handles basic object persistence using the Dao design pattern.
 * It defines parsing and database storage of an object.
 * It can create objects and object Lists.
 * Extend this class to fit specific needs.
 * The class needs an object (to be stored).
 * The class needs a mapper for database storage.
 * The class needs a parser for object <-> model (transfer object) mapping.
 */
class Tx_Commerce_Dao_BasicDao {
	/**
	 * @var Tx_Commerce_Dao_BasicDaoObject
	 */
	protected $object;

	/**
	 * @var Tx_Commerce_Dao_BasicDaoParser
	 */
	protected $parser;

	/**
	 * @var Tx_Commerce_Dao_BasicDaoMapper
	 */
	protected $mapper;

	/**
	 * Constructor
	 *
	 * @param integer $id
	 * @return self
	 */
	public function __construct($id = NULL) {
		$this->init();
		if (!empty($id)) {
			$this->object->setId($id);
			$this->load();
		}
	}

	/**
	 * Initialization
	 *
	 * @return void
	 */
	protected function init() {
		$this->parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_BasicDaoParser');
		$this->mapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_BasicDaoMapper', $this->parser);
		$this->object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_BasicDaoObject');
	}

	/**
	 * Getter
	 *
	 * @return Tx_Commerce_Dao_BasicDaoObject
	 */
	public function &getObject() {
		return $this->object;
	}

	/**
	 * Setter
	 *
	 * @param Tx_Commerce_Dao_BasicDaoObject &$object
	 * @return void
	 */
	public function setObject(&$object) {
		$this->object = $object;
	}

	/**
	 * Getter
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->object->getId();
	}

	/**
	 * Setter
	 *
	 * @param integer $value
	 * @return void
	 */
	public function setId($value) {
		$this->object->setId($value);
	}

	/**
	 * Getter
	 *
	 * @param string $propertyName
	 * @return mixed
	 */
	public function get($propertyName) {
		$properties = get_object_vars($this->object);
		if (method_exists($this->object, 'get' . ucfirst($propertyName))) {
			$value = call_user_func(array($this->object, 'get' . ucfirst($propertyName)), NULL);
		} else {
			$value = $properties[$propertyName];
		}

		return $value;
	}

	/**
	 * Setter
	 *
	 * @param string $propertyName
	 * @param mixed $value
	 * @return void
	 */
	public function set($propertyName, $value) {
		$properties = get_object_vars($this->object);
		if (array_key_exists($propertyName, $properties)) {
			if (method_exists($this->object, 'set' . ucfirst($propertyName))) {
				call_user_func(array($this->object, 'set' . ucfirst($propertyName)), $value);
			} else {
				$this->object->$propertyName = $value;
			}
		}
	}

	/**
	 * Check if property is empty
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isEmpty($propertyName) {
		$properties = get_object_vars($this->object);

		return empty($properties[$propertyName]);
	}

	/**
	 * Check if property exists
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	public function issetProperty($propertyName) {
		$properties = get_object_vars($this->object);

		return isset($properties[$propertyName]);
	}

	/**
	 * Load object
	 *
	 * @return void
	 */
	public function load() {
		$this->mapper->load($this->object);
	}

	/**
	 * Save object
	 *
	 * @return void
	 */
	public function save() {
		$this->mapper->save($this->object);
	}

	/**
	 * Remove object
	 *
	 * @return void
	 */
	public function remove() {
		$this->mapper->remove($this->object);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/BasicDao.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/BasicDao.php']);
}

?>
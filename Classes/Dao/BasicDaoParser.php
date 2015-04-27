<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2008 Carsten Lausen <cl@e-netconsulting.de>
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
 * class basic Dao parser
 * This class is used by the Dao to parse objects to database model objects
 * (transfer objects) and vice versa.
 * All knowledge about the database model is in this class.
 * Extend this class to fit specific needs.
 */
class Tx_Commerce_Dao_BasicDaoParser {
	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
	}

	/**
	 * Parse object to model
	 *
	 * @param Tx_Commerce_Dao_BasicDaoObject $object
	 * @return array
	 */
	public function &parseObjectToModel($object) {
		$model = array();

			// parse attribs
		$propertyNames = array_keys(get_object_vars($object));
		foreach ($propertyNames as $attrib) {
			if ($attrib != 'id') {
				if (method_exists($object, 'get' . ucfirst($attrib))) {
					$model[$attrib] = call_user_func(array($object, 'get' . ucfirst($attrib)), NULL);
				} else {
					$model[$attrib] = $object->$attrib;
				}
			}
		}

		unset ($model['uid']);
		return $model;
	}

	/**
	 * Parse model to object
	 *
	 * @param array $model
	 * @param Tx_Commerce_Dao_BasicDaoObject &$object
	 * @return void
	 */
	public function parseModelToObject($model, &$object) {
			// parse attribs
		$propertyNames = array_keys(get_object_vars($object));
		foreach ($propertyNames as $attrib) {
			if ($attrib != 'id') {
				if (array_key_exists($attrib, $model)) {
					if (method_exists($object, 'set' . ucfirst($attrib))) {
						call_user_func(array($object, 'set' . ucfirst($attrib)), $model[$attrib]);
					} else {
						$object->$attrib = $model[$attrib];
					}
				}
			}
		}
	}

	/**
	 * Setter
	 *
	 * @param array &$model
	 * @param integer $pid
	 * @return void
	 */
	public function setPid(&$model, $pid) {
		$model['pid'] = $pid;
	}
}

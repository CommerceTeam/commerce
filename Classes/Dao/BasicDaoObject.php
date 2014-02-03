<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006 Carsten Lausen <cl@e-netconsulting.de>
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
 * This is the basic object class.
 * Basic methods for object handling are in this class.
 * Extend this class to fit specific needs.
 */
class Tx_Commerce_Dao_BasicDaoObject {
	/**
	 * @var integer
	 */
	protected $id = 0;

	/**
	 * @param integer $id
	 * @return void
	 */
	public function setId($id) {
		if (empty($this->id)) {
			$this->id = $id;
		}
	}

	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return boolean
	 */
	public function issetId() {
		return !empty($this->id);
	}

	/**
	 * @return void
	 */
	public function clear() {
		$attribList = array_keys(get_class_vars(get_class($this)));
		foreach ($attribList as $attrib) {
			$this->$attrib = NULL;
		}
	}

	/**
	 * @return void
	 */
	public function destroy() {
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/BasicDaoObject.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/BasicDaoObject.php']);
}

?>
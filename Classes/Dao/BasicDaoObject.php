<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2008 Carsten Lausen <cl@e-netconsulting.de>
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
	 * Setter
	 *
	 * @param integer $id
	 * @return void
	 */
	public function setId($id) {
		if (empty($this->id)) {
			$this->id = $id;
		}
	}

	/**
	 * Getter
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Check if id is set
	 *
	 * @return boolean
	 */
	public function issetId() {
		return !empty($this->id);
	}

	/**
	 * Clear values
	 *
	 * @return void
	 */
	public function clear() {
		$attribList = array_keys(get_class_vars(get_class($this)));
		foreach ($attribList as $attrib) {
			$this->$attrib = NULL;
		}
	}

	/**
	 * Destructor
	 *
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
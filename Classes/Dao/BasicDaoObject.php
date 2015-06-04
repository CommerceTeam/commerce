<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This is the basic object class.
 * Basic methods for object handling are in this class.
 * Extend this class to fit specific needs.
 *
 * Class Tx_Commerce_Dao_BasicDaoObject
 *
 * @author 2006-2008 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Dao_BasicDaoObject {
	/**
	 * Object id
	 *
	 * @var int
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
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/BasicDaoObject.php']);
}

<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Libary for Frontend-Rendering of attribute values. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic Database calls are made from a separate Database Class
 *
 * Main script class for the handling of attribute Values. An attribute_value
 * desribes the technical data of an article
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_attribute_value extends tx_commerce_element_alib {
		// Title of Attribute (private)
	protected $title = '';

		// The Value for
	protected $value = '';

	/**
	 * @var show value
	 * if thzis value should be shown in Fe output
	 * @acces private
	 */
	protected $showvalue = 1;

	/**
	 * @var icon
	 * Icon for this Value
	 * @acces private
	 */
	protected $icon = '';

	/**
	 * Constructor Class
	 * If $uid is not given on construction you MUST call init manually
	 *
	 * @param integer $uid or attribute, default false
	 * @param integer $lang_uid language uid, default 0
	 */
	public function __construct($uid = 0, $lang_uid = 0) {
		if ((int) $uid > 0) {
			$this->init($uid, $lang_uid);
		}
	}

	/**
	 * Init Class
	 *
	 * @param integer $uid or attribute
	 * @param integer $lang_uid language uid, default 0
	 * @return void
	 */
	public function init($uid, $lang_uid = 0) {
		$uid = intval($uid);
		$lang_uid = intval($lang_uid);
		/**
		 * Define variables
		 */
		$this->fieldlist = array('title', 'value', 'showvalue', 'icon', 'l18n_parent');
		$this->databaseClass = 'tx_commerce_db_attribute_value';
		$this->uid = $uid;
		$this->lang_uid = $lang_uid;
		$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'postinit')) {
				$hookObj->postinit($this);
			}
		}
	}

	/**
	 * gets the attribute title
	 *
	 * @param boolean $checkvalue optional check if value shoudl be show in FE
	 * @return string title
	 */
	public function getValue($checkvalue = FALSE) {
		if (($checkvalue) && ($this->showicon)) {
			return $this->value;
		} elseif ($checkvalue == FALSE) {
			return $this->value;
		} else {
			return FALSE;
		}
	}

	/**
	 * @deprecated alias for getValue
	 */
	public function get_value($checkvalue) {
		return $this->getValue($checkvalue);
	}

	/**
	 * Overwrite get_attributes as attribute_values cant hav attributes
	 * @return false;
	 */
	public function get_attributes() {
		return FALSE;
	}

	/**
	 * Gets the icon for this value
	 *
	 * @return string
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * Gets the showicon value
	 * @return integer
	 */
	public function getshowicon() {
		return $this->showicon;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute_value.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute_value.php']);
}

?>
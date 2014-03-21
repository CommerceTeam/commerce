<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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
 * Libary for Frontend-Rendering of attribute values. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from Tx_Commerce_Domain_Model_AbstractEntity, all
 * basic Database calls are made from a separate Database Class
 *
 * Main script class for the handling of attribute Values. An attribute_value
 * desribes the technical data of an article
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 */
class Tx_Commerce_Domain_Model_AttributeValue extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_AttributeValueRepository';

	/**
	 * @var Tx_Commerce_Domain_Repository_AttributeValueRepository
	 */
	public $databaseConnection;

	/**
	 * @var array
	 */
	protected $fieldlist = array(
		'title',
		'value',
		'showvalue',
		'icon',
		'l18n_parent'
	);

	/**
	 * Title of Attribute (private)
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * The Value for
	 *
	 * @var string
	 */
	protected $value = '';

	/**
	 * if this value should be shown in Fe output
	 *
	 * @var boolean show value
	 */
	protected $showvalue = 1;

	/**
	 * Icon for this Value
	 *
	 * @var string icon
	 */
	protected $icon = '';

	/**
	 * @var string
	 */
	protected $showicon;

	/**
	 * Constructor, basically calls init
	 *
	 * @param integer $uid
	 * @param integer $languageUid
	 * @return self
	 */
	public function __construct($uid, $languageUid = 0) {
		if ((int) $uid) {
			$this->init($uid, $languageUid);
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
		$this->uid = (int) $uid;
		$this->lang_uid = (int) $lang_uid;
		$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);

		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_attribute_value.php\'][\'postinit\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/AttributeValue.php\'][\'postinit\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'] as $classRef) {
				$hookObj = &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'postinit')) {
					$hookObj->postinit($this);
				}
			}
		}
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/AttributeValue.php']['postinit'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/AttributeValue.php']['postinit'] as $classRef) {
				$hookObj = &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'postinit')) {
					$hookObj->postinit($this);
				}
			}
		}
	}

	/**
	 * Overwrite get_attributes as attribute_values can't have attributes
	 *
	 * @return boolean FALSE
	 */
	public function getAttributes() {
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
	 * @return boolean
	 */
	public function getShowvalue() {
		return $this->showvalue;
	}

	/**
	 * gets the attribute title
	 *
	 * @param boolean $checkvalue optional check if value shoudl be show in FE
	 * @return string title
	 */
	public function getValue($checkvalue = FALSE) {
		if (($checkvalue) && ($this->showvalue)) {
			return $this->value;
		} elseif ($checkvalue == FALSE) {
			return $this->value;
		} else {
			return FALSE;
		}
	}


	/**
	 * Gets the showicon value
	 *
	 * @return integer
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, never was returning a value
	 * remove $this->showicon with this method
	 */
	public function getshowicon() {
		t3lib_div::logDeprecatedFunction();
		return $this->showicon;
	}

	/**
	 * Overwrite get_attributes as attribute_values can't have attributes
	 *
	 * @return boolean FALSE
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getValue
	 */
	public function get_attributes() {
		t3lib_div::logDeprecatedFunction();
		return $this->getAttributes();
	}

	/**
	 * @param boolean $checkvalue
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getValue
	 */
	public function get_value($checkvalue) {
		t3lib_div::logDeprecatedFunction();
		return $this->getValue($checkvalue);
	}
}

class_alias('Tx_Commerce_Domain_Model_AttributeValue', 'tx_commerce_attribute_value');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/AttributeValue.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/AttributeValue.php']);
}

?>
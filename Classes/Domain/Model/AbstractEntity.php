<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2012 Ingo Schmitt <is@marketing-factory.de>
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
 * Constants definition for Attribute correlation_types
 * Add new contants to array in alib class
 */

/**
 * @var integer
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_SELECTOR', 1);
define ('ATTRIB_selector', ATTRIB_SELECTOR);

/**
 * @var integer
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_SHAL', 2);
define ('ATTRIB_shal', ATTRIB_SHAL);

/**
 * @var integer
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_CAN', 3);
define ('ATTRIB_can', ATTRIB_CAN);

/**
 * @var integer
 * @see sql tx_commerce_attribute_correlationtypes
 */
define ('ATTRIB_PRODUCT', 4);
define ('ATTRIB_product', ATTRIB_PRODUCT);

/**
 * Basic abtract Class for element
 * tx_commerce_product
 * tx_commerce_article
 * tx_commerce_category
 * tx_commerce_attribute
 */
class Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * uid of element
	 *
	 * @var integer
	 */
	protected $uid = 0;

	/**
	 * Language uid
	 *
	 * @var integer
	 */
	protected $lang_uid = 0;

	/**
	 * Language uid
	 *
	 * @var integer
	 */
	protected $l18n_parent;

	/**
	 * Database class for inhertitation
	 *
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_Repository';

	/**
	 * @var Tx_Commerce_Domain_Repository_Repository
	 */
	protected $databaseConnection;

	/**
	 * @var array fieldlist for inhertitation
	 */
	protected $fieldlist = array(
		'title',
		'lang_uid',
		'l18n_parent',
		'_LOCALIZED_UID'
	);

	/**
	 * Changes hier must be made, if a new correewlation_type is invented
	 *
	 * @var array of possible attribute correlation_types
	 */
	public $correlation_types = array(
		ATTRIB_SELECTOR,
		ATTRIB_SHAL,
		ATTRIB_CAN,
		ATTRIB_PRODUCT
	);

	/**
	 * @var string default_add_where for deleted hidden and more
	 */
	protected $default_add_where = ' AND hidden = 0 AND deleted = 0';

	/**
	 * @var array of attribute UIDs
	 */
	protected $attributes_uids = array();

	/**
	 * @var array of attributes
	 */
	protected $attribute = array();

	/**
	 * @var string Translation Mode for getRecordOverlay
	 * @see class.t3lib_page.php
	 */
	protected $translationMode = 'hideNonTranslated';

	/**
	 * @return boolean if a record is translaed
	 */
	protected $recordTranslated = FALSE;

	/**
	 * @var integer lokalized UID   the uid of the localized record
	 */
	public $_LOCALIZED_UID;

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * returns the possible attributes
	 *
	 * @param array $attributeCorelationTypeList of attribut_correlation_types
	 * @return array
	 */
	public function getAttributes($attributeCorelationTypeList = array()) {
		$result = array();
		if (($this->attributes_uids = $this->databaseConnection->getAttributes($this->uid, $attributeCorelationTypeList))) {
			foreach ($this->attributes_uids as $attributeUid) {
				/** @var Tx_Commerce_Domain_Model_Attribute $attribute */
				$attribute = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_Attribute', $attributeUid, $this->lang_uid);
				$attribute->loadData();

				$this->attribute[$attributeUid] = $attribute;
			}
			$result = $this->attributes_uids;
		}
		return $result;
	}

	/**
	 * set a given field, only to use with custom field without own method
	 * Warning: commerce provides getMethods for all default fields. For Compatibility
	 * reasons always use the built in Methods. Only use this method with you own added fields
	 *
	 * @see add_fields_to_fieldlist
	 * @see add_field_to_fieldlist
	 * @param string	$field: fieldname
	 * @param mixed	$value: value
	 * @return void
	 */
	public function setField($field, $value) {
		$this->$field = $value;
	}

	/**
	 * get a given field value, only to use with custom field without own method
	 * Warning: commerce provides getMethods for all default fields. For Compatibility
	 * reasons always use the built in Methods. Only use this method with you own added fields
	 *
	 * @see add_fields_to_fieldlist
	 * @see add_field_to_fieldlist
	 * @param string	$field: fieldname
	 * @return mixed	value of the field
	 */
	public function getField($field) {
		return $this->$field;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * language id
	 *
	 * @return integer
	 */
	public function getLang() {
		return $this->lang_uid;
	}

	/**
	 * @return integer l18n_partent uid
	 * @access public
	 */
	public function getL18nParent() {
		return $this->l18n_parent;
	}

	/**
	 * @return integer
	 */
	public function getLocalizedUid() {
		return $this->_LOCALIZED_UID;
	}

	/**
	 * Get uid of item
	 *
	 * @return integer Uid
	 */
	public function getUid() {
		return (int) $this->uid;
	}


	/**
	 * Loads the Data from the database
	 * via the named database class $databaseClass
	 *
	 * @param boolean $translationMode Transaltio Mode of the record, default false to use the default way of translation
	 * @return array
	 */
	public function loadData($translationMode = FALSE) {
		if ($translationMode) {
			$this->translationMode = $translationMode;
		}

		if (!$this->databaseConnection) {
			$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);
		}
		$this->data = $this->databaseConnection->getData($this->uid, $this->lang_uid, $translationMode);

		if (!$this->data) {
			$this->recordTranslated = FALSE;
			return FALSE;
		} else {
			$this->recordTranslated = TRUE;
		}

		foreach ($this->fieldlist as $field) {
			$this->$field = $this->data[$field];
		}

		if ($this->data['_LOCALIZED_UID']) {
			$this->_LOCALIZED_UID = $this->data['_LOCALIZED_UID'];
		}

		return $this->data;
	}

	/**
	 * Adds a field to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 * Basically it creates an array with the string as value
	 * and calls $this->add_fields_to_fieldlist
	 *
	 * @param string $fieldname Database fieldname
	 */
	public function addFieldToFieldlist($fieldname) {
		$this->addFieldsToFieldlist(array(trim($fieldname)));
	}

	/**
	 * Adds a set of fields to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 *
	 * @param array $fieldarray array of databse filednames
	 */
	public function addFieldsToFieldlist($fieldarray) {
		$this->fieldlist = array_merge($this->fieldlist, (array) $fieldarray);
	}

	/**
	 * Checks in the Database if object is
	 * basically checks against the enableFields
	 *
	 * @see: class.tx_commerce_db_alib.php->isAccessible(
	 * @return boolean   TRUE    if is accessible
	 *            FALSE    if is not accessible
	 */
	public function isAccessible() {
		if (!$this->databaseConnection) {
			$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);
		}

		return $this->databaseConnection->isAccessible($this->uid);
	}

	/**
	 * Returns true, if a translation for the initialised Laguage is availiable
	 *
	 * @return boolean
	 */
	public function isTranslated() {
		return $this->recordTranslated;
	}

	/**
	 * Checks if the UID is valid and availiable in the database
	 *
	 * @return boolean true if uid is valid
	 */
	public function isValidUid() {
		if (!$this->databaseConnection) {
			$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);
		}

		return $this->databaseConnection->isUid($this->uid);
	}

	/**
	 * Returns the data of this object als array
	 *
	 * @param string $prefix Prefix for the keys or returnung array optional
	 * @return array Assoc Arry of data
	 */
	public function returnAssocArray($prefix = '') {
		$data = array();

		foreach ($this->fieldlist as $field) {
			$data[$prefix . $field] = $this->$field;
		}

		return $data;
	}

	/**
	 * Sets the PageTitle titile from via the TSFE
	 *
	 * @param string $field (default title) for setting as title
	 */
	public function setPageTitle($field = 'title') {
		$GLOBALS['TSFE']->page['title'] = $this->$field . ' : ' . $GLOBALS['TSFE']->page['title'];
			// set pagetitle for indexed search also
		$GLOBALS['TSFE']->indexedDocTitle = $this->$field . ' : ' . $GLOBALS['TSFE']->indexedDocTitle;
	}


	/**
	 * Renders values from fieldlist to markers
	 *
	 * @param tslib_cObj &$cobj refference to cobj class
	 * @param array $conf configuration for this viewmode to render cObj
	 * @param string $prefix optinonal prefix for marker
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use tx_commerce_pibase->renderElement in combination with $this->returnAssocArray instead
	 */
	public function getMarkerArray(&$cobj, $conf, $prefix = '') {
		t3lib_div::logDeprecatedFunction();
		$markContentArray = $this->returnAssocArray('');
		$markerArray = array();
		foreach ($markContentArray as $k => $v) {
			$vr = '';
			switch (strtoupper($conf[$k])) {
				case 'IMGTEXT' :
				case 'IMAGE' :
						$i = 1;
					$imgArray = explode(';', $v);
					foreach ($imgArray as $img) {
						$conf[$k . '.'][$i . '.'] = $conf[$k . '.']['defaultImgConf.'];
						$conf[$k . '.'][$i . '.']['file'] = $conf['imageFolder'] . $img;
						$vr = $cobj->IMAGE($conf[$k . '.'][$i . '.']);
						}
				break;
				case 'STDWRAP' :
					$vr = $cobj->stdWrap($v, $conf[$k . '.']);
				break;
				default :
					$vr = $v;
				break;
			}
			$markerArray['###' . strtoupper($prefix . $k) . '###'] = $vr;
		}

		return $markerArray;
	}

	/**
	 * Get uid of object
	 *
	 * @return integer uid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getUid instead
	 */
	public function get_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->getUid();
	}

	/**
	 * Returns the UID of the localized Record
	 *
	 * @return integer _LOCALIZED_UID
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getLocalizedUid instead
	 */
	public function get_LOCALIZED_UID() {
		t3lib_div::logDeprecatedFunction();
		return $this->getLocalizedUid();
	}

	/**
	 * @return integer language id
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getLang instead
	 */
	public function get_lang() {
		t3lib_div::logDeprecatedFunction();
		return $this->getLang();
	}

	/**
	 * Returns  the data of this object als array
	 *
	 * @param string $prefix for the keys or returnung array optional
	 * @return array Assoc Arry of data
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use returnAssocArray instead
	 */
	public function return_assoc_array($prefix = '') {
		t3lib_div::logDeprecatedFunction();
		return $this->returnAssocArray($prefix);
	}

	/**
	 * Adds a field to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 * Basically it creates an array with the string as value
	 * and calls $this->add_fields_to_fieldlist
	 *
	 * @param string $fieldname Database fieldname
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use addFieldToFieldlist instead
	 */
	public function add_field_to_fieldlist($fieldname) {
		t3lib_div::logDeprecatedFunction();
		$this->addFieldToFieldlist($fieldname);
	}

	/**
	 * Adds a set of fields to the $fieldlist variable
	 * used for hooks to add own fields to the output
	 *
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use addFieldsToFieldlist instead
	 */
	public function add_fields_to_fieldlist($fieldarray) {
		t3lib_div::logDeprecatedFunction();
		$this->addFieldsToFieldlist($fieldarray);
	}

	/**
	 * Checks if the UID is valid and availiable in the database
	 *
	 * @return boolean true if uid is valid
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use isValidUid instead
	 */
	public function is_valid_uid() {
		t3lib_div::logDeprecatedFunction();
		return $this->isValidUid();
	}

	/**
	 * returns the possible attributes
	 *
	 * @param array $attributeCorelationTypeList array of attribut_correlation_types
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getAttributes instead
	 */
	public function get_attributes($attributeCorelationTypeList = array()) {
		t3lib_div::logDeprecatedFunction();
		return $this->getAttributes($attributeCorelationTypeList);
	}

	/**
	 * Loads the Data from the database
	 * via the named database class $databaseClass
	 *
	 * @param boolean $translationMode Transaltio Mode of the record, default false to use the default way of translation
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use loadData instead
	 */
	public function load_data($translationMode = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->loadData($translationMode);
	}
}

class_alias('Tx_Commerce_Domain_Model_AbstractEntity', 'tx_commerce_element_alib');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/AbstractEntity.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/AbstractEntity.php']);
}

?>
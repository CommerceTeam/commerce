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
 * Main script class for the handling of attributes. An attribute desribes the
 * technical data of an article
 * Libary for Frontend-Rendering of attributes. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from Tx_Commerce_Domain_Model_AbstractEntity, all
 * basic Database calls are made from a separate Database Class
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 * Basic class for handleing attributes
 */
class Tx_Commerce_Domain_Model_Attribute extends Tx_Commerce_Domain_Model_AbstractEntity {
	/**
	 * @var string
	 */
	protected $databaseClass = 'Tx_Commerce_Domain_Repository_AttributeRepository';

	/**
	 * @var Tx_Commerce_Domain_Repository_AttributeRepository
	 */
	public $databaseConnection;

	/**
	 * Title of Attribute (private)
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Unit auf the attribute (private)
	 *
	 * @var string
	 */
	protected $unit = '';

	/**
	 * If the attribute has a separate value_list for selecting the value (private)
	 *
	 * @var integer
	 */
	protected $has_valuelist = 0;

	/**
	 * check if attribute values are already loaded
	 *
	 * @var boolean
	 */
	protected $attributeValuesLoaded = FALSE;

	/**
	 * Attribute value uid list
	 *
	 * @var array
	 */
	protected $attribute_value_uids = array();

	/**
	 * Attribute value object list
	 *
	 * @var array
	 */
	protected $attribute_values = array();

	/**
	 * @var integer
	 */
	protected $iconmode = 0;

	/**
	 * @var integer|Tx_Commerce_Domain_Model_Attribute
	 */
	protected $parent = 0;

	/**
	 * @var array
	 */
	protected $children = NULL;

	/**
	 * Constructor class, basically calls init
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

	/** Constructor class, basically calls init
	 *
	 * @param integer $uid uid or attribute
	 * @param integer $languageUid language uid, default 0
	 * @return boolean
	 */
	public function init($uid, $languageUid = 0) {
		$uid = (int) $uid;
		$this->fieldlist = array(
			'title',
			'unit',
			'iconmode',
			'has_valuelist',
			'l18n_parent',
			'parent'
		);

		if ($uid > 0) {
			$this->uid = $uid;
			$this->lang_uid = (int) $languageUid;
			$this->databaseConnection = t3lib_div::makeInstance($this->databaseClass);

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute.php']['postinit'])) {
				t3lib_div::deprecationLog('
					hook
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_attribute.php\'][\'postinit\']
					is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
					$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/Attribute.php\'][\'postinit\']
				');
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute.php']['postinit'] as $classRef) {
					$hookObj = & t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Attribute.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/Attribute.php']['postinit'] as $classRef) {
					$hookObj = & t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'postinit')) {
						$hookObj->postinit($this);
					}
				}
			}

			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Franz: how do we take care about depencies between attributes?
	 *
	 * @param boolean|object $returnObjects condition to return the value objects instead of values
	 * @param boolean|object $productObject return only attribute values that are possible for the given product
	 * @return array values of attribute
	 * @access public
	 */
	public function getAllValues($returnObjects = FALSE, $productObject = FALSE) {
		/** @var $attributeValue Tx_Commerce_Domain_Model_AttributeValue */
		if ($this->attributeValuesLoaded === FALSE) {
			if ($this->attribute_value_uids = $this->databaseConnection->getAttributeValueUids($this->uid)) {
				foreach ($this->attribute_value_uids as $value_uid) {
					/** @var Tx_Commerce_Domain_Model_AttributeValue $attributValue */
					$attributValue = t3lib_div::makeInstance('Tx_Commerce_Domain_Model_AttributeValue');
					$attributValue->init($value_uid, $this->lang_uid);
					$attributValue->loadData();

					$this->attribute_values[$value_uid] = $attributValue;
				}
				$this->attributeValuesLoaded = TRUE;
			}
		}

		$attributeValues = $this->attribute_values;

			// if productObject is a productObject we have to remove the attribute
			// values wich are not possible at all for this product
		if (is_object($productObject)) {
			$tAttributeValues = array();
			$productSelectAttributeValues = $productObject->get_selectattribute_matrix(FALSE, array($this->uid));
			foreach ($attributeValues as $attributeKey => $attributeValue) {
				foreach ($productSelectAttributeValues[$this->uid]['values'] as $selectAttributeValue) {
					if ($attributeValue->getUid() == $selectAttributeValue['uid']) {
						$tAttributeValues[$attributeKey] = $attributeValue;
					}
				}
			}
			$attributeValues = $tAttributeValues;
		}

		if ($returnObjects) {
			return $attributeValues;
		}

		$return_array = array();
		foreach ($attributeValues as $value_uid => $attributeValue) {
			$return_array[$value_uid] = $attributeValue->getValue();
		}

		return $return_array;
	}

	/**
	 * @param boolean|array $includeValues array of allowed values, if empty all values are allowed
	 * @return integer first attribute uid
	 *  @access public
	 */
	public function getFirstAttributeValueUid($includeValues = FALSE) {
		$attributes = $this->databaseConnection->getAttributeValueUids($this->uid);
		if (is_array($includeValues) && count($includeValues) > 0) {
			$attributes = array_intersect($attributes, array_keys($includeValues));
		}

		return array_shift($attributes);
	}

	/**
	 * synonym to get_all_values
	 *
	 * @see tx_commerce_attributes->get_all_values()
	 */
	public function getValues() {
		return $this->getAllValues();
	}

	/**
	 * synonym to get_all_values
	 *
	 * @see tx_commerce_attributes->get_all_values()
	 * @param integer $uid uid of value
	 * @return boolean|string
	 */
	public function getValue($uid) {
		$result = FALSE;
		if ($uid) {
			if (!$this->has_valuelist) {
				$this->getAllValues();

				/** @var $attributeValue Tx_Commerce_Domain_Model_AttributeValue */
				$attributeValue = $this->attribute_values[$uid];
				$result = $attributeValue->getValue();
			}
		}

		return $result;
	}

	/**
	 * gets the attribute title
	 *
	 * @return string title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string unit
	 */
	public function getUnit() {
		return $this->unit;
	}

	/**
	 * Overwrite get_attributes as attributes cant hav attributes
	 *
	 * @return boolean
	 */
	public function getAttributes() {
		return FALSE;
	}

	/**
	 * @param boolean|string $translationMode
	 * @return integer|Tx_Commerce_Domain_Model_Attribute
	 */
	public function getParent($translationMode = FALSE) {
		if (is_int($this->parent) && $this->parent > 0) {
			/** @var $parent Tx_Commerce_Domain_Model_Attribute */
			$parent = t3lib_div::makeInstance(get_class($this));
			$parent->init($this->parent, $this->lang_uid);
			$parent->loadData($translationMode);

			$this->parent = $parent;
		}

		return $this->parent;
	}

	/**
	 * @param boolean|string $translationMode
	 * @return null|array
	 */
	public function getChildren($translationMode = FALSE) {
		if ($this->children === NULL) {
			$childAttributeList = $this->databaseConnection->getChildAttributeUids($this->uid);

			foreach ($childAttributeList as $childAttributeUid) {
				/** @var $parent Tx_Commerce_Domain_Model_Attribute */
				$attribute = t3lib_div::makeInstance(get_class($this));
				$attribute->init($childAttributeUid, $this->lang_uid);
				$attribute->loadData($translationMode);

				$this->children[$childAttributeUid] = $attribute;
			}
		}

		return $this->children;
	}

	/**
	 * Check if it is an Iconmode Attribute
	 *
	 * @return boolean
	 */
	public function isIconmode() {
		return $this->iconmode == '1';
		}

	/**
	 * @return boolean
	 */
	public function hasParent() {
		return is_object($this->parent);
	}

	/**
	 * @return boolean
	 */
	public function hasChildren() {
		return count($this->children) > 0;
	}


	/**
	 * @param boolean|object $returnObjects
	 * @param boolean|object $productObject
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_attribute::getAllValues() instead
	 */
	public function get_all_values($returnObjects = FALSE, $productObject = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->getAllValues($returnObjects, $productObject);
	}

	/**
	 * @return array
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_attribute::getValues() instead
	 */
	public function get_values() {
		t3lib_div::logDeprecatedFunction();
		return $this->getValues();
	}

	/**
	 * @param integer $uid
	 * @return boolean|string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_attribute::getValue() instead
	 */
	public function get_value($uid) {
		t3lib_div::logDeprecatedFunction();
		return $this->getValue($uid);
	}

	/**
	 * @return string title
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_attribute::getTitle() instead
	 */
	public function get_title() {
		t3lib_div::logDeprecatedFunction();
		return $this->getTitle();
	}

	/**
	 * Overwrite get_attributes as attributes cant hav attributes
	 *
	 * @return boolean
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_attribute::getAttributes() instead
	 */
	public function get_attributes() {
		t3lib_div::logDeprecatedFunction();
		return $this->getAttributes();
	}

	/**
	 * @return string unit
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0 - Use tx_commerce_attribute::getUnit() instead
	 */
	public function get_unit() {
		t3lib_div::logDeprecatedFunction();
		return $this->getUnit();
	}
}

class_alias('Tx_Commerce_Domain_Model_Attribute', 'tx_commerce_attribute');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/Attribute.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Domain/Model/Attribute.php']);
}

?>
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
 * Main script class for the handling of attributes. An attribute desribes the
 * technical data of an article
 *
 * Libary for Frontend-Rendering of attributes. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic Database calls are made from a separate Database Class
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 *
 * Basic class for handleing attributes
 */
class tx_commerce_attribute extends tx_commerce_element_alib {

		// Title of Attribute (private)
	var $title='';

		// Unit auf the attribute (private)
	var $unit='';

		//  If the attribute has a separate value_list for selecting the value (private)
	var $has_valuelist=0;

		// check if attribute values are already loaded
	private $attributeValuesLoaded = false;

	/**
	 * Attribute value uid list
	 * @acces private
	 */
	var $attribute_value_uids=array();

	/**
	 * Attribute value object list
	 * @acces private
	 */
	var $attribute_values=array();

	/** Constructor class, basically calls init
	 * @param uid integer uid or attribute
	 * @param lang_uid integer language uid, default 0
	 */
	function tx_commerce_attribute() {
		if ((func_num_args()>0) && (func_num_args()<=2)) {
			$uid = func_get_arg(0);
			if (func_num_args()==2) {
				$lang_uid=func_get_arg(1);
			} else {
				$lang_uid=0;
			}
			return $this->init($uid,$lang_uid);
		}
	}

	/** Constructor class, basically calls init
	 * @param uid integer uid or attribute
	 * @param lang_uid integer language uid, default 0
	 */
	function init($uid,$lang_uid=0) {
		$uid = intval($uid);
		$lang_uid = intval($lang_uid);
		$this->fieldlist=array('title','unit','iconmode','has_valuelist','l18n_parent');
		$this->database_class='tx_commerce_db_attribute';
		if ($uid>0) {
			$this->uid=$uid;
			$this->lang_uid=$lang_uid;
			$this->conn_db=new $this->database_class;
			$hookObjectsArr = array();
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute.php']['postinit'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute.php']['postinit'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
			foreach($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postinit')) {
					$hookObj->postinit($this);
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Franz: how do we take care about depencies between attributes?
	 * @param returnObjects condition to return the value objects instead of values
	 * @param productObject return only attribute values that are possible for the given product
	 * @since 21.02.2010 added param returnObjects
	 * @return array values of attribute
	 *
	 * @access public
	 */
	function get_all_values($returnObjects = false, $productObject = false) {
		if ($this->attributeValuesLoaded === false) {
			if ($this->attribute_value_uids=$this->conn_db->get_attribute_value_uids($this->uid)) {
				foreach ($this->attribute_value_uids as $value_uid) {
					$this->attribute_values[$value_uid] = t3lib_div::makeInstance('tx_commerce_attribute_value');
					$this->attribute_values[$value_uid]->init($value_uid,$this->lang_uid);
					$this->attribute_values[$value_uid]->load_data();
				}
				$this->attributeValuesLoaded = true;
			}
		}

		$attributeValues = $this->attribute_values;

		// if productObject is a productObject we have to remove the attribute values wich are not possible at all for this product
		if (is_object($productObject)) {
			$tAttributeValues = array();
			$productSelectAttributeValues = $productObject->get_selectattribute_matrix(false,array($this->uid));
			foreach($attributeValues as $attributeKey => $attributeValue) {
				foreach($productSelectAttributeValues[$this->uid]['values'] as $selectAttributeValue) {
					if($attributeValue->getUid() == $selectAttributeValue['uid']) {
						$tAttributeValues[$attributeKey] = $attributeValue;
					}
				}
			}
			$attributeValues = $tAttributeValues;
		}

		if($returnObjects) {
			return $attributeValues;
		}

		$return_array=array();
		foreach ($attributeValues as $value_uid => $one_value) {
			$return_array[$value_uid]=$one_value->get_value();
		}

		return $return_array;
	}

	/**
	 *  @param includeValues array of allowed values, if empty all values are allowed
	 *  @return first attribute uid
	 *  @access public
	 */
	function getFirstAttributeValueUid ($includeValues = false) {
		$attributes = $this->conn_db->get_attribute_value_uids($this->uid);
		if(is_array($includeValues) && count($includeValues)>0) {
			$attributes = array_intersect($attributes,array_keys($includeValues));
		}
		return array_shift($attributes);
	}

	/**
	 * synonym to get_all_values
	 * @see tx_commerce_attributes->get_all_values()
	 *
	 */
	function get_values() {
		return $this->get_all_values();
	}

	/**
	 * synonym to get_all_values
	 * @see tx_commerce_attributes->get_all_values()
	 * @param uid uid of value
	 */
	function get_value($uid) {
		if ($uid) {
			if ($this->has_valuelist) {
			}
			else {
				$this->get_all_values();
				return $this->attribute_values[$uid]->get_value();
			}
		} else {
			return false;
		}
	}

	/**
	 * gets the attribute title
	 * @return string title
	 * @access public
	 */
	function get_title() {
		return $this->title;
	}

	/**
	 *
	 * @return string unit
	 *  @access public
	 */
	function get_unit() {
		return $this->unit;
	}

	/**
	 * Overwrite get_attributes as attributes cant hav attributes
	 * @return false;
	 */
	function get_attributes() {
		return false;
	}

	/**
	 * Check if it is an Iconmode Attribute
	 * @return boolean 
	 */
	function isIconmode() {
		if($this->iconmode == "1") {
			return true;
		}
		return false;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_attribute.php']);
}
?>
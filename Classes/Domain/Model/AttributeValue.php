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
 * Libary for Frontend-Rendering of attribute values. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from Tx_Commerce_Domain_Model_AbstractEntity, all
 * basic Database calls are made from a separate Database Class
 * Main script class for the handling of attribute Values. An attribute_value
 * desribes the technical data of an article
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * Class Tx_Commerce_Domain_Model_AttributeValue
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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
	 * @var bool show value
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
	 * @param int $uid
	 * @param int $languageUid
	 *
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
	 * @param int $uid Attribute
	 * @param int $languageUid Language uid, default 0
	 *
	 * @return void
	 */
	public function init($uid, $languageUid = 0) {
		$this->uid = (int) $uid;
		$this->lang_uid = (int) $languageUid;
		$this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_attribute_value.php\'][\'postinit\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/Classes/Domain/Model/AttributeValue.php\'][\'postinit\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_attribute_value.php']['postinit'] as $classRef) {
				$hookObj = & \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postinit')) {
					$hookObj->postinit($this);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/AttributeValue.php']['postinit'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Domain/Model/AttributeValue.php']['postinit'] as $classRef) {
				$hookObj = & \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'postinit')) {
					$hookObj->postinit($this);
				}
			}
		}
	}

	/**
	 * Overwrite get_attributes as attribute_values can't have attributes
	 *
	 * @return bool FALSE
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
	 * Get show value
	 *
	 * @return bool
	 */
	public function getShowvalue() {
		return $this->showvalue;
	}

	/**
	 * gets the attribute title
	 *
	 * @param bool $checkvalue optional check if value shoudl be show in FE
	 *
	 * @return string title
	 */
	public function getValue($checkvalue = FALSE) {
		if (($checkvalue) && ($this->showvalue)) {
			return $this->value;
		} elseif ($checkvalue == FALSE) {
			return $this->value;
		}

		return FALSE;
	}


	/**
	 * Gets the showicon value
	 *
	 * @return int
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, never was returning a value
	 * remove $this->showicon with this method
	 */
	public function getshowicon() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->showicon;
	}

	/**
	 * Overwrite get_attributes as attribute_values can't have attributes
	 *
	 * @return bool FALSE
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getValue
	 */
	public function get_attributes() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getAttributes();
	}

	/**
	 * @param bool $checkvalue
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use getValue
	 */
	public function get_value($checkvalue) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return $this->getValue($checkvalue);
	}
}

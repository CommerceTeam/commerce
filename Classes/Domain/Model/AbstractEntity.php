<?php
namespace CommerceTeam\Commerce\Domain\Model;

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

/*
 * Constants definition for Attribute correlation_types
 * Add new contants to array in alib class
 */

/**
 * Attribute correlation type selector
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
use TYPO3\CMS\Core\Database\DatabaseConnection;

define('ATTRIB_SELECTOR', 1);

/**
 * Attribute correlation type shall
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
define('ATTRIB_SHAL', 2);

/**
 * Attribute correlation type can
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
define('ATTRIB_CAN', 3);

/**
 * Attribute correlation type product
 *
 * @var int
 * @see sql tx_commerce_attribute_correlationtypes
 */
define('ATTRIB_PRODUCT', 4);

/**
 * Basic abtract Class for element
 * tx_commerce_product
 * tx_commerce_article
 * tx_commerce_category
 * tx_commerce_attribute.
 *
 * Class \CommerceTeam\Commerce\Domain\Model\AbstractEntity
 */
class AbstractEntity
{
    /**
     * Uid of element.
     *
     * @var int
     */
    protected $uid = 0;

    /**
     * Language uid.
     *
     * @var int
     */
    protected $lang_uid = 0;

    /**
     * Language uid.
     *
     * @var int
     */
    protected $l18n_parent;

    /**
     * Database class for inhertitation.
     *
     * @var string
     */
    protected $repositoryClass = \CommerceTeam\Commerce\Domain\Repository\Repository::class;

    /**
     * Database connection.
     *
     * @var \CommerceTeam\Commerce\Domain\Repository\Repository
     */
    protected $databaseConnection;

    /**
     * Fieldlist for inhertitation.
     *
     * @var array
     */
    protected $fieldlist = [
        'title',
        'lang_uid',
        'l18n_parent',
        '_LOCALIZED_UID',
    ];

    /**
     * Changes hier must be made, if a new correewlation_type is invented.
     *
     * @var array of possible attribute correlation_types
     */
    public $correlation_types = [
        ATTRIB_SELECTOR,
        ATTRIB_SHAL,
        ATTRIB_CAN,
        ATTRIB_PRODUCT,
    ];

    /**
     * Default add where for deleted hidden and more.
     *
     * @var string
     */
    protected $default_add_where = ' AND hidden = 0 AND deleted = 0';

    /**
     * Attribute UIDs.
     *
     * @var array
     */
    protected $attributes_uids = [];

    /**
     * Attributes.
     *
     * @var array
     */
    protected $attribute = [];

    /**
     * Translation Mode for getRecordOverlay.
     *
     * @var string
     */
    protected $translationMode = 'hideNonTranslated';

    /**
     * Localized UID
     * the uid of the localized record.
     *
     * @var int
     */
    public $_LOCALIZED_UID;

    /**
     * Database record.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Possible attributes.
     *
     * @param array $attributeCorelationTypeList Attribut correlation types
     *
     * @return array
     */
    public function getAttributes(array $attributeCorelationTypeList = [])
    {
        $result = [];
        if (($this->attributes_uids = $this->databaseConnection->getAttributes(
            $this->uid,
            $attributeCorelationTypeList
        ))) {
            foreach ($this->attributes_uids as $attributeUid) {
                /**
                 * Attribute.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Attribute $attribute
                 */
                $attribute = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \CommerceTeam\Commerce\Domain\Model\Attribute::class,
                    $attributeUid,
                    $this->lang_uid
                );
                $attribute->loadData();

                $this->attribute[$attributeUid] = $attribute;
            }
            $result = $this->attributes_uids;
        }

        return $result;
    }

    /**
     * Set a given field, only to use with custom field without own method
     * Warning: commerce provides getMethods for all default fields. For
     * compatibility reasons always use the built in methods. Only use this
     * method with you own added fields.
     *
     * @param string $field Fieldname
     * @param mixed $value Value
     *
     * @return void
     */
    public function setField($field, $value)
    {
        $this->$field = $value;
    }

    /**
     * Get a given field value, only to use with custom field without own method
     * Warning: commerce provides getMethods for all default fields. For
     * compatibility reasons always use the built in methods. Only use this
     * method with you own added fields.
     *
     * @param string $field Fieldname
     *
     * @return mixed Value of the field
     */
    public function getField($field)
    {
        return $this->$field;
    }

    /**
     * Get data array.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Language uid.
     *
     * @return int
     */
    public function getLang()
    {
        return (int) $this->lang_uid;
    }

    /**
     * L18n parent uid.
     *
     * @return int
     */
    public function getL18nParent()
    {
        return (int) $this->l18n_parent;
    }

    /**
     * Localized uid.
     *
     * @return int
     */
    public function getLocalizedUid()
    {
        return (int) $this->_LOCALIZED_UID;
    }

    /**
     * Get uid.
     *
     * @return int
     */
    public function getUid()
    {
        return (int) $this->uid;
    }

    /**
     * Loads the Data from the database
     * via the named database class $databaseClass.
     *
     * @param bool $translationMode Translation mode of the record,
     *      default false to use the default way of translation
     *
     * @return array
     */
    public function loadData($translationMode = false)
    {
        if ($translationMode) {
            $this->translationMode = $translationMode;
        }

        $this->data = $this->getRepository()->getData($this->uid, $this->lang_uid, $translationMode);

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
     * and calls $this->add_fields_to_fieldlist.
     *
     * @param string $fieldname Database fieldname
     *
     * @return void
     */
    public function addFieldToFieldlist($fieldname)
    {
        $this->addFieldsToFieldlist([trim($fieldname)]);
    }

    /**
     * Adds a set of fields to the $fieldlist variable
     * used for hooks to add own fields to the output.
     *
     * @param array $fieldarray Databse filednames
     *
     * @return void
     */
    public function addFieldsToFieldlist(array $fieldarray)
    {
        $this->fieldlist = array_merge($this->fieldlist, (array) $fieldarray);
    }

    /**
     * Checks in the Database if object is
     * basically checks against the enableFields.
     *
     * @return bool If is accessible [TRUE|FALSE]
     */
    public function isAccessible()
    {
        return $this->getRepository()->isAccessible($this->uid);
    }

    /**
     * Returns true, if a translation for the initialised Language is available.
     *
     * @return bool
     */
    public function isTranslated()
    {
        return $this->_LOCALIZED_UID > 0;
    }

    /**
     * Checks if the uid is valid and available in the database.
     *
     * @return bool true if uid is valid
     */
    public function isValidUid()
    {
        return $this->getRepository()->isUid($this->uid);
    }

    /**
     * Returns the data of this object als array.
     *
     * @param string $prefix Prefix for the keys or returnung array optional
     *
     * @return array Assoc array of data
     */
    public function returnAssocArray($prefix = '')
    {
        $data = [];

        foreach ($this->fieldlist as $field) {
            $data[$prefix . $field] = $this->$field;
        }

        return $data;
    }

    /**
     * Sets the PageTitle titile from via the TSFE.
     *
     * @param string $field Setting of page title
     *
     * @return void
     */
    public function setPageTitle($field = 'title')
    {
        $title = $this->$field . ' : ' . $this->getFrontendController()->page['title'];
        $this->getFrontendController()->page['title'] = $title;
        // set pagetitle for indexed search also
        $this->getFrontendController()->indexedDocTitle = $title;
    }


    /**
     * @return \CommerceTeam\Commerce\Domain\Repository\Repository|object
     */
    protected function getRepository()
    {
        if (!$this->databaseConnection) {
            $this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->repositoryClass);
        }

        return $this->databaseConnection;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get typoscript frontend controller.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}

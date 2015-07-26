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

/**
 * Main script class for the handling of attributes. An attribute desribes the
 * technical data of an article
 * Libary for Frontend-Rendering of attributes. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from
 * \CommerceTeam\Commerce\Domain\Model\AbstractEntity, all
 * basic Database calls are made from a separate Database Class
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 * Basic class for handleing attributes.
 *
 * Class \CommerceTeam\Commerce\Domain\Model\Attribute
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Attribute extends AbstractEntity
{
    /**
     * Database class name.
     *
     * @var string
     */
    protected $databaseClass = 'CommerceTeam\\Commerce\\Domain\\Repository\\AttributeRepository';

    /**
     * Database connection.
     *
     * @var \CommerceTeam\Commerce\Domain\Repository\AttributeRepository
     */
    public $databaseConnection;

    /**
     * Title of Attribute.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Unit auf the attribute.
     *
     * @var string
     */
    protected $unit = '';

    /**
     * If the attribute has a separate value_list for selecting the value.
     *
     * @var int
     */
    protected $has_valuelist = 0;

    /**
     * Check if attribute values are already loaded.
     *
     * @var bool
     */
    protected $attributeValuesLoaded = false;

    /**
     * Attribute value uid list.
     *
     * @var array
     */
    protected $attribute_value_uids = array();

    /**
     * Attribute value object list.
     *
     * @var array
     */
    protected $attribute_values = array();

    /**
     * Icon mode.
     *
     * @var int
     */
    protected $iconmode = 0;

    /**
     * Attribute.
     *
     * @var int|\CommerceTeam\Commerce\Domain\Model\Attribute
     */
    protected $parent = 0;

    /**
     * Children.
     *
     * @var array
     */
    protected $children = null;

    /**
     * Constructor class, basically calls init.
     *
     * @param int $uid Attribute uid
     * @param int $languageUid Language uid
     *
     * @return self
     */
    public function __construct($uid, $languageUid = 0)
    {
        if ((int) $uid) {
            $this->init($uid, $languageUid);
        }
    }

    /**
     * Constructor class, basically calls init.
     *
     * @param int $uid Uid or attribute
     * @param int $languageUid Language uid, default 0
     *
     * @return bool
     */
    public function init($uid, $languageUid = 0)
    {
        $uid = (int) $uid;
        $this->fieldlist = array(
            'title',
            'unit',
            'iconmode',
            'has_valuelist',
            'l18n_parent',
            'parent',
        );

        if ($uid > 0) {
            $this->uid = $uid;
            $this->lang_uid = (int) $languageUid;
            $this->databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->databaseClass);

            $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Domain/Model/Attribute', 'init');
            foreach ($hooks as $hook) {
                if (method_exists($hook, 'postinit')) {
                    $hook->postinit($this);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * How do we take care about depencies between attributes?
     *
     * @param bool|object $returnAsObjects Condition to return the value objects
     * @param bool|object $product         Return only attribute values that are
     *                                     possible for the given product
     *
     * @return array values of attribute
     */
    public function getAllValues($returnAsObjects = false, $product = false)
    {
        if ($this->attributeValuesLoaded === false) {
            if (($this->attribute_value_uids = $this->databaseConnection->getAttributeValueUids($this->uid))) {
                foreach ($this->attribute_value_uids as $valueUid) {
                    /**
                     * Attribute value
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\AttributeValue $attributeValue
                     */
                    $attributeValue = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'CommerceTeam\\Commerce\\Domain\\Model\\AttributeValue',
                        $valueUid,
                        $this->lang_uid
                    );
                    $attributeValue->loadData();

                    $this->attribute_values[$valueUid] = $attributeValue;
                }
                $this->attributeValuesLoaded = true;
            }
        }

        $attributeValues = $this->attribute_values;

        // if productObject is a productObject we have to remove the attribute
        // values wich are not possible at all for this product
        if (is_object($product)) {
            $tAttributeValues = array();
            $productSelectAttributeValues = $product->getSelectattributeMatrix(false, array($this->uid));
            /**
             * Attribute value.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\AttributeValue $attributeValue
             */
            foreach ($attributeValues as $attributeKey => $attributeValue) {
                foreach ($productSelectAttributeValues[$this->uid]['values'] as $selectAttributeValue) {
                    if ($attributeValue->getUid() == $selectAttributeValue['uid']) {
                        $tAttributeValues[$attributeKey] = $attributeValue;
                    }
                }
            }
            $attributeValues = $tAttributeValues;
        }

        if ($returnAsObjects) {
            return $attributeValues;
        }

        $return = array();
        foreach ($attributeValues as $valueUid => $attributeValue) {
            $return[$valueUid] = $attributeValue->getValue();
        }

        return $return;
    }

    /**
     * Get first attribute value uid.
     *
     * @param bool|array $includeValues Array of allowed values,
     *                                  if empty all values are allowed
     *
     * @return int first attribute uid
     */
    public function getFirstAttributeValueUid($includeValues = false)
    {
        $attributes = $this->databaseConnection->getAttributeValueUids($this->uid);

        if (is_array($includeValues) && !empty($includeValues)) {
            $attributes = array_intersect($attributes, array_keys($includeValues));
        }

        return array_shift($attributes);
    }

    /**
     * Synonym to get_all_values.
     *
     * @see tx_commerce_attributes->get_all_values()
     *
     * @return array
     */
    public function getValues()
    {
        return $this->getAllValues();
    }

    /**
     * Synonym to get_all_values.
     *
     * @param int $uid Value
     *
     * @return bool|string
     *
     * @see tx_commerce_attributes->get_all_values()
     */
    public function getValue($uid)
    {
        $result = false;
        if ($uid) {
            if (!$this->has_valuelist) {
                $this->getAllValues();

                /**
                 * Attribute value.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\AttributeValue $attributeValue
                 */
                $attributeValue = $this->attribute_values[$uid];
                $result = $attributeValue->getValue();
            }
        }

        return $result;
    }

    /**
     * Gets the title.
     *
     * @return string title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Getter.
     *
     * @return string unit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Overwrite get_attributes as attributes cant hav attributes.
     *
     * @return bool
     */
    public function getAttributes()
    {
        return false;
    }

    /**
     * Get parent.
     *
     * @param bool|string $translationMode Translation mode
     *
     * @return int|\CommerceTeam\Commerce\Domain\Model\Attribute
     */
    public function getParent($translationMode = false)
    {
        if (is_int($this->parent) && $this->parent > 0) {
            /**
             * Attribute.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Attribute $parent
             */
            $parent = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(get_class($this));
            $parent->init($this->parent, $this->lang_uid);
            $parent->loadData($translationMode);

            $this->parent = $parent;
        }

        return $this->parent;
    }

    /**
     * Get children.
     *
     * @param bool|string $translationMode Translation mode
     *
     * @return null|array
     */
    public function getChildren($translationMode = false)
    {
        if ($this->children === null) {
            $childAttributeList = $this->databaseConnection->getChildAttributeUids($this->uid);

            foreach ($childAttributeList as $childAttributeUid) {
                /*
                 * Attribute
                 *
                 * @var $parent \CommerceTeam\Commerce\Domain\Model\Attribute
                 */
                $attribute = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(get_class($this));
                $attribute->init($childAttributeUid, $this->lang_uid);
                $attribute->loadData($translationMode);

                $this->children[$childAttributeUid] = $attribute;
            }
        }

        return $this->children;
    }

    /**
     * Check if it is an Iconmode Attribute.
     *
     * @return bool
     */
    public function isIconmode()
    {
        return $this->iconmode == '1';
    }

    /**
     * Check if attribute has parent.
     *
     * @return bool
     */
    public function hasParent()
    {
        return is_object($this->parent);
    }

    /**
     * Check if attribute has children.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }
}

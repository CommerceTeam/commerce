<?php
namespace CommerceTeam\Commerce\Dao;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class handles basic object persistence using the Dao design pattern.
 * It defines parsing and database storage of an object.
 * It can create objects and object Lists.
 * Extend this class to fit specific needs.
 * The class needs an object (to be stored).
 * The class needs a mapper for database storage.
 * The class needs a parser for object <-> model (transfer object) mapping.
 *
 * Class \CommerceTeam\Commerce\Dao\BasicDao
 */
abstract class BasicDao
{
    /**
     * Dao object.
     *
     * @var BasicDaoObject
     */
    protected $object;

    /**
     * Parser.
     *
     * @var BasicDaoParser
     */
    protected $parser;

    /**
     * Mapper.
     *
     * @var BasicDaoMapper
     */
    protected $mapper;

    /**
     * Constructor.
     *
     * @param int $id Id
     */
    public function __construct($id = null)
    {
        $this->init();
        if (!empty($id)) {
            $this->object->setId($id);
            $this->load();
        }
    }

    /**
     * Initialization.
     */
    abstract protected function init();

    /**
     * Getter.
     *
     * @return BasicDaoObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Setter.
     *
     * @param BasicDaoObject $object Object
     */
    public function setObject(BasicDaoObject &$object)
    {
        $this->object = $object;
    }

    /**
     * Getter.
     *
     * @return int
     */
    public function getId()
    {
        return $this->object->getId();
    }

    /**
     * Setter.
     *
     * @param int $value Id
     */
    public function setId($value)
    {
        $this->object->setId($value);
    }

    /**
     * Getter.
     *
     * @param string $propertyName Property name
     *
     * @return mixed
     */
    public function get($propertyName)
    {
        $value = null;

        $properties = get_object_vars($this->object);
        if (array_key_exists($propertyName, $properties)) {
            $camelCasePropertyName = GeneralUtility::underscoredToUpperCamelCase($propertyName);
            if (method_exists($this->object, 'get' . ucfirst($propertyName))) {
                $value = call_user_func([$this->object, 'get' . ucfirst($propertyName)], null);
            } elseif (method_exists($this->object, 'get' . ucfirst($camelCasePropertyName))) {
                $value = call_user_func([$this->object, 'get' . ucfirst($camelCasePropertyName)], null);
            } else {
                $value = $this->object->$propertyName;
            }
        }

        return $value;
    }

    /**
     * Setter.
     *
     * @param string $propertyName Property name
     * @param mixed $value Value
     */
    public function set($propertyName, $value)
    {
        $properties = get_object_vars($this->object);
        if (array_key_exists($propertyName, $properties)) {
            $camelCasePropertyName = GeneralUtility::underscoredToUpperCamelCase($propertyName);
            if (method_exists($this->object, 'set' . ucfirst($propertyName))) {
                call_user_func([$this->object, 'set' . ucfirst($propertyName)], $value);
            } elseif (method_exists($this->object, 'get' . ucfirst($camelCasePropertyName))) {
                call_user_func([$this->object, 'set' . ucfirst($camelCasePropertyName)], $value);
            } else {
                $this->object->$propertyName = $value;
            }
        }
    }

    /**
     * Check if property is empty.
     *
     * @param string $propertyName Property name
     *
     * @return bool
     */
    public function isEmpty($propertyName)
    {
        $properties = get_object_vars($this->object);

        return empty($properties[$propertyName]);
    }

    /**
     * Check if property exists.
     *
     * @param string $propertyName Property name
     *
     * @return bool
     */
    public function issetProperty($propertyName)
    {
        $properties = get_object_vars($this->object);

        return isset($properties[$propertyName]);
    }

    /**
     * Load object.
     */
    public function load()
    {
        $this->mapper->load($this->object);
    }

    /**
     * Save object.
     */
    public function save()
    {
        $this->mapper->save($this->object);
    }

    /**
     * Remove object.
     */
    public function remove()
    {
        $this->mapper->remove($this->object);
    }
}

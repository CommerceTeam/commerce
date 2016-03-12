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

/**
 * This class is used by the Dao to parse objects to database model objects
 * (transfer objects) and vice versa.
 * All knowledge about the database model is in this class.
 * Extend this class to fit specific needs.
 *
 * Class \CommerceTeam\Commerce\Dao\BasicDaoParser
 */
class BasicDaoParser
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Parse object to model.
     *
     * @param BasicDaoObject $object Object
     *
     * @return array
     */
    public function parseObjectToModel(BasicDaoObject $object)
    {
        $model = [];

        // parse attribs
        $propertyNames = array_keys(get_object_vars($object));
        foreach ($propertyNames as $attrib) {
            if ($attrib != 'id') {
                if (method_exists($object, 'get' . ucfirst($attrib))) {
                    $model[$attrib] = call_user_func([$object, 'get' . ucfirst($attrib)], null);
                } else {
                    $model[$attrib] = $object->$attrib;
                }
            }
        }

        unset($model['uid']);

        return $model;
    }

    /**
     * Parse model to object.
     *
     * @param array $model Model
     * @param BasicDaoObject $object Object
     *
     * @return void
     */
    public function parseModelToObject(array $model, BasicDaoObject &$object)
    {
        // parse attribs
        $propertyNames = array_keys(get_object_vars($object));
        foreach ($propertyNames as $attrib) {
            if ($attrib != 'id') {
                if (array_key_exists($attrib, $model)) {
                    if (method_exists($object, 'set' . ucfirst($attrib))) {
                        call_user_func([$object, 'set' . ucfirst($attrib)], $model[$attrib]);
                    } else {
                        $object->$attrib = $model[$attrib];
                    }
                }
            }
        }
    }

    /**
     * Setter.
     *
     * @param array $model Model
     * @param int $pid Page id
     *
     * @return void
     */
    public function setPid(array &$model, $pid)
    {
        $model['pid'] = $pid;
    }
}

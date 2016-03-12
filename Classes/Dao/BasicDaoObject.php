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
 * This is the basic object class.
 * Basic methods for object handling are in this class.
 * Extend this class to fit specific needs.
 *
 * Class \CommerceTeam\Commerce\Dao\BasicDaoObject
 */
class BasicDaoObject
{
    /**
     * Object id.
     *
     * @var int
     */
    protected $id = 0;

    /**
     * Setter.
     *
     * @param int $id Id
     *
     * @return void
     */
    public function setId($id)
    {
        if (empty($this->id)) {
            $this->id = $id;
        }
    }

    /**
     * Getter.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Check if id is set.
     *
     * @return bool
     */
    public function issetId()
    {
        return !empty($this->id);
    }

    /**
     * Clear values.
     *
     * @return void
     */
    public function clear()
    {
        $attribList = array_keys(get_class_vars(get_class($this)));
        foreach ($attribList as $attrib) {
            $this->$attrib = null;
        }
    }

    /**
     * Destructor.
     *
     * @return void
     */
    public function destroy()
    {
    }
}

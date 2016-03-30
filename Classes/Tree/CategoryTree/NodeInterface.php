<?php
namespace CommerceTeam\Commerce\Tree\CategoryTree;

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

interface NodeInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getWorkspaceId();

    /**
     * @return string
     */
    public function getTextSourceField();

    /**
     * @return bool
     */
    public function isLeafNode();

    /**
     * @param string $prefix
     * @return string
     */
    public function calculateNodeId($prefix = '');

    /**
     * @return int
     */
    public function getPid();
}

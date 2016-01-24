<?php
namespace CommerceTeam\Commerce\Tree\Leaf;

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
 * Implements a leaf specific for holding categories.
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\Category
 *
 * @author 2008 Erik Frister <typo3@marketing-factory.de>
 */
class Category extends Master
{
    /**
     * Mount class.
     *
     * @var string
     */
    protected $mountClass = \CommerceTeam\Commerce\Tree\CategoryMounts::class;
}

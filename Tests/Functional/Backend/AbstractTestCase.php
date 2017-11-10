<?php
namespace CommerceTeam\Commerce\Tests\Functional\Backend;

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
 * Functional test for the DataHandler
 */
abstract class AbstractTestCase extends \CommerceTeam\Commerce\Tests\Functional\AbstractTestCase
{
    /**
     * @var string BE|FE
     */
    protected static $type = 'BE';
}

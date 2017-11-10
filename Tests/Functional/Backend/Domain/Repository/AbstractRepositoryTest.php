<?php
namespace CommerceTeam\Commerce\Tests\Functional\Backend\Domain\Repository;

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
 * Test case for \CommerceTeam\Commerce\Domain\Repository\AbstractRepository
 */
class AbstractRepositoryTest extends
 \CommerceTeam\Commerce\Tests\Functional\Frontend\Domain\Repository\AbstractRepositoryTest
{
    /**
     * @var string BE|FE
     */
    protected static $type = 'BE';
}

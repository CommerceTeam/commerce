<?php
namespace CommerceTeam\Commerce\Tests\Unit\Domain\Repository;

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

use CommerceTeam\Commerce\Domain\Repository\ProductRepository;

/**
 * Test case for \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 */
class ProductRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function mockIsInstancOfGivenClass()
    {
        $subject = $this->getMock(ProductRepository::class);
        $this->assertEquals(
            true,
            $subject instanceof ProductRepository
        );
    }
}

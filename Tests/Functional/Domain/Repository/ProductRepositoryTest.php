<?php
namespace CommerceTeam\Commerce\Tests\Domain\Repository;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for \CommerceTeam\Commerce\Domain\Repository\ProductRepository
 */
class ProductRepositoryTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var int
     */
    private $expectedLogEntries;

    /**
     * @var string
     */
    protected $backendUserFixture = 'typo3conf/ext/commerce/Tests/Functional/Fixtures/be_users.xml';

    /**
     * @var string
     */
    protected $fixturePath = 'typo3conf/ext/commerce/Tests/Functional/Fixtures/';

    /**
     * @var \CommerceTeam\Commerce\Domain\Repository\ProductRepository
     */
    private $subject;

    /**
     * Sets up this test suite.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->testExtensionsToLoad[] = 'typo3conf/ext/commerce';

        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

        $this->expectedLogEntries = 0;

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'] = 1;

        $this->importDataSet(ORIGINAL_ROOT . $this->fixturePath . 'tx_commerce_products.xml');

        $this->subject = GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Repository\ProductRepository::class
        );
    }

    /**
     * Tears down this test case.
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->assertNoLogEntries();

        $this->expectedLogEntries = 0;

        parent::tearDown();
    }


    /**
     * @test
     */
    public function findById()
    {
        $response = $this->subject->findByUid(1);
        $this->assertEquals([
            'title' => 'product1'
        ], $response);
    }

    /**
     * Assert that no sys_log entries had been written.
     *
     * @return void
     */
    protected function assertNoLogEntries()
    {
        $logEntries = $this->getLogEntries();

        if (count($logEntries) > $this->expectedLogEntries) {
            var_dump(array_values($logEntries));
            ob_flush();
            $this->fail('The sys_log table contains unexpected entries.');
        } elseif (count($logEntries) < $this->expectedLogEntries) {
            $this->fail('Expected count of sys_log entries no reached.');
        }
    }

    /**
     * Gets log entries from the sys_log
     *
     * @return array
     */
    protected function getLogEntries()
    {
        return $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'sys_log', 'error IN (1,2)');
    }
}

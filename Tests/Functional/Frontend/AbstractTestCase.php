<?php
namespace CommerceTeam\Commerce\Tests\Functional\Frontend;

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
abstract class AbstractTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var int
     */
    protected $expectedLogEntries;

    /**
     * @var string
     */
    protected $backendUserFixture = 'typo3conf/ext/commerce/Tests/Functional/Fixtures/be_users.xml';

    /**
     * @var string
     */
    protected $fixturePath = 'typo3conf/ext/commerce/Tests/Functional/Fixtures/';


    /**
     * Sets up this test suite.
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'] = 1;

        $this->testExtensionsToLoad[] = 'typo3conf/ext/commerce';

        if (!defined('ORIGINAL_ROOT')) {
            $this->markTestSkipped('Functional tests must be called through phpunit on CLI');
        }
        $bootstrapUtility = new \CommerceTeam\Commerce\Tests\Functional\Frontend\TestCaseBootstrapUtility();
        $bootstrapUtility->setUp(
            get_class($this),
            $this->coreExtensionsToLoad,
            $this->testExtensionsToLoad,
            $this->pathsToLinkInTestInstance,
            $this->configurationToUseInTestInstance,
            $this->additionalFoldersToCreate
        );

        $this->setUpBackendUserFromFixture(2);
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

        $this->expectedLogEntries = 0;

        $GLOBALS['TSFE'] = $this->getMock(
            \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
            [],
            [$GLOBALS['TYPO3_CONF_VARS'], 1, 1]
        );
        $GLOBALS['TSFE']->sys_page = new \TYPO3\CMS\Frontend\Page\PageRepository();
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

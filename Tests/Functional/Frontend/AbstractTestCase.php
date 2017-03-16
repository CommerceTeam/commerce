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

use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
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

        // Use a 7 char long hash of class name as identifier
        $this->identifier = substr(sha1(get_class($this)), 0, 7);
        $this->instancePath = ORIGINAL_ROOT . 'typo3temp/var/tests/functional-' . $this->identifier;

        $testbase = new Testbase();
        $testbase->defineTypo3ModeFe();
        $testbase->setTypo3TestingContext();
        if ($testbase->recentTestInstanceExists($this->instancePath)) {
            // Reusing an existing instance. This typically happens for the second, third, ... test
            // in a test case, so environment is set up only once per test case.
            $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            $testbase->initializeTestDatabaseAndTruncateTables();
            $testbase->loadExtensionTables();
        } else {
            $testbase->removeOldInstanceIfExists($this->instancePath);
            // Basic instance directory structure
            $testbase->createDirectory($this->instancePath . '/fileadmin');
            $testbase->createDirectory($this->instancePath . '/typo3temp/var/transient');
            $testbase->createDirectory($this->instancePath . '/typo3temp/assets');
            $testbase->createDirectory($this->instancePath . '/typo3conf/ext');
            $testbase->createDirectory($this->instancePath . '/uploads');
            // Additionally requested directories
            foreach ($this->additionalFoldersToCreate as $directory) {
                $testbase->createDirectory($this->instancePath . '/' . $directory);
            }
            $testbase->createLastRunTextfile($this->instancePath);
            $testbase->setUpInstanceCoreLinks($this->instancePath);
            $testbase->linkTestExtensionsToInstance($this->instancePath, $this->testExtensionsToLoad);
            $testbase->linkPathsInTestInstance($this->instancePath, $this->pathsToLinkInTestInstance);
            $localConfiguration['DB'] = $testbase->getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration();
            $originalDatabaseName = $localConfiguration['DB']['Connections']['Default']['dbname'];
            // Append the unique identifier to the base database name to end up with a single database per test case
            $localConfiguration['DB']['Connections']['Default']['dbname'] =
                $originalDatabaseName . '_ft' . $this->identifier;
            $testbase->testDatabaseNameIsNotTooLong($originalDatabaseName, $localConfiguration);
            // Set some hard coded base settings for the instance. Those could be overruled by
            // $this->configurationToUseInTestInstance if needed again.
            $localConfiguration['SYS']['isInitialInstallationInProgress'] = false;
            $localConfiguration['SYS']['isInitialDatabaseImportDone'] = true;
            $localConfiguration['SYS']['displayErrors'] = '1';
            $localConfiguration['SYS']['debugExceptionHandler'] = '';
            $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
            // @todo: This should be moved over to DB/Connections/Default/initCommands
            $localConfiguration['SYS']['setDBinit'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,' .
                'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,' .
                'NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
            $localConfiguration['SYS']['caching']['cacheConfigurations']['extbase_object']['backend'] =
                NullBackend::class;
            $testbase->setUpLocalConfiguration(
                $this->instancePath,
                $localConfiguration,
                $this->configurationToUseInTestInstance
            );
            $defaultCoreExtensionsToLoad = [
                'core',
                'backend',
                'frontend',
                'lang',
                'extbase',
                'install',
            ];
            $testbase->setUpPackageStates(
                $this->instancePath,
                $defaultCoreExtensionsToLoad,
                $this->coreExtensionsToLoad,
                $this->testExtensionsToLoad
            );
            $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            $testbase->setUpTestDatabase(
                $localConfiguration['DB']['Connections']['Default']['dbname'],
                $originalDatabaseName
            );
            $testbase->loadExtensionTables();
            $testbase->createDatabaseStructure();
        }

        $this->setUpBackendUserFromFixture(2);
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

        $this->expectedLogEntries = 0;

        /** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
        $tsfe = $this->createMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class);
        $tsfe->sys_page = new \TYPO3\CMS\Frontend\Page\PageRepository();
        $GLOBALS['TSFE'] = $tsfe;
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $result = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->in(
                    'error',
                    [1, 2]
                )
            )
            ->execute()
            ->fetchAll();
        return $result;
    }
}

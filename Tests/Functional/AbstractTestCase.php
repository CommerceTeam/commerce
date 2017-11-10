<?php
namespace CommerceTeam\Commerce\Tests\Functional;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var string BE|FE
     */
    protected static $type = '';

    /**
     * @var int
     */
    protected $expectedLogEntries;

    /**
     * Array of test/fixture extensions paths that should be loaded for a test.
     *
     * This property will stay empty in this abstract, so it is possible
     * to just overwrite it in extending classes. Extensions noted here will
     * be loaded for every test of a test case and it is not possible to change
     * the list of loaded extensions between single tests of a test case.
     *
     * Given path is expected to be relative to your document root, example:
     *
     * array(
     *   'typo3conf/ext/some_extension/Tests/Functional/Fixtures/Extensions/test_extension',
     *   'typo3conf/ext/base_extension',
     * );
     *
     * Extensions in this array are linked to the test instance, loaded
     * and their ext_tables.sql will be applied.
     *
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/commerce',
        'typo3conf/ext/tt_address',
        'typo3conf/ext/static_info_tables',
    ];

    /**
     * @var string
     */
    protected $backendUserFixture = 'EXT:commerce/Tests/Functional/Fixtures/be_users.xml';

    /**
     * @var string
     */
    protected $fixturePath = 'EXT:commerce/Tests/Functional/Fixtures/';

    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        define('COMMERCE_TEST_MODE', $this::$type);

        if (!defined('ORIGINAL_ROOT')) {
            $this->markTestSkipped('Functional tests must be called through phpunit on CLI');
        }

        // Use a 7 char long hash of class name as identifier
        $this->identifier = substr(sha1(get_class($this)), 0, 7);
        $this->instancePath = ORIGINAL_ROOT . 'typo3temp/var/tests/functional-' . $this->identifier;
        putenv('TYPO3_PATH_ROOT=' . $this->instancePath);

        $testbase = new TestBase();
        $testbase->defineTypo3ModeBe();
        $testbase->definePackagesPath();
        $testbase->setTypo3TestingContext();
        if ($testbase->recentTestInstanceExists($this->instancePath)) {
            // Reusing an existing instance. This typically happens for the second, third, ... test
            // in a test case, so environment is set up only once per test case.
            $testbase->setUpBasicTypo3Bootstrap($this->instancePath);
            $testbase->initializeTestDatabaseAndTruncateTables();
            Bootstrap::getInstance()->initializeBackendRouter();
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
            Bootstrap::getInstance()->initializeBackendRouter();
            $testbase->loadExtensionTables();
            $testbase->createDatabaseStructure();
        }

        $this->setUpBackendUserFromFixture(2);
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

        $this->expectedLogEntries = 0;

        if ($this::$type == 'FE') {
            /** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
            $tsfe = $this->createMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class);
            $GLOBALS['TSFE'] = $tsfe;
            $tsfe->sys_page = new \TYPO3\CMS\Frontend\Page\PageRepository();
        }
    }

    /**
     * Tears down this test case.
     */
    protected function tearDown()
    {
        $this->assertNoLogEntries();

        $this->expectedLogEntries = 0;

        parent::tearDown();
    }

    /**
     * Assert that no sys_log entries had been written.
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
        $queryBuilder = $this->getQueryBuilderForTable('sys_log');
        $result = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->in(
                    'error',
                    $queryBuilder->createNamedParameter([1, 2], \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY)
                )
            )
            ->execute()
            ->fetchAll();
        return $result;
    }

    /**
     * Mark the test as skipped.
     *
     * @param string $message
     *
     * @throws \PHPUnit_Framework_SkippedTestError
     */
    public static function markTestSkipped($message = '')
    {
        if (strpos($message, static::$type) === 0) {
            throw new \PHPUnit_Framework_SkippedTestError($message);
        }
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @param string $table
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilderForTable($table): \TYPO3\CMS\Core\Database\Query\QueryBuilder
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable($table);
    }
}

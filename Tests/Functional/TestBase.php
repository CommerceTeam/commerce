<?php
namespace CommerceTeam\Commerce\Tests\Functional;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use TYPO3\TestingFramework\Core\Exception;

class TestBase extends \TYPO3\TestingFramework\Core\Testbase
{
    /**
     * Define TYPO3_MODE to FE
     */
    public function defineTypo3ModeBe()
    {
        define('TYPO3_MODE', COMMERCE_TEST_MODE);
    }

    /**
     * Database settings for functional and acceptance tests can be either set by
     * environment variables (recommended), or from an existing LocalConfiguration as fallback.
     * The method fetches these.
     *
     * An unique name will be added to the database name later.
     *
     * @throws Exception
     * @return array [DB][memory], [DB][path], ...
     */
    public function getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration()
    {
        $configuration = parent::getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration();

        // the driver need to be set because it's not in the list
        // of the validated parameter in the parent method
        // (
        //  $databaseName
        //  || $databaseHost
        //  || $databaseUsername
        //  || $databasePassword
        //  || $databasePort
        //  || $databaseSocket
        //  || $databaseCharset
        // )
        $databaseDriver = trim(getenv('typo3DatabaseDriver'));
        if ($databaseDriver) {
            $configuration['Connections']['Default']['driver'] = $databaseDriver;
        }

        $databaseMemory = trim(getenv('typo3DatabaseMemory'));
        $databasePath = trim(getenv('typo3DatabasePath'));
        if ($databaseMemory) {
            $configuration['Connections']['Default']['memory'] = $databaseMemory;
        }
        if ($databasePath) {
            if (strpos($databasePath, '/') === false) {
                $databasePath = getenv('TYPO3_PATH_ROOT') . '/' . $databasePath;
            }
            $configuration['Connections']['Default']['path'] = $databasePath;
        }

        return $configuration;
    }

    /**
     * Create a low level connection to dbms, without selecting the target database.
     * Drop existing database if it exists and create a new one.
     *
     * @param string $databaseName Database name of this test instance
     * @param string $originalDatabaseName Original database name before suffix was added
     * @throws \TYPO3\TestingFramework\Core\Exception
     * @return void
     */
    public function setUpTestDatabase($databaseName, $originalDatabaseName)
    {
        // Drop database if exists. Directly using the Doctrine DriverManager to
        // work around connection caching in ConnectionPool
        $connectionParameters = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        unset($connectionParameters['dbname']);
        $schemaManager = DriverManager::getConnection($connectionParameters)->getSchemaManager();

        if ($connectionParameters['driver'] == 'pdo_sqlite') {
            $databaseName = $connectionParameters['path'];
        } elseif (in_array($databaseName, $schemaManager->listDatabases(), true)) {
            $schemaManager->dropDatabase($databaseName);
        }

        try {
            $schemaManager->createDatabase($databaseName);
        } catch (DBALException $e) {
            $user = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'];
            $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'];
            throw new Exception(
                'Unable to create database with name ' . $databaseName . '. This is probably a permission problem.'
                . ' For this instance this could be fixed executing:'
                . ' GRANT ALL ON `' . $originalDatabaseName . '_%`.* TO `' . $user . '`@`' . $host . '`;'
                . ' Original message thrown by database layer: ' . $e->getMessage(),
                1376579070
            );
        }
    }
}

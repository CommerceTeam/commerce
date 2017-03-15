<?php
namespace CommerceTeam\Commerce\Tests\Functional\Frontend;

class TestCaseBootstrapUtility extends \TYPO3\CMS\Core\Tests\FunctionalTestCaseBootstrapUtility
{
    /**
     * Bootstrap basic TYPO3
     *
     * @return void
     */
    protected function setUpBasicTypo3Bootstrap()
    {
        $_SERVER['PWD'] = $this->instancePath;
        $_SERVER['argv'][0] = 'index.php';

        define('TYPO3_MODE', 'FE');
        define('TYPO3_cliMode', true);

        $classLoader = require rtrim(realpath($this->instancePath . '/typo3'), '\\/') . '/../vendor/autoload.php';
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
            ->initializeClassLoader($classLoader)
            ->baseSetup('')
            ->loadConfigurationAndInitialize(true)
            ->loadTypo3LoadedExtAndExtLocalconf(true)
            ->setFinalCachingFrameworkCacheConfiguration()
            ->defineLoggingAndExceptionConstants()
            ->unsetReservedGlobalVariables();
    }
}

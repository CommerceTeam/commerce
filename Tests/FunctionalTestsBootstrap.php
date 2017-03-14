<?php

// add wrapper to set constant that is needed in creating isolated processes
if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $_SERVER['IDE_PHPUNIT_CUSTOM_LOADER']);
}

$file = realpath(__DIR__ . '/../../../../typo3_src/typo3/sysext/core/Build/FunctionalTestsBootstrap.php');
require_once $file;

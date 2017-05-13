<?php

// add wrapper to set constant that is needed in creating isolated processes
if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $_SERVER['IDE_PHPUNIT_CUSTOM_LOADER']);
}

$file = realpath(__DIR__ . '/../../../../typo3_src/vendor/typo3/testing-framework/Resources/Core/Build/FunctionalTestsBootstrap.php');
/** @noinspection PhpIncludeInspection */
require_once $file;

/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require ORIGINAL_ROOT . 'typo3_src/vendor/autoload.php';
$classLoader->addPsr4('CommerceTeam\\Commerce\\', [ORIGINAL_ROOT . 'typo3conf/ext/commerce/Classes']);
$classLoader->addPsr4('CommerceTeam\\Commerce\\Tests\\', [ORIGINAL_ROOT . 'typo3conf/ext/commerce/Tests']);

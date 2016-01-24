<?php

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

unset($MCONF);
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/ViewHelpers/');
$BACK_PATH = '../../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/Resources/Private/Language/locallang_iframetreebrowser.xml';

$MCONF['script'] = 'index.php';
$MCONF['name'] = 'commerce_txcommerceTreeBrowser';
$MCONF['access'] = '';

// @todo remove by using routing
define('TYPO3_MODE', 'BE');
require $BACK_PATH . '/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->run('typo3/');

/**
 * Language
 *
 * @var \TYPO3\CMS\Lang\LanguageService $language
 */
$language = $GLOBALS['LANG'];
$language->includeLLFile('EXT:lang/locallang_misc.xml');

/**
 * Treelib browser.
 *
 * @var \CommerceTeam\Commerce\ViewHelpers\TreelibBrowser $treelibBrowser
 */
$treelibBrowser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \CommerceTeam\Commerce\ViewHelpers\TreelibBrowser::class
);
$treelibBrowser->main();
$treelibBrowser->printContent();

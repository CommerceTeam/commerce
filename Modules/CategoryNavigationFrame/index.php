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
require_once('conf.php');
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Modules/CategoryNavigationFrame/');
$BACK_PATH = '../../../../../typo3/';
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');

// Make instance if it is not an AJAX call
if (!(defined('TYPO3_REQUESTTYPE') && defined('TYPO3_REQUESTTYPE_AJAX')) ||
    !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)
) {
    /**
     * Category navigation frame controller.
     *
     * @var \CommerceTeam\Commerce\Controller\CategoryNavigationFrameController $categoryNavigationFrameController
     */
    $categoryNavigationFrameController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        'CommerceTeam\\Commerce\\Controller\\CategoryNavigationFrameController'
    );
    $categoryNavigationFrameController->initPage();
    $categoryNavigationFrameController->main();
    $categoryNavigationFrameController->printContent();
}

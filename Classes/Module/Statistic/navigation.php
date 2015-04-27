<?php
/**
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
 * Statistic module navigation frame
 */
unset($MCONF);

require_once('conf.php');
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/Module/Statistic/');
$BACK_PATH = '../../../../../../typo3/';

/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');

/** @var Tx_Commerce_ViewHelpers_Navigation_OrdersViewHelper $SOBE */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_ViewHelpers_Navigation_OrdersViewHelper');
$SOBE->init();
$SOBE->initPage();
$SOBE->main();
$SOBE->printContent();

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

/**
 * Category module navigation frame
 */
unset($MCONF);

if (!(defined('TYPO3_REQUESTTYPE') || defined('TYPO3_REQUESTTYPE_AJAX'))) {
	require_once('conf.php');
	define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/Module/Category/');
	$BACK_PATH = '../../../../../../typo3/';
} else {
	// In case of an AJAX Request the script including this script is ajax.php,
	// from which the BACK PATH is ''
	$BACK_PATH = '';
}

require_once($BACK_PATH . 'init.php');

// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	/**
	 * Category navigation viewhelper
	 *
	 * @var \CommerceTeam\Commerce\ViewHelpers\Navigation\CategoryViewHelper $SOBE
	 */
	$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
		'CommerceTeam\\Commerce\\ViewHelpers\\Navigation\\CategoryViewHelper'
	);
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

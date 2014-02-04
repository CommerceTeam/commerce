<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2011 Marketing Factory Consulting GmbH <typo3@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Access module navigation frame
 */
unset($MCONF);

if (!(defined('TYPO3_REQUESTTYPE') || defined('TYPO3_REQUESTTYPE_AJAX'))) {
	require_once('conf.php');
	/** @noinspection PhpIncludeInspection */
	require_once($BACK_PATH . 'init.php');
	/** @noinspection PhpIncludeInspection */
	require_once($BACK_PATH . 'template.php');
} else {
		// In case of an AJAX Request the script including this script is ajax.php, from which the BACK PATH is ''
	/** @noinspection PhpIncludeInspection */
	require_once('init.php');
	/** @noinspection PhpIncludeInspection */
	require('template.php');
}

	// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	/** @var Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper $SOBE */
	$SOBE = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_Navigation_CategoryViewHelper');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

?>
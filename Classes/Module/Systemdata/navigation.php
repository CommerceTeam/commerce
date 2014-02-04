<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2012 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Systemdata module navigation frame
 */
unset($MCONF);

require_once('conf.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xml');

	// This checks permissions and exits if the users has no permission for entry.
/** @var t3lib_beUserAuth $backendUser */
$backendUser = $GLOBALS['BE_USER'];
/** @noinspection PhpUndefinedVariableInspection */
$backendUser->modAccess($MCONF, 1);

if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	/** @var $SOBE Tx_Commerce_ViewHelpers_Navigation_SystemdataViewHelper */
	$SOBE = t3lib_div::makeInstance('Tx_Commerce_ViewHelpers_Navigation_SystemdataViewHelper');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

?>
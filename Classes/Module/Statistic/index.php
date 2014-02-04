<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004 - 2011 Joerg Sprung <typo3@marketing-factory.de>
 *  (c) 2008 - 2011 Ingo Schmitt <typo3@marketing-factory.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Module 'Statistics' for the 'commerce' extension.
 */
unset($MCONF);
require_once('conf.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'init.php');
/** @noinspection PhpIncludeInspection */
require_once($BACK_PATH . 'template.php');

	// This checks permissions and exits if the users has no permission for entry.
/** @var t3lib_beUserAuth $backendUser */
$backendUser = $GLOBALS['BE_USER'];
/** @noinspection PhpUndefinedVariableInspection */
$backendUser->modAccess($MCONF, 1);

/** @var language $language */
$language = $GLOBALS['LANG'];
$language->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xml');
$language->includeLLFile('EXT:lang/locallang_mod_web_list.php');

/** @var Tx_Commerce_Controller_StatisticController $SOBE */
$SOBE = t3lib_div::makeInstance('Tx_Commerce_Controller_StatisticController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
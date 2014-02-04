<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2011 Rene Fritz <r.fritz@colorcube.de>
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

unset($MCONF);
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/ViewHelpers/');
$BACK_PATH = '../../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/Resources/Private/Language/locallang_iframetreebrowser.xml';

$MCONF['script'] = 'index.php';
$MCONF['name'] = 'commerce_txcommerceTreeBrowser';
$MCONF['access'] = '';

/** @noinspection PhpIncludeInspection */
require($BACK_PATH . 'init.php');
/** @noinspection PhpIncludeInspection */
require($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:lang/locallang_misc.xml');

/** @var tx_commerce_treelib_browser $SOBE */
$SOBE = t3lib_div::makeInstance('tx_commerce_treelib_browser');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
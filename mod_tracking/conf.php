<?php
/**
 * $Id: conf.php 147 2006-04-04 10:43:22Z thomas $
 */
 
	// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', 'ext/commerce/mod_tracking/');
$BACK_PATH='../../../';

$MCONF['name']='txcommerceM1_tracking';
$MCONF['access']='user,group';
$MCONF['script']='index.php';
$MCONF['navFrameScript']='class.tx_commerce_category_navframe.php';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref']='LLL:EXT:commerce/mod_tracking/locallang_mod.php';
?>
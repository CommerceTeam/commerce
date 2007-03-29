<?php
/**
 * $Id: conf.php 147 2006-04-04 10:43:22Z thomas $
 */
 
	// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/mod_orders/');
$BACK_PATH='../../../../typo3/';

$MCONF['name']='txcommerceM1_orders';	
$MCONF['access']='user,group';
$MCONF['script']='index.php';
$MCONF['navFrameScript']='class.tx_commerce_order_navframe.php';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref']='LLL:EXT:commerce/mod_orders/locallang_mod.php';
?>
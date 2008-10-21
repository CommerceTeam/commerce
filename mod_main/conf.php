<?php
/**
 * $Id: conf.php 147 2006-04-04 10:43:22Z thomas $
 */
 
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/mod_main/');
$BACK_PATH='../../../../typo3/';

$MCONF['name']='txcommerceM1';
$MCONF['access']='user,group';
//$MCONF['script']='index.php';
$MCONF['navFrameScript']='class.tx_commerce_navframe.php';  // ohne dieser Zeile fehlt der Kategoriebaum !! Franz

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref']='LLL:EXT:commerce/mod_main/locallang_mod.php';
?>
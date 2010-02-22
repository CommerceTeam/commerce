<?php
/**
 * $Id: conf.php 5526 2007-05-25 07:55:11Z franzholz $
 */
 
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/mod_perftest/');
$BACK_PATH='../../../../typo3/';

//$MCONF['navFrameScript'] = 'class.tx_commerce_access_navframe.php'; //Pointer to the navframe-script

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref']='LLL:EXT:commerce/mod_perftest/locallang_mod.php';

$MCONF['script']='index.php';
$MCONF['name']='txcommerceM1';
$MCONF['access']='user,group';
?>

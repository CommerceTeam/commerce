<?php
/**
 * $Id: conf.php 147 2006-04-04 10:43:22Z thomas $
 */
 
define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/mod_category/');
$BACK_PATH='../../../../typo3/';

$MCONF['navFrameScript']='class.tx_commerce_category_navframe.php';  // ohne dieser Zeile fehlt der Kategoriebaum !! Franz

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref']='LLL:EXT:commerce/mod_category/locallang_mod.php';

$MCONF['script']='index.php';
$MCONF['name']='txcommerceM1';
$MCONF['access']='user,group';
?>

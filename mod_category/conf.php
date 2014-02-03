<?php

define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/mod_category/');
$BACK_PATH = '../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/mod_category/locallang_mod.php';

$MCONF['script'] = 'index.php';
$MCONF['name'] = 'txcommerceM1_category';
$MCONF['access'] = 'user,group';
$MCONF['navFrameScript'] = 'class.tx_commerce_category_navframe.php';

?>
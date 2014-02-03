<?php

define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/mod_statistic/');
$BACK_PATH = '../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_statistic.xml';

$MCONF['script'] = 'index.php';
$MCONF['name'] = 'txcommerceM1_statistic';
$MCONF['access'] = 'user,group';
$MCONF['navFrameScript'] = 'class.tx_commerce_statistic_navframe.php';

?>
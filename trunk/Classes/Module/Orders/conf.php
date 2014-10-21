<?php

define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/Module/Orders/');
$BACK_PATH = '../../../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = '../../../Resources/Public/Icons/mod_orders.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_orders.xml';

$MCONF['name'] = 'txcommerceM1_orders';
$MCONF['script'] = 'index.php';
$MCONF['navFrameScript'] = 'navigation.php';
$MCONF['access'] = 'user,group';

?>
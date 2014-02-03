<?php

define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/Module/Category/');
$BACK_PATH = '../../../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = '../../../Resources/Public/Icons/mod_category.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_category.xml';

$MCONF['script'] = 'index.php';
$MCONF['name'] = 'txcommerceM1_category';
$MCONF['access'] = 'user,group';
$MCONF['navFrameScript'] = 'navigation.php';

?>
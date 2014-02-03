<?php

define('TYPO3_MOD_PATH', '../typo3conf/ext/commerce/Classes/Module/Access/');
$BACK_PATH = '../../../../../../typo3/';

$MLANG['default']['tabs_images']['tab'] = '../../../Resources/Public/Icons/mod_access.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_access.xml';

$MCONF['script'] = 'index.php';
$MCONF['name'] = 'txcommerceM1_access';
$MCONF['access'] = 'user,group';
$MCONF['navFrameScript'] = 'navigation.php';

?>
<?php
/**
 * Used to enable switch of the index-File
 * Between graytree and new treelib
 */
unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
$useGraytree = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['useGraytree'];

if($useGraytree) {
	require_once('index_graytree.php');
} else {
	require_once('index.php');
}
exit;
?>

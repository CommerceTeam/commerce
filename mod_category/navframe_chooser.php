<?php
/**
 * Used to switch between the navframes
 * Between the graytree Version and the new one from treelib
 */
unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
$useGraytree = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['useGraytree'];

if($useGraytree) {
	header('Location: class.tx_commerce_category_navframe_graytree.php');
} else {
	header('Location: class.tx_commerce_category_navframe.php');
}
exit;
?>

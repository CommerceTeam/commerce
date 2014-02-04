<?php
/**
 * Implements a leaf specific for holding categories
 */
class Tx_Commerce_Tree_Leaf_Category extends Tx_Commerce_Tree_Leaf_Master {
	protected $mountClass = 'Tx_Commerce_Tree_CategoryMounts';
}

class_alias('Tx_Commerce_Tree_Leaf_Category', 'tx_commerce_leaf_category');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/Category.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/Category.php']);
}

?>
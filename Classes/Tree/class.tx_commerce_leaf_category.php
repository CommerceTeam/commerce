<?php
/**
 * Implements a leaf specific for holding categories
 */
class tx_commerce_leaf_category extends leafMaster {
	protected $mountClass = 'tx_commerce_categorymounts';
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_category.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_category.php']);
}

?>
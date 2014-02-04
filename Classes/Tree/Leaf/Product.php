<?php
/**
 * Implements the Leaf for the Products
 */
class Tx_Commerce_Tree_Leaf_Product extends Tx_Commerce_Tree_Leaf_Slave {
}

class_alias('Tx_Commerce_Tree_Leaf_Product', 'tx_commerce_leaf_product');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/Product.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/Product.php']);
}

?>
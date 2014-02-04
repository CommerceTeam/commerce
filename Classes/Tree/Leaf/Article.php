<?php
/**
 * Implements the Leaf for the Articles
 */
class Tx_Commerce_Tree_Leaf_Article extends Tx_Commerce_Tree_Leaf_Slave {
}

class_alias('Tx_Commerce_Tree_Leaf_Article', 'tx_commerce_leaf_article');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/Article.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Tree/Leaf/Article.php']);
}

?>
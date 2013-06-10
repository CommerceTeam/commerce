<?php
/**
 * Implements the Leaf for the Articles
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leafSlave.php');

class tx_commerce_leaf_article extends leafSlave {
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_article.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_article.php']);
}
?>

<?php
/**
 * Created on 30.07.2008
 * 
 * Implements a leaf specific for holding categories
 * 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 */
require_once(t3lib_extmgm::extPath('commerce').'tree/class.leafMaster.php');

class tx_commerce_leaf_category extends leafMaster {
	protected $mountClass = 'tx_commerce_categorymounts'; 
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/treelib/class.tx_commerce_leaf_category.php']);
}
?>

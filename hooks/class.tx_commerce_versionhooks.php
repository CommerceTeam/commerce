<?php
/**
 * Implements the hooks for versioning and swapping
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
require_once(t3lib_extmgm::extPath('commerce') .'lib/class.tx_commerce_belib.php');

class tx_commerce_versionhooks {
	
	/**
	 * After versioning for tx_commerce_products, this also
	 * 1) copies the Attributes (flex and mm)
	 * 2) copies the Articles and keeps their relations
	 * 
	 * @return {void}
	 * @param $table {string}	Tablename on which the swap happens
	 * @param $id {int}			id of the LIVE Version to swap
	 * @param $swapWith {int}	id of the Offline Version to swap with
	 * @param $swapIntoWS {int}	If set, swaps online into workspace instead of publishing out of workspace.
	 * @param $pObj {object}	TCEMain Class Reference
	 */
	public function processSwap_postProcessSwap($table, $id, $swapWith, $swapIntoWS, & $pObj) {
		if('tx_commerce_products' == $table) {
			$copy = !is_null($swapIntoWS);
		
			//give Attributes from swapWith to id
			tx_commerce_belib::swapProductAttributes($swapWith, $id, $copy);
			
			//give Articles from swapWith to id
			tx_commerce_belib::swapProductArticles($swapWith, $id, $copy);
		}
	}
	
}

//XClass Statement
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_versionhooks.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_versionhooks.php"]);
}
?>
<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


class tx_commerce_linkhandler {

	function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, &$pObj) {
		$this->pObj=&$pObj;
		
		$linkHandlerData=t3lib_div::trimExplode('|',$linkHandlerValue);
		$addparams = "";
		foreach ($linkHandlerData as $linkData) {
			$params = t3lib_div::trimExplode(':',$linkData);
			if (isset($params[0])){
				if ($params[0] == 'tx_commerce_products') {
					$addparams .= "&tx_commerce_pi1[showUid]=".(int)$params[1];
				} elseif ($params[0] == 'tx_commerce_categories') {
					$addparams .= "&tx_commerce_pi1[catUid]=".(int)$params[1];
				}
			}
			if (isset($params[2])){
				if ($params[2] == 'tx_commerce_products') {
					$addparams .= "&tx_commerce_pi1[showUid]=".(int)$params[3];
				} elseif ($params[2] == 'tx_commerce_categories') {
					$addparams .= "&tx_commerce_pi1[catUid]=".(int)$params[3];
				}
			}			
		}
 
		if (strlen($addparams) <= 0) {
			return $linktxt;
		}		

		$localcObj = t3lib_div::makeInstance('tslib_cObj');
		$lconf = array ( "parameter" => $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_commerce_pi1.']['overridePid'],
						"additionalParams" => $addparams,
						"additionalParams.insertData" => 1,
						"useCacheHash" => 1);

		return $localcObj->typoLink($linktxt, $lconf);

	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/hooks/commerce/class.tx_commerce_linkhandler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/hooks/commerce/class.tx_commerce_linkhandler.php']);
}
?>
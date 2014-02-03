<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

class tx_commerce_linkhandler {
	/**
	 * @var tslib_cObj
	 */
	public $pObj;

	/**
	 * @param string $linktxt
	 * @param array $conf
	 * @param string $linkHandlerKeyword
	 * @param string $linkHandlerValue
	 * @param array $link_param
	 * @param tslib_cObj $pObj
	 * @return string
	 */
	public function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, &$pObj) {
		$this->pObj = &$pObj;

		$linkHandlerData = t3lib_div::trimExplode('|', $linkHandlerValue);
		
		$addparams = '';
		foreach ($linkHandlerData as $linkData) {
			$params = t3lib_div::trimExplode(':', $linkData);
			if (isset($params[0])) {
				if ($params[0] == 'tx_commerce_products') {
					$addparams .= '&tx_commerce_pi1[showUid]=' . (int) $params[1];
				} elseif ($params[0] == 'tx_commerce_categories') {
					$addparams .= '&tx_commerce_pi1[catUid]=' . (int) $params[1];
				}
			}
			if (isset($params[2])) {
				if ($params[2] == 'tx_commerce_products') {
					$addparams .= '&tx_commerce_pi1[showUid]=' . (int) $params[3];
				} elseif ($params[2] == 'tx_commerce_categories') {
					$addparams .= '&tx_commerce_pi1[catUid]=' . (int) $params[3];
				}
			}			
		}
 
		if (strlen($addparams) <= 0) {
			return $linktxt;
		}		

		/** @var tslib_cObj $localcObj */
		$localcObj = t3lib_div::makeInstance('tslib_cObj');
		
		$DisplayPID = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_commerce_pi1.']['overridePid'];
		if (empty($DisplayPID)) {
			$DisplayPID = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['previewPageID'];
		}
		
			// remove the first param of '$link_param' (this is the page id wich is set by $DisplayPID)
			// and add all params left (e.g. css class, target...) to the value of $lconf['paramter']
		$link_param_array = explode(' ', $link_param);
		if (is_array($link_param_array)) {
			$link_param_array = array_splice($link_param_array, 1);
			if (count($link_param_array) > 0) {
				$link_param = $DisplayPID . ' ' . implode(' ', $link_param_array);
			} else {
				$link_param = $DisplayPID;
			}
		} else {
			$link_param = $DisplayPID;
		}
		
		$lconf = array (
			'parameter' => $link_param,
			'additionalParams' => $addparams,
			'additionalParams.insertData' => 1,
			'useCacheHash' => 1
		);
		
		return $localcObj->typoLink($linktxt, $lconf);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/hooks/commerce/class.tx_commerce_linkhandler.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/hooks/commerce/class.tx_commerce_linkhandler.php']);
}

?>

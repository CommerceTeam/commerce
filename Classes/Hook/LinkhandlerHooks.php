<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2009 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_Commerce_Hook_LinkhandlerHooks {
	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $pObj;

	/**
	 * @param string $linktxt
	 * @param array $conf
	 * @param string $linkHandlerKeyword
	 * @param string $linkHandlerValue
	 * @param array $link_param
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
	 * @return string
	 */
	public function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, &$pObj) {
		$this->pObj = &$pObj;

		$linkHandlerData = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $linkHandlerValue);

		$addparams = '';
		foreach ($linkHandlerData as $linkData) {
			$params = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $linkData);
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

		/** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $localcObj */
		$localcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController');

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

		$lconf = $conf;
		unset($lconf['parameter.']);
		$lconf['parameter'] = $link_param;
		$lconf['additionalParams'] .= $addparams;

		return $localcObj->typoLink($linktxt, $lconf);
	}
}

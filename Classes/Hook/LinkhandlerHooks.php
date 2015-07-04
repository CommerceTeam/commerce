<?php
namespace CommerceTeam\Commerce\Hook;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class \CommerceTeam\Commerce\Hook\LinkhandlerHooks
 *
 * @author 2008-2009 Ingo Schmitt <is@marketing-factory.de>
 */
class LinkhandlerHooks {
	/**
	 * Parent object
	 *
	 * @var ContentObjectRenderer
	 */
	protected $pObj;

	/**
	 * Main function
	 *
	 * @param string $linktxt Link text
	 * @param array $conf Configuration
	 * @param string $linkHandlerKeyword Keyword
	 * @param string $linkHandlerValue Value
	 * @param array $linkParameter Link parameter
	 * @param ContentObjectRenderer $pObj Parent

	 * @return string
	 */
	public function main($linktxt, array $conf, $linkHandlerKeyword, $linkHandlerValue, array $linkParameter,
		ContentObjectRenderer &$pObj
	) {
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

		/**
		 * Local content object
		 *
		 * @var ContentObjectRenderer $localcObj
		 */
		$localcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

		$displayPageId = $this->getFrontendController()->tmpl->setup['plugin.']['tx_commerce_pi1.']['overridePid'];
		if (empty($displayPageId)) {
			$displayPageId = SettingsFactory::getInstance()->getExtConf('previewPageID');
		}

		// remove the first param of '$link_param' (this is the page id wich is
		// set by $DisplayPID) and add all params left (e.g. css class,
		// target...) to the value of $lconf['paramter']
		$linkParamArray = explode(' ', $linkParameter);
		if (is_array($linkParamArray)) {
			$linkParamArray = array_splice($linkParamArray, 1);
			if (count($linkParamArray) > 0) {
				$linkParameter = $displayPageId . ' ' . implode(' ', $linkParamArray);
			} else {
				$linkParameter = $displayPageId;
			}
		} else {
			$linkParameter = $displayPageId;
		}

		$lconf = $conf;
		unset($lconf['parameter.']);
		$lconf['parameter'] = $linkParameter;
		$lconf['additionalParams'] .= $addparams;

		return $localcObj->typoLink($linktxt, $lconf);
	}


	/**
	 * Get typoscript frontend controller
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}
}

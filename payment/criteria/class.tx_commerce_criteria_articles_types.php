<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Volker Graubaum <vg@e-netconsulting.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 *
 *
 * @package commerce
 * @subpackage payment
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @internal Maintainer Michael Staatz <michael.staatz@e-netconsulting.com>
 */

require_once (t3lib_extmgm::extPath('commerce') . 'payment/criteria/class.tx_commerce_criteria_abstract.php');

class tx_commerce_criteria_articles_types extends tx_commerce_criteria_abstract {

	public function isAllowed() {
		if (is_array($this->options['allowedArticlesTypes'])) {
			$articlesTypes = 0;
			foreach ($this->options['allowedArticlesTypes'] as $typeUid) {
				$articlesTypes += $GLOBALS['TSFE']->fe_user->tx_commerce_basket->getArticleTypeCount($typeUid);
			}
			return ($articlesTypes > 0);
		} elseif (is_array($this->options['notAllowedArticlesTypes'])) {
			$articlesTypes = 0;
			foreach ($this->options['notAllowedArticlesTypes'] as $typeUid) {
				$articlesTypes += $GLOBALS['TSFE']->fe_user->tx_commerce_basket->getArticleTypeCount($typeUid);
			}
			return ($articlesTypes == 0);
		} else {
			$message = '$this->options[allowedArticlesTypes] or ';
			$message .= '$this->options[notAllowedArticlesTypes] not set ';
			throw new Exception($message);
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_articles_types.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_articles_types.php"]);
}
?>
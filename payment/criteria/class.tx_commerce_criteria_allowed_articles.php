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
 * @internal Maintainer Michael Staatz
 */

require_once (t3lib_extmgm::extPath('commerce') . 'payment/criteria/class.tx_commerce_criteria_abstract.php');

class tx_commerce_criteria_allowed_articles extends tx_commerce_criteria_abstract {

	public function isAllowed() {
		if (is_array($this->options['allowedArticles'])) {
			$articles = array();
			foreach($this->options['allowedArticles'] as $typeUid) {
				$articles = array_merge(
					$articles,
					$GLOBALS['TSFE']->fe_user->tx_commerce_basket->get_articles_by_article_type_uid_asUidlist($typeUid)
				);
			}
			return (count($articles) > 0);
		} elseif (is_array($this->options['notAllowedArticles'])) {
			$articles = array();
			foreach($this->options['notAllowedArticles'] as $typeUid) {
				$articles = array_merge(
					$articles,
					$GLOBALS['TSFE']->fe_user->tx_commerce_basket->get_articles_by_article_type_uid_asUidlist($typeUid)
				);
			}
			return (count($articles) > 0);
		}else {
			$message = '$this->options[allowedArticles] or ';
			$message .= '$this->options[notAllowedArticles] not set ';
			throw new Exception($message);
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_allowed_articles.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_allowed_articles.php"]);
}
?>
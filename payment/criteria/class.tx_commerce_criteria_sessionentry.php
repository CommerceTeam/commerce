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
 * @package commerce
 * @subpackage payment
 * @author Volker Graubaum <vg@e-netconsulting.de>
 * @internal Maintainer Michael Staatz
 */

require_once (t3lib_extmgm::extPath('commerce') . 'payment/criteria/class.tx_commerce_criteria_abstract.php');

class tx_commerce_criteria_sessionentry extends tx_commerce_criteria_abstract {

	public function isAllowed() {
		if (!empty($this->options['isInSession'])) {
			foreach ($this->options['isInSession'] as $sessionKey => $sessionValue) {
				if ($GLOBALS['TSFE']->fe_user->getKey('ses', $sessionKey) == $sessionValue) {
					return true;
				}
			}
			return false;
		} elseif (!empty($this->options['isNotInSession'])) {
			foreach ($this->options['isNotInSession'] as $sessionKey => $sessionValue) {
				if ($GLOBALS['TSFE']->fe_user->getKey('ses', $sessionKey) != $sessionValue) {
					return true;
				}
			}
			return false;
		} else {
			$message = '$this->options[isInSession] or ';
			$message .= '$this->options[isNotInSession] are not set!';
			throw new Exception($message);
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_sessionentry.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_sessionentry.php"]);
}
?>
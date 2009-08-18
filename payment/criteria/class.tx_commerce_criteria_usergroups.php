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

class tx_commerce_criteria_usergroups extends tx_commerce_criteria_abstract {

	public function isAllowed() {
		if (!empty($this->options['allowedUsergroups'])) {
			// check if given list of usergroup-id's is in $GLOBALS['TSFE']->gr_list
			// list is like 0, -1, ....... or
			// 				0, -2, .......
			// where -1 => not logged in and -2 => logged in
			return t3lib_div::inList($GLOBALS['TSFE']->gr_list, $this->options['allowedUsergroups']);
		} elseif (!empty($this->options['notAllowedUsergroups'])) {
			// same as above but we want a "false" if is in list
			$notAllowedUsergroups = !t3lib_div::inList(
				$GLOBALS['TSFE']->gr_list,
				$this->options['notAllowedUsergroups']
			);

			// because we use this criteria only for payment, not for provider,
			// this give us the posibility for an OR of criterias.
			if (isset($this->options['iffalse'])) {
				$criterias = array();
				$criteraConfigurations = $this->options['iffalse'];
				if (is_array($criteraConfigurations)) {
					foreach ($criteraConfigurations as $criteriaConfiguration) {
						$criteria = t3lib_div::getUserObj($criteriaConfiguration['class']);
						$criteria->init();
						if ($criteria instanceof tx_commerce_criteria_abstract) {
							if (is_array($criteriaConfiguration['options'])) {
								$criteria->setOptions($criteriaConfiguration['options']);
							}
							$criterias[] = $criteria;
						}
					}
					unset($criteria);
					foreach ($criterias as $criteria) {
						if ($criteria->isAllowed() === false) {
							// here we have to return true if the criteria is not allowed
							// so we can cancel in this case the result of
							// "notAllowedUsergroups"
							return true;
						}
					}
				}
			}

			return $notAllowedUsergroups;
		} else {
			$message = '$this->options[allowedUsergroups] or ';
			$message .= '$this->options[notAllowedUsergroups] are not set!';
			throw new Exception($message);
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_usergroups.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/payment/criteria/class.tx_commerce_criteria_usergroups.php"]);
}
?>
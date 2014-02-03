<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Michiel Roos (typo3@meyson.nl)
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
 * Class that adds the wizard icon.
 *
 * @author	Michiel Roos <typo3@meyson.nl>
 */
class tx_commerce_pi3_wizicon {
	public function proc($wizardItems) {
		/** @var language $language */
		$language = $GLOBALS['LANG'];

		$LL = $this->includeLocalLang();
		$wizardItems['plugins_tx_commerce_pi3'] = array(
			'icon' => t3lib_extMgm::extRelPath('commerce') . 'Resources/Public/Icons/ce_wiz.gif',
			'title' => $language->getLLL('tt_content.list_type_pi3', $LL),
			'description' => $language->getLLL('tt_content.list_type_pi3.wiz_description', $LL),
			'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=commerce_pi3'
		);

		return $wizardItems;
	}


	protected function includeLocalLang() {
		$llFile = t3lib_extMgm::extPath('commerce') . 'Resources/Private/Language/locallang_be.xml';
		$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);

		return $LOCAL_LANG;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi3/class.tx_commerce_pi3_wizicon.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/pi3/class.tx_commerce_pi3_wizicon.php']);
}

?>
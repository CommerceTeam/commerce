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
	function proc($wizardItems) {
		global $LANG;

		$LL = $this->includeLocalLang();
		$wizardItems['plugins_tx_commerce_pi3'] = array(
			'icon' => t3lib_extMgm::extRelPath('commerce') . 'res/icons/ce_wiz.gif',
			'title' => $LANG->getLLL('tt_content.list_type_pi3', $LL),
			'description' => $LANG->getLLL('tt_content.list_type_pi3.wiz_description', $LL),
			'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=commerce_pi3'
		);

		return $wizardItems;
	}


	function includeLocalLang() {
		$llFile = t3lib_extMgm::extPath('commerce') . 'locallang_be.xml';
		$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);

		return $LOCAL_LANG;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/pi3/class.tx_commerce_pi3_wizicon.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/pi3/class.tx_commerce_pi3_wizicon.php']);
}
?>

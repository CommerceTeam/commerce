<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Carsten Lausen
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



/**
* class tx_srfeuserregister_hooksHandler for the extension takeaday feuser extension
* The method registrationProcess_afterSaveCreate() from this class is called by save() from class.tx_srfeuserregister.php
* The method registrationProcess_afterSaveEdit() from this class is called by save() from class.tx_srfeuserregister.php
* 
* This class handles frontend feuser updates 
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/



class tx_srfeuserregister_commerce_hooksHandler {


	/**
	 * after save create
	 * 
	 * sr_feuser_register registration process after saving new dataset
	 * 
	 * @param array $currentArr: complete array of feuser fields
	 * @param object $pObj: page Object
	 */
	function registrationProcess_afterSaveCreate($currentArr, &$pObj) {
//		debug($currentArr,'$currentArr');
//		debug($pObj,'$pObj');

		//notify observer
		feusers_observer::update('new', $currentArr['uid'], $currentArr);
	}


	/**
	 * after edit create
	 * 
	 * sr_feuser_register registration process after saving edited dataset
	 * 
	 * @param array $currentArr: complete array of feuser fields
	 * @param object $pObj: page Object
	 */
	function registrationProcess_afterSaveEdit($currentArr, &$pObj) {
//		debug($currentArr,'$currentArr');
//		debug($pObj,'$pObj');

		//notify observer
		feusers_observer::update('update', $currentArr['uid'], $currentArr);
	}

}

if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/commerce/hooks/class.tx_srfeuserregister_hooksHandler.php"]) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/commerce/hooks/class.tx_srfeuserregister_hooksHandler.php"]);
}

?>
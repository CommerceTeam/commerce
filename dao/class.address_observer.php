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
* class address_observer for the takeaday feuser extension
* The class satisfies the observer design pattern.
* The method update() from this class is called as static by "hooksHandler" classes
*
* This class handles tt_address updates
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/

require_once(t3lib_extMgm::extPath('commerce').'dao/class.address_object.php');
require_once(t3lib_extMgm::extPath('commerce').'dao/class.feuser_object.php');

class address_observer {

	var $observable;  //Link to observable

	/**
	 * Constructor
	 *
	 * Link observer and observable
	 * Not needed for typo3 hook concept.
	 *
	 * @param obj &$observable: observed object
	 */
	function feuser_observer(&$observable) {
		$this->observable =& $observable;
		$observable->addObserver($this);
	}


	/**
	 * Handle update event.
	 * Is called from observable or hook handlers upon event.
	 *
	 * Keep this method static for efficient integration into hookHandlers.
	 * Communicate using push principle to avoid errors.
	 *
	 * @param string $status: update or new
	 * @param string $id: database table
	 * @param array $changedFieldArray: reference to the incoming fields
	 */
	function update($status, $id, &$changedFieldArray) {

//		xdebug_start_trace();

		//get complete address object
		$address_dao =& new address_dao($id);

		//get feuser id
		$feuser_id = $address_dao->get('tx_commerce_fe_user_id');

		if(!empty($feuser_id)) {
			//get associated feuser object
			$feuser_dao =& new feuser_dao($feuser_id);

			//update feuser object
			$field_mapper = new feuser_address_fieldmapper;
			$field_mapper->map_address_to_feuser($address_dao,$feuser_dao);

			//set main address id in feuser
			$feuser_dao->set('tx_commerce_tt_address_id',$id);
			$feuser_dao->save();
		}

//		debug($address_dao,'$address_dao');
//		debug($feuser_dao,'$feuser_dao');

//		debug(xdebug_get_function_trace(),'xdebug_get_function_trace()');
//		xdebug_stop_trace();
	}


	function checkDelete($id) {

 		$dbFields = 'uid';
 		$dbTable = 'fe_users';
 		$dbWhere = '(tx_commerce_tt_address_id="'.intval($id).'")';
 		$dbWhere .= 'AND (deleted="0")';

		//execute query
//		debug(array('dbSelectById' => $GLOBALS['TYPO3_DB']->SELECTquery($dbFields, $dbTable, $dbWhere)),get_class($this));
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($dbFields, $dbTable, $dbWhere);

        //check dependencies (selected rows)
        if($GLOBALS["TYPO3_DB"]->sql_num_rows($res)>0) {
        	//errormessage
        	$msg='Main feuser address. You can not delete this address.';
        } else {
        	//no errormessage
        	$msg=false;
        }

		//free results
		$GLOBALS["TYPO3_DB"]->sql_free_result($res);

		//debug(array($id,$msg));
		return $msg;
	}



}
// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_observer.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_observer.php']);
}
?>
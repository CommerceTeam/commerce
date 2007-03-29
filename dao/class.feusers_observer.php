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
* class feusers_observer for the takeaday feuser extension
* The class satisfies the observer design pattern.
* The method update() from this class is called as static by "hooksHandler" classes
*
* This class handles feuser updates
*
*
* @access public
* @package TYPO3
* @subpackage commerce
* @author Carsten Lausen <cl@e-netconsulting.de>
*/

require_once(t3lib_extMgm::extPath('commerce').'dao/class.address_object.php');
require_once(t3lib_extMgm::extPath('commerce').'dao/class.feuser_object.php');

class feusers_observer {

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

//		debug($changedFieldArray,'changedFieldArray');
//
//		if($changedFieldArray['tx_commerce_tt_address_id']) {
//
//		}


		//get complete feuser object
		$feuser_dao =& new feuser_dao($id);
//		debug($feuser_dao);
		//$feuser_obj =& $feuser_dao->getObject();

		//get main address id from feuser object
		$top_id = $feuser_dao->get('tx_commerce_tt_address_id');

		//debug($top_id);

		if(empty($top_id)) {

			//get new address object
			$address_dao =& new address_dao();

			//set feuser uid and main address flag
			$address_dao->set('tx_commerce_fe_user_id',$feuser_dao->get('id'));
			$address_dao->set('tx_commerce_is_main_address','1');

			//set address type if not yet defined
			if(!$address_dao->issetProperty('tx_commerce_address_type_id'))$address_dao->set('tx_commerce_address_type_id',1);

		} else {

			//get existing address object
			$address_dao =& new address_dao($top_id);
		}

//		debug($address_dao,'$address_dao');
//		debug($feuser_dao,'$feuser_dao');
//		debug($status);

		//apply changes to address object
		$field_mapper = new feuser_address_fieldmapper;
		$field_mapper->map_feuser_to_address($feuser_dao,$address_dao);

		//save address object
		$address_dao->save();

		//update main address id
		if ($top_id != $address_dao->get('id')) {
			$feuser_dao->set('tx_commerce_tt_address_id',$address_dao->get('id'));
			$feuser_dao->save();
		}

//		debug($address_dao,'$address_dao');
//		debug($feuser_dao,'$feuser_dao');

//		debug(xdebug_get_function_trace(),'xdebug_get_function_trace()');
//		xdebug_stop_trace();
	}


}
 // Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feusers_observer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feusers_observer.php']);
}
?>
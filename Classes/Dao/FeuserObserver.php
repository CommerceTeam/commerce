<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 Carsten Lausen <cl@e-netconsulting.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * class Tx_Commerce_Dao_FeuserObserver for the takeaday feuser extension
 * The class satisfies the observer design pattern.
 * The method update() from this class is called as static by "hooksHandler" classes
 * This class handles feuser updates
 */
class Tx_Commerce_Dao_FeuserObserver {
	/**
	 * Link to observable
	 *
	 * @var object
	 */
	public $observable;

	/**
	 * Constructor
	 * Link observer and observable
	 * Not needed for typo3 hook concept.
	 *
	 * @param object &$observable : observed object
	 */
	public function __construct(&$observable) {
		$this->observable = & $observable;
		$observable->addObserver($this);
	}

	/**
	 * Handle update event.
	 * Is called from observable or hook handlers upon event.
	 * Keep this method static for efficient integration into hookHandlers.
	 * Communicate using push principle to avoid errors.
	 *
	 * @param string $status : update or new
	 * @param string $id : database table
	 * @param array $changedFieldArray : reference to the incoming fields
	 */
	public static function update($status, $id, &$changedFieldArray) {
		/** @var Tx_Commerce_Dao_AddressDao $addressDao */
		/** @var Tx_Commerce_Dao_FeuserDao $feuserDao */
		$feuserDao = t3lib_div::makeInstance('Tx_Commerce_Dao_FeuserDao', $id);

			// get main address id from feuser object
		$topId = $feuserDao->get('tx_commerce_tt_address_id');

		/** @var Tx_Commerce_Dao_AddressDao $addressDao */
		if (empty($topId)) {
				// get new address object
			$addressDao = t3lib_div::makeInstance('Tx_Commerce_Dao_AddressDao');

				// set feuser uid and main address flag
			$addressDao->set('tx_commerce_fe_user_id', $feuserDao->get('id'));
			$addressDao->set('tx_commerce_is_main_address', '1');

				// set address type if not yet defined
			if (!$addressDao->issetProperty('tx_commerce_address_type_id')) {
				$addressDao->set('tx_commerce_address_type_id', 1);
			}
		} else {
				// get existing address object
			$addressDao = t3lib_div::makeInstance('Tx_Commerce_Dao_AddressDao', $topId);
		}

			// apply changes to address object
		/** @var Tx_Commerce_Dao_FeuserAddressFieldmapper $fieldMapper */
		$fieldMapper = t3lib_div::makeInstance('Tx_Commerce_Dao_FeuserAddressFieldmapper');
		$fieldMapper->mapFeuserToAddress($feuserDao, $addressDao);

			// save address object
		$addressDao->save();

			// update main address id
		if ($topId != $addressDao->get('id')) {
			$feuserDao->set('tx_commerce_tt_address_id', $addressDao->get('id'));
			$feuserDao->save();
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Dao/class.feusers_observer.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Dao/class.feusers_observer.php']);
}

?>
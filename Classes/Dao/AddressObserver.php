<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
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
 * class Tx_Commerce_Dao_AddressObserver for the takeaday feuser extension
 * The class satisfies the observer design pattern.
 * The method update() from this class is called as static by "hooksHandler"
 * classes
 * This class handles tt_address updates
 */
class Tx_Commerce_Dao_AddressObserver {
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
	 * @return self
	 */
	public function __construct(&$observable) {
		$this->observable = $observable;
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
	 * @return void
	 */
	public static function update($status, $id) {
			// get complete address object
		/** @var Tx_Commerce_Dao_AddressDao $addressDao */
		$addressDao = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_AddressDao', $id);

			// get feuser id
		$feuserId = $addressDao->get('tx_commerce_fe_user_id');

		if (!empty($feuserId)) {
				// get associated feuser object
			/** @var Tx_Commerce_Dao_FeuserDao $feuserDao */
			$feuserDao = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDao', $feuserId);

				// update feuser object
			/** @var Tx_Commerce_Dao_FeuserAddressFieldmapper $fieldMapper */
			$fieldMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserAddressFieldmapper');
			$fieldMapper->mapAddressToFeuser($addressDao, $feuserDao);

				// set main address id in feuser
			$feuserDao->set('tx_commerce_tt_address_id', $id);
			$feuserDao->save();
		}
	}

	/**
	 * Check if address may get deleted
	 *
	 * @param integer $id
	 * @return boolean|string
	 */
	public static function checkDelete($id) {
		$dbFields = 'uid';
		$dbTable = 'fe_users';
		$dbWhere = '(tx_commerce_tt_address_id="' . (int) $id . '") AND (deleted="0")';

		$database = self::getDatabaseConnection();

		$res = $database->exec_SELECTquery($dbFields, $dbTable, $dbWhere);

		// check dependencies (selected rows)
		if ($database->sql_num_rows($res) > 0) {
			// errormessage
			$msg = 'Main feuser address. You can not delete this address.';
		} else {
			// no errormessage
			$msg = FALSE;
		}

			// free results
		$database->sql_free_result($res);

		return $msg;
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected static function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressObserver.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressObserver.php']);
}

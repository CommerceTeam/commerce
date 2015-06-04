<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * For the takeaday feuser extension
 * The class satisfies the observer design pattern.
 * The method update() from this class is called as static by "hooksHandler"
 * classes
 * This class handles feuser updates
 *
 * Class Tx_Commerce_Dao_FeuserObserver
 *
 * @author 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
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
	 * @param object $observable Observed object
	 *
	 * @return self
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
	 * @return void
	 */
	public static function update($status, $id) {
		/** @var Tx_Commerce_Dao_FeuserDao $feuserDao */
		$feuserDao = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDao', $id);

			// get main address id from feuser object
		$topId = $feuserDao->get('tx_commerce_tt_address_id');

		/** @var Tx_Commerce_Dao_AddressDao $addressDao */
		if (empty($topId)) {
				// get new address object
			$addressDao = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_AddressDao');

				// set feuser uid and main address flag
			$addressDao->set('tx_commerce_fe_user_id', $feuserDao->get('id'));
			$addressDao->set('tx_commerce_is_main_address', '1');

				// set address type if not yet defined
			if (!$addressDao->issetProperty('tx_commerce_address_type_id')) {
				$addressDao->set('tx_commerce_address_type_id', 1);
			}
		} else {
				// get existing address object
			$addressDao = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_AddressDao', $topId);
		}

			// apply changes to address object
		/** @var Tx_Commerce_Dao_FeuserAddressFieldmapper $fieldMapper */
		$fieldMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserAddressFieldmapper');
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserObserver.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserObserver.php']);
}

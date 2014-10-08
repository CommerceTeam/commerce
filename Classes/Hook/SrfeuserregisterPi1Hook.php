<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2008 Carsten Lausen <cl@e-netconsulting.de>
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
 * class tx_srfeuserregister_hooksHandler for the extension takeaday feuser
 * The method registrationProcess_afterSaveCreate() is called by save()
 * The method registrationProcess_afterSaveEdit() is called by save()
 *
 * This class handles frontend feuser updates
 */
class Tx_Commerce_Hook_SrfeuserregisterPi1Hook {
	/**
	 * after save create
	 *
	 * sr_feuser_register registration process after saving new dataset
	 *
	 * @param array $currentArr complete array of feuser fields
	 * @return void
	 */
	public function registrationProcess_afterSaveCreate($currentArr) {
			// notify observer
		Tx_Commerce_Dao_FeuserObserver::update('new', $currentArr['uid'], $currentArr);
	}

	/**
	 * after edit create
	 *
	 * sr_feuser_register registration process after saving edited dataset
	 *
	 * @param array $currentArr complete array of feuser fields
	 * @return void
	 */
	public function registrationProcess_afterSaveEdit($currentArr) {
			// notify observer
		Tx_Commerce_Dao_FeuserObserver::update('update', $currentArr['uid'], $currentArr);
	}
}

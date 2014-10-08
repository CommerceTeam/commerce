<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Erik Frister <typo3@marketing-factory.de>
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
 * Implements the hooks for versioning and swapping
 */
class Tx_Commerce_Hook_VersionHooks {
	/**
	 * After versioning for tx_commerce_products, this also
	 * 1) copies the Attributes (flex and mm)
	 * 2) copies the Articles and keeps their relations
	 *
	 * @param string $table Tablename on which the swap happens
	 * @param integer $id id of the LIVE Version to swap
	 * @param integer $swapWith id of the Offline Version to swap with
	 * @param integer $swapIntoWS If set, swaps online into workspace instead of publishing out of workspace.
	 * @return void
	 */
	public function processSwap_postProcessSwap($table, $id, $swapWith, $swapIntoWS) {
		if ('tx_commerce_products' == $table) {
			$copy = !is_null($swapIntoWS);

				// give Attributes from swapWith to id
			Tx_Commerce_Utility_BackendUtility::swapProductAttributes($swapWith, $id, $copy);

				// give Articles from swapWith to id
			Tx_Commerce_Utility_BackendUtility::swapProductArticles($swapWith, $id, $copy);
		}
	}
}

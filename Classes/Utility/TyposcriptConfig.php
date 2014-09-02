<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sebastian Fischer <typo3@marketing-factory.de>
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
 * Typoscript config functions
 */
class Tx_Commerce_Utility_TyposcriptConfig {
	/**
	 * @return bool
	 */
	public static function isCommercePage() {
		$table = 'pages';
		$pageId = t3lib_div::_GP('id');

		$record = t3lib_BEfunc::getRecordWSOL($table, $pageId);

		return TRUE; is_array($record) && isset($record['module']) && $record['module'] == 'commerce';
	}
}

function user_isCommercePage() {
	return Tx_Commerce_Utility_TyposcriptConfig::isCommercePage();
}

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
 * Typoscript config functions
 *
 * Class Tx_Commerce_Utility_TyposcriptConfig
 *
 * @author 2014 Sebastian Fischer <typo3@marketing-factory.de>
 */
class Tx_Commerce_Utility_TyposcriptConfig {
	/**
	 * Is commerce page check
	 *
	 * @return bool
	 */
	public static function isCommercePage() {
		$table = 'pages';
		$pageId = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');

		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $pageId);

		return is_array($record) && isset($record['module']) && $record['module'] == 'commerce';
	}
}

/**
 * Check if a commerce plugin is on the page
 *
 * @return bool
 */
function user_isCommercePage() {
	return Tx_Commerce_Utility_TyposcriptConfig::isCommercePage();
}

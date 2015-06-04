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
 * This class handles object persistence using the Dao design pattern.
 * It extends the basic Dao.
 *
 * Class Tx_Commerce_Dao_FeuserDao
 *
 * @author 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Dao_FeuserDao extends Tx_Commerce_Dao_BasicDao {
	/**
	 * Initialization
	 *
	 * @return void
	 */
	protected function init() {
		$this->parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDaoParser');
		$this->mapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDaoMapper', $this->parser);
		$this->object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Dao_FeuserDaoObject');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserDao.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserDao.php']);
}

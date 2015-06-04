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
 * Address Dao mapping
 * This class used by the Dao for database storage.
 * It extends the basic Dao mapper.
 *
 * Class Tx_Commerce_Dao_AddressDaoMapper
 *
 * @author 2006-2008 Carsten Lausen <cl@e-netconsulting.de>
 */
class Tx_Commerce_Dao_AddressDaoMapper extends Tx_Commerce_Dao_BasicDaoMapper {
	/**
	 * dbtable for persistence
	 *
	 * @var string
	 */
	protected $dbTable = 'tt_address';

	/**
	 * Initialization
	 *
	 * @return void
	 */
	protected function init() {
		parent::init();
		$this->createPid = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['create_address_pid'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressDaoMapper.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/AddressDaoMapper.php']);
}

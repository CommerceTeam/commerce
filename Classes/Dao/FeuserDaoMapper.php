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
 * feuser Dao mapper
 * This class used by the Dao for database storage.
 * It extends the basic Dao mapper.
 */
class Tx_Commerce_Dao_FeuserDaoMapper extends Tx_Commerce_Dao_BasicDaoMapper {
	/**
	 * dbtable for persistence
	 *
	 * @var string
	 */
	protected $dbTable = 'fe_users';

	/**
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->createPid = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['create_feuser_pid'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserDaoMapper.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Dao/FeuserDaoMapper.php']);
}

?>
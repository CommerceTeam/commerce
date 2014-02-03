<?php
/**
 * address dao mapping
 * This class used by the dao for database storage.
 * It extends the basic dao mapper.
 */
class address_dao_mapper extends basic_dao_mapper {

	public function init() {
			// dbtable for persistence
		$this->dbTable = 'tt_address';
			// new record pid
		$this->createPid = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['create_address_pid'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_dao_mapper.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.address_dao_mapper.php']);
}

?>
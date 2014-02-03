<?php
/**
* feuser dao mapper
* This class used by the dao for database storage.
* It extends the basic dao mapper.
*/
class feuser_mapper extends basic_dao_mapper {
	public function init() {
			// dbtable for persistence
		$this->dbTable = 'fe_users';
			// new record pid
		$this->createPid = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['create_feuser_pid'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feuser_object.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feuser_object.php']);
}

?>
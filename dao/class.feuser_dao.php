<?php
/**
* feuser dao
* This class handles object persistence using the dao design pattern.
* It extends the basic dao.
*/
class feuser_dao extends basic_dao {
	public function init() {
		$this->parser = t3lib_div::makeInstance('feuser_parser');
		$this->mapper = t3lib_div::makeInstance('feuser_mapper', $this->parser);
		$this->obj = t3lib_div::makeInstance('feuser_object');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feuser_dao.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feuser_dao.php']);
}

?>
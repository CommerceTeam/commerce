<?php
/**
* feuser dao parser
* This class is used by the dao for object/model parsing.
* It extends the basic dao parser.
*/
class feuser_parser extends basic_dao_parser {
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feuser_object.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/dao/class.feuser_object.php']);
}

?>
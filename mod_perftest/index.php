<?php
/**
 * Module: Performance testing
 *
 * @author	Marketing Factory
 * @maintainer Erik Frister
 */

unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');

//Require the classes from the PEAR Benchmark Package
require_once('lib/Benchmark/Timer.php'); 
require_once('lib/Benchmark/Iterate.php'); 
require_once('lib/Benchmark/Profiler.php'); 

require_once('lib/class.tx_commerce_perfsuite.php'); 

$BE_USER->modAccess($MCONF,1);

/**
 * Module: Performance testing
 *
 * Measures the performance of several tasks
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
class SC_mod_perftest_index {

	/**
	 * Module config
	 * Internal static
	 * @var array
	 */
	protected $MCONF = array();
	
	/**
	 * Performance Suite
	 * @var object
	 */
	protected $suite;
	
	var $BACK_PATH = '../../../../typo3/'; ###MAKE THIS BE CALCULATED OR ANDERS ERMITTELT###

	/**
	 * Initialization of the class
	 *
	 * @return	void
	 */
	public function init() {
		$this->suite = t3lib_div::makeInstance('tx_commerce_perfsuite');
		$this->suite->setTestPath(t3lib_extmgm::extPath('commerce').'mod_perftest/lib/');
		
		//Add the test cases
		$this->suite->addTest('class.tx_commerce_browseTree_perfTest.php:tx_commerce_browseTree_perfTest');
	}

	/**
	 * Main function, creating the content for the access editing forms/listings
	 *
	 * @return	void
	 */
	public function main() {
		global $BE_USER, $LANG;
		
		//Only admins may see this page
		if(!$BE_USER->isAdmin()) die('You do not have the permissions to view this page.');
		
		//Run the suite
		$this->suite->harness();
		$this->suite->run();
		
		
		$this->content = $this->suite->renderOutput();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	public function printContent() {
		echo $this->content;
	}
}

//XClass Statement
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/mod_perftest/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/mod_perftest/index.php']);
}

//Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_perftest_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

if ($TYPO3_CONF_VARS['BE']['compressionLevel'])	{
	new gzip_encode($TYPO3_CONF_VARS['BE']['compressionLevel']);
}

?>

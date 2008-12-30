<?php
/**
 * Implements the Speed test for the browsetree
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
require_once(t3lib_extmgm::extPath('commerce').'mod_perftest/lib/class.tx_commerce_perfcategorytree.php');

class tx_commerce_browseTree_perfTest {
	
	/**
	 * Sets up for extending of the perfcategorytree and the classes it used
	 * @return {void}
	 */
	public function setUp() {
		require_once(t3lib_extmgm::extPath('commerce').'mod_perftest/lib/class.tx_commerce_perfleaf_category.php');
		require_once(t3lib_extmgm::extPath('commerce').'mod_perftest/lib/class.tx_commerce_leaf_perfcategorydata.php');
		require_once(t3lib_extmgm::extPath('commerce').'mod_perftest/lib/class.tx_commerce_leaf_perfcategoryview.php');
	}
	
	/**
	 * Test the 
	 * @return {object} profiler
	 */
	public function getBrowseableTree_testCase() {
		$tree = t3lib_div::makeInstance('tx_commerce_perfcategorytree');
		
		$tree->profiler->start();
		
		$tree->init();
		$tree->getBrowseableTree();
		
		$tree->profiler->stop();
		
		return $tree->profiler;
	}
	
	/*public function getBrowseableAjaxTree_testCase() {
		$tree = t3lib_div::makeInstance('tx_commerce_perfcategorytree');
		
		$tree->profiler->start();
		
		//Set the Params that would be send by an AJAX request
		$_GET['PM'] = 'txcommerceCategoryTree_0_0_1_21428'; //Specific to any category
		
		$PM = t3lib_div::_GP('PM');
		// IE takes anchor as parameter
		if(($PMpos = strpos($PM, '#')) !== false) { $PM = substr($PM, 0, $PMpos); }
		$PM = explode('_', $PM);
		
		//Now we should have a PM Array looking like:
		//0: treeName, 1: leafIndex, 2: Mount, 3: set/clear [4:,5:,.. further leafIndices], 5[+++]: Item UID
		
		if(is_array($PM) && count($PM) >= 4) {
			$id 	= $PM[count($PM)-1]; //ID is always the last Item
			$bank 	= $PM[2];
		}
		
		$tree->init();
		$tree->getBrowseableAjaxTree($PM);
		
		$tree->profiler->stop();
		
		return $tree->profiler;
	}*/
	
	/**
	 * Releases the GLOBALS and reinitiates the original state
	 * @return {void}
	 */
	public function tearDown() {

	}
}
?>

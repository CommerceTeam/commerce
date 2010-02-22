<?php
/**
 * Implements a performance suite for Commerce
 * 
 * @author Marketing Factory
 * @maintainer Erik Frister
 */
class tx_commerce_perfsuite {
		
		protected $testPath;
		protected $testCases;
		
		protected $out;	//Will hold the outputs of all cases - that is the profiler object reference
		
		public function __construct() {
			$this->testPath 	= '';
			$this->testCases 	= array();
			$this->out			= array();
		}
		
		/**
		 * Sets the path to the performance test classes
		 * Used to include the files 
		 * @return {void}
		 * @param $path {string}		Absolute Path to the include dir for the tests
		 */
		public function setTestPath($path) {
			$this->testPath = $path;
		}
		
		/**
		 * Adds a test case to the suite
		 * @return  {boolean}		Success
		 * @param $test {string}	Specially formatted string '{file_in_path:classname}'
		 */
		public function addTest($test) {
			$testArray = explode(':', $test);
			
			//if the test is string is malformed, abort.
			if(count($testArray) != 2) return false;
			
			//Add the file path as a key, the class name as value
			$this->testCases[$testArray[0]] = $testArray[1];
			
			return true;
		}
		
		/**
		 * Includes the added test cases
		 * @return {void}
		 */
		public function harness() {
			$includes = array_keys($this->testCases);
			
			for($i = 0, $l = count($includes); $i < $l; $i ++) {
				require_once($includes[$i]);
			}
		}
		
		/**
		 * Runs the Suite - starts all the tests in the suite and stores the output
		 * @return {void}
		 */
		public function run() {
			//Go over each testcase
			$case = null;
			$keys = array_keys($this->testCases);
			
			for($i = 0, $l = count($keys); $i < $l; $i ++) {
				$case = t3lib_div::makeInstance($this->testCases[$keys[$i]]);
				
				//Look for methods with the name _testCase in it
				$methods = $this->getTestCaseMethods($case);
	
				//Call setup if exists
				if(method_exists($case, 'setUp')) {
					$case->setUp();	
				}
	
				//Call the methods
				for($i = 0, $l = count($methods); $i < $l; $i ++) {
						$this->out[] = & $case->$methods[$i]();
				}
				
				//Cell tearDown if exists
				if(method_exists($case, 'tearDown')) {
					$case->tearDown();	
				}
			}
		}
		
		/**
		 * Returns an array with methods that have a _testCase in it
		 * @return {array}
		 * @param $testCase {object}	TestCase Object
		 */
		public function getTestCaseMethods($testCase) {
			if(!is_object($testCase)) return null;
			
			$allMethods 	= get_class_methods($testCase);
			$caseMethods 	= array();
			
			for($i = 0, $l = count($allMethods); $i < $l; $i ++) {
				if(false !== stristr($allMethods[$i], '_testCase')) {
					$caseMethods[] = $allMethods[$i];	
				}
			}
			
			return $caseMethods;
		}
		
		/**
		 * Returns a nicely rendered output
		 * @return {string}	Output HTML
		 */
		public function renderOutput() {
			for($i = 0, $l = count($this->out); $i < $l; $i ++) {
				$this->out[$i]->display('html');
			}
		}
}
?>

<?php
/**
 * Implements the i18n base for the tree
 
 * @author 		Marketing Factory <typo3@marketing-factory.de>
 * @maintainer 	Erik Frister <typo3@marketing-factory.de>
 **/
class langbase {
	
	// --internal--
	protected $lang;	//Holds a reference to the global $LANG object
	protected $isLoaded = false;
	protected $llFile 	= 'EXT:commerce/locallang_treelib.xml';
	
	/**
	 * Load the LocalLang features
	 * 
	 * @return {void}
	 */
	public function __construct() {
		$this->loadLL();
	}	
	
	/**
	 * Loads the LocalLang file
	 * Overwrite this by and extending class if you want to change the ll file implementation
	 * If you only want to use a different ll file, overwrite the variable instead!
	 * 
	 * @return {void}
	 */
	public function loadLL() {
		$GLOBALS['LANG']->includeLLFile($this->llFile);
	}
	
	/**
	 * Gets a Locallang-Field inside the LANG
	 * @return {string}
	 * @param $field {string}	LL Field
	 */
	public function getLL($field) {
		return $GLOBALS['LANG']->getLL($field);
	}
}
?>

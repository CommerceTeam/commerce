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
 * Implements the i18n base for the tree
 *
 * Class Tx_Commerce_Tree_Leaf_Base
 *
 * @author 2008-2009 Erik Frister <typo3@marketing-factory.de>
 */
class Tx_Commerce_Tree_Leaf_Base {
	/**
	 * Holds a reference to the global $LANG object
	 *
	 * @var language
	 * @deprecated Since 2.0.0 will be removed in 4.0.0
	 */
	protected $language;

	/**
	 * Flag if is loaded
	 *
	 * @var bool
	 */
	protected $isLoaded = FALSE;

	/**
	 * Path to language file
	 *
	 * @var string
	 */
	protected $llFile = 'EXT:commerce/Resources/Private/Language/locallang_treelib.xml';

	/**
	 * Load the LocalLang features
	 *
	 * @return self
	 */
	public function __construct() {
		$this->language = $this->getLanguageService();
		$this->loadLL();
	}

	/**
	 * Loads the LocalLang file
	 * Extending this class if you want to change the ll file implementation
	 * If you only want to use a different ll file, overwrite the variable instead!
	 *
	 * @return void
	 */
	public function loadLL() {
		$this->getLanguageService()->includeLLFile($this->llFile);
	}

	/**
	 * Gets a Locallang-Field inside the LANG
	 *
	 * @param string $field LL Field
	 * @return string
	 */
	public function getLL($field) {
		return $this->getLanguageService()->getLL($field);
	}


	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}

<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2008 - 2012 Ingo Schmitt <typo3@marketing-factory.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Implements the i18n base for the tree
 */
class langbase {
	/**
	 * Holds a reference to the global $LANG object
	 *
	 * @var language
	 */
	protected $language;

	/**
	 * @var boolean
	 */
	protected $isLoaded = FALSE;

	/**
	 * @var string
	 */
	protected $llFile = 'EXT:commerce/Resources/Private/Language/locallang_treelib.xml';

	/**
	 * Load the LocalLang features
	 *
	 * @return self
	 */
	public function __construct() {
		$this->language = $GLOBALS['LANG'];
		$this->loadLL();
	}

	/**
	 * Loads the LocalLang file
	 * Overwrite this by and extending class if you want to change the ll file implementation
	 * If you only want to use a different ll file, overwrite the variable instead!
	 *
	 * @return void
	 */
	public function loadLL() {
		$this->language->includeLLFile($this->llFile);
	}

	/**
	 * Gets a Locallang-Field inside the LANG
	 * @return string
	 * @param string $field LL Field
	 */
	public function getLL($field) {
		return $this->language->getLL($field);
	}
}

?>
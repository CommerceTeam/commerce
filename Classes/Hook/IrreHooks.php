<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class Tx_Commerce_Hook_IrreHooks
 */
class Tx_Commerce_Hook_IrreHooks implements \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface {
	/**
	 * @var \TYPO3\CMS\Backend\Form\Element\InlineElement
	 */
	protected $parentObject;

	/**
	 * @var array
	 */
	protected $extconf;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->extconf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf'];
	}

	/**
	 * Initializes this hook object.
	 *
	 * @param \TYPO3\CMS\Backend\Form\Element\InlineElement $parentObject:
	 * 	The calling \TYPO3\CMS\Backend\Form\Element\InlineElement object.
	 * @return void
	 */
	public function init(&$parentObject) {
		$this->parentObject = $parentObject;
	}

	/**
	 * Pre-processing to define which control items are enabled or disabled.
	 *
	 * @param string $parentUid: The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreignTable: The table (foreign_table) we create control-icons for
	 * @param array $childRecord: The current record of that foreign_table
	 * @param array $childConfig: TCA configuration of the current field of the child record
	 * @param boolean $isVirtual: Defines whether the current records is only virtually shown and not physically part of the parent record
	 * @param array &$enabledControls: (reference) Associative array with the enabled control items
	 * @return void
	 */
	public function renderForeignRecordHeaderControl_preProcess($parentUid, $foreignTable, array $childRecord, array $childConfig,
			$isVirtual, array &$enabledControls) {
		if ($this->extconf['simpleMode'] == 1 && $foreignTable == 'tx_commerce_articles' && $parentUid == $this->extconf['deliveryID']) {
			$enabledControls = array('new' => TRUE, 'hide' => TRUE, 'delete' => TRUE);
		} elseif ($this->extconf['simpleMode'] == 1 && $foreignTable == 'tx_commerce_articles') {
			$enabledControls = array('hide' => TRUE);
		} elseif ($foreignTable == 'tx_commerce_article_prices') {
			$enabledControls = array('new' => TRUE, 'sort' => TRUE, 'hide' => TRUE, 'delete' => TRUE);
		}
	}

	/**
	 * Post-processing to define which control items to show. Possibly own icons can be added here.
	 *
	 * @param string $parentUid: The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreignTable: The table (foreign_table) we create control-icons for
	 * @param array $childRecord: The current record of that foreign_table
	 * @param array $childConfig: TCA configuration of the current field of the child record
	 * @param boolean $isVirtual: Defines whether the current records is only virtually shown and not physically part of the parent record
	 * @param array &$controlItems: (reference) Associative array with the currently available control items
	 * @return void
	 */
	public function renderForeignRecordHeaderControl_postProcess($parentUid, $foreignTable, array $childRecord, array $childConfig,
			$isVirtual, array &$controlItems) {
		// registered empty to satisfy interface
	}
}

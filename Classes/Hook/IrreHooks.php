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
 * Class Tx_Commerce_Hook_IrreHooks
 *
 * @author 2008-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Hook_IrreHooks implements \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface {
	/**
	 * Parent object
	 *
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
	 * @param \TYPO3\CMS\Backend\Form\Element\InlineElement $parentObject The calling object.
	 *
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
	public function renderForeignRecordHeaderControl_postProcess($parentUid, $foreignTable, array $childRecord, array $childConfig, $isVirtual, array &$controlItems) {
		// registered empty to satisfy interface
	}
}

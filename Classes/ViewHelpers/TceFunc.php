<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Erik Frister <typo3@marketing-factory.de>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Holds the TCE Functions
 */
class Tx_Commerce_ViewHelpers_TceFunc {
	/**
	 * @var t3lib_TCEforms
	 */
	protected $tceForms;

	/**
	 * This will render a selector box element for selecting elements
	 * of (category) trees.
	 * Depending on the tree it display full trees or root elements only
	 *
	 * @param array $parameter An array with additional configuration options.
	 * @param t3lib_TCEforms $fObj TCEForms object reference
	 * @return string The HTML code for the TCEform field
	 */
	public function getSingleField_selectCategories($parameter, &$fObj) {
		$this->tceForms = &$parameter['pObj'];

		$table = $parameter['table'];
		$field = $parameter['field'];
		$row = $parameter['row'];
		$config = $parameter['fieldConf']['config'];

		$disabled = '';
		if ($this->tceForms->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

			// @todo it seems TCE has a bug and do not work correctly with '1'
		$config['maxitems'] = ($config['maxitems'] == 2) ? 1 : $config['maxitems'];

			// read the permissions we are restricting the tree to, depending on the table
		$perms = 'show';

		switch ($table) {
			case 'tx_commerce_categories':
				$perms = 'new';
				break;

			case 'tx_commerce_products':
				$perms = 'editcontent';
				break;

			case 'tt_content':
				// fall through
			case 'be_groups':
				// fall through
			case 'be_users':
				$perms = 'show';
				break;

			default:
		}

		/** @var Tx_Commerce_Tree_CategoryTree $browseTrees */
		$browseTrees = GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryTree');
			// disabled clickmenu
		$browseTrees->noClickmenu();
			// set the minimum permissions
		$browseTrees->setMinCategoryPerms($perms);

		if ($config['allowProducts']) {
			$browseTrees->setBare(FALSE);
		}

		if ($config['substituteRealValues']) {
			$browseTrees->substituteRealValues();
		}

		/**
		 * Disallows clicks on certain leafs
		 * Values is a comma-separated list of leaf names
		 * (e.g. Tx_Commerce_Tree_Leaf_Category)
		 */
		$browseTrees->disallowClick($config['disallowClick']);

		$browseTrees->init();

		/** @var Tx_Commerce_ViewHelpers_TreelibTceforms $renderBrowseTrees */
		$renderBrowseTrees = GeneralUtility::makeInstance('Tx_Commerce_ViewHelpers_TreelibTceforms');
		$renderBrowseTrees->init ($parameter, $fObj);
		$renderBrowseTrees->setIFrameTreeBrowserScript(
			$this->tceForms->backPath . PATH_TXCOMMERCE_REL . 'Classes/ViewHelpers/IframeTreeBrowser.php'
		);

		// Render the tree
		$renderBrowseTrees->renderBrowsableMountTrees($browseTrees);

		$thumbnails = '';
		if (!$disabled) {
			if ($renderBrowseTrees->isIFrameContentRendering()) {
					// just the trees are needed - we're inside of an iframe!
				return $renderBrowseTrees->getTreeContent();
			} elseif ($renderBrowseTrees->isIFrameRendering()) {
				// If we want to display a browseable tree, we need to run the tree in an iframe
				// element. In the logic of tceforms the iframe is displayed in the "thumbnails"
				// position. In consequence this means that the current function is both
				// responsible for displaying the iframe
				// and displaying the tree. It will be called twice then. Once from alt_doc.php
				// and from dam/mod_treebrowser/index.php

				// Within this if-condition the iframe is written
				// The source of the iframe is dam/mod_treebrowser/index.php which will be
				// called with the current _GET variables. In the configuration of the TCA
				// treeViewBrowseable is set to TRUE. The value 'iframeContent' for
				// treeViewBrowseable will be set in dam/mod_treebrowser/index.php as
				// internal configuration logic
				$thumbnails = $renderBrowseTrees->renderIFrame();
			} else {
				// tree frame <div>
				$thumbnails = $renderBrowseTrees->renderDivBox();
			}
		}

		// get selected processed items - depending on the table we want to insert
		// into (tx_commerce_products, tx_commerce_categories, be_users)
		// if row['uid'] is defined and is an integer we do display an existing record
		// otherwhise it's a new record, so get default values
		$itemArray = array();

		if ((int) $row['uid']) {
				// existing Record
			switch($table) {
				case 'tx_commerce_categories':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreePCategory($browseTrees, $row['uid']);
					break;

				case 'tx_commerce_products':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeProduct($browseTrees, $row['uid']);
					break;

				case 'be_users':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTree($browseTrees, $row['uid']);
					break;

				case 'be_groups':
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeGroups($browseTrees, $row['uid']);
					break;

				case 'tt_content':
					// Perform modification of the selected items array:
					$itemArray = GeneralUtility::trimExplode(',', $parameter['itemFormElValue'], 1);
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeCategory($browseTrees, $itemArray[0]);
					break;

				default:
					$itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeDefault($parameter['itemFormElValue']);
			}
		} else {
				// New record
			$defVals = GeneralUtility::_GP('defVals');
			switch ($table) {
				case 'tx_commerce_categories':
					/** @var Tx_Commerce_Domain_Model_Category $category */
					$category = GeneralUtility::makeInstance(
						'Tx_Commerce_Domain_Model_Category',
						$defVals['tx_commerce_categories']['parent_category']
					);
					$category->loadData();
					$itemArray = array($category->getUid() . '|' . $category->getTitle());
					break;

				case 'tx_commerce_products':
					/** @var Tx_Commerce_Domain_Model_Category $category */
					$category = GeneralUtility::makeInstance(
						'Tx_Commerce_Domain_Model_Category',
						$defVals['tx_commerce_products']['categories']
					);
					$category->loadData();
					$itemArray = array($category->getUid() . '|' . $category->getTitle());
					break;

				default:
			}
		}

		// process selected values
		// Creating the label for the "No Matching Value" entry.
		$noMatchingValueLabel = isset($parameter['fieldTSConfig']['noMatchingValue_label']) ?
			$this->tceForms->sL($parameter['fieldTSConfig']['noMatchingValue_label']) :
			'[ ' . $this->tceForms->getLL('l_noMatchingValue') . ' ]';
		$noMatchingValueLabel = @sprintf($noMatchingValueLabel, $parameter['itemFormElValue']);

		// Possibly remove some items:
		$removeItems = GeneralUtility::trimExplode(',', $parameter['fieldTSConfig']['removeItems'], TRUE);
		foreach ($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			if (in_array($tvP[0], $removeItems) && !$parameter['fieldTSConfig']['disableNoMatchingValueElement']) {
				$tvP[1] = rawurlencode($noMatchingValueLabel);
			} elseif (isset($parameter['fieldTSConfig']['altLabels.'][$tvP[0]])) {
				$tvP[1] = rawurlencode($this->tceForms->sL($parameter['fieldTSConfig']['altLabels.'][$tvP[0]]));
			}
			$itemArray[$tk] = implode('|', $tvP);
		}

			// Rendering and output
		$minitems = max($config['minitems'], 0);
		$maxitems = max($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}

		$this->tceForms->requiredElements[$parameter['itemFormElName']] =
			array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field);

		$item = '<input type="hidden" name="' . $parameter['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) .
			'"' . $disabled . ' />';

		$params = array(
			'size' => $config['size'],
			'autoSizeMax' => \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
			'style' => ' style="width:200px;"',
			'dontShowMoveIcons' => ($maxitems <= 1),
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->tceForms->getLL('l_selected') . ':<br />',
				'items' => ($disabled ? '': $this->tceForms->getLL('l_items') . ':<br />')
			),
			'noBrowser' => TRUE,
			'readOnly' => $disabled,
			'thumbnails' => $thumbnails
		);

		$item .= $this->tceForms->dbFileIcons(
			$parameter['itemFormElName'], $config['internal_type'], $config['allowed'], $itemArray, '', $params, $parameter['onFocus']
		);

			// Wizards:
		if (!$disabled) {
			$specConf = $this->tceForms->getSpecConfFromString($parameter['extra'], $parameter['fieldConf']['defaultExtras']);
			$altItem = '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
				htmlspecialchars($parameter['itemFormElValue']) . '" />';
			$item = $this->tceForms->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $parameter,
				$parameter['itemFormElName'], $specConf);
		}

		return $item;
	}
}

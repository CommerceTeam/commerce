<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2008 - 2012 Ingo Schmitt <typo3@marketing-factory.de>
 * (c) 2013 Sebastian Fischer <typo3@marketing-factory.de>
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
 * Implements a slave leaf of the browsetree
 */
class leafSlave extends leaf {
	/**
	 * If the leaf has a parent leaf, then it is stored in this variable
	 *
	 * @var leaf
	 */
	protected $parentLeaf;

	/**
	 * @var tx_commerce_leaf_categorydata
	 */
	public $data;

	/**
	 * @var leafView
	 */
	public $view;

	/**
	 * Sets the parent leaf of this leaf
	 *
	 * @param leaf $parentLeaf that is the parent of this leaf
	 * @return void
	 */
	public function setParentLeaf(leaf &$parentLeaf) {
		if (is_null($parentLeaf)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setParentLeaf (leafSlave) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->parentLeaf = $parentLeaf;
	}

	/**
	 * Initializes the leaf
	 * Passes the Parameters to its child leafs
	 *
	 * @param integer $index Index of this leaf
	 * @param array $parentIndices Array with parent indices
	 * @return void
	 */
	public function init($index, $parentIndices = array()) {
		if (!is_numeric($index) || !is_array($parentIndices)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('init (leafSlave) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

			// Initialize the LeafData
		$this->data->init();
		$this->data->initRecords($index, $parentIndices, $this->parentLeaf->data);

		parent::init($index, $parentIndices);
	}

	/**
	 * Prints the single leaf item
	 * Since this is a slave, this can only EVER be called by AJAX
	 *
	 * @param integer $startUid UID in which we start
	 * @param integer $bank Bank UID
	 * @param integer $pid UID of the parent item
	 * @return string HTML Code
	 */
	public function printChildleafsByLoop($startUid, $bank, $pid) {
			// Check for valid parameters
		if (!is_numeric($startUid) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('printChildleafsByLoop (leafSlave) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Set the bank
		$this->view->setBank($bank);
		$this->data->setBank($bank);

			// Set the TreeName
		$this->view->setTreeName($this->treeName);

			// init vars
		$out = '';

			// get the Parent Item and set it as the starting child to print
		$child = $this->data->getChildByUid($startUid);
		$child['item_parent'] = $pid;

			// Abort if the starting Category is not found
		if (NULL == $child) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('printChildleafsByLoop (leafSlave) cannot find the starting category by its uid.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		/********************
		 * Printing the Item
		 *******************/
			// Give class 'expanded' if it is
		$exp = $this->data->isExpanded($child['uid']);
		$cssExpanded = ($exp) ? 'expanded' : '';

			// Add class 'last' if it is
		$isLast = $this->isLast($child, $pid);
		$cssLast = ($isLast) ? ' last' : '';

		$cssClass = $cssExpanded . ' ' . $cssLast;

			// start the element
		$out .= '<li class="' . $cssClass . '">';

			// a slave can never be a bank
		$isBank = FALSE;
		$hasChildren = $this->hasChildren($child);

			// pm icon
		$out .= $this->view->PMicon($child, $isLast, $exp, $isBank, $hasChildren);

			// icon
		$out .= $this->view->getIcon($child);

		if (
			(strpos(t3lib_div::getIndpEnv('REQUEST_URI'), 'class.tx_commerce_category_navframe.php') === FALSE)
			&& (strpos(t3lib_div::getIndpEnv('HTTP_REFERER'), 'class.tx_commerce_category_navframe.php') === FALSE)
		) {
			$this->view->substituteRealValues();
		}

			// title
		$out .= $this->view->wrapTitle($child['title'], $child);

		/******************
		 * Done printing
		 *****************/

			// Print the children from the child leafs if the current leaf is expanded
		if ($exp) {
			$out .= '<ul>';
			for ($i = 0; $i < $this->leafcount; $i ++) {
				/** @var leafSlave $leaf */
				$leaf = & $this->leafs[$i];
				$out .= $leaf->printChildleafsByParent($child['uid'], $bank, $this->treeName);
			}
			$out .= '</ul>';
		}

			// close the list item
		$out .= '</li>';

		return $out;
	}

	/**
	 * Prints all leafs by the parent item
	 *
	 * @param integer $pid UID of the parent item
	 * @param integer $bank Bank UID
	 * @return string HTML Code
	 */
	public function printChildleafsByParent($pid, $bank) {
			// Check for valid parameters
		if (!is_numeric($pid) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('printChildleafsByParent (leafSlave) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

		$out = '';

			// get the children
		$children = $this->data->getChildrenByPid($pid);

		$l = count($children);

			// Process the child and children
		for ($i = 0; $i < $l; $i ++) {
			$child = $children[$i];

			$out .= $this->printChildleafsByLoop($child['uid'], $bank, $pid);
		}

			// DLOG
		if (TYPO3_DLOG) {
			t3lib_div::devLog('printChildleafsByParent (leafSlave) did ' . $l . ' loops!', COMMERCE_EXTKEY, 1);
		}

		return $out;
	}
}

?>
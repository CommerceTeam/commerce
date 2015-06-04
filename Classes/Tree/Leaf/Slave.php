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
 * Implements a slave leaf of the Tx_Commerce_Tree_Browsetree
 *
 * Tx_Commerce_Tree_Leaf_Slave
 *
 * @author 2008-2013 Erik Frister <typo3@marketing-factory.de>
 */
class Tx_Commerce_Tree_Leaf_Slave extends Tx_Commerce_Tree_Leaf_Leaf {
	/**
	 * If the leaf has a parent leaf, then it is stored in this variable
	 *
	 * @var Tx_Commerce_Tree_Leaf_Leaf
	 */
	protected $parentLeaf;

	/**
	 * @var Tx_Commerce_Tree_Leaf_SlaveData
	 */
	public $data;

	/**
	 * @var Tx_Commerce_Tree_Leaf_View
	 */
	public $view;

	/**
	 * @var integer
	 */
	protected $pid;

	/**
	 * Sets the parent leaf of this leaf
	 *
	 * @param Tx_Commerce_Tree_Leaf_Leaf $parentLeaf that is the parent of this leaf
	 * @return void
	 */
	public function setParentLeaf(Tx_Commerce_Tree_Leaf_Leaf &$parentLeaf) {
		if (is_null($parentLeaf)) {
			if (TYPO3_DLOG) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'setParentLeaf (Tx_Commerce_Tree_Leaf_Slave) gets passed invalid parameters.',
					COMMERCE_EXTKEY, 3
				);
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
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'init (Tx_Commerce_Tree_Leaf_Slave) gets passed invalid parameters.',
					COMMERCE_EXTKEY, 3
				);
			}
			return;
		}

			// Initialize the Tx_Commerce_Tree_Leaf_Data
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
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'printChildleafsByLoop (Tx_Commerce_Tree_Leaf_Slave) gets passed invalid parameters.',
					COMMERCE_EXTKEY, 3
				);
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
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'printChildleafsByLoop (Tx_Commerce_Tree_Leaf_Slave) cannot find the starting category by its uid.',
					COMMERCE_EXTKEY, 3
				);
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
		$out .= '<li class="' . $cssClass . '">
					<div>';

			// a slave can never be a bank
		$isBank = FALSE;
		$hasChildren = $this->hasChildren($child);

			// pm icon
		$out .= $this->view->PMicon($child, $isLast, $exp, $isBank, $hasChildren);

			// icon
		$out .= $this->view->getIcon($child);

		if (
			(strpos(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'), '/navigation.php') === FALSE)
			&& (strpos(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER'), '/navigation.php') === FALSE)
		) {
			$this->view->substituteRealValues();
		}

			// title
		$out .= $this->view->wrapTitle($child['title'], $child) . '</div>';

		/******************
		 * Done printing
		 *****************/

			// Print the children from the child leafs if the current leaf is expanded
		if ($exp) {
			$out .= '<ul>';
			for ($i = 0; $i < $this->leafcount; $i++) {
				/** @var Tx_Commerce_Tree_Leaf_Slave $leaf */
				$leaf = & $this->leafs[$i];
				$out .= $leaf->printChildleafsByParent($child['uid'], $bank);
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
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
					'printChildleafsByParent (Tx_Commerce_Tree_Leaf_Slave) gets passed invalid parameters.',
					COMMERCE_EXTKEY, 3
				);
			}
			return '';
		}

		$out = '';

			// get the children
		$children = $this->data->getChildrenByPid($pid);

		$l = count($children);

			// Process the child and children
		for ($i = 0; $i < $l; $i++) {
			$child = $children[$i];

			$this->pid = $pid;
			$out .= $this->printChildleafsByLoop($child['uid'], $bank, $pid);
		}

			// DLOG
		if (TYPO3_DLOG) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
				'printChildleafsByParent (Tx_Commerce_Tree_Leaf_Slave) did ' . $l . ' loops!',
				COMMERCE_EXTKEY, 1
			);
		}

		return $out;
	}
}

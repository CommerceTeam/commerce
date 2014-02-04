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
 * Implements a master leaf for the Tx_Commerce_Tree_Browsetree
 */
class Tx_Commerce_Tree_Leaf_Master extends Tx_Commerce_Tree_Leaf_Leaf {
	/**
	 * @var string
	 */
	protected $mountClass = 'Tx_Commerce_Tree_Leaf_Mounts';

	/**
	 * Flag if the Leafitems shall be read by specific mountpoints
	 *
	 * @var boolean
	 */
	protected $byMounts;

	/**
	 * Mountpoint-Object with the mountpoints of the leaf (if it is a treeleaf)
	 *
	 * @var Tx_Commerce_Tree_Leaf_Mounts
	 */
	protected $mounts;

	/**
	 * @var Tx_Commerce_Tree_Leaf_MasterData
	 */
	public $data;

	/**
	 * Initializes the leaf
	 * Passes the Parameters to its child leafs
	 *
	 * @param $index {int}			Index of this leaf
	 * @param $parentIndices {array}Array with parent indices
	 * @return void
	 */
	public function init($index, $parentIndices = array()) {
		if (!is_numeric($index) || !is_array($parentIndices)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('init (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

			// Load Mountpoints and init the Position if we want to read the leafs by Mountpoints
		if ($this->byMounts) {
			$this->loadMountpoints();
		}

			// Initialize the LeafData
		$this->data->init();
		$this->data->initRecords($index, $parentIndices);

		parent::init($index, $parentIndices);
	}

	/**
	 * Sets the View and the Data of the Leaf
	 *
	 * @return void
	 * @param Tx_Commerce_Tree_Leaf_View $view LeafView of the Leaf
	 * @param Tx_Commerce_Tree_Leaf_Data $data LeafData of the Leaf
	 */
	public function initBasic(&$view, &$data) {
		if (is_null($view) || is_null($data)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('initBasic (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

		parent::initBasic($view, $data);

		$this->byMounts = FALSE;
		$this->mounts = NULL;
	}

	/**
	 * Sets if the leaf should be read by the Mountpoints
	 *
	 * @return boolean
	 * @param boolean $flag Flag
	 */
	public function byMounts($flag = TRUE) {
		$this->byMounts = (bool) $flag;
	}

	/**
	 * Pass the Item UID Array with the Mountpoints to the LeafData
	 *
	 * @return void
	 * @param array $mountIds Array with item UIDs that are mountpoints
	 */
	public function setMounts($mountIds) {
		if (!is_array($mountIds)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setMounts (Tx_Commerce_Tree_Leaf_Master) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->data->setMounts($mountIds);
	}

	/**
	 * Loads the leafs Mountpoints and sets their UIDs to the LeafData
	 *
	 * @return void
	 */
	protected function loadMountpoints() {
		$this->mounts = t3lib_div::makeInstance($this->mountClass);
		$this->mounts->init($GLOBALS['BE_USER']->user['uid']);

		$this->setMounts($this->mounts->getMountData());
	}

	/**
	 * Pass the UID of the Item to recursively build a tree from to the LeafData
	 *
	 * @return void
	 * @param integer $uid UID of the Item
	 */
	public function setUid($uid) {
		if (!is_numeric($uid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setUid (Tx_Commerce_Tree_Leaf_Master) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->data->setUid($uid);
	}

	/**
	 * Sets the recursive depth of the tree
	 *
	 * @return void
	 * @param integer $depth Recursive Depth
	 */
	public function setDepth($depth) {
		if (!is_numeric($depth)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('setDepth (Tx_Commerce_Tree_Leaf_Master) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->data->setDepth($depth);
	}

	/**
	 * Prints the Leaf by its mountpoint
	 *
	 * @return string HTML Code
	 */
	public function printLeafByMounts() {
		$out = '';

			// If we don't have a mount object, return the error message
		if ($this->mounts == NULL || !$this->mounts->hasMounts() || !$this->data->hasRecords()) {
			return $this->getLL('leaf.noMount');
		}

		while (($mount = $this->mounts->walk()) !== FALSE) {
			$out .= $this->printChildleafsByLoop($mount, $mount);
		}

		return $out;
	}

	/**
	 * Returns whether or not a node is the last in the current subtree
	 *
	 * @return boolean
	 * @param array $row Row Item
	 * @param integer $pid Parent UID of the current Row Item
	 */
	public function isLast($row, $pid = 0) {
		if (!is_array($row) || !is_numeric($pid)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('isLast (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		$isLast = parent::isLast($row, $pid);

			// In case the row is last, check if it is really the last by seeing if any of its slave leafs have records
		if ($isLast) {
			$isLast = !$this->leafsHaveRecords($pid);
		}

		return $isLast;
	}

	/**
	 * Returns whether or not a node has Children
	 *
	 * @param array $row Row Item
	 * @return boolean
	 */
	public function hasChildren($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('hasChildren (Tx_Commerce_Tree_Leaf_Master) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		$hasChildren = ($this->data->getChildrenByPid($row['uid']));

			// if current item doesn't have subchildren, look in slaveLeafs
		if (!$hasChildren) {
			$hasChildren = parent::hasChildren($row);
		}

		return $hasChildren;
	}

	/**
	 * Does the same thing printChildleafsByUid and printChildleafsByPid do in one function without recursion
	 *
	 * @return string HTML Code
	 * @param integer $startUid UID in which we start
	 * @param integer $bank Bank UID
	 * @param boolean|integer $pid UID of the parent Item - only passed if this function is called by ajax; thus it will only influence functionality if it is numeric
	 */
	public function printChildleafsByLoop($startUid, $bank, $pid = FALSE) {
			// Check for valid parameters
		if (!is_numeric($startUid) || !is_numeric($bank)) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('printChildleafsByLoop (Tx_Commerce_Tree_Leaf_Master) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
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
		$lastLevel = 0;

			// Max. number of loops we make
		$crazyStart = $crazyRecursion = 10000;
			// temporary child stack
		$tempChildren = array();
			// temporary level stack - already filled with a 0 because the starting child is on level 0
		$tempLevels = array(0);
			// holds which uid openend which level
		$levelOpener = array();

			// get the current item and set it as the starting child to print
		$child = $this->data->getChildByUid($startUid);

			// Abort if the starting Category is not found
		if ($child == NULL) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('printChildleafsByLoop (Tx_Commerce_Tree_Leaf_Master) cannot find the starting item by its uid.', COMMERCE_EXTKEY, 3);
			}
			return '';
		}

			// Process the child and children
		while (!is_null($child) && is_array($child) && $crazyRecursion > 0) {
				// get the current level
			$level = @array_pop($tempLevels);

				// close the parent list if we are on a higher level than the list
			if ($level < $lastLevel) {
				for ($i = $level; $i < $lastLevel; $i ++) {
						// get opener uid
					$uid = array_pop($levelOpener);

						// print slave elements from the opener
					$out .= $this->getSlaveElements($uid, $bank);
						// close opener
					$out .= '</ul></li>';
				}
			}

			$lastLevel = $level;

			/********************
			 * Printing the Item
			 *******************/
				// Give class 'expanded' if it is
			$exp = $this->data->isExpanded($child['uid']);
			$cssExpanded = ($exp) ? 'expanded' : '';

			if ($pid !== FALSE) {
					// called by AJAX - to get it's true parent item, we have to pass the pid because otherwise its ambiguous
				$child['item_parent'] = $pid;
					// all following items are not to be passed the pid because they are not ambiguous
				$pid = FALSE;
			}

				// Add class 'last' if it is
			$isLast = $this->isLast($child, $child['item_parent']);
			$cssLast = ($isLast) ? ' last' : '';

			$cssClass = $cssExpanded . ' ' . $cssLast;

				// start the element
			$out .= '<li class="' . $cssClass . '">';
$out .= '<div>';

			$isBank = ($child['uid'] == $bank);

			$hasChildren 	= $this->hasChildren($child);
			$out .= $this->view->PMicon($child, $isLast, $exp, $isBank, $hasChildren);

			$out .= (0 == $child['uid']) ? $this->view->getRootIcon($child) : $this->view->getIcon($child);
			$out .= $this->view->wrapTitle($child['title'], $child);
$out .= '</div>';

			/******************
			 * Done printing
			 *****************/

				// read the children
			$childElements = ($exp) ? $this->data->getChildrenByPid($child['uid']) : array();
			$m  = count($childElements);

				// if there are children
			if ($m > 0) {
					// add that this record uid opened the last level
				$levelOpener[] = $child['uid'];

					// set $child to first child element and store the other in a temp Array, with the second child being on the last position (like a stack)
				$child = $childElements[0];

				$out .= '<ul>';
				$level++;

					// add all children except the first to the stack
				for ($j = $m - 1; $j > 0; $j --) {
					$tempChildren[] = $childElements[$j];
						// Add the levels of the current items to the stack
					$tempLevels[] = $level;
				}

					// add the level of the next child as well
				$tempLevels[] = $level;
			} else {
					// Print the children from the slave leafs if the current leaf is expanded
				if ($exp) {
					$out .= '<ul>';
					$out .= $this->getSlaveElements($child['uid'], $bank);
					$out .= '</ul>';
				}

					// pop the last element from the temp array and set it as a child; if temp array is empty, break;
				$child = @array_pop($tempChildren);

					// close the list item
				$out .= '</li>';
			}

			$crazyRecursion --;
		}

			// DLOG
		if (TYPO3_DLOG) {
			t3lib_div::devLog('printChildLeafsByLoop (Tx_Commerce_Tree_Leaf_Master) did ' . ($crazyStart - $crazyRecursion) . ' loops!', COMMERCE_EXTKEY, 1);
		}

			// Close the rest of the lists
		for ($i = 0; $i < $lastLevel; $i ++) {

				// get opener uid
			$uid = array_pop($levelOpener);

				// print slave elements for the opener
			$out .= $this->getSlaveElements($uid, $bank);
				// close the opener
			$out .= '</ul></li>';
		}

			// Abort if the max. number of loops has been reached
		if ($crazyRecursion <= 0) {
			if (TYPO3_DLOG) {
				t3lib_div::devLog('printChildleafsByLoop (Tx_Commerce_Tree_Leaf_Master) was put to hold because there was a danger of endless recursion.', COMMERCE_EXTKEY, 3);
			}
			return $this->getLL('leaf.maxRecursion');
		}

		return $out;
	}

	/**
	 * Gets the elements of the slave leafs
	 *
	 * @param integer $pid
	 * @param integer $bank
	 * @return string HTML Code generated by the slaveleafs
	 */
	protected function getSlaveElements($pid, $bank) {
		$out = '';

		$leafcount = count($this->leafs);
		for ($i = 0; $i < $leafcount; $i ++) {
			/** @var Tx_Commerce_Tree_Leaf_Slave $leaf */
			$leaf = & $this->leafs[$i];
			$out .= $leaf->printChildleafsByParent($pid, $bank);
		}

		return $out;
	}
}

class_alias('Tx_Commerce_Tree_Leaf_Master', 'leafMaster');

?>
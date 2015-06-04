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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Implements an abstract leaf of the Tx_Commerce_Tree_Browsetree
 *
 * Class Tx_Commerce_Tree_Leaf_Leaf
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
abstract class Tx_Commerce_Tree_Leaf_Leaf extends Tx_Commerce_Tree_Leaf_Base {
	/**
	 * LeafView Object of the Leaf
	 *
	 * @var Tx_Commerce_Tree_Leaf_View
	 */
	public $view;

	/**
	 * LeafData Object of the Leaf
	 *
	 * @var Tx_Commerce_Tree_Leaf_Data
	 */
	public $data;

	/**
	 * Leafs can contain leafs
	 *
	 * @var array
	 */
	protected $leafs = array();

	/**
	 * Amount of childleafs (ONLY direct children are counted)
	 *
	 * @var integer
	 */
	protected $leafcount;

	/**
	 * @var string
	 */
	protected $BACK_PATH = '../../../../typo3/';

	/**
	 * @var string
	 */
	protected $treeName;

	/**
	 * @var boolean
	 */
	protected $resetDone;

	/**
	 * @var string
	 */
	protected $parentClass = '';

	/**
	 * @var string
	 */
	protected $selfClass = '';

	/**
	 * Sets the View and the Data of the Leaf
	 *
	 * @param Tx_Commerce_Tree_Leaf_View $view LeafView of the Leaf
	 * @param Tx_Commerce_Tree_Leaf_Data $data LeafData of the Leaf
	 * @return void
	 */
	public function initBasic(&$view, &$data) {
		if (is_null($view) || is_null($data)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('initBasic (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

			// Storing the View and the Data and initializing the standard values
		$this->view = $view;
		$this->data = $data;

		$this->leafs = array();
		$this->leafcount = 0;

			// do NOT set treename or it will break the functionality
		$this->resetDone = FALSE;
			// store the name of this class
		$this->selfClass = get_class($this);
	}

	/**
	 * Passes to the leafview if it should enable the clickmenu
	 *
	 * @param boolean $flag Flag
	 * @return void
	 */
	public function noClickmenu($flag = TRUE) {
		$this->view->noClickmenu($flag);
	}

	/**
	 * Adds a child leaf to the leaf
	 *
	 * @param Tx_Commerce_Tree_Leaf_Slave $leaf Slave Leaf-Object
	 * @return boolean
	 */
	public function addLeaf(Tx_Commerce_Tree_Leaf_Slave &$leaf) {
		if (NULL == $leaf) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('addLeaf (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		// pass treename to the leaf
		$leaf->setTreeName($this->treeName);

		$this->leafs[$this->leafcount++] = &$leaf;
		return TRUE;
	}

	/**
	 * Stores the name of the tree
	 *
	 * @param string $treeName Name of the tree
	 * @return void
	 */
	public function setTreeName($treeName) {
		if (!is_string($treeName)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('setTreeName (leaf) gets passed invalid parameters. Are set to default!', COMMERCE_EXTKEY, 3);
			}
			$treeName = 'unassigned';
		}

		$this->treeName = $treeName;
	}

	/**
	 * Returns the childleaf at a given index
	 *
	 * @param integer $index Index of the childleaf
	 * @return Tx_Commerce_Tree_Leaf_Slave Childleaf
	 */
	public function getChildLeaf($index) {
		if (!is_numeric($index)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('getChildLeaf (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return NULL;
		}

		if ($index >= $this->leafcount) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('getChildLeaf (leaf) has an index out of bounds.', COMMERCE_EXTKEY, 3);
			}
			return NULL;
		}

		return $this->leafs[$index];
	}

	/**
	 * Pass the Item UID Array with the Userpositions to the LeafData
	 *
	 * @return void
	 * @param array $positionIds Array with item uids that are positions
	 */
	public function setPositions(&$positionIds) {
		if (!is_array($positionIds)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('setPositions (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}
		$this->data->setPositions($positionIds);
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
				GeneralUtility::devLog('init (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

			// Store the index
		$this->view->setLeafIndex($index);
		$this->view->setParentIndices($parentIndices);

			// Add our own index to the parentIndices Array
		$parentIndices[] = $index;

		// Call 'init' for all child leafs - notice how the childleafs
		// are NOT read by mounts
		for ($i = 0; $i < $this->leafcount; $i++) {
				// For every childleaf, set its parent leaf to the current leaf
			/** @var Tx_Commerce_Tree_Leaf_Slave $leaf */
			$leaf = & $this->leafs[$i];
			$leaf->setParentLeaf($this);
			$leaf->init($i, $parentIndices);
		}
	}

	/**
	 * Sets the PositionIds for this leafs own LeafData and
	 * its ChildLeafs ("recursively")
	 *
	 * @param array $positions item uids that are positions
	 * @return void
	 */
	public function setDataPositions(&$positions) {
		if (!is_array($positions)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('setDataPositions (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

		$this->data->setPositions($positions);

		// "Recursive" Call
		for ($i = 0; $i < $this->leafcount; $i++) {
			/** @var Tx_Commerce_Tree_Leaf_Leaf $leaf */
			$leaf = & $this->leafs[$i];
			$leaf->setDataPositions($positions);
		}
	}

	/**
	 * Sorts the Leafdata in a way to represent the linear tree structure
	 * Sorts its leafs as well
	 *
	 * @param integer $rootUid uid of the Item that will act as the root of the tree
	 * @return void
	 */
	public function sort($rootUid) {
		if (!is_numeric($rootUid)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('sort (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return;
		}

		$this->data->sort($rootUid);

			// Sort Leafs
		for ($i = 0; $i < $this->leafcount; $i++) {
			/** @var Tx_Commerce_Tree_Leaf_Leaf $leaf */
			$leaf = & $this->leafs[$i];
			$leaf->sort($rootUid);
		}
	}

	/**
	 * Returns the sorted array
	 * Merges with the sorted arrays of the leafs
	 *
	 * @return array
	 */
	public function getSortedArray() {
		$sortedData = $this->data->getSortedArray();

		for ($i = 0; $i < $this->leafcount; $i++) {
			/** @var Tx_Commerce_Tree_Leaf_Leaf $leaf */
			$leaf = & $this->leafs[$i];
			$sortedData = array_merge($sortedData, $leaf->getSortedArray());
		}

		return $sortedData;
	}

	/**
	 * Returns if any leaf (beneath this one) has subrecords for a specific row
	 *
	 * @param integer $pid Row Item which would be parent of the leaf's records
	 * @return boolean
	 */
	public function leafsHaveRecords($pid) {
		if (!is_numeric($pid)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('leafsHaveRecords (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		// if we have no leafs, we have no records - if we dont have an entry
		// 'uid', what should we look for? - the row has to be expanded
		if (0 >= $this->leafcount || !$this->data->isExpanded($pid)) {
			return FALSE;
		}

		for ($i = 0; $i < $this->leafcount; $i++) {
				// if the childleaf has children for the parent
			if (0 < count($this->leafs[$i]->data->getChildrenByPid($pid))) {
				return TRUE;
			}
		}
		return FALSE;
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
				GeneralUtility::devLog('isLast (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		// If the row has an entry 'lastNode', its position is supplied
		// from the DB - check if the item is last under the current pid
		$isLast = (isset($row['lastNode']) && GeneralUtility::inList($row['lastNode'], $pid)) ? TRUE : FALSE;

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
				GeneralUtility::devLog('hasChildren (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		$hasChildren = FALSE;

			// check if any leaf has a subitem for the current row
		if ($this->leafcount > 0) {
			for ($i = 0; $i < $this->leafcount; $i++) {
				/** @var Tx_Commerce_Tree_Leaf_Leaf $leaf */
				$leaf = & $this->leafs[$i];
				$hasChildren = $leaf->hasSubitems($row);
				if (TRUE == $hasChildren) {
					break;
				}
			}
		}
		return $hasChildren;
	}

	/**
	 * Returns whether we have at least 1 subitem for a specific parent row
	 *
	 * @return boolean
	 * @param array $row Parent Row Information
	 */
	public function hasSubitems($row) {
		if (!is_array($row)) {
			if (TYPO3_DLOG) {
				GeneralUtility::devLog('hasSubitems (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
			}
			return FALSE;
		}

		$children = $this->data->getChildrenByPid($row['uid']);

		return (0 < count($children));
	}
}

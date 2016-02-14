<?php
namespace CommerceTeam\Commerce\Tree\Leaf;

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
 * Implements an abstract leaf of the \CommerceTeam\Commerce\Tree\Browsetree.
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\Leaf
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
abstract class Leaf extends Base
{
    /**
     * LeafView Object of the Leaf.
     *
     * @var \CommerceTeam\Commerce\Tree\Leaf\View
     */
    public $view;

    /**
     * LeafData Object of the Leaf.
     *
     * @var \CommerceTeam\Commerce\Tree\Leaf\Data
     */
    public $data;

    /**
     * Leafs can contain leafs.
     *
     * @var array
     */
    protected $leafs = array();

    /**
     * Amount of childleafs (ONLY direct children are counted).
     *
     * @var int
     */
    protected $leafcount;

    /**
     * Back path.
     *
     * @var string
     */
    protected $backPath = '../../../../../../typo3/';

    /**
     * Tree name.
     *
     * @var string
     */
    protected $treeName;

    /**
     * Reset done.
     *
     * @var bool
     */
    protected $resetDone;

    /**
     * Parent class.
     *
     * @var string
     */
    protected $parentClass = '';

    /**
     * Self class.
     *
     * @var string
     */
    protected $selfClass = '';

    /**
     * Sets the View and the Data of the Leaf.
     *
     * @param \CommerceTeam\Commerce\Tree\Leaf\View $view LeafView of the Leaf
     * @param \CommerceTeam\Commerce\Tree\Leaf\Data $data LeafData of the Leaf
     *
     * @return void
     */
    public function initBasic(
        \CommerceTeam\Commerce\Tree\Leaf\View &$view,
        \CommerceTeam\Commerce\Tree\Leaf\Data &$data
    ) {
        if (is_null($view) || is_null($data)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('initBasic (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return;
        }

        // Storing the View and the Data and initializing the standard values
        $this->view = $view;
        $this->data = $data;

        $this->leafs = array();
        $this->leafcount = 0;

        // do NOT set treename or it will break the functionality
        $this->resetDone = false;
        // store the name of this class
        $this->selfClass = get_class($this);
    }

    /**
     * Passes to the leafview if it should enable the clickmenu.
     *
     * @param bool $flag Flag
     *
     * @return void
     */
    public function noClickmenu($flag = true)
    {
        $this->view->noClickmenu($flag);
    }

    /**
     * Adds a child leaf to the leaf.
     *
     * @param \CommerceTeam\Commerce\Tree\Leaf\Slave $leaf Slave Leaf-Object
     *
     * @return bool
     */
    public function addLeaf(\CommerceTeam\Commerce\Tree\Leaf\Slave &$leaf)
    {
        if (null == $leaf) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('addLeaf (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        // pass treename to the leaf
        $leaf->setTreeName($this->treeName);

        $this->leafs[$this->leafcount++] = &$leaf;

        return true;
    }

    /**
     * Stores the name of the tree.
     *
     * @param string $treeName Name of the tree
     *
     * @return void
     */
    public function setTreeName($treeName)
    {
        if (!is_string($treeName)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'setTreeName (leaf) gets passed invalid parameters. Are set to default!',
                    'commerce',
                    3
                );
            }
            $treeName = 'unassigned';
        }

        $this->treeName = $treeName;
    }

    /**
     * Returns the childleaf at a given index.
     *
     * @param int $index Index of the childleaf
     *
     * @return \CommerceTeam\Commerce\Tree\Leaf\Slave Childleaf
     */
    public function getChildLeaf($index)
    {
        if (!is_numeric($index)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('getChildLeaf (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return null;
        }

        if ($index >= $this->leafcount) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('getChildLeaf (leaf) has an index out of bounds.', 'commerce', 3);
            }

            return null;
        }

        return $this->leafs[$index];
    }

    /**
     * Pass the Item UID Array with the Userpositions to the LeafData.
     *
     * @param array $positionIds Array with item uids that are positions
     *
     * @return void
     */
    public function setPositions(array &$positionIds)
    {
        if (!is_array($positionIds)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('setPositions (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return;
        }
        $this->data->setPositions($positionIds);
    }

    /**
     * Initializes the leaf
     * Passes the Parameters to its child leafs.
     *
     * @param int $index Index of this leaf
     * @param array $parentIndices Array with parent indices
     *
     * @return void
     */
    public function init($index, array $parentIndices = array())
    {
        if (!is_numeric($index) || !is_array($parentIndices)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('init (leaf) gets passed invalid parameters.', 'commerce', 3);
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
        for ($i = 0; $i < $this->leafcount; ++$i) {
            // For every childleaf, set its parent leaf to the current leaf
            /**
             * Slave.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Slave $leafSlave
             */
            $leafSlave = &$this->leafs[$i];
            $leafSlave->setParentLeaf($this);
            $leafSlave->init($i, $parentIndices);
        }
    }

    /**
     * Sets the PositionIds for this leafs own LeafData and
     * its ChildLeafs ("recursively").
     *
     * @param array $positions Item uids that are positions
     *
     * @return void
     */
    public function setDataPositions(array &$positions)
    {
        if (!is_array($positions)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('setDataPositions (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return;
        }

        $this->data->setPositions($positions);

        // Recursive Call
        for ($i = 0; $i < $this->leafcount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Leaf $leaf
             */
            $leaf = &$this->leafs[$i];
            $leaf->setDataPositions($positions);
        }
    }

    /**
     * Sorts the Leafdata in a way to represent the linear tree structure
     * Sorts its leafs as well.
     *
     * @param int $rootUid Item that will act as the root of the tree
     *
     * @return void
     */
    public function sort($rootUid)
    {
        if (!is_numeric($rootUid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('sort (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return;
        }

        $this->data->sort($rootUid);

        // Sort Leafs
        for ($i = 0; $i < $this->leafcount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Leaf $leaf
             */
            $leaf = &$this->leafs[$i];
            $leaf->sort($rootUid);
        }
    }

    /**
     * Returns the sorted array
     * Merges with the sorted arrays of the leafs.
     *
     * @return array
     */
    public function getSortedArray()
    {
        $sortedData = $this->data->getSortedArray();

        for ($i = 0; $i < $this->leafcount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Leaf $leaf
             */
            $leaf = &$this->leafs[$i];
            $sortedData = array_merge($sortedData, $leaf->getSortedArray());
        }

        return $sortedData;
    }

    /**
     * Returns if any leaf (beneath this one) has subrecords for a specific row.
     *
     * @param int $pid Row Item which would be parent of the leaf's records
     *
     * @return bool
     */
    public function leafsHaveRecords($pid)
    {
        if (!is_numeric($pid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('leafsHaveRecords (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        // if we have no leafs, we have no records - if we dont have an entry
        // 'uid', what should we look for? - the row has to be expanded
        if (0 >= $this->leafcount || !$this->data->isExpanded($pid)) {
            return false;
        }

        for ($i = 0; $i < $this->leafcount; ++$i) {
            // if the childleaf has children for the parent
            if (!empty($this->leafs[$i]->data->getChildrenByPid($pid))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether or not a node is the last in the current subtree.
     *
     * @param array $row Row Item
     * @param int $pid Parent UID of the current Row Item
     *
     * @return bool
     */
    public function isLast(array $row, $pid = 0)
    {
        if (!is_array($row) || !is_numeric($pid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('isLast (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        // If the row has an entry 'lastNode', its position is supplied
        // from the DB - check if the item is last under the current pid
        $isLast = (isset($row['lastNode']) && GeneralUtility::inList($row['lastNode'], $pid)) ? true : false;

        return $isLast;
    }

    /**
     * Returns whether or not a node has Children.
     *
     * @param array $row Row Item
     *
     * @return bool
     */
    public function hasChildren(array $row)
    {
        if (!is_array($row)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('hasChildren (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        $hasChildren = false;

        // check if any leaf has a subitem for the current row
        if ($this->leafcount > 0) {
            for ($i = 0; $i < $this->leafcount; ++$i) {
                /**
                 * Leaf.
                 *
                 * @var \CommerceTeam\Commerce\Tree\Leaf\Leaf $leaf
                 */
                $leaf = &$this->leafs[$i];
                $hasChildren = $leaf->hasSubitems($row);
                if (true == $hasChildren) {
                    break;
                }
            }
        }

        return $hasChildren;
    }

    /**
     * Returns whether we have at least 1 subitem for a specific parent row.
     *
     * @param array $row Parent Row Information
     *
     * @return bool
     */
    public function hasSubitems(array $row)
    {
        if (!is_array($row)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('hasSubitems (leaf) gets passed invalid parameters.', 'commerce', 3);
            }

            return false;
        }

        return !empty($this->data->getChildrenByPid($row['uid']));
    }
}

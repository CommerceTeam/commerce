<?php
namespace CommerceTeam\Commerce\Tree;

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
 * Implements a browseable AJAX tree.
 *
 * Class \CommerceTeam\Commerce\Tree\Browsetree
 *
 * @author 2008 Erik Frister <typo3@marketing-factory.de>
 */
abstract class Browsetree
{
    /**
     * Name of the table.
     *
     * @var string
     */
    protected $treeName;

    /**
     * Should the clickmenu be disabled?
     *
     * @var bool
     */
    protected $noClickmenu;

    /**
     * The Leafs of the tree.
     *
     * @var array
     */
    protected $leafs;

    /**
     * Has the tree already been initialized?
     *
     * @var bool
     */
    protected $isInit;

    /**
     * Number of leafs in the tree.
     *
     * @var int
     */
    protected $leafcount;

    /**
     * Will hold the rendering method of the tree.
     *
     * @var string
     */
    protected $renderBy;

    /**
     * The uid from which to start rendering recursively, if we so chose to.
     *
     * @var int
     */
    protected $startingUid;

    /**
     * The recursive depth to choose if we chose to render recursively.
     *
     * @var int
     */
    protected $depth;

    /**
     * Constructor - init values.
     *
     * @return self
     */
    public function __construct()
    {
        $this->leafs = array();
        $this->leafcount = 0;
        $this->isInit = false;
        $this->noClickmenu = false;
        $this->renderBy = \CommerceTeam\Commerce\Tree\Leaf\Mounts::class;
        $this->startingUid = 0;
    }

    /**
     * Initializes the Browsetree.
     *
     * @return void
     */
    public function init()
    {
        $this->isInit = true;
    }

    /**
     * Sets the clickmenu flag for the tree
     * Gets passed along to all leafs, which themselves pass it to their view
     * Has to be set BEFORE initializing the tree with init().
     *
     * @param bool $flag Value to set
     *
     * @return void
     */
    public function noClickmenu($flag = true)
    {
        if (!is_bool($flag)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'noClickmenu (' . \CommerceTeam\Commerce\Tree\Browsetree::class .
                    ') gets a non-bool parameter (expected bool)!',
                    COMMERCE_EXTKEY,
                    2
                );
            }
        }
        $this->noClickmenu = $flag;
    }

    /**
     * Adds a leaf to the Tree.
     *
     * @param \CommerceTeam\Commerce\Tree\Leaf\Master $leaf Treeleaf Object which
     *     holds the \CommerceTeam\Commerce\Tree\Leaf\Data
     *     and the \CommerceTeam\Commerce\Tree\Leaf\View
     *
     * @return bool
     */
    public function addLeaf(\CommerceTeam\Commerce\Tree\Leaf\Master &$leaf)
    {
        // pass tree vars to the new leaf
        $leaf->setTreeName($this->treeName);
        $leaf->noClickmenu($this->noClickmenu);

        // add to collection
        $this->leafs[$this->leafcount++] = $leaf;

        return true;
    }

    /**
     * Returns the leaf object at the given index.
     *
     * @param int $index Leaf index
     *
     * @return \CommerceTeam\Commerce\Tree\Leaf\Master
     */
    public function getLeaf($index)
    {
        if (!is_numeric($index) || !isset($this->leafs[$index])) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'getLeaf (' . \CommerceTeam\Commerce\Tree\Browsetree::class . ') has an invalid parameter.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return null;
        }

        return $this->leafs[$index];
    }

    /**
     * Sets the unique tree name.
     *
     * @param string $tree Name of the Tree
     *
     * @return void
     */
    public function setTreeName($tree = '')
    {
        $this->treeName = $tree;
    }

    /**
     * Sets the internal rendering method to
     * \CommerceTeam\Commerce\Tree\Leaf\Mounts
     * Call BEFORE initializing.
     *
     * @return void
     */
    public function readByMounts()
    {
        // set internal var
        $this->renderBy = \CommerceTeam\Commerce\Tree\Leaf\Mounts::class;
    }

    /**
     * Sets the internal rendering method to 'recursively'
     * Call BEFORE initializing.
     *
     * @param int $uid UID from which the masterleafs should start
     * @param int $depth Depth
     *
     * @return void
     */
    public function readRecursively($uid, $depth = 100)
    {
        if (!is_numeric($uid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'readRecursively (' . \CommerceTeam\Commerce\Tree\Browsetree::class . ') has an invalid parameter.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }

        // set internal vars
        $this->renderBy = 'recursively';
        $this->depth = $depth;
        $this->startingUid = $uid;
    }

    /**
     * Returns a browseable Tree
     * Tree is automatically generated by using the Mountpoints and the User position.
     *
     * @return string
     */
    public function getBrowseableTree()
    {
        $return = '';

        switch ($this->renderBy) {
            case \CommerceTeam\Commerce\Tree\Leaf\Mounts::class:
                $this->getTreeByMountpoints();
                $return = $this->printTreeByMountpoints();
                break;

            case 'recursively':
                $this->getTree();
                break;

            default:
                if (TYPO3_DLOG) {
                    GeneralUtility::devLog(
                        'The Browseable Tree could not be printed. No rendering method was specified',
                        COMMERCE_EXTKEY,
                        3
                    );
                }
        }

        return $return;
    }

    /**
     * Returns a browseable Tree (only called by AJAX)
     * Note that so far this is only supported if you work with mountpoints;.
     *
     * @param array $parameter Array from PM link
     *
     * @return string HTML Code for Tree
     */
    public function getBrowseableAjaxTree(array $parameter)
    {
        if (is_null($parameter) || !is_array($parameter)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'The browseable AJAX tree (getBrowseableAjaxTree) was not printed because a parameter was invalid.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return '';
        }

        // Create the tree by mountpoints
        $this->getTreeByMountpoints();

        return $this->printAjaxTree($parameter);
    }

    /**
     * Forms the tree based on the mountpoints and the user positions.
     *
     * @return void
     */
    public function getTreeByMountpoints()
    {
        // Alternate Approach: Read all open Categories at once
        // Select those whose parent_id is set in the positions-Array
        // and those whose UID is set as the Mountpoint

        // Get the current position of the user
        $this->initializePositionSaving();

        // Go through the leafs and feed them the ids
        $leafCount = count($this->leafs);
        for ($i = 0; $i < $leafCount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Master $leafMaster
             */
            $leafMaster = &$this->leafs[$i];
            $leafMaster->byMounts();
            // Pass $i as the leaf's index
            $leafMaster->init($i);
        }
    }

    /**
     * Forms the tree.
     *
     * @return void
     */
    public function getTree()
    {
        $uid = $this->startingUid;
        $depth = $this->depth;

        // Go through the leafs and feed them the id
        $leafCount = count($this->leafs);
        for ($i = 0; $i < $leafCount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Master $leafMaster
             */
            $leafMaster = &$this->leafs[$i];
            $leafMaster->setUid($uid);
            $leafMaster->setDepth($depth);
            $leafMaster->init($i);
        }
    }

    /**
     * Prints the subtree for AJAX requests only.
     *
     * @param array $parameter Array from PM link
     *
     * @return string HTML Code
     */
    public function printAjaxTree(array $parameter)
    {
        if (is_null($parameter) || !is_array($parameter)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'The AJAX Tree (printAjaxTree) was not printed because the parameter was invalid.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return '';
        }

        $l = count($parameter);

        // parent|ID is always the last Item
        $values = explode('|', $parameter[count($parameter) - 1]);

        // assign current uid
        $id = $values[0];
        // assign item parent
        $pid = $values[1];
        // Extract the bank
        $bank = $parameter[2];
        $indexFirst = $parameter[1];

        $out = '';

        // Go to the correct leaf and print it
        /**
         * Leaf.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\Master $leafMaster
         */
        $leafMaster = &$this->leafs[$indexFirst];

        // i = 4 because if we have childleafs at all,
        // this is where they will stand in PM Array
        // l - 1 because the last entry in PM is the id
        for ($i = 4; $i < $l - 1; ++$i) {
            $leafMaster = &$leafMaster->getChildLeaf($parameter[$i]);

            // If we didnt get a leaf, return
            if ($leafMaster == null) {
                return '';
            }
        }

        $out .= $leafMaster->printChildleafsByLoop($id, $bank, $pid);

        return $out;
    }

    /**
     * Prints the Tree by the Mountpoints of each treeleaf.
     *
     * @return string HTML Code for Tree
     */
    public function printTreeByMountpoints()
    {
        $out = '<ul class="x-tree-root-ct x-tree-lines">';

        // Get the Tree for each leaf
        for ($i = 0; $i < $this->leafcount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Master $leafMaster
             */
            $leafMaster = &$this->leafs[$i];
            $out .= $leafMaster->printLeafByMounts();
        }
        $out .= '</ul>';

        return $out;
    }

    /**
     * Returns the Records in the tree as a array
     * Records will be sorted to represent the tree in linear order.
     *
     * @param int $rootUid Uid of the Item that will act as the root of the tree
     *
     * @return array
     */
    public function getRecordsAsArray($rootUid)
    {
        if (!is_numeric($rootUid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('getRecordsAsArray has an invalid $rootUid', COMMERCE_EXTKEY, 3);
            }

            return array();
        }

        // Go through the leafs and get sorted array
        $leafCount = count($this->leafs);

        $sortedData = array();

        // Initialize the categories (and its leafs)
        for ($i = 0; $i < $leafCount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Master $leafMaster
             */
            $leafMaster = $this->leafs[$i];
            if ($leafMaster->data->hasRecords()) {
                $leafMaster->sort($rootUid);
                $sortedData = array_merge($sortedData, $leafMaster->getSortedArray());
            }
        }

        return $sortedData;
    }

    /**
     * Returns an array that has as key the depth
     * and as value the category ids on that depth
     * Sorts the array in the process
     *     [0] => '13'
     *     [1] => '12, 11, 39, 54'.
     *
     * @param int $rootUid Root uid
     *
     * @return array
     */
    public function getRecordsPerLevelArray($rootUid)
    {
        if (!is_numeric($rootUid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('getRecordsPerLevelArray has an invalid parameter.', COMMERCE_EXTKEY, 3);
            }

            return array();
        }

        // Go through the leafs and get sorted array
        $leafCount = count($this->leafs);

        $sortedData = array();

        // Sort and return the sorted array
        for ($i = 0; $i < $leafCount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Master $leafMaster
             */
            $leafMaster = $this->leafs[$i];
            $leafMaster->sort($rootUid);
            $sorted = $leafMaster->getSortedArray();
            $sortedData = array_merge($sortedData, $sorted);
        }

        // Create the depth_catUids array
        $depth = array();

        $l = count($sortedData);

        for ($i = 0; $i < $l; ++$i) {
            if (!is_array($depth[$sortedData[$i]['depth']])) {
                $depth[$sortedData[$i]['depth']] = array($sortedData[$i]['row']['uid']);
            } else {
                $depth[$sortedData[$i]['depth']][] = $sortedData[$i]['row']['uid'];
            }
        }

        return $depth;
    }

    /**
     * Will initialize the User Position
     * Saves it in the Session and gives
     * the Position UIDs to the \CommerceTeam\Commerce\Tree\Leaf\Data.
     *
     * @return void
     */
    protected function initializePositionSaving()
    {
        // Get stored tree structure:
        $positions = unserialize($this->getBackendUser()->uc['browseTrees'][$this->treeName]);

        // In case the array is not set, initialize it
        if (!is_array($positions) || empty($positions) || key($positions[0][key($positions[0])]) !== 'items') {
            // reinitialize damaged array
            $positions = array();
            $this->savePosition($positions);
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('Resetting the Positions of the Browsetree. Were damaged.', COMMERCE_EXTKEY, 2);
            }
        }

        $parameter = GeneralUtility::_GP('PM');
        // IE takes # as anchor
        if (($parameterPosition = strpos($parameter, '#')) !== false) {
            $parameter = substr($parameter, 0, $parameterPosition);
        }

        // 0: treeName,
        // 1: leafIndex,
        // 2: Mount,
        // 3: set/clear
        // [4:,5:,.. further leafIndices], 5[+++]: Item UID
        $parameter = explode('_', $parameter);

        // PM has to be at LEAST 5 Items (up to a (theoratically) unlimited count)
        if (count($parameter) >= 5 && $parameter[0] == $this->treeName) {
            // Get the value - is always the last item
            // so far this is 'current UID|Parent UID'
            $value = explode('|', $parameter[count($parameter) - 1]);
            // now it is 'current UID'
            $value = $value[0];

            // Prepare the Array
            $c = count($parameter);
            // We get the Mount-Array of the corresponding leaf index
            $field = &$positions[$parameter[1]][$parameter[2]];

            // Move the field forward if necessary
            if ($c > 5) {
                $c -= 4;

                // Walk the PM
                $i = 4;

                // Leave out last value of the $PM Array since that
                // is the value and no longer a leaf Index
                while ($c > 1) {
                    // Mind that we increment $i on the fly on this line
                    $field = &$field[$parameter[$i++]];
                    --$c;
                }
            }

            if ($parameter[3]) {
                $field['items'][$value] = 1;
                $this->savePosition($positions);
            } else {
                unset($field['items'][$value]);
                $this->savePosition($positions);
            }
        }

        // Set the Positions for each leaf
        $leafCount = count($this->leafs);
        for ($i = 0; $i < $leafCount; ++$i) {
            /**
             * Leaf.
             *
             * @var \CommerceTeam\Commerce\Tree\Leaf\Master $leafMaster
             */
            $leafMaster = &$this->leafs[$i];
            $leafMaster->setDataPositions($positions);
        }
    }

    /**
     * Saves the content of ->stored (keeps track of expanded positions in the tree)
     * $this->treeName will be used as key for BE_USER->uc[] to store it in.
     *
     * @param array $positions Positions array
     *
     * @return void
     */
    protected function savePosition(array &$positions)
    {
        if (is_null($positions) || !is_array($positions)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'The Positions were not saved because the parameter was invalid',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }

        $backendUser = $this->getBackendUser();
        $backendUser->uc['browseTrees'][$this->treeName] = serialize($positions);
        $backendUser->writeUC();
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

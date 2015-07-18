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
 * Implements a master leaf for the \CommerceTeam\Commerce\Tree\Browsetree.
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\Master
 *
 * @author 2008-2009 Erik Frister <typo3@marketing-factory.de>
 */
class Master extends Leaf
{
    /**
     * Mount class.
     *
     * @var string
     */
    protected $mountClass = 'CommerceTeam\\Commerce\\Tree\\Leaf\\Mounts';

    /**
     * Flag if the Leafitems shall be read by specific mountpoints.
     *
     * @var bool
     */
    protected $byMounts;

    /**
     * Mountpoint-Object with the mountpoints of the leaf (if it is a treeleaf).
     *
     * @var \CommerceTeam\Commerce\Tree\Leaf\Mounts
     */
    protected $mounts;

    /**
     * Leaf data.
     *
     * @var \CommerceTeam\Commerce\Tree\Leaf\MasterData
     */
    public $data;

    /**
     * Initializes the leaf
     * Passes the Parameters to its child leafs.
     *
     * @param int   $index         Index of this leaf
     * @param array $parentIndices Array with parent indices
     */
    public function init($index, array $parentIndices = array())
    {
        if (!is_numeric($index) || !is_array($parentIndices)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('init (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return;
        }

        // Load Mountpoints and init the Position if we want to read the leafs by Mount
        if ($this->byMounts) {
            $this->loadMountpoints();
        }

            // Initialize the LeafData
        $this->data->init();
        $this->data->initRecords($index, $parentIndices);

        parent::init($index, $parentIndices);
    }

    /**
     * Sets the View and the Data of the Leaf.
     *
     * @param \CommerceTeam\Commerce\Tree\Leaf\View $view LeafView of the Leaf
     * @param \CommerceTeam\Commerce\Tree\Leaf\Data $data LeafData of the Leaf
     */
    public function initBasic(\CommerceTeam\Commerce\Tree\Leaf\View &$view, \CommerceTeam\Commerce\Tree\Leaf\Data &$data)
    {
        if (is_null($view) || is_null($data)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('initBasic (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return;
        }

        parent::initBasic($view, $data);

        $this->byMounts = false;
        $this->mounts = null;
    }

    /**
     * Sets if the leaf should be read by the Mountpoints.
     *
     * @param bool $flag Flag
     */
    public function byMounts($flag = true)
    {
        $this->byMounts = (bool) $flag;
    }

    /**
     * Pass the Item UID Array with the Mountpoints to the LeafData.
     *
     * @param array $mountIds Array with item UIDs that are mountpoints
     */
    public function setMounts(array $mountIds)
    {
        if (!is_array($mountIds)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'setMounts (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }
        $this->data->setMounts($mountIds);
    }

    /**
     * Loads the leafs Mountpoints and sets their UIDs to the LeafData.
     */
    protected function loadMountpoints()
    {
        $this->mounts = GeneralUtility::makeInstance($this->mountClass);
        $this->mounts->init((int) $this->getBackendUser()->user['uid']);

        $this->setMounts($this->mounts->getMountData());
    }

    /**
     * Pass the UID of the Item to recursively build a tree from to the LeafData.
     *
     * @param int $uid UID of the Item
     */
    public function setUid($uid)
    {
        if (!is_numeric($uid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'setUid (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }
        $this->data->setUid($uid);
    }

    /**
     * Get uid.
     *
     * @return int
     */
    public function getUid()
    {
        return $this->data->getUid();
    }

    /**
     * Sets the recursive depth of the tree.
     *
     * @param int $depth Recursive Depth
     */
    public function setDepth($depth)
    {
        if (!is_numeric($depth)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'setDepth (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }
        $this->data->setDepth($depth);
    }

    /**
     * Prints the Leaf by its mountpoint.
     *
     * @return string HTML Code
     */
    public function printLeafByMounts()
    {
        $out = '';

        // If we don't have a mount object, return the error message
        if ($this->mounts == null || !$this->mounts->hasMounts() || !$this->data->hasRecords()) {
            return $this->getLL('leaf.noMount');
        }

        while (($mount = $this->mounts->walk()) !== false) {
            $out .= $this->printChildleafsByLoop($mount, $mount);
        }

        return $out;
    }

    /**
     * Returns whether or not a node is the last in the current subtree.
     *
     * @param array $row Row Item
     * @param int   $pid Parent UID of the current Row Item
     *
     * @return bool
     */
    public function isLast(array $row, $pid = 0)
    {
        if (!is_array($row) || !is_numeric($pid)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog('isLast (leaf) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return false;
        }

        $isLast = parent::isLast($row, $pid);

        // In case the row is last, check if it is really the last
        // by seeing if any of its slave leafs have records
        if ($isLast) {
            $isLast = !$this->leafsHaveRecords($pid);
        }

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
                GeneralUtility::devLog(
                    'hasChildren (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return false;
        }

        $hasChildren = ($this->data->getChildrenByPid($row['uid']));

            // if current item doesn't have subchildren, look in slaveLeafs
        if (!$hasChildren) {
            $hasChildren = parent::hasChildren($row);
        }

        return $hasChildren;
    }

    /**
     * Does the same thing printChildleafsByUid and printChildleafsByPid
     * do in one function without recursion.
     *
     * @param int      $startUid UID in which we start
     * @param int      $bank     Bank UID
     * @param bool|int $pid      UID of the parent Item - only passed if
     *                           this function is called by ajax; thus it will only influence functionality
     *                           if it is numeric
     *
     * @return string HTML Code
     */
    public function printChildleafsByLoop($startUid, $bank, $pid = false)
    {
        // Check for valid parameters
        if (!is_numeric($startUid) || !is_numeric($bank)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'printChildleafsByLoop (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
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
        $lastLevel = 0;

        // Max. number of loops we make
        $crazyStart = $crazyRecursion = 10000;
        // temporary child stack
        $tempChildren = array();
        // temporary level stack - already filled with
        // a 0 because the starting child is on level 0
        $tempLevels = array(0);
            // holds which uid openend which level
        $levelOpener = array();

        // get the current item and set it as the starting child to print
        $child = $this->data->getChildByUid($startUid);

        // Abort if the starting Category is not found
        if ($child == null) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'printChildleafsByLoop (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) cannot find the starting item by its uid.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return '';
        }

        // Process the child and children
        while (!is_null($child) && is_array($child) && $crazyRecursion > 0) {
            // get the current level
            $level = @array_pop($tempLevels);

            // close the parent list if we are on a higher level than the list
            if ($level < $lastLevel) {
                for ($i = $level; $i < $lastLevel; ++$i) {
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
            $exp = $child['uid'] ? $this->data->isExpanded((int) $child['uid']) : true;
            $cssExpanded = ($exp) ? 'expanded' : '';

            if ($pid !== false) {
                // called by AJAX - to get it's true parent item, we have to pass
                // the pid because otherwise its ambiguous
                $child['item_parent'] = $pid;
                // all following items are not to be passed the pid because
                // they are not ambiguous
                $pid = false;
            }

            // Add class 'last' if it is
            $isLast = $this->isLast($child, $child['item_parent']);
            $cssLast = ($isLast) ? ' last' : '';

            $cssClass = $cssExpanded.' '.$cssLast.($child['uid'] > 0 ? '' : ' typo3-pagetree-node-notExpandable');

            // start the element
            $out .= '<li class="'.$cssClass.'">
				<div>';

            $isBank = ($child['uid'] == $bank);

            $hasChildren = $this->hasChildren($child);
            if ($child['uid']) {
                $out .= $this->view->PMicon($child, $isLast, $exp, $isBank, $hasChildren);
            } else {
                $backPath = $this->getControllerDocumentTemplate()->backPath;
                $out .= '<img alt="" src="'.$backPath.'clear.gif" class="x-tree-ec-icon x-tree-elbow-end-minus">';
            }

            $out .= $child['uid'] ? $this->view->getIcon($child) : $this->view->getRootIcon($child);
            $out .= $this->view->wrapTitle($child['title'], $child);

            $out .= '</div>';

            /******************
             * Done printing
             *****************/

            // read the children
            $childElements = ($exp) ? $this->data->getChildrenByPid((int) $child['uid']) : array();
            $m = count($childElements);

            // if there are children
            if ($m > 0) {
                // add that this record uid opened the last level
                $levelOpener[] = $child['uid'];

                // set $child to first child element and store the other in a temp Array,
                // with the second child being on the last position (like a stack)
                $child = $childElements[0];

                $out .= '<ul>';
                ++$level;

                // add all children except the first to the stack
                for ($j = $m - 1; $j > 0; --$j) {
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
                    $out .= $this->getSlaveElements((int) $child['uid'], $bank);
                    $out .= '</ul>';
                }

                // pop the last element from the temp array and set
                // it as a child; if temp array is empty, break;
                $child = @array_pop($tempChildren);

                    // close the list item
                $out .= '</li>';
            }

            --$crazyRecursion;
        }

        // DLOG
        if (TYPO3_DLOG) {
            GeneralUtility::devLog(
                'printChildLeafsByLoop (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) did '.($crazyStart - $crazyRecursion).' loops!',
                COMMERCE_EXTKEY,
                1
            );
        }

        // Close the rest of the lists
        for ($i = 0; $i < $lastLevel; ++$i) {
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
                GeneralUtility::devLog(
                    'printChildleafsByLoop (CommerceTeam\\Commerce\\Tree\\Leaf\\Master) was put to hold because there was a
						danger of endless recursion.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return $this->getLL('leaf.maxRecursion');
        }

        return $out;
    }

    /**
     * Gets the elements of the slave leafs.
     *
     * @param int $pid  Pade id
     * @param int $bank Bank
     *
     * @return string HTML Code generated by the slaveleafs
     */
    protected function getSlaveElements($pid, $bank)
    {
        $out = '';

        /**
         * Leaf.
         *
         * @var \CommerceTeam\Commerce\Tree\Leaf\Slave
         */
        foreach ($this->leafs as $leaf) {
            $out .= $leaf->printChildleafsByParent($pid, $bank);
        }

        return $out;
    }

    /**
     * Get controller document template.
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getControllerDocumentTemplate()
    {
        // $GLOBALS['SOBE'] might be any kind of PHP class (controller most
        // of the times) These class do not inherit from any common class,
        // but they all seem to have a "doc" member
        return $GLOBALS['SOBE']->doc;
    }
}

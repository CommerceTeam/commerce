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

/**
 * Implements a slave leaf of the \CommerceTeam\Commerce\Tree\Browsetree.
 *
 * \CommerceTeam\Commerce\Tree\Leaf\Slave
 *
 * @author 2008-2013 Erik Frister <typo3@marketing-factory.de>
 */
class Slave extends Leaf
{
    /**
     * Parent leaf.
     *
     * @var \CommerceTeam\Commerce\Tree\Leaf\Leaf
     */
    protected $parentLeaf;

    /**
     * Slave data.
     *
     * @var \CommerceTeam\Commerce\Tree\Leaf\SlaveData
     */
    public $data;

    /**
     * Leaf view.
     *
     * @var \CommerceTeam\Commerce\Tree\Leaf\View
     */
    public $view;

    /**
     * Page id.
     *
     * @var int
     */
    protected $pid;

    /**
     * Sets the parent leaf of this leaf.
     *
     * @param \CommerceTeam\Commerce\Tree\Leaf\Leaf $parentLeaf Parent of this leaf
     *
     * @return void
     */
    public function setParentLeaf(\CommerceTeam\Commerce\Tree\Leaf\Leaf &$parentLeaf)
    {
        if (is_null($parentLeaf)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'setParentLeaf (' . self::class . ') gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }
        $this->parentLeaf = $parentLeaf;
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
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'init (' . self::class . ') gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }

        // Initialize the \CommerceTeam\Commerce\Tree\Leaf\Data
        $this->data->init();
        $this->data->initRecords($index, $parentIndices, $this->parentLeaf->data);

        parent::init($index, $parentIndices);
    }

    /**
     * Prints the single leaf item
     * Since this is a slave, this can only EVER be called by AJAX.
     *
     * @param int $startUid UID in which we start
     * @param int $bank Bank UID
     * @param int $pid UID of the parent item
     *
     * @return string HTML Code
     */
    public function printChildleafsByLoop($startUid, $bank, $pid)
    {
        // Check for valid parameters
        if (!is_numeric($startUid) || !is_numeric($bank)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'printChildleafsByLoop (' . self::class . ') gets passed invalid parameters.',
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

        // get the Parent Item and set it as the starting child to print
        $child = $this->data->getChildByUid($startUid);
        $child['item_parent'] = $pid;

        // Abort if the starting Category is not found
        if (null == $child) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'printChildleafsByLoop (' . self::class . ') cannot find the starting category by its uid.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return '';
        }

        /********************
         * Printing the Item
         *******************/
        // Give class 'expanded' if it is
        $exp = $this->data->isExpanded((int) $child['uid']);
        $cssExpanded = ($exp) ? 'expanded' : '';

        // Add class 'last' if it is
        $isLast = $this->isLast($child, $pid);
        $cssLast = ($isLast) ? ' last' : '';

        $cssClass = $cssExpanded . ' ' . $cssLast;

        // start the element
        $out .= '<li class="' . $cssClass . '">
            <div>';

        // a slave can never be a bank
        $isBank = false;
        $hasChildren = $this->hasChildren($child);

        // pm icon
        $out .= $this->view->PMicon($child, $isLast, $exp, $isBank, $hasChildren);

        // icon
        $out .= $this->view->getIcon($child);

        // title
        $out .= $this->view->wrapTitle($child['title'], $child) . '</div>';

        /******************
         * Done printing
         *****************/

        // Print the children from the child leafs if the current leaf is expanded
        if ($exp) {
            $out .= '<ul>';
            for ($i = 0; $i < $this->leafcount; ++$i) {
                /**
                 * Slave.
                 *
                 * @var \CommerceTeam\Commerce\Tree\Leaf\Slave $leaf
                 */
                $leaf = &$this->leafs[$i];
                $out .= $leaf->printChildleafsByParent((int) $child['uid'], $bank);
            }
            $out .= '</ul>';
        }

        // close the list item
        $out .= '</li>';

        return $out;
    }

    /**
     * Prints all leafs by the parent item.
     *
     * @param int $pid UID of the parent item
     * @param int $bank Bank UID
     *
     * @return string HTML Code
     */
    public function printChildleafsByParent($pid, $bank)
    {
        // Check for valid parameters
        if (!is_numeric($pid) || !is_numeric($bank)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'printChildleafsByParent (' . self::class . ') gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return '';
        }

        $out = '';

        // get the children
        $children = $this->data->getChildrenByPid($pid);

        $l = count($children);

        // Process the child and children
        for ($i = 0; $i < $l; ++$i) {
            $child = $children[$i];

            $this->pid = $pid;
            $out .= $this->printChildleafsByLoop($child['uid'], $bank, $pid);
        }

        // DLOG
        if (TYPO3_DLOG) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                'printChildleafsByParent (' . self::class . ') did ' . $l . ' loops!',
                COMMERCE_EXTKEY,
                1
            );
        }

        return $out;
    }
}

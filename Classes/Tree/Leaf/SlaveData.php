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
 * Implements the data view for leaf slave.
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\SlaveData
 *
 * @author 2008 Erik Frister <typo3@marketing-factory.de>
 */
abstract class SlaveData extends Data
{
    /**
     * Returns an array of Positions.
     *
     * @param int $index Index of this leaf
     * @param array $indices Parent Indices
     *
     * @return array
     */
    public function getPositionsByIndices($index, array $indices)
    {
        if (!is_numeric($index) || !is_array($indices)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'getPositionsByIndices (productdata) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return array();
        }

        // Construct the Array of Position Ids
        $firstIndex = $indices[0];
        if (!is_array($this->positionArray[$firstIndex])) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'getPositionsByIndices (CommerceTeam\\Commerce\\Tree\\Leaf\\SlaveData)
                    does not find the first Index in the position array.',
                    COMMERCE_EXTKEY,
                    2
                );
            }
            $this->positionUids = array();

            return $this->positionUids;
        }

        $this->mountIds = array_keys($this->positionArray[$firstIndex]);

        return parent::getPositionsByIndices($index, $indices);
    }

    /**
     * Initializes the Records
     * All Products are read, no matter what the rights - only editing is restricted!
     *
     * @param int $index Leaf index
     * @param array $parentIndices Parent Indices
     * @param \CommerceTeam\Commerce\Tree\Leaf\Data $parentLeafData Parent leafData
     *
     * @return void
     */
    public function initRecords($index, array $parentIndices, \CommerceTeam\Commerce\Tree\Leaf\Data &$parentLeafData)
    {
        if (!is_numeric($index) || !is_array($parentIndices) || is_null($parentLeafData)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'initRecords (CommerceTeam\\Commerce\\Tree\\Leaf\\SlaveData) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }
        // Check if User's Group may view the records
        $backendUser = $this->getBackendUser();
        if (!$backendUser->check('tables_select', $this->table)) {
            $this->records = null;
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'initRecords User is not allowed to view table: ' . $this->table,
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return;
        }

        // Store the position Uids
        $this->getPositionsByIndices($index, $parentIndices);

        // Get the uids of the open parent - returns uids which are currently open
        $recordUids = $parentLeafData->getRecordsUids();

        if ($recordUids == null) {
            return;
        }

        // Read all items
        if ($this->useMMTable) {
            $this->where['uid_foreign'] = implode(',', $recordUids);
            $this->where['uid_local'] = 0;
        } else {
            $this->where[$this->itemParentField] = implode(',', $recordUids);
            $this->where['uid'] = 0;
        }

        $this->records = $this->loadRecords();
    }
}

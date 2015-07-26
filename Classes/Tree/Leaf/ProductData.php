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
 * Implements the \CommerceTeam\Commerce\Tree\Leaf\Data for Product.
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\ProductData
 *
 * @author 2008 Erik Frister <typo3@marketing-factory.de>
 */
class ProductData extends SlaveData
{
    /**
     * Fields that should be read from the products.
     *
     * @var string
     */
    protected $extendedFields = 'title, navtitle, hidden, deleted, starttime, endtime, fe_group, t3ver_oid,
        t3ver_id, t3ver_wsid, t3ver_label, t3ver_state, t3ver_stage, t3ver_count, t3ver_tstamp';

    /**
     * Data table.
     *
     * @var string
     */
    protected $table = 'tx_commerce_products';

    /**
     * Table to read the leafitems from.
     *
     * @var string
     */
    protected $itemTable = 'tx_commerce_products';

    /**
     * Table that is to be used to find parent items.
     *
     * @var string
     */
    protected $mmTable = 'tx_commerce_products_categories_mm';

    /**
     * Flag if mm table is to be used or the parent field.
     *
     * @var bool
     */
    protected $useMMTable = true;

    /**
     * Item parent.
     *
     * @var string
     */
    protected $item_parent = 'uid_foreign';

    /**
     * Initializes the ProductData Object.
     */
    public function init()
    {
        // do not read deleted and offline versions
        $this->whereClause = 'tx_commerce_products.deleted = 0 AND tx_commerce_products.pid != -1';
        $this->order = 'tx_commerce_products.sorting ASC';
    }

    /**
     * Loads and returns the Array of Records.
     *
     * @param int $uid   UID of the Category that is the parent
     * @param int $depth Recursive Depth (not used here)
     *
     * @return array
     */
    public function getRecordsDbList($uid, $depth = 2)
    {
        $backendUser = $this->getBackendUser();

        if (!is_numeric($uid) || !is_numeric($depth)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'getRecordsDbList (productdata) gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return null;
        }

        // Check if User's Group may view the records
        if (!$backendUser->check('tables_select', $this->table)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
                    'getRecordsDbList (productdata): Usergroup is not allowed to view records.',
                    COMMERCE_EXTKEY,
                    2
                );
            }

            return null;
        }

        if (!is_numeric($uid)) {
            return null;
        }

        $this->where['uid_foreign'] = $uid;
        $this->where['uid_local'] = 0;

        return $this->loadRecords();
    }
}

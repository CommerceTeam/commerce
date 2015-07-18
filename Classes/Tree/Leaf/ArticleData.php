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
 * Implements the Data for the Article Leaf.
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\ArticleData
 *
 * @author 2008 Erik Frister <typo3@marketing-factory.de>
 */
class ArticleData extends SlaveData
{
    /**
     * Fields that should be read from the products.
     *
     * @var string
     */
    protected $extendedFields = 'title, navtitle, hidden, deleted, starttime, endtime, fe_group, t3ver_oid, t3ver_id, t3ver_label';

    /**
     * Table.
     *
     * @var string
     */
    protected $table = 'tx_commerce_articles';

    /**
     * Table to read the leafitems from.
     *
     * @var string
     */
    protected $itemTable = 'tx_commerce_articles';

    /**
     * Flag if mm table is to be used or the parent field.
     *
     * @var bool
     */
    protected $useMMTable = false;

    /**
     * Item parent field.
     *
     * @var string
     */
    protected $itemParentField = 'uid_product';

    /**
     * Item parent.
     *
     * @var string
     */
    protected $item_parent = 'uid_product';

    /**
     * Initializes the ProductData Object.
     */
    public function init()
    {
        $this->whereClause = 'deleted = 0';
        $this->order = 'tx_commerce_articles.sorting ASC';
    }

    /**
     * Returns true if this Article is currently expanded.
     *
     * @param array $uid Uid of the current row
     *
     * @return bool
     *
     * @todo If we implement the positions (see above), we should also implement this and any function related to making this leaf not ultimate
     */
    public function isExpanded($uid)
    {
        // Article is the ultimate leaf, so to speak - it currently has no subleafs
        return false;
    }
}

<?php
namespace CommerceTeam\Commerce\ViewHelpers\Browselinks;

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
 * Implements the \CommerceTeam\Commerce\Tree\Leaf\View for the Category.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\Browselinks\CategoryView
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class CategoryView extends \CommerceTeam\Commerce\Tree\Leaf\View
{
    /**
     * DB Table.
     *
     * @var string
     */
    protected $table = 'tx_commerce_categories';

    /**
     * Dom id prefix.
     *
     * @var string
     */
    protected $domIdPrefix = 'txcommerceCategory';

    /**
     * The linked category.
     *
     * @var int
     */
    protected $openCat = 0;

    /**
     * Returns the link from the tree used to jump to a destination.
     *
     * @param array $row Array with the ID Information
     *
     * @return string
     */
    public function getJumpToParam(array $row)
    {
        $result = 'commerce:tx_commerce_categories:' . $row['uid'];

        if (!is_array($row)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'getJumpToParam (' . self::class . ') gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            $result = '';
        }

        return $result;
    }

    /**
     * Set open category.
     *
     * @param int $uid Uid
     *
     * @return void
     */
    public function setOpenCategory($uid)
    {
        $this->openCat = $uid;
    }

    /**
     * Wrapping title in a-tags.
     *
     * @param string $title Title
     * @param string $row Record
     * @param int $bank Pointer (which mount point number)
     *
     * @return string
     */
    public function wrapTitle($title, $row, $bank = 0)
    {
        if (!is_array($row) || !is_numeric($bank)) {
            if (TYPO3_DLOG) {
                GeneralUtility::devLog(
                    'wrapTitle (' . self::class . ') gets passed invalid parameters.',
                    COMMERCE_EXTKEY,
                    3
                );
            }

            return '';
        }

        // Max. size for Title of 30
        $title = trim($title) ? GeneralUtility::fixed_lgd_cs($title, 30) : $this->getLL('leaf.noTitle');

        $aOnClick = 'return link_commerce(\'' . $this->getJumpToParam($row) . '\');';

        $style = ($row['uid'] == $this->openCat && $this->openCat > 0) ? 'style="color: red; font-weight: bold"' : '';
        $result = (($this->noRootOnclick && 0 == $row['uid']) || $this->noOnclick) ? $title : '<a href="#" onclick="' .
            htmlspecialchars($aOnClick) . '" ' . $style . '>' . $title . '</a>';

        return $result;
    }
}

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
 * Implements the View for Articles.
 *
 * Class \CommerceTeam\Commerce\Tree\Leaf\ArticleView
 *
 * @author 2008-2014 Erik Frister <typo3@marketing-factory.de>
 */
class ArticleView extends View
{
    /**
     * DB Table.
     *
     * @var string
     */
    protected $table = 'tx_commerce_articles';

    /**
     * Dom id prefix.
     *
     * @var string
     */
    protected $domIdPrefix = 'txcommerceArticle';

    /**
     * Wrapping $title in a-tags.
     *
     * @param string $title Title
     * @param string $row   Record
     * @param int    $bank  Pointer (which mount point number)
     *
     * @return string
     */
    public function wrapTitle($title, &$row, $bank = 0)
    {
        if (!is_array($row) || !is_numeric($bank)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('wrapTitle (articleview) gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return '';
        }

        // Max. size for Title of 255
        $title = trim($title) ? \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, 255) : $this->getLL('leaf.noTitle');

        $aOnClick = 'if(top.content.list_frame){top.content.list_frame.location.href=top.TS.PATH_typo3+\'alt_doc.php?'.
            'returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+'.
            'top.content.list_frame.document.location.search)+\'&'.$this->getJumpToParam($row).'\';}';

        $res = ($this->noOnclick) ? $title : '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
            htmlspecialchars(strip_tags($title)).'</a>';

        return $res;
    }

    /**
     * Returns the link from the tree used to jump to a destination.
     *
     * @param array $row Array with the ID Information
     *
     * @return string
     */
    public function getJumpToParam(array &$row)
    {
        if (!is_array($row)) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('getJumpToParam gets passed invalid parameters.', COMMERCE_EXTKEY, 3);
            }

            return '';
        }

        $value = 'edit';

        if ($this->realValues) {
            $value = $this->table.'_'.$row['uid'];
        }

        $res = 'edit['.$this->table.']['.$row['uid'].']='.$value;

        return $res;
    }
}

<?php
namespace CommerceTeam\Commerce\Tree\View;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generate a page-tree, non-browsable.
 */
class CategoryTreeView extends \TYPO3\CMS\Backend\Tree\View\AbstractTreeView
{
    /**
     * @var array
     */
    public $fieldArray = [
        'uid',
        'pid',
        'title',
        'navtitle',
        't3ver_id',
        't3ver_state',
        'hidden',
        'starttime',
        'endtime',
        'fe_group',
    ];

    /**
     * override to use this treeName
     * @var string
     */
    public $treeName = 'categories';

    /**
     * override to use this table
     * @var string
     */
    public $table = 'tx_commerce_categories';

    /**
     * @var string
     */
    public $parentField = 'mm.uid_foreign';

    /**
     * @var bool
     */
    public $ext_showNavTitle = false;

    /**
     * Init function
     * REMEMBER to feed a $clause which will filter out non-readable pages!
     *
     * @param string $clause Part of where query which will filter out non-readable pages.
     * @param string $orderByFields Record ORDER BY field
     * @return void
     */
    public function init($clause = '', $orderByFields = '')
    {
        parent::init(' AND deleted=0 ' . $clause, $this->table . '.sorting');
    }

    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - and all levels should, so we always return 1.
     *
     * @param int $id ID (uid) to test for (see extending classes where this is checked against session data)
     * @return bool
     */
    public function expandNext($id)
    {
        return 1;
    }

    /**
     * Generate the plus/minus icon for the browsable tree.
     * In this case, there is no plus-minus icon displayed.
     *
     * @param array $row Record for the entry
     * @param int $a The current entry number
     * @param int $c The total number of entries. If equal to $a, a 'bottom' element is returned.
     * @param int $nextCount The number of sub-elements to the current element.
     * @param bool $isExpand The element was expanded to render subelements if this flag is set.
     * @return string Image tag with the plus/minus icon.
     * @access private
     * @see AbstractTreeView::PMicon()
     */
    public function PMicon($row, $a, $c, $nextCount, $isExpand)
    {
        return '<span class="treeline-icon treeline-icon-join' . ($a == $c ? 'bottom' : '') . '"></span>';
    }

    /**
     * Get stored tree structure AND updating it if needed according to incoming PM GET var.
     * - Here we just set it to nothing since we want to just render the tree, nothing more.
     *
     * @return void
     * @access private
     */
    public function initializePositionSaving()
    {
        $this->stored = [];
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * Do NOT htmlspecialchar the string from this function - has already been done.
     *
     * @param array $row The input row (where the key "title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title.
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        $lang = $this->getLanguageService();
        if ($this->ext_showNavTitle && isset($row['navtitle']) && trim($row['navtitle']) !== '') {
            $title = '<span title="' . $lang->sL('LLL:EXT:lang/locallang_tca.xlf:title', true) . ' '
                     . htmlspecialchars(trim($row['title'])) . '">'
                     . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['navtitle'], $titleLen))
                     . '</span>';
        } else {
            $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $titleLen));
            if (isset($row['navtitle']) && trim($row['navtitle']) !== '') {
                $title = '<span title="'
                     . $lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.nav_title', true)
                     . ' ' . htmlspecialchars(trim($row['navtitle'])) . '">' . $title
                     . '</span>';
            }
            $title = trim($row['title']) === ''
                ? '<em>[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', true) . ']</em>'
                : $title;
        }
        return $title;
    }


    /**
     * Getting the tree data: Selecting/Initializing data pointer to items for a certain parent id.
     * For tables: This will make a database query to select all children to "parent"
     * For arrays: This will return key to the ->dataLookup array
     *
     * @param int $parentId parent item id
     *
     * @return \Doctrine\DBAL\Driver\Statement|int Data handle (
     *  Tables: An sql-resource,
     *  arrays: A parentId integer. -1 is returned if there were NO subLevel.
     * )
     */
    public function getDataInit($parentId)
    {
        if (is_array($this->data)) {
            if (!is_array($this->dataLookup[$parentId][$this->subLevelID])) {
                $parentId = -1;
            } else {
                reset($this->dataLookup[$parentId][$this->subLevelID]);
            }
            return $parentId;
        } else {
            $queryBuilder = $this->getQueryBuilderForTable($this->table);

            /** @var BackendWorkspaceRestriction $workspaceRestriction */
            $workspaceRestriction = GeneralUtility::makeInstance(BackendWorkspaceRestriction::class);
            /** @var DeletedRestriction $deleteRestriction */
            $deleteRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add($deleteRestriction)
                ->add($workspaceRestriction);

            $result = $queryBuilder
                ->select($this->table . '.' . implode(', ' . $this->table . '.', $this->fieldArray))
                ->from($this->table)
                ->innerJoin('t', 'tx_commerce_categories_parent_category_mm', 'mm', 't.uid = mm.uid_local')
                ->where(
                    $queryBuilder->expr()->eq(
                        $this->parentField,
                        $queryBuilder->createNamedParameter($parentId, \PDO::PARAM_STR)
                    )
                )
                ->orderBy('t.' . $this->orderByFields)
                ->execute();

            return $result;
        }
    }

    /**
     * Returns the number of records having the parent id, $uid
     *
     * @param int $uid Id to count subitems for
     *
     * @return int
     */
    public function getCount($uid)
    {
        if (is_array($this->data)) {
            $res = $this->getDataInit($uid);
            return $this->getDataCount($res);
        } else {
            $queryBuilder = $this->getQueryBuilderForTable($this->table);

            /** @var BackendWorkspaceRestriction $workspaceRestriction */
            $workspaceRestriction = GeneralUtility::makeInstance(BackendWorkspaceRestriction::class);
            /** @var DeletedRestriction $deleteRestriction */
            $deleteRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add($deleteRestriction)
                ->add($workspaceRestriction);

            $count = $queryBuilder
                ->count('t.uid')
                ->from($this->table)
                ->innerJoin('t', 'tx_commerce_categories_parent_category_mm', 'mm', 't.uid = mm.uid_local')
                ->where(
                    $queryBuilder->expr()->eq(
                        $this->parentField,
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_STR)
                    )
                )
                ->execute()
                ->fetchColumn();

            return $count;
        }
    }

    /**
     * @param $table
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilderForTable($table): \TYPO3\CMS\Core\Database\Query\QueryBuilder
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable($table);
        return $queryBuilder;
    }
}

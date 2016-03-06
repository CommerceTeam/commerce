<?php
namespace CommerceTeam\Commerce\Tree\View;

use CommerceTeam\Commerce\Utility\BackendUserUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as CoreBackendUtility;
use CommerceTeam\Commerce\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

class ElementBrowserCategoryTreeView extends \TYPO3\CMS\Backend\Tree\View\BrowseTreeView
{
    /**
     * override to use this treeName
     * @var string
     */
    public $treeName = 'browseLinkCategories';

    /**
     * override to use this table
     * @var string
     */
    public $table = 'tx_commerce_categories';

    /**
     * whether the page ID should be shown next to the title, activate through
     * userTSconfig (options.pageTree.showPageIdWithTitle)
     *
     * @var bool
     */
    public $ext_showPageId = false;

    /**
     * @var bool
     */
    public $ext_pArrPages = true;

    /**
     * @var LinkParameterProviderInterface
     */
    protected $linkParameterProvider;

    /**
     * Constructor. Just calling init()
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Initialize, setting what is necessary for browsing pages.
     * Using the current user.
     *
     * @param string $clause Additional clause for selecting pages.
     * @param string $orderByFields record ORDER BY field
     * @return void
     */
    public function init($clause = '', $orderByFields = '')
    {
        // This is very important for making trees of categories:
        //  - Filtering out deleted categories
        //  - categories with no access to
        //  - and sorting them correctly
        parent::init(' AND ' . BackendUtility::getCategoryPermsClause(1) . ' ' . $clause, 'sorting');
        $this->title = 'Commerce';

        /** BackendUserUtility */
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        $this->MOUNTS = $backendUserUtility->returnWebmounts();
    }

    /**
     * @param LinkParameterProviderInterface $linkParameterProvider
     *
     * @return void
     */
    public function setLinkParameterProvider(LinkParameterProviderInterface $linkParameterProvider)
    {
        $this->linkParameterProvider = $linkParameterProvider;
        $this->thisScript = $linkParameterProvider->getScriptUrl();
    }

    /**
     * Wrapping the title in a link, if applicable.
     *
     * @param string $title Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
     * @param array $v The record
     * @param bool $ext_pArrPages (ignored)
     * @return string Wrapping title string.
     */
    public function wrapTitle($title, $v, $ext_pArrPages = false)
    {
        if ($this->ext_isLinkable($v['uid'])) {
            return '<span class="list-tree-title"><a href="#" class="t3js-pageLink" data-id="commerce:c:'
                . (int)$v['uid'] . '">' . $title . '</a></span>';
        } else {
            return '<span class="list-tree-title text-muted">' . $title . '</span>';
        }
    }

    /**
     * Fetches the data for the tree
     *
     * @param int $uid item id for which to select subitems (parent id)
     * @param int $depth Max depth (recursivity limit)
     * @param string $depthData HTML-code prefix for recursive calls.

     * @return int The count of items on the level
     */
    public function getTree($uid, $depth = 999, $depthData = '')
    {
        // Buffer for id hierarchy is reset:
        $this->buffer_idH = [];
        // Init vars
        $depth = (int)$depth;
        $HTML = '';
        $a = 0;
        $res = $this->getDataInit($uid);
        $c = $this->getDataCount($res);
        $crazyRecursionLimiter = 999;
        $idH = [];
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        // Traverse the records:
        while ($crazyRecursionLimiter > 0 && ($row = $this->getDataNext($res))) {
            $pageUid = ($this->table === 'tx_commerce_categories') ? $row['uid'] : $row['pid'];
            if (!$backendUserUtility->isInWebMount((int)$pageUid)) {
                // Current record is not within web mount => skip it
                continue;
            }

            $a++;
            $crazyRecursionLimiter--;
            $newID = $row['uid'];
            if ($newID == 0) {
                throw new \RuntimeException('Endless recursion detected: TYPO3 has detected an error in the database.
                    Please fix it manually (e.g. using phpMyAdmin) and change the UID of ' . $this->table .
                    ':0 to a new value. See http://forge.typo3.org/issues/16150 to get more information about a
                    possible cause.', 1294586383);
            }
            // Reserve space.
            $this->tree[] = [];
            end($this->tree);
            // Get the key for this space
            $treeKey = key($this->tree);
            // If records should be accumulated, do so
            if ($this->setRecs) {
                $this->recs[(int)$row['uid']] = $row;
            }
            // Accumulate the id of the element in the internal arrays
            $this->ids[] = ($idH[(int)$row['uid']]['uid'] = $row['uid']);
            $this->ids_hierarchy[$depth][] = $row['uid'];
            $this->orig_ids_hierarchy[$depth][] = $row['_ORIG_uid'] ?: $row['uid'];

            // Make a recursive call to the next level
            $nextLevelDepthData = $depthData . '<span class="treeline-icon treeline-icon-'
                . ($a === $c ? 'clear' : 'line') . '"></span>';
            $hasSub = $this->expandNext((int)$newID) && !$row['php_tree_stop'];
            if ($depth > 1 && $hasSub) {
                $nextCount = $this->getTree((int)$newID, $depth - 1, $nextLevelDepthData);
                if (!empty($this->buffer_idH)) {
                    $idH[(int)$row['uid']]['subrow'] = $this->buffer_idH;
                }
                // Set "did expand" flag
                $isOpen = 1;
            } else {
                $nextCount = $this->getCount((int)$newID);
                // Clear "did expand" flag
                $isOpen = 0;
            }
            // Set HTML-icons, if any:
            if ($this->makeHTML) {
                $HTML = $this->PMicon($row, $a, $c, $nextCount, $isOpen) . $this->wrapStop($this->getIcon($row), $row);
            }
            // Finally, add the row/HTML content to the ->tree array in the reserved key.
            $this->tree[$treeKey] = [
                'row' => $row,
                'HTML' => $HTML,
                'invertedDepth' => $depth,
                'depthData' => $depthData,
                'bank' => $this->bank,
                'hasSub' => $nextCount && $hasSub,
                'isFirst' => $a === 1,
                'isLast' => $a === $c,
            ];
        }

        $this->getDataFree($res);
        $this->buffer_idH = $idH;
        return $c;
    }

    /**
     * Getting the tree data: Selecting/Initializing data pointer to items for a certain parent id.
     * For tables: This will make a database query to select all children to "parent"
     * For arrays: This will return key to the ->dataLookup array
     *
     * @param int $parentId parent item id
     *
     * @return mixed Data handle (
     *  Tables: An sql-resource,
     *  arrays: A parentId integer. -1 is returned if there were NO subLevel.
     * )
     * @access private
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
            $db = $this->getDatabaseConnection();
            $where = 'mm.uid_foreign = ' . $db->fullQuoteStr($parentId, $this->table)
                . CoreBackendUtility::deleteClause($this->table)
                . CoreBackendUtility::versioningPlaceholderClause($this->table) . $this->clause;
            return $db->exec_SELECTquery(
                'uid, mm.uid_foreign AS parent_category, title',
                $this->table
                . ' INNER JOIN tx_commerce_categories_parent_category_mm AS mm
                    ON ' . $this->table . '.uid = mm.uid_local',
                $where,
                '',
                $this->table . '.' . $this->orderByFields
            );
        }
    }

    /**
     * Returns the number of records having the parent id, $uid
     *
     * @param int $uid Id to count subitems for
     * @return int
     * @access private
     */
    public function getCount($uid)
    {
        if (is_array($this->data)) {
            $res = $this->getDataInit($uid);
            return $this->getDataCount($res);
        } else {
            $db = $this->getDatabaseConnection();
            $where = 'mm.uid_foreign = ' . $db->fullQuoteStr($uid, $this->table)
                . CoreBackendUtility::deleteClause($this->table)
                . CoreBackendUtility::versioningPlaceholderClause($this->table) . $this->clause;
            return $db->exec_SELECTcountRows(
                'uid',
                $this->table
                . ' INNER JOIN tx_commerce_categories_parent_category_mm AS mm
                    ON ' . $this->table . '.uid = mm.uid_local',
                $where
            );
        }
    }

    /**
     * Create the page navigation tree in HTML
     *
     * @param array|string $treeArr Tree array
     * @return string HTML output.
     */
    public function printTree($treeArr = '')
    {
        $titleLen = (int)$GLOBALS['BE_USER']->uc['titleLen'];
        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }
        $out = '';
        // We need to count the opened <ul>'s every time we dig into another level,
        // so we know how many we have to close when all children are done rendering
        $closeDepth = [];
        foreach ($treeArr as $treeItem) {
            $classAttr = $treeItem['row']['_CSSCLASS'];
            if ($treeItem['isFirst']) {
                $out .= '<ul class="list-tree">';
            }

            // Add CSS classes to the list item
            if ($treeItem['hasSub']) {
                $classAttr .= ' list-tree-control-open';
            }

            $selected = '';
            if ($this->linkParameterProvider->isCurrentlySelectedItem(['uid' => (int)$treeItem['row']['uid']])) {
                $selected = ' bg-success';
                $classAttr .= ' active';
            }
            $urlParameters = $this->linkParameterProvider->getUrlParameters(['pid' => (int)$treeItem['row']['uid']]);
            $aOnClick = 'return jumpToUrl('
                . GeneralUtility::quoteJSvalue(
                    $this->getThisScript() . ltrim(GeneralUtility::implodeArrayForUrl('', $urlParameters), '&')
                )
                . ');';
            $cEbullet = $this->ext_isLinkable($treeItem['row']['uid'])
                ? '<a href="#" class="list-tree-show" onclick="' . htmlspecialchars($aOnClick)
                    . '"><i class="fa fa-caret-square-o-right"></i></a>'
                : '';
            $out .= '
				<li' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group' . $selected . '">
						' . $cEbullet . $treeItem['HTML']
                . $this->wrapTitle(
                    $this->getTitleStr($treeItem['row'], $titleLen),
                    $treeItem['row'],
                    $this->ext_pArrPages
                ) . '
					</span>
				';
            if (!$treeItem['hasSub']) {
                $out .= '</li>';
            }

            // We have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if ($treeItem['isLast']) {
                $closeDepth[$treeItem['invertedDepth']] = 1;
            }
            // If this is the last one and does not have subitems, we need to close
            // the tree as long as the upper levels have last items too
            if ($treeItem['isLast'] && !$treeItem['hasSub']) {
                for ($i = $treeItem['invertedDepth']; $closeDepth[$i] == 1; $i++) {
                    $closeDepth[$i] = 0;
                    $out .= '</ul></li>';
                }
            }
        }
        return '<ul class="list-tree list-tree-root">' . $out . '</ul>';
    }

    /**
     * Returns TRUE if a doktype can be linked.
     *
     * @param int $uid
     *
     * @return bool
     */
    public function ext_isLinkable($uid = 0)
    {
        return $uid > 0;
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $icon HTML string to wrap, probably an image tag.
     * @param string $cmd Command for 'PM' get var
     * @param string $bMark If set, the link will have an anchor point (=$bMark) and a name attribute (=$bMark)
     * @param bool $isOpen
     * @return string Link-wrapped input string
     */
    public function PM_ATagWrap($icon, $cmd, $bMark = '', $isOpen = false)
    {
        $anchor = $bMark ? '#' . $bMark : '';
        $name = $bMark ? ' name=' . $bMark : '';
        $urlParameters = $this->linkParameterProvider->getUrlParameters([]);
        $urlParameters['PM'] = $cmd;
        $aOnClick = 'return jumpToUrl('
            . GeneralUtility::quoteJSvalue(
                $this->getThisScript()
                . ltrim(GeneralUtility::implodeArrayForUrl('', $urlParameters), '&')
            )
            . ',' . GeneralUtility::quoteJSvalue($anchor) . ');';
        return '<a class="list-tree-control ' . ($isOpen ? 'list-tree-control-open' : 'list-tree-control-closed')
            . '" href="#"' . htmlspecialchars($name) . ' onclick="' . htmlspecialchars($aOnClick)
            . '"><i class="fa"></i></a>';
    }

    /**
     * Wrapping the image tag, $icon, for the row, $row
     *
     * @param string $icon The image tag for the icon
     * @param array $row The row for the current element
     * @return string The processed icon input value.
     */
    public function wrapIcon($icon, $row)
    {
        if ($this->ext_showPageId) {
            $icon .= '[' . $row['uid'] . ']&nbsp;';
        }
        return $icon;
    }


    /**
     * Returns an array with the webmounts.
     * If no webmounts, and empty array is returned.
     * NOTICE: Deleted categories WILL NOT be filtered out! So if a mounted category has been deleted
     *         it is STILL coming out as a webmount. This is not checked due to performance.
     *
     * @return array
     */
    protected function returnCategoryMounts()
    {
        $groupData = $this->getBackendUser()->groupData;

        $mountpoints = (string)$groupData['tx_commerce_mountpoints'] != '' ?
            explode(',', $groupData['tx_commerce_mountpoints']) :
            [];

        if (empty($mountpoints) && $this->getBackendUser()->isAdmin()) {
            $mountpoints = [ '0' ];
        }

        return $mountpoints;
    }
}

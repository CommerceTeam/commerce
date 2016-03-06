<?php
namespace CommerceTeam\Commerce\Tree\View;

class CategoryTreeElementCategoryTreeView extends ElementBrowserCategoryTreeView
{
    /**
     * override to use this treeName
     * @var string
     */
    public $treeName = 'browseElementCategories';

    /**
     * @var bool
     */
    public $ajaxCall = false;

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
            return '<a class="list-tree-title" data-value="' . (int)$v['uid'] . '" title="' . $title . '">'
                . $title . '</a>';
        } else {
            return '<span class="list-tree-title text-muted">' . $title . '</span>';
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

            $out .= '
				<li' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group' . $selected . '">
						' . $treeItem['HTML']
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
        return !$this->ajaxCall ? '<ul class="list-tree list-tree-root">' . $out . '</ul>' : $out;
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
        $name = $bMark ? ' name=' . $bMark : '';

        return '<span class="list-tree-control ' . ($isOpen ? 'list-tree-control-open' : 'list-tree-control-closed')
            . '" data-pm="' . $cmd . '"' . htmlspecialchars($name) . '><i class="fa"></i></span>';
    }
}

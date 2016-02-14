<?php
namespace CommerceTeam\Commerce\ViewHelpers;

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
 * TCEforms functions for handling and rendering of trees for group/select
 * elements If we want to display a browseable tree, we need to run the tree
 * in an iframe element. In consequence this means that the display of the
 * browseable tree needs to be generated from an extra script.
 * This is the base class for such a script.
 * The class itself do not render the tree but call tceforms to render the field.
 * In beforehand the TCA config value of treeViewBrowseable will be set to
 * 'iframeContent' to force the right rendering. That means the script do not
 * know anything about trees. It just set parameters and render the field with
 * TCEforms.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\TreelibTceforms
 *
 * @author 2003-2012 Rene Fritz <r.fritz@colorcube.de>
 */
class TreelibTceforms
{
    /**
     * Count rendered tree items - just for frame height calculation.
     *
     * @var int
     */
    public $treeItemC = 0;

    /**
     * Count rendered trees.
     *
     * @var int
     */
    public $treesC = 0;

    /**
     * Rendered trees as HTML.
     *
     * @var string
     */
    protected $treeContent = '';

    /**
     * ItemArray for usage in TCEforms
     * This holds the original values.
     *
     * @var array
     */
    protected $itemArray = array();

    /**
     * ItemArray for usage in TCEforms
     * This holds the processed values with titles/labels.
     *
     * @var array
     */
    protected $itemArrayProcessed = array();

    /**
     * Defines the prefix used for JS code to call the parent window.
     * This is for iframe mode.
     *
     * @var string
     */
    protected $jsParent = '';

    /**
     * Form engine.
     *
     * @var \TYPO3\CMS\Backend\Form\FormEngine
     */
    public $tceforms;

    /**
     * Parameter.
     *
     * @var array
     */
    public $PA;

    /**
     * Table.
     *
     * @var string
     */
    public $table;

    /**
     * Field.
     *
     * @var string
     */
    public $field;

    /**
     * Row.
     *
     * @var array
     */
    public $row;

    /**
     * Config.
     *
     * @var array
     */
    public $config;

    /**
     * Tree browser script.
     *
     * @var string
     */
    public $treeBrowserScript;

    /* Getter / Setter */

    /**
     * Init.
     *
     * @param array $parameter An array with additional configuration options.
     *
     * @return void
     */
    public function init(array $parameter)
    {
        $this->tceforms = &$parameter['pObj'];
        $this->PA = &$parameter;

        $this->table = $parameter['table'];
        $this->field = $parameter['field'];
        $this->row = $parameter['row'];
        $this->config = $parameter['fieldConf']['config'];

        // set currently selected items
        $itemArray = GeneralUtility::trimExplode(',', $this->PA['itemFormElValue'], true);
        $this->setItemArray($itemArray);
    }

    /**
     * Set the selected items.
     *
     * @param array $itemArray Item array
     *
     * @return void
     */
    public function setItemArray(array $itemArray)
    {
        $this->itemArray = $itemArray;
    }

    /**
     * Return the processed aray of selected items.
     *
     * @return array
     *
     * @return void
     */
    public function getItemArrayProcessed()
    {
        return $this->itemArrayProcessed;
    }

    /**
     * Return the count value of selectable items.
     *
     * @return int
     */
    public function getItemCountSelectable()
    {
        return $this->treeItemC;
    }

    /**
     * Return the count value of rendered trees.
     *
     * @return int
     */
    public function getItemCountTrees()
    {
        return $this->treesC;
    }

    /* Rendering */

    /**
     * Renders the category tree for mounts.
     *
     * @param object $browseTree Category Tree
     *
     * @return string the rendered trees (HTML)
     */
    public function renderBrowsableMountTrees($browseTree)
    {
        $this->treeContent = $browseTree->getBrowseableTree();

        return $this->treeContent;
    }

    /* Div-Frame specific stuff */

    /**
     * Returns div HTML code which includes the rendered tree(s).
     *
     * @param string $width  CSS width definition
     * @param string $height CSS height definition
     *
     * @return string HTML content
     */
    public function renderDivBox($width = null, $height = null)
    {
        if ($width == null) {
            list($width, $height) = $this->calcFrameSizeCss();
        }
        $divStyle = 'position:relative; left:0px; top:0px; height:' . $height . '; width:' . $width .
            ';border:solid 1px;overflow:auto;background:#fff;';
        $divFrame = '<div  name="' . $this->PA['itemFormElName'] . '_selTree" style="' . htmlspecialchars($divStyle) .
            '">' . $this->treeContent . '</div>';

            // include function
        $divFrame .= '<script type="text/javascript">
            function jumpTo(id, linkObj, highLightID, script) {
                // We can leave out the "="
                var catUid = id.substr(id.lastIndexOf("=") + 1);
                var text = (linkObj.firstChild) ? linkObj.firstChild.nodeValue : "Unknown";
                // Params (field, value, caption)
                setFormValueFromBrowseWin("' . $this->PA['itemFormElName'] . '", catUid, text);
            }
            </script>';
        $divFrame .= '<script src="' . $this->tceforms->backPath . 'js/tree.js"></script>
            <script type="text/javascript">
            Tree.ajaxID = "CommerceTeam_Commerce_CategoryViewHelper::ajaxExpandCollapseWithoutProduct";
            </script>
        ';

        return $divFrame;
    }

    /* Rendering tools */

    /**
     * Calculate size of the tree frame.
     *
     * @param int $itemCountSelectable Item count selectable
     *
     * @return array Size with array($width, $height)
     */
    public function calcFrameSizeCss($itemCountSelectable = null)
    {
        if ($itemCountSelectable === null) {
            $itemCountSelectable = max(1, $this->treeItemC + $this->treesC + 1);
        }

        $width = '240px';

        $this->config['autoSizeMax'] = max($this->config['autoSizeMax'], 0);
        $height = $this->config['autoSizeMax'] ?
            \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(
                $itemCountSelectable,
                \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->config['size'], 1),
                $this->config['autoSizeMax']
            ) :
            $this->config['size'];

            // hardcoded: 16 is the height of the icons
        $height = ($height * 16) . 'px';

        return array(
            $width,
            $height,
        );
    }

    /* Data tools */

    /**
     * In effect this function returns an array with the preselected item
     * (aka Mountpoints that are already assigned) to the user
     *      [0] => 5|Fernseher
     *  Meta: [0] => $key|$caption.
     *
     * @param object $tree Browsetree Object
     * @param int $userid User UID (this is not NECESSARILY
     *      the UID of the currently logged-in user
     *
     * @return array
     */
    public function processItemArrayForBrowseableTree(&$tree, $userid)
    {
        /**
         * Category mount.
         *
         * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
         */
        $mount = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\CategoryMounts::class);
        $mount->init($userid);

        $preselected = $mount->getMountDataLabeled();

        // Modify the Array - separate the uid and label with a '|'
        $l = count($preselected);

        for ($i = 0; $i < $l; ++$i) {
            $preselected[$i] = implode('|', $preselected[$i]);
        }

        $this->itemArrayProcessed = $preselected;

        return $preselected;
    }

    /**
     * In effect this function returns an array with the preselected item
     * (aka Mountpoints that are already assigned) to the Group
     *    [0] => 5|Fernseher
     *  Meta: [0] => $key|$caption.
     *
     * @param object $tree Browsetree Object
     * @param int $groupuid User UID (this is not NECESSARILY
     *      the UID of the currently logged-in user
     *
     * @return array
     */
    public function processItemArrayForBrowseableTreeGroups(&$tree, $groupuid)
    {
        /**
         * Category mount.
         *
         * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
         */
        $mount = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\CategoryMounts::class);
        $mount->initByGroup($groupuid);

        $preselected = $mount->getMountDataLabeled();

        // Modify the Array - separate the uid and label with a '|'
        $l = count($preselected);

        for ($i = 0; $i < $l; ++$i) {
            $preselected[$i] = implode('|', $preselected[$i]);
        }

        $this->itemArrayProcessed = $preselected;

        return $preselected;
    }

    /**
     * In effect this function returns an array with the preselected item
     * (aka Parent Categories that are already assigned)
     *      [0] => 5|Fernseher
     *  Meta: [0] => $key|$caption.
     *
     * @param object $tree Browsetree Object
     * @param int $catUid Cat UID
     *
     * @return array
     */
    public function processItemArrayForBrowseableTreePCategory(&$tree, $catUid)
    {
        if (!is_numeric($catUid)) {
            return array();
        }

        // Get the parent Categories for the cat uid
        /**
         * Category.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\Category $category
         */
        $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $catUid);
        $category->loadData();
        $parent = $category->getParentCategories();

        $this->itemArrayProcessed = array();

        /**
         * Category mounts.
         *
         * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
         */
        $mount = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\CategoryMounts::class);
        $mount->init((int) $this->getBackendUser()->user['uid']);

        if (is_array($parent)) {
            for ($i = 0, $l = count($parent); $i < $l; ++$i) {
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = &$parent[$i];
                $category->loadData();

                // Separate Key and Title with a |
                $title = ($category->isPermissionSet('show') && $mount->isInCommerceMounts($category->getUid())) ?
                    $category->getTitle() :
                    $this->getLanguageService()->sL(
                        'LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xlf:leaf.restrictedAccess',
                        1
                    );
                $this->itemArrayProcessed[] = $category->getUid() . '|' . $title;
            }
        }

        return $this->itemArrayProcessed;
    }

    /**
     * In effect this function returns an array with the preselected item
     * (aka Categories that are already assigned to the plugin)
     *      [0] => 5|Fernseher
     *  Meta: [0] => $key|$caption.
     *
     * @param object $tree Browsetree Object
     * @param int $catUid Cat UID
     *
     * @return array
     */
    public function processItemArrayForBrowseableTreeCategory(&$tree, $catUid)
    {
        if (!is_numeric($catUid)) {
            return array();
        }

        /**
         * Category.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\Category $category
         */
        $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $catUid);
        $category->loadData();

        $this->itemArrayProcessed = array();

        /**
         * Category mounts.
         *
         * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
         */
        $mount = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\CategoryMounts::class);
        $mount->init((int) $this->getBackendUser()->user['uid']);

        // Separate Key and Title with a |
        $title = ($category->isPermissionSet('show') && $mount->isInCommerceMounts($category->getUid())) ?
            $category->getTitle() :
            $this->getLanguageService()->sL(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xlf:leaf.restrictedAccess',
                1
            );
        $this->itemArrayProcessed = array($category->getUid() . '|' . $title);

        return $this->itemArrayProcessed;
    }

    /**
     * In effect this function returns an array with the preselected item
     * (aka Parent Categories that are already assigned to the product!)
     *      [0] => 5|Fernseher
     *  Meta: [0] => $key|$caption.
     *
     * @param object $tree Browsetree Object
     * @param int $uid Product UID
     *
     * @return array
     */
    public function processItemArrayForBrowseableTreeProduct(&$tree, $uid)
    {
        if (!is_numeric($uid)) {
            return array();
        }

        // Get the parent Categories for the cat uid
        /**
         * Product.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\Product $product
         */
        $product = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Product::class, $uid);
        $product->loadData();

        // read parent categories from the live product
        if ($product->getT3verOid() != 0) {
            $product->init($product->getT3verOid());
            $product->loadData();
        }

        $parentCategories = $product->getParentCategories();

        // Load each category and push into the array
        $cat = null;
        $itemArray = array();

        $parentCategoryCount = count($parentCategories);
        for ($i = 0, $l = $parentCategoryCount; $i < $l; ++$i) {
            /**
             * Category.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Category $category
             */
            $category = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Model\Category::class,
                $parentCategories[$i]
            );
            $category->loadData();

            $title = ($category->isPermissionSet('show')) ?
                $category->getTitle() :
                $this->getLanguageService()->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_treelib.xlf:leaf.restrictedAccess',
                    1
                );
            // Separate Key and Title with a |
            $itemArray[] = $category->getUid() . '|' . $title;
        }

        $this->itemArrayProcessed = $itemArray;

        return $this->itemArrayProcessed;
    }

    /**
     * Extracts the ID and the Title from which every item we have.
     *
     * @param string $itemFormElValue Item element (tx_commerce_article_42)
     *
     * @return array
     */
    public function processItemArrayForBrowseableTreeDefault($itemFormElValue)
    {
        $items = GeneralUtility::trimExplode(',', $itemFormElValue, true);

        $itemArray = array();

        // Walk the records we have.
        foreach ($items as $value) {
            // Get parts.
            $parts = GeneralUtility::trimExplode('_', $value, true);

            $uid = array_pop($parts);
            $table = implode('_', $parts);

            // Product
            if ('tx_commerce_products' == $table) {
                /**
                 * Product.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Product $product
                 */
                $product = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Product::class, $uid);
                $product->loadData();

                $itemArray[] = $value . '|' . $product->getTitle();
            } elseif ('tx_commerce_articles' == $table) {
                /**
                 * Article.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Article $article
                 */
                $article = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Article::class, $uid);
                $article->loadData();

                $itemArray[] = $value . '|' . $article->getTitle();
            } elseif ('tx_commerce_categories' == $table) {
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $uid);
                $category->loadData();

                $itemArray[] = $value . '|' . $category->getTitle();
            } else {
                // Hook:
                $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks(
                    'ViewHelpers/TreelibTceforms',
                    'processItemArrayForBrowseableTreeDefault'
                );
                foreach ($hooks as $hook) {
                    if (method_exists($hook, 'processDefault')) {
                        $itemArray[] = $hook->processDefault($itemFormElValue, $table, $uid);
                    }
                }
            }
        }

        return $itemArray;
    }

    /**
     * Extracts the id's from $PA['itemFormElValue'] in standard TCE format.
     *
     * @param string $itemFormElValue Item element
     *
     * @return array
     */
    public function getItemFormElValueIdArr($itemFormElValue)
    {
        $out = array();
        $items = GeneralUtility::trimExplode(',', $itemFormElValue, true);
        foreach ($items as $value) {
            $values = GeneralUtility::trimExplode('|', $value, true);
            $out[] = $values[0];
        }

        return $out;
    }

    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

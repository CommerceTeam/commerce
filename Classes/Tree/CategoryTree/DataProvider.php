<?php
namespace CommerceTeam\Commerce\Tree\CategoryTree;

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

use CommerceTeam\Commerce\Factory\HookFactory;
use CommerceTeam\Commerce\Utility\BackendUserUtility;
use TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Page tree data provider.
 */
class DataProvider extends \TYPO3\CMS\Backend\Tree\AbstractTreeDataProvider
{
    /**
     * Node limit that should be loaded for this request per mount
     *
     * @var int
     */
    protected $nodeLimit = 0;

    /**
     * Current amount of nodes
     *
     * @var int
     */
    protected $nodeCounter = 0;

    /**
     * TRUE to show the path of each mountpoint in the tree
     *
     * @var bool
     */
    protected $showRootlineAboveMounts = false;

    /**
     * Hidden Records
     *
     * @var array<string>
     */
    protected $hiddenRecords = array();

    /**
     * Process collection hook objects
     *
     * @var array<\TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface>
     */
    protected $processCollectionHookObjects = array();

    /**
     * Constructor
     *
     * @param int $nodeLimit (optional)
     */
    public function __construct($nodeLimit = null)
    {
        if ($nodeLimit === null) {
            $nodeLimit = $GLOBALS['TYPO3_CONF_VARS']['BE']['pageTree']['preloadLimit'];
        }
        $this->nodeLimit = abs((int)$nodeLimit);

        $this->showRootlineAboveMounts = $this->getBackendUserAuthentication()->getTSConfigVal(
            'options.pageTree.showPathAboveMounts'
        );

        $this->processCollectionHookObjects = HookFactory::getHooks('Tree/CategoryTree/DataProvider', 'construct');
    }

    /**
     * Returns the root node.
     *
     * @return CategoryNode the root node
     */
    public function getRoot()
    {
        /** @var $node CategoryNode */
        $node = GeneralUtility::makeInstance(CategoryNode::class);
        $node->setId('root');
        $node->setExpanded(true);
        return $node;
    }

    /**
     * Empty method to satisfy abstract parent
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node Node
     * @param int $mountPoint Mount point
     * @param int $level Internally used variable as a recursion limiter
     *
     * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    public function getNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $mountPoint = 0, $level = 0)
    {
    }

    /**
     * Fetches the sub-nodes of the given node
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|CategoryNode $node
     * @param int $mountPoint
     * @param int $level internally used variable as a recursion limiter
     * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    public function getCategoryNodes(CategoryNode $node, $mountPoint = 0, $level = 0)
    {
        /** @var $nodeCollection PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        if ($level >= 99 || $node->getStopPageTree()) {
            return $nodeCollection;
        }
        $isVirtualRootNode = false;
        $subCategories = $this->getSubCategories($node->getId());
        // check if fetching subpages the "root"-page
        // and in case of a virtual root return the mountpoints as virtual "subpages"
        if ((int)$node->getId() === 0) {
            // check no temporary mountpoint is used
            if (!(int)$this->getBackendUserAuthentication()->uc['pageTree_temporaryMountPoint']) {
                $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
                $mountPoints = array_map('intval', $backendUserUtility->returnWebmounts());
                $mountPoints = array_unique($mountPoints);
                if (!in_array(0, $mountPoints)) {
                    // using a virtual root node
                    // so then return the mount points here as "subpages" of the first node
                    $isVirtualRootNode = true;
                    $subCategories = array();
                    foreach ($mountPoints as $webMountPoint) {
                        $subCategories[] = array(
                            'uid' => $webMountPoint,
                            'isMountPoint' => true
                        );
                    }
                }
            }
        }
        if (is_array($subCategories) && !empty($subCategories)) {
            foreach ($subCategories as $subCategory) {
                if (in_array($subCategory['uid'], $this->hiddenRecords)) {
                    continue;
                }
                // must be calculated above getRecordWithWorkspaceOverlay,
                // because the information is lost otherwise
                $isMountPoint = $subCategory['isMountPoint'] === true;
                if ($isVirtualRootNode) {
                    $mountPoint = (int)$subCategory['uid'];
                }
                $subCategory = BackendUtility::getRecordWSOL(
                    'tx_commerce_categories',
                    $subCategory['uid'],
                    '*',
                    '',
                    true,
                    true
                );
                if (!$subCategory) {
                    continue;
                }
                $subNode = Commands::getCategoryNode($subCategory, $mountPoint);
                $subNode->setIsMountPoint($isMountPoint);
                if ($isMountPoint && $this->showRootlineAboveMounts) {
                    $rootline = Commands::getMountPointPath($subCategory['uid']);
                    $subNode->setReadableRootline($rootline);
                }
                if ($this->nodeCounter < $this->nodeLimit) {
                    $childNodes = $this->getCategoryNodes($subNode, $mountPoint, $level + 1);
                    // @todo add product child nodes
                    $subNode->setChildNodes($childNodes);
                    $this->nodeCounter += $childNodes->count();
                } else {
                    $subNode->setLeaf(!$this->hasNodeSubPages($subNode->getId()));
                }
                if (!$this->getBackendUserAuthentication()->isAdmin() && (int)$subCategory['editlock'] === 1) {
                    $subNode->setLabelIsEditable(false);
                }
                $nodeCollection->append($subNode);
            }
        }
        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessGetNodes($node, $mountPoint, $level, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Wrapper method for \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL
     *
     * @param int $uid The page id
     * @param bool $unsetMovePointers Whether to unset move pointers
     * @return array
     */
    protected function getRecordWithWorkspaceOverlay($uid, $unsetMovePointers = false)
    {
        return BackendUtility::getRecordWSOL('pages', $uid, '*', '', true, $unsetMovePointers);
    }

    /**
     * Returns a node collection of filtered nodes
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|CategoryNode $node
     * @param string $searchFilter
     * @param int $mountPoint
     * @return PagetreeNodeCollection the filtered nodes
     */
    public function getFilteredNodes(CategoryNode $node, $searchFilter, $mountPoint = 0)
    {
        /** @var $nodeCollection PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        $records = $this->getSubCategories(-1, $searchFilter);
        if (!is_array($records) || empty($records)) {
            return $nodeCollection;
        } elseif (count($records) > 500) {
            return $nodeCollection;
        }
        // check no temporary mountpoint is used
        $mountPoints = (int)$this->getBackendUserAuthentication()->uc['pageTree_temporaryMountPoint'];
        if (!$mountPoints) {
            $mountPoints = array_map('intval', $this->getBackendUserAuthentication()->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
        } else {
            $mountPoints = array($mountPoints);
        }
        $isNumericSearchFilter = is_numeric($searchFilter) && $searchFilter > 0;
        $searchFilterQuoted = preg_quote($searchFilter, '/');
        $nodeId = (int)$node->getId();
        $processedRecordIds = array();
        foreach ($records as $record) {
            if ((int)$record['t3ver_wsid'] !== (int)$this->getBackendUserAuthentication()->workspace
                && (int)$record['t3ver_wsid'] !== 0
            ) {
                continue;
            }
            $liveVersion = BackendUtility::getLiveVersionOfRecord('pages', $record['uid'], 'uid');
            if ($liveVersion !== null) {
                $record = $liveVersion;
            }

            $record = Commands::getNodeRecord($record['uid'], false);
            if ((int)$record['pid'] === -1
                || in_array($record['uid'], $this->hiddenRecords)
                || in_array($record['uid'], $processedRecordIds)
            ) {
                continue;
            }
            $processedRecordIds[] = $record['uid'];

            $rootline = BackendUtility::BEgetRootLine(
                $record['uid'],
                '',
                $this->getBackendUserAuthentication()->workspace != 0
            );
            $rootline = array_reverse($rootline);
            if (!in_array(0, $mountPoints, true)) {
                $isInsideMountPoints = false;
                foreach ($rootline as $rootlineElement) {
                    if (in_array((int)$rootlineElement['uid'], $mountPoints, true)) {
                        $isInsideMountPoints = true;
                        break;
                    }
                }
                if (!$isInsideMountPoints) {
                    continue;
                }
            }
            $reference = $nodeCollection;
            $inFilteredRootline = false;
            $amountOfRootlineElements = count($rootline);
            for ($i = 0; $i < $amountOfRootlineElements; ++$i) {
                $rootlineElement = $rootline[$i];
                $rootlineElement['uid'] = (int)$rootlineElement['uid'];
                $isInWebMount = (int)$this->getBackendUserAuthentication()->isInWebMount($rootlineElement['uid']);
                if (!$isInWebMount
                    || ($rootlineElement['uid'] === (int)$mountPoints[0]
                        && $rootlineElement['uid'] !== $isInWebMount)
                ) {
                    continue;
                }
                if ((int)$rootlineElement['pid'] === $nodeId
                    || $rootlineElement['uid'] === $nodeId
                    || ($rootlineElement['uid'] === $isInWebMount
                        && in_array($rootlineElement['uid'], $mountPoints, true))
                ) {
                    $inFilteredRootline = true;
                }
                if (!$inFilteredRootline || $rootlineElement['uid'] === $mountPoint) {
                    continue;
                }
                $rootlineElement = Commands::getNodeRecord($rootlineElement['uid'], false);
                $ident = (int)$rootlineElement['sorting'] . (int)$rootlineElement['uid'];
                if ($reference && $reference->offsetExists($ident)) {
                    /** @var $refNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
                    $refNode = $reference->offsetGet($ident);
                    $refNode->setExpanded(true);
                    $refNode->setLeaf(false);
                    $reference = $refNode->getChildNodes();
                    if ($reference == null) {
                        $reference = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
                        $refNode->setChildNodes($reference);
                    }
                } else {
                    $refNode = Commands::getCategoryNode($rootlineElement, $mountPoint);
                    $replacement = '<span class="typo3-pagetree-filteringTree-highlight">$1</span>';
                    if ($isNumericSearchFilter && (int)$rootlineElement['uid'] === (int)$searchFilter) {
                        $text = str_replace('$1', $refNode->getText(), $replacement);
                    } else {
                        $text = preg_replace('/(' . $searchFilterQuoted . ')/i', $replacement, $refNode->getText());
                    }
                    $refNode->setText(
                        $text,
                        $refNode->getTextSourceField(),
                        $refNode->getPrefix(),
                        $refNode->getSuffix()
                    );
                    /** @var $childCollection PagetreeNodeCollection */
                    $childCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
                    if ($i + 1 >= $amountOfRootlineElements) {
                        $childNodes = $this->getNodes($refNode, $mountPoint);
                        foreach ($childNodes as $childNode) {
                            /** @var $childNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
                            $childRecord = $childNode->getRecord();
                            $childIdent = (int)$childRecord['sorting'] . (int)$childRecord['uid'];
                            $childCollection->offsetSet($childIdent, $childNode);
                        }
                        $refNode->setChildNodes($childNodes);
                    }
                    $refNode->setChildNodes($childCollection);
                    $reference->offsetSet($ident, $refNode);
                    $reference->ksort();
                    $reference = $childCollection;
                }
            }
        }
        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Returns the page tree mounts for the current user
     *
     * Note: If you add the search filter parameter, the nodes will be filtered by this string.
     *
     * @param string $searchFilter
     * @return PagetreeNodeCollection
     */
    public function getTreeMounts($searchFilter = '')
    {
        /** @var $nodeCollection PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        $isTemporaryMountPoint = false;
        $rootNodeIsVirtual = false;
        $mountPoints = GeneralUtility::intExplode(
            ',',
            $this->getBackendUserAuthentication()->user['tx_commerce_mountpoints']
        );
        if ($this->getBackendUserAuthentication()->isAdmin()) {
            $mountPoints = array_merge([0], $mountPoints);
        }
        $mountPoints = array_unique($mountPoints);
        if (empty($mountPoints)) {
            return $nodeCollection;
        }

        foreach ($mountPoints as $mountPoint) {
            if ($mountPoint === 0) {
                $sitename = 'Commerce';
                $record = array(
                    'uid' => 0,
                    'title' => $sitename
                );
                $subNode = Commands::getCategoryNode($record);
                $subNode->setLabelIsEditable(false);
                if ($rootNodeIsVirtual) {
                    $subNode->setType('virtual_root');
                    $subNode->setIsDropTarget(false);
                } else {
                    $subNode->setType('category_root');
                    $subNode->setIsDropTarget(true);
                }
            } else {
                if (in_array($mountPoint, $this->hiddenRecords)) {
                    continue;
                }
                $record = BackendUtility::getRecordWSOL('tx_commerce_categories', $mountPoint);
                if (!$record) {
                    continue;
                }
                $subNode = Commands::getCategoryNode($record, $mountPoint);
                if ($this->showRootlineAboveMounts && !$isTemporaryMountPoint) {
                    $rootline = Commands::getMountPointPath($record['uid']);
                    $subNode->setReadableRootline($rootline);
                }
            }
            if (count($mountPoints) <= 1) {
                $subNode->setExpanded(true);
                $subNode->setCls('commerce-categorytree-node-notExpandable');
            }
            $subNode->setIsMountPoint(true);
            $subNode->setDraggable(false);
            if ($searchFilter === '') {
                $childNodes = $this->getCategoryNodes($subNode, $mountPoint);
            } else {
                $childNodes = $this->getFilteredNodes($subNode, $searchFilter, $mountPoint);
                $subNode->setExpanded(true);
            }
            $subNode->setChildNodes($childNodes);
            $nodeCollection->append($subNode);
        }
        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessGetTreeMounts($searchFilter, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Returns the where clause for fetching pages
     *
     * @param int $id
     * @param string $searchFilter
     * @return string
     */
    protected function getWhereClause($id, $searchFilter = '')
    {
        $where = $this->getBackendUserAuthentication()->getPagePermsClause(1) . BackendUtility::deleteClause('pages')
            . BackendUtility::versioningPlaceholderClause('pages');
        if (is_numeric($id) && $id >= 0) {
            $where .= ' AND pid= ' . $this->getDatabaseConnection()->fullQuoteStr((int)$id, 'pages');
        }

        $excludedDoktypes = $this->getBackendUserAuthentication()->getTSConfigVal('options.pageTree.excludeDoktypes');
        if (!empty($excludedDoktypes)) {
            $excludedDoktypes = $this->getDatabaseConnection()->fullQuoteArray(
                GeneralUtility::intExplode(',', $excludedDoktypes),
                'pages'
            );
            $where .= ' AND doktype NOT IN (' . implode(',', $excludedDoktypes) . ')';
        }

        if ($searchFilter !== '') {
            $searchWhere = '';
            if (is_numeric($searchFilter) && $searchFilter > 0) {
                $searchWhere .= 'uid = ' . (int)$searchFilter . ' OR ';
            }
            $searchFilter = $this->getDatabaseConnection()->fullQuoteStr('%' . $searchFilter . '%', 'pages');
            $useNavTitle = $this->getBackendUserAuthentication()->getTSConfigVal('options.pageTree.showNavTitle');
            $useAlias = $this->getBackendUserAuthentication()->getTSConfigVal('options.pageTree.searchInAlias');

            $searchWhereAlias = '';
            if ($useAlias) {
                $searchWhereAlias = ' OR alias LIKE ' . $searchFilter;
            }

            if ($useNavTitle) {
                $searchWhere .= '(nav_title LIKE ' . $searchFilter .
                ' OR (nav_title = "" AND title LIKE ' . $searchFilter . ')' . $searchWhereAlias . ')';
            } else {
                $searchWhere .= 'title LIKE ' . $searchFilter . $searchWhereAlias;
            }

            $where .= ' AND (' . $searchWhere . ')';
        }
        return $where;
    }

    /**
     * Returns the where clause for fetching pages
     *
     * @param int $id Page id
     * @param string $searchFilter Search filter
     *
     * @return string
     */
    protected function getCategoryWhereClause($id, $searchFilter = '')
    {
        $where = \CommerceTeam\Commerce\Utility\BackendUtility::getCategoryPermsClause(1) .
            BackendUtility::deleteClause('tx_commerce_categories') .
            BackendUtility::versioningPlaceholderClause('tx_commerce_categories');
        if (is_numeric($id) && $id >= 0) {
            $where .= ' AND uid_foreign = ' . (int) $id;
        }

        if ($searchFilter !== '') {
            $searchWhere = '';
            if (is_numeric($searchFilter) && $searchFilter > 0) {
                $searchWhere .= 'uid = ' . (int) $searchFilter . ' OR ';
            }

            $searchFilter = $this->getDatabaseConnection()->fullQuoteStr(
                '%' . $searchFilter . '%',
                'tx_commerce_categories'
            );
            $searchWhere .= 'title LIKE ' . $searchFilter;

            $where .= ' AND (' . $searchWhere . ')';
        }

        return $where;
    }

    /**
     * Returns all sub-pages of a given id
     *
     * @param int $id
     * @param string $searchFilter
     * @return array
     */
    protected function getSubCategories($id, $searchFilter = '')
    {
        $where = $this->getCategoryWhereClause($id, $searchFilter);
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, t3ver_wsid',
            'tx_commerce_categories
                INNER JOIN tx_commerce_categories_parent_category_mm ON
                    tx_commerce_categories.uid = tx_commerce_categories_parent_category_mm.uid_local',
            $where,
            '',
            'tx_commerce_categories.sorting',
            '',
            'uid'
        );
    }

    /**
     * Returns TRUE if the node has child's
     *
     * @param int $id
     * @return bool
     */
    protected function hasNodeSubPages($id)
    {
        $where = $this->getWhereClause($id);
        $subpage = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            'pages',
            $where,
            '',
            'sorting'
        );
        $returnValue = true;
        if (!$subpage['uid']) {
            $returnValue = false;
        }
        return $returnValue;
    }


    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get backend user authentication
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}

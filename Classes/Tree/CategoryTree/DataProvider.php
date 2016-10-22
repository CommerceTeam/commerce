<?php
namespace CommerceTeam\Commerce\Tree\CategoryTree;

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
    protected $nodeLimit = 100;

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
    protected $hiddenRecords = [];

    /**
     * Process collection hook objects
     *
     * @var array<\TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface>
     */
    protected $processCollectionHookObjects = [];

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
     * @return void
     */
    public function getNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $mountPoint = 0, $level = 0)
    {
    }

    /**
     * Fetches the sub-nodes of the given node
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|CategoryNode|ProductNode $node
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
        $subCategories = $this->getCategories($node->getId());
        // check if fetching subpages the "root"-page
        // and in case of a virtual root return the mountpoints as virtual "subpages"
        if ((int)$node->getId() === 0) {
            $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
            $mountPoints = array_map('intval', $backendUserUtility->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
            if (!in_array(0, $mountPoints)) {
                // using a virtual root node
                // so then return the mount points here as "subpages" of the first node
                $isVirtualRootNode = true;
                $subCategories = [];
                foreach ($mountPoints as $webMountPoint) {
                    $subCategories[] = [
                        'uid' => $webMountPoint,
                        'isMountPoint' => true
                    ];
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
                $subCategory = Commands::getNodeRecord('tx_commerce_categories', $subCategory['uid']);
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
                    $subNode->setChildNodes($childNodes);
                    $this->nodeCounter += $childNodes->count();
                } else {
                    $hasNodeSubCategories = !$this->hasNodeSubCategories($subNode->getId());
                    // In permission module only categories should be used
                    if ((!isset($_GET['namespace']) || $_GET['namespace'] != 'TYPO3.Components.PermissionTree')) {
                        $hasNodeSubCategories = $hasNodeSubCategories && !$this->hasNodeSubProducts($subNode->getId());
                    }
                    $subNode->setLeaf($hasNodeSubCategories);
                }
                if (!$this->getBackendUserAuthentication()->isAdmin() && (int)$subCategory['editlock'] === 1) {
                    $subNode->setLabelIsEditable(false);
                }
                $nodeCollection->append($subNode);
            }
        }

        // its possible to have products on the same level as categries even
        // even if nested categories are available
        // In permission module only categories should be used
        if ((!isset($_GET['namespace']) || $_GET['namespace'] != 'TYPO3.Components.PermissionTree')) {
            $nodeCollection = $this->getProductNodes($node, $mountPoint, $level, $nodeCollection);
        }

        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessGetNodes($node, $mountPoint, $level, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Fetches the sub-nodes of the given node
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|CategoryNode $node Node
     * @param int $mountPoint Mount point
     * @param int $level Internally used variable as a recursion limiter
     * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $nodeCollection Node Collection
     *
     * @return PagetreeNodeCollection
     */
    public function getProductNodes(CategoryNode $node, $mountPoint = 0, $level = 0, $nodeCollection = null)
    {
        if (is_null($nodeCollection)) {
            /** @var $nodeCollection PagetreeNodeCollection */
            $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        }
        if ($level >= 99 || $node->getStopPageTree()) {
            return $nodeCollection;
        }
        $products = $this->getProducts($node->getId());
        if (is_array($products) && !empty($products)) {
            foreach ($products as $product) {
                $product = BackendUtility::getRecordWSOL('tx_commerce_products', $product['uid'], '*', '', true, true);
                if (!$product) {
                    continue;
                }
                $product['category'] = $node->getId();
                $subNode = Commands::getProductNode($product, $mountPoint);
                if ($this->nodeCounter < $this->nodeLimit) {
                    $childNodes = $this->getArticleNodes($subNode, $nodeCollection);
                    $subNode->setChildNodes($childNodes);
                    $this->nodeCounter += $childNodes->count();
                } else {
                    $subNode->setLeaf(!$this->hasNodeSubArticles($subNode->getId()));
                }
                if (!$this->getBackendUserAuthentication()->isAdmin() && (int)$product['editlock'] === 1) {
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
     * Fetches the sub-nodes of the given node
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|ProductNode $node Node
     * @param int $mountPoint Mount point
     * @param int $level Internally used variable as a recursion limiter
     *
     * @return PagetreeNodeCollection
     */
    public function getArticleNodes(ProductNode $node, $mountPoint = 0, $level = 0)
    {
        /** @var $nodeCollection PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        if ($level >= 99) {
            return $nodeCollection;
        }
        $articles = $this->getArticles($node->getId());
        if (is_array($articles) && !empty($articles)) {
            foreach ($articles as $article) {
                $article = BackendUtility::getRecordWSOL('tx_commerce_articles', $article['uid'], '*', '', true, true);
                if (!$article) {
                    continue;
                }
                $article['product'] = $node->getId();
                $article['category'] = $node->getCategory();
                $articleNode = Commands::getArticleNode($article);
                $articleNode->setLeaf(true);
                $nodeCollection->append($articleNode);
            }
        }

        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessGetNodes($node, $mountPoint, $level, $nodeCollection);
        }
        return $nodeCollection;
    }


    /**
     * Returns a node collection of filtered nodes
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|CategoryNode $node
     * @param string $searchFilter
     * @param int $mountPoint
     * @return PagetreeNodeCollection the filtered nodes
     */
    public function getFilteredCategoryNodes(CategoryNode $node, $searchFilter, $mountPoint = 0)
    {
        /** @var $nodeCollection PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        $subCategories = $this->getCategories(-1, $searchFilter);
        if (!is_array($subCategories) || empty($subCategories)) {
            return $nodeCollection;
        } elseif (count($subCategories) > 500) {
            return $nodeCollection;
        }
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        $mountPoints = array_map('intval', $backendUserUtility->returnWebmounts());
        $mountPoints = array_unique($mountPoints);

        $isNumericSearchFilter = is_numeric($searchFilter) && $searchFilter > 0;
        $searchFilterQuoted = preg_quote($searchFilter, '/');
        $nodeId = (int)$node->getId();
        $processedRecordIds = [];
        foreach ($subCategories as $subCategory) {
            if ((int)$subCategory['t3ver_wsid'] !== (int)$this->getBackendUserAuthentication()->workspace
                && (int)$subCategory['t3ver_wsid'] !== 0
            ) {
                continue;
            }
            $liveVersion = BackendUtility::getLiveVersionOfRecord('tx_commerce_categories', $subCategory['uid'], 'uid');
            if ($liveVersion !== null) {
                $subCategory = $liveVersion;
            }

            $subCategory = Commands::getNodeRecord('tx_commerce_categories', $subCategory['uid'], false);
            if ((int)$subCategory['pid'] === -1
                || in_array($subCategory['uid'], $this->hiddenRecords)
                || in_array($subCategory['uid'], $processedRecordIds)
            ) {
                continue;
            }
            $processedRecordIds[] = $subCategory['uid'];

            $rootline = \CommerceTeam\Commerce\Utility\BackendUtility::BEgetRootLine(
                $subCategory['uid'],
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
                $isInWebMount = (int)$backendUserUtility->isInWebMount($rootlineElement['uid']);
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
                $rootlineElement = Commands::getNodeRecord('tx_commerce_categories', $rootlineElement['uid'], false);
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
                    $replacement = '<span class="commerce-categorytree-filteringTree-highlight">$1</span>';
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
                        $childNodes = $this->getFilteredProductNodes($refNode, $searchFilter, $mountPoint);
                        $childFound = false;
                        foreach ($childNodes as $childNode) {
                            /** @var $childNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
                            $childRecord = $childNode->getRecord();
                            $childIdent = (int)$childRecord['sorting'] . (int)$childRecord['uid'];
                            $childCollection->offsetSet($childIdent, $childNode);
                            $childFound = true;
                        }
                        $refNode->setChildNodes($childNodes);
                        if ($childFound) {
                            $refNode->setExpanded(true);
                        }
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
     * Returns a node collection of filtered nodes
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|CategoryNode $node
     * @param string $searchFilter
     * @param int $mountPoint
     * @return PagetreeNodeCollection the filtered nodes
     */
    public function getFilteredProductNodes(CategoryNode $node, $searchFilter, $mountPoint = 0)
    {
        /** @var $nodeCollection PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        $records = $this->getProducts($node->getId(), $searchFilter);
        if (!is_array($records) || empty($records)) {
            return $nodeCollection;
        } elseif (count($records) > 500) {
            return $nodeCollection;
        }
        $isNumericSearchFilter = is_numeric($searchFilter) && $searchFilter > 0;
        $searchFilterQuoted = preg_quote($searchFilter, '/');
        $processedRecordIds = [];
        foreach ($records as $productRecord) {
            if ((int)$productRecord['t3ver_wsid'] !== (int)$this->getBackendUserAuthentication()->workspace
                && (int)$productRecord['t3ver_wsid'] !== 0
            ) {
                continue;
            }
            $liveVersion = BackendUtility::getLiveVersionOfRecord('tx_commerce_products', $productRecord['uid'], 'uid');
            if ($liveVersion !== null) {
                $productRecord = $liveVersion;
            }

            $productRecord = Commands::getNodeRecord('tx_commerce_products', $productRecord['uid'], false);
            if ((int)$productRecord['pid'] === -1
                || in_array($productRecord['uid'], $this->hiddenRecords)
                || in_array($productRecord['uid'], $processedRecordIds)
            ) {
                continue;
            }
            $processedRecordIds[] = $productRecord['uid'];
            $productRecord['category'] = $node->getId();

            $reference = $nodeCollection;

            $ident = (int)$productRecord['sorting'] . (int)$productRecord['uid'];
            $refNode = Commands::getProductNode($productRecord, $mountPoint);
            $replacement = '<span class="commerce-categorytree-filteringTree-highlight">$1</span>';
            if ($isNumericSearchFilter && (int)$productRecord['uid'] === (int)$searchFilter) {
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
            $childNodes = $this->getFilteredArticleNodes($refNode, $searchFilter, $mountPoint);
            $childFound = false;
            foreach ($childNodes as $childNode) {
                /** @var $childNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
                $childRecord = $childNode->getRecord();
                $childIdent = (int)$childRecord['sorting'] . (int)$childRecord['uid'];
                $childCollection->offsetSet($childIdent, $childNode);
                $childFound = true;
            }
            $refNode->setChildNodes($childNodes);
            if ($childFound) {
                $refNode->setExpanded(true);
            }

            $refNode->setChildNodes($childCollection);
            $reference->offsetSet($ident, $refNode);
            $reference->ksort();
        }
        foreach ($this->processCollectionHookObjects as $hookObject) {
            /** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
            $hookObject->postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection);
        }
        return $nodeCollection;
    }

    /**
     * Returns a node collection of filtered nodes
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode|ProductNode $node
     * @param string $searchFilter
     * @param int $mountPoint
     * @return PagetreeNodeCollection the filtered nodes
     */
    public function getFilteredArticleNodes(ProductNode $node, $searchFilter, $mountPoint = 0)
    {
        /** @var $nodeCollection PagetreeNodeCollection */
        $nodeCollection = GeneralUtility::makeInstance(PagetreeNodeCollection::class);
        $records = $this->getArticles($node->getId(), $searchFilter);
        if (!is_array($records) || empty($records)) {
            return $nodeCollection;
        } elseif (count($records) > 500) {
            return $nodeCollection;
        }
        $isNumericSearchFilter = is_numeric($searchFilter) && $searchFilter > 0;
        $searchFilterQuoted = preg_quote($searchFilter, '/');
        $processedRecordIds = [];
        foreach ($records as $articleRecord) {
            if ((int)$articleRecord['t3ver_wsid'] !== (int)$this->getBackendUserAuthentication()->workspace
                && (int)$articleRecord['t3ver_wsid'] !== 0
            ) {
                continue;
            }
            $liveVersion = BackendUtility::getLiveVersionOfRecord('tx_commerce_articles', $articleRecord['uid'], 'uid');
            if ($liveVersion !== null) {
                $articleRecord = $liveVersion;
            }

            $articleRecord = Commands::getNodeRecord('tx_commerce_articles', $articleRecord['uid'], false);
            if ((int)$articleRecord['pid'] === -1
                || in_array($articleRecord['uid'], $this->hiddenRecords)
                || in_array($articleRecord['uid'], $processedRecordIds)
            ) {
                continue;
            }
            $processedRecordIds[] = $articleRecord['uid'];
            $articleRecord['product'] = $node->getId();
            $articleRecord['category'] = $node->getCategory();

            $reference = $nodeCollection;

            $ident = (int)$articleRecord['sorting'] . (int)$articleRecord['uid'];
            $refNode = Commands::getArticleNode($articleRecord);
            $replacement = '<span class="commerce-categorytree-filteringTree-highlight">$1</span>';
            if ($isNumericSearchFilter && (int)$articleRecord['uid'] === (int)$searchFilter) {
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
            $reference->offsetSet($ident, $refNode);
            $reference->ksort();
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
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        $mountPoints = array_map('intval', $backendUserUtility->returnWebmounts());
        $mountPoints = array_unique($mountPoints);
        if (empty($mountPoints)) {
            return $nodeCollection;
        }

        foreach ($mountPoints as $mountPoint) {
            if ($mountPoint === 0) {
                $record = [
                    'uid' => 0,
                    'title' => 'Commerce'
                ];
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
                $childNodes = $this->getFilteredCategoryNodes($subNode, $searchFilter, $mountPoint);
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
     * @param int|array $id Category id
     * @param string $searchFilter Search filter
     * @param array $categoryUids
     *
     * @return string
     */
    protected function getCategoryWhereClause($id, $searchFilter = '', array $categoryUids = [])
    {
        $where = '';

        if (is_numeric($id) && $id >= 0) {
            $where .= ' AND uid_foreign = ' . (int) $id;
        }

        if ($searchFilter !== '') {
            $searchWhere = [];
            // in case a previous search for products or articles returned category uids
            if (!empty($categoryUids)) {
                $searchWhere[] = 'tx_commerce_categories.uid IN (' . implode(',', $categoryUids) . ')';
            }

            if (is_numeric($searchFilter) && $searchFilter > 0) {
                $searchWhere[] = 'tx_commerce_categories.uid = ' . (int) $searchFilter;
            }

            $searchFilter = $this->getDatabaseConnection()->fullQuoteStr(
                '%' . $searchFilter . '%',
                'tx_commerce_categories'
            );
            $searchWhere[] = 'tx_commerce_categories.title LIKE ' . $searchFilter;

            $where .= ' AND (' . implode(' OR ', $searchWhere) . ')';
        }

        $where .= ' AND ' . \CommerceTeam\Commerce\Utility\BackendUtility::getCategoryPermsClause(1) .
            BackendUtility::deleteClause('tx_commerce_categories') .
            BackendUtility::versioningPlaceholderClause('tx_commerce_categories');

        return ltrim($where, ' AND ');
    }

    /**
     * Returns the where clause for fetching pages
     *
     * @param int $id Category id
     * @param string $searchFilter Search filter
     * @param array $productUids
     *
     * @return string
     */
    protected function getProductWhereClause($id, $searchFilter = '', array $productUids = [])
    {
        $where = '';

        if (is_numeric($id) && $id >= 0) {
            $where .= ' AND uid_foreign = ' . (int) $id;
        }

        if ($searchFilter !== '') {
            $searchWhere = [];
            // in case a previous search for articles returned product uids
            if (!empty($productUids)) {
                $searchWhere[] = 'tx_commerce_products.uid IN (' . implode(',', $productUids) . ')';
            }

            if (is_numeric($searchFilter) && $searchFilter > 0) {
                $searchWhere[] = 'tx_commerce_products.uid = ' . (int) $searchFilter;
            }

            $searchFilter = $this->getDatabaseConnection()->fullQuoteStr(
                '%' . $searchFilter . '%',
                'tx_commerce_products'
            );

            $searchWhere[] = 'tx_commerce_products.title LIKE ' . $searchFilter;

            $where .= ' AND (' . implode(' OR ', $searchWhere) . ')';
        }

        $where .= ' AND ' . \CommerceTeam\Commerce\Utility\BackendUtility::getCategoryPermsClause(1) .
            BackendUtility::deleteClause('tx_commerce_products') .
            BackendUtility::versioningPlaceholderClause('tx_commerce_products');

        return ltrim($where, ' AND ');
    }

    /**
     * Returns the where clause for fetching pages
     *
     * @param int $id Product id
     * @param string $searchFilter Search filter
     *
     * @return string
     */
    protected function getArticleWhereClause($id, $searchFilter = '')
    {
        $where = '';

        if (is_numeric($id) && $id >= 0) {
            $where .= ' AND uid_product = ' . (int) $id;
        }

        if ($searchFilter !== '') {
            $searchWhere = [];
            if (is_numeric($searchFilter) && $searchFilter > 0) {
                $searchWhere[] = 'tx_commerce_articles.uid = ' . (int) $searchFilter;
            }
            $searchFilter = $this->getDatabaseConnection()->fullQuoteStr(
                '%' . $searchFilter . '%',
                'tx_commerce_articles'
            );

            $searchWhere[] = 'tx_commerce_articles.title LIKE ' . $searchFilter;

            $where .= ' AND (' . implode(' OR ', $searchWhere) . ')';
        }

        $where .= ($where ? '' : '1 = 1') . BackendUtility::deleteClause('tx_commerce_articles') .
            BackendUtility::versioningPlaceholderClause('tx_commerce_articles');

        return ltrim($where, ' AND ');
    }


    /**
     * Returns all sub-pages of a given id
     *
     * @param int $id
     * @param string $searchFilter
     * @return array
     */
    protected function getCategories($id, $searchFilter = '')
    {
        $where = '';
        // try to find articles and products with searchFilter to include the categories they are on to the result
        if ($searchFilter) {
            $categories = $this->searchCategoryWithMatchingArticle($searchFilter);
            if (empty($categories)) {
                $categories = $this->searchCategoryWithMatchingProduct($searchFilter);
            }

            if (!empty($categories)) {
                $categoryUids = [];
                foreach ($categories as $category) {
                    $categoryUids[] = $category['uid'];
                }

                $where = $this->getCategoryWhereClause($id, $searchFilter, $categoryUids);
            }
        }
        if ($where == '') {
            $where = $this->getCategoryWhereClause($id, $searchFilter);
        }

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
     * Returns all sub-pages of a given id
     *
     * @param int $categoryId Category id
     * @param string $searchFilter Search filter
     *
     * @return array
     */
    protected function getProducts($categoryId, $searchFilter = '')
    {
        $where = '';
        // try to find articles with searchFilter to include the products they are on to the result
        if ($searchFilter) {
            $products = $this->searchProductWithMathingArticle($searchFilter);

            if (!empty($products)) {
                $productUids = [];
                foreach ($products as $product) {
                    $productUids[] = $product['uid'];
                }

                $where = $this->getProductWhereClause($categoryId, $searchFilter, $productUids);
            }
        }
        if ($where == '') {
            $where = $this->getProductWhereClause($categoryId, $searchFilter);
        }

        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'tx_commerce_products.uid AS uid, tx_commerce_products.t3ver_wsid',
            'tx_commerce_products
                INNER JOIN tx_commerce_products_categories_mm AS mm ON tx_commerce_products.uid = mm.uid_local
                INNER JOIN tx_commerce_categories ON mm.uid_foreign = tx_commerce_categories.uid',
            $where,
            '',
            'tx_commerce_products.sorting',
            '',
            'uid'
        );
    }

    /**
     * Returns all sub-pages of a given id
     *
     * @param int $productId Product id
     * @param string $searchFilter Search filter
     *
     * @return array
     */
    protected function getArticles($productId, $searchFilter = '')
    {
        $where = $this->getArticleWhereClause($productId, $searchFilter);
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, t3ver_wsid',
            'tx_commerce_articles',
            $where,
            '',
            'tx_commerce_articles.sorting',
            '',
            'uid'
        );
    }

    /**
     * As the category search can only search in the category table this
     * search gathers all category ids that contain articles that match
     * the search. The search in category takes these ids as part of the
     * search pattern
     *
     * @param string $searchFilter
     * @return array
     */
    protected function searchCategoryWithMatchingArticle($searchFilter)
    {
        $where = $this->getArticleWhereClause(-1, $searchFilter);
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'tx_commerce_categories.uid, tx_commerce_categories.t3ver_wsid',
            'tx_commerce_articles
                INNER JOIN tx_commerce_products_categories_mm AS mm ON
                    tx_commerce_articles.uid_product = mm.uid_local
                INNER JOIN tx_commerce_categories ON mm.uid_foreign = tx_commerce_categories.uid',
            $where,
            '',
            'tx_commerce_categories.sorting',
            '',
            'uid'
        );
    }

    /**
     * As the category search can only search in the category table this
     * search gathers all category ids that contain products that match
     * the search. The search in category takes these ids as part of the
     * search pattern
     *
     * @param string $searchFilter
     * @return array
     */
    protected function searchCategoryWithMatchingProduct($searchFilter)
    {
        $where = $this->getProductWhereClause(-1, $searchFilter);
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'tx_commerce_categories.uid, tx_commerce_categories.t3ver_wsid',
            'tx_commerce_categories
                INNER JOIN tx_commerce_products_categories_mm AS mm ON
                    tx_commerce_categories.uid = mm.uid_local
                INNER JOIN tx_commerce_products ON mm.uid_foreign = tx_commerce_products.uid',
            $where,
            '',
            'tx_commerce_categories.sorting',
            '',
            'uid'
        );
    }

    /**
     * As the product search can only search in the product table this
     * search gathers all product ids that contain articles that match
     * the search. The search in product takes these ids as part of the
     * search pattern
     *
     * @param $searchFilter
     * @return array
     */
    protected function searchProductWithMathingArticle($searchFilter)
    {
        $where = $this->getArticleWhereClause(-1, $searchFilter);
        return (array) $this->getDatabaseConnection()->exec_SELECTgetRows(
            'tx_commerce_products.uid, tx_commerce_products.t3ver_wsid',
            'tx_commerce_articles
                INNER JOIN tx_commerce_products ON
                    tx_commerce_articles.uid_product = tx_commerce_products.uid',
            $where,
            '',
            'tx_commerce_products.sorting',
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
    protected function hasNodeSubCategories($id)
    {
        $where = $this->getCategoryWhereClause($id);
        $category = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'tx_commerce_categories.uid',
            'tx_commerce_categories
                INNER JOIN tx_commerce_categories_parent_category_mm ON
                    tx_commerce_categories.uid = tx_commerce_categories_parent_category_mm.uid_local',
            $where,
            '',
            'tx_commerce_categories.sorting'
        );
        $returnValue = true;
        if (!$category['uid']) {
            $returnValue = false;
        }
        return $returnValue;
    }

    /**
     * Returns TRUE if the node has child's
     *
     * @param int $id
     * @return bool
     */
    protected function hasNodeSubProducts($id)
    {
        $where = $this->getProductWhereClause($id);
        $product = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'tx_commerce_products.uid',
            'tx_commerce_products
                INNER JOIN tx_commerce_products_categories_mm AS mm ON mm.uid_local = tx_commerce_products.uid
                INNER JOIN tx_commerce_categories ON mm.uid_foreign = tx_commerce_categories.uid',
            $where,
            '',
            'tx_commerce_products.sorting'
        );
        $returnValue = true;
        if (!$product['uid']) {
            $returnValue = false;
        }
        return $returnValue;
    }

    /**
     * Returns TRUE if the node has child's
     *
     * @param int $id Page id
     *
     * @return boolean
     */
    protected function hasNodeSubArticles($id)
    {
        $where = $this->getArticleWhereClause($id);
        $article = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            'tx_commerce_articles',
            $where,
            '',
            'sorting'
        );

        $returnValue = true;
        if (!$article['uid']) {
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
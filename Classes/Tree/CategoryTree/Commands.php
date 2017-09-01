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

use CommerceTeam\Commerce\Domain\Repository\SysDomainRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Page Tree and Context Menu Commands
 */
class Commands
{
    /**
     * @var bool|null
     */
    protected static $useNavTitle = null;

    /**
     * @var bool|null
     */
    protected static $addIdAsPrefix = null;

    /**
     * @var bool|null
     */
    protected static $addDomainName = null;

    /**
     * @var array|null
     */
    protected static $backgroundColors = null;

    /**
     * @var int|null
     */
    protected static $titleLength = null;

    /**
     * Visibly the record
     *
     * @param NodeInterface $node
     */
    public static function visiblyNode(NodeInterface $node)
    {
        $data[$node->getType()][$node->getWorkspaceId()]['hidden'] = 0;
        self::processTceCmdAndDataMap([], $data);
    }

    /**
     * Hide the page
     *
     * @param NodeInterface $node
     */
    public static function disableNode(NodeInterface $node)
    {
        $data[$node->getType()][$node->getWorkspaceId()]['hidden'] = 1;
        self::processTceCmdAndDataMap([], $data);
    }

    /**
     * Delete the page
     *
     * @param NodeInterface $node
     */
    public static function deleteNode(NodeInterface $node)
    {
        $cmd[$node->getType()][$node->getId()]['delete'] = 1;
        self::processTceCmdAndDataMap($cmd);
    }

    /**
     * Restore the page
     *
     * @param NodeInterface $node
     * @param int $targetId
     */
    public static function restoreNode(NodeInterface $node, $targetId)
    {
        $cmd[$node->getType()][$node->getId()]['undelete'] = 1;
        self::processTceCmdAndDataMap($cmd);
        if ($node->getId() !== $targetId) {
            self::moveNode($node, $targetId);
        }
    }

    /**
     * Updates the node label
     *
     * @param NodeInterface $node
     * @param string $updatedLabel
     */
    public static function updateNodeLabel(NodeInterface $node, $updatedLabel)
    {
        $table = $node->getType();
        if (self::getBackendUserAuthentication()->checkLanguageAccess(0)) {
            $data[$table][$node->getWorkspaceId()][$node->getTextSourceField()] = $updatedLabel;
            self::processTceCmdAndDataMap([], $data);
        } else {
            throw new \RuntimeException(
                implode(LF, [
                    'Editing title of ' . $table . ' id \'' . $node->getWorkspaceId()
                    . '\' failed. Editing default language is not allowed.'
                ]),
                1365513336
            );
        }
    }

    /**
     * Copies a page and returns the id of the new page
     *
     * Node: Use a negative target id to specify a sibling target else the parent is used
     *
     * @param NodeInterface $node
     * @param int $targetId
     * @return int
     */
    public static function copyNode(NodeInterface $node, $targetId)
    {
        $table = $node->getType();
        $cmd[$table][$node->getId()]['copy'] = $targetId;
        $returnValue = self::processTceCmdAndDataMap($cmd);
        return $returnValue[$table][$node->getId()];
    }

    /**
     * Moves a page
     *
     * Node: Use a negative target id to specify a sibling target else the parent is used
     *
     * @param NodeInterface $node
     * @param int $targetId
     */
    public static function moveNode(NodeInterface $node, $targetId)
    {
        $cmd[$node->getType()][$node->getId()]['move'] = $targetId;
        self::processTceCmdAndDataMap($cmd);
    }

    /**
     * Creates a page of the given doktype and returns the id of the created page
     *
     * @param NodeInterface $parentNode
     * @param int $targetId
     * @param string $type
     * @return int
     */
    public static function createNode(NodeInterface $parentNode, $targetId, $type)
    {
        $placeholder = 'NEW12345';
        $pid = (int)$parentNode->getPid();
        $targetId = (int)$targetId;
        $table = $parentNode->getType();

        // Use page TsConfig as default page initialization
        $pageTs = BackendUtility::getPagesTSconfig($pid);
        if (array_key_exists('TCAdefaults.', $pageTs) && array_key_exists($table . '.', $pageTs['TCAdefaults.'])) {
            $data[$table][$placeholder] = $pageTs['TCAdefaults.'][$table . '.'];
        } else {
            $data[$table][$placeholder] = [];
        }

        $data[$table][$placeholder]['pid'] = $pid;
        $data[$table][$placeholder]['title'] = htmlspecialchars(self::getLanguageService()->sL(
            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:tree.defaultPageTitle'
        ));

        if ($parentNode->getType() == 'tx_commerce_categories') {
            if ($type == 'tx_category_categories') {
                $data[$table][$placeholder]['defVals']['parent_category'] = $parentNode->getId();
            } else {
                $data[$table][$placeholder]['defVals']['categories'] = [$parentNode->getId()];
            }
        } elseif ($parentNode->getType() == 'tx_commerce_products') {
            $data[$table][$placeholder]['defVals']['uid_product'] = $parentNode->getId();
        }

        $newPageId = self::processTceCmdAndDataMap([], $data);
        $node = self::getNode($table, $newPageId[$placeholder]);
        if ($pid !== $targetId) {
            self::moveNode($node, $targetId);
        }

        return $newPageId[$placeholder];
    }

    /**
     * Process TCEMAIN commands and data maps
     *
     * Command Map:
     * Used for moving, recover, remove and some more operations.
     *
     * Data Map:
     * Used for creating and updating records,
     *
     * This API contains all necessary access checks.
     *
     * @param array $cmd
     * @param array $data
     * @return array
     * @throws \RuntimeException if an error happened while the TCE processing
     */
    protected static function processTceCmdAndDataMap(array $cmd, array $data = [])
    {
        /** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $tce->start($data, $cmd);
        $tce->copyTree = MathUtility::forceIntegerInRange($GLOBALS['BE_USER']->uc['copyLevels'], 0, 100);
        if (!empty($cmd)) {
            $tce->process_cmdmap();
            $returnValues = $tce->copyMappingArray_merged;
        } elseif (!empty($data)) {
            $tce->process_datamap();
            $returnValues = $tce->substNEWwithIDs;
        } else {
            $returnValues = [];
        }
        // check errors
        if (!empty($tce->errorLog)) {
            throw new \RuntimeException(implode(LF, $tce->errorLog), 1333754629);
        }
        return $returnValues;
    }

    /**
     * Returns the mount point path for a temporary mount or the given id
     *
     * @param int $uid
     * @return string
     */
    public static function getMountPointPath($uid = -1)
    {
        if ($uid === -1) {
            $uid = (int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
        }
        if ($uid <= 0) {
            return '';
        }
        if (self::$useNavTitle === null) {
            self::$useNavTitle = self::getBackendUserAuthentication()->getTSConfigVal('options.pageTree.showNavTitle');
        }
        // @todo make category rootline in commerce BEutility
        $rootline = array_reverse(BackendUtility::BEgetRootLine($uid));
        array_shift($rootline);
        $path = [];
        foreach ($rootline as $rootlineElement) {
            $record = self::getNodeRecord('tx_commerce_categories', $rootlineElement['uid']);
            $text = $record['title'];
            if (self::$useNavTitle && trim($record['nav_title']) !== '') {
                $text = $record['nav_title'];
            }
            $path[] = htmlspecialchars($text);
        }
        return '/' . implode('/', $path);
    }

    /**
     * Returns a node from the given node id
     *
     * @param string $table
     * @param int $nodeId
     * @param bool $unsetMovePointers
     * @return CategoryNode
     */
    public static function getNode($table, $nodeId, $unsetMovePointers = true)
    {
        $record = self::getNodeRecord($table, $nodeId, $unsetMovePointers);
        switch ($table) {
            case 'tx_commerce_products':
                $node = self::getProductNode($record);
                break;

            case 'tx_commerce_articles':
                $node = self::getArticleNode($record);
                break;

            default:
                $node = self::getCategoryNode($record);
        }
        return $node;
    }

    /**
     * Returns a node record from a given id
     *
     * @param string $table
     * @param int $nodeId
     * @param bool $unsetMovePointers
     * @return array
     */
    public static function getNodeRecord($table, $nodeId, $unsetMovePointers = true)
    {
        $record = BackendUtility::getRecordWSOL($table, $nodeId, '*', '', true, $unsetMovePointers);
        return $record;
    }

    /**
     * Returns the first configured domain name for a page
     *
     * @param int $uid
     * @return string
     */
    public static function getDomainName($uid)
    {
        /** @var SysDomainRepository $sysDomainRepository */
        $sysDomainRepository = GeneralUtility::makeInstance(SysDomainRepository::class);
        return $sysDomainRepository->findFirstByPid($uid);
    }

    /**
     * Creates a node with the given record information
     *
     * @param array $record
     * @param int $mountPoint
     * @return CategoryNode
     */
    public static function getCategoryNode($record, $mountPoint = 0)
    {
        $backendUser = self::getBackendUserAuthentication();
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if (self::$titleLength === null) {
            self::$useNavTitle = $backendUser->getTSConfigVal('options.pageTree.showNavTitle');
            self::$addIdAsPrefix = $backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
            self::$addDomainName = $backendUser->getTSConfigVal('options.pageTree.showDomainNameWithTitle');
            self::$backgroundColors = $backendUser->getTSConfigProp('options.pageTree.backgroundColor');
            self::$titleLength = (int)$backendUser->uc['titleLen'];
        }
        if (!isset(self::$backgroundColors['category'])) {
            self::$backgroundColors['category'] = $backendUser->getTSConfigProp(
                'options.categoryTree.category.backgroundColor'
            );
        }

        /** @var $subNode CategoryNode */
        $subNode = GeneralUtility::makeInstance(CategoryNode::class);
        $subNode->setRecord($record);
        $subNode->setCls($record['_CSSCLASS']);
        $subNode->setType('tx_commerce_categories');
        $subNode->setId($record['uid']);
        $subNode->setMountPoint($mountPoint);
        $subNode->setWorkspaceId($record['_ORIG_uid'] ?: $record['uid']);
        $subNode->setBackgroundColor(self::$backgroundColors[$record['uid']]);
        $field = 'title';
        $text = $record['title'];
        if (self::$useNavTitle && trim($record['nav_title']) !== '') {
            $field = 'nav_title';
            $text = $record['nav_title'];
        }
        if (trim($text) === '') {
            $visibleText = '[' . htmlspecialchars(self::getLanguageService()->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.no_title'
            )) . ']';
        } else {
            $visibleText = $text;
        }
        $visibleText = GeneralUtility::fixed_lgd_cs($visibleText, self::$titleLength);
        $suffix = '';
        if (self::$addDomainName) {
            $domain = self::getDomainName($record['uid']);
            $suffix = $domain !== '' ? ' [' . $domain . ']' : '';
        }
        $qtip = str_replace(' - ', '<br />', htmlspecialchars(BackendUtility::titleAttribForPages($record, '', false)));
        $prefix = '';
        /** @noinspection PhpInternalEntityUsedInspection */
        $lockInfo = BackendUtility::isRecordLocked('tx_commerce_categories', $record['uid']);
        if (is_array($lockInfo)) {
            $qtip .= '<br />' . htmlspecialchars($lockInfo['msg']);
            $prefix .= '<span class="commerce-categorytree-status">'
                . (string)$iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>';
        }
        // Call stats information hook
        $stat = '';
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $_params = ['tx_commerce_categories', $record['uid']];
            $fakeThis = null;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $fakeThis);
            }
        }
        $prefix .= htmlspecialchars(self::$addIdAsPrefix ? '[' . $record['uid'] . '] ' : '');
        $subNode->setEditableText($text);
        $subNode->setText(htmlspecialchars($visibleText), $field, $prefix, htmlspecialchars($suffix) . $stat);
        $subNode->setQTip($qtip);
        if ((int)$record['uid'] !== 0) {
            $spriteIconCode = $iconFactory->getIconForRecord('tx_commerce_categories', $record, Icon::SIZE_SMALL);
        } else {
            $spriteIconCode = $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render();
        }
        $subNode->setSpriteIconCode((string)$spriteIconCode);
        if (!$subNode->canCreateNewPages()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $subNode->setIsDropTarget(false);
        }
        if (!$subNode->canBeEdited()
            || !$subNode->canBeRemoved()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $subNode->setDraggable(false);
        }
        return $subNode;
    }

    /**
     * Creates a node with the given record information
     *
     * @param array $record
     * @param int $mountPoint
     * @return ProductNode
     */
    public static function getProductNode($record, $mountPoint = 0)
    {
        $backendUser = self::getBackendUserAuthentication();
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if (self::$titleLength === null) {
            self::$useNavTitle = $backendUser->getTSConfigVal('options.pageTree.showNavTitle');
            self::$addIdAsPrefix = $backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
            self::$addDomainName = $backendUser->getTSConfigVal('options.pageTree.showDomainNameWithTitle');
            self::$backgroundColors = $backendUser->getTSConfigProp('options.pageTree.backgroundColor');
            self::$titleLength = (int)$backendUser->uc['titleLen'];
        }
        if (!isset(self::$backgroundColors['product'])) {
            self::$backgroundColors['product'] = $backendUser->getTSConfigProp(
                'options.categoryTree.product.backgroundColor'
            );
        }

        /** @var $productNode ProductNode */
        $productNode = GeneralUtility::makeInstance(ProductNode::class);
        $productNode->setRecord($record);
        $productNode->setCls($record['_CSSCLASS']);
        $productNode->setType('tx_commerce_products');
        $productNode->setCategory($record['category']);
        $productNode->setId($record['uid']);
        $productNode->setMountPoint($mountPoint);
        $productNode->setWorkspaceId($record['_ORIG_uid'] ?: $record['uid']);
        $productNode->setBackgroundColor(self::$backgroundColors[$record['uid']]);
        $field = 'title';
        $text = $record['title'];
        if (self::$useNavTitle && trim($record['nav_title']) !== '') {
            $field = 'nav_title';
            $text = $record['nav_title'];
        }
        if (trim($text) === '') {
            $visibleText = '['
                . htmlspecialchars(self::getLanguageService()->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.no_title'
                ))
                . ']';
        } else {
            $visibleText = $text;
        }
        $visibleText = GeneralUtility::fixed_lgd_cs($visibleText, self::$titleLength);
        $suffix = '';
        if (self::$addDomainName) {
            $domain = self::getDomainName($record['uid']);
            $suffix = $domain !== '' ? ' [' . $domain . ']' : '';
        }
        $qtip = str_replace(' - ', '<br />', htmlspecialchars(BackendUtility::titleAttribForPages($record, '', false)));
        $prefix = '';
        /** @noinspection PhpInternalEntityUsedInspection */
        $lockInfo = BackendUtility::isRecordLocked('tx_commerce_products', $record['uid']);
        if (is_array($lockInfo)) {
            $qtip .= '<br />' . htmlspecialchars($lockInfo['msg']);
            $prefix .= '<span class="commerce-categorytree-status">'
                . (string)$iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>';
        }
        // Call stats information hook
        $stat = '';
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $_params = ['tx_commerce_products', $record['uid']];
            $fakeThis = null;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $fakeThis);
            }
        }
        $prefix .= htmlspecialchars(self::$addIdAsPrefix ? '[' . $record['uid'] . '] ' : '');
        $productNode->setEditableText($text);
        $productNode->setText(htmlspecialchars($visibleText), $field, $prefix, htmlspecialchars($suffix) . $stat);
        $productNode->setQTip($qtip);
        if ((int)$record['uid'] !== 0) {
            $spriteIconCode = $iconFactory->getIconForRecord(
                'tx_commerce_products',
                $record,
                Icon::SIZE_SMALL
            )->render();
        } else {
            $spriteIconCode = $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render();
        }
        $productNode->setSpriteIconCode((string)$spriteIconCode);
        if (!$productNode->canCreateNewPages()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $productNode->setIsDropTarget(false);
        }
        if (!$productNode->canBeEdited()
            || !$productNode->canBeRemoved()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $productNode->setDraggable(false);
        }
        return $productNode;
    }

    /**
     * Creates a node with the given record information
     *
     * @param array $record
     * @return ArticleNode
     */
    public static function getArticleNode($record)
    {
        $backendUser = self::getBackendUserAuthentication();
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if (self::$titleLength === null) {
            self::$useNavTitle = $backendUser->getTSConfigVal('options.pageTree.showNavTitle');
            self::$addIdAsPrefix = $backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
            self::$addDomainName = $backendUser->getTSConfigVal('options.pageTree.showDomainNameWithTitle');
            self::$backgroundColors = $backendUser->getTSConfigProp('options.pageTree.backgroundColor');
            self::$titleLength = (int)$backendUser->uc['titleLen'];
        }
        if (!isset(self::$backgroundColors['article'])) {
            self::$backgroundColors['article'] = $backendUser->getTSConfigProp(
                'options.categoryTree.article.backgroundColor'
            );
        }

        /** @var $articleNode ArticleNode */
        $articleNode = GeneralUtility::makeInstance(ArticleNode::class);
        $articleNode->setRecord($record);
        $articleNode->setCls($record['_CSSCLASS']);
        $articleNode->setType('tx_commerce_articles');
        $articleNode->setProduct($record['product']);
        $articleNode->setCategory($record['category']);
        $articleNode->setId($record['uid']);
        $articleNode->setWorkspaceId($record['_ORIG_uid'] ?: $record['uid']);
        $articleNode->setBackgroundColor(self::$backgroundColors[$record['uid']]);
        $field = 'title';
        $text = $record['title'];
        if (self::$useNavTitle && trim($record['nav_title']) !== '') {
            $field = 'nav_title';
            $text = $record['nav_title'];
        }
        if (trim($text) === '') {
            $visibleText = '[' . htmlspecialchars(self::getLanguageService()->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.no_title'
            )) . ']';
        } else {
            $visibleText = $text;
        }
        $visibleText = GeneralUtility::fixed_lgd_cs($visibleText, self::$titleLength);
        $suffix = '';
        if (self::$addDomainName) {
            $domain = self::getDomainName($record['uid']);
            $suffix = $domain !== '' ? ' [' . $domain . ']' : '';
        }
        $qtip = str_replace(' - ', '<br />', htmlspecialchars(BackendUtility::titleAttribForPages($record, '', false)));
        $prefix = '';
        /** @noinspection PhpInternalEntityUsedInspection */
        $lockInfo = BackendUtility::isRecordLocked('tx_commerce_articles', $record['uid']);
        if (is_array($lockInfo)) {
            $qtip .= '<br />' . htmlspecialchars($lockInfo['msg']);
            $prefix .= '<span class="commerce-categorytree-status">'
                . (string)$iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>';
        }
        // Call stats information hook
        $stat = '';
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $_params = ['tx_commerce_articles', $record['uid']];
            $fakeThis = null;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $fakeThis);
            }
        }
        $prefix .= htmlspecialchars(self::$addIdAsPrefix ? '[' . $record['uid'] . '] ' : '');
        $articleNode->setEditableText($text);
        $articleNode->setText(htmlspecialchars($visibleText), $field, $prefix, htmlspecialchars($suffix) . $stat);
        $articleNode->setQTip($qtip);
        if ((int)$record['uid'] !== 0) {
            $spriteIconCode = $iconFactory->getIconForRecord(
                'tx_commerce_articles',
                $record,
                Icon::SIZE_SMALL
            )->render();
        } else {
            $spriteIconCode = $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render();
        }
        $articleNode->setSpriteIconCode((string)$spriteIconCode);
        if (!$articleNode->canCreateNewPages()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $articleNode->setIsDropTarget(false);
        }
        if (!$articleNode->canBeEdited()
            || !$articleNode->canBeRemoved()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $articleNode->setDraggable(false);
        }
        return $articleNode;
    }

    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     * @deprecated since 6.0.0 will be removed in 7.0.0
     */
    protected function getDatabaseConnection()
    {
        GeneralUtility::logDeprecatedFunction();
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get backend user authentication
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected static function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}

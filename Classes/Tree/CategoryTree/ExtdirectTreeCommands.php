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

use CommerceTeam\Commerce\Utility\BackendUserUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Commands for the Page tree
 */
class ExtdirectTreeCommands
{
    /**
     * @param array|\stdClass $nodeData
     * @return NodeInterface
     */
    protected function getNode($nodeData)
    {
        if (!is_array($nodeData)) {
            $nodeData = (array)$nodeData;
        }

        switch ($nodeData['type']) {
            case 'tx_commerce_products':
                $type = ProductNode::class;
                break;

            case 'tx_commerce_articles':
                $type = ArticleNode::class;
                break;

            default:
                $type = CategoryNode::class;
        }
        return GeneralUtility::makeInstance($type, (array)$nodeData);
    }

    /**
     * Visibly the page
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function visiblyNode($nodeData)
    {
        $node = $this->getNode($nodeData);
        try {
            Commands::visiblyNode($node);
            $newNode = Commands::getNode($node->getType(), $node->getId());
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'error' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Hide the page
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function disableNode($nodeData)
    {
        $node = $this->getNode($nodeData);
        try {
            Commands::disableNode($node);
            $newNode = Commands::getNode($node->getType(), $node->getId());
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Delete the page
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function deleteNode($nodeData)
    {
        $node = $this->getNode($nodeData);
        try {
            Commands::deleteNode($node);
            $returnValue = array();
            if ($GLOBALS['BE_USER']->workspace) {
                $record = Commands::getNodeRecord($node->getType(), $node->getId());
                if ($record['_ORIG_uid']) {
                    switch ($node->getType()) {
                        case 'tx_commerce_products':
                            $newNode = Commands::getProductNode($record);
                            break;

                        case 'tx_commerce_articles':
                            $newNode = Commands::getArticleNode($record);
                            break;

                        default:
                            $newNode = Commands::getCategoryNode($record);
                    }
                    $returnValue = $newNode->toArray();
                }
            }
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Restore the page
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function restoreNode($nodeData, $destination)
    {
        $node = $this->getNode($nodeData);
        try {
            Commands::restoreNode($node, $destination);
            $newNode = Commands::getNode($node->getType(), $node->getId());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Updates the given field with a new text value, may be used to inline update
     * the title field in the new page tree
     *
     * @param \stdClass $nodeData
     * @param string $updatedLabel
     * @return array
     */
    public function updateLabel($nodeData, $updatedLabel)
    {
        if ($updatedLabel === '') {
            return array();
        }

        $node = $this->getNode($nodeData);
        try {
            Commands::updateNodeLabel($node, $updatedLabel);
            $shortendedText = GeneralUtility::fixed_lgd_cs($updatedLabel, (int)$GLOBALS['BE_USER']->uc['titleLen']);
            $returnValue = array(
                'editableText' => $updatedLabel,
                'updatedText' => htmlspecialchars($shortendedText)
            );
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Sets a temporary mount point
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public static function setTemporaryMountPoint($nodeData)
    {
        $node = self::getNode($nodeData);
        $backendUser = self::getBackendUserAuthentication();
        $backendUser->uc['pageTree_temporaryMountPoint'] = $node->getId();
        $backendUser->writeUC($GLOBALS['BE_USER']->uc);
        return Commands::getMountPointPath();
    }

    /**
     * Moves the source node directly as the first child of the destination node
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function moveNodeToFirstChildOfDestination($nodeData, $destination)
    {
        $node = $this->getNode($nodeData);
        try {
            Commands::moveNode($node, $destination);
            $newNode = Commands::getNode($node->getId(), false);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Moves the source node directly after the destination node
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function moveNodeAfterDestination($nodeData, $destination)
    {
        $node = $this->getNode($nodeData);
        try {
            Commands::moveNode($node, -$destination);
            $newNode = Commands::getNode($node->getId(), false);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Copies the source node directly as the first child of the destination node and
     * returns the created node.
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function copyNodeToFirstChildOfDestination($nodeData, $destination)
    {
        $node = $this->getNode($nodeData);
        try {
            $newPageId = Commands::copyNode($node, $destination);
            $newNode = Commands::getNode($node->getType(), $newPageId);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Copies the source node directly after the destination node and returns the
     * created node.
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function copyNodeAfterDestination($nodeData, $destination)
    {
        $node = $this->getNode($nodeData);
        try {
            $newPageId = Commands::copyNode($node, -$destination);
            $newNode = Commands::getNode($node->getType(), $newPageId);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Inserts a new node as the first child node of the destination node and returns the created node.
     *
     * @param \stdClass $nodeData
     * @param string $type
     * @return array
     */
    public function insertNodeToFirstChildOfDestination($nodeData, $type)
    {
        $node = $this->getNode($nodeData);
        try {
            $newPageId = Commands::createNode($node, $node->getId(), $type);
            $returnValue = Commands::getNode($type, $newPageId)->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Inserts a new node directly after the destination node and returns the created node.
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @param string $type
     * @return array
     */
    public function insertNodeAfterDestination($nodeData, $destination, $type)
    {
        $node = $this->getNode($nodeData);
        try {
            $newPageId = Commands::createNode($node, -$destination, $type);
            $returnValue = Commands::getNode($type, $newPageId)->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Returns the view link of a given node
     *
     * @param \\stdClass $nodeData
     * @return string
     */
    public function getViewLink($nodeData)
    {
        $node = $this->getNode($nodeData);
        $javascriptLink = BackendUtility::viewOnClick($node->getId());
        $extractedLink = '';
        if (preg_match('/window\\.open\\(\'([^\']+)\'/i', $javascriptLink, $match)) {
            $extractedLink = json_decode('"' . trim($match[1], '"') . '"', JSON_HEX_AMP);
        };
        return $extractedLink;
    }

    /**
     * Adds the rootline of a given node to the tree expansion state and adds the node
     * itself as the current selected page. This leads to the expansion and selection of
     * the node in the tree after a refresh.
     *
     * @param string $stateId
     * @param int $nodeId
     * @param array|\stdClass $nodeData
     * @return array
     */
    public function addRootlineOfNodeToStateHash($stateId, $nodeId, $nodeData = [])
    {
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        $mountPoints = array_map('intval', $backendUserUtility->returnWebmounts());
        $mountPoints = array_unique($mountPoints);
        /** @var $userSettingsController \TYPO3\CMS\Backend\Controller\UserSettingsController */
        $userSettingsController = GeneralUtility::makeInstance(
            \TYPO3\CMS\Backend\Controller\UserSettingsController::class
        );
        $state = $userSettingsController->process('get', 'BackendComponents.States.' . $stateId);
        if (empty($state)) {
            $state = new \stdClass();
            $state->stateHash = new \stdClass();
        }
        $state->stateHash = (object)$state->stateHash;

        /** @var ArticleNode $articleNode */
        /** @var ProductNode $productNode */
        $articleNode = null;
        $productNode = null;
        $productId = 0;
        if ($nodeData->type == 'tx_commerce_articles') {
            $articleNode = self::getNode($nodeData);
            $productId = $articleNode->getProduct();
            $nodeId = $articleNode->getCategory();
        }
        if ($nodeData->type == 'tx_commerce_products') {
            $productNode = self::getNode($nodeData);
            $productId = $productNode->getId();
            $nodeId = $productNode->getCategory();
        }

        $rootline = \CommerceTeam\Commerce\Utility\BackendUtility::BEgetRootLine(
            $nodeId,
            '',
            $this->getBackendUserAuthentication()->workspace != 0
        );
        $rootlineIds = array();
        foreach ($rootline as $pageData) {
            $rootlineIds[] = (int)$pageData['uid'];
        }
        foreach ($mountPoints as $mountPoint) {
            if (!in_array($mountPoint, $rootlineIds, true)) {
                continue;
            }
            $isFirstNode = true;
            foreach ($rootline as $pageData) {
                /** @var NodeInterface $node */
                $node = Commands::getCategoryNode($pageData, $mountPoint);
                if ($isFirstNode) {
                    // for clicked article or products we need a special handling
                    // of which was the last selected node
                    if ($articleNode) {
                        $state->stateHash->lastSelectedNode = $articleNode->calculateNodeId();
                        $state->stateHash->{'pp' . dechex($productId)} = 1;
                        $state->stateHash->{$node->calculateNodeId('')} = 1;
                    } elseif ($productNode) {
                        $state->stateHash->lastSelectedNode = $productNode->calculateNodeId();
                        $state->stateHash->{$node->calculateNodeId('')} = 1;
                    } else {
                        $state->stateHash->lastSelectedNode = $node->calculateNodeId();
                    }

                    $isFirstNode = false;
                } else {
                    $state->stateHash->{$node->calculateNodeId('')} = 1;
                }
            }
        }
        $userSettingsController->process('set', 'BackendComponents.States.' . $stateId, $state);
        return (array)$state->stateHash;
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

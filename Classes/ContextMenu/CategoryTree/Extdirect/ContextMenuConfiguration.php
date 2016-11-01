<?php
namespace CommerceTeam\Commerce\ContextMenu\CategoryTree\Extdirect;

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

use CommerceTeam\Commerce\Tree\CategoryTree\ArticleNode;
use CommerceTeam\Commerce\Tree\CategoryTree\CategoryNode;
use CommerceTeam\Commerce\Tree\CategoryTree\ProductNode;

/**
 * Context Menu of the Page Tree
 */
class ContextMenuConfiguration extends \TYPO3\CMS\Backend\ContextMenu\Extdirect\AbstractExtdirectContextMenu
{
    /**
     * @var array
     */
    protected $allowedClassnames = [
        \CommerceTeam\Commerce\Tree\CategoryTree\ArticleNode::class,
        \CommerceTeam\Commerce\Tree\CategoryTree\CategoryNode::class,
        \CommerceTeam\Commerce\Tree\CategoryTree\ProductNode::class,
    ];

    /**
     * Sets the data provider
     *
     * @return void
     */
    protected function initDataProvider()
    {
        /** @var $dataProvider \CommerceTeam\Commerce\ContextMenu\CategoryTree\ContextMenuDataProvider */
        $dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\ContextMenu\CategoryTree\ContextMenuDataProvider::class
        );
        $this->setDataProvider($dataProvider);
    }

    /**
     * Returns the actions for the given node information's
     *
     * @param \stdClass $nodeData
     *
     * @return array
     */
    public function getActionsForNodeArray($nodeData)
    {
        $className = $nodeData->serializeClassName;
        if (!in_array($className, $this->allowedClassnames)) {
            return [];
        }

        /** @var $node ArticleNode|CategoryNode|ProductNode */
        $node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, (array)$nodeData);
        $node->setRecord(
            \CommerceTeam\Commerce\Tree\CategoryTree\Commands::getNodeRecord($node->getType(), $node->getId())
        );
        $this->initDataProvider();
        $this->dataProvider->setContextMenuType('table.' . $node->getType());
        $actionCollection = $this->dataProvider->getActionsForNode($node);
        $actions = array();
        if ($actionCollection instanceof \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection) {
            $actions = $actionCollection->toArray();
        }
        return $actions;
    }
}

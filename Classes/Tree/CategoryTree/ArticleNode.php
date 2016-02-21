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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Node designated for the page tree
 */
class ArticleNode extends CategoryNode
{
    /**
     * Indicator if the node can have children's
     *
     * @var boolean
     */
    protected $allowChildren = false;

    /**
     * Returns the calculated id representation of this node
     *
     * @param string $prefix Defaults to 'a'
     * @return string
     */
    public function calculateNodeId($prefix = 'pa')
    {
        return $prefix . dechex($this->getId());
    }

    /**
     * @return string
     */
    public function getJumpUrl()
    {
        $params = '&edit[' . $this->getType() . '][' . $this->getId() . ']=edit';
        $id = FolderRepository::initFolders('Products');

        return BackendUtility::getModuleUrl('record_edit') . '&id=' . $id . $params . '&returnUrl=T3_THIS_LOCATION';
    }
}

<?php
namespace CommerceTeam\Commerce\Tree\OrderTree;

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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data Provider of the Page Tree
 */
class ExtdirectTreeDataProvider extends \TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider
{
    /**
     * Fetches the next tree level
     *
     * @param int $nodeId
     * @param \stdClass $nodeData
     * @return array
     */
    public function getNextTreeLevel($nodeId, $nodeData)
    {
        $this->initDataProvider();
        if ($nodeId === 'root') {
            $backendUser = $this->getBackendUserAuthentication();
            $mountPoints = $backendUser->uc['pageTree_temporaryMountPoint'];

            // use temporary mount point to only show orders page and subpages
            $backendUser->uc['pageTree_temporaryMountPoint'] = FolderRepository::initFolders(
                'Orders',
                FolderRepository::initFolders()
            );
            $nodeCollection = $this->dataProvider->getTreeMounts();

            $backendUser->uc['pageTree_temporaryMountPoint'] = $mountPoints;
        } else {
            /** @var $node PagetreeNode */
            $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
            $nodeCollection = $this->dataProvider->getNodes($node, $node->getMountPoint());
        }
        return $nodeCollection->toArray();
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

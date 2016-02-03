<?php
namespace CommerceTeam\Commerce\Tree\Pagetree;

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
 * Class \CommerceTeam\Commerce\Tree\Pagetree\ExtdirectSystemdataNavigationProvider
 */
class ExtdirectSystemdataNavigationProvider extends \TYPO3\CMS\Backend\Tree\AbstractExtJsTree
{
    /**
     * Data Provider
     *
     * @var \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider
     */
    protected $dataProvider = null;

    /**
     * Sets the data provider
     *
     * @return void
     */
    protected function initDataProvider()
    {
        /** @var $dataProvider \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider */
        $dataProvider = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\DataProvider::class);
        $this->setDataProvider($dataProvider);
    }

    /**
     * Returns the root node of the tree
     *
     * @return array
     */
    public function getRoot()
    {
        $this->initDataProvider();
        $node = $this->dataProvider->getRoot();
        return $node->toArray();
    }

    /**
     * Fetches the next tree level
     *
     * @param integer $nodeId
     * @param \stdClass $nodeData
     * @return array
     */
    public function getNextTreeLevel($nodeId, $nodeData)
    {
        $this->initDataProvider();
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array) $nodeData);
        if ($nodeId === 'root') {
            $nodeCollection = $this->dataProvider->getTreeMounts();
        } else {
            $nodeCollection = $this->dataProvider->getNodes($node, $node->getMountPoint());
        }
        return $nodeCollection->toArray();
    }

    /**
     * Returns
     *
     * @return array
     */
    public function getIndicators()
    {
        /** @var $indicatorProvider \TYPO3\CMS\Backend\Tree\Pagetree\Indicator */
        $indicatorProvider = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\Indicator::class);
        $indicatorHtmlArr = $indicatorProvider->getAllIndicators();
        $indicator = array(
            'html' => implode(' ', $indicatorHtmlArr),
            '_COUNT' => count($indicatorHtmlArr)
        );
        return $indicator;
    }

    /**
     * Returns the language labels, sprites and configuration options for the pagetree
     *
     * @return array
     */
    public function loadResources()
    {
        $indicators = $this->getIndicators();
        $configuration = array(
            'Configuration' => array(
                'hideFilter' => $this->getBackendUser()->getTSConfigVal('options.pageTree.hideFilter'),
                'displayDeleteConfirmation' => $this->getBackendUser()->jsConfirmation(4),
                'canDeleteRecursivly' => $this->getBackendUser()->uc['recursiveDelete'] == true,
                'disableIconLinkToContextmenu' => $this->getBackendUser()->getTSConfigVal(
                    'options.pageTree.disableIconLinkToContextmenu'
                ),
                'indicator' => $indicators['html'],
            ),
        );

        return $configuration;
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

<?php
namespace CommerceTeam\Commerce\Controller;

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

use CommerceTeam\Commerce\Tree\View\CategoryTreeElementCategoryTreeView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

class CategoryAjaxController implements LinkParameterProviderInterface
{
    /**
     * The local configuration array
     *
     * @var array
     */
    protected $conf = [];

    /**
     * Currently in the element selected items
     *
     * @var array
     */
    protected $selectedItems = [];

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->conf = $request->getParsedBody();
        $this->selectedItems = (array)$this->conf['selectedItems'];

        $content = '';
        switch ($this->conf['action']) {
            case 'getSubtree':
                $content = $this->getSubtreeAction();
                break;

            case 'storeState':
                $content = $this->storeStateAction();
                break;
        }

        $response->getBody()->write($content);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Gets the subtree for a given node
     *
     * @return string
     */
    protected function getSubtreeAction()
    {
        $pmParts = explode('_', $this->conf['PM']);

        $categoryTree = $this->getCategoryTreeObject();
        $categoryTree->initializePositionSaving();
        $categoryTree->getTree($pmParts[2], 1000);
        $categoryTree->ajaxCall = true;
        $tree = $categoryTree->printTree();

        return $tree;
    }

    /**
     * @return string
     */
    protected function storeStateAction()
    {
        $categoryTree = $this->getCategoryTreeObject();
        $categoryTree->initializePositionSaving();

        return '1';
    }

    /**
     * @return CategoryTreeElementCategoryTreeView
     */
    protected function getCategoryTreeObject()
    {
        $backendUser = $this->getBackendUserAuthentication();

        /** @var CategoryTreeElementCategoryTreeView $categoryTree */
        $categoryTree = GeneralUtility::makeInstance(CategoryTreeElementCategoryTreeView::class);
        $categoryTree->setLinkParameterProvider($this);
        $categoryTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $categoryTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $categoryTree->addField('navtitle');

        return $categoryTree;
    }


    /**
     * @return string
     */
    public function getScriptUrl()
    {
        return GeneralUtility::getIndpEnv('SCRIPT_NAME');
    }

    /**
     * @param array $values
     * @return array
     */
    public function getUrlParameters(array $values)
    {
        return $values;
    }

    /**
     * @param array $values Values to be checked
     *
     * @return bool Returns true if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return !empty($this->selectedItems) && in_array($values['uid'], $this->selectedItems);
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

<?php
namespace CommerceTeam\Commerce\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class extends the commerce module in the TYPO3 Backend to provide
 * convenient methods of editing of category permissions
 * (including category ownership (user and group)) via new
 * \TYPO3\CMS\Core\Http\AjaxRequestHandler facility.
 *
 * Class \CommerceTeam\Commerce\Controller\PermissionAjaxController
 *
 * @author 2007-2008 mehrwert <typo3@mehrwert.de>
 */
class PermissionAjaxController extends \TYPO3\CMS\Beuser\Controller\PermissionAjaxController
{
    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $extPath = ExtensionManagementUtility::extPath('beuser');

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths(array('default' => $extPath . 'Resources/Private/Partials'));
        $view->assign('pageId', $this->conf['page']);

        $content = '';
        // Basic test for required value
        if ($this->conf['page'] > 0) {
            // Init TCE for execution of update
            /** @var $tce DataHandler */
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            // Determine the scripts to execute
            switch ($this->conf['action']) {
                case 'show_change_owner_selector':
                    $content = $this->renderUserSelector(
                        (int)$this->conf['page'],
                        (int)$this->conf['ownerUid'],
                        $this->conf['username']
                    );
                    break;

                case 'change_owner':
                    $userId = $this->conf['new_owner_uid'];
                    if (is_int($userId)) {
                        // Prepare data to change
                        $data = array();
                        $data['pages'][$this->conf['page']]['perms_userid'] = $userId;
                        // Execute TCE Update
                        $tce->start($data, array());
                        $tce->process_datamap();

                        $view->setTemplatePathAndFilename(
                            $extPath . 'Resources/Private/Templates/PermissionAjax/ChangeOwner.html'
                        );
                        $view->assign('userId', $userId);
                        $usernameArray = BackendUtility::getUserNames('username', ' AND uid = ' . $userId);
                        $view->assign('username', $usernameArray[$userId]['username']);
                        $content = $view->render();
                    } else {
                        $response->getBody()->write('An error occurred: No page owner uid specified');
                        $response = $response->withStatus(500);
                    }
                    break;

                case 'show_change_group_selector':
                    $content = $this->renderGroupSelector(
                        (int)$this->conf['page'],
                        (int)$this->conf['groupUid'],
                        $this->conf['groupname']
                    );
                    break;

                case 'change_group':
                    $groupId = $this->conf['new_group_uid'];
                    if (is_int($groupId)) {
                        // Prepare data to change
                        $data = array();
                        $data['pages'][$this->conf['page']]['perms_groupid'] = $groupId;
                        // Execute TCE Update
                        $tce->start($data, array());
                        $tce->process_datamap();

                        $view->setTemplatePathAndFilename(
                            $extPath . 'Resources/Private/Templates/PermissionAjax/ChangeGroup.html'
                        );
                        $view->assign('groupId', $groupId);
                        $groupnameArray = BackendUtility::getGroupNames('title', ' AND uid = ' . $groupId);
                        $view->assign('groupname', $groupnameArray[$groupId]['title']);
                        $content = $view->render();
                    } else {
                        $response->getBody()->write('An error occurred: No page group uid specified');
                        $response = $response->withStatus(500);
                    }
                    break;

                case 'toggle_edit_lock':
                    // Prepare data to change
                    $data = array();
                    $data['tx_commerce_categories'][$this->conf['page']]['editlock'] =
                        $this->conf['editLockState'] === 1 ? 0 : 1;
                    // Execute TCE Update
                    $tce->start($data, array());
                    $tce->process_datamap();
                    $content = $this->renderToggleEditLock(
                        (int)$this->conf['page'],
                        $data['tx_commerce_categories'][$this->conf['page']]['editlock']
                    );
                    break;

                default:
                    if ($this->conf['mode'] === 'delete') {
                        $this->conf['permissions'] = (int)($this->conf['permissions'] - $this->conf['bits']);
                    } else {
                        $this->conf['permissions'] = (int)($this->conf['permissions'] + $this->conf['bits']);
                    }
                    // Prepare data to change
                    $data = array();
                    $data['tx_commerce_categories'][$this->conf['page']]['perms_' . $this->conf['who']] =
                        $this->conf['permissions'];
                    // Execute TCE Update
                    $tce->start($data, array());
                    $tce->process_datamap();

                    $view->setTemplatePathAndFilename(
                        $extPath . 'Resources/Private/Templates/PermissionAjax/ChangePermission.html'
                    );
                    $view->assign('permission', $this->conf['permissions']);
                    $view->assign('scope', $this->conf['who']);
                    $content = $view->render();
            }
        } else {
            $response->getBody()->write('This script cannot be called directly');
            $response = $response->withStatus(500);
        }
        $response->getBody()->write($content);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }
}

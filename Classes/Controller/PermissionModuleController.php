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

use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use CommerceTeam\Commerce\Tree\View\CategoryTreeView;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use \CommerceTeam\Commerce\Utility\BackendUtility as CommerceBackendUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend module page permissions
 */
class PermissionModuleController extends ActionController
{
    /**
     * @var string prefix for session
     */
    const SESSION_PREFIX = 'tx_Commerce_';

    /**
     * Category uid.
     *
     * @var int
     */
    public $categoryUid = 0;

    /**
     * @var int
     */
    protected $returnId;

    /**
     * @var int
     */
    protected $depth;

    /**
     * @var int
     */
    protected $lastEdited;

    /**
     * Number of levels to enable recursive settings for
     *
     * @var int
     */
    protected $getLevels = 10;

    /**
     * @var array
     */
    protected $pageInfo = [];

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Initialize action
     *
     * @return void
     */
    protected function initializeAction()
    {
        // determine depth parameter
        $this->depth = ((int)GeneralUtility::_GP('depth') > 0)
            ? (int) GeneralUtility::_GP('depth')
            : $this->getBackendUserAuthentication()->getSessionData(self::SESSION_PREFIX . 'depth');
        if ($this->request->hasArgument('depth')) {
            $this->depth = (int)$this->request->getArgument('depth');
        }
        $this->getBackendUserAuthentication()->setAndSaveSessionData(self::SESSION_PREFIX . 'depth', $this->depth);
        $this->lastEdited = GeneralUtility::_GP('lastEdited');
        $this->returnId = GeneralUtility::_GP('returnId');

        // Get category uid from control
        $controlFromGetPost = GeneralUtility::_GP('control');
        if (is_array($controlFromGetPost) && isset($controlFromGetPost['categoryUid'])) {
            $this->categoryUid = (int) $controlFromGetPost['categoryUid'];
        } elseif ($this->request->hasArgument('categoryUid')) {
            $this->categoryUid = (int)$this->request->getArgument('categoryUid');
        }
        // Loading current category record and checking access:
        $this->pageInfo = CommerceBackendUtility::readCategoryAccess($this->categoryUid, ' 1=1');
    }

    /**
     * Initializes view
     *
     * @param ViewInterface $view The view to be initialized
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->assign(
            'previewUrl',
            BackendUtility::viewOnClick(
                (int)$this->pageInfo['uid'],
                '',
                BackendUtility::BEgetRootLine((int)$this->pageInfo['uid'])
            )
        );

        // the view of the update action has a different view class
        if ($view instanceof BackendTemplateView) {
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Commerce/Permissions');
            $view->getModuleTemplate()->addJavaScriptCode(
                'jumpToUrl',
                '
                function jumpToUrl(URL) {
                    window.location.href = URL;
                    return false;
                }
                '
            );
            $this->registerDocHeaderButtons();
            $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
    }

    /**
     * Registers the Icons into the docheader
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerDocHeaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $extensionName = $currentRequest->getControllerExtensionName();
        if (empty($getVars)) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = ['id', 'M', $modulePrefix];
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);

        if ($this->categoryUid > 0) {
            $iconFactory = $this->view->getModuleTemplate()->getIconFactory();
            $viewButton = $buttonBar->makeLinkButton()
                ->setOnClick(BackendUtility::viewOnClick(
                    (int)$this->pageInfo['uid'],
                    '',
                    BackendUtility::BEgetRootLine((int)$this->pageInfo['uid'])
                ))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage'))
                ->setIcon($iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL))
                ->setHref('#');

            $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        if (!$this->categoryUid) {
            $this->pageInfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
        }

        if ($this->getBackendUserAuthentication()->workspace != 0) {
            // Adding section with the permission setting matrix:
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText',
                    'beuser'
                ),
                LocalizationUtility::translate(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning',
                    'beuser'
                ),
                FlashMessage::WARNING
            );
        }

        // depth options
        $depthOptions = [];
        $url = $this->uriBuilder->reset()->setArguments([
            'action' => 'index',
            'depth' => '__DEPTH__',
            'categoryUid' => $this->categoryUid
        ])->buildBackendUri();
        foreach ([1, 2, 3, 4, 10] as $depthLevel) {
            $depthOptions[$depthLevel] = $depthLevel . ' ' . LocalizationUtility::translate(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:levels',
                'beuser'
            );
        }
        $this->view->assign('depthBaseUrl', $url);
        $this->view->assign('depth', $this->depth);
        $this->view->assign('depthOptions', $depthOptions);

        $beUserArray = BackendUtility::getUserNames();
        $this->view->assign('beUsers', $beUserArray);
        $beGroupArray = BackendUtility::getGroupNames();
        $this->view->assign('beGroups', $beGroupArray);

        /** @var $tree CategoryTreeView */
        $tree = GeneralUtility::makeInstance(CategoryTreeView::class);
        $tree->init();
        $tree->addField('perms_user', true);
        $tree->addField('perms_group', true);
        $tree->addField('perms_everybody', true);
        $tree->addField('perms_userid', true);
        $tree->addField('perms_groupid', true);
        $tree->addField('hidden');
        $tree->addField('fe_group');
        $tree->addField('starttime');
        $tree->addField('endtime');
        $tree->addField('editlock');

        // Create the tree from $this->categoryUid
        if ($this->categoryUid) {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getIcon($this->categoryUid)];
        } else {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getRootIcon($this->pageInfo)];
        }
        $tree->getTree($this->categoryUid, $this->depth);
        $this->view->assign('viewTree', $tree->tree);

        // CSH for permissions setting
        $this->view->assign('cshItem', BackendUtility::cshItem(
            'xMOD_csh_corebe',
            'perm_module',
            null,
            '<span class="btn btn-default btn-sm">|</span>'
        ));
    }


    /**
     * Edit action
     *
     * @return void
     */
    public function editAction()
    {
        $this->view->assign('categoryUid', $this->categoryUid);
        $this->view->assign('depth', $this->depth);

        if (!$this->categoryUid) {
            $this->pageInfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
        }
        if ($this->getBackendUserAuthentication()->workspace != 0) {
            // Adding FlashMessage with the permission setting matrix:
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText',
                    'beuser'
                ),
                LocalizationUtility::translate(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning',
                    'beuser'
                ),
                FlashMessage::WARNING
            );
        }
        // Get usernames and groupnames
        $beGroupArray = BackendUtility::getListGroupNames('title,uid');
        $beUserArray  = BackendUtility::getUserNames();

        // Owner selector
        $beUserDataArray = [0 => LocalizationUtility::translate(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:selectNone',
            'beuser'
        )];
        foreach ($beUserArray as $uid => &$row) {
            $beUserDataArray[$uid] = $row['username'];
        }
        $beUserDataArray[-1] = LocalizationUtility::translate(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:selectUnchanged',
            'beuser'
        );
        $this->view->assign('currentBeUser', $this->pageInfo['perms_userid']);
        $this->view->assign('beUserData', $beUserDataArray);

        // Group selector
        $beGroupDataArray = [0 => LocalizationUtility::translate(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:selectNone',
            'beuser'
        )];
        foreach ($beGroupArray as $uid => $row) {
            $beGroupDataArray[$uid] = $row['title'];
        }
        $beGroupDataArray[-1] = LocalizationUtility::translate(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:selectUnchanged',
            'beuser'
        );
        $this->view->assign('currentBeGroup', $this->pageInfo['perms_groupid']);
        $this->view->assign('beGroupData', $beGroupDataArray);
        $this->view->assign('pageInfo', $this->pageInfo);
        $this->view->assign('returnId', $this->returnId);
        $this->view->assign('recursiveSelectOptions', $this->getRecursiveSelectOptions());
    }

    /**
     * Finding tree and offer setting of values recursively.
     *
     * @return array
     */
    protected function getRecursiveSelectOptions()
    {
        // Initialize tree object:
        /** @var CategoryTreeView $tree */
        $tree = GeneralUtility::makeInstance(CategoryTreeView::class);
        $tree->init();
        $tree->addField('perms_userid', true);
        $tree->makeHTML = 0;
        $tree->setRecs = 1;
        // Make tree:
        $tree->getTree($this->categoryUid, $this->getLevels, '');
        $options = [];
        $options[''] = '';
        // If there are a hierarchy of page ids, then...
        if ($this->getBackendUserAuthentication()->user['uid'] && !empty($tree->orig_ids_hierarchy)) {
            // Init:
            $labelRecursive = LocalizationUtility::translate(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:recursive',
                'beuser'
            );
            $labelLevels = LocalizationUtility::translate(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:levels',
                'beuser'
            );
            $labelPagesAffected = LocalizationUtility::translate(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_permission.xlf:categories_affected',
                'beuser'
            );
            $theIdListArr = [];
            // Traverse the number of levels we want to allow recursive
            // setting of permissions for:
            for ($a = $this->getLevels; $a > 0; $a--) {
                if (is_array($tree->orig_ids_hierarchy[$a])) {
                    foreach ($tree->orig_ids_hierarchy[$a] as $theId) {
                        $theIdListArr[] = $theId;
                    }
                    $lKey = $this->getLevels - $a + 1;
                    $options[implode(',', $theIdListArr)] = $labelRecursive . ' ' . $lKey . ' ' . $labelLevels .
                        ' (' . count($theIdListArr) . ' ' . $labelPagesAffected . ')';
                }
            }
        }
        return $options;
    }


    /**
     * Update action
     *
     * @param array $data
     * @param array $mirror
     * @return void
     */
    protected function updateAction(array $data, array $mirror)
    {
        if (!empty($data['categories'])) {
            foreach ($data['categories'] as $categoryUid => $properties) {
                // if the owner and group field shouldn't be touched, unset the option
                if ((int)$properties['perms_userid'] === -1) {
                    unset($properties['perms_userid']);
                }
                if ((int)$properties['perms_groupid'] === -1) {
                    unset($properties['perms_groupid']);
                }
                /** @var CategoryRepository $categoryRepository */
                $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
                $categoryRepository->updateRecord((int) $categoryUid, $properties);

                if (!empty($mirror['categories'][$categoryUid])) {
                    $mirrorCategoryUids = GeneralUtility::trimExplode(',', $mirror['categories'][$categoryUid]);
                    foreach ($mirrorCategoryUids as $mirrorCategoryUid) {
                        $categoryRepository->updateRecord((int) $mirrorCategoryUid, $properties);
                    }
                }
            }
        }
        $this->redirect('index', null, null, ['categoryUid' => $this->returnId, 'depth' => $this->depth]);
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

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}

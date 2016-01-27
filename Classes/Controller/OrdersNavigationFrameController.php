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
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main script class for the tree edit navigation frame.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\Navigation\OrdersViewHelper
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class OrdersNavigationFrameController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * Page tree.
     *
     * @var \CommerceTeam\Commerce\Tree\OrderTree
     */
    protected $pagetree;

    /**
     * Temporary mount point (record), if any.
     *
     * @var int
     */
    protected $activeTemporaryMountPoint = 0;

    /**
     * Current sub script.
     *
     * @var string
     */
    protected $currentSubScript;

    /**
     * Cmr Parameter.
     *
     * @var string
     */
    protected $cMR;

    /**
     * If not '' (blank) then it will clear (0) or set (>0) Temporary DB mount.
     *
     * @var string
     */
    protected $setTemporaryDatabaseMount;

    /**
     * Do highlight.
     *
     * @var bool
     */
    protected $doHighlight;

    /**
     * Document template.
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * @var ModuleTemplate
     */
    public $moduleTemplate;

    /**
     * Initiation of the class.
     *
     * @return void
     * @todo Check with User Permissions
     */
    public function init()
    {
        $backendUser = $this->getBackendUser();

        // Setting GPvars:
        $this->currentSubScript = GeneralUtility::_GP('currentSubScript');
        $this->cMR = GeneralUtility::_GP('cMR');
        $this->setTemporaryDatabaseMount = GeneralUtility::_GP('setTempDBmount');

        // Generate Folder if necessary
        // @todo move init folder somewhere else as its to hefty to try to create the folders over and over again
        \CommerceTeam\Commerce\Utility\FolderUtility::initFolders();

        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);

        // Create page tree object:
        $this->pagetree = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Tree\OrderTree::class);
        $this->pagetree->ext_IconMode = $backendUser->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
        $this->pagetree->ext_showPageId = $backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $this->pagetree->addField('alias');
        $this->pagetree->addField('shortcut');
        $this->pagetree->addField('shortcut_mode');
        $this->pagetree->addField('mount_pid');
        $this->pagetree->addField('mount_pid_ol');
        $this->pagetree->addField('nav_hide');
        $this->pagetree->addField('url');

        // Temporary DB mounts:
        // @todo fix this to match expectations of MOUNTS
        $this->pagetree->MOUNTS = array(
            \CommerceTeam\Commerce\Utility\BackendUtility::getOrderFolderUid()
        );
        $this->initializeTemporaryDatabaseMount();

        // Setting highlight mode:
        $this->doHighlight = !$backendUser->getTSConfigVal('options.pageTree.disableTitleHighlight');

        $this->initPage();
    }

    /**
     * Initializes the Page.
     *
     * @return void
     */
    public function initPage()
    {
        /**
         * Document template.
         *
         * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
         */
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:commerce/Resources/Private/Backend/mod_navigation.html');
        $this->doc->showFlashMessages = false;

        $subScript = $this->currentSubScript ? 'top.currentSubScript=unescape("' .
            rawurlencode($this->currentSubScript) . '");' : '';
        $highlight = $this->doHighlight ? 'hilight_row("commerce", highLightID);' : '';
        $formstyle = !$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) { linkObj.blur(); }';

        // Setting JavaScript for menu.
        $this->doc->JScode = $this->doc->wrapScriptTags(
            $subScript . '

            // Function, loading the list frame from navigation tree:
            function jumpTo(id, linkObj, highLightID) {
                var theUrl = top.TS.PATH_typo3 + top.currentSubScript + "&id=" + id;

                if (top.condensedMode) {
                    top.content.document.location = theUrl;
                } else {
                    parent.list_frame.document.location = theUrl;
                }

                ' . $highlight . '
                ' . $formstyle . '
                return false;
            }

            // Call this function, refresh_nav(), from another script in the backend if you
            // want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
            // See BackendUtility::getSetUpdateSignal()
            function refresh_nav() {
                window.setTimeout("_refresh_nav();", 0);
            }
            function _refresh_nav() {
                document.location="' . $this->pagetree->thisScript . '?unique=' . $GLOBALS['EXEC_TIME'] . '";
            }

                // Highlighting rows in the page tree:
            function hilight_row(frameSetModule, highLightID) {
                    // Remove old:
                theObj = document.getElementById(top.fsMod.navFrameHighlightedID[frameSetModule] + "_0");

                if (theObj) {
                    theObj.style.backgroundColor = "";
                }

                    // Set new:
                top.fsMod.navFrameHighlightedID[frameSetModule] = highLightID;
                theObj = document.getElementById(highLightID + "_0");

                if (theObj) {
                    theObj.style.backgroundColor = "' . GeneralUtility::modifyHTMLColorAll($this->doc->bgColor, -20) .
            '";
                }
            }

            ' . ($this->cMR ? "jumpTo(top.fsMod.recentIds['web'], '');" : '') . ';
        '
        );

    }

    /**
     * Main function, rendering the browsable page tree.
     *
     * @return void
     */
    public function main()
    {
        $this->getButtons();

        // Outputting page tree:
        $this->content .= $this->pagetree->getBrowsableTree();
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();

        $this->main();

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Create the panel of buttons for submitting the form
     * or otherwise perform operations.
     *
     * @return void
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle(
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload', true)
            )->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Getting temporary DB mount.
     *
     * @return void
     */
    protected function initializeTemporaryDatabaseMount()
    {
        $backendUser = $this->getBackendUser();

        // Set/Cancel Temporary DB Mount:
        if (strlen($this->setTemporaryDatabaseMount)) {
            $set = max($this->setTemporaryDatabaseMount, 0);
            // Setting...:
            if ($set > 0 && $backendUser->isInWebMount($set)) {
                $this->settingTemporaryMountPoint($set);
                // Clear:
            } else {
                $this->settingTemporaryMountPoint(0);
            }
        }

        // Getting temporary mount point ID:
        $temporaryMountPoint = (int) $backendUser->getSessionData('pageTree_temporaryMountPoint_orders');

        // If mount point ID existed and is within users
        // real mount points, then set it temporarily:
        if ($temporaryMountPoint && $backendUser->isInWebMount($temporaryMountPoint)) {
            $this->pagetree->MOUNTS = array($temporaryMountPoint);
            $this->activeTemporaryMountPoint = BackendUtility::readPageAccess(
                $temporaryMountPoint,
                $backendUser->getPagePermsClause(1)
            );
        }
    }

    /**
     * Setting temporary mount point.
     *
     * @param int $pageId Page id
     */
    protected function settingTemporaryMountPoint($pageId)
    {
        // Setting temporary mount point ID:
        $this->getBackendUser()->setAndSaveSessionData('pageTree_temporaryMountPoint_orders', (int) $pageId);
    }
}

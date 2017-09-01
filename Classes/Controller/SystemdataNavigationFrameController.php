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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Main script class for the systemData navigation frame.
 *
 * Class SystemdataNavigationFrameController
 */
class SystemdataNavigationFrameController extends BaseScriptClass
{
    /**
     * Has filter box.
     *
     * @var bool
     */
    protected $hasFilterBox = false;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'xMOD_csh_commercebe';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Initialization.
     */
    public function init()
    {
        $this->getLanguageService()->includeLLFile(
            'EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf'
        );

        $this->id = \CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders();

        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('commerce');

        $this->moduleTemplate->addJavaScriptCode('jumpToUrl', '
            function jumpTo(url, linkObj) {
                var theUrl = url;

                if (top.condensedMode) {
                    top.content.document.location = theUrl;
                } else {
                    parent.list_frame.document.location = theUrl;
                }

                return false;
            }
        ');
    }

    /**
     * Main method.
     */
    public function main()
    {
        $this->getButtons();

        $templatePathAndFilename = GeneralUtility::getFileAbsFileName(
            'EXT:commerce/Resources/Private/Backend/mod_systemdata_navigation.html'
        );
        $this->view->setTemplatePathAndFilename($templatePathAndFilename);

        $attributeUrl = BackendUtility::getModuleUrl(
            'commerce_systemdata_attribute',
            ['SET' => [
                'function' => 'CommerceTeam\Commerce\Controller\SystemdataModuleAttributeController'
            ]]
        );
        $manufacturerUrl = BackendUtility::getModuleUrl(
            'commerce_systemdata_manufacturer',
            ['SET' => [
                'function' => 'CommerceTeam\Commerce\Controller\SystemdataModuleManufacturerController'
            ]]
        );
        $supplierUrl = BackendUtility::getModuleUrl(
            'commerce_systemdata_supplier',
            ['SET' => [
                'function' => 'CommerceTeam\Commerce\Controller\SystemdataModuleSupplierController'
            ]]
        );
        $this->view->assign('attributeUrl', $attributeUrl);
        $this->view->assign('manufacturerUrl', $manufacturerUrl);
        $this->view->assign('supplierUrl', $supplierUrl);

        // Set content
        $this->content = $this->view->render();
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $_ the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $_, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();

        $this->main();

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Create the panel of buttons for submitting the
     * form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // CSH
        $contextSensitiveHelpButton = $buttonBar->makeHelpButton()
            ->setModuleName($this->moduleName)
            ->setFieldName('systemdata');
        $buttonBar->addButton($contextSensitiveHelpButton);

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle(
                htmlspecialchars($this->getLanguageService()->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload'
                ))
            )->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }
}

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
 * Class \CommerceTeam\Commerce\ViewHelpers\Navigation\SystemdataViewHelper
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
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
     * Constructor
     *
     * @return self
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * @return void
     */
    public static function render()
    {
        $instance = GeneralUtility::makeInstance(self::class);
        $instance->main();
        $instance->printContent();
    }

    /**
     * Initialization.
     *
     * @return void
     */
    public function init()
    {
        $this->getLanguageService()->includeLLFile(
            'EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xml'
        );

        $this->id = reset(\CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders(
            'Commerce',
            'commerce'
        ));

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

        $this->moduleTemplate->addJavaScriptCode('mainJsFunctions', '
            if (top.fsMod) {
                top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
                top.fsMod.navFrameHighlightedID["web"] = "pages' . (int)$this->id .
            '_"+top.fsMod.currentBank; ' . (int)$this->id . ';
            }
        ');
    }

    /**
     * Main method.
     *
     * @return void
     */
    public function main()
    {
        $this->makeButtons();

        $templatePathAndFilename = GeneralUtility::getFileAbsFileName(
            'EXT:commerce/Resources/Private/Backend/mod_systemdata_navigation.html'
        );
        $this->view->setTemplatePathAndFilename($templatePathAndFilename);

        $attributeUrl = BackendUtility::getModuleUrl('commerce_systemdata', array('SET' => array('function' => 1)));
        $manufacturerUrl = BackendUtility::getModuleUrl('commerce_systemdata', array('SET' => array('function' => 2)));
        $supplierUrl = BackendUtility::getModuleUrl('commerce_systemdata', array('SET' => array('function' => 3)));
        $this->view->assign('attributeUrl', $attributeUrl);
        $this->view->assign('manufacturerUrl', $manufacturerUrl);
        $this->view->assign('supplierUrl', $supplierUrl);

        // Set content
        $this->moduleTemplate->setContent($this->view->render());
    }

    /**
     * Outputting the accumulated content to screen.
     *
     * @return void
     */
    public function printContent()
    {
        echo $this->moduleTemplate->renderContent();
    }

    /**
     * Create the panel of buttons for submitting the
     * form or otherwise perform operations.
     *
     * @return array all available buttons as an assoc. array
     */
    protected function makeButtons()
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
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload', true)
            )->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }


    /**
     * Get back path.
     *
     * @return string
     */
    protected function getBackPath()
    {
        return $GLOBALS['BACK_PATH'];
    }
}

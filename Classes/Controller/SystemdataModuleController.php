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

use CommerceTeam\Commerce\Domain\Repository\SysRefindexRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Template\ModuleTemplate;

/**
 * Module 'Systemdata' for the 'commerce' extension.
 *
 * Class \CommerceTeam\Commerce\Controller\SystemdataModuleController
 */
abstract class SystemdataModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * @var StandaloneView
     */
    public $view;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The name of the module
     *
     * @var string
     */
    public $moduleName = 'commerce_systemdata';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    public $moduleTemplate;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var bool
     */
    public $access = false;

    /**
     * Page record.
     *
     * @var array
     */
    public $pageinfo;

    /**
     * Marker.
     *
     * @var array
     */
    public $markers = [];

    /**
     * @var int
     */
    protected $fixedL = 30;

    /**
     * Reference count.
     *
     * @var array
     */
    protected $referenceCount = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('commerce');

        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile(
            'EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf'
        );

        $this->MCONF = [
            'name' => $this->moduleName,
        ];
    }

    /**
     * Initialization.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->id = FolderRepository::initFolders('Attributes', FolderRepository::initFolders());
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
    }

    /**
     * Main method.
     *
     * @return void
     */
    public function main()
    {
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->access = is_array($this->pageinfo);

        if ($this->id && $this->access) {
            /** @noinspection PhpInternalEntityUsedInspection */
            $requestUri = $this->moduleTemplate->redirectUrls(GeneralUtility::getIndpEnv('REQUEST_URI'));

            // JavaScript
            $this->moduleTemplate->addJavaScriptCode(
                'SystemdataModuleController',
                '
                function jumpExt(URL, anchor) {
                    var anc = anchor ? anchor : "";
                    window.location.href = URL + (T3_THIS_LOCATION ? "&returnUrl=" + T3_THIS_LOCATION : "") + anc;
                    return false;
                }
                function jumpSelf(URL) {
                    window.location.href = URL + (T3_RETURN_URL ? "&returnUrl=" + T3_RETURN_URL : "");
                    return false;
                }
                function jumpToUrl(URL) {
                    window.location.href = URL;
                    return false;
                }

                function setHighlight(id) {
                    if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
                        top.content.nav_frame.refresh_nav();
                    }
                }
                ' . $requestUri . '
                function editRecords(table, idList, addParams, CBflag) {
                    window.location.href = "'
                . BackendUtility::getModuleUrl(
                    'record_edit',
                    ['returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')]
                )
                . '&edit[" + table + "][" + idList + "]=edit" + addParams;
                }
                function editList(table, idList) {
                    var list = "";

                    // Checking how many is checked, how many is not
                    var pointer = 0;
                    var pos = idList.indexOf(",");
                    while (pos != -1) {
                        if (cbValue(table + "|" + idList.substr(pointer, pos-pointer))) {
                            list += idList.substr(pointer, pos-pointer) + ",";
                        }
                        pointer = pos + 1;
                        pos = idList.indexOf(",", pointer);
                    }
                    if (cbValue(table + "|" + idList.substr(pointer))) {
                        list += idList.substr(pointer) + ",";
                    }

                    return list ? list : idList;
                }
                '
            );

            $this->moduleTemplate->addJavaScriptCode(
                'mainJsFunctions',
                '
                function deleteRecord(table,id,url) {   //
                    if (confirm(' . GeneralUtility::quoteJSvalue($this->getLanguageService()->getLL('deleteWarning'))
                . ')) {
                        window.location.href = ' .
                GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_db') . '&cmd[')
                . ' + table + "][ " + id + "][delete]=1&redirect=" + escape(url) + "&prErr=1&uPT=1";
                    }
                    return false;
                }
                '
            );

            $this->content .= $this->getSubModuleContent();
            $this->getButtons();
        } else {
            $this->content = '<h1>' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '</h1>';
            $this->content .= 'Access denied or commerce pages not created yet!';

            $this->getButtons();
        }
    }

    /**
     * Injects the request object for the current request or subrequest
     * Simply calls main() and init() and outputs the content
     *
     * @param ServerRequestInterface $_ the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $_, ResponseInterface $response)
    {
        $functionClassname = $this->getFunctionClassname();
        if ($functionClassname !== '' && $functionClassname != static::class) {
            $function = GeneralUtility::makeInstance($functionClassname);
        } else {
            $function = $this;
        }

        $function->init();
        $GLOBALS['SOBE'] = $function;
        $function->main();
        $function->moduleTemplate->setContent($function->content);
        $response->getBody()->write($function->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @return string
     */
    protected function getFunctionClassname()
    {
        $this->menuConfig();
        $this->handleExternalFunctionValue();
        return isset($this->extClassConf['name']) ? $this->extClassConf['name'] : '';
    }

    /**
     * Create the panel of buttons for submitting the form or other operations.
     *
     * @return void
     */
    public function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Shortcut
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName($this->moduleName)
                ->setGetVariables(
                    [
                        'id',
                        'M',
                        'imagemode',
                        'pointer',
                        'table',
                        'search_field',
                        'search_levels',
                        'showLimit',
                        'sortField',
                        'sortRev',
                    ]
                )->setSetVariables(array_keys($this->MOD_MENU));
            $buttonBar->addButton($shortcutButton);
        }

        // Add CSH (Context Sensitive Help) icon to tool bar
        if (!strlen($this->id)) {
            $cshKey = 'list_module_noId';
        } elseif (!$this->id) {
            $cshKey = 'list_module_root';
        } else {
            $cshKey = 'list_module';
        }
        $contextSensitiveHelpButton = $buttonBar->makeHelpButton()
            ->setModuleName($this->moduleName)
            ->setFieldName($cshKey);
        $buttonBar->addButton($contextSensitiveHelpButton);

        // New
        if ($this->access) {
            $params = '&edit[' . $this->table . '][' . $this->id . ']=new';
            $onClick = htmlspecialchars(BackendUtility::editOnClick($params, '', -1));
            /** @var LinkButton $newRecordButton */
            $newRecordButton = $buttonBar->makeLinkButton();
            $newRecordButton->setHref('#')->setOnClick($onClick)
                ->setTitle(
                    htmlspecialchars($this->getLanguageService()->sL(
                        'LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral'
                    ))
                )
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL));
            $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle(
                htmlspecialchars($this->getLanguageService()->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload'
                ))
            )
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * @return mixed
     */
    abstract protected function getSubModuleContent();

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param string $tableName Table name of the referenced record
     * @param int $uid UID of the referenced record, must be > 0
     *
     * @return int the number of references to record $uid in table
     *      $tableName, will be >= 0
     */
    protected function getReferenceCount($tableName, $uid)
    {
        if (!isset($this->referenceCount[$tableName][$uid])) {
            /** @var SysRefindexRepository $sysRefindexRepository */
            $sysRefindexRepository = $this->getObjectManager()->get(SysRefindexRepository::class);
            $this->referenceCount[$tableName][$uid] = $sysRefindexRepository->countByTablenameUid($tableName, $uid);
        }

        return $this->referenceCount[$tableName][$uid];
    }


    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager(): \TYPO3\CMS\Extbase\Object\ObjectManager
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Extbase\Object\ObjectManager::class
        );
        return $objectManager;
    }
}

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
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Template\ModuleTemplate;

/**
 * Module 'Systemdata' for the 'commerce' extension.
 *
 * Class \CommerceTeam\Commerce\Controller\SystemdataModuleController
 *
 * @author 2005-2013 Ingo Schmitt <is@marketing-factory.de>
 */
class SystemdataModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
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
     * @var SystemdataAttributesModuleFunctionController
     */
    public $extObj;

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
        $commercePid = FolderRepository::initFolders();
        $this->id = FolderRepository::initFolders('Attributes', $commercePid);
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
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
                ' . $this->moduleTemplate->redirectUrls(GeneralUtility::getIndpEnv('REQUEST_URI')) . '
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
                . ' + table + "][ " + id + "][delete]=1&redirect=" + escape(url) + "&vC='
                . $this->getBackendUser()->veriCode() . '&prErr=1&uPT=1";
                    }
                    return false;
                }
                '
            );

            $this->content .= '<h1>' . $this->getLanguageService()->sL($this->extClassConf['title']) . '</h1>';
            $this->extObjContent();
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
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();

        // Checking for first level external objects
        $this->checkExtObj();

        $this->main();

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
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
            $params = '&edit[' . $this->extObj->table . '][' . $this->id . ']=new';
            $onClick = htmlspecialchars(BackendUtility::editOnClick($params, '', -1));
            $newRecordButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setOnClick($onClick)
                ->setTitle(
                    $this->getLanguageService()->sL(
                        'LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral',
                        true
                    )
                )->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL));
            $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle(
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload', true)
            )->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

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
    public function getReferenceCount($tableName, $uid)
    {
        if (!isset($this->referenceCount[$tableName][$uid])) {
            $numberOfReferences = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                'sys_refindex',
                'ref_table = ' . $this->getDatabaseConnection()->fullQuoteStr($tableName, 'sys_refindex') .
                ' AND ref_uid = ' . (int) $uid . ' AND deleted = 0'
            );

            $this->referenceCount[$tableName][$uid] = $numberOfReferences;
        }

        return $this->referenceCount[$tableName][$uid];
    }
}

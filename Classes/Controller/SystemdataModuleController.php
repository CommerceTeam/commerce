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
use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * PopView id - for opening a window with the page
     *
     * @var bool
     */
    public $popView;

    /**
     * @var int
     */
    public $id = 0;

    /**
     * Page record.
     *
     * @var array
     */
    public $pageinfo;

    /**
     * Containing the Root-Folder-Pid of Commerce.
     *
     * @var int
     */
    public $newRecordPid;

    /**
     * Attribute page id.
     *
     * @var int
     */
    public $attributePid;

    /**
     * Table to be used in new link.
     *
     * @var string
     */
    protected $tableForNewLink;

    /**
     * Marker.
     *
     * @var array
     */
    public $markers = array();

    /**
     * Document template.
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Pages-select clause
     *
     * @var string
     */
    public $perms_clause;

    /**
     * Reference count.
     *
     * @var array
     */
    protected $referenceCount = array();

    /**
     * Content for module accumulated here.
     *
     * @var string
     */
    public $content;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'commerce_systemdata';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Constructor
     *
     * @return self
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile(
            'EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xml'
        );

        $this->MCONF = array(
            'name' => $this->moduleName,
        );
    }

    /**
     * Initialization.
     *
     * @return void
     */
    public function init()
    {
        $this->newRecordPid = $this->id = FolderRepository::initFolders('Commerce', 'commerce');
        $this->attributePid = FolderRepository::initFolders('Attributes', 'commerce', $this->id);
        $this->popView = GeneralUtility::_GP('popView');

        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);

        $this->menuConfig();
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = array(
            'function' => array(
                '1' => 'attributes',
                '2' => 'manufacturer',
                '3' => 'supplier',
            ),
        );
        parent::menuConfig();
    }

    /**
     * Main method.
     *
     * @return void
     */
    public function main()
    {
        // Access check!
        // The page will show only if there is a valid page and if user may access it
        $access = is_array($this->pageinfo) ? 1 : 0;

        $content = '';
        if ($this->id && $access) {
            // JavaScript
            $this->moduleTemplate->addJavaScriptCode('jumpToUrl', '
                function jumpToUrl(URL,formEl) {
                    if (document.editform && TBE_EDITOR.isFormChanged)  {
                        // Check if the function exists... (works in all browsers?)
                        if (!TBE_EDITOR.isFormChanged()) {
                            window.location.href = URL;
                        } else if (formEl) {
                            if (formEl.type == "checkbox") formEl.checked = formEl.checked ? 0 : 1;
                        }
                    } else {
                        window.location.href = URL;
                    }
                }
            ');

            $this->moduleTemplate->addJavaScriptCode('mainJsFunctions', '
                if (top.fsMod) {
                    top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
                    top.fsMod.navFrameHighlightedID["web"] = "pages' . (int)$this->id .
                        '_"+top.fsMod.currentBank; ' . (int)$this->id . ';
                }
                ' . (
                    $this->popView ?
                    BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id)) :
                    ''
                ) . '
                function deleteRecord(table,id,url) {   //
                    if (confirm(' . GeneralUtility::quoteJSvalue($this->getLanguageService()->getLL('deleteWarning')) .
                        ')) {
                        window.location.href = ' .
                GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_db') . '&cmd[') .
                ' + table + "][ " + id + "][delete]=1&redirect=" + escape(url) + "&vC=' .
                $this->getBackendUser()->veriCode() . '&prErr=1&uPT=1";
                    }
                    return false;
                }
            ');

            // Render content:
            $content .= $this->getModuleContent();

            $this->getButtons();
        } else {
            $this->moduleTemplate->addJavaScriptCode(
                'mainJsFunctions',
                'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';'
            );

            $content .= '<h1>' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '</h1>';
            $content .= 'Access denied or commerce pages not created yet!';
        }

        // Set content
        $this->moduleTemplate->setContent($content);
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
        $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue(
            BackendUtility::getModuleUrl('db_new', [
                'id' => $this->id,
                'edit' => array(
                    'tx_commerce_' . $this->tableForNewLink => array(
                        $this->newRecordPid => 'new'
                    )
                )
            ])
        ) . ');';
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

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle(
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload', true)
            )->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Generates the module content.
     *
     * @return string
     */
    protected function getModuleContent()
    {
        switch ((string) $this->MOD_SETTINGS['function']) {
            case '2':
                $content = $this->getManufacturerListing();
                break;

            case '3':
                $content = $this->getSupplierListing();
                break;

            case '1':
            default:
                $this->newRecordPid = $this->attributePid;
                $content = $this->getAttributeListing();
        }

        return $content;
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
        $this->main();

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Render attribute listing.
     *
     * @return string
     */
    protected function getAttributeListing()
    {
        $language = $this->getLanguageService();

        $headerRow = '<tr><td class="bgColor6" colspan="3"><strong>' . $language->getLL('title_attributes') .
            '</strong></td><td class="bgColor6"><strong>' . $language->getLL('title_values') . '</strong></td></tr>';

        $result = $this->fetchAttributes();
        $attributeRows = $this->renderAttributeRows($result);

        $this->tableForNewLink = 'attributes';

        return '<table>' . $headerRow . $attributeRows . '</table>';
    }

    /**
     * Fetch attributes from db.
     *
     * @return \mysqli_result
     */
    protected function fetchAttributes()
    {
        return $this->getDatabaseConnection()->exec_SELECTquery(
            '*',
            'tx_commerce_attributes',
            'pid = ' . (int) $this->attributePid .
            ' AND hidden = 0 AND deleted = 0 and (sys_language_uid = 0 OR sys_language_uid = -1)',
            '',
            'internal_title, title'
        );
    }

    /**
     * Fetch attribute translation.
     *
     * @param int $uid Attribute uid
     *
     * @return \mysqli_result
     */
    protected function fetchAttributeTranslation($uid)
    {
        return $this->getDatabaseConnection()->exec_SELECTquery(
            '*',
            'tx_commerce_attributes',
            'pid = ' . (int) $this->attributePid .
            ' AND hidden = 0 AND deleted = 0 AND sys_language_uid != 0 and l18n_parent =' . (int) $uid,
            '',
            'sys_language_uid'
        );
    }

    /**
     * Render attribute rows.
     *
     * @param \mysqli_result $result Result
     *
     * @return string
     */
    protected function renderAttributeRows(\mysqli_result $result)
    {
        $language = $this->getLanguageService();
        $database = $this->getDatabaseConnection();

        /**
         * Record list.
         *
         * @var \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $recordList
         */
        $recordList = GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class);
        $recordList->backPath = $this->getBackPath();
        $recordList->initializeLanguages();

        $output = '';

        $table = 'tx_commerce_attributes';
        while (($attribute = $database->sql_fetch_assoc($result))) {
            $refCountMsg = BackendUtility::referenceCount(
                $table,
                $attribute['uid'],
                ' ' . $language->sL(
                    'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
                ),
                $this->getReferenceCount($table, $attribute['uid'])
            );
            $editParams = '&edit[' . $table . '][' . (int) $attribute['uid'] . ']=edit';
            $deleteParams = '&cmd[' . $table . '][' . (int) $attribute['uid'] . '][delete]=1';

            $output .= '<tr><td class="bgColor4" align="center" valign="top"> ' .
                BackendUtility::thumbCode($attribute, 'tx_commerce_attributes', 'icon', $this->getBackPath()) . '</td>';
            if ($attribute['internal_title']) {
                $output .= '<td valign="top" class="bgColor4"><strong>' .
                    htmlspecialchars($attribute['internal_title']) . '</strong> (' .
                    htmlspecialchars($attribute['title']) . ')';
            } else {
                $output .= '<td valign="top" class="bgColor4"><strong>' .
                    htmlspecialchars($attribute['title']) . '</strong>';
            }

            $catCount = $this->fetchRelationCount('tx_commerce_categories_attributes_mm', $attribute['uid']);
            $proCount = $this->fetchRelationCount('tx_commerce_products_attributes_mm', $attribute['uid']);

            // Select language versions
            $resLocalVersion = $this->fetchAttributeTranslation($attribute['uid']);
            if ($database->sql_num_rows($resLocalVersion)) {
                $output .= '<table >';
                while (($localAttributes = $database->sql_fetch_assoc($resLocalVersion))) {
                    $output .= '<tr><td>&nbsp;';
                    $output .= '</td><td>';
                    if ($localAttributes['internal_title']) {
                        $output .= htmlspecialchars($localAttributes['internal_title']) .
                            ' (' . htmlspecialchars($localAttributes['title']) . ')';
                    } else {
                        $output .= htmlspecialchars($localAttributes['title']);
                    }
                    $output .= '</td><td>';
                    $output .= $recordList->languageFlag($localAttributes['sys_language_uid']);
                    $output .= '</td></tr>';
                }
                $output .= '</table>';
            }

            $output .= '<br />' . $language->getLL('usage');
            $output .= ' <strong>' . $language->getLL('categories') . '</strong>: ' . $catCount;
            $output .= ' <strong>' . $language->getLL('products') . '</strong>: ' . $proCount;
            $output .= '</td>';

            $onClickAction = 'onclick="' .
                htmlspecialchars(BackendUtility::editOnClick($editParams, $this->getBackPath(), -1)) . '"';

            $output .= '<td><a href="#" ' . $onClickAction . '>' .
                $this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-open',
                    Icon::SIZE_SMALL,
                    array('title' => $language->getLL('edit', true))
                ) .
                '</a>';
            $output .= '<a href="#" onclick="' . htmlspecialchars(
                'if (confirm(' . GeneralUtility::quoteJSvalue(
                    $language->getLL('deleteWarningManufacturer') . ' "' . $attribute['title'] . '" ' . $refCountMsg
                ) . ')) {jumpToUrl(\'' . BackendUtility::getLinkToDataHandlerAction($deleteParams, -1) .
                '\');} return false;'
            ) . '">' .
                $this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-edit-delete',
                    Icon::SIZE_SMALL,
                    array('title' => $language->getLL('delete', true))
                ) .
                '</a>';

            $output .= '</td><td>';

            if ($attribute['has_valuelist'] == 1) {
                $valueRes = $database->exec_SELECTquery(
                    '*',
                    'tx_commerce_attribute_values',
                    'attributes_uid = ' . (int) $attribute['uid'] . ' AND hidden = 0 AND deleted = 0',
                    '',
                    'sorting'
                );
                if ($database->sql_num_rows($valueRes)) {
                    $output .= '<table border="0">';
                    while (($value = $database->sql_fetch_assoc($valueRes))) {
                        $output .= '<tr><td>' . htmlspecialchars($value['value']) . '</td></tr>';
                    }
                    $output .= '</table>';
                } else {
                    $output .= $language->getLL('no_values');
                }
            } else {
                $output .= $language->getLL('no_valuelist');
            }

            $output .= '</td></tr>';
        }

        return $output;
    }

    /**
     * Generates a list of all saved Manufacturers.
     *
     * @return string
     */
    protected function getManufacturerListing()
    {
        $fields = explode(',', SettingsFactory::getInstance()->getExtConf('coManufacturers'));

        $headerRow = '<tr><td></td>';
        foreach ($fields as $field) {
            $headerRow .= '<td class="bgColor6"><strong>' . $this->getLanguageService()->sL(
                BackendUtility::getItemLabel('tx_commerce_manufacturer', htmlspecialchars($field))
            ) . '</strong></td>';
        }
        $headerRow .= '</tr>';

        $result = $this->fetchDataByTable('tx_commerce_manufacturer');
        $manufacturerRows = $this->renderManufacturerRows($result, $fields);

        $this->tableForNewLink = 'manufacturer';

        return '<table>' . $headerRow . $manufacturerRows . '</table>';
    }

    /**
     * Render manufacturer row.
     *
     * @param \mysqli_result $result Result
     * @param array $fields Fields
     *
     * @return string
     */
    protected function renderManufacturerRows(\mysqli_result $result, array $fields)
    {
        $language = $this->getLanguageService();
        $output = '';

        $table = 'tx_commerce_manufacturer';
        while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($result))) {
            $refCountMsg = BackendUtility::referenceCount(
                $table,
                $row['uid'],
                ' ' . $language->sL(
                    'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
                ),
                $this->getReferenceCount($table, $row['uid'])
            );
            $editParams = '&edit[' . $table . '][' . (int) $row['uid'] . ']=edit';
            $deleteParams = '&cmd[' . $table . '][' . (int) $row['uid'] . '][delete]=1';

            $onClickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick(
                $editParams,
                $this->getBackPath(),
                -1
            )) . '"';
            $output .= '<tr><td><a href="#" ' . $onClickAction . ' title="' . $language->getLL('edit', true) . '">' .
                $this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-open',
                    Icon::SIZE_SMALL
                ) .
                '</a>';

            $onClickAction = 'onclick="' . htmlspecialchars(
                'if (confirm(' . GeneralUtility::quoteJSvalue(
                    $language->getLL('deleteWarningManufacturer') . ' "' . htmlspecialchars($row['title']) . '" ' .
                    $refCountMsg
                ) . ')) {jumpToUrl(\'' . BackendUtility::getLinkToDataHandlerAction($deleteParams, -1) .
                '\');} return false;'
            ) . '"';
            $output .= '<a href="#" ' . $onClickAction . ' title="' . $language->getLL('delete', true) . '">' .
                $this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-edit-delete',
                    Icon::SIZE_SMALL
                ) .
                '</a>';
            $output .= '</td>';

            foreach ($fields as $field) {
                $output .= '<td valign="top" class="bgColor4"><strong>' . htmlspecialchars($row[$field]) . '</strong>';
            }

            $output .= '</td></tr>';
        }

        return $output;
    }

    /**
     * Generates a list of all saved Suppliers.
     *
     * @return string
     */
    protected function getSupplierListing()
    {
        $fields = explode(',', SettingsFactory::getInstance()->getExtConf('coSuppliers'));

        $headerRow = '<tr><td></td>';
        foreach ($fields as $field) {
            $headerRow .= '<td class="bgColor6"><strong>' . $this->getLanguageService()->sL(
                BackendUtility::getItemLabel('tx_commerce_supplier', htmlspecialchars($field))
            ) . '</strong></td>';
        }
        $headerRow .= '</tr>';

        $result = $this->fetchDataByTable('tx_commerce_supplier');
        $supplierRows = $this->renderSupplierRows($result, $fields);

        $this->tableForNewLink = 'supplier';

        return '<table>' . $headerRow . $supplierRows . '</table>';
    }

    /**
     * Render supplier row.
     *
     * @param \mysqli_result $result Result
     * @param array $fields Fields
     *
     * @return string
     */
    protected function renderSupplierRows(\mysqli_result $result, array $fields)
    {
        $language = $this->getLanguageService();
        $output = '';

        $table = 'tx_commerce_supplier';
        while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($result))) {
            $refCountMsg = BackendUtility::referenceCount(
                $table,
                $row['uid'],
                ' ' . $language->sL('LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'),
                $this->getReferenceCount($table, $row['uid'])
            );
            $editParams = '&edit[' . $table . '][' . (int) $row['uid'] . ']=edit';
            $deleteParams = '&cmd[' . $table . '][' . (int) $row['uid'] . '][delete]=1';

            $onClickAction = 'onclick="' . htmlspecialchars(
                BackendUtility::editOnClick($editParams, $this->getBackPath(), -1)
            ) . '"';
            $output .= '<tr><td><a href="#" ' . $onClickAction . ' title="' . $language->getLL('edit', true) . '">' .
                $this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-open',
                    Icon::SIZE_SMALL
                ) . '</a>';

            $onClickAction = 'onclick="' . htmlspecialchars(
                'if (confirm(' . GeneralUtility::quoteJSvalue(
                    $language->getLL('deleteWarningSupplier') . ' "' . htmlspecialchars($row['title']) .
                    '" ' . $refCountMsg
                ) . ')) {jumpToUrl(\'' . BackendUtility::getLinkToDataHandlerAction($deleteParams, -1) .
                '\');} return false;'
            ) . '"';
            $output .= '<a href="#" ' . $onClickAction . ' title="' . $language->getLL('delete', true) . '">' .
                $this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-edit-delete',
                    Icon::SIZE_SMALL
                ) . '</a>';
            $output .= '</td>';

            foreach ($fields as $field) {
                $output .= '<td valign="top" class="bgColor4"><strong>' . htmlspecialchars($row[$field]) . '</strong>';
            }

            $output .= '</td></tr>';
        }

        return $output;
    }

    /**
     * Fetch data for table.
     *
     * @param string $table Table
     *
     * @return \mysqli_result
     */
    protected function fetchDataByTable($table)
    {
        return $this->getDatabaseConnection()->exec_SELECTquery(
            '*',
            $table,
            'pid = ' . (int) $this->newRecordPid . ' AND hidden = 0 AND deleted = 0',
            '',
            'title'
        );
    }

    /**
     * Fetch the relation count.
     *
     * @param string $table Table
     * @param int $uidForeign Foreign uid
     *
     * @return int
     */
    protected function fetchRelationCount($table, $uidForeign)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'COUNT(*) AS count',
            $table,
            'uid_foreign = ' . (int) $uidForeign
        );

        return $row['count'];
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
    protected function getReferenceCount($tableName, $uid)
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


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
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

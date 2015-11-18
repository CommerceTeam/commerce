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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Page record.
     *
     * @var array
     */
    public $pageRow;

    /**
     * Containing the Root-Folder-Pid of Commerce.
     *
     * @var int
     */
    public $modPid;

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
     * Reference count.
     *
     * @var array
     */
    protected $referenceCount = array();

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
        parent::init();

        $language = $this->getLanguageService();
        $language->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xml');

        $this->id = $this->modPid = (int) reset(FolderRepository::initFolders('Commerce', 'commerce'));
        $this->attributePid = (int) reset(
            FolderRepository::initFolders('Attributes', 'commerce', $this->modPid)
        );

        $this->MCONF = $GLOBALS['MCONF'];

        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->pageRow = BackendUtility::readPageAccess($this->id, $this->perms_clause);

        $this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
        $this->doc->backPath = $this->getBackPath();
        $this->doc->docType = 'xhtml_trans';
        $this->doc->setModuleTemplate(PATH_TXCOMMERCE . 'Resources/Private/Backend/mod_index.html');
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
        $listUrl = GeneralUtility::getIndpEnv('REQUEST_URI');

        // Access check!
        // The page will show only if there is a valid page and if user may access it
        if ($this->id && (is_array($this->pageRow) ? 1 : 0)) {
            // JavaScript
            $this->doc->JScode = $this->doc->wrapScriptTags('
                script_ended = 0;
                function jumpToUrl(URL) {
                    document.location = URL;
                }
                function deleteRecord(table, id, url, warning) {
                    if (
                        confirm(eval(warning))
                    ) {
                        window.location.href = "' . $this->getBackPath() .
                'tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url);
                    }
                    return false;
                }
                ' . $this->doc->redirectUrls($listUrl) . '
            ');

            $this->doc->postCode = $this->doc->wrapScriptTags('
                script_ended = 1;
                if (top.fsMod) {
                    top.fsMod.recentIds["web"] = ' . (int) $this->id . ';
                }
            ');

            $this->doc->inDocStylesArray['mod_systemdata'] = '';

                // Render content:
            $this->moduleContent();
        } else {
            $this->content = 'Access denied or commerce pages not created yet!';
        }

        $docHeaderButtons = $this->getHeaderButtons();

        $markers = array(
            'CSH' => $docHeaderButtons['csh'],
            'CONTENT' => $this->content,
        );
        $markers['FUNC_MENU'] = $this->doc->funcMenu(
            '',
            BackendUtility::getFuncMenu(
                $this->id,
                'SET[function]',
                $this->MOD_SETTINGS['function'],
                $this->MOD_MENU['function']
            )
        );

        // put it all together
        $this->content = $this->doc->startPage($this->getLanguageService()->getLL('title'));
        $this->content .= $this->doc->moduleBody($this->pageRow, $docHeaderButtons, $markers);
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);
    }

    /**
     * Create the panel of buttons for submitting the form or other operations.
     *
     * @return array all available buttons as an assoc. array
     */
    public function getHeaderButtons()
    {
        $buttons = array(
            'csh' => '',
                // group left 1
            'level_up' => '',
            'back' => '',
                // group left 2
            'new_record' => '',
            'paste' => '',
                // group left 3
            'view' => '',
            'edit' => '',
            'move' => '',
            'hide_unhide' => '',
                // group left 4
            'csv' => '',
            'export' => '',
                // group right 1
            'cache' => '',
            'reload' => '',
            'shortcut' => '',
        );

            // CSH
        if (!strlen($this->id)) {
            $cshKey = 'list_module_noId';
        } elseif (!$this->id) {
            $cshKey = 'list_module_root';
        } else {
            $cshKey = 'list_module';
        }
        $buttons['csh'] = BackendUtility::cshItem('_MOD_commerce', $cshKey, $this->getBackPath(), '', true);

        // New
        $newParams = '&edit[tx_commerce_' . $this->tableForNewLink . '][' . (int) $this->modPid . ']=new';
        $buttons['new_record'] = '<a href="#" onclick="' .
            htmlspecialchars(BackendUtility::editOnClick($newParams, $this->getBackPath(), -1)) .
            '" title="' . $this->getLanguageService()->getLL('create_' . $this->tableForNewLink) . '">' .
            IconUtility::getSpriteIcon('actions-document-new') . '</a>';

        // Reload
        $buttons['reload'] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript()) . '">' .
            IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';

        // Shortcut
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $buttons['shortcut'] = $this->doc->makeShortcutIcon(
                'id, showThumbs, pointer, table, search_field, searchLevels, showLimit, sortField, sortRev',
                implode(',', array_keys($this->MOD_MENU)),
                'commerce_systemdata'
            );
        }

        return $buttons;
    }

    /**
     * Prints out the module HTML.
     *
     * @return void
     */
    public function printContent()
    {
        echo $this->content;
    }

    /**
     * Generates the module content.
     *
     * @return void
     */
    protected function moduleContent()
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
                $this->modPid = $this->attributePid;
                $content = $this->getAttributeListing();
        }

        $this->content .= $this->doc->section('', $content, 0, 1);
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
        $recordList = GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList');
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
                IconUtility::getSpriteIcon('actions-document-open', array('title' => $language->getLL('edit', true))) .
                '</a>';
            $output .= '<a href="#" onclick="' . htmlspecialchars(
                'if (confirm(' . $language->JScharCode(
                    $language->getLL('deleteWarningManufacturer') . ' "' . $attribute['title'] . '" ' . $refCountMsg
                ) . ')) {jumpToUrl(\'' . $this->doc->issueCommand($deleteParams, -1) . '\');} return false;'
            ) . '">' .
                IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $language->getLL('delete', true))) .
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

            $output .= '<tr><td><a href="#" ' . $onClickAction . '>' .
                IconUtility::getSpriteIcon('actions-document-open', array('title' => $language->getLL('edit', true))) .
                '</a>';
            $output .= '<a href="#" onclick="' . htmlspecialchars(
                'if (confirm(' . $language->JScharCode(
                    $language->getLL('deleteWarningManufacturer') . ' "' . htmlspecialchars($row['title']) . '" ' .
                    $refCountMsg
                ) . ')) {jumpToUrl(\'' . $this->doc->issueCommand($deleteParams, -1) . '\');} return false;'
            ) . '">' .
                IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $language->getLL('delete', true))) .
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

            $output .= '<tr><td><a href="#" ' . $onClickAction . '>' .
                IconUtility::getSpriteIcon(
                    'actions-document-open',
                    array('title' => $language->getLL('edit', true))
                ) . '</a>';
            $output .= '<a href="#" onclick="' . htmlspecialchars(
                'if (confirm(' . $language->JScharCode(
                    $language->getLL('deleteWarningSupplier') . ' "' . htmlspecialchars($row['title']) .
                    '" ' . $refCountMsg
                ) . ')) {jumpToUrl(\'' . $this->doc->issueCommand($deleteParams, -1) . '\');} return false;'
            ) . '">' . IconUtility::getSpriteIcon(
                'actions-edit-delete',
                array('title' => $language->getLL('delete', true))
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
            'pid = ' . (int) $this->modPid . ' AND hidden = 0 AND deleted = 0',
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

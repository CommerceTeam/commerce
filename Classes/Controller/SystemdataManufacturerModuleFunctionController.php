<?php
namespace CommerceTeam\Commerce\Controller;

use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class SystemdataManufacturerModuleFunctionController extends AbstractFunctionModule
{
    /**
     * @var SystemdataModuleController
     */
    public $pObj;

    /**
     * @var string
     */
    public $table = 'tx_commerce_manufacturer';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var int
     */
    protected $fixedL = 30;

    /**
     * @var array
     */
    protected $referenceCount = array();

    /**
     * @return string
     */
    public function main()
    {
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');

        $this->iconFactory = $this->pObj->moduleTemplate->getIconFactory();
        $fields = explode(',', ConfigurationUtility::getInstance()->getExtConf('coManufacturers'));

        $headerRow = '<tr><td class="col-icon"></td><td class="col-title">';
        foreach ($fields as $field) {
            $headerRow .= '<strong>' . $this->getLanguageService()->sL(
                BackendUtility::getItemLabel($this->table, htmlspecialchars($field))
            ) . '</strong>';
        }
        $headerRow .= '</td><td class="col-control"></td></tr>';

        $result = $this->fetchManufacturer();
        $manufacturerRows = $this->renderRows($result, $fields);

        $tableHeader = '<a>' . $this->getLanguageService()->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:' . $this->table
        )
            . ' (<span class="t3js-table-total-items">'
            . $this->getDatabaseConnection()->sql_num_rows($result) . '</span>)</a>';

        if (!$manufacturerRows) {
            $out = $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xml:noManufacturer'
                ),
                '',
                FlashMessage::INFO
            )->render();
        } else {
            $out = '

            <!--
                DB listing of elements:	"' . htmlspecialchars($this->table) . '"
            -->
                <div class="panel panel-space panel-default">
                    <div class="panel-heading">
                    ' . $tableHeader . '
                    </div>
                    <div class="table-fit" id="recordlist-' . htmlspecialchars($this->table) . '" data-state="expanded">
                        <table data-table="' . htmlspecialchars($this->table)
                . '" class="table table-striped table-hover">
                            <thead>' . $headerRow . '</thead><tbody>' . $manufacturerRows . '</tbody>
                        </table>
                    </div>
                </div>
            ';
        }

        return $out;
    }

    /**
     * Fetch manufacturer
     *
     * @return \mysqli_result
     */
    protected function fetchManufacturer()
    {
        return $this->getDatabaseConnection()->exec_SELECTquery(
            '*',
            $this->table,
            'pid = ' . (int) $this->pObj->id . ' AND deleted = 0',
            '',
            'title'
        );
    }

    /**
     * Render manufacturer row.
     *
     * @param \mysqli_result $result Result
     * @param array $fields Fields
     *
     * @return string
     */
    protected function renderRows(\mysqli_result $result, array $fields)
    {
        $output = '';

        while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($result))) {
            // edit action
            $params = '&edit[' . $this->table . '][' . $row['uid'] . ']=edit';
            $iconIdentifier = 'actions-open';
            $editAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                . '" title="' . $this->getLanguageService()->getLL('edit', true) . '">'
                . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL) . '</a>';

            // hide action
            $hiddenField = $GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'];
            if ($row[$hiddenField]) {
                $iconIdentifier = 'actions-edit-unhide';
                $params = 'data[' . $this->table . '][' . $row['uid'] . '][' . $hiddenField . ']=0';
                $state = 'hidden';
            } else {
                $iconIdentifier = 'actions-edit-hide';
                $params = 'data[' . $this->table . '][' . $row['uid'] . '][' . $hiddenField . ']=1';
                $state = 'visible';
            }
            $hideTitle = $this->getLanguageService()->getLL('hide', true);
            $unhideTitle = $this->getLanguageService()->getLL('unHide', true);
            $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="' . $state . '" href="#"'
                . ' data-params="' . htmlspecialchars($params) . '"'
                . ' title="' . $unhideTitle . '"'
                . ' data-toggle-title="' . $hideTitle . '">'
                . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL) . '</a>';

            // delete action
            $actionName = 'delete';
            $refCountMsg = BackendUtility::referenceCount(
                $this->table,
                $row['uid'],
                ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToRecord'),
                $this->getReferenceCount($this->table, $row['uid'])
            ) . BackendUtility::translationCount(
                $this->table,
                $row['uid'],
                ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord')
            );
            $titleOrig = BackendUtility::getRecordTitle($this->table, $row, false, true);
            $title = GeneralUtility::slashJS(GeneralUtility::fixed_lgd_cs($titleOrig, $this->fixedL), true);
            $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" '
                . '[' . $this->table . ':' . $row['uid'] . ']' . $refCountMsg;

            $params = 'cmd[' . $this->table . '][' . $row['uid'] . '][delete]=1';
            $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL);
            $linkTitle = $this->getLanguageService()->getLL($actionName, true);
            $deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" '
                . ' data-l10parent="' . htmlspecialchars($row['l10n_parent']) . '"'
                . ' data-params="' . htmlspecialchars($params) . '" data-title="' . htmlspecialchars($titleOrig) . '"'
                . ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"'
                . '>' . $icon . '</a>';

            $toolTip = BackendUtility::getRecordToolTip($row, $this->table);
            $iconImg = '<span ' . $toolTip . '>'
                . $this->iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL)->render()
                . '</span>';

            $output .= '<tr data-uid="' . $row['uid'] . '">';
            $output .= '<td class="col-icon">' . $iconImg . '</td>';

            foreach ($fields as $field) {
                $output .= '<td valign="top">' . htmlspecialchars($row[$field]) . '</td>';
            }

            $output .= '<td class="col-control">' . $editAction . $hideAction . $deleteAction . '</td></tr>';
        }

        return $output;
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param string $tableName
     * @param int $uid
     * @return int The number of references to record $uid in table
     */
    protected function getReferenceCount($tableName, $uid)
    {
        $db = $this->getDatabaseConnection();
        if (!isset($this->referenceCount[$tableName][$uid])) {
            $where = 'ref_table = ' . $db->fullQuoteStr($tableName, 'sys_refindex')
                . ' AND ref_uid = ' . $uid . ' AND deleted = 0';
            $numberOfReferences = $db->exec_SELECTcountRows('*', 'sys_refindex', $where);
            $this->referenceCount[$tableName][$uid] = $numberOfReferences;
        }
        return $this->referenceCount[$tableName][$uid];
    }
}

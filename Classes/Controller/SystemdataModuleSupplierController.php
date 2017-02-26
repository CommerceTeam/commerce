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

use CommerceTeam\Commerce\Domain\Repository\SupplierRepository;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class SystemdataModuleSupplierController extends SystemdataModuleController
{
    /**
     * @var string
     */
    public $table = 'tx_commerce_supplier';

    /**
     * Initialize the object
     *
     * @throws \RuntimeException
     * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
     */
    public function init()
    {
        parent::init();
        $this->id = \CommerceTeam\Commerce\Domain\Repository\FolderRepository::initFolders();
    }

    /**
     * @return string
     */
    public function getSubModuleContent()
    {
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');

        $out = '<h1>' . $this->getLanguageService()->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_supplier'
        ) . '</h1>';

        $fields = explode(',', ConfigurationUtility::getInstance()->getExtConf('coSuppliers'));

        $headerRow = '<tr>
            <td class="col-icon"></td>
            <td class="col-title">';
        foreach ($fields as $field) {
            $headerRow .= '<strong>' . $this->getLanguageService()->sL(
                BackendUtility::getItemLabel($this->table, htmlspecialchars($field))
            ) . '</strong>';
        }
        $headerRow .= '</td>
            <td class="col-control"></td>
        </tr>';

        /** @var SupplierRepository $supplierRepository */
        $supplierRepository = $this->getObjectManager()->get(SupplierRepository::class);
        $result = $supplierRepository->findByPid($this->id);
        $supplierRows = $this->renderRows($result, $fields);

        $tableHeader = '<a>' . $this->getLanguageService()->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:tx_commerce_supplier'
        ) .
            ' (<span class="t3js-table-total-items">' .
            $result->rowCount() .
            '</span>)</a>';

        if (!$supplierRows) {
            $out .= '<span class="label label-info">'
                . htmlspecialchars($this->getLanguageService()->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:noSupplier'
                ))
                . '</span>';
        } else {
            $out .= '

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
                            <thead>' . $headerRow . '</thead><tbody>' . $supplierRows . '</tbody>
                        </table>
                    </div>
                </div>
            ';
        }

        return $out;
    }

    /**
     * Render manufacturer row.
     *
     * @param \Doctrine\DBAL\Driver\Statement $result Result
     * @param array $fields Fields
     *
     * @return string
     */
    protected function renderRows(\Doctrine\DBAL\Driver\Statement $result, array $fields)
    {
        $output = '';

        while ($row = $result->fetch()) {
            // edit action
            $params = '&edit[' . $this->table . '][' . $row['uid'] . ']=edit';
            $onClickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1)) . '"';
            $iconIdentifier = 'actions-open';
            $editAction = '<a class="btn btn-default" href="#" ' . $onClickAction .
                ' title="' . $this->getLanguageService()->getLL('edit', true) . '">' .
                $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() .
                '</a>';

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
                . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() . '</a>';

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
            $title = str_replace('\\', '\\\\', GeneralUtility::fixed_lgd_cs($titleOrig, $this->fixedL));
            $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" ' .
                '[' . $this->table . ':' . $row['uid'] . ']' . $refCountMsg;

            $params = 'cmd[' . $this->table . '][' . $row['uid'] . '][delete]=1';
            $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
            $linkTitle = $this->getLanguageService()->getLL($actionName, true);
            $deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" ' .
                ' data-l10parent="' . htmlspecialchars($row['l10n_parent']) . '"' .
                ' data-params="' . htmlspecialchars($params) . '" data-title="' . htmlspecialchars($titleOrig) . '"' .
                ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"' .
                '>' . $icon . '</a>';

            $toolTip = BackendUtility::getRecordToolTip($row, $this->table);
            $iconImg = '<span ' . $toolTip . '>' .
                $this->iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL)->render() .
                '</span>';

            $output .= '<tr data-uid="' . $row['uid'] . '">';
            $output .= '<td class="col-icon">' . $iconImg . '</td>';

            foreach ($fields as $field) {
                $output .= '<td valign="top">' . htmlspecialchars($row[$field]) . '</td>';
            }

            $output .= '<td class="col-control">' . $editAction . $hideAction . $deleteAction . '</td></tr>';
        }

        return $output;
    }
}

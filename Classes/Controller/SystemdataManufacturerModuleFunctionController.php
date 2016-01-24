<?php
namespace CommerceTeam\Commerce\Controller;

use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class SystemdataManufacturerModuleFunctionController extends AbstractFunctionModule
{
    /**
     * @var SystemdataModuleController
     */
    public $pObj;

    /**
     * @var int
     */
    public $newRecordPid;

    /**
     * @var string
     */
    public $table = 'tx_commerce_manufacturer';

    /**
     * @return string
     */
    public function main()
    {
        $this->newRecordPid = $this->pObj->id;
        $fields = explode(',', SettingsFactory::getInstance()->getExtConf('coManufacturers'));

        $headerRow = '<tr><td></td>';
        foreach ($fields as $field) {
            $headerRow .= '<td><strong>' . $this->getLanguageService()->sL(
                BackendUtility::getItemLabel($this->table, htmlspecialchars($field))
            ) . '</strong></td>';
        }
        $headerRow .= '</tr>';

        $result = $this->fetchManufacturer();
        $manufacturerRows = $this->renderManufacturerRows($result, $fields);

        return '<table>' . $headerRow . $manufacturerRows . '</table>';
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
            'pid = ' . (int) $this->pObj->id . ' AND hidden = 0 AND deleted = 0',
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
    protected function renderManufacturerRows(\mysqli_result $result, array $fields)
    {
        $language = $this->getLanguageService();
        $output = '';

        while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($result))) {
            $refCountMsg = BackendUtility::referenceCount(
                $this->table,
                $row['uid'],
                ' ' . $language->sL('LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'),
                $this->pObj->getReferenceCount($this->table, $row['uid'])
            );
            $editParams = '&edit[' . $this->table . '][' . (int) $row['uid'] . ']=edit';
            $deleteParams = '&cmd[' . $this->table . '][' . (int) $row['uid'] . '][delete]=1';

            $onClickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick(
                $editParams,
                $this->getBackPath(),
                -1
            )) . '"';
            $output .= '<tr><td><a href="#" ' . $onClickAction . ' title="' . $language->getLL('edit', true) . '">' .
                $this->pObj->moduleTemplate->getIconFactory()->getIcon(
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
                $this->pObj->moduleTemplate->getIconFactory()->getIcon(
                    'actions-edit-delete',
                    Icon::SIZE_SMALL
                ) .
                '</a>';
            $output .= '</td>';

            foreach ($fields as $field) {
                $output .= '<td valign="top"><strong>' . htmlspecialchars($row[$field]) . '</strong>';
            }

            $output .= '</td></tr>';
        }

        return $output;
    }
}
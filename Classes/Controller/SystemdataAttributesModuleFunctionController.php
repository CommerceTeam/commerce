<?php
namespace CommerceTeam\Commerce\Controller;

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class SystemdataAttributesModuleFunctionController extends AbstractFunctionModule
{
    /**
     * @var SystemdataModuleController
     */
    public $pObj;

    /**
     * @var int
     */
    protected $attributePid;

    /**
     * @var int
     */
    public $newRecordPid;

    /**
     * @var string
     */
    public $table = 'tx_commerce_attributes';

    /**
     * @return string
     */
    public function main()
    {
        $this->newRecordPid = $this->attributePid = FolderRepository::initFolders('Attributes', $this->pObj->id);
        $language = $this->getLanguageService();

        $headerRow = '<tr>
            <td class="bgColor6" colspan="3"><strong>' . $language->getLL('title_attributes') . '</strong></td>
            <td class="bgColor6"><strong>' . $language->getLL('title_values') . '</strong></td>
        </tr>';

        $result = $this->fetchAttributes();
        $attributeRows = $this->renderAttributeRows($result);

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
     * Render attribute rows.
     *
     * @param \mysqli_result $result Result
     *
     * @return string
     */
    protected function renderAttributeRows(\mysqli_result $result)
    {
        /**
         * Record list.
         *
         * @var \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $recordList
         */
        $recordList = GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class);
        $recordList->initializeLanguages();

        $output = '';

        while (($attribute = $this->getDatabaseConnection()->sql_fetch_assoc($result))) {
            $refCountMsg = BackendUtility::referenceCount(
                $this->table,
                $attribute['uid'],
                ' ' . $this->getLanguageService()->sL(
                    'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
                ),
                $this->pObj->getReferenceCount($this->table, $attribute['uid'])
            );
            $editParams = '&edit[' . $this->table . '][' . (int) $attribute['uid'] . ']=edit';
            $deleteParams = '&cmd[' . $this->table . '][' . (int) $attribute['uid'] . '][delete]=1';

            $output .= '<tr><td align="center" valign="top"> ' .
                BackendUtility::thumbCode($attribute, 'tx_commerce_attributes', 'icon', $this->getBackPath()) . '</td>';
            if ($attribute['internal_title']) {
                $output .= '<td valign="top"><strong>' .
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
            if ($this->getDatabaseConnection()->sql_num_rows($resLocalVersion)) {
                $output .= '<table >';
                while (($localAttributes = $this->getDatabaseConnection()->sql_fetch_assoc($resLocalVersion))) {
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

            $output .= '<br />' . $this->getLanguageService()->getLL('usage');
            $output .= ' <strong>' . $this->getLanguageService()->getLL('categories') . '</strong>: ' . $catCount;
            $output .= ' <strong>' . $this->getLanguageService()->getLL('products') . '</strong>: ' . $proCount;
            $output .= '</td>';

            $onClickAction = 'onclick="' .
                htmlspecialchars(BackendUtility::editOnClick($editParams, $this->getBackPath(), -1)) . '"';

            $output .= '<td><a href="#" ' . $onClickAction . '>' .
                $this->pObj->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-open',
                    Icon::SIZE_SMALL,
                    array('title' => $this->getLanguageService()->getLL('edit', true))
                ) .
                '</a>';
            $output .= '<a href="#" onclick="' . htmlspecialchars(
                'if (confirm(' . GeneralUtility::quoteJSvalue(
                    $this->getLanguageService()->getLL('deleteWarningManufacturer') . ' "' . $attribute['title'] .
                    '" ' . $refCountMsg
                ) . ')) {jumpToUrl(\'' . BackendUtility::getLinkToDataHandlerAction($deleteParams, -1) .
                '\');} return false;'
            ) . '">' .
                $this->pObj->moduleTemplate->getIconFactory()->getIcon(
                    'actions-edit-delete',
                    Icon::SIZE_SMALL,
                    array('title' => $this->getLanguageService()->getLL('delete', true))
                ) .
                '</a>';

            $output .= '</td><td>';

            if ($attribute['has_valuelist'] == 1) {
                $valueRes = $this->getDatabaseConnection()->exec_SELECTquery(
                    '*',
                    'tx_commerce_attribute_values',
                    'attributes_uid = ' . (int) $attribute['uid'] . ' AND hidden = 0 AND deleted = 0',
                    '',
                    'sorting'
                );
                if ($this->getDatabaseConnection()->sql_num_rows($valueRes)) {
                    $output .= '<table border="0">';
                    while (($value = $this->getDatabaseConnection()->sql_fetch_assoc($valueRes))) {
                        $output .= '<tr><td>' . htmlspecialchars($value['value']) . '</td></tr>';
                    }
                    $output .= '</table>';
                } else {
                    $output .= $this->getLanguageService()->getLL('no_values');
                }
            } else {
                $output .= $this->getLanguageService()->getLL('no_valuelist');
            }

            $output .= '</td></tr>';
        }

        return $output;
    }
}
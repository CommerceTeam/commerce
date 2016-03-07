<?php
namespace CommerceTeam\Commerce\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class SystemdataModuleAttributeController extends SystemdataModuleController
{
    /**
     * @var string
     */
    public $table = 'tx_commerce_attributes';

    /**
     * @return string
     */
    public function getSubModuleContent()
    {
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_list.xlf');
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');

        $out = '<h1>' . $this->getLanguageService()->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:title_attributes'
        ) . '</h1>';

        $headerRow = '<tr>
            <td class="col-icon"></td>
            <td class="col-title"><strong>' . $this->getLanguageService()->getLL('title_attributes') . '</strong></td>
            <td class="col-control"></td>
            <td><strong>' . $this->getLanguageService()->getLL('title_values') . '</strong></td>
        </tr>';

        $result = $this->fetchAttributes();
        $attributeRows = $this->renderAttributeRows($result);

        $tableHeader = '<a>' . $this->getLanguageService()->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:' . $this->table
        )
            . ' (<span class="t3js-table-total-items">'
            . $this->getDatabaseConnection()->sql_num_rows($result) . '</span>)</a>';

        if (!$attributeRows) {
            $out .= '<span class="label label-info">'
                . htmlspecialchars($this->getLanguageService()->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:noAttribute'
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
                            <thead>' . $headerRow . '</thead><tbody>' . $attributeRows . '</tbody>
                        </table>
                    </div>
                </div>
            ';
        }

        return $out;
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
            'pid = ' . (int) $this->id .
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
            'pid = ' . (int) $this->id .
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
            // Edit link
            $params = '&edit[' . $this->table . '][' . $attribute['uid'] . ']=edit';
            $iconIdentifier = 'actions-open';
            $editAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                . '" title="' . $this->getLanguageService()->getLL('edit', true) . '">'
                . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() . '</a>';

            // Delete link
            $actionName = 'delete';
            $refCountMsg = BackendUtility::referenceCount(
                $this->table,
                $attribute['uid'],
                ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToRecord'),
                $this->getReferenceCount($this->table, $attribute['uid'])
            ) . BackendUtility::translationCount(
                $this->table,
                $attribute['uid'],
                ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord')
            );
            $titleOrig = BackendUtility::getRecordTitle($this->table, $attribute, false, true);
            $title = GeneralUtility::slashJS(GeneralUtility::fixed_lgd_cs($titleOrig, 30), true);
            $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" '
                . '[' . $this->table . ':' . $attribute['uid'] . ']' . $refCountMsg;

            $params = 'cmd[' . $this->table . '][' . $attribute['uid'] . '][delete]=1';
            $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
            $linkTitle = $this->getLanguageService()->getLL($actionName, true);
            $deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" '
                . ' data-l10parent="' . htmlspecialchars($attribute['l10n_parent']) . '"'
                . ' data-params="' . htmlspecialchars($params) . '" data-title="'
                . htmlspecialchars($titleOrig) . '"'
                . ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"'
                . '>' . $icon . '</a>';

            $toolTip = BackendUtility::getRecordToolTip($attribute, $this->table);
            $iconImg = '<span ' . $toolTip . '>'
                . $this->iconFactory->getIconForRecord(
                    $this->table,
                    $attribute,
                    Icon::SIZE_SMALL
                )
                . '</span>';

            $fields = '';
            if ($attribute['internal_title']) {
                $fields .= '<strong>' .
                    htmlspecialchars($attribute['internal_title']) . '</strong> (' .
                    htmlspecialchars($attribute['title']) . ')';
            } else {
                $fields .= '<strong>' .
                    htmlspecialchars($attribute['title']) . '</strong>';
            }

            $catCount = $this->fetchRelationCount('tx_commerce_categories_attributes_mm', $attribute['uid']);
            $proCount = $this->fetchRelationCount('tx_commerce_products_attributes_mm', $attribute['uid']);
            $artCount = $this->fetchRelationCount('tx_commerce_articles_article_attributes_mm', $attribute['uid']);

            // Select language versions
            $resLocalVersion = $this->fetchAttributeTranslation($attribute['uid']);
            if ($this->getDatabaseConnection()->sql_num_rows($resLocalVersion)) {
                $fields .= '<table >';
                while (($localAttributes = $this->getDatabaseConnection()->sql_fetch_assoc($resLocalVersion))) {
                    $fields .= '<tr><td>&nbsp;';
                    $fields .= '</td><td>';
                    if ($localAttributes['internal_title']) {
                        $fields .= htmlspecialchars($localAttributes['internal_title']) .
                            ' (' . htmlspecialchars($localAttributes['title']) . ')';
                    } else {
                        $fields .= htmlspecialchars($localAttributes['title']);
                    }
                    $fields .= '</td><td>';
                    $fields .= $recordList->languageFlag($localAttributes['sys_language_uid']);
                    $fields .= '</td></tr>';
                }
                $fields .= '</table>';
            }

            $fields .= '<br />' . $this->getLanguageService()->getLL('usage');
            $fields .= ' <strong>' . $this->getLanguageService()->getLL('categories') . '</strong>: ' . $catCount;
            $fields .= ' <strong>' . $this->getLanguageService()->getLL('products') . '</strong>: ' . $proCount;
            $fields .= ' <strong>' . $this->getLanguageService()->getLL('articles') . '</strong>: ' . $artCount;

            $valueList = '';
            if ($attribute['has_valuelist'] == 1) {
                $valueRes = $this->getDatabaseConnection()->exec_SELECTquery(
                    '*',
                    'tx_commerce_attribute_values',
                    'attributes_uid = ' . $attribute['uid'] . ' AND hidden = 0 AND deleted = 0',
                    '',
                    'sorting'
                );
                if ($this->getDatabaseConnection()->sql_num_rows($valueRes)) {
                    $valueList .= '<table border="0">';
                    while (($value = $this->getDatabaseConnection()->sql_fetch_assoc($valueRes))) {
                        $valueList .= '<tr><td>' . htmlspecialchars($value['value']) . '</td></tr>';
                    }
                    $valueList .= '</table>';
                } else {
                    $valueList .= $this->getLanguageService()->getLL('no_values');
                }
            } else {
                $valueList .= $this->getLanguageService()->getLL('no_valuelist');
            }

            $output .= '<tr data-uid="' . $attribute['uid'] . '">';
            $output .= '<td class="col-icon">' . $iconImg . '</td>
                <td nowrap="nowrap">' . $fields . '</td>';


            $output .= '<td class="col-control">' . $editAction . $deleteAction . '</td>
                <td>' . $valueList . '</td>
            </tr>';
        }

        return $output;
    }
}

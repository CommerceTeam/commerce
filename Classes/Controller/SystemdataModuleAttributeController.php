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

use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf');
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

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = $this->getObjectManager()->get(AttributeRepository::class);
        $result = $attributeRepository->findByPid($this->id);
        $attributeRows = $this->renderRows($result);

        $tableHeader = '<a>' . $this->getLanguageService()->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xlf:' . $this->table
        ) .
            ' (<span class="t3js-table-total-items">' .
            $result->rowCount() .
            '</span>)</a>';

        if (!$attributeRows) {
            $out .= '<span class="label label-info">' .
                htmlspecialchars($this->getLanguageService()->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_mod_systemdata.xlf:noAttribute'
                )) .
                '</span>';
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
     * Render attribute rows.
     *
     * @param \Doctrine\DBAL\Driver\Statement $result Result
     *
     * @return string
     */
    protected function renderRows(\Doctrine\DBAL\Driver\Statement $result)
    {
        /**
         * Record list.
         *
         * @var \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $recordList
         */
        $recordList = GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class);
        $recordList->initializeLanguages();

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = $this->getObjectManager()->get(AttributeRepository::class);
        /** @var AttributeValueRepository $attributeValueRepository */
        $attributeValueRepository = $this->getObjectManager()->get(AttributeValueRepository::class);

        $output = '';

        while ($attribute = $result->fetch()) {
            // Edit link
            $params = '&edit[' . $this->table . '][' . $attribute['uid'] . ']=edit';
            $onClickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1)) . '"';
            $iconIdentifier = 'actions-open';
            $editAction = '<a class="btn btn-default" href="#" ' . $onClickAction .
                ' title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">' .
                $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() .
                '</a>';

            // Delete link
            $actionName = 'delete';
            $refCountMsg = BackendUtility::referenceCount(
                $this->table,
                $attribute['uid'],
                ' ' . $this->getLanguageService()->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'
                ),
                $this->getReferenceCount($this->table, $attribute['uid'])
            ) . BackendUtility::translationCount(
                $this->table,
                $attribute['uid'],
                ' ' . $this->getLanguageService()->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord'
                )
            );
            $titleOrig = BackendUtility::getRecordTitle($this->table, $attribute, false, true);
            $title = str_replace('\\', '\\\\', GeneralUtility::fixed_lgd_cs($titleOrig, 30));
            $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" ' .
                '[' . $this->table . ':' . $attribute['uid'] . ']' . $refCountMsg;

            $params = 'cmd[' . $this->table . '][' . $attribute['uid'] . '][delete]=1';
            $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
            $linkTitle = htmlspecialchars($this->getLanguageService()->getLL($actionName));
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

            $catCount = $attributeRepository->countCategoryRelations($attribute['uid']);
            $proCount = $attributeRepository->countProductRelations($attribute['uid']);
            $artCount = $attributeRepository->countArticleRelations($attribute['uid']);

            // Select language versions
            $translationResult = $attributeRepository->findTranslationByParentUid($this->id, $attribute['uid']);
            if ($translationResult->rowCount()) {
                $fields .= '<table >';
                while ($localAttributes = $translationResult->fetch()) {
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
                $values = $attributeValueRepository->findByAttributeUid($attribute['uid']);
                if ($values->rowCount()) {
                    $valueList .= '<table border="0">';
                    while ($value = $values->fetch()) {
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

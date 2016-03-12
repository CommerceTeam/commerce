<?php
namespace CommerceTeam\Commerce\ViewHelpers;

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

use CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * User Class for displaying Orders.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\AttributeEditFunc
 */
class AttributeEditFunc
{
    /**
     * Renders the value list to a value.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function valuelist(array $parameter)
    {
        $language = $this->getLanguageService();

        $content = '';
        $foreignTable = 'tx_commerce_attribute_values';
        $table = 'tx_commerce_attributes';

        $attributeStoragePid = $parameter['row']['pid'];
        $attributeUid = $parameter['row']['uid'];
        /*
         * Select Attribute Values
         */

        // @todo TS config of fields in list
        $rowFields = ['attributes_uid', 'value'];
        $titleCol = ConfigurationUtility::getInstance()->getTcaValue($foreignTable . '.ctrl.label');

        /**
         * Attribute value repository.
         *
         * @var AttributeValueRepository $attributeValueRepository
         */
        $attributeValueRepository = GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository::class
        );
        $attributeValues = $attributeValueRepository->findByAttributeInPage($attributeUid, $attributeStoragePid);

        $out = '';
        if (!empty($attributeValues)) {
            /*
             * Only if we have a result
             */
            $theData[$titleCol] = '<span class="c-table">' .
                $language->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:attributeview.valuelist',
                    1
                ) .
                '</span> (' . count($attributeValues) . ')';

            $out .= '
                    <tr>
                        <td class="c-headLineTable" style="width: 95%;" colspan="' . (count($rowFields) + 1) . '">' .
                $theData[$titleCol] . '</td>
                    </tr>';
            /*
             * Header colum
             */
            $out .= '<tr>';
            foreach ($rowFields as $field) {
                $out .= '<td class="c-headLineTable"><b>' .
                    $language->sL(BackendUtility::getItemLabel($foreignTable, $field)) . '</b></td>';
            }
            $out .= '<td class="c-headLineTable"></td>
                </tr>';

            /*
             * Walk true Data
             */
            $cc = 0;
            $iOut = '';
            foreach ($attributeValues as $row) {
                ++$cc;

                /*
                 * Not very noice to render html_code directly
                 *
                 * @todo change rendering html code here
                 * */
                $iOut .= '<tr>';
                foreach ($rowFields as $field) {
                    $iOut .= '<td>';
                    $wrap = ['', ''];

                    switch ($field) {
                        case $titleCol:
                            $params = '&edit[' . $foreignTable . '][' . $row['uid'] . ']=edit';
                            $wrap = [
                                '<a href="#" onclick="' .
                                htmlspecialchars(BackendUtility::editOnClick($params)) . '">',
                                '</a>',
                            ];
                            break;

                        default:
                    }
                    $iOut .= implode(
                        BackendUtility::getProcessedValue($foreignTable, $field, $row[$field], 100),
                        $wrap
                    );
                    $iOut .= '</td>';
                }
                /*
                 * Trash icon
                 */
                $onClick = 'onclick="deleteRecord(\'' . $foreignTable . '\', ' . $row['uid'] .
                    ', \'alt_doc.php?edit[tx_commerce_attributes][' . $attributeUid . ']=edit\');"';

                $iOut .= '<td>&nbsp;
                    <a href="#" ' . $onClick . '>' .
                    \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete') . '</a></td>
                    </tr>';
            }

            $out .= $iOut;
            /*
             * Cerate the summ row
             */
            $out .= '<tr>';

            foreach ($rowFields as $field) {
                $out .= '<td class="c-headLineTable"><b>';
                // @todo this makes no sense how to fix?
                if ($sum[$field] > 0) {
                    $out .= BackendUtility::getProcessedValueExtra($foreignTable, $field, $sum[$field], 100);
                }

                $out .= '</b></td>';
            }
            $out .= '<td class="c-headLineTable"></td>';
            $out .= '</tr>';
        }

        $out = '
            <!--
                DB listing of elements: "' . htmlspecialchars($table) . '"
            -->
            <table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
                ' . $out . '
            </table>';
        $content .= $out;

        /*
         * New article
         */
        $params = '&edit[' . $foreignTable . '][' . $attributeStoragePid . ']=new&defVals[' . $foreignTable .
            '][attributes_uid]=' . urlencode($attributeUid);
        $onClickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick($params)) .
            '"';

        $content .= '<div id="typo3-newRecordLink">
			<a href="#" ' . $onClickAction . '>
				' .
            $language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:attributeview.addvalue', 1) .
                '</a>
			</div>';

        return $content;
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
     * Get controller document template.
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getControllerDocumentTemplate()
    {
        // $GLOBALS['SOBE'] might be any kind of PHP class (controller most
        // of the times) These class do not inherit from any common class,
        // but they all seem to have a "doc" member
        return $GLOBALS['SOBE']->doc;
    }
}

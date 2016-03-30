<?php
namespace CommerceTeam\Commerce\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides several methods for creating articles from within
 * a product. It provides the user fields and creates the entries in the
 * database.
 *
 * Class \CommerceTeam\Commerce\Form\Element\ArticleCreatorElement
 */
class ProducibleArticlesElement extends AbstractFormElement
{
    /**
     * @var string
     */
    protected $table = 'tx_commerce_articles';

    /**
     * Existing articles.
     *
     * @var array
     */
    protected $existingArticles = [];

    /**
     * Attributes.
     *
     * @var array
     */
    protected $attributes = null;

    /**
     * Backend utility.
     *
     * @var \CommerceTeam\Commerce\Utility\BackendUtility
     */
    protected $backendUtility;

    /**
     * Return url.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * ProducibleArticlesElement constructor.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);

        $this->backendUtility = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);

        $this->existingArticles = $this->data['databaseRow']['articles'];
        $this->attributes = $this->backendUtility->getAttributesForProduct(
            (int) $this->data['vanillaUid'],
            true,
            true,
            true
        );

        $this->getLanguageService()->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_db.xlf');
    }

    /**
     * Render available articles element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();

        $rowCount = $this->calculateRowCount();
        if ($rowCount > 1000) {
            $resultArray['html'] = sprintf(
                $this->getLanguageService()->getLL('tx_commerce_products.to_many_articles'),
                $rowCount
            );
            return $resultArray;
        }

        // create the headrow from the product attributes, select attributes without
        // valuelist and normal select attributes
        $colCount = 0;
        $headRow = $this->getHeadRow($colCount, ['&nbsp;']);
        $emptyRow = $this->getEmptyRow($colCount);
        $resultRows = $this->getRows($this->getValues(), $counter = 0, $headRow);

        $resultArray['html'] = '
            <div class="table-fit">
                <table class="table table-striped table-hover">'
            . $headRow
            . $emptyRow
            . $resultRows
            . '
                </table>
            </div>';
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Commerce/ProducibleArticles';

        return $resultArray;
    }

    /**
     * Returns the number of articles that would be created with the number
     * of attributes the product have.
     *
     * @return int The number of rows
     */
    protected function calculateRowCount()
    {
        $result = 1;

        if (isset($this->attributes['ct1']) && is_array($this->attributes['ct1'])) {
            foreach ($this->attributes['ct1'] as $attribute) {
                $valueCount = count($attribute['valueList']);
                $result *= $valueCount;
            }
        }

        return $result;
    }

    /**
     * Returns the HTML code for the header row.

*
*@param int $columnCount The number of columns we have
     * @param array $additionalColumnsBefore The additional columns before the attribute columns
     * @param array $additionalColumnsAfter The additional columns after the attribute columns
     * @param bool $addTableRow Add table row
     * @return string The HTML header code
     */
    protected function getHeadRow(
        &$columnCount,
        array $additionalColumnsBefore = [],
        array $additionalColumnsAfter = [],
        $addTableRow = true
    ) {
        $result = '';

        if (!empty($additionalColumnsBefore)) {
            $result .= '<th class="col-icon">' . implode('</th><th>', $additionalColumnsBefore) . '</th>';
        }

        if (isset($this->attributes['ct1']) && is_array($this->attributes['ct1'])) {
            //$attributes = array_reverse($this->attributes['ct1']);
            foreach ($this->attributes['ct1'] as $attribute) {
                $result .= '<th  style="width: {width}%">'
                    . htmlspecialchars(strip_tags($attribute['attributeData']['title'])) . '</th>';
                ++$columnCount;
            }
        }
        if ($columnCount == 0) {
            $result .= '<th  style="width: 100%">&nbsp;</th>';
        }

        if (!empty($additionalColumnsAfter)) {
            $result .= '<th>' . implode('</th><th>', $additionalColumnsAfter) . '</th>';
        }

        if ($addTableRow) {
            $result = '<tr>' . $result . '</tr>';
        }

        $columnCount += count($additionalColumnsBefore) + count($additionalColumnsAfter);

        return str_replace('{width}', (100 / max(1, $columnCount - 1)), $result);
    }

    /**
     * @param int $colCount
     * @return string
     */
    protected function getEmptyRow($colCount)
    {
        $emptyRow = '<tr>
                <td class="col-icon">' . $this->getCreateAction([]) . '</td>
                <td colspan="' . ($colCount - 1) . '">' .
                $this->getLanguageService()->getLL('tx_commerce_products.empty_article') .
                '</td>
            </tr>';

        return $emptyRow;
    }

    /**
     * This method builds up a matrix from the ct1 attributes with valuelist.
     *
     * Example:
     *  Attribute 1 Value 1
     *  Attribute 1 Value 1 - Attribute 2 Value 1
     *  Attribute 1 Value 1 - Attribute 2 Value 2
     *  Attribute 1 Value 2
     *  Attribute 1 Value 2 - Attribute 2 Value 1
     *  Attribute 1 Value 2 - Attribute 2 Value 2
     *
     * @param int $index The index we're currently working on
     *
     * @return array
     */
    protected function getValues($index = 0)
    {
        $result = [];

        if (isset($this->attributes['ct1'])
            && is_array($this->attributes['ct1'])
            && count($this->attributes['ct1']) > $index
        ) {
            foreach ($this->attributes['ct1'][$index]['valueList'] as $aValue) {
                $data['attributeUid'] = (int) $this->attributes['ct1'][$index]['attributeData']['uid'];
                $data['attributeValueUid'] = (int) $aValue['uid'];
                $data['attributeValueLabel'] = $aValue['value'];

                $newI = $index + 1;
                $other = $this->getValues($newI);
                if ($other) {
                    $data['other'] = $other;
                }

                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * Returns the html table rows for the article matrix.
     *
     * @param array $data The data we should build the matrix from
     * @param int $counter The article counter
     * @param string $headRow The header row for inserting after a number of articles
     * @param array $extraRowData Some additional data like checkbox column
     * @param int $index The level inside the matrix
     * @param array $row The current row data
     *
     * @return string
     */
    protected function getRows(
        array $data,
        &$counter,
        $headRow,
        array $extraRowData = [],
        $index = 1,
        array $row = []
    ) {
        $resultRows = '';

        foreach ($data as $dataItem) {
            $row[$index] = $dataItem;
            unset($row[$index]['other']);

            if (is_array($dataItem['other'])) {
                $resultRows .= $this->getRows(
                    $dataItem['other'],
                    $counter,
                    $headRow,
                    $extraRowData,
                    ($index + 1),
                    $row
                );
            } else {
                // serialize data for form saveing
                $labelData = [];
                $attributeValue = [];

                //$row = array_reverse($row);

                foreach ($row as $rd) {
                    $attributeValue[$rd['attributeUid']] = $rd['attributeValueUid'];
                    $labelData[] = $rd['attributeValueLabel'];
                }
                asort($attributeValue);

                // needs to use json_encode or the check against stored articles will result in a wrong result
                // this is because ajax handed attribute data are objects due to associativ array usage
                if ($this->backendUtility->checkArray(
                    md5(json_encode($attributeValue)),
                    $this->existingArticles,
                    'attribute_hash'
                )) {
                    continue;
                }

                ++$counter;

                // select format and insert headrow if we are in the 20th row
                if (($counter % 20) == 0) {
                    $resultRows .= $headRow;
                }

                // create the row
                $resultRows .= '<tr>
                    <td class="col-icon">' . $this->getCreateAction($attributeValue) . '</td>
                    <td>' . GeneralUtility::removeXSS(implode('</td><td>', $labelData)) . '</td>';
                if (!empty($extraRowData)) {
                    $resultRows .= '<td>' . GeneralUtility::removeXSS(implode('</td><td>', $extraRowData)) . '</td>';
                }
                $resultRows .= '</tr>';
            }
        }

        return $resultRows;
    }

    /**
     * @param array $hashData
     * @return string
     */
    protected function getCreateAction(array $hashData)
    {
        $icon = $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render();
        $createAction = '<a class="btn btn-default t3js-article-create" href="#"
            data-attribute-value=\'' . json_encode($hashData) . '\'
            data-product="' . $this->data['vanillaUid'] . '"
            title="' . htmlspecialchars($this->getLanguageService()->getLL('newRecordGeneral')) . '">'
            . $icon . '</a>';

        return $createAction;
    }
}

<?php
namespace CommerceTeam\Commerce\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides several methods for creating articles from within
 * a product. It provides the user fields and creates the entries in the
 * database.
 *
 * Class \CommerceTeam\Commerce\Form\Element\ArticleCreatorElement
 */
class AvailableArticlesElement extends AbstractFormElement
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
    protected $existingArticles = null;

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
    protected $belib;

    /**
     * Return url.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * Render available articles element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $this->belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);

        $this->existingArticles = $this->data['databaseRow']['articles'];
        $this->attributes = $this->data['databaseRow']['attributes'];

        $this->getLanguageService()->includeLLFile('EXT:commerce/Resources/Private/Language/locallang_db.xlf');

        $rowCount = $this->calculateRowCount();
        if ($rowCount > 1000) {
            return sprintf(
                $this->getLanguageService()->sL('tx_commerce_products.to_many_articles'),
                $rowCount
            );
        }

        // create the headrow from the product attributes, select attributes without
        // valuelist and normal select attributes
        $colCount = 0;
        $headRow = $this->getHeadRow($colCount, ['&nbsp;']);

        $valueMatrix = (array) $this->getValues();
        $counter = 0;
        $resultRows = $this->getLanguageService()->sL('tx_commerce_products.create_warning');

        $this->getRows($valueMatrix, $resultRows, $counter, $headRow);

        $emptyRow = '<tr><td><input type="checkbox" name="createList[empty]" /></td>';
        $emptyRow .= '<td colspan="' . ($colCount - 1) . '">' .
            $this->getLanguageService()->sL('tx_commerce_products.empty_article') .
            '</td></tr>';

        // create a checkbox for selecting all articles
        $selectJs = '<script language="JavaScript">
            function updateArticleList() {
                var sourceSB = document.getElementById("selectAllArticles");
                for (var i = 1; i <= ' . $rowCount . '; i++) {
                    document.getElementById("createRow_" + i).checked = sourceSB.checked;
                }
            }
        </script>';

        $selectAllRow = '';
        if (!empty($valueMatrix)) {
            $onClick = 'onclick="updateArticleList()"';
            $selectAllRow = '<tr><td><input type="checkbox" id="selectAllArticles" ' . $onClick . '/></td>';
            $selectAllRow .= '<td colspan="' . ($colCount - 1) . '">'
                . $this->getLanguageService()->sL('tx_commerce_products.select_all_articles')
                . '</td></tr>';
        }

        $out = '<table>' . $selectJs . $headRow . $emptyRow . $selectAllRow . $resultRows . '</table>';

        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = $out;

        return $resultArray;
    }

    /**
     * This method builds up a matrix from the ct1 attributes with valuelist.
     *
     * @param int $index The index we're currently working on
     *
     * @return array
     */
    protected function getValues($index = 0)
    {
        $result = [];

        if (count($this->attributes['ct1']) > $index) {
            if (is_array($this->attributes['ct1'])) {
                foreach ($this->attributes['ct1'][$index]['valueList'] as $aValue) {
                    $data['aUid'] = (int) $this->attributes['ct1'][$index]['attributeData']['uid'];
                    $data['vUid'] = (int) $aValue['uid'];
                    $data['vLabel'] = $aValue['value'];

                    $newI = $index + 1;
                    $other = $this->getValues($newI);
                    if ($other) {
                        $data['other'] = $other;
                    }

                    $result[] = $data;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the html table rows for the article matrix.
     *
     * @param array $data The data we should build the matrix from
     * @param string $resultRows The rendered resulting rows
     * @param int $counter The article counter
     * @param string $headRow The header row for inserting after a number of articles
     * @param array $extraRowData Some additional data like checkbox column
     * @param int $index The level inside the matrix
     * @param array $row The current row data
     *
     * @return void
     */
    protected function getRows(
        array $data,
        &$resultRows,
        &$counter,
        $headRow,
        array $extraRowData = [],
        $index = 1,
        array $row = []
    ) {
        if (is_array($data)) {
            foreach ($data as $dataItem) {
                $dummyData = $dataItem;
                unset($dummyData['other']);
                $row[$index] = $dummyData;

                if (is_array($dataItem['other'])) {
                    $this->getRows(
                        $dataItem['other'],
                        $resultRows,
                        $counter,
                        $headRow,
                        $extraRowData,
                        ($index + 1),
                        $row
                    );
                } else {
                    // serialize data for formsaveing
                    $labelData = [];
                    $hashData = [];

                    foreach ($row as $rd) {
                        $hashData[$rd['aUid']] = $rd['vUid'];
                        $labelData[] = $rd['vLabel'];
                    }
                    asort($hashData);

                    // try to fetch an article with this special attribute values
                    $hashData = serialize($hashData);
                    $hash = md5($hashData);

                    if ($this->belib->checkArray($hash, $this->existingArticles, 'attribute_hash')) {
                        continue;
                    }

                    ++$counter;

                    // select format and insert headrow if we are in the 20th row
                    if (($counter % 20) == 0) {
                        $resultRows .= $headRow;
                    }
                    $class = ($counter % 2 == 1) ? 'background-color: silver' : 'background: none';

                    // create the row
                    $resultRows .= '<tr><td style="' . $class . '">
                        <input type="checkbox" name="createList[' . $counter . ']" id="createRow_' . $counter . '" />
                        <input type="hidden" name="createData[' . $counter . ']" value="' .
                        htmlspecialchars($hashData) . '" /></td>';

                    $resultRows .= '<td style="' . $class . '">' .
                        implode(
                            '</td><td style="' . $class . '">',
                            \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($labelData)
                        ) .
                        '</td>';
                    if (!empty($extraRowData)) {
                        $resultRows .= '<td style="' . $class . '">' .
                            implode(
                                '</td><td style="' . $class . '">',
                                \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($extraRowData)
                            ) .
                            '</td>';
                    }
                    $resultRows .= '</tr>';
                }
            }
        }
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

        if (is_array($this->attributes['ct1'])) {
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
     * @param int $colCount The number of columns we have
     * @param array $acBefore The additional columns before the attribute columns
     * @param array $acAfter The additional columns after the attribute columns
     * @param bool $addTr Add table row
     *
     * @return string The HTML header code
     */
    protected function getHeadRow(&$colCount, array $acBefore = null, array $acAfter = null, $addTr = true)
    {
        $result = '';

        if ($addTr) {
            $result .= '<tr>';
        }

        if ($acBefore != null) {
            $result .= '<th>' . implode(
                '</th><th>',
                \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($acBefore)
            ) . '</th>';
        }

        if (is_array($this->attributes['ct1'])) {
            foreach ($this->attributes['ct1'] as $attribute) {
                $result .= '<th>' . htmlspecialchars(strip_tags($attribute['attributeData']['title'])) . '</th>';
                ++$colCount;
            }
        }

        if ($acAfter != null) {
            $result .= '<th>' . implode(
                '</th><th>',
                \CommerceTeam\Commerce\Utility\GeneralUtility::removeXSSStripTagsArray($acAfter)
            ) . '</th>';
        }

        if ($addTr) {
            $result .= '</tr>';
        }

        $colCount += count($acBefore) + count($acAfter);

        return $result;
    }
}

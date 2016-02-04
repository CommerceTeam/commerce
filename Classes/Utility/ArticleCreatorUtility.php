<?php
namespace CommerceTeam\Commerce\Utility;

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

use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Backend\Utility\BackendUtility as CoreBackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides several methods for creating articles from within
 * a product. It provides the user fields and creates the entries in the
 * database.
 *
 * Class \CommerceTeam\Commerce\Utility\ArticleCreatorUtility
 *
 * @author 2005-2012 Thomas Hempel <thomas@work.de>
 */
class ArticleCreatorUtility
{
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
     * Flatted attributes.
     *
     * @var array
     */
    protected $flattedAttributes = array();

    /**
     * Uid.
     *
     * @var int
     */
    protected $uid = 0;

    /**
     * Page id.
     *
     * @var int
     */
    protected $pid = 0;

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
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        $this->belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
        $this->returnUrl = htmlspecialchars(urlencode(GeneralUtility::_GP('returnUrl')));
    }

    /**
     * Initializes the Article Creator if it's not called directly
     * from the Flexforms.
     *
     * @param int $uid Uid of the product
     * @param int $pid Page id
     *
     * @return void
     */
    public function init($uid, $pid)
    {
        $this->uid = (int) $uid;
        $this->pid = (int) $pid;

        if ($this->attributes == null) {
            $this->attributes = $this->belib->getAttributesForProduct($this->uid, true, true, true);
        }
    }

    /**
     * Get all articles that already exist. Add some buttons for editing.
     *
     * @param array $parameter Parameter
     *
     * @return string a HTML-table with the articles
     */
    public function existingArticles(array $parameter)
    {
        $database = $this->getDatabaseConnection();

        $this->uid = (int) $parameter['row']['uid'];
        $this->pid = (int) $parameter['row']['pid'];

        // get all attributes for this product, if they where not fetched yet
        if ($this->attributes == null) {
            $this->attributes = $this->belib->getAttributesForProduct($this->uid, true, true, true);
        }

        // get existing articles for this product, if they where not fetched yet
        if ($this->existingArticles == null) {
            $this->existingArticles = $this->belib->getArticlesOfProduct($this->uid, '', 'sorting');
        }

        if (empty($this->existingArticles) || $this->uid == 0 || $this->existingArticles === false) {
            return 'No articles existing for this product';
        }

        // generate the security token
        $formSecurityToken = '&prErr=1&vC=' . $this->getBackendUser()->veriCode() .
            CoreBackendUtility::getUrlToken('tceAction');

        $colCount = 0;
        $headRow = $this->getHeadRow($colCount, null, null, false);
        $result = '<script>' . $this->redirectUrls() . '</script>
            <input type="hidden" name="deleteaid" value="0" />
            <table border="0">
                ';

        $lastUid = 0;

        $result .= '<tr><td>&nbsp;</td>' . $headRow . '</td><td colspan="5">&nbsp;</td></tr>';

        $buttonUp = IconUtility::getSpriteIcon('actions-move-up');
        $buttonDown = IconUtility::getSpriteIcon('actions-move-down');
        $clear = '<span style="display: block; width: 11px; height: 10px"></span>';
        $delete = IconUtility::getSpriteIcon('actions-edit-delete');
        $edit = IconUtility::getSpriteIcon('actions-document-open');
        $hide = IconUtility::getSpriteIcon('actions-edit-hide');
        $unhide = IconUtility::getSpriteIcon('actions-edit-unhide');

        for ($i = 0, $articleCount = count($this->existingArticles); $i < $articleCount; ++$i) {
            $article = $this->existingArticles[$i];
            $articleUid = (int) $article['uid'];

            $result .= '<tr><td style="border-top:1px black solid; border-right: 1px gray dotted"><strong>'.
                htmlspecialchars($article['title']) . '</strong><br />UID:' . $articleUid . '</td>';

            if (is_array($this->attributes['ct1'])) {
                foreach ($this->attributes['ct1'] as $attribute) {
                    // get all article attribute relations
                    $atrRes = $database->exec_SELECTquery(
                        'uid_valuelist, default_value, value_char',
                        'tx_commerce_articles_article_attributes_mm',
                        'uid_local = ' . $articleUid . ' AND uid_foreign = ' . $attribute['uid_foreign']
                    );

                    $cellStyle = 'border-top:1px black solid; border-right: 1px gray dotted';
                    while (($attributeData = $database->sql_fetch_assoc($atrRes))) {
                        if ($attribute['attributeData']['has_valuelist'] == 1) {
                            if ($attributeData['uid_valuelist'] == 0) {
                                // if the attribute has no value, create a select box with valid values
                                $result .= '<td style="' . $cellStyle . '"><select name="updateData[' .
                                    $articleUid . '][' . (int) $attribute['uid_foreign'] . ']" />';
                                $result .= '<option value="0" selected="selected"></option>';
                                foreach ($attribute['valueList'] as $attrValueUid => $attrValueData) {
                                    $result .= '<option value="' . (int) $attrValueUid . '">' .
                                        htmlspecialchars($attrValueData['value']) . '</option>';
                                }
                                $result .= '</select></td>';
                            } else {
                                $result .= '<td style="' . $cellStyle . '">' .
                                    htmlspecialchars(
                                        strip_tags($attribute['valueList'][$attributeData['uid_valuelist']]['value'])
                                    ) . '</td>';
                            }
                        } elseif (!empty($attributeData['value_char'])) {
                            $result .= '<td style="' . $cellStyle . '">' .
                                htmlspecialchars(strip_tags($attributeData['value_char'])) . '</td>';
                        } else {
                            $result .= '<td style="' . $cellStyle . '">' .
                                htmlspecialchars(strip_tags($attributeData['default_value'])) . '</td>';
                        }
                    }
                }
            }

            // the edit pencil (with jump back to this dataset)
            $result .= '<td style="border-top: 1px black solid">
				<a onclick="document.location=\'alt_doc.php?returnUrl=alt_doc.php?edit[tx_commerce_products][' .
                $this->uid . ']=edit&amp;edit[tx_commerce_articles][' . $articleUid .
                ']=edit\'; return false;" href="#">';
            $result .= $edit . '</a></td>';

            // add the hide button
            $params = '&data[tx_commerce_articles][' . $articleUid . '][hidden]=' . (int) (!$article['hidden']) .
                '&redirect=alt_doc.php?edit[tx_commerce_products][' . $this->uid . ']=edit' . $formSecurityToken;
            $result .= '<td style="border-top:1px black solid">
				<a href="#" onclick="return jumpToUrl(\'' .
                $this->getControllerDocumentTemplate()->issueCommand($params, -1) . '\');">' .
                ($article['hidden'] ? $unhide : $hide) . '</a></td>';

            // add the sorting buttons
            // UP
            if (isset($this->existingArticles[$i - 1])) {
                if (isset($this->existingArticles[$i - 2])) {
                    $moveItTo = '-' . (int) $this->existingArticles[$i - 2]['uid'];
                } else {
                    $moveItTo = (int) $article['pid'];
                }

                $params = 'cmd[tx_commerce_articles][' . $articleUid . '][move]=' . $moveItTo;
                $result .= '<td style="border-top: 1px black solid"><a onClick="return jumpToUrl(\'tce_db.php?' .
                    $params . $formSecurityToken . '&redirect=alt_doc.php?edit[tx_commerce_products][' .
                    (int) $this->uid . ']=edit\');" href="#">' . $buttonUp . '</a></td>';
                $result .= '<td style="border-top: 1px black solid"><a onClick="return jumpToUrl(\'tce_db.php?' .
                    $params . $formSecurityToken . '&redirect=alt_doc.php?edit[tx_commerce_products][' .
                    (int) $this->uid . ']=edit\');" href="#">' . $buttonUp . '</a></td>';
            } else {
                $result .= '<td>' . $clear . '</td>';
            }

            // DOWN
            if (isset($this->existingArticles[$i + 1])) {
                $params = 'cmd[tx_commerce_articles][' . $articleUid . '][move]=-' .
                    (int) $this->existingArticles[$i + 1]['uid'];
                $result .= '<td style="border-top: 1px black solid"><a onClick="return jumpToUrl(\'tce_db.php?' .
                    $params . $formSecurityToken . '&redirect=alt_doc.php?edit[tx_commerce_products][' .
                    (int) $this->uid . ']=edit\');" href="#">' . $buttonDown . '</a></td>';
                $result .= '<td style="border-top: 1px black solid"><a onClick="return jumpToUrl(\'tce_db.php?' .
                    $params . $formSecurityToken . '&redirect=alt_doc.php?edit[tx_commerce_products][' .
                    (int) $this->uid . ']=edit\');" href="#">' . $buttonDown . '</a></td>';
            } else {
                $result .= '<td>' . $clear . '</td>';
            }

            $onClick = 'onclick="deleteRecord(\'tx_commerce_articles\', ' . $articleUid .
                ', \'alt_doc.php?edit[tx_commerce_products][' . (int) $this->uid . ']=edit\');"';

            // add the delete icon
            $result .= '<td style="border-top:1px black solid"><a href="#" ' . $onClick . '>' . $delete . '</a></td>';
            $result .= '</tr>';

            if ($articleUid > $lastUid) {
                $lastUid = $articleUid;
            }
        }

        $result .= '</table>';

        return $result;
    }

    /**
     * Returns JavaScript variables setting the returnUrl and thisScript
     * location for use by JavaScript on the page.
     * Used in fx. db_list.php (Web>List).
     *
     * @param string $thisLocation URL to "this location" / current script
     *
     * @return string Urls are returned as T3_RETURN_URL and T3_THIS_LOCATION
     */
    protected function redirectUrls($thisLocation = '')
    {
        $thisLocation = $thisLocation ? $thisLocation : GeneralUtility::linkThisScript(array(
            'CB' => '',
            'SET' => '',
            'cmd' => '',
            'popViewId' => '',
        ));

        $out = '
            var T3_RETURN_URL = \'' .
            str_replace('%20', '', rawurlencode(GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl')))) .
            '\';
            var T3_THIS_LOCATION = \'' . str_replace('%20', '', rawurlencode($thisLocation)) . '\';
        ';

        return $out;
    }

    /**
     * Create a matrix of producible articles.
     *
     * @param array $parameter Parameter
     * @param FormEngine $fObj Form engine
     *
     * @return string A HTML-table with checkboxes and all needed stuff
     */
    public function producibleArticles(array $parameter, FormEngine $fObj)
    {
        $this->uid = (int) $parameter['row']['uid'];
        $this->pid = (int) $parameter['row']['pid'];

            // get existing articles for this product, if they where not fetched yet
        if ($this->existingArticles == null) {
            $this->existingArticles = $this->belib->getArticlesOfProduct($this->uid);
        }

            // get all attributes for this product, if they where not fetched yet
        if ($this->attributes == null) {
            $this->attributes = $this->belib->getAttributesForProduct($this->uid, true, true, true);
        }

        $rowCount = $this->calculateRowCount();
        if ($rowCount > 1000) {
            return sprintf(
                $fObj->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.to_many_articles'
                ),
                $rowCount
            );
        }

        // create the headrow from the product attributes, select attributes without
        // valuelist and normal select attributes
        $colCount = 0;
        $headRow = $this->getHeadRow($colCount, array('&nbsp;'));

        $valueMatrix = (array) $this->getValues();
        $counter = 0;
        $resultRows = $fObj->sL(
            'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.create_warning'
        );

        $this->getRows($valueMatrix, $resultRows, $counter, $headRow);

        $emptyRow = '<tr><td><input type="checkbox" name="createList[empty]" /></td>';
        $emptyRow .= '<td colspan="' . ($colCount - 1) . '">' .
            $fObj->sL(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.empty_article'
            ) .
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
            $selectAllRow .= '<td colspan="' . ($colCount - 1) . '">' . $fObj->sL(
                'LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.select_all_articles'
            ) . '</td></tr>';
        }

        $result = '<table border="0">' . $selectJs . $headRow . $emptyRow . $selectAllRow . $resultRows . '</table>';

        return $result;
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
        $result = array();

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
        array $extraRowData = array(),
        $index = 1,
        array $row = array()
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
                    $labelData = array();
                    $hashData = array();

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

    /**
     * Creates all articles that should be created (defined through the POST vars).
     *
     * @param array $parameter Parameter
     *
     * @return void
     */
    public function createArticles(array $parameter)
    {
        $database = $this->getDatabaseConnection();

        if (is_array(GeneralUtility::_GP('createList'))) {
            $result = $database->exec_SELECTquery(
                'uid, value',
                'tx_commerce_attribute_values',
                'deleted = 0'
            );

            while (($row = $database->sql_fetch_assoc($result))) {
                $this->flattedAttributes[$row['uid']] = $row['value'];
            }

            foreach (array_keys(GeneralUtility::_GP('createList')) as $key) {
                $this->createArticle($parameter, $key);
            }

            CoreBackendUtility::setUpdateSignal('updateFolderTree');
        }
    }

    /**
     * Updates all articles.
     * This adds new attributes to all existing articles that where added
     * to the parent product or categories.
     *
     * @return void
     */
    public function updateArticles()
    {
        $fullAttributeList = array();

        if (!is_array($this->attributes['ct1'])) {
            return;
        }

        foreach ($this->attributes['ct1'] as $attributeData) {
            $fullAttributeList[] = $attributeData['uid_foreign'];
        }

        if (is_array(GeneralUtility::_GP('updateData'))) {
            foreach (GeneralUtility::_GP('updateData') as $articleUid => $relData) {
                foreach ($relData as $attributeUid => $attributeValueUid) {
                    if ($attributeValueUid == 0) {
                        continue;
                    }

                    $database = $this->getDatabaseConnection();

                    $database->exec_UPDATEquery(
                        'tx_commerce_articles_article_attributes_mm',
                        'uid_local = ' . $articleUid . ' AND uid_foreign = ' . $attributeUid,
                        array('uid_valuelist' => $attributeValueUid)
                    );
                }

                $this->belib->updateArticleHash($articleUid, $fullAttributeList);
            }
        }
    }

    /**
     * Creates article title out of attributes.
     *
     * @param array $parameter Parameter
     * @param array $data Data
     *
     * @return string Returns the product title + attribute titles for article title
     */
    protected function createArticleTitleFromAttributes(array $parameter, array $data)
    {
        $content = $parameter['title'];
        if (is_array($data) && !empty($data)) {
            $selectedValues = array();
            foreach ($data as $value) {
                if ($this->flattedAttributes[$value]) {
                    $selectedValues[] = $this->flattedAttributes[$value];
                }
            }
            if (!empty($selectedValues)) {
                $content .= ' (' . implode(', ', $selectedValues) . ')';
            }
        }

        return $content;
    }

    /**
     * Creates an article in the database and all needed releations to attributes
     * and values. It also creates a new prices and assignes it to the new article.
     *
     * @param array $parameter Parameter
     * @param string $key The key in the POST var array
     *
     * @return int Returns the new articleUid if success
     */
    protected function createArticle(array $parameter, $key)
    {
        $database = $this->getDatabaseConnection();

        // get the create data
        $data = GeneralUtility::_GP('createData');
        $hash = '';
        if (is_array($data)) {
            $data = $data[$key];
            $hash = md5($data);
            $data = unserialize($data);
        }
        $database->debugOutput = 1;
        $database->store_lastBuiltQuery = true;
        // get the highest sorting
        $sorting = $database->exec_SELECTgetSingleRow(
            'uid, sorting',
            'tx_commerce_articles',
            'uid_product = ' . $this->uid,
            '',
            'sorting DESC',
            1
        );
        $sorting = (is_array($sorting) && isset($sorting['sorting'])) ? $sorting['sorting'] + 20 : 0;

        // create article data array
        $articleData = array(
            'pid' => $this->pid,
            'crdate' => time(),
            'title' => strip_tags($this->createArticleTitleFromAttributes($parameter, (array) $data)),
            'uid_product' => (int) $this->uid,
            'sorting' => $sorting,
            'article_attributes' => count($this->attributes['rest']) + count($data),
            'attribute_hash' => $hash,
            'article_type_uid' => 1,
        );

        $temp = CoreBackendUtility::getModTSconfig($this->pid, 'mod.commerce.category');
        if ($temp) {
            $moduleConfig = CoreBackendUtility::implodeTSParams($temp['properties']);
            $defaultTax = (int) $moduleConfig['defaultTaxValue'];
            if ($defaultTax > 0) {
                $articleData['tax'] = $defaultTax;
            }
        }

        $hookObject = \CommerceTeam\Commerce\Factory\HookFactory::getHook(
            'Utility/ArticleCreatorUtility',
            'createArticle'
        );
        if (method_exists($hookObject, 'preinsert')) {
            $hookObject->preinsert($articleData);
        }

        // create the article
        $database->exec_INSERTquery('tx_commerce_articles', $articleData);
        $articleUid = $database->sql_insert_id();

        // create a new price that is assigned to the new article
        $database->exec_INSERTquery(
            'tx_commerce_article_prices',
            array(
                'pid' => $this->pid,
                'crdate' => time(),
                'tstamp' => time(),
                'uid_article' => $articleUid,
            )
        );

        // now write all relations between article and attributes into the database
        $relationBaseData = array(
            'uid_local' => $articleUid,
        );

        $createdArticleRelations = array();
        $relationCreateData = $relationBaseData;

        $productsAttributesRes = $database->exec_SELECTquery(
            'sorting, uid_local, uid_foreign',
            'tx_commerce_products_attributes_mm',
            'uid_local = ' . (int) $this->uid
        );
        $attributesSorting = array();
        while (($productsAttributes = $database->sql_fetch_assoc($productsAttributesRes))) {
            $attributesSorting[$productsAttributes['uid_foreign']] = $productsAttributes['sorting'];
        }

        if (is_array($data)) {
            foreach ($data as $selectAttributeUid => $selectAttributeValueUid) {
                $relationCreateData['uid_foreign'] = $selectAttributeUid;
                $relationCreateData['uid_valuelist'] = $selectAttributeValueUid;

                $relationCreateData['sorting'] = $attributesSorting[$selectAttributeUid];

                $createdArticleRelations[] = $relationCreateData;
                $database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
            }
        }

        if (is_array($this->attributes['rest'])) {
            foreach ($this->attributes['rest'] as $attribute) {
                if (empty($attribute['attributeData']['uid'])) {
                    continue;
                }

                $relationCreateData = $relationBaseData;

                $relationCreateData['sorting'] = $attribute['sorting'];
                $relationCreateData['uid_foreign'] = $attribute['attributeData']['uid'];
                if ($attribute['uid_correlationtype'] == 4) {
                    $relationCreateData['uid_product'] = $this->uid;
                }

                $relationCreateData['default_value'] = '';
                $relationCreateData['value_char'] = '';
                $relationCreateData['uid_valuelist'] = $attribute['uid_valuelist'];

                if (!$this->belib->isNumber($attribute['default_value'])) {
                    $relationCreateData['default_value'] = $attribute['default_value'];
                } else {
                    $relationCreateData['value_char'] = $attribute['default_value'];
                }

                $createdArticleRelations[] = $relationCreateData;

                $database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
            }
        }

        // update the article
        // we have to write the xml datastructure for this article. This is needed
        // to have the correct values inserted on first call of the article.
        $this->belib->updateArticleXML($createdArticleRelations, false, $articleUid);

        // Now check, if the parent Product is already lokalised, so creat Article in
        // the lokalised version Select from Database different localisations
        $resOricArticle = $database->exec_SELECTquery(
            '*',
            'tx_commerce_articles',
            'uid = ' . (int) $articleUid . ' AND deleted = 0'
        );
        $origArticle = $database->sql_fetch_assoc($resOricArticle);

        $result = $database->exec_SELECTquery(
            '*',
            'tx_commerce_products',
            'l18n_parent = ' . (int) $this->uid . ' AND deleted = 0'
        );

        if ($database->sql_num_rows($result)) {
            // Only if there are products
            while (($localizedProducts = $database->sql_fetch_assoc($result))) {
                // walk thru and create articles
                $destLanguage = $localizedProducts['sys_language_uid'];
                // get the highest sorting
                $langIsoCode = CoreBackendUtility::getRecord(
                    'sys_language',
                    (int) $destLanguage,
                    'static_lang_isocode'
                );
                $langIdent = CoreBackendUtility::getRecord(
                    'static_languages',
                    (int) $langIsoCode['static_lang_isocode'],
                    'lg_typo3'
                );
                $langIdent = strtoupper($langIdent['lg_typo3']);

                // create article data array
                $articleData = array(
                    'pid' => $this->pid,
                    'crdate' => time(),
                    'title' => $parameter['title'],
                    'uid_product' => $localizedProducts['uid'],
                    'sys_language_uid' => $localizedProducts['sys_language_uid'],
                    'l18n_parent' => $articleUid,
                    'sorting' => $sorting['sorting'] + 20,
                    'article_attributes' => count($this->attributes['rest']) + count($data),
                    'attribute_hash' => $hash,
                    'article_type_uid' => 1,
                    'attributesedit' => $this->belib->buildLocalisedAttributeValues(
                        $origArticle['attributesedit'],
                        $langIdent
                    ),
                );

                // create the article
                $database->exec_INSERTquery('tx_commerce_articles', $articleData);
                $localizedArticleUid = $database->sql_insert_id();

                // get all relations to attributes from the old article and copy them
                // to new article
                $res = $database->exec_SELECTquery(
                    '*',
                    'tx_commerce_articles_article_attributes_mm',
                    'uid_local = ' . (int) $origArticle['uid'] . ' AND uid_valuelist = 0'
                );

                while (($origRelation = $database->sql_fetch_assoc($res))) {
                    $origRelation['uid_local'] = $localizedArticleUid;

                    $database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $origRelation);
                }
                $this->belib->updateArticleXML($createdArticleRelations, false, $localizedArticleUid);
            }
        }

        return $articleUid;
    }

    /**
     * Returns a hidden field with the name and value of the current form element.
     *
     * @param array $parameter Parameter
     *
     * @return string
     */
    public function articleUid(array $parameter)
    {
        return '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
            htmlspecialchars($parameter['itemFormElValue']) . '">';
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

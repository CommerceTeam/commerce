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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * This class provides article rows for editing in product records.
 *
 * Class \CommerceTeam\Commerce\Form\Element\ExistingArticlesElement
 */
class ExistingArticlesElement extends AbstractFormElement
{
    /**
     * @var string
     */
    protected $table = 'tx_commerce_articles';

    /**
     * Render existing articles element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $articles = $this->data['databaseRow']['articles'];
        $attributes = $this->data['databaseRow']['attributes'];

        if ($this->data['vanillaUid'] == 0 || empty($articles)) {
            return 'No articles existing for this product';
        }

        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_list.xlf');

        $clear = '<span class="btn btn-default disabled">'
            . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';

        $output = '';
        $i = 0;
        foreach ($articles as $article) {
            $articleUid = (int) $article['uid'];

            $editAction = $this->getEditAction($article);
            $deleteAction = $this->getDeleteAction($article);
            $hideAction = $this->getHideAction($article, $articleUid);
            $moveUpAction = $this->getMoveUpAction($articles, $i, $article, $articleUid, $clear);
            $moveDownAction = $this->getMoveDownAction($articles, $i, $articleUid, $clear);
            $viewBigAction = $this->getViewBigAction($article);

            $toolTip = BackendUtility::getRecordToolTip($article, $this->table) . ' title="id=' . $articleUid . '"';
            $iconImg = '<span ' . $toolTip . '>'
                . $this->iconFactory->getIconForRecord($this->table, $article, Icon::SIZE_SMALL)->render()
                . '</span>';

            $fields = htmlspecialchars($article['title']);

            $valueList = '';
            if (is_array($attributes['ct1'])) {
                foreach ($attributes['ct1'] as $attribute) {
                    foreach ($attribute['values'] as $attributeData) {
                        if ($attribute['attributeData']['has_valuelist'] == 1) {
                            if ($attributeData['uid_valuelist'] == 0) {
                                // if the attribute has no value, create a select box with valid values
                                $valueList .= '<td><select name="updateData[' .
                                    $articleUid . '][' . (int) $attribute['uid_foreign'] . ']" />';
                                $valueList .= '<option value="0" selected="selected"></option>';
                                foreach ($attribute['valueList'] as $attrValueUid => $attrValueData) {
                                    $valueList .= '<option value="' . (int) $attrValueUid . '">'
                                        . htmlspecialchars($attrValueData['value']) . '</option>';
                                }
                                $valueList .= '</select></td>';
                            } else {
                                $valueList .= '<td>'
                                    .htmlspecialchars($attribute['valueList'][$attributeData['uid_valuelist']]['value'])
                                    . '</td>';
                            }
                        } elseif (!empty($attributeData['value_char'])) {
                            $valueList .= '<td>'
                                . htmlspecialchars(strip_tags($attributeData['value_char'])) . '</td>';
                        } else {
                            $valueList .= '<td>'
                                . htmlspecialchars(strip_tags($attributeData['default_value'])) . '</td>';
                        }
                    }
                }
            }

            $output .= '<tr data-uid="' . $articleUid . '">';
            $output .= '<td class="col-icon">' . $iconImg . '</td>
                <td nowrap="nowrap">' . $fields . '</td>';

            $output .= '<td class="col-control">'
                . $editAction
                . $hideAction
                . $deleteAction
                . $viewBigAction
                . $moveUpAction
                . $moveDownAction
                . '</td>
                <td>' . $valueList . '</td>
            </tr>';
            $i++;
        }

        $out = '
            <script>
                ' . $this->redirectUrls() . ';
            </script>
            <input type="hidden" name="deleteaid" value="0" />

            <!--
                DB listing of elements:	"' . htmlspecialchars($this->table) . '"
            -->
                <div class="panel panel-space panel-default">
                    <div class="panel-heading"></div>
                    <div class="table-fit" id="recordlist-' . htmlspecialchars($this->table) . '" data-state="expanded">
                        <table data-table="' . htmlspecialchars($this->table)
            . '" class="table table-striped table-hover">
                            <tbody>' . $output . '</tbody>
                        </table>
                    </div>
                </div>
            ';

        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = $out;
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/AjaxDataHandler';

        return $resultArray;
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
        $thisLocation = $thisLocation ? $thisLocation : GeneralUtility::linkThisScript([
            'CB' => '',
            'SET' => '',
            'cmd' => '',
            'popViewId' => '',
        ]);

        $out = '
            var T3_RETURN_URL = \'' .
            str_replace('%20', '', rawurlencode(GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl')))) .
            '\';
            var T3_THIS_LOCATION = \'' . str_replace('%20', '', rawurlencode($thisLocation)) . '\';
        ';

        return $out;
    }

    /**
     * Edit link
     *
     * @param array $article
     *
     * @return array
     */
    protected function getEditAction($article)
    {
        $params = '&edit[' . $this->table . '][' . $article['uid'] . ']=edit';
        $iconIdentifier = 'actions-open';
        $editAction = '<a class="btn btn-default" href="#" onclick="'
            . htmlspecialchars(BackendUtility::editOnClick($params, '', -1)) . '" title="'
            . $this->getLanguageService()->getLL('edit', true) . '">'
            . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render()
            . '</a>';

        return $editAction;
    }

    /**
     * Delete link
     *
     * @param $article
     *
     * @return array
     */
    protected function getDeleteAction($article)
    {
        $actionName = 'delete';
        $refCountMsg = BackendUtility::referenceCount(
            $this->table,
            $article['uid'],
            ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToRecord'),
            $article['_reference_count']
        ) . BackendUtility::translationCount(
            $this->table,
            $article['uid'],
            ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord')
        );
        $titleOrig = BackendUtility::getRecordTitle($this->table, $article, false, true);
        $title = GeneralUtility::slashJS(GeneralUtility::fixed_lgd_cs($titleOrig, 30), true);
        $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" ' . '['
            . $this->table . ':' . $article['uid'] . ']' . $refCountMsg;

        $params = 'cmd[' . $this->table . '][' . $article['uid'] . '][delete]=1';
        $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
        $linkTitle = $this->getLanguageService()->getLL($actionName, true);
        $deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" '
            . ' data-l10parent="' . htmlspecialchars($article['l10n_parent'])
            . '" data-params="' . htmlspecialchars($params)
            . '" data-title="' . htmlspecialchars($titleOrig)
            . '" data-message="' . htmlspecialchars($warningText)
            . '" title="' . $linkTitle
            . '">' . $icon . '</a>';

        return $deleteAction;
    }

    /**
     * Hide link
     *
     * @param $article
     * @param $articleUid
     *
     * @return array
     */
    protected function getHideAction($article, $articleUid)
    {
        $hideTitle = $this->getLanguageService()->getLL('hide', true);
        $unhideTitle = $this->getLanguageService()->getLL('unHide', true);
        if ($article['hidden']) {
            $params = 'data[' . $this->table . '][' . $articleUid . '][hidden]=0';
            $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="hidden" href="#"'
                . ' data-params="' . htmlspecialchars($params)
                . '" title="' . $unhideTitle
                . '" data-toggle-title="'
                . $hideTitle . '">' . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render()
                . '</a>';
        } else {
            $params = 'data[' . $this->table . '][' . $articleUid . '][hidden]=1';
            $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="visible" href="#"'
                . ' data-params="' . htmlspecialchars($params)
                . '" title="' . $hideTitle
                . '" data-toggle-title="'
                . $unhideTitle . '">' . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render()
                . '</a>';
        }

        return $hideAction;
    }

    /**
     * Move up link
     *
     * @param $articles
     * @param $i
     * @param $article
     * @param $articleUid
     * @param $clear
     *
     * @return array
     */
    protected function getMoveUpAction($articles, $i, $article, $articleUid, $clear)
    {
        // there must be one previous article
        if (isset($articles[$i - 1])) {
            // there are more the one previous article so use the negative uid of the previous article
            if (isset($articles[$i - 2])) {
                $moveItTo = '-' . (int)$articles[$i - 2]['uid'];
            // there is only one previous article so use the pageid
            } else {
                $moveItTo = (int)$article['pid'];
            }
            $params = '&cmd[' . $this->table . '][' . $articleUid . '][move]=' . $moveItTo;
            $moveUpAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars('return jumpToUrl('
                . BackendUtility::getLinkToDataHandlerAction($params, -1)
                . ');')
                . '" title="'
                . $this->getLanguageService()->getLL('moveUp', true) . '">'
                . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $moveUpAction = $clear;
        }

        return $moveUpAction;
    }

    /**
     * Move down link
     *
     * @param $articles
     * @param $i
     * @param $articleUid
     * @param $clear
     *
     * @return array
     */
    protected function getMoveDownAction($articles, $i, $articleUid, $clear)
    {
        // at least one following article must exist
        if (isset($articles[$i + 1])) {
            $params = '&cmd[' . $this->table . '][' . $articleUid . '][move]=-' . (int)$articles[$i + 1]['uid'];
            $moveDownAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars('return jumpToUrl('
                . BackendUtility::getLinkToDataHandlerAction($params, -1)
                . ');')
                . '" title="'
                . $this->getLanguageService()->getLL('moveDown', true) . '">'
                . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $moveDownAction = $clear;
        }

        return $moveDownAction;
    }

    /**
     * @param $article
     *
     * @return string
     */
    protected function getViewBigAction($article)
    {
        // "Info": (All records)
        $onClick = 'top.launchView(' . GeneralUtility::quoteJSvalue($this->table) . ', ' . (int)$article['uid']
            . '); return false;';
        $viewBigAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
            . $this->getLanguageService()->getLL('showInfo', true) . '">'
            . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';

        return $viewBigAction;
    }
}

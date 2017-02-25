<?php
namespace CommerceTeam\Commerce\Form\Container;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class ExistingArticleContainer
{
    /**
     * Main data array to work on, given from parent to child elements
     *
     * @var array
     */
    protected $data = array();

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var string
     */
    protected $clear;

    /**
     * ExistingArticleContainer constructor.
     *
     * @param string $table
     * @param array $data
     * @param IconFactory $iconFactory
     */
    public function __construct($table, $data, $iconFactory)
    {
        $this->table = $table;
        $this->data = $data;
        $this->iconFactory = $iconFactory;

        $this->clear = '<span class="btn btn-default disabled">' .
            $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() .
            '</span>';

        $this->backendUtility = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
    }

    /**
     * @param array $articles
     * @param array $article
     * @param int $i
     *
     * @return string
     */
    public function renderArticleRow($articles, $article, $i)
    {
        $output = '';
        $articleUid = (int) $article['uid'];
        $attributes = $this->backendUtility->getAttributesForArticle($articleUid, 1);

        $editAction = $this->getEditAction($article);
        $deleteAction = $this->getDeleteAction($article);
        $hideAction = $this->getHideAction($article, $articleUid);
        $moveUpAction = $this->getMoveUpAction($articles, $i, $article, $articleUid, $this->clear);
        $moveDownAction = $this->getMoveDownAction($articles, $i, $articleUid, $this->clear);
        $viewBigAction = $this->getViewBigAction($article);

        $toolTip = BackendUtility::getRecordToolTip($article, $this->table) . ' title="id=' . $articleUid . '"';
        $iconImg = '<span ' . $toolTip . '>' .
            $this->iconFactory->getIconForRecord($this->table, $article, Icon::SIZE_SMALL)->render() .
            '</span>';

        $fields = htmlspecialchars($article['title']);

        $valueList = '';
        foreach ($attributes as $attribute) {
            if ($attribute['has_valuelist'] == 1) {
                if ($attribute['uid_valuelist'] == 0) {
                    // if the attribute has no value, create a select box with valid values
                    $valueList .= '<td><select name="updateData[' .
                        $articleUid . '][' . (int) $attribute['uid_foreign'] . ']" class="form-control">';
                    $valueList .= '<option value="0" selected="selected"></option>';
                    foreach ($attribute['valueList'] as $attrValueUid => $attrValueData) {
                        $valueList .= '<option value="' . (int) $attrValueUid . '">'
                            . htmlspecialchars($attrValueData['value']) . '</option>';
                    }
                    $valueList .= '</select></td>';
                } else {
                    $valueList .= '<td>' . htmlspecialchars(
                        $attribute['valueList'][$attribute['uid_valuelist']]['value']
                    ) . '</td>';
                }
            } elseif (!empty($attribute['value_char'])) {
                $valueList .= '<td>' . htmlspecialchars($attribute['value_char']) . '</td>';
            } else {
                $valueList .= '<td>' . htmlspecialchars($attribute['default_value']) . '</td>';
            }
        }

        $output .= '<tr data-uid="' . $articleUid . '">
            <td class="col-icon">' . $iconImg . '</td>
            <td nowrap="nowrap" class="col-title">' . $fields . '</td>
            <td class="col-control">' .
            $editAction .
            $hideAction .
            $deleteAction .
            $viewBigAction .
            $moveUpAction .
            $moveDownAction .
            '</td>' .
            $valueList .
        '</tr>';

        return $output;
    }

    /**
     * Edit link
     *
     * @param array $article
     *
     * @return string
     */
    protected function getEditAction($article)
    {
        $params = '&edit[' . $this->table . '][' . $article['uid'] . ']=edit';
        $iconIdentifier = 'actions-open';
        $editAction = '<a class="btn btn-default" href="#" onclick="' .
            htmlspecialchars(BackendUtility::editOnClick($params, '', -1)) . '" title="' .
            $this->getLanguageService()->getLL('edit', true) . '">' .
            $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() .
            '</a>';

        return $editAction;
    }

    /**
     * Delete link
     *
     * @param array $article
     *
     * @return string
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
        $title = str_replace('\\', '\\\\', GeneralUtility::fixed_lgd_cs($titleOrig, 30));
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
     * @param array $article
     * @param int $articleUid
     *
     * @return string
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
     * @param array $articles
     * @param int $i
     * @param array $article
     * @param int $articleUid
     * @param $clear
     *
     * @return string
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
            $moveUpAction = '<a class="btn btn-default" href="#" onclick="' .
                htmlspecialchars(
                    'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                ) .
                '" title="' .
                $this->getLanguageService()->getLL('moveUp', true) . '">' .
                $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() .
                '</a>';
        } else {
            $moveUpAction = $clear;
        }

        return $moveUpAction;
    }

    /**
     * Move down link
     *
     * @param array $articles
     * @param int $i
     * @param int $articleUid
     * @param string $clear
     *
     * @return string
     */
    protected function getMoveDownAction($articles, $i, $articleUid, $clear)
    {
        // at least one following article must exist
        if (isset($articles[$i + 1])) {
            $params = '&cmd[' . $this->table . '][' . $articleUid . '][move]=-' . (int)$articles[$i + 1]['uid'];
            $moveDownAction = '<a class="btn btn-default" href="#" onclick="' .
                htmlspecialchars(
                    'return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');'
                ) .
                '" title="' .
                $this->getLanguageService()->getLL('moveDown', true) . '">' .
                $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() .
                '</a>';
        } else {
            $moveDownAction = $clear;
        }

        return $moveDownAction;
    }

    /**
     * @param array $article
     *
     * @return string
     */
    protected function getViewBigAction($article)
    {
        // "Info": (All records)
        $onClick = 'top.launchView(' . GeneralUtility::quoteJSvalue($this->table) . ', ' . (int)$article['uid'] .
            '); return false;';
        $viewBigAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' .
            $this->getLanguageService()->getLL('showInfo', true) . '">' .
            $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() .
            '</a>';

        return $viewBigAction;
    }


    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}

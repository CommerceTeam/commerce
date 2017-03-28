<?php
namespace CommerceTeam\Commerce\ViewHelpers\Be\Link;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * View helper which return page info icon as known from TYPO3 backend modules
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code>
 * <f:be.pageInfo />
 * </code>
 * <output>
 * Page info icon with context menu
 * </output>
 */
class EditActionViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * Initialize arguments.
     *
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('tableName', 'string', 'Table of which the records should get edited', true);
        $this->registerArgument('uids', 'array', 'Uids of records to edit with this action', true);
        $this->registerArgument('columnsOnly', 'array', 'Columns to edit with this action', false);
        $this->registerArgument('content', 'string', 'Content of the link', false);
    }

    /**
     * Render edit action link
     *
     * @return string the rendered edit action link
     */
    public function render()
    {
        $tableName = $this->arguments['tableName'];
        $uids = $this->arguments['uids'];
        $columnsOnly = $this->arguments['columnsOnly'];
        $content = $this->arguments['content'];
        $title = $this->arguments['title'];

        return static::renderStatic(
            [
                'tableName' => $tableName,
                'uids' => $uids,
                'columnsOnly' => $columnsOnly,
                'content' => $content,
                'title' => $title,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $tableName = $arguments['tableName'];
        $uids = $arguments['uids'] ?: [];
        $columnsOnly = $arguments['columnsOnly'] ?: [];
        $content = $arguments['content'] ?: $renderChildrenClosure();
        $title = $arguments['title'];

        $thisLocation = GeneralUtility::linkThisScript([
            'CB' => '',
            'SET' => '',
            'cmd' => '',
            'popViewId' => '',
        ]);

        $thisLocation = str_replace('%20', '', rawurlencode($thisLocation));

        $editIdList = implode(',', $uids);
        $params = 'edit[' . $tableName . '][' . $editIdList . ']=edit&columnsOnly=' . implode(',', $columnsOnly);
        // we need to build this uri differently,
        // otherwise GeneralUtility::quoteJSvalue messes up the edit list function
        $onClick = BackendUtility::editOnClick('', '', -1);
        $onClickArray = explode('?', $onClick, 2);
        $lastElement = array_pop($onClickArray);
        array_push($onClickArray, $params . '&' . $lastElement);
        $onClickAction = str_replace('T3_THIS_LOCATION', '\'' . $thisLocation . '\'', implode('?', $onClickArray));
        $onClickAttribute = 'onclick="' . htmlspecialchars($onClickAction) . '"';

        return '<a href="#" ' . $onClickAttribute . ' title="' . htmlspecialchars($title) . '">' . $content . '</a>';
    }
}

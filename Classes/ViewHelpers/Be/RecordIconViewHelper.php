<?php
namespace CommerceTeam\Commerce\ViewHelpers\Be;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class RecordIconViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('tableName', 'string', 'Table of which the records should get edited', true);
        $this->registerArgument('record', 'array', 'All fields of record', true);
    }

    /**
     * Render edit action link
     *
     * @return string the rendered edit action link
     */
    public function render()
    {
        $tableName = $this->arguments['tableName'];
        $record = $this->arguments['record'];

        return static::renderStatic(
            [
                'tableName' => $tableName,
                'record' => $record,
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
        $record = $arguments['record'];

        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $params = '&edit[' . $tableName . '][' . $record['uid'] . ']=edit';

        $iconImgTag = $iconFactory->getIconForRecord($tableName, $record, Icon::SIZE_SMALL)->render();
        $onclickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick($params)) . '"';
        $content =
            BackendUtility::wrapClickMenuOnIcon($iconImgTag, $tableName, $record['uid']) .
            '<b>
                <a href="#" ' . $onclickAction . '>' .
            htmlspecialchars(GeneralUtility::fixed_lgd_cs(
                strip_tags(BackendUtility::getRecordTitle($tableName, $record)),
                45
            )) .
            '   </a>
            </b>';

        return $content;
    }
}

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

use CommerceTeam\Commerce\Tree\View\CategoryTreeElementCategoryTreeView;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Form\Element\SelectMultipleSideBySideElement;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

class CategoryTreeElement extends SelectMultipleSideBySideElement implements LinkParameterProviderInterface
{
    /**
     * Default field controls for this element.
     *
     * @var array
     */
    protected $defaultFieldControl = [];

    /**
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [];

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var Clipboard
     */
    protected $clipboard;

    /**
     * Render category tree element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementName = $parameterArray['itemFormElName'];

        if ($config['readOnly']) {
            // Early return for the relatively simple read only case
            return $this->renderReadOnly();
        }

        $possibleItems = $config['items'];
        $selectedItems = $parameterArray['itemFormElValue'] ?: [];
        $selectedItemsCount = count($selectedItems);

        $maxItems = $config['maxitems'];
        $autoSizeMax = MathUtility::forceIntegerInRange($config['autoSizeMax'], 0);
        $size = 2;
        if (isset($config['size'])) {
            $size = (int)$config['size'];
        }
        if ($autoSizeMax >= 1) {
            $size = MathUtility::forceIntegerInRange(
                $selectedItemsCount + 1,
                MathUtility::forceIntegerInRange($size, 1),
                $autoSizeMax
            );
        }
        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);

        $listOfSelectedValues = [];
        $selectedItemsHtml = [];
        foreach ($selectedItems as $itemNumber => $itemValue) {
            foreach ($possibleItems as $possibleItem) {
                if ($possibleItem[1] == $itemValue) {
                    $title = $possibleItem[0];
                    $listOfSelectedValues[] = (int) $itemValue;
                    $selectedItemsHtml[] = '<option value="' . htmlspecialchars($itemValue) .
                        '" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($title) . '</option>';
                    break;
                }
            }
        }

        /** @var CategoryTreeElementCategoryTreeView $categoryTree */
        $categoryTree = GeneralUtility::makeInstance(CategoryTreeElementCategoryTreeView::class);
        $categoryTree->setLinkParameterProvider($this);
        $categoryTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $categoryTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $categoryTree->addField('navtitle');
        $tree = $categoryTree->getBrowsableTree();

        // Html stuff for filter and select filter on top of right side of multi select boxes
        $filterTextfield = [];
        if ($config['enableMultiSelectFilterTextfield']) {
            $filterTextfield[] = '<span class="input-group input-group-sm">';
            $filterTextfield[] =    '<span class="input-group-addon">';
            $filterTextfield[] =        '<span class="fa fa-filter"></span>';
            $filterTextfield[] =    '</span>';
            $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control">';
            $filterTextfield[] = '</span>';
        }
        $filterDropDownOptions = [];
        if (isset($config['multiSelectFilterItems'])
            && is_array($config['multiSelectFilterItems'])
            && count($config['multiSelectFilterItems']) > 1
        ) {
            foreach ($config['multiSelectFilterItems'] as $optionElement) {
                $value = $languageService->sL($optionElement[0]);
                $label = $value;
                if (isset($optionElement[1]) && trim($optionElement[1]) !== '') {
                    $label = $languageService->sL($optionElement[1]);
                }
                $filterDropDownOptions[] = '<option value="' . htmlspecialchars($value) . '">' .
                    htmlspecialchars($label) . '</option>';
            }
        }
        $filterHtml = [];
        if (!empty($filterTextfield) || !empty($filterDropDownOptions)) {
            $filterHtml[] = '<div class="form-multigroup-item-wizard">';
            if (!empty($filterTextfield) && !empty($filterDropDownOptions)) {
                $filterHtml[] = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">';
                $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
                $filterHtml[] =         '<select class="form-control input-sm ';
                $filterHtml[] =         't3js-formengine-multiselect-filter-dropdown">';
                $filterHtml[] =             implode(LF, $filterDropDownOptions);
                $filterHtml[] =         '</select>';
                $filterHtml[] =     '</div>';
                $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
                $filterHtml[] =         implode(LF, $filterTextfield);
                $filterHtml[] =     '</div>';
                $filterHtml[] = '</div>';
            } elseif (!empty($filterTextfield)) {
                $filterHtml[] = implode(LF, $filterTextfield);
            } else {
                $filterHtml[] = '<select class="form-control input-sm t3js-formengine-multiselect-filter-dropdown">';
                $filterHtml[] =     implode(LF, $filterDropDownOptions);
                $filterHtml[] = '</select>';
            }
            $filterHtml[] = '</div>';
        }

        $classes = [];
        $classes[] = 'form-control';
        $classes[] = 'tceforms-multiselect';
        if ($maxItems === 1) {
            $classes[] = 'form-select-no-siblings';
        }
        $multipleAttribute = '';
        if ($maxItems !== 1 && $size !== 1) {
            $multipleAttribute = ' multiple="multiple"';
        }
        $selectedListStyle = '';
        if (isset($config['selectedListStyle'])) {
            $selectedListStyle = ' style="' . htmlspecialchars($config['selectedListStyle']) . '"';
        }

        $height = ($size * 18 + 14) - ($config['enableMultiSelectFilterTextfield'] ? 30 : 0);

        // Put together the selector box:
        $selectableListStyle = ' style="height: ' . $height . 'px; overflow-y: scroll; ' . (
            isset($config['itemListStyle']) ?
            htmlspecialchars($config['itemListStyle']) :
            ''
        ) . '"';

        $itemsToSelect[] =          '<div ';
        $itemsToSelect[] =              ' data-relatedfieldname="' . htmlspecialchars($elementName) . '"';
        $itemsToSelect[] =              ' data-exclusivevalues="' . htmlspecialchars($config['exclusiveKeys']) . '"';
        $itemsToSelect[] =              ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $itemsToSelect[] =              ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $itemsToSelect[] =              ' class="form-control t3js-commerce-categorytree-itemstoselect"';
        $itemsToSelect[] =              ' size="' . $size . '"';
        $itemsToSelect[] =              ' data-formengine-validation-rules="' .
            htmlspecialchars($this->getValidationDataAsJsonString($config)) . '"';
        $itemsToSelect[] =              $selectableListStyle;
        $itemsToSelect[] =          '>';
        $itemsToSelect[] =              $tree;
        $itemsToSelect[] =          '</div>';

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item commerce-categorytree-element">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<input type="hidden" data-formengine-input-name="' . htmlspecialchars($elementName) .
            '" value="' . (int)$itemCanBeSelectedMoreThanOnce . '" />';
        $html[] =           '<div class="form-multigroup-wrap t3js-formengine-field-group">';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL(
            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.selected'
        ));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           '<select';
        $html[] =                               ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $html[] =                               ' size="' . $size . '"';
        $html[] =                               ' class="' . implode(' ', $classes) . '"';
        $html[] =                               $multipleAttribute;
        $html[] =                               ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $html[] =                               ' data-selected-values="' . json_encode($listOfSelectedValues) . '"';
        $html[] =                               $selectedListStyle;
        $html[] =                           '>';
        $html[] =                               implode(LF, $selectedItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        $html[] =                       '<div class="form-wizards-items-aside">';
        $html[] =                           '<div class="btn-group-vertical">';
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-top"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_to_top'
            )) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon(
                'actions-move-to-top',
                Icon::SIZE_SMALL
            )->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-up"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_up'
            )) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon(
                'actions-move-up',
                Icon::SIZE_SMALL
            )->render();
            $html[] =                           '</a>';
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-down"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_down'
            )) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon(
                'actions-move-down',
                Icon::SIZE_SMALL
            )->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-bottom"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom'
            )) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon(
                'actions-move-to-bottom',
                Icon::SIZE_SMALL
            )->render();
            $html[] =                           '</a>';
        }

        $html[] =                               '<a href="#"';
        $html[] =                                   ' class="btn btn-default t3js-btn-removeoption"';
        $html[] =                                   ' data-fieldname="' . htmlspecialchars($elementName) . '"';
        $html[] =                                   ' title="' . htmlspecialchars($languageService->sL(
            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.remove_selected'
        )) . '"';
        $html[] =                               '>';
        $html[] =                                   $this->iconFactory->getIcon(
            'actions-selection-delete',
            Icon::SIZE_SMALL
        )->render();
        $html[] =                               '</a>';
        $html[] =                           '</div>';
        $html[] =                       '</div>';
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL(
            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.items'
        ));
        $html[] =                   '</label>';
        $html[] =                   implode(LF, $filterHtml);
        $html[] =                   implode(' ', $itemsToSelect);
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<input type="hidden" name="' . htmlspecialchars($elementName) . '" value="' .
            htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';
        $html[] =       '</div>';
        $html[] =       '<div class="form-wizards-items-aside">';
        $html[] =           '<div class="btn-group-vertical">';
        $html[] =               $fieldControlHtml;
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =       '<div class="form-wizards-items-bottom">';
        $html[] =           $fieldWizardHtml;
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Commerce/FormElementCategoryTree';

        return $resultArray;
    }

    /**
     * @return string
     */
    public function getScriptUrl()
    {
        return GeneralUtility::getIndpEnv('SCRIPT_NAME');
    }

    /**
     * @param array $values
     * @return array
     */
    public function getUrlParameters(array $values)
    {
        return $values;
    }

    /**
     * @param array $values Values to be checked
     *
     * @return bool Returns true if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return !empty($this->items) && isset($this->items[(int)$values['uid']]);
    }
}

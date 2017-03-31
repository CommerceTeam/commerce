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
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\Icon;
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
                if ($possibleItem[1] === $itemValue) {
                    $title = $possibleItem[0];
                    $listOfSelectedValues[] = $itemValue;
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
                $filterHtml[] =         '<select class="form-control input-sm t3js-formengine-multiselect-filter-dropdown">';
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
        $selectableListStyle = '';
        if (isset($config['itemListStyle'])) {
            $selectableListStyle = ' style="' . htmlspecialchars($config['itemListStyle']) . '"';
        }

        $itemsToSelect[] =          '<div ';
        $itemsToSelect[] =              ' data-relatedfieldname="' . htmlspecialchars($elementName) . '"';
        $itemsToSelect[] =              ' data-exclusivevalues="' . htmlspecialchars($config['exclusiveKeys']) . '"';
        $itemsToSelect[] =              ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $itemsToSelect[] =              ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $itemsToSelect[] =              ' class="form-control t3js-commerce-categorytree-itemstoselect"';
        $itemsToSelect[] =              ' size="' . $size . '"';
        $itemsToSelect[] =              ' data-fieldchanged-values="{';
        $itemsToSelect[] =                  'tableName: \'' . htmlspecialchars($table) . '\',';
        $itemsToSelect[] =                  'uid:' . $this->data['vanillaUid'] . ',';
        $itemsToSelect[] =                  'fieldName: \'' . htmlspecialchars($elementName) . '\',';
        $itemsToSelect[] =                  'element: \'' . htmlspecialchars($elementName) . '\'}" ';
        $itemsToSelect[] =              ' data-formengine-validation-rules="' .
            htmlspecialchars($this->getValidationDataAsJsonString($config)) . '"';
        $itemsToSelect[] =              $selectableListStyle;
        $itemsToSelect[] =          '>';
        $itemsToSelect[] =              $tree;
        $itemsToSelect[] =          '</div>';

        $legacyWizards = $this->renderWizards();
        $legacyFieldControlHtml = implode(LF, $legacyWizards['fieldControl']);
        $legacyFieldWizardHtml = implode(LF, $legacyWizards['fieldWizard']);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $legacyFieldControlHtml . $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $legacyFieldWizardHtml . $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<input type="hidden" data-formengine-input-name="' . htmlspecialchars($elementName) . '" value="' . (int)$itemCanBeSelectedMoreThanOnce . '" />';
        $html[] =           '<div class="form-multigroup-wrap t3js-formengine-field-group">';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.selected'));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           '<select';
        $html[] =                               ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $html[] =                               ' size="' . $size . '"';
        $html[] =                               ' class="' . implode(' ', $classes) . '"';
        $html[] =                               $multipleAttribute;
        $html[] =                               ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
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
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-up"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-down"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-bottom"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        $html[] =                               '<a href="#"';
        $html[] =                                   ' class="btn btn-default t3js-btn-removeoption"';
        $html[] =                                   ' data-fieldname="' . htmlspecialchars($elementName) . '"';
        $html[] =                                   ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
        $html[] =                               '>';
        $html[] =                                   $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render();
        $html[] =                               '</a>';
        $html[] =                           '</div>';
        $html[] =                       '</div>';
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.items'));
        $html[] =                   '</label>';
        $html[] =                   implode(LF, $filterHtml);
        /*$html[] =                   '<select';
        $html[] =                       ' data-relatedfieldname="' . htmlspecialchars($elementName) . '"';
        $html[] =                       ' data-exclusivevalues="' . htmlspecialchars($config['exclusiveKeys']) . '"';
        $html[] =                       ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $html[] =                       ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $html[] =                       ' class="form-control t3js-formengine-select-itemstoselect"';
        $html[] =                       ' size="' . $size . '"';
        $html[] =                       ' onchange="' . htmlspecialchars(implode('', $parameterArray['fieldChangeFunc'])) . '"';
        $html[] =                       ' data-formengine-validation-rules="' . htmlspecialchars($this->getValidationDataAsJsonString($config)) . '"';
        $html[] =                       $selectableListStyle;
        $html[] =                   '>';
        $html[] =                       implode(LF, $selectableItemsHtml);
        $html[] =                   '</select>';*/
        $html[] =                   implode(' ', $itemsToSelect);
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<input type="hidden" name="' . htmlspecialchars($elementName) . '" value="' . htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';
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
        return $resultArray;

        // @todo continue here



        // enable filter functionality via a text field
        if ($config['enableMultiSelectFilterTextfield']) {
            $filterTextfield[] = '<span class="input-group input-group-sm">';
            $filterTextfield[] =    '<span class="input-group-addon">';
            $filterTextfield[] =        '<span class="fa fa-filter"></span>';
            $filterTextfield[] =    '</span>';
            $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control">';
            $filterTextfield[] = '</span>';
        }

        if (!empty(trim($filterSelectbox)) && !empty($filterTextfield)) {
            $filterSelectbox = '<div class="form-multigroup-item form-multigroup-element a">'
                . $filterSelectbox . '</div>';
            $filterTextfield = '<div class="form-multigroup-item form-multigroup-element">'
                . implode(LF, $filterTextfield) . '</div>';
            $selectBoxFilterContents = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">'
                . $filterSelectbox . $filterTextfield . '</div>';
        } else {
            $selectBoxFilterContents = trim($filterSelectbox . ' ' . implode(LF, $filterTextfield));
        }

        // Pass to "dbFileIcons" function:
        $params = [
            'size' => $size,
            'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
            'style' => isset($config['selectedListStyle'])
                ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
                : '',
            'dontShowMoveIcons' => $maxItems <= 1,
            'maxitems' => $maxItems,
            'info' => '',
            'headers' => [
                'selector' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.selected'),
                'items' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.items'),
                'selectorbox' => $selectBoxFilterContents,
            ],
            'noBrowser' => 1,
            'rightbox' => implode(LF, $itemsToSelect),
        ];
        $html .= $this->dbFileIcons(
            $parameterArray['itemFormElName'],
            '',
            '',
            $itemsArray,
            '',
            $params,
            $parameterArray['onFocus']
        );

        // Wizards:
        $html = '<div class="commerce-categorytree-element">' . $this->renderWizards(
            [$html],
            $config['wizards'],
            $table,
            $this->data['databaseRow'],
            $field,
            $parameterArray,
            $parameterArray['itemFormElName'],
            $parameterArray['fieldConf']['defaultExtras']
        ) . '</div>';

        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Commerce/FormElementCategoryTree';

        $resultArray['html'] = $html;
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

    /**
     * Get backend user authentication
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }


    /**
     * Prints the selector box form-field for the db/file/select elements (multiple)
     *
     * @param string $fName Form element name
     * @param string $mode Mode "db", "file" (internal_type for the "group" type) OR blank (then for the "select" type)
     * @param string $allowed Commalist of "allowed
     * @param array $itemArray The array of items. For "select" and "group"/"file" this is just a set of value.
     *  For "db" its an array of arrays with table/uid pairs.
     * @param string $selector Alternative selector box.
     * @param array $params An array of additional parameters, eg: "size", "info", "headers" (array with "selector"
     *  and "items"), "noBrowser", "thumbnails
     * @param string $onFocus On focus attribute string
     * @param string $table (optional) Table name processing for
     * @param string $field (optional) Field of table name processing for
     * @param string $uid (optional) uid of table record processing for
     * @param array $config (optional) The TCA field config
     * @return string The form fields for the selection.
     * @throws \UnexpectedValueException
     * @todo: Hack this mess into pieces and inline to group / select element depending on what they need
     */
    protected function dbFileIcons(
        $fName,
        $mode,
        $allowed,
        $itemArray,
        $selector = '',
        $params = array(),
        $onFocus = '',
        $table = '',
        $field = '',
        $uid = '',
        $config = array()
    ) {
        $languageService = $this->getLanguageService();

        // INIT
        $uidList = array();
        $opt = array();
        $itemArrayC = 0;
        // Creating <option> elements:
        if (is_array($itemArray)) {
            $itemArrayC = count($itemArray);
            foreach ($itemArray as $pp) {
                $pRec = BackendUtility::getRecordWSOL($pp['table'], $pp['id']);
                if (is_array($pRec)) {
                    $pTitle = BackendUtility::getRecordTitle($pp['table'], $pRec, false, true);
                    $pUid = $pp['table'] . '_' . $pp['id'];
                    $uidList[] = $pUid;
                    $title = htmlspecialchars($pTitle);
                    $opt[] = '<option value="' . htmlspecialchars($pUid) . '" title="' . $title . '">' .
                        $title . '</option>';
                }
            }
        }
        // Create selector box of the options
        $sSize = $params['autoSizeMax'] ?
            MathUtility::forceIntegerInRange(
                $itemArrayC + 1,
                MathUtility::forceIntegerInRange($params['size'], 1),
                $params['autoSizeMax']
            ) :
            $params['size'];
        if (!$selector) {
            $isMultiple = $params['maxitems'] != 1 && $params['size'] != 1;
            $selector = '<select id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '" ' .
                ($params['noList'] ?
                    'style="display: none"' :
                    'size="' . $sSize . '" class="form-control tceforms-multiselect"') .
                ($isMultiple ? ' multiple="multiple"' : '') .
                ' data-formengine-input-name="' . htmlspecialchars($fName) . '" ' .
                $this->getValidationDataAsJsonString($config) . $onFocus . $params['style'] . '>' .
                implode('', $opt) .
                '</select>';
        }
        $icons = array(
            'L' => array(),
            'R' => array()
        );
        $rOnClickInline = '';
        if (!$params['readOnly'] && !$params['noList']) {
            if (!$params['noBrowser']) {
                // Check against inline uniqueness
                /** @var InlineStackProcessor $inlineStackProcessor */
                $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
                $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
                $aOnClickInline = '';
                if ($this->data['isInlineChild'] && $this->data['inlineParentUid']) {
                    if ($this->data['inlineParentConfig']['foreign_table'] === $table
                        && $this->data['inlineParentConfig']['foreign_unique'] === $field
                    ) {
                        $objectPrefix = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix(
                            $this->data['inlineFirstPid']
                        ) . '-' . $table;
                        $aOnClickInline = $objectPrefix . '|inline.checkUniqueElement|inline.setUniqueElement';
                        $rOnClickInline = 'onClick="inline.revertUnique(' .
                            GeneralUtility::quoteJSvalue($objectPrefix) .
                            ',null,' . GeneralUtility::quoteJSvalue($uid) . ');"';
                    }
                }
                if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserType'])) {
                    $elementBrowserType = $config['appearance']['elementBrowserType'];
                } else {
                    $elementBrowserType = $mode;
                }
                if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserAllowed'])) {
                    $elementBrowserAllowed = $config['appearance']['elementBrowserAllowed'];
                } else {
                    $elementBrowserAllowed = $allowed;
                }
                $aOnClick = 'onclick="' . htmlspecialchars(
                    'setFormValueOpenBrowser(' . GeneralUtility::quoteJSvalue($elementBrowserType) . ',' .
                    GeneralUtility::quoteJSvalue(($fName . '|||' . $elementBrowserAllowed . '|' . $aOnClickInline)) .
                    '); return false;'
                ) . '"';
                $icons['R'][] = '
					<a href="#" ' . $aOnClick . '
						class="btn btn-default"
						title="' .
                        htmlspecialchars($languageService->sL(
                            'LLL:EXT:lang/locallang_core.xlf:labels.browse_' . ($mode == 'db' ? 'db' : 'file')
                        )) .
                    '">' . $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL)->render() . '
					</a>';
            }
            if (!$params['dontShowMoveIcons']) {
                if ($sSize >= 5) {
                    $icons['L'][] = '
						<a href="#"
							class="btn btn-default t3-btn-moveoption-top"
							data-fieldname="' . $fName . '"
							title="' .
                        htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_to_top')) .
                        '">' . $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render() . '
						</a>';
                }
                $icons['L'][] = '
					<a href="#"
						class="btn btn-default t3-btn-moveoption-up"
						data-fieldname="' . $fName . '"
						title="' .
                    htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_up')) .
                    '">
						' . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '
					</a>';
                $icons['L'][] = '
					<a href="#"
						class="btn btn-default t3-btn-moveoption-down"
						data-fieldname="' . $fName . '"
						title="' .
                    htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_down')) .
                    '">' . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '
					</a>';
                if ($sSize >= 5) {
                    $icons['L'][] = '
						<a href="#"
							class="btn btn-default t3-btn-moveoption-bottom"
							data-fieldname="' . $fName . '"
							title="' .
                        htmlspecialchars(
                            $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move_to_bottom')
                        ) .
                        '">' . $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render() . '
						</a>';
                }
            }
            $clipElements = $this->getClipboardElements($allowed, $mode);
            if (!empty($clipElements)) {
                $aOnClick = '';
                foreach ($clipElements as $elValue) {
                    if ($mode == 'db') {
                        list($itemTable, $itemUid) = explode('|', $elValue);
                        $recordTitle = BackendUtility::getRecordTitle(
                            $itemTable,
                            BackendUtility::getRecordWSOL($itemTable, $itemUid)
                        );
                        $itemTitle = GeneralUtility::quoteJSvalue($recordTitle);
                        $elValue = $itemTable . '_' . $itemUid;
                    } else {
                        // 'file', 'file_reference' and 'folder' mode
                        $itemTitle = 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(basename($elValue))) . ')';
                    }
                    $aOnClick .= 'setFormValueFromBrowseWin(' . GeneralUtility::quoteJSvalue($fName) . ',unescape(' .
                        GeneralUtility::quoteJSvalue(
                            rawurlencode(str_replace('%20', ' ', $elValue))
                        ) . '),' . $itemTitle . ',' . $itemTitle . ');';
                }
                $aOnClick .= 'onclick="' . htmlspecialchars('return false;') . '"';
                $icons['R'][] = '
					<a href="#" ' . $aOnClick . '
						title="' .
                    htmlspecialchars(sprintf(
                        $languageService->sL(
                            'LLL:EXT:lang/locallang_core.xlf:labels.clipInsert_' . ($mode == 'db' ? 'db' : 'file')
                        ),
                        count($clipElements)
                    )) . '">
						' . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() . '
					</a>';
            }
        }
        if (!$params['readOnly'] && !$params['noDelete']) {
            $icons['L'][] = '
				<a href="#"
					class="btn btn-default t3-btn-removeoption" ' . $rOnClickInline . '
					data-fieldname="' . $fName . '"
					title="' .
                htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.remove_selected')) .
                '">' . $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render() . '
				</a>';
        }

        // Thumbnails
        $imagesOnly = false;
        if ($params['thumbnails'] && $params['allowed']) {
            // In case we have thumbnails, check if only images are allowed.
            // In this case, render them below the field, instead of to the right
            $allowedExtensionList = $params['allowed'];
            $imageExtensionList = GeneralUtility::trimExplode(
                ',',
                strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']),
                true
            );
            $imagesOnly = true;
            foreach ($allowedExtensionList as $allowedExtension) {
                if (!in_array($allowedExtension, $imageExtensionList)) {
                    $imagesOnly = false;
                    break;
                }
            }
        }
        $thumbnails = '';
        if (is_array($params['thumbnails']) && !empty($params['thumbnails'])) {
            if ($imagesOnly) {
                $thumbnails .= '<ul class="list-inline">';
                foreach ($params['thumbnails'] as $thumbnail) {
                    $thumbnails .= '<li><span class="thumbnail">' . $thumbnail['image'] . '</span></li>';
                }
                $thumbnails .= '</ul>';
            } else {
                $thumbnails .= '<div class="table-fit"><table class="table table-white"><tbody>';
                foreach ($params['thumbnails'] as $thumbnail) {
                    $thumbnails .= '
						<tr>
							<td class="col-icon">
								' . ($config['internal_type'] === 'db' ?
                            BackendUtility::wrapClickMenuOnIcon(
                                $thumbnail['image'],
                                $thumbnail['table'],
                                $thumbnail['uid'],
                                1,
                                '',
                                '+copy,info,edit,view'
                            ) :
                            $thumbnail['image']) . '
							</td>
							<td class="col-title">
								' . ($config['internal_type'] === 'db' ?
                            BackendUtility::wrapClickMenuOnIcon(
                                $thumbnail['name'],
                                $thumbnail['table'],
                                $thumbnail['uid'],
                                1,
                                '',
                                '+copy,info,edit,view'
                            ) :
                            $thumbnail['name']) . '
								' . (
                                    $config['internal_type'] === 'db' ?
                                    ' <span class="text-muted">[' . $thumbnail['uid'] . ']</span>' :
                                    ''
                                ) . '
							</td>
						</tr>
						';
                }
                $thumbnails .= '</tbody></table></div>';
            }
        }

        // Allowed Tables
        $allowedTables = '';
        if (is_array($params['allowedTables']) && !empty($params['allowedTables'])) {
            $allowedTables .= '<div class="help-block">';
            foreach ($params['allowedTables'] as $key => $item) {
                if (is_array($item)) {
                    if (empty($params['readOnly'])) {
                        $onClick = 'onClick="' . htmlspecialchars($item['onClick']) . '"';
                        $allowedTables .= '<a href="#" ' . $onClick . ' class="btn btn-default">' .
                            $item['icon'] . ' ' . htmlspecialchars($item['name']) .
                            '</a> ';
                    } else {
                        $allowedTables .= '<span>' . htmlspecialchars($item['name']) . '</span> ';
                    }
                } elseif ($key === 'name') {
                    $allowedTables .= '<span>' . htmlspecialchars($item) . '</span> ';
                }
            }
            $allowedTables .= '</div>';
        }
        // Allowed
        $allowedList = '';
        if (is_array($params['allowed']) && !empty($params['allowed'])) {
            foreach ($params['allowed'] as $item) {
                $allowedList .= '<span class="label label-success">' . strtoupper($item) . '</span> ';
            }
        }
        // Disallowed
        $disallowedList = '';
        if (is_array($params['disallowed']) && !empty($params['disallowed'])) {
            foreach ($params['disallowed'] as $item) {
                $disallowedList .= '<span class="label label-danger">' . strtoupper($item) . '</span> ';
            }
        }
        // Rightbox
        $rightbox = ($params['rightbox'] ?: '');

        // Output
        $str = '
			' . ($params['headers']['selector'] ? '<label>' . $params['headers']['selector'] . '</label>' : '') . '
			<div class="form-wizards-wrap form-wizards-aside">
				<div class="form-wizards-element">
					' . $selector . '
					' . (!$params['noList'] && !empty($allowedTables) ? $allowedTables : '') . '
					' . (!$params['noList'] && (!empty($allowedList) || !empty($disallowedList))
                ? '<div class="help-block">' . $allowedList . $disallowedList . ' </div>'
                : '') . '
				</div>
				' . (!empty($icons['L']) ? '<div class="form-wizards-items"><div class="btn-group-vertical">' .
                implode('', $icons['L']) . '</div></div>' : '') . '
				' . (!empty($icons['R']) ? '<div class="form-wizards-items"><div class="btn-group-vertical">' .
                implode('', $icons['R']) . '</div></div>' : '') . '
			</div>
			';
        if ($rightbox) {
            $str = '
				<div class="form-multigroup-wrap t3js-formengine-field-group">
					<div class="form-multigroup-item form-multigroup-element">' . $str . '</div>
					<div class="form-multigroup-item form-multigroup-element">
						' . ($params['headers']['items'] ? '<label>' . $params['headers']['items'] . '</label>' : '') .
                '
						' . ($params['headers']['selectorbox'] ? '<div class="form-multigroup-item-wizard">' .
                $params['headers']['selectorbox'] . '</div>' : '') . '
						' . $rightbox . '
					</div>
				</div>
				';
        }
        $str .= $thumbnails;

        // Creating the hidden field which contains the actual value as a comma list.
        $str .= '<input type="hidden" name="' . $fName . '" value="' .
            htmlspecialchars(implode(',', $uidList)) . '" />';
        return $str;
    }

    /**
     * Returns array of elements from clipboard to insert into GROUP element box.
     *
     * @param string $allowed Allowed elements, Eg "pages,tt_content", "gif,jpg,jpeg,png
     * @param string $mode Mode of relations: "db" or "file
     * @return array Array of elements in values (keys are insignificant), if none found, empty array.
     */
    protected function getClipboardElements($allowed, $mode)
    {
        if (!is_object($this->clipboard)) {
            $this->clipboard = GeneralUtility::makeInstance(Clipboard::class);
            $this->clipboard->initializeClipboard();
        }

        $output = array();
        switch ($mode) {
            case 'file_reference':

            case 'file':
                $elFromTable = $this->clipboard->elFromTable('_FILE');
                $allowedExts = GeneralUtility::trimExplode(',', $allowed, true);
                // If there are a set of allowed extensions, filter the content:
                if ($allowedExts) {
                    foreach ($elFromTable as $elValue) {
                        $pI = pathinfo($elValue);
                        $ext = strtolower($pI['extension']);
                        if (in_array($ext, $allowedExts)) {
                            $output[] = $elValue;
                        }
                    }
                } else {
                    // If all is allowed, insert all: (This does NOT respect any disallowed extensions,
                    // but those will be filtered away by the backend TCEmain)
                    $output = $elFromTable;
                }
                break;
            case 'db':
                $allowedTables = GeneralUtility::trimExplode(',', $allowed, true);
                // All tables allowed for relation:
                if (trim($allowedTables[0]) === '*') {
                    $output = $this->clipboard->elFromTable('');
                } else {
                    // Only some tables, filter them:
                    foreach ($allowedTables as $tablename) {
                        $elFromTable = $this->clipboard->elFromTable($tablename);
                        $output = array_merge($output, $elFromTable);
                    }
                }
                $output = array_keys($output);
                break;
        }

        return $output;
    }
}

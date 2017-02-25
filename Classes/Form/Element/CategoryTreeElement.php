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
use TYPO3\CMS\Backend\Form\DatabaseFileIconsHookInterface;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

class CategoryTreeElement extends AbstractFormElement implements LinkParameterProviderInterface
{
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
        $table = $this->data['tableName'];
        $field = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];

        $html = '';
        $disabled = '';
        if ($config['readOnly']) {
            $disabled = ' disabled="disabled"';
        }
        // Setting this hidden field (as a flag that JavaScript can read out)
        if (!$disabled) {
            $html .= '<input type="hidden" data-formengine-input-name="'
                . htmlspecialchars($parameterArray['itemFormElName'])
                . '" value="' . ($config['multiple'] ? 1 : 0) . '" />';
        }
        // Set max and min items:
        $maxitems = max($config['maxitems'], 0);
        if (!$maxitems) {
            $maxitems = 100000;
        }
        // Get the array with selected items:
        $this->items = $itemsArray = $parameterArray['itemFormElValue'] ?: [];

        // Perform modification of the selected items array:
        foreach ($itemsArray as $itemNumber => $itemValue) {
            $itemArray = [0 => $itemValue['value']];

            if (isset($parameterArray['fieldTSConfig']['altIcons.'][$itemValue['uid']])) {
                $itemArray[2] = $parameterArray['fieldTSConfig']['altIcons.'][$itemValue['uid']];
            }

            $itemsArray[$itemNumber] = implode('|', $itemArray);
        }

        // size must be at least two, as there are always maxitems > 1 (see parent function)
        if (isset($config['size'])) {
            $size = (int)$config['size'];
        } else {
            $size = 2;
        }
        $size = $config['autoSizeMax']
            ? min(max(count($itemsArray) + 1, max($size, 1)), $config['autoSizeMax']) :
            $size;

        $itemsToSelect = [];
        $filterTextfield = [];
        $filterSelectbox = '';
        if (!$disabled) {
            $backendUser = $this->getBackendUserAuthentication();
            $height = ($size * 18 + 14) - ($config['enableMultiSelectFilterTextfield'] ? 30 : 0);

            // Put together the selector box:
            $selector_itemListStyle = ' style="height: ' . $height . 'px; overflow-y: scroll; ' . (
                isset($config['itemListStyle']) ?
                htmlspecialchars($config['itemListStyle']) :
                ''
            ) . '"';

            /** @var CategoryTreeElementCategoryTreeView $categoryTree */
            $categoryTree = GeneralUtility::makeInstance(CategoryTreeElementCategoryTreeView::class);
            $categoryTree->setLinkParameterProvider($this);
            $categoryTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
            $categoryTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
            $categoryTree->addField('navtitle');
            $tree = $categoryTree->getBrowsableTree();

            $itemsToSelect[] = '<div data-relatedfieldname="'
                . htmlspecialchars($parameterArray['itemFormElName']) . '" '
                . 'data-exclusivevalues="' . htmlspecialchars($config['exclusiveKeys']) . '" '
                . 'id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '" '
                . 'data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" '
                . 'class="form-control t3js-commerce-categorytree-itemstoselect" '
                . 'data-fieldchanged-values="{tableName: \'' . htmlspecialchars($table) . '\', uid: '
                . $this->data['vanillaUid'] . ', fieldName: \'' . $field . '\', element: \''
                . $parameterArray['itemFormElName'] . '\'}" '
                . $this->getValidationDataAsJsonString($config)
                . $selector_itemListStyle
                . '>';
            $itemsToSelect[] = $tree;
            $itemsToSelect[] = '</div>';

            // enable filter functionality via a text field
            if ($config['enableMultiSelectFilterTextfield']) {
                $filterTextfield[] = '<span class="input-group input-group-sm">';
                $filterTextfield[] =    '<span class="input-group-addon">';
                $filterTextfield[] =        '<span class="fa fa-filter"></span>';
                $filterTextfield[] =    '</span>';
                $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control">';
                $filterTextfield[] = '</span>';
            }
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
            'dontShowMoveIcons' => $maxitems <= 1,
            'maxitems' => $maxitems,
            'info' => '',
            'headers' => [
                'selector' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.selected'),
                'items' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.items'),
                'selectorbox' => $selectBoxFilterContents,
            ],
            'noBrowser' => 1,
            'rightbox' => implode(LF, $itemsToSelect),
            'readOnly' => $disabled
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

        $resultArray = $this->initializeResultArray();

        // Wizards:
        if (!$disabled) {
            $html = '<div class="commerce-categorytree-element">' . $this->renderWizards(
                [$html],
                $config['wizards'],
                $table,
                $this->data['databaseRow'],
                $field,
                $parameterArray,
                $parameterArray['itemFormElName'],
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            ) . '</div>';

            $resultArray['requireJsModules'][] = 'TYPO3/CMS/Commerce/FormElementCategoryTree';
        }

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
        $disabled = '';
        if ($params['readOnly']) {
            $disabled = ' disabled="disabled"';
        }
        // INIT
        $uidList = array();
        $opt = array();
        $itemArrayC = 0;
        // Creating <option> elements:
        if (is_array($itemArray)) {
            $itemArrayC = count($itemArray);
            switch ($mode) {
                case 'db':
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
                    break;
                case 'file_reference':

                case 'file':
                    foreach ($itemArray as $item) {
                        $itemParts = explode('|', $item);
                        $uidList[] = ($pUid = ($pTitle = $itemParts[0]));
                        $title = htmlspecialchars(rawurldecode($itemParts[1]));
                        $opt[] = '<option value="' . htmlspecialchars(rawurldecode($itemParts[0])) . '" title="' .
                            $title . '">' . $title . '</option>';
                    }
                    break;
                case 'folder':
                    foreach ($itemArray as $pp) {
                        $pParts = explode('|', $pp);
                        $uidList[] = ($pUid = ($pTitle = $pParts[0]));
                        $title = htmlspecialchars(rawurldecode($pParts[0]));
                        $opt[] = '<option value="' . htmlspecialchars(rawurldecode($pParts[0])) . '" title="' .
                            $title . '">' . $title . '</option>';
                    }
                    break;
                default:
                    foreach ($itemArray as $pp) {
                        $pParts = explode('|', $pp, 2);
                        $uidList[] = ($pUid = $pParts[0]);
                        $pTitle = $pParts[1];
                        $title = htmlspecialchars(rawurldecode($pTitle));
                        $opt[] = '<option value="' . htmlspecialchars(rawurldecode($pUid)) . '" title="' .
                            $title . '">' . $title . '</option>';
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
                $this->getValidationDataAsJsonString($config) . $onFocus . $params['style'] . $disabled . '>' .
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
                        $rOnClickInline = 'inline.revertUnique(' . GeneralUtility::quoteJSvalue($objectPrefix) .
                            ',null,' . GeneralUtility::quoteJSvalue($uid) . ');';
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
                $aOnClick = 'setFormValueOpenBrowser(' . GeneralUtility::quoteJSvalue($elementBrowserType) . ',' .
                    GeneralUtility::quoteJSvalue(($fName . '|||' . $elementBrowserAllowed . '|' . $aOnClickInline)) .
                    '); return false;';
                $icons['R'][] = '
					<a href="#"
						onclick="' . htmlspecialchars($aOnClick) . '"
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
                $aOnClick .= 'return false;';
                $icons['R'][] = '
					<a href="#"
						onclick="' . htmlspecialchars($aOnClick) . '"
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
					class="btn btn-default t3-btn-removeoption"
					onClick="' . $rOnClickInline . '"
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
                if (!ArrayUtility::inArray($imageExtensionList, $allowedExtension)) {
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
                        $allowedTables .= '<a href="#" onClick="' . htmlspecialchars($item['onClick']) .
                            '" class="btn btn-default">' . $item['icon'] . ' ' . htmlspecialchars($item['name']) .
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

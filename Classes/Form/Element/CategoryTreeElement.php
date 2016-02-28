<?php
namespace CommerceTeam\Commerce\Form\Element;

use CommerceTeam\Commerce\Tree\View\CategoryTreeElementCategoryTreeView;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * Render side by side element.
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
            $itemArray = array(
                0 => $itemValue['value'],
            );

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
                . $this->getValidationDataAsDataAttribute($config)
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
        $params = array(
            'size' => $size,
            'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
            'style' => isset($config['selectedListStyle'])
                ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
                : '',
            'dontShowMoveIcons' => $maxitems <= 1,
            'maxitems' => $maxitems,
            'info' => '',
            'headers' => array(
                'selector' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.selected'),
                'items' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.items'),
                'selectorbox' => $selectBoxFilterContents,
            ),
            'noBrowser' => 1,
            'rightbox' => implode(LF, $itemsToSelect),
            'readOnly' => $disabled
        );
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
            $html = $this->renderWizards(
                array($html),
                $config['wizards'],
                $table,
                $this->data['databaseRow'],
                $field,
                $parameterArray,
                $parameterArray['itemFormElName'],
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            );

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
        return !empty($this->items) && isset($this->items[(int)$values['pid']]);
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
}

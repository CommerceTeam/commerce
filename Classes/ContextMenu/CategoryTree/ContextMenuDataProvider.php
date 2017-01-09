<?php
namespace CommerceTeam\Commerce\ContextMenu\CategoryTree;

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

use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\ContextMenu\ContextMenuAction;
use TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection;

/**
 * Context Menu Data Provider for the Page Tree
 */
class ContextMenuDataProvider
{
    /**
     * List of actions that are generally disabled
     *
     * @var array
     */
    protected $disableItems = [];

    /**
     * Context Menu Type (e.g. table.pages, table.tt_content)
     *
     * @var string
     */
    protected $contextMenuType = '';

    /**
     * Old Context Menu Options (access mapping)
     *
     * Note: Only option with different namings are mapped!
     *
     * @var array
     */
    protected $legacyContextMenuMapping = array(
        'hide' => 'disable',
        'paste' => 'pasteInto,pasteAfter',
        'mount_as_treeroot' => 'mountAsTreeroot'
    );

    /**
     * Returns the context menu type
     *
     * @return string
     */
    public function getContextMenuType()
    {
        return $this->contextMenuType;
    }

    /**
     * Sets the context menu type
     *
     * @param string $contextMenuType
     * @return void
     */
    public function setContextMenuType($contextMenuType)
    {
        $this->contextMenuType = $contextMenuType;
    }

    /**
     * Fetches the items that should be disabled from the context menu
     *
     * @return array
     */
    protected function getDisableActions()
    {
        $tsConfig = $this->getBackendUser()->getTSConfig(
            'options.contextMenu.' . $this->getContextMenuType() . '.disableItems'
        );
        $disableItems = array();
        if (trim($tsConfig['value']) !== '') {
            $disableItems = GeneralUtility::trimExplode(',', $tsConfig['value']);
        }
        $tsConfig = $this->getBackendUser()->getTSConfig('options.contextMenu.categoryTree.disableItems');
        $oldDisableItems = array();
        if (trim($tsConfig['value']) !== '') {
            $oldDisableItems = GeneralUtility::trimExplode(',', $tsConfig['value']);
        }
        $additionalItems = array();
        foreach ($oldDisableItems as $item) {
            if (!isset($this->legacyContextMenuMapping[$item])) {
                $additionalItems[] = $item;
                continue;
            }
            if (strpos($this->legacyContextMenuMapping[$item], ',')) {
                $actions = GeneralUtility::trimExplode(',', $this->legacyContextMenuMapping[$item]);
                $additionalItems = array_merge($additionalItems, $actions);
            } else {
                $additionalItems[] = $item;
            }
        }
        return array_merge($disableItems, $additionalItems);
    }

    /**
     * Returns the actions for the node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @return \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection
     */
    public function getActionsForNode(\TYPO3\CMS\Backend\Tree\TreeNode $node)
    {
        $this->disableItems = $this->getDisableActions();
        $configuration = $this->getConfiguration();
        $contextMenuActions = array();
        if (is_array($configuration)) {
            $contextMenuActions = $this->getNextContextMenuLevel($configuration, $node);
        }
        return $contextMenuActions;
    }

    /**
     * Returns the configuration of the specified context menu type
     *
     * @return array
     */
    protected function getConfiguration()
    {
        $contextMenuActions = $this->getBackendUser()->getTSConfig(
            'options.contextMenu.' . $this->contextMenuType . '.items'
        );
        return $contextMenuActions['properties'];
    }

    /**
     * Evaluates a given display condition and returns TRUE if the condition matches
     *
     * Examples:
     * getContextInfo|inCutMode:1 || isInCopyMode:1
     * isLeafNode:1
     * isLeafNode:1 && isInCutMode:1
     *
     * @param TreeNode $node
     * @param string $displayCondition
     * @return bool
     */
    protected function evaluateDisplayCondition(TreeNode $node, $displayCondition)
    {
        if ($displayCondition === '') {
            return true;
        }
        // Parse condition string
        $conditions = [];
        preg_match_all('/(.+?)(>=|<=|!=|=|>|<)(.+?)(\\|\\||&&|$)/is', $displayCondition, $conditions);
        $lastResult = false;
        $chainType = '';
        $amountOfConditions = count($conditions[0]);
        for ($i = 0; $i < $amountOfConditions; ++$i) {
            // Check method for existence
            $method = trim($conditions[1][$i]);
            list($method, $index) = explode('|', $method);
            if (!method_exists($node, $method)) {
                continue;
            }
            // Fetch compare value
            $returnValue = call_user_func([$node, $method]);
            if (is_array($returnValue)) {
                $returnValue = $returnValue[$index];
            }
            // Compare fetched and expected values
            $operator = trim($conditions[2][$i]);
            $expected = trim($conditions[3][$i]);
            if ($operator === '=') {
                $returnValue = $returnValue == $expected;
            } elseif ($operator === '>') {
                $returnValue = $returnValue > $expected;
            } elseif ($operator === '<') {
                $returnValue = $returnValue < $expected;
            } elseif ($operator === '>=') {
                $returnValue = $returnValue >= $expected;
            } elseif ($operator === '<=') {
                $returnValue = $returnValue <= $expected;
            } elseif ($operator === '!=') {
                $returnValue = $returnValue != $expected;
            } else {
                $returnValue = false;
                $lastResult = false;
            }
            // Chain last result and the current if requested
            if ($chainType === '||') {
                $lastResult = $lastResult || $returnValue;
            } elseif ($chainType === '&&') {
                $lastResult = $lastResult && $returnValue;
            } else {
                $lastResult = $returnValue;
            }
            // Save chain type for the next condition
            $chainType = trim($conditions[4][$i]);
        }
        return $lastResult;
    }

    /**
     * Returns the next context menu level
     *
     * @param array $actions
     * @param TreeNode $node
     * @param int $level
     * @return ContextMenuActionCollection
     */
    protected function getNextContextMenuLevel(array $actions, TreeNode $node, $level = 0)
    {
        /** @var $actionCollection ContextMenuActionCollection */
        $actionCollection = GeneralUtility::makeInstance(ContextMenuActionCollection::class);
        /** @var $iconFactory IconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if ($level > 5) {
            return $actionCollection;
        }
        $type = '';
        foreach ($actions as $index => $actionConfiguration) {
            if (substr($index, -1) !== '.') {
                $type = $actionConfiguration;
                if ($type !== 'DIVIDER') {
                    continue;
                }
            }
            if (!in_array($type, ['DIVIDER', 'SUBMENU', 'ITEM'])) {
                continue;
            }
            /** @var $action ContextMenuAction */
            $action = GeneralUtility::makeInstance(ContextMenuAction::class);
            $action->setId($index);
            if ($type === 'DIVIDER') {
                $action->setType('divider');
            } else {
                if (in_array($actionConfiguration['name'], $this->disableItems)
                    || isset($actionConfiguration['displayCondition'])
                    && trim($actionConfiguration['displayCondition']) !== ''
                    && !$this->evaluateDisplayCondition($node, $actionConfiguration['displayCondition'])) {
                    unset($action);
                    continue;
                }
                $label = $this->getLanguageService()->sL($actionConfiguration['label'], true);
                if ($type === 'SUBMENU') {
                    $action->setType('submenu');
                    $action->setChildActions($this->getNextContextMenuLevel($actionConfiguration, $node, $level + 1));
                } else {
                    $action->setType('action');
                    $action->setCallbackAction($actionConfiguration['callbackAction']);
                    if (is_array($actionConfiguration['customAttributes.'])) {
                        if (!empty($actionConfiguration['customAttributes.']['contentUrl'])) {
                            $actionConfiguration['customAttributes.']['contentUrl'] =
                                $this->replaceModuleTokenInContentUrl(
                                    $actionConfiguration['customAttributes.']['contentUrl']
                                );
                        }
                        $action->setCustomAttributes($actionConfiguration['customAttributes.']);
                    }
                }
                $action->setLabel($label);
                if (!isset($actionConfiguration['iconName'])) {
                    $actionConfiguration['iconName'] = 'miscellaneous-placeholder';
                }
                $action->setIcon($iconFactory->getIcon($actionConfiguration['iconName'], Icon::SIZE_SMALL)->render());
            }
            $actionCollection->offsetSet($level . (int)$index, $action);
            $actionCollection->ksort();
        }
        return $actionCollection;
    }

    /**
     * Add the CSRF token to the module URL if a "M" parameter is found
     *
     * @param string $contentUrl
     * @return string
     */
    protected function replaceModuleTokenInContentUrl($contentUrl)
    {
        $parsedUrl = parse_url($contentUrl);
        parse_str($parsedUrl['query'], $urlParameters);
        if (isset($urlParameters['M'])) {
            $moduleName = $urlParameters['M'];
            unset($urlParameters['M']);
            $contentUrl = BackendUtility::getModuleUrl($moduleName, $urlParameters);
        }
        return $contentUrl;
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

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

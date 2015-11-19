<?php
namespace CommerceTeam\Commerce\ViewHelpers;

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

use TYPO3\CMS\Backend\Form\Element\UserElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Holds the TCE Functions.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\TceFunc
 *
 * @author 2008-2011 Erik Frister <typo3@marketing-factory.de>
 */
class TceFunc
{
    /**
     * Form engine.
     *
     * @var UserElement
     */
    protected $userElement;

    /**
     * This will render a selector box element for selecting elements
     * of (category) trees.
     * Depending on the tree it display full trees or root elements only.
     *
     * @param array $parameter An array with additional configuration options.
     * @param UserElement $userElement TCEForms object reference
     *
     * @return string The HTML code for the TCEform field
     */
    public function getSingleFieldSelectCategories(array $parameter, UserElement $userElement)
    {
        $this->userElement = $userElement;
        $languageService = $this->getLanguageService();

        $table = $parameter['table'];
        $field = $parameter['field'];
        $row = $parameter['row'];
        $config = $parameter['fieldConf']['config'];

        $disabled = '';
        if ($config['readOnly']) {
            $disabled = ' disabled="disabled"';
        }

        $permissions = $this->getPermissions($table);

        /**
         * Category tree.
         *
         * @var \CommerceTeam\Commerce\Tree\CategoryTree $categoryTree
         */
        $categoryTree = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\CategoryTree');
        // disabled clickmenu
        $categoryTree->noClickmenu();
        // set the minimum permissions
        $categoryTree->setMinCategoryPerms($permissions);

        if ($config['allowProducts']) {
            $categoryTree->setBare(false);
        }

        if ($config['substituteRealValues']) {
            // @todo fix me
            $categoryTree->substituteRealValues();
        }

        /*
         * Disallows clicks on certain leafs
         * Values is a comma-separated list of leaf names
         * (e.g. \CommerceTeam\Commerce\Tree\Leaf\Category)
         */
        $categoryTree->disallowClick($config['disallowClick']);

        $categoryTree->init();

        /**
         * Browse tree.
         *
         * @var \CommerceTeam\Commerce\ViewHelpers\TreelibTceforms $renderBrowseTrees
         */
        $renderBrowseTrees = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\ViewHelpers\\TreelibTceforms');
        $renderBrowseTrees->init($parameter);

        // Render the tree
        $renderBrowseTrees->renderBrowsableMountTrees($categoryTree);

        $thumbnails = '';
        if (!$disabled) {
            // tree frame <div>
            $thumbnails = $renderBrowseTrees->renderDivBox();
        }

        $itemArray = $this->getSelectedProcessedItems($table, $row, $parameter, $renderBrowseTrees);

        // process selected values
        // Creating the label for the "No Matching Value" entry.
        $noMatchingValueLabel = isset($parameter['fieldTSConfig']['noMatchingValue_label']) ?
            $languageService->sL($parameter['fieldTSConfig']['noMatchingValue_label']) :
            '[ ' . $languageService->getLL('l_noMatchingValue') . ' ]';
        $noMatchingValueLabel = @sprintf($noMatchingValueLabel, $parameter['itemFormElValue']);

        // Possibly remove some items:
        $removeItems = GeneralUtility::trimExplode(',', $parameter['fieldTSConfig']['removeItems'], true);
        foreach ($itemArray as $tk => $tv) {
            $tvP = explode('|', $tv, 2);
            if (in_array($tvP[0], $removeItems) && !$parameter['fieldTSConfig']['disableNoMatchingValueElement']) {
                $tvP[1] = rawurlencode($noMatchingValueLabel);
            } elseif (isset($parameter['fieldTSConfig']['altLabels.'][$tvP[0]])) {
                $tvP[1] = rawurlencode($languageService->sL($parameter['fieldTSConfig']['altLabels.'][$tvP[0]]));
            }
            $itemArray[$tk] = implode('|', $tvP);
        }

        // Rendering and output
        $minitems = max($config['minitems'], 0);
        $maxitems = max($config['maxitems'], 0);
        if (!$maxitems) {
            $maxitems = 100000;
        }

        $this->userElement->requiredElements[$parameter['itemFormElName']] = array(
            $minitems,
            $maxitems,
            'imgName' => $table . '_' . $row['uid'] . '_' . $field
        );

        $item = '<input type="hidden" name="' . $parameter['itemFormElName'] . '_mul" value="' .
            ($config['multiple'] ? 1 : 0) . '"' . $disabled . ' />';

        $params = array(
            'size' => $config['size'],
            'autoSizeMax' => \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
            'style' => ' style="width:200px;"',
            'dontShowMoveIcons' => ($maxitems <= 1),
            'maxitems' => $maxitems,
            'info' => '',
            'headers' => array(
                'selector' => $languageService->getLL('l_selected') . ':<br />',
                'items' => ($disabled ? '' : $languageService->getLL('l_items') . ':<br />'),
            ),
            'noBrowser' => true,
            'readOnly' => $disabled,
            'thumbnails' => $thumbnails,
        );

        $item .= '
		<style type="text/css">
		.x-tree-root-ct ul {
			padding: 0 0 0 19px;
			margin: 0;
		}

		.x-tree-root-ct {
			padding-left: 0;
		}

		tr:hover .x-tree-root-ct a {
			text-decoration: none;
		}

		.x-tree-root-ct li {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		.x-tree-root-ct ul li.expanded ul {
			background: url("' . $this->userElement->backPath
            . 'sysext/t3skin/icons/gfx/ol/line.gif") repeat-y scroll left top transparent;
		}

		.x-tree-root-ct ul li.expanded.last ul {
			background: none;
		}

		.x-tree-root-ct li {
			clear: left;
			margin-bottom: 0;
		}
		</style>
		';

        $item .= $this->userElement->dbFileIcons(
            $parameter['itemFormElName'],
            $config['internal_type'],
            $config['allowed'],
            $itemArray,
            '',
            $params,
            $parameter['onFocus']
        );

        // Wizards:
        if (!$disabled) {
            $specConf = \TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParts(
                $parameter['extra'],
                $parameter['fieldConf']['defaultExtras']
            );

            $altItem = '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
                htmlspecialchars($parameter['itemFormElValue']) . '" />';
            $item = $this->userElement->renderWizards(
                array($item, $altItem),
                $config['wizards'],
                $table,
                $row,
                $field,
                $parameter,
                $parameter['itemFormElName'],
                $specConf
            );
        }

        return $item;
    }

    /**
     * @param string $table
     *
     * @return string
     */
    protected function getPermissions($table)
    {
        // read the permissions we are restricting the tree to, depending on the table
        $permissions = 'show';

        switch ($table) {
            case 'tx_commerce_categories':
                $permissions = 'new';
                break;

            case 'tx_commerce_products':
                $permissions = 'editcontent';
                break;

            case 'tt_content':
                // fall through
            case 'be_groups':
                // fall through
            case 'be_users':
                $permissions = 'show';
                break;

            default:
        }

        return $permissions;
    }

    /**
     * get selected processed items - depending on the table we want to insert
     * into (tx_commerce_products, tx_commerce_categories, be_users)
     * if row['uid'] is defined and is an int we do display an existing record
     * otherwise it's a new record, so get default values
     *
     * @param string $table
     * @param array $row
     * @param array $parameter
     * @param \CommerceTeam\Commerce\ViewHelpers\TreelibTceforms $renderBrowseTrees
     *
     * @return array
     */
    protected function getSelectedProcessedItems($table, $row, $parameter, $renderBrowseTrees)
    {
        $itemArray = array();

        if ((int) $row['uid']) {
            // existing Record
            switch ($table) {
                case 'tx_commerce_categories':
                    $itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreePCategory(
                        $categoryTree,
                        $row['uid']
                    );
                    break;

                case 'tx_commerce_products':
                    $itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeProduct(
                        $categoryTree,
                        $row['uid']
                    );
                    break;

                case 'be_users':
                    $itemArray = $renderBrowseTrees->processItemArrayForBrowseableTree(
                        $categoryTree,
                        $row['uid']
                    );
                    break;

                case 'be_groups':
                    $itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeGroups(
                        $categoryTree,
                        $row['uid']
                    );
                    break;

                case 'tt_content':
                    // Perform modification of the selected items array:
                    $itemArray = GeneralUtility::trimExplode(',', $parameter['itemFormElValue'], 1);
                    $itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeCategory(
                        $categoryTree,
                        $itemArray[0]
                    );
                    break;

                default:
                    $itemArray = $renderBrowseTrees->processItemArrayForBrowseableTreeDefault(
                        $parameter['itemFormElValue']
                    );
            }
        } else {
            // New record
            $defaultValues = GeneralUtility::_GP('defVals');
            switch ($table) {
                case 'tx_commerce_categories':
                    /**
                     * Category.
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                     */
                    $category = GeneralUtility::makeInstance(
                        'CommerceTeam\\Commerce\\Domain\\Model\\Category',
                        $defaultValues['tx_commerce_categories']['parent_category']
                    );
                    $category->loadData();
                    if ($category->getUid() > 0) {
                        $itemArray = array($category->getUid() . '|' . $category->getTitle());
                    }
                    break;

                case 'tx_commerce_products':
                    /**
                     * Category.
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                     */
                    $category = GeneralUtility::makeInstance(
                        'CommerceTeam\\Commerce\\Domain\\Model\\Category',
                        $defaultValues['tx_commerce_products']['categories']
                    );
                    $category->loadData();
                    if ($category->getUid() > 0) {
                        $itemArray = array($category->getUid() . '|' . $category->getTitle());
                    }
                    break;

                default:
            }
        }

        return $itemArray;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}

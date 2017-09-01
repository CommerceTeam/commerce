<?php
namespace CommerceTeam\Commerce\Hooks;

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

/**
 * Hook to render javascript needed to refresh category tree
 *
 * Class \CommerceTeam\Commerce\Hook\CommandMapHooks
 */
class UpdateSignalHook
{
    /**
     * @param array $result
     */
    public function updateCategoryTree(array &$result)
    {
        $result['JScode'] = '
            if (top && top.TYPO3.Backend.NavigationContainer.CategoryTree) {
                top.TYPO3.Backend.NavigationContainer.CategoryTree.refreshTree();
            }
            ';
    }

    /**
     * @param array $result
     */
    public function updateOrderTree(array &$result)
    {
        $result['JScode'] = '
            if (top && top.TYPO3.Backend.NavigationContainer.OrderTree) {
                top.TYPO3.Backend.NavigationContainer.OrderTree.refreshTree();
            }
            ';
    }
}

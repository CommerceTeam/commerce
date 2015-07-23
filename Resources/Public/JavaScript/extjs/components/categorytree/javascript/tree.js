/**
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
Ext.namespace('TYPO3.Components.CatgoryTree');

/**
 * Callback method for the module menu
 *
 * @return {TYPO3.Components.PageTree.App}
 */
TYPO3.ModuleMenu.App.registerNavigationComponent('category-navframe', function () {
    TYPO3.Backend.NavigationContainer.CatgoryTree = new TYPO3.Components.PageTree.App();

    // compatibility code
    top.nav = TYPO3.Backend.NavigationContainer.CatgoryTree;
    top.nav_frame = TYPO3.Backend.NavigationContainer.CatgoryTree;
    top.content.nav_frame = TYPO3.Backend.NavigationContainer.CatgoryTree;

    return TYPO3.Backend.NavigationContainer.CatgoryTree;
});

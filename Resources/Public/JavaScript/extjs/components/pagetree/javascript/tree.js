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

/**
 * Callback method for the module menu
 *
 * @return {TYPO3.Components.PageTree.App}
 */
TYPO3.ModuleMenu.App.registerNavigationComponent('order-navframe', function () {
    TYPO3.Backend.NavigationContainer.OrderTree = new TYPO3.Components.PageTree.App();

    // compatibility code
    top.nav = TYPO3.Backend.NavigationContainer.OrderTree;
    top.nav_frame = TYPO3.Backend.NavigationContainer.OrderTree;
    top.content.nav_frame = TYPO3.Backend.NavigationContainer.OrderTree;

    return TYPO3.Backend.NavigationContainer.OrderTree;
});

/**
 * Callback method for the module menu
 *
 * @return {TYPO3.Components.PageTree.App}
 */
TYPO3.ModuleMenu.App.registerNavigationComponent('systemdata-navframe', function () {
    TYPO3.Backend.NavigationContainer.SystemdataTree = new TYPO3.Components.PageTree.App();

    // compatibility code
    top.nav = TYPO3.Backend.NavigationContainer.SystemdataTree;
    top.nav_frame = TYPO3.Backend.NavigationContainer.SystemdataTree;
    top.content.nav_frame = TYPO3.Backend.NavigationContainer.SystemdataTree;

    return TYPO3.Backend.NavigationContainer.SystemdataTree;
});
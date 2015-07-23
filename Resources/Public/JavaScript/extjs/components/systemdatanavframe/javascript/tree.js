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
Ext.namespace('TYPO3.Components.SystemdataNavframe');

/**
 * @class TYPO3.Components.SystemdataNavframe.App
 *
 * Systemdata tree main application that controls setups the components
 *
 * @namespace TYPO3.Components.SystemdataNavframe
 * @extends Ext.Panel
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
TYPO3.Components.SystemdataNavframe.App = Ext.extend(Ext.Panel, {
    /**
     * Panel id
     *
     * @type {String}
     */
    id: 'systemdata-navframe',

    /**
     * Border
     *
     * @type {Boolean}
     */
    border: false,

    /**
     * Layout Type
     *
     * @type {String}
     */
    layout: 'fit',

    /**
     * Active tree
     *
     * @type {TYPO3.Components.SystemdataNavframe.Tree}
     */
    activeTree: null,

    /**
     * Main SystemdataNavframe
     *
     * @type {TYPO3.Components.SystemdataNavframe.Tree}
     */
    mainTree: null,

    /**
     * Initializes the application
     *
     * Set's the necessary language labels, configuration options and sprite icons by an
     * external call and initializes the needed components.
     *
     * @return {void}
     */
    initComponent: function () {
        TYPO3.Components.SystemdataNavframe.DataProvider.loadResources(function (response) {
            TYPO3.Components.SystemdataNavframe.LLL = response['LLL'];
            TYPO3.Components.SystemdataNavframe.Configuration = response['Configuration'];
            TYPO3.Components.SystemdataNavframe.Sprites = response['Sprites'];

            this.mainTree = this.activeTree = new TYPO3.Components.SystemdataNavframe.Tree({
                id: this.id + '-tree',
                deletionDropZoneId: this.id + '-deletionDropZone',
                ddGroup: this.id,
                stateful: true,
                stateId: 'SystemdataNavframe' + TYPO3.Components.SystemdataNavframe.Configuration.temporaryMountPoint,
                stateEvents: [],
                autoScroll: true,
                autoHeight: false,
                plugins: new Ext.ux.state.TreePanel(),
                commandProvider: TYPO3.Components.SystemdataNavframe.Actions,
                contextMenuProvider: TYPO3.Components.SystemdataNavframe.ContextMenuDataProvider,
                treeDataProvider: TYPO3.Components.SystemdataNavframe.DataProvider,
                app: this,
                listeners: {
                    resize: {
                        fn: function () {
                            var treeContainer = Ext.getCmp(this.id + '-treeContainer');
                            Ext.getCmp(this.id + '-filteringTree').setSize(treeContainer.getSize());
                            treeContainer.doLayout();
                        },
                        scope: this,
                        buffer: 250
                    }
                }
            });

            var filteringTree = new TYPO3.Components.SystemdataNavframe.FilteringTree({
                id: this.id + '-filteringTree',
                deletionDropZoneId: this.id + '-deletionDropZone',
                ddGroup: this.id,
                autoScroll: true,
                autoHeight: false,
                commandProvider: TYPO3.Components.SystemdataNavframe.Actions,
                contextMenuProvider: TYPO3.Components.SystemdataNavframe.ContextMenuDataProvider,
                treeDataProvider: TYPO3.Components.SystemdataNavframe.DataProvider,
                app: this
            }).hide();

            var topPanel = new TYPO3.Components.SystemdataNavframe.TopPanel({
                dataProvider: TYPO3.Components.SystemdataNavframe.DataProvider,
                filteringTree: filteringTree,
                ddGroup: this.id,
                tree: this.mainTree,
                app: this
            });

            var deletionDropZone = new TYPO3.Components.SystemdataNavframe.DeletionDropZone({
                id: this.id + '-deletionDropZone',
                commandProvider: TYPO3.Components.SystemdataNavframe.Actions,
                ddGroup: this.id,
                app: this,
                region: 'south',
                height: 35
            });

            var topPanelItems = new Ext.Panel({
                id: this.id + '-topPanelItems',
                border: false,
                region: 'north',
                height: 49,
                items: [
                    topPanel, {
                        border: false,
                        id: this.id + '-indicatorBar'
                    }
                ]
            });

            this.add({
                layout: 'border',
                items: [
                    topPanelItems,
                    {
                        border: false,
                        id: this.id + '-treeContainer',
                        region: 'center',
                        layout: 'fit',
                        items: [this.mainTree, filteringTree]
                    },
                    deletionDropZone
                ]
            });

            if (TYPO3.Components.SystemdataNavframe.Configuration.temporaryMountPoint) {
                topPanelItems.on('afterrender', function () {
                    this.addTemporaryMountPointIndicator();
                }, this);
            }

            if (TYPO3.Components.SystemdataNavframe.Configuration.indicator !== '') {
                this.addIndicatorItems();
            }
            this.doLayout();

            this.ownerCt.on('resize', function () {
                this.doLayout();
            });
        }, this);

        TYPO3.Components.SystemdataNavframe.App.superclass.initComponent.apply(this, arguments);
    }
});

/**
 * Callback method for the module menu
 *
 * @return {TYPO3.Components.SystemdataNavframe.App}
 */
TYPO3.ModuleMenu.App.registerNavigationComponent('systemdata-navframe', function () {
    TYPO3.Backend.NavigationContainer.SystemdataNavframe = new TYPO3.Components.SystemdataNavframe.App();

    // compatibility code
    top.nav = TYPO3.Backend.NavigationContainer.SystemdataNavframe;
    top.nav_frame = TYPO3.Backend.NavigationContainer.SystemdataNavframe;
    top.content.nav_frame = TYPO3.Backend.NavigationContainer.SystemdataNavframe;

    return TYPO3.Backend.NavigationContainer.SystemdataNavframe;
});

// XTYPE Registration
Ext.reg('TYPO3.Components.SystemdataNavframe.App', TYPO3.Components.SystemdataNavframe.App);
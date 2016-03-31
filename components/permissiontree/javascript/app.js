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
Ext.namespace('TYPO3.Components.PermissionTree');

/**
 * @class TYPO3.Components.PermissionTree.App
 *
 * Page tree main application that controls setups the components
 *
 * @namespace TYPO3.Components.PermissionTree
 * @extends Ext.Panel
 */
TYPO3.Components.PermissionTree.App = Ext.extend(Ext.Panel, {
	/**
	 * Panel id
	 *
	 * @type {String}
	 */
	id: 'commerce-permissiontree',

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
	layout:'fit',

	/**
	 * Active tree
	 *
	 * @type {TYPO3.Components.PermissionTree.Tree}
	 */
	activeTree: null,

	/**
	 * Main PermissionTree
	 *
	 * @type {TYPO3.Components.PermissionTree.Tree}
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
	initComponent: function() {
		TYPO3.Components.PermissionTree.DataProvider.loadResources(function(response) {
			TYPO3.Components.PermissionTree.LLL = response['LLL'];
			TYPO3.Components.PermissionTree.Configuration = response['Configuration'];
			TYPO3.Components.PermissionTree.Sprites = response['Sprites'];
			TYPO3.Components.PermissionTree.Icons = response['Icons'];

			this.mainTree = this.activeTree = new TYPO3.Components.PermissionTree.Tree({
				id: this.id + '-tree',
				deletionDropZoneId: this.id + '-deletionDropZone',
				ddGroup: this.id,
				stateful: true,
				stateId: 'PermissionTree' + TYPO3.Components.PermissionTree.Configuration.temporaryMountPoint,
				stateEvents: [],
				autoScroll: true,
				autoHeight: false,
				plugins: new Ext.ux.state.TreePanel(),
				commandProvider: TYPO3.Components.PermissionTree.Actions,
				contextMenuProvider: TYPO3.Components.PermissionTree.ContextMenuDataProvider,
				treeDataProvider: TYPO3.Components.PermissionTree.DataProvider,
				app: this,
				listeners: {
					resize: {
						fn: function() {
							var treeContainer = Ext.getCmp(this.id + '-treeContainer');
							Ext.getCmp(this.id + '-filteringTree').setSize(treeContainer.getSize());
							treeContainer.doLayout();
						},
						scope: this,
						buffer: 250
					}
				}
			});

			var filteringTree = new TYPO3.Components.PermissionTree.FilteringTree({
				id: this.id + '-filteringTree',
				deletionDropZoneId: this.id + '-deletionDropZone',
				ddGroup: this.id,
				autoScroll: true,
				autoHeight: false,
				commandProvider: TYPO3.Components.PermissionTree.Actions,
				contextMenuProvider: TYPO3.Components.PermissionTree.ContextMenuDataProvider,
				treeDataProvider: TYPO3.Components.PermissionTree.DataProvider,
				app: this
			}).hide();

			var topPanel = new TYPO3.Components.PermissionTree.TopPanel({
				cls: this.id + '-toppanel',
				dataProvider: TYPO3.Components.PermissionTree.DataProvider,
				filteringTree: filteringTree,
				ddGroup: this.id,
				tree: this.mainTree,
				app: this
			});

			/*var deletionDropZone = new TYPO3.Components.PermissionTree.DeletionDropZone({
				id: this.id + '-deletionDropZone',
				commandProvider: TYPO3.Components.PermissionTree.Actions,
				ddGroup: this.id,
				app: this,
				region: 'south',
				height: 35
			});*/

			var topPanelItems = new Ext.Panel({
				id: this.id + '-topPanelItems',
				cls: this.id + '-toppanel-items',
				border: false,
				region: 'north',
				height: 65,
				items: [
					topPanel, {
						border: false,
						id: this.id + '-indicatorBar'
					}
				]
			});

			this.add({
				layout: 'border',
				border: false,
				items: [
					topPanelItems,
					{
						border: false,
						id: this.id + '-treeContainer',
						region: 'center',
						layout: 'fit',
						items: [this.mainTree, filteringTree]
					}//,
					//deletionDropZone
				]
			});

			if (TYPO3.Components.PermissionTree.Configuration.temporaryMountPoint) {
				topPanelItems.on('afterrender', function() {
					this.addTemporaryMountPointIndicator();
				}, this);
			}

			if (TYPO3.Components.PermissionTree.Configuration.indicator !== '') {
				this.addIndicatorItems();
			}
			this.doLayout();

			this.ownerCt.on('resize', function() {
				this.doLayout();
			});
		}, this);

		TYPO3.Components.PermissionTree.App.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Adds the default indicator items
	 *
	 * @return {void}
	 */
	addIndicatorItems: function() {
		this.addIndicator({
			border: false,
			id: this.id + '-indicatorBar-indicatorTitle',
			cls: this.id + '-indicatorBar-item',
			html: TYPO3.Components.PermissionTree.Configuration.indicator
		});
	},

	/**
	 * Adds the temporary mount point indicator item
	 *
	 * @return {void}
	 */
	addTemporaryMountPointIndicator: function() {
		this.temporaryMountPointInfoIndicator = this.addIndicator({
			border: false,
			id: this.id + '-indicatorBar-temporaryMountPoint',
			cls: this.id + '-indicatorBar-item',

			listeners: {
				afterrender: {
					fn: function() {
						var element = Ext.fly(this.id + '-indicatorBar-temporaryMountPoint-clear');
						var me = this;
						element.on('click', function() {
							top.TYPO3.Storage.Persistent.unset('pageTree_temporaryMountPoint').done(
								function() {
									TYPO3.Components.PermissionTree.Configuration.temporaryMountPoint = null;
									me.removeIndicator(me.temporaryMountPointInfoIndicator);
									me.getTree().refreshTree();
									me.getTree().stateId = 'PermissionTree';
								}
							);
						}, this);
					},
					scope: this
				}
			},
			html: '' +
				'<div class="alert alert-info">' +
					'<div class="media">' +
						'<div class="media-left">' +
							TYPO3.Components.PermissionTree.Icons.Info +
						'</div>' +
						'<div class="media-body">' +
							TYPO3.Components.PermissionTree.Configuration.temporaryMountPoint +
						'</div>' +
						'<div class="media-right">' +
							'<a href="#" id="' + this.id + '-indicatorBar-temporaryMountPoint-clear">' +
								TYPO3.Components.PermissionTree.Icons.Close +
							'</a>' +
						'</div>' +
					'</div>' +
				'</div>'
		});
	},

	/**
	 * Adds an indicator item
	 *
	 * @param {Object} component
	 * @return {void}
	 */
	addIndicator: function(component) {
		if (component.listeners && component.listeners.afterrender) {
			component.listeners.afterrender.fn = component.listeners.afterrender.fn.createSequence(
				this.afterTopPanelItemAdded, this
			);
		} else {
			if (component.listeners) {
				component.listeners = {}
			}

			component.listeners.afterrender = {
				scope: this,
				fn: this.afterTopPanelItemAdded
			}
		}

		var indicator = Ext.getCmp(this.id + '-indicatorBar').add(component);
		this.doLayout();

		return indicator;
	},

	/**
	 * Recalculates the top panel items height after an indicator was added
	 *
	 * @param {Ext.Component} component
	 * @return {void}
	 */
	afterTopPanelItemAdded: function(component) {
		var topPanelItems = Ext.getCmp(this.id + '-topPanelItems');
		topPanelItems.setHeight(topPanelItems.getHeight() + component.getHeight());
		this.doLayout();
	},

	/**
	 * Removes an indicator item from the indicator bar
	 *
	 * @param {Ext.Component} component
	 * @return {void}
	 */
	removeIndicator: function(component) {
		var topPanelItems = Ext.getCmp(this.id + '-topPanelItems');
		topPanelItems.setHeight(topPanelItems.getHeight() - component.getHeight());
		Ext.getCmp(this.id + '-indicatorBar').remove(component);
		this.doLayout();
	},

	/**
	 * Compatibility method that calls refreshTree()
	 *
	 * @return {void}
	 */
	refresh: function() {
		this.refreshTree();
	},

	/**
	 * Another compatibility method that calls refreshTree()
	 *
	 * @return {void}
	 */
	refresh_nav: function() {
		this.refreshTree();
	},

	/**
	 * Refreshes the tree and selects the node defined by fsMod.recentIds['commerce_permission']
	 *
	 * @return {void}
	 */
	refreshTree: function() {
		if (!isNaN(fsMod.recentIds['commerce_category']) && fsMod.recentIds['commerce_category'] !== '') {
			this.select(fsMod.recentIds['commerce_category'], true);
		}

		TYPO3.Components.PermissionTree.DataProvider.getIndicators(function(response) {
			var indicators = Ext.getCmp(this.id + '-indicatorBar-indicatorTitle');
			if (indicators) {
				this.removeIndicator(indicators);
			}

			if (response._COUNT > 0) {
				TYPO3.Components.PermissionTree.Configuration.indicator = response.html;
				this.addIndicatorItems();
			}
		}, this);

		this.activeTree.refreshTree();
	},

	/**
	 * Returns the current active tree
	 *
	 * @return {TYPO3.Components.PermissionTree.Tree}
	 */
	getTree: function() {
		return this.activeTree;
	},

	/**
	 * Selects a node defined by the page id. If the second parameter is set, we
	 * store the new location into the state hash.
	 *
	 * @param {int} pageId
	 * @param {Object} nodeData
	 * @return {Boolean}
	 */
	select: function(pageId, nodeData) {
		TYPO3.Components.PermissionTree.Commands.addRootlineOfNodeToStateHash(
			TYPO3.Backend.NavigationContainer.PermissionTree.mainTree.stateId,
			pageId, function(stateHash) {
				TYPO3.Backend.NavigationContainer.PermissionTree.mainTree.stateHash = stateHash;
				TYPO3.Backend.NavigationContainer.PermissionTree.mainTree.refreshTree();
			}
		);

		return true;
	},

	/**
	 * Returns the currently selected node
	 *
	 * @return {Ext.tree.TreeNode}
	 */
	getSelected: function() {
		var node = this.getTree().getSelectionModel().getSelectedNode();
		return node ? node : null;
	}
});

/**
 * Callback method for the module menu
 *
 * @return {TYPO3.Components.PermissionTree.App}
 */
TYPO3.ModuleMenu.App.registerNavigationComponent('commerce-permissiontree', function() {
	TYPO3.Backend.NavigationContainer.PermissionTree = new TYPO3.Components.PermissionTree.App();

		// compatibility code
    top.nav = TYPO3.Backend.NavigationContainer.PermissionTree;
    top.nav_frame = TYPO3.Backend.NavigationContainer.PermissionTree;
    top.content.nav_frame = TYPO3.Backend.NavigationContainer.PermissionTree;

	return TYPO3.Backend.NavigationContainer.PermissionTree;
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PermissionTree.App', TYPO3.Components.PermissionTree.App);

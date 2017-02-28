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
Ext.namespace('TYPO3.Components.CategoryTree');

/**
 * @class TYPO3.Components.CategoryTree.App
 *
 * Page tree main application that controls setups the components
 *
 * @namespace TYPO3.Components.CategoryTree
 * @extends Ext.Panel
 */
TYPO3.Components.CategoryTree.App = Ext.extend(Ext.Panel, {
	/**
	 * Panel id
	 *
	 * @type {String}
	 */
	id: 'commerce-categorytree',

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
	 * Monitor resize
	 *
	 * @type {Boolean}
	 */
	monitorResize: true,

	/**
	 * Active tree
	 *
	 * @type {TYPO3.Components.CategoryTree.Tree}
	 */
	activeTree: null,

	/**
	 * Main CategoryTree
	 *
	 * @type {TYPO3.Components.CategoryTree.Tree}
	 */
	mainTree: null,

	/**
	 * Local cache for node paths
	 *
	 * @type {object}
	 */
	nodePaths: {},

	/**
	 * Initializes the application
	 *
	 * Set's the necessary language labels, configuration options and sprite icons by an
	 * external call and initializes the needed components.
	 *
	 * @return {void}
	 */
	initComponent: function() {
		TYPO3.Components.CategoryTree.DataProvider.loadResources(function(response) {
			TYPO3.Components.CategoryTree.LLL = response['LLL'];
			TYPO3.Components.CategoryTree.Configuration = response['Configuration'];
			TYPO3.Components.CategoryTree.Icons = response['Icons'];

			this.mainTree = this.activeTree = new TYPO3.Components.CategoryTree.Tree({
				id: this.id + '-tree',
				deletionDropZoneId: this.id + '-deletionDropZone',
				ddGroup: this.id,
				stateful: true,
				stateId: 'CategoryTree' + TYPO3.Components.CategoryTree.Configuration.temporaryMountPoint,
				stateEvents: [],
				autoScroll: true,
				autoHeight: true,
				autoWidth: true,
				plugins: [new Ext.ux.state.TreePanel()],
				commandProvider: TYPO3.Components.CategoryTree.Actions,
				treeDataProvider: TYPO3.Components.CategoryTree.DataProvider,
				monitorResize: true,
				app: this,
				listeners: {
					resize: {
						fn: function() {
							this.doLayout(true);
							TYPO3.Backend.doLayout();
						},
						scope: this,
						buffer: 250
					}
				}
			});

			var filteringTree = new TYPO3.Components.CategoryTree.FilteringTree({
				id: this.id + '-filteringTree',
				deletionDropZoneId: this.id + '-deletionDropZone',
				ddGroup: this.id,
				autoScroll: true,
				autoHeight: false,
				commandProvider: TYPO3.Components.CategoryTree.Actions,
				treeDataProvider: TYPO3.Components.CategoryTree.DataProvider,
				app: this
			}).hide();

			var topPanel = new TYPO3.Components.CategoryTree.TopPanel({
				cls: this.id + '-toppanel',
				dataProvider: TYPO3.Components.CategoryTree.DataProvider,
				filteringTree: filteringTree,
				ddGroup: this.id,
				tree: this.mainTree,
				app: this
			});

			var deletionDropZone = new TYPO3.Components.CategoryTree.DeletionDropZone({
				id: this.id + '-deletionDropZone',
				commandProvider: TYPO3.Components.CategoryTree.Actions,
				ddGroup: this.id,
				app: this,
				region: 'south',
				height: 35
			});

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
					},
					deletionDropZone
				]
			});

			if (TYPO3.Components.CategoryTree.Configuration.temporaryMountPoint) {
				topPanelItems.on('afterrender', function() {
					this.addTemporaryMountPointIndicator();
				}, this);
			}

			this.doLayout();

		}, this);

		TYPO3.Components.CategoryTree.App.superclass.initComponent.apply(this, arguments);
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
			scope: this,
			listeners: {
				afterrender: {
					fn: function() {
						var element = Ext.fly(this.id + '-indicatorBar-temporaryMountPoint-clear');
						var me = this;
						element.on('click', function() {
							top.TYPO3.Storage.Persistent.unset('categoryTree_temporaryMountPoint').done(
								function() {
									TYPO3.Components.CategoryTree.Configuration.temporaryMountPoint = null;
									me.removeIndicator(me.temporaryMountPointInfoIndicator);
									me.getTree().refreshTree();
									me.getTree().stateId = 'CategoryTree';
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
							TYPO3.Components.CategoryTree.Icons.Info +
						'</div>' +
						'<div class="media-body">' +
							TYPO3.Components.CategoryTree.Configuration.temporaryMountPoint +
						'</div>' +
						'<div class="media-right">' +
							'<a href="#" id="' + this.id + '-indicatorBar-temporaryMountPoint-clear">' +
								TYPO3.Components.CategoryTree.Icons.Close +
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
	 * Refreshes the tree and selects the node defined by fsMod.recentIds['commerce_category']
	 *
	 * @return {void}
	 */
	refreshTree: function() {
		if (!isNaN(fsMod.recentIds['commerce_category']) && fsMod.recentIds['commerce_category'] !== '') {
			this.select(fsMod.recentIds['commerce_category'], true);
		}
		this.activeTree.refreshTree();
	},

	/**
	 * Returns the current active tree
	 *
	 * @return {TYPO3.Components.CategoryTree.Tree}
	 */
	getTree: function() {
		return this.activeTree;
	},

	/**
	 * Selects a node defined by the page id.
	 *
	 * @param {int} nodeId
	 * @param {Object} nodeData
	 * @return {Boolean}
	 */
	select: function(nodeId, nodeData) {
		/*TYPO3.Components.CategoryTree.Commands.addRootlineOfNodeToStateHash(
			TYPO3.Backend.NavigationContainer.CategoryTree.mainTree.stateId,
			nodeId,
			nodeData,
			function(stateHash) {
				TYPO3.Backend.NavigationContainer.CategoryTree.mainTree.stateHash = stateHash;
				TYPO3.Backend.NavigationContainer.CategoryTree.mainTree.refreshTree();
			}
		);

		return true;*/
		this.selectPageId(nodeId);
	},

	selectPageId: function(pageId, allResults) {
		this.invokePageId(pageId, Ext.createDelegate(this.mainTree.selectPath, this.mainTree), allResults);
	},

	expandPageId: function(pageId, allResults) {
		this.invokePageId(pageId, Ext.createDelegate(this.mainTree.expandPath, this.mainTree), allResults);
	},

	/**
	 * @param {int} pageId
	 * @param {Function} callback
	 * @param {Boolean} allResults
	 */
	invokePageId: function(pageId, callback, allResults) {
		if (typeof this.nodePaths[pageId] !== 'undefined') {
			this.invokeNodePaths(this.nodePaths[pageId], pageId, callback, allResults);
		} else {
			var handler = function(nodePaths) {
				this.nodePaths[pageId] = nodePaths;
				this.invokeNodePaths(nodePaths, pageId, callback, allResults);
			};
			TYPO3.Components.CategoryTree.Commands.getNodePaths(pageId, handler, this);
		}
	},

	invokeNodePaths: function(nodePaths, pageId, callback, allResults) {
		if (!nodePaths.length) {
			return;
		}
		if (!allResults) {
			nodePaths = [nodePaths[0]];
		}
		Ext.each(nodePaths, function(nodePath) { callback('/root/' + nodePath.join('/')); });
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
 * @return {TYPO3.Components.CategoryTree.App}
 */
require(
	[
		'TYPO3/CMS/Backend/ModuleMenu'
	],
	function () {
		// extjs loading bugfix
		window.setTimeout(function() {
			TYPO3.ModuleMenu.App.registerNavigationComponent('commerce-categorytree', function () {
				return new TYPO3.Components.CategoryTree.App();
			});
		}, 5000);
	}
);

// XTYPE Registration
Ext.reg('TYPO3.Components.CategoryTree.App', TYPO3.Components.CategoryTree.App);

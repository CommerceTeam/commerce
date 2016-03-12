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
Ext.namespace('TYPO3.Components.OrderTree');

/**
 * @class TYPO3.Components.OrderTree.TopPanel
 *
 * Top Panel
 *
 * @namespace TYPO3.Components.OrderTree
 * @extends Ext.Panel
 */
TYPO3.Components.OrderTree.TopPanel = Ext.extend(Ext.Panel, {
	/**
	 * Component Id
	 *
	 * @type {String}
	 */
	id: 'commerce-ordertree-topPanel',

	/**
	 * Component Class
	 *
	 * @type {String}
	 */
	cls: 'commerce-ordertree-toppanel',

	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	border: false,

	/**
	 * Toolbar Object
	 *
	 * @type {Ext.Toolbar}
	 */
	tbar: new Ext.Toolbar(),

	/**
	 * Currently Clicked Toolbar Button
	 *
	 * @type {Ext.Button}
	 */
	currentlyClickedButton: null,

	/**
	 * Currently Shown Panel
	 *
	 * @type {Ext.Component}
	 */
	currentlyShownPanel: null,

	/**
	 * Filtering Indicator Item
	 *
	 * @type {Ext.Panel}
	 */
	filteringIndicator: null,

	/**
	 * Drag and Drop Group
	 *
	 * @cfg {String}
	 */
	ddGroup: '',

	/**
	 * Data Provider
	 *
	 * @cfg {Object}
	 */
	dataProvider: null,

	/**
	 * Filtering Tree
	 *
	 * @cfg {TYPO3.Components.OrderTree.FilteringTree}
	 */
	filteringTree: null,

	/**
	 * Page Tree
	 *
	 * @cfg {TYPO3.Components.OrderTree.Tree}
	 */
	tree: null,

	/**
	 * Application Panel
	 *
	 * @cfg {TYPO3.Components.OrderTree.App}
	 */
	app: null,

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		TYPO3.Components.OrderTree.TopPanel.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Returns a custom button template to fix some nasty webkit issues
	 * by removing some useless wrapping html code
	 *
	 * @return {void}
	 */
	getButtonTemplate: function() {
		return new Ext.Template(
			'<div id="{4}" class="x-btn {3}"><button type="{0}">&nbsp;</button></div>'
		);
	},

	/**
	 * Adds a button to the components toolbar with a related component
	 *
	 * @param {Object} button
	 * @param {Object} connectedWidget
	 * @return {void}
	 */
	addButton: function(button, connectedWidget) {
		button.template = this.getButtonTemplate();
		button.on('toggle', this.topbarButtonToggleCallback);
		if (!button.hasListener('click')) {
			button.on('click', this.topbarButtonCallback);
		}

		if (connectedWidget) {
			connectedWidget.hidden = true;
			button.connectedWidget = connectedWidget;
			this.add(connectedWidget);
		}

		this.getTopToolbar().addItem(button);
		this.doLayout();
	},

	/**
	 * Toggle button state
	 *
	 * @return {void}
	 */
	topbarButtonToggleCallback: function() {
		if (this.pressed) {
			this.el.addClass(['active']);
		} else {
			this.el.removeClass(['active']);
		}
	},

	/**
	 * Usual button callback method that triggers the assigned component of the
	 * clicked toolbar button
	 *
	 * @return {void}
	 */
	topbarButtonCallback: function() {
		var topPanel = this.ownerCt.ownerCt;

		topPanel.currentlyShownPanel.hide();
		if (topPanel.currentlyClickedButton) {
			topPanel.currentlyClickedButton.toggle(false);
		}

		if (topPanel.currentlyClickedButton === this) {
			topPanel.currentlyClickedButton = null;
			topPanel.currentlyShownPanel = topPanel.get(topPanel.id + '-defaultPanel');
		} else {
			this.toggle(true);
			topPanel.currentlyClickedButton = this;
			topPanel.currentlyShownPanel = this.connectedWidget;
		}

		topPanel.currentlyShownPanel.show();
	},

	/**
	 * Loads the filtering tree nodes with the given search word
	 *
	 * @param {Ext.form.TextField} textField
	 * @return {void}
	 */
	createFilterTree: function(textField) {
		var searchWord = textField.getValue();
		var isNumber = TYPO3.Utility.isNumber(searchWord);
		var hasMinLength = (searchWord.length > 2 || searchWord.length <= 0);
		if ((!hasMinLength && !isNumber) || searchWord === this.filteringTree.searchWord) {
			return;
		}

		this.filteringTree.searchWord = searchWord;
		if (this.filteringTree.searchWord === '') {
			this.app.activeTree = this.tree;
			this.tree.t3ContextNode = this.filteringTree.t3ContextNode;

			textField.setHideTrigger(true);
			this.filteringTree.hide();
			this.tree.show().refreshTree(function() {
				textField.focus(false, 500);
			}, this);

			if (this.filteringIndicator) {
				this.app.removeIndicator(this.filteringIndicator);
				this.filteringIndicator = null;
			}
		} else {
			var selectedNode = this.app.getSelected();
			this.app.activeTree = this.filteringTree;

			if (!this.filteringIndicator) {
				this.filteringIndicator = this.app.addIndicator(
					this.createIndicatorItem(textField)
				);
			}

			textField.setHideTrigger(false);
			this.tree.hide();
			this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
			this.app.ownerCt.getEl().addClass('t3-mask-loading');
			this.filteringTree.show().refreshTree(function() {
				if (selectedNode) {
					this.app.select(selectedNode.attributes.nodeData.id, false);
				}
				textField.focus();
				this.app.ownerCt.getEl().unmask();
			}, this);
		}

		this.doLayout();
	},

	/**
	 * Adds an indicator item to the page tree application for the filtering feature
	 *
	 * @param {Ext.form.TextField} textField
	 * @return {void}
	 */
	createIndicatorItem: function(textField) {
		return {
			border: false,
			id: this.app.id + '-indicatorBar-filter',
			cls: this.app.id + '-indicatorBar-item',
			html: '' +
				'<div class="alert alert-info">' +
					'<div class="media">' +
						'<div class="media-left">' +
							TYPO3.Components.OrderTree.Icons.Info +
						'</div>' +
						'<div class="media-body">' +
							TYPO3.Components.OrderTree.LLL.activeFilterMode +
						'</div>' +
						'<div class="media-right">' +
							'<a href="#" id="' + this.app.id + '-indicatorBar-filter-clear">' +
								TYPO3.Components.OrderTree.Icons.Close +
							'</a>' +
						'</div>' +
					'</div>' +
				'</div>',
			filteringTree: this.filteringTree,

			listeners: {
				afterrender: {
					scope: this,
					fn: function() {
						var element = Ext.fly(this.app.id + '-indicatorBar-filter-clear');
						element.on('click', function() {
							textField.setValue('');
							this.createFilterTree(textField);
						}, this);
					}
				}
			}
		};
	},

	/**
	 * Adds the necessary functionality and components for the filtering feature
	 *
	 * @return {void}
	 */
	addFilterFeature: function() {
		var topPanelButton = new Ext.Button({
			id: this.id + '-button-filter',
			cls: 'btn btn-default btn-sm',
			text: TYPO3.Components.OrderTree.Icons.Filter,
			tooltip: TYPO3.Components.OrderTree.LLL.buttonFilter
		});

		var textField = new Ext.form.TriggerField({
			id: this.id + '-filter',
			cls: 'form-control input-sm commerce-ordertree-toppanel-filter',
			enableKeyEvents: true,
			triggerConfig: {
				tag: 'span',
				html: TYPO3.Components.OrderTree.Icons.InputClear,
				cls: 'commerce-ordertree-toppanel-filter-clear'
			},
			value: TYPO3.Components.OrderTree.LLL.searchTermInfo,

			listeners: {
				blur: {
					scope: this,
					fn:function(textField) {
						if (textField.getValue() === '') {
							textField.setValue(TYPO3.Components.OrderTree.LLL.searchTermInfo);
							textField.addClass(this.id + '-filter-defaultText');
						}
					}
				},

				focus: {
					scope: this,
					fn: function(textField) {
						if (textField.getValue() === TYPO3.Components.OrderTree.LLL.searchTermInfo) {
							textField.setValue('');
							textField.removeClass(this.id + '-filter-defaultText');
						}
					}
				},

				keydown: {
					fn: this.createFilterTree,
					scope: this,
					buffer: 1000
				}
			}
		});

		textField.setHideTrigger(true);
		textField.onTriggerClick = function() {
			textField.setValue('');
			this.createFilterTree(textField);
		}.createDelegate(this);

		var topPanelWidget = new Ext.Container({
			border: false,
			id: this.id + '-filterWrap',
			cls: this.id + '-item commerce-ordertree-toppanel-item',
			border: false,
			items: [textField],

			listeners: {
				show: {
					scope: this,
					fn: function(panel) {
						panel.get(this.id + '-filter').focus();
					}
				}
			}
		});

		this.addButton(topPanelButton, topPanelWidget);
	},

	/**
	 * Creates the entries for the new node drag zone toolbar
	 *
	 * @return {void}
	 */
	createNewNodeToolbar: function() {
		this.dragZone = new Ext.dd.DragZone(this.getEl(), {
			ddGroup: this.ownerCt.ddGroup,
			topPanel: this.ownerCt,

			endDrag: function() {
				this.topPanel.app.activeTree.dontSetOverClass = false;
			},

			getDragData: function(event) {
				this.proxyElement = document.createElement('div');
				if (event.getTarget('.x-btn') !== null){
					var node = Ext.getCmp(event.getTarget('.x-btn').id);
					node.shouldCreateNewNode = true;
					return {
						ddel: this.proxyElement,
						item: node
					}
				}
			},

			onInitDrag: function() {
				this.topPanel.app.activeTree.dontSetOverClass = true;
				var clickedButton = this.dragData.item;

				this.proxyElement.shadow = false;
				this.proxyElement.innerHTML = '<div class="x-dd-drag-ghost-categorytree">' +
					'<span class="x-dd-drag-ghost-categorytree-icon">' + clickedButton.initialConfig.html + '</span>' +
					'<span class="x-dd-drag-ghost-categorytree-text">'  + clickedButton.title + '</span>' +
				'</div>';

				this.proxy.update(this.proxyElement);
			}
		});

			// listens on the escape key to stop the dragging
		(new Ext.KeyMap(document, {
			key: Ext.EventObject.ESC,
			scope: this,
			buffer: 250,
			fn: function(event) {
				if (this.dragZone.dragging) {
					Ext.dd.DragDropMgr.stopDrag(event);
					this.dragZone.onInvalidDrop(event);
				}
			}
		}, 'keydown'));
	},

	/**
	 * Creates the necessary components for new node drag and drop feature
	 *
	 * @return {void}
	 */
	addDragDropNodeInsertionFeature: function() {
		var newNodeToolbar = new Ext.Toolbar({
			border: false,
			id: this.id + '-item-newNode',
			listeners: {
				render: {
					fn: this.createNewNodeToolbar
				}
			}
		});

		this.dataProvider.getNodeTypes(function(response) {
			var amountOfNodeTypes = response.length;
			if (amountOfNodeTypes > 0) {
				topPanelButton.show();
				for (var i = 0; i < amountOfNodeTypes; ++i) {
					response[i].template = this.getButtonTemplate();
					response[i].cls = 'commerce-ordertree-toppanel-drag-node';
					newNodeToolbar.addItem(response[i]);
				}
				newNodeToolbar.doLayout();
			}
		}, this);

		var topPanelButton = new Ext.Button({
			id: this.id + '-button-newNode',
			cls: 'btn btn-default btn-sm',
			text: TYPO3.Components.OrderTree.Icons.NewNode,
			tooltip: TYPO3.Components.OrderTree.LLL.buttonNewNode,
			hidden: true
		});

		this.addButton(topPanelButton, newNodeToolbar);
	},

	/**
	 * Adds a button to the toolbar for the refreshing feature
	 *
	 * @return {void}
	 */
	addRefreshTreeFeature: function() {
		var topPanelButton = new Ext.Button({
			id: this.id + '-button-refresh',
			cls: 'btn btn-default btn-sm',
			text: TYPO3.Components.OrderTree.Icons.Refresh,
			tooltip: TYPO3.Components.OrderTree.LLL.buttonRefresh,

			listeners: {
				click: {
					scope: this,
					fn: function() {
						this.app.activeTree.refreshTree();
					}
				}
			}
		});

		this.addButton(topPanelButton);
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.OrderTree.TopPanel', TYPO3.Components.OrderTree.TopPanel);

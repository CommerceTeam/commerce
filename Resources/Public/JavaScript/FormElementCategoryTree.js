/* globals TYPO3, TBE_EDITOR */

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
define(['require', 'TYPO3/CMS/Backend/FormEngine', 'jquery'], function(require, FormEngine, $) {
	'use strict';

	/** @var {Object} TYPO3 */
	/** @var {{ajaxUrls: array}} settings */
	var settings = TYPO3.settings,
		ajaxUrl = settings.ajaxUrls['commerce_category_tree'];

	/**
	 * Module: TYPO3/CMS/Commerce/FormElementCategoryTree
	 * Enables interaction with category selection tree
	 *
	 * @exports TYPO3/CMS/Backend/FormElementCategoryTree
	 */
	var FormElementCategoryTree = (function () {
		/**
		 * The constructor, set the class properties default values
		 */
		function FormElementCategoryTree() {
			var _this = this;

			this.treeItemClass = '.t3js-commerce-categorytree-itemstoselect';
			this.removeItemClass = '.commerce-categorytree-element .t3js-btn-removeoption';

			/**
			 * Add clicked element to selectbox if not already added
			 *
			 * @param $listFieldEl
			 * @param fieldName
			 * @param $treeElement
			 */
			this.addItemToSelectbox = function ($listFieldEl, $treeElement, fieldName) {
				// store clicked element for later comparison in remove option method
				$listFieldEl.data('selected-values').push($treeElement.data('value'));

				// set element active and change background
				$treeElement.parent().addClass('bg-success').parent().addClass('active');

				FormEngine.setSelectOptionFromExternalSource(
					fieldName,
					$treeElement.data('value'),
					$treeElement.text(),
					$treeElement.attr('title'),
					''
				);
				TBE_EDITOR.fieldChanged();
			};

			/**
			 * removes currently selected options from a select field
			 *
			 * @param {Object} $listFieldEl a jQuery object, containing the select field
			 * @param {Object} $availableFieldEl a jQuery object, containing all available value
			 */
			this.removeOption = function ($listFieldEl, $availableFieldEl) {
				var previousSelected = $listFieldEl.data('selected-values');

				$.each(previousSelected, function (i, previousValue) {
					var stillSelected = $listFieldEl.find('option[value="' + previousValue + '"]:not(:selected)').length > 0;

					if (!stillSelected) {
						$availableFieldEl
							.find('[data-value="' + previousValue + '"]')
							.parent()
							.removeClass('bg-success')
							.parent()
							.removeClass('active');
					}
				});
			};

			/**
			 * Open or close subtree based on element state
			 *
			 * @param $listFieldEl
			 * @param $treeElement
			 */
			this.openOrCloseSubtree = function ($listFieldEl, $treeElement) {
				var $parent = $treeElement.parent().parent(),
					$submenu = $parent.find('> ul');

				if ($treeElement.hasClass('list-tree-control-open')) {
					this.closeSubtree($treeElement, $parent, $submenu);
				} else {
					this.openSubtree($listFieldEl, $treeElement, $parent, $submenu);
				}
			};

			/**
			 * Open subtree if already loaded and closed or load subtree via ajax
			 *
			 * @param $listFieldEl
			 * @param $treeElement
			 * @param $parent
			 * @param $submenu
			 */
			this.openSubtree = function ($listFieldEl, $treeElement, $parent, $submenu) {
				$treeElement.removeClass('list-tree-control-closed').addClass('list-tree-control-open');
				$treeElement.data('pm', $treeElement.data('pm').replace('0_0_', '0_1_'));
				$parent.addClass('list-tree-control-open');

				if ($submenu.length > 0) {
					$submenu.css('display', 'inherit');

					this.storeTreeState($treeElement);
				} else {
					this.getSubtree($listFieldEl, $treeElement);
				}
			};

			/**
			 * Load subtree with ajax
			 *
			 * @param $listFieldEl
			 * @param $treeElement
			 */
			this.getSubtree = function ($listFieldEl, $treeElement) {
				$.ajax({
					url: ajaxUrl,
					type: 'post',
					dataType: 'html',
					cache: false,
					data: {
						'action': 'getSubtree',
						'PM': $treeElement.data('pm'),
						'selectedItems': $listFieldEl.children('option').map(function() { return $(this).val(); }).get()
					}
				}).done(function(response) {
					$treeElement.parent().after(response);
				});
			};

			/**
			 * Close subtree
			 *
			 * @param $treeElement
			 * @param $parent
			 * @param $submenu
			 */
			this.closeSubtree = function ($treeElement, $parent, $submenu) {
				$treeElement.removeClass('list-tree-control-open').addClass('list-tree-control-closed');
				$treeElement.data('pm', $treeElement.data('pm').replace('0_1_', '0_0_'));
				$parent.removeClass('list-tree-control-open');
				$submenu.css('display', 'none');

				this.storeTreeState($treeElement);
			};

			/**
			 * Store elements state if an subtree was closed
			 * The open state is saved while opening the subtree
			 *
			 * @param $treeElement
			 */
			this.storeTreeState = function ($treeElement) {
				$.ajax({
					url: ajaxUrl,
					type: 'post',
					dataType: 'html',
					cache: false,
					data: {
						action: 'storeState',
						PM: $treeElement.data('pm')
					}
				}).done(function() {
					console.log('stored tree state');
				});
			};

			/**
			 * Handles event if a tree item was clicked
			 *
			 * @param event
			 */
			this.treeItemClicked = function (event) {
				var $el = $(this),
					fieldName = $el.data('relatedfieldname');

				if (fieldName) {
					var $listFieldEl = FormEngine.getFieldElement(fieldName, '_list', true),
						$treeElement = $(event.target);

					if ($treeElement.hasClass('list-tree-title')) {
						_this.addItemToSelectbox($listFieldEl, $treeElement, fieldName);
					} else if ($treeElement.hasClass('list-tree-control')) {
						_this.openOrCloseSubtree($listFieldEl, $treeElement);
					}
				}
			};

			/**
			 * Handles the event if the delete button to remove selected options was clicked
			 */
			this.removeItemClicked = function () {
				var $el = $(this),
					fieldName = $el.data('fieldname'),
					$listFieldEl = FormEngine.getFieldElement(fieldName, '_list', true),
					$availableFieldEl = $(
						'div[data-relatedfieldname="' + fieldName + '"]',
						FormEngine.getFormElement('')
					);

				_this.removeOption($listFieldEl, $availableFieldEl);
			};

			this.initialize();
		}

		/**
		 * initializes events using deferred bound to document
		 * so AJAX reloads are no problem
		 */
		FormElementCategoryTree.prototype.initialize = function () {
			var $document = $(document);
			$document.on('click', this.treeItemClass, this.treeItemClicked);
			$document.on('click', this.removeItemClass, this.removeItemClicked);
		};

		return FormElementCategoryTree;
	}());

	return new FormElementCategoryTree();
});
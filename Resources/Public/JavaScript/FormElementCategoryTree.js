/* globals window, TYPO3, TBE_EDITOR */

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
 * Module: TYPO3/CMS/Commerce/FormElementCategoryTree
 * Javascript functions regarding the category tree in category element
 */
define(['TYPO3/CMS/Backend/FormEngine', 'jquery'], function(FormEngine, $) {
	'use strict';

	/** @var {Object} TYPO3 */
	var settings = TYPO3.settings,
		ajaxUrl = settings.ajaxUrls['commerce_category_tree'];

	var FormElementCategoryTree = {};

	/**
	 * Add clicked element to selectbox if not already added
	 *
	 * @param $listFieldEl
	 * @param fieldName
	 * @param $treeElement
	 */
	FormElementCategoryTree.addItemToSelectbox = function($listFieldEl, fieldName, $treeElement) {
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
	FormElementCategoryTree.removeOption = function ($listFieldEl, $availableFieldEl) {
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
	FormElementCategoryTree.openOrCloseSubtree = function($listFieldEl, $treeElement) {
		var $parent = $treeElement.parent().parent(),
			$submenu = $parent.find('> ul');

		if ($treeElement.hasClass('list-tree-control-open')) {
			FormElementCategoryTree.closeSubtree($treeElement, $parent, $submenu);
		} else {
			FormElementCategoryTree.openSubtree($listFieldEl, $treeElement, $parent, $submenu);
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
	FormElementCategoryTree.openSubtree = function($listFieldEl, $treeElement, $parent, $submenu) {
		$treeElement.removeClass('list-tree-control-closed').addClass('list-tree-control-open');
		$treeElement.data('pm', $treeElement.data('pm').replace('0_0_', '0_1_'));
		$parent.addClass('list-tree-control-open');

		if ($submenu.length > 0) {
			$submenu.css('display', 'inherit');

			FormElementCategoryTree.storeTreeState($treeElement);
		} else {
			FormElementCategoryTree.getSubtree($listFieldEl, $treeElement);
		}
	};

	/**
	 * Load subtree with ajax
	 *
	 * @param $listFieldEl
	 * @param $treeElement
	 */
	FormElementCategoryTree.getSubtree = function($listFieldEl, $treeElement) {
		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'getSubtree',
				PM: $treeElement.data('pm'),
				selectedItems: $listFieldEl.children('option').map(function() { return $(this).val(); }).get()
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
	FormElementCategoryTree.closeSubtree = function($treeElement, $parent, $submenu) {
		$treeElement.removeClass('list-tree-control-open').addClass('list-tree-control-closed');
		$treeElement.data('pm', $treeElement.data('pm').replace('0_1_', '0_0_'));
		$parent.removeClass('list-tree-control-open');
		$submenu.css('display', 'none');

		FormElementCategoryTree.storeTreeState($treeElement);
	};

	/**
	 * Store elements state if an subtree was closed
	 * The open state is saved while opening the subtree
	 *
	 * @param $treeElement
	 */
	FormElementCategoryTree.storeTreeState = function($treeElement) {
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

	FormElementCategoryTree.treeItemClicked = function (evt) {
		var $el = $(this),
			fieldName = $el.data('relatedfieldname');

		if (fieldName) {
			var $listFieldEl = FormEngine.getFieldElement(fieldName, '_list', true),
				$treeElement = $(evt.target);

			if ($treeElement.hasClass('list-tree-title')) {
				FormElementCategoryTree.addItemToSelectbox($listFieldEl, fieldName, $treeElement);
			} else if ($treeElement.hasClass('list-tree-control')) {
				FormElementCategoryTree.openOrCloseSubtree($listFieldEl, $treeElement);
			}
		}
	};

	FormElementCategoryTree.removeItemClicked = function removeOption () {
		var $el = $(this),
			fieldName = $el.data('fieldname'),
			$listFieldEl = FormEngine.getFieldElement(fieldName, '_list', true),
			$availableFieldEl = $(
				'div[data-relatedfieldname="' + fieldName + '"]',
				FormEngine.getFormElement('')
			);

		FormElementCategoryTree.removeOption($listFieldEl, $availableFieldEl);
	};

	/**
	 * initializes events using deferred bound to document
	 * so AJAX reloads are no problem
	 */
	FormElementCategoryTree.initializeEvents = function() {
		var $document = $(document);
		$document.on(
			'click',
			'.t3js-commerce-categorytree-itemstoselect',
			FormElementCategoryTree.treeItemClicked
		);
		$document.on(
			'click',
			'.commerce-categorytree-element .t3js-btn-removeoption',
			FormElementCategoryTree.removeItemClicked
		);
	};

	$(FormElementCategoryTree.initializeEvents);

	return FormElementCategoryTree;
});
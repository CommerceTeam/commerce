/* globals window, TYPO3 */

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
define(['jquery'], function($) {
	/**
	 *
	 * @type {{options: {containerSelector: string}}}
	 * @exports TYPO3/CMS/Beuser/Permissons
	 */
	var FormElementCategoryTree = {
		options: {
			containerSelector: '.t3js-commerce-categorytree-itemstoselect',
			treeControll: '.list-tree-control',
			itemTitleSelector: '.list-tree-title',
			removeButtonSelector: '.commerce-categorytree-element .t3-btn-removeoption'
		}
	};

	var $treeContainer,
		$selectField,
		ajaxUrl = TYPO3.settings.ajaxUrls['commerce_category_tree'];

	/**
	 * Add clicked element to selectbox if not already added
	 *
	 * @param fieldName
	 * @param $treeElement
	 */
	FormElementCategoryTree.addItemToSelectbox = function(fieldName, $treeElement) {
		$treeElement.parent().addClass('bg-success').parent().addClass('active');
		TYPO3.FormEngine.setSelectOptionFromExternalSource(
			fieldName,
			$treeElement.data('value'),
			$treeElement.text(),
			$treeElement.attr('title'),
			''
		);
		TBE_EDITOR.fieldChanged();
	};

	/**
	 * Removes the active state of the item that was removed from the select
	 *
	 * @param value
	 */
	FormElementCategoryTree.deactivateRemovedItem = function(value) {
		$('[data-value="' + value + '"]').parent().removeClass('bg-success').parent().removeClass('active');
	};

	/**
	 * Open or close subtree based on element state
	 *
	 * @param $treeElement
	 */
	FormElementCategoryTree.openOrCloseSubtree = function($treeElement) {
		var $parent = $treeElement.parent().parent(),
			$submenu = $parent.find('> ul');

		if ($treeElement.hasClass('list-tree-control-open')) {
			FormElementCategoryTree.closeSubtree($treeElement, $parent, $submenu);
		} else {
			FormElementCategoryTree.openSubtree($treeElement, $parent, $submenu);
		}
	};

	/**
	 * Open subtree if already loaded and closed or load subtree via ajax
	 *
	 * @param $treeElement
	 * @param $parent
	 * @param $submenu
	 */
	FormElementCategoryTree.openSubtree = function($treeElement, $parent, $submenu) {
		$treeElement.removeClass('list-tree-control-closed').addClass('list-tree-control-open');
		$treeElement.data('pm', $treeElement.data('pm').replace('0_0_', '0_1_'));
		$parent.addClass('list-tree-control-open');

		if ($submenu.length > 0) {
			$submenu.css('display', 'inherit');

			FormElementCategoryTree.storeTreeState($treeElement);
		} else {
			FormElementCategoryTree.getSubtree($treeElement);
		}
	};

	/**
	 * Load subtree with ajax
	 *
	 * @param $treeElement
	 */
	FormElementCategoryTree.getSubtree = function($treeElement) {
		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'getSubtree',
				PM: $treeElement.data('pm'),
				selectedItems: $selectField.children('option').map(function() { return $(this).val(); }).get()
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
		}).done(function(response) {
			console.log('stored tree state');
		});
	};

	/**
	 * initializes events using deferred bound to document
	 * so AJAX reloads are no problem
	 */
	FormElementCategoryTree.initializeEvents = function() {
		$(document).on(
			'click',
			FormElementCategoryTree.options.containerSelector,
			function(evt) {
				$treeContainer = $(this);
				var fieldName = $treeContainer.data('relatedfieldname'),
					fieldChangedValues = $treeContainer.data('fieldchanged-values');

				if (fieldName) {
					$selectField = TYPO3.FormEngine.getFieldElement(fieldName, '_list', true);

					var $treeElement = $(evt.target);
					if ($treeElement.hasClass('list-tree-title')) {
						FormElementCategoryTree.addItemToSelectbox(fieldName, $treeElement);
					} else if ($treeElement.hasClass('list-tree-control')) {
						FormElementCategoryTree.openOrCloseSubtree($treeElement);
					}
				}
			}
		).on(
			'click',
			FormElementCategoryTree.options.removeButtonSelector,
			function () {
				var $deleteButton = $(this),
					fieldName = $deleteButton.data().fieldname;
				$selectField = TYPO3.FormEngine.getFieldElement(fieldName, '_list', true);

				FormElementCategoryTree.deactivateRemovedItem($selectField.children('option:selected').prop('value'));
			}
		);
	};

	$(FormElementCategoryTree.initializeEvents);

	// expose to global
	TYPO3.FormElementCategoryTree = FormElementCategoryTree;

	return FormElementCategoryTree;
});
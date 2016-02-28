/*
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
			treeControll: '.list-tree-control'
		}
	};
	var ajaxUrl = TYPO3.settings.ajaxUrls['commerce_category_tree'];

	/**
	 * initializes events using deferred bound to document
	 * so AJAX reloads are no problem
	 */
	FormElementCategoryTree.initializeEvents = function() {
		$(document).on('click', FormElementCategoryTree.options.containerSelector, function(evt) {
			var $el = $(this),
				fieldName = $el.data('relatedfieldname'),
				fieldChangedValues = $el.data('fieldchanged-values');

			if (fieldName) {
				var $optionEl = $(evt.target);
				TYPO3.FormEngine.setSelectOptionFromExternalSource(
					fieldName,
					$optionEl.data('value'),
					$optionEl.text(),
					$optionEl.attr('title'),
					''
				);
				TBE_EDITOR.fieldChanged();
			}
		});
	};

	$(FormElementCategoryTree.initializeEvents);

	// expose to global
	TYPO3.FormElementCategoryTree = FormElementCategoryTree;

	return FormElementCategoryTree;
});
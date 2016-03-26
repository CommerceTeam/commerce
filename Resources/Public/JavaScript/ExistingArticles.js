/* globals window */

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
 * Module: TYPO3/CMS/Commerce/Permissions
 * Javascript functions regarding the permissions module
 */
define([
	'jquery',
	'TYPO3/CMS/Backend/AjaxDataHandler',
	'TYPO3/CMS/Backend/Icons'
], function($, DataHandler, Icons) {
	/**
	 * @type {{options: {deleteActionSelector: string}}}
	 * @exports TYPO3/CMS/Commerce/ExistingArticles
	 */
	var ExistingArticles = {
		options: {
			deleteActionSelector: '#recordlist-tx_commerce_articles'
		}
	};

	/**
	 * delete record by given element (icon in table)
	 * don't call it directly!
	 *
	 * @param {HTMLElement} element
	 */
	DataHandler.deleteRecord = function(element) {
		var $anchorElement = $(element);
		var params = $anchorElement.data('params');
		var $iconElement = $anchorElement.find(DataHandler.identifier.icon);

		// add a spinner
		DataHandler._showSpinnerIcon($iconElement);

		// make the AJAX call to toggle the visibility
		DataHandler._call(params).done(function(result) {
			// revert to the old class
			Icons.getIcon('actions-edit-delete', Icons.sizes.small).done(function(icon) {
				$iconElement.replaceWith(icon);
			});
			// print messages on errors
			if (result.hasErrors) {
				DataHandler.handleErrors(result);
			} else {
				var $table = $anchorElement.closest('table[data-table]');
				var $panel = $anchorElement.closest('.panel');
				var $panelHeading = $panel.find('.panel-heading');
				var table = $table.data('table');
				var $rowElements = $anchorElement.closest('tr[data-uid]');
				var uid = $rowElements.data('uid');
				var $translatedRowElements = $table.find('[data-l10nparent=' + uid + ']').closest('tr[data-uid]');
				$rowElements = $rowElements.add($translatedRowElements);

				$rowElements.fadeTo('slow', 0.4, function() {
					$rowElements.slideUp('slow', 0, function() {
						$rowElements.remove();
						if ($table.find('tbody tr').length === 0) {
							$panel.slideUp('slow');
						}
					});
				});
				if ($anchorElement.data('l10parent') === '0' || $anchorElement.data('l10parent') === '') {
					var count = Number($panelHeading.find('.t3js-table-total-items').html());
					$panelHeading.find('.t3js-table-total-items').html(count-1);
				}

				ExistingArticles.refreshCategoryTree();
			}
		});
	};

	/**
	 * Calls the refresh tree method of the category tree
	 */
	ExistingArticles.refreshCategoryTree = function() {
		top.TYPO3.Backend.NavigationContainer.CategoryTree.refreshTree();
	};

	/**
	 * initializes events using deferred bound to document
	 * so AJAX reloads are no problem
	 */
	ExistingArticles.initializeEvents = function() {
		$(document).on(
			'click',
			ExistingArticles.options.deleteActionSelector,
			function() {
				$clickedCreateAction = $(this);
			}
		);
	};

	$(ExistingArticles.initializeEvents);

	// expose to global
	window.TYPO3.ExistingArticles = ExistingArticles;

	return ExistingArticles;
});

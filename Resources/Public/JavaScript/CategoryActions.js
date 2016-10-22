/*
 * This file is part of the TYPO3 Commerce project.
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
 * Module: TYPO3/CMS/Commerce/CategoryActions
 * JavaScript implementations for category actions
 */
define(['jquery', 'TYPO3/CMS/Backend/Storage'], function($, Storage) {
	'use strict';

	/**
	 *
	 * @type {{settings: {pageId: number, language: {pageOverlayId: number}}, identifier: {pageTitle: string, hiddenElements: string}, elements: {$pageTitle: null, $showHiddenElementsCheckbox: null}, documentIsReady: boolean}}
	 * @exports TYPO3/CMS/Backend/CategoryActions
	 */
	var CategoryActions = {
		settings: {
			categoryId: 0,
			language: {
				pageOverlayId: 0
			}
		},
		identifier: {
			pageTitle: '.t3js-title-inlineedit'
		},
		elements: {
			$pageTitle: null
		},
		documentIsReady: false
	};

	/**
	 * Initialize category title renaming
	 */
	CategoryActions.initializePageTitleRenaming = function() {
		if (!CategoryActions.documentIsReady) {
			$(function() {
				CategoryActions.initializePageTitleRenaming();
			});
			return;
		}
		if (CategoryActions.settings.categoryId <= 0) {
			return;
		}

		var $editActionLink = $('<a class="hidden" href="#" data-action="edit"><span class="t3-icon fa fa-pencil"></span></a>');
		$editActionLink.on('click', function(e) {
			e.preventDefault();
			CategoryActions.editPageTitle();
		});
		CategoryActions.elements.$pageTitle
			.on('dblclick', CategoryActions.editPageTitle)
			.on('mouseover', function() { $editActionLink.removeClass('hidden'); })
			.on('mouseout', function() { $editActionLink.addClass('hidden'); })
			.append($editActionLink);
	};

	/**
	 * Initialize elements
	 */
	CategoryActions.initializeElements = function() {
		CategoryActions.elements.$pageTitle = $(CategoryActions.identifier.pageTitle + ':first');
	};

	/**
	 * Changes the h1 to an edit form
	 */
	CategoryActions.editPageTitle = function() {
		var $inputFieldWrap = $(
				'<form>' +
					'<div class="form-group">' +
						'<div class="input-group input-group-lg">' +
							'<input class="form-control">' +
							'<span class="input-group-btn">' +
								'<button class="btn btn-default" type="button" data-action="submit"><span class="t3-icon fa fa-floppy-o"></span></button> ' +
							'</span>' +
							'<span class="input-group-btn">' +
								'<button class="btn btn-default" type="button" data-action="cancel"><span class="t3-icon fa fa-times"></span></button> ' +
							'</span>' +
						'</div>' +
					'</div>' +
				'</form>'
			),
			$inputField = $inputFieldWrap.find('input');

		$inputFieldWrap.find('[data-action=cancel]').on('click', function() {
			$inputFieldWrap.replaceWith(CategoryActions.elements.$pageTitle);
			CategoryActions.initializePageTitleRenaming();
		});

		$inputFieldWrap.find('[data-action=submit]').on('click', function() {
			var newPageTitle = $.trim($inputField.val());
			if (newPageTitle !== '' && CategoryActions.elements.$pageTitle.text() !== newPageTitle) {
				CategoryActions.saveChanges($inputField);
			} else {
				$inputFieldWrap.find('[data-action=cancel]').trigger('click');
			}
		});

		// the form stuff is a wacky workaround to prevent the submission of the docheader form
		$inputField.parents('form').on('submit', function(e) {
			e.preventDefault();
			return false;
		});

		var $h1 = CategoryActions.elements.$pageTitle;
		$h1.children().last().remove();
		$h1.replaceWith($inputFieldWrap);
		$inputField.val($h1.text()).focus();

		$inputField.on('keyup', function(e) {
			switch (e.which) {
				case 13: // enter
					$inputFieldWrap.find('[data-action=submit]').trigger('click');
					break;
				case 27: // escape
					$inputFieldWrap.find('[data-action=cancel]').trigger('click');
					break;
			}
		});
	};

	/**
	 * Set the page id (used in the RequireJS callback)
	 *
	 * @param {Number} categoryId
	 */
	CategoryActions.setCategoryId = function(categoryId) {
		CategoryActions.settings.categoryId = categoryId;
	};

	/**
	 * Set the overlay id
	 *
	 * @param {Number} overlayId
	 */
	CategoryActions.setLanguageOverlayId = function(overlayId) {
		CategoryActions.settings.language.pageOverlayId = overlayId;
	};

	/**
	 * Save the changes and reload the page tree
	 *
	 * @param {Object} $field
	 */
	CategoryActions.saveChanges = function($field) {
		var $inputFieldWrap = $field.parents('form');
		$inputFieldWrap.find('button').addClass('disabled');
		$field.attr('disabled', 'disabled');

		var parameters = {},
			pagesTable,
			recordUid;

		if (CategoryActions.settings.language.pageOverlayId === 0) {
			pagesTable = 'tx_commerce_categories';
			recordUid = CategoryActions.settings.categoryId;
		} else {
			pagesTable = 'tx_commerce_categories';
			recordUid = CategoryActions.settings.language.pageOverlayId;
		}

		parameters.data = {};
		parameters.data[pagesTable] = {};
		parameters.data[pagesTable][recordUid] = {title: $field.val()};

		require(['TYPO3/CMS/Backend/AjaxDataHandler'], function(DataHandler) {
			DataHandler.process(parameters).done(function() {
				$inputFieldWrap.find('[data-action=cancel]').trigger('click');
				CategoryActions.elements.$pageTitle.text($field.val());
				CategoryActions.initializePageTitleRenaming();
				top.TYPO3.Backend.NavigationContainer.CategoryTree.refreshTree();
			}).fail(function() {
				$inputFieldWrap.find('[data-action=cancel]').trigger('click');
			});
		});
	};

	$(function() {
		CategoryActions.initializeElements();
		CategoryActions.documentIsReady = true;
	});

	return CategoryActions;
});

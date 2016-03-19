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
define(['jquery'], function($) {

	/**
	 *
	 * @type {{options: {containerSelector: string}}}
	 * @exports TYPO3/CMS/Beuser/Permissons
	 */
	var ProducibleArticles = {
		options: {
			createArticleSelector: '.t3js-article-create',
			articleListSelector: '#recordlist-tx_commerce_articles'
		}
	};
	var ajaxUrl = TYPO3.settings.ajaxUrls['user_article_create'],
		$clickedCreateAction;

	/**
	 * Call ajax to create article
	 *
	 * @param int product
	 * @param object|array attributeValue
	 */
	ProducibleArticles.createArticle = function(product, attributeValue) {
		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'createArticle',
				'product': product,
				'attributeValue': attributeValue
			}
		}).done(function(response) {
			ProducibleArticles.addArticleRowToList(response);
			ProducibleArticles.removeLastCreatedAttributes();
			ProducibleArticles.switchToExistingArticlesTab();
		});
	};

	/**
	 * Add article row html to the end of article list
	 *
	 * @param string articleRow
	 */
	ProducibleArticles.addArticleRowToList = function(articleRow) {
		$(ProducibleArticles.options.articleListSelector).find('tbody').append(articleRow);
	};

	/**
	 * Remove the row with the attributes of the last create article
	 *
	 * @return {void}
	 */
	ProducibleArticles.removeLastCreatedAttributes = function() {
		if (!$.isEmptyObject($clickedCreateAction.data('attribute-value'))) {
			$clickedCreateAction.closest('tr').remove();
			$clickedCreateAction = null;
		}
	};

	/**
	 * Switch to existing articles tab
	 *
	 * @return {void}
	 */
	ProducibleArticles.switchToExistingArticlesTab = function() {
		$(ProducibleArticles.options.articleListSelector)
			.closest('.tab-content')
				.parent()
					.find('.t3js-tabmenu-item:eq(0) a')
					.click();
	};

	/**
	 * initializes events using deferred bound to document
	 * so AJAX reloads are no problem
	 */
	ProducibleArticles.initializeEvents = function() {
		$(document).on(
			'click',
			ProducibleArticles.options.createArticleSelector,
			function() {
				$clickedCreateAction = $(this);

				ProducibleArticles.createArticle(
					$clickedCreateAction.data('product'),
					$clickedCreateAction.data('attribute-value')
				);
			}
		);
	};

	$(ProducibleArticles.initializeEvents);

	// expose to global
	TYPO3.ProducibleArticles = ProducibleArticles;

	return ProducibleArticles;
});

<?php
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
 * Part of the COMMERCE (Advanced Shopping System) extension.
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 *
 * Class Tx_Commerce_Hook_CommandMapHooks
 *
 * @author 2005-2011 Franz Holzinger <kontakt@fholzinger.com>
 */
class Tx_Commerce_Hook_CommandMapHooks {
	/**
	 * @var int
	 */
	const ATTRIBUTE_LOCALIZATION_TITLE_EMPTY = 0;

	/**
	 * @var int
	 */
	const ATTRIBUTE_LOCALIZATION_TITLE_COPY = 1;

	/**
	 * @var int
	 */
	const ATTRIBUTE_LOCALIZATION_TITLE_PREPENDED = 2;


	/**
	 * @var Tx_Commerce_Utility_BackendUtility
	 */
	protected $belib;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $pObj;


	/**
	 * This is just a constructor to instanciate the backend library
	 */
	public function __construct() {
		$this->belib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Utility_BackendUtility');
	}


	/**
	 * This hook is processed Before a commandmap is processed (delete, etc.)
	 * Do Nothing if the command is lokalize an table is article
	 *
	 * @param string $command
	 * @param string $table Table the data will be stored in
	 * @param int $id The uid of the dataset we're working on
	 * @param mixed $value
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
	 *
	 * @return void
	 */
	public function processCmdmap_preProcess(&$command, $table, &$id, $value, $pObj) {
		$this->pObj = $pObj;

		switch ($table) {
			case 'tx_commerce_categories':
				$this->preProcessCategory($command, $id);
				break;

			case 'tx_commerce_products':
				$this->preProcessProduct($command, $id);
				break;

			case 'tx_commerce_articles':
				$this->preProcessArticle($command, $id);
				break;

			default:
		}
	}

	/**
	 * Preprocess category
	 *
	 * @param string $command
	 * @param int $categoryUid
	 *
	 * @return void
	 */
	protected function preProcessCategory(&$command, &$categoryUid) {
		if ($command == 'delete') {
			/** @var Tx_Commerce_Domain_Model_Category $category */
			$category = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $categoryUid);
			$category->loadData();

			// check if category is a translation and get l18n parent for access rights
			if ($category->getL18nParent()) {
				/** @var Tx_Commerce_Domain_Model_Category $category */
				$category = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Category', $category->getL18nParent());
			}

			// get mounted categories of user to check if current category is child of these
			/** @var Tx_Commerce_Tree_CategoryMounts $mounts */
			$mounts = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Tree_CategoryMounts');
			$mounts->init($GLOBALS['BE_USER']->user['uid']);

			if (!$category->isPermissionSet($command) || !$mounts->isInCommerceMounts($category->getUid())) {
				// Log the error
				$this->pObj->log(
					'tx_commerce_categories', $categoryUid, 3, 0, 1,
					'Attempt to ' . $command . ' record without ' . $command . '-permissions'
				);
				// Set id to 0 (reference!) to prevent delete of the record
				$categoryUid = 0;
			}
		}
	}

	/**
	 * Preprocess product
	 *
	 * @param string $command
	 * @param int $productUid
	 *
	 * @return void
	 */
	protected function preProcessProduct(&$command, &$productUid) {
		$backendUser = $this->getBackendUser();

		if ($command == 'localize') {
			// check if product has articles
			if ($this->belib->getArticlesOfProduct($productUid) == FALSE) {
				// Error output, no articles
				$command = '';
				$this->error('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:product.localization_without_article');
			}

			// Write to session that we copy
			// this is used by the hook to the datamap class to figure out if it should
			// check if the categories-field is filled - since it is mergeIfNotBlank, it
			// would always be empty so far this is the best (though not very clean) way
			// to solve the issue we get when localizing a product
			$backendUser->uc['txcommerce_copyProcess'] = 1;
			$backendUser->writeUC();
		} elseif ($command == 'delete') {
			/** @var Tx_Commerce_Domain_Model_Product $item */
			$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $productUid);

			// check if product or if translated the translation parent category
			if (!current($product->getParentCategories())) {
				/** @var Tx_Commerce_Domain_Model_Product $product */
				$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $product->getL18nParent());
			}

			// check existing categories
			if (!Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(
				$product->getParentCategories(), array('editcontent')
			)
			) {
				// Log the error
				$this->pObj->log(
					'tx_commerce_products', $productUid, 3, 0, 1,
					'Attempt to ' . $command . ' record without ' . $command . '-permissions'
				);
				// Set id to 0 (reference!) to prevent delete of the record
				$productUid = 0;
			}
		}
	}

	/**
	 * Proprocess article
	 *
	 * @param string $command
	 * @param int $articleUid
	 *
	 * @return void
	 */
	protected function preProcessArticle(&$command, &$articleUid) {
		if ($command == 'localize') {
			$command = '';
			$this->error('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:article.localization');
		} elseif ($command == 'delete') {
			/** @var Tx_Commerce_Domain_Model_Article $article */
			$article = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Article', $articleUid);
			$article->loadData();

			/** @var Tx_Commerce_Domain_Model_Product $product */
			$product = $article->getParentProduct();

			// check if product or if translated the translation parent category
			if (!current($product->getParentCategories())) {
				/** @var Tx_Commerce_Domain_Model_Product $product */
				$product = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Commerce_Domain_Model_Product', $product->getL18nParent());
			}

			if (!Tx_Commerce_Utility_BackendUtility::checkPermissionsOnCategoryContent(
				$product->getParentCategories(), array('editcontent')
			)
			) {
				// Log the error
				$this->pObj->log(
					'tx_commerce_articles', $articleUid, 3, 0, 1,
					'Attempt to ' . $command . ' record without ' . $command . '-permissions'
				);
				// Set id to 0 (reference!) to prevent delete of the record
				$articleUid = 0;
			}
		}
	}


	/**
	 * This hook is processed AFTER a commandmap is processed (delete, etc.)
	 * Calculation of missing price
	 *
	 * @param string $command
	 * @param string $table Table the data will be stored in
	 * @param int $id The uid of the dataset we're working on
	 * @param int $value
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj The instance of the BE data handler
	 *
	 * @return void
	 */
	public function processCmdmap_postProcess(&$command, $table, $id, $value, $pObj) {
		$this->pObj = $pObj;

		switch ($table) {
			case 'tx_commerce_categories':
				$this->postProcessCategory($command, $id);
				break;

			case 'tx_commerce_products':
				$this->postProcessProduct($command, $id, $value);
				break;

			default:
		}
	}

	/**
	 * Postprocess category
	 *
	 * @param string $command
	 * @param int $categoryUid
	 * @return void
	 */
	protected function postProcessCategory($command, $categoryUid) {
		if ($command == 'delete') {
			$this->deleteChildCategoriesProductsArticlesPricesOfCategory($categoryUid);
		} elseif ($command == 'copy') {
			$newCategoryUid = $this->pObj->copyMappingArray['tx_commerce_categories'][$categoryUid];
			$locale = $this->getLocale();

			$this->belib->copyCategoriesByCategory($newCategoryUid, $categoryUid, $locale);
			$this->belib->copyProductsByCategory($newCategoryUid, $categoryUid, $locale);
		}
	}

	/**
	 * Postprocess product
	 *
	 * @param string &$command
	 * @param int $productUid
	 * @param int $value
	 * @return void
	 */
	protected function postProcessProduct(&$command, $productUid, $value) {
		if ($command == 'localize') {
			$this->translateArticlesAndAttributesOfProduct($productUid, $value);
		} elseif ($command == 'delete') {
			$this->deleteArticlesAndPricesOfProduct($productUid);
			$this->deleteProductTranslationsByProductList(array($productUid));
		} elseif ($command == 'copy') {
			$newProductUid = $this->pObj->copyMappingArray['tx_commerce_products'][$productUid];

			$this->changeCategoryOfCopiedProduct($newProductUid);
			$this->copyProductTanslations($productUid, $newProductUid);
			$this->belib->copyArticlesByProduct($newProductUid, $productUid);
		}
	}


	/**
	 * Localize all articles that are related to the current product
	 * and localize all product attributes realted to this product from
	 *
	 * @param int $productUid The uid of the dataset we're working on
	 * @param int $value
	 *
	 * @return void
	 */
	protected function translateArticlesAndAttributesOfProduct($productUid, $value) {
		// get the uid of the newly created product
		$localizedProductUid = $this->pObj->copyMappingArray['tx_commerce_products'][$productUid];
		if ($localizedProductUid == NULL) {
			$this->error('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:product.no_find_uid');
		}

		$backendUser = $this->getBackendUser();

		// copying done, clear session
		$backendUser->uc['txcommerce_copyProcess'] = 0;
		$backendUser->writeUC();

		$this->translateAttributesOfProduct($productUid, $localizedProductUid, $value);
		$this->translateArticlesOfProduct($productUid, $localizedProductUid, $value);
	}

	/**
	 * Localize attributes of product
	 *
	 * @param int $productUid
	 * @param int $localizedProductUid
	 * @param int $value
	 *
	 * @return void
	 */
	protected function translateAttributesOfProduct($productUid, $localizedProductUid, $value) {
		$database = $this->getDatabaseConnection();

		// get all related attributes
		$productAttributes = $this->belib->getAttributesForProduct($productUid, FALSE, TRUE);
		// check if localized product has attributes
		$localizedProductAttributes = $this->belib->getAttributesForProduct($localizedProductUid);

		// Check product has attrinutes and no attributes are
		// avaliable for localized version
		if ($localizedProductAttributes == FALSE && count($productAttributes)) {
			// if true
			$langIsoCode = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_language', (int) $value, 'static_lang_isocode');
			$langIdent = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('static_languages', (int) $langIsoCode['static_lang_isocode'], 'lg_typo3');
			$langIdent = strtoupper($langIdent['lg_typo3']);

			foreach ($productAttributes as $productAttribute) {
				// only if we have attributes type 4 and no valuelist
				if ($productAttribute['uid_correlationtype'] == 4 && !$productAttribute['has_valuelist'] == 1) {
					$localizedProductAttribute = $productAttribute;

					unset($localizedProductAttribute['attributeData']);
					unset($localizedProductAttribute['has_valuelist']);

					/**
					 * Decide on what to to on lokalisation, how to act
					 *
					 * @see translateAttributesOfProduct($productUid, $localizedProductUid, $value)
					 * attributeLokalisationType[0|1|2]
					 * 0: set blank
					 * 1: Copy
					 * 2: prepend [Translate to . $langRec['title'] . :]
					 */
					if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['attributeLokalisationType'])
						&& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['attributeLokalisationType']
					) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
							'
														extension configuration parameter
														attributeLokalisationType
														is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
														attributeLocalizationType
													'
						);

						$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['attributeLocalizationType'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['attributeLokalisationType'];
					}

					switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['extConf']['attributeLocalizationType']) {
						case self::ATTRIBUTE_LOCALIZATION_TITLE_EMPTY:
							unset($localizedProductAttribute['default_value']);
							break;

						case self::ATTRIBUTE_LOCALIZATION_TITLE_COPY:
							break;

						case self::ATTRIBUTE_LOCALIZATION_TITLE_PREPENDED:
							/**
							 * Walk through the array and prepend text
							 */
							$prepend = '[Translate to ' . $langIdent . ':] ';
							$localizedProductAttribute['default_value'] = $prepend . $localizedProductAttribute['default_value'];
							break;

						default:
					}
					$localizedProductAttribute['uid_local'] = $localizedProductUid;

					$database->exec_INSERTquery('tx_commerce_products_attributes_mm', $localizedProductAttribute);
				}
			}

			/**
			 * Update the flexform
			 */
			$resProduct = $database->exec_SELECTquery('attributesedit,attributes', 'tx_commerce_products', 'uid =' . $productUid);
			if (($rowProduct = $database->sql_fetch_assoc($resProduct))) {
				$product['attributesedit'] = $this->belib->buildLocalisedAttributeValues(
					$rowProduct['attributesedit'], $langIdent
				);
				$database->exec_UPDATEquery('tx_commerce_products', 'uid = ' . $localizedProductUid, $product);
			}
		}
	}

	/**
	 * Localize articles of product
	 *
	 * @param int $productUid
	 * @param int $localizedProductUid
	 * @param int $value
	 *
	 * @return void
	 */
	protected function translateArticlesOfProduct($productUid, $localizedProductUid, $value) {
		$database = $this->getDatabaseConnection();

		// get articles of localized Product
		$localizedProductArticles = $this->belib->getArticlesOfProduct($localizedProductUid);
		// get all related articles
		$articles = $this->belib->getArticlesOfProduct($productUid);
		if (!count($articles)) {
			// Error Output, no Articles
			$this->error('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:product.localization_without_article');
		}

		// Check if product has articles and localized product has no articles
		if (count($articles) && !count($localizedProductArticles)) {
			// determine language identifier
			// this is needed for updating the XML of the new created articles
			$langIsoCode = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_language', (int) $value, 'static_lang_isocode');
			$langIdent = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('static_languages', (int) $langIsoCode['static_lang_isocode'], 'lg_typo3');
			$langIdent = strtoupper($langIdent['lg_typo3']);
			if (empty($langIdent)) {
				$langIdent = 'DEF';
			}

			// process all existing articles and copy them
			if (is_array($articles)) {
				foreach ($articles as $origArticle) {
					// make a localization version
					$locArticle = $origArticle;
					// unset some values
					unset($locArticle['uid']);

					// set new article values
					$now = time();
					$locArticle['tstamp'] = $now;
					$locArticle['crdate'] = $now;
					$locArticle['sys_language_uid'] = $value;
					$locArticle['l18n_parent'] = $origArticle['uid'];
					$locArticle['uid_product'] = $localizedProductUid;

					// get XML for attributes
					// this has only to be changed if the language is something else than default.
					// The possibility that something else happens is very small but anyhow... ;-)
					if ($langIdent != 'DEF' && $origArticle['attributesedit']) {
						$locArticle['attributesedit'] = $this->belib->buildLocalisedAttributeValues(
							$origArticle['attributesedit'], $langIdent
						);
					}

					// create new article in DB
					$database->exec_INSERTquery('tx_commerce_articles', $locArticle);

					// get the uid of the localized article
					$locatedArticleUid = $database->sql_insert_id();

					// get all relations to attributes from the old article
					// and copy them to new article
					$res = $database->exec_SELECTquery(
						'*', 'tx_commerce_articles_article_attributes_mm',
						'uid_local = ' . (int) $origArticle['uid'] . ' AND uid_valuelist = 0'
					);
					while (($origRelation = $database->sql_fetch_assoc($res))) {
						$origRelation['uid_local'] = $locatedArticleUid;
						$database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $origRelation);
					}
				}
			}
		}
	}


	/**
	 * Get available language uids of product folder
	 *
	 * @return array
	 */
	protected function getLocale() {
		list($commercePid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Commerce', 'commerce');
		list($productPid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'commerce', $commercePid);

		$database = $this->getDatabaseConnection();

		$locale = array_keys(
			(array) $database->exec_SELECTgetRows(
				'sys_language_uid', 'pages_language_overlay', 'pid = ' . $productPid, '', '', '', 'sys_language_uid'
			)
		);

		return $locale;
	}

	/**
	 * Change category of copied product
	 *
	 * @param int $productUid
	 * @return void
	 */
	protected function changeCategoryOfCopiedProduct($productUid) {
		$pasteData = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('CB');

		/** @var \TYPO3\CMS\Backend\Clipboard\Clipboard $clipObj */
		$clipObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
		$clipObj->initializeClipboard();
		$clipObj->setCurrentPad($pasteData['pad']);

		$fromData = array_pop(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', key($clipObj->clipData[$clipObj->current]['el']), TRUE));
		$toData = array_pop(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $pasteData['paste'], TRUE));

		if ($fromData && $toData) {
			$database = $this->getDatabaseConnection();

			$database->exec_DELETEquery(
				'tx_commerce_products_categories_mm', 'uid_local = ' . $productUid . ' AND uid_foreign = ' . $fromData
			);
			$database->exec_INSERTquery(
				'tx_commerce_products_categories_mm', array(
					'uid_local' => $productUid,
					'uid_foreign' => $toData
				)
			);
		}
	}

	/**
	 * Copy product localizations
	 *
	 * @param int $oldProductUid
	 * @param int $newProductUid
	 *
	 * @return void
	 */
	protected function copyProductTanslations($oldProductUid, $newProductUid) {
		$database = $this->getDatabaseConnection();
		$backendUser = $this->getBackendUser();

		$products = $database->exec_SELECTgetRows('*', 'tx_commerce_products', 'l18n_parent = ' . $oldProductUid);

		foreach ($products as $product) {
			$oldTranslationProductUid = $product['uid'];

			/** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;

			$tcaDefaultOverride = $backendUser->getTSConfigProp('TCAdefaults');
			if (is_array($tcaDefaultOverride)) {
				$tce->setDefaultsFromUserTS($tcaDefaultOverride);
			}

			// start
			$tce->start(array(), array());

			$overrideArray = array('l18n_parent' => $newProductUid);

			$newTranslationProductUid = $tce->copyRecord(
				'tx_commerce_products', $oldTranslationProductUid, $product['pid'], 1, $overrideArray
			);

			$this->belib->copyArticlesByProduct($newTranslationProductUid, $oldTranslationProductUid);
		}
	}


	/**
	 * Delete all categories->products->articles if a category should be deleted.
	 * This one does NOT delete any relations!
	 * This is not wanted because you might want to
	 * restore deleted categories, products or articles.
	 *
	 * @param int $categoryUid
	 *
	 * @return void
	 */
	protected function deleteChildCategoriesProductsArticlesPricesOfCategory($categoryUid) {
		// we dont use Tx_Commerce_Domain_Model_Category::getChildCategoriesUidlist
		// because of performance issues
		// @todo is there realy a performance issue?
		$childCategories = array();
		$this->belib->getChildCategories($categoryUid, $childCategories, 0, 0, TRUE);

		if (count($childCategories)) {
			foreach ($childCategories as $childCategoryUid) {
				$products = $this->belib->getProductsOfCategory($childCategoryUid);

				if (count($products)) {
					$productList = array();
					foreach ($products as $product) {
						$productList[] = $product['uid_local'];

						$articles = $this->belib->getArticlesOfProduct($product['uid_local']);
						if (count($articles)) {
							$articleList = array();
							foreach ($articles as $article) {
								$articleList[] = $article['uid'];
							}

							$this->deletePricesByArticleList($articleList);
							$this->deleteArticlesByArticleList($articleList);
						}
					}

					$this->deleteProductsByProductList($productList);
					$this->deleteProductTranslationsByProductList($productList);
				}
			}

			$this->deleteCategoriesByCategoryList($childCategories);
			$this->deleteCategoryTranslationsByCategoryList($childCategories);
		}
	}

	/**
	 * If a product is deleted, delete all articles below and their locales.
	 *
	 * @param int $productUid
	 *
	 * @return void
	 */
	protected function deleteArticlesAndPricesOfProduct($productUid) {
		$articles = $this->belib->getArticlesOfProduct($productUid);
		if (count($articles)) {
			$articleList = array();
			foreach ($articles as $article) {
				$articleList[] = $article['uid'];
			}

			$this->deletePricesByArticleList($articleList);
			$this->deleteArticlesByArticleList($articleList);
		}
	}

	/**
	 * flag categories as deleted for categoryList
	 *
	 * @param array $categoryList
	 * @return void
	 */
	protected function deleteCategoriesByCategoryList($categoryList) {
		$database = $this->getDatabaseConnection();

		$updateValues = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'deleted' => 1
		);

		$database->exec_UPDATEquery('tx_commerce_categories', 'uid IN (' . implode(',', $categoryList) . ')', $updateValues);
	}

	/**
	 * flag category translations as deleted for categoryList
	 *
	 * @param array $categoryList
	 * @return void
	 */
	protected function deleteCategoryTranslationsByCategoryList($categoryList) {
		$database = $this->getDatabaseConnection();

		$updateValues = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'deleted' => 1
		);

		$database->exec_UPDATEquery(
			'tx_commerce_categories', 'l18n_parent IN (' . implode(',', $categoryList) . ')', $updateValues
		);
	}

	/**
	 * flag product as deleted for productList
	 *
	 * @param array $productList
	 * @return void
	 */
	protected function deleteProductsByProductList($productList) {
		$database = $this->getDatabaseConnection();

		$updateValues = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'deleted' => 1
		);

		$database->exec_UPDATEquery('tx_commerce_products', 'uid IN (' . implode(',', $productList) . ')', $updateValues);
	}

	/**
	 * flag product translations as deleted for productList
	 *
	 * @param array $productList
	 * @return void
	 */
	protected function deleteProductTranslationsByProductList($productList) {
		$database = $this->getDatabaseConnection();

		$updateValues = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'deleted' => 1
		);

		$database->exec_UPDATEquery('tx_commerce_products', 'l18n_parent IN (' . implode(',', $productList) . ')', $updateValues);

		$translatedArticles = array();
		foreach ($productList as $productId) {
			$translatedArticles = array_merge($translatedArticles, $this->belib->getArticlesOfProductAsUidList($productId));
		}
		$translatedArticles = array_unique($translatedArticles);

		if (count($translatedArticles)) {
			$this->deletePricesByArticleList($translatedArticles);
			$this->deleteArticlesByArticleList($translatedArticles);
		}
	}

	/**
	 * flag articles as deleted for articleList
	 *
	 * @param array $articleList
	 * @return void
	 */
	protected function deleteArticlesByArticleList($articleList) {
		$database = $this->getDatabaseConnection();

		$updateValues = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'deleted' => 1
		);

		$database->exec_UPDATEquery('tx_commerce_articles', 'uid IN (' . implode(',', $articleList) . ') OR l18n_parent IN (' .
			implode(',', $articleList) . ')', $updateValues);
	}

	/**
	 * flag prices as deleted for articleList
	 *
	 * @param array $articleList
	 * @return void
	 */
	protected function deletePricesByArticleList($articleList) {
		$database = $this->getDatabaseConnection();

		$updateValues = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'deleted' => 1
		);

		$database->exec_UPDATEquery('tx_commerce_article_prices', 'uid IN (' . implode(',', $articleList) . ')', $updateValues);
	}


	/**
	 * Prints out the error
	 *
	 * @param string $error
	 * @return void
	 */
	protected function error($error) {
		$language = $this->getLanguageService();

		/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate $errorDocument */
		$errorDocument = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$errorDocument->backPath = '';

		$errorHeadline = $language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:error', 1);
		$submitLabel = $language->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:continue', 1);
		$onClickAction = 'onclick="document.location=\'' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '\'; return false;"';

		$content = $errorDocument->startPage('Tx_Commerce_Hook_CommandMapHooks error Output');
		$content .= '
			<br/>
			<br/>
			<table>
				<tr class="bgColor5">
					<td colspan="2" align="center"><strong>' . $errorHeadline . '</strong></td>
				</tr>
				<tr class="bgColor4">
					<td valign="top">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error') . '</td>
					<td>' . $language->sL($error, 0) . '</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
					<br />
						<form action="' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '">
							<input type="submit" value="' . $submitLabel . '" ' . $onClickAction . ' />
						</form>
					</td>
				</tr>
			</table>';

		$content .= $errorDocument->endPage();
		echo $content;
		exit;
	}


	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Get language service
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}

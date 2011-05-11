<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Ingo Schmitt <is@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Basic class for handling products
 *
 * Libary for Frontend-Rendering of products. This class
 * should be used for all Frontend renderings. No database calls
 * to the commerce tables should be made directly.
 *
 * This Class is inhertited from tx_commerce_element_alib, all
 * basic database calls are made from a separate database Class
 *
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private
 *
 * @author Ingo Schmitt <is@marketing-factory.de>
 * @package TYPO3
 * @subpackage tx_commerce
 */
class tx_commerce_product extends tx_commerce_element_alib {
	/**
	 * Data Variables
	 */
	var $title = ''; // Title of the product e.g.productname (private)
	var $pid = 0;
	var $subtitle = ''; // Subtitle of the product (private)
	var $description = ''; //  product description (private)
	var $teaser = '';
	var $images = ''; // images database field (private)
	var $images_array = array(); // Images for the product (private)
	var $teaserImages = ''; // images database field (private)
	var $teaserImagesArray = array(); // Images for the product (private)
	var $articles = array(); // array of tx_commcerc_article (private)
	var $articles_uids = array(); // Array of tx_commerce_article_uid (private)
	var $attributes = array();
	var $attributes_uids = array();
	var $relatedProducts = array();
	var $relatedProduct_uids = array();
	var $relatedProducts_loaded = FALSE;

	/**
	 * @var int Maximum Articles to render for this product. Normally PHP_INT_MAX
	 */
	var $renderMaxArticles = PHP_INT_MAX;

	//Versioning
	var $t3ver_oid = 0;
	var $t3ver_id = 0;
	var $t3ver_label = '';
	var $t3ver_wsid = 0;
	var $t3ver_state = 0;
	var $t3ver_stage = 0;
	var $t3ver_tstamp = 0;

	/**
	 * @var boolean articles_loaded TRUE if artciles are loaded, so load articles can simply return with the values from the object
	 * @acces private
	 */
	 
	var $articles_loaded = FALSE;


	/**
	 * Constructor, basically calls init
	 */
	function tx_commerce_product() {
		if ((func_num_args() > 0) && (func_num_args() <= 2)) {
			$uid = func_get_arg(0);
			if (func_num_args() == 2) {
				$lang_uid = func_get_arg(1);
			} else {
				$lang_uid = 0;
			}
			return $this->init($uid, $lang_uid);
		}
	}


	/**
	 * Class initialization
	 *
	 * @param integer uid of product
	 * @param integer language uid, default 0
	 * @return boolean TRUE if initialization was successful
	 */
	function init($uid, $lang_uid = 0) {
		$uid = intval($uid);
		$lang_uid = intval($lang_uid);
		$this->database_class = 'tx_commerce_db_product';
		$this->fieldlist = array('uid', 'title', 'pid', 'subtitle', 'description', 'teaser', 'images', 'teaserimages', 'relatedpage', 'l18n_parent', 'manufacturer_uid', 't3ver_oid', 't3ver_id', 't3ver_label', 't3ver_wsid', 't3ver_stage', 't3ver_state', 't3ver_tstamp');

		if ($uid > 0) {
			$this->uid = $uid;
			$this->lang_uid = $lang_uid;
			$this->conn_db = new $this->database_class;

			$hookObjectsArr = array();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['postinit'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/lib/class.tx_commerce_product.php']['postinit'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
			foreach($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'postinit')) {
					$hookObj->postinit($this);
				}
			}

			return TRUE;
		} else {
			return FALSE;
		}
	}


	/*******************************************************************************************
	 * Public Methods
	 *******************************************************************************************/

	/**
	 * Return product title
	 *
	 * @return string Product title
	 * @access public
	 */
	function get_title() {
		return $this->title;
	}


	/**
	 * Return product pid
	 *
	 * @return integer Product pid
	 * @access public
	 */
	function get_pid() {
		return $this->pid;
	}


	/**
	 * Returns the uid of the live version of this product
	 *
	 * @return integer UID of live version of this product
	 */
	function get_t3ver_oid() {
		return $this->t3ver_oid;
	}


	/**
	 * Returns the related page of the product
	 *
	 * @return integer Related page
	 * @access public
	 */
	function getRelatedPage() {
		return $this->relatedpage;
	}


	/**
	 * Return product subtitle
	 *
	 * @return string Product subtitle
	 * @access public
	 */
	function get_subtitle() {
		return $this->subtitle;
	}


	/**
	 * Return Product description
	 *
	 * @return string Product description
	 * @access public
	 */
	function get_description() {
		return $this->description;
	}


	/**
	 * Returns the product teaser
	 *
	 * @return string Product teaser
	 * @access public
	 */
	function get_teaser() {
		return $this->teaser;
	}


	/**
	 * Returns an Array of Images
	 *
	 * @return array Images of this product
	 * @access public
	 */
	function getTeaserImages() {
		return $this->teaserImagesArray;
	}


	/**
	 * Get list of article uids
	 *
	 * @return array Article uids
	 */
	function getArticleUids() {
		return $this->articles_uids;
	}


	/**
	 * Get list of article objects
	 *
	 * @return array Article objects
	 */
	function getArticleObjects() {
		return $this->articles;
	}


	/**
	 * Get number of articles of this product
	 *
	 * @return integer Number of articles
	 */
	function getNumberOfArticles() {
		return count($this->articles);
	}


	/**
	 * Load article list of this product and store in private class variable
	 *
	 * @return array Article uids
	 */
	function load_articles() {
		if ($this->articles_loaded == FALSE) {
			$uidToLoadFrom = $this->uid;
			if (($this->get_t3ver_oid() > 0) && ($this->get_t3ver_oid() <> $this->uid) && (is_Object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->beUserLogin)) {
				$uidToLoadFrom = $this->get_t3ver_oid();
			}
			if ($this->articles_uids = $this->conn_db->get_articles($uidToLoadFrom)) {
				foreach($this->articles_uids as $article_uid) {
					// initialise Array of articles
					$this->articles[$article_uid] = t3lib_div::makeInstance('tx_commerce_article');
					$this->articles[$article_uid]->init($article_uid, $this->lang_uid);
					$this->articles[$article_uid]->load_data();
				}
				$this->articles_loaded = TRUE;
				return $this->articles_uids;
			} else {
				return FALSE;
			}
		} else {
			return $this->articles_uids;
		}
	}


	/**
	 * Load data and divide comma sparated images in array
	 * inherited from parent
	 *
	 * @param mixed Translation mode of the record, default FALSE to use the default way of translation
	 */
	function load_data($translationMode = FALSE) {
		$return = parent::load_data($translationMode);
		$this->images_array = t3lib_div::trimExplode(',', $this->images);
		$this->teaserImagesArray = t3lib_div::trimExplode(',', $this->teaserimages);

		return $return;
	}


	/**
	 * Get category master parent category
	 *
	 * @return uid of category
	 */
	function getMasterparentCategory() {
		return $this->conn_db->get_parent_categorie($this->uid);
	}


	/**
	 * Get related products
	 *
	 * @TODO Check for stock/show_with_no_stock=1 ?
	 * @return array Related product objecs
	 */
	function getRelatedProducts() {
		if (!$this->relatedProducts_loaded) {
			$this->relatedProduct_uids = $this->conn_db->get_related_product_uids($this->uid);
			if (count($this->relatedProduct_uids) > 0) {
				foreach ($this->relatedProduct_uids as $productId => $categoryId) {
					$product = t3lib_div::makeInstance('tx_commerce_product');
					$product->init($productId, $this->lang_uid);
					$product->load_data(); // TODO: is it our job to load here?
					$product->load_articles();
					// Check if the user is allowed to access the product and if the product has at least one article
					if ($product->isAccessible() && $product->getNumberOfArticles() >= 1) {
						$this->relatedProducts[] = $product;
					}
				}
			}
			$this->relatedProducts_loaded = TRUE;
		}
		
		return $this->relatedProducts;
	}


	/**
	 * Get all parent categories
	 *
	 * @return array Parent categories of product
	 */
	function getParentCategories() {
		return $this->conn_db->getParentCategories($this->uid);
	}


	/**
	 * Get l18n overlays of this product
	 *
	 * @return array l18n overlay objects
	 */
	function get_l18n_products() {
		$uid_lang = $this->conn_db->get_l18n_products($this->uid);
		return $uid_lang;
	}


	/**
	 * Get list of articles of this product filtered by given attribute UID and attribute value
	 *
	 * @TODO Move DB connector to db_product
	 * @TODO Create useful and understandable comments in english ...
	 * @param array (
	 * 			array('AttributeUid'=>$attributeUID, 'AttributeValue'=>$attributeValue),
	 * 			array('AttributeUid'=>$attributeUID, 'AttributeValue'=>$attributeValue),
	 * 		...
	 * 		)
	 * @param Proof if script is running without instance and so without a single product
	 * @return array of article uids
	 */
	function get_Articles_by_AttributeArray($attribute_Array, $proofUid = 1) {
		if ($proofUid) {
			$whereUid = ' and tx_commerce_articles.uid_product = ' . intval($this->uid);
		}

		$first = 1;

		$addwhere = '';
		if (is_array($attribute_Array)) {
			foreach($attribute_Array as $uid_val_pair) {
				// Initialize arrays to prevent warningn in array_intersect()
				$next_array = array();

				$addwheretmp = '';

					// attribute char wird noch nicht verwendet, dafuer muss eine Pruefung auf die ID
				if (is_string($uid_val_pair['AttributeValue'])) {
					$addwheretmp.= " OR (tx_commerce_attributes.uid = " . intval($uid_val_pair['AttributeUid']) . " AND tx_commerce_articles_article_attributes_mm.value_char='" . $GLOBALS['TYPO3_DB']->quoteStr($uid_val_pair['AttributeValue'], 'tx_commerce_articles_article_attributes_mm') . "' )";
				}

					// Nach dem charwert immer ueberpruefen, solange value_char noch nicht drin ist.
				if (is_float($uid_val_pair['AttributeValue']) || is_integer(intval($uid_val_pair['AttributeValue']))) {
					$addwheretmp.= " OR (tx_commerce_attributes.uid = " . intval($uid_val_pair['AttributeUid']) . " AND tx_commerce_articles_article_attributes_mm.default_value in ('" . $GLOBALS['TYPO3_DB']->quoteStr($uid_val_pair['AttributeValue'], 'tx_commerce_articles_article_attributes_mm') . "' ) )";
				}

				if (is_float($uid_val_pair['AttributeValue']) || is_integer(intval($uid_val_pair['AttributeValue']))) {
					$addwheretmp.= " OR (tx_commerce_attributes.uid = " . intval($uid_val_pair['AttributeUid']) . " AND tx_commerce_articles_article_attributes_mm.uid_valuelist in ('" . $GLOBALS['TYPO3_DB']->quoteStr($uid_val_pair['AttributeValue'], 'tx_commerce_articles_article_attributes_mm') . "') )";
				}

				$addwhere = ' AND (0 ' . $addwheretmp . ') ';

				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
					'distinct tx_commerce_articles.uid',
					'tx_commerce_articles',
					'tx_commerce_articles_article_attributes_mm',
					'tx_commerce_attributes',
					$addwhere . " AND tx_commerce_articles.hidden = 0 and tx_commerce_articles.deleted = 0" . $whereUid
				);

				if (($result) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0)) {
					while ($return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
						$next_array[] = $return_data['uid'];
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($result);
				}

					// Es sollen nur Artikel zur?ckgeliefert werden, die in allen Array's vorkommen.
					// Daher das Erste Array setzen und dann mit Array Intersect nur noch die ?bereinstimmungen
					// behalten.
				if ($first) {
					$attribute_uid_list = $next_array;
					$first = 0;
				} else {
					$attribute_uid_list = array_intersect($attribute_uid_list, $next_array);
				}
			}

			if (count($attribute_uid_list) > 0) {
				sort($attribute_uid_list);
				return $attribute_uid_list;
			} else {
				return array();
			}
		}
	}


	/**
	 * Get list of articles of this product filtered by given attribute UID and attribute value
	 *
	 * @TODO handling of valuelists
	 * @see get_Articles_by_AttributeArray()
	 * @param attribute_UID
	 * @param attribute_value
	 * @return array of article uids
	 */
	function get_Articles_by_Attribute($attributeUid, $attributeValue) {
		return $this->get_Articles_by_AttributeArray(array(array('AttributeUid' => $attributeUid, 'AttributeValue' => $attributeValue)));
	}


	/**
	 * Compare an array record by its sorting value
	 *
	 * @param array Left
	 * @param array Right
	 */
	public static function compareBySorting($array1, $array2) {
		return $array1['sorting'] - $array2['sorting'];
	}


	/**
	 * Get attribute matrix of products and articles
	 * Both products and articles have a mm relation to the attribute table
	 * This method gets the attributes of a product or an article and compiles them to an unified array of attributes
	 * This method handles the different types of values of an attribute: character values, integer values and value lists
	 *
	 * @param mixed Array of restricted product articles (usually shall, must, ...), FALSE for all, FALSE for product attribute list
	 * @param mixed Array of restricted attributes, FALSE for all
	 * @param boolean TRUE if 'showvalue' field of value list table should be cared of
	 * @param string Name of table with sorting field of table to order records
	 * @param boolean TRUE if a fallback to default value should be done if a localization of an attribute value or value char is not available in localized row
	 * @param string Name of parent table, either tx_commerce_articles or tx_commerce_products
	 * @return mixed Array if attributes where found, else FALSE
	 */
	public function getAttributeMatrix(
			$articleList = FALSE,
			$attributeListInclude = FALSE,
			$valueListShowValueInArticleProduct = TRUE,
			$sortingTable = 'tx_commerce_articles_article_attributes_mm',
			$localizationAttributeValuesFallbackToDefault = FALSE,
			$parentTable = 'tx_commerce_articles'
		) {

			// Early return if no product is given
		if (!$this->uid > 0) {
			return FALSE;
		}

		if ($parentTable == 'tx_commerce_articles') {
				// mm table for article->attribute
			$mmTable = 'tx_commerce_articles_article_attributes_mm';
		} else {
				// mm table for product->attribute
			$mmTable = 'tx_commerce_products_attributes_mm';
		}

			// Execute main query
		$attributeDataArrayRessource = $GLOBALS['TYPO3_DB']->sql_query(
			$this->getAttributeMatrixQuery(
				$parentTable,
				$mmTable,
				$sortingTable,
				$articleList,
				$attributeListInclude
			)
		);

			// Accumulated result array
		$targetDataArray = array();

			// Attributes uids are added to this array if there is no language overlay for an attribute
			// to prevent fetching of non-existing language overlays in subsequent rows for the same attribute
		$attributeLanguageOverlayBlacklist = array();

			// Compile target data array
		while ($attributeDataRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($attributeDataArrayRessource)) {
				// AttributeUid affected by this reord
			$currentAttributeUid = $attributeDataRow['attributes_uid'];

				// Don't handle this row if a prior row was already unable to fetch a language overlay of the attribute
			if ($this->lang_uid > 0 && count(array_intersect(array($currentAttributeUid), $attributeLanguageOverlayBlacklist)) > 0) {
				continue;
			}

				// Initialize array for this attribute uid and fetch attribute language overlay for localization
			if (!isset($targetDataArray[$currentAttributeUid])) {
					// Initialize target row and fill in attribute values
				$targetDataArray[$currentAttributeUid]['title'] = $attributeDataRow['attributes_title'];
				$targetDataArray[$currentAttributeUid]['unit'] = $attributeDataRow['attributes_unit'];
				$targetDataArray[$currentAttributeUid]['values'] = array();
				$targetDataArray[$currentAttributeUid]['valueuidlist'] = array();
				$targetDataArray[$currentAttributeUid]['valueformat'] = $attributeDataRow['attributes_valueformat'];
				$targetDataArray[$currentAttributeUid]['Internal_title'] = $attributeDataRow['attributes_internal_title'];
				$targetDataArray[$currentAttributeUid]['icon'] = $attributeDataRow['attributes_icon'];
				
					// Fetch language overlay of attribute if given
					// Overwrite title, unit and Internal_title (sic!) of attribute
					// @TODO: This should be refactored to some language overlay method of an attribute object
				if ($this->lang_uid > 0) {
					$overwriteValues = array();
					$overwriteValues['uid'] = $currentAttributeUid;
					$overwriteValues['pid'] = $attributeDataRow['attributes_pid'];
					$overwriteValues['sys_language_uid'] = $attributeDataRow['attritubes_sys_language_uid'];
					$overwriteValues['title'] = $attributeDataRow['attributes_title'];
					$overwriteValues['unit'] = $attributeDataRow['attributes_unit'];
					$overwriteValues['internal_title'] = $attributeDataRow['attributes_internal_title'];
					$languageOverlayRecord = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
						'tx_commerce_attributes',
						$overwriteValues,
						$this->lang_uid,
						$this->translationMode
					);
					if ($languageOverlayRecord) {
						$targetDataArray[$currentAttributeUid]['title'] = $languageOverlayRecord['title'];
						$targetDataArray[$currentAttributeUid]['unit'] = $languageOverlayRecord['unit'];
						$targetDataArray[$currentAttributeUid]['Internal_title'] = $languageOverlayRecord['internal_title'];
					} else {
							// Throw away array if there is no lang overlay, add to blacklist
						unset($targetDataArray[$currentAttributeUid]);
						$attributeLanguageOverlayBlacklist[] = $currentAttributeUid;
						continue;
					}
				} // End of language handling
			} // End of if new attribute uid

				// There is a nasty difference between article and product attributes regarding default_value field:
				// For attributes: default_value must be an integer value and string values are stored in value_char
				// For products: Everything is stored in default_value
			$defaultValue = FALSE;
			if ($parentTable == 'tx_commerce_articles') {
				if ($attributeDataRow['default_value'] > 0) {
					$defaultValue = TRUE;
				}
			} else {
				if (strlen($attributeDataRow['default_value']) > 0) {
					$defaultValue = TRUE;
				}
			}

				// Handle value, default_value and value lists of attributes
			if ((strlen($attributeDataRow['value_char']) > 0) || $defaultValue) {
					// Localization of value_char
				if ($this->lang_uid > 0) {
						// Get uid of localized article (lang_uid = selected lang and l18n_parent = current article)
						// @TODO: Add a db key on l18n_parent + sys_language_uid, it probably makes sense for this type of query
					$localizedArticleUid = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid',
						$parentTable,
						'l18n_parent=' . $attributeDataRow['parent_uid'] .
							' AND sys_language_uid=' . $this->lang_uid .
							$GLOBALS['TSFE']->sys_page->enableFields($parentTable, $GLOBALS['TSFE']->showHiddenRecords)
					);

						// Fetch the article-attribute mm record with localized article uid and current attribute
					$localizedArticleUid = (int)$localizedArticleUid[0]['uid'];
					if ($localizedArticleUid > 0) {
						$selectFields = array();
						$selectFields[] = 'default_value';
							// Again difference between product->attribute and article->attribute
						if ($parentTable == 'tx_commerce_articles') {
							$selectFields[] = 'value_char';
						}
							// Fetch mm record with overlay values
						$localizedArticleAttributeValues = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
							implode(', ', $selectFields),
							$mmTable,
							'uid_local=' . $localizedArticleUid .
								' AND uid_foreign=' . $currentAttributeUid
						);
							// Use value_char if set, else check for default_value, else use non localized value if enabled fallback
						if (strlen($localizedArticleAttributeValues[0]['value_char']) > 0) {
							$targetDataArray[$currentAttributeUid]['values'][] = $localizedArticleAttributeValues[0]['value_char'];
						} else if (strlen($localizedArticleAttributeValues[0]['default_value']) > 0) {
							$targetDataArray[$currentAttributeUid]['values'][] = $localizedArticleAttributeValues[0]['default_value'];
						} else if ($localizationAttributeValuesFallbackToDefault) {
							$targetDataArray[$currentAttributeUid]['values'][] = $attributeDataRow['value_char'];
						}
					} // End of localization handling
				} else { // Not localized record
						// Use value_char if set, else default_value
					if (strlen($attributeDataRow['value_char']) > 0) {
						$targetDataArray[$currentAttributeUid]['values'][] = $attributeDataRow['value_char'];
					} else {
						$targetDataArray[$currentAttributeUid]['values'][] = $attributeDataRow['default_value'];
					}
				}
			} else if ($attributeDataRow['uid_valuelist']) {
					// Get value list rows
				$valueListArrayRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'tx_commerce_attribute_values',
					'uid IN (' . $attributeDataRow['uid_valuelist'] . ')'
				);
				foreach ($valueListArrayRows as $valueListArrayRowNumber => $valueListArrayRow) {
						// Ignore row if this value list has already been calculated
						// This might happen if method is called with multiple article uid's
					if (count(array_intersect(array($valueListArrayRow['uid']), $targetDataArray[$currentAttributeUid]['valueuidlist'])) > 0) {
						continue;
					}

						// Value lists must be localized. So overwrite current row with localization record
						// @TODO: This doesn't seem to be very clever and is just a re-implementation of the original matrix method
					if ($this->lang_uid > 0) {
						$valueListArrayRow = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
							'tx_commerce_attribute_values',
							$valueListArrayRow,
							$this->lang_uid,
							$this->translationMode
						);
					}
					if (!$valueListArrayRow) {
							// @TODO: There is probably a bug with this: An attribute value should be
							// unset if no value list had a language overlay
							// This is a bug re-implementation from the original matrix method!
						continue;
					}
					
						// Add value list row to target array
					if ($valueListShowValueInArticleProduct || $valueListArrayRow['showvalue'] == 1) {
						$targetDataArray[$currentAttributeUid]['values'][] = $valueListArrayRow;
						$targetDataArray[$currentAttributeUid]['valueuidlist'][] = $valueListArrayRow['uid'];
					}
				} // End of value list iteration
			} // End of attribute value list handling

		} // End of while fetch mm rows

			// Free resources of main query
		$GLOBALS['TYPO3_DB']->sql_free_result($attributeDataArrayRessource);

			// Return "I didn't found anything, so I'm not an array"
			// This hack is a re-implementation of the original matrix behaviour
		if (count($targetDataArray) == 0) {
			return FALSE;
		}

			// Sort value lists by sorting value
		foreach ($targetDataArray as $attributeUid => $attributeValues) {
			if (count($attributeValues['valueuidlist']) > 1) {
					// compareBySorting is a special callback function to order the array by its sorting value
				usort($targetDataArray[$attributeUid]['values'], array('tx_commerce_product', 'compareBySorting'));

					// Sort valuelist as well to get deterministic array output
				sort($attributeValues['valueuidlist']);
				$targetDataArray[$attributeUid]['valueuidlist'] = $attributeValues['valueuidlist'];
			}
		}

		return $targetDataArray;
	}


	/**
	 * Create query to get all attributes of articles or products
	 * This is a join over three tables:
	 * 		parent table, either tx_commerce_articles or tx_commerce_producs
	 *		corresponding mm table
	 *		tx_commerce_attributes
	 *
	 * @param string Name of the parent table, either tx_commerce_articles or tx_commerce_products
	 * @param string Name of the mm table, either tx_commerce_articles_article_attributes_mm or tx_commerce_products_attributes_mm
	 * @param string Name of table with .sorting field to order records
	 * @param mixed Array of some restricted articles of this product (shall, must, ...), FALSE for all articles of product, FALSE if $parentTable = tx_commerce_products
	 * @param mixed Array of restricted attributes, FALSE for all attributes
	 * @return string Query to be executed
	 */
	protected function getAttributeMatrixQuery(
			$parentTable = 'tx_commerce_articles',
			$mmTable = 'tx_commerce_articles_article_attributes_mm',
			$sortingTable = 'tx_commerce_articles_article_attributes_mm',
			$articleList = FALSE,
			$attributeList = FALSE
		) {

		$selectFields = array();
		$selectWhere = array();

			// Distinguish differences between product->attribute and article->attribute query
		if ($parentTable == 'tx_commerce_articles') {
				// Load full article list of product if not given
			if ($articleList === FALSE) {
				$articleList = $this->load_articles();
			}
				// Get article attributes of current product only
			$selectWhere[] = $parentTable . '.uid_product = ' . $this->uid;
				// value_char is only available in article->attribute mm table
			$selectFields[] = $mmTable . '.value_char';
				// Restrict article list if given
			if (is_array($articleList) && count($articleList) > 0) {
				$selectWhere[] = $parentTable . '.uid IN (' . implode(',', $articleList) . ')';
			}
		} else {
				// Get attributes of current product only
			$selectWhere[] = $parentTable . '.uid = ' . $this->uid;
		}

		$selectFields[] = $parentTable . '.uid AS parent_uid';
		$selectFields[] = 'tx_commerce_attributes.uid AS attributes_uid';
		$selectFields[] = 'tx_commerce_attributes.pid AS attributes_pid';
		$selectFields[] = 'tx_commerce_attributes.sys_language_uid AS attributes_sys_language_uid';
		$selectFields[] = 'tx_commerce_attributes.title AS attributes_title';
		$selectFields[] = 'tx_commerce_attributes.unit AS attributes_unit';
		$selectFields[] = 'tx_commerce_attributes.valueformat AS attributes_valueformat';
		$selectFields[] = 'tx_commerce_attributes.internal_title AS attributes_internal_title';
		$selectFields[] = 'tx_commerce_attributes.icon AS attributes_icon';
		$selectFields[] = $mmTable . '.default_value';
		$selectFields[] = $mmTable . '.uid_valuelist';
		$selectFields[] = $sortingTable . '.sorting';

		$selectFrom = array();
		$selectFrom[] = $parentTable;
		$selectFrom[] = $mmTable;
		$selectFrom[] = 'tx_commerce_attributes';

			// mm join restriction
		$selectWhere[] = $parentTable . '.uid = ' . $mmTable . '.uid_local';
		$selectWhere[] = 'tx_commerce_attributes.uid = ' . $mmTable . '.uid_foreign';

			// Restrict attribute list if given
		if (is_array($attributeList)) {
			$selectWhere[] = 'tx_commerce_attributes.uid IN (' . implode(',', $attributeList) . ')';
		}

			// Get enabled rows only
		$selectWhere[] = ' 1 ' . $GLOBALS['TSFE']->sys_page->enableFields(
			'tx_commerce_attributes',
			$GLOBALS['TSFE']->showHiddenRecords
		);
		$selectWhere[] = ' 1 ' . $GLOBALS['TSFE']->sys_page->enableFields(
			$parentTable,
			$GLOBALS['TSFE']->showHiddenRecords
		);

			// Order rows by given sorting table
		$selectOrder = $sortingTable . '.sorting';

			// Compile query
		$attributeMmQuery = $GLOBALS['TYPO3_DB']->SELECTquery(
			'DISTINCT ' . implode(', ', $selectFields),
			implode(', ', $selectFrom),
			implode(' AND ', $selectWhere),
			'',
			$selectOrder
		);

		return($attributeMmQuery);
	}




	/**
	 * Generates the matrix for attribute values for attribute select options in FE
	 *
	 * @param array of attribute->value pairs, used as default.
	 * @return array Values
	 */
	function getSelectAttributeValueMatrix(&$attributeValues = array()) {
		if ($this->uid > 0) {
			$articleList = $this->load_articles();

			$addWhere = '';
			if (is_array($articleList) && count($articleList) > 0) {
				$queryArticleList = implode(',', $articleList);
				$addWhere = 'uid_local IN (' . $queryArticleList . ')';
			}

			$articleAttributes = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid_local,uid_foreign,uid_valuelist',
				'tx_commerce_articles_article_attributes_mm',
				$addWhere,
				'',
				'uid_local,sorting'
			);

			$levels = array();
			$article = FALSE;
			$values = array();
			$levelAttributes = array();
			$attributeValuesList = array();
			$attributeValueSortIndex = array();

			foreach($articleAttributes as $articleAttribute) {
				$attributeValuesList[] = $articleAttribute['uid_valuelist'];
				if ($article != $articleAttribute['uid_local']) {
					$current = &$values;
					if (count($levels)) {
						foreach($levels as $level) {
							if (!isset($current[$level])) {
								$current[$level] = array();
							}
							$current = &$current[$level];
						}
					}
					$levels = array();
					$levelAttributes = array();
					$article = $articleAttribute['uid_local'];
				}
				$levels[] = $articleAttribute['uid_valuelist'];
				$levelAttributes[] = $articleAttribute['uid_foreign'];
			}

			$current = &$values;
			if (count($levels)) {
				foreach($levels as $level) {
					if (!isset($current[$level])) {
						$current[$level] = array();
					}
					$current = &$current[$level];
				}
			}

				// Get the sorting value for all attribute values
			if (count($attributeValuesList) > 0) {
				$attributeValuesList = array_unique($attributeValuesList);
				$attributeValuesList = implode($attributeValuesList, ',');
				$attributeValueSortQuery = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'sorting,uid',
					'tx_commerce_attribute_values',
					'uid IN (' . $attributeValuesList . ')'
				);
				while ($attributeValueSort = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($attributeValueSortQuery)) {
					$attributeValueSortIndex[$attributeValueSort['uid']] = $attributeValueSort['sorting'];
				}
			}
		} // End of if product uid

		$selectMatrix = array();
		$possible = $values;
		$impossible = array();

		foreach($levelAttributes as $kV) {
			$tImpossible = array();
			$tPossible = array();
			$selected = $attributeValues[$kV];
			if (!$selected) {
				$attributeObj = t3lib_div::makeInstance('tx_commerce_attribute');
				$attributeObj->init($kV, $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']);
				$attributeObj->load_data();
				$attributeValues[$kV] = $selected = $attributeObj->getFirstAttributeValueUid($possible);
			}

			foreach($impossible as $key => $val) {
				$selectMatrix[$kV][$key] = 'disabled';
				foreach($val as $k => $v) {
					$tImpossible[$k] = $v;
				}
			}

			foreach($possible as $key => $val) {
				$selectMatrix[$kV][$key] = $selected == $key ? 'selected' : 'possible';
				foreach($val as $k => $v) {
					if (!$selected || $key == $selected) {
						$tPossible[$k] = $v;
					} else {
						$tImpossible[$k] = $v;
					}
				}
			}

			$possible = $tPossible;
			$impossible = $tImpossible;
		}

		return $selectMatrix;
	}


	/**
	 * Generates a Matrix from these concerning articles for all attributes and the values therefor
	 *
	 * @TODO Split DB connects to db_class
	 * @param mixed Uids of articles or FALSE
	 * @param mixed Array of attribute uids to include or FALSE for all attributes
	 * @param boolean Wether or net hidden values should be shown
	 * @param string Default order by of attributes
	 */
	function get_selectattribute_matrix($articleList = FALSE, $attribute_include = FALSE, $showHiddenValues = TRUE, $sortingTable = 'tx_commerce_articles_article_attributes_mm') {
		$return_array = array();

			// If no list is given, take complate arctile-list from product
		if ($this->uid > 0) {
			if ($articleList == FALSE) {
				$articleList = $this->load_articles();
			}

			if (is_array($attribute_include)) {
				if (!is_null($attribute_include[0])) {
					$addwhere.= ' AND tx_commerce_attributes.uid in (' . implode(',', $attribute_include) . ')';
				}
			}

			if (is_array($articleList) && count($articleList) > 0) {
				$query_article_list = implode(',', $articleList);
				$addwhere2 = ' AND tx_commerce_articles.uid in (' . $query_article_list . ')';
			}

			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'distinct tx_commerce_attributes.uid,tx_commerce_attributes.sys_language_uid,tx_commerce_articles.uid as article ,tx_commerce_attributes.title, tx_commerce_attributes.unit, tx_commerce_attributes.valueformat, tx_commerce_attributes.internal_title,tx_commerce_attributes.icon,tx_commerce_attributes.iconmode, ' . $sortingTable . '.sorting',
				'tx_commerce_articles',
				'tx_commerce_articles_article_attributes_mm',
				'tx_commerce_attributes',
				' AND tx_commerce_articles.uid_product = ' . $this->uid . ' ' .
					$addwhere .
					$addwhere2 .
					' order by ' . $sortingTable . '.sorting'
			);

			$addwhere = $addwhere2;

			if (($result) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0)) {
				while ($data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
						// Language overlay
					if ($this->lang_uid > 0) {
						if (is_object($GLOBALS['TSFE']->sys_page)) {
							$proofSQL = $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_attributes', $GLOBALS['TSFE']->showHiddenRecords);
						}
						$result2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'*',
							'tx_commerce_attributes',
							'uid = ' . $data['uid'] . ' ' . $proofSQL
						);

							// Result should contain only one Dataset
						if ($GLOBALS['TYPO3_DB']->sql_num_rows($result2) == 1) {
							$return_data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result2);
							$GLOBALS['TYPO3_DB']->sql_free_result($result2);
							$return_data = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attributes', $return_data, $this->lang_uid, $this->translationMode);
							if (!is_array($return_data)) {
									// No Translation possible, so next interation
								continue;
							}
						}
						$data['title'] = $return_data['title'];
						$data['unit'] = $return_data['unit'];
						$data['internal_title'] = $return_data['internal_title'];
					} // End of localization

					$valueshown = FALSE;

						// Only get select attributs, since we don't need any other in selectattribut Matrix and we need the arrayKeys in this case
						// @since 13.12.2005 Get the lokalized values from tx_commerce_articles_article_attributes_mm
						// @author Ingo Schmitt <is@marketing-factory.de>
					$valuelist = array();
					$valueUidList = array();
					$attribute_uid = $data['uid'];
					$article = $data['article'];

					$result_value = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
						'distinct tx_commerce_articles_article_attributes_mm.uid_valuelist',
						'tx_commerce_articles',
						'tx_commerce_articles_article_attributes_mm',
						'tx_commerce_attributes',
						' AND tx_commerce_articles_article_attributes_mm.uid_valuelist>0 ' .
							' AND tx_commerce_articles.uid_product = ' . $this->uid .
							" AND tx_commerce_attributes.uid=$attribute_uid" .
							$addwhere
					);
					if (($valueshown == FALSE) && ($result_value) && ($GLOBALS['TYPO3_DB']->sql_num_rows($result_value) > 0)) {
						while ($value = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result_value)) {
							if ($value['uid_valuelist'] > 0) {
								$resvalue = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
									'*',
									'tx_commerce_attribute_values',
									'uid = ' . $value['uid_valuelist']
								);
								$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resvalue);
								if ($this->lang_uid > 0) {
									$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_commerce_attribute_values', $row, $this->lang_uid, $this->translationMode);
									if (!is_array($row)) {
										continue;
									}
								}
								if (($showHiddenValues == TRUE) || (($showHiddenValues == FALSE) && ($row['showvalue'] == 1))) {
									$valuelist[$row['uid']] = $row;
									$valueshown = TRUE;
								}
							}
						}

						usort($valuelist, array('tx_commerce_product', 'compareBySorting'));
					}

					if ($valueshown == TRUE) {
						$return_array[$attribute_uid] = array(
							'title' => $data['title'],
							'unit' => $data['unit'],
							'values' => $valuelist,
							'valueformat' => $data['valueformat'],
							'Internal_title' => $data['internal_title'],
							'icon' => $data['icon'],
							'iconmode' => $data['iconmode'],
						);
					}
				}

				return $return_array;
			} // End of if query ressource

		} // End of if product uid

		return FALSE;
	}


	/**
	 * @TODO: Clean up and comment
	 */
	function getRelevantArticles($attributeArray = FALSE) {
			// First we need all possible Attribute id's (not attribute value id's)
		foreach($this->attribute as $attribute) {
			$att_is_in_array = FALSE;
			foreach($attributeArray as $attribute_temp) {
				if ($attribute_temp['AttributeUid'] == $attribute->uid) $att_is_in_array = TRUE;
			}
			if (!$att_is_in_array) $attributeArray[] = array('AttributeUid' => $attribute->uid, 'AttributeValue' => NULL);
		}

		if ($this->uid > 0 && is_array($attributeArray) && count($attributeArray)) {
			foreach($attributeArray as $key => $attr) {
				if ($attr['AttributeValue']) {
					$unionSelects[] = 'SELECT uid_local AS article_id,uid_valuelist FROM tx_commerce_articles_article_attributes_mm,tx_commerce_articles WHERE uid_local = uid AND uid_valuelist = ' . intval($attr['AttributeValue']) . ' AND tx_commerce_articles.uid_product = ' . $this->uid . ' AND uid_foreign = ' . intval($attr['AttributeUid']) . $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
				} else {
					$unionSelects[] = 'SELECT uid_local AS article_id,uid_valuelist FROM tx_commerce_articles_article_attributes_mm,tx_commerce_articles WHERE uid_local = uid AND tx_commerce_articles.uid_product = ' . $this->uid . ' AND uid_foreign = ' . intval($attr['AttributeUid']) . $GLOBALS['TSFE']->sys_page->enableFields('tx_commerce_articles', $GLOBALS['TSFE']->showHiddenRecords);
				}
			}
			if (is_array($unionSelects)) {
				$sql = ' SELECT count(article_id) AS counter, article_id FROM ( ';
				$sql.= implode(" \n UNION \n ", $unionSelects);
				$sql.= ') AS data GROUP BY article_id having COUNT(article_id) >= ' . (count($unionSelects) - 1) . '';
			}
			$res = $GLOBALS['TYPO3_DB']->sql_query($sql);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$article_uid_list[] = $row['article_id'];
			}
			return $article_uid_list;
		}
		return FALSE;
	}


	/**
	 * Returns list of articles (from this product) filtered by price
	 *
	 * @todo Move DB connector to db_product
	 * @author Franz Ripfel
	 * @param long smallest unit (e.g. cents)
	 * @param long biggest unit (e.g. cents)
	 * @param boolean Normally we check for net price, switch to gross price
	 * @param boolean If script is running without instance and so without a single product
	 * @return array of article uids
	 */
	function getArticlesByPrice($priceMin = 0, $priceMax = 0, $usePriceGrossInstead = 0, $proofUid = 1) {
			// first get all real articles, then create objects and check prices
			// do not get prices directly from DB because we need to take (price) hooks into account
		$table = 'tx_commerce_articles';
		$where = '1=1';
		if ($proofUid) {
			$where .= ' and tx_commerce_articles.uid_product = ' . $this->uid;
		}
			//todo: put correct constant here
		$where .= ' and article_type_uid=1';
		$where .= $this->cObj->enableFields($table);
		$groupBy = '';
		$orderBy = 'sorting';
		$limit = '';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, $where, $groupBy, $orderBy, $limit);
		$rawArticleUidList = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$rawArticleUidList[] = $row['uid'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

			// Run price test
		$articleUidList = array();
		foreach($rawArticleUidList as $rawArticleUid) {
			$tmpArticle = t3lib_div::makeInstance('tx_commerce_article');
			$tmpArticle->init($rawArticleUid, $this->lang_uid);
			$tmpArticle->load_data();
			$myPrice = $usePriceGrossInstead ? $tmpArticle->get_price_gross() : $tmpArticle->get_price_net();
			if (($priceMin <= $myPrice) && ($myPrice <= $priceMax)) {
				$articleUidList[] = $tmpArticle->get_uid();
			}
		}

		if (count($articleUidList) > 0) {
			return $articleUidList;
		} else {
			return FALSE;
		}
	}


	/**
	 * Evaluates the cheapest article for current product by gross price
	 *
	 * @author Franz Ripfel
	 * @param boolean If true, Compare prices by net instead of gross
	 * @return article id, FALSE if no article
	 */
	function GetCheapestArticle($usePriceNet = 0) {
		$this->load_articles();
		$priceArr = array();
		if (!is_array($this->articles_uids)) {
			return FALSE;
		}

		for ($j = 0;$j < count($this->articles_uids);$j++) {
			if (is_object($this->articles[$this->articles_uids[$j]]) && ($this->articles[$this->articles_uids[$j]] instanceof tx_commerce_article)) {
				$priceArr[$this->articles[$this->articles_uids[$j]]->get_uid() ] = ($usePriceNet) ? $this->articles[$this->articles_uids[$j]]->get_price_net() : $this->articles[$this->articles_uids[$j]]->get_price_gross();
			}
		}

		asort($priceArr);
		reset($priceArr);
		foreach($priceArr as $key => $value) {
			return $key;
		}
	}


	/**
	 * Get manufacturer UID of the product if set
	 *
	 * @author Joerg Sprung <jsp@marketing-factory.de>
	 * @return integer UID of manufacturer
	 */
	function getManufacturerUid() {
		if (isset($this->manufacturer_uid)) {
			return $this->manufacturer_uid;
		}
		return FALSE;
	}


	/**
	 * Get manufacturer title
	 *
	 * @return string manufacturer title
	 */
	function getManufacturerTitle() {
		if ($this->getManufacturerUid()) {
			return $this->conn_db->getManufacturerTitle($this->getManufacturerUid());
		}
	}


	/**
	 * Returns TRUE if one Article of Product have more than
	 * null articles on stock
	 *
	 * @return boolean TRUE if one article of product has stock > 0
	 */
	function hasStock() {
		$this->load_articles();
		foreach($this->articles as $articleObj) {
			if ($articleObj->getStock() > 0) {
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * Carries out the move of the product to the new parent
	 * Permissions are NOT checked, this MUST be done beforehanda
	 *
	 * @param integer uid of the move target
	 * @param string Operation of move (can be 'after' or 'into'
	 * @return boolean True on success
	 */
	function move($uid, $op = 'after') {
		if ('into' == $op) {
				// Uid is a future parent
			$parent_uid = $uid;
		} else {
			return FALSE;
		}

			// Update parent_category
		$set = $this->conn_db->updateRecord($this->uid, array('categories' => $parent_uid));

			// Update relations only, if parent_category was successfully set
		if ($set) {
			$catList = array($parent_uid);
			$catList = tx_commerce_belib::getUidListFromList($catList);
			$catList = tx_commerce_belib::extractFieldArray($catList, 'uid_foreign', TRUE);
			tx_commerce_belib::saveRelations($this->uid, $catList, 'tx_commerce_products_categories_mm', TRUE);
		} else {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Sets renderMaxArticles Value in the Object
	 *
	 * @param integer New Value
	 * @return void
	 */
	function setRenderMaxArticles($count) {
		$this->renderMaxArticles = (int)$count;
	}


	/**
	 * Get renderMaxArticles Value in the Object
	 *
	 * @return int RenderMaxArticles
	 */
	function getRenderMaxArticles() {
		return $this->renderMaxArticles;
	}



	/*******************************************************************************************
	 * Deprecated methods
	 *******************************************************************************************/

	/**
	 * @see tx_comemrce_product::getARticleUids();
	 * @deprecated Will be removed after 2011-02-27
	 */
	function getArticles() {
		return $this->getArticleUids();
	}


	/**
	 * Returns an Array of Images
	 *
	 * @seet getImages()
	 * @deprecated Will be removed after 2011-02-27
	 */
	function get_images() {
		return $this->getImages();
	}


	/**
	 * Returns an Array of Images
	 *
	 * @return array Images of this product
	 * @access public
	 * @deprecated Will be removed after 2011-02-27
	 */
	function getImages() {
		return $this->images_array;
	}


	/**
	 * Get category master parent category
	 *
	 * @deprecated Will be removed after 2011-02-27
	 * @see getMasterparentCategory()
	 * @return uid of master parent category
	 */
	function getMasterparentCategorie() {
		return $this->getMasterparentCategory();
	}


	/**
	 * Gets the category master parent
	 *
	 * @see getMasterparentCategory()
	 * @deprecated Will be removed after 2011-02-27
	 */
	function get_masterparent_categorie() {
		return $this->getMasterparentCategory();
	}


	/**
	 * Get all parent categories
	 * @return array Uids of categories
	 *
	 * @see getParentCategories()
	 * @deprecated Will be removed after 2011-02-27, this method returns only one parent category
	 */
	function get_parent_categories() {
		return $this->conn_db->get_parent_categories($this->uid);
	}


	/**
	 * Sets a short description
	 *
	 * @deprecated Will be removed after 2011-02-27
	 */
	function set_leng_description($leng = 150) {
		$this->description = substr($this->description, 0, $leng) . '...';
	}


	/**
	 * Returns the attribute matrix
	 *
	 * @see getAttributeMatrix()
	 * @deprecated Will be removed after 2011-04-08
	 */
	function get_attribute_matrix($articleList = FALSE, $attribute_include = FALSE, $showHiddenValues = TRUE, $sortingTable = 'tx_commerce_articles_article_attributes_mm', $fallbackToDefault = FALSE) {
		return $this->getAttributeMatrix($articleList, $attribute_include, $showHiddenValues, $sortingTable, $fallbackToDefault);
	}


	/**
	 * Returns the attribute matrix
	 *
	 * @see getAttributeMatrix()
	 * @deprecated Will be removed after 2011-02-27
	 */
	function get_atrribute_matrix($articleList = FALSE, $attribute_include = FALSE, $showHiddenValues = TRUE, $sortingTable = 'tx_commerce_articles_article_attributes_mm') {
		return $this->getAttributeMatrix($articleList, $attribute_include, $showHiddenValues, $sortingTable);
	}


	/**
	 * Returns the attribute matrix
	 *
	 * @see getAttributeMatrix()
	 * @deprecated Will be removed after 2011-04-08
	 */
	function get_product_attribute_matrix($attribute_include = FALSE, $showHiddenValues = TRUE, $sortingTable = 'tx_commerce_products_attributes_mm') {	
		return $this->getAttributeMatrix(FALSE, $attribute_include, $showHiddenValues, $sortingTable, FALSE, 'tx_commerce_products');
	}


	/**
	 * Generates a Matrix fro these concerning products for all Attributes and the values therfor
	 *
	 * @see getAttributeMatrix()
	 * @deprecated Will be removed after 2011-02-27
	 */
	function get_product_atrribute_matrix($attribute_include = FALSE, $showHiddenValues = TRUE, $sortingTable = 'tx_commerce_products_attributes_mm') {
		return $this->getAttributeMatrix(FALSE, $attribute_include,  $showHiddenValues, $sortingTable, FALSE, 'tx_commerce_products');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_product.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce/lib/class.tx_commerce_product.php']);
}
?>
<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Thomas Hempel <thomas@work.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
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
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 *
 * @package TYPO3
 * @subpackage tx_commerce
 *
 * @author Thomas Hempel <thomas@work.de>
 * @author Ingo Schmitt <is@marketing-factory.de>
 */
class tx_commerce_dmhooks {

	var $belib;
	var $catList = NULL;

	/**
	 * This is just a constructor to instanciate the backend library
	 *
	 * @author Thomas Hempel <thomas@work.de>
	 */
	function tx_commerce_dmhooks()	{
		$this->belib = t3lib_div::makeInstance('tx_commerce_belib');
	}

	/**
	 * This hook is processed BEFORE a datamap is processed (save, update etc.)
	 * We use this to check if a product or category is inheriting any attributes from
	 * other categories (parents or similiar). It also removes invalid attributes from the
	 * fieldArray which is saved in the database after this method.
	 * So, if we change it here, the method "processDatamap_afterDatabaseOperations" will work
	 * with the data we maybe have modified here.
	 *
	 * @param	array		$incomingFieldArray: the array of fields that where changed in BE (passed by reference)
	 * @param	string		$table: the table the data will be stored in
	 * @param	integer		$id: The uid of the dataset we're working on
	 * @param	object		$pObj: The instance of the BE Form
	 * @return	void
	 * @author Thomas Hempel <thomas@work.de>
	 * @since 6.10.2005
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 * 	Calculation of missing price
	 */
	function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$pObj)	{
		
			// check if we have to do something with a really fancy if-statement :-D	
		if (
			!(
			($table == 'tx_commerce_articles' &&
				(isset($incomingFieldArray['attributesedit']) ||
				isset($incomingFieldArray['prices']) ||
				isset($incomingFieldArray['create_new_price']))) ||
			(($table == 'tx_commerce_products' || $table == 'tx_commerce_categories') &&
				isset($incomingFieldArray['attributes']))
				||
			(($table == 'tx_commerce_orders' || $table == 'tx_commerce_order_articles') )
		) ||
				// don't try ro save anything, if the dataset was just created
			strtolower(substr($id, 0, 3)) == 'new'
		) {
			return;	
		}
		$handleAttributes = false;

		switch ($table)	{
			//remove any parent_category that has the same uid as the category we are going to save
			case 'tx_commerce_categories':
				$pcList = explode(',', $incomingFieldArray['parent_category']);
				$catList = array();

				//look on every element, and remove it if it the same as the uid of the category we're going to safe
				if (is_array($pcList))	{
					foreach ($pcList as $pcId)	{
						if ($pcId != $id)	{
							$catList[] = $pcId;
						}
					}
				}
				
				if (count($catList) > 0)	{
					$incomingFieldArray['parent_category'] = implode(',', $catList);
				} else {
					$incomingFieldArray['parent_category'] = null;
				}

				$this->catList = $this->belib->getUidListFromList($catList);
				$handleAttributes = true;
				break;
			case 'tx_commerce_products':
				$this->catList = $this->belib->getUidListFromList(explode(',', $incomingFieldArray['categories']));
				$handleAttributes = true;

				$articles = $this->belib->getArticlesOfProduct($id);
				if (is_array($articles))	{
					foreach ($articles as $article)	{
						$this->belib->updateArticleHash($article['uid']);
					}
				}
				
				
				// direct preview
				#if (isset($GLOBALS['_POST']['_savedokview_x']) &&  !$GLOBALS['BE_USER']->workspace)	{
				if (isset($GLOBALS['_POST']['_savedokview_x'])  )	{
					// if "savedokview" has been pressed and  the beUser works in the LIVE workspace open current record in single view
					$pagesTSC = t3lib_BEfunc::getPagesTSconfig($GLOBALS['_POST']['popViewId']); // get page TSconfig
					if ($pagesTSC['tx_commerce.']['singlePid']) {
						$previewPageID = $pagesTSC['tx_commerce.']['singlePid'];
					}else{
						$previewPageID = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['previewPageID'];
					}
					#debug($previewPageID,'$previewPageID',__LINE__,__FILE__);
					if ($previewPageID > 0) {
						// Get Parent CAT UID
						$productObj = t3lib_div::makeInstance('tx_commerce_product');
						$productObj -> init($id,sys_language_uid);
						$productObj ->load_data();
						$parentCateory = $productObj->getMasterparentCategory();
						$GLOBALS['_POST']['popViewId_addParams'] = ($fieldArray['sys_language_uid']>0?'&L='.$fieldArray['sys_language_uid']:'').
						'&ADMCMD_vPrev&no_cache=1&tx_commerce[showUid]='.$id.
						'&tx_commerce[catUid]='.$parentCateory;
						$GLOBALS['_POST']['popViewId'] = $previewPageID;
					}
					#debug(t3lib_div::_GP('popViewId_addParams'),__FUNCTION__);
	
				}
				
					
				break;
			case 'tx_commerce_articles':
			
					// update article attribute relations
				if (isset($incomingFieldArray['attributesedit']))	{
						// get the data from the flexForm
					$attributes = $incomingFieldArray['attributesedit']['data']['sDEF']['lDEF'];
			
					/**
					 * @since 13.12.2005 / 03.01.2006 Fixed Bug in passing Variables to getUidFromKey Ingo Schmitt <is@marketing-factory.de>
					 */
					foreach ($attributes as $aKey => $aValue)	{
						$value= $aValue['vDEF'];
						$aUid = $this->belib->getUidFromKey($aKey, $aValue);
						$attributeData = $this->belib->getAttributeData($aUid, 'has_valuelist,multiple,sorting');
						
						if ($attributeData['multiple'] == 1)	{
							
							$relations = explode(',', $value);
							$GLOBALS['TYPO3_DB']->exec_DELETEquery(
								'tx_commerce_articles_article_attributes_mm',
								'uid_local=' .$id .' AND uid_foreign=' .$aUid
							);
							$relCount = 0;
							foreach ($relations as $relation)	{
								if (empty($relation) || $attributeData == 0)	continue;

								$updateArrays = $this->belib->getUpdateData($attributeData,$relation);

									// update article attribute relation
								$GLOBALS['TYPO3_DB']->exec_INSERTquery(
									'tx_commerce_articles_article_attributes_mm',
									array_merge(
										array(
											'uid_local' => $id,
											'uid_foreign' => $aUid,
											'sorting' => $attributeData['sorting']
										),
										$updateArrays[1]
									)
								);
								$relCount++;
							}
								// insert at least one relation
							if ($relCount == 0)	{
								$GLOBALS['TYPO3_DB']->exec_INSERTquery(
									'tx_commerce_articles_article_attributes_mm',
									array(
										'uid_local' => $id,
										'uid_foreign' => $aUid,
										'sorting' => $attributeData['sorting']
									)
								);
							}
						} else {

							$updateArrays = $this->belib->getUpdateData($attributeData,$value);

								// update article attribute relation
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
								'tx_commerce_articles_article_attributes_mm',
								'uid_local=' .$id .' AND ' .
								'uid_foreign=' .$aUid,
								$updateArrays[1]
							);
						}
						// recalculate hash for this article
						 $this->belib->updateArticleHash($id);
					}
				}

					// create a new price if the checkbox was toggled get pid of article
				$create_new_scale_prices_count=is_numeric($incomingFieldArray['create_new_scale_prices_count'])?intval($incomingFieldArray['create_new_scale_prices_count']):0;
				$create_new_scale_prices_steps=is_numeric($incomingFieldArray['create_new_scale_prices_steps'])?intval($incomingFieldArray['create_new_scale_prices_steps']):0;
				$create_new_scale_prices_startamount=is_numeric($incomingFieldArray['create_new_scale_prices_startamount'])?intval($incomingFieldArray['create_new_scale_prices_startamount']):0;

				if ($create_new_scale_prices_count>0 && $create_new_scale_prices_steps>0 && $create_new_scale_prices_startamount>0)	{
					    // somehow hook is used two times sometime. So switch off new creating.
				    $incomingFieldArray['create_new_scale_prices_count'] = 0;

						// get pid
					list($modPid,$defaultFolder,$folderList) = tx_commerce_folder_db::initFolders('Commerce', 'commerce');
					list($prodPid,$defaultFolder,$folderList) = tx_commerce_folder_db::initFolders('Products', 'commerce',$modPid);


					$aPid = $prodPid;

						// set some status vars
					$time = time();
					$myScaleAmountStart=$create_new_scale_prices_startamount;
					$myScaleAmountEnd=$create_new_scale_prices_startamount+$create_new_scale_prices_steps-1;

						// create the different prices
					for($myScaleCounter=1;$myScaleCounter<=$create_new_scale_prices_count;$myScaleCounter++){
						$insertArr=array(
								'pid' => $aPid,
								'tstamp' => $time,
								'crdate' => $time,
								'uid_article' => $id,
								'fe_group'=> $incomingFieldArray['create_new_scale_prices_fe_group'],
								'price_scale_amount_start'=>$myScaleAmountStart,
								'price_scale_amount_end'=>$myScaleAmountEnd,
						);
						
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_article_prices',$insertArr);

						// TODO: update artciles XML

						$myScaleAmountStart+=$create_new_scale_prices_steps;
						$myScaleAmountEnd+=$create_new_scale_prices_steps;
					}
				}
				break;
			case 'tx_commerce_orders':
				$this->doNotChangeCrdate($status, $table, $id, $incomingFieldArray, $pObj);
 				$this->moveOrders($status, $table, $id, $incomingFieldArray, $pObj);
	 			break;
 			case 'tx_commerce_order_articles':
 				$this->recalculateOrderSum($status, $table, $id, $incomingFieldArray, $pObj);
 				break;
		}

			// This checks if the attributes in the select fields are valid or not.
			// This only works and makes sense for categories and products, and so it should
			// only be executed if the flag is set.
		if ($handleAttributes)	{
				// get all parent categories, excluding this
			$this->belib->getParentCategoriesFromList($this->catList);
			
				// get all correlation types from flexform
				// this flexform was created by dynaflex and consistes of all available CTs!
			$CTs = $incomingFieldArray['attributes']['data']['sDEF']['lDEF'];
			
			$usedAttributes = array();

			foreach ($CTs as $key =>  $data)	{
				$keyData = array();
				$ctUid = $this->belib->getUidFromKey($key, $keyData);
				if ($keyData[0] == 'ct')	{
						// get the attributes from the categories of this product
					$localAttributes = explode(',', $data['vDEF']);
					if (is_array($localAttributes))	{
						$validAttributes = array();
						foreach ($localAttributes as $localAttribute)	{
							if ($localAttribute == '')	continue;
							$attributeUid = $this->belib->getUidFromKey($localAttribute, $keyData);
							if (!$this->belib->checkArray($attributeUid, $usedAttributes, 'uid_foreign'))	{
								$validAttributes[] = $localAttribute;
								$usedAttributes[] = array('uid_foreign' => $attributeUid);
							}
						}
						$incomingFieldArray['attributes']['data']['sDEF']['lDEF'][$key]['vDEF'] = implode(',', $validAttributes);
					}
				}
			}
		}

	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cUid: ...
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$saveAnyway: ...
	 * @param	[type]		$delete: ...
	 * @param	[type]		$updateXML: ...
	 * @return	[type]		...
	 * @author Thomas Hempel <thomas@work.de>
	 */
	function saveCategoryRelations($cUid, $fieldArray = NULL, $saveAnyway = false, $delete = true, $updateXML = true)	{
			// now we have to save all attribute relations for this category and all their child categories ...
			// but only if the fieldArray has changed
		if (isset($fieldArray['attributes']) || $saveAnyway)	{
				// first of all, get all parent categories ...
			$catList = array();
			$this->belib->getParentCategories($cUid, $catList, $cUid, 0, false);

				// get all correlation types
			$ctList = $this->belib->getAllCorrelationTypes();

				// ... and their attributes
			$paList = $this->belib->getAttributesForCategoryList($catList);

				// Then extract all attributes from this category and merge it into the attribute list
			if (!empty($fieldArray['attributes']))	{
				$ffData = t3lib_div::xml2array($fieldArray['attributes']);
			} else {
				$ffData = array();
			}
			
			if(!is_array($ffData) || !is_array($ffData['data']) || !is_array($ffData['data']['sDEF'])) {
				$ffData = array();
			}
			$this->belib->mergeAttributeListFromFFData($ffData['data']['sDEF']['lDEF'], 'ct_', $ctList, $cUid, $paList);

				// get the list of uid_foreign and save relations for this category
			$uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, array('uid_correlationtype'));
			$this->belib->saveRelations($cUid, $uidList, 'tx_commerce_categories_attributes_mm', $delete, false);

				// update the XML structure if needed
			if ($updateXML) $this->belib->updateXML('attributes', 'tx_commerce_categories', $cUid, 'category', $ctList);

				// save all attributes of this category into all poroducts, that are related to it
			$products = $this->belib->getProductsOfCategory($cUid);
			if (count($products) > 0)	{
				foreach ($products as $product)	{
					$this->belib->saveRelations($product['uid_local'], $uidList, 'tx_commerce_products_attributes_mm', false, false);
					$this->belib->updateXML('attributes', 'tx_commerce_products', $product['uid_local'], 'product', $ctList);
				}
			}

				// get children of this category after this operation the childList contains all categories that are related to this category (recursively)
			$childList = array();
			$this->belib->getChildCategories($cUid, $childList, $cUid, 0, false);

			foreach ($childList as $childUid)	{
				$this->saveCategoryRelations($childUid, NULL, true, false);
			}
		}
	}

	/**
	 * Saves all relations between products and his attributes
	 *
	 * @param	integr		$pUid: The UID of the product
	 * @param	array		$fieldArray:
	 *
	 * @author Thomas Hempel <thomas@work.de>
	 */
	function saveProductRelations($pUid, $fieldArray = NULL)	{
		// first step is to save all relations between this product and all attributes of this product.
		// We don't have to check for any parent categories, because the attributes from them should already be saved for this product.

		// create an article and a new price for a new product
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['simpleMode'] && $pUid != NULL)	{
		
				// search for an article of this product
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_articles', 'uid_product=' .intval($pUid), '', '', 1);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0)	{
					// create a new article if no one exists
				$pRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title', 'tx_commerce_products', 'uid=' .intval($pUid), '', '', 1);
				$productData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($pRes);

				$aRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_commerce_articles',
					array(
						'pid' => $fieldArray['pid'],
						'tstamp' => time(),
						'crdate' => time(),
						'uid_product' => $pUid,
						'article_type_uid' => 1,
						'title' => $productData['title']
					)
				);
				$aUid = $GLOBALS['TYPO3_DB']->sql_insert_id($aRes);
			} else {
				$aRes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$aUid = $aRes['uid'];
			}

				// check if the article has already a price
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_article_prices', 'uid_article=' .intval($aUid), '', '', 1);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0 && $aRes['sys_language_uid']<1)	{
					// create a new price if no one exists
				$pRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_article_prices', array('pid' => $fieldArray['pid'],'uid_article' => $aUid, 'tstamp' => time(),'crdate' => time()));
			}
		}

		$delete = true;
		if (isset($fieldArray['categories']))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign', 'tx_commerce_products_categories_mm', 'uid_local=' .intval($pUid));
			while ($sres = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) $catList[] = $sres['uid_foreign'];
			$paList = $this->belib->getAttributesForCategoryList($catList);
			$uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, array('uid_correlationtype'));

			$this->belib->saveRelations($pUid, $uidList, 'tx_commerce_products_attributes_mm', false, false);
			$this->belib->updateXML('attributes', 'tx_commerce_products', $pUid, 'product', $ctList);
			$delete = false;
		}

		if (isset($fieldArray['attributes'])) {
				// get all correlation types
			$ctList = $this->belib->getAllCorrelationTypes();
			$paList = array();

				// extract all attributes from FlexForm
			$ffData = t3lib_div::xml2array($fieldArray['attributes']);
			if (is_array($ffData)) {
				$this->belib->mergeAttributeListFromFFData($ffData['data']['sDEF']['lDEF'], 'ct_', $ctList, $pUid, $paList);
			}
				// get the list of uid_foreign and save relations for this category
			$uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, array('uid_correlationtype'));

 				// get all ct4 attributes
 			$ct4Attributes = array();
			if (is_array($uidList))	{
				foreach ($uidList as $uidItem)	{
					if ($uidItem['uid_correlationtype'] == 4) $ct4Attributes[] = $uidItem['uid_foreign'];
				}
			}

			$this->belib->saveRelations($pUid, $uidList, 'tx_commerce_products_attributes_mm', $delete, false);
			
			/**
			 * Rebuild the XML (last param set to true)
			 * Fixes that l10n of products had invalid XML attributes
			 */
			$this->belib->updateXML('attributes', 'tx_commerce_products', $pUid, 'product', $ctList, true);
			
				// update the XML for this product, we remove everything that is not set for current attributes
			$pXML = $GLOBALS['TYPO3_DB']->exec_SELECTquery('attributesedit', 'tx_commerce_products', 'uid=' .intval($pUid));
			$pXML = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($pXML);

			if (!empty($pXML['attributesedit']))	{
				$pXML = t3lib_div::xml2array($pXML['attributesedit']);

				if (is_array($pXML['data']['sDEF']['lDEF']))	{
					foreach ($pXML['data']['sDEF']['lDEF'] as $key => $item)	{
						$uid = $this->belib->getUIdFromKey($key, $data);
						if (!in_array($uid, $ct4Attributes))	{
							unset($pXML['data']['sDEF']['lDEF'][$key]);
						}
					}
				}
				
				if(is_array($pXML) && is_array($pXML['data']) && is_array($pXML['data']['sDEF'])) {
					$pXML = t3lib_div::array2xml($pXML, '', 0, 'T3FlexForms');
					$fieldArray['attributesedit'] = $pXML;
				}
			}

			// now get all articles that where created from this product
			$articles = $this->belib->getArticlesOfProduct($pUid);

			// build relation table
			if (count($articles) > 0)	{
				$uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true);
				if (is_array($articles))	{
					foreach ($articles as $article)	{
						$this->belib->saveRelations($article['uid'], $uidList, 'tx_commerce_articles_article_attributes_mm', true, false);
					}
				}
			}
		}

		// update all articles of this product
		if (! empty($fieldArray['attributesedit'])) {
			if (!$ctList) $ctList = $this->belib->getAllCorrelationTypes();

			$ffData = t3lib_div::xml2array($fieldArray['attributesedit']);
			if (is_array($ffData) && is_array($ffData['data']) && is_array($ffData['data']['sDEF']['lDEF'])) {

					// get articles if they are not already there
				if (!$articles) $articles = $this->belib->getArticlesOfProduct($pUid);

					// update this product
				$articleRelations = array();
				$counter = 0;

				foreach ($ffData['data']['sDEF']['lDEF'] as $ffDataItemKey => $ffDataItem)	{
					$counter++;
					$attributeKey = $this->belib->getUidFromKey($ffDataItemKey, $keyData);
					$attributeData = $this->belib->getAttributeData($attributeKey, 'has_valuelist,multiple');

						// check if the attribute has more than one value, if that is true, we have to create a relation for each value
					if ($attributeData['multiple'] == 1)	{
							// if we have a multiple valuelist we need to handle the attributes a little bit different
							// first we delete all existing attributes
						$GLOBALS['TYPO3_DB']->exec_DELETEquery(
							'tx_commerce_products_attributes_mm', 'uid_local=' .$pUid .' AND uid_foreign=' .$attributeKey);

							// now explode the data
						$attributeValues = explode(',', $ffDataItem['vDEF']);
						$attributeCount = count($attributeValues);

						foreach ($attributeValues as $attributeValue)	{
								// The first time an attribute value is selected, TYPO3 returns them INCLUDING an empty value!
								// This would cause an unnecessary entry in the database, so we have to filter this here.
							if ($attributeCount > 1 && empty($attributeValue))	continue;

							$updateData = $this->belib->getUpdateData($attributeData, $attributeValue, $pUid);
							$GLOBALS['TYPO3_DB']->exec_INSERTquery(
								'tx_commerce_products_attributes_mm',
								array_merge(
									array (
										'uid_local' => intval($pUid),
										'uid_foreign' => intval($attributeKey),
										'uid_correlationtype' => 4,
									),
									$updateData[0]
								)
							);
						}
					} else {
							// update a simple valuelist and normal attributes as usual
						$updateArrays = $this->belib->getUpdateData($attributeData, $ffDataItem['vDEF'], $pUid);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
							'tx_commerce_products_attributes_mm',
							'uid_local=' .$pUid .' AND uid_foreign=' .$attributeKey,
							$updateArrays[0]
						);
					}

						// update articles
					if (is_array($articles) && count($articles) > 0)	{
						foreach ($articles as $article)	{
							if ($attributeData['multiple'] == 1)	{
									// if we have a multiple valuelist we need to handle the attributes a little bit different
									// first we delete all existing attributes
								$GLOBALS['TYPO3_DB']->exec_DELETEquery(
									'tx_commerce_articles_article_attributes_mm', 'uid_local=' .$article['uid'] .' AND uid_foreign=' .$attributeKey);

									// now explode the data
								$attributeValues = explode(',', $ffDataItem['vDEF']);
								$attributeCount = 0;
								foreach ($attributeValues as $attributeValue)	{
									if (empty($attributeValue))	continue;
									$attributeCount++;

									$updateData = $this->belib->getUpdateData($attributeData, $attributeValue, $pUid);
									$GLOBALS['TYPO3_DB']->exec_INSERTquery(
										'tx_commerce_articles_article_attributes_mm',
										array_merge(
											array (
												'uid_local' => $article['uid'],
												'uid_foreign' => $attributeKey,
												'uid_product' => $pUid,
												'sorting' => $counter
											),
											$updateData[1]
										)
									);
								}

									// create at least an empty relation if no attributes where set
								if ($attributeCount == 0)	{
									$updateData = $this->belib->getUpdateData(0, $attributeValue, $pUid);
									$GLOBALS['TYPO3_DB']->exec_INSERTquery(
										'tx_commerce_articles_article_attributes_mm',
										array_merge(
											array (
												'uid_local' => $article['uid'],
												'uid_foreign' => $attributeKey,
												'uid_product' => $pUid,
												'sorting' => $counter
											),
											$updateData[1]
										)
									);
								}
							} else {
									// if the article has already this attribute, we have to insert so try to select this attribute for this article
								$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
									'uid_local, uid_foreign',
									'tx_commerce_articles_article_attributes_mm',
									'uid_local=' .intval($article['uid']) .' AND uid_foreign=' .intval($attributeKey)
								);
								if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0)	{
									$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
										'tx_commerce_articles_article_attributes_mm',
										'uid_local=' .$article['uid'] .' AND uid_foreign=' .$attributeKey,
										array_merge($updateArrays[1], array('sorting' => $counter))
									);
								} else {
									$GLOBALS['TYPO3_DB']->exec_INSERTquery(
										'tx_commerce_articles_article_attributes_mm',
										array_merge ($updateArrays[1], array(
											'uid_local' => $article['uid'],
											'uid_product' => $pUid,
											'uid_foreign' => $attributeKey,
											'sorting' => $counter
										))
									);
								}
							}

							$relArray = $updateArrays[0];
							$relArray['uid_foreign'] = $attributeKey;
							if (!in_array($relArray, $articleRelations)) $articleRelations[] = $relArray;


							$this->belib->updateArticleHash($article['uid']);
						}
					}
				}
				// Finally update the Felxform for this Product
				$this->belib->updateArticleXML($articleRelations, false, NULL, $pUid);

				// And add those datas from the database to the articles
				if (is_array($articles) && count($articles) > 0)	{
						foreach ($articles as $article)	{
								$thisArticleRelations = $this->belib->getAttributesForArticle($article['uid']);

								$this->belib->updateArticleXML($thisArticleRelations, false, $article['uid'],NULL);
						}
				}
			}
		}
		// Check if we do have some localised products an call the method recursivly
		$resLocalised=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tx_commerce_products','deleted=0 and l18n_parent='.intval($pUid));
		while ($rowLocalised = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resLocalised)) {
			$this->saveProductRelations($rowLocalised['uid'], $fieldArray);

		}
	}

	/**
	 * Change FieldArray after operations have been executed and just before it is passed to the db
	 * @return {void}
	 * @param $status {string}		Status of the Datamap
	 * @param $table {string}		DB Table we are operating on
	 * @param $id {int}				UID of the Item we are operating on
	 * @param $fieldArray {array}	Array with the fields to be inserted into the db
	 * @param $pObj {object}		Reference to the BE Form Object of the caller
	 */
	function &processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj)	{
		
		switch (strtolower((string)$table))	{
			case 'tx_commerce_article_prices':
				
				/**
				 * Removed since it multiplies all prices
				 * @Since 20090134 Ingo Schmitt
				 * @ToDo: Is copy process still working?
				if(!$GLOBALS['BE_USER']->uc['txcommerce_copyProcess']) {
					$fieldArray['price_net'] = $fieldArray['price_net'] *100;
					$fieldArray['price_gross'] = $fieldArray['price_gross'] *100;
					$fieldArray['purchase_price'] = $fieldArray['purchase_price'] *100;
				}
				**/


			break;
			//Permissions <- used for recursive assignment of the persmissions in Permissions[EDIT]
			
			/**
			 * Checks if the permissions we need to process the datamap are still in place
			 */
			case 'tx_commerce_products':
				$data = $pObj->datamap[$table][$id];
				
				//Read the old parent categories
				if('new' != $status) {
					$item = t3lib_div::makeInstance('tx_commerce_product');
					$item->init($id);
					
					$parentCategories = $item->getParentCategories();
					
					//check existing categories
					if(!tx_commerce_belib::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'))) {
						$pObj->newlog('You dont have the permissions to edit the product.',1);
						$fieldArray = array();	
					}
				} else {
					
					//new products have to have a category
					//if a product is copied, we never check if it has categories - this is MANDATORY, otherwise localize will not work at all!!!
					//remove this only if you decide to not define the l10n_mode of "categories"" (products) 
					if('' == trim($fieldArray['categories']) && !isset($GLOBALS['BE_USER']->uc['txcommerce_copyProcess'])) {
						$pObj->newlog('You have to specify at least 1 parent category for the product.',1);
						$fieldArray = array();	
					} /*else if($GLOBALS['BE_USER']->uc['txcommerce_copyProcess'] && $fieldArray['sys_language_uid']) {
						//if we are in the copy process and a localization is being copied, unset the category
						unset($fieldArray['categories']);
					}*/
					
					$parentCategories = array();
				}
				
				//check new categories
				if (isset($data['categories'])) {
				    $newCats = $this->single_diff_assoc(explode(',',t3lib_div::uniqueList($data['categories'])), $parentCategories);
			         
					if(!tx_commerce_belib::checkPermissionsOnCategoryContent($newCats, array('editcontent'))) {
						$pObj->newlog('You do not have the permissions to add one or all categories you added.'.t3lib_div::uniqueList($data['categories']),1);
						$fieldArray = array();	
					}
				}
				
				if(isset($fieldArray['categories'])) {
					$fieldArray['categories'] = t3lib_div::uniqueList($fieldArray['categories']);
				}
				 
				
			break;
			
			/**
			 * Checks if the permissions we need to process the datamap are still in place
			 */
			case 'tx_commerce_articles':
				$parentCategories = array();
				
				//Read the old parent product - skip this if we are copying or overwriting the article
				if('new' != $status && !$GLOBALS['BE_USER']->uc['txcommerce_copyProcess']) {
					$item = t3lib_div::makeInstance('tx_commerce_article');
					$item->init($id);
					$productUid = $item->getParentProductUid();
		
					//get the parent categories of the product
					$item = t3lib_div::makeInstance('tx_commerce_product');
					$item->init($productUid);
					
					$parentCategories = $item->get_parent_categories();
				}
				
				// read new assigned product
				if(!tx_commerce_belib::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'))) {
					$pObj->newlog('You dont have the permissions to edit the article.',1);
					$fieldArray = array();	
				}
			break;
			
			/**
			 * Will overwrite the data because it has been removed - this is because typo3 only allows pages to have permissions so far
			 * Will also make some checks to see if all permissions are available that are needed to proceed with the datamap
			 * @author Erik Frister
			 */
			case 'tx_commerce_categories':
				//Will be called for every Category that is in the datamap - so at this time we only need to worry about the current $id item
				$data = $pObj->datamap[$table][$id];
				
				if(is_array($data)) {
					
					$locale = false;
                    $l18nParent = (isset($data['l18n_parent'])) ? $data['l18n_parent'] : 0;
					
					//check if the user has the permission to edit this category; abort if he doesnt.
					if('new' != $status)  {
					    
                        //check if we have the right to edit and are in commerce mounts
                        $checkId = $id;
                        
                        // Use the l18n parent as category for permission checks.
                        if ($l18nParent > 0) {
                            $checkId = $l18nParent;
                        }
                        
						$category = t3lib_div::makeInstance('tx_commerce_category');
						$category->init($checkId);
						
						$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
						$mounts->init($GLOBALS['BE_USER']->user['uid']);
                        
                        // check
						if(!$category->isPSet('edit') || !$mounts->isInCommerceMounts($category->getUid())) {
							$pObj->newlog('You dont have the permissions to edit this category.',1);
							$fieldArray = array();	
							break;
						}
					}	/*else if($GLOBALS['BE_USER']->uc['txcommerce_copyProcess'] && $fieldArray['sys_language_uid']) {
						//if we are in the copy process and a localization is being copied, unset the category
						unset($fieldArray['parent_category']);
						$locale = true;
					}*/
					
					//add the perms back into the field_array
					$keys = array_keys($data);
					
					for($i = 0, $l = count($keys); $i < $l; $i ++) {
						switch($keys[$i]) {
							case 'perms_userid':
							case 'perms_groupid':
							case 'perms_user':
							case 'perms_group':
							case 'perms_everybody':
								//Overwrite only the perms fields
								$fieldArray[$keys[$i]] = $data[$keys[$i]];
								break;
						}	
					}
					
					//chmod new categories for the user if the new category is not a localization
					if('new' == $status) {
						$fieldArray['perms_userid'] = $GLOBALS['BE_USER']->user['uid'];
						$fieldArray['perms_user']   = 31; //31 grants every right
					}
					
					//break if the parent_categories didn't change
					if(!isset($fieldArray['parent_category'])) break;
					
					//check if we are allowed to create new categories under the newly assigned categories
					//check if we are allowed to remove this category from the parent categories it was in before
					$existingParents = array();
					
					if ('new' != $status) {
						//if category is existing, check if it has parent categories that were deleted by a user who is not authorized to do so
						//if that is the case, add those categories back in
						$parentCategories = $category->getParentCategories();
						
						for($i = 0, $l = count($parentCategories); $i < $l; $i ++) {
							$parent = $parentCategories[$i];
							$existingParents[] = $parent->getUid();
							
							$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
							$mounts->init($GLOBALS['BE_USER']->user['uid']);
							
							//Add parent to list if the user has no show right on it or it is not in the user's mountpoints
							if(!$parent->isPSet('read') || !$mounts->isInCommerceMounts($parent->getUid())) {
								$fieldArray['parent_category'] .= ','.$parent->getUid();
							}
						}
					}
					
					//Unique the list
					$fieldArray['parent_category'] = t3lib_div::uniqueList($fieldArray['parent_category']);
					
					//abort if the user didn't assign a category - rights need not be checked then
					if('' == $fieldArray['parent_category']) {
						
						$root = t3lib_div::makeInstance('tx_commerce_category');
						$root->init(0);
						
						$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
						$mounts->init($GLOBALS['BE_USER']->user['uid']);
						
						if($mounts->isInCommerceMounts(0)) {
							$fieldArray['parent_category'] = 0; //assign the root as the parent category if it is empty
						} else {
							$pObj->newlog('You have to assign a category as a parent category.',1);
							$fieldArray = array();	
						}
						break;
					}
					
					//Check if any parent_category has been set that is not allowed because no child-records are to be set beneath it
					$newParents = array_diff(explode(',',$fieldArray['parent_category']), $existingParents); //Only on parents that were newly added
					
					$keys = array_keys($newParents); //work with keys because array_diff does not start with key 0 but keeps the old keys - that means gaps could exist
					$l 	  = count($keys);
					
					if(0 < $l) {
						$groupRights = false;
						$groupId	 = 0;
						
						for($i = 0; $i < $l; $i ++) {
							$uid = $newParents[$keys[$i]];
							//empty string replace with 0
							$uid = ('' == $uid) ? 0 : $uid;
							
							$cat = t3lib_div::makeInstance('tx_commerce_category');
							$cat->init($uid);
							
							$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
							$mounts->init($GLOBALS['BE_USER']->user['uid']);
							
							//abort if the parent category is not in the webmounts
							if(!$mounts->isInCommerceMounts($uid)) {
								$fieldArray['parent_category'] = '';
								break;	
							}
							
							//skip the root for permission check - if it is in mounts, it is allowed
							if(0 == $uid) continue;
							
							$cat->load_perms();
							
							//remove category from list if it is not permitted
							if(!$cat->isPSet('new')) {
								$fieldArray['parent_category'] = t3lib_div::rmFromList($uid, $fieldArray['parent_category']);
							} else {
								$groupRights = (false === $groupRights) ? (int)$cat->getPermsGroup() : ($groupRights & (int)$cat->getPermsGroup()); //conversion to int is important, otherwise the binary & will not work properly
								$groupId     = $cat->getPermsGroupId();
							}
						}
						
						//set the group id and permissions for a new record
						if('new' == $status) {
							$fieldArray['perms_group']   = $groupRights;
							$fieldArray['perms_groupid'] = $groupId;
						}
					}
					
					//if there is no parent_category left from the ones the user wanted to add, abort and inform him.
					if('' == $fieldArray['parent_category'] && count($newParents) > 0) {
						$pObj->newlog('You dont have the permissions to use any of the parent categories you chose as a parent.',1);
						$fieldArray = array();	
					}
					
					// make sure the category does not end up as its own parent - would lead to endless recursion.
					if('' != $fieldArray['parent_category'] && 'new' != $status) {
						$catUids = t3lib_div::intExplode(',', $fieldArray['parent_category']);
						
						foreach($catUids as $catUid) {
							
							// Skip root.
							if(0 == $catUid) continue;
							
							// Make sure we did not assign self as parent category
							if($catUid == $id) {
								$pObj->newlog('You cannot select this category itself as a parent category.', 1);
								$fieldArray = array();
							}
							
							$catDirect = t3lib_div::makeInstance('tx_commerce_category');
							$catDirect->init($catUid);
							$catDirect->load_data();
					
							$tmpCats 	= $catDirect->getParentCategories();
							$tmpParents = null;
							$i 			= 1000;
							
							while(!is_null($cat = @array_pop($tmpCats))) {
								
								//Prevent endless recursion
								if($i < 0) {
									$pObj->newlog('Endless recursion occured while processing your request. Notify your admin if this error persists.', 1);
									$fieldArray = array();
								}
								
								
								if($cat->getUid() == $id) {
									$pObj->newlog('You cannot select a child category or self as a parent category. Selected Category in question: ' . $catDirect->getTitle(), 1);
									$fieldArray = array();	
								}
								
								$tmpParents = $cat->getParentCategories();
					
								if(is_array($tmpParents) && 0 < count($tmpParents)) {
									$tmpCats = array_merge($tmpCats, $tmpParents);	
								}
								$i --;
							}
						}
					}
				}
			break;
		}
	}

	/**
	 * This Function is simlar to array_diff but looks for array sorting too.
	 * @param array		$a1
	 * @param array		$a2
	 * @return array	$r		different fields between a1 & a2 
	 */
	function single_diff_assoc(&$a1,&$a2) {
		$r = array(); // return

		foreach ($a1 as $k => $pl) {
			if (! isset($a2[$k]) || $a2[$k] != $pl)
				$r[$k] = $pl;
		}

		foreach ($a2 as $k => $pl) {
			if ( (! isset($a1[$k]) || $a1[$k] != $pl ) && ! isset($r[$k]) )
				$r[$k] = $pl;
		}
		return $r;
	}

	/**
	 * When all operations in the database where made from TYPO3 side, we have to make some special
	 * entries for the shop. Because we don't use the built in routines to save relations between
	 * tables, we have to do this on our own. We make it manually because we save some additonal information
	 * in the relation tables like values, correlation types and such stuff.
	 * The hole save stuff is done by the "saveAllCorrelations" method.
	 * After the relations are stored in the database, we have to call the dynaflex extension to modify
	 * the TCA that it fit's the current situation of saved database entries. We call it here because the TCA
	 * is allready built and so the calls in the tca.php of commerce won't be executed between now and the point
	 * where the backendform is rendered.
	 *
	 * @param	[type]		$status: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$pObj: ...
	 * @return	[type]		...
	 * @author Thomas Hempel <thomas@work.de>
	 */
	function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $pObj)	{
		
		//if($GLOBALS['BE_USER']->user['uid'] == 27)
		//echo t3lib_div::debug($fieldArray,__LINE__.__FILE__);
		
		// get the UID of the created record if it was just created
		if ('new' == $status && count($fieldArray))	{
			$id = $pObj->substNEWwithIDs[$id];
		}
		
		switch (trim(strtolower((string)$table)))	{
			case 'tx_commerce_categories':
				
				//if the field array has been unset, do not save anything, but load the dynaflex
				if(0 < count($fieldArray)) {
					
					if (isset($fieldArray['parent_category']))	{

						// get the list of categories for this category and save the relations in the database
						$catList = explode(',', $fieldArray['parent_category']);
						
						// preserve the 0 as root.
						$preserve = array();
						
						if(in_array(0, $catList)) {
							$preserve[] = 0;	
						}
						
						// extract uids.
						$catList = $this->belib->getUidListFromList($catList);
						$catList = $this->belib->extractFieldArray($catList, 'uid_foreign', true);
						
						// add preserved
						$catList = array_merge($catList, $preserve);

						$this->belib->saveRelations($id, $catList, 'tx_commerce_categories_parent_category_mm', true);
					}
	
						// save all relations concerning categories
					$this->saveCategoryRelations($id, $fieldArray);
				}
			break;

			case 'tx_commerce_products':

				// if fieldArray has been unset, do not save anything, but load dynaflex config
				if(0 < count($fieldArray)) {

					$item = t3lib_div::makeInstance('tx_commerce_product');
					$item->init($id);
					$item->load_data();
					
					if (isset($fieldArray['categories']))	{
						$catList = $this->belib->getUidListFromList(explode(',', $fieldArray['categories']));
						$catList = $this->belib->extractFieldArray($catList, 'uid_foreign', true);
						
						//get id of the live placeholder instead if such exists
						$relId = ('new' != $status && $item->get_pid() == '-1') ? $item->get_t3ver_oid() : $id;
						
						$this->belib->saveRelations($relId, $catList, 'tx_commerce_products_categories_mm', true, false);
					}
					
					//if the live shadow is saved, the product relations have to be saved to the versioned version
					if('new' == $status && $fieldArray['pid'] == '-1') {
						$id ++;	
					}
					
					$this->saveProductRelations($id, $fieldArray);
				}

				// sometimes the array is unset because only the checkbox "create new article" has been checked
				// if that is the case and we have the rights, create the articles
				// so we check if the product is already created and if we have edit rights on it
				if(t3lib_div::testInt($id)){
					
					// check permissions
					$item = t3lib_div::makeInstance('tx_commerce_product');
					$item->init($id);
					
					$parentCategories = $item->getParentCategories();
					
					//check existing categories
					if(!tx_commerce_belib::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'))) {
						$pObj->newlog('You dont have the permissions to create a new article.',1);
						$fieldArray = array();	
					} else {
						// ini the article creator
						$ac = t3lib_div::makeInstance('tx_commerce_articleCreator');
						$ac->init($id, $this->belib->getProductFolderUid());
						
						// create new articles
						$ac->createArticles($pObj->datamap[$table][$id]);
						
						// update articles if new attributes were added
						$ac->updateArticles($pObj->datamap[$table][$id]);
					}
				}
				
				// load dynaflex config
				require_once(t3lib_extMgm::extPath('commerce') .'ext_df_product_config.php'); //
				break;
				
			case 'tx_commerce_articles':
				
				// articles always load dynaflex config
				require_once(t3lib_extMgm::extPath('commerce') .'ext_df_article_config.php');
				$dynaFlexConf['workingTable'] = 'tx_commerce_articles';
			break;
			case 'tx_commerce_article_prices':
				if (!isset($fieldArray['uid_article']))	{
					$uidArticleRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid_article',
						'tx_commerce_article_prices',
						'uid=' .intval($id)
					);
					$uidArticle = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($uidArticleRes);
					$uidArticle = $uidArticle['uid_article'];
				} else {
					$uidArticle = $fieldArray['uid_article'];
				}
				$this->belib->savePriceFlexformWithArticle( $id , $uidArticle , $fieldArray );

				break;
		}
		
		// update the page tree  difefreent in version 4.1 and 4.2
		if (t3lib_div::int_from_ver(TYPO3_version) >= '4002000') { 	
			t3lib_BEfunc::setUpdateSignal('updatePageTree');
		}else{
			t3lib_BEfunc::setUpdateSignal('updatePageTree');
		}
		
		$loadDynaFlex = true;
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['simpleMode']){
		    if(trim(strtolower((string)$table))== 'tx_commerce_articles'){
				$loadDynaFlex = false;
		    }
		
		}
		//txcommerce_copyProcess: this is so that dynaflex is not called when we copy an article - otherwise we would get an error
		//@see tx_commerce_belib::copyArticle
		if ($loadDynaFlex && t3lib_extMgm::isLoaded('dynaflex') && !empty($dynaFlexConf) && (!isset($GLOBALS['BE_USER']->uc['txcommerce_copyProcess']) || !$GLOBALS['BE_USER']->uc['txcommerce_copyProcess']))	{
			$dynaFlexConf[0]['uid'] = $id;
			$dynaFlexConf[1]['uid'] = $id;
			require_once(t3lib_extMgm::extPath('dynaflex') .'class.dynaflex.php');
			
			$dynaflex = new dynaflex($GLOBALS['TCA'], $dynaFlexConf);
			$GLOBALS['TCA'] = $dynaflex->getDynamicTCA();
			// change for simple mode
			// override the dynaflex settings after the DynamicTCA
        	if($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['simpleMode'] && trim(strtolower((string)$table))== 'tx_commerce_products') {
                $GLOBALS['TCA']['tx_commerce_products']['columns']['articles'] = array (
                        'exclude' => 1,
                        'label' => 'LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.articles',
                        'config' => array (
                                'type' => 'inline',
                                'foreign_table'=>'tx_commerce_articles',
                                'foreign_field'=>'uid_product',
                                'minitems'=>0,
                        ),
                );
                # print_r( $GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem']);
                $GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem'] = str_replace('articleslok', 'articles', $GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem']);
               # print_r( $GLOBALS['TCA']['tx_commerce_products']['types']['0']['showitem']);
        	}
        #	print_r($GLOBALS['TCA']['tx_commerce_products']['columns']);
        #	echo "\n\n<br><br>";
			
		}
	}


	/**
	 * Process Data when saving Order
	 * Change the PID from this order via the new field newpid
	 * As TYPO3 don't allowes changing the PId directly
	 *
	 * @param	[type]		$status: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$th_obj: ...
	 * @return	[type]		...
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 */
 	function moveOrders($status, $table, $id, &$fieldArray, &$th_obj)	{
			// Only if newpid in arrayKeys fieldlist
		if (in_array('newpid', array_keys($fieldArray)))	{
			$hookObjectsArr = array();
				// Add firstly the pid filed
			$fieldArray['pid']=$fieldArray['newpid'];

			/**
			* @TODO: Should all relations to orders be moved oder should
			* we invent separate storage folders for order_articles and system_informations as Payment and Customers
			*
			*/

				// Move Order articles
			$res_order_id=$GLOBALS['TYPO3_DB']->exec_SELECTquery('order_id,pid,uid,order_sys_language_uid',$table,'uid='.intval($id));
			if (!$GLOBALS['TYPO3_DB']->sql_error())	{



				if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders']))	{
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'] as $classRef)	{
						$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
					}
				}
				$order_row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_order_id);

				if ($order_row['pid']<>$fieldArray['newpid']) {

					// order_sys_language_uid is not always set in fieldArray so we overwrite it with our order data
					if($fieldArray['order_sys_language_uid'] === NULL) $fieldArray['order_sys_language_uid'] = $order_row['order_sys_language_uid'];

					foreach($hookObjectsArr as $hookObj)	{
						if (method_exists($hookObj, 'moveOrders_preMoveOrder'))	{
							$hookObj->moveOrders_preMoveOrder($order_row,$fieldArray);
						}
					}

					$order_id=$order_row['order_id'];
					$res_order_articles=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_order_articles',"order_id='".$GLOBALS['TYPO3_DB']->quoteStr($order_id,'tx_commerce_order_articles')."'");
					if (!$GLOBALS['TYPO3_DB']->sql_error())	{
							// Run trough all articles from this order and move to it to other storage folder
						while($order_artikel_row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_order_articles))	{
							$order_artikel_row['pid']=$fieldArray['newpid'];
							$order_artikel_row['tstamp']=time();
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_order_articles','uid='.$order_artikel_row['uid'],$order_artikel_row);
						}
					} else {
						$th_obj->log($table,$id,2,0,2,"SQL error: '%s' (%s)",12,array($GLOBALS['TYPO3_DB']->sql_error(),$table.':'.$id));
					}
					$order_row['pid']=$fieldArray['newpid'];
					$order_row['tstamp']=time();
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_orders','uid='.$order_row['uid'],$order_row);

					foreach($hookObjectsArr as $hookObj)	{
						if (method_exists($hookObj, 'moveOrders_postMoveOrder'))	{
							$hookObj->moveOrders_postMoveOrder($order_row,$fieldArray);
						}
					}
				}
			} else {
				$th_obj->log($table,$id,2,0,2,"SQL error: '%s' (%s)",12,array($GLOBALS['TYPO3_DB']->sql_error(),$table.':'.$id));
			}
		}
 	}

	/**
	 * Process Data when saving Order
	 * Basically removes the crdate field
	 * and so prevents vom chan ging the crdate by admin
	 *
	 * @param	[type]		$status: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$th_obj: ...
	 * @return	[type]		...
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 */
 	function doNotChangeCrdate($status, $table, $id, &$fieldArray, &$th_obj) {
		$fieldArray['crdate'] = null;
 	}

	/**
	 * Process Data when saving Ordered_artciles
	 * Recalculate Order sum
	 *
	 * @param	[type]		$status: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$th_obj: ...
	 * @return	[type]		...
	 * @author Ingo Schmitt <is@marketing-factory.de>
	 */
	function recalculateOrderSum($status, $table, $id, &$fieldArray, &$th_obj)	{
		$foreign_table = 'tx_commerce_orders';
		$res_order_id = $GLOBALS['TYPO3_DB']->exec_SELECTquery('order_id', $table, 'uid=' .intval($id));
		if (!$GLOBALS['TYPO3_DB']->sql_error())	{
			list($order_id)=$GLOBALS['TYPO3_DB']->sql_fetch_row($res_order_id);
			$res_order_articles=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_order_articles',"order_id='".$GLOBALS['TYPO3_DB']->quoteStr($order_id,'tx_commerce_order_articles')."'");
			if (!$GLOBALS['TYPO3_DB']->sql_error())	{
				while($order_artikel_row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_order_articles))	{
					/**
					* Calculate Sums
					*/
					$sum['amount']+=$order_artikel_row['amount'];
					$sum['price_net']+=$order_artikel_row['amount']*$order_artikel_row['price_net'];
					$sum['price_gross']+=$order_artikel_row['amount']*$order_artikel_row['price_gross'];
				}
			} else {
				$th_obj->log($table,$id,2,0,2,"SQL error: '%s' (%s)",12,array($GLOBALS['TYPO3_DB']->sql_error(),$table.':'.$id));
			}
			$values = array(
				'sum_price_gross' => $sum['price_gross'],
				'sum_price_net' => $sum['price_net']
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,"order_id='".$GLOBALS['TYPO3_DB']->quoteStr($order_id,$foreign_table)."'",$values);
			if ($GLOBALS['TYPO3_DB']->sql_error())	{
				$th_obj->log($table,$id,2,0,2,"SQL error: '%s' (%s)",12,array($GLOBALS['TYPO3_DB']->sql_error(),$table.':'.$id));
			}
		} else {
			$th_obj->log($table,$id,2,0,2,"SQL error: '%s' (%s)",12,array($GLOBALS['TYPO3_DB']->sql_error(),$table.':'.$id));
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_dmhooks.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_dmhooks.php"]);
}
?>
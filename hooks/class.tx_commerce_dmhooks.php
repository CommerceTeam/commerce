<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Thomas Hempel (thomas@work.de)
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
 * @author 		Thomas Hempel <thomas@work.de>
 * @author 		Ingo Schmitt <is@marketing-factory.de>
 * @maintainer	Thomas Hempel <thomas@work.de>
 *
 * $Id: class.tx_commerce_dmhooks.php 578 2007-03-28 09:58:59Z ingo $
 */
require_once(t3lib_extmgm::extPath('commerce') .'lib/class.tx_commerce_belib.php');
require_once(t3lib_extmgm::extPath('graytree').'lib/class.tx_graytree_folder_db.php');

class tx_commerce_dmhooks	{
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
	function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, $pObj)	{
		// debug(array($incomingFieldArray, $table, $id), 'processDatamap_preProcessFieldArray');

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
		) return;
		$handleAttributes = false;

		switch ($table)	{
			case 'tx_commerce_categories':
				$pcList = explode(',', $incomingFieldArray['parent_category']);
				$catList = array();

					// look on every element, and remove it if it the same as the uid of the category we're going to safe
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
					unset($incomingFieldArray['parent_category']);
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

					// update article prices
				if (isset($incomingFieldArray['prices']))	{
					$prices = $incomingFieldArray['prices']['data']['sDEF']['lDEF'];
					$pricesData = array();
					foreach ($prices as $pKey => $keyData)	{
						if ($keyData)	{
							$value = $keyData['vDEF'];
							$pUid = $this->belib->getUidFromKey($pKey, $keyData);

							unset($keyData[(count($keyData) -1)]);
							$key = implode('_', $keyData);


							if ($key == 'price_net' || $key == 'price_gross' || $key == 'purchase_price')	{
								if (is_numeric($value)){
									$value = $value *100;
								}
								
							}

							/**
							 * Price from tax calculation
							 * @since 06.10.2005
							 * @author Ingo Schmitt <is@marketing-factory.de>
							 */
						
							if (isset($incomingFieldArray['tax']))	{
								$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
								
										
								switch ($extConf['genprices']){
									case 0:
										/**
										 * Do nothing;
										 */
										break;
									case 2: 
										/**
										 * Calculare from net 
										 */
										  if ($key == 'price_gross') {
										 	$price_net_value=$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_net_'.$pUid]['vDEF'];
											$value=round(($price_net_value*100)*(100+$incomingFieldArray['tax'])/100);
											$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_gross_'.$pUid]['vDEF']=$value/100;
										  }
										 break;
									case 3:
										/**
										 * Calculate from gross
										 */
										 if ($key == 'price_net') {
										 	$price_gross_value=$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_gross_'.$pUid]['vDEF'];
											$value=round(($price_gross_value*100)/(100+$incomingFieldArray['tax'])*100);
											$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_net_'.$pUid]['vDEF']=$value/100;
										 }
										 break;
									case 1:
									default:
										
										if (($key == 'price_net') && (!isset($value)) || ($value === '') || (strlen($value)==0))	{
											
											$price_gross_value=$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_gross_'.$pUid]['vDEF'];
											$value=round(($price_gross_value*100)/(100+$incomingFieldArray['tax'])*100);
											$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_net_'.$pUid]['vDEF']=$value/100;
										} elseif (($key == 'price_gross') && (!isset($value)) || ($value === '') || (strlen($value)==0))	{
											$price_net_value=$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_net_'.$pUid]['vDEF'];
											$value=round(($price_net_value*100)*(100+$incomingFieldArray['tax'])/100);
											$incomingFieldArray['prices']['data']['sDEF']['lDEF']['price_gross_'.$pUid]['vDEF']=$value/100;
										}
									break;
								}
							}
							
							if ($value > '')	{
								$pricesData[$pUid][$key] = $value;
							}
						}
					}
					$error=false;
					/**
					 * @TODO Do Localisation in Output
					 */
					/**
					 * Do some Checks with the data,
					 */
					$minPrice =0;
					
					foreach ($pricesData as $onePrice) {
						if ($onePrice['price_scale_amount_start']>0 && ($minPrice==0 || $minPrice>$onePrice['price_scale_amount_start'])) {
							$minPrice = $onePrice['price_scale_amount_start'];
						}

						if ($onePrice['price_scale_amount_start'] >$onePrice['price_scale_amount_end']) {
							$pObj->log($table,$id,2,0,1,"Price Scale Amount Start was greater than price scale amount end",1,array($table));
							$error=true;
						}
					}
					if ($minPrice >1) {
						$pObj->log($table,$id,2,0,1,"Minimum Price Sacale amount was more than 1",1,array($table));
					}
					if ($error) {
						// Unset Array to change no value

						$incomingFieldArray = array();
					}

					if (is_array($pricesData) && $error===false)	{
						foreach ($pricesData as $pUid => $pArray)	{

							unset($pArray["create_new_scale_prices_fe"]);
							if (count($pArray)==0) continue;
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
								'tx_commerce_article_prices',
								'uid=' .$pUid,
								$pArray
							);
						}
					}
				}

					// create a new price if the checkbox was toggled get pid of article
				if ($incomingFieldArray['create_new_price'] == 'on')	{
					    // somehow hook is used two times sometime. So switch off new creating.
				    $incomingFieldArray['create_new_price'] = 'off';


					list($modPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Commerce', 'commerce');
					list($prodPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Products', 'commerce',$modPid);


					$aPid = $prodPid;

						// set some status vars
					$time = time();

						// create the price
					$GLOBALS['TYPO3_DB']->exec_INSERTquery(
						'tx_commerce_article_prices',
						array(
							'pid' => $aPid,
							'tstamp' => $time,
							'crdate' => $time,
							'cruser_id' => $GLOBALS['BE_USER']->user['uid'],
							'uid_article' => $id,
						)
					);
				}


					// create new scale prices if all fields were filled out correcly

					// $create_new_scale_prices_count:	how many steps? (e.g. 3)
					// $create_new_scale_prices_steps:	how big is the step from amount to the next? (e.g. 5)
					// $create_new_scale_prices_startamount:	what is the first amount? (e.g. 10)

					// example values above will create 3 prices with amounts 10-14, 15-19 and 20-24
				$create_new_scale_prices_count=is_numeric($incomingFieldArray['create_new_scale_prices_count'])?intval($incomingFieldArray['create_new_scale_prices_count']):0;
				$create_new_scale_prices_steps=is_numeric($incomingFieldArray['create_new_scale_prices_steps'])?intval($incomingFieldArray['create_new_scale_prices_steps']):0;
				$create_new_scale_prices_startamount=is_numeric($incomingFieldArray['create_new_scale_prices_startamount'])?intval($incomingFieldArray['create_new_scale_prices_startamount']):0;

				if ($create_new_scale_prices_count>0 && $create_new_scale_prices_steps>0 && $create_new_scale_prices_startamount>0)	{
					    // somehow hook is used two times sometime. So switch off new creating.
				    $incomingFieldArray['create_new_scale_prices_count'] = 0;

						// get pid
					list($modPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Commerce', 'commerce');
					list($prodPid,$defaultFolder,$folderList) = tx_graytree_folder_db::initFolders('Products', 'commerce',$modPid);


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
						#t3lib_div::debug($insertArr);
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_article_prices',$insertArr);

						// TODO: update artciles XML

						$myScaleAmountStart+=$create_new_scale_prices_steps;
						$myScaleAmountEnd+=$create_new_scale_prices_steps;
					}
					$this->belib->updatePriceXMLFromDatabase($id);


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

		// debug(array($pUid, $fieldArray), 'saveProductRelations');



		// create an article and a new price for a new product
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['commerce']);
		if ($extConf['simpleMode'] && $pUid != NULL)	{
				// search for an article of this product
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_articles', 'uid_product=' .$pUid, '', '', 1);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0)	{
					// create a new article if no one exists
				$pRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title', 'tx_commerce_products', 'uid=' .$pUid, '', '', 1);
				$productData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($pRes);

				$aRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_commerce_articles',
					array(
						'pid' => $fieldArray['pid'],
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
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_article_prices', 'uid_article=' .$aUid, '', '', 1);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0)	{
					// create a new price if no one exists
				$pRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_article_prices', array('pid' => $fieldArray['pid'],'uid_article' => $aUid));
			}
		}

		$delete = true;
		if (isset($fieldArray['categories']))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign', 'tx_commerce_products_categories_mm', 'uid_local=' .$pUid);
			while ($sres = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) $catList[] = $sres['uid_foreign'];
			$paList = $this->belib->getAttributesForCategoryList($catList);
			$uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, array('uid_correlationtype'));

			$this->belib->saveRelations($pUid, $uidList, 'tx_commerce_products_attributes_mm', false, false);
			$this->belib->updateXML('attributes', 'tx_commerce_products', $pUid, 'product', $ctList);
			$delete = false;
		}
		if (isset($fieldArray['attributes']) && !isset($_REQUEST['createList']))	{
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
			$this->belib->updateXML('attributes', 'tx_commerce_products', $pUid, 'product', $ctList);

				// update the XML for this product, we remove everything that is not set for current attributes
			$pXML = $GLOBALS['TYPO3_DB']->exec_SELECTquery('attributesedit', 'tx_commerce_products', 'uid=' .$pUid);
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
				$pXML = t3lib_div::array2xml($pXML, '', 0, 'T3FlexForms');
				$fieldArray['attributesedit'] = $pXML;
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
			if (is_array($ffData['data']['sDEF']['lDEF'])) {

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
										'uid_local' => $pUid,
										'uid_foreign' => $attributeKey,
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
									'uid_local=' .$article['uid'] .' AND uid_foreign=' .$attributeKey
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
		// Check if we do have some localosed products an clal the recursvly
		$resLocalised=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tx_commerce_products','deleted=0 and l18n_parent='.$pUid);
		while ($rowLocalised = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resLocalised)) {
			$this->saveProductRelations($rowLocalised['uid'], $fieldArray);

		}
	}

	function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, $pObj)	{
		switch (strtolower((string)$table))	{
			case 'tx_commerce_article_prices':
				
				$fieldArray['price_net'] = $fieldArray['price_net'] *100;
				$fieldArray['price_gross'] = $fieldArray['price_gross'] *100;
				$fieldArray['purchase_price'] = $fieldArray['purchase_price'] *100;
			break;
		}
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
		// debug(array($fieldArray, $table, $id, $fieldArray), 'processDatamap_afterDatabaseOperations');

			// get the UID of the created record if it was just created
		if (strtolower(substr($id, 0, 3)) == 'new')	{
			$id = $pObj->substNEWwithIDs[$id];
		}

		switch (trim(strtolower((string)$table)))	{
			case 'tx_commerce_categories':
					// get the list of categories for this category and save the relations in the database
				$catList = explode(',', $fieldArray['parent_category']);
				$catList = $this->belib->getUidListFromList($catList);
				$catList = $this->belib->extractFieldArray($catList, 'uid_foreign', true);

				if (isset($fieldArray['parent_category']))	{
						// to avoid that relations will be deleted if the categories where not changed
					$this->belib->saveRelations($id, $catList, 'tx_commerce_categories_parent_category_mm', true);
				}

					// save all relations concerning categories
				$this->saveCategoryRelations($id, $fieldArray);

				require_once(t3lib_extMgm::extPath('commerce') .'ext_df_category_config.php');
				break;

			case 'tx_commerce_products':
				$catList = $this->belib->getUidListFromList(explode(',', $fieldArray['categories']));
				$catList = $this->belib->extractFieldArray($catList, 'uid_foreign', true);
				if (isset($fieldArray['categories']))	{
						// to avoid that relations will be deleted if the categories where not changed
					$this->belib->saveRelations($id, $catList, 'tx_commerce_products_categories_mm', true, false);
				}

				$this->saveProductRelations($id, $fieldArray);

				require_once(t3lib_extMgm::extPath('commerce') .'ext_df_product_config.php');
				break;
			case 'tx_commerce_articles':
				require_once(t3lib_extMgm::extPath('commerce') .'ext_df_article_config.php');
				$dynaFlexConf['workingTable'] = 'tx_commerce_articles';
			break;
			case 'tx_commerce_article_prices':
				if (!isset($fieldArray['uid_article']))	{
					$uidArticleRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid_article',
						'tx_commerce_article_prices',
						'uid=' .$id
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
			t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
		}

		if (t3lib_extMgm::isLoaded('dynaflex') && !empty($dynaFlexConf))	{
			$dynaFlexConf[0]['uid'] = $id;
			$dynaFlexConf[1]['uid'] = $id;

			require_once(t3lib_extMgm::extPath('dynaflex') .'class.dynaflex.php');
			$dynaflex = new dynaflex($GLOBALS['TCA'], $dynaFlexConf);
			$GLOBALS['TCA'] = $dynaflex->getDynamicTCA();
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
			$res_order_id=$GLOBALS['TYPO3_DB']->exec_SELECTquery('order_id,pid,uid',$table,'uid='.$id);
			if (!$GLOBALS['TYPO3_DB']->sql_error())	{



				if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders']))	{
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/hooks/class.tx_commerce_dmhooks.php']['moveOrders'] as $classRef)	{
						$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
					}
				}
				$order_row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_order_id);

				if ($order_row['pid']<>$fieldArray['newpid']) {


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
		unset($fieldArray['crdate']);
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
		$res_order_id = $GLOBALS['TYPO3_DB']->exec_SELECTquery('order_id', $table, 'uid=' .$id);
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

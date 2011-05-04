<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2011 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the COMMERCE (Advanced Shopping System) extension.
 *
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 *
 * @author Franz Holzinger <kontakt@fholzinger.com>
 * @author Thomas Hempel <thomas@work.de>
 *
 * @package TYPO3
 * @subpackage tx_commerce
 */

require_once(t3lib_extmgm::extPath('commerce') .'lib/class.tx_commerce_belib.php');
require_once(t3lib_extmgm::extPath('commerce').'treelib/class.tx_commerce_categorymounts.php');
require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_article.php'); 

class tx_commerce_cmhooks {

	/**
	 * This hook is processed Before a commandmap is processed (delete, etc.)
	 *
	 * @param	array		$incomingFieldArray: the array of fields that where changed in BE (passed by reference)
	 * @param	string		$table: the table the data will be stored in
	 * @param	integer		$id: The uid of the dataset we're working on
	 * @param	object		$pObj: The instance of the BE Form
	 * @return	void
	 * @author	Ingo Schmitt
	 
	 * Do Nothing if the command is lokalize an table is article
	 */
	function processCmdmap_preProcess(&$command,$table, &$id, $value, &$pObj)	{
		global $BE_USER;
		
		if (($table == 'tx_commerce_articles') && ($command == 'localize'))	{
			$command='';
			$this->error('LLL:EXT:commerce/locallang_be_errors.php:article.localization');
		}
				
		if (($table=='tx_commerce_products') && ($command=='localize'))	{
			$belib = t3lib_div::makeInstance('tx_commerce_belib');
			
				// get all related articles
			$articles = $belib->getArticlesOfProduct($id);
			
				// Check if product has articles
			if ($articles==false)	{
					// Error Outpout, no articles
				$command='';
				$this->error('LLL:EXT:commerce/locallang_be_errors.php:product.localization_without_article');
			}
			
			//Write to session that we copy 
			//this is used by the hook to the datamap class to figure out if it should check if the categories-field is filled - since it is mergeIfNotBlank, it would always be empty
			//so far this is the best (though not very clean) way to solve the issue we get when localizing a product
			$BE_USER->uc['txcommerce_copyProcess'] = 1;
			$BE_USER->writeUC();
			
		}
		
		//Check if user really is allowed to delete - may not be the case
		if('tx_commerce_categories' == $table && ('delete' == $command)) {
			$cat = t3lib_div::makeInstance('tx_commerce_category');
			$cat->init($id);
			
			$mounts = t3lib_div::makeInstance('tx_commerce_categorymounts');
			$mounts->init($GLOBALS['BE_USER']->user['uid']);
		
			if(false == $cat->isPSet($command) || !$mounts->isInCommerceMounts($cat->getUid())) {
				$pObj->log($table,$id,3,0,1,'Attempt to '.$command.' record without '.$command.'-permissions'); //Log the error
				$id = 0; //Set id to 0 (reference!) to prevent delete of the record
			}
		} else if('tx_commerce_products' == $table && ('delete' == $command)) {
			//Read the parent categories
			$item = t3lib_div::makeInstance('tx_commerce_product');
			$item->init($id);
			
			$parentCategories = $item->getParentCategories();
			
			//check existing categories
			if(!tx_commerce_belib::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'))) {
				$pObj->log($table,$id,3,0,1,'Attempt to '.$command.' record without '.$command.'-permissions'); //Log the error
				$id = 0; //Set id to 0 (reference!) to prevent delete of the record
			}
		} else if('tx_commerce_articles' == $table && ('delete' == $command)) {
			//Read the parent product
			$item = t3lib_div::makeInstance('tx_commerce_article');
			$item->init($id);
			
			$productUid = $item->getParentProductUid();
			
			//get the parent categories of the product
			$item = t3lib_div::makeInstance('tx_commerce_product');
			$item->init($productUid);
			
			$parentCategories = $item->get_parent_categories();
			
			if(!tx_commerce_belib::checkPermissionsOnCategoryContent($parentCategories, array('editcontent'))) {
				$pObj->log($table,$id,3,0,1,'Attempt to '.$command.' record without '.$command.'-permissions'); //Log the error
				$id = 0; //Set id to 0 (reference!) to prevent delete of the record
			}
		}
	}


	/**
	 * This hook is processed AFTER a commandmap is processed (delete, etc.)
	 *
	 * @param	array		$incomingFieldArray: the array of fields that where changed in BE (passed by reference)
	 * @param	string		$table: the table the data will be stored in
	 * @param	integer		$id: The uid of the dataset we're working on
	 * @param	object		$pObj: The instance of the BE Form
	 * @return	void
	 * @author	Franz Holzinger <kontakt@fholzinger.com>
	 * @author	Thomas Hempel	<thomas@work.de>
	 * @author	Ingo Schmitt	<is@marketing-factory.de>
	 * Calculation of missing price
	 */
	function processCmdmap_postProcess(&$command, $table, $id, $value, &$pObj)	{
		global $BE_USER;
			
			// update the page tree
		t3lib_BEfunc::setUpdateSignal('updatePageTree');
		
		/**
		 * Delete all categories->products->articles if a category should be deleted.
		 * This one does NOT delete any relations! This is not wanted because you might want to
		 * restore deleted categories, products or articles.
		 * 
		 * @author	Thomas Hempel	<thomas@work.de>
		 */
		if ($table == 'tx_commerce_categories' && $command == 'delete')	{
			$belib = t3lib_div::makeInstance('tx_commerce_belib');
			$deleteArray = array('deleted' => 1);
			$childCategories = array();
			
			$belib->getChildCategories($id, $childCategories, 0, 0, true);
			
			if (is_array($childCategories) && count($childCategories) > 0)	{
				$categoryList = array();
				
				foreach ($childCategories as $categoryUid)	{
					$categoryList[] = $categoryUid;
					$products = $belib->getProductsOfCategory($categoryUid);
					$productList = array();
					if (is_array($products) && count($products) > 0)	{
						foreach ($products as $product)	{
							$articles = $belib->getArticlesOfProduct($product['uid_local']);
							if (is_array($articles) && count($articles) > 0)	{
								$articleList = array();
								foreach ($articles as $article)	{
									$articleList[] = $article['uid'];
									
										// delete prices for article
									$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_article_prices', 'uid_article='.$article['uid'], $deleteArray);
								}
								$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_articles', 'uid IN ('.implode(',', $articleList).')', $deleteArray);
							}
							$productList[] = $product['uid_local'];
						}
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_products', 'uid IN ('.implode(',', $productList).')', $deleteArray);
					}
				}
				
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_categories', 'uid IN ('.implode(',', $categoryList).')', $deleteArray);
			}
		}
		
		/**
		 * If a product is deleted, delete all articles below and their locales.
		 * @author	Ingo Schmitt	<is@marketing-factory.de>
		 * 
		 */
		if ($table == 'tx_commerce_products' && $command == 'delete') {
			// instanciate the backend library
			$belib = t3lib_div::makeInstance('tx_commerce_belib');
			// get all related articles
			$articles = $belib->getArticlesOfProduct($id);
			if (($articles != false) && (is_array($articles))) {
				/**
				 * Only if there are articles, walk thru the array
				 * and delete articles from database
				 * by setting deleted =1
				 */
				 $update_array['deleted']=1;
				 foreach ($articles as $oneArticle)	{
				 		
				 	if ($oneArticle['uid']>0) {
				 		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_articles', 'uid = '.$oneArticle['uid'],$update_array);
				 		$belib->deleteL18n('tx_commerce_articles', $oneArticle['uid']);
				 	}
				 }
			}
		}
		
		/**
		 * If a product is deleted, delete all localizations of it
		 * @author Erik Frister
		 */
		if ($table == 'tx_commerce_products' && $command == 'delete') {
			// instanciate the backend library
			$belib = t3lib_div::makeInstance('tx_commerce_belib');
			// delete the localizations for products
			$belib->deleteL18n($table, $id);
		}
		
		/**
		 * localize all articles that are related to the current product
		 *  and lokalise all product attributes realted to this product from 
		 */
			// 
		if ($table == 'tx_commerce_products' && $command == 'localize')	{
			
			//copying done, clear session
			$BE_USER->uc['txcommerce_copyProcess'] = 0;
			$BE_USER->writeUC();
			
				// get the uid of the newly created product
			$locPUid = $pObj->copyMappingArray[$table][$id];
			
			if(null == $locPUid) {
				$command = '';
				$this->error('LLL:EXT:commerce/locallang_be_errors.php:product.no_find_uid');
			}
			
			
			
				// instanciate the backend library
			$belib = t3lib_div::makeInstance('tx_commerce_belib');
			
				// get all related articles
			$articles = $belib->getArticlesOfProduct($id);
				// get all related attributes
			$productAttributes = $belib->getAttributesForProduct($id,false,true);
				// Check if Localised Product already has artiles
			$locProductARticles	  = $belib->getArticlesOfProduct($locPUid);
			$locProductAttributes = $belib->getAttributesForProduct($locPUid);
			
			// Check product has attrinutes and no Attributes are avaliable for localised version
			
			
			
			if ( (is_array($productAttributes)) && (count($productAttributes) > 0) && ($locProductAttributes  == false) ) {
				// als thrue
				
				$langIsoCode = t3lib_BEfunc::getRecord('sys_language', intval($value), 'static_lang_isocode');
				$langIdent = t3lib_BEfunc::getRecord('static_languages', intval($langIsoCode['static_lang_isocode']), 'lg_typo3');
				$langIdent = strtoupper($langIdent['lg_typo3']);

				if (is_array($productAttributes)) {
					foreach ($productAttributes as $oneAttribute) {
						if (($oneAttribute['uid_correlationtype'] == 4) && (!$oneAttribute['has_valuelist']=='1') ) {
							
							// only if we have attributes type 4
							// and no valuelist
							/**
							 * @TODO: Reference to Constants ?
							 */
							 $locAttributeMM = $oneAttribute;	
							 /**
							 * Decide on what to to on lokalisation, how to act
							 * @see ext_conf_template
							 * attributeLokalisationType[0|1|2]
							 * 0: set blank
							 * 1: Copy
							 * 2: prepend [Translate to .$langRec['title'].:]
							 */
					
							unset($locAttributeMM['attributeData']);
							unset($locAttributeMM['has_valuelist']);
							switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTkey]['extConf']['attributeLokalisationType']) {
							 	case 0:
							 		unset($locAttributeMM['default_value']);
							 	break;
							 	case 1:
							 	break;
							 	case 2:
							 		/**
							 		 * Walk thru the array and prepend text
							 		 */	
							 		 $prepend= "[Translate to $langIdent:] ";
							 		 $locAttributeMM['default_value'] = $prepend.$locAttributeMM['default_value'];
							 		
							 	break;
							 						 
							}
							$locAttributeMM['uid_local']=$locPUid;
							
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_products_attributes_mm',  $locAttributeMM);
							
						}
						
						
						
					}
					/**
					 * Update the flexform
					 */
					 
					$resProduct = $GLOBALS['TYPO3_DB']->exec_SELECTquery('attributesedit,attributes','tx_commerce_products','uid ='.$id);
					#print_r($GLOBALS['TYPO3_DB']->SELECTquery('attributesedit,attributes','tx_commerce_products','uid ='.$id));
					if ($rowProduct = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resProduct)) {
						
						#$product['attributesedit'] = $rowProduct['attributes'];
						$product['attributesedit']=$belib->buildLocalisedAttributeValues($rowProduct['attributesedit'],$langIdent);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_products','uid ='.$locPUid,$product);
					}
					
				}
				
				
			}
			
			
				// Check if product has articles and localised product has no articles
			if (($articles != false) && ($locProductARticles == false))	{
					// determine language identifier
					// this is needed for updating the XML of the new created articles
				$langIsoCode = t3lib_BEfunc::getRecord('sys_language', intval($value), 'static_lang_isocode');
				$langIdent = t3lib_BEfunc::getRecord('static_languages', intval($langIsoCode['static_lang_isocode']), 'lg_typo3');
				$langIdent = strtoupper($langIdent['lg_typo3']);
				if (empty($langIdent)) $langIdent = 'DEF';
				
					// process all existing articles and copy them
					
					
					
				if (is_array($articles))	{
					foreach ($articles as $origArticle)	{
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
						$locArticle['uid_product'] = $locPUid;
						
							// get prices XML
						// $pricesFFData = t3lib_div::xml2array($origArticle['prices']);
			
							// get XML for attributes
							// this has only to be changed if the language is something else than default.
							// The possibility that something else happens is very small but anyhow... ;-)
						if ($langIdent != 'DEF' && $origArticle['attributesedit'])	{
							$locArticle['attributesedit']=$belib->buildLocalisedAttributeValues($origArticle['attributesedit'],$langIdent);
							
						}
						
							// create new article in DB
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles', $locArticle);
			
							// get the uid of the localized article
						$locAUid = $GLOBALS['TYPO3_DB']->sql_insert_id(); 
			
						// get all relations to attributes from the old article and copy them to new article
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_articles_article_attributes_mm', 'uid_local=' .intval($origArticle['uid']) .' AND uid_valuelist=0');
						while ($origRelation = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
							$origRelation['uid_local'] = $locAUid;
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $origRelation);
						}
			
							// update article with the new Price XML
						// $pricesXML = t3lib_div::array2xml($pricesFFData, '', 0, 'T3FlexForms');
						// $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_commerce_articles', 'uid=' .$locAUid, array('prices' => $pricesXML));
					}
				}
			} elseif ($locProductARticles == false) {
					// Error Output, no Articles	
				$command='';
				$this->error('LLL:EXT:commerce/locallang_be_errors.php:product.localization_without_article');
			} 
		}
	}


	/**
	 * 
	 * Prints out the error
	 * @param 	String 	$error
	 */
	
	function error($error)	{
		$error_doc = t3lib_div::makeInstance('template');
		$error_doc->backPath = '';

		$content.= $error_doc->startPage('tx_commerce_chhooks error Output');
		$content.= '
			<br/><br/>
			<table border="0" cellpadding="1" cellspacing="1" width="300" align="center">';
	
		$content.='	<tr class="bgColor5">
					<td colspan="2" align="center"><strong>'.$GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be_errors.php:error',1).'</strong></td>
				</tr>';
	
		$content.='
				<tr class="bgColor4">
					<td valign="top"><img'.t3lib_iconWorks::skinImg('','gfx/icon_fatalerror.gif','width="18" height="16"').' alt="" /></td>
					<td>'.$GLOBALS['LANG']->sL($error,0).'</td>
				</tr>';
		

		$content.='
				<tr>
					<td colspan="2" align="center"><br />'.
				
					'<form action="'.htmlspecialchars($_SERVER["HTTP_REFERER"]).'"><input type="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be_errors.php:continue',1).'" onclick="document.location='.htmlspecialchars($_SERVER["HTTP_REFERER"]).'return false;" /></form>'.
					'</td>
				</tr>';

		$content.= '</table>';

		$content.= $error_doc->endPage();
		echo $content;
		exit;	
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_cmhooks.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/hooks/class.tx_commerce_cmhooks.php"]);
}
?>
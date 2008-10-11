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
 * This class provides several methods for creating articles from within a product. It provides
 * the user fields and creates the entries in the database.
 *
 * @package TYPO3
 * @subpackage commerce
 *
 * @author Thomas Hempel <thomas@work.de>
 * @maintainer Thomas Hempel
 *
 * $Id: class.tx_commerce_articlecreator.php 525 2007-01-30 15:56:28Z ingo $
 */

require_once(t3lib_extmgm::extPath('commerce').'lib/class.tx_commerce_belib.php');

class tx_commerce_articleCreator {
	var $existingArticles = NULL;
	var $attributes = NULL;

	var $uid = 0;
	var $pid = 0;

	var $belib;

	/**
	 * Constructor
	 */
	function tx_commerce_articleCreator() {
		$this->belib = t3lib_div::makeInstance('tx_commerce_belib');
		$this->returnUrl = htmlspecialchars(urlencode(t3lib_div::_GP('returnUrl')));
	}

	/**
	 * Get all articles that already exist. Add some buttons for editing.
	 *
	 * @param	array		$PA: ...
	 * @param	object		$fObj: ...
	 * @return	A HTML-table with the articles
	 */
	function existingArticles($PA, $fObj) {
		global $LANG;

		$this->uid = intval($PA['row']['uid']);
		$this->pid = intval($PA['row']['pid']);

			// get all attributes for this product, if they where not fetched yet
		if ($this->attributes == NULL) $this->attributes = $this->belib->getAttributesForProduct($this->uid, true, true, true);

		$this->createArticles($PA);
		$this->updateArticles($PA);

			// get existing articles for this product, if they where not fetched yet
		if ($this->existingArticles == NULL) $this->existingArticles = $this->belib->getArticlesOfProduct($this->uid, '', 'sorting');

		if ((count($this->existingArticles) == 0) || ($this->uid==0) || ($this->existingArticles===false))	{
			return 'No articles existing for this product';
		}

		$colCount = 0;
		$headRow = $this->getHeadRow($colCount, NULL, NULL, false);
		$result = '<table border="0">';
		$result .= '<input type="hidden" name="deleteaid" value="0" />';

		$lastUid = 0;
		$prevArticle = 0;
		
		$result .= '<tr><td>&nbsp;</td>'.$headRow.'</td><td colspan="5">&nbsp;</td></tr>';

		for ($i = 0; $i < count($this->existingArticles); $i++)	{
			$article = $this->existingArticles[$i];

			$result .= '<tr><td style="border-top:1px black solid; border-right: 1px gray dotted"><strong>' .$article['title'] .'</strong>';
			$result .= '<br />UID:' .$article['uid'] .'</td>';

			if (is_array($this->attributes['ct1']))	{
				foreach ($this->attributes['ct1'] as $attribute)	{
						// get all article attribute relations
					$atrRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid_valuelist, default_value, value_char',
						'tx_commerce_articles_article_attributes_mm',
						'uid_local=' .$article['uid'] .' AND uid_foreign=' .$attribute['uid_foreign']
					);
					while ($attributeData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($atrRes))	{
						if ($attribute['attributeData']['has_valuelist'] == 1)	{
							if ($attributeData['uid_valuelist'] == 0)	{
									// if the attribute has no value, create a select box with valid values
								$result .= '<td style="border-top:1px black solid; border-right: 1px gray dotted"><select name="updateData[' .$article['uid'] .'][' .$attribute['uid_foreign'] .']" />';
								$result .= '<option value="0" selected="selected"></option>';
								foreach ($attribute['valueList'] as $attrValueUid => $attrValueData)	{
									$result .= '<option value="' .$attrValueUid .'">' .$attrValueData['value'] .'</option>';
								}
								$result .= '</select></td>';
							} else {
								$result .= '<td style="border-top:1px black solid; border-right: 1px gray dotted">' .$attribute['valueList'][$attributeData['uid_valuelist']]['value'] .'</td>';
							}
						} elseif (!empty($attributeData['value_char'])) {
							$result .= '<td style="border-top:1px black solid; border-right: 1px gray dotted">' .$attributeData['value_char'] .'</td>';
						} else {
							$result .= '<td style="border-top:1px black solid; border-right: 1px gray dotted">' .$attributeData['default_value'] .'</td>';
						}
					}
				}
			}

				// the edit pencil (with jump back to this dataset)
			$result .= '<td style="border-top:1px black solid"><a href="#" onclick="document.location=\'alt_doc.php?returnUrl=alt_doc.php?edit[tx_commerce_products][' .$this->uid .']=edit&amp;edit[tx_commerce_articles][' .$article['uid'] .']=edit\'; return false;">';
			$result .= '<img src="../typo3/gfx/edit2.gif" border="0" /></a></td>';

				// add the hide button
			$result .= '<td style="border-top:1px black solid"><a href="#" onclick="return jumpToUrl(\'tce_db.php?&amp;data[tx_commerce_articles][' .$article['uid'] .'][hidden]=' .(!$article['hidden']) .'&amp;redirect=alt_doc.php?edit[tx_commerce_products][' .$this->uid .']=edit\');">';
			$result .= '<img src="../typo3/gfx/button_' .(($article['hidden']) ? 'un' : '') .'hide.gif" border="0" /></a></td>';

				// add the sorting buttons
				// UP
			if (isset($this->existingArticles[($i -1)]))	{
				if (isset($this->existingArticles[($i -2)]))	{
					$moveItTo = '-' .$this->existingArticles[($i -2)]['uid'];
				} else {
					$moveItTo = $article['pid'];
				}
				$params = 'cmd[tx_commerce_articles][' .$article['uid'] .'][move]=' .$moveItTo;
				$result .= '<td style="border-top:1px black solid"><a href="#" onClick="return jumpToUrl(\'tce_db.php?' .$params .'&redirect=alt_doc.php?edit[tx_commerce_products][' .$this->uid .']=edit\');"><img src="../typo3/gfx/button_up.gif" width="11" height="10" border="0" align="top" /></a></td>';
			} else {
				$result .= '<td style="border-top:1px black solid"><img src="clear.gif" width="11" height="10" align="top"></td>';
			}

				// DOWN
			if (isset($this->existingArticles[($i +1)]))	{
				$params = 'cmd[tx_commerce_articles][' .$article['uid'] .'][move]=-' .$this->existingArticles[($i +1)]['uid'];
				$result .= '<td style="border-top:1px black solid"><a href="#" onClick="return jumpToUrl(\'tce_db.php?'.$params .'&redirect=alt_doc.php?edit[tx_commerce_products][' .$this->uid .']=edit\');"><img src="../typo3/gfx/button_down.gif" width="11" height="10" border="0" align="top" /></a></td>';
			} else {
				$result .= '<td style="border-top:1px black solid"><img src="clear.gif" width="11" height="10" align="top"></td>';
			}

				// add the delete icon
			$result .= '<td style="border-top:1px black solid"><a href="#" onclick="deleteRecord(\'tx_commerce_articles\', ' .$article['uid'] .', \'alt_doc.php?edit[tx_commerce_products][' .$this->uid .']=edit\');"><img src="../typo3/gfx/garbage.gif" border="0" /></a></td>';
			$result .= '</tr>';

			if ($article['uid'] > $lastUid) $lastUid = $article['uid'];
			$prevUid = $article['uid'];
		}

		$result .= '</table>';

		return $result;
	}


	/**
	 * Create a matrix of producible articles
	 *
	 * @param	[type]		$PA: ...
	 * @param	[type]		$fObj: ...
	 * @return	A HTML-table with checkboxes and all needed stuff
	 */
	function producibleArticles($PA, $fObj)	{
		$this->uid = intval($PA['row']['uid']);
		$this->pid = intval($PA['row']['pid']);

			// get existing articles for this product, if they where not fetched yet
		if ($this->existingArticles == NULL) $this->existingArticles = $this->belib->getArticlesOfProduct($this->uid);

			// get all attributes for this product, if they where not fetched yet
		if ($this->attributes == NULL) $this->attributes = $this->belib->getAttributesForProduct($this->uid, true, true, true);

		$rowCount = $this->calculateRowCount();
		if ($rowCount > 1000)	{
			return sprintf($fObj->sL('LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.to_much_articles'), $rowCount);
		}

			// build the row addition for all attributes that have no valuelist and that are
			// product attributes
			// The product attributes have to be handled in a special way, because for them, we
			// have to store some values. The select attributes without a valuelist have to be
			// filled in the article itself.
		$extraHeader = array();

		/*
		$extraRowData = array();
		if (is_array($this->attributes['rest'])) {
			foreach ($this->attributes['rest'] as $attribute) {
				$extraHeader[] = $attribute['attributeData']['title'];
				if ($attribute['uid_correlationtype'] == 4) {
					$value = $this->belib->getAttributeValue($this->uid, $attribute['uid_foreign'], 'tx_commerce_products_attributes_mm', $attribute, $attribute['attributeData']);
					if (empty($value)) $value = '&nbsp';
					$extraRowData[] = $value;
				} else {
					$extraRowData[] = '&nbsp;';
				}
			}
		}
		*/

			// create the headrow from the product attributes, select attributes without valuelist and normal select attributes
		$colCount = 0;
		$headRow = $this->getHeadRow($colCount, array('&nbsp;'));

		$valueMatrix = $this->getValues();
		$counter = 0;
		$resultRows = '';
		$resultRows .= $fObj->sL('LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.create_warning');

		$this->getRows($valueMatrix, $resultRows, $counter, $headRow);

		$emptyRow = '<tr><td><input type="checkbox" name="createList[empty]" /></td>';
		$emptyRow .= '<td colspan="' .($colCount -1) .'">' .$fObj->sL('LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.empty_article') .'</td></tr>';

			// create a checkbox for selecting all articles
		$selectJS = '<script language="JavaScript">
			function updateArticleList()	{
				var sourceSB = document.getElementById("selectAllArticles");
				for (var i = 1; i <= ' .$rowCount .'; i++)	{
					document.getElementById("createRow_" +i).checked = sourceSB.checked;
				}
			}
		</script>';
		
		if (count($valueMatrix) > 0)	{
			$selectAllRow = '<tr><td><input type="checkbox" id="selectAllArticles" onclick="updateArticleList()" /></td>';
			$selectAllRow .= '<td colspan="' .($colCount -1) .'">' .$fObj->sL('LLL:EXT:commerce/locallang_db.xml:tx_commerce_products.select_all_articles') .'</td></tr>';
		}

		$result = '<table border="0">' .$selectJS .$headRow .$emptyRow .$selectAllRow .$resultRows;

		$result .= '</table>';

		return $result;
	}


	/**
	 * This method builds up a matrix from the ct1 attributes with valuelist
	 *
	 * @param	integer		$index: The index we're currently working on
	 * @return	[type]		...
	 */
	function getValues($index = 0) {
		$result = array();

		if ($index >= count($this->attributes['ct1'])) return;

		if(is_array($this->attributes['ct1']))	{
			foreach ($this->attributes['ct1'][$index]['valueList'] as $aValue)	{
				$data['aUid'] = $this->attributes['ct1'][$index]['attributeData']['uid'];
				$data['vUid'] = $aValue['uid'];
				$data['vLabel'] = $aValue['value'];
				$newI = $index +1;
				$other = $this->getValues($newI);
				if ($other) $data['other'] = $other;
				$result[] = $data;
			}
		}

		return $result;
	}

	/**
	 * Returns the html table rows for the article matrix
	 *
	 * @param	array		$data: The data we should build the matrix from
	 * @param	array		$resultRows: The resulting rows
	 * @param	integer		$counter: The article counter
	 * @param	string		$headRow: The header row (html for inserting after a number of articles)
	 * @param	array		$extraRowData: some additional data like checkbox column
	 * @param	integer		$index: The level inside the matrix
	 * @param	array		$row: The current row data
	 * @return
	 */
	function getRows($data, &$resultRows, &$counter, $headRow, $extraRowData = array(), $index = 1, $row = array()) {
		if (is_array($data)) {
			foreach ($data as $dataItem)	{
				$dummyData = $dataItem;
				unset($dummyData['other']);
				$row[$index] = $dummyData;

				if (is_array($dataItem['other']))	{
					$this->getRows($dataItem['other'], $resultRows, $counter, $headRow, $extraRowData, ($index +1), $row);
				} else {
						// serialize data for formsaveing
					$labelData = array();
					$hashData = array();
					reset($row);
					while (list($rdIndex, $rd) = each($row))	{
						$hashData[$rd['aUid']] = $rd['vUid'];
						$labelData[] = $rd['vLabel'];
					}
					asort($hashData);

						// try to fetch an article with this special attribute values
					$hashData = serialize($hashData);
					$hash = md5($hashData);

					if ($this->belib->checkArray($hash, $this->existingArticles, 'attribute_hash')) continue;

					$counter++;

						// select format and insert headrow if we are in the 20th row
					if (($counter % 20) == 0) $resultRows .= $headRow;
					$class = ($counter % 2 == 1) ? 'background-color:silver' : 'background:none';

						// create the row
					$resultRows .= '<tr><td style="' .$class .'">';
					$resultRows .= '<input type="checkbox" name="createList[' .$counter .']" id="createRow_' .$counter .'" />';
					$resultRows .= '<input type="hidden" name="createData[' .$counter .']" value="' .htmlspecialchars($hashData) .'" /></td>';

					$resultRows .= '<td style="' .$class .'">' .implode('</td><td style="' .$class .'">', $labelData) .'</td>';
					if (count($extraRowData) > 0)	{
						$resultRows .= '<td style="' .$class .'">' .implode('</td><td style="' .$class .'">', $extraRowData) .'</td>';
					}
					$resultRows .= '</tr>';
				}
			}
		}
	}

	/**
	 * returns the number of articles that would be created with the number of attributes the product have.
	 *
	 * @return	The number of rows
	 */
	function calculateRowCount() {
		$result = 1;
		if(is_array($this->attributes['ct1']))	{
			foreach ($this->attributes['ct1'] as $attribute)	{
				$valueCount = count($attribute['valueList']);
				$result *= $valueCount;
			}
		}
		return $result;
	}

	/**
	 * Returns the HTML code for the header row
	 *
	 * @param	integer		$colCount: The number of columns we have
	 * @param	array		$acBefore: The additional columns before the attribute columns
	 * @param	array		$acAfter: The additional columns after the attribute columns
	 * @return	The HTML header code
	 */
	function getHeadRow(&$colCount, $acBefore = NULL, $acAfter = NULL, $addTR = true)	{
		if ($addTR)	{ $result .= '<tr>'; }
		
		if ($acBefore != NULL) $result .= '<th>' .implode('</th><th>', $acBefore) .'</th>';
		if (is_array($this->attributes['ct1']))	{
			foreach ($this->attributes['ct1'] as $attribute)	{
				$result .= '<th>' .$attribute['attributeData']['title'] .'</th>';
				$colCount++;
			}
		}
		if ($acAfter != NULL) $result .= '<th>' .implode('</th><th>', $acAfter) .'</th>';
		
		if ($addTR)	{ $result .= '</tr>'; }

		$colCount += count($acBefore) +count($acAfter);

		return $result;
	}

	/**
	 * Creates all articles that should be created (defined through the POST vars)
	 *
	 * @param	array		$PA: ...
	 * @return	void
	 */
	function createArticles($PA)	{
		if (is_array(t3lib_div::_GP('createList')))	{
			foreach (t3lib_div::_GP('createList') as $key => $switch)	{
				$this->createArticle($PA, $key);
			}
		}
	}

	/**
	 * Updates all articles.
	 * This adds new attributes to all existing articles that where added to the parent
	 * product or categories.
	 *
	 * @param	array		$PA: ...
	 * @return	void
	 */
	function updateArticles($PA)	{
		$fullAttributeList = array();
		if (!is_array($this->attributes['ct1']))	return;
		
		foreach ($this->attributes['ct1'] as $attributeData)	{
			$fullAttributeList[] = $attributeData['uid_foreign'];
		}

		if (is_array(t3lib_div::_GP('updateData')))	{
			foreach (t3lib_div::_GP('updateData') as $articleUid => $relData)	{
				foreach ($relData as $attributeUid => $attributeValueUid)	{
					if ($attributeValueUid == 0)	continue;

					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'tx_commerce_articles_article_attributes_mm',
						'uid_local='.$articleUid.' AND uid_foreign='.$attributeUid,
						array ('uid_valuelist' => $attributeValueUid)
					);
				}

				$this->belib->updateArticleHash($articleUid, $fullAttributeList);
			}
		}
	}

	/**
	 * Creates an article in the database and all needed releations to attributes and values.
	 * It also creates a new prices and assignes it to the new article.
	 *
	 * @param	array		$PA: ...
	 * @param	string		$key: The key in the POST var array
	 * @return	articleUID	Returns the new articleUid if success
	 */
	function createArticle($PA, $key)	{
			// get the create data
		$data = t3lib_div::_GP('createData');
		$data = $data[$key];
		$hash = md5($data);
		$data = unserialize($data);
		
		// get the highest sorting
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,sorting',
			'tx_commerce_articles',
			'uid_product=' .$this->uid,
			'', 'sorting DESC', 1
		);
		$sorting = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);


			// create article data array
		$articleData = array(
			'pid' => $this->pid,
			'crdate' => time(),
			'title' => $PA['row']['title'],
			'uid_product' => $this->uid,
			'sorting' => ($sorting['sorting'] *2),
			'article_attributes' => count($this->attributes['rest']) +count($data),
			'attribute_hash' => $hash,
			'article_type_uid' => 1,
			
		);
		
		$temp = t3lib_BEfunc::getModTSconfig($this->pid,'mod.commerce.category');
		if ($temp) {
	 		$moduleConfig = t3lib_BEfunc::implodeTSParams($temp['properties']);
	 		$defaultTax= (int)$moduleConfig['defaultTaxValue'];
			if ($defaultTax > 0) {
				$articleData['tax'] = $defaultTax;
			}
		}
		

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_articlecreator.php']['preinsert'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_articlecreator.php']['preinsert'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		foreach($hookObjectsArr as $hookObj)    {
			if (method_exists($hookObj, 'preinsert')) {
				$hookObj->preinsert($articleData);
			}
		}


			// create the article
		$articleRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles', $articleData);
		$articleUid = $GLOBALS['TYPO3_DB']->sql_insert_id($articleRes);

			// create a new price that is assigned to the new article
		$priceRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_commerce_article_prices',
			array (
				'pid' => $this->pid,
				'crdate' => time(),
				'tstamp' => time(),
				'uid_article' => $articleUid
			)
		);

			// now write all relations between article and attributes into the database
		$relationBaseData = array(
			'uid_local' => $articleUid,
		);

		$createdArticleRelations = array();
		$relationCreateData = $relationBaseData;
		if (is_array($data))	{
			foreach ($data as $selectAttributeUid => $selectAttributeValueUid)	{
				$relationCreateData['uid_foreign'] = $selectAttributeUid;
				$relationCreateData['uid_valuelist'] = $selectAttributeValueUid;

				$createdArticleRelations[] = $relationCreateData;
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
			}
		}

		if (is_array($this->attributes['rest']))	{
			foreach ($this->attributes['rest'] as $attribute)	{
				if (empty($attribute['attributeData']['uid']))	continue;
				
				$relationCreateData = $relationBaseData;

				$relationCreateData['sorting'] = $attribute['sorting'];
				$relationCreateData['uid_foreign'] = $attribute['attributeData']['uid'];
				if ($attribute['uid_correlationtype'] == 4)	{
					$relationCreateData['uid_product'] = $this->uid;
				}

				$relationCreateData['default_value'] = '';
				$relationCreateData['value_char'] = '';
				$relationCreateData['uid_valuelist'] = $attribute['uid_valuelist'];

				if (!$this->belib->isNumber($attribute['default_value']))	{
				  $relationCreateData['default_value'] = $attribute['default_value'];
				} else {
				  $relationCreateData['value_char'] = $attribute['default_value'];
				}

				$createdArticleRelations[] = $relationCreateData;
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
			}
		}

		// update the article
		// we have to write the xml datastructure for this article. This is needed
		// to have the correct values inserted on first call of the article.
		$this->belib->updateArticleXML($createdArticleRelations, false, $articleUid);
		
		
		/**
		 * @author 	Ingo Schmitt	<is@marketing-factory.de>
		 */
		// Now check, if the parent Product is already lokalised, so creat Article in the lokalised version
		// Select from Database different localisations
		$resOricArticle=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_articles','uid='.$articleUid.' and deleted = 0');
		$origArticle=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($resOricArticle);
		
		$resLocalisedProducts=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_commerce_products','l18n_parent='.$this->uid.' and deleted = 0');
		
		if (($resLocalisedProducts) && ($GLOBALS['TYPO3_DB']->sql_num_rows($resLocalisedProducts)>0)) {
			// Only if there are products
			
			while ($localisedProducts = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resLocalisedProducts))	{
				// walk thru and create articles
				$destLanguage=$localisedProducts['sys_language_uid'];
				// get the highest sorting
				$langIsoCode = t3lib_BEfunc::getRecord('sys_language', intval($destLanguage), 'static_lang_isocode');
				$langIdent = t3lib_BEfunc::getRecord('static_languages', intval($langIsoCode['static_lang_isocode']), 'lg_typo3');
				$langIdent = strtoupper($langIdent['lg_typo3']);
				
				
				

				// create article data array
				$articleData = array(
					'pid' => $this->pid,
					'crdate' => time(),
					'title' => $PA['row']['title'],
					'uid_product' => $localisedProducts['uid'],
					'sys_language_uid' => $localisedProducts['sys_language_uid'],
					'l18n_parent' => $articleUid,
					'sorting' => ($sorting['sorting'] *2),
					'article_attributes' => count($this->attributes['rest']) +count($data),
					'attribute_hash' => $hash,
					'article_type_uid' => 1,
					'attributesedit' => $this->belib->buildLocalisedAttributeValues($origArticle['attributesedit'],$langIdent),
					
				);
				
					// create the article
				$articleRes = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles', $articleData);
				$LocArticleUid = $GLOBALS['TYPO3_DB']->sql_insert_id($articleRes);
				
				
				// get all relations to attributes from the old article and copy them to new article
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_commerce_articles_article_attributes_mm', 'uid_local=' .$origArticle['uid'] .' AND uid_valuelist=0');
			
				while ($origRelation = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$origRelation['uid_local'] = $LocArticleUid;
					
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $origRelation);
				}
				$this->belib->updateArticleXML($createdArticleRelations, false,$LocArticleUid);
				
			}
		}
		return $articleUid;
		
	}

	/**
	 * Creates a checkbox that has to be toggled for creating a new price for an article.
	 * The handling for creating the new price is inside the tcehooks
	 */
	function createNewPriceCB($PA, $fObj) {
		$content .= '<div id="typo3-newRecordLink">';
		$content .= '<input type="checkbox" name="data[tx_commerce_articles][' .$PA['row']['uid'] .'][create_new_price]" />';
		$content .= $GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be.php:articles.add_article_price', 1);

		$content .= '</div>';
		return $content;
	}
	/**
	 * Creates ...
	 */
	function createNewScalePricesCount($PA, $fObj) {
	
		$content = '<input style="width: 77px;" class="formField1" maxlength="20" type="input" name="data[tx_commerce_articles][' .$PA['row']['uid'] .'][create_new_scale_prices_count]" />';


		return $content;
	}	
	/**
	 * Creates ...
	 */
	function createNewScalePricesSteps($PA, $fObj) {


		$content = '<input style="width: 77px;" class="formField1" maxlength="20"type="input" name="data[tx_commerce_articles][' .$PA['row']['uid'] .'][create_new_scale_prices_steps]" />';	


		return $content;
	}	
	/**
	 * Creates ...
	 */
	function createNewScalePricesStartAmount($PA, $fObj) {
	
		$content = '<input style="width: 77px;" class="formField1" maxlength="20" type="input" name="data[tx_commerce_articles][' .$PA['row']['uid'] .'][create_new_scale_prices_startamount]" />';


		return $content;
	}	


	/**
	 * Creates a delete button that is assigned to a price. If the button is pressed the price will be deleted from the article
	 */
	function deletePriceButton($PA, $fObj)	{
			// get the return URL.This is need to fit all possible combinations of GET vars
		$returnUrl = explode('/', $fObj->returnUrl);
		$returnUrl = $returnUrl[(count($returnUrl) -1)];

			// get the UID of the price
		$name = explode('caption_', $PA['itemFormElName']);
		$name = explode(']', $name[1]);
		$pUid = $name[0];

			// build the link code
		$result = '<a href="#" onclick="deleteRecord(\'tx_commerce_article_prices\', ' .$pUid .', \'' .$returnUrl .'\');">';
		$result .= '<img src="../typo3/gfx/garbage.gif" border="0" />';
		$result .=  $GLOBALS['LANG']->sL('LLL:EXT:commerce/locallang_be.php:articles.del_article_price', 1).'</a>';

		return $result;
	}

	/**
	 * Returns a hidden field with the name and value of the current form element
	 */
	function articleUid($PA, $fObj) {
		$content.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'">';
 		return $content;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/class.tx_commerce_articlecreator.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']["ext/commerce/class.tx_commerce_articlecreator.php"]);
}
?>
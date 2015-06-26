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
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once(PATH_TXCOMMERCE . 'Classes/Utility/GeneralUtility.php');

/**
 * This class provides several methods for creating articles from within
 * a product. It provides the user fields and creates the entries in the
 * database.
 *
 * Class Tx_Commerce_Utility_ArticleCreatorUtility
 *
 * @author 2005-2012 Thomas Hempel <thomas@work.de>
 */
class Tx_Commerce_Utility_ArticleCreatorUtility {
	/**
	 * Existing articles
	 *
	 * @var array
	 */
	protected $existingArticles = NULL;

	/**
	 * Attributes
	 *
	 * @var array
	 */
	protected $attributes = NULL;

	/**
	 * Flatted attributes
	 *
	 * @var array
	 */
	protected $flattedAttributes = array();

	/**
	 * Uid
	 *
	 * @var int
	 */
	protected $uid = 0;

	/**
	 * Page id
	 *
	 * @var int
	 */
	protected $pid = 0;

	/**
	 * Backend utility
	 *
	 * @var Tx_Commerce_Utility_BackendUtility
	 */
	protected $belib;

	/**
	 * Return url
	 *
	 * @var string
	 */
	protected $returnUrl;

	/**
	 * Constructor
	 *
	 * @return self
	 */
	public function __construct() {
		$this->belib = GeneralUtility::makeInstance('Tx_Commerce_Utility_BackendUtility');
		$this->returnUrl = htmlspecialchars(urlencode(GeneralUtility::_GP('returnUrl')));
	}

	/**
	 * Initializes the Article Creator if it's not called directly from the Flexforms
	 *
	 * @param int $uid Uid of the product
	 * @param int $pid Page id
	 *
	 * @return void
	 */
	public function init($uid, $pid) {
		$this->uid = (int) $uid;
		$this->pid = (int) $pid;

		if ($this->attributes == NULL) {
			$this->attributes = $this->belib->getAttributesForProduct($this->uid, TRUE, TRUE, TRUE);
		}
	}

	/**
	 * Get all articles that already exist. Add some buttons for editing.
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string a HTML-table with the articles
	 */
	public function existingArticles(array $parameter) {
		$backendUser = $this->getBackendUser();
		$database = $this->getDatabaseConnection();

		$this->uid = (int)$parameter['row']['uid'];
		$this->pid = (int)$parameter['row']['pid'];

		// get all attributes for this product, if they where not fetched yet
		if ($this->attributes == NULL) {
			$this->attributes = $this->belib->getAttributesForProduct($this->uid, TRUE, TRUE, TRUE);
		}

		// get existing articles for this product, if they where not fetched yet
		if ($this->existingArticles == NULL) {
			$this->existingArticles = $this->belib->getArticlesOfProduct($this->uid, '', 'sorting');
		}

		if ((count($this->existingArticles) == 0) || ($this->uid == 0) || ($this->existingArticles === FALSE)) {
			return 'No articles existing for this product';
		}

		// generate the security token
		$formSecurityToken = '&prErr=1&vC=' . $backendUser->veriCode() . BackendUtility::getUrlToken('tceAction');

		$colCount = 0;
		$headRow = $this->getHeadRow($colCount, NULL, NULL, FALSE);
		$result = '
			<input type="hidden" name="deleteaid" value="0" />
			<table border="0">
				';

		$lastUid = 0;

		$result .= '<tr><td>&nbsp;</td>' . $headRow . '</td><td colspan="5">&nbsp;</td></tr>';

		for ($i = 0, $articleCount = count($this->existingArticles); $i < $articleCount; $i++) {
			$article = $this->existingArticles[$i];

			$result .= '<tr><td style="border-top:1px black solid; border-right: 1px gray dotted"><strong>' .
				htmlspecialchars($article['title']) . '</strong><br />UID:' . (int) $article['uid'] . '</td>';

			if (is_array($this->attributes['ct1'])) {
				foreach ($this->attributes['ct1'] as $attribute) {
						// get all article attribute relations
					$atrRes = $database->exec_SELECTquery(
						'uid_valuelist, default_value, value_char',
						'tx_commerce_articles_article_attributes_mm',
						'uid_local=' . $article['uid'] . ' AND uid_foreign=' . $attribute['uid_foreign']
					);

					$cellStyle = 'border-top:1px black solid; border-right: 1px gray dotted';
					while (($attributeData = $database->sql_fetch_assoc($atrRes))) {
						if ($attribute['attributeData']['has_valuelist'] == 1) {
							if ($attributeData['uid_valuelist'] == 0) {
									// if the attribute has no value, create a select box with valid values
								$result .= '<td style="' . $cellStyle . '"><select name="updateData[' .
									(int) $article['uid'] . '][' . (int) $attribute['uid_foreign'] . ']" />';
								$result .= '<option value="0" selected="selected"></option>';
								foreach ($attribute['valueList'] as $attrValueUid => $attrValueData) {
									$result .= '<option value="' . (int) $attrValueUid . '">' . htmlspecialchars($attrValueData['value']) . '</option>';
								}
								$result .= '</select></td>';
							} else {
								$result .= '<td style="' . $cellStyle . '">' .
									htmlspecialchars(strip_tags($attribute['valueList'][$attributeData['uid_valuelist']]['value'])) . '</td>';
							}
						} elseif (!empty($attributeData['value_char'])) {
							$result .= '<td style="' . $cellStyle . '">' .
								htmlspecialchars(strip_tags($attributeData['value_char'])) . '</td>';
						} else {
							$result .= '<td style="' . $cellStyle . '">' .
								htmlspecialchars(strip_tags($attributeData['default_value'])) . '</td>';
						}
					}
				}
			}

			// the edit pencil (with jump back to this dataset)
			$result .= '<td style="border-top:1px black solid">
				<a href="#" onclick="document.location=\'alt_doc.php?returnUrl=alt_doc.php?edit[tx_commerce_products][' .
				(int) $this->uid . ']=edit&amp;edit[tx_commerce_articles][' . (int) $article['uid'] . ']=edit\'; return false;">';
			$result .= IconUtility::getSpriteIcon('actions-document-open') . '</a></td>';

			// add the hide button
			$result .= '<td style="border-top:1px black solid">
				<a href="#" onclick="return jumpToUrl(\'tce_db.php?&amp;data[tx_commerce_articles][' .
				(int) $article['uid'] . '][hidden]=' . (!$article['hidden']) . '&amp;redirect=alt_doc.php?edit[tx_commerce_products][' .
				(int) $this->uid . ']=edit\');">';
			$result .= '<td style="border-top:1px black solid">
				<a href="#" onclick="return jumpToUrl(\'tce_db.php?&amp;data[tx_commerce_articles][' .
				$article['uid'] . '][hidden]=' . (!$article['hidden']) . '&amp;redirect=alt_doc.php?edit[tx_commerce_products][' .
				$this->uid . ']=edit' . $formSecurityToken . '\');">';
			$result .= '<img src="/' . TYPO3_mainDir . '/gfx/button_' . (($article['hidden']) ?
					'un' :
					'') . 'hide.gif" border="0" /></a></td>';

			$buttonUp = '<img src="/' . TYPO3_mainDir . '/gfx/button_up.gif" width="11" height="10" align="top" />';
			$buttonDown = '<img src="/' . TYPO3_mainDir . '/gfx/button_down.gif" width="11" height="10" align="top" />';
			$clear = '<img src="/' . TYPO3_mainDir . 'clear.gif" width="11" height="10">';
			$garbage = '<img src="/' . TYPO3_mainDir . '/gfx/garbage.gif" />';

			// add the sorting buttons
			// UP
			if (isset($this->existingArticles[$i - 1])) {
				if (isset($this->existingArticles[$i - 2])) {
					$moveItTo = '-' . (int) $this->existingArticles[$i - 2]['uid'];
				} else {
					$moveItTo = (int) $article['pid'];
				}

				$params = 'cmd[tx_commerce_articles][' . (int) $article['uid'] . '][move]=' . $moveItTo;
				$result .= '<td style="border-top:1px black solid"><a href="#" onClick="return jumpToUrl(\'tce_db.php?' . $params .
					'&redirect=alt_doc.php?edit[tx_commerce_products][' . (int) $this->uid .
					']=edit\');">' . $buttonUp . '</a></td>';
				$result .= '<td style="border-top:1px black solid"><a href="#" onClick="return jumpToUrl(\'tce_db.php?' . $params .
					$formSecurityToken . '&redirect=alt_doc.php?edit[tx_commerce_products][' . (int) $this->uid .
					']=edit\');">' . $buttonUp . '</a></td>';
			} else {
				$result .= '<td>' . $clear . '</td>';
			}

			// DOWN
			if (isset($this->existingArticles[$i + 1])) {
				$params = 'cmd[tx_commerce_articles][' . (int) $article['uid'] . '][move]=-' . (int) $this->existingArticles[$i + 1]['uid'];
				$result .= '<td style="border-top:1px black solid"><a href="#" onClick="return jumpToUrl(\'tce_db.php?' . $params .
					'&redirect=alt_doc.php?edit[tx_commerce_products][' . (int) $this->uid .
					']=edit\');">' . $buttonDown . '</a></td>';
				$result .= '<td style="border-top:1px black solid"><a href="#" onClick="return jumpToUrl(\'tce_db.php?' . $params .
					$formSecurityToken . '&redirect=alt_doc.php?edit[tx_commerce_products][' . $this->uid .
					']=edit\');">' . $buttonDown . '</a></td>';
			} else {
				$result .= '<td>' . $clear . '</td>';
			}

			// add the delete icon
			$result .= '<td style="border-top:1px black solid"><a href="#" onclick="deleteRecord(\'tx_commerce_articles\', ' .
				(int) $article['uid'] . ', \'alt_doc.php?edit[tx_commerce_products][' . (int) $this->uid .
				']=edit\');">' . $garbage . '</a></td>';
			$result .= '</tr>';

			if ($article['uid'] > $lastUid) {
				$lastUid = $article['uid'];
			}
		}

		$result .= '</table>';

		return $result;
	}

	/**
	 * Create a matrix of producible articles
	 *
	 * @param array $parameter Parameter
	 * @param FormEngine $fObj Form engine
	 *
	 * @return string A HTML-table with checkboxes and all needed stuff
	 */
	public function producibleArticles(array $parameter, FormEngine $fObj) {
		$this->uid = (int)$parameter['row']['uid'];
		$this->pid = (int)$parameter['row']['pid'];

			// get existing articles for this product, if they where not fetched yet
		if ($this->existingArticles == NULL) {
			$this->existingArticles = $this->belib->getArticlesOfProduct($this->uid);
		}

			// get all attributes for this product, if they where not fetched yet
		if ($this->attributes == NULL) {
			$this->attributes = $this->belib->getAttributesForProduct($this->uid, TRUE, TRUE, TRUE);
		}

		$rowCount = $this->calculateRowCount();
		if ($rowCount > 1000) {
			return sprintf(
				$fObj->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.to_many_articles'),
				$rowCount
			);
		}

		// create the headrow from the product attributes, select attributes without
		// valuelist and normal select attributes
		$colCount = 0;
		$headRow = $this->getHeadRow($colCount, array('&nbsp;'));

		$valueMatrix = (array) $this->getValues();
		$counter = 0;
		$resultRows = '';
		$resultRows .= $fObj->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.create_warning');

		$this->getRows($valueMatrix, $resultRows, $counter, $headRow);

		$emptyRow = '<tr><td><input type="checkbox" name="createList[empty]" /></td>';
		$emptyRow .= '<td colspan="' . ($colCount - 1) . '">' .
			$fObj->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.empty_article') .
			'</td></tr>';

			// create a checkbox for selecting all articles
		$selectJs = '<script language="JavaScript">
			function updateArticleList() {
				var sourceSB = document.getElementById("selectAllArticles");
				for (var i = 1; i <= ' . $rowCount . '; i++) {
					document.getElementById("createRow_" +i).checked = sourceSB.checked;
				}
			}
		</script>';

		$selectAllRow = '';
		if (count($valueMatrix) > 0) {
			$selectAllRow = '<tr><td><input type="checkbox" id="selectAllArticles" onclick="updateArticleList()" /></td>';
			$selectAllRow .= '<td colspan="' . ($colCount - 1) . '">' .
				$fObj->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_db.xml:tx_commerce_products.select_all_articles') .
				'</td></tr>';
		}

		$result = '<table border="0">' . $selectJs . $headRow . $emptyRow . $selectAllRow . $resultRows . '</table>';

		return $result;
	}

	/**
	 * This method builds up a matrix from the ct1 attributes with valuelist
	 *
	 * @param int $index The index we're currently working on
	 *
	 * @return array
	 */
	protected function getValues($index = 0) {
		$result = array();

		if (count($this->attributes['ct1']) > $index) {
			if (is_array($this->attributes['ct1'])) {
				foreach ($this->attributes['ct1'][$index]['valueList'] as $aValue) {
					$data['aUid'] = (int) $this->attributes['ct1'][$index]['attributeData']['uid'];
					$data['vUid'] = (int) $aValue['uid'];
					$data['vLabel'] = $aValue['value'];

					$newI = $index + 1;
					$other = $this->getValues($newI);
					if ($other) {
						$data['other'] = $other;
					}

					$result[] = $data;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the html table rows for the article matrix
	 *
	 * @param array $data The data we should build the matrix from
	 * @param string $resultRows The rendered resulting rows
	 * @param int $counter The article counter
	 * @param string $headRow The header row for inserting after a number of articles
	 * @param array $extraRowData Some additional data like checkbox column
	 * @param int $index The level inside the matrix
	 * @param array $row The current row data
	 *
	 * @return void
	 */
	protected function getRows(array $data, &$resultRows, &$counter, $headRow, array $extraRowData = array(), $index = 1,
		array $row = array()
	) {
		if (is_array($data)) {
			foreach ($data as $dataItem) {
				$dummyData = $dataItem;
				unset($dummyData['other']);
				$row[$index] = $dummyData;

				if (is_array($dataItem['other'])) {
					$this->getRows($dataItem['other'], $resultRows, $counter, $headRow, $extraRowData, ($index + 1), $row);
				} else {
						// serialize data for formsaveing
					$labelData = array();
					$hashData = array();

					foreach ($row as $rd) {
						$hashData[$rd['aUid']] = $rd['vUid'];
						$labelData[] = $rd['vLabel'];
					}
					asort($hashData);

						// try to fetch an article with this special attribute values
					$hashData = serialize($hashData);
					$hash = md5($hashData);

					if ($this->belib->checkArray($hash, $this->existingArticles, 'attribute_hash')) {
						continue;
					}

					$counter++;

					// select format and insert headrow if we are in the 20th row
					if (($counter % 20) == 0) {
						$resultRows .= $headRow;
					}
					$class = ($counter % 2 == 1) ? 'background-color:silver' : 'background:none';

					// create the row
					$resultRows .= '<tr><td style="' . $class . '">';
					$resultRows .= '<input type="checkbox" name="createList[' . $counter . ']" id="createRow_' . $counter . '" />';
					$resultRows .= '<input type="hidden" name="createData[' . $counter . ']" value="' .
						htmlspecialchars($hashData) . '" /></td>';

					$resultRows .= '<td style="' . $class . '">' .
						implode('</td><td style="' . $class . '">', Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($labelData)) .
						'</td>';
					if (count($extraRowData) > 0) {
						$resultRows .= '<td style="' . $class . '">' .
							implode('</td><td style="' . $class . '">', Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($extraRowData)) .
							'</td>';
					}
					$resultRows .= '</tr>';
				}
			}
		}
	}

	/**
	 * Returns the number of articles that would be created with the number
	 * of attributes the product have.
	 *
	 * @return int The number of rows
	 */
	protected function calculateRowCount() {
		$result = 1;
		if (is_array($this->attributes['ct1'])) {
			foreach ($this->attributes['ct1'] as $attribute) {
				$valueCount = count($attribute['valueList']);
				$result *= $valueCount;
			}
		}
		return $result;
	}

	/**
	 * Returns the HTML code for the header row
	 *
	 * @param int $colCount The number of columns we have
	 * @param array $acBefore The additional columns before the attribute columns
	 * @param array $acAfter The additional columns after the attribute columns
	 * @param bool $addTr Add table row
	 *
	 * @return string The HTML header code
	 */
	protected function getHeadRow(&$colCount, array $acBefore = NULL, array $acAfter = NULL, $addTr = TRUE) {
		$result = '';

		if ($addTr) {
			$result .= '<tr>';
		}

		if ($acBefore != NULL) {
			$result .= '<th>' . implode('</th><th>', Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($acBefore)) . '</th>';
		}

		if (is_array($this->attributes['ct1'])) {
			foreach ($this->attributes['ct1'] as $attribute) {
				$result .= '<th>' . htmlspecialchars(strip_tags($attribute['attributeData']['title'])) . '</th>';
				$colCount++;
			}
		}

		if ($acAfter != NULL) {
			$result .= '<th>' . implode('</th><th>', Tx_Commerce_Utility_GeneralUtility::removeXSSStripTagsArray($acAfter)) . '</th>';
		}

		if ($addTr) {
			$result .= '</tr>';
		}

		$colCount += count($acBefore) + count($acAfter);

		return $result;
	}

	/**
	 * Creates all articles that should be created (defined through the POST vars)
	 *
	 * @param array $parameter Parameter
	 *
	 * @return void
	 */
	public function createArticles(array $parameter) {
		$database = $this->getDatabaseConnection();

		if (is_array(GeneralUtility::_GP('createList'))) {
			$res = $database->exec_SELECTquery(
				'uid,value',
				'tx_commerce_attribute_values',
				'deleted = 0'
			);

			while (($row = $database->sql_fetch_assoc($res))) {
				$this->flattedAttributes[$row['uid']] = $row['value'];
			}

			foreach (array_keys(GeneralUtility::_GP('createList')) as $key) {
				$this->createArticle($parameter, $key);
			}

			BackendUtility::setUpdateSignal('updateFolderTree');
		}
	}

	/**
	 * Updates all articles.
	 * This adds new attributes to all existing articles that where added
	 * to the parent product or categories.
	 *
	 * @return void
	 */
	public function updateArticles() {
		$fullAttributeList = array();

		if (!is_array($this->attributes['ct1'])) {
			return;
		}

		foreach ($this->attributes['ct1'] as $attributeData) {
			$fullAttributeList[] = $attributeData['uid_foreign'];
		}

		if (is_array(GeneralUtility::_GP('updateData'))) {
			foreach (GeneralUtility::_GP('updateData') as $articleUid => $relData) {
				foreach ($relData as $attributeUid => $attributeValueUid) {
					if ($attributeValueUid == 0) {
						continue;
					}

					$database = $this->getDatabaseConnection();

					$database->exec_UPDATEquery(
						'tx_commerce_articles_article_attributes_mm',
						'uid_local=' . $articleUid . ' AND uid_foreign=' . $attributeUid,
						array ('uid_valuelist' => $attributeValueUid)
					);
				}

				$this->belib->updateArticleHash($articleUid, $fullAttributeList);
			}
		}
	}

	/**
	 * Creates article title out of attributes
	 *
	 * @param array $parameter Parameter
	 * @param array $data Data
	 *
	 * @return string Returns the product title + attribute titles for article title
	 */
	protected function createArticleTitleFromAttributes(array $parameter, array $data) {
		$content = $parameter['title'];
		if (is_array($data) && count($data)) {
			$selectedValues = array();
			foreach ($data as $value) {
				if ($this->flattedAttributes[$value]) {
					$selectedValues[] = $this->flattedAttributes[$value];
				}
			}
			if (count($selectedValues)) {
				$content .= ' (' . implode(', ', $selectedValues) . ')';
			}
		}
		return $content;
	}

	/**
	 * Creates an article in the database and all needed releations to attributes
	 * and values. It also creates a new prices and assignes it to the new article.
	 *
	 * @param array $parameter Parameter
	 * @param string $key The key in the POST var array
	 *
	 * @return int Returns the new articleUid if success
	 */
	protected function createArticle(array $parameter, $key) {
		$database = $this->getDatabaseConnection();

		// get the create data
		$data = GeneralUtility::_GP('createData');
		$hash = '';
		if (is_array($data)) {
			$data = $data[$key];
			$hash = md5($data);
			$data = unserialize($data);
		}
		$database->debugOutput = 1;
		$database->store_lastBuiltQuery = TRUE;
		// get the highest sorting
		$sorting = $database->exec_SELECTgetSingleRow(
			'uid, sorting',
			'tx_commerce_articles',
			'uid_product = ' . $this->uid,
			'',
			'sorting DESC',
			1
		);
		$sorting = (is_array($sorting) && isset($sorting['sorting'])) ? $sorting['sorting'] + 20 : 0;

		// create article data array
		$articleData = array(
			'pid' => $this->pid,
			'crdate' => time(),
			'title' => strip_tags($this->createArticleTitleFromAttributes($parameter, (array)$data)),
			'uid_product' => (int) $this->uid,
			'sorting' => $sorting,
			'article_attributes' => count($this->attributes['rest']) + count($data),
			'attribute_hash' => $hash,
			'article_type_uid' => 1,
		);

		$temp = BackendUtility::getModTSconfig($this->pid, 'mod.commerce.category');
		if ($temp) {
			$moduleConfig = BackendUtility::implodeTSParams($temp['properties']);
			$defaultTax = (int) $moduleConfig['defaultTaxValue'];
			if ($defaultTax > 0) {
				$articleData['tax'] = $defaultTax;
			}
		}

		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_articlecreator.php']['preinsert'])) {
			GeneralUtility::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/class.tx_commerce_articlecreator.php\'][\'preinsert\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0, please use instead
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/lib/class.tx_commerce_basket.php\'][\'createArticlePreInsert\']
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_articlecreator.php']['preinsert'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'preinsert')) {
					$hookObj->preinsert($articleData);
				}
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/ArticleCreatorUtility.php']['createArticlePreInsert'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/Classes/Utility/ArticleCreatorUtility.php']['createArticlePreInsert'] as $classRef) {
				$hookObj = &GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'preinsert')) {
					$hookObj->preinsert($articleData);
				}
			}
		}

		// create the article
		$database->exec_INSERTquery('tx_commerce_articles', $articleData);
		$articleUid = $database->sql_insert_id();

		// create a new price that is assigned to the new article
		$database->exec_INSERTquery(
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

		$productsAttributesRes = $database->exec_SELECTquery(
			'sorting, uid_local, uid_foreign',
			'tx_commerce_products_attributes_mm',
			'uid_local = ' . (int) $this->uid
		);
		$attributesSorting = array();
		while (($productsAttributes = $database->sql_fetch_assoc($productsAttributesRes))) {
			$attributesSorting[$productsAttributes['uid_foreign']] = $productsAttributes['sorting'];
		}

		if (is_array($data)) {
			foreach ($data as $selectAttributeUid => $selectAttributeValueUid) {
				$relationCreateData['uid_foreign'] = $selectAttributeUid;
				$relationCreateData['uid_valuelist'] = $selectAttributeValueUid;

				$relationCreateData['sorting'] = $attributesSorting[$selectAttributeUid];

				$createdArticleRelations[] = $relationCreateData;
				$database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
			}
		}

		if (is_array($this->attributes['rest'])) {
			foreach ($this->attributes['rest'] as $attribute) {
				if (empty($attribute['attributeData']['uid'])) {
					continue;
				}

				$relationCreateData = $relationBaseData;

				$relationCreateData['sorting'] = $attribute['sorting'];
				$relationCreateData['uid_foreign'] = $attribute['attributeData']['uid'];
				if ($attribute['uid_correlationtype'] == 4) {
					$relationCreateData['uid_product'] = $this->uid;
				}

				$relationCreateData['default_value'] = '';
				$relationCreateData['value_char'] = '';
				$relationCreateData['uid_valuelist'] = $attribute['uid_valuelist'];

				if (!$this->belib->isNumber($attribute['default_value'])) {
					$relationCreateData['default_value'] = $attribute['default_value'];
				} else {
					$relationCreateData['value_char'] = $attribute['default_value'];
				}

				$createdArticleRelations[] = $relationCreateData;

				$database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $relationCreateData);
			}
		}

		// update the article
		// we have to write the xml datastructure for this article. This is needed
		// to have the correct values inserted on first call of the article.
		$this->belib->updateArticleXML($createdArticleRelations, FALSE, $articleUid);

		// Now check, if the parent Product is already lokalised, so creat Article in
		// the lokalised version Select from Database different localisations
		$resOricArticle = $database->exec_SELECTquery('*', 'tx_commerce_articles', 'uid=' . (int) $articleUid . ' and deleted = 0');
		$origArticle = $database->sql_fetch_assoc($resOricArticle);

		$resLocalizedProducts = $database->exec_SELECTquery(
			'*',
			'tx_commerce_products',
			'l18n_parent = ' . (int) $this->uid . ' AND deleted = 0'
		);

		if (($resLocalizedProducts) && ($database->sql_num_rows($resLocalizedProducts) > 0)) {
			// Only if there are products
			while (($localizedProducts = $database->sql_fetch_assoc($resLocalizedProducts))) {
				// walk thru and create articles
				$destLanguage = $localizedProducts['sys_language_uid'];
				// get the highest sorting
				$langIsoCode = BackendUtility::getRecord('sys_language', (int) $destLanguage, 'static_lang_isocode');
				$langIdent = BackendUtility::getRecord('static_languages', (int) $langIsoCode['static_lang_isocode'], 'lg_typo3');
				$langIdent = strtoupper($langIdent['lg_typo3']);

				// create article data array
				$articleData = array(
					'pid' => $this->pid,
					'crdate' => time(),
					'title' => $parameter['title'],
					'uid_product' => $localizedProducts['uid'],
					'sys_language_uid' => $localizedProducts['sys_language_uid'],
					'l18n_parent' => $articleUid,
					'sorting' => $sorting['sorting'] + 20,
					'article_attributes' => count($this->attributes['rest']) + count($data),
					'attribute_hash' => $hash,
					'article_type_uid' => 1,
					'attributesedit' => $this->belib->buildLocalisedAttributeValues($origArticle['attributesedit'], $langIdent),
				);

				// create the article
				$database->exec_INSERTquery('tx_commerce_articles', $articleData);
				$localizedArticleUid = $database->sql_insert_id();

				// get all relations to attributes from the old article and copy them
				// to new article
				$res = $database->exec_SELECTquery(
					'*',
					'tx_commerce_articles_article_attributes_mm',
					'uid_local = ' . (int) $origArticle['uid'] . ' AND uid_valuelist = 0'
				);

				while (($origRelation = $database->sql_fetch_assoc($res))) {
					$origRelation['uid_local'] = $localizedArticleUid;

					$database->exec_INSERTquery('tx_commerce_articles_article_attributes_mm', $origRelation);
				}
				$this->belib->updateArticleXML($createdArticleRelations, FALSE, $localizedArticleUid);
			}
		}

		return $articleUid;
	}

	/**
	 * Returns a hidden field with the name and value of the current form element
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string
	 */
	public function articleUid(array $parameter) {
		return '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
			htmlspecialchars($parameter['itemFormElValue']) . '">';
	}


	/**
	 * Creates a checkbox that has to be toggled for creating a new price for an
	 * article. The handling for creating the new price is inside the tcehooks
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, this wont get replaced as it was removed from the api
	 */
	public function createNewPriceCB(array $parameter) {
		GeneralUtility::logDeprecatedFunction();

		$content = '<div id="typo3-newRecordLink">
			<input type="checkbox" name="data[tx_commerce_articles][' . (int) $parameter['row']['uid'] . '][create_new_price]" />' .
			$this->getLanguageService()->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:articles.add_article_price', 1) .
			'</div>';
		return $content;
	}

	/**
	 * Creates ...
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, this wont get replaced as it was removed from the api
	 */
	public function createNewScalePricesCount(array $parameter) {
		GeneralUtility::logDeprecatedFunction();

		return '<input style="width: 77px;" class="formField1" maxlength="20" type="input" name="data[tx_commerce_articles][' .
			(int) $parameter['row']['uid'] . '][create_new_scale_prices_count]" />';
	}

	/**
	 * Creates ...
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, this wont get replaced as it was removed from the api
	 */
	public function createNewScalePricesSteps(array $parameter) {
		GeneralUtility::logDeprecatedFunction();

		return '<input style="width: 77px;" class="formField1" maxlength="20"type="input" name="data[tx_commerce_articles][' .
			(int) $parameter['row']['uid'] . '][create_new_scale_prices_steps]" />';
	}

	/**
	 * Creates ...
	 *
	 * @param array $parameter Parameter
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, this wont get replaced as it was removed from the api
	 */
	public function createNewScalePricesStartAmount(array $parameter) {
		GeneralUtility::logDeprecatedFunction();

		return '<input style="width: 77px;" class="formField1" maxlength="20" type="input" name="data[tx_commerce_articles][' .
			(int) $parameter['row']['uid'] . '][create_new_scale_prices_startamount]" />';
	}

	/**
	 * Creates a delete button that is assigned to a price. If the button is pressed
	 * the price will be deleted from the article
	 *
	 * @param array $parameter Parameter
	 * @param FormEngine $fObj Form engine
	 *
	 * @return string
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, this wont get replaced as it was removed from the api
	 */
	public function deletePriceButton(array $parameter, FormEngine $fObj) {
		GeneralUtility::logDeprecatedFunction();

		// get the return URL.This is need to fit all possible combinations of GET vars
		$returnUrl = explode('/', $fObj->returnUrl);
		$returnUrl = $returnUrl[(count($returnUrl) - 1)];

		// get the UID of the price
		$name = explode('caption_', $parameter['itemFormElName']);
		$name = explode(']', $name[1]);
		$pUid = $name[0];

		// build the link code
		$result = '<a href="#" onclick="deleteRecord(\'tx_commerce_article_prices\', ' . (int) $pUid . ', \'' . $returnUrl . '\');">
			<img src="/' . TYPO3_mainDir . '/gfx/garbage.gif" border="0" />' .
			$this->getLanguageService()->sL('LLL:EXT:commerce/Resources/Private/Language/locallang_be.xml:articles.del_article_price', 1) .
			'</a>';

		return $result;
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

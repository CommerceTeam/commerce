<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Ingo Schmitt <is@marketing-factory.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * ItemProc Methods for flexforms
 */
class Tx_Commerce_Utility_TceformsUtility {
	/**
	 * @param array $data
	 */
	public function productsSelector(&$data) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

		$numArticleNumbersShow = 3;

		$addWhere = ' AND tx_commerce_articles.article_type_uid=' . NORMALARTICLETYPE . ' ';
		if ($data['row']['sys_language_uid'] > 0) {
			$addWhere .= ' and tx_commerce_products.sys_language_uid=' . $data['row']['sys_language_uid'] . ' ';
		}
		$addWhere .= ' and tx_commerce_products.deleted = 0 and tx_commerce_articles.deleted =0 ';
		$resProducts = $database->exec_SELECTquery(
			'distinct tx_commerce_products.title,tx_commerce_products.uid, tx_commerce_products.sys_language_uid, count(tx_commerce_articles.uid) as anzahl',
			'tx_commerce_products,tx_commerce_articles',
			'tx_commerce_products.uid=tx_commerce_articles.uid_product ' . $addWhere,
			'tx_commerce_products.title,tx_commerce_products.uid, tx_commerce_products.sys_language_uid',
			'tx_commerce_products.title,tx_commerce_products.sys_language_uid'
		);
		$data['items'] = array();
		$items = array();
		$items[] = array('', -1);
		while ($rowProducts = $database->sql_fetch_assoc($resProducts)) {
				// Select Languages
			$language = '';

			if ($rowProducts['sys_language_uid'] > 0) {
				$resLanguage = $database->exec_SELECTquery('title', 'sys_language', 'uid=' . $rowProducts['sys_language_uid']);
				if ($rowLanguage = $database->sql_fetch_assoc($resLanguage)) {
					$language = $rowLanguage['title'];
				}
			}

			if ($language) {
				$title = $rowProducts['title'] . ' [' . $language . '] ';
			} else {
				$title = $rowProducts['title'];
			}

			if ($rowProducts['anzahl'] > 0) {
				$resArticles = $database->exec_SELECTquery(
					'eancode,l18n_parent,ordernumber',
					'tx_commerce_articles',
					'tx_commerce_articles.uid_product=' . $rowProducts['uid'] . ' and tx_commerce_articles.deleted=0 '
				);

				if ($resArticles) {
					$NumRows = $database->sql_num_rows($resArticles);
					$count = 0;
					$eancodes = array();
					$ordernumbers = array();

					while (($rowArticles = $database->sql_fetch_assoc($resArticles)) && ($count < $numArticleNumbersShow)) {
						if ($rowArticles['l18n_parent'] > 0) {
							$resL18nParent = $database->exec_SELECTquery(
								'eancode,ordernumber',
								'tx_commerce_articles',
								'tx_commerce_articles.uid=' . $rowArticles['l18n_parent']
							);

							if ($resL18nParent) {
								$rowL18nParents = $database->sql_fetch_assoc($resL18nParent);
								if ($rowL18nParents['eancode'] <> '') {
									$eancodes[] = $rowL18nParents['eancode'];
								}
								if ($rowL18nParents['ordernumber'] <> '') {
									$ordernumbers[] = $rowL18nParents['ordernumber'];
								}

							} else {
								if ($rowArticles['eancode'] <> '') {
									$eancodes[] = $rowArticles['eancode'];
								}
								if ($rowArticles['ordernumber'] <> '') {
									$ordernumbers[] = $rowArticles['ordernumber'];
								}
							}

						} else {
							if ($rowArticles['eancode'] <> '') {
								$eancodes[] = $rowArticles['eancode'];
							}
							if ($rowArticles['ordernumber'] <> '') {
								$ordernumbers[] = $rowArticles['ordernumber'];
							}
						}
						$count++;
					}

					if (count($ordernumbers) >= count($eancodes) ) {
						$numbers = implode(',', $ordernumbers);
					} else {
						$numbers = implode(',', $eancodes);
					}

					if ($NumRows > $count) {
						$numbers .= ',...';
					}
					$title .= ' (' . $numbers . ')';
				}
			}

			$items[] = array($title, $rowProducts['uid']);
		}
		$database->sql_free_result($resProducts);

		$data['items'] = $items;
	}
}

class_alias('Tx_Commerce_Utility_TceformsUtility', 'tx_commerce_forms_select');

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Utility/TceformsUtility.php']) {
	/** @noinspection PhpIncludeInspection */
	require_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/Classes/Utility/TceformsUtility.php']);
}

?>
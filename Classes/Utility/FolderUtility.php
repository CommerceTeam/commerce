<?php
namespace CommerceTeam\Commerce\Utility;
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

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;

/**
 * This class creates the systemfolders for TX_commerce
 * Handling of sysfolders inside tx_commerce. Basically creates
 * needed sysfolders and system_articles.
 *
 * The method of this class should be called by
 * \CommerceTeam\Commerce\Utility\FolderUtility::methodname
 *
 * Creation of tx_commerce_basic folders
 * call: tx_commerce_create_folder::initFolders()
 *
 * Class \CommerceTeam\Commerce\Utility\FolderUtility
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class FolderUtility {
	/**
	 * Initializes the folders for tx_commerce
	 *
	 * @return void
	 */
	public static function initFolders() {
		/**
		 * Folder Creation
		 *
		 * @todo Get list from Order folders from TS
		 */
		list($modPid) = FolderRepository::initFolders('Commerce', 'commerce');
		list($prodPid) = FolderRepository::initFolders('Products', 'commerce', $modPid);
		FolderRepository::initFolders('Attributes', 'commerce', $modPid);

		list($orderPid) = FolderRepository::initFolders('Orders', 'commerce', $modPid);
		FolderRepository::initFolders('Incoming', 'commerce', $orderPid);
		FolderRepository::initFolders('Working', 'commerce', $orderPid);
		FolderRepository::initFolders('Waiting', 'commerce', $orderPid);
		FolderRepository::initFolders('Delivered', 'commerce', $orderPid);

		// Create System Product for payment and other things.
		$now = time();
		$addArray = array('tstamp' => $now, 'crdate' => $now, 'pid' => $prodPid);

		$database = self::getDatabaseConnection();

		// handle payment types
		// create the category if it not exists
		$res = $database->exec_SELECTquery(
			'uid',
			'tx_commerce_categories',
			'uname = \'SYSTEM\' AND parent_category = \'\' AND deleted=0'
		);
		$catUid = $database->sql_fetch_assoc($res);
		$catUid = $catUid['uid'];

		if (!$res || (int) $catUid == 0) {
			$catArray = $addArray;
			$catArray['title'] = 'SYSTEM';
			$catArray['uname'] = 'SYSTEM';
			$database->exec_INSERTquery('tx_commerce_categories', $catArray);
			$catUid = $database->sql_insert_id();
		}

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS'] as $type => $_) {
				self::makeSystemCatsProductsArtcilesAndPrices($catUid, strtoupper($type), $addArray);
			}
		}
	}

	/**
	 * Generates the System Articles
	 *
	 * @param int $catUid Category uid
	 * @param string $type Type
	 * @param array $addArray Additional Values
	 *
	 * @return void
	 */
	public static function makeSystemCatsProductsArtcilesAndPrices($catUid, $type, array $addArray) {
		$pUid = self::makeProduct($catUid, $type, $addArray);
			// create some articles, depending on the PAYMENT types
		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS'][$type]['types'] as $key => $value) {
			self::makeArticle($pUid, $key, $value, $addArray);
		}
	}

	/**
	 * Creates a product with a special uname inside of a specific category.
	 * If the product already exists, the method returns the UID of it.
	 *
	 * @param int $catUid Category uid
	 * @param string $uname Unique name
	 * @param array $addArray Additional values
	 *
	 * @return bool
	 */
	public static function makeProduct($catUid, $uname, array $addArray) {
		// first of all, check if there is a product for this value
		// if the product already exists, exit
		$pCheck = self::checkProd($catUid, $uname);
		if (isset($pCheck) && !($pCheck === FALSE)) {
			// the return value of the method above is the uid of the product
			// in the category
			return $pCheck;
		}

		// noproduct was found, so we create one
		// make the addArray
		$paArray = $addArray;
		$paArray['uname'] = $uname;
		$paArray['title'] = $uname;
		$paArray['categories'] = $catUid;

		$database = self::getDatabaseConnection();

		$database->exec_INSERTquery('tx_commerce_products', $paArray);
		$pUid = $database->sql_insert_id();

		// create relation between product and category
		$database->exec_INSERTquery('tx_commerce_products_categories_mm', array('uid_local' => $pUid, 'uid_foreign' => $catUid));

		return $pUid;
	}

	/**
	 * Checks if a product is inside a category. The product is identified
	 * by the uname field.
	 *
	 * @param int $cUid The uid of the category we search in
	 * @param string $uname The unique name by which the product should be identified
	 *
	 * @return bool|int false or UID of the found product
	 */
	public static function checkProd($cUid, $uname) {
		$database = self::getDatabaseConnection();

		// select all product from that category
		$res = $database->exec_SELECTquery(
			'uid_local',
			'tx_commerce_products_categories_mm',
			'uid_foreign=' . (int) $cUid
		);
		$pList = array();
		while (($pUid = $database->sql_fetch_assoc($res))) {
			$pList[] = (int)$pUid['uid_local'];
		}
		// if no products where found for this category, we can return false
		if (count($pList) <= 0) {
			return FALSE;
		}

		// else search the uid of the product with the classname within the product list
		$res = $database->exec_SELECTquery(
			'uid',
			'tx_commerce_products',
			'uname=\'' . $uname . '\' AND uid IN (' . implode(',', $pList) . ') AND deleted=0 AND hidden=0',
			'', '', 1
		);
		$pUid = $database->sql_fetch_assoc($res);
		$pUid = $pUid['uid'];

		return $pUid;
	}

	/**
	 * Creates an article for the product. Used for sysarticles
	 * (e.g. payment articles)
	 *
	 * @param int $pUid Product Uid under wich the articles are created
	 * @param int $key Keyname for the sysarticle, used for classname and title
	 * @param array $value Values for the article, only type is used
	 * @param array $addArray Additional params for the inserts (like timestamp)
	 *
	 * @return int
	 */
	public static function makeArticle($pUid, $key, array $value, array $addArray) {
		$database = self::getDatabaseConnection();

		// try to select an article that has a relation for this product
		// and the correct classname
		/**
		 * Backend library
		 *
		 * @var BackendUtility $belib
		 */
		$belib = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Utility\\BackendUtility');
		$articles = $belib->getArticlesOfProduct($pUid, 'classname=\'' . $key . '\'');

		if (is_array($articles) AND count($articles) > 0) {
			return $articles[0]['uid'];
		}

		$aArray = $addArray;
		$aArray['classname'] = $key;
		$aArray['title'] = !$value['title'] ? $key : $value['title'];
		$aArray['uid_product'] = (int)$pUid;
		$aArray['article_type_uid'] = (int)$value['type'];
		$database->exec_INSERTquery('tx_commerce_articles', $aArray);
		$aUid = $database->sql_insert_id();

		$pArray = $addArray;
		$pArray['uid_article'] = $aUid;
		// create a price
		$database->exec_INSERTquery('tx_commerce_article_prices', $pArray);

		return $aUid;
	}


	/**
	 * Get database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected static function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}

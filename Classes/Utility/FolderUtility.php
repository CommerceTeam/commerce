<?php
/**
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
 * This class creates the systemfolders for TX_commerce
 * Handling of sysfolders inside tx_commerce. Basically creates
 * needed sysfolders and system_articles.
 *
 * The method of this class should be called by
 * tx_commerce_create_folder::methodname
 *
 * Creation of tx_commerce_basic folders
 * call: tx_commerce_create_folder::initFolders()
 *
 * Class Tx_Commerce_Utility_FolderUtility
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class Tx_Commerce_Utility_FolderUtility {
	/**
	 * Initializes the folders for tx_commerce
	 *
	 * @return void
	 */
	public static function initFolders() {
		/**
		 * Folder Creation
		 * @TODO Get list from Order folders from TS
		 */
		list($modPid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Commerce', 'commerce');
		list($prodPid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Products', 'commerce', $modPid);
		Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Attributes', 'commerce', $modPid);

		list($orderPid) = Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Orders', 'commerce', $modPid);
		Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Incoming', 'commerce', $orderPid);
		Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Working', 'commerce', $orderPid);
		Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Waiting', 'commerce', $orderPid);
		Tx_Commerce_Domain_Repository_FolderRepository::initFolders('Delivered', 'commerce', $orderPid);

		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatefolder'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/class.tx_commerce_create_folder.php\'][\'postcreatefolder\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0 as no method was used
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatefolder'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

			// Create System Product for payment and other things.
		$now = time();
		$addArray = array('tstamp' => $now, 'crdate' => $now, 'pid' => $prodPid);

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// handle payment types
			// create the category if it not exists
		$res = $database->exec_SELECTquery(
			'uid',
			'tx_commerce_categories',
			'uname = \'SYSTEM\' AND parent_category = \'\' AND deleted=0'
		);
		$catUid = $database->sql_fetch_assoc($res);
		$catUid = $catUid['uid'];
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['precreatesyscategory'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/class.tx_commerce_create_folder.php\'][\'precreatesyscategory\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0 as no method was used
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['precreatesyscategory'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		if (!$res || (int) $catUid == 0) {
			$catArray = $addArray;
			$catArray['title'] = 'SYSTEM';
			$catArray['uname'] = 'SYSTEM';
			$database->exec_INSERTquery('tx_commerce_categories', $catArray);
			$catUid = $database->sql_insert_id();
		}
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatesyscategory'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/class.tx_commerce_create_folder.php\'][\'postcreatesyscategory\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0 as no method was used
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatesyscategory'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][COMMERCE_EXTKEY]['SYSPRODUCTS'] as $type => $data) {
				self::makeSystemCatsProductsArtcilesAndPrices($catUid, strtoupper($type), $addArray);
			}
		}
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatediliveryarticles'])) {
			t3lib_div::deprecationLog('
				hook
				$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'commerce/class.tx_commerce_create_folder.php\'][\'postcreatediliveryarticles\']
				is deprecated since commerce 1.0.0, it will be removed in commerce 1.4.0 as no method was used
			');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce/class.tx_commerce_create_folder.php']['postcreatediliveryarticles'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
	}

	/**
	 * Generates the System Articles
	 *
	 * @param integer $catUid
	 * @param string $type
	 * @param array $addArray
	 * @return void
	 */
	public static function makeSystemCatsProductsArtcilesAndPrices($catUid, $type, $addArray) {
		$pUid = self::makeProduct($catUid, $type, $addArray);
			// create some articles, depending on the PAYMENT types
		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['commerce']['SYSPRODUCTS'][$type]['types'] as $key => $value) {
			self::makeArticle($pUid, $key, $value, $addArray);
		}
	}

	/**
	 * Creates a product with a special uname inside of a specific category.
	 * If the product already exists, the method returns the UID of it.
	 *
	 * @param integer $catUid
	 * @param string $uname
	 * @param array $addArray
	 * @return boolean
	 */
	public static function makeProduct($catUid, $uname, $addArray) {
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

		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

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
	 * @param integer $cUid The uid of the category we search in
	 * @param string $uname The unique name by which the product should be identified
	 * @return boolean|integer false or UID of the found product
	 */
	public static function checkProd($cUid, $uname) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// select all product from that category
		$res = $database->exec_SELECTquery(
			'uid_local',
			'tx_commerce_products_categories_mm',
			'uid_foreign=' . (int) $cUid
		);
		$pList = array();
		while ($pUid = $database->sql_fetch_assoc($res)) {
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
	 * Creates an article for the product. Used for sysarticles (e.g. payment articles)
	 *
	 * @param integer $pUid Product Uid under wich the articles are created
	 * @param integer $key keyname for the sysarticle, used for classname and title first, title can be changed
	 * @param array $value values for the article, only type is used
	 * @param array $addArray additional params for the inserts (like timestamp)
	 * @return integer
	 */
	public static function makeArticle($pUid, $key, $value, $addArray) {
		/** @var t3lib_db $database */
		$database = $GLOBALS['TYPO3_DB'];

			// try to select an article that has a relation for this product
			// and the correct classname
		/** @var Tx_Commerce_Utility_BackendUtility $belib */
		$belib = t3lib_div::makeInstance('Tx_Commerce_Utility_BackendUtility');
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
	 * Initializes the folders for tx_commerce
	 *
	 * @deprecated since commerce 1.0.0, this function will be removed in commerce 1.4.0, please use init_folders instead
	 * @return void
	 */
	public static function init_folders() {
		t3lib_div::logDeprecatedFunction();
		self::initFolders();
	}
}

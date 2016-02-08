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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class creates the systemfolders for TX_commerce
 * Handling of sysfolders inside tx_commerce. Basically creates
 * needed sysfolders and system_articles.
 *
 * The method of this class should be called by
 * \CommerceTeam\Commerce\Utility\FolderUtility::methodname
 *
 * Creation of tx_commerce_basic folders
 *
 * Class \CommerceTeam\Commerce\Utility\FolderUtility
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class FolderUtility
{
    /**
     * Initializes the folders for tx_commerce.
     *
     * @return void
     */
    public static function initFolders()
    {
        /*
         * Folder Creation
         *
         * @todo Get list from Order folders from TS
         */
        $modPid = FolderRepository::initFolders('Commerce');
        $prodPid = FolderRepository::initFolders('Products', $modPid);
        FolderRepository::initFolders('Attributes', $modPid);

        $orderPid = FolderRepository::initFolders('Orders', $modPid);
        FolderRepository::initFolders('Incoming', $orderPid);
        FolderRepository::initFolders('Working', $orderPid);
        FolderRepository::initFolders('Waiting', $orderPid);
        FolderRepository::initFolders('Delivered', $orderPid);

        // Create System Product for payment and other things.
        $now = time();
        $addArray = array('tstamp' => $now, 'crdate' => $now, 'pid' => $prodPid);

        $database = self::getDatabaseConnection();

        // handle payment types
        // create the category if it not exists
        $res = $database->exec_SELECTquery(
            'uid',
            'tx_commerce_categories',
            'uname = "SYSTEM" AND parent_category = "" AND deleted = 0'
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

        $sysProducts = ConfigurationUtility::getInstance()->getConfiguration('SYSPRODUCTS');
        if (is_array($sysProducts)) {
            foreach ($sysProducts as $type => $_) {
                self::makeSystemCatsProductsArtcilesAndPrices($catUid, strtoupper($type), $addArray);
            }
        }

        /**
         * Update utility.
         *
         * @var \CommerceTeam\Commerce\Utility\UpdateUtility $updateUtility
         */
        $updateUtility = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\UpdateUtility::class);
        $updateUtility->main();
    }

    /**
     * Generates the System Articles.
     *
     * @param int $catUid Category uid
     * @param string $type Type
     * @param array $addArray Additional Values
     *
     * @return void
     */
    public static function makeSystemCatsProductsArtcilesAndPrices($catUid, $type, array $addArray)
    {
        $productUid = self::makeProduct($catUid, $type, $addArray);
        // create some articles, depending on the PAYMENT types
        $sysProductTypes = (array) ConfigurationUtility::getInstance()->getConfiguration('SYSPRODUCTS.' . $type . '.types');
        foreach ($sysProductTypes as $key => $value) {
            self::makeArticle($productUid, $key, $value, $addArray);
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
    public static function makeProduct($catUid, $uname, array $addArray)
    {
        // first of all, check if there is a product for this value
        // if the product already exists, exit
        $pCheck = self::checkProd($catUid, $uname);
        if ($pCheck) {
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
        $database->exec_INSERTquery(
            'tx_commerce_products_categories_mm',
            array('uid_local' => $pUid, 'uid_foreign' => $catUid)
        );

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
    public static function checkProd($cUid, $uname)
    {
        $database = self::getDatabaseConnection();

        // select all product from that category
        $res = $database->exec_SELECTquery(
            'uid_local',
            'tx_commerce_products_categories_mm',
            'uid_foreign = ' . (int) $cUid
        );
        $pList = array();
        while (($pUid = $database->sql_fetch_assoc($res))) {
            $pList[] = (int) $pUid['uid_local'];
        }
        // if no products where found for this category, we can return false
        if (empty($pList)) {
            return false;
        }

        // else search the uid of the product with the classname within the product list
        $pUid = (array) $database->exec_SELECTgetSingleRow(
            'uid',
            'tx_commerce_products',
            'uname = \'' . $uname . '\' AND uid IN (' . implode(',', $pList) . ') AND deleted = 0 AND hidden = 0'
        );

        return isset($pUid['uid']) ? $pUid['uid'] : 0;
    }

    /**
     * Creates an article for the product. Used for sysarticles
     * (e.g. payment articles).
     *
     * @param int $pUid Product Uid under wich the articles are created
     * @param int $key Keyname for the sysarticle, used for classname and title
     * @param array $value Values for the article, only type is used
     * @param array $addArray Additional params for the inserts (like timestamp)
     *
     * @return int
     */
    public static function makeArticle($pUid, $key, array $value, array $addArray)
    {
        $database = self::getDatabaseConnection();

        // try to select an article that has a relation for this product
        // and the correct classname
        /**
         * Backend library.
         *
         * @var BackendUtility $belib
         */
        $belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
        $articles = $belib->getArticlesOfProduct($pUid, 'classname=\'' . $key . '\'');

        if (is_array($articles) and !empty($articles)) {
            return $articles[0]['uid'];
        }

        $aArray = $addArray;
        $aArray['classname'] = $key;
        $aArray['title'] = !$value['title'] ? $key : $value['title'];
        $aArray['uid_product'] = (int) $pUid;
        $aArray['article_type_uid'] = (int) $value['type'];
        $database->exec_INSERTquery('tx_commerce_articles', $aArray);
        $aUid = $database->sql_insert_id();

        $pArray = $addArray;
        $pArray['uid_article'] = $aUid;
        // create a price
        $database->exec_INSERTquery('tx_commerce_article_prices', $pArray);

        return $aUid;
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

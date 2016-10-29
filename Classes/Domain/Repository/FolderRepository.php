<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Misc commerce db functions.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\FolderRepository
 */
class FolderRepository
{
    /**
     * Cache of page ids
     *
     * @var array
     */
    protected static $folderIds = [];

    /**
     * Find the extension folders.
     *
     * @param string $title Folder title as named in pages table
     * @param int $pid Parent Page id
     * @param string $module Extension module
     * @param bool $parentTitle Deprecated parameter do not use it to create folders on the fly
     * @param bool $executeUpdateUtility Deprecated parameter
     *
     * @return int
     */
    public static function initFolders(
        $title = 'Commerce',
        $pid = 0,
        $module = 'commerce',
        $parentTitle = false,
        $executeUpdateUtility = false
    ) {
        if ($parentTitle) {
            GeneralUtility::deprecationLog(
                'Creating parent folder is not supported anymore. Please change your code to use createFolder.
                    Parameter will get removed in version 6.'
            );
        }

        if ($executeUpdateUtility) {
            GeneralUtility::deprecationLog(
                'Executing update utility is not supported anymore. Please change your code to call it on your own.
                    Parameter will get removed in version 6.'
            );
        }

        if (is_string($pid) && is_int($module)) {
            GeneralUtility::deprecationLog(
                'Parameter $pid and $module swapped position. Fallback handling will get removed in version 6.'
            );
            $temp = $pid;
            $pid = $module;
            $module = $temp;
            unset($temp);
        }

        $cacheHash = $title . '|' . $module . '|' . $pid;
        if (!isset(static::$folderIds[$cacheHash])) {
            $folder = self::getFolder($title, $pid, $module);
            if (empty($folder)) {
                if ($title == 'Commerce') {
                    // If the first folder that gets fetched is empty try to create all default folders
                    self::createBasicFolders();
                }
                static::$folderIds[$cacheHash] = self::getFolder($title, $pid, $module);
            } else {
                static::$folderIds[$cacheHash] = (int)$folder['uid'];
            }
        }

        return static::$folderIds[$cacheHash];
    }

    /**
     * Find the extension folders.
     *
     * @param string $module Module
     * @param int $pid Page id
     * @param string $title Title
     *
     * @return array rows of found extension folders
     * @deprecated since Version 5 will be removed in 6. Please use only getFolder instead
     */
    public static function getFolders($module = 'commerce', $pid = 0, $title = '')
    {
        GeneralUtility::logDeprecatedFunction();
        $row = self::getFolder($title, $pid, $module);
        return isset($row['uid']) ? [$row['uid'] => $row] : [];
    }

    /**
     * Find folder by module and title takes pid into account.
     *
     * @param string $title Title
     * @param int $pid Page id
     * @param string $module Module
     *
     * @return array rows of found extension folders
     */
    public static function getFolder($title, $pid = 0, $module = 'commerce')
    {
        $row = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid, pid, title',
            'pages',
            'doktype = 254 AND tx_commerce_foldername = \'' . strtolower($title) . '\' AND pid = ' . (int) $pid .
            ' AND module = \'' . $module . '\' ' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages')
        );

        return $row;
    }

    /**
     * Create your database table folder
     * overwrite this if wanted.
     *
     * @param string $title Title
     * @param int $pid Page id
     * @param string $module Module
     *
     * @return int
     */
    public static function createFolder($title, $pid = 0, $module = 'commerce')
    {
        $sorting = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
            'sorting',
            'pages',
            'pid = ' . $pid,
            '',
            'sorting DESC'
        );

        self::getDatabaseConnection()->exec_INSERTquery(
            'pages',
            [
                'sorting' => $sorting['sorting'] ? $sorting['sorting'] + 1 : 10111,
                'perms_user' => 31,
                'perms_group' => 31,
                'perms_everybody' => 31,
                'doktype' => 254,
                'pid' => $pid,
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'title' => $title,
                'tx_commerce_foldername' => strtolower($title),
                'module' => $module,
            ]
        );

        return self::getDatabaseConnection()->sql_insert_id();
    }


    /**
     * Initializes the basic folders for ext:commerce.
     *
     * @return void
     */
    protected static function createBasicFolders()
    {
        /*
         * Folder Creation
         */
        $modulePid = FolderRepository::initFolders();
        $productPid = FolderRepository::initFolders('Products', $modulePid);
        FolderRepository::initFolders('Attributes', $modulePid);

        $orderPid = FolderRepository::initFolders('Orders', $modulePid);
        FolderRepository::initFolders('Incoming', $orderPid);
        FolderRepository::initFolders('Working', $orderPid);
        FolderRepository::initFolders('Waiting', $orderPid);
        FolderRepository::initFolders('Delivered', $orderPid);

        // Create System Product for payment and other things.
        $addArray = [
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'pid' => $productPid
        ];

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        // create the category if it not exists
        $catUid = $categoryRepository->getSystemCategoryUid();
        if ((int) $catUid == 0) {
            $categoryData = $addArray;
            $categoryData['title'] = 'SYSTEM';
            $categoryData['uname'] = 'SYSTEM';
            $catUid = $categoryRepository->addRecord($categoryData);
        }

        $sysProducts = ConfigurationUtility::getInstance()->getConfiguration('SYSPRODUCTS');
        if (is_array($sysProducts)) {
            foreach ($sysProducts as $type => $_) {
                self::makeSystemCategoriesProductsArticlesAndPrices($catUid, strtoupper($type), $addArray);
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
     * @param int $categoryUid Category uid
     * @param string $type Type
     * @param array $addArray Additional Values
     *
     * @return void
     */
    protected static function makeSystemCategoriesProductsArticlesAndPrices($categoryUid, $type, array $addArray)
    {
        $productUid = self::makeProduct($categoryUid, $type, $addArray);
        // create some articles, depending on the PAYMENT types
        $sysProductTypes = (array) ConfigurationUtility::getInstance()->getConfiguration(
            'SYSPRODUCTS.' . $type . '.types'
        );
        foreach ($sysProductTypes as $key => $value) {
            self::makeArticle($productUid, $key, $value, $addArray);
        }
    }

    /**
     * Creates a product with a special uname inside of a specific category.
     * If the product already exists, the method returns the UID of it.
     *
     * @param int $categoryUid Category uid
     * @param string $uname Unique name
     * @param array $addArray Additional values

     * @return bool
     */
    protected static function makeProduct($categoryUid, $uname, array $addArray)
    {
        // first of all, check if there is a product for this value
        // if the product already exists, exit
        $pCheck = self::checkProduct($categoryUid, $uname);
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
        $paArray['categories'] = $categoryUid;

        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $productUid = $productRepository->addRecord($paArray);
        $productRepository->addCategoryRelation($productUid, $categoryUid);

        return $productUid;
    }

    /**
     * Checks if a product is inside a category. The product is identified
     * by the uname field.
     *
     * @param int $categoryUid The uid of the category we search in
     * @param string $uname The unique name by which the product should be identified
     *
     * @return bool|int false or UID of the found product
     */
    protected static function checkProduct($categoryUid, $uname)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = GeneralUtility::makeInstance(ProductRepository::class);
        $product = $productRepository->findByCategoryAndUname($categoryUid, $uname);

        return isset($product['uid']) ? $product['uid'] : 0;
    }

    /**
     * Creates an article for the product. Used for sysarticles (e.g. payment articles).
     *
     * @param int $productUid Product Uid under wich the articles are created
     * @param int $classname Keyname for the sysarticle, used for classname and title
     * @param array $value Values for the article, only type is used
     * @param array $addArray Additional params for the inserts (like timestamp)
     * @return int
     */
    public static function makeArticle($productUid, $classname, array $value, array $addArray)
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);

        // try to select an article that has a relation for this product and the correct classname
        $article = $articleRepository->findByClassname($classname);
        if (!empty($article) && $article['uid_product'] == $productUid) {
            return $article['uid'];
        }

        /** @var ArticlePriceRepository $articlePriceRepository */
        $articlePriceRepository = GeneralUtility::makeInstance(ArticlePriceRepository::class);

        $articleData = $addArray;
        $articleData['classname'] = $classname;
        $articleData['title'] = $value['title'] ? $value['title'] : $classname;
        $articleData['uid_product'] = (int) $productUid;
        $articleData['article_type_uid'] = (int) $value['type'];
        $articleUid = $articleRepository->addRecord($articleData);

        $priceData = $addArray;
        $priceData['uid_article'] = $articleUid;
        $articlePriceRepository->addRecord($priceData);

        return $articleUid;
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

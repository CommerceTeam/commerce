<?php
namespace CommerceTeam\Commerce\Utility;

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

use CommerceTeam\Commerce\Domain\Repository\ArticlePriceRepository;
use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use CommerceTeam\Commerce\Domain\Repository\ProductRepository;
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
    public static function makeProduct($categoryUid, $uname, array $addArray)
    {
        // first of all, check if there is a product for this value
        // if the product already exists, exit
        $pCheck = self::checkProd($categoryUid, $uname);
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
    public static function checkProd($categoryUid, $uname)
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
}

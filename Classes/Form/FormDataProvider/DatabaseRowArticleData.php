<?php
namespace CommerceTeam\Commerce\Form\FormDataProvider;

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

use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use CommerceTeam\Commerce\Domain\Repository\SysRefindexRepository;
use CommerceTeam\Commerce\Utility\BackendUtility;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle article values on row.
 */
class DatabaseRowArticleData implements FormDataProviderInterface
{
    /**
     * Initialize new row with default values from various sources
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if (!$this->isValidRecord($result)) {
            return $result;
        }

        switch ($result['tableName']) {
            case 'tx_commerce_categories':
                $result = $this->addCategoryData($result);
                break;

            case 'tx_commerce_products':
                $result = $this->addProductData($result);
                break;

            case 'tx_commerce_articles':
                break;
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function addCategoryData(array $result)
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $attributes = $categoryRepository->findAttributesByCategoryUid($result['vanillaUid']);

        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);
        $correlationTypes = $attributeRepository->findAllCorrelationTypes();

        $root = [];
        foreach ($correlationTypes as $correlationType) {
            $root['ct_' . $correlationType['uid']] = ['vDEF' => []];
        }
        foreach ($attributes as $attribute) {
            $root['ct_' . $attribute['uid_correlationtype']]['vDEF'][] = $attribute['uid_foreign'];
        }

        if (!empty($root)) {
            $result['databaseRow']['attributes'] =
                is_array($result['databaseRow']['attributes']) ?
                $result['databaseRow']['attributes'] : [];
            $result['databaseRow']['attributes']['data'] =
                is_array($result['databaseRow']['attributes']['data']) ?
                $result['databaseRow']['attributes']['data'] : [];
            $result['databaseRow']['attributes']['data']['sDEF'] =
                is_array($result['databaseRow']['attributes']['data']['sDEF']) ?
                $result['databaseRow']['attributes']['data']['sDEF'] : [];
            $result['databaseRow']['attributes']['data']['sDEF']['lDEF'] = $root;
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function addProductData(array $result)
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articles = $articleRepository->findByProductUid($result['vanillaUid']);
        /** @var BackendUtility $belib */
        $belib = GeneralUtility::makeInstance(BackendUtility::class);
        $attributes = $belib->getAttributesForProduct($result['vanillaUid'], true, true, true);

        if (is_array($attributes['ct1'])) {
            foreach ($articles as &$article) {
                $article['_reference_count'] = $this->getArticleReferenceCount($article['uid']);

                foreach ($attributes['ct1'] as &$attribute) {
                    // get all article attribute relations
                    $attribute['values'] = $articleRepository->findAttributeRelationsByArticleAndAttribute(
                        $article['uid'],
                        $attribute['uid_foreign']
                    );
                }
            }
        }

        $root = [];
        foreach ($attributes['grouped'] as $correlationType => $attributes) {
            if (!isset($root[$correlationType])) {
                $root[$correlationType] = ['vDEF' => []];
            }
            foreach ($attributes as $attribute) {
                $root[$correlationType]['vDEF'][] = $attribute['uid_foreign'];
            }
        }
        if (!empty($root) && is_array($result['databaseRow']['attributes'])) {
            $result['databaseRow']['attributes']['data']['sDEF']['lDEF'] = $root;
        }
        $result['databaseRow']['articles'] = $articles;

        return $result;
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    protected function isValidRecord($result)
    {
        return in_array(
            $result['tableName'],
            ['tx_commerce_categories', 'tx_commerce_products', 'tx_commerce_articles']
        );
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param int $uid UID of the referenced record, must be > 0
     *
     * @return int the number of references to record $uid in table
     *      $tableName, will be >= 0
     */
    protected function getArticleReferenceCount($uid)
    {
        /** @var SysRefindexRepository $referenceRepository */
        $referenceRepository = GeneralUtility::makeInstance(SysRefindexRepository::class);
        return $referenceRepository->countByTablenameUid('tx_commerce_articles', $uid);
    }
}

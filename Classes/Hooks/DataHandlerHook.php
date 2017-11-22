<?php
namespace CommerceTeam\Commerce\Hooks;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 */
class DataHandlerHook
{
    /**
     * Backend utility.
     *
     * @var \CommerceTeam\Commerce\Utility\BackendUtility
     */
    protected $belib;

    /**
     * This is just a constructor to instanciate the backend library.
     */
    public function __construct()
    {
        $this->belib = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Utility\BackendUtility::class);
    }

    /**
     * This hook is processed BEFORE a datamap is processed (save, update etc.)
     * We use this to check if a product or category is inheriting any attributes
     * from other categories (parents or similiar). It also removes invalid
     * attributes from the fieldArray which is saved in the database after this
     * method.
     * So, if we change it here, the method "processDatamap_afterDatabaseOperations"
     * will work with the data we maybe have modified here.
     * Calculation of missing price.
     *
     * @param array $incomingFieldArray Fields that where changed in BE
     * @param string $table Table the data will be stored in
     * @param int $id The uid of the dataset we're working on
     */
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, $table, $id)
    {
        if ($this->preProcessIsNotAllowed($incomingFieldArray, $table, $id)) {
            return;
        }

        $categoryList = $this->getDataMapProcessor($table)->preProcess($incomingFieldArray, $id);
        $incomingFieldArray = $this->preProcessAttributes($incomingFieldArray, $categoryList);
    }

    /**
     * @param string $table
     * @return \CommerceTeam\Commerce\Hooks\DataHandling\AbstractDataMapProcessor
     */
    protected function getDataMapProcessor($table)
    {
        $classname = 'CommerceTeam\\Commerce\\Hooks\\DataHandling\\' .
            GeneralUtility::underscoredToUpperCamelCase(str_replace('tx_commerce_', '', $table)) .
            'DataMapProcessor';

        /** @var \CommerceTeam\Commerce\Hooks\DataHandling\AbstractDataMapProcessor $processor */
        $processor = GeneralUtility::makeInstance($classname);
        return $processor;
    }

    /**
     * Check if preprocessing is allowed.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param string $table Table
     * @param string|int $id Id
     *
     * @return bool
     */
    protected function preProcessIsNotAllowed(array $incomingFieldArray, $table, $id)
    {
        // pre process is not allowed if the dataset was just created
        $idIsNew = strpos(strtolower($id), 'new') === 0;

        // articles may get preprocessed if the attributesedit,
        // prices or create_new_price fields are set
        $articleEditPriceOrAttribute = $table == 'tx_commerce_articles' && (
            isset($incomingFieldArray['attributesedit'])
            || isset($incomingFieldArray['prices'])
            || isset($incomingFieldArray['create_new_price'])
        );

        // categories or products may get preprocessed if attributes are set
        $categoryEditAttribute = $table == 'tx_commerce_categories' && isset($incomingFieldArray['attributes']);
        $productEditAttribute = $table == 'tx_commerce_products' && isset($incomingFieldArray['attributes']);

        // prices, orders and order articles may get preprocessed
        $allowedTables = in_array(
            $table,
            [
                'tx_commerce_article_prices',
                'tx_commerce_orders',
                'tx_commerce_order_articles'
            ]
        );

        return $idIsNew || !(
                $articleEditPriceOrAttribute
                || $categoryEditAttribute
                || $productEditAttribute
                || $allowedTables
            );
    }

    /**
     * Check attributes of products and categories.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param array $catList Whether to handle attributes
     *
     * @return mixed
     */
    protected function preProcessAttributes(array $incomingFieldArray, $catList)
    {
        if (empty($catList)) {
            return $incomingFieldArray;
        }

        // get all parent categories, excluding this
        $this->belib->getParentCategoriesFromList($catList);

        $correlationTypes = [];
        // get all correlation types from flexform
        if (isset($incomingFieldArray['attributes'])
            && is_array($incomingFieldArray['attributes'])
            && isset($incomingFieldArray['attributes']['data'])
            && is_array($incomingFieldArray['attributes']['data'])
            && isset($incomingFieldArray['attributes']['data']['sDEF'])
            && is_array($incomingFieldArray['attributes']['data']['sDEF'])
            && isset($incomingFieldArray['attributes']['data']['sDEF']['lDEF'])
            && is_array($incomingFieldArray['attributes']['data']['sDEF']['lDEF'])
        ) {
            $correlationTypes = &$incomingFieldArray['attributes']['data']['sDEF']['lDEF'];
        }

        $usedAttributes = [];

        foreach ($correlationTypes as $key => $data) {
            $keyData = [];
            $validAttributes = [];
            $this->belib->getUidFromKey($key, $keyData);
            if ($keyData[0] == 'ct' && is_array($data['vDEF']) && !empty($data['vDEF'])) {
                // get the attributes from the category or product
                foreach ($data['vDEF'] as $localAttribute) {
                    if ($localAttribute == '') {
                        continue;
                    }
                    $attributeUid = $this->belib->getUidFromKey($localAttribute, $keyData);
                    if (!$this->belib->checkArray($attributeUid, $usedAttributes, 'uid_foreign')) {
                        $validAttributes[] = $localAttribute;
                        $usedAttributes[] = ['uid_foreign' => $attributeUid];
                    }
                }
                $correlationTypes[$key]['vDEF'] = $validAttributes;
            }
        }

        return $incomingFieldArray;
    }

    /**
     * Change FieldArray after operations have been executed and just before
     * it is passed to the db.
     *
     * @param string $status Status of the Datamap
     * @param string $table DB Table we are operating on
     * @param int $id UID of the Item we are operating on
     * @param array $fieldArray Fields to be inserted into the db
     * @param DataHandler $pObj Reference to the BE Form Object of the caller
     */
    public function processDatamap_postProcessFieldArray($status, $table, $id, array &$fieldArray, DataHandler $pObj)
    {
        if (in_array($table, ['tx_commerce_articles', 'tx_commerce_products', 'tx_commerce_categories'])) {
            $this->getDataMapProcessor($table)->postProcess($status, $table, $id, $fieldArray, $pObj);
        }
    }

    /**
     * When all operations in the database where made from TYPO3 side, we
     * have to make some special entries for the shop. Because we don't use
     * the built in routines to save relations between tables, we have to
     * do this on our own. We make it manually because we save some additonal
     * information in the relation tables like values, correlation types and
     * such stuff.
     * The hole save stuff is done by the "saveAllCorrelations" method.
     *
     * @param string $status Status
     * @param string $table Table
     * @param int $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $dataHandler Parent object
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $dataHandler
    ) {
        if (!in_array($table, ['tx_commerce_products', 'tx_commerce_categories', 'tx_commerce_prices'])) {
            return;
        }

        $this->getDataMapProcessor($table)->afterDatabase($table, $id, $fieldArray, $dataHandler, $status);

        if (TYPO3_MODE == 'BE' && $this->isUpdateSignalAllowed($table, $fieldArray)) {
            BackendUtility::setUpdateSignal('updateCategoryTree');
        }
    }

    /**
     * @param string $table
     * @param array $fieldArray
     *
     * @return bool
     */
    protected function isUpdateSignalAllowed($table, $fieldArray)
    {
        if (!in_array($table, ['tx_commerce_categories', 'tx_commerce_products', 'tx_commerce_articles'])) {
            return false;
        }

        $ctrl = $GLOBALS['TCA'][$table]['ctrl'];
        $enableFields = array_merge([$ctrl['delete']], $ctrl['enablecolumns']);

        $isEnableFieldSet = false;
        foreach ($enableFields as $enableField) {
            if ($enableField && isset($fieldArray[$enableField])) {
                $isEnableFieldSet = true;
                break;
            }
        }
        return $isEnableFieldSet;
    }

    /**
     * Hook needed to remove attribute relations
     *
     * @param DataHandler $dataHandler
     * @param array $currentValueArray
     * @param array $arrValue
     */
    public function checkFlexFormValue_beforeMerge($dataHandler, &$currentValueArray, $arrValue)
    {
        if ((
                isset($dataHandler->datamap['tx_commerce_categories'])
                || isset($dataHandler->datamap['tx_commerce_products'])
            )
            && isset($arrValue['data'])
            && isset($arrValue['data']['sDEF'])
            && isset($arrValue['data']['sDEF']['lDEF'])
        ) {
            foreach ($arrValue['data']['sDEF']['lDEF'] as $key => $value) {
                if (empty($value['vDEF'])) {
                    $currentValueArray['data']['sDEF']['lDEF'][$key] = $value;
                }
            }
        }
    }
}

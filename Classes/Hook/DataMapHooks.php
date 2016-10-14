<?php
namespace CommerceTeam\Commerce\Hook;

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
use CommerceTeam\Commerce\Factory\SettingsFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains some hooks for processing formdata.
 * Hook for saving order data and order_articles.
 *
 * Class \CommerceTeam\Commerce\Hook\DataMapHooks
 *
 * @author 2005-2013 Thomas Hempel <thomas@work.de>
 */
class DataMapHooks
{
    /**
     * Backend utility.
     *
     * @var \CommerceTeam\Commerce\Utility\BackendUtility
     */
    protected $belib;

    /**
     * Category list.
     *
     * @var array
     */
    protected $catList = array();

    /**
     * Unsubstituted id.
     *
     * @var string
     */
    protected $unsubstitutedId;

    /**
     * This is just a constructor to instanciate the backend library.
     *
     * @return self
     */
    public function __construct()
    {
        $this->belib = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Utility\\BackendUtility');
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
     * @param DataHandler $pObj The instance of the BE Form
     *
     * @return void
     */
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, $table, $id, DataHandler $pObj)
    {
        // check if we have to do something
        if (!$this->isPreProcessAllowed($incomingFieldArray, $table, $id)) {
            return;
        }

        $handleAttributes = false;

        switch ($table) {
            case 'tx_commerce_categories':
                $handleAttributes = true;
                $incomingFieldArray = $this->preProcessCategory($incomingFieldArray, $id);
                break;

            case 'tx_commerce_products':
                $handleAttributes = true;
                $incomingFieldArray = $this->preProcessProduct($incomingFieldArray, $id);
                break;

            case 'tx_commerce_articles':
                $incomingFieldArray = $this->preProcessArticle($incomingFieldArray, $id);
                break;

            case 'tx_commerce_article_prices':
                $incomingFieldArray = $this->preProcessArticlePrice($incomingFieldArray, $id);
                break;

            case 'tx_commerce_orders':
                $incomingFieldArray = $this->preProcessOrder($incomingFieldArray, $table, $id, $pObj);
                break;

            case 'tx_commerce_order_articles':
                $this->preProcessOrderArticle($table, $id, $pObj);
                break;

            default:
        }

        $incomingFieldArray = $this->preProcessAttributes($incomingFieldArray, $handleAttributes);
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
    protected function isPreProcessAllowed(array $incomingFieldArray, $table, $id)
    {
        // preprocess is not allowed if the dataset was just created
        return !strtolower(substr($id, 0, 3)) == 'new'
            || (
                (
                    // articles may get preprocessed if the attributesedit,
                    // prices or create_new_price fields are set
                    $table == 'tx_commerce_articles'
                    && (
                        isset($incomingFieldArray['attributesedit'])
                        || isset($incomingFieldArray['prices'])
                        || isset($incomingFieldArray['create_new_price'])
                    )
                )
                || ($table == 'tx_commerce_article_prices')
                || (
                    // categories or products may get preprocessed if attributes are set
                    ($table == 'tx_commerce_products' || $table == 'tx_commerce_categories')
                    && isset($incomingFieldArray['attributes'])
                )
                // orders and order articles may get preprocessed
                || ($table == 'tx_commerce_orders' || $table == 'tx_commerce_order_articles')
            );
    }

    /**
     * Remove any parent_category that has the same uid as the category we are
     * going to save.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     *
     * @return array
     */
    protected function preProcessCategory(array $incomingFieldArray, $id)
    {
        $categories = array_diff(
            GeneralUtility::trimExplode(',', $incomingFieldArray['parent_category'], true),
            array($id)
        );

        $incomingFieldArray['parent_category'] = !empty($categories) ? implode(',', $categories) : null;

        $this->catList = $this->belib->getUidListFromList($categories);

        return $incomingFieldArray;
    }

    /**
     * Preprocess product.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     *
     * @return array
     */
    protected function preProcessProduct(array $incomingFieldArray, $id)
    {
        $this->catList = $this->belib->getUidListFromList(
            GeneralUtility::trimExplode(',', $incomingFieldArray['categories'])
        );

        $articles = $this->belib->getArticlesOfProduct($id);
        if (is_array($articles)) {
            foreach ($articles as $article) {
                $this->belib->updateArticleHash($article['uid']);
            }
        }

        // direct preview
        if (GeneralUtility::_POST('_savedokview_x')) {
            // if "savedokview" has been pressed and  the beUser works in the LIVE workspace
            // open current record in single view get page TSconfig
            $pagesTypoScriptConfig = BackendUtility::getPagesTSconfig(GeneralUtility::_POST('popViewId'));
            if ($pagesTypoScriptConfig['tx_commerce.']['singlePid']) {
                $previewPageId = $pagesTypoScriptConfig['tx_commerce.']['singlePid'];
            } else {
                $previewPageId = SettingsFactory::getInstance()->getExtConf('previewPageID');
            }

            if ($previewPageId > 0) {
                // Get Parent CAT UID
                /**
                 * Product.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Product $product
                 */
                $product = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Product', $id);
                $product->loadData();

                $parentCategory = $product->getMasterparentCategory();
                $GLOBALS['_POST']['popViewId_addParams'] =
                    (
                        $incomingFieldArray['sys_language_uid'] > 0 ?
                        '&L=' . $incomingFieldArray['sys_language_uid'] :
                        ''
                    ) .
                    '&ADMCMD_vPrev&no_cache=1&tx_commerce_pi1[showUid]=' . $id . '&tx_commerce_pi1[catUid]=' .
                    $parentCategory;
                $GLOBALS['_POST']['popViewId'] = $previewPageId;
            }
        }

        return $incomingFieldArray;
    }

    /**
     * Preprocess article.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     *
     * @return array
     */
    protected function preProcessArticle(array $incomingFieldArray, $id)
    {
        $this->updateArticleAttributeRelations($incomingFieldArray, $id);

        // create a new price if the checkbox was toggled get pid of article
        $pricesCount = is_numeric($incomingFieldArray['create_new_scale_prices_count']) ?
            (int) $incomingFieldArray['create_new_scale_prices_count'] : 0;
        $pricesSteps = is_numeric($incomingFieldArray['create_new_scale_prices_steps']) ?
            (int) $incomingFieldArray['create_new_scale_prices_steps'] : 0;
        $pricesStartamount = is_numeric($incomingFieldArray['create_new_scale_prices_startamount']) ?
            (int) $incomingFieldArray['create_new_scale_prices_startamount'] : 0;

        if ($pricesCount > 0 && $pricesSteps > 0 && $pricesStartamount > 0) {
            // somehow hook is used two times sometime. So switch off new creating.
            $incomingFieldArray['create_new_scale_prices_count'] = 0;

            // get pid
            list($commercePid) = FolderRepository::initFolders('Commerce', 'commerce');
            list($productPid) = FolderRepository::initFolders('Products', 'commerce', $commercePid);

            // set some status vars
            $myScaleAmountStart = $pricesStartamount;
            $myScaleAmountEnd = $pricesStartamount + $pricesSteps - 1;

            // create the different prices
            for ($myScaleCounter = 1; $myScaleCounter <= $pricesCount; ++$myScaleCounter) {
                $insertArr = array(
                    'pid' => $productPid,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'uid_article' => $id,
                    'fe_group' => $incomingFieldArray['create_new_scale_prices_fe_group'],
                    'price_scale_amount_start' => $myScaleAmountStart,
                    'price_scale_amount_end' => $myScaleAmountEnd,
                );

                $this->getDatabaseConnection()->exec_INSERTquery('tx_commerce_article_prices', $insertArr);

                // @todo update articles XML

                $myScaleAmountStart += $pricesSteps;
                $myScaleAmountEnd += $pricesSteps;
            }
        }

        return $incomingFieldArray;
    }

    /**
     * Preprocess article price.
     *
     * @param array $incomingFields Incoming field array
     * @param int $id Id
     *
     * @return array
     */
    protected function preProcessArticlePrice(array $incomingFields, $id)
    {
        if (isset($incomingFields['price_gross']) && $incomingFields['price_gross']) {
            $incomingFields['price_gross'] = $this->centurionMultiplication($incomingFields['price_gross']);
        }

        if (isset($incomingFields['price_net']) && $incomingFields['price_net']) {
            $incomingFields['price_net'] = $this->centurionMultiplication($incomingFields['price_net']);
        }

        if (isset($incomingFields['purchase_price']) && $incomingFields['purchase_price']) {
            $incomingFields['purchase_price'] = $this->centurionMultiplication($incomingFields['purchase_price']);
        }

        return $incomingFields;
    }

    /**
     * Centurion multiplication.
     *
     * @param string $price Price
     *
     * @return int
     */
    protected function centurionMultiplication($price)
    {
        return intval(strval(str_replace(',','.',$price) * 100));
    }

    /**
     * Process Data when saving Order
     * Change the PID from this order via the new field newpid
     * As TYPO3 don't allows changing the PId directly.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param string $table Table
     * @param int $id Id
     * @param DataHandler $pObj Parent object
     *
     * @return array
     */
    protected function preProcessOrder(array $incomingFieldArray, $table, $id, DataHandler &$pObj)
    {
        $incomingFieldArray['crdate'] = null;

        if (isset($incomingFieldArray['newpid'])) {
            $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Hook/DataMapHooks', 'preProcessOrder');

            $database = $this->getDatabaseConnection();

            // Add first the pid filled
            $incomingFieldArray['pid'] = $incomingFieldArray['newpid'];

            // Move Order articles
            $orders = $database->exec_SELECTquery(
                'order_id, pid, uid, order_sys_language_uid',
                $table,
                'uid = ' . (int) $id
            );
            if (!$database->sql_error()) {
                $order = $database->sql_fetch_assoc($orders);

                if ($order['pid'] != $incomingFieldArray['newpid']) {
                    // order_sys_language_uid is not always set in fieldArray so we overwrite
                    // it with our order data
                    if ($incomingFieldArray['order_sys_language_uid'] === null) {
                        $incomingFieldArray['order_sys_language_uid'] = $order['order_sys_language_uid'];
                    }

                    foreach ($hooks as $hookObj) {
                        if (method_exists($hookObj, 'moveOrders_preMoveOrder')) {
                            // @deprecated This method call gets removed in 5.0.0
                            $hookObj->moveOrders_preMoveOrder($order, $incomingFieldArray);
                        } elseif (method_exists($hookObj, 'moveOrdersPreMoveOrder')) {
                            $hookObj->moveOrdersPreMoveOrder($order, $incomingFieldArray);
                        }
                    }

                    $orderId = $order['order_id'];
                    $resultOrderArticles = $database->exec_SELECTquery(
                        '*',
                        'tx_commerce_order_articles',
                        'order_id = ' . $database->fullQuoteStr($orderId, 'tx_commerce_order_articles')
                    );
                    if (!$database->sql_error()) {
                        // Run trough all articles from this order and move it to other storage folder
                        while (($orderArtikelRow = $database->sql_fetch_assoc($resultOrderArticles))) {
                            $orderArtikelRow['pid'] = $incomingFieldArray['newpid'];
                            $orderArtikelRow['tstamp'] = $GLOBALS['EXEC_TIME'];

                            $database->exec_UPDATEquery(
                                'tx_commerce_order_articles',
                                'uid = ' . $orderArtikelRow['uid'],
                                $orderArtikelRow
                            );
                        }
                    } else {
                        $pObj->log($table, $id, 2, 0, 2, 'SQL error: \'%s\' (%s)', 12, array(
                            $database->sql_error(),
                            $table . ':' . $id
                        ));
                    }
                    $order['pid'] = $incomingFieldArray['newpid'];
                    $order['tstamp'] = $GLOBALS['EXEC_TIME'];

                    $database->exec_UPDATEquery('tx_commerce_orders', 'uid = ' . $order['uid'], $order);

                    foreach ($hooks as $hookObj) {
                        if (method_exists($hookObj, 'moveOrders_postMoveOrder')) {
                            // @deprecated This method call gets removed in 5.0.0
                            $hookObj->moveOrders_postMoveOrder($order, $incomingFieldArray);
                        } elseif (method_exists($hookObj, 'moveOrdersPostMoveOrder')) {
                            $hookObj->moveOrdersPostMoveOrder($order, $incomingFieldArray);
                        }
                    }
                }
            } else {
                $pObj->log($table, $id, 2, 0, 2, 'SQL error: \'%s\' (%s)', 12, array(
                    $database->sql_error(),
                    $table . ':' . $id
                ));
            }
        }

        return $incomingFieldArray;
    }

    /**
     * Process Data when saving ordered articles
     * Recalculate Order sum.
     *
     * @param string $table Table
     * @param int $id Id
     * @param DataHandler $pObj Parent object
     *
     * @return void
     */
    protected function preProcessOrderArticle($table, $id, DataHandler $pObj)
    {
        $database = $this->getDatabaseConnection();

        $orderIdResult = $database->exec_SELECTquery('order_id', $table, 'uid = ' . (int) $id);
        if (!$database->sql_error()) {
            list($orderId) = $database->sql_fetch_row($orderIdResult);
            $sum = array('sum_price_gross' => 0, 'sum_price_net' => 0);

            $orderArticles = $database->exec_SELECTquery(
                '*',
                $table,
                'order_id = ' . $database->fullQuoteStr($orderId, $table)
            );
            if (!$database->sql_error()) {
                while (($orderArticle = $database->sql_fetch_assoc($orderArticles))) {
                    /*
                     * Calculate Sums
                     */
                    $sum['sum_price_gross'] += $orderArticle['amount'] * $orderArticle['price_net'];
                    $sum['sum_price_net'] += $orderArticle['amount'] * $orderArticle['price_gross'];
                }
            } else {
                $pObj->log($table, $id, 2, 0, 2, 'SQL error: \'%s\' (%s)', 12, array(
                    $database->sql_error(),
                    $table . ':' . $id
                ));
            }

            $database->exec_UPDATEquery(
                'tx_commerce_orders',
                'order_id = ' . $database->fullQuoteStr($orderId, 'tx_commerce_orders'),
                $sum
            );
            if ($database->sql_error()) {
                $pObj->log($table, $id, 2, 0, 2, 'SQL error: \'%s\' (%s)', 12, array(
                    $database->sql_error(),
                    $table . ':' . $id
                ));
            }
        } else {
            $pObj->log($table, $id, 2, 0, 2, 'SQL error: \'%s\' (%s)', 12, array(
                $database->sql_error(),
                $table . ':' . $id
            ));
        }
    }

    /**
     * Check attributes of products and categories.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param bool|int $handleAttributes Whether to handle attributes
     *
     * @return mixed
     */
    protected function preProcessAttributes(array $incomingFieldArray, $handleAttributes)
    {
        if ($handleAttributes) {
            // get all parent categories, excluding this
            $this->belib->getParentCategoriesFromList($this->catList);

            $correlationTypes = array();
            // get all correlation types from flexform thats was created by dynaflex!
            if (is_array($incomingFieldArray)
                && isset($incomingFieldArray['attributes'])
                && is_array($incomingFieldArray['attributes'])
                && isset($incomingFieldArray['attributes']['data'])
                && is_array($incomingFieldArray['attributes']['data'])
                && isset($incomingFieldArray['attributes']['data']['sDEF'])
                && is_array($incomingFieldArray['attributes']['data']['sDEF'])
                && isset($incomingFieldArray['attributes']['data']['sDEF']['lDEF'])
                && is_array($incomingFieldArray['attributes']['data']['sDEF']['lDEF'])
            ) {
                $correlationTypes = $incomingFieldArray['attributes']['data']['sDEF']['lDEF'];
            }

            $usedAttributes = array();

            foreach ($correlationTypes as $key => $data) {
                $keyData = array();
                // @todo this cant work, we are checking on a new created empty array
                if ($keyData[0] == 'ct') {
                    // get the attributes from the categories of this product
                    $localAttributes = explode(',', $data['vDEF']);
                    if (is_array($localAttributes)) {
                        $validAttributes = array();
                        foreach ($localAttributes as $localAttribute) {
                            if ($localAttribute == '') {
                                continue;
                            }
                            $attributeUid = $this->belib->getUidFromKey($localAttribute, $keyData);
                            if (!$this->belib->checkArray($attributeUid, $usedAttributes, 'uid_foreign')) {
                                $validAttributes[] = $localAttribute;
                                $usedAttributes[] = array('uid_foreign' => $attributeUid);
                            }
                        }
                        $incomingFieldArray['attributes']['data']['sDEF']['lDEF'][$key]['vDEF'] = implode(
                            ',',
                            $validAttributes
                        );
                    }
                }
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
     *
     * @return void
     */
    public function processDatamap_postProcessFieldArray($status, $table, $id, array &$fieldArray, DataHandler $pObj)
    {
        switch ($table) {
            case 'tx_commerce_categories':
                $this->postProcessCategory($status, $table, $id, $fieldArray, $pObj);
                break;

            case 'tx_commerce_products':
                $this->postProcessProduct($status, $table, $id, $fieldArray, $pObj);
                break;

            case 'tx_commerce_articles':
                $this->postProcessArticle($status, $id, $fieldArray, $pObj);
                break;

            default:
        }
    }

    /**
     * Will overwrite the data because it has been removed - this is because typo3
     * only allows pages to have permissions so far
     * Will also make some checks to see if all permissions are available that are
     * needed to proceed with the datamap.
     *
     * @param string $status Status
     * @param string $table Table
     * @param int|string $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     *
     * @return void
     */
    protected function postProcessCategory($status, $table, $id, array &$fieldArray, DataHandler $pObj)
    {
        $backendUser = $this->getBackendUser();

        // Will be called for every Category that is in the datamap - so at this time
        // we only need to worry about the current $id item
        $data = $pObj->datamap[$table][$id];

        if (is_array($data)) {
            $l18nParent = (int) $data['l18n_parent'];

            $category = null;
            // check if the user has the permission to edit this category
            if ($status != 'new') {
                // check if we have the right to edit and are in commerce mounts
                $checkId = $id;

                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Category', $checkId);
                $category->loadData();

                // Use the l18n parent as category for permission checks.
                if ($l18nParent || $category->getField('l18n_parent') > 0) {
                    $checkId = $l18nParent ?: $category->getField('l18n_parent');
                    $category = GeneralUtility::makeInstance(
                        'CommerceTeam\\Commerce\\Domain\\Model\\Category',
                        $checkId
                    );
                }

                // check if the category is in mount
                /**
                 * Category mounts.
                 *
                 * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
                 */
                $mount = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\CategoryMounts');
                $mount->init((int) $backendUser->user['uid']);

                // check
                if (!$category->isPermissionSet('edit') || !$mount->isInCommerceMounts($category->getUid())) {
                    $pObj->newlog('You dont have the permissions to edit this category.', 1);
                    $fieldArray = array();

                    return;
                }
            }

            // add the perms back into the field_array
            foreach ($data as $field => $value) {
                switch ($field) {
                    case 'perms_userid':
                        // fall through
                    case 'perms_groupid':
                        // fall through
                    case 'perms_user':
                        // fall through
                    case 'perms_group':
                        // fall through
                    case 'perms_everybody':
                        // Overwrite only the perms fields
                        $fieldArray[$field] = $value;
                        break;

                    default:
                }
            }

            // add permissions for current user
            if ($status == 'new') {
                $fieldArray['perms_userid'] = $backendUser->user['uid'];
                // 31 grants every right
                $fieldArray['perms_user'] = 31;
            }

            // break if the parent_categories didn't change
            if (!isset($fieldArray['parent_category'])) {
                return;
            }

            // check if we are allowed to create new categories under the newly assigned
            // categories
            // check if we are allowed to remove this category from the parent categories
            // it was in before
            $existingParents = array();

            if ($status != 'new') {
                // if category is existing, check if it has parent categories that were deleted
                // by a user who is not authorized to do so
                // if that is the case, add those categories back in
                $parentCategories = $category->getParentCategories();

                /**
                 * Parent category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                foreach ($parentCategories as $category) {
                    $existingParents[] = $category->getUid();

                    /**
                     * Category mounts.
                     *
                     * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
                     */
                    $mount = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\CategoryMounts');
                    $mount->init((int) $backendUser->user['uid']);

                    // if the user has no right to see one of the parent categories or its not
                    // in the mounts it would miss afterwards
                    // by this its readded to the parent_category field
                    if (!$category->isPermissionSet('show') || !$mount->isInCommerceMounts($category->getUid())) {
                        $fieldArray['parent_category'] .= ',' . $category->getUid();
                    }
                }
            }

            // Unique the list
            $fieldArray['parent_category'] = implode(',', $this->belib->getUidListFromList(explode(',', GeneralUtility::uniqueList($fieldArray['parent_category']))));

            // abort if the user didn't assign a category - rights need not be checked then
            if ($fieldArray['parent_category'] == '') {
                /**
                 * Category mounts.
                 *
                 * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
                 */
                $mount = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\CategoryMounts');
                $mount->init((int) $backendUser->user['uid']);

                if ($mount->isInCommerceMounts(0)) {
                    // assign the root as the parent category if it is empty
                    $fieldArray['parent_category'] = 0;
                } else {
                    $pObj->newlog('You have to assign a category as a parent category.', 1);
                    $fieldArray = array();
                }

                return;
            }

            // Check if any parent_category has been set that is not allowed because no
            // child-records are to be set beneath it
            // Only on parents that were newly added
            $newParents = array_diff(explode(',', $fieldArray['parent_category']), $existingParents);

            // work with keys because array_diff does not start with key 0 but keeps the
            // old keys - that means gaps could exist
            $keys = array_keys($newParents);
            $l = count($keys);

            if ($l) {
                $groupRights = false;
                $groupId = 0;

                for ($i = 0; $i < $l; ++$i) {
                    $uid = (int) $newParents[$keys[$i]];

                    /**
                     * Category
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                     */
                    $category = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Category', $uid);

                    /**
                     * Category mounts.
                     *
                     * @var \CommerceTeam\Commerce\Tree\CategoryMounts $mount
                     */
                    $mount = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Tree\\CategoryMounts');
                    $mount->init((int) $backendUser->user['uid']);

                    // abort if the parent category is not in the webmounts
                    if (!$mount->isInCommerceMounts($uid)) {
                        $fieldArray['parent_category'] = '';
                        break;
                    }

                    // skip the root for permission check - if it is in mounts, it is allowed
                    if (!$uid) {
                        continue;
                    }

                    $category->loadPermissions();

                    // remove category from list if it is not permitted
                    if (!$category->isPermissionSet('new')) {
                        $fieldArray['parent_category'] = GeneralUtility::rmFromList(
                            $uid,
                            $fieldArray['parent_category']
                        );
                    } else {
                        // conversion to int is important, otherwise the binary & will not work properly
                        if ($groupRights === false) {
                            $groupRights = (int) $category->getPermsGroup();
                        } else {
                            $groupRights = ($groupRights & (int) $category->getPermsGroup());
                        }

                        $groupId = $category->getPermsGroupId();
                    }
                }

                // set the group id and permissions for a new record
                if ($status == 'new') {
                    $fieldArray['perms_group'] = $groupRights;
                    $fieldArray['perms_groupid'] = $groupId;
                }
            }

            // if there is no parent_category left from the ones the user wanted to add,
            // abort and inform him.
            if ($fieldArray['parent_category'] == '' && !empty($newParents)) {
                $pObj->newlog(
                    'You dont have the permissions to use any of the parent categories you chose as a parent.',
                    1
                );
                $fieldArray = array();
            }

            // make sure the category does not end up as its own parent - would lead
            // to endless recursion.
            if ($fieldArray['parent_category'] != '' && $status == 'new') {
                $catUids = GeneralUtility::intExplode(',', $fieldArray['parent_category']);

                foreach ($catUids as $catUid) {
                    // Skip root.
                    if (!$catUid) {
                        continue;
                    }

                    // Make sure we did not assign self as parent category
                    if ($catUid == $id) {
                        $pObj->newlog('You cannot select this category itself as a parent category.', 1);
                        $fieldArray = array();
                    }

                    /**
                     * Category.
                     *
                     * @var \CommerceTeam\Commerce\Domain\Model\Category $catDirect
                     */
                    $catDirect = GeneralUtility::makeInstance(
                        'CommerceTeam\\Commerce\\Domain\\Model\\Category',
                        $catUid
                    );
                    $catDirect->loadData();

                    $tmpCats = $catDirect->getParentCategories();
                    $tmpParents = null;
                    $i = 1000;

                    while (!is_null($cat = @array_pop($tmpCats))) {
                        // Prevent endless recursion
                        if ($i < 0) {
                            $pObj->newlog(
                                'Endless recursion occured while processing your request.
                                     Notify your admin if this error persists.',
                                1
                            );
                            $fieldArray = array();
                        }

                        if ($cat->getUid() == $id) {
                            $pObj->newlog(
                                'You cannot select a child category or self as a parent category.
                                    Selected Category in question: ' . $catDirect->getTitle(),
                                1
                            );
                            $fieldArray = array();
                        }

                        $tmpParents = $cat->getParentCategories();

                        if (is_array($tmpParents) && !empty($tmpParents)) {
                            $tmpCats = array_merge($tmpCats, $tmpParents);
                        }

                        --$i;
                    }
                }
            }
        }
    }

    /**
     * Checks if the permissions we need to process the datamap are still in place.
     *
     * @param string $status Status
     * @param string $table Table
     * @param int|string $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     *
     * @return array
     */
    protected function postProcessProduct($status, $table, $id, array &$fieldArray, DataHandler $pObj)
    {
        $backendUser = $this->getBackendUser();

        $data = $pObj->datamap[$table][$id];

        // Read the old parent categories
        if ($status != 'new') {
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $item
             */
            $item = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Product', $id);

            $parentCategories = $item->getParentCategories();

            // check existing categories
            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $parentCategories,
                array('editcontent')
            )) {
                $pObj->newlog('You dont have the permissions to edit the product.', 1);
                $fieldArray = array();
            }
        } else {
            // new products have to have a category
            // if a product is copied, we never check if it has categories
            // - this is MANDATORY, otherwise localize will not work at all!!!
            // remove this only if you decide to not define the l10n_mode of "categories"
            if (!trim($fieldArray['categories']) && !isset($backendUser->uc['txcommerce_copyProcess'])) {
                $pObj->newlog('You have to specify at least 1 parent category for the product.', 1);
                $fieldArray = array();
            }

            $parentCategories = array();
        }

        // check new categories
        if (isset($data['categories'])) {
            $newCats = $this->singleDiffAssoc(
                $this->belib->getUidListFromList(GeneralUtility::trimExplode(',', GeneralUtility::uniqueList($data['categories']))),
                $parentCategories
            );

            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $newCats,
                array('editcontent')
            )) {
                $pObj->newlog('You do not have the permissions to add one or all categories you added.'.
                    GeneralUtility::uniqueList($data['categories']), 1);
                $fieldArray = array();
            }
        }

        if (isset($fieldArray['categories'])) {
            $fieldArray['categories'] = GeneralUtility::uniqueList($fieldArray['categories']);
        }

        return $data;
    }

    /**
     * Checks if the permissions we need to process the datamap are still in place.
     *
     * @param string $status Status
     * @param int|string $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     *
     * @return void
     */
    protected function postProcessArticle($status, $id, array &$fieldArray, DataHandler $pObj)
    {
        $backendUser = $this->getBackendUser();

        $parentCategories = array();

        // Read the old parent product - skip this if we are copying or
        // overwriting an article
        if ($status != 'new' && !$backendUser->uc['txcommerce_copyProcess']) {
            /**
             * Article.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Article $article
             */
            $article = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Article', $id);
            $article->loadData();

            // get the parent categories of the product
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $product
             */
            $product = GeneralUtility::makeInstance(
                'CommerceTeam\\Commerce\\Domain\\Model\\Product',
                $article->getParentProductUid()
            );
            $product->loadData();

            if ($product->getL18nParent()) {
                $product = GeneralUtility::makeInstance(
                    'CommerceTeam\\Commerce\\Domain\\Model\\Product',
                    $product->getL18nParent()
                );
                $product->loadData();
            }

            $parentCategories = $product->getParentCategories();
        }

        // read new assigned product
        if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
            $parentCategories,
            array('editcontent')
        )) {
            $pObj->newlog('You dont have the permissions to edit the article.', 1);
            $fieldArray = array();
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
     * After the relations are stored in the database, we have to call the
     * dynaflex extension to modify the TCA that it fit's the current
     * situation of saved database entries. We call it here because the TCA
     * is allready built and so the calls in the tca.php of commerce won't
     * be executed between now and the point where the backendform is
     * rendered.
     *
     * @param string $status Status
     * @param string $table Table
     * @param int $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     *
     * @return void
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, DataHandler $pObj)
    {
        // get the UID of the created record if it was just created
        if ($status == 'new' && !empty($fieldArray)) {
            $this->unsubstitutedId = $id;
            $id = $pObj->substNEWwithIDs[$id];
        }

        switch ($table) {
            case 'tx_commerce_categories':
                $this->afterDatabaseCategory($fieldArray, $id);
                break;

            case 'tx_commerce_products':
                $this->afterDatabaseProduct($status, $table, $id, $fieldArray, $pObj);
                break;

            case 'tx_commerce_article_prices':
                $this->afterDatabasePrice($fieldArray, $id);
                break;

            default:
        }

        if (TYPO3_MODE == 'BE') {
            BackendUtility::setUpdateSignal('updateFolderTree');
        }

        $this->afterDatabaseHandleDynaflex($table, $id);
    }

    /**
     * After database category handling.
     *
     * @param array $fieldArray Field array
     * @param int $id Id
     *
     * @return void
     */
    protected function afterDatabaseCategory(array $fieldArray, $id)
    {
        // if unset, do not save anything, but load the dynaflex
        if (!empty($fieldArray)) {
            if (isset($fieldArray['parent_category'])) {
                // get the list of parent categories and save the relations in the database
                $catList = explode(',', $fieldArray['parent_category']);

                // preserve the 0 as root.
                $preserve = array();

                if (in_array(0, $catList)) {
                    $preserve[] = 0;
                }

                // extract uids.
                $catList = $this->belib->getUidListFromList($catList);
                $catList = $this->belib->extractFieldArray($catList, 'uid_foreign', true);

                // add preserved
                $catList = array_merge($catList, $preserve);

                $this->belib->saveRelations($id, $catList, 'tx_commerce_categories_parent_category_mm', true);
            }

            // save all relations concerning categories
            $this->saveCategoryRelations($id, $fieldArray);
        }
    }

    /**
     * After database product handling.
     *
     * @param string $status Status
     * @param string $table Table
     * @param string|int $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $pObj Parent object
     *
     * @return void
     */
    protected function afterDatabaseProduct($status, $table, $id, array $fieldArray, DataHandler $pObj)
    {
        // if fieldArray has been unset, do not save anything, but load dynaflex config
        if (!empty($fieldArray)) {
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $product
             */
            $product = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Product', $id);
            $product->loadData();

            if (isset($fieldArray['categories'])) {
                $catList = $this->belib->getUidListFromList(explode(',', $fieldArray['categories']));
                $catList = $this->belib->extractFieldArray($catList, 'uid_foreign', true);

                // get id of the live placeholder instead if such exists
                $relId = ($status != 'new' && $product->getPid() == '-1') ? $product->getT3verOid() : $id;

                $this->belib->saveRelations($relId, $catList, 'tx_commerce_products_categories_mm', true, false);
            }

            // if the live shadow is saved, the product relations have to be saved
            // to the versioned version
            if ($status == 'new' && $fieldArray['pid'] == '-1') {
                ++$id;
            }

            $this->saveProductRelations($id, $fieldArray);
        }

        // sometimes the array is unset because only the checkbox "create new article"
        // has been checked if that is the case and we have the rights, create the
        // articles so we check if the product is already created and if we have edit
        // rights on it
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($id)) {
            // check permissions
            /**
             * Product.
             *
             * @var \CommerceTeam\Commerce\Domain\Model\Product $product
             */
            $product = GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Domain\\Model\\Product', $id);

            $parentCategories = $product->getParentCategories();

            // check existing categories
            if (!\CommerceTeam\Commerce\Utility\BackendUtility::checkPermissionsOnCategoryContent(
                $parentCategories,
                array('editcontent')
            )) {
                $pObj->newlog('You dont have the permissions to create a new article.', 1);
            } else {
                // init the article creator
                /**
                 * Article creator.
                 *
                 * @var \CommerceTeam\Commerce\Utility\ArticleCreatorUtility $articleCreator
                 */
                $articleCreator = GeneralUtility::makeInstance(
                    'CommerceTeam\\Commerce\\Utility\\ArticleCreatorUtility'
                );
                $articleCreator->init($id, $this->belib->getProductFolderUid());

                $datamap = $pObj->datamap[$table][$this->unsubstitutedId ? $this->unsubstitutedId : $id];
                if (is_array($datamap) && !empty($datamap)) {
                    // create new articles
                    $articleCreator->createArticles($datamap);
                }

                // update articles if new attributes were added
                $articleCreator->updateArticles();
            }
        }
    }

    /**
     * After database price handling.
     *
     * @param array $fieldArray Field array
     * @param int $id Id
     *
     * @return void
     */
    protected function afterDatabasePrice(array $fieldArray, $id)
    {
        if (!isset($fieldArray['uid_article'])) {
            $database = $this->getDatabaseConnection();

            $uidArticleRow = $database->exec_SELECTgetSingleRow(
                'uid_article',
                'tx_commerce_article_prices',
                'uid = ' . (int) $id
            );
            $uidArticle = $uidArticleRow['uid_article'];
        } else {
            $uidArticle = $fieldArray['uid_article'];
        }

        // @todo what to do with this? it was empty before refactoring
        $this->belib->savePriceFlexformWithArticle($id, $uidArticle, $fieldArray);
    }

    /**
     * After database dynaflex handling.
     *
     * @param string $table Table
     * @param int $id Id
     *
     * @return void
     */
    protected function afterDatabaseHandleDynaflex($table, $id)
    {
        $backendUser = $this->getBackendUser();

        $record = BackendUtility::getRecord($table, $id);

        $backendUser->uc['txcommerce_afterDatabaseOperations'] = 1;
        $backendUser->writeUC();

        $dynaFlexConf = \Tx_Dynaflex_Utility_TcaUtility::loadDynaFlexConfig($table, (int) $record['pid'], $record);
        $dynaFlexConf = $dynaFlexConf['DCA'];

        $backendUser->uc['txcommerce_afterDatabaseOperations'] = 0;
        $backendUser->writeUC();

        if (!is_array($dynaFlexConf) || empty($dynaFlexConf)) {
            return;
        }

        // txcommerce_copyProcess: this is so that dynaflex is not called when we copy
        // an article - otherwise we would get an error
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dynaflex')
            && (!isset($backendUser->uc['txcommerce_copyProcess']) || $backendUser->uc['txcommerce_copyProcess'])
        ) {
            $dynaFlexConf[0]['uid'] = $id;
            $dynaFlexConf[1]['uid'] = $id;

            /**
             * Dynaflex.
             *
             * @var \Tx_Dynaflex_Utility_TcaUtility
             */
            $dynaflex = GeneralUtility::makeInstance('Tx_Dynaflex_Utility_TcaUtility', $GLOBALS['TCA'], $dynaFlexConf);
            $GLOBALS['TCA'] = $dynaflex->getDynamicTCA();
        }
    }

    /**
     * Update article attribute relations.
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     *
     * @return void
     */
    protected function updateArticleAttributeRelations(array $incomingFieldArray, $id)
    {
        if (isset($incomingFieldArray['attributesedit'])) {
            $database = $this->getDatabaseConnection();

            // get the data from the flexForm
            $attributes = $incomingFieldArray['attributesedit']['data']['sDEF']['lDEF'];

            foreach ($attributes as $aKey => $aValue) {
                $value = $aValue['vDEF'];
                $attributeId = $this->belib->getUidFromKey($aKey, $aValue);
                $attributeData = $this->belib->getAttributeData($attributeId, 'has_valuelist,multiple,sorting');

                if ($attributeData['multiple'] == 1) {
                    // remove relations before creating new relations this is needed because we dont
                    // know which attribute were removed
                    $database->exec_DELETEquery(
                        'tx_commerce_articles_article_attributes_mm',
                        'uid_local = ' . $id . ' AND uid_foreign = ' . $attributeId
                    );

                    $relCount = 0;
                    $relations = GeneralUtility::trimExplode(',', $value, true);
                    foreach ($relations as $relation) {
                        $updateArrays = $this->belib->getUpdateData($attributeData, $relation);

                        // create relations for current saved attributes
                        $database->exec_INSERTquery(
                            'tx_commerce_articles_article_attributes_mm',
                            array_merge(
                                array(
                                    'uid_local' => $id,
                                    'uid_foreign' => $attributeId,
                                    'sorting' => $attributeData['sorting'],
                                ),
                                $updateArrays[1]
                            )
                        );
                        ++$relCount;
                    }

                    // insert at least one relation
                    if (!$relCount) {
                        $database->exec_INSERTquery(
                            'tx_commerce_articles_article_attributes_mm',
                            array(
                                'uid_local' => $id,
                                'uid_foreign' => $attributeId,
                                'sorting' => $attributeData['sorting'],
                            )
                        );
                    }
                } else {
                    $updateArrays = $this->belib->getUpdateData($attributeData, $value);

                    // update article attribute relation
                    $database->exec_UPDATEquery(
                        'tx_commerce_articles_article_attributes_mm',
                        'uid_local = ' . $id . ' AND uid_foreign = ' . $attributeId,
                        $updateArrays[1]
                    );
                }

                // recalculate hash for this article
                $this->belib->updateArticleHash($id);
            }
        }
    }

    /**
     * Save category relations.
     *
     * @param int $cUid Categor uid
     * @param array $fieldArray Field array
     * @param bool $saveAnyway Save anyway
     * @param bool $delete Delete
     * @param bool $updateXml Update xml
     *
     * @return void
     */
    protected function saveCategoryRelations(
        $cUid,
        array $fieldArray = array(),
        $saveAnyway = false,
        $delete = true,
        $updateXml = true
    ) {
        // now we have to save all attribute relations for this category and all their
        // child categories  but only if the fieldArray has changed
        if (isset($fieldArray['attributes']) || $saveAnyway) {
            // get all parent categories ...
            $catList = array();
            $this->belib->getParentCategories($cUid, $catList, $cUid, 0, false);

            // get all correlation types
            $correlationTypeList = $this->belib->getAllCorrelationTypes();

            // get their attributes
            $paList = $this->belib->getAttributesForCategoryList($catList);

            // Then extract all attributes from this category and merge it into the
            // attribute list
            if (!empty($fieldArray['attributes'])) {
                $ffData = (array) GeneralUtility::xml2array($fieldArray['attributes']);
            } else {
                $ffData = array();
            }
            if (!is_array($ffData['data']) || !is_array($ffData['data']['sDEF'])) {
                $ffData = array();
            }

            $this->belib->mergeAttributeListFromFFData(
                (array) $ffData['data']['sDEF']['lDEF'],
                'ct_',
                $correlationTypeList,
                $cUid,
                $paList
            );

            // get the list of uid_foreign and save relations for this category
            $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, array('uid_correlationtype'));
            $this->belib->saveRelations($cUid, $uidList, 'tx_commerce_categories_attributes_mm', $delete, false);

            // update the XML structure if needed
            if ($updateXml) {
                $this->belib->updateXML(
                    'attributes',
                    'tx_commerce_categories',
                    $cUid,
                    'category',
                    $correlationTypeList
                );
            }

            // save all attributes of this category into all poroducts,
            // that are related to it
            $products = $this->belib->getProductsOfCategory($cUid);
            if (!empty($products)) {
                foreach ($products as $product) {
                    $this->belib->saveRelations(
                        $product['uid_local'],
                        $uidList,
                        'tx_commerce_products_attributes_mm',
                        false,
                        false
                    );
                    $this->belib->updateXML(
                        'attributes',
                        'tx_commerce_products',
                        $product['uid_local'],
                        'product',
                        $correlationTypeList
                    );
                }
            }

            // get children of this category after this operation the childList contains
            // all categories that are related to this category (recursively)
            $childList = array();
            $this->belib->getChildCategories($cUid, $childList, $cUid, 0, false);

            foreach ($childList as $childUid) {
                $this->saveCategoryRelations($childUid, array(), true, false);
            }
        }
    }

    /**
     * Saves all relations between products and his attributes.
     *
     * @param int $productId The UID of the product
     * @param array $fieldArray Field array
     *
     * @return void
     */
    protected function saveProductRelations($productId, array $fieldArray = null)
    {
        $productId = (int) $productId;
        // first step is to save all relations between this product and all attributes
        // of this product.
        // We don't have to check for any parent categories, because the attributes
        // from them should already be saved for this product.
        $database = $this->getDatabaseConnection();

        // create an article and a new price for a new product
        if (SettingsFactory::getInstance()->getExtConf('simpleMode') && $productId != null) {
            // search for an article of this product
            $res = $database->exec_SELECTquery('*', 'tx_commerce_articles', 'uid_product = ' . $productId, '', '', 1);

            $aRes = array();
            if ($database->sql_num_rows($res)) {
                $aRes = $database->sql_fetch_assoc($res);
                $aUid = $aRes['uid'];
            } else {
                // create a new article if no one exists
                $pRes = $database->exec_SELECTquery('title', 'tx_commerce_products', 'uid = ' . $productId, '', '', 1);
                $productData = $database->sql_fetch_assoc($pRes);

                $database->exec_INSERTquery(
                    'tx_commerce_articles',
                    array(
                        'pid' => $fieldArray['pid'],
                        'tstamp' => $GLOBALS['EXEC_TIME'],
                        'crdate' => $GLOBALS['EXEC_TIME'],
                        'uid_product' => $productId,
                        'article_type_uid' => 1,
                        'title' => $productData['title'],
                    )
                );
                $aUid = $database->sql_insert_id();
            }

            // check if the article has already a price
            $row = $database->exec_SELECTgetSingleRow(
                '*',
                'tx_commerce_article_prices',
                'uid_article = ' . $productId
            );
            if (empty($row) && $aRes['sys_language_uid'] < 1) {
                // create a new price if no one exists
                $database->exec_INSERTquery(
                    'tx_commerce_article_prices',
                    array(
                        'pid' => $fieldArray['pid'],
                        'uid_article' => $aUid,
                        'tstamp' => $GLOBALS['EXEC_TIME'],
                        'crdate' => $GLOBALS['EXEC_TIME'],
                    )
                );
            }
        }

        $delete = true;
        if (isset($fieldArray['categories'])) {
            $catList = array();
            $res = $database->exec_SELECTquery(
                'uid_foreign',
                'tx_commerce_products_categories_mm',
                'uid_local = ' . $productId
            );
            while (($sres = $database->sql_fetch_assoc($res))) {
                $catList[] = $sres['uid_foreign'];
            }
            $paList = $this->belib->getAttributesForCategoryList($catList);
            $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, array('uid_correlationtype'));

            $this->belib->saveRelations($productId, $uidList, 'tx_commerce_products_attributes_mm', false, false);
            $this->belib->updateXML('attributes', 'tx_commerce_products', $productId, 'product', $catList);
            $delete = false;
        }

        $articles = false;
        if (isset($fieldArray['attributes'])) {
            // get all correlation types
            $correlationTypeList = $this->belib->getAllCorrelationTypes();
            $paList = array();

            // extract all attributes from FlexForm
            $ffData = GeneralUtility::xml2array($fieldArray['attributes']);
            if (is_array($ffData)) {
                $this->belib->mergeAttributeListFromFFData(
                    $ffData['data']['sDEF']['lDEF'],
                    'ct_',
                    $correlationTypeList,
                    $productId,
                    $paList
                );
            }
            // get the list of uid_foreign and save relations for this category
            $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, array('uid_correlationtype'));

            // get all ct4 attributes
            $ct4Attributes = array();
            if (is_array($uidList)) {
                foreach ($uidList as $uidItem) {
                    if ($uidItem['uid_correlationtype'] == 4) {
                        $ct4Attributes[] = $uidItem['uid_foreign'];
                    }
                }
            }

            $this->belib->saveRelations($productId, $uidList, 'tx_commerce_products_attributes_mm', $delete, false);

            /*
             * Rebuild the XML (last param set to true)
             * Fixes that l10n of products had invalid XML attributes
             */
            $this->belib->updateXML(
                'attributes',
                'tx_commerce_products',
                $productId,
                'product',
                $correlationTypeList,
                true
            );

            // update the XML for this product, we remove everything that is not
            // set for current attributes
            $pXml = $database->exec_SELECTquery('attributesedit', 'tx_commerce_products', 'uid = ' . $productId);
            $pXml = $database->sql_fetch_assoc($pXml);

            if (!empty($pXml['attributesedit'])) {
                $pXml = GeneralUtility::xml2array($pXml['attributesedit']);

                if (is_array($pXml['data']['sDEF']['lDEF'])) {
                    foreach (array_keys($pXml['data']['sDEF']['lDEF']) as $key) {
                        $data = array();
                        $uid = $this->belib->getUIdFromKey($key, $data);
                        if (!in_array($uid, $ct4Attributes)) {
                            unset($pXml['data']['sDEF']['lDEF'][$key]);
                        }
                    }
                }

                if (is_array($pXml) && is_array($pXml['data']) && is_array($pXml['data']['sDEF'])) {
                    $pXml = GeneralUtility::array2xml($pXml, '', 0, 'T3FlexForms');
                    $fieldArray['attributesedit'] = $pXml;
                }
            }

            // now get all articles that where created from this product
            $articles = $this->belib->getArticlesOfProduct($productId);

            // build relation table
            if (is_array($articles) && !empty($articles)) {
                $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true);
                foreach ($articles as $article) {
                    $this->belib->saveRelations(
                        $article['uid'],
                        $uidList,
                        'tx_commerce_articles_article_attributes_mm',
                        true,
                        false
                    );
                }
            }
        }

        $updateArrays = array();
        // update all articles of this product
        if (!empty($fieldArray['attributesedit'])) {
            $ffData = (array) GeneralUtility::xml2array($fieldArray['attributesedit']);
            if (is_array($ffData['data']) && is_array($ffData['data']['sDEF']['lDEF'])) {
                // get articles if they are not already there
                if (!$articles) {
                    $articles = $this->belib->getArticlesOfProduct($productId);
                }

                // update this product
                $articleRelations = array();
                $counter = 0;
                foreach ($ffData['data']['sDEF']['lDEF'] as $ffDataItemKey => $ffDataItem) {
                    ++$counter;

                    $attributeKey = $this->belib->getUidFromKey($ffDataItemKey, $keyData);
                    $attributeData = $this->belib->getAttributeData($attributeKey, 'has_valuelist,multiple');

                    // check if the attribute has more than one value, if that is true,
                    // we have to create a relation for each value
                    if ($attributeData['multiple'] == 1) {
                        // if we have a multiple valuelist we need to handle the attributes a little
                        // bit different first we delete all existing attributes
                        $database->exec_DELETEquery(
                            'tx_commerce_products_attributes_mm',
                            'uid_local = ' . $productId . ' AND uid_foreign = ' . $attributeKey
                        );

                        // now explode the data
                        $attributeValues = GeneralUtility::trimExplode(',', $ffDataItem['vDEF'], true);

                        foreach ($attributeValues as $attributeValue) {
                            // The first time an attribute value is selected, TYPO3 returns them
                            // INCLUDING an empty value! This would cause an unnecessary entry in the
                            // database, so we have to filter this here.
                            if (empty($attributeValue)) {
                                continue;
                            }

                            $updateData = $this->belib->getUpdateData($attributeData, $attributeValue, $productId);
                            $database->exec_INSERTquery(
                                'tx_commerce_products_attributes_mm',
                                array_merge(
                                    array(
                                        'uid_local' => $productId,
                                        'uid_foreign' => $attributeKey,
                                        'uid_correlationtype' => 4,
                                    ),
                                    $updateData[0]
                                )
                            );
                        }
                    } else {
                        // update a simple valuelist and normal attributes as usual
                        $updateArrays = $this->belib->getUpdateData($attributeData, $ffDataItem['vDEF'], $productId);
                        $database->exec_UPDATEquery(
                            'tx_commerce_products_attributes_mm',
                            'uid_local = ' . $productId . ' AND uid_foreign = ' . $attributeKey,
                            $updateArrays[0]
                        );
                    }

                    // update articles
                    if (is_array($articles) && !empty($articles)) {
                        foreach ($articles as $article) {
                            if ($attributeData['multiple'] == 1) {
                                // if we have a multiple valuelist we need to handle the attributes a little
                                // bit different first we delete all existing attributes
                                $database->exec_DELETEquery(
                                    'tx_commerce_articles_article_attributes_mm',
                                    'uid_local = ' . $article['uid'] . ' AND uid_foreign = ' . $attributeKey
                                );

                                // now explode the data
                                $attributeValues = GeneralUtility::trimExplode(',', $ffDataItem['vDEF'], true);
                                $attributeCount = 0;
                                $attributeValue = '';
                                foreach ($attributeValues as $attributeValue) {
                                    if (empty($attributeValue)) {
                                        continue;
                                    }

                                    ++$attributeCount;

                                    $updateData = $this->belib->getUpdateData(
                                        $attributeData,
                                        $attributeValue,
                                        $productId
                                    );
                                    $database->exec_INSERTquery(
                                        'tx_commerce_articles_article_attributes_mm',
                                        array_merge(
                                            array(
                                                'uid_local' => $article['uid'],
                                                'uid_foreign' => $attributeKey,
                                                'uid_product' => $productId,
                                                'sorting' => $counter,
                                            ),
                                            $updateData[1]
                                        )
                                    );
                                }

                                // create at least an empty relation if no attributes where set
                                if ($attributeCount == 0) {
                                    $updateData = $this->belib->getUpdateData(array(), $attributeValue, $productId);
                                    $database->exec_INSERTquery(
                                        'tx_commerce_articles_article_attributes_mm',
                                        array_merge(
                                            array(
                                                'uid_local' => $article['uid'],
                                                'uid_foreign' => $attributeKey,
                                                'uid_product' => $productId,
                                                'sorting' => $counter,
                                            ),
                                            $updateData[1]
                                        )
                                    );
                                }
                            } else {
                                // if the article has already this attribute, we have to insert so try
                                // to select this attribute for this article
                                $res = $database->exec_SELECTquery(
                                    'uid_local, uid_foreign',
                                    'tx_commerce_articles_article_attributes_mm',
                                    'uid_local = ' . $article['uid'] . ' AND uid_foreign = ' . $attributeKey
                                );

                                if ($database->sql_num_rows($res)) {
                                    $database->exec_UPDATEquery(
                                        'tx_commerce_articles_article_attributes_mm',
                                        'uid_local = ' . $article['uid'] . ' AND uid_foreign = ' . $attributeKey,
                                        array_merge($updateArrays[1], array('sorting' => $counter))
                                    );
                                } else {
                                    $database->exec_INSERTquery(
                                        'tx_commerce_articles_article_attributes_mm',
                                        array_merge($updateArrays[1], array(
                                            'uid_local' => $article['uid'],
                                            'uid_product' => $productId,
                                            'uid_foreign' => $attributeKey,
                                            'sorting' => $counter,
                                        ))
                                    );
                                }
                            }

                            $relArray = $updateArrays[0];
                            $relArray['uid_foreign'] = $attributeKey;
                            if (!in_array($relArray, $articleRelations)) {
                                $articleRelations[] = $relArray;
                            }

                            $this->belib->updateArticleHash($article['uid']);
                        }
                    }
                }

                // Finally update the Felxform for this Product
                $this->belib->updateArticleXML($articleRelations, false, null, $productId);

                // And add those datas from the database to the articles
                if (is_array($articles) && !empty($articles)) {
                    foreach ($articles as $article) {
                        $thisArticleRelations = $this->belib->getAttributesForArticle($article['uid']);

                        $this->belib->updateArticleXML($thisArticleRelations, false, $article['uid'], null);
                    }
                }
            }
        }

        // Check if we do have some localized products an call the method recursivly
        $resLocalised = $database->exec_SELECTquery(
            'uid',
            'tx_commerce_products',
            'deleted = 0 AND l18n_parent = ' . $productId
        );
        while (($rowLocalised = $database->sql_fetch_assoc($resLocalised))) {
            $this->saveProductRelations($rowLocalised['uid'], $fieldArray);
        }
    }

    /**
     * This Function is simlar to array_diff but looks for array sorting too.
     *
     * @param array $array1 Array one - Reference is only to save memory
     * @param array $array2 Array two - Reference is only to save memory
     *
     * @return array $result different fields between array1 & array2
     */
    protected function singleDiffAssoc(array &$array1, array &$array2)
    {
        $result = array();

        // check for each value if in array2 the index is not set or
        // the value is not equal
        foreach ($array1 as $index => $value) {
            if (!isset($array2[$index]) || $array2[$index] != $value) {
                $result[$index] = $value;
            }
        }

        // check for each value if in array1 the index is not set or the value is not
        // equal and in result the index is not set
        foreach ($array2 as $index => $value) {
            if ((!isset($array1[$index]) || $array1[$index] != $value) && !isset($result[$index])) {
                $result[$index] = $value;
            }
        }

        return $result;
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

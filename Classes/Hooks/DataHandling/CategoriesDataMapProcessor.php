<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use CommerceTeam\Commerce\Domain\Model\Category;
use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use CommerceTeam\Commerce\Utility\BackendUserUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoriesDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * @var array
     */
    protected static $parentCategoriesPreProcessing = [];

    /**
     * @param DataHandler $dataHandler
     */
    public function beforeStart(DataHandler $dataHandler)
    {
        $categories = array_keys($dataHandler->datamap['tx_commerce_categories']);

        foreach ($categories as $categoryUid) {
            /** @var Category $category */
            $category = GeneralUtility::makeInstance(Category::class, $categoryUid);
            $category->loadData();

            if ($category->getL18nParent()) {
                $category = GeneralUtility::makeInstance(Category::class, $category->getL18nParent());
            }

            self::$parentCategoriesPreProcessing[$categoryUid] = $category->getParentCategories();
        }
    }

    /**
     * Remove current category uid from  parent_category
     *
     * @param array $incomingFieldArray Incoming field array
     * @param int $id Id
     *
     * @return array
     */
    public function preProcess(array &$incomingFieldArray, $id)
    {
        $categories = array_diff(
            GeneralUtility::trimExplode(',', $incomingFieldArray['parent_category'], true),
            [$id]
        );

        $incomingFieldArray['parent_category'] = !empty($categories) ? implode(',', $categories) : '';

        return $categories;
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
     */
    public function postProcess($status, $table, $id, array &$fieldArray, DataHandler $pObj)
    {
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

                /** @var Category $category */
                $category = GeneralUtility::makeInstance(Category::class, $checkId);
                $category->loadData();

                // Use the l18n parent as category for permission checks.
                if ($l18nParent || $category->getField('l18n_parent') > 0) {
                    $checkId = $l18nParent ?: $category->getField('l18n_parent');
                    $category = GeneralUtility::makeInstance(Category::class, $checkId);
                }

                /** @var BackendUserUtility $backendUserUtility */
                $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
                if (!$category->isPermissionSet('edit') || !$backendUserUtility->isInWebMount($category->getUid())) {
                    $pObj->newlog('You dont have the permissions to edit this category.', 1);
                    $fieldArray = [];

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
                /** @noinspection PhpInternalEntityUsedInspection */
                $fieldArray['perms_userid'] = $this->getBackendUserAuthentication()->user['uid'];
                // 31 grants every right
                $fieldArray['perms_user'] = 31;
            }

            // break if the parent_categories didn't change
            if (!isset($fieldArray['parent_category'])) {
                return;
            }

            // check if we are allowed to create new categories under the newly assigned categories
            // check if we are allowed to remove this category from the parent categories it was in before
            $existingParents = [];
            $newParents = [];
            if ($status != 'new') {
                $fieldArray = $this->restoreDeletedParentCategory(
                    $fieldArray,
                    $existingParents,
                    $newParents,
                    $category
                );
            }

            // abort if the user didn't assign a category - rights need not be checked then
            if ($fieldArray['parent_category'] == '') {
                if ($this->getBackendUserAuthentication()->isAdmin()) {
                    // assign the root as the parent category if it is empty
                    $fieldArray['parent_category'] = 0;
                } else {
                    $pObj->newlog('You have to assign a category as a parent category.', 1);
                    $fieldArray = [];
                }

                return;
            }

            if (count($newParents)) {
                $groupRights = false;
                $groupId = 0;

                /** @var CategoryRepository $categoryRepository */
                $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
                foreach ($newParents as $uid) {
                    /** @var Category $parentCategory */
                    $parentCategory = GeneralUtility::makeInstance(Category::class, $uid);

                    // abort if the parent category is not in the webmounts
                    /** @var BackendUserUtility $backendUserUtility */
                    $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
                    if (!$backendUserUtility->isInWebMount($uid)) {
                        $fieldArray['parent_category'] = '';
                        break;
                    }

                    // skip the root for permission check - if it is in mounts, it is allowed
                    if (!$uid) {
                        continue;
                    }

                    $parentCategory->loadPermissions();

                    // remove category from list if it is not permitted
                    if (!$parentCategory->isPermissionSet('new')) {
                        $categoryRepository->removeParentRelation($category->getUid(), $parentCategory->getUid());
                        $fieldArray['parent_category']--;
                    } else {
                        // conversion to int is important, otherwise the binary & will not work properly
                        if ($groupRights === false) {
                            $groupRights = (int) $parentCategory->getPermsGroup();
                        } else {
                            $groupRights = ($groupRights & (int) $parentCategory->getPermsGroup());
                        }

                        $groupId = $parentCategory->getPermsGroupId();
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
            if ($fieldArray['parent_category'] < 1 && !empty($newParents)) {
                $pObj->newlog(
                    'You dont have the permissions to use any of the parent categories you chose as a parent.',
                    1
                );
                $fieldArray = [];
                return;
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
                        $fieldArray = [];
                    }

                    /**
                     * Category.
                     *
                     * @var Category $catDirect
                     */
                    $catDirect = GeneralUtility::makeInstance(
                        Category::class,
                        $catUid
                    );
                    $catDirect->loadData();

                    $tmpCats = $catDirect->getParentCategories();
                    $tmpParents = null;
                    $i = 1000;

                    /** @var Category $cat */
                    while (!is_null($cat = @array_pop($tmpCats))) {
                        // Prevent endless recursion
                        if ($i < 0) {
                            $pObj->newlog(
                                'Endless recursion occured while processing your request.
                                     Notify your admin if this error persists.',
                                1
                            );
                            $fieldArray = [];
                        }

                        if ($cat->getUid() == $id) {
                            $pObj->newlog(
                                'You cannot select a child category or self as a parent category.
                                    Selected Category in question: ' . $catDirect->getTitle(),
                                1
                            );
                            $fieldArray = [];
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
     * If a user delete a parent category which is not accessible this restores the references
     *
     * @param array $fieldArray
     * @param array $existingParents
     * @param array $newParents
     * @param Category $category
     *
     * @return array
     */
    protected function restoreDeletedParentCategory($fieldArray, &$existingParents, &$newParents, Category $category)
    {
        // due to the DataHandlerProcessor the current parent categories are already referenced in
        // the database that's why we can resort on fetching them
        $currentParentCategories = $category->getParentCategories();

        /** @var BackendUserUtility $backendUserUtility */
        $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        if (isset(self::$parentCategoriesPreProcessing[$category->getUid()])) {
            /** @var Category $parentCategory */
            /** @var Category $currentParentCategory */
            foreach (self::$parentCategoriesPreProcessing[$category->getUid()] as $parentCategory) {
                $found = false;
                foreach ($currentParentCategories as $currentParentCategory) {
                    $newParents[] = $currentParentCategory->getUid();
                    if ($parentCategory->getUid() == $currentParentCategory->getUid()) {
                        $found = true;
                    }
                }
                if ($found) {
                    $existingParents[] = $parentCategory->getUid();
                } elseif (!$parentCategory->isPermissionSet('show')
                    || !$backendUserUtility->isInWebMount($parentCategory->getUid())
                ) {
                    $existingParents[] = $parentCategory->getUid();
                    // field only contains the count
                    $fieldArray['parent_category']++;

                    $sorting = 1 + $categoryRepository->findHighestParentCategoryReferenceSorting($category->getUid());

                    $categoryRepository->insertParentRelation(
                        $category->getUid(),
                        $parentCategory->getUid(),
                        $sorting
                    );
                }
            }
        }

        // remove all old references from current to get the new references
        $newParents = array_diff(array_unique($newParents), $existingParents);
        $newParents = array_values($newParents);

        return $fieldArray;
    }

    /**
     * After database category handling.
     *
     * @param string $table Table
     * @param string|int $id Id
     * @param array $fieldArray Field array
     * @param DataHandler $dataHandler Parent object
     * @param string $_
     */
    public function afterDatabase($table, $id, array $fieldArray, DataHandler $dataHandler, $_)
    {
        if (!empty($fieldArray)) {
            if (isset($fieldArray['parent_category'])) {
                // get the list of parent categories and save the relations in the database
                if (!empty($dataHandler->datamap[$table][$id]['parent_category'])) {
                    $categories = explode(',', $dataHandler->datamap[$table][$id]['parent_category']);
                } else {
                    $categories = [];
                }

                // preserve the 0 as root.
                $preserve = [];
                if (in_array(0, $categories)) {
                    $preserve[] = 0;
                }

                // extract uids.
                $categories = $this->belib->extractFieldArray($categories, 'uid_foreign', true);

                // add preserved
                $categories = array_merge($categories, $preserve);

                $this->belib->saveRelations(
                    $this->getSubstitutedId($dataHandler, $id),
                    $categories,
                    'tx_commerce_categories_parent_category_mm',
                    true
                );
            }

            // save all relations concerning categories
            $this->saveCategoryRelations($this->getSubstitutedId($dataHandler, $id), $fieldArray);
        }
    }

    /**
     * Save category relations.
     *
     * @param int $categoryUid Categor uid
     * @param array $fieldArray Field array
     * @param bool $saveAnyway Save anyway
     * @param bool $delete Delete
     * @param bool $updateXml Update xml
     */
    protected function saveCategoryRelations(
        $categoryUid,
        array $fieldArray = [],
        $saveAnyway = false,
        $delete = true,
        $updateXml = true
    ) {
        // now we have to save all attribute relations for this category and all their
        // child categories but only if the fieldArray has changed
        if (isset($fieldArray['attributes']) || $saveAnyway) {
            /** @var AttributeRepository $attributeRepository */
            $attributeRepository = GeneralUtility::makeInstance(AttributeRepository::class);

            // get all parent categories ...
            $catList = [];
            $this->belib->getParentCategories($categoryUid, $catList, $categoryUid, 0, false);

            // get all correlation types
            $correlationTypeList = $attributeRepository->findAllCorrelationTypes();

            // get their attributes
            $paList = $this->belib->getAttributesForCategoryList($catList);

            // Then extract all attributes from this category and merge it into the
            // attribute list
            if (!empty($fieldArray['attributes'])) {
                $ffData = (array) GeneralUtility::xml2array($fieldArray['attributes']);
            } else {
                $ffData = [];
            }
            if (!is_array($ffData['data']) || !is_array($ffData['data']['sDEF'])) {
                $ffData = [];
            }

            $this->belib->mergeAttributeListFromFlexFormData(
                (array) $ffData['data']['sDEF']['lDEF'],
                'ct_',
                $correlationTypeList,
                $categoryUid,
                $paList
            );

            // get the list of uid_foreign and save relations for this category
            $uidList = $this->belib->extractFieldArray($paList, 'uid_foreign', true, ['uid_correlationtype']);
            $this->belib->saveRelations($categoryUid, $uidList, 'tx_commerce_categories_attributes_mm', $delete, false);

            // update the XML structure if needed
            if ($updateXml) {
                $this->belib->updateXML(
                    'attributes',
                    'tx_commerce_categories',
                    $categoryUid,
                    'category',
                    $correlationTypeList
                );
            }

            // save all attributes of this category into all poroducts,
            // that are related to it
            $products = $this->belib->getProductsOfCategory($categoryUid);
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
            $childList = [];
            $this->belib->getChildCategories($categoryUid, $childList, $categoryUid, 0, false);

            foreach ($childList as $childUid) {
                $this->saveCategoryRelations($childUid, [], true, false);
            }
        }
    }
}

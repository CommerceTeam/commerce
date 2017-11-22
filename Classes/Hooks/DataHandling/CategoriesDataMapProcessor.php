<?php
namespace CommerceTeam\Commerce\Hooks\DataHandling;

use CommerceTeam\Commerce\Domain\Repository\AttributeRepository;
use CommerceTeam\Commerce\Utility\BackendUserUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoriesDataMapProcessor extends AbstractDataMapProcessor
{
    /**
     * Category list.
     *
     * @var array
     */
    protected $catList = [];

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

        // @todo get category from dataHandler
        $this->catList = $this->belib->getUidListFromList($categories);

        return $this->catList;
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

                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $checkId);
                $category->loadData();

                // Use the l18n parent as category for permission checks.
                if ($l18nParent || $category->getField('l18n_parent') > 0) {
                    $checkId = $l18nParent ?: $category->getField('l18n_parent');
                    $category = GeneralUtility::makeInstance(
                        \CommerceTeam\Commerce\Domain\Model\Category::class,
                        $checkId
                    );
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

            // check if we are allowed to create new categories under the newly assigned
            // categories
            // check if we are allowed to remove this category from the parent categories
            // it was in before
            $existingParents = [];

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

                    // if the user has no right to see one of the parent categories or its not
                    // in the mounts it would miss afterwards
                    // by this its readded to the parent_category field
                    /** @var BackendUserUtility $backendUserUtility */
                    $backendUserUtility = GeneralUtility::makeInstance(BackendUserUtility::class);
                    if (!$category->isPermissionSet('show')
                        || !$backendUserUtility->isInWebMount($category->getUid())
                    ) {
                        $fieldArray['parent_category'] .= ',' . $category->getUid();
                    }
                }
            }

            // Unique the list
            $fieldArray['parent_category'] = implode(
                ',',
                $this->belib->getUidListFromList(
                    explode(',', GeneralUtility::uniqueList($fieldArray['parent_category']))
                )
            );

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
                    $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $uid);

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
                     * @var \CommerceTeam\Commerce\Domain\Model\Category $catDirect
                     */
                    $catDirect = GeneralUtility::makeInstance(
                        \CommerceTeam\Commerce\Domain\Model\Category::class,
                        $catUid
                    );
                    $catDirect->loadData();

                    $tmpCats = $catDirect->getParentCategories();
                    $tmpParents = null;
                    $i = 1000;

                    /** @var \CommerceTeam\Commerce\Domain\Model\Category $cat */
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

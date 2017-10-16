<?php
namespace CommerceTeam\Commerce\Tree;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class \CommerceTeam\Commerce\Tree\CategoryMounts
 */
class CategoryMounts extends \CommerceTeam\Commerce\Tree\Leaf\Mounts
{
    /**
     * Overwrite necessary variable.
     *
     * @var string
     */
    protected $field = 'tx_commerce_mountpoints';

    /**
     * Returns the Mountdata, but not just as an array with ids, but with an array
     * with arrays(id, category).
     *
     * @return array
     */
    public function getMountDataLabeled()
    {
        $backendUser = $this->getBackendUser();

        $this->resetPointer();

        // Walk the Mounts and create the tupels of 'uid' and 'label'
        $tupels = [];

        while (false !== ($id = $this->walk())) {
            // If the mount point is the root
            if ($id == 0) {
                $tupels[] = $backendUser->isAdmin() ?
                    [$id, $this->getLL('leaf.category.root')] :
                    [$id, $this->getLL('leaf.restrictedAccess')];
            } else {
                // Get the title
                /**
                 * Category.
                 *
                 * @var \CommerceTeam\Commerce\Domain\Model\Category $category
                 */
                $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $id);
                $category->loadData();

                $title = ($category->isPermissionSet('show') && $this->isInCommerceMounts($category->getUid())) ?
                    $category->getTitle() :
                    $this->getLL('leaf.restrictedAccess');

                $tupels[] = [$id, $title];
            }
        }

        $this->resetPointer();

        return $tupels;
    }

    /**
     * Returns false if the category is not in the categorymounts of the user.
     *
     * @param int $categoryUid Category uid
     *
     * @return bool Is in mounts?
     */
    public function isInCommerceMounts($categoryUid)
    {
        $backendUser = $this->getBackendUser();

        $categories = $this->getMountData();

        // is user admin? has mount 0? is parent category in mounts?
        if ($backendUser->isAdmin() || in_array(0, $categories) || in_array($categoryUid, $categories)) {
            return true;
        }

        // if the root is not a mount, return if we got here
        if ($categoryUid == 0) {
            return false;
        }

        // load the category and go up the tree until
        // we either reach a mount or we reach root
        /**
         * Category.
         *
         * @var \CommerceTeam\Commerce\Domain\Model\Category
         */
        $category = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Model\Category::class, $categoryUid);
        $category->loadData();

        $tmpCats = $category->getParentCategories();
        $tmpParents = null;
        $i = 1000;

        while (!is_null($category = @array_pop($tmpCats))) {
            // Prevent endless recursion
            if ($i < 0) {
                if (TYPO3_DLOG) {
                    GeneralUtility::devLog(
                        'isInCommerceMounts (categorymounts) has aborted because $i (' . $i .
                        ') has reached its allowed recursive maximum.',
                        'commerce',
                        3
                    );
                }

                return false;
            }

            // true if we can find any parent category of
            // this category in the commerce mounts
            if (in_array($category->getUid(), $categories)) {
                return true;
            }

            $tmpParents = $category->getParentCategories();

            if (is_array($tmpParents) && !empty($tmpParents)) {
                $tmpCats = array_merge($tmpCats, $tmpParents);
            }
            --$i;
        }

        return false;
    }
}

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

use CommerceTeam\Commerce\Domain\Repository\ArticleRepository;
use CommerceTeam\Commerce\Domain\Repository\BackendUserRepository;
use CommerceTeam\Commerce\Domain\Repository\CategoryRepository;
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update Class for DB Updates of version 0.11.0.
 *
 * Basically checks for the new Tree, if all records have a MM
 * relation to Record UID 0 if not, these records are created
 *
 * Class \CommerceTeam\Commerce\Utility\UpdateUtility
 */
class UpdateUtility
{
    /**
     * Performes the Updates
     * Outputs HTML Content.
     *
     * @return string
     */
    public function main()
    {
        $htmlCode = [];

        $htmlCode[] = 'This updates were performed successfully:
			<ul>';

        if ($this->isCategoryWithoutParentMm()) {
            $createdRelations = $this->createParentMmRecords();
            if ($createdRelations > 0) {
                $htmlCode[] = '<li>' . $createdRelations .
                    ' updated mm-Relations for the Category Records. <b>Please Check you Category Tree!</b></li>';
            }
        }

        if ($this->isCategoryWithoutUserrights()) {
            $createDefaultRights = $this->createDefaultRights();
            if ($createDefaultRights > 0) {
                $htmlCode[] = '<li>' . $createDefaultRights .
                    ' updated User-rights on categories. Set to rights on the commerce products folder</li>';
            }
        }

        if (!$this->isBackendUserSet()) {
            $createBackendUser = $this->createBackendUser();
            if ($createBackendUser) {
                $htmlCode[] = '<li>Default user created</li>';
            }
        }

        if ($this->isOldRelationTable()) {
            $this->renameRelationTable();
            $htmlCode[] = '<li>Renamed article-attribute relation table</li>';
        }

        if ($this->isOldColumns()) {
            $this->migrateOldColumn();
            $htmlCode[] = '<li>Migrated foldername column to new name</li>';
        }

        $htmlCode[] = '</ul>';

        return implode(LF, $htmlCode);
    }

    /**
     * Creates the missing MM records for
     * categories below the root (UID=0) element.
     *
     * @return int Num Records Changed
     */
    public function createParentMmRecords()
    {
        $countRecords = 0;
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        $result = $categoryRepository->findWithoutParentReference();
        while ($row = $result->fetch()) {
            $categoryRepository->insertParentRelation($row['uid'], 0, 99);
            $countRecords++;
        }

        return $countRecords;
    }

    /**
     * Sets the default user rights, based on the
     * User-Rights in the commerce-products folder.
     *
     * @return int
     */
    public function createDefaultRights()
    {
        $countRecords = 0;
        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        /*
         * Get data from folder
         */
        $page = $pageRepository->findByUid(FolderRepository::initFolders('Products', FolderRepository::initFolders()));
        $data = array_intersect_key($page, [
            'perms_userid' => '',
            'perms_groupid' => '',
            'perms_user' => '',
            'perms_group' => '',
            'perms_everybody' => '',
        ]);

        $result = $categoryRepository->findWithoutPermissionsSet();
        while ($row = $result->fetch()) {
            $categoryRepository->updateRecord($row['uid'], $data);
            $countRecords++;
        }

        return $countRecords;
    }

    /**
     * Creates the missing MM records for categories
     * below the root (UID=0) element.
     *
     * @return int
     */
    public function createBackendUser()
    {
        $userId = 0;
        /** @var BackendUserRepository $backendUserRepository */
        $backendUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class);

        $row = $backendUserRepository->findByUsername('_fe_commerce');
        if (empty($row)) {
            $userId = $backendUserRepository->insertUser([
                'pid' => 0,
                'username' => '_fe_commerce',
                'password' => md5(microtime() . uniqid() . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']),
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'crdate' => $GLOBALS['EXEC_TIME'],
            ]);
        }

        return $userId;
    }

    /**
     * Rename old article attribute relation table
     */
    public function renameRelationTable()
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        $articleRepository->migrateOldAttributeReferenceTable();
    }

    /**
     * Update pages and set tx_commerce_foldername to the same content as graytree_foldername
     */
    public function migrateOldColumn()
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pageRepository->migrateOldFolderColumns();
    }

    /**
     * Check if the Ipdate is necessary.
     *
     * @return bool True if update should be perfomed
     */
    public function access()
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('commerce')) {
            return false;
        }

        if ($this->isCategoryWithoutParentMm()) {
            return true;
        }
        if ($this->isCategoryWithoutUserrights()) {
            return true;
        }
        if (!$this->isBackendUserSet()) {
            return true;
        }
        if ($this->isOldRelationTable()) {
            return true;
        }
        if ($this->isOldColumns()) {
            return true;
        }

        return false;
    }

    /**
     * Check if category without parent mm relation is present.
     *
     * @return bool
     */
    protected function isCategoryWithoutParentMm()
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        $result = $categoryRepository->findWithoutParentReference();

        return $result->rowCount() > 0;
    }

    /**
     * Checks if category records without any user rights are present.
     *
     * @return bool
     */
    protected function isCategoryWithoutUserrights()
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        $result = $categoryRepository->findWithoutParentReference();

        return $result->rowCount() > 0;
    }

    /**
     * Check if backend user is set.
     *
     * @return int
     */
    protected function isBackendUserSet()
    {
        /** @var BackendUserRepository $backendUserRepository */
        $backendUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class);

        $row = $backendUserRepository->findByUsername('_fe_commerce');

        return empty($row);
    }

    /**
     * Check if an article attribute relation table is present
     *
     * @return bool
     */
    protected function isOldRelationTable()
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = GeneralUtility::makeInstance(ArticleRepository::class);
        return $articleRepository->hasTable('tx_commerce_articles_article_attributes_mm');
    }

    /**
     * Check if old columns need to be migrated
     *
     * @return bool
     */
    protected function isOldColumns()
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        // Check if old column is present
        $oldColumn = $pageRepository->hasColumn('tx_graytree_foldername');

        $newColumn = false;
        // Old column is present so check if new column is present too
        if ($oldColumn) {
            $newColumn = $pageRepository->hasColumn('tx_commerce_foldername');
        }

        $differingColumns = 0;
        // Old and new column are present so check if they differ
        if ($oldColumn && $newColumn) {
            $differingColumns = $pageRepository->countDifferingFolders();
        }

        return $differingColumns > 0;
    }
}

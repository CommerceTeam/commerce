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
use CommerceTeam\Commerce\Domain\Repository\FolderRepository;

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
        $database = $this->getDatabaseConnection();
        $countRecords = 0;

        $rows = $database->exec_SELECTgetRows(
            'uid',
            'tx_commerce_categories',
            'sys_language_uid = 0 AND l18n_parent = 0 AND uid NOT IN (
                SELECT uid_local FROM tx_commerce_categories_parent_category_mm
            ) AND tx_commerce_categories.deleted = 0'
        );
        foreach ($rows as $row) {
            $data = [
                'uid_local' => $row['uid'],
                'uid_foreign' => 0,
                'tablenames' => '',
                'sorting' => 99,
            ];

            $database->exec_INSERTquery('tx_commerce_categories_parent_category_mm', $data);
            ++$countRecords;
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
        $database = $this->getDatabaseConnection();
        $countRecords = 1;

        /*
         * Get data from folder
         */
        $data = $database->exec_SELECTgetSingleRow(
            'perms_userid, perms_groupid, perms_user, perms_group, perms_everybody',
            'pages',
            'uid = ' . FolderRepository::initFolders('Products', FolderRepository::initFolders())
        );

        $rows = $database->exec_SELECTgetRows(
            'uid',
            'tx_commerce_categories',
            'perms_user = 0 OR perms_group = 0 OR perms_everybody = 0'
        );
        foreach ($rows as $row) {
            $database->exec_UPDATEquery('tx_commerce_categories', 'uid = ' . $row['uid'], $data);
            ++$countRecords;
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
        $database = $this->getDatabaseConnection();

        $row = $database->exec_SELECTgetSingleRow('uid', 'be_users', 'username = \'_fe_commerce\'');
        if (empty($row)) {
            $data = [
                'pid' => 0,
                'username' => '_fe_commerce',
                'password' => 'MD5(RAND())',
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'crdate' => $GLOBALS['EXEC_TIME'],
            ];

            $database->exec_INSERTquery(
                'be_users',
                $data,
                [
                    'password',
                    'tstamp',
                    'crdate',
                ]
            );
            $userId = $this->getDatabaseConnection()->sql_insert_id();
        }

        return $userId;
    }

    /**
     * Rename old article attribute relation table
     *
     * @return void
     */
    public function renameRelationTable()
    {
        $this->getDatabaseConnection()->sql_query('
            ALTER TABLE tx_commerce_articles_article_attributes_mm RENAME tx_commerce_articles_attributes_mm;
        ');
    }

    /**
     * Update pages and set commerce_foldername to the same content as graytree_foldername
     *
     * @return void
     */
    public function migrateOldColumn()
    {
        $this->getDatabaseConnection()->sql_query('
            UPDATE pages SET tx_commerce_foldername = tx_graytree_foldername WHERE tx_graytree_foldername != \'\'
        ');
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
        $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_commerce_categories',
            'uid NOT IN (
                SELECT uid_local FROM tx_commerce_categories_parent_category_mm
            ) AND tx_commerce_categories.deleted = 0 AND sys_language_uid = 0 AND l18n_parent = 0'
        );

        return $count > 0;
    }

    /**
     * Checks if category records without any user rights are present.
     *
     * @return bool
     */
    protected function isCategoryWithoutUserrights()
    {
        $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_commerce_categories',
            'perms_user = 0 AND perms_group = 0 AND perms_everybody = 0'
        );

        return $count > 0;
    }

    /**
     * Check if backend user is set.
     *
     * @return int
     */
    protected function isBackendUserSet()
    {
        $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'be_users',
            'username = \'_fe_commerce\''
        );

        return $count > 0;
    }

    /**
     * Check if an article attribute relation table is present
     *
     * @return bool
     */
    protected function isOldRelationTable()
    {
        $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'information_schema.tables',
            'table_schema = \'' . $GLOBALS['TYPO3_CONF_VARS']['DB']['database']
            . '\' AND table_name = \'tx_commerce_articles_article_attributes_mm\''
        );

        return $count > 0;
    }

    /**
     * Check if old columns need to be migrated
     *
     * @return bool
     */
    protected function isOldColumns()
    {
        // Check if old column is present
        $oldColumn = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'information_schema.columns',
            'table_schema = \'' . $GLOBALS['TYPO3_CONF_VARS']['DB']['database']
            . '\' AND table_name = \'pages\' AND column_name = \'tx_graytree_foldername\''
        );

        $newColumn = 0;
        // Old column is present so check if new column is present too
        if ($oldColumn) {
            $newColumn = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                'information_schema.columns',
                'table_schema = \'' . $GLOBALS['TYPO3_CONF_VARS']['DB']['database']
                . '\' AND table_name = \'pages\' AND column_name = \'tx_commerce_foldername\''
            );
        }

        $differingColumns = 0;
        // Old and new column are present so check if they differ
        if ($oldColumn && $newColumn) {
            $differingColumns = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                'pages',
                'tx_graytree_foldername != \'\' AND tx_commerce_foldername != tx_graytree_foldername'
            );
        }

        return $differingColumns > 0;
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     * @deprecated since 6.0.0 will be removed in 7.0.0
     */
    protected function getDatabaseConnection()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        return $GLOBALS['TYPO3_DB'];
    }
}

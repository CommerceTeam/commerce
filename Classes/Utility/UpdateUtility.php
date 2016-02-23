<?php
namespace CommerceTeam\Commerce\Utility;

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

/**
 * Update Class for DB Updates of version 0.11.0.
 *
 * Basically checks for the new Tree, if all records have a MM
 * relation to Record UID 0 if not, these records are created
 *
 * Class \CommerceTeam\Commerce\Utility\UpdateUtility
 *
 * @author 2008-2011 Ingo Schmitt <is@marketing-factory.de>
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
        $htmlCode = array();

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

        $result = $database->exec_SELECTquery(
            'uid',
            'tx_commerce_categories',
            'sys_language_uid = 0 AND l18n_parent = 0 AND uid NOT IN (
                SELECT uid_local FROM tx_commerce_categories_parent_category_mm
            ) AND tx_commerce_categories.deleted = 0'
        );
        while (($row = $database->sql_fetch_assoc($result))) {
            $data = array(
                'uid_local' => $row['uid'],
                'uid_foreign' => 0,
                'tablenames' => '',
                'sorting' => 99,
            );

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
            'uid = ' . BackendUtility::getProductFolderUid()
        );

        $result = $database->exec_SELECTquery(
            'uid',
            'tx_commerce_categories',
            'perms_user = 0 OR perms_group = 0 OR perms_everybody = 0'
        );
        while (($row = $database->sql_fetch_assoc($result))) {
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

        $result = $database->exec_SELECTquery('uid', 'be_users', 'username = \'_fe_commerce\'');
        if (!$database->sql_num_rows($result)) {
            $data = array(
                'pid' => 0,
                'username' => '_fe_commerce',
                'password' => 'MD5(RAND())',
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'crdate' => $GLOBALS['EXEC_TIME'],
            );

            $database->exec_INSERTquery(
                'be_users',
                $data,
                array(
                    'password',
                    'tstamp',
                    'crdate',
                )
            );
            $userId = $this->getDatabaseConnection()->sql_insert_id();
        }

        return $userId;
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

        return false;
    }

    /**
     * Check if category without parent mm relation is present.
     *
     * @return bool
     */
    protected function isCategoryWithoutParentMm()
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'COUNT(uid) AS count',
            'tx_commerce_categories',
            'uid NOT IN (
                SELECT uid_local FROM tx_commerce_categories_parent_category_mm
            ) AND tx_commerce_categories.deleted = 0 AND sys_language_uid = 0 AND l18n_parent = 0'
        );

        return $row['count'] > 0;
    }

    /**
     * Checks if category records without any user rights are present.
     *
     * @return bool
     */
    protected function isCategoryWithoutUserrights()
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'COUNT(uid) AS count',
            'tx_commerce_categories',
            'perms_user = 0 AND perms_group = 0 AND perms_everybody = 0'
        );

        return $row['count'] > 0;
    }

    /**
     * Check if backend user is set.
     *
     * @return int
     */
    protected function isBackendUserSet()
    {
        return !empty($this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            'be_users',
            'username = \'_fe_commerce\''
        ));
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

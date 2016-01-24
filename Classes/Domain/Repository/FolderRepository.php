<?php
namespace CommerceTeam\Commerce\Domain\Repository;

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
 * Misc commerce db functions.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\FolderRepository
 *
 * @author 2008-2011 Eric Frister <ef@marketing-factory.de>
 */
class FolderRepository
{
    /**
     * Find the extension folders or create one.
     *
     * @param string $title Folder Title as named in pages table
     * @param string $module Extension Moduke
     * @param int $pid Parent Page id
     * @param bool $parentTitle Deprecated parameter do not use it to create folders on the fly
     * @param bool $executeUpdateUtility Deprecated parameter
     *
     * @return int
     */
    public static function initFolders(
        $title = 'Commerce',
        $module = 'commerce',
        $pid = 0,
        $parentTitle = false,
        $executeUpdateUtility = true
    ) {
        if ($parentTitle) {
            GeneralUtility::deprecationLog(
                'Creating parent folder is not supported anymore. Please change your code to use createFolder'
            );
        }

        $folder = self::getFolder($module, $pid, $title);
        if (empty($folder)) {
            self::createFolder($title, $module, $pid);
            $folder = self::getFolder($module, $pid, $title);
        }

        // @todo move to FolderUtility
        if ($executeUpdateUtility) {
            GeneralUtility::deprecationLog(
                'Executing update utility is not supported anymore. Please change your code to call it on your own'
            );
        }

        return (int)$folder['uid'];
    }

    /**
     * Find the extension folders.
     *
     * @param string $module Module
     * @param int $pid Page id
     * @param string $title Title
     *
     * @return array rows of found extension folders
     * @deprecated since Version 5 will be removed in 6. Please use only getFolder instead
     */
    public static function getFolders($module = 'commerce', $pid = 0, $title = '') {
        GeneralUtility::logDeprecatedFunction();
        $row = self::getFolder($module, $pid, $title);
        return isset($row['uid']) ? array($row['uid'] => $row) : array();
    }

    /**
     * Find folder by module and title takes pid into account.
     *
     * @param string $module Module
     * @param int $pid Page id
     * @param string $title Title
     *
     * @return array rows of found extension folders
     */
    public static function getFolder($module = 'commerce', $pid = 0, $title = '')
    {
        $row = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid, pid, title',
            'pages',
            'doktype = 254 AND tx_commerce_foldername = \'' . strtolower($title) . '\' AND pid = ' . (int) $pid .
            ' AND module=\'' . $module . '\' ' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages')
        );

        return (array) $row;
    }

    /**
     * Create your database table folder
     * overwrite this if wanted.
     *
     * @param string $title Title
     * @param string $module Module
     * @param int $pid Page id
     *
     * @return int
     *
     * @todo get title from extkey
     * @todo sorting
     */
    protected function createFolder($title = 'Commerce', $module = 'commerce', $pid = 0)
    {
        self::getDatabaseConnection()->exec_INSERTquery(
            'pages',
            array(
                'sorting' => 10111,
                'perms_user' => 31,
                'perms_group' => 31,
                'perms_everybody' => 31,
                'doktype' => 254,
                'pid' => $pid,
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'title' => $title,
                'tx_commerce_foldername' => strtolower($title),
                'module' => $module,
            )
        );

        return self::getDatabaseConnection()->sql_insert_id();
    }


    /**
     * Get database connection.
     *
     * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

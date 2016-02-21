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
     * Cache of page ids
     *
     * @var array
     */
    protected static $folderIds = array();

    /**
     * Find the extension folders.
     *
     * @param string $title Folder title as named in pages table
     * @param int $pid Parent Page id
     * @param string $module Extension module
     * @param bool $parentTitle Deprecated parameter do not use it to create folders on the fly
     * @param bool $executeUpdateUtility Deprecated parameter
     *
     * @return int
     */
    public static function initFolders(
        $title = 'Commerce',
        $pid = 0,
        $module = 'commerce',
        $parentTitle = false,
        $executeUpdateUtility = true
    ) {
        if ($parentTitle) {
            GeneralUtility::deprecationLog(
                'Creating parent folder is not supported anymore. Please change your code to use createFolder.
                    Parameter will get removed in version 6.'
            );
        }

        if ($executeUpdateUtility) {
            GeneralUtility::deprecationLog(
                'Executing update utility is not supported anymore. Please change your code to call it on your own.
                    Parameter will get removed in version 6.'
            );
        }

        if (is_string($pid) && is_int($module)) {
            GeneralUtility::deprecationLog(
                'Parameter $pid and $module swapped position. Fallback handling will get removed in version 6.'
            );
            $temp = $pid;
            $pid = $module;
            $module = $temp;
            unset($temp);
        }
        $cacheHash = $title . '|' . $module . '|' . $pid;
        if (isset(static::$folderIds[$cacheHash])) {
            return static::$folderIds[$cacheHash];
        }

        $folder = self::getFolder($module, $pid, $title);
        if (empty($folder)) {
            self::createFolder($title, $module, $pid);
            $folder = self::getFolder($module, $pid, $title);
        }

        static::$folderIds[$cacheHash] = (int)$folder['uid'];
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
    public static function getFolders($module = 'commerce', $pid = 0, $title = '')
    {
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
     */
    protected function createFolder($title = 'Commerce', $module = 'commerce', $pid = 0)
    {
        $sorting = self::getDatabaseConnection()->exec_SELECTgetRows(
            'sorting',
            'pages',
            'pid = ' . $pid,
            '',
            'sorting DESC',
            1,
            'sorting'
        );

        self::getDatabaseConnection()->exec_INSERTquery(
            'pages',
            array(
                'sorting' => $sorting ? $sorting + 1 : 10111,
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

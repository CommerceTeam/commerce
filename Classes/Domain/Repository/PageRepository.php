<?php
namespace CommerceTeam\Commerce\Domain\Repository;

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

/**
 * Class \CommerceTeam\Commerce\Domain\Repository\PageRepository
 */
class PageRepository extends AbstractRepository
{
    /**
     * Database table concerning the data.
     *
     * @var string
     */
    protected $databaseTable = 'pages';

    /**
     * @param int $parentId
     *
     * @return array
     */
    public function findByPid($parentId)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($parentId, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * Find folder by uid that is editable.
     *
     * @param int $uid Page uid
     *
     * @return array
     */
    public function findEditFolderByUid($uid)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('tx_commerce_foldereditorder')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_commerce_foldereditorder',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return is_array($result) ? $result : [];
    }

    /**
     * Find all sys language uids of page overlay for given page
     *
     * @param int $pageUid
     *
     * @return array
     */
    public function findLanguageUidsByUid($pageUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable('pages_language_overlay');
        $result = $queryBuilder
            ->select('sys_language_uid')
            ->from('pages_language_overlay')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        $locale = [];
        while ($row = $result->fetch()) {
            $locale[$row['sys_language_uid']] = $row;
        }

        return $locale;
    }

    /**
     * @return int
     */
    public function countDifferingFolders()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = (int) $queryBuilder
            ->count('p.*')
            ->from($this->databaseTable, 'p')
            ->where(
                $queryBuilder->expr()->neq(
                    'p.tx_graytree_foldername',
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->neq(
                    'p.tx_commerce_foldername',
                    'p.tx_graytree_foldername'
                )
            )
            ->execute()
            ->fetchColumn();
        return $result;
    }

    public function migrateOldFolderColumns()
    {
        $queryBuilder = $this->getQueryBuilderForTable('pages');
        $queryBuilder
            ->update('pages', 'p')
            ->where(
                $queryBuilder->expr()->neq(
                    'p.tx_graytree_foldername',
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                )
            )
            ->set('p.tx_commerce_foldername', 'p.tx_graytree_foldername')
            ->execute();
    }
}

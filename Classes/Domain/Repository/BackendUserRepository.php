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
 * Database Class for be_users. All database calls should
 * be made by this class. In most cases you should use the methodes
 * provided by tx_commerce_product to get informations for articles.
 *
 * Class \CommerceTeam\Commerce\Domain\Repository\BackendUsergroupRepository
 */
class BackendUserRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'be_users';

    /**
     * @param array $groupList
     *
     * @return array
     */
    public function findByGroupList($groupList)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('tx_commerce_mountpoints')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $groupList
                )
            )
            ->execute()
            ->fetchAll();
        return is_array($result) ? $result : [];
    }

    /**
     * @param string $username
     *
     * @return array
     */
    public function findByUsername($username)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $result = $queryBuilder
            ->select('*')
            ->from($this->databaseTable)
            ->where(
                $queryBuilder->expr()->in(
                    'username',
                    $username
                )
            )
            ->execute()
            ->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function insertUser(array $data)
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->insert($this->databaseTable);

        foreach ($data as $field => $value) {
            $queryBuilder->set($field, $value);
        }

        $queryBuilder->execute();
        return $queryBuilder->getConnection()->lastInsertId();
    }
}

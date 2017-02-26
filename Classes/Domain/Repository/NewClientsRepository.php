<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/**
 * Class NewClientsRepository
 *
 * @package CommerceTeam\Commerce\Domain\Repository
 */
class NewClientsRepository extends AbstractRepository
{
    /**
     * @return void
     */
    public function truncate()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getConnection()->truncate($this->databaseTable);
    }
}

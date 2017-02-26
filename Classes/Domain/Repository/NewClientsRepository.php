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
     * @var string
     */
    protected $databaseTable = 'tx_commerce_newclients';

    /**
     * @return void
     */
    public function truncate()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getConnection()->truncate($this->databaseTable);
    }

    /**
     * @return int
     */
    public function findHighestTimestamp()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        return (int) $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('tstamp')
            )
            ->from($this->databaseTable)
            ->execute()
            ->fetchColumn();
    }
}

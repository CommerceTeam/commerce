<?php
namespace CommerceTeam\Commerce\Domain\Repository;

/**
 * Class SalesFiguresRepository
 *
 * @package CommerceTeam\Commerce\Domain\Repository#
 */
class SalesFiguresRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $databaseTable = 'tx_commerce_salesfigures';

    /**
     * @return void
     */
    public function truncate()
    {
        $queryBuilder = $this->getQueryBuilderForTable($this->databaseTable);
        $queryBuilder->getConnection()->truncate($this->databaseTable);
    }
}

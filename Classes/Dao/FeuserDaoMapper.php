<?php
namespace CommerceTeam\Commerce\Dao;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;

/**
 * This class used by the Dao for database storage.
 * It extends the basic Dao mapper.
 *
 * Class \CommerceTeam\Commerce\Dao\FeuserDaoMapper
 */
class FeuserDaoMapper extends BasicDaoMapper
{
    /**
     * Table for persistence.
     *
     * @var string
     */
    protected $dbTable = 'fe_users';

    /**
     * Initialization.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->createPid = ConfigurationUtility::getInstance()->getExtConf('create_feuser_pid');
    }
}

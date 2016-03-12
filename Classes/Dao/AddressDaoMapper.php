<?php
namespace CommerceTeam\Commerce\Dao;

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

use CommerceTeam\Commerce\Utility\ConfigurationUtility;

/**
 * Address Dao mapping
 * This class used by the Dao for database storage.
 * It extends the basic Dao mapper.
 *
 * Class \CommerceTeam\Commerce\Dao\AddressDaoMapper
 */
class AddressDaoMapper extends BasicDaoMapper
{
    /**
     * Table for persistence.
     *
     * @var string
     */
    protected $dbTable = 'tt_address';

    /**
     * Initialization.
     *
     * @return void
     */
    protected function init()
    {
        parent::init();
        $this->createPid = ConfigurationUtility::getInstance()->getExtConf('create_address_pid');
    }
}

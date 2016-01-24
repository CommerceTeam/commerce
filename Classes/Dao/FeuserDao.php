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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class handles object persistence using the Dao design pattern.
 * It extends the basic Dao.
 *
 * Class \CommerceTeam\Commerce\Dao\FeuserDao
 *
 * @author 2005-2011 Carsten Lausen <cl@e-netconsulting.de>
 */
class FeuserDao extends BasicDao
{
    /**
     * Initialization.
     *
     * @return void
     */
    protected function init()
    {
        $this->parser = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Dao\FeuserDaoParser::class);
        $this->mapper = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Dao\FeuserDaoMapper::class, $this->parser);
        $this->object = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Dao\FeuserDaoObject::class);
    }
}

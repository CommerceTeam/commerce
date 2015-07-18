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
     */
    protected function init()
    {
        $this->parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Dao\\FeuserDaoParser');
        $this->mapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'CommerceTeam\\Commerce\\Dao\\FeuserDaoMapper',
            $this->parser
        );
        $this->object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('CommerceTeam\\Commerce\\Dao\\FeuserDaoObject');
    }
}

<?php
namespace CommerceTeam\Commerce\Hooks;

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
 * Hook for the extension takeaday feuser
 * The method registrationProcess_afterSaveCreate() is called by save()
 * The method registrationProcess_afterSaveEdit() is called by save().
 *
 * This class handles frontend feuser updates
 *
 * Class \CommerceTeam\Commerce\Hook\SrfeuserregisterPi1Hook
 */
class SrfeuserregisterPi1Hook
{
    /**
     * After save create.
     *
     * Sr_feuser_register registration process after saving new dataset
     *
     * @param string $_ Table
     * @param array $dataArray Complete array of feuser fields
     *
     * @return void
     */
    public function registrationProcess_afterSaveCreate($_, array $dataArray)
    {
        // notify observer
        \CommerceTeam\Commerce\Dao\FeuserObserver::update('new', $dataArray['uid']);
    }

    /**
     * After edit create.
     *
     * Sr_feuser_register registration process after saving edited dataset
     *
     * @param string $_ Table
     * @param array $dataArray Complete array of feuser fields
     *
     * @return void
     */
    public function registrationProcess_afterSaveEdit($_, array $dataArray)
    {
        // notify observer
        \CommerceTeam\Commerce\Dao\FeuserObserver::update('update', $dataArray['uid']);
    }
}
